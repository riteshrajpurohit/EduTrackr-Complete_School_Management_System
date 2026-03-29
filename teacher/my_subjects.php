<?php
/**
 * Teacher - My Subjects
 * EduTrackr - School Management System
 */
require_once '../includes/functions.php';
requireRole('teacher');

$currentPage = 'subjects';
$pageTitle = "My Subjects";

// Get teacher_id
$teacherId = getTeacherId($_SESSION['user_id']);
if (!$teacherId) {
    header('Location: ../error.php?msg=Teacher record not found');
    exit();
}

// Get teacher's assigned subjects
$subjects = getTeacherSubjects($teacherId);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">My Subjects</h1>
        <p class="text-gray-600">Subjects assigned to you by the administrator</p>
    </div>

    <!-- Subjects List -->
    <div class="card p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Assigned Subjects (<?php echo count($subjects); ?>)</h2>
        
        <?php if (count($subjects) > 0): ?>
            <div class="overflow-x-auto">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Class</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjects as $subject): ?>
                            <tr>
                                <td>
                                    <div class="font-medium text-gray-800"><?php echo htmlspecialchars($subject['subject_name']); ?></div>
                                    <?php if (isset($subject['subject_code']) && $subject['subject_code']): ?>
                                        <div class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars($subject['subject_code']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?php echo htmlspecialchars($subject['class_name']); ?></span>
                                </td>
                                <td>
                                    <a href="marks.php?subject_assignment_id=<?php echo $subject['id']; ?>" 
                                       class="btn-primary text-white px-4 py-2 text-sm rounded-lg font-medium">
                                        📝 Upload Marks
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-8">
                No subjects assigned yet. Please contact the administrator to assign subjects to you.
            </p>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

