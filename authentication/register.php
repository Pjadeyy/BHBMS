<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Register - BHouse Billing Hub</title>
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
        .register-container {
            max-width: 600px;
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
            text-align: center;
            margin-top: 1rem;
            font-size: 14px;
        }
        .form-links a {
            color: orange;
            font-weight: bold;
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
    <div class="register-container">
        <h2 class="form-title">Register</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger text-center" role="alert">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success text-center" role="alert">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <form action="register-validate.php" method="POST">
            <div class="row mb-3">
                <div class="col">
                    <label for="firstName" class="form-label">First Name</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-person"></i></span>
                        <input type="text" name="firstname" class="form-control" id="firstName" placeholder="Enter your first name" value="<?php echo isset($_POST['firstname']) ? htmlspecialchars($_POST['firstname']) : ''; ?>" required>
                    </div>
                </div>
                <div class="col">
                    <label for="lastName" class="form-label">Last Name</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="bi bi-person"></i></span>
                        <input type="text" name="lastname" class="form-control" id="lastName" placeholder="Enter your last name" value="<?php echo isset($_POST['lastname']) ? htmlspecialchars($_POST['lastname']) : ''; ?>" required>
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="email" class="form-control" id="email" placeholder="Enter your email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label for="contactNumber" class="form-label">Contact Number</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-telephone"></i></span>
                    <input type="text" name="contact" class="form-control" id="contactNumber" placeholder="Enter your contact number" value="<?php echo isset($_POST['contact']) ? htmlspecialchars($_POST['contact']) : ''; ?>">
                </div>
            </div>

            <div class="mb-3">
                <label for="govId" class="form-label">Government ID Number</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-card-text"></i></span>
                    <input type="text" name="government_id" class="form-control" id="govId" placeholder="Enter your government ID number" value="<?php echo isset($_POST['government_id']) ? htmlspecialchars($_POST['government_id']) : ''; ?>">
                </div>
            </div>

            <div class="mb-3">
                <label for="tinNumber" class="form-label">TIN Number</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-file-earmark-text"></i></span>
                    <input type="text" name="tin_num" class="form-control" id="tinNumber" placeholder="Enter your TIN number" value="<?php echo isset($_POST['tin_num']) ? htmlspecialchars($_POST['tin_num']) : ''; ?>">
                </div>
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-house-door"></i></span>
                    <input type="text" name="address" class="form-control" id="address" placeholder="Enter your address" value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
                </div>
            </div>

            <div class="mb-3">
                <label for="boardingHouseName" class="form-label">Boarding House Name</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-building"></i></span>
                    <input type="text" name="boardinghousename" class="form-control" id="boardingHouseName" placeholder="Enter boarding house name" value="<?php echo isset($_POST['boardinghousename']) ? htmlspecialchars($_POST['boardinghousename']) : ''; ?>">
                </div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control" id="password" placeholder="Enter password" required>
                    <span class="input-group-text bg-white">
                        <i class="bi bi-eye-slash" id="togglePassword" style="cursor: pointer;"></i>
                    </span>
                </div>
            </div>

            <div class="mb-3">
                <label for="confirmPassword" class="form-label">Re-enter Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-lock"></i></span>
                    <input type="password" name="confirm_password" class="form-control" id="confirmPassword" placeholder="Re-enter your password" required>
                    <span class="input-group-text bg-white">
                        <i class="bi bi-eye-slash" id="toggleConfirmPassword" style="cursor: pointer;"></i>
                    </span>
                </div>
            </div>

            <button type="submit" class="btn btn-orange w-100 mt-3">Register</button>

            <div class="form-links">
                <span>Already have an account? <a href="login.php">Login</a></span>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const togglePassword = document.querySelector("#togglePassword");
        const password = document.querySelector("#password");
        togglePassword.addEventListener("click", function () {
            const type = password.getAttribute("type") === "password" ? "text" : "password";
            password.setAttribute("type", type);
            this.classList.toggle("bi-eye");
            this.classList.toggle("bi-eye-slash");
        });

        const toggleConfirmPassword = document.querySelector("#toggleConfirmPassword");
        const confirmPassword = document.querySelector("#confirmPassword");
        toggleConfirmPassword.addEventListener("click", function () {
            const type = confirmPassword.getAttribute("type") === "password" ? "text" : "password";
            confirmPassword.setAttribute("type", type);
            this.classList.toggle("bi-eye");
            this.classList.toggle("bi-eye-slash");
        });
    </script>
</body>

</html>