<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Please log in to view boarder status.']);
    exit();
}

// Database connection
$host = 'localhost';
$username = 'root'; 
$password = ''; 
$database = 'bh';

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Connection failed: ' . $conn->connect_error]);
    exit();
}

$user_id = $_SESSION['user_id'];

// Validate status
$status = $_POST['status'] ?? '';
$valid_statuses = ['Pending', 'Partial', 'Overdue', 'Paid'];
if (!in_array($status, $valid_statuses)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid status selected.']);
    exit();
}

// Fetch boarders by status
$current_month = date('Y-m');
$month_start = $current_month . '-01';
$month_end = date('Y-m-t');
$due_date = date('Y-m-10');

// Fetch rates
$stmt = $conn->prepare("SELECT monthly_rate, appliances_rate FROM rates WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$rate = $result->fetch_assoc();
$monthly_rate = $rate['monthly_rate'] ?? 0.00;
$appliances_rate = $rate['appliances_rate'] ?? 0.00;
$stmt->close();

// Prepare the SQL query based on status
$sql = "";
switch($status) {
    case 'Paid':
        $sql = "
            SELECT DISTINCT b.id, CONCAT(b.firstname, ' ', b.lastname) as name, b.room,
                   COALESCE(SUM(p.amount), 0) as paid_amount
            FROM boarders b
            LEFT JOIN payments p ON b.id = p.boarder_id 
                AND p.payment_date BETWEEN ? AND ?
                AND p.payment_type = 'rent'
            WHERE b.user_id = ? AND b.status = 'active'
            GROUP BY b.id, b.room
            HAVING paid_amount >= (? + (COALESCE(MAX(p.appliances), 0) * ?))
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssidd", $month_start, $month_end, $user_id, $monthly_rate, $appliances_rate);
        break;

    case 'Partial':
        $sql = "
            SELECT DISTINCT b.id, CONCAT(b.firstname, ' ', b.lastname) as name, b.room,
                   COALESCE(SUM(p.amount), 0) as paid_amount
            FROM boarders b
            LEFT JOIN payments p ON b.id = p.boarder_id 
                AND p.payment_date BETWEEN ? AND ?
                AND p.payment_type = 'rent'
            WHERE b.user_id = ? AND b.status = 'active'
            GROUP BY b.id, b.room
            HAVING paid_amount > 0 AND paid_amount < (? + (COALESCE(MAX(p.appliances), 0) * ?))
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssidd", $month_start, $month_end, $user_id, $monthly_rate, $appliances_rate);
        break;

    case 'Overdue':
        $sql = "
            SELECT DISTINCT b.id, CONCAT(b.firstname, ' ', b.lastname) as name, b.room
            FROM boarders b
            LEFT JOIN payments p ON b.id = p.boarder_id 
                AND p.payment_date BETWEEN ? AND ?
                AND p.payment_type = 'rent'
            WHERE b.user_id = ? 
            AND b.status = 'active'
            AND CURRENT_DATE > ?
            GROUP BY b.id, b.room
            HAVING COALESCE(SUM(p.amount), 0) = 0
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $month_start, $month_end, $user_id, $due_date);
        break;

    case 'Pending':
        $sql = "
            SELECT DISTINCT b.id, CONCAT(b.firstname, ' ', b.lastname) as name, b.room
            FROM boarders b
            LEFT JOIN payments p ON b.id = p.boarder_id 
                AND p.payment_date BETWEEN ? AND ?
                AND p.payment_type = 'rent'
            WHERE b.user_id = ? 
            AND b.status = 'active'
            AND CURRENT_DATE <= ?
            GROUP BY b.id, b.room
            HAVING COALESCE(SUM(p.amount), 0) = 0
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $month_start, $month_end, $user_id, $due_date);
        break;

    default:
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid status']);
        exit();
}

$stmt->execute();
$result = $stmt->get_result();
$boarders = [];

while ($row = $result->fetch_assoc()) {
    $boarders[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'room' => $row['room']
    ];
}

$stmt->close();
$conn->close();

// Output JSON
header('Content-Type: application/json');
echo json_encode(['boarders' => $boarders]);
?>