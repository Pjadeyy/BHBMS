<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$host = 'localhost';
$username = 'root'; 
$password = ''; 
$database = 'bh';

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$user_id = $_SESSION['user_id'];
$email_notif = isset($_POST['emailNotif']) && $_POST['emailNotif'] === 'true' ? 1 : 0;
$sms_notif = isset($_POST['smsNotif']) && $_POST['smsNotif'] === 'true' ? 1 : 0;
$language = filter_var($_POST['language'] ?? 'English', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$timezone = filter_var($_POST['timezone'] ?? 'Asia/Manila', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

$stmt = $conn->prepare("INSERT INTO settings (user_id, email_notifications, sms_notifications, default_language, timezone) VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE email_notifications = ?, sms_notifications = ?, default_language = ?, timezone = ?");
$stmt->bind_param("iissssiss", $user_id, $email_notif, $sms_notif, $language, $timezone, $email_notif, $sms_notif, $language, $timezone);
$success = $stmt->execute();

echo json_encode([
    'success' => $success,
    'message' => $success ? 'Settings saved successfully' : 'Failed to save settings'
]);

$stmt->close();
$conn->close();
?>