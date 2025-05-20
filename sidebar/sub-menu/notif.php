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

// Create notifications table if it doesn't exist
$conn->query("
    CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        message TEXT,
        status ENUM('New', 'Read') DEFAULT 'New',
        created_at DATE,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )
");

// Check if today is the 4th of the month
$today = date('d');
$current_month = date('Y-m');
$notification_message = "Rent is due on the 10th. Prepare to engage boarders with payment reminders.";
$email_template = "
Dear [Boarder Name],

This is a friendly reminder that your rent payment of ₱[Rent Amount] for [Room Number] is due on the 10th of [Month]. Please ensure timely payment to avoid a late fee of ₱[Late Fee].

Payment can be made via [Payment Method, e.g., bank transfer to Account XYZ or cash]. Contact us at [Your Contact Info] for any questions.

Thank you,
Kring-Kring Ladies Boarding House
";

if ($today == '04') {
    // Check if a notification for this month already exists
    $stmt = $conn->prepare("SELECT id FROM notifications WHERE user_id = ? AND created_at LIKE ? AND message = ?");
    $month_wildcard = "$current_month%";
    $stmt->bind_param("iss", $user_id, $month_wildcard, $notification_message);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 0) {
        // Insert new notification
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, status, created_at) VALUES (?, ?, 'New', CURDATE())");
        $stmt->bind_param("is", $user_id, $notification_message);
        $stmt->execute();
    }
    $stmt->close();
}

// Fetch all notifications for the user
$notifications = [];
$stmt = $conn->prepare("SELECT id, message, status, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Notifications - Boarding House</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        .notification-preview:hover {
            background-color: #f8f9fa;
        }
        .email-template {
            background-color: #f1f1f1;
            padding: 10px;
            border-radius: 5px;
            font-size: 0.9em;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar text-white p-3">
            <div class="text-center mb-4">
                <img src="/BHBMS/images/logo.png" alt="Logo" class="img-fluid" style="max-width: 100px;">
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
                        <a class="nav-link text-white active" href="notif.php"><span class="material-icons me-2">notifications</span>Notification</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link text-white" href="settings.php"><span class="material-icons me-2">settings</span>Settings</a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link text-white" href="logout.php"><span class="material-icons me-2">logout</span>Log Out</a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="flex-grow-1">
            <header class="d-flex justify-content-between align-items-center p-3 border-bottom">
                <h4 class="mb-0" style="font-weight: bold;"><span id="boardingHouseName"><?php echo htmlspecialchars($user['boardinghousename'] ?? 'N/A'); ?></span> Boarding House</h4>
                <div class="d-flex align-items-center gap-3">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Search...">
                        <button class="btn" type="button">
                            <span class="material-icons">search</span>
                        </button>
                    </div>
                    <button class="btn btn-outline-primary position-relative">
                        <span class="material-icons">notifications</span>
                    </button>
                    <button class="btn btn-outline-secondary">
                        <span class="material-icons">calendar_month</span>
                    </button>
                </div>
            </header>

            <!-- Main Content -->
            <div class="content p-4">
                <div class="mb-4">
                    <h5 style="font-weight: bold; color: #360938; border-bottom: 2px solid #360938; padding-bottom: 10px;">
                        NOTIFICATIONS
                    </h5>
                    <div class="container mt-5">
                        <div class="card shadow-sm p-4">
                            <h4>Check Important Announcements</h4>
                            <!-- Notification Previews -->
                            <?php foreach ($notifications as $notif): ?>
                                <div id="notification-<?php echo $notif['id']; ?>" 
                                     class="notification-preview p-3 border-bottom d-flex justify-content-between align-items-center" 
                                     style="cursor: pointer;" 
                                     data-bs-toggle="modal" 
                                     data-bs-target="#notifDetailModal-<?php echo $notif['id']; ?>">
                                    <span class="fw-bold">📢 <?php echo htmlspecialchars($notif['message']); ?></span>
                                    <span class="badge bg-<?php echo $notif['status'] == 'New' ? 'danger' : 'secondary'; ?>">
                                        <?php echo $notif['status']; ?>
                                    </span>
                                </div>
                                <!-- Detailed Notification Modal -->
                                <div class="modal fade" 
                                     id="notifDetailModal-<?php echo $notif['id']; ?>" 
                                     tabindex="-1" 
                                     aria-labelledby="notifDetailModalLabel-<?php echo $notif['id']; ?>" 
                                     aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content shadow">
                                            <div class="modal-header bg-warning text-dark">
                                                <h5 class="modal-title" id="notifDetailModalLabel-<?php echo $notif['id']; ?>">
                                                    📢 Notification
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p><strong>Message:</strong> <?php echo htmlspecialchars($notif['message']); ?></p>
                                                <p><strong>Date:</strong> <?php echo $notif['created_at']; ?></p>
                                                <?php if ($notif['message'] == $notification_message): ?>
                                                    <p><strong>Suggested Email Template:</strong></p>
                                                    <div class="email-template"><?php echo htmlspecialchars($email_template); ?></div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer d-flex justify-content-between">
                                                <div>
                                                    <button class="btn btn-outline-success btn-sm" 
                                                            onclick="markAsRead(<?php echo $notif['id']; ?>)">Mark as Read</button>
                                                    <button class="btn btn-outline-secondary btn-sm" 
                                                            onclick="markAsUnread(<?php echo $notif['id']; ?>)">Mark as Unread</button>
                                                </div>
                                                <button class="btn btn-outline-danger btn-sm" 
                                                        onclick="deleteNotification(<?php echo $notif['id']; ?>)">Delete</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (empty($notifications)): ?>
                                <p>No notifications found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function markAsRead(id) {
            $.ajax({
                url: 'update_notification.php',
                method: 'POST',
                data: { id: id, status: 'Read' },
                success: function() {
                    $('#notification-' + id + ' .badge')
                        .removeClass('bg-danger')
                        .addClass('bg-secondary')
                        .text('Read');
                    $('#notifDetailModal-' + id).modal('hide');
                    console.log('Marked as read:', id);
                },
                error: function(xhr, status, error) {
                    console.error('Error marking as read:', error);
                }
            });
        }

        function markAsUnread(id) {
            $.ajax({
                url: 'update_notification.php',
                method: 'POST',
                data: { id: id, status: 'New' },
                success: function() {
                    $('#notification-' + id + ' .badge')
                        .removeClass('bg-secondary')
                        .addClass('bg-danger')
                        .text('New');
                    $('#notifDetailModal-' + id).modal('hide');
                    console.log('Marked as unread:', id);
                },
                error: function(xhr, status, error) {
                    console.error('Error marking as unread:', error);
                }
            });
        }

        function deleteNotification(id) {
            $.ajax({
                url: 'update_notification.php',
                method: 'POST',
                data: { id: id, action: 'delete' },
                success: function() {
                    $('#notification-' + id).remove();
                    $('#notifDetailModal-' + id).modal('hide');
                    console.log('Deleted notification:', id);
                },
                error: function(xhr, status, error) {
                    console.error('Error deleting notification:', error);
                }
            });
        }
    </script>
</body>
</html>