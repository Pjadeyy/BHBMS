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

// Fetch all boarders for the logged-in user
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM boarders WHERE user_id = ? ORDER BY boarder_id");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$boarders = [];
while ($row = $result->fetch_assoc()) {
    $boarders[] = $row;
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Boarders Profile - Boarding House</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .table-container { overflow-x: auto; }
        .table th, .table td { vertical-align: middle; }
        .badge { font-size: 0.9em; }
        .input-group { max-width:  300px;}
        .form-control { max-width: 200px; }
        #searchBtn { transition: background-color 0.2s; }
        #searchBtn:hover { background-color: #e9ecef; }
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
                        BOARDER'S PROFILE LIST
                    </h5>
                </div>

                <div class="d-flex gap-3 mt-4">
                    <a href="add_boarder.php" class="btn btn-success d-flex align-items-center px-4 py-2 rounded-3 shadow">
                        <i class="fas fa-user-plus me-2"></i> RECORD NEW BOARDER
                    </a>
                </div>

                <div class="table-container mt-3">
                    <table class="table table-striped" id="boarderTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Room</th>
                                <th>Move</th>
                                <th>Monthly</th>
                                <th>App</th>
                                <th>Deposit</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($boarders) > 0): ?>
                                <?php foreach ($boarders as $boarder): ?>
                                    <tr>
                                <td><?php echo htmlspecialchars($boarder['boarder_id']); ?></td>
                                <td><?php echo htmlspecialchars($boarder['firstname'] . ' ' . $boarder['lastname']); ?></td>
                                <td><?php echo htmlspecialchars($boarder['email']); ?></td>
                                <td><?php echo htmlspecialchars($boarder['phone'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($boarder['address'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($boarder['room'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($boarder['move_in_date']); ?></td>
                                <td>₱<?php echo number_format($boarder['monthly_rate'], 2); ?></td>
                                <td><?php echo htmlspecialchars($boarder['appliances'] ?? 0); ?></td>
                                <td>₱<?php echo number_format($boarder['deposit_amount'], 2); ?></td>
                                <td><span class="badge bg-<?php echo $boarder['status'] == 'active' ? 'success' : 'danger'; ?>"><?php echo htmlspecialchars($boarder['status']); ?></span></td>
                                <td>
                                    <a href="edit_boarder.php?id=<?php echo $boarder['id']; ?>" class="btn btn-outline-primary btn-sm me-2" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete_boarder.php?id=<?php echo $boarder['id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to delete this boarder?')" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="12" class="text-center">No boarders found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Search functionality
        $(document).ready(function() {
            const $rows = $('#boarderTable tbody tr');
            const $searchInput = $('#search');
            const $searchBtn = $('#searchBtn');

            function filterBoarders() {
                const searchValue = $searchInput.val().trim().toLowerCase();
                $rows.each(function() {
                    const name = $(this).find('td').eq(1).text().toLowerCase();
                    $(this).toggle(searchValue === '' || name.includes(searchValue));
                });
            }

            // Trigger search on button click
            $searchBtn.click(filterBoarders);

            // Trigger search on Enter key
            $searchInput.on('keypress', function(e) {
                if (e.which === 13) {
                    filterBoarders();
                }
            });

            // Re-filter on input change (for clearing search)
            $searchInput.on('input', function() {
                if ($(this).val().trim() === '') {
                    $rows.show(); // Show all rows when search is cleared
                }
            });
        });
    </script>
</body>
</html>