<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

// ==============================================================
//  SETUP — fill in your Gmail before using:
//
//  1. Set SCREENHUB_GMAIL_USER in the PHP environment.
//  2. Set SCREENHUB_GMAIL_PASS to a Gmail App Password in the PHP environment.
//       Gmail → Google Account → Security
//       → 2-Step Verification (turn ON first)
//       → App Passwords → create one → paste the 16 chars below
//       (Do NOT use your normal Gmail password — it won't work)
// ==============================================================
$GMAIL_USER = getenv('SCREENHUB_GMAIL_USER') ?: '';
$GMAIL_PASS = getenv('SCREENHUB_GMAIL_PASS') ?: '';

// Read JSON body
$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Please enter a valid email address.']);
    exit();
}

// Check email exists in DB
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();
$exists = $stmt->num_rows > 0;
$stmt->close();

if (!$exists) {
    echo json_encode(['status' => 'success', 'message' => 'If that email is registered, a code has been sent.']);
    exit();
}

// Generate 4-digit code, save to DB
$code    = str_pad((string)rand(1000, 9999), 4, '0', STR_PAD_LEFT);
// Let MySQL calculate expiry using its own clock — fixes timezone mismatch

$stmt = $conn->prepare("UPDATE users SET reset_code = ?, reset_code_expires = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE email = ?");
$stmt->bind_param("ss", $code, $email);
if (!$stmt->execute()) {
    echo json_encode(['status' => 'error', 'message' => 'Database error. Please try again.']);
    exit();
}
$stmt->close();

// ==============================================================
//  Send via Gmail SMTP — no Composer / no PHPMailer needed
// ==============================================================

// Read ALL lines of a multi-line SMTP response (e.g. EHLO returns many lines).
// Returns the last line (which has a space after the code, not a dash).
function smtp_read($sock) {
    $last = '';
    while (true) {
        $line = fgets($sock, 512);
        if ($line === false) break;
        $last = $line;
        // Multi-line responses use "250-...", final line uses "250 ..."
        if (strlen($line) >= 4 && $line[3] === ' ') break;
    }
    return $last;
}

// Send one command and read (and drain) the full server response.
function smtp_cmd($sock, $cmd) {
    fputs($sock, $cmd . "\r\n");
    return smtp_read($sock);
}

function smtp_send($from, $fromName, $to, $subject, $htmlBody, $smtpUser, $smtpPass) {
    $host = 'smtp.gmail.com';

    $boundary  = md5(time());
    $plainText = strip_tags($htmlBody);

    $headers =
        "From: {$fromName} <{$from}>\r\n" .
        "To: <{$to}>\r\n" .
        "Subject: {$subject}\r\n" .
        "MIME-Version: 1.0\r\n" .
        "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";

    $body =
        "--{$boundary}\r\n" .
        "Content-Type: text/plain; charset=UTF-8\r\n\r\n" .
        $plainText . "\r\n" .
        "--{$boundary}\r\n" .
        "Content-Type: text/html; charset=UTF-8\r\n\r\n" .
        $htmlBody . "\r\n" .
        "--{$boundary}--";

    $raw = $headers . "\r\n" . $body;

    // ── Try SSL on port 465 first, fall back to STARTTLS on 587 ──
    $sock = @fsockopen('ssl://' . $host, 465, $errno, $errstr, 15);
    if ($sock) {
        smtp_read($sock);                    // 220 greeting
        smtp_cmd($sock, 'EHLO localhost');   // drain all EHLO lines
    } else {
        $sock = @fsockopen($host, 587, $errno, $errstr, 15);
        if (!$sock) return ['ok' => false, 'error' => "Cannot connect to Gmail SMTP: $errstr ($errno)"];
        smtp_read($sock);                          // 220 greeting
        smtp_cmd($sock, 'EHLO localhost');         // drain EHLO
        $r = smtp_cmd($sock, 'STARTTLS');
        if (strpos($r, '220') === false) {
            fclose($sock);
            return ['ok' => false, 'error' => 'STARTTLS rejected: ' . trim($r)];
        }
        stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        smtp_cmd($sock, 'EHLO localhost');         // re-introduce after TLS
    }

    // ── AUTH LOGIN — each step gets its own 334 challenge ──
    $r = smtp_cmd($sock, 'AUTH LOGIN');
    if (strpos($r, '334') === false) {
        fclose($sock);
        return ['ok' => false, 'error' => 'AUTH LOGIN not accepted: ' . trim($r)];
    }

    $r = smtp_cmd($sock, base64_encode($smtpUser));   // send username
    if (strpos($r, '334') === false) {
        fclose($sock);
        return ['ok' => false, 'error' => 'Username rejected: ' . trim($r)];
    }

    $r = smtp_cmd($sock, base64_encode($smtpPass));   // send password
    if (strpos($r, '235') === false) {
        fclose($sock);
        return ['ok' => false, 'error' => 'Password rejected (check your Gmail App Password): ' . trim($r)];
    }

    // ── Envelope + data ──
    smtp_cmd($sock, "MAIL FROM:<{$from}>");
    smtp_cmd($sock, "RCPT TO:<{$to}>");
    smtp_cmd($sock, 'DATA');
    fputs($sock, $raw . "\r\n.\r\n");
    $dataResp = smtp_read($sock);
    smtp_cmd($sock, 'QUIT');
    fclose($sock);

    if (strpos($dataResp, '250') !== false) {
        return ['ok' => true];
    }
    return ['ok' => false, 'error' => 'Message rejected by Gmail: ' . trim($dataResp)];
}

// Build the email HTML
$html = "
<html>
<body style='font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:0;'>
  <table width='100%' cellpadding='0' cellspacing='0' style='background:#f4f4f4;padding:40px 0;'>
    <tr><td align='center'>
      <table width='480' cellpadding='0' cellspacing='0' style='background:#fff;border-radius:12px;overflow:hidden;'>
        <tr><td style='background:#e50914;padding:28px 40px;'>
          <h1 style='color:#fff;margin:0;font-size:24px;letter-spacing:2px;'>SCREENHUB</h1>
        </td></tr>
        <tr><td style='padding:40px;'>
          <h2 style='margin:0 0 16px;color:#111;font-size:20px;'>Password Reset Code</h2>
          <p style='color:#555;margin:0 0 28px;'>Enter this 4-digit code on the reset page. It expires in <strong>10 minutes</strong>.</p>
          <div style='background:#e50914;color:#fff;font-size:48px;font-weight:bold;letter-spacing:20px;text-align:center;padding:28px 0;border-radius:10px;margin-bottom:28px;'>$code</div>
          <p style='color:#888;font-size:13px;'>If you didn't request a password reset, ignore this email.</p>
        </td></tr>
        <tr><td style='background:#f9f9f9;padding:16px 40px;text-align:center;'>
          <p style='color:#bbb;font-size:12px;margin:0;'>&copy; ScreenHub</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>";

$result = smtp_send(
    $GMAIL_USER,
    'ScreenHub',
    $email,
    'ScreenHub — Your Password Reset Code',
    $html,
    $GMAIL_USER,
    $GMAIL_PASS
);

if ($result['ok']) {
    echo json_encode(['status' => 'success', 'message' => 'Code sent! Check your inbox (and spam folder). Expires in 10 minutes.']);
} else {
    // Log the real error, return a helpful message
    error_log('ScreenHub SMTP error: ' . $result['error']);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Could not send email. Make sure you set your Gmail and App Password in forgot_password.php. Error: ' . $result['error']
    ]);
}
?>
