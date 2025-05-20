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

// Fetch user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT firstname, lastname, email, contact, government_id, tin_num, address, boardinghousename FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
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
$daily_rate = 100.00; // Fixed daily rate for visitors
$stmt->close();

// Fetch boarder payments
$boarder_payments = [];
$stmt = $conn->prepare("
    SELECT p.id AS invoice_number, b.id AS boarder_id, CONCAT(b.firstname, ' ', b.lastname) AS name, 
           b.room, p.amount AS paid, p.payment_date, p.appliances,
           CASE 
               WHEN p.amount >= (? + (p.appliances * ?)) THEN 'Paid'
               WHEN p.amount > 0 THEN 'Partial'
               WHEN CURRENT_DATE > ? THEN 'Overdue'
               ELSE 'Pending'
           END as status
    FROM payments p
    JOIN boarders b ON p.boarder_id = b.id
    WHERE p.user_id = ? AND p.payment_type = 'rent'
");
$stmt->bind_param("ddsi", $monthly_rate, $appliances_rate, $due_date, $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $row['rent'] = $monthly_rate + ($row['appliances'] * $appliances_rate);
    $row['charge'] = $row['rent'] - $row['paid'];
    $row['balance'] = $row['rent'] + $row['charge'] - $row['paid'];
    $boarder_payments[] = $row;
}
$stmt->close();

// Fetch visitor payments
$visitor_payments = [];
$stmt = $conn->prepare("
    SELECT p.id AS invoice_number, v.id AS visitor_id, v.name AS visitor_name, 
           b.room AS visitor_room, p.payment_date, p.days, 
           b.id AS boarder_id, CONCAT(b.firstname, ' ', b.lastname) AS boarder_name, 
           b.room AS boarder_room, p.amount AS paid, p.status
    FROM payments p
    JOIN boarders b ON p.boarder_id = b.id
    LEFT JOIN visitors v ON p.boarder_id = v.boarder_id AND p.user_id = v.user_id
    WHERE p.user_id = ? AND p.payment_type = 'visitor'
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $row['stay_fee'] = $row['days'] * $daily_rate;
    $row['balance'] = $row['stay_fee'] - $row['paid'];
    $visitor_payments[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt/Invoice - Boarding House</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../sidebar/style.css">
    <style>
        .bg-purple { background-color: #360938; }
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
        .table-container .bg-purple { color: white; }
        .print-area { display: none; }
        @media print {
            body * { visibility: hidden; }
            .print-area, .print-area * { visibility: visible; }
            .print-area { position: absolute; top: 0; left: 0; width: 100%; }
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
                    <a class="nav-link text-white active" href="../sidebar/receipt.php"><span class="material-icons me-2">receipt</span>Receipt/Invoice</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white" href="../sidebar/financial_rep.php"><span class="material-icons me-2">assessment</span>Financial Report</a>
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
                        RECEIPT/INVOICE
                    </h5>
                </div>

                <div class="row mb-3 mt-3">
                    <div class="col-md-4">
                        <input type="text" id="searchName" class="form-control" placeholder="Search by Name">
                    </div>
                    <div class="col-md-4">
                        <input type="text" id="searchRoom" class="form-control" placeholder="Search by Room Number">
                    </div>
                    <div class="col-md-4">
                        <select id="searchStatus" class="form-select">
                            <option value="">All Status</option>
                            <option value="Pending">Pending</option>
                            <option value="Overdue">Overdue</option>
                            <option value="Paid">Paid</option>
                            <option value="Partial">Partial</option>
                        </select>
                    </div>
                </div>

                <!-- Boarders Collected Table -->
                <div class="table-container mt-2">
                    <div class="d-flex align-items-center mb-0 py-2 px-3 bg-purple text-white rounded shadow-sm">
                        <h3 class="mb-0 fw-bold text-uppercase" style="font-size: 1.5rem;">Boarders Collected</h3>
                    </div>
                    <div class="table-responsive">
                        <table id="boarderTablePayment" class="table table-bordered table-hover align-middle text-center ms-0">
                            <thead>
                                <tr>
                                    <th>Invoice Number</th>
                                    <th>Boarder ID</th>
                                    <th>Name</th>
                                    <th>Room</th>
                                    <th>Rent</th>
                                    <th>Status</th>
                                    <th>Charge</th>
                                    <th>Paid</th>
                                    <th>Balance</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($boarder_payments as $payment): ?>
                                    <tr>
                                        <td><?php echo $payment['invoice_number']; ?></td>
                                        <td><?php echo $payment['boarder_id']; ?></td>
                                        <td><?php echo htmlspecialchars($payment['name']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['room'] ?? 'N/A'); ?></td>
                                        <td>₱<?php echo number_format($payment['rent'], 2); ?></td>
                                        <td><span class="badge bg-<?php echo getStatusColor($payment['status']); ?>"><?php echo $payment['status']; ?></span></td>
                                        <td>₱<?php echo number_format($payment['charge'], 2); ?></td>
                                        <td>₱<?php echo number_format($payment['paid'], 2); ?></td>
                                        <td>₱<?php echo number_format($payment['balance'], 2); ?></td>
                                        <td>
                                            <button class="btn btn-outline-success btn-sm print-receipt" 
                                                    data-type="boarder" 
                                                    data-invoice="<?php echo $payment['invoice_number']; ?>"
                                                    data-boarder-id="<?php echo $payment['boarder_id']; ?>"
                                                    data-name="<?php echo htmlspecialchars($payment['name']); ?>"
                                                    data-room="<?php echo htmlspecialchars($payment['room'] ?? 'N/A'); ?>"
                                                    data-rent="<?php echo $payment['rent']; ?>"
                                                    data-status="<?php echo $payment['status']; ?>"
                                                    data-charge="<?php echo $payment['charge']; ?>"
                                                    data-paid="<?php echo $payment['paid']; ?>"
                                                    data-balance="<?php echo $payment['balance']; ?>"
                                                    data-payment-date="<?php echo $payment['payment_date']; ?>"
                                                    data-appliances="<?php echo $payment['appliances']; ?>">
                                                <i class="fas fa-print"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Visitors Collected Table -->
                <div class="table-container mt-5">
                    <div class="d-flex align-items-center mb-0 py-2 px-3 bg-purple text-white rounded shadow-sm">
                        <h3 class="mb-0 fw-bold text-uppercase" style="font-size: 1.5rem;">Visitors Collected</h3>
                    </div>
                    <div class="table-responsive">
                        <table id="VisitorsTablePayment" class="table table-bordered table-hover align-middle text-center ms-0">
                            <thead>
                                <tr>
                                    <th>Invoice Number</th>
                                    <th>Visitor ID</th>
                                    <th>Visitor Name</th>
                                    <th>Room</th>
                                    <th>Date of Stay</th>
                                    <th>Duration of Days</th>
                                    <th>Boarder ID</th>
                                    <th>Boarder Name</th>
                                    <th>Boarder Room</th>
                                    <th>Paid</th>
                                    <th>Balance</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($visitor_payments as $payment): ?>
                                    <tr>
                                        <td><?php echo $payment['invoice_number']; ?></td>
                                        <td><?php echo htmlspecialchars($payment['visitor_id'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($payment['visitor_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($payment['visitor_room'] ?? 'N/A'); ?></td>
                                        <td><?php echo $payment['payment_date']; ?></td>
                                        <td><?php echo $payment['days']; ?></td>
                                        <td><?php echo $payment['boarder_id']; ?></td>
                                        <td><?php echo htmlspecialchars($payment['boarder_name']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['boarder_room'] ?? 'N/A'); ?></td>
                                        <td>₱<?php echo number_format($payment['paid'], 2); ?></td>
                                        <td>₱<?php echo number_format($payment['balance'], 2); ?></td>
                                        <td>
                                            <button class="btn btn-outline-success btn-sm print-receipt" 
                                                    data-type="visitor"
                                                    data-invoice="<?php echo $payment['invoice_number']; ?>"
                                                    data-visitor-id="<?php echo htmlspecialchars($payment['visitor_id'] ?? 'N/A'); ?>"
                                                    data-visitor-name="<?php echo htmlspecialchars($payment['visitor_name'] ?? 'N/A'); ?>"
                                                    data-room="<?php echo htmlspecialchars($payment['visitor_room'] ?? 'N/A'); ?>"
                                                    data-date-stay="<?php echo $payment['payment_date']; ?>"
                                                    data-days="<?php echo $payment['days']; ?>"
                                                    data-boarder-id="<?php echo $payment['boarder_id']; ?>"
                                                    data-boarder-name="<?php echo htmlspecialchars($payment['boarder_name']); ?>"
                                                    data-boarder-room="<?php echo htmlspecialchars($payment['boarder_room'] ?? 'N/A'); ?>"
                                                    data-paid="<?php echo $payment['paid']; ?>"
                                                    data-balance="<?php echo $payment['balance']; ?>"
                                                    data-stay-fee="<?php echo $payment['stay_fee']; ?>">
                                                <i class="fas fa-print"></i>
                                            </button>
                                            
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="d-flex justify-content-end align-items-center mb-3 mt-4">
                    <button id="exportBtn" class="btn btn-outline-primary me-3">Print All</button>
                </div>

                <!-- Print Area -->
                <div class="print-area" id="printArea"></div>

                <!-- Receipt Preview Modal -->
                <div class="modal fade" id="receiptPreviewModal" tabindex="-1" aria-labelledby="receiptPreviewModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="receiptPreviewModalLabel">Receipt Preview</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" id="receiptPreviewContent">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="downloadPdfBtn">Download PDF</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- All Receipts Preview Modal -->
                <div class="modal fade" id="allReceiptsPreviewModal" tabindex="-1" aria-labelledby="allReceiptsPreviewModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="allReceiptsPreviewModalLabel">All Receipts Preview</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" id="allReceiptsPreviewContent" style="max-height: 70vh; overflow-y: auto;">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="downloadAllPdfBtn">Download All PDF</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function getStatusColor(status) {
            switch (status.toLowerCase()) {
                case 'pending': return 'warning';
                case 'overdue': return 'danger';
                case 'paid': return 'success';
                case 'partial': return 'primary';
                default: return 'secondary';
            }
        }

        // Table Filtering
        const searchName = document.getElementById('searchName');
        const searchRoom = document.getElementById('searchRoom');
        const searchStatus = document.getElementById('searchStatus');

        function filterTables() {
            const nameValue = searchName.value.toLowerCase().trim();
            const roomValue = searchRoom.value.toLowerCase().trim();
            const statusValue = searchStatus.value.toLowerCase();

            // Filter Boarder Table
            filterTableRows('boarderTablePayment', {
                nameCol: 2,
                roomCol: 3,
                statusCol: 5,
                nameValue,
                roomValue,
                statusValue
            });

            // Filter Visitor Table
            filterTableRows('VisitorsTablePayment', {
                nameCol: 7, // Boarder Name
                roomCol: 8, // Boarder Room
                visitorNameCol: 2, // Visitor Name
                visitorRoomCol: 3, // Visitor Room
                nameValue,
                roomValue,
                statusValue
            });

            // Update visible count
            updateVisibleCount();
        }

        function filterTableRows(tableId, filters) {
            const table = document.getElementById(tableId);
            if (!table) return;

            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            const isBoarderTable = tableId === 'boarderTablePayment';

            for (let row of rows) {
                let showRow = true;

                // Name filtering
                if (filters.nameValue) {
                    const nameMatch = isBoarderTable
                        ? row.cells[filters.nameCol].textContent.toLowerCase().includes(filters.nameValue)
                        : (row.cells[filters.visitorNameCol].textContent.toLowerCase().includes(filters.nameValue) ||
                           row.cells[filters.nameCol].textContent.toLowerCase().includes(filters.nameValue));
                    
                    if (!nameMatch) showRow = false;
                }

                // Room filtering
                if (showRow && filters.roomValue) {
                    const roomMatch = isBoarderTable
                        ? row.cells[filters.roomCol].textContent.toLowerCase().includes(filters.roomValue)
                        : (row.cells[filters.visitorRoomCol].textContent.toLowerCase().includes(filters.roomValue) ||
                           row.cells[filters.roomCol].textContent.toLowerCase().includes(filters.roomValue));
                    
                    if (!roomMatch) showRow = false;
                }

                // Status filtering (only for boarder table)
                if (showRow && filters.statusValue && isBoarderTable) {
                    const statusText = row.cells[filters.statusCol].textContent.toLowerCase();
                    if (filters.statusValue !== '' && statusText !== filters.statusValue) {
                        showRow = false;
                    }
                }

                // Apply visibility
                row.style.display = showRow ? '' : 'none';
            }
        }

        function updateVisibleCount() {
            const boarderCount = countVisibleRows('boarderTablePayment');
            const visitorCount = countVisibleRows('VisitorsTablePayment');
            
            // Update counts in the UI (you can add elements to show these)
            const countDisplay = document.getElementById('visibleCountDisplay') || createCountDisplay();
            countDisplay.textContent = `Showing: ${boarderCount} Boarders, ${visitorCount} Visitors`;
        }

        function countVisibleRows(tableId) {
            const table = document.getElementById(tableId);
            if (!table) return 0;
            
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            return Array.from(rows).filter(row => row.style.display !== 'none').length;
        }

        function createCountDisplay() {
            const container = document.createElement('div');
            container.id = 'visibleCountDisplay';
            container.style.cssText = 'margin: 10px 0; font-size: 14px; color: #666;';
            
            // Insert before the first table
            const firstTable = document.querySelector('.table-container');
            firstTable.parentNode.insertBefore(container, firstTable);
            
            return container;
        }

        // Add event listeners for real-time filtering
        searchName.addEventListener('input', debounce(filterTables, 300));
        searchRoom.addEventListener('input', debounce(filterTables, 300));
        searchStatus.addEventListener('change', filterTables);

        // Debounce function to prevent too many rapid updates
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        // Initialize filtering
        filterTables();

        // Add clear button functionality
        function addClearButton(input) {
            const clearButton = document.createElement('button');
            clearButton.innerHTML = '&times;';
            clearButton.className = 'clear-button';
            clearButton.style.cssText = `
                position: absolute;
                right: 8px;
                top: 50%;
                transform: translateY(-50%);
                background: none;
                border: none;
                font-size: 18px;
                cursor: pointer;
                color: #999;
                display: none;
            `;
            
            const wrapper = document.createElement('div');
            wrapper.style.position = 'relative';
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);
            wrapper.appendChild(clearButton);
            
            clearButton.addEventListener('click', () => {
                input.value = '';
                clearButton.style.display = 'none';
                filterTables();
                input.focus();
            });
            
            input.addEventListener('input', () => {
                clearButton.style.display = input.value ? 'block' : 'none';
            });
        }

        // Add clear buttons to search inputs
        addClearButton(searchName);
        addClearButton(searchRoom);

        // Add placeholder text
        searchName.placeholder = 'Search by name...';
        searchRoom.placeholder = 'Search by room number...';
        
        // Style the search inputs
        const searchInputs = [searchName, searchRoom];
        searchInputs.forEach(input => {
            input.style.cssText += `
                padding-right: 30px;
                transition: border-color 0.3s ease;
            `;
        });

        // Style the status dropdown
        searchStatus.style.cssText += `
            cursor: pointer;
            transition: border-color 0.3s ease;
        `;

        // Print Individual Receipt
        let currentReceiptData = null;
        const previewModal = new bootstrap.Modal(document.getElementById('receiptPreviewModal'));

        document.addEventListener('click', function(e) {
            if (e.target.closest('.print-receipt')) {
                const button = e.target.closest('.print-receipt');
                const data = button.dataset;
                
                currentReceiptData = {
                    type: data.type
                };

                if (data.type === 'boarder') {
                    currentReceiptData = {
                        ...currentReceiptData,
                        invoice: data.invoice,
                        boarderId: data.boarderId,
                        name: data.name,
                        room: data.room,
                        rent: parseFloat(data.rent),
                        charge: parseFloat(data.charge),
                        paid: parseFloat(data.paid),
                        balance: parseFloat(data.balance),
                        status: data.status,
                        paymentDate: data.paymentDate,
                        appliances: data.appliances
                    };
                } else if (data.type === 'visitor') {
                    currentReceiptData = {
                        ...currentReceiptData,
                        invoice: data.invoice,
                        visitorId: data.visitorId,
                        visitorName: data.visitorName,
                        room: data.room,
                        dateStay: data.dateStay,
                        days: data.days,
                        boarderId: data.boarderId,
                        boarderName: data.boarderName,
                        boarderRoom: data.boarderRoom,
                        paid: parseFloat(data.paid),
                        balance: parseFloat(data.balance),
                        stayFee: parseFloat(data.stayFee)
                    };
                }

                // Show preview
                fetch('generate_receipt.php?preview=true', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ data: currentReceiptData })
                })
                .then(response => response.text())
                .then(html => {
                    document.getElementById('receiptPreviewContent').innerHTML = html;
                    previewModal.show();
                })
                .catch(error => {
                    console.error('Error generating preview:', error);
                    alert('Error generating receipt preview. Please try again.');
                });
            }
        });

        // Handle PDF download from preview
        document.getElementById('downloadPdfBtn').addEventListener('click', function() {
            if (!currentReceiptData) return;

            fetch('generate_receipt.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ data: currentReceiptData })
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => Promise.reject(err));
                }
                return response.blob();
            })
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `Receipt-${currentReceiptData.invoice}.pdf`;
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);
                previewModal.hide();
            })
            .catch(error => {
                console.error('Error generating receipt:', error);
                alert('Error generating receipt. Please try again.');
            });
        });

        // Print All Receipts with Preview
        const allReceiptsPreviewModal = new bootstrap.Modal(document.getElementById('allReceiptsPreviewModal'));
        let allReceiptsData = null;

        document.getElementById('exportBtn').addEventListener('click', function () {
            const boarderRows = document.querySelectorAll('#boarderTablePayment tbody tr');
            const visitorRows = document.querySelectorAll('#VisitorsTablePayment tbody tr');

            const boarders = Array.from(boarderRows)
                .filter(row => row.style.display !== 'none')
                .map(row => ({
                    invoice_number: row.cells[0].textContent.trim(),
                    boarder_id: row.cells[1].textContent.trim(),
                    name: row.cells[2].textContent.trim(),
                    room: row.cells[3].textContent.trim(),
                    rent: parseFloat(row.cells[4].textContent.replace('₱', '').replace(',', '')),
                    status: row.cells[5].textContent.trim(),
                    charge: parseFloat(row.cells[6].textContent.replace('₱', '').replace(',', '')),
                    paid: parseFloat(row.cells[7].textContent.replace('₱', '').replace(',', '')),
                    balance: parseFloat(row.cells[8].textContent.replace('₱', '').replace(',', ''))
                }));

            const visitors = Array.from(visitorRows)
                .filter(row => row.style.display !== 'none')
                .map(row => ({
                    invoice_number: row.cells[0].textContent.trim(),
                    visitor_id: row.cells[1].textContent.trim(),
                    visitor_name: row.cells[2].textContent.trim(),
                    room: row.cells[3].textContent.trim(),
                    date_stay: row.cells[4].textContent.trim(),
                    days: row.cells[5].textContent.trim(),
                    boarder_id: row.cells[6].textContent.trim(),
                    boarder_name: row.cells[7].textContent.trim(),
                    boarder_room: row.cells[8].textContent.trim(),
                    paid: parseFloat(row.cells[9].textContent.replace('₱', '').replace(',', '')),
                    balance: parseFloat(row.cells[10].textContent.replace('₱', '').replace(',', ''))
                }));

            if (boarders.length === 0 && visitors.length === 0) {
                alert('No receipts to print. Please make sure there are visible entries in the table.');
                return;
            }

            allReceiptsData = { boarders, visitors };

            // Show preview
            fetch('generate_all_receipts.php?preview=true', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(allReceiptsData)
            })
            .then(response => response.text())
            .then(html => {
                document.getElementById('allReceiptsPreviewContent').innerHTML = html;
                allReceiptsPreviewModal.show();
            })
            .catch(error => {
                console.error('Error generating preview:', error);
                alert('Error generating receipts preview. Please try again.');
            });
        });

        // Handle PDF download for all receipts
        document.getElementById('downloadAllPdfBtn').addEventListener('click', function() {
            if (!allReceiptsData) return;

            fetch('generate_all_receipts.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(allReceiptsData)
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => Promise.reject(err));
                }
                return response.blob();
            })
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'All_Receipts.pdf';
                document.body.appendChild(a);
                a.click();
                a.remove();
                window.URL.revokeObjectURL(url);
                allReceiptsPreviewModal.hide();
            })
            .catch(error => {
                console.error('Error generating receipts:', error);
                alert('Error generating receipts. Please try again.');
            });
        });
    </script>
</body>
</html>

<?php
function getStatusColor($status) {
    switch (strtolower($status)) {
        case 'pending': return 'warning';
        case 'overdue': return 'danger';
        case 'paid': return 'success';
        case 'partial': return 'primary';
        default: return 'secondary';
    }
}
?>