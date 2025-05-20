<?php
require_once __DIR__ . '/../vendor/autoload.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../authentication/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    die("No data received.");
}

// Check if this is a preview request
$isPreview = isset($_GET['preview']);

$html = '
<style>
    body { font-family: Arial, sans-serif; }
    .main-header { 
        text-align: center; 
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #333;
    }
    .main-header img { 
        width: 60px !important;
        height: auto !important;
        margin-bottom: 5px;
    }
    .main-title { 
        font-size: 20px; 
        font-weight: bold; 
        margin: 10px 0 5px 0;
    }
    .sub-title {
        font-size: 16px;
        margin: 5px 0;
        color: #444;
    }
    .date {
        font-size: 14px;
        color: #666;
    }
    .section-title {
        font-size: 16px;
        font-weight: bold;
        margin: 20px 0 10px 0;
        padding: 5px 10px;
        background-color: #f5f5f5;
    }
    .receipt-item {
        margin: 15px 0;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
    .info-row {
        margin: 5px 0;
        display: flex;
        justify-content: space-between;
    }
    .label {
        font-weight: bold;
        color: #555;
        width: 150px;
    }
    .value {
        flex: 1;
        text-align: right;
    }
    .status-badge {
        padding: 3px 8px;
        border-radius: 3px;
        color: white;
        font-size: 12px;
    }
    .status-paid { background-color: #28a745; }
    .status-pending { background-color: #ffc107; color: black; }
    .status-partial { background-color: #007bff; }
    .status-overdue { background-color: #dc3545; }
    .footer {
        text-align: center;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #ddd;
        font-size: 12px;
        color: #666;
    }
</style>

<div class="main-header">
    <img src="../images/receipt_logo.jpg" width="100px" alt="Logo">
    <div class="main-title">Kring-Kring Ladies Boarding House</div>
    <div class="sub-title">Receipt/Invoice Summary</div>
    <div class="date">Generated on: ' . date('F d, Y') . '</div>
</div>';

// Add Boarders Data if exists
if (!empty($data['boarders'])) {
    $html .= '<div class="section-title">Boarders Collected</div>';
    foreach ($data['boarders'] as $boarder) {
        $statusClass = 'status-' . strtolower($boarder['status']);
        $html .= '
        <div class="receipt-item">
            <div class="info-row">
                <span class="label">Invoice Number:</span>
                <span class="value">' . htmlspecialchars($boarder['invoice_number']) . '</span>
            </div>
            <div class="info-row">
                <span class="label">Name:</span>
                <span class="value">' . htmlspecialchars($boarder['name']) . '</span>
            </div>
            <div class="info-row">
                <span class="label">Room:</span>
                <span class="value">' . htmlspecialchars($boarder['room']) . '</span>
            </div>
            <div class="info-row">
                <span class="label">Rent:</span>
                <span class="value">₱' . number_format($boarder['rent'], 2) . '</span>
            </div>
            <div class="info-row">
                <span class="label">Status:</span>
                <span class="value"><span class="status-badge ' . $statusClass . '">' . htmlspecialchars($boarder['status']) . '</span></span>
            </div>
            <div class="info-row">
                <span class="label">Late Charge:</span>
                <span class="value">₱' . number_format($boarder['charge'], 2) . '</span>
            </div>
            <div class="info-row">
                <span class="label">Paid:</span>
                <span class="value">₱' . number_format($boarder['paid'], 2) . '</span>
            </div>
            <div class="info-row">
                <span class="label">Balance:</span>
                <span class="value">₱' . number_format($boarder['balance'], 2) . '</span>
            </div>
        </div>';
    }
}

// Add Visitors Data if exists
if (!empty($data['visitors'])) {
    $html .= '<div class="section-title">Visitors Collected</div>';
    foreach ($data['visitors'] as $visitor) {
        $html .= '
        <div class="receipt-item">
            <div class="info-row">
                <span class="label">Invoice Number:</span>
                <span class="value">' . htmlspecialchars($visitor['invoice_number']) . '</span>
            </div>
            <div class="info-row">
                <span class="label">Visitor Name:</span>
                <span class="value">' . htmlspecialchars($visitor['visitor_name']) . '</span>
            </div>
            <div class="info-row">
                <span class="label">Room:</span>
                <span class="value">' . htmlspecialchars($visitor['room']) . '</span>
            </div>
            <div class="info-row">
                <span class="label">Date of Stay:</span>
                <span class="value">' . htmlspecialchars($visitor['date_stay']) . '</span>
            </div>
            <div class="info-row">
                <span class="label">Duration:</span>
                <span class="value">' . htmlspecialchars($visitor['days']) . ' days</span>
            </div>
            <div class="info-row">
                <span class="label">Paid:</span>
                <span class="value">₱' . number_format($visitor['paid'], 2) . '</span>
            </div>
            <div class="info-row">
                <span class="label">Balance:</span>
                <span class="value">₱' . number_format($visitor['balance'], 2) . '</span>
            </div>
        </div>';
    }
}

$html .= '
<div class="footer">
    <p><strong>Thank you for your payments!</strong></p>
    <p>This is a computer-generated receipt summary. No signature required.</p>
</div>';

if ($isPreview) {
    // Return HTML for preview
    echo $html;
    exit;
}

// Create new mPDF instance
$mpdf = new \Mpdf\Mpdf([
    'margin_left' => 10,
    'margin_right' => 10,
    'margin_top' => 10,
    'margin_bottom' => 10,
    'format' => 'A4'
]);

// Write PDF
$mpdf->WriteHTML($html);

// Output PDF
$mpdf->Output('All_Receipts.pdf', 'D');
?>
