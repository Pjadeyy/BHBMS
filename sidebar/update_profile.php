<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Database connection
$host = 'localhost';
$username = 'root'; 
$password = ''; 
$database = 'bh';

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

// Get user ID
$user_id = $_SESSION['user_id'];

// Get form data
$firstname = $_POST['firstname'] ?? '';
$lastname = $_POST['lastname'] ?? '';
$email = $_POST['email'] ?? '';
$contact = $_POST['contact'] ?? null;
$government_id = $_POST['government_id'] ?? null;
$tin_num = $_POST['tin_num'] ?? null;
$address = $_POST['address'] ?? null;
$boardinghousename = $_POST['boardinghousename'] ?? null;

// Validate required fields
if (empty($firstname) || empty($lastname) || empty($email)) {
    echo json_encode(['success' => false, 'error' => 'Required fields are missing']);
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit();
}

// Check if email is already used by another user
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$stmt->bind_param("si", $email, $user_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'Email is already in use']);
    $stmt->close();
    $conn->close();
    exit();
}
$stmt->close();

// Update user data
$stmt = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ?, contact = ?, government_id = ?, tin_num = ?, address = ?, boardinghousename = ? WHERE id = ?");
$stmt->bind_param("ssssssssi", $firstname, $lastname, $email, $contact, $government_id, $tin_num, $address, $boardinghousename, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update profile']);
}

$stmt->close();
$conn->close();
?>