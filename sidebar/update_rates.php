<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $due_date = trim($_POST['due_date']);
    $monthly_rate = trim($_POST['monthly_rate']);
    $appliances_rate = trim($_POST['appliances_rate']);
    $late_fee = trim($_POST['late_fee']);

    $errors = [];

    // Validation
    if (empty($due_date)) {
        $errors[] = 'Due date is required.';
    }
    if (empty($monthly_rate) || $monthly_rate < 0) {
        $errors[] = 'Valid monthly rate is required.';
    }
    if (empty($appliances_rate) || $appliances_rate < 0) {
        $errors[] = 'Valid appliances rate is required.';
    }
    if (empty($late_fee) || $late_fee < 0) {
        $errors[] = 'Valid late fee is required.';
    }

    if (empty($errors)) {
        // Check if rate exists
        $stmt = $conn->prepare("SELECT id FROM rates WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();

        if ($exists) {
            // Update existing rate
            $stmt = $conn->prepare("UPDATE rates SET due_date = ?, monthly_rate = ?, appliances_rate = ?, late_fee = ? WHERE user_id = ?");
            $stmt->bind_param("sdddi", $due_date, $monthly_rate, $appliances_rate, $late_fee, $user_id);
        } else {
            // Insert new rate
            $stmt = $conn->prepare("INSERT INTO rates (user_id, due_date, monthly_rate, appliances_rate, late_fee) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isddd", $user_id, $due_date, $monthly_rate, $appliances_rate, $late_fee);
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to update rate']);
        }
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => implode(' ', $errors)]);
    }
}

$conn->close();
?>