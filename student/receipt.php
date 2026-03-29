<?php
/**
 * Generate PDF Receipt
 * EduTrackr - School Management System
 */
require_once '../includes/functions.php';
requireRole('student');

$paymentId = isset($_GET['payment_id']) ? intval($_GET['payment_id']) : 0;

if ($paymentId <= 0) {
    die('Invalid payment ID');
}

// Get student data
$student = getStudentData($_SESSION['user_id']);
if (!$student || !isset($student['student_id'])) {
    die('Student record not found');
}

$studentId = $student['student_id'];
global $conn;

// Get payment details
$stmt = $conn->prepare("SELECT fp.*, 
                       sf.amount as fee_amount,
                       fg.name as fee_group_name,
                       fi.name as installment_name,
                       ef.description as extra_fee_description,
                       s.roll_no, u.name as student_name, c.name as class_name
                       FROM fee_payments fp
                       JOIN student_fees sf ON fp.student_fee_id = sf.student_fee_id
                       JOIN fee_groups fg ON sf.fee_group_id = fg.fee_group_id
                       JOIN students s ON sf.student_id = s.student_id
                       JOIN users u ON s.user_id = u.id
                       LEFT JOIN classes c ON s.class_id = c.id
                       LEFT JOIN fee_installments fi ON sf.installment_id = fi.installment_id
                       LEFT JOIN extra_fees ef ON sf.extra_fee_id = ef.extra_fee_id
                       WHERE fp.payment_id = ? AND sf.student_id = ?");
$stmt->bind_param("ii", $paymentId, $studentId);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$payment) {
    die('Payment record not found or access denied');
}

// Parse payment details
$details = [];
if ($payment['details']) {
    $details = json_decode($payment['details'], true);
}

// Generate HTML receipt
$schoolName = "EduTrackr School";
$schoolAddress = "123 Education Street, Learning City, LC 12345";
$schoolPhone = "+1 (555) 123-4567";
$schoolEmail = "info@edutrackr.com";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?php echo htmlspecialchars($payment['receipt_number']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #3f51b5;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .school-name {
            font-size: 28px;
            font-weight: bold;
            color: #3f51b5;
            margin-bottom: 10px;
        }
        .school-details {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }
        .receipt-title {
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin: 30px 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .receipt-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
        .info-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .info-value {
            font-size: 16px;
            font-weight: bold;
            color: #333;
        }
        .payment-details {
            margin: 30px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        .detail-label {
            color: #666;
            font-weight: 500;
        }
        .detail-value {
            color: #333;
            font-weight: bold;
        }
        .amount-box {
            background: linear-gradient(135deg, #3f51b5 0%, #5c6bc0 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 30px 0;
        }
        .amount-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 5px;
        }
        .amount-value {
            font-size: 32px;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
        .signature-section {
            margin-top: 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }
        .signature-box {
            text-align: center;
        }
        .signature-line {
            border-top: 2px solid #333;
            margin-top: 50px;
            padding-top: 5px;
            font-size: 12px;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .receipt-container {
                box-shadow: none;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="header">
            <div class="school-name"><?php echo htmlspecialchars($schoolName); ?></div>
            <div class="school-details">
                <?php echo htmlspecialchars($schoolAddress); ?><br>
                Phone: <?php echo htmlspecialchars($schoolPhone); ?> | Email: <?php echo htmlspecialchars($schoolEmail); ?>
            </div>
        </div>
        
        <div class="receipt-title">Payment Receipt</div>
        
        <div class="receipt-info">
            <div class="info-box">
                <div class="info-label">Receipt Number</div>
                <div class="info-value"><?php echo htmlspecialchars($payment['receipt_number']); ?></div>
            </div>
            <div class="info-box">
                <div class="info-label">Transaction ID</div>
                <div class="info-value"><?php echo htmlspecialchars($payment['transaction_id']); ?></div>
            </div>
            <div class="info-box">
                <div class="info-label">Payment Date</div>
                <div class="info-value"><?php echo date('F d, Y', strtotime($payment['payment_date'])); ?></div>
            </div>
            <div class="info-box">
                <div class="info-label">Payment Time</div>
                <div class="info-value"><?php echo date('h:i A', strtotime($payment['created_at'])); ?></div>
            </div>
        </div>
        
        <div class="payment-details">
            <h3 style="margin-bottom: 15px; color: #333;">Student Information</h3>
            <div class="detail-row">
                <span class="detail-label">Student Name:</span>
                <span class="detail-value"><?php echo htmlspecialchars($payment['student_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Roll Number:</span>
                <span class="detail-value"><?php echo htmlspecialchars($payment['roll_no']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Class:</span>
                <span class="detail-value"><?php echo htmlspecialchars($payment['class_name'] ?? 'Not Assigned'); ?></span>
            </div>
        </div>
        
        <div class="payment-details">
            <h3 style="margin-bottom: 15px; color: #333;">Payment Details</h3>
            <div class="detail-row">
                <span class="detail-label">Fee Group:</span>
                <span class="detail-value"><?php echo htmlspecialchars($payment['fee_group_name']); ?></span>
            </div>
            <?php if ($payment['installment_name']): ?>
            <div class="detail-row">
                <span class="detail-label">Installment:</span>
                <span class="detail-value"><?php echo htmlspecialchars($payment['installment_name']); ?></span>
            </div>
            <?php elseif ($payment['extra_fee_description']): ?>
            <div class="detail-row">
                <span class="detail-label">Description:</span>
                <span class="detail-value"><?php echo htmlspecialchars($payment['extra_fee_description']); ?></span>
            </div>
            <?php endif; ?>
            <div class="detail-row">
                <span class="detail-label">Payment Mode:</span>
                <span class="detail-value"><?php echo ucwords(str_replace('_', ' ', $payment['payment_mode'])); ?></span>
            </div>
            <?php if ($payment['payment_mode'] === 'upi' && isset($details['upi_id'])): ?>
            <div class="detail-row">
                <span class="detail-label">UPI ID:</span>
                <span class="detail-value"><?php echo htmlspecialchars($details['upi_id']); ?></span>
            </div>
            <?php elseif (in_array($payment['payment_mode'], ['debit_card', 'credit_card']) && isset($details['card_last4'])): ?>
            <div class="detail-row">
                <span class="detail-label">Card Last 4 Digits:</span>
                <span class="detail-value">****<?php echo htmlspecialchars($details['card_last4']); ?></span>
            </div>
            <?php elseif ($payment['payment_mode'] === 'net_banking' && isset($details['bank_name'])): ?>
            <div class="detail-row">
                <span class="detail-label">Bank:</span>
                <span class="detail-value"><?php echo htmlspecialchars($details['bank_name']); ?></span>
            </div>
            <?php endif; ?>
            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="detail-value" style="color: <?php echo $payment['status'] === 'Paid' ? '#2ecc71' : '#f39c12'; ?>;">
                    <?php echo ucfirst($payment['status']); ?>
                </span>
            </div>
        </div>
        
        <div class="amount-box">
            <div class="amount-label">Amount Paid</div>
            <div class="amount-value">₹<?php echo number_format($payment['amount_paid'], 2); ?></div>
        </div>
        
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line">Student Signature</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">Authorized Signatory</div>
            </div>
        </div>
        
        <div class="footer">
            <p>This is a computer-generated receipt. No signature required.</p>
            <p style="margin-top: 10px;">For any queries, please contact: <?php echo htmlspecialchars($schoolEmail); ?></p>
            <p style="margin-top: 5px;">Generated on: <?php echo date('F d, Y h:i A'); ?></p>
        </div>
    </div>
    
    <script>
        window.onload = function() {
            // Auto-print option (optional)
            // window.print();
        }
    </script>
</body>
</html>

