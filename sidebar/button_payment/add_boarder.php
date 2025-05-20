<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../authentication/login.php");
    exit();
}

// Database connection
$host = 'localhost';
$username = 'root'; 
$password = ''; 
$database = 'bh';

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$errors = [];
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $room_number = trim($_POST['roomNum']);
    $move_in_date = trim($_POST['moveInDate']);
    $monthly_rate = trim(str_replace('PHP ', '', $_POST['monthlyRate']));
    $appliances = trim($_POST['applianceInput']) ?: null;
    $deposit_amount = trim($_POST['depositAmount']);

    // Validation
    if (empty($name)) {
        $errors[] = "Name is required.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email is required.";
    }
    if (empty($room_number)) {
        $errors[] = "Room number is required.";
    }
    if (empty($move_in_date)) {
        $errors[] = "Move-in date is required.";
    }
    if (empty($monthly_rate) || !is_numeric($monthly_rate) || $monthly_rate <= 0) {
        $errors[] = "Valid monthly rate is required.";
    }
    if (empty($deposit_amount) || !is_numeric($deposit_amount) || $deposit_amount < 0) {
        $errors[] = "Valid deposit amount is required.";
    }
    if (!empty($appliances) && (!is_numeric($appliances) || $appliances < 0)) {
        $errors[] = "Number of appliances must be a non-negative number.";
    }

    // Insert into database
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO boarder (name, email, phone, address, room_number, move_in_date, monthly_rate, appliances, deposit_amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssdid", $name, $email, $phone, $address, $room_number, $move_in_date, $monthly_rate, $appliances, $deposit_amount);

        if ($stmt->execute()) {
            header("Location: ../../sidebar/boarder_profile.php?success=Boarder+added+successfully");
            exit();
        } else {
            $errors[] = "Error adding boarder: " . ($stmt->errno == 1062 ? "Email already exists." : $stmt->error);
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Boarding House Dashboard</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        #goBackBtn {
            padding: 10px 20px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 8px;
            background-color: #6c757d;
            color: white;
            border: none;
            transition: transform 0.3s ease, background-color 0.3s ease;
        }
        #goBackBtn:hover {
            background-color: #5a6268;
            transform: scale(1.05);
        }
        #goBackBtn:active {
            transform: scale(1.1);
        }
    </style>
</head>

<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar text-white p-3">
            <div class="text-center mb-4">
                <img src="../../images/logo.png" alt="Logo" class="img-fluid" style="max-width: 100px;">
                <h5 class="mt-2">Rent Billing Management System</h5>
            </div>
            <a href="../admin.php" class="d-flex align-items-center justify-content-between mb-4 px-2 nav-link admin-border">
                <div class="d-flex align-items-center">
                    <span class="material-icons me-2" style="font-size: 32px;">person</span>
                    <span class="fw-bold">Admin</span>
                </div>
                <span class="material-icons" style="font-size: 20px;">chevron_right</span>
            </a>
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="../dashboard.php"><span class="material-icons me-2">dashboard</span>Dashboard</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="../rent_payment.php"><span class="material-icons me-2">payments</span>Rent Payment</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="../receipt.php"><span class="material-icons me-2">receipt</span>Receipt/Invoice</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="../financial_rep.php"><span class="material-icons me-2">assessment</span>Financial Report</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white active" href="../boarder_profile.php"><span class="material-icons me-2">group</span>Boarders Profile</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="../communication.php"><span class="material-icons me-2">chat</span>Communication</a>
                </li>
            </ul>
            <div class="mt-2">
                <h6 class="text-uppercase small">Sub Menu</h6>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2">
                        <a class="nav-link text-white" href="../sub-menu/notif.php"><span class="material-icons me-2">notifications</span>Notification</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link text-white" href="../sub-menu/settings.php"><span class="material-icons me-2">settings</span>Settings</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link text-white" href="../sub-menu/logout.php"><span class="material-icons me-2">logout</span>Log Out</a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="flex-grow-1">
            <header class="d-flex justify-content-between align-items-center p-3 border-bottom">
                <h4 class="mb-0" style="font-weight: bold;">Kring-Kring Ladies Boarding House</h4>
                <div class="d-flex align-items-center gap-3">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Search..." id="search">
                        <button class="btn" type="button" id="searchBtn">
                            <span class="material-icons">search</span>
                        </button>
                    </div>
                    <button class="btn btn-outline-primary position-relative">
                        <span class="material-icons">notifications</span>
                    </button>
                    <button class="btn btn-outline-secondary">
                        <span class="material-icons">calendar_month</span>
                    </button>
                </div>
            </header>

            <div class="content p-4">
                <div class="mb-4">
                    <h5 style="font-weight: bold; color: #360938; border-bottom: 2px solid #360938; padding-bottom: 10px;">
                        ADD NEW BOARDER'S PROFILE
                    </h5>
                </div>

                <div class="d-flex" id="boarderProfileContainer">
                    <div class="container mt-3">
                        <div class="card">
                            <div class="card-header" style="background-color: purple; color: white;">
                                <h5 class="card-title" style="display: flex; align-items: center; font-weight: bold;">
                                    <i class="material-icons" style="margin-right: 10px; vertical-align: middle;">person</i>
                                    Add Boarder Information
                                </h5>
                            </div>

                            <div class="card-body">
                                <?php if ($success): ?>
                                    <div class="alert alert-success"><?php echo $success; ?></div>
                                <?php endif; ?>
                                <?php if (!empty($errors)): ?>
                                    <div class="alert alert-danger">
                                        <ul>
                                            <?php foreach ($errors as $error): ?>
                                                <li><?php echo $error; ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <form id="boarderForm" method="POST" action="add_boarder.php">
                                    <div class="row">
                                        <div class="col-md-12 mb-3">
                                            <div class="input-group">
                                                <span class="input-group-text" style="display: flex; align-items: center;">
                                                    <i class="material-icons" style="font-size: 20px;">badge</i>
                                                </span>
                                                <input type="text" id="name" name="name" class="form-control" placeholder="Enter name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="material-icons">email</i></span>
                                                <input type="email" id="email" name="email" class="form-control" placeholder="Enter email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                                            </div>
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="material-icons">phone</i></span>
                                                <input type="text" id="phone" name="phone" class="form-control" placeholder="Enter contact number" value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-12 mb-3">
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="material-icons">home</i></span>
                                                <input type="text" id="address" name="address" class="form-control" placeholder="Enter address" value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
                                            </div>
                                        </div>
                                        <hr>
                                        <h5 class="mb-3"><i class="material-icons">meeting_room</i> Rent Details</h5>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label"><i class="material-icons">door_front</i> Room Number</label>
                                                <input type="text" class="form-control" id="roomNum" name="roomNum" value="<?php echo isset($_POST['roomNum']) ? htmlspecialchars($_POST['roomNum']) : ''; ?>" required>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label"><i class="material-icons">calendar_today</i> Move In Date</label>
                                                <input type="date" id="moveInDate" name="moveInDate" class="form-control" value="<?php echo isset($_POST['moveInDate']) ? htmlspecialchars($_POST['moveInDate']) : ''; ?>" required>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label"><i class="material-icons">attach_money</i> Monthly Rate</label>
                                                <input type="text" id="monthlyRate" name="monthlyRate" class="form-control" value="<?php echo isset($_POST['monthlyRate']) ? htmlspecialchars($_POST['monthlyRate']) : 'PHP 1000'; ?>" readonly required>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label"><i class="material-icons">electrical_services</i> Number of Appliances</label>
                                            <div class="input-group mb-2">
                                                <input type="number" id="applianceInput" name="applianceInput" class="form-control" placeholder="Enter number of appliances" min="0" value="<?php echo isset($_POST['applianceInput']) ? htmlspecialchars($_POST['applianceInput']) : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="depositAmount" class="form-label">Deposit Amount (₱)</label>
                                            <input type="number" class="form-control" id="depositAmount" name="depositAmount" placeholder="Enter amount" min="0" step="0.01" value="<?php echo isset($_POST['depositAmount']) ? htmlspecialchars($_POST['depositAmount']) : ''; ?>" required>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary" id="saveBtn">Save</button>
                                        <button type="button" class="btn btn-secondary" onclick="clearForm()">Cancel</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mb-3 mt-3">
                            <button id="goBackBtn" class="d-flex align-items-center gap-1" onclick="window.location.href='../../boarder_profile.php';">
                                <i class="material-icons">arrow_back</i>
                                Go Back
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../scripts.js"></script>
    <script>
        function clearForm() {
            document.getElementById("boarderForm").reset();
            document.getElementById("monthlyRate").value = "PHP 1000";
        }
    </script>
</body>

</html>