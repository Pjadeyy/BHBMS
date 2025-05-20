<?php
session_start();
require '../includes/db.php';

if (!isset($_SESSION['email']) || !isset($_SESSION['reset_code_verified']) || !$_SESSION['reset_code_verified']) {
    header("Location: ../authentication/enter_code.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirm_password']);

    $errors = [];

    // Validate password length
    if (strlen($newPassword) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }

    // Check if passwords match
    if ($newPassword !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }

    // If there are no validation errors
    if (empty($errors)) {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->execute([$hashedPassword, $_SESSION['reset_email']]);

        unset($_SESSION['reset_email']);
        unset($_SESSION['reset_code_verified']);

        $_SESSION['success'] = 'Your password has been reset successfully.';
        header('Location: ../authentication/login.php');
        exit();
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: ../authentication/reset_password.php");
        exit();
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Reset Password - BHouse Billing Hub</title>
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

    .reset-container {
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
  <div class="reset-container">
    <h2 class="form-title">Reset Password</h2>

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
            
    <form action="../authentication/reset_password.php" method="POST">
      <div class="mb-3">
        <label for="newPassword" class="form-label">New Password</label>
        <div class="input-group">
          <span class="input-group-text bg-white"><i class="bi bi-lock"></i></span>
          <input type="password" name="password" class="form-control" id="newPassword" placeholder="Enter new password" required>
        </div>
      </div>

      <div class="mb-3">
        <label for="confirmNewPassword" class="form-label">Confirm New Password</label>
        <div class="input-group">
          <span class="input-group-text bg-white"><i class="bi bi-lock-fill"></i></span>
          <input type="password" name="confirm_password" class="form-control" id="confirmNewPassword" placeholder="Confirm new password" required>
        </div>
      </div>

      <button type="submit" class="btn btn-orange w-100">Reset Password</button>

      <div class="back-to-login">
        <a href="../authentication/login.php">Back to Login</a>
    </div>
    </form>
    
  </div>


  <script>
    document.querySelector('form').addEventListener('submit', function (e) {
        const newPassword = document.getElementById('newPassword').value.trim();
        const confirmPassword = document.getElementById('confirmNewPassword').value.trim();

        // Password validation
        if (newPassword.length < 8) {
            alert('Password must be at least 8 characters long.');
            e.preventDefault();
            return;
        }

        // Match validation
        if (newPassword !== confirmPassword) {
            alert('Passwords do not match.');
            e.preventDefault();
        }
    });
</script>

</body>

</html>