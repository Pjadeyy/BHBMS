<?php
session_start(); //Starts a session to track user data across multiple requests.
require '../includes/db.php'; //Includes a file with database connection logic

if ($_SERVER['REQUEST_METHOD'] === 'POST') { //Checks if the request method is POST
    if (!isset($_SESSION['email'])) { //Ensures an email session exists. Redirects if not found
        $_SESSION['error'] = "No email session found. Please try again."; //Fetches the reset code from the database for the email in the session
        header("Location: ../authentication/forgot_password.php");
        exit();
    }

    $email = $_SESSION['email'];
    $enteredCode = $_POST['code'];

    $stmt = $pdo->prepare("SELECT reset_code FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Ensure both codes are treated as strings to avoid type mismatch
        if (trim($enteredCode) === strval($user['reset_code'])) { //Compares the entered code with the stored reset code
            $_SESSION['reset_email'] = $email;
            $_SESSION['reset_code_verified'] = true;
            header("Location: ../authentication/reset_password.php"); //Redirects the user to the reset password page if the code matches
            exit();
        } else {
            $_SESSION['error'] = "Invalid Code. Please try again."; //Sets appropriate error messages in the session and redirects back to the form if validation fails
            header("Location: ../authentication/enter_code.php");
            exit();
        }
    } else {
        $_SESSION['error'] = "No user found with that email.";
        header("Location: ../authentication/forgot_password.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
<title>Enter Verification Code - BHouse Billing Hub</title> <!---Sets the page title for the browser tab--->
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"> <!----Includes Bootstrap for styling and responsiveness--->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body {
      background-color: #140517;
      font-family: 'Segoe UI', sans-serif;
    }

    .code-container {
      max-width: 400px;
      margin: 100px auto;
      padding: 2rem;
      background-color: white;
      border-radius: 15px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .logo {
      position: absolute;
      top: 20px;
      left: 20px;
      width: 150px;
      height: auto;
    }

    .login-container {
      max-width: 400px;
      margin: 100px auto;
      padding: 2rem;
      background-color: white;
      border-radius: 15px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .form-title {
      text-align: center;
      margin-bottom: 1.5rem;
      font-weight: bold;
      font-size: 24px;
      color: #140517;
    }

    .btn-orange {
      background-color: orange;
      color: white;
      border: none;
    }

    .btn-orange:hover {
      background-color: darkorange;
    }

    .back-to-forgot {
      text-align: center;
      margin-top: 1rem;
      font-size: 14px;
    }

    .back-to-forgot a {
      color: orange;
      text-decoration: none;
      font-weight: bold;
    }

    .back-to-forgot a:hover {
      text-decoration: underline;
      color: darkorange;
    }
  </style>
</head>

<body>
  <div class="logo">
    <img src="../images/logo.png" alt="BHouse Billing Hub Logo" class="img-fluid">
  </div>

  <div class="code-container">
    <h2 class="form-title">Enter Verification Code</h2>
    <?php
                if (isset($_SESSION['success'])) {
                    echo '<div class="alert alert-info text-center">' . $_SESSION['success'] . '</div>';
                    unset($_SESSION['success']);
                }

                if (isset($_SESSION['error'])) {
                    echo '<div class="alert alert-danger text-center">' . $_SESSION['error'] . '</div>';
                    unset($_SESSION['error']);
                }
            ?>

      <form action="../authentication/enter_code.php" method="POST"> <!---Defines a form for users to enter the verification code--->
      <div class="mb-3">
        <label for="verificationCode" class="form-label">Verification Code</label>
        <div class="input-group">
          <span class="input-group-text bg-white"><i class="bi bi-shield-lock"></i></span>
          <input type="text" class="form-control" id="verificationCode" name="code" placeholder="Enter the code" required>
        </div>
      </div>

      <button type="submit" class="btn btn-orange w-100">Verify Code</button>

      <div class="back-to-forgot">
        <a href="../authentication/forgot_password.php"> Back to Forgot Password</a>
      </div>
    </form>
  </div>

  <script>
    if (enteredCode.length !== 6) {
    alert('The verification code must be 6 digits long.');
    e.preventDefault();
}
  </script>
</body>
                
</html>