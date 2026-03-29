<?php
/**
 * Super Admin - Student Fee Ledger
 * EduTrackr - School Management System
 */
require_once '../../includes/functions.php';
requireSuperAdmin();

$currentPage = 'fees';
$pageTitle = "Student Fee Ledger";

$success = '';
$error = '';

global $conn;

// Get selected student
$selectedStudentId = isset($_GET['student_id']) ? intval($_GET['student_id']) : null;
$selectedClassId = isset($_GET['class_id']) ? intval($_GET['class_id']) : null;

// Handle manual fee assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_fees'])) {
    $studentId = intval($_POST['student_id']);
    $classId = intval($_POST['class_id']);
    
    if ($studentId > 0 && $classId > 0) {
        if (assignInstallmentsToStudent($studentId, $classId)) {
            $success = 'Fees assigned to student successfully!';
        } else {
            $error = 'Error assigning fees.';
        }
    } else {
        $error = 'Invalid student or class selected.';
    }
}

// Get all students
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
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Student Fee Ledger</h1>
                <p class="text-gray-600">View and manage fees for each student</p>
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

    <!-- Filter and Assign Section -->
    <div class="card p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Filter Students</h2>
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
                    <a href="student_ledger.php" class="px-4 py-3 rounded-lg font-medium border border-gray-300 hover:bg-gray-50">
                        Clear Filter
                    </a>
                </div>
            </div>
        </form>
        
        <!-- Manual Assignment -->
        <?php if ($selectedStudentId && $studentInfo && $studentInfo['class_id']): ?>
            <div class="mt-6 pt-6 border-t border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Manually Assign Fees</h3>
                <form method="POST" action="" onsubmit="return confirm('This will assign all class installments and extra fees to this student. Continue?');">
                    <input type="hidden" name="student_id" value="<?php echo $selectedStudentId; ?>">
                    <input type="hidden" name="class_id" value="<?php echo $studentInfo['class_id']; ?>">
                    <button type="submit" name="assign_fees" class="btn-primary text-white px-6 py-3 rounded-lg font-medium">
                        Assign All Class Fees to Student
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <!-- Student Info -->
    <?php if ($studentInfo): ?>
        <div class="card p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Student Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Name</p>
                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($studentInfo['name']); ?></p>
                </div>
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

    <!-- Student Fees -->
    <?php if ($selectedStudentId && count($studentFees) > 0): ?>
        <?php
        $totalDue = 0;
        $totalPaid = 0;
        foreach ($studentFees as $fee) {
            $totalDue += $fee['amount'];
            $totalPaid += ($fee['paid_amount'] ?? 0);
        }
        $totalPending = $totalDue - $totalPaid;
        ?>
        
        <!-- Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="card stat-card p-6">
                <p class="text-gray-500 text-sm mb-2 font-medium uppercase tracking-wide">Total Due</p>
                <p class="text-3xl font-bold text-red-600">₹<?php echo number_format($totalDue, 2); ?></p>
            </div>
            <div class="card stat-card p-6">
                <p class="text-gray-500 text-sm mb-2 font-medium uppercase tracking-wide">Total Paid</p>
                <p class="text-3xl font-bold text-green-600">₹<?php echo number_format($totalPaid, 2); ?></p>
            </div>
            <div class="card stat-card p-6">
                <p class="text-gray-500 text-sm mb-2 font-medium uppercase tracking-wide">Pending</p>
                <p class="text-3xl font-bold text-orange-600">₹<?php echo number_format($totalPending, 2); ?></p>
            </div>
        </div>
        
        <!-- Fees List -->
        <div class="card p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Fee Details</h2>
            <div class="overflow-x-auto">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Fee Group</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Due Date</th>
                            <th>Paid</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($studentFees as $fee): 
                            $paid = $fee['paid_amount'] ?? 0;
                            $remaining = $fee['amount'] - $paid;
                            $isPaid = $paid >= $fee['amount'];
                            $isPartial = $paid > 0 && $paid < $fee['amount'];
                        ?>
                            <tr>
                                <td>
                                    <div class="font-semibold text-gray-800"><?php echo htmlspecialchars($fee['fee_group_name']); ?></div>
                                </td>
                                <td>
                                    <?php if ($fee['installment_id']): ?>
                                        <span class="badge badge-info"><?php echo htmlspecialchars($fee['installment_name']); ?></span>
                                    <?php elseif ($fee['extra_fee_id']): ?>
                                        <span class="badge badge-warning">Extra Fee</span>
                                        <?php if ($fee['extra_fee_description']): ?>
                                            <div class="text-xs text-gray-500 mt-1"><?php echo htmlspecialchars($fee['extra_fee_description']); ?></div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="font-bold text-gray-800">₹<?php echo number_format($fee['amount'], 2); ?></td>
                                <td class="text-sm">
                                    <?php 
                                    $dueDate = strtotime($fee['due_date']);
                                    $isOverdue = $dueDate < time() && !$isPaid;
                                    ?>
                                    <span class="<?php echo $isOverdue ? 'text-red-600 font-semibold' : 'text-gray-600'; ?>">
                                        <?php echo date('M d, Y', $dueDate); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="font-semibold text-green-600">₹<?php echo number_format($paid, 2); ?></div>
                                    <div class="text-xs text-gray-500">Remaining: ₹<?php echo number_format($remaining, 2); ?></div>
                                </td>
                                <td>
                                    <span class="badge <?php 
                                        echo $isPaid ? 'badge-success' : 
                                            ($isPartial ? 'badge-warning' : 'badge-error'); 
                                    ?>">
                                        <?php echo $isPaid ? 'Paid' : ($isPartial ? 'Partial' : 'Pending'); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php elseif ($selectedStudentId): ?>
        <div class="card p-8 text-center">
            <div class="text-6xl mb-4">💰</div>
            <h2 class="text-2xl font-semibold text-gray-800 mb-2">No Fees Assigned</h2>
            <p class="text-gray-600">This student has no fees assigned yet.</p>
        </div>
    <?php else: ?>
        <div class="card p-8 text-center">
            <div class="text-6xl mb-4">👤</div>
            <h2 class="text-2xl font-semibold text-gray-800 mb-2">Select a Student</h2>
            <p class="text-gray-600">Choose a student from the filter above to view their fee ledger.</p>
        </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>

