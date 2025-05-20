<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../authentication/login.php");
    exit();
}

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


// Fetch boarders
$boarders = [];
$stmt = $conn->prepare("SELECT id, CONCAT(firstname, ' ', lastname) as name, room FROM boarders WHERE user_id = ? AND status = 'active'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $boarders[] = $row;
}
$stmt->close();

// Fetch rates
$stmt = $conn->prepare("SELECT monthly_rate FROM rates WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$rate = $result->fetch_assoc();
$monthly_rate = $rate['monthly_rate'] ?? 5000.00;
$daily_rate = 100.00; // Fixed daily rate for visitors
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Record Visitors Payment - Boarding House</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        .bg-purple { background-color: #360938; }
        .card-header h5 { color: white; }
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
                    <a class="nav-link text-white active" href="../rent_payment.php"><span class="material-icons me-2">payments</span>Rent Payment</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="../receipt.php"><span class="material-icons me-2">receipt</span>Receipt/Invoice</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="../financial_rep.php"><span class="material-icons me-2">assessment</span>Financial Report</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="../boarder_profile.php"><span class="material-icons me-2">group</span>Boarders Profile</a>
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
                <h5 style="font-weight: bold; color: #360938; border-bottom: 2px solid #360938; padding-bottom: 10px;">
                    RECORD VISITORS PAYMENT
                </h5>

                <div class="card shadow-lg mb-4">
                    <div class="card-header bg-purple text-white py-3">
                        <h5 class="mb-0 fw-bold text-uppercase">VISITOR PAYMENT FORM</h5>
                    </div>
                    <div class="card-body">
                        <form id="visitorPaymentForm">
                            <!-- SECTION 1: Visitor Details -->
                            <h6 class="mb-3">Visitor Details</h6>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="visitorId" class="form-label d-flex align-items-center">
                                        <i class="material-icons me-2">badge</i> Visitor ID
                                    </label>
                                    <input type="text" class="form-control" id="visitorId" name="visitor_id" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="visitorName" class="form-label d-flex align-items-center">
                                        <i class="material-icons me-2">person</i> Visitor Name
                                    </label>
                                    <input type="text" class="form-control" id="visitorName" name="visitor_name" required>
                                </div>
                            </div>

                            <!-- SECTION 2: Boarder Details -->
                            <hr>
                            <h6 class="mb-3">Boarder Details</h6>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="boarderId" class="form-label d-flex align-items-center">
                                        <i class="material-icons me-2">badge</i> Boarder
                                    </label>
                                    <select class="form-select" id="boarderId" name="boarder_id" required>
                                        <option value="">Select Boarder</option>
                                        <?php foreach ($boarders as $boarder): ?>
                                            <option value="<?php echo $boarder['id']; ?>" 
                                                    data-name="<?php echo htmlspecialchars($boarder['name']); ?>" 
                                                    data-room="<?php echo htmlspecialchars($boarder['room']); ?>">
                                                <?php echo htmlspecialchars($boarder['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="boarderName" class="form-label d-flex align-items-center">
                                        <i class="material-icons me-2">person</i> Boarder Name
                                    </label>
                                    <input type="text" class="form-control" id="boarderName" readonly>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="boarderRoom" class="form-label d-flex align-items-center">
                                        <i class="material-icons me-2">meeting_room</i> Room Number
                                    </label>
                                    <input type="text" class="form-control" id="boarderRoom" readonly>
                                </div>
                            </div>

                            <!-- SECTION 3: Visitors Stay Fees -->
                            <hr>
                            <h6 class="mb-3">Visitors Stay Fees</h6>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="days" class="form-label d-flex align-items-center">
                                        <i class="material-icons me-2">calendar_today</i> Duration of Stay (Days)
                                    </label>
                                    <input type="number" class="form-control" id="days" name="days" min="0" value="0" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="daysFee" class="form-label d-flex align-items-center">
                                        <i class="material-icons me-2">home</i> Total Stay Fee
                                    </label>
                                    <input type="number" class="form-control" id="daysFee" readonly>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="paymentDate" class="form-label d-flex align-items-center">
                                        <i class="material-icons me-2">calendar_today</i> Payment Date
                                    </label>
                                    <input type="date" class="form-control" id="paymentDate" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>

                            <!-- SECTION 4: Transaction Details -->
                            <hr>
                            <h6 class="mb-3">Transaction Details</h6>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="totalDue" class="form-label d-flex align-items-center">
                                        <i class="material-icons me-2">request_quote</i> Total Due
                                    </label>
                                    <input type="number" class="form-control" id="totalDue" readonly>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="modeOfPayment" class="form-label d-flex align-items-center">
                                        <i class="material-icons me-2">credit_card</i> Mode of Payment
                                    </label>
                                    <select class="form-select" id="modeOfPayment" name="mode_of_payment" required>
                                        <option value="Cash">Cash</option>
                                        <option value="GCash">GCash</option>
                                        <option value="Bank Transfer">Bank Transfer</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="paid" class="form-label d-flex align-items-center">
                                        <i class="material-icons me-2">paid</i> Paid Amount
                                    </label>
                                    <input type="number" class="form-control" id="paid" name="paid" step="0.01" min="0" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="charge" class="form-label d-flex align-items-center">
                                        <i class="material-icons me-2">money_off</i> Change
                                    </label>
                                    <input type="number" class="form-control" id="charge" readonly>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="balance" class="form-label d-flex align-items-center">
                                        <i class="material-icons me-2">account_balance_wallet</i> Balance
                                    </label>
                                    <input type="number" class="form-control" id="balance" readonly>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="status" class="form-label d-flex align-items-center">
                                        <i class="material-icons me-2">info</i> Payment Status
                                    </label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="Paid">Paid</option>
                                        <option value="Partial">Partial</option>
                                        <option value="Pending">Pending</option>
                                        <option value="Overdue">Overdue</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-secondary d-flex align-items-center" onclick="goBack()">
                                    <i class="material-icons me-1">arrow_back</i> Back
                                </button>
                                <button type="submit" class="btn btn-success d-flex align-items-center">
                                    <i class="material-icons me-1">save</i> Save Payment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function goBack() {
            window.history.back();
        }

        const boarderIdSelect = document.getElementById('boarderId');
        const boarderNameInput = document.getElementById('boarderName');
        const boarderRoomInput = document.getElementById('boarderRoom');
        const daysInput = document.getElementById('days');
        const daysFeeInput = document.getElementById('daysFee');
        const paymentDateInput = document.getElementById('paymentDate');
        const totalDueInput = document.getElementById('totalDue');
        const paidInput = document.getElementById('paid');
        const chargeInput = document.getElementById('charge');
        const balanceInput = document.getElementById('balance');
        const statusSelect = document.getElementById('status');
        const dailyRate = <?php echo $daily_rate; ?>;

        boarderIdSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            boarderNameInput.value = selectedOption.dataset.name || '';
            boarderRoomInput.value = selectedOption.dataset.room || '';
            calculateTotals();
        });

        daysInput.addEventListener('input', calculateTotals);
        paidInput.addEventListener('input', calculateTotals);

        function calculateTotals() {
            const days = parseInt(daysInput.value) || 0;
            const daysFee = days * dailyRate;
            daysFeeInput.value = daysFee.toFixed(2);
            totalDueInput.value = daysFee.toFixed(2);

            const paid = parseFloat(paidInput.value) || 0;
            const change = paid > daysFee ? paid - daysFee : 0;
            const balance = paid < daysFee ? daysFee - paid : 0;

            chargeInput.value = change.toFixed(2);
            balanceInput.value = balance.toFixed(2);

            statusSelect.value = paid >= daysFee ? 'Paid' : (paid > 0 ? 'Partial' : 'Pending');
        }

        $('#visitorPaymentForm').on('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('payment_type', 'visitor');

            $.ajax({
                url: '../process_payment.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    const result = JSON.parse(response);
                    if (result.success) {
                        alert('Visitor payment recorded successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + result.error);
                    }
                },
                error: function() {
                    alert('Failed to record visitor payment.');
                }
            });
        });
    </script>
</body>
</html>