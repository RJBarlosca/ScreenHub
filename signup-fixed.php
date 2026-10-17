<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "screenhub_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = trim($_POST['username']);
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $birthdate = $_POST['birthdate'];
    $pass = $_POST['password'];
    $confirm = $_POST['confirmPassword'];

    $error = '';
    if (empty($first_name) || empty($last_name)) {
        $error = "First and last name are required.";
    } elseif (empty($birthdate) || strtotime($birthdate) > strtotime('-18 years')) {
        $error = "You must be 18 or older.";
    } elseif (strlen($pass) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[@$!%*?&])[A-Za-z\\d@$!%*?&]{8,}$/', $pass)) {
        $error = "Password must contain upper, lower, number, special char.";
    } elseif ($pass !== $confirm) {
        $error = "Passwords do not match.";
    } else {
        // Unique check prepared
        $stmt = $conn->prepare("SELECT id FROM users WHERE username=? OR email=?");
        $stmt->bind_param("ss", $user, $email);
        $stmt->execute();
        $check = $stmt->get_result();
        if ($check->num_rows > 0) {
            $error = "Username or email already exists.";
        } else {
            $hashed = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, first_name, middle_name, last_name, email, birthdate, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $null_middle = $middle_name ?: null;
            $stmt->bind_param("sssssss", $user, $first_name, $middle_name, $last_name, $email, $birthdate, $hashed);
            if ($stmt->execute()) {
                $_SESSION['username'] = $user;
                $_SESSION['just_registered'] = true;
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Database error.";
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>ScreenHub | Sign Up</title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

body {
  font-family: 'Inter', sans-serif;
  background: radial-gradient(circle at center, #1a0a2e 0%, #000 70%);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
}

.signup-container {
  background: rgba(20, 25, 45, 0.95);
  backdrop-filter: blur(20px);
  border-radius: 24px;
  padding: 3rem 2.5rem;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 25px 60px rgba(0,0,0,0.8);
  border: 1px solid rgba(255,255,255,0.08);
  position: relative;
  overflow: hidden;
}

.signup-container::before {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(45deg, transparent 30%, rgba(229,9,20,0.05), transparent 70%);
  animation: rotate 8s linear infinite;
  z-index: 0;
}

@keyframes rotate {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.signup-container > * { position: relative; z-index: 1; }

.logo {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 2.8rem;
  text-align: center;
  margin-bottom: 1.5rem;
  background: linear-gradient(135deg, #ff6b6b 0%, #e50914 50%, #ff4757 100%);
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  letter-spacing: 0.05em;
}

.title {
  text-align: center;
  margin-bottom: 2rem;
  color: #fff;
  font-size: 1.6rem;
  font-weight: 500;
}

.input-group {
  position: relative;
  margin-bottom: 1.8rem;
}

.input-field {
  width: 100%;
  padding: 1.2rem 1.5rem;
  background: rgba(30, 35, 55, 0.6);
  border: 2px solid rgba(255,255,255,0.1);
  border-radius: 14px;
  color: #fff;
  font-size: 1rem;
  transition: all 0.35s cubic-bezier(0.25,0.46,0.45,0.94);
  backdrop-filter: blur(12px);
}

.input-field:focus {
  outline: none;
  border-color: rgba(229,9,20,0.6);
  background: rgba(30,35,55,0.85);
  box-shadow: 0 0 0 4px rgba(229,9,20,0.15);
  transform: translateY(-1px);
}

.field-label {
  position: absolute;
  left: 1.5rem;
  top: 50%;
  transform: translateY(-50%);
  color: rgba(255,255,255,0.65);
  font-size: 0.9rem;
  font-weight: 500;
  pointer-events: none;
  transition: 0.3s ease;
  background: rgba(30,35,55,0.9);
  padding: 0.3rem 0.8rem;
  border-radius: 6px;
  backdrop-filter: blur(10px);
}

.input-field:focus + .field-label,
.input-field:valid + .field-label {
  top: -0.8rem;
  left: 1.2rem;
  font-size: 0.75rem;
  color: #e50914;
  background: rgba(229,9,20,0.9);
}

.password-group .password-icon {
  position: absolute;
  right: 1.5rem;
  top: 50%;
  transform: translateY(-50%);
  color: rgba(255,255,255,0.6);
  cursor: pointer;
  font-size: 1.1rem;
  transition: 0.3s ease;
}

.password-group .password-icon:hover {
  color: #e50914;
}

.strength-group {
  margin: 1.5rem 0;
}

.strength-text {
  color: rgba(255,255,255,0.8);
  font-size: 0.85rem;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.strength-progress {
  height: 6px;
  background: rgba(255,255,255,0.1);
  border-radius: 3px;
  overflow: hidden;
}

.strength-progress-fill {
  height: 100%;
  width: 0%;
  background: linear-gradient(to right, #ff4757, #ffa502, #2ed573);
  border-radius: 3px;
  transition: width 0.4s ease;
}

.submit-button {
  width: 100%;
  padding: 1.3rem;
  background: linear-gradient(135deg, #e50914 0%, #ff4757 50%, #ff6b6b 100%);
  border: none;
  border-radius: 14px;
  color: white;
  font-size: 1.05rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 10px 30px rgba(229,9,20,0.4);
  margin-top: 1rem;
  position: relative;
  overflow: hidden;
}

.submit-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 15px 40px rgba(229,9,20,0.6);
}

.submit-button:active {
  transform: translateY(0);
}

.login-prompt {
  text-align: center;
  margin-top: 1.8rem;
  color: rgba(255,255,255,0.7);
  font-size: 0.95rem;
}

.login-prompt a {
  color: #e50914;
  font-weight: 600;
  text-decoration: none;
}

.error-message {
  background: rgba(255,71,87,0.2);
  border: 1px solid rgba(255,71,87,0.4);
  color: #ff6b6b;
  padding: 1rem;
  border-radius: 12px;
  margin-bottom: 1.5rem;
  text-align: center;
  font-size: 0.9rem;
}

@media (max-width: 480px) {
  .signup-container { padding: 2.5rem 2rem; margin: 1rem; }
}

.name-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

@media (max-width: 480px) {
  .name-row { grid-template-columns: 1fr; gap: 1rem; }
}
</style>
</head>
<body>
  <div class="signup-container">
    <h1 class="logo">SCREENHUB</h1>
    <h2 class="title">Sign Up</h2>

    <?php if ($error): ?>
      <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="name-row">
        <div class="input-group">
          <input type="text" class="input-field" name="first_name" id="first_name" required>
          <label class="field-label" for="first_name">First Name</label>
        </div>
        <div class="input-group">
          <input type="text" class="input-field" name="last_name" id="last_name" required>
          <label class="field-label" for="last_name">Last Name</label>
        </div>
      </div>

      <div class="input-group">
        <input type="text" class="input-field" name="username" id="username" required>
        <label class="field-label" for="username">Username</label>
      </div>

      <div class="input-group">
        <input type="email" class="input-field" name="email" id="email" required>
        <label class="field-label" for="email">Email</label>
      </div>

      <div class="input-group">
        <input type="date" class="input-field" name="birthdate" id="birthdate" required max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
        <label class="field-label" for="birthdate">Birth Date (18+)</label>
      </div>

      <div class="input-group">
        <input type="text" class="input-field" name="middle_name" id="middle_name" placeholder=" ">
        <label class="field-label" for="middle_name">Middle Name (optional)</label>
      </div>

      <div class="password-group input-group">
        <input type="password" class="input-field" name="password" id="password" required>
        <label class="field-label" for="password">Password</label>
        <div class="password-icon" onclick="togglePassword('password')">
          <i class="fas fa-eye-slash"></i>
        </div>
      </div>

      <div class="password-group input-group">
        <input type="password" class="input-field" name="confirmPassword" id="confirmPassword" required>
        <label class="field-label" for="confirmPassword">Confirm Password</label>
        <div class="password-icon" onclick="togglePassword('confirmPassword')">
          <i class="fas fa-eye-slash"></i>
        </div>
      </div>

      <div class="strength-group">
        <span class="strength-text">Password Strength</span>
        <div class="strength-progress">
          <div class="strength-progress-fill" id="strength-progress"></div>
        </div>
      </div>

      <button type="submit" class="submit-button">
        Create Account <i class="fas fa-arrow-right ml-2"></i>
      </button>
    </form>

    <div class="login-prompt">
      Already have an account? <a href="index.php">Sign in</a>
    </div>
  </div>

  <script>
    function togglePassword(id) {
      const input = document.getElementById(id);
      const icon = input.parentNode.querySelector('.password-icon i');
      input.type = input.type === 'password' ? 'text' : 'password';
      icon.classList.toggle('fa-eye-slash');
      icon.classList.toggle('fa-eye');
    }

    document.getElementById('password').addEventListener('input', function() {
      const val = this.value;
      let score = 0;
      if (val.length >= 8) score++;
      if (/[A-Z]/.test(val)) score++;
      if (/[a-z]/.test(val)) score++;
      if (/[0-9]/.test(val)) score++;
      if (/[^A-Za-z0-9]/.test(val)) score++;
      
      const progress = document.getElementById('strength-progress');
      const submitBtn = document.querySelector('.submit-button');
      progress.style.width = (score / 5) * 100 + '%';
      progress.style.background = score < 3 ? '#ff4757' : score < 4 ? '#ffa502' : '#2ed573';
      submitBtn.disabled = score < 5;
      submitBtn.style.opacity = score < 5 ? '0.6' : '1';
    });

    // Confirm match check
    document.getElementById('confirmPassword').addEventListener('input', function() {
      const pass = document.getElementById('password').value;
      const submitBtn = document.querySelector('.submit-button');
      if (this.value !== pass) {
        submitBtn.disabled = true;
        submitBtn.style.opacity = '0.6';
      }
    });
  </script>
</body>
</html> 

**New File**: `signup-fixed.php` – **totally new glassmorphism UI**
**Live**: http://localhost/SCREEN/signup-fixed.php

**Self-contained**: Inline CSS, no style.css conflicts
**Features**: Lift effects, blur, strength bar, perfect spacing

**SQL**: Run `create_users_table.sql` for hashed passwords

Copy to `signup.php` if perfect.
