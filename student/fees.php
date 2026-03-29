<?php
/**
 * Student - View Fees and Make Payments
 * EduTrackr - School Management System
 */
require_once '../includes/functions.php';
requireRole('student');

$currentPage = 'fees';
$pageTitle = "My Fees";

$success = '';
$error = '';

// Get student data
$student = getStudentData($_SESSION['user_id']);
if (!$student || !isset($student['student_id'])) {
    header('Location: ../error.php?msg=Student record not found. Please contact administrator.');
    exit();
}

$studentId = $student['student_id'];
$classId = isset($student['class_id']) ? $student['class_id'] : null;

global $conn;

// Handle payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['make_payment'])) {
    $studentFeeId = intval($_POST['student_fee_id']);
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
        $error = 'Payment amount must be greater than zero.';
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
            $error = 'Invalid fee record.';
        } elseif ($amountPaid > ($studentFee['amount'] - ($studentFee['paid_amount'] ?? 0))) {
            $error = 'Payment amount exceeds remaining balance.';
        } else {
            // Generate transaction ID and receipt number
            $transactionId = generateTransactionId($paymentMode, $details);
            $receiptNumber = generateReceiptNumber($studentId, $paymentDate);
            
            // Determine payment status
            $currentPaid = $studentFee['paid_amount'] ?? 0;
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
                
                $success = 'Payment recorded successfully! Receipt Number: ' . $receiptNumber;
            } else {
                $error = 'Error recording payment: ' . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Get student fees
$studentFees = [];
$paymentHistory = [];

if ($classId) {
    // Ensure fees are assigned (auto-assign if not)
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM student_fees WHERE student_id = ?");
    $checkStmt->bind_param("i", $studentId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $count = $result->fetch_assoc()['count'];
    $checkStmt->close();
    
    // Auto-assign fees if not assigned
    if ($count == 0) {
        assignInstallmentsToStudent($studentId, $classId);
    }
    
    // Get student fees
    $studentFees = getStudentFees($studentId);
    
    // Get payment history
    $paymentHistory = getStudentPaymentHistory($studentId);
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">My Fees</h1>
        <p class="text-gray-600">View your fee details and make payments</p>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-50 border-2 border-red-300 px-4 py-3 rounded-lg mb-6" style="border-color: #e74c3c;">
            <p class="font-medium" style="color: #e74c3c;"><?php echo $error; ?></p>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-50 border-2 border-green-300 px-4 py-3 rounded-lg mb-6" style="border-color: #2ecc71;">
            <p class="font-medium" style="color: #2ecc71;"><?php echo $success; ?></p>
        </div>
    <?php endif; ?>

    <?php if (!$classId): ?>
        <div class="card p-8 text-center">
            <div class="text-6xl mb-4">💰</div>
            <h2 class="text-2xl font-semibold text-gray-800 mb-2">No Class Assigned</h2>
            <p class="text-gray-600 mb-6">You need to choose a class first to view fees.</p>
            <a href="classes.php" class="btn-primary text-white px-6 py-3 rounded-lg font-medium inline-block">
                Choose a Class
            </a>
        </div>
    <?php elseif (count($studentFees) == 0): ?>
        <div class="card p-8 text-center">
            <div class="text-6xl mb-4">✅</div>
            <h2 class="text-2xl font-semibold text-gray-800 mb-2">No Fees Due</h2>
            <p class="text-gray-600">There are no fees assigned to you at the moment.</p>
        </div>
    <?php else: ?>
        <?php
        $totalDue = 0;
        $totalPaid = 0;
        foreach ($studentFees as $fee) {
            $totalDue += $fee['amount'];
            $totalPaid += ($fee['paid_amount'] ?? $fee['paid_amount'] ?? 0);
        }
        $totalPending = $totalDue - $totalPaid;
        $paymentPercentage = $totalDue > 0 ? ($totalPaid / $totalDue) * 100 : 0;
        ?>
        
        <!-- Fees Overview -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="card stat-card p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-gray-500 text-sm mb-2 font-medium uppercase tracking-wide">Total Due</p>
                        <p class="text-3xl font-bold text-red-600">₹<?php echo number_format($totalDue, 2); ?></p>
                    </div>
                    <div class="p-4 bg-gradient-to-br from-red-100 to-red-50 rounded-xl shadow-sm">
                        <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="card stat-card p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-gray-500 text-sm mb-2 font-medium uppercase tracking-wide">Total Paid</p>
                        <p class="text-3xl font-bold text-green-600">₹<?php echo number_format($totalPaid, 2); ?></p>
                        <div class="mt-3">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo min($paymentPercentage, 100); ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 bg-gradient-to-br from-green-100 to-green-50 rounded-xl shadow-sm">
                        <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="card stat-card p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-gray-500 text-sm mb-2 font-medium uppercase tracking-wide">Pending</p>
                        <p class="text-3xl font-bold text-orange-600">₹<?php echo number_format($totalPending, 2); ?></p>
                        <p class="text-xs text-gray-400 mt-1">Outstanding</p>
                    </div>
                    <div class="p-4 bg-gradient-to-br from-orange-100 to-orange-50 rounded-xl shadow-sm">
                        <svg class="w-10 h-10 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fees List -->
        <div class="card p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Fee Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($studentFees as $fee): 
                    $paid = $fee['paid_amount'] ?? 0;
                    $remaining = $fee['amount'] - $paid;
                    $isPaid = $paid >= $fee['amount'];
                    $isPartial = $paid > 0 && $paid < $fee['amount'];
                    $paidPercentage = $fee['amount'] > 0 ? ($paid / $fee['amount']) * 100 : 0;
                    $dueDate = strtotime($fee['due_date']);
                    $isOverdue = $dueDate < time() && !$isPaid;
                ?>
                    <div class="card p-6 border-2 <?php echo $isOverdue ? 'border-red-300' : ($isPaid ? 'border-green-300' : 'border-gray-200'); ?>">
                        <div class="flex items-start justify-between mb-4">
                            <div class="flex-1">
                                <h3 class="text-lg font-bold text-gray-800 mb-1">
                                    <?php echo htmlspecialchars($fee['fee_group_name']); ?>
                                </h3>
                                <?php if ($fee['installment_id']): ?>
                                    <span class="badge badge-info text-xs"><?php echo htmlspecialchars($fee['installment_name']); ?></span>
                                <?php elseif ($fee['extra_fee_id']): ?>
                                    <span class="badge badge-warning text-xs">Extra Fee</span>
                                <?php endif; ?>
                            </div>
                            <span class="badge <?php 
                                echo $isPaid ? 'badge-success' : 
                                    ($isPartial ? 'badge-warning' : 'badge-error'); 
                            ?>">
                                <?php echo $isPaid ? 'Paid' : ($isPartial ? 'Partial' : 'Pending'); ?>
                            </span>
                        </div>
                        
                        <div class="space-y-3 mb-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Total Amount:</span>
                                <span class="font-bold text-gray-800">₹<?php echo number_format($fee['amount'], 2); ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Paid:</span>
                                <span class="font-semibold text-green-600">₹<?php echo number_format($paid, 2); ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Remaining:</span>
                                <span class="font-semibold text-orange-600">₹<?php echo number_format($remaining, 2); ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Due Date:</span>
                                <span class="text-sm <?php echo $isOverdue ? 'text-red-600 font-semibold' : 'text-gray-600'; ?>">
                                    <?php echo date('M d, Y', $dueDate); ?>
                                    <?php if ($isOverdue): ?>
                                        <span class="badge badge-error ml-2 text-xs">Overdue</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo min($paidPercentage, 100); ?>%"></div>
                            </div>
                        </div>
                        
                        <?php if (!$isPaid): ?>
                            <button onclick="openPaymentModal(<?php echo $fee['student_fee_id']; ?>, '<?php echo htmlspecialchars($fee['fee_group_name'], ENT_QUOTES); ?>', <?php echo $fee['amount']; ?>, <?php echo $remaining; ?>, '<?php echo htmlspecialchars($fee['installment_name'] ?? 'Extra Fee', ENT_QUOTES); ?>')" 
                                    class="btn-primary text-white w-full px-6 py-3 rounded-lg font-medium text-center">
                                💳 Pay Now
                            </button>
                        <?php else: ?>
                            <div class="text-center py-2">
                                <span class="text-green-600 font-semibold">✅ Payment Complete</span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Payment History -->
        <?php if (count($paymentHistory) > 0): ?>
            <div class="card p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Payment History</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Fee Group</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Type</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Amount Paid</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Payment Mode</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Transaction ID</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Receipt</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Date</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paymentHistory as $payment): ?>
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3 px-4"><?php echo htmlspecialchars($payment['fee_group_name']); ?></td>
                                    <td class="py-3 px-4">
                                        <?php if ($payment['installment_name']): ?>
                                            <span class="badge badge-info"><?php echo htmlspecialchars($payment['installment_name']); ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Extra Fee</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 font-semibold">₹<?php echo number_format($payment['amount_paid'], 2); ?></td>
                                    <td class="py-3 px-4 text-sm capitalize">
                                        <?php echo str_replace('_', ' ', htmlspecialchars($payment['payment_mode'] ?? 'cash')); ?>
                                    </td>
                                    <td class="py-3 px-4 text-sm font-mono text-gray-600">
                                        <?php echo htmlspecialchars($payment['transaction_id'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="py-3 px-4">
                                        <?php if ($payment['receipt_number']): ?>
                                            <a href="receipt.php?payment_id=<?php echo $payment['payment_id']; ?>" 
                                               target="_blank"
                                               class="px-3 py-1.5 text-sm rounded-lg font-medium bg-blue-100 text-blue-800 hover:bg-blue-200 transition-all">
                                                🧾 Download
                                            </a>
                                        <?php else: ?>
                                            <span class="text-gray-400">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3 px-4 text-sm"><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                    <td class="py-3 px-4">
                                        <span class="px-3 py-1 rounded-full text-sm font-medium
                                            <?php 
                                            echo $payment['status'] === 'Paid' ? 'bg-green-100 text-green-800' : 
                                                 ($payment['status'] === 'Partial' ? 'bg-yellow-100 text-yellow-800' : 
                                                 'bg-gray-100 text-gray-800');
                                            ?>">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 shadow-2xl">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-semibold text-gray-800">Make Payment</h3>
            <button onclick="closePaymentModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form method="POST" action="" id="paymentForm">
            <input type="hidden" name="student_fee_id" id="modal_student_fee_id">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fee Group</label>
                    <input type="text" id="modal_fee_group" readonly class="input-field w-full px-4 py-3 bg-gray-50">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Installment/Type</label>
                    <input type="text" id="modal_installment" readonly class="input-field w-full px-4 py-3 bg-gray-50">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Amount to Pay (₹) *</label>
                    <input type="number" step="0.01" min="0" name="amount_paid" id="modal_amount" required 
                           class="input-field w-full px-4 py-3">
                    <p class="text-xs text-gray-500 mt-1">Remaining: ₹<span id="modal_remaining">0.00</span></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Payment Date *</label>
                    <input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required 
                           class="input-field w-full px-4 py-3">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Payment Mode *</label>
                    <select name="payment_mode" id="payment_mode" required class="input-field w-full px-4 py-3" onchange="togglePaymentDetails()">
                        <option value="cash">Cash</option>
                        <option value="upi">UPI</option>
                        <option value="debit_card">Debit Card</option>
                        <option value="credit_card">Credit Card</option>
                        <option value="net_banking">Net Banking</option>
                    </select>
                </div>
                <div id="upi_details" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">UPI ID</label>
                    <input type="text" name="upi_id" class="input-field w-full px-4 py-3" 
                           placeholder="yourname@upi">
                </div>
                <div id="card_details" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Card Last 4 Digits</label>
                    <input type="text" name="card_last4" maxlength="4" class="input-field w-full px-4 py-3" 
                           placeholder="1234">
                </div>
                <div id="net_banking_details" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Bank Code</label>
                    <input type="text" name="bank_code" class="input-field w-full px-4 py-3 mb-2" 
                           placeholder="SBI, HDFC, etc.">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Bank Name</label>
                    <input type="text" name="bank_name" class="input-field w-full px-4 py-3" 
                           placeholder="State Bank of India">
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="submit" name="make_payment" class="btn-primary text-white px-6 py-3 rounded-lg font-medium flex-1">
                    💳 Confirm Payment
                </button>
                <button type="button" onclick="closePaymentModal()" class="px-6 py-3 rounded-lg font-medium border border-gray-300 hover:bg-gray-50">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openPaymentModal(studentFeeId, feeGroup, totalAmount, remaining, installment) {
    document.getElementById('modal_student_fee_id').value = studentFeeId;
    document.getElementById('modal_fee_group').value = feeGroup;
    document.getElementById('modal_installment').value = installment;
    document.getElementById('modal_amount').value = remaining;
    document.getElementById('modal_amount').max = remaining;
    document.getElementById('modal_remaining').textContent = remaining.toFixed(2);
    document.getElementById('paymentModal').classList.remove('hidden');
    togglePaymentDetails();
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
    document.getElementById('paymentForm').reset();
}

function togglePaymentDetails() {
    const mode = document.getElementById('payment_mode').value;
    document.getElementById('upi_details').classList.add('hidden');
    document.getElementById('card_details').classList.add('hidden');
    document.getElementById('net_banking_details').classList.add('hidden');
    
    if (mode === 'upi') {
        document.getElementById('upi_details').classList.remove('hidden');
    } else if (mode === 'debit_card' || mode === 'credit_card') {
        document.getElementById('card_details').classList.remove('hidden');
    } else if (mode === 'net_banking') {
        document.getElementById('net_banking_details').classList.remove('hidden');
    }
}
</script>

<?php include '../includes/footer.php'; ?>
