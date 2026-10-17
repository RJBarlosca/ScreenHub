<?php
session_start();
$servername = "localhost"; $username = "root"; $password = ""; $dbname = "screenhub_db";
$conn = new mysqli("127.0.0.1", "root", "", "screenhub_db", 3306);

$error = '';
$user = $email = $first = $last = $middle = $bday = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user   = trim($_POST['username']    ?? '');
    $email  = trim($_POST['email']       ?? '');
    $first  = trim($_POST['first_name']  ?? '');
    $middle = trim($_POST['middle_name'] ?? '');
    $last   = trim($_POST['last_name']   ?? '');
    $bday   = $_POST['birthdate']        ?? '';
    $pass   = $_POST['password']         ?? '';
    $confirm = $_POST['confirmPassword'] ?? '';

    if (empty($first) || empty($last))
        $error = 'First and last name are required.';
    elseif (empty($bday) || strtotime($bday) > strtotime('-18 years'))
        $error = 'You must be 18 or older to register.';
    elseif (strlen($pass) < 8)
        $error = 'Password must be at least 8 characters.';
    elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $pass))
        $error = 'Password needs uppercase, lowercase, number and special character.';
    elseif ($pass !== $confirm)
        $error = 'Passwords do not match.';
    else {
        $stmt = $conn->prepare('SELECT id FROM users WHERE username=? OR email=?');
        $stmt->bind_param('ss', $user, $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Username or email already exists.';
        } else {
            $stmt->close();
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO users (username, first_name, middle_name, last_name, email, birthdate, password) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('sssssss', $user, $first, $middle, $last, $email, $bday, $hashed);
            if ($stmt->execute()) {
                header('Location: index.php?success=Account+created+successfully!+Please+sign+in.');
                exit();
            } else {
                $error = 'Database error. Please try again.';
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ScreenHub | Sign Up</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="screenhub-auth">
    <div class="screenhub-bg"></div>
    <div class="screenhub-wrapper">
        <div class="screenhub-logo">SCREENHUB</div>
        <div class="screenhub-card">
            <h2>Create Account</h2>

            <?php if ($error): ?>
            <div style="background:rgba(255,71,87,0.15);border:1px solid rgba(255,71,87,0.4);color:#ff6b6b;padding:0.8rem 1rem;border-radius:10px;margin-bottom:1rem;font-size:0.88rem;text-align:center;">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <div class="screenhub-field">
                    <input type="text" name="username" value="<?php echo htmlspecialchars($user); ?>" placeholder=" " required>
                    <label>Username</label>
                </div>
                <div class="screenhub-field">
                    <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" placeholder=" " required>
                    <label>Email</label>
                </div>

                <div style="display:flex; gap:10px;">
                    <div class="screenhub-field" style="flex:1;">
                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($first); ?>" placeholder=" " required>
                        <label>First Name</label>
                    </div>
                    <div class="screenhub-field" style="flex:1;">
                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($last); ?>" placeholder=" " required>
                        <label>Last Name</label>
                    </div>
                </div>

                <div class="screenhub-field">
                    <input type="text" name="middle_name" value="<?php echo htmlspecialchars($middle); ?>" placeholder=" ">
                    <label>Middle Name (optional)</label>
                </div>

                <div class="screenhub-field">
                    <input type="date" name="birthdate" value="<?php echo htmlspecialchars($bday); ?>" placeholder=" " required>
                    <label>Birth Date</label>
                </div>

                <div class="screenhub-field password-field">
                    <input type="password" name="password" id="pass" placeholder=" " required oninput="checkStrength(this.value)">
                    <label>Password</label>
                    <span class="toggle-eye" onclick="togglePass('pass', this)"><i class="fa-solid fa-eye"></i></span>
                </div>

                <div id="strength-container" class="password-strength">
                    <div class="strength-bars"><span></span><span></span><span></span></div>
                    <small id="strength-text">Password Strength</small>
                </div>

                <div class="screenhub-field password-field">
                    <input type="password" name="confirmPassword" id="conf" placeholder=" " required>
                    <label>Confirm Password</label>
                    <span class="toggle-eye" onclick="togglePass('conf', this)"><i class="fa-solid fa-eye"></i></span>
                </div>

                <button type="submit" class="screenhub-btn">Create Account</button>
            </form>

            <p class="screenhub-link">Already have an account? <a href="index.php">Sign in</a></p>
        </div>
    </div>

    <script>
        function togglePass(id, el) {
            const input = document.getElementById(id);
            const icon  = el.querySelector("i");
            input.type  = input.type === "password" ? "text" : "password";
            icon.classList.toggle("fa-eye");
            icon.classList.toggle("fa-eye-slash");
        }
        function checkStrength(p) {
            const c = document.getElementById('strength-container');
            let s   = 0;
            if (p.length > 7) s++;
            if (/[A-Z]/.test(p) && /[0-9]/.test(p)) s++;
            if (/[^A-Za-z0-9]/.test(p)) s++;
            c.className = "password-strength " + (s === 1 ? 'weak' : s === 2 ? 'medium' : s === 3 ? 'strong' : '');
        }
    </script>
</body>
</html>
