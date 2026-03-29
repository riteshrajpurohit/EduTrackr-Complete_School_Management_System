<?php
/**
 * Teacher - Students List
 * EduTrackr - School Management System
 */
require_once '../includes/functions.php';
requireRole('teacher');

$currentPage = 'students';
$pageTitle = "View Students";

// Get teacher_id
$teacherId = getTeacherId($_SESSION['user_id']);
if (!$teacherId) {
    header('Location: ../error.php?msg=Teacher record not found');
    exit();
}

// Get assigned classes
$teacherClasses = getTeacherClasses($teacherId);

// Get students for selected class
$students = [];
$selectedClass = null;
$classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : null;

if ($classId) {
    // Verify teacher has access to this class
    $hasAccess = false;
    foreach ($teacherClasses as $class) {
        if ($class['id'] == $classId) {
            $hasAccess = true;
            $selectedClass = $class;
            break;
        }
    }
    
    if ($hasAccess) {
        $students = getStudentsByClass($classId);
    } else {
        $error = 'Unauthorized: You do not have access to this class.';
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">View Students</h1>
        <p class="text-gray-600">View students in your assigned classes</p>
    </div>

    <!-- Class Selection -->
    <div class="card p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Select Class</h2>
        <form method="GET" action="" class="flex gap-4">
            <div class="flex-1">
                <select name="class_id" required
                        class="input-field w-full px-4 py-2 border border-gray-300 rounded-lg"
                        onchange="this.form.submit()">
                    <option value="">Select a class</option>
                    <?php foreach ($teacherClasses as $class): ?>
                        <option value="<?php echo $class['id']; ?>" 
                                <?php echo ($classId == $class['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class['class_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <!-- Students List -->
    <?php if ($selectedClass): ?>
        <div class="card p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">
                Students in <?php echo htmlspecialchars($selectedClass['class_name']); ?>
            </h2>
            
            <?php if (count($students) > 0): ?>
                <div class="overflow-x-auto">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Roll No</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Contact</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td>
                                        <span class="font-medium text-gray-800"><?php echo htmlspecialchars($student['roll_no']); ?></span>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-400 to-indigo-600 flex items-center justify-center text-white font-semibold text-sm">
                                                <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                                            </div>
                                            <span class="font-medium text-gray-800"><?php echo htmlspecialchars($student['name']); ?></span>
                                        </div>
                                    </td>
                                    <td class="text-gray-600"><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td class="text-gray-600"><?php echo htmlspecialchars($student['contact'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-center py-8">No students enrolled in this class assignment yet.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="card p-6">
            <p class="text-gray-500 text-center py-8">Please select a class assignment above to view students.</p>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

