<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$host = 'localhost';
$username = 'root'; 
$password = ''; 
$database = 'bh';

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $boarder_id = trim($_POST['boarder_id']);
    $amount = trim($_POST['paid']);
    $payment_date = trim($_POST['payment_date']);
    $status = trim($_POST['status']);
    $payment_type = trim($_POST['payment_type']);
    $appliances = isset($_POST['appliances']) ? trim($_POST['appliances']) : 0;
    $days = isset($_POST['days']) ? trim($_POST['days']) : 0;

    // Fetch rates for validation
    $stmt = $conn->prepare("SELECT monthly_rate, appliances_rate, late_fee, due_date FROM rates WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $rate = $result->fetch_assoc();
    $monthly_rate = $rate['monthly_rate'] ?? 1000.00;
    $appliances_rate = $rate['appliances_rate'] ?? 100.00;
    $late_fee = $rate['late_fee'] ?? 0.00;
    $due_date = $rate['due_date'] ?? date('Y-m-10');
    // Calculate different daily rates for rent and visitors
    $rent_daily_rate = $monthly_rate / 30; // For boarders: 1000 / 30 ≈ 33.33
    $visitor_daily_rate = 100.00; // Fixed rate for visitors: 100 per day
    $stmt->close();

    $errors = [];
    if (empty($boarder_id)) $errors[] = 'Boarder is required.';
    if (empty($amount) || $amount < 0) $errors[] = 'Valid paid amount is required.';
    if (empty($payment_date)) $errors[] = 'Payment date is required.';
    if (empty($status)) $errors[] = 'Payment status is required.';
    if (empty($payment_type) || !in_array($payment_type, ['rent', 'visitor'])) $errors[] = 'Invalid payment type.';

    if ($payment_type === 'rent') {
        if ($appliances === '' || $appliances < 0) $errors[] = 'Valid number of appliances is required.';
        $total_appliances = $appliances * $appliances_rate;
        $is_late = strtotime($payment_date) > strtotime($due_date) ? 1 : 0;
        $total_late_charge = $is_late ? $late_fee : 0;
        $total_due = $monthly_rate + $total_appliances + $total_late_charge;
    } else {
        if ($days === '' || $days < 0) $errors[] = 'Valid number of days is required.';
        $total_due = $days * $visitor_daily_rate; // Use visitor daily rate for visitors
        $appliances = 0; // No appliances for visitor
    }

    if (empty($errors)) {
        // Check if payment is late
        $is_late = strtotime($payment_date) > strtotime($due_date);
        $current_date = strtotime(date('Y-m-d'));

        // Validate status
        if ($payment_type === 'rent') {
            // Get total amount paid by this boarder for the current month
            $month_start = date('Y-m-01');
            $month_end = date('Y-m-t');
            $stmt = $conn->prepare("SELECT COALESCE(SUM(amount), 0) as total_paid FROM payments WHERE boarder_id = ? AND payment_date BETWEEN ? AND ? AND payment_type = 'rent'");
            $stmt->bind_param("iss", $boarder_id, $month_start, $month_end);
            $stmt->execute();
            $result = $stmt->get_result();
            $total_paid = $result->fetch_assoc()['total_paid'] + $amount; // Include current payment
            $stmt->close();

            if ($total_paid >= $total_due) {
                // Full payment received (including previous payments)
                if ($status !== 'Paid') {
                    $errors[] = 'Status should be Paid for full payments.';
                }
            } else if ($amount > 0) {
                // Partial payment
                if ($status !== 'Partial') {
                    $errors[] = 'Status should be Partial for partial payments.';
                }
            } else {
                // No payment
                if ($current_date > strtotime($due_date)) {
                    if ($status !== 'Overdue') {
                        $errors[] = 'Status should be Overdue when no payment is made after the due date.';
                    }
                } else {
                    if ($status !== 'Pending') {
                        $errors[] = 'Status should be Pending when no payment is made before the due date.';
                    }
                }
            }
        }

        if (empty($errors)) {
            // Start transaction
            $conn->begin_transaction();
            try {
                // Insert payment record
                $stmt = $conn->prepare("INSERT INTO payments (user_id, boarder_id, amount, payment_date, status, payment_type, days) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iidsssi", $user_id, $boarder_id, $amount, $payment_date, $status, $payment_type, $days);
                $stmt->execute();
                $payment_id = $stmt->insert_id;
                $stmt->close();

                // If it's a visitor payment, save visitor information
                if ($payment_type === 'visitor' && isset($_POST['visitor_id']) && isset($_POST['visitor_name'])) {
                    // Create visitors table if it doesn't exist
                    $conn->query("CREATE TABLE IF NOT EXISTS visitors (
                        id VARCHAR(50) NOT NULL,
                        name VARCHAR(100) NOT NULL,
                        boarder_id INT NOT NULL,
                        user_id INT NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (id, boarder_id, user_id),
                        FOREIGN KEY (boarder_id) REFERENCES boarders(id),
                        FOREIGN KEY (user_id) REFERENCES users(id)
                    )");

                    // Insert visitor record
                    $visitor_id = trim($_POST['visitor_id']);
                    $visitor_name = trim($_POST['visitor_name']);
                    
                    $stmt = $conn->prepare("INSERT INTO visitors (id, name, boarder_id, user_id) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = ?");
                    $stmt->bind_param("ssiis", $visitor_id, $visitor_name, $boarder_id, $user_id, $visitor_name);
                    $stmt->execute();
                    $stmt->close();
                }

                // Commit transaction
                $conn->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                // Rollback on error
                $conn->rollback();
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => implode(' ', $errors)]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => implode(' ', $errors)]);
    }
}

$conn->close();
?>