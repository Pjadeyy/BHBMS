<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../authentication/login.php");
    exit();
}

// Database connection
$host = 'localhost';
$username = 'root'; 
$password = ''; 
$database = 'bh';

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// Check if boarder ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: boarder_profile.php");
    exit();
}

$boarder_id = $_GET['id'];

// Verify the boarder exists and belongs to the user
$stmt = $conn->prepare("SELECT id FROM boarders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $boarder_id, $user_id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    $_SESSION['message'] = '<div class="alert alert-danger">Boarder not found or you do not have permission to delete this boarder.</div>';
    $stmt->close();
    header("Location: boarder_profile.php");
    exit();
}
$stmt->close();

// Delete the boarder
$stmt = $conn->prepare("DELETE FROM boarders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $boarder_id, $user_id);
if ($stmt->execute()) {
    $_SESSION['message'] = '<div class="alert alert-success">Boarder deleted successfully!</div>';
} else {
    $_SESSION['message'] = '<div class="alert alert-danger">Failed to delete boarder: ' . $conn->error . '</div>';
}
$stmt->close();
$conn->close();

header("Location: boarder_profile.php");
exit();
?>