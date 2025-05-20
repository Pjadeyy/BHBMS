<?php
session_start();

require '../includes/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST'){
    $email = $_POST['email'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $reset_code = rand(100000, 999999);

        $update = $pdo->prepare("UPDATE users SET reset_code = ? WHERE email = ?");
        $update->execute([$reset_code, $email]);

        $mail = new PHPMailer(true);

        try {
          $mail->isSMTP();
          $mail->Host = 'smtp.gmail.com';
          $mail->SMTPAuth = true;
          $mail->Username = 'prixanejadegales@gmail.com';
          $mail->Password = 'thecljfydqkjyiyo'; 
          $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
          $mail->Port = 587;

          $mail->setFrom('prixanejadegales@gmail.com', 'PJ');
          $mail->addAddress($email, 'THIS IS YOUR CLIENT');

          $mail->isHTML(true);
          $mail->Subject = 'Password Reset Code';
          $mail->Body    = "<p>Hello <strong>{$user['firstname']}</strong>,<br><br>Your verification code is: <strong>$reset_code</strong><br><br>Enter this code to reset your password.</p>";
          $mail->AltBody = "Hello, Use the code below to reset your password: \n\n $reset_code\n\n";
          $mail->send();

            $_SESSION['email'] = $email;
            $_SESSION['email_sent'] = true;
            $_SESSION['success'] = "A verification code was sent to your email.";
            header("Location: ../authentication/enter_code.php");
            exit();

        } catch (Exception $e) {
            $_SESSION['error'] = "Message could not be sent.";
            header("Location: ../authentication/forgot_password.php");
            exit();
        }   
    } else {
        $_SESSION['error'] = "Email not found!";
        header("Location: ../authentication/forgot_password.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Forgot Password - BHouse Billing Hub</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body {
      background-color: #140517;
      font-family: 'Segoe UI', sans-serif;
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

    .forgot-container {
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

    .back-to-login {
      text-align: center;
      margin-top: 1rem;
      font-size: 14px;
    }

    .back-to-login a {
      color: orange;
      text-decoration: none;
      font-weight: bold;
    }

    .back-to-login a:hover {
      text-decoration: underline;
      color: darkorange;
    }
  </style>
</head>

<body>
  <div class="logo">
    <img src="../images/logo.png" alt="BHouse Billing Hub Logo" class="img-fluid">
  </div>

  <div class="forgot-container">
    <h2 class="form-title">Forgot Password</h2>
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

    <form action="../authentication/forgot_password.php" method="POST">
      <div class="mb-3">
        <label for="email" class="form-label">Enter your email address</label>
        <div class="input-group">
          <span class="input-group-text bg-white"><i class="bi bi-envelope"></i></span>
          <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
        </div>
      </div>

      <button type="submit" class="btn btn-orange w-100">Send Reset Link</button>

      <div class="back-to-login">
        <a href="../authentication/login.php">Back to Login</a>
      </div>
    </form>
  </div>

</body>

</html>