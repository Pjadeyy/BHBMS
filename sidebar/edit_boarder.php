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
// Fetch user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT firstname, lastname, email, contact, government_id, tin_num, address, boardinghousename FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$user_id = $_SESSION['user_id'];
$message = '';

// Check if boarder ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: boarder_profile.php");
    exit();
}

$boarder_id = $_GET['id'];

// Fetch boarder details
$stmt = $conn->prepare("SELECT * FROM boarders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $boarder_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $message = '<div class="alert alert-danger">Boarder not found or you do not have permission to edit this boarder.</div>';
    $boarder = null;
} else {
    $boarder = $result->fetch_assoc();
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $boarder) {
    $new_boarder_id = trim($_POST['boarder_id']);
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $move_in_date = trim($_POST['move_in_date']);
    $monthly_rate = trim($_POST['monthly_rate']);
    $deposit_amount = trim($_POST['deposit_amount']);
    $room = trim($_POST['room']);
    $status = trim($_POST['status']);
    $appliances = trim($_POST['appliances']);

    // Validate required fields
    if (empty($new_boarder_id) || empty($firstname) || empty($lastname) || empty($email) || empty($move_in_date) || empty($monthly_rate) || empty($deposit_amount)) {
        $message = '<div class="alert alert-danger">All required fields must be filled.</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert alert-danger">Invalid email format.</div>';
    } elseif (!is_numeric($monthly_rate) || $monthly_rate < 0) {
        $message = '<div class="alert alert-danger">Monthly rate must be a non-negative number.</div>';
    } elseif (!is_numeric($deposit_amount) || $deposit_amount < 0) {
        $message = '<div class="alert alert-danger">Deposit amount must be a non-negative number.</div>';
    } elseif (!is_numeric($appliances) || $appliances < 0) {
        $message = '<div class="alert alert-danger">Appliances must be a non-negative number.</div>';
    } else {
        // Check if boarder_id is unique (excluding current boarder)
        $stmt = $conn->prepare("SELECT id FROM boarders WHERE boarder_id = ? AND id != ?");
        $stmt->bind_param("ii", $new_boarder_id, $boarder_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $message = '<div class="alert alert-danger">Boarder ID is already in use.</div>';
            $stmt->close();
        } else {
            $stmt->close();
            // Update boarder data
            $stmt = $conn->prepare("
                UPDATE boarders 
                SET boarder_id = ?, firstname = ?, lastname = ?, email = ?, phone = ?, address = ?, 
                    move_in_date = ?, monthly_rate = ?, deposit_amount = ?, room = ?, status = ?, appliances = ?
                WHERE id = ? AND user_id = ?
            ");
            $stmt->bind_param("issssssddssiii", $new_boarder_id, $firstname, $lastname, $email, $phone, $address, 
                              $move_in_date, $monthly_rate, $deposit_amount, $room, $status, $appliances, $boarder_id, $user_id);
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Boarder updated successfully! <a href="boarder_profile.php">Back to Boarders List</a></div>';
            } else {
                $message = '<div class="alert alert-danger">Failed to update boarder: ' . $conn->error . '</div>';
            }
            $stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Boarder - Boarding House</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
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
                    <a class="nav-link text-white" href="dashboard.php"><span class="material-icons me-2">dashboard</span>Dashboard</a>
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
                    <a class="nav-link text-white active" href="boarder_profile.php"><span class="material-icons me-2">group</span>Boarders Profile</a>
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
                        EDIT BOARDER
                    </h5>
                </div>

                <div class="container mt-5" style="font-family: 'Segoe UI', sans-serif;">
                    <?php echo $message; ?>
                    <?php if ($boarder): ?>
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="boarder_id" class="form-label">Boarder ID</label>
                                    <input type="number" class="form-control" id="boarder_id" name="boarder_id" value="<?php echo htmlspecialchars($boarder['boarder_id']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="firstname" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="firstname" name="firstname" value="<?php echo htmlspecialchars($boarder['firstname']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="lastname" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="lastname" name="lastname" value="<?php echo htmlspecialchars($boarder['lastname']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($boarder['email']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($boarder['phone'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($boarder['address'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="move_in_date" class="form-label">Move-In Date</label>
                                    <input type="date" class="form-control" id="move_in_date" name="move_in_date" value="<?php echo htmlspecialchars($boarder['move_in_date']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="monthly_rate" class="form-label">Monthly Rate (₱)</label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="monthly_rate" name="monthly_rate" value="<?php echo htmlspecialchars($boarder['monthly_rate']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="deposit_amount" class="form-label">Deposit Amount (₱)</label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="deposit_amount" name="deposit_amount" value="<?php echo htmlspecialchars($boarder['deposit_amount']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="room" class="form-label">Room</label>
                                    <input type="text" class="form-control" id="room" name="room" value="<?php echo htmlspecialchars($boarder['room'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="active" <?php echo $boarder['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $boarder['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="appliances" class="form-label">Number of Appliances</label>
                                    <input type="number" min="0" class="form-control" id="appliances" name="appliances" value="<?php echo htmlspecialchars($boarder['appliances'] ?? 0); ?>">
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                <a href="boarder_profile.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>