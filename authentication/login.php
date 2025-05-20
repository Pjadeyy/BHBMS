<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login - BHouse Billing Hub</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .form-title {
            text-align: center;
            margin-bottom: 1.5rem;
            font-weight: bold;
            font-size: 24px;
            color: #140517;
        }
        .form-control:focus {
            box-shadow: none;
            border-color: orange;
        }
        .btn-orange {
            background-color: orange;
            color: white;
            border: none;
        }
        .btn-orange:hover {
            background-color: darkorange;
        }
        .form-links {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
            margin-top: 10px;
        }
        .form-links a {
            color: #6c757d;
            text-decoration: none;
        }
        .form-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="logo">
        <a href="welcome.php">
            <img src="../images/logo.png" alt="BHouse Billing Hub Logo" class="img-fluid">
        </a>
    </div>

    <div class="login-container">
        <h2 class="form-title">Login</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger" role="alert">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success" role="alert">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <form action="login-validate.php" method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control" id="email" 
                    placeholder="Enter your email" 
                    value="<?= isset($_COOKIE['remember_email']) ? htmlspecialchars($_COOKIE['remember_email']) : '' ?>" 
                    required>
                </div>
            </div>

            <div class="mb-2">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control" id="password" placeholder="Enter your password" required>
                    <span class="input-group-text bg-white">
                        <i class="bi bi-eye-slash" id="togglePassword" style="cursor: pointer;"></i>
                    </span>
                </div>
            </div>
                <!-- Google reCAPTCHA -->
            <div class="g-recaptcha" data-sitekey="6Ld0MjYrAAAAAFgus5noRt2UjrrQZi8s26L9c7N7"></div> 

            <div class="form-links">
                <div>
                <input type="checkbox" id="remember" name="remember" <?= isset($_COOKIE['remember_email']) ? 'checked' : '' ?>>
                    <label for="remember">Remember me</label>
                </div>
                <a href="forgot_password.php">Forgot password?</a>
            </div>

            <button type="submit" class="btn btn-orange w-100 mt-3">Login</button>

            <div class="text-center mt-3">
                <span>Don't have an account? <a href="register.php" style="font-weight: bold; color: orange;">Register</a></span>
            </div>
        </form>
        <!-- Google Login -->
        <div class="text-center mt-4">
            <div id="g_id_onload"
                data-client_id="871135033493-0pi69n77saj4p87i0eqmg4gavkhugmv1.apps.googleusercontent.com"
                data-context="signin"
                data-ux_mode="popup"
                data-callback="handleCredentialResponse"
                data-auto_prompt="false">
            </div>
            <div class="g_id_signin" data-type="standard"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script>
        const togglePassword = document.querySelector("#togglePassword");
        const password = document.querySelector("#password");

        togglePassword.addEventListener("click", function () {
            const type = password.getAttribute("type") === "password" ? "text" : "password";
            password.setAttribute("type", type);
            this.classList.toggle("bi-eye");
            this.classList.toggle("bi-eye-slash");
        });
    </script>
</body>

</html>