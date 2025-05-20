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

// Initialize filter variables
$yearFilter = isset($_GET['year']) ? $_GET['year'] : '';
$monthFilter = isset($_GET['month']) ? $_GET['month'] : '';

// Fetch rates
$stmt = $conn->prepare("SELECT monthly_rate, appliances_rate, late_fee, due_date FROM rates WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$rate = $result->fetch_assoc();
$monthly_rate = $rate['monthly_rate'] ?? 1000.00;
$appliances_rate = $rate['appliances_rate'] ?? 100.00;
$stmt->close();

// Fetch rent payments
$payments = [];
$stmt = $conn->prepare("
    SELECT p.payment_date, b.id AS boarder_id, CONCAT(b.firstname, ' ', b.lastname) AS boarder_name,
           b.room, p.amount, p.appliances
    FROM payments p
    JOIN boarders b ON p.boarder_id = b.id
    WHERE p.user_id = ? AND p.payment_type = 'rent'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $row['total_rent'] = $monthly_rate + ($row['appliances'] * $appliances_rate);
    $row['year'] = date('Y', strtotime($row['payment_date']));
    $row['month'] = date('F', strtotime($row['payment_date']));
    $row['day'] = date('d', strtotime($row['payment_date']));
    $payments[] = $row;
}
$stmt->close();

// Generate year options dynamically
$years = array_unique(array_column($payments, 'year'));
sort($years);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Yearly Rent Collection</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
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
            body * {
                visibility: hidden;
            }
            #printContent, #printContent * {
                visibility: visible;
            }
            #printContent {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print {
                display: none !important;
            }
        }

        .print-preview-content {
            background: white;
            padding: 20px;
            margin: 0 auto;
            max-width: 1000px;
        }

        .print-preview-content .boarding-house-logo {
            max-width: 150px;
            margin-bottom: 15px;
        }

        .print-preview-content table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .print-preview-content th {
            background-color: #360938;
            color: white;
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
        }

        .print-preview-content td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: center;
        }

        .print-preview-content .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }

        .print-preview-content .text-right {
            text-align: right;
        }

        /* Add print preview modal styles */
        .print-preview-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .print-preview-content {
            position: relative;
            width: 90%;
            max-width: 1000px;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .print-preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }

        .print-preview-options {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }

        .print-preview-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
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
                <div class="mb-4">
                    <h5 style="font-weight: bold; color: #360938; border-bottom: 2px solid #360938; padding-bottom: 10px;">
                        YEARLY RENT COLLECTION
                    </h5>
                    <div class="d-flex justify-content-between align-items-center mb-3 mt-4 gap-3 flex-wrap">
                        <div class="input-group" style="width: 100px; font-weight: bold; font-size: 25px;">
                            <h5>Filter By:</h5>
                        </div>
                        <div class="input-group" style="width: 350px;">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" id="filterName" class="form-control" placeholder="Search by Name">
                        </div>
                        <div class="input-group" style="width: 350px;">
                            <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                            <select id="filterYear" class="form-select">
                                <option value="">Year</option>
                                <?php foreach ($years as $year): ?>
                                    <option value="<?php echo $year; ?>"><?php echo $year; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="input-group" style="width: 350px;">
                            <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                            <select id="filterMonth" class="form-select">
                                <option value="">Month</option>
                                <option value="January">January</option>
                                <option value="February">February</option>
                                <option value="March">March</option>
                                <option value="April">April</option>
                                <option value="May">May</option>
                                <option value="June">June</option>
                                <option value="July">July</option>
                                <option value="August">August</option>
                                <option value="September">September</option>
                                <option value="October">October</option>
                                <option value="November">November</option>
                                <option value="December">December</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="table-responsive mt-3">
                    <table id="yearlyRentTable" class="table table-bordered table-striped text-center">
                        <thead id="yearlyRentHead">
                            <tr>
                                <th id="thPaymentYear">Year</th>
                                <th id="thPaymentMonth">Month</th>
                                <th id="thPaymentDay">Day</th>
                                <th id="thBoarderID">Boarder ID</th>
                                <th id="thBoarderName">Boarder Name</th>
                                <th id="thRoom">Room</th>
                                <th id="thTotalRent">Total Rent</th>
                                <th id="thPaidAmount">Paid Amount</th>
                            </tr>
                        </thead>
                        <tbody id="yearlyRentBody">
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?php echo $payment['year']; ?></td>
                                    <td><?php echo $payment['month']; ?></td>
                                    <td><?php echo $payment['day']; ?></td>
                                    <td><?php echo $payment['boarder_id']; ?></td>
                                    <td><?php echo htmlspecialchars($payment['boarder_name']); ?></td>
                                    <td><?php echo htmlspecialchars($payment['room'] ?? 'N/A'); ?></td>
                                    <td>₱<?php echo number_format($payment['total_rent'], 2); ?></td>
                                    <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6" class="text-end fw-bold">Total:</td>
                                <td id="totalRentSum" class="fw-bold text-success">₱0.00</td>
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

                <!-- Print Content -->
                <div id="printContent" style="display: none;">
                    <div class="text-center mb-4">
                        <img src="../../images/receipt_logo.jpg" width="100px" alt="Logo" class="boarding-house-logo">
                        <h2 class="boarding-house-name mb-2" style="color: #360938;"><?php echo htmlspecialchars($user['boardinghousename'] ?? 'Boarding House'); ?></h2>
                        <h4 class="mb-3">Yearly Rent Collection Report</h4>
                        <p class="text-muted"><?php echo $yearFilter ? "Year: $yearFilter" : date('Y'); ?><?php echo $monthFilter ? " - Month: $monthFilter" : ""; ?></p>
                    </div>
                    <div class="table-container">
                        <!-- Table will be dynamically inserted here -->
                    </div>
                    <div class="text-center mt-4">
                        <p class="small text-muted">Generated on <?php echo date('Y-m-d H:i:s'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const filterName = document.getElementById('filterName');
            const filterYear = document.getElementById('filterYear');
            const filterMonth = document.getElementById('filterMonth');
            const table = document.getElementById('yearlyRentTable');

            function filterTable() {
                const nameValue = filterName.value.toLowerCase();
                const yearValue = filterYear.value;
                const monthValue = filterMonth.value;

                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const year = row.cells[0].textContent;
                    const month = row.cells[1].textContent;
                    const name = row.cells[4].textContent.toLowerCase();

                    const matchYear = !yearValue || year === yearValue;
                    const matchMonth = !monthValue || month === monthValue;
                    const matchName = name.includes(nameValue);

                    row.style.display = (matchYear && matchMonth && matchName) ? '' : 'none';
                });

                calculateTotals();
            }

            filterName.addEventListener('input', filterTable);
            filterYear.addEventListener('change', filterTable);
            filterMonth.addEventListener('change', filterTable);

            function calculateTotals() {
                const rows = table.querySelectorAll('tbody tr');
                let totalRent = 0;
                let totalPaid = 0;

                rows.forEach(row => {
                    if (row.style.display !== 'none') {
                        const rent = parseFloat(row.cells[6].textContent.replace(/[₱,]/g, '')) || 0;
                        const paid = parseFloat(row.cells[7].textContent.replace(/[₱,]/g, '')) || 0;
                        totalRent += rent;
                        totalPaid += paid;
                    }
                });

                document.getElementById('totalRentSum').textContent = `₱${totalRent.toFixed(2)}`;
                document.getElementById('paidAmountSum').textContent = `₱${totalPaid.toFixed(2)}`;
            }

            calculateTotals();

            document.getElementById('exportBtn').onclick = function() {
                // Get the current table data
                const tableContent = document.getElementById('yearlyRentTable').cloneNode(true);
                
                // Only keep visible rows
                const rows = tableContent.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    if (row.style.display === 'none') {
                        row.remove();
                    }
                });

                // Update the print content
                const printContainer = document.querySelector('#printContent .table-container');
                printContainer.innerHTML = '';
                printContainer.appendChild(tableContent);

                // Show print preview
                const printContent = document.getElementById('printContent');
                printContent.style.display = 'block';

                // Print the document
                window.print();

                // Hide print content after printing
                printContent.style.display = 'none';
            };

            document.getElementById('goBackBtn').onclick = function () {
                window.history.back();
            };

            // Close modal when clicking outside
            window.onclick = function(event) {
                const modal = document.getElementById('previewModal');
                if (event.target === modal) {
                    const previewModal = bootstrap.Modal.getInstance(modal);
                    previewModal.hide();
                }
            };
        });
    </script>
</body>
</html>