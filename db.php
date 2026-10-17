<?php
$host   = "127.0.0.1";
$dbuser = "root";
$dbpass = "";
$dbname = "screenhub_db";

$conn = new mysqli($host, $dbuser, $dbpass, $dbname, 3306);

if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Database connection failed: " . $conn->connect_error]);
    exit();
}

$conn->set_charset("utf8mb4");
?>
