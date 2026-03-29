<?php
/**
 * Teacher - View Student Fees (Read-Only)
 * EduTrackr - School Management System
 */
require_once '../includes/functions.php';
requireRole('teacher');

$currentPage = 'fees';
$pageTitle = "View Student Fees";

global $conn;

// Get teacher data
$teacher = getTeacherData($_SESSION['user_id']);
$teacherId = $teacher['teacher_id'] ?? null;

if (!$teacherId) {
    header('Location: ../error.php?msg=Teacher record not found.');
    exit();
}

// Get filter parameters
$selectedClassId = isset($_GET['class_id']) ? intval($_GET['class_id']) : null;
$selectedStudentId = isset($_GET['student_id']) ? intval($_GET['student_id']) : null;

// Get classes assigned to teacher
$teacherClasses = getTeacherClasses($teacherId);

// Get students
$students = [];
if ($selectedClassId) {
    $students = getStudentsByClass($selectedClassId);
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

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">View Student Fees</h1>
        <p class="text-gray-600">View fee status for students in your classes (Read-Only)</p>
    </div>

    <!-- Filter Section -->
    <div class="card p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Filter Students</h2>
        <form method="GET" action="" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Class</label>
                    <select name="class_id" class="input-field w-full px-4 py-3" onchange="this.form.submit()">
                        <option value="">All Classes</option>
                        <?php foreach ($teacherClasses as $class): ?>
                            <option value="<?php echo $class['id']; ?>" <?php echo $selectedClassId == $class['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['class_name']); ?>
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
                                <?php echo htmlspecialchars($student['roll_no'] . ' - ' . $student['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex items-end">
                    <a href="fees_view.php" class="px-4 py-3 rounded-lg font-medium border border-gray-300 hover:bg-gray-50">
                        Clear Filter
                    </a>
                </div>
            </div>
        </form>
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
            <p class="text-gray-600">Choose a student from the filter above to view their fee status.</p>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

