<?php
/**
 * Student - My Subjects
 * EduTrackr - School Management System
 */
require_once '../includes/functions.php';
requireRole('student');

$currentPage = 'subjects';
$pageTitle = "My Subjects";

// Get student data
$student = getStudentData($_SESSION['user_id']);

// Check if student record exists
if (!$student || !isset($student['student_id'])) {
    header('Location: ../error.php?msg=Student record not found. Please contact administrator.');
    exit();
}

$studentId = $student['student_id'];
$classId = isset($student['class_id']) ? $student['class_id'] : null;

// Get subjects - from subject_assignments for student's class
$subjects = [];
global $conn;
if ($classId) {
    $stmt = $conn->prepare("SELECT DISTINCT s.subject_id, s.name, s.code, s.max_marks,
                           sa.id as subject_assignment_id,
                           t.teacher_id, u.name as teacher_name
                           FROM subject_assignments sa
                           INNER JOIN subjects s ON sa.subject_id = s.subject_id
                           INNER JOIN teachers t ON sa.teacher_id = t.teacher_id
                           INNER JOIN users u ON t.user_id = u.id
                           WHERE sa.class_id = ?
                           ORDER BY s.name");
    $stmt->bind_param("i", $classId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $subjects = $result->fetch_all(MYSQLI_ASSOC);
        
        // Get marks for each subject
        foreach ($subjects as &$subject) {
            // Get marks using subject_assignment_id
            $markStmt = $conn->prepare("SELECT m.* FROM marks m 
                                       WHERE m.student_id = ? AND m.subject_assignment_id = ?
                                       LIMIT 1");
            $markStmt->bind_param("ii", $studentId, $subject['subject_assignment_id']);
            $markStmt->execute();
            $markResult = $markStmt->get_result();
            $subject['marks'] = $markResult->fetch_assoc();
            $markStmt->close();
        }
    }
    $stmt->close();
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">My Subjects</h1>
        <p class="text-gray-600">View all your enrolled subjects</p>
    </div>

    <?php if (!$classId): ?>
        <div class="card p-8 text-center">
            <div class="text-6xl mb-4">📚</div>
            <h2 class="text-2xl font-semibold text-gray-800 mb-2">No Class Assigned</h2>
            <p class="text-gray-600 mb-6">You need to choose a class first to view your subjects.</p>
            <a href="classes.php" class="btn-primary text-white px-6 py-3 rounded-lg font-medium inline-block">
                Choose a Class
            </a>
        </div>
    <?php elseif (count($subjects) > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($subjects as $subject): ?>
                <div class="card p-6 hover:shadow-xl transition-all">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($subject['name']); ?></h3>
                        <div class="p-2 bg-indigo-100 rounded-full">
                            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                            </svg>
                        </div>
                    </div>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Teacher:</span>
                            <span class="font-medium"><?php echo htmlspecialchars($subject['teacher_name']); ?></span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Max Marks:</span>
                            <span class="font-medium"><?php echo $subject['max_marks']; ?></span>
                        </div>
                        
                        <?php if ($subject['marks']): 
                            $percentage = ($subject['marks']['marks_obtained'] / $subject['marks']['max_marks']) * 100;
                        ?>
                            <div class="pt-3 border-t border-gray-200">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm text-gray-600">Your Marks:</span>
                                    <span class="font-bold text-lg"><?php echo $subject['marks']['marks_obtained']; ?> / <?php echo $subject['marks']['max_marks']; ?></span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Grade:</span>
                                    <span class="px-3 py-1 rounded-full text-sm font-medium
                                        <?php 
                                        $grade = $subject['marks']['grade'];
                                        echo ($grade === 'A+' || $grade === 'A') ? 'bg-green-100 text-green-800' : 
                                             (($grade === 'B' || $grade === 'C') ? 'bg-blue-100 text-blue-800' : 
                                             (($grade === 'D') ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'));
                                        ?>">
                                        <?php echo $grade; ?>
                                    </span>
                                </div>
                                <div class="mt-2">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-indigo-600 h-2 rounded-full" style="width: <?php echo min($percentage, 100); ?>%"></div>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-1 text-right"><?php echo number_format($percentage, 1); ?>%</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="pt-3 border-t border-gray-200">
                                <p class="text-sm text-gray-500 text-center">No marks recorded yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="card p-8 text-center">
            <div class="text-6xl mb-4">📖</div>
            <h2 class="text-2xl font-semibold text-gray-800 mb-2">No Subjects Found</h2>
            <p class="text-gray-600">No subjects are assigned to your class yet.</p>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

