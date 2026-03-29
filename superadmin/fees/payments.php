<?php
/**
 * Super Admin - Record Fee Payments
 * EduTrackr - School Management System
 */
require_once '../../includes/functions.php';
requireSuperAdmin();

$currentPage = 'fees';
$pageTitle = "Record Payments";

$success = '';
$error = '';

global $conn;

// Handle payment recording
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_payment'])) {
    $studentFeeId = intval($_POST['student_fee_id']);
    $amountPaid = floatval($_POST['amount_paid'] ?? 0);
    $paymentDate = $_POST['payment_date'] ?? date('Y-m-d');
    $paymentMode = $_POST['payment_mode'] ?? 'cash';
    $status = $_POST['status'] ?? 'Paid';
    $remarks = trim($_POST['remarks'] ?? '');
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
        $stmt = $conn->prepare("SELECT sf.*, s.student_id FROM student_fees sf
                               JOIN students s ON sf.student_id = s.student_id
                               WHERE sf.student_fee_id = ?");
        $stmt->bind_param("i", $studentFeeId);
        $stmt->execute();
        $studentFee = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$studentFee) {
            $error = 'Invalid student fee record.';
        } else {
            // Generate transaction ID and receipt number
            $transactionId = generateTransactionId($paymentMode, $details);
            $receiptNumber = generateReceiptNumber($studentFee['student_id'], $paymentDate);
            
            // Record payment
            $detailsJson = json_encode($details);
            $stmt = $conn->prepare("INSERT INTO fee_payments 
                                   (student_fee_id, transaction_id, receipt_number, payment_mode, amount_paid, paid_amount, 
                                    payment_date, status, details, remarks, created_by) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssddssssi", $studentFeeId, $transactionId, $receiptNumber, $paymentMode, 
                            $amountPaid, $amountPaid, $paymentDate, $status, $detailsJson, $remarks, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                // Update student fee status
                $currentPaid = $studentFee['paid_amount'] ?? 0;
                $newTotalPaid = $currentPaid + $amountPaid;
                
                $newStatus = 'Pending';
                if ($newTotalPaid >= $studentFee['amount']) {
                    $newStatus = 'Paid';
                } elseif ($newTotalPaid > 0) {
                    $newStatus = 'Partial';
                }
                
                $updateStmt = $conn->prepare("UPDATE student_fees 
                                            SET paid_amount = ?, 
                                                status = ?, 
                                                last_payment_date = ? 
                                            WHERE student_fee_id = ?");
                $updateStmt->bind_param("dssi", $newTotalPaid, $newStatus, $paymentDate, $studentFeeId);
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

// Get filter parameters
$selectedStudentId = isset($_GET['student_id']) ? intval($_GET['student_id']) : null;
$selectedClassId = isset($_GET['class_id']) ? intval($_GET['class_id']) : null;

// Get students
$students = [];
if ($selectedClassId) {
    $students = getStudentsByClass($selectedClassId);
} else {
    $result = $conn->query("SELECT s.*, u.name, u.email, c.name as class_name
                           FROM students s
                           JOIN users u ON s.user_id = u.id
                           LEFT JOIN classes c ON s.class_id = c.id
                           ORDER BY s.roll_no");
    if ($result) {
        $students = $result->fetch_all(MYSQLI_ASSOC);
    }
}

// Get student fees if student selected
$studentFees = [];
$studentInfo = null;
if ($selectedStudentId) {
    $studentFees = getStudentFees($selectedStudentId);
    
    // Get student info
    $stmt = $conn->prepare("SELECT s.*, u.name, u.email, c.name as class_name
                           FROM students s
                           JOIN users u ON s.user_id = u.id
                           LEFT JOIN classes c ON s.class_id = c.id
                           WHERE s.student_id = ?");
    $stmt->bind_param("i", $selectedStudentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $studentInfo = $result->fetch_assoc();
    $stmt->close();
}

// Get classes for filter
$classes = getAllClasses();

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Record Fee Payments</h1>
                <p class="text-gray-600">Mark fees as paid or partially paid</p>
            </div>
            <a href="index.php" class="btn-primary text-white px-6 py-3 rounded-lg font-medium">
                ← Back to Fees
            </a>
        </div>
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

    <!-- Filter Section -->
    <div class="card p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Select Student</h2>
        <form method="GET" action="" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Class</label>
                    <select name="class_id" class="input-field w-full px-4 py-3" onchange="this.form.submit()">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>" <?php echo $selectedClassId == $class['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Student</label>
                    <select name="student_id" class="input-field w-full px-4 py-3" onchange="this.form.submit()">
                        <option value="">Choose a student</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['student_id']; ?>" <?php echo $selectedStudentId == $student['student_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($student['roll_no'] . ' - ' . $student['name'] . ' (' . ($student['class_name'] ?? 'No Class') . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <a href="payments.php" class="px-4 py-3 rounded-lg font-medium border border-gray-300 hover:bg-gray-50">
                        Clear Filter
                    </a>
                </div>
            </div>
        </form>
    </div>

    <!-- Student Info -->
    <?php if ($studentInfo): ?>
        <div class="card p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Student: <?php echo htmlspecialchars($studentInfo['name']); ?></h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Roll No</p>
                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($studentInfo['roll_no']); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Class</p>
                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($studentInfo['class_name'] ?? 'Not Assigned'); ?></p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Email</p>
                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($studentInfo['email']); ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Payment Form -->
    <?php if ($selectedStudentId && count($studentFees) > 0): ?>
        <div class="card p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Record Payment</h2>
            <form method="POST" action="" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Fee *</label>
                        <select name="student_fee_id" id="fee_select" required class="input-field w-full px-4 py-3" onchange="updateFeeDetails()">
                            <option value="">Choose a fee</option>
                            <?php foreach ($studentFees as $fee): 
                                $paid = $fee['paid_amount'] ?? 0;
                                $remaining = $fee['amount'] - $paid;
                            ?>
                                <option value="<?php echo $fee['student_fee_id']; ?>" 
                                        data-amount="<?php echo $fee['amount']; ?>"
                                        data-paid="<?php echo $paid; ?>"
                                        data-remaining="<?php echo $remaining; ?>">
                                    <?php echo htmlspecialchars($fee['fee_group_name']); ?> - 
                                    <?php echo $fee['installment_id'] ? htmlspecialchars($fee['installment_name']) : 'Extra Fee'; ?> 
                                    (₹<?php echo number_format($fee['amount'], 2); ?>, 
                                    Remaining: ₹<?php echo number_format($remaining, 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Payment Date *</label>
                        <input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required 
                               class="input-field w-full px-4 py-3">
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Amount Paid (₹) *</label>
                        <input type="number" step="0.01" min="0" name="amount_paid" id="amount_paid" required 
                               class="input-field w-full px-4 py-3" placeholder="0.00">
                        <p class="text-xs text-gray-500 mt-1">Remaining: ₹<span id="remaining_amount">0.00</span></p>
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
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Bank Code</label>
                            <input type="text" name="bank_code" class="input-field w-full px-4 py-3" 
                                   placeholder="SBI, HDFC, etc.">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Bank Name</label>
                            <input type="text" name="bank_name" class="input-field w-full px-4 py-3" 
                                   placeholder="Bank Name">
                        </div>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Payment Status *</label>
                    <select name="status" required class="input-field w-full px-4 py-3">
                        <option value="Paid">Paid</option>
                        <option value="Partial">Partial</option>
                        <option value="Pending">Pending</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
                    <textarea name="remarks" rows="2" class="input-field w-full px-4 py-3" 
                              placeholder="Optional remarks"></textarea>
                </div>
                
                <button type="submit" name="record_payment" class="btn-primary text-white px-6 py-3 rounded-lg font-medium">
                    Record Payment
                </button>
            </form>
        </div>
        
        <!-- Recent Payments -->
        <div class="card p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Recent Payments</h2>
            <?php
            $payments = getStudentPaymentHistory($selectedStudentId);
            if (count($payments) > 0):
            ?>
                <div class="overflow-x-auto">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Fee Group</th>
                                <th>Type</th>
                                <th>Amount Paid</th>
                                <th>Payment Mode</th>
                                <th>Transaction ID</th>
                                <th>Receipt No</th>
                                <th>Payment Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($payment['fee_group_name']); ?></td>
                                    <td>
                                        <?php if ($payment['installment_name']): ?>
                                            <span class="badge badge-info"><?php echo htmlspecialchars($payment['installment_name']); ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Extra Fee</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="font-bold text-green-600">₹<?php echo number_format($payment['amount_paid'], 2); ?></td>
                                    <td class="text-sm capitalize">
                                        <?php echo str_replace('_', ' ', htmlspecialchars($payment['payment_mode'] ?? 'cash')); ?>
                                    </td>
                                    <td class="text-sm font-mono text-gray-600">
                                        <?php echo htmlspecialchars($payment['transaction_id'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="text-sm font-mono text-gray-600">
                                        <?php echo htmlspecialchars($payment['receipt_number'] ?? 'N/A'); ?>
                                    </td>
                                    <td class="text-sm"><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                    <td>
                                        <span class="badge <?php 
                                            echo $payment['status'] === 'Paid' ? 'badge-success' : 
                                                ($payment['status'] === 'Partial' ? 'badge-warning' : 'badge-error'); 
                                        ?>">
                                            <?php echo $payment['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($payment['receipt_number']): ?>
                                            <a href="../student/receipt.php?payment_id=<?php echo $payment['payment_id']; ?>" 
                                               target="_blank"
                                               class="px-3 py-1.5 text-sm rounded-lg font-medium bg-blue-100 text-blue-800 hover:bg-blue-200 transition-all">
                                                🧾 Receipt
                                            </a>
                                        <?php else: ?>
                                            <span class="text-gray-400 text-sm">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-600 text-center py-8">No payments recorded yet.</p>
            <?php endif; ?>
        </div>
    <?php elseif ($selectedStudentId): ?>
        <div class="card p-8 text-center">
            <div class="text-6xl mb-4">💰</div>
            <h2 class="text-2xl font-semibold text-gray-800 mb-2">No Fees Found</h2>
            <p class="text-gray-600">This student has no fees assigned yet.</p>
        </div>
    <?php else: ?>
        <div class="card p-8 text-center">
            <div class="text-6xl mb-4">👤</div>
            <h2 class="text-2xl font-semibold text-gray-800 mb-2">Select a Student</h2>
            <p class="text-gray-600">Choose a student from the filter above to record payments.</p>
        </div>
    <?php endif; ?>
</div>

<script>
function updateFeeDetails() {
    const select = document.getElementById('fee_select');
    const option = select.options[select.selectedIndex];
    if (option.value) {
        const remaining = parseFloat(option.getAttribute('data-remaining'));
        document.getElementById('remaining_amount').textContent = remaining.toFixed(2);
        document.getElementById('amount_paid').max = remaining;
    } else {
        document.getElementById('remaining_amount').textContent = '0.00';
    }
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

<?php include '../../includes/footer.php'; ?>

