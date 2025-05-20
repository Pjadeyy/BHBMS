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
$isPreview = isset($_GET['preview']) && $_GET['preview'] === 'true';

// Prepare the query with filters
$current_month = date('Y-m');
$month_start = $current_month . '-01';
$month_end = date('Y-m-t');

$query = "
    SELECT p.payment_date, v.id AS visitor_id, v.name AS visitor_name,
           b.id AS boarder_id, CONCAT(b.firstname, ' ', b.lastname) AS boarder_name,
           b.room, p.days, p.amount
    FROM payments p
    JOIN boarders b ON p.boarder_id = b.id
    LEFT JOIN visitors v ON p.boarder_id = v.boarder_id AND p.user_id = v.user_id
    WHERE p.user_id = ? AND p.payment_type = 'visitor' 
    AND p.payment_date BETWEEN ? AND ?
";

if ($nameFilter) {
    $query .= " AND v.name LIKE ?";
}
if ($roomFilter) {
    $query .= " AND b.room LIKE ?";
}
if ($dateFilter) {
    $query .= " AND DATE(p.payment_date) = ?";
}

$stmt = $conn->prepare($query);

// Create parameter array and types string
$params = [$user_id, $month_start, $month_end];
$types = "iss";

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
$totalPaid = 0;

while ($row = $result->fetch_assoc()) {
    $row['visitor_id'] = $row['visitor_id'] ?? 'N/A';
    $row['visitor_name'] = $row['visitor_name'] ?? 'N/A';
    $totalPaid += $row['amount'];
    $payments[] = $row;
}

// Common CSS for both preview and PDF
$styles = $isPreview ? '
    <style>
        .header { text-align: center; color: #360938; margin-bottom: 20px; }
        .sub-header { text-align: center; color: #666; font-size: 14px; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { background-color: #360938; color: white; padding: 10px; }
        td { padding: 8px; border: 1px solid #ddd; }
        .total-row { font-weight: bold; background-color: #f8f9fa; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .preview-container { padding: 20px; }
    </style>
' : '
    .header { text-align: center; color: #360938; margin-bottom: 20px; }
    .sub-header { text-align: center; color: #666; font-size: 14px; margin-bottom: 30px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th { background-color: #360938; color: white; padding: 10px; }
    td { padding: 8px; border: 1px solid #ddd; }
    .total-row { font-weight: bold; background-color: #f8f9fa; }
    .text-right { text-align: right; }
    .text-center { text-align: center; }
';

// Build HTML content
$html = $isPreview ? '<div class="preview-container">' : '';
$html .= "
    <div class='header'>
        <img src='../../images/receipt_logo.jpg' class='logo' width='100' />
        <h2>{$boardingHouseName}</h2>
        <div class='sub-header'>Visitor's Financial Report - " . date('F Y') . "</div>
    </div>

    <table" . ($isPreview ? ' class="table table-bordered"' : '') . ">
        <thead>
            <tr>
                <th>Payment Date</th>
                <th>Visitor ID</th>
                <th>Visitor Name</th>
                <th>Boarder ID</th>
                <th>Boarder Name</th>
                <th>Room</th>
                <th>Duration of Stay</th>
                <th>Paid Amount</th>
            </tr>
        </thead>
        <tbody>";

foreach ($payments as $payment) {
    $html .= "<tr>
        <td class='text-center'>" . date('Y-m-d', strtotime($payment['payment_date'])) . "</td>
        <td class='text-center'>{$payment['visitor_id']}</td>
        <td>{$payment['visitor_name']}</td>
        <td class='text-center'>{$payment['boarder_id']}</td>
        <td>{$payment['boarder_name']}</td>
        <td class='text-center'>{$payment['room']}</td>
        <td class='text-center'>{$payment['days']} days</td>
        <td class='text-right'>₱" . number_format($payment['amount'], 2) . "</td>
    </tr>";
}

$html .= "
        </tbody>
        <tfoot>
            <tr class='total-row'>
                <td colspan='7' class='text-right'><strong>Total:</strong></td>
                <td class='text-right'><strong>₱" . number_format($totalPaid, 2) . "</strong></td>
            </tr>
        </tfoot>
    </table>

    <div class='text-center' style='margin-top: 30px; font-size: 12px; color: #666;'>
        Generated on " . date('Y-m-d H:i:s') . "
    </div>";

$html .= $isPreview ? '</div>' : '';

if ($isPreview) {
    // Return HTML for preview
    echo $styles . $html;
    exit;
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
    .logo { display: block; margin: 0 auto 15px auto; }
';

$mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);

// Build HTML content
$html = "
    <div class='header'>
        <img src='../../images/receipt_logo.jpg' class='logo' width='100' />
        <h2>{$boardingHouseName}</h2>
        <div class='sub-header'>Visitor's Financial Report - " . date('F Y') . "</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Payment Date</th>
                <th>Visitor ID</th>
                <th>Visitor Name</th>
                <th>Boarder ID</th>
                <th>Boarder Name</th>
                <th>Room</th>
                <th>Duration of Stay</th>
                <th>Paid Amount</th>
            </tr>
        </thead>
        <tbody>";

foreach ($payments as $payment) {
    $html .= "<tr>
        <td class='text-center'>" . date('Y-m-d', strtotime($payment['payment_date'])) . "</td>
        <td class='text-center'>{$payment['visitor_id']}</td>
        <td>{$payment['visitor_name']}</td>
        <td class='text-center'>{$payment['boarder_id']}</td>
        <td>{$payment['boarder_name']}</td>
        <td class='text-center'>{$payment['room']}</td>
        <td class='text-center'>{$payment['days']} days</td>
        <td class='text-right'>₱" . number_format($payment['amount'], 2) . "</td>
    </tr>";
}

$html .= "
        </tbody>
        <tfoot>
            <tr class='total-row'>
                <td colspan='7' class='text-right'><strong>Total:</strong></td>
                <td class='text-right'><strong>₱" . number_format($totalPaid, 2) . "</strong></td>
            </tr>
        </tfoot>
    </table>

    <div class='text-center' style='margin-top: 30px; font-size: 12px; color: #666;'>
        Generated on " . date('Y-m-d H:i:s') . "
    </div>";

$mpdf->WriteHTML($html);

// Set filename
$filename = 'Visitors_Financial_Report_' . date('Y_m') . '.pdf';

// Output PDF
$mpdf->Output($filename, 'D');

$conn->close();
?> 