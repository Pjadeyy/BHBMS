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
$current_month = date('Y-m');
$month_start = $current_month . '-01';
$month_end = date('Y-m-t');

// Fetch user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT firstname, lastname, email, contact, government_id, tin_num, address, boardinghousename FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Fetch rates
$stmt = $conn->prepare("SELECT monthly_rate FROM rates WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$rate = $result->fetch_assoc();
$monthly_rate = $rate['monthly_rate'] ?? 1000.00;
$daily_rate = 100.00; // Fixed daily rate for visitors
$stmt->close();

// Fetch visitor payments
$payments = [];
$stmt = $conn->prepare("
    SELECT p.payment_date, v.id AS visitor_id, v.name AS visitor_name,
           b.id AS boarder_id, CONCAT(b.firstname, ' ', b.lastname) AS boarder_name,
           b.room, p.days, p.amount
    FROM payments p
    JOIN boarders b ON p.boarder_id = b.id
    LEFT JOIN visitors v ON p.boarder_id = v.boarder_id AND p.user_id = v.user_id
    WHERE p.user_id = ? AND p.payment_type = 'visitor' AND p.payment_date BETWEEN ? AND ?
");
$stmt->bind_param("iss", $user_id, $month_start, $month_end);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $row['visitor_id'] = $row['visitor_id'] ?? 'N/A';
    $row['visitor_name'] = $row['visitor_name'] ?? 'N/A';
    $payments[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Visitor's Financial Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        .btn-primary {
            background-color: #360938;
            border-color: #360938;
        }
        .btn-primary:hover {
            background-color: #4a0d4a;
            border-color: #4a0d4a;
        }
        #exportBtn {
            padding: 10px 20px;
            font-size: 16px;
            font-weight: bold;
            border-radius: 8px;
            background-color: #6f42c1;
            color: white;
            border: none;
            transition: transform 0.3s ease, background-color 0.3s ease;
        }
        #exportBtn:hover {
            background-color: #360938;
            transform: scale(1.05);
        }
        #exportBtn:active {
            transform: scale(1.1);
        }
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
        .print-area { display: none; }
        @media print {
            body * { visibility: hidden; }
            .print-area, .print-area * { visibility: visible; }
            .print-area { position: absolute; top: 0; left: 0; width: 100%; }
        }
        #previewContent {
            font-family: Arial, sans-serif;
        }
        #previewContent .header {
            text-align: center;
            margin-bottom: 20px;
        }
        #previewContent .header h2 {
            color: #360938;
            margin-bottom: 10px;
        }
        #previewContent table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        #previewContent th {
            background-color: #360938;
            color: white;
            padding: 10px;
            text-align: center;
        }
        #previewContent td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        #previewContent .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        #previewContent .text-right {
            text-align: right;
        }
        #previewContent .text-center {
            text-align: center;
        }
        #previewContent .footer {
            text-align: center;
            font-size: 12px;
            margin-top: 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="d-flex">
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
                    <a class="nav-link text-white active" href="../financial_rep.php"><span class="material-icons me-2">assessment</span>Financial Report</a>
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
                    VISITOR'S FINANCIAL REPORT (<?php echo date('F Y'); ?>)
                </h5>
                <div class="d-flex justify-content-between mb-3 mt-4 gap-3 flex-wrap">
                    <div class="input-group" style="width: 100px; font-weight: bold; font-size: 25px;">
                        <h5>Filter By:</h5>
                    </div>
                    <div class="input-group w-25">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" id="filterName" class="form-control" placeholder="Search by Visitor Name">
                    </div>
                    <div class="input-group w-25">
                        <span class="input-group-text"><i class="fas fa-door-open"></i></span>
                        <input type="text" id="filterRoom" class="form-control" placeholder="Search by Room">
                    </div>
                    <div class="input-group w-25">
                        <span class="input-group-text"><i class="fas fa-calendar-day"></i></span>
                        <input type="date" id="filterDate" class="form-control">
                    </div>
                </div>

                <div class="mb-4">                    <div class="table-responsive mt-3">
                        <table id="visitorTable" class="table table-bordered table-striped text-center">
                            <thead id="visitorTableHead">
                                <tr>
                                    <th id="thPaymentDate">Payment Date</th>
                                    <th id="thVisitorID">Visitor ID</th>
                                    <th id="thVisitorName">Visitor Name</th>
                                    <th id="thBoarderID">Boarder ID</th>
                                    <th id="thBoarderName">Boarder Name</th>
                                    <th id="thRoom">Room</th>
                                    <th id="thTotalDuration">Duration of Stay</th>
                                    <th id="thPaidAmount">Paid Amount</th>
                                </tr>
                            </thead>
                            <tbody id="visitorTableBody">
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><?php echo $payment['payment_date']; ?></td>
                                        <td><?php echo htmlspecialchars($payment['visitor_id']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['visitor_name']); ?></td>
                                        <td><?php echo $payment['boarder_id']; ?></td>
                                        <td><?php echo htmlspecialchars($payment['boarder_name']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['room'] ?? 'N/A'); ?></td>
                                        <td><?php echo $payment['days']; ?> days</td>
                                        <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr id="totalPaidRow">
                                    <td colspan="7" class="text-end fw-bold">Total:</td>
                                    <td id="paidAmountSum" class="fw-bold text-primary">₱0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end align-items-center mb-3 mt-4">
                        <button id="goBackBtn" class="btn btn-outline-secondary me-3 d-flex align-items-center">
                            <i class="material-icons me-1">arrow_back</i>Go Back
                        </button>
                        <button id="exportBtn" class="btn btn-outline-primary me-3">
                            <i class="material-icons me-1">picture_as_pdf</i>Generate PDF
                        </button>
                    </div>

                    <div class="print-area" id="printArea"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Print Content -->
    <div id="printContent" style="display: none;">
        <div class="text-center mb-4">
            <img src="../../images/receipt_logo.jpg" width="100px" alt="Logo" class="boarding-house-logo">
            <h2 class="boarding-house-name mb-2" style="color: #360938;"><?php echo htmlspecialchars($user['boardinghousename'] ?? 'Boarding House'); ?></h2>
            <h4 class="mb-3">Visitor's Financial Report</h4>
            <p class="text-muted"><?php echo date('F Y'); ?></p>
        </div>
        <div class="table-container">
            <!-- Table will be dynamically inserted here -->
        </div>
        <div class="text-center mt-4">
            <p class="small text-muted">Generated on <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const filterName = document.getElementById('filterName');
            const filterRoom = document.getElementById('filterRoom');
            const filterDate = document.getElementById('filterDate');
            const table = document.getElementById('visitorTable');

            function filterTable() {
                const nameValue = filterName.value.toLowerCase();
                const roomValue = filterRoom.value.toLowerCase();
                const dateValue = filterDate.value;

                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const nameCell = row.cells[2].textContent.toLowerCase();
                    const roomCell = row.cells[5].textContent.toLowerCase();
                    const dateCell = row.cells[0].textContent;

                    const nameMatch = nameCell.includes(nameValue);
                    const roomMatch = roomCell.includes(roomValue);
                    const dateMatch = !dateValue || dateCell.includes(dateValue);

                    row.style.display = (nameMatch && roomMatch && dateMatch) ? '' : 'none';
                });

                calculateTotals();
            }

            filterName.addEventListener('input', filterTable);
            filterRoom.addEventListener('input', filterTable);
            filterDate.addEventListener('input', filterTable);

            function calculateTotals() {
                const rows = table.querySelectorAll('tbody tr');
                let totalPaid = 0;

                rows.forEach(row => {
                    if (row.style.display !== 'none') {
                        const paid = parseFloat(row.cells[7].textContent.replace(/[₱,]/g, '')) || 0;
                        totalPaid += paid;
                    }
                });

                document.getElementById('totalPaidRow').innerHTML = `
                    <td colspan="7" class="text-end fw-bold">Total:</td>
                    <td id="paidAmountSum" class="fw-bold text-primary">₱${totalPaid.toFixed(2)}</td>
                `;
            }

            calculateTotals();

            document.getElementById('exportBtn').onclick = function() {
                // Create a new div for printing
                const printDiv = document.createElement('div');
                printDiv.className = 'print-content';
                
                // Clone the current table data
                const tableContent = document.getElementById('visitorTable').cloneNode(true);
                
                // Only keep visible rows
                const rows = tableContent.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    if (row.style.display === 'none') {
                        row.remove();
                    }
                });

                // Create the content for printing
                const currentDate = new Date();
                const monthYear = currentDate.toLocaleString('default', { month: 'long', year: 'numeric' });
                
                printDiv.innerHTML = `
                    <div class="text-center mb-4">
                        <img src="../../images/receipt_logo.jpg" width="100px" alt="Logo" class="boarding-house-logo">
                        <h2 class="boarding-house-name mb-2" style="color: #360938;">${document.getElementById('boardingHouseName').textContent} Boarding House</h2>
                        <h4 class="mb-3">Visitor's Financial Report</h4>
                        <p class="text-muted">${monthYear}</p>
                    </div>
                    <div class="table-container">
                        ${tableContent.outerHTML}
                    </div>
                    <div class="text-center mt-4">
                        <p class="small text-muted">Generated on ${currentDate.toLocaleString()}</p>
                    </div>
                `;

                // Add print-specific styles
                const style = document.createElement('style');
                style.textContent = `
                    @media print {
                        body * { visibility: hidden; }
                        .print-content, .print-content * { visibility: visible; }
                        .print-content { position: absolute; left: 0; top: 0; width: 100%; }
                        @page { size: portrait; margin: 2cm; }
                        table { width: 100%; border-collapse: collapse; }
                        th { background-color: #360938 !important; color: white !important; -webkit-print-color-adjust: exact; }
                        td, th { border: 1px solid #ddd; padding: 8px; }
                        img { max-width: 100px; }
                    }
                `;
                
                // Append the print content and styles to the body
                document.body.appendChild(style);
                document.body.appendChild(printDiv);
                
                // Open browser print dialog
                window.print();
                
                // Remove the print elements after dialog closes
                setTimeout(() => {
                    document.body.removeChild(style);
                    document.body.removeChild(printDiv);
                }, 1000);
            };

            document.getElementById('goBackBtn').onclick = function() {
                window.history.back();
            };
        });
    </script>
</body>
</html>