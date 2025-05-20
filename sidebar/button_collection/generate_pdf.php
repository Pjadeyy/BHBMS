<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';

if (!isset($_SESSION['user_id'])) {
    die('Unauthorized access');
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

// Fetch user data for boarding house name
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT boardinghousename FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$boardingHouseName = $user['boardinghousename'] ?? 'Boarding House';

// Get filter parameters
$nameFilter = $_GET['name'] ?? '';
$roomFilter = $_GET['room'] ?? '';
$dateFilter = $_GET['date'] ?? '';

// Prepare the query with filters
$current_month = date('Y-m');
$month_start = $current_month . '-01';
$month_end = date('Y-m-t');

$query = "
    SELECT p.payment_date, b.id AS boarder_id, CONCAT(b.firstname, ' ', b.lastname) AS boarder_name,
           b.room, p.amount, p.appliances,
           (SELECT monthly_rate FROM rates WHERE user_id = ?) as monthly_rate,
           (SELECT appliances_rate FROM rates WHERE user_id = ?) as appliances_rate
    FROM payments p
    JOIN boarders b ON p.boarder_id = b.id
    WHERE p.user_id = ? AND p.payment_type = 'rent' 
    AND p.payment_date BETWEEN ? AND ?
";

if ($nameFilter) {
    $query .= " AND CONCAT(b.firstname, ' ', b.lastname) LIKE ?";
}
if ($roomFilter) {
    $query .= " AND b.room LIKE ?";
}
if ($dateFilter) {
    $query .= " AND DATE(p.payment_date) = ?";
}

$stmt = $conn->prepare($query);

// Create parameter array and types string
$params = [$user_id, $user_id, $user_id, $month_start, $month_end];
$types = "iiiss";

if ($nameFilter) {
    $params[] = "%$nameFilter%";
    $types .= "s";
}
if ($roomFilter) {
    $params[] = "%$roomFilter%";
    $types .= "s";
}
if ($dateFilter) {
    $params[] = $dateFilter;
    $types .= "s";
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$payments = [];
$totalRent = 0;
$totalPaid = 0;

while ($row = $result->fetch_assoc()) {
    $row['total_rent'] = $row['monthly_rate'] + ($row['appliances'] * $row['appliances_rate']);
    $totalRent += $row['total_rent'];
    $totalPaid += $row['amount'];
    $payments[] = $row;
}

// Create new mPDF instance
$mpdf = new \Mpdf\Mpdf([
    'margin_left' => 15,
    'margin_right' => 15,
    'margin_top' => 15,
    'margin_bottom' => 15,
]);

// Add custom CSS
$stylesheet = '
    .header { text-align: center; color: #360938; margin-bottom: 20px; }
    .sub-header { text-align: center; color: #666; font-size: 14px; margin-bottom: 30px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th { background-color: #360938; color: white; padding: 10px; }
    td { padding: 8px; border: 1px solid #ddd; }
    .total-row { font-weight: bold; background-color: #f8f9fa; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
';

$mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);

// Build HTML content
$html = "
    <div class='header'>
        <h2>{$boardingHouseName}</h2>
        <div class='sub-header'>Monthly Rent Collection Report - " . date('F Y') . "</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Payment Date</th>
                <th>Boarder ID</th>
                <th>Boarder Name</th>
                <th>Room</th>
                <th>Total Rent</th>
                <th>Paid Amount</th>
            </tr>
        </thead>
        <tbody>";

foreach ($payments as $payment) {
    $html .= "<tr>
        <td class='text-center'>" . date('Y-m-d', strtotime($payment['payment_date'])) . "</td>
        <td class='text-center'>{$payment['boarder_id']}</td>
        <td>{$payment['boarder_name']}</td>
        <td class='text-center'>{$payment['room']}</td>
        <td class='text-right'>₱" . number_format($payment['total_rent'], 2) . "</td>
        <td class='text-right'>₱" . number_format($payment['amount'], 2) . "</td>
    </tr>";
}

$html .= "
        </tbody>
        <tfoot>
            <tr class='total-row'>
                <td colspan='4' class='text-right'>Total:</td>
                <td class='text-right'>₱" . number_format($totalRent, 2) . "</td>
                <td class='text-right'>₱" . number_format($totalPaid, 2) . "</td>
            </tr>
        </tfoot>
    </table>

    <div class='text-center' style='margin-top: 30px; font-size: 12px;'>
        Generated on " . date('Y-m-d H:i:s') . "
    </div>";

$mpdf->WriteHTML($html);

// Output PDF
$mpdf->Output('Monthly_Rent_Collection_' . date('Y_m') . '.pdf', 'D');

$conn->close();
?> 