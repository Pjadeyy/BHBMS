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
$yearFilter = $_GET['year'] ?? '';
$monthFilter = $_GET['month'] ?? '';
$layout = $_GET['layout'] ?? 'portrait';

// Fetch rates
$stmt = $conn->prepare("SELECT monthly_rate, appliances_rate FROM rates WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$rate = $result->fetch_assoc();
$monthly_rate = $rate['monthly_rate'] ?? 1000.00;
$appliances_rate = $rate['appliances_rate'] ?? 100.00;
$stmt->close();

// Build the query with filters
$query = "
    SELECT 
        p.payment_date,
        b.id AS boarder_id,
        CONCAT(b.firstname, ' ', b.lastname) AS boarder_name,
        b.room,
        p.amount,
        p.appliances,
        YEAR(p.payment_date) as year,
        MONTHNAME(p.payment_date) as month,
        DAY(p.payment_date) as day
    FROM payments p
    JOIN boarders b ON p.boarder_id = b.id
    WHERE p.user_id = ? AND p.payment_type = 'rent'
";

$params = [$user_id];
$types = "i";

if ($nameFilter) {
    $query .= " AND CONCAT(b.firstname, ' ', b.lastname) LIKE ?";
    $params[] = "%$nameFilter%";
    $types .= "s";
}

if ($yearFilter) {
    $query .= " AND YEAR(p.payment_date) = ?";
    $params[] = $yearFilter;
    $types .= "s";
}

if ($monthFilter) {
    $query .= " AND MONTHNAME(p.payment_date) = ?";
    $params[] = $monthFilter;
    $types .= "s";
}

$query .= " ORDER BY p.payment_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$payments = [];
$totalRent = 0;
$totalPaid = 0;

while ($row = $result->fetch_assoc()) {
    $row['total_rent'] = $monthly_rate + ($row['appliances'] * $appliances_rate);
    $totalRent += $row['total_rent'];
    $totalPaid += $row['amount'];
    $payments[] = $row;
}

// Create new mPDF instance$mpdf = new \Mpdf\Mpdf([    'margin_left' => 15,    'margin_right' => 15,    'margin_top' => 15,    'margin_bottom' => 15,    'orientation' => $layout]);// Add custom CSS$stylesheet = '    .header { text-align: center; color: #360938; margin-bottom: 20px; }    .sub-header { text-align: center; color: #666; font-size: 14px; margin-bottom: 30px; }    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }    th { background-color: #360938; color: white; padding: 10px; }    td { padding: 8px; border: 1px solid #ddd; }    .total-row { font-weight: bold; background-color: #f8f9fa; }    .text-right { text-align: right; }    .text-center { text-align: center; }    .logo { display: block; margin: 0 auto 15px auto; }';

$mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);

// Build HTML content$html = "    <div class='header'>        <img src='../../images/receipt_logo.jpg' class='logo' width='100' />        <h2>{$boardingHouseName}</h2>        <div class='sub-header'>Yearly Rent Collection Report" .         ($yearFilter ? " - Year: $yearFilter" : "") .         ($monthFilter ? " - Month: $monthFilter" : "") . "</div>    </div>    <table>        <thead>            <tr>                <th>Year</th>                <th>Month</th>                <th>Day</th>                <th>Boarder ID</th>                <th>Boarder Name</th>                <th>Room</th>                <th>Total Rent</th>                <th>Paid Amount</th>            </tr>        </thead>        <tbody>";

foreach ($payments as $payment) {    $html .= "<tr>        <td class='text-center'>{$payment['year']}</td>        <td class='text-center'>{$payment['month']}</td>        <td class='text-center'>{$payment['day']}</td>        <td class='text-center'>{$payment['boarder_id']}</td>        <td>{$payment['boarder_name']}</td>        <td class='text-center'>{$payment['room']}</td>        <td class='text-right'>₱" . number_format($payment['total_rent'], 2) . "</td>        <td class='text-right'>₱" . number_format($payment['amount'], 2) . "</td>    </tr>";}$html .= "        </tbody>        <tfoot>            <tr class='total-row'>                <td colspan='6' class='text-right'><strong>Total:</strong></td>                <td class='text-right'><strong>₱" . number_format($totalRent, 2) . "</strong></td>                <td class='text-right'><strong>₱" . number_format($totalPaid, 2) . "</strong></td>            </tr>        </tfoot>    </table>    <div class='text-center' style='margin-top: 30px; font-size: 12px; color: #666;'>        Generated on " . date('Y-m-d H:i:s') . "    </div>";

$mpdf->WriteHTML($html);

// Set filename with filters if any
$filename = 'Yearly_Rent_Collection';
if ($yearFilter) $filename .= "_$yearFilter";
if ($monthFilter) $filename .= "_$monthFilter";
$filename .= '.pdf';

// Output PDF
$mpdf->Output($filename, 'D');

$conn->close();
?> 