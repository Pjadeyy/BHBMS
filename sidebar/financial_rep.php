 <?php
  session_start();

        if (!isset($_SESSION['user_id'])) {
        header("Location: ../authentication/login.php");
        exit();
        }

    $host = 'localhost';
    $username = 'root'; 
    $password = ''; // Replace with your MySQL password
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

    // Fetch financial data
    $current_month = date('Y-m');
    $current_year = date('Y');
    $month_start = $current_month . '-01';
    $month_end = date('Y-m-t');
    $year_start = $current_year . '-01-01';
    $year_end = $current_year . '-12-31';

    // Total Monthly Collection (rent + visitor)
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE user_id = ? AND payment_date BETWEEN ? AND ?");
    $stmt->bind_param("iss", $user_id, $month_start, $month_end);
    $stmt->execute();
    $result = $stmt->get_result();
    $monthly_total = $result->fetch_assoc()['total'];
    $stmt->close();

    // Total Yearly Collection (rent + visitor)
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE user_id = ? AND payment_date BETWEEN ? AND ?");
    $stmt->bind_param("iss", $user_id, $year_start, $year_end);
    $stmt->execute();
    $result = $stmt->get_result();
    $yearly_total = $result->fetch_assoc()['total'];
    $stmt->close();

    // Total Visitors Collection (monthly)
    $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) AS total FROM payments WHERE user_id = ? AND payment_type = 'visitor' AND payment_date BETWEEN ? AND ?");
    $stmt->bind_param("iss", $user_id, $month_start, $month_end);
    $stmt->execute();
    $result = $stmt->get_result();
    $visitors_monthly_total = $result->fetch_assoc()['total'];
    $stmt->close();

    $conn->close();
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Financial Report - Boarding House</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
        <link rel="stylesheet" href="../sidebar/style.css">
        <style>
            .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            }
            .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(83, 8, 102, 0.2);
            }
            .card h5 {
            font-size: 1.2rem;
            margin-bottom: 10px;
            }
            .card h2 {
            font-size: 2rem;
            margin-top: 0;
            }
            .btn {
            border-radius: 10px;
            }
            .btn-outline-purple {
            color: #170c2b;
            border: 1px solid #170c2b;
            }
            .btn-outline-purple:hover {
            background-color: #ff9800;
            color: white;
            }
        </style>
    </head>
<body>
    <div class="d-flex">
<!-- Sidebar -->
    <nav class="sidebar text-white p-3">
    <div class="text-center mb-4">
        <img src="../images/logo.png" alt="Logo" class="img-fluid" style="max-width: 100px;">
        <h5 class="mt-2">Rent Billing Management System</h5>
    </div>
        <a href="../sidebar/admin.php" class="d-flex align-items-center justify-content-between mb-4 px-2 nav-link admin-border">
            <div class="d-flex align-items-center">
                <span class="material-icons me-2" style="font-size: 32px;">person</span>
                <span class="fw-bold">Admin</span>
            </div>
            <span class="material-icons" style="font-size: 20px;">chevron_right</span>
        </a>
    <ul class="nav flex-column">
        <li class="nav-item mb-2">
            <a class="nav-link text-white" href="../sidebar/dashboard.php"><span class="material-icons me-2">dashboard</span>Dashboard</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link text-white" href="../sidebar/rent_payment.php"><span class="material-icons me-2">payments</span>Rent Payment</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link text-white" href="../sidebar/receipt.php"><span class="material-icons me-2">receipt</span>Receipt/Invoice</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link text-white active" href="../sidebar/financial_rep.php"><span class="material-icons me-2">assessment</span>Financial Report</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link text-white" href="../sidebar/boarder_profile.php"><span class="material-icons me-2">group</span>Boarders Profile</a>
        </li>
        <li class="nav-item mb-2">
            <a class="nav-link text-white" href="../sidebar/communication.php"><span class="material-icons me-2">chat</span>Communication</a>
        </li>
        </ul>
    <div class="mt-2">
            <h6 class="text-uppercase small">Sub Menu</h6>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2">
                        <a class="nav-link text-white" href="../sidebar/sub-menu/notif.php"><span class="material-icons me-2">notifications</span>Notification</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link text-white" href="../sidebar/sub-menu/settings.php"><span class="material-icons me-2">settings</span>Settings</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link text-white" href="../sidebar/sub-menu/logout.php"><span class="material-icons me-2">logout</span>Log Out</a>
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
            FINANCIAL REPORT
        </h5>
    </div>

<div class="row mb-4 mt-5">
<!-- Monthly Total Card -->
        <div class="col-md-4">
            <div class="card border border-secondary-subtle rounded-3 h-100" id="monthly-card">
            <div class="card-body d-flex flex-column justify-content-between">
        <div>
            <h6 class="text-muted">Total Monthly Collection</h6>
            <h3 class="fw-semibold mb-0" id="monthly-total">₱<?php echo number_format($monthly_total, 2); ?></h3>
        </div>
        <a href="../sidebar/button_collection/monthly_report.php" class="btn btn-outline-purple btn-sm mt-3">
            View Monthly Report
        </a>
        </div>
    </div>
</div>

<!-- Yearly Total Card -->
    <div class="col-md-4">
        <div class="card border border-secondary-subtle rounded-3 h-100" id="yearly-card101">
        <div class="card-body d-flex flex-column justify-content-between">
    <div>
        <h6 class="text-muted">Total Yearly Collection</h6>
        <h3 class="fw-semibold mb-0" id="yearly-total">₱<?php echo number_format($yearly_total, 2); ?></h3>
    </div>
        <a href="../sidebar/button_collection/yearly_report.php" class="btn btn-outline-purple btn-sm mt-3">
    View Yearly Report
        </a>
        </div>
    </div>
</div>

    <!-- Total Visitors Monthly Collection Card -->
    <div class="col-md-4">
        <div class="card border border-secondary-subtle rounded-3 h-100" id="visitors-monthly-card">
        <div class="card-body d-flex flex-column justify-content-between">
    <div>
        <h6 class="text-muted">Total Visitors Collection</h6>
        <h3 class="fw-semibold mb-0" id="visitors-monthly-total">₱<?php echo number_format($visitors_monthly_total, 2); ?></h3>
    </div>
        <a href="../sidebar/button_collection/visitors_report.php" class="btn btn-outline-purple btn-sm mt-3">
        View Visitors Report
    </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>