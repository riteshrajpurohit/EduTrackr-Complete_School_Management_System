<?php
/**
 * Super Admin - Manage Extra Fees
 * EduTrackr - School Management System
 */
require_once '../../includes/functions.php';
requireSuperAdmin();

$currentPage = 'fees';
$pageTitle = "Manage Extra Fees";

$success = '';
$error = '';

global $conn;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_extra_fee'])) {
        $feeGroupId = intval($_POST['fee_group_id'] ?? 0);
        $classId = !empty($_POST['class_id']) ? intval($_POST['class_id']) : null;
        $studentId = !empty($_POST['student_id']) ? intval($_POST['student_id']) : null;
        $amount = floatval($_POST['amount'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        
        if ($feeGroupId == 0 || $amount <= 0) {
            $error = 'Fee group and amount are required.';
        } elseif ($classId == null && $studentId == null) {
            $error = 'Either class or student must be selected.';
        } else {
            $stmt = $conn->prepare("INSERT INTO extra_fees (fee_group_id, class_id, student_id, amount, description) 
                                   VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiids", $feeGroupId, $classId, $studentId, $amount, $description);
            
            if ($stmt->execute()) {
                $extraFeeId = $conn->insert_id;
                
                // If assigned to class, assign to all students in that class
                if ($classId && !$studentId) {
                    $students = getStudentsByClass($classId);
                    foreach ($students as $student) {
                        // Check if already assigned
                        $checkStmt = $conn->prepare("SELECT student_fee_id FROM student_fees WHERE student_id = ? AND extra_fee_id = ?");
                        $checkStmt->bind_param("ii", $student['student_id'], $extraFeeId);
                        $checkStmt->execute();
                        $result = $checkStmt->get_result();
                        
                        if ($result->num_rows == 0) {
                            $assignStmt = $conn->prepare("INSERT INTO student_fees (student_id, fee_group_id, extra_fee_id, amount, due_date, status) 
                                                          VALUES (?, ?, ?, ?, CURDATE(), 'Pending')");
                            $assignStmt->bind_param("iiid", $student['student_id'], $feeGroupId, $extraFeeId, $amount);
                            $assignStmt->execute();
                            $assignStmt->close();
                        }
                        $checkStmt->close();
                    }
                } elseif ($studentId) {
                    // Assign to specific student
                    $checkStmt = $conn->prepare("SELECT student_fee_id FROM student_fees WHERE student_id = ? AND extra_fee_id = ?");
                    $checkStmt->bind_param("ii", $studentId, $extraFeeId);
                    $checkStmt->execute();
                    $result = $checkStmt->get_result();
                    
                    if ($result->num_rows == 0) {
                        $assignStmt = $conn->prepare("INSERT INTO student_fees (student_id, fee_group_id, extra_fee_id, amount, due_date, status) 
                                                      VALUES (?, ?, ?, ?, CURDATE(), 'Pending')");
                        $assignStmt->bind_param("iiid", $studentId, $feeGroupId, $extraFeeId, $amount);
                        $assignStmt->execute();
                        $assignStmt->close();
                    }
                    $checkStmt->close();
                }
                
                $success = 'Extra fee created and assigned successfully!';
            } else {
                $error = 'Error creating extra fee: ' . $conn->error;
            }
            $stmt->close();
        }
    } elseif (isset($_POST['delete_extra_fee'])) {
        $extraFeeId = intval($_POST['extra_fee_id']);
        
        // Check if assigned to students
        $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM student_fees WHERE extra_fee_id = ?");
        $checkStmt->bind_param("i", $extraFeeId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $count = $result->fetch_assoc()['count'];
        $checkStmt->close();
        
        if ($count > 0) {
            $error = 'Cannot delete extra fee. It is assigned to ' . $count . ' student(s). Remove assignments first.';
        } else {
            $stmt = $conn->prepare("DELETE FROM extra_fees WHERE extra_fee_id = ?");
            $stmt->bind_param("i", $extraFeeId);
            
            if ($stmt->execute()) {
                $success = 'Extra fee deleted successfully.';
            } else {
                $error = 'Error deleting extra fee: ' . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Get all extra fees
$extraFees = [];
$result = $conn->query("SELECT ef.*, fg.name as fee_group_name, c.name as class_name,
                       s.roll_no, u.name as student_name
                       FROM extra_fees ef
                       JOIN fee_groups fg ON ef.fee_group_id = fg.fee_group_id
                       LEFT JOIN classes c ON ef.class_id = c.id
                       LEFT JOIN students s ON ef.student_id = s.student_id
                       LEFT JOIN users u ON s.user_id = u.id
                       ORDER BY ef.assigned_at DESC");
if ($result) {
    $extraFees = $result->fetch_all(MYSQLI_ASSOC);
}

// Get classes, fee groups, and students for dropdowns
$classes = getAllClasses();
$feeGroups = getAllFeeGroups();
$allStudents = [];
$result = $conn->query("SELECT s.student_id, s.roll_no, u.name, c.name as class_name
                       FROM students s
                       JOIN users u ON s.user_id = u.id
                       LEFT JOIN classes c ON s.class_id = c.id
                       ORDER BY s.roll_no");
if ($result) {
    $allStudents = $result->fetch_all(MYSQLI_ASSOC);
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Manage Extra Fees</h1>
                <p class="text-gray-600">Add extra fees per class or specific students</p>
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

    <!-- Create Extra Fee Form -->
    <div class="card p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Create New Extra Fee</h2>
        <form method="POST" action="" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fee Group *</label>
                    <select name="fee_group_id" required class="input-field w-full px-4 py-3">
                        <option value="">Choose a fee group</option>
                        <?php foreach ($feeGroups as $group): ?>
                            <option value="<?php echo $group['fee_group_id']; ?>">
                                <?php echo htmlspecialchars($group['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Amount (₹) *</label>
                    <input type="number" step="0.01" min="0" name="amount" required 
                           class="input-field w-full px-4 py-3" placeholder="0.00">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Assign to Class</label>
                    <select name="class_id" id="class_select" class="input-field w-full px-4 py-3" onchange="toggleStudentSelect()">
                        <option value="">Choose a class (applies to all students)</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>">
                                <?php echo htmlspecialchars($class['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Leave empty if assigning to specific student</p>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">OR Assign to Specific Student</label>
                    <select name="student_id" id="student_select" class="input-field w-full px-4 py-3" onchange="toggleClassSelect()">
                        <option value="">Choose a student</option>
                        <?php foreach ($allStudents as $student): ?>
                            <option value="<?php echo $student['student_id']; ?>">
                                <?php echo htmlspecialchars($student['roll_no'] . ' - ' . $student['name'] . ' (' . ($student['class_name'] ?? 'No Class') . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Leave empty if assigning to entire class</p>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" rows="2" class="input-field w-full px-4 py-3" 
                          placeholder="Optional description"></textarea>
            </div>
            
            <button type="submit" name="create_extra_fee" class="btn-primary text-white px-6 py-3 rounded-lg font-medium">
                Create Extra Fee
            </button>
        </form>
    </div>

    <!-- Extra Fees List -->
    <div class="card p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">All Extra Fees</h2>
        
        <?php if (count($extraFees) > 0): ?>
            <div class="overflow-x-auto">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Fee Group</th>
                            <th>Assigned To</th>
                            <th>Amount</th>
                            <th>Description</th>
                            <th>Assigned At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($extraFees as $fee): ?>
                            <tr>
                                <td>
                                    <div class="font-semibold text-gray-800"><?php echo htmlspecialchars($fee['fee_group_name']); ?></div>
                                </td>
                                <td>
                                    <?php if ($fee['student_id']): ?>
                                        <span class="badge badge-info">
                                            <?php echo htmlspecialchars($fee['roll_no'] . ' - ' . $fee['student_name']); ?>
                                        </span>
                                    <?php elseif ($fee['class_id']): ?>
                                        <span class="badge badge-success">
                                            Class: <?php echo htmlspecialchars($fee['class_name']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td class="font-bold text-gray-800">₹<?php echo number_format($fee['amount'], 2); ?></td>
                                <td>
                                    <div class="text-gray-600"><?php echo htmlspecialchars($fee['description'] ?? 'N/A'); ?></div>
                                </td>
                                <td class="text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($fee['assigned_at'])); ?>
                                </td>
                                <td>
                                    <form method="POST" action="" class="inline" 
                                          onsubmit="return confirm('Are you sure you want to delete this extra fee?');">
                                        <input type="hidden" name="extra_fee_id" value="<?php echo $fee['extra_fee_id']; ?>">
                                        <button type="submit" name="delete_extra_fee" 
                                                class="px-3 py-1.5 text-sm rounded-lg font-medium bg-red-100 text-red-800 hover:bg-red-200 transition-all">
                                            🗑️ Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-gray-600 text-center py-8">No extra fees created yet.</p>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleStudentSelect() {
    const classSelect = document.getElementById('class_select');
    const studentSelect = document.getElementById('student_select');
    if (classSelect.value) {
        studentSelect.value = '';
        studentSelect.disabled = true;
    } else {
        studentSelect.disabled = false;
    }
}

function toggleClassSelect() {
    const classSelect = document.getElementById('class_select');
    const studentSelect = document.getElementById('student_select');
    if (studentSelect.value) {
        classSelect.value = '';
        classSelect.disabled = true;
    } else {
        classSelect.disabled = false;
    }
}
</script>

<?php include '../../includes/footer.php'; ?>

