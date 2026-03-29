<?php
/**
 * Teacher - My Classes
 * EduTrackr - School Management System
 */
require_once '../includes/functions.php';
requireRole('teacher');

$currentPage = 'classes';
$pageTitle = "My Classes";

// Get teacher_id
$teacherId = getTeacherId($_SESSION['user_id']);
if (!$teacherId) {
    header('Location: ../error.php?msg=Teacher record not found');
    exit();
}

// Get teacher's assigned classes
$teacherClasses = getTeacherClasses($teacherId);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">My Classes</h1>
        <p class="text-gray-600">Class assignments where you teach subjects</p>
    </div>

    <!-- Classes List -->
    <div class="card p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Assigned Classes (<?php echo count($teacherClasses); ?>)</h2>
        
        <?php if (count($teacherClasses) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($teacherClasses as $class): ?>
                    <div class="border border-gray-200 rounded-lg p-6 hover:shadow-lg transition-shadow">
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">
                            <?php echo htmlspecialchars($class['class_name']); ?>
                        </h3>
                        <?php if ($class['description']): ?>
                            <p class="text-sm text-gray-600 mb-4"><?php echo htmlspecialchars($class['description']); ?></p>
                        <?php endif; ?>
                        <div class="flex gap-2">
                            <a href="students_list.php?class_id=<?php echo $class['id']; ?>" 
                               class="flex-1 text-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium">
                                View Students
                            </a>
                            <a href="my_subjects.php?class_id=<?php echo $class['id']; ?>" 
                               class="flex-1 text-center px-4 py-2 border border-indigo-600 text-indigo-600 hover:bg-indigo-50 rounded-lg text-sm font-medium">
                                View Subjects
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-8">
                No classes assigned yet. Please contact the administrator to assign classes to you.
            </p>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

