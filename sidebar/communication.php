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

// Fetch boarders for email dropdown
$stmt = $conn->prepare("SELECT id, email, CONCAT(firstname, ' ', lastname) AS name, room FROM boarders WHERE user_id = ? AND status = 'active'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$boarders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch rates for email template
$stmt = $conn->prepare("SELECT monthly_rate, late_fee FROM rates WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$rates = $result->fetch_assoc();
$monthly_rate = $rates['monthly_rate'] ?? 1000.00;
$late_fee = $rates['late_fee'] ?? 50.00;
$stmt->close();

// Email template
$email_template = "
Dear [Boarder Name],

This is a friendly reminder that your rent payment of ₱[Rent Amount] for [Room Number] is due on the 10th of [Month]. Please ensure timely payment to avoid a late fee of ₱[Late Fee].

Payment can be made via [Payment Method]. Contact us at [Your Contact Info] for any questions.

Thank you,
Kring Kring Ladies Boarding House
";

// Handle form submission with PHPMailer
require 'phpmailer-main/PHPMailer.php';
require 'phpmailer-main/SMTP.php';
require 'phpmailer-main/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recipient_id'])) {
    $recipient_id = $_POST['recipient_id'];
    $subject = $_POST['emailSubject'] ?? '';
    $body = $_POST['emailMessage'] ?? '';
    $link = $_POST['link'] ?? '';
    $month = date('F Y'); // e.g., May 2025
    $payment_method = 'cash'; // Customize as needed
    $contact_info = $user['email'] ?? 'contact@boardinghouse.com';

    // Find the recipient
    $recipient = null;
    foreach ($boarders as $boarder) {
        if ($boarder['id'] == $recipient_id) {
            $recipient = $boarder;
            break;
        }
    }

    if ($recipient) {
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'jadeunders@gmail.com'; // Gmail address
            $mail->Password = 'kghzepggzdsqqaun'; // App Password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom($user['email'] ?? 'contact@boardinghouse.com', $user['boardinghousename'] ?? 'Kring-Kring Ladies Boarding House');
            $mail->addAddress($recipient['email'], $recipient['name']);
            $mail->Subject = $subject;
            $mail->isHTML(false);

            // Use email template for rent reminders
            if (stripos($subject, 'rent') !== false || stripos($subject, 'payment') !== false) {
                $body = str_replace(
                    ['[Boarder Name]', '[Rent Amount]', '[Room Number]', '[Month]', '[Late Fee]', '[Payment Method]', '[Your Contact Info]'],
                    [$recipient['name'], number_format($monthly_rate, 2), $recipient['room'] ?? 'N/A', $month, number_format($late_fee, 2), $payment_method, $contact_info],
                    $email_template
                );
            }
            if ($link) {
                $body .= "\n\nLink: $link";
            }
            $mail->Body = $body;

            // Handle attachments
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $mail->addAttachment($_FILES['image']['tmp_name'], $_FILES['image']['name']);
            }
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $mail->addAttachment($_FILES['file']['tmp_name'], $_FILES['file']['name']);
            }

            $mail->send();

            // Log the email to sent_emails table
            $stmt = $conn->prepare("INSERT INTO sent_emails (user_id, boarder_id, subject, body, sent_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("iiss", $user_id, $recipient_id, $subject, $body);
            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Email sent successfully to ' . htmlspecialchars($recipient['name']) . ' and logged in database.</div>';
            } else {
                $message = '<div class="alert alert-warning">Email sent successfully to ' . htmlspecialchars($recipient['name']) . ', but failed to log in database: ' . htmlspecialchars($conn->error) . '</div>';
            }
            $stmt->close();

        } catch (Exception $e) {
            $message = '<div class="alert alert-danger">Failed to send email. Error: ' . htmlspecialchars($mail->ErrorInfo) . '</div>';
        }
    } else {
        $message = '<div class="alert alert-danger">Invalid recipient selected.</div>';
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Communication - Boarding House</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .icon-button {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .border-box {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
        }
        #boarderStatusResult {
            max-height: 300px;
            overflow-y: auto;
        }
        .input-group { max-width: 300px; }
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
                <img src="/BHBMS/images/logo.png" alt="Logo" class="img-fluid" style="max-width: 100px;">
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
                    <a class="nav-link text-white" href="boarder_profile.php"><span class="material-icons me-2">group</span>Boarders Profile</a>
                </li>
                <li class="nav-item mb-2">
                    <a class="nav-link text-white active" href="communication.php"><span class="material-icons me-2">chat</span>Communication</a>
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
            <!-- Header -->
            <header class="d-flex justify-content-between align-items-center p-3 border-bottom">
                <h4 class="mb-0" style="font-weight: bold;"><span id="boardingHouseName"><?php echo htmlspecialchars($user['boardinghousename'] ?? 'N/A'); ?></span> Ladies Boarding House</h4>
                <div class="d-flex align-items-center gap-3">
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
                        COMMUNICATION
                    </h5>
                </div>

                <div class="container-fluid mt-4">
                    <div class="row">
                        <!-- LEFT: Email/Message Form -->
                        <div class="col-md-6">
                            <div class="border-box">
                                <h5>Send Message to Boarder</h5>
                                <?php echo $message; ?>
                                <form id="emailForm" method="POST" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="recipient_id" class="form-label">Recipient</label>
                                        <select class="form-select" id="recipient_id" name="recipient_id" required>
                                            <option value="">Select Boarder</option>
                                            <?php foreach ($boarders as $boarder): ?>
                                                <option value="<?php echo htmlspecialchars($boarder['id']); ?>">
                                                    <?php echo htmlspecialchars($boarder['name'] . ' (' . $boarder['email'] . ')'); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="emailSubject" class="form-label">Subject</label>
                                        <input type="text" class="form-control" id="emailSubject" name="emailSubject" placeholder="Enter subject" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="emailMessage" class="form-label">Message</label>
                                        <textarea class="form-control" id="emailMessage" name="emailMessage" rows="6" placeholder="Write your message here..." required></textarea>
                                    </div>
                                    <!-- Attachments -->
                                    <div class="mb-3 d-flex gap-2">
                                        <button type="button" class="btn btn-outline-secondary icon-button" id="attachLink" title="Add Link">
                                            <span class="material-icons">link</span>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary icon-button" id="attachImage" title="Attach Image">
                                            <span class="material-icons">image</span>
                                        </button>
                                        <button type="button"incidence btn btn-outline-secondary icon-button" id="attachFile" title="Attach File">
                                            <span class="material-icons">attach_file</span>
                                        </button>
                                    </div>
                                    <!-- Hidden Inputs for Attachments -->
                                    <input type="url" id="linkInput" name="link" class="form-control mb-3" style="display: none;">
                                    <input type="file" id="imageInput" name="image" accept="image/*" class="form-control mb-3" style="display: none;">
                                    <input type="file" id="fileInput" name="file" class="form-control mb-3" style="display: none;">
                                    <button type="submit" class="btn btn-primary">Send</button>
                                </form>
                            </div>
                        </div>

                        <!-- RIGHT: Dropdown for Boarder Status -->
                        <div class="col-md-6">
                            <div class="border-box">
                                <h5>Boarder Status</h5>
                                <div class="dropdown">
                                    <button class="btn btn-secondary dropdown-toggle" type="button" id="statusDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                        Select Status
                                    </button>
                                    <ul class="dropdown-menu" aria-labelledby="statusDropdown">
                                        <li><a class="dropdown-item" href="#" data-status="Pending">Pending</a></li>
                                        <li><a class="dropdown-item" href="#" data-status="Partial">Partial</a></li>
                                        <li><a class="dropdown-item" href="#" data-status="Overdue">Overdue</a></li>
                                        <li><a class="dropdown-item" href="#" data-status="Paid">Paid</a></li>
                                    </ul>
                                </div>
                                <!-- Result Display Area -->
                                <div id="boarderStatusResult" class="mt-4">
                                    <!-- Boarders will be listed here via AJAX -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Attachment button handlers
            $('#attachLink').click(function() {
                const link = prompt('Enter the URL to attach:');
                if (link) {
                    $('#linkInput').val(link).show();
                }
            });

            $('#attachImage').click(function() {
                $('#imageInput').click();
            });

            $('#attachFile').click(function() {
                $('#fileInput').click();
            });

            $('#imageInput').change(function() {
                if (this.files.length > 0) {
                    $('#imageInput').after('<div class="form-text">Selected: ' + this.files[0].name + '</div>');
                }
            });

            $('#fileInput').change(function() {
                if (this.files.length > 0) {
                    $('#fileInput').after('<div class="form-text">Selected: ' + this.files[0].name + '</div>');
                }
            });

            // Status dropdown handler
            let allBoarders = [];
            $('.dropdown-item').click(function(e) {
                e.preventDefault();
                const status = $(this).data('status');
                $('#statusDropdown').text(status);
                $('#boarderStatusResult').html('<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</p>');

                $.ajax({
                    url: 'fetch_status.php',
                    type: 'POST',
                    data: { status: status },
                    dataType: 'json',
                    success: function(response) {
                        if (response.error) {
                            $('#boarderStatusResult').html('<p class="text-danger">' + response.error + '</p>');
                            return;
                        }
                        
                        allBoarders = response.boarders || [];
                        displayBoarders(allBoarders);
                    },
                    error: function() {
                        $('#boarderStatusResult').html('<p class="text-danger">Failed to load boarders.</p>');
                    }
                });
            });

            function displayBoarders(boarders) {
                if (boarders.length === 0) {
                    $('#boarderStatusResult').html('<p class="text-center">No boarders found with this status.</p>');
                    return;
                }

                let html = '<div class="table-responsive"><table class="table table-hover">';
                html += '<thead><tr>';
                html += '<th>Name</th>';
                html += '<th>Room</th>';
                html += '<th>Actions</th>';
                html += '</tr></thead><tbody>';

                boarders.forEach(boarder => {
                    html += '<tr>';
                    html += '<td>' + boarder.name + '</td>';
                    html += '<td><span class="badge bg-info">' + (boarder.room || 'N/A') + '</span></td>';
                    html += '<td>';
                    html += '<button class="btn btn-sm btn-primary me-1 select-recipient" data-id="' + boarder.id + '" data-name="' + boarder.name + '" data-room="' + (boarder.room || 'N/A') + '">';
                    html += '<i class="fas fa-envelope"></i> Select';
                    html += '</button>';
                    html += '</td>';
                    html += '</tr>';
                });

                html += '</tbody></table></div>';
                $('#boarderStatusResult').html(html);

                // Add click handler for select buttons
                $('.select-recipient').click(function() {
                    const boarderId = $(this).data('id');
                    const boarderName = $(this).data('name');
                    const boarderRoom = $(this).data('room');
                    $('#recipient_id').val(boarderId);
                    
                    // Pre-fill subject with room number
                    if ($('#emailSubject').val() === '') {
                        $('#emailSubject').val('Rent Payment Reminder - Room ' + boarderRoom);
                    }
                    
                    // Scroll to the email form
                    $('html, body').animate({
                        scrollTop: $("#emailForm").offset().top
                    }, 500);
                });
            }
        });
    </script>
</body>
</html>