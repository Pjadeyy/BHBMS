<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../authentication/login.php");
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
$stmt = $conn->prepare("SELECT monthly_rate, appliances_rate, late_fee, due_date FROM rates WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$rate = $result->fetch_assoc();
$monthly_rate = $rate['monthly_rate'] ?? 1000.00;
$appliances_rate = $rate['appliances_rate'] ?? 100.00;
$late_fee = $rate['late_fee'] ?? 0.00;
$due_date = $rate['due_date'] ?? date('Y-m-10');
$stmt->close();

// Fetch boarder payment data
$boarder_payments = [];
$current_month = date('Y-m');
$month_start = $current_month . '-01';
$month_end = date('Y-m-t');
$stmt = $conn->prepare("
    SELECT b.id, CONCAT(b.firstname, ' ', b.lastname) as name, b.room, 
           COALESCE(SUM(p.amount), 0) as paid,
           COALESCE(MAX(p.appliances), 0) as appliances,
           CASE 
               WHEN COALESCE(SUM(p.amount), 0) >= (? + (COALESCE(MAX(p.appliances), 0) * ?)) THEN 'Paid'
               WHEN COALESCE(SUM(p.amount), 0) > 0 THEN 'Partial'
               WHEN CURRENT_DATE > ? AND COALESCE(SUM(p.amount), 0) = 0 THEN 'Overdue'
               ELSE 'Pending'
           END as status
    FROM boarders b
    LEFT JOIN payments p ON b.id = p.boarder_id 
        AND p.payment_date BETWEEN ? AND ? 
        AND p.payment_type = 'rent'
    WHERE b.user_id = ? AND b.status = 'active'
    GROUP BY b.id
");
$stmt->bind_param("ddsssi", $monthly_rate, $appliances_rate, $due_date, $month_start, $month_end, $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $row['rent'] = $monthly_rate + ($appliances_rate * $row['appliances']);
    $row['balance'] = $row['rent'] - $row['paid'];
    $row['status'] = $row['status'] ?? 'Pending';
    $boarder_payments[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rent Payment - Boarding House</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .bg-purple { background-color: #360938; }
        .card-header h5 { color: white; }
        #boarderTablePayment { margin-left: 0; }
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
                    <a class="nav-link text-white active" href="rent_payment.php"><span class="material-icons me-2">payments</span>Rent Payment</a>
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
                <div class="mb-4 d-flex justify-content-between align-items-center">
                    <h5 style="font-weight: bold; color: #360938; border-bottom: 2px solid #360938; padding-bottom: 10px;">
                        RENT PAYMENT
                    </h5>
                    <a href="button_payment/visitors_payment.php" class="btn btn-success d-flex align-items-center">
                        <i class="material-icons me-1">person_add</i> Record Visitors Payment
                    </a>
                </div>

                <!-- Boarder Table -->
                <div class="card shadow-lg mb-4">
                    <div class="card-header bg-purple text-white py-3">
                        <h5 class="mb-0 fw-bold text-uppercase">BOARDER PAYMENTS</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <input type="text" id="searchName" class="form-control" placeholder="Search by Name">
                            </div>
                            <div class="col-md-4">
                                <input type="text" id="searchRoom" class="form-control" placeholder="Search by Room">
                            </div>
                            <div class="col-md-4">
                                <select id="searchStatus" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="Paid">Paid</option>
                                    <option value="Partial">Partial</option>
                                    <option value="Pending">Pending</option>
                                    <option value="Overdue">Overdue</option>
                                </select>
                            </div>
                        </div>
                        <table id="boarderTablePayment" class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Profile</th>
                                    <th>Name</th>
                                    <th>Room</th>
                                    <th>Rent</th>
                                    <th>Status</th>
                                    <th>Paid</th>
                                    <th>Balance</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Payment Form -->
                <div class="card shadow-lg mb-4">
                    <div class="card-header bg-purple text-white py-3">
                        <h5 class="mb-0 fw-bold text-uppercase">RECORD PAYMENT</h5>
                    </div>
                    <div class="card-body">
                        <form id="rentPaymentForm">
                            <!-- SECTION 1: Boarder Details -->
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
                                    <label for="name" class="form-label d-flex align-items-center">
                                        <i class="material-icons me-2">person</i> Name
                                    </label>
                                    <input type="text" class="form-control" id="name" readonly>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="roomNumber" class="form-label d-flex align-items-center">
                                        <i class="material-icons me-2">meeting_room</i> Room Number
                                    </label>
                                    <input type="text" class="form-control" id="roomNumber" readonly>
                                </div>
                            </div>

                            <!-- SECTION 2: Rent Details -->
                            <hr>
                            <h6 class="mb-3">Rent Details</h6>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="rent" class="form-label d-flex align-items-center">
                                        <i class="material-icons me-2">home</i> Monthly Rate
                                    </label>
                                    <input type="number" class="form-control" id="rent" value="<?php echo $monthly_rate; ?>" readonly>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="appliances" class="form-label d-flex align-items-center">
                                        <i class="material-icons me-2">electrical_services</i> No. of Appliances
                                    </label>
                                    <input type="number" class="form-control" id="appliances" name="appliances" min="0" value="0">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="Totalappliances" class="form-label d-flex align-items-center">
                                        <i class="material-icons me-2">electrical_services</i> Total Appliances Charge
                                    </label>
                                    <input type="number" class="form-control" id="Totalappliances" readonly>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="paymentDate" class="form-label d-flex align-items-center">
                                        <i class="material-icons me-2">calendar_today</i> Payment Date
                                    </label>
                                    <input type="date" class="form-control" id="paymentDate" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="isLate" class="form-label d-flex align-items-center">
                                        <i class="material-icons me-2">report</i> Is Late?
                                    </label>
                                    <select class="form-select" id="isLate" name="is_late" disabled>
                                        <option value="No">No</option>
                                        <option value="Yes">Yes</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="TotalLateCharge" class="form-label d-flex align-items-center">
                                        <i class="material-icons me-2">report</i> Late Fee
                                    </label>
                                    <input type="number" class="form-control" id="TotalLateCharge" value="<?php echo $late_fee; ?>" readonly>
                                </div>
                            </div>

                            <!-- SECTION 3: Transaction Details -->
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
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary d-flex align-items-center">
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
        // Boarder Table Population
        let boarderIdCounter = 1;
        const boarderData = <?php echo json_encode($boarder_payments); ?>;

        function addBoarderRow({ id, name, room, rent, status, paid, balance, appliances }) {
            const tableBody = document.querySelector("#boarderTablePayment tbody");
            const newRow = tableBody.insertRow();
            newRow.setAttribute('id', `boarder-${id}`);

            newRow.innerHTML = `
                <td>${id}</td>
                <td>👤</td>
                <td>${name}</td>
                <td>${room || 'N/A'}</td>
                <td>₱${parseFloat(rent).toFixed(2)}</td>
                <td><span class="badge bg-${getStatusColor(status)}">${status}</span></td>
                <td>₱${parseFloat(paid).toFixed(2)}</td>
                <td>₱${parseFloat(balance).toFixed(2)}</td>
                <td>
                    <a href="boarder_profile.php?id=${id}" class="btn btn-outline-info btn-sm me-2" title="View Profile">
                        <i class="fas fa-eye"></i>
                    </a>
                    <button class="btn btn-outline-success btn-sm select-boarder" data-id="${id}" data-appliances="${appliances}" title="Record Payment">
                        <i class="fas fa-coins"></i>
                    </button>
                </td>
            `;
        }

        function getStatusColor(status) {
            switch (status.toLowerCase()) {
                case 'pending': return 'warning';
                case 'overdue': return 'danger';
                case 'paid': return 'success';
                case 'partial': return 'primary';
                default: return 'secondary';
            }
        }

        boarderData.forEach(boarder => addBoarderRow(boarder));

        // Table Filtering
        const searchName = document.getElementById('searchName');
        const searchRoom = document.getElementById('searchRoom');
        const searchStatus = document.getElementById('searchStatus');

        function filterTables() {
            const nameFilter = searchName.value.toLowerCase();
            const roomFilter = searchRoom.value.toLowerCase();
            const statusFilter = searchStatus.value;

            const boardersRows = document.querySelectorAll('#boarderTablePayment tbody tr');
            boardersRows.forEach(row => {
                const name = row.cells[2].textContent.toLowerCase();
                const room = row.cells[3].textContent.toLowerCase();
                const status = row.cells[5].textContent;

                const match = name.includes(nameFilter) &&
                             room.includes(roomFilter) &&
                             (statusFilter === "" || status === statusFilter);

                row.style.display = match ? "" : "none";
            });
        }

        searchName.addEventListener('input', filterTables);
        searchRoom.addEventListener('input', filterTables);
        searchStatus.addEventListener('change', filterTables);

        // Form Calculations
        const boarderIdSelect = document.getElementById('boarderId');
        const nameInput = document.getElementById('name');
        const roomInput = document.getElementById('roomNumber');
        const appliancesInput = document.getElementById('appliances');
        const TotalappliancesInput = document.getElementById('Totalappliances');
        const paymentDateInput = document.getElementById('paymentDate');
        const isLateSelect = document.getElementById('isLate');
        const TotalLateChargeInput = document.getElementById('TotalLateCharge');
        const totalDueInput = document.getElementById('totalDue');
        const paidInput = document.getElementById('paid');
        const chargeInput = document.getElementById('charge');
        const balanceInput = document.getElementById('balance');
        const statusSelect = document.getElementById('status');
        const monthlyRate = <?php echo $monthly_rate; ?>;
        const appliancesRate = <?php echo $appliances_rate; ?>;
        const lateFee = <?php echo $late_fee; ?>;
        const dueDate = new Date('<?php echo $due_date; ?>');

        boarderIdSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            nameInput.value = selectedOption.dataset.name || '';
            roomInput.value = selectedOption.dataset.room || '';
            appliancesInput.value = 0;
            calculateTotals();
        });

        appliancesInput.addEventListener('input', calculateTotals);
        paymentDateInput.addEventListener('change', calculateTotals);
        paidInput.addEventListener('input', calculateTotals);

        function calculateTotals() {
            const appliances = parseInt(appliancesInput.value) || 0;
            const paymentDate = new Date(paymentDateInput.value);
            const isLate = paymentDate > dueDate ? 'Yes' : 'No';
            isLateSelect.value = isLate;

            const totalAppliances = appliances * appliancesRate;
            const totalLateCharge = isLate === 'Yes' ? lateFee : 0;
            const totalDue = monthlyRate + totalAppliances + totalLateCharge;

            TotalappliancesInput.value = totalAppliances.toFixed(2);
            TotalLateChargeInput.value = totalLateCharge.toFixed(2);
            totalDueInput.value = totalDue.toFixed(2);

            const paid = parseFloat(paidInput.value) || 0;
            const change = paid > totalDue ? paid - totalDue : 0;
            const balance = paid < totalDue ? totalDue - paid : 0;

            chargeInput.value = change.toFixed(2);
            balanceInput.value = balance.toFixed(2);

            // Update status based on payment and due date
            let newStatus;
            if (paid >= totalDue) {
                newStatus = 'Paid';  // If fully paid, always mark as Paid regardless of payment date
            } else if (paid > 0) {
                newStatus = 'Partial';
            } else {
                newStatus = isLate === 'Yes' ? 'Overdue' : 'Pending';
            }
            statusSelect.value = newStatus;
        }

        // Select Boarder from Table
        document.addEventListener('click', function(e) {
            if (e.target.closest('.select-boarder')) {
                const button = e.target.closest('.select-boarder');
                const boarderId = button.dataset.id;
                const appliances = button.dataset.appliances || 0;
                boarderIdSelect.value = boarderId;
                appliancesInput.value = appliances;
                const event = new Event('change');
                boarderIdSelect.dispatchEvent(event);
                calculateTotals();
            }
        });

        // Form Submission
        $('#rentPaymentForm').on('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('payment_type', 'rent');

            $.ajax({
                url: 'process_payment.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    const result = JSON.parse(response);
                    if (result.success) {
                        alert('Payment recorded successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + result.error);
                    }
                },
                error: function() {
                    alert('Failed to record payment.');
                }
            });
        });
    </script>
</body>
</html>