<?php
session_start();
header('Content-Type: application/json');

// DATABASE CONNECTION
$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "screenhub_db";

$conn = new mysqli("127.0.0.1", "root", "", "screenhub_db", 3306);

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit();
}

// Get JSON input
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if(!isset($input['username']) || !isset($input['password'])){
    echo json_encode(["status"=>"error","message"=>"Please enter username and password"]);
    exit();
}

$user = trim($input['username']);
$pass = $input['password'];

// Prepared statement
$stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ? OR email = ? LIMIT 1");
$stmt->bind_param("ss", $user, $user);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $db_pass = $row['password'];

    if(password_verify($pass, $db_pass)){
        $_SESSION['username'] = $row['username'];
        echo json_encode(["status"=>"success"]);
    } else {
        echo json_encode(["status"=>"error","message"=>"Incorrect password"]);
    }
} else {
    echo json_encode(["status"=>"error","message"=>"User not found"]);
}
$stmt->close();

$conn->close();
?>