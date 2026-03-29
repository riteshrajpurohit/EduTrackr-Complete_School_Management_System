<?php
/**
 * Student Profile
 * EduTrackr - School Management System
 */
require_once '../includes/functions.php';
requireRole('student');

$currentPage = 'profile';
$pageTitle = "My Profile";

// Get student data
$student = getStudentData($_SESSION['user_id']);
$user = getUserData($_SESSION['user_id']);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">My Profile</h1>
        <p class="text-gray-600">View your personal information</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Personal Information -->
        <div class="card p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Personal Information</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Full Name</label>
                    <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($user['name']); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Email Address</label>
                    <p class="text-lg text-gray-800"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Roll Number</label>
                    <p class="text-lg font-semibold text-gray-800"><?php echo $student ? htmlspecialchars($student['roll_no']) : 'N/A'; ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Contact</label>
                    <p class="text-lg text-gray-800"><?php echo $student && $student['contact'] ? htmlspecialchars($student['contact']) : 'N/A'; ?></p>
                </div>
            </div>
        </div>

        <!-- Academic Information -->
        <div class="card p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Academic Information</h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Class</label>
                    <p class="text-lg font-semibold text-gray-800">
                        <?php 
                        if ($student && $student['class_name']) {
                            echo htmlspecialchars($student['class_name']);
                        } else {
                            echo 'Not assigned';
                        }
                        ?>
                    </p>
                    <?php if (!$student || !isset($student['class_id']) || !$student['class_id']): ?>
                        <a href="classes.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium mt-2 inline-block">
                            Choose a class →
                        </a>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Account Created</label>
                    <p class="text-lg text-gray-800"><?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

