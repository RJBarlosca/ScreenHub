<?php
session_start();
// index.php only serves the HTML. Login is handled by login.php via AJAX (login.js).
if (isset($_SESSION['username'])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ScreenHub | Sign In</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="screenhub-auth">
    <div class="screenhub-bg"></div>
    <div class="screenhub-wrapper">
        <div class="screenhub-logo">SCREENHUB</div>
        <div class="screenhub-card">
            <h2>Sign In</h2>

            <?php if (!empty($_GET['success'])): ?>
            <div style="background:rgba(39,174,96,0.15);border:1px solid rgba(39,174,96,0.4);color:#2ecc71;padding:0.8rem 1rem;border-radius:10px;margin-bottom:1rem;font-size:0.88rem;text-align:center;">
                ✓ <?php echo htmlspecialchars($_GET['success']); ?>
            </div>
            <?php endif; ?>

            <!-- LOGIN FORM: id="loginForm" and id="loginError" required by login.js -->
            <form id="loginForm" autocomplete="off">
                <div class="screenhub-field">
                    <input type="text" id="username" name="username" placeholder=" " required>
                    <label>Username or Email</label>
                </div>
                <div class="screenhub-field password-field">
                    <input type="password" id="password" name="password" placeholder=" " required>
                    <label>Password</label>
                    <span class="toggle-eye" onclick="togglePass('password', this)"><i class="fa-solid fa-eye"></i></span>
                </div>
                <div id="loginError" style="color:#ff6b6b; font-size:0.85rem; margin-bottom:8px; min-height:18px;"></div>
                <button type="submit" class="screenhub-btn">Sign In</button>
            </form>

            <!-- Forgot link sits OUTSIDE forgotContainer so it's always visible -->
            <div class="forgot-link" id="forgotLink" onclick="toggleForgot()">Forgot Password?</div>

            <!-- FORGOT PASSWORD: hidden by default, revealed by toggleForgot() -->
            <div class="forgot-password-container" id="forgotContainer" style="display:none;">
                <!-- Step 1: Enter email -->
                <div class="screenhub-field forgot-email-field">
                    <input type="email" id="forgotEmail" placeholder=" ">
                    <label>Email Address</label>
                </div>
                <button type="button" class="forgot-btn" id="sendResetBtn">Send Code</button>

                <!-- Step 2: Enter code (shown after email sent) -->
                <div id="codeSection" style="display:none; margin-top:15px;">
                    <div class="screenhub-field">
                        <input type="text" id="codeInput" maxlength="4" placeholder=" ">
                        <label>4-Digit Code</label>
                    </div>
                    <button type="button" class="forgot-btn" id="verifyCodeBtn">Verify Code</button>
                </div>

                <!-- Step 3: New password (shown after code verified) -->
                <div id="passwordSection" style="display:none; margin-top:15px;">
                    <div class="screenhub-field password-field">
                        <input type="password" id="newPass" placeholder=" ">
                        <label>New Password</label>
                        <span class="toggle-eye" onclick="togglePass('newPass', this)"><i class="fa-solid fa-eye"></i></span>
                    </div>
                    <div class="screenhub-field password-field">
                        <input type="password" id="confirmPass" placeholder=" ">
                        <label>Confirm Password</label>
                        <span class="toggle-eye" onclick="togglePass('confirmPass', this)"><i class="fa-solid fa-eye"></i></span>
                    </div>
                    <button type="button" class="screenhub-btn" id="updatePassBtn">Update Password</button>
                </div>

                <div class="forgot-status" id="forgotStatus" style="display:none;"></div>
            </div>

            <p class="screenhub-link">New to ScreenHub? <a href="signup.php">Create account</a></p>
        </div>
    </div>

    <script src="login.js"></script>
    <script>
        function togglePass(id, el) {
            const input = document.getElementById(id);
            const icon = el.querySelector("i");
            input.type = input.type === "password" ? "text" : "password";
            icon.classList.toggle("fa-eye");
            icon.classList.toggle("fa-eye-slash");
        }
        function toggleForgot() {
            const container = document.getElementById('forgotContainer');
            const link = document.getElementById('forgotLink');
            const isOpen = container.style.display === 'block';
            container.style.display = isOpen ? 'none' : 'block';
            link.textContent = isOpen ? 'Forgot Password?' : 'Back to Sign In';
        }
    </script>
</body>
</html>
