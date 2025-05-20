<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Mpdf\Mpdf;

try {
    // Get JSON data from POST request and decode it
    $jsonData = file_get_contents('php://input');
    $postData = json_decode($jsonData, true);
    $data = $postData['data'] ?? null;

    if (!$data) {
        throw new Exception('No data received');
    }

    // Get current date
    $currentDate = date('F d, Y');

    // Check if this is a preview request
    $isPreview = isset($_GET['preview']);

    // Common HTML content with conditional styling
    $html = '
    <style>
        body { font-family: Arial, sans-serif; }
        .receipt-header { text-align: center; margin-bottom: 15px; }
        ' . ($isPreview ? '
        /* Preview logo size */
        .receipt-header img { 
            width: 60px;
            height: auto;
            margin-bottom: 5px;
        }
        ' : '
        /* PDF logo size */
        .receipt-header img { 
            width: 15px !important;
            height: 30px !important;
            margin-bottom: 5px;
        }
        ') . '
        .receipt-title { 
            font-size: 18px; 
            font-weight: bold; 
            margin: 5px 0; 
        }
        .receipt-subtitle { font-size: 14px; margin: 4px 0; }
        .receipt-body { 
            margin: 15px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .info-row { margin: 8px 0; line-height: 1.4; }
        .label { 
            font-weight: bold; 
            display: inline-block; 
            width: 140px;
            color: #444;
        }
        .value { display: inline-block; }
        .total-section {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 2px solid #eee;
        }
        .total-row {
            font-weight: bold;
            color: #2c3e50;
        }
        .footer { 
            text-align: center; 
            margin-top: 20px; 
            font-size: 12px;
            color: #666;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 3px;
            color: white;
            font-weight: bold;
            display: inline-block;
            font-size: 12px;
        }
        .status-paid { background-color: #28a745; }
        .status-pending { background-color: #ffc107; color: black; }
        .status-partial { background-color: #007bff; }
        .status-overdue { background-color: #dc3545; }
    </style>

    <div class="receipt-container">
        <div class="receipt-header">
            <img src="../images/receipt_logo.jpg" width="100px" alt="Logo">
            <div class="receipt-title">Kring-Kring Ladies Boarding House</div>
            <div class="receipt-subtitle">Official Receipt</div>
            <div class="receipt-subtitle">Date: ' . $currentDate . '</div>
        </div>

        <div class="receipt-body">';

    // Add receipt details based on payment type
    if ($data['type'] === 'boarder') {
        $totalAmount = $data['rent'] + $data['charge'];
        // Calculate status based on payment amount
        $status = $data['paid'] >= $totalAmount ? 'Paid' : 
                 ($data['paid'] > 0 ? 'Partial' : 
                 (strtotime('now') > strtotime($due_date) ? 'Overdue' : 'Pending'));
        $statusClass = 'status-' . strtolower($status);
        
        $html .= '
            <div class="info-row"><span class="label">Invoice Number:</span><span class="value">' . htmlspecialchars($data['invoice']) . '</span></div>
            <div class="info-row"><span class="label">Boarder ID:</span><span class="value">' . htmlspecialchars($data['boarderId']) . '</span></div>
            <div class="info-row"><span class="label">Name:</span><span class="value">' . htmlspecialchars($data['name']) . '</span></div>
            <div class="info-row"><span class="label">Room:</span><span class="value">' . htmlspecialchars($data['room']) . '</span></div>
            <div class="info-row"><span class="label">Monthly Rent:</span><span class="value">₱' . number_format($data['rent'], 2) . '</span></div>
            <div class="info-row"><span class="label">Appliances Used:</span><span class="value">' . htmlspecialchars($data['appliances']) . ' appliance(s)</span></div>
            <div class="info-row"><span class="label">Late Charge:</span><span class="value">₱' . number_format($data['charge'], 2) . '</span></div>
            
            <div class="total-section">
                <div class="info-row total-row"><span class="label">Total Amount:</span><span class="value">₱' . number_format($totalAmount, 2) . '</span></div>
                <div class="info-row"><span class="label">Amount Paid:</span><span class="value">₱' . number_format($data['paid'], 2) . '</span></div>
                <div class="info-row"><span class="label">Balance:</span><span class="value">₱' . number_format($data['balance'], 2) . '</span></div>
            </div>

            <div class="info-row"><span class="label">Status:</span><span class="value"><span class="status-badge ' . $statusClass . '">' . htmlspecialchars($status) . '</span></span></div>
            <div class="info-row"><span class="label">Payment Date:</span><span class="value">' . date('F d, Y', strtotime($data['paymentDate'])) . '</span></div>';
    } else if ($data['type'] === 'visitor') {
        $html .= '
            <div class="info-row"><span class="label">Invoice Number:</span><span class="value">' . htmlspecialchars($data['invoice']) . '</span></div>
            <div class="info-row"><span class="label">Visitor ID:</span><span class="value">' . htmlspecialchars($data['visitorId']) . '</span></div>
            <div class="info-row"><span class="label">Visitor Name:</span><span class="value">' . htmlspecialchars($data['visitorName']) . '</span></div>
            <div class="info-row"><span class="label">Room:</span><span class="value">' . htmlspecialchars($data['room']) . '</span></div>
            <div class="info-row"><span class="label">Date of Stay:</span><span class="value">' . htmlspecialchars($data['dateStay']) . '</span></div>
            <div class="info-row"><span class="label">Duration:</span><span class="value">' . htmlspecialchars($data['days']) . ' days</span></div>
            <div class="info-row"><span class="label">Boarder ID:</span><span class="value">' . htmlspecialchars($data['boarderId']) . '</span></div>
            <div class="info-row"><span class="label">Boarder Name:</span><span class="value">' . htmlspecialchars($data['boarderName']) . '</span></div>
            <div class="info-row"><span class="label">Boarder Room:</span><span class="value">' . htmlspecialchars($data['boarderRoom']) . '</span></div>
            
            <div class="total-section">
                <div class="info-row total-row"><span class="label">Stay Fee:</span><span class="value">₱' . number_format($data['stayFee'], 2) . '</span></div>
                <div class="info-row"><span class="label">Amount Paid:</span><span class="value">₱' . number_format($data['paid'], 2) . '</span></div>
                <div class="info-row"><span class="label">Balance:</span><span class="value">₱' . number_format($data['balance'], 2) . '</span></div>
            </div>';
    }

    $html .= '
        </div>

        <div class="footer">
            <p><strong>Thank you for your payment!</strong></p>
            <p>This is a computer-generated receipt. No signature required.</p>
            <p>Generated on: ' . $currentDate . '</p>
        </div>
    </div>';

    // Check if this is a preview request
    if (isset($_GET['preview'])) {
        // Return HTML for preview
        echo $html;
        exit;
    }
    // Create new mPDF instance with custom configuration
    $mpdf = new Mpdf([
        'margin_left' => 10,
        'margin_right' => 10,
        'margin_top' => 5,
        'margin_bottom' => 5,
        'format' => 'A4'
    ]);

    // Write PDF
    $mpdf->WriteHTML($html);

    // Output PDF for download
    $mpdf->Output('Receipt-' . $data['invoice'] . '.pdf', 'D');

} catch (Exception $e) {
    // Return error response
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?> 