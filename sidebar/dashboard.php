<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../authentication/login.php");
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

$user_id = $_SESSION['user_id'];

// Fetch user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT firstname, lastname, email, contact, government_id, tin_num, address, boardinghousename FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Get current month and due date
$current_date = new DateTime();
$due_date = $current_date->format('Y-m-10');
$current_month = $current_date->format('Y-m');
$month_start = $current_month . '-01';
$month_end = $current_date->format('Y-m-t');

// Fetch rate data
$stmt = $conn->prepare("SELECT monthly_rate, appliances_rate FROM rates WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$rate = $result->fetch_assoc();
$monthly_rate = $rate['monthly_rate'] ?? 0.00;
$appliances_rate = $rate['appliances_rate'] ?? 0.00;
$stmt->close();

// Total Boarders
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM boarders WHERE user_id = ? AND status = 'active'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_boarders = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Paid Boarders 
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT b.id) as paid 
    FROM boarders b
    LEFT JOIN payments p ON b.id = p.boarder_id 
        AND p.payment_date BETWEEN ? AND ?
        AND p.payment_type = 'rent'
    WHERE b.user_id = ? 
    AND b.status = 'active'
    GROUP BY b.id
    HAVING COALESCE(SUM(p.amount), 0) >= (? + (COALESCE(MAX(p.appliances), 0) * ?))
");
$stmt->bind_param("ssidd", $month_start, $month_end, $user_id, $monthly_rate, $appliances_rate);
$stmt->execute();
$result = $stmt->get_result();
$paid_boarders = $result->num_rows;
$stmt->close();

// Pending Boarders
$stmt = $conn->prepare("SELECT COUNT(*) as pending FROM boarders b WHERE user_id = ? AND status = 'active' AND NOT EXISTS (SELECT 1 FROM payments p WHERE p.boarder_id = b.id AND p.user_id = ? AND p.payment_date BETWEEN ? AND ?)");
$stmt->bind_param("iiss", $user_id, $user_id, $month_start, $month_end);
$stmt->execute();
$pending_boarders = $stmt->get_result()->fetch_assoc()['pending'];
$stmt->close();

// Partial Boarders
$stmt = $conn->prepare("SELECT COUNT(DISTINCT boarder_id) as partial FROM payments WHERE user_id = ? AND status = 'partial' AND payment_date BETWEEN ? AND ?");
$stmt->bind_param("iss", $user_id, $month_start, $month_end);
$stmt->execute();
$partial_boarders = $stmt->get_result()->fetch_assoc()['partial'];
$stmt->close();

// Overdue Boarders
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT b.id) as overdue 
    FROM boarders b
    LEFT JOIN payments p ON b.id = p.boarder_id 
        AND p.payment_date BETWEEN ? AND ?
        AND p.payment_type = 'rent'
    WHERE b.user_id = ? 
    AND b.status = 'active'
    AND CURRENT_DATE > ?
    GROUP BY b.id
    HAVING COALESCE(SUM(p.amount), 0) = 0
");
$stmt->bind_param("ssss", $month_start, $month_end, $user_id, $due_date);
$stmt->execute();
$result = $stmt->get_result();
$overdue_boarders = $result->fetch_assoc()['overdue'] ?? 0;
$stmt->close();

// Monthly Collection
$stmt = $conn->prepare("SELECT SUM(amount) as collected FROM payments WHERE user_id = ? AND payment_type = 'rent' AND payment_date BETWEEN ? AND ?");
$stmt->bind_param("iss", $user_id, $month_start, $month_end);
$stmt->execute();
$monthly_collection = $stmt->get_result()->fetch_assoc()['collected'] ?? 0.00;
$stmt->close();

// Expected Collection
$expected_collection = $total_boarders * ($monthly_rate + $appliances_rate);

// Visitor Collection
$stmt = $conn->prepare("SELECT SUM(amount) as visitor FROM payments WHERE user_id = ? AND payment_type = 'visitor' AND payment_date BETWEEN ? AND ?");
$stmt->bind_param("iss", $user_id, $month_start, $month_end);
$stmt->execute();
$visitor_collection = $stmt->get_result()->fetch_assoc()['visitor'] ?? 0.00;
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Boarding House</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .card-icon {
            font-size: 2rem;
            color: #360938;
        }
        .card-title {
            font-weight: bold;
            color: #360938;
        }
        .card-text {
            font-size: 1.5rem;
            font-weight: bold;
        }
    </style>
    <?php if (!isset($_SESSION['user_id'])): ?>
<div class="d-flex align-items-center gap-3">
    <a href="../googleAuth/google-login.php" class="btn btn-outline-danger d-flex align-items-center">
        <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/53/Google_%22G%22_Logo.svg/512px-Google_%22G%22_Logo.svg.png" alt="Google Logo" style="width: 20px; height: 20px; margin-right: 10px;">
        Sign in with Google
    </a>
</div>
<?php endif; ?>

</head>

