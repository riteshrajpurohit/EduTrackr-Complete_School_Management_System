<?php
/**
 * Download Receipt - Student
 * Generates PDF receipt for fee payment
 */
require_once '../includes/functions.php';
require_once '../includes/payment_processor.php';
requireRole('student');

$paymentId = isset($_GET['payment_id']) ? intval($_GET['payment_id']) : 0;

if ($paymentId <= 0) {
    header('Location: fees.php?error=Invalid payment ID');
    exit();
}

// Get payment details
$payment = getPaymentDetails($paymentId);

if (!$payment) {
    header('Location: fees.php?error=Payment not found');
    exit();
}

// Verify student owns this payment
$student = getStudentData($_SESSION['user_id']);
if ($student['student_id'] != $payment['student_id']) {
    header('Location: fees.php?error=Access denied');
    exit();
}

// Generate PDF using FPDF (fallback to simple HTML if FPDF not available)
generateReceiptPDF($payment);

/**
 * Generate PDF Receipt
 */
function generateReceiptPDF($payment) {
    // Try to use FPDF if available, otherwise use HTML
    if (class_exists('FPDF')) {
        generateReceiptFPDF($payment);
    } else {
        generateReceiptHTML($payment);
    }
}

/**
 * Generate Receipt using FPDF
 */
function generateReceiptFPDF($payment) {
    require_once('../vendor/fpdf/fpdf.php');
    
    $pdf = new FPDF('P', 'mm', 'A4');
    $pdf->AddPage();
    
    // Header
    $pdf->SetFont('Arial', 'B', 20);
    $pdf->Cell(0, 10, 'EduTrackr School', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 5, 'Fee Payment Receipt', 0, 1, 'C');
    $pdf->Ln(10);
    
    // Receipt Details
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(50, 8, 'Receipt Number:', 0, 0);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, $payment['receipt_number'], 0, 1);
    
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(50, 8, 'Transaction ID:', 0, 0);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 8, $payment['transaction_id'], 0, 1);
    
    $pdf->Ln(5);
    
    // Student Details
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 8, 'Student Details', 0, 1);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(50, 8, 'Name:', 0, 0);
    $pdf->Cell(0, 8, $payment['student_name'], 0, 1);
    $pdf->Cell(50, 8, 'Roll No:', 0, 0);
    $pdf->Cell(0, 8, $payment['roll_no'], 0, 1);
    $pdf->Cell(50, 8, 'Class:', 0, 0);
    $pdf->Cell(0, 8, $payment['class_name'] ?? 'N/A', 0, 1);
    
    $pdf->Ln(5);
    
    // Payment Details
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 8, 'Payment Details', 0, 1);
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(50, 8, 'Fee Group:', 0, 0);
    $pdf->Cell(0, 8, $payment['fee_group_name'], 0, 1);
    
    if ($payment['installment_name']) {
        $pdf->Cell(50, 8, 'Installment:', 0, 0);
        $pdf->Cell(0, 8, $payment['installment_name'], 0, 1);
    }
    
    $pdf->Cell(50, 8, 'Amount Paid:', 0, 0);
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 8, '₹' . number_format($payment['amount_paid'], 2), 0, 1);
    $pdf->SetFont('Arial', '', 12);
    
    $pdf->Cell(50, 8, 'Payment Mode:', 0, 0);
    $pdf->Cell(0, 8, ucwords(str_replace('_', ' ', $payment['payment_mode'])), 0, 1);
    
    $pdf->Cell(50, 8, 'Payment Date:', 0, 0);
    $pdf->Cell(0, 8, date('F d, Y', strtotime($payment['payment_date'])), 0, 1);
    
    $pdf->Ln(10);
    
    // Footer
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->Cell(0, 5, 'This is a computer-generated receipt.', 0, 1, 'C');
    $pdf->Cell(0, 5, 'EduTrackr School Management System', 0, 1, 'C');
    
    $pdf->Output('D', 'Receipt-' . $payment['receipt_number'] . '.pdf');
}

/**
 * Generate Receipt as HTML (fallback)
 */
function generateReceiptHTML($payment) {
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Receipt - <?php echo htmlspecialchars($payment['receipt_number']); ?></title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            .header { text-align: center; border-bottom: 3px solid #3f51b5; padding-bottom: 20px; margin-bottom: 30px; }
            .header h1 { color: #3f51b5; margin: 0; }
            .receipt-box { border: 2px solid #ddd; padding: 30px; border-radius: 10px; background: #f9f9f9; }
            .section { margin-bottom: 25px; }
            .section h2 { color: #3f51b5; border-bottom: 2px solid #3f51b5; padding-bottom: 5px; }
            .row { display: flex; margin: 10px 0; }
            .label { font-weight: bold; width: 150px; }
            .value { flex: 1; }
            .amount { font-size: 24px; color: #2ecc71; font-weight: bold; }
            .footer { text-align: center; margin-top: 40px; padding-top: 20px; border-top: 2px solid #ddd; color: #666; }
            @media print { .no-print { display: none; } }
        </style>
    </head>
    <body>
        <div class="receipt-box">
            <div class="header">
                <h1>EduTrackr School</h1>
                <p>Fee Payment Receipt</p>
            </div>
            
            <div class="section">
                <h2>Receipt Information</h2>
                <div class="row">
                    <div class="label">Receipt Number:</div>
                    <div class="value"><strong><?php echo htmlspecialchars($payment['receipt_number']); ?></strong></div>
                </div>
                <div class="row">
                    <div class="label">Transaction ID:</div>
                    <div class="value"><?php echo htmlspecialchars($payment['transaction_id']); ?></div>
                </div>
                <div class="row">
                    <div class="label">Payment Date:</div>
                    <div class="value"><?php echo date('F d, Y', strtotime($payment['payment_date'])); ?></div>
                </div>
            </div>
            
            <div class="section">
                <h2>Student Details</h2>
                <div class="row">
                    <div class="label">Name:</div>
                    <div class="value"><?php echo htmlspecialchars($payment['student_name']); ?></div>
                </div>
                <div class="row">
                    <div class="label">Roll No:</div>
                    <div class="value"><?php echo htmlspecialchars($payment['roll_no']); ?></div>
                </div>
                <div class="row">
                    <div class="label">Class:</div>
                    <div class="value"><?php echo htmlspecialchars($payment['class_name'] ?? 'N/A'); ?></div>
                </div>
            </div>
            
            <div class="section">
                <h2>Payment Details</h2>
                <div class="row">
                    <div class="label">Fee Group:</div>
                    <div class="value"><?php echo htmlspecialchars($payment['fee_group_name']); ?></div>
                </div>
                <?php if ($payment['installment_name']): ?>
                <div class="row">
                    <div class="label">Installment:</div>
                    <div class="value"><?php echo htmlspecialchars($payment['installment_name']); ?></div>
                </div>
                <?php endif; ?>
                <div class="row">
                    <div class="label">Payment Mode:</div>
                    <div class="value"><?php echo ucwords(str_replace('_', ' ', $payment['payment_mode'])); ?></div>
                </div>
                <div class="row">
                    <div class="label">Amount Paid:</div>
                    <div class="value amount">₹<?php echo number_format($payment['amount_paid'], 2); ?></div>
                </div>
            </div>
            
            <div class="footer">
                <p>This is a computer-generated receipt.</p>
                <p>EduTrackr School Management System</p>
                <p class="no-print"><button onclick="window.print()">Print Receipt</button></p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit();
}

