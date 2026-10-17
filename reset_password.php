<?php
session_start();
require_once 'db.php';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$error = $success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $newPass = trim($_POST['new_password']);
    $confirmPass = trim($_POST['confirm_password']);

    if (strlen($newPass) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($newPass !== $confirmPass) {
        $error = 'Passwords do not match.';
    } else {
        // Verify token
        $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE reset_token = ?");
            $stmt->bind_param("ss", $hashed, $token);
            if ($stmt->execute()) {
                $success = 'Password updated! <a href="index.php">Sign in</a>';
            } else {
                $error = 'Update failed. Try again.';
            }
        } else {
            $error = 'Invalid or expired token.';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ScreenHub | Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="screenhub-auth">
    <div class="screenhub-bg"></div>
    <div class="screenhub-wrapper">
        <div class="screenhub-logo">SCREENHUB</div>
        <div class="screenhub-card">
            <h2>Reset Password</h2>
            <?php if ($error): ?>
                <div style="background: rgba(231, 76, 60, 0.2); color: #e74c3c; padding: 15px; border-radius: 6px; margin-bottom: 20px; text-align: center; border: 1px solid rgba(231, 76, 60, 0.4);"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div style="background: rgba(46, 204, 113, 0.2); color: #2ecc71; padding: 15px; border-radius: 6px; margin-bottom: 20px; text-align: center; border: 1px solid rgba(46, 204, 113, 0.4);"><?php echo $success; ?></div>
            <?php else: ?>
                <form method="POST" autocomplete="off">
                    <div class="screenhub-field password-field">
                        <input type="password" name="new_password" id="newPass" placeholder=" " required>
                        <label>New Password</label>
                        <span class="toggle-eye" onclick="togglePass('newPass', this)"><i class="fa-solid fa-eye"></i></span>
                    </div>
                    <div class="screenhub-field password-field">
                        <input type="password" name="confirm_password" id="confirmPass" placeholder=" " required>
                        <label>Confirm Password</label>
                        <span class="toggle-eye" onclick="togglePass('confirmPass', this)"><i class="fa-solid fa-eye"></i></span>
                    </div>
                    <button type="submit" class="screenhub-btn">Update Password</button>
                </form>
                <p class="screenhub-link"><a href="index.php">Back to Sign In</a></p>
            <?php endif; ?>
        </div>
    </div>
    <script>
        function togglePass(id, el) {
            const input = document.getElementById(id);
            const icon = el.querySelector('i');
            input.type = input.type === 'password' ? 'text' : 'password';
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        }
    </script>
</body>
</html>

