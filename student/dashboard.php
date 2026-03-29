<?php
/**
 * Student Dashboard
 * EduTrackr - School Management System
 */
require_once '../includes/functions.php';
requireRole('student');

$currentPage = 'dashboard';
$pageTitle = "Student Dashboard";

// Get student data
$student = getStudentData($_SESSION['user_id']);
$user = getUserData($_SESSION['user_id']);

// Check database connection
global $conn;
if (!isset($conn) || !$conn) {
    header('Location: ../error.php?msg=Database connection failed. Please check your database configuration.');
    exit();
}

// Check if student record exists
if (!$student || !isset($student['student_id'])) {
    header('Location: ../error.php?msg=Student record not found. Please contact administrator.');
    exit();
}

$studentId = $student['student_id'];
$classId = isset($student['class_id']) ? $student['class_id'] : null;

// Total subjects - count subjects assigned to student's class
$totalSubjects = 0;
if ($classId) {
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT sa.subject_id) as total 
                            FROM subject_assignments sa 
                            WHERE sa.class_id = ?");
    $stmt->bind_param("i", $classId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $row = $result->fetch_assoc();
        $totalSubjects = $row ? (int)$row['total'] : 0;
    }
    $stmt->close();
}

// Total marks entries
$totalMarks = 0;
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM marks WHERE student_id = ?");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    $row = $result->fetch_assoc();
    $totalMarks = $row ? (int)$row['total'] : 0;
}
$stmt->close();

// Average percentage
$averagePercentage = 0;
$stmt = $conn->prepare("SELECT AVG((marks_obtained / max_marks) * 100) as avg FROM marks WHERE student_id = ?");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    $row = $result->fetch_assoc();
    $averagePercentage = $row && $row['avg'] ? round((float)$row['avg'], 2) : 0;
}
$stmt->close();

// Recent marks - using subject_assignments join
$recentMarks = [];
$stmt = $conn->prepare("SELECT m.*, s.name as subject_name, s.code as subject_code
                       FROM marks m 
                       LEFT JOIN subject_assignments sa ON m.subject_assignment_id = sa.id
                       LEFT JOIN subjects s ON sa.subject_id = s.subject_id
                       WHERE m.student_id = ? 
                       ORDER BY m.updated_at DESC 
                       LIMIT 5");
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    $recentMarks = $result->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</h1>
        <p class="text-gray-600">Here's your academic overview</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="card stat-card p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-gray-500 text-sm mb-2 font-medium uppercase tracking-wide">My Subjects</p>
                    <p class="text-4xl font-bold text-indigo-600 counter" data-target="<?php echo $totalSubjects; ?>">0</p>
                    <p class="text-xs text-gray-400 mt-1">Enrolled subjects</p>
                </div>
                <div class="p-4 bg-gradient-to-br from-indigo-100 to-indigo-50 rounded-xl shadow-sm">
                    <svg class="w-10 h-10 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="card stat-card p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-gray-500 text-sm mb-2 font-medium uppercase tracking-wide">Marks Recorded</p>
                    <p class="text-4xl font-bold text-emerald-600 counter" data-target="<?php echo $totalMarks; ?>">0</p>
                </div>
                <div class="p-3 bg-emerald-100 rounded-full">
                    <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="card stat-card p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-gray-500 text-sm mb-2 font-medium uppercase tracking-wide">Average Percentage</p>
                    <p class="text-4xl font-bold text-purple-600 counter" data-target="<?php echo round($averagePercentage); ?>">0</p>
                    <div class="mt-3">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo min($averagePercentage, 100); ?>%"></div>
                        </div>
                    </div>
                </div>
                <div class="p-3 bg-purple-100 rounded-full">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Info -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <div class="card p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Class Information</h2>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600">Roll Number:</span>
                    <span class="font-medium"><?php echo $student ? htmlspecialchars($student['roll_no']) : 'N/A'; ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">Class:</span>
                    <span class="font-medium">
                        <?php echo $student && $student['class_name'] ? htmlspecialchars($student['class_name']) : 'Not assigned'; ?>
                    </span>
                </div>
                <?php if (!$student || !$classId): ?>
                    <div class="mt-4">
                        <a href="classes.php" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                            Choose a class →
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Quick Actions</h2>
            <div class="space-y-3">
                <a href="marks.php" class="flex items-center p-3 border border-gray-200 rounded-lg hover:border-indigo-500 hover:bg-indigo-50 transition-all">
                    <svg class="w-5 h-5 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <span>View My Marks</span>
                </a>
                <a href="subjects.php" class="flex items-center p-3 border border-gray-200 rounded-lg hover:border-indigo-500 hover:bg-indigo-50 transition-all">
                    <svg class="w-5 h-5 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                    <span>My Subjects</span>
                </a>
                <a href="profile.php" class="flex items-center p-3 border border-gray-200 rounded-lg hover:border-indigo-500 hover:bg-indigo-50 transition-all">
                    <svg class="w-5 h-5 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span>My Profile</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Marks -->
    <?php if (count($recentMarks) > 0): ?>
        <div class="card p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Recent Marks</h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Subject</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Marks</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Percentage</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Grade</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentMarks as $mark): 
                            $percentage = ($mark['marks_obtained'] / $mark['max_marks']) * 100;
                        ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($mark['subject_name']); ?></td>
                                <td class="py-3 px-4"><?php echo $mark['marks_obtained']; ?> / <?php echo $mark['max_marks']; ?></td>
                                <td class="py-3 px-4"><?php echo number_format($percentage, 2); ?>%</td>
                                <td class="py-3 px-4">
                                    <span class="px-3 py-1 rounded-full text-sm font-medium
                                        <?php 
                                        $grade = $mark['grade'];
                                        echo ($grade === 'A+' || $grade === 'A') ? 'bg-green-100 text-green-800' : 
                                             (($grade === 'B' || $grade === 'C') ? 'bg-blue-100 text-blue-800' : 
                                             (($grade === 'D') ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'));
                                        ?>">
                                        <?php echo $grade; ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-gray-600 text-sm"><?php echo date('M d, Y', strtotime($mark['updated_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

