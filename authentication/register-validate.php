<?php
session_start();

// Database connection
$host = 'localhost';
$username = 'root'; // Replace with your MySQL username
$password = ''; // Replace with your MySQL password
$database = 'bh';

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form data
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact']);
    $government_id = trim($_POST['government_id']);
    $tin_num = trim($_POST['tin_num']);
    $address = trim($_POST['address']);
    $boardinghousename = trim($_POST['boardinghousename']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    $errors = [];

    // Validation
    if (empty($firstname)) {
        $errors[] = "First name is required.";
    }
    if (empty($lastname)) {
        $errors[] = "Last name is required.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    if (!empty($contact) && !preg_match("/^[0-9+\-() ]{7,20}$/", $contact)) {
        $errors[] = "Invalid contact number format.";
    }

    // Check for duplicate email
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "Email is already registered.";
    }
    $stmt->close();

    // If no errors, proceed with registration
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, email, contact, government_id, tin_num, address, boardinghousename, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssss", $firstname, $lastname, $email, $contact, $government_id, $tin_num, $address, $boardinghousename, $hashed_password);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Registration successful! Please log in.";
            header("Location: login.php");
            exit();
        } else {
            $_SESSION['error'] = "Error registering user: " . $conn->error;
            header("Location: register.php");
            $stmt->close();
            exit();
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: register.php");
        exit();
    }
}

$conn->close();
?>