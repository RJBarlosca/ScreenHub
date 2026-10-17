<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(["success" => false, "error" => "Not logged in."]);
    exit();
}

$conn = new mysqli("127.0.0.1", "root", "", "screenhub_db", 3306);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "Database connection failed."]);
    exit();
}

$input       = json_decode(file_get_contents('php://input'), true);
$currentUser = $_SESSION['username'];
$newUsername = trim($input['username'] ?? $currentUser);
$newPassword = $input['password'] ?? '';
$newAvatar   = $input['avatar']   ?? '';

// ── Username change ──
if ($newUsername !== $currentUser) {
    // Check not taken by someone else
    $check = $conn->prepare("SELECT id FROM users WHERE username = ? AND username != ?");
    $check->bind_param("ss", $newUsername, $currentUser);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        echo json_encode(["success" => false, "error" => "Username already taken."]);
        exit();
    }
    $check->close();

    $stmt = $conn->prepare("UPDATE users SET username = ? WHERE username = ?");
    $stmt->bind_param("ss", $newUsername, $currentUser);
    if (!$stmt->execute()) {
        echo json_encode(["success" => false, "error" => "Failed to update username."]);
        exit();
    }
    $stmt->close();
    $_SESSION['username'] = $newUsername;
    $currentUser = $newUsername;
}

// ── Password change ──
if (!empty($newPassword)) {
    if (strlen($newPassword) < 8) {
        echo json_encode(["success" => false, "error" => "Password must be at least 8 characters."]);
        exit();
    }
    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->bind_param("ss", $hashed, $currentUser);
    $stmt->execute();
    $stmt->close();
}

// ── Avatar change ──
if (!empty($newAvatar)) {
    $stmt = $conn->prepare("UPDATE users SET avatar = ? WHERE username = ?");
    $stmt->bind_param("ss", $newAvatar, $currentUser);
    if (!$stmt->execute()) {
        echo json_encode(["success" => false, "error" => "Failed to save avatar."]);
        exit();
    }
    $stmt->close();
    $_SESSION['avatar'] = $newAvatar;
}

$conn->close();
echo json_encode(["success" => true, "redirect" => "dashboard.php"]);
?>
