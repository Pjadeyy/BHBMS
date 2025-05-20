<?php
session_start();

$host = 'localhost';
$username = 'root'; 
$password = ''; 
$database = 'bh';

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$notification_id = $_POST['id'] ?? null;
$action = $_POST['action'] ?? null;
$status = $_POST['status'] ?? null;

if ($notification_id && $user_id) {
    if ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notification_id, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE notifications SET status = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("sii", $status, $notification_id, $user_id);
    }
    $stmt->execute();
    $stmt->close();
}

$conn->close();
?>