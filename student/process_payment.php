<?php
/**
 * Process Payment - AJAX Endpoint
 * EduTrackr - School Management System
 */
require_once '../includes/functions.php';
requireRole('student');

header('Content-Type: application/json');

$response = ['success' => false, 'message' => '', 'transaction_id' => '', 'receipt_number' => ''];

// Get student data
$student = getStudentData($_SESSION['user_id']);
if (!$student || !isset($student['student_id'])) {
    $response['message'] = 'Student record not found.';
    echo json_encode($response);
    exit();
}

$studentId = $student['student_id'];
global $conn;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $studentFeeId = intval($_POST['student_fee_id'] ?? 0);
    $amountPaid = floatval($_POST['amount_paid'] ?? 0);
    $paymentMode = $_POST['payment_mode'] ?? 'cash';
    $paymentDate = $_POST['payment_date'] ?? date('Y-m-d');
    $details = [];
    
    // Collect payment details based on mode
    if ($paymentMode === 'upi' && !empty($_POST['upi_id'])) {
        $details['upi_id'] = trim($_POST['upi_id']);
    } elseif (in_array($paymentMode, ['debit_card', 'credit_card']) && !empty($_POST['card_last4'])) {
        $details['card_last4'] = trim($_POST['card_last4']);
        $details['card_type'] = $paymentMode;
    } elseif ($paymentMode === 'net_banking' && !empty($_POST['bank_code'])) {
        $details['bank_code'] = trim($_POST['bank_code']);
        $details['bank_name'] = trim($_POST['bank_name'] ?? '');
    }
    
    if ($amountPaid <= 0) {
        $response['message'] = 'Payment amount must be greater than zero.';
    } elseif ($studentFeeId <= 0) {
        $response['message'] = 'Invalid fee record.';
    } else {
        // Get student fee details
        $stmt = $conn->prepare("SELECT sf.*, fg.name as fee_group_name, fi.name as installment_name
                               FROM student_fees sf
                               JOIN fee_groups fg ON sf.fee_group_id = fg.fee_group_id
                               LEFT JOIN fee_installments fi ON sf.installment_id = fi.installment_id
                               WHERE sf.student_fee_id = ? AND sf.student_id = ?");
        $stmt->bind_param("ii", $studentFeeId, $studentId);
        $stmt->execute();
        $studentFee = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$studentFee) {
            $response['message'] = 'Invalid fee record.';
        } else {
            $currentPaid = $studentFee['paid_amount'] ?? 0;
            $remaining = $studentFee['amount'] - $currentPaid;
            
            if ($amountPaid > $remaining) {
                $response['message'] = 'Payment amount exceeds remaining balance.';
            } else {
                // Generate transaction ID and receipt number
                $transactionId = generateTransactionId($paymentMode, $details);
                $receiptNumber = generateReceiptNumber($studentId, $paymentDate);
                
                // Determine payment status
                $newTotalPaid = $currentPaid + $amountPaid;
                $paymentStatus = 'Partial';
                if ($newTotalPaid >= $studentFee['amount']) {
                    $paymentStatus = 'Paid';
                }
                
                // Insert payment
                $detailsJson = json_encode($details);
                $stmt = $conn->prepare("INSERT INTO fee_payments 
                                       (student_fee_id, transaction_id, receipt_number, payment_mode, amount_paid, paid_amount, 
                                        payment_date, status, details, created_by) 
                                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("isssddsssi", $studentFeeId, $transactionId, $receiptNumber, $paymentMode, 
                                $amountPaid, $amountPaid, $paymentDate, $paymentStatus, $detailsJson, $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    // Update student fee status
                    $updateStmt = $conn->prepare("UPDATE student_fees 
                                                SET paid_amount = ?, 
                                                    status = ?, 
                                                    last_payment_date = ? 
                                                WHERE student_fee_id = ?");
                    $updateStmt->bind_param("dssi", $newTotalPaid, $paymentStatus, $paymentDate, $studentFeeId);
                    $updateStmt->execute();
                    $updateStmt->close();
                    
                    $response['success'] = true;
                    $response['message'] = 'Payment recorded successfully!';
                    $response['transaction_id'] = $transactionId;
                    $response['receipt_number'] = $receiptNumber;
                } else {
                    $response['message'] = 'Error recording payment: ' . $conn->error;
                }
                $stmt->close();
            }
        }
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
