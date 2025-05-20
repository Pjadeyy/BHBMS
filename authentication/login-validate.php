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
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $recaptchaResponse = $_POST['g-recaptcha-response']; // Capturing reCAPTCHA response

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Email and password are required.";
        header("Location: login.php");
        exit();
    }

    // Verify reCAPTCHA response
    $recaptchaSecret = '6Ld0MjYrAAAAAKWeZRKju8st6Lskosw2QGlZpywv'; // Replace with your reCAPTCHA secret key
    $recaptchaURL = 'https://www.google.com/recaptcha/api/siteverify';
    
    $response = file_get_contents($recaptchaURL . '?secret=' . $recaptchaSecret . '&response=' . $recaptchaResponse);
    $responseKeys = json_decode($response, true);

    if (!$responseKeys['success']) {
        $_SESSION['error'] = "Captcha verification failed. Please try again.";
        header("Location: login.php");
        exit();
    }

    // Proceed with login logic if reCAPTCHA passes
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header("Location: ../sidebar/dashboard.php");
            exit();
        } else {
            $_SESSION['error'] = "Invalid email or password.";
        }
    } else {
        $_SESSION['error'] = "Invalid email or password.";
    }
    $stmt->close();
    header("Location: login.php");
    exit();
}

$conn->close();
?>