<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar text-white p-3">
            <div class="text-center mb-4">
                <img src="../images/logo.png" alt="Logo" class="img-fluid" style="max-width: 100px;">
                <h5 class="mt-2">Rent Billing Management System</h5>
            </div>
            <a href="admin.php" class="d-flex align-items-center justify-content-between mb-4 px-2 nav-link admin-border">
                <div class="d-flex align-items-center">
                    <span class="material-icons me-2" style="font-size: 32px;">person</span>
                    <span class="fw-bold">Admin</span>
                </div>
                <span class="material-icons" style="font-size: 20px;">chevron_right</span>
            </a>
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a class="nav-link text-white active" href="dashboard.php"><span class="material-icons me-2">dashboard</span>Dashboard</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="rent_payment.php"><span class="material-icons me-2">payments</span>Rent Payment</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="receipt.php"><span class="material-icons me-2">receipt</span>Receipt/Invoice</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="financial_rep.php"><span class="material-icons me-2">assessment</span>Financial Report</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="boarder_profile.php"><span class="material-icons me-2">group</span>Boarders Profile</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="communication.php"><span class="material-icons me-2">chat</span>Communication</a>
                </li>
            </ul>
            <div class="mt-2">
                <h6 class="text-uppercase small">Sub Menu</h6>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2">
                        <a class="nav-link text-white" href="sub-menu/notif.php"><span class="material-icons me-2">notifications</span>Notification</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link text-white" href="sub-menu/settings.php"><span class="material-icons me-2">settings</span>Settings</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link text-white" href="sub-menu/logout.php"><span class="material-icons me-2">logout</span>Log Out</a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="flex-grow-1">
            <header class="d-flex justify-content-between align-items-center p-3 border-bottom">
                <h4 class="mb-0" style="font-weight: bold;"><span id="boardingHouseName"><?php echo htmlspecialchars($user['boardinghousename'] ?? 'N/A'); ?></span> Boarding House</h4>
                <div class="d-flex align-items-center gap-3">
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
                        DASHBOARD
                    </h5>
                </div>

                <div class="container mt-5" style="font-family: 'Segoe UI', sans-serif;">
                    <div class="row g-4">
                        <!-- Total Boarders -->
                        <div class="col-md-4 col-lg-3">
                            <div class="card shadow rounded-4 border-0">
                                <div class="card-body text-center">
                                    <span class="material-icons card-icon mb-2">group</span>
                                    <h6 class="card-title">Total Boarders</h6>
                                    <p class="card-text"><?php echo $total_boarders; ?></p>
                                </div>
                            </div>
                        </div>
                        <!-- Paid Boarders -->
                        <div class="col-md-4 col-lg-3">
                            <div class="card shadow rounded-4 border-0">
                                <div class="card-body text-center">
                                    <span class="material-icons card-icon mb-2">check_circle</span>
                                    <h6 class="card-title">Paid Boarders</h6>
                                    <p class="card-text"><?php echo $paid_boarders; ?></p>
                                </div>
                            </div>
                        </div>
                        <!-- Pending Boarders -->
                        <div class="col-md-4 col-lg-3">
                            <div class="card shadow rounded-4 border-0">
                                <div class="card-body text-center">
                                    <span class="material-icons card-icon mb-2">pending</span>
                                    <h6 class="card-title">Pending Boarders</h6>
                                    <p class="card-text"><?php echo $pending_boarders; ?></p>
                                </div>
                            </div>
                        </div>
                        <!-- Partial Boarders -->
                        <div class="col-md-4 col-lg-3">
                            <div class="card shadow rounded-4 border-0">
                                <div class="card-body text-center">
                                    <span class="material-icons card-icon mb-2">donut_large</span>
                                    <h6 class="card-title">Partial Boarders</h6>
                                    <p class="card-text"><?php echo $partial_boarders; ?></p>
                                </div>
                            </div>
                        </div>
                        <!-- Overdue Boarders -->
                        <div class="col-md-4 col-lg-3">
                            <div class="card shadow rounded-4 border-0">
                                <div class="card-body text-center">
                                    <span class="material-icons card-icon mb-2">exclamation_triangle</span>
                                    <h6 class="card-title">Overdue Boarders</h6>
                                    <p class="card-text"><?php echo $overdue_boarders; ?></p>
                                </div>
                            </div>
                        </div>
                        <!-- Monthly Collection vs Expected -->
                        <div class="col-md-4 col-lg-3">
                            <div class="card shadow rounded-4 border-0">
                                <div class="card-body text-center">
                                    <span class="material-icons card-icon mb-2">cash_stack</span>
                                    <h6 class="card-title">Monthly Collection</h6>
                                    <p class="card-text">₱<?php echo number_format($monthly_collection, 2); ?> / ₱<?php echo number_format($expected_collection, 2); ?></p>
                                </div>
                            </div>
                        </div>
                        <!-- Visitor Collection -->
                        <div class="col-md-4 col-lg-3">
                            <div class="card shadow rounded-4 border-0">
                                <div class="card-body text-center">
                                    <span class="material-icons card-icon mb-2">person_add</span>
                                    <h6 class="card-title">Visitor Collection</h6>
                                    <p class="card-text">₱<?php echo number_format($visitor_collection, 2); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Shortcut Buttons -->
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <a href="rent_payment.php" id="btnRecordPayment" class="btn btn-primary w-100 shadow-sm d-flex justify-content-center align-items-center text-white text-decoration-none">
                                <span class="material-icons me-2">attach_money</span>
                                Record Payment
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="button_payment/visitors_payment.php" id="btnRecordVisitorPayment" class="btn btn-secondary w-100 shadow-sm d-flex justify-content-center align-items-center text-white text-decoration-none">
                                <span class="material-icons me-2">person_add_alt</span>
                                Record Visitor Payment
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="add_boarder.php" id="btnAddNewBoarder" class="btn btn-success w-100 shadow-sm d-flex justify-content-center align-items-center text-white text-decoration-none">
                                <span class="material-icons me-2">how_to_reg</span>
                                Add New Boarder
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>