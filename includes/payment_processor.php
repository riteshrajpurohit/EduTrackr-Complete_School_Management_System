<?php
/**
 * Payment Processor
 * Handles fee payments with auto-generated transaction IDs and receipt numbers
 * EduTrackr - School Management System
 */

require_once __DIR__ . '/db.php';

/**
 * Generate transaction ID based on payment mode
 */
function generateTransactionId($paymentMode) {
    $prefix = '';
    $suffix = '';
    
    switch ($paymentMode) {
        case 'cash':
            $prefix = 'CASH';
            $suffix = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
            break;
            
        case 'upi':
            $prefix = 'UPI';
            $suffix = time(); // Timestamp
            break;
            
        case 'debit_card':
        case 'credit_card':
            $prefix = 'CARD';
            // Generate random 4 digits (simulating last 4 of card)
            $cardLast4 = isset($_POST['card_last4']) ? $_POST['card_last4'] : str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            $suffix = $cardLast4 . '-' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
            break;
            
        case 'net_banking':
            $prefix = 'NET';
            $bankCode = isset($_POST['bank_code']) ? strtoupper(substr($_POST['bank_code'], 0, 3)) : 'BNK';
            $suffix = $bankCode . '-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            break;
            
        default:
            $prefix = 'PAY';
            $suffix = time();
    }
    
    return $prefix . '-' . $suffix;
}

/**
 * Generate receipt number
 * Format: RCT-{YEAR}{MONTH}{DAY}-{STUDENT_ID}-{SEQUENCE}
 */
function generateReceiptNumber($studentId) {
    global $conn;
    
    $datePrefix = date('Ymd');
    $sequence = 1;
    
    // Get or create sequence for today
    $stmt = $conn->prepare("SELECT sequence_number FROM receipt_sequence WHERE date_prefix = ? FOR UPDATE");
    $stmt->bind_param("s", $datePrefix);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $sequence = $row['sequence_number'] + 1;
        
        // Update sequence
        $updateStmt = $conn->prepare("UPDATE receipt_sequence SET sequence_number = ? WHERE date_prefix = ?");
        $updateStmt->bind_param("is", $sequence, $datePrefix);
        $updateStmt->execute();
        $updateStmt->close();
    } else {
        // Create new sequence for today
        $insertStmt = $conn->prepare("INSERT INTO receipt_sequence (date_prefix, sequence_number) VALUES (?, ?)");
        $insertStmt->bind_param("si", $datePrefix, $sequence);
        $insertStmt->execute();
        $insertStmt->close();
    }
    $stmt->close();
    
    // Format: RCT-20251117-102-0001
    return 'RCT-' . $datePrefix . '-' . str_pad($studentId, 3, '0', STR_PAD_LEFT) . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
}

/**
 * Process payment
 */
function processPayment($studentFeeId, $amountPaid, $paymentMode, $paymentDate, $details = []) {
    global $conn;
    
    // Get student fee details
    $stmt = $conn->prepare("SELECT sf.*, s.student_id 
                           FROM student_fees sf
                           JOIN students s ON sf.student_id = s.student_id
                           WHERE sf.student_fee_id = ?");
    $stmt->bind_param("i", $studentFeeId);
    $stmt->execute();
    $result = $stmt->get_result();
    $studentFee = $result->fetch_assoc();
    $stmt->close();
    
    if (!$studentFee) {
        return ['success' => false, 'error' => 'Invalid student fee record'];
    }
    
    // Generate transaction ID and receipt number
    $transactionId = generateTransactionId($paymentMode);
    $receiptNumber = generateReceiptNumber($studentFee['student_id']);
    
    // Determine payment status
    $currentPaid = $studentFee['paid_amount'] ?? 0;
    $newTotalPaid = $currentPaid + $amountPaid;
    $totalAmount = $studentFee['amount'];
    
    $paymentStatus = 'Partial';
    if ($newTotalPaid >= $totalAmount) {
        $paymentStatus = 'Paid';
    } elseif ($amountPaid == 0) {
        $paymentStatus = 'Pending';
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert payment record
        $detailsJson = !empty($details) ? json_encode($details) : null;
        $stmt = $conn->prepare("INSERT INTO fee_payments 
                               (student_fee_id, transaction_id, receipt_number, payment_mode, amount_paid, 
                                payment_date, status, details, created_by) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssdsssi", $studentFeeId, $transactionId, $receiptNumber, $paymentMode, 
                         $amountPaid, $paymentDate, $paymentStatus, $detailsJson, $_SESSION['user_id']);
        $stmt->execute();
        $paymentId = $conn->insert_id;
        $stmt->close();
        
        // Update student fee status
        $updateStmt = $conn->prepare("UPDATE student_fees 
                                      SET paid_amount = ?, 
                                          status = ?, 
                                          last_payment_date = ? 
                                      WHERE student_fee_id = ?");
        $updateStmt->bind_param("dssi", $newTotalPaid, $paymentStatus, $paymentDate, $studentFeeId);
        $updateStmt->execute();
        $updateStmt->close();
        
        $conn->commit();
        
        return [
            'success' => true,
            'payment_id' => $paymentId,
            'transaction_id' => $transactionId,
            'receipt_number' => $receiptNumber,
            'status' => $paymentStatus
        ];
        
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'error' => 'Payment processing failed: ' . $e->getMessage()];
    }
}

/**
 * Get payment details by payment ID
 */
function getPaymentDetails($paymentId) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT fp.*, 
                           sf.amount as fee_amount,
                           sf.due_date,
                           s.student_id,
                           s.roll_no,
                           u.name as student_name,
                           u.email as student_email,
                           c.name as class_name,
                           fg.name as fee_group_name,
                           fi.name as installment_name,
                           ef.description as extra_fee_description
                           FROM fee_payments fp
                           JOIN student_fees sf ON fp.student_fee_id = sf.student_fee_id
                           JOIN students s ON sf.student_id = s.student_id
                           JOIN users u ON s.user_id = u.id
                           LEFT JOIN classes c ON s.class_id = c.id
                           JOIN fee_groups fg ON sf.fee_group_id = fg.fee_group_id
                           LEFT JOIN fee_installments fi ON sf.installment_id = fi.installment_id
                           LEFT JOIN extra_fees ef ON sf.extra_fee_id = ef.extra_fee_id
                           WHERE fp.payment_id = ?");
    $stmt->bind_param("i", $paymentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $payment = $result->fetch_assoc();
    $stmt->close();
    
    if ($payment && $payment['details']) {
        $payment['details'] = json_decode($payment['details'], true);
    }
    
    return $payment;
}

