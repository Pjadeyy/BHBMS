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

// Fetch rate data
$stmt = $conn->prepare("SELECT due_date, monthly_rate, appliances_rate, late_fee FROM rates WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$rate = $result->fetch_assoc();
$stmt->close();

// Set due date to 10th of current month if not set
$current_date = new DateTime();
$due_date = $current_date->format('Y-m-10');
$due_date_display = $rate['due_date'] ? date('F j, Y', strtotime($rate['due_date'])) : date('F j, Y', strtotime($due_date));

// Default rate values if none exist
$monthly_rate = $rate['monthly_rate'] ?? '0.00';
$appliances_rate = $rate['appliances_rate'] ?? '0.00';
$late_fee = $rate['late_fee'] ?? '0.00';

$conn->close();

// Prepare full name
$full_name = $user['firstname'] . ' ' . $user['lastname'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Hub - Boarding House</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .custom-edit-btn {
            border: 2px solid orange;
            color: black;
            background-color: transparent;
        }
        .custom-edit-btn:hover {
            background: orange;
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
            <a href="admin.php" class="d-flex align-items-center justify-content-between mb-4 px-2 nav-link active admin-border">
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
                        ADMIN'S HUB
                    </h5>
                </div>

                <div class="container mt-5" style="font-family: 'Segoe UI', sans-serif;">
                    <div class="row g-4">
                        <!-- Admin Profile Card -->
                        <div class="col-md-6 d-flex">
                            <div class="card shadow rounded-4 border-0 w-100">
                                <div class="card-body d-flex flex-column p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="bi bi-person-circle fs-3 text-primary me-3"></i>
                                        <h4 class="mb-0">Admin Profile</h4>
                                    </div>
                                    <hr>
                                    <div id="adminView">
                                        <p><strong>Full Name:</strong> <span id="fullName"><?php echo htmlspecialchars($full_name); ?></span></p>
                                        <p><strong>Email:</strong> <span id="email"><?php echo htmlspecialchars($user['email']); ?></span></p>
                                        <p><strong>Contact Number:</strong> <span id="contactNumber"><?php echo htmlspecialchars($user['contact'] ?? 'N/A'); ?></span></p>
                                        <p><strong>Government ID:</strong> <span id="govId"><?php echo htmlspecialchars($user['government_id'] ?? 'N/A'); ?></span></p>
                                        <p><strong>TIN:</strong> <span id="tinNumber"><?php echo htmlspecialchars($user['tin_num'] ?? 'N/A'); ?></span></p>
                                        <p><strong>Address:</strong> <span id="address"><?php echo htmlspecialchars($user['address'] ?? 'N/A'); ?></span></p>
                                        <p><strong>Boarding House:</strong> <span id="boardingHouseName"><?php echo htmlspecialchars($user['boardinghousename'] ?? 'N/A'); ?></span></p>
                                    </div>
                                    <div id="adminEdit" class="d-none">
                                        <form id="adminProfileForm">
                                            <div class="mb-2">
                                                <label class="form-label"><strong>First Name:</strong></label>
                                                <input type="text" name="firstname" class="form-control" value="<?php echo htmlspecialchars($user['firstname']); ?>" required>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label"><strong>Last Name:</strong></label>
                                                <input type="text" name="lastname" class="form-control" value="<?php echo htmlspecialchars($user['lastname']); ?>" required>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label"><strong>Email:</strong></label>
                                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label"><strong>Contact Number:</strong></label>
                                                <input type="text" name="contact" class="form-control" value="<?php echo htmlspecialchars($user['contact'] ?? ''); ?>">
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label"><strong>Government ID:</strong></label>
                                                <input type="text" name="government_id" class="form-control" value="<?php echo htmlspecialchars($user['government_id'] ?? ''); ?>">
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label"><strong>TIN:</strong></label>
                                                <input type="text" name="tin_num" class="form-control" value="<?php echo htmlspecialchars($user['tin_num'] ?? ''); ?>">
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label"><strong>Address:</strong></label>
                                                <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label"><strong>Boarding House:</strong></label>
                                                <input type="text" name="boardinghousename" class="form-control" value="<?php echo htmlspecialchars($user['boardinghousename'] ?? ''); ?>">
                                            </div>
                                        </form>
                                    </div>
                                    <div class="mt-auto text-end">
                                        <button class="btn btn-outline-warning rounded-pill px-4 custom-edit-btn" id="editAdminBtn" onclick="toggleEdit('admin')">
                                            <i class="bi bi-pencil me-2"></i>Edit Profile
                                        </button>
                                        <div id="adminEditControls" class="d-none">
                                            <button class="btn btn-success rounded-pill me-2 px-4" onclick="saveAdminProfile()">Save</button>
                                            <button class="btn btn-secondary rounded-pill px-4" onclick="toggleEdit('admin')">Cancel</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Rate Fee Card -->
                        <div class="col-md-6 d-flex">
                            <div class="card shadow rounded-4 border-0 w-100">
                                <div class="card-body d-flex flex-column p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <i class="bi bi-info-circle-fill fs-3 text-success me-3"></i>
                                        <h4 class="mb-0">Rate Fee</h4>
                                    </div>
                                    <hr>
                                    <div id="rateView">
                                        <p><i class="bi bi-star me-2 text-secondary"></i><strong>Due Date:</strong> <span id="dueDate"><?php echo htmlspecialchars($due_date_display); ?></span></p>
                                        <p><i class="bi bi-calendar-check me-2 text-secondary"></i><strong>Monthly Rate:</strong> <span id="monthlyRate">₱<?php echo number_format($monthly_rate, 2); ?></span></p>
                                        <p><i class="bi bi-gear me-2 text-secondary"></i><strong>Appliances Rate:</strong> <span id="applianceRate">₱<?php echo number_format($appliances_rate, 2); ?></span></p>
                                        <p><i class="bi bi-check-circle me-2 text-success"></i><strong>Late Fee:</strong> <span id="lateFee">₱<?php echo number_format($late_fee, 2); ?></span></p>
                                    </div>
                                    <div id="rateEdit" class="d-none">
                                        <form id="rateForm">
                                            <div class="mb-2">
                                                <label class="formchae form-label"><strong>Due Date:</strong></label>
                                                <input type="date" name="due_date" class="form-control" value="<?php echo htmlspecialchars($rate['due_date'] ?? $due_date); ?>" required>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label"><strong>Monthly Rate (₱):</strong></label>
                                                <input type="number" name="monthly_rate" class="form-control" value="<?php echo htmlspecialchars($monthly_rate); ?>" step="0.01" min="0" required>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label"><strong>Appliances Rate (₱):</strong></label>
                                                <input type="number" name="appliances_rate" class="form-control" value="<?php echo htmlspecialchars($appliances_rate); ?>" step="0.01" min="0" required>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label"><strong>Late Fee (₱):</strong></label>
                                                <input type="number" name="late_fee" class="form-control" value="<?php echo htmlspecialchars($late_fee); ?>" step="0.01" min="0" required>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="mt-auto text-end">
                                        <button class="btn btn-outline-warning rounded-pill px-4 custom-edit-btn" id="editRateBtn" onclick="toggleEdit('rate')">
                                            <i class="bi bi-pencil me-2"></i>Edit Rate
                                        </button>
                                        <div id="rateEditControls" class="d-none">
                                            <button class="btn btn-success rounded-pill me-2 px-4" onclick="saveRateInfo()">Save</button>
                                            <button class="btn btn-secondary rounded-pill px-4" onclick="toggleEdit('rate')">Cancel</button>
                                        </div>
                                    </div>
                                </div>
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
        // Toggle edit and view modes
        function toggleEdit(cardId) {
            const view = document.getElementById(`${cardId}View`);
            const edit = document.getElementById(`${cardId}Edit`);
            const editBtn = document.getElementById(`edit${cardId.charAt(0).toUpperCase() + cardId.slice(1)}Btn`);
            const editControls = document.getElementById(`${cardId}EditControls`);

            view.classList.toggle("d-none");
            edit.classList.toggle("d-none");
            editBtn.classList.toggle("d-none");
            editControls.classList.toggle("d-none");
        }

        // Save admin profile
        function saveAdminProfile() {
            const form = document.getElementById('adminProfileForm');
            const formData = new FormData(form);

            // Basic client-side validation
            if (!formData.get('firstname') || !formData.get('lastname') || !formData.get('email')) {
                alert('Please fill in all required fields.');
                return;
            }

            $.ajax({
                url: 'update_profile.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    try {
                        const result = JSON.parse(response);
                        if (result.success) {
                            // Update view mode
                            document.getElementById('fullName').textContent = formData.get('firstname') + ' ' + formData.get('lastname');
                            document.getElementById('email').textContent = formData.get('email');
                            document.getElementById('contactNumber').textContent = formData.get('contact') || 'N/A';
                            document.getElementById('govId').textContent = formData.get('government_id') || 'N/A';
                            document.getElementById('tinNumber').textContent = formData.get('tin_num') || 'N/A';
                            document.getElementById('address').textContent = formData.get('address') || 'N/A';
                            document.getElementById('boardingHouseName').textContent = formData.get('boardinghousename') || 'N/A';
                            toggleEdit('admin');
                            alert('Profile updated successfully!');
                        } else {
                            alert('Error: ' + result.error);
                        }
                    } catch (e) {
                        alert('Invalid response from server. Please try again.');
                    }
                },
                error: function(xhr, status, error) {
                    alert('Failed to update profile: ' + error);
                }
            });
        }

        // Save rate info
        function saveRateInfo() {
            const form = document.getElementById('rateForm');
            const formData = new FormData(form);

            // Basic client-side validation
            if (!formData.get('due_date') || !formData.get('monthly_rate') || !formData.get('appliances_rate') || !formData.get('late_fee')) {
                alert('Please fill in all required fields.');
                return;
            }

            $.ajax({
                url: 'update_rates.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    try {
                        const result = JSON.parse(response);
                        if (result.success) {
                            // Update view mode
                            const dueDate = new Date(formData.get('due_date')).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
                            document.getElementById('dueDate').textContent = dueDate;
                            document.getElementById('monthlyRate').textContent = '₱' + parseFloat(formData.get('monthly_rate')).toFixed(2);
                            document.getElementById('applianceRate').textContent = '₱' + parseFloat(formData.get('appliances_rate')).toFixed(2);
                            document.getElementById('lateFee').textContent = '₱' + parseFloat(formData.get('late_fee')).toFixed(2);
                            toggleEdit('rate');
                            alert('Rate updated successfully!');
                        } else {
                            alert('Error: ' + result.error);
                        }
                    } catch (e) {
                        alert('Invalid response from server. Please try again.');
                    }
                },
                error: function(xhr, status, error) {
                    alert('Failed to update rate: ' + error);
                }
            });
        }
    </script>
</body>
</html>