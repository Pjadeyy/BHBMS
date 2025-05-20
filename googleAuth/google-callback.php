<?php
require 'vendor/autoload.php';
require 'includes/db.php';
session_start();

$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT']);
$client->setClientSecret($_ENV['GOOGLE_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT']);

if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token['access_token']);

    $oauth = new Google_Service_Oauth2($client);
    $google_user = $oauth->userinfo->get();

    $email = $google_user->email;

    $conn = new mysqli('localhost', 'root', '', 'bh'); 

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['email'] = $user['email'];
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['login_method'] = 'google';
        header('Location: ../sidebar/dashboard.php');
        exit();
    } else {
        echo "No account found for this Google email: $email";
    }
} else {
    header('Location: google-login.php');
    exit();
}