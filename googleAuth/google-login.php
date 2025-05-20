<?php
session_start();

require 'vendor/autoload.php';
require_once 'includes/db.php';

$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT']);
$client->setClientSecret($_ENV['GOOGLE_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT']);
$client->addScope("email");
$client->addScope("profile");

$auth_url = $client->createAuthUrl();
header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
exit;