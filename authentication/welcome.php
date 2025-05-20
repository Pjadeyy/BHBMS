<?php

session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Boarding House Billing Hub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Roboto+Condensed:wght@800&display=swap" rel="stylesheet">
  <style>
    body {
      background-color: #f9f9f9;
      font-family: 'Segoe UI', sans-serif;
      background-color: #140517;
    }

    .header {
      background-color: rgba(188, 65, 195, 0.15);
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .header-title {
      font-family: 'Segoe UI', sans-serif;
      font-weight: 800;
      font-size: 1.8rem;
      margin: 0;
    }

    .custom-login-btn {
      width: 10%;
      color: white;
      border: 2px solid orange;
      background-color: transparent;
      transition: 0.3s ease;
    }

    .custom-login-btn:hover,
    .custom-login-btn:focus {
      background-color: orange;
      color: #140517;
      border-color: orange;
    }

    .welcome-section {
      max-width: 800px;
      padding: 0 2rem;
      margin-top: 4rem;
      text-align: justify;
      color: white;

      /* Changed: Added flexbox to align content side-by-side */
      display: flex;
      justify-content: space-between;
      align-items: center;

      animation: fadeIn 1s ease-in;
    }

    .welcome-title {
      font-size: 55px;
      font-weight: 900;
    }

    .welcome-text {
      font-size: 20px;
      font-weight: 400;
      text-align: justify;
    }

    .card-section {
      max-width: 800px;
      margin-top: 2rem;
      margin-left: auto;
      margin-right: auto;
      margin: 0;
      padding: 0 2rem;
    }

    .feature-card {
      background-color: rgba(255, 255, 255, 0.05);
      color: white;
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      padding: 1.5rem;
      margin-bottom: 1rem;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .feature-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 30px rgba(255, 165, 0, 0.2);
    }

    .text-end img {
      max-width: 300px;
    }

    @keyframes fadeIn {
      0% {
        opacity: 0;
        transform: translateY(20px);
      }

      100% {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .logo {
      position: absolute;
      top: 150px;
      right: 20px;
      width: 500px;
      height: auto;
      z-index: 2;
    }
  </style>
</head>

<body>

  <!-- Header -->
  <div class="header">
    <h1 class="header-title" style="color: white;">
      <span style="color: orange;">B</span>House
      <span style="color: orange;">B</span>illing
      <span style="color: orange;"> H</span>ub
    </h1>
    <a href="../authentication/login.php" class="btn custom-login-btn">Login</a>
  </div>

  <!-- Welcome Title Section -->
  <div class="welcome-section">
    <div>
      <h1 class="welcome-title">Simplified Boarding House <br> Rent Billing Management</h1>
      <p class="welcome-text">
        Welcome to the Boarding House Rent Billing Management System – built to make your boarding house management
        easier,
        faster, and more reliable. <br>Manage. Track. Simplify.
      </p>
    </div>
  </div>

  <!-- Logo in Top Right Corner -->
  <img src="../images/logo.png" alt="BHouse Billing Hub Logo" class="logo">

  <!-- Cards Section -->
  <div class="card-section">
    <div class="row">
      <div class="col-md-6">
        <div class="card feature-card">
          <div class="card-body">
            <h5 class="card-title">Secure Billing</h5>
            <p class="card-text small">
              Automated rent tracking and error-free billing ensure owners stay organized and tenants pay with
              confidence.
            </p>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card feature-card">
          <div class="card-body">
            <h5 class="card-title">User-Friendly</h5>
            <p class="card-text small">
              A simple, easy-to-use system designed for boarding house owners and tenants alike – no tech skills
              required.
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>