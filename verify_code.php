<?php
session_start();
header('Content-Type: application/json');
require_once 'db.php';

$input    = json_decode(file_get_contents('php://input'), true);
$email    = trim($input['email']    ?? '');
$code     = trim($input['code']     ?? '');
$password = $input['password']      ?? '';

if (!$email || !$code) {
    echo json_encode(["status" => "error", "message" => "Missing email or code."]);
    exit();
}

// -------------------------------------------------------
// STEP 2 — Verify the 4-digit code only (no password yet)
// -------------------------------------------------------
if (empty($password)) {
    $stmt = $conn->prepare(
        "SELECT id FROM users WHERE email = ? AND reset_code = ? AND reset_code_expires > NOW()"
    );
    $stmt->bind_param("ss", $email, $code);
    $stmt->execute();
    $stmt->store_result(); // required for num_rows to work

    if ($stmt->num_rows === 1) {
        $_SESSION['reset_verified_email'] = $email;
        $_SESSION['reset_verified_code']  = $code;
        echo json_encode(["status" => "success", "message" => "Code verified!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid or expired code. Please try again."]);
    }
    $stmt->close();
    exit();
}

// -------------------------------------------------------
// STEP 3 — Code + new password → update the password
// -------------------------------------------------------
if (strlen($password) < 8) {
    echo json_encode(["status" => "error", "message" => "Password must be at least 8 characters."]);
    exit();
}

// Re-verify the code before updating (security: can't skip step 2)
$stmt = $conn->prepare(
    "SELECT id FROM users WHERE email = ? AND reset_code = ? AND reset_code_expires > NOW()"
);
$stmt->bind_param("ss", $email, $code);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows !== 1) {
    echo json_encode(["status" => "error", "message" => "Invalid or expired code."]);
    $stmt->close();
    exit();
}
$stmt->close();

$hashed = password_hash($password, PASSWORD_DEFAULT);
$stmt2  = $conn->prepare(
    "UPDATE users SET password = ?, reset_code = NULL, reset_code_expires = NULL WHERE email = ?"
);
$stmt2->bind_param("ss", $hashed, $email);

if ($stmt2->execute()) {
    unset($_SESSION['reset_verified_email'], $_SESSION['reset_verified_code']);
    echo json_encode(["status" => "success", "message" => "Password updated! You can now sign in."]);
} else {
    echo json_encode(["status" => "error", "message" => "Database error. Please try again."]);
}
$stmt2->close();
?>
