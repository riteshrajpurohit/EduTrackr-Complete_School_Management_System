<?php
/**
 * Teacher Dashboard
 * EduTrackr - School Management System
 */
require_once '../includes/functions.php';
requireRole('teacher');

$currentPage = 'dashboard';
$pageTitle = "Teacher Dashboard";

// Get teacher data
$teacher = getTeacherData($_SESSION['user_id']);
$user = getUserData($_SESSION['user_id']);

// Get teacher_id from teachers table
$teacherId = getTeacherId($_SESSION['user_id']);
if (!$teacherId) {
    header('Location: ../error.php?msg=Teacher record not found. Please contact administrator.');
    exit();
}

// Get statistics
global $conn;

// Check database connection
if (!isset($conn) || !$conn) {
    header('Location: ../error.php?msg=Database connection failed. Please check your database configuration.');
    exit();
}

// Initialize default values
$totalSubjects = 0;
$totalClasses = 0;
$totalStudents = 0;
$todayMarks = 0;

// Total subjects - count all subjects assigned to this teacher via subject_assignments
$stmt = $conn->prepare("SELECT COUNT(DISTINCT sa.subject_id) as total 
                        FROM subject_assignments sa 
                        WHERE sa.teacher_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $row = $result->fetch_assoc();
        $totalSubjects = $row ? (int)$row['total'] : 0;
    }
    $stmt->close();
}

// Total classes - count distinct classes where teacher has subjects
$stmt = $conn->prepare("SELECT COUNT(DISTINCT sa.class_id) as total 
                        FROM subject_assignments sa 
                        WHERE sa.teacher_id = ?");
if ($stmt) {
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $row = $result->fetch_assoc();
        $totalClasses = $row ? (int)$row['total'] : 0;
    }
    $stmt->close();
}

// Total students - Count students from classes where teacher has subjects
$stmt = $conn->prepare("SELECT COUNT(DISTINCT s.student_id) as total 
                        FROM students s
                        INNER JOIN subject_assignments sa ON s.class_id = sa.class_id
                        WHERE sa.teacher_id = ? AND s.class_id IS NOT NULL");
if ($stmt) {
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $row = $result->fetch_assoc();
        $totalStudents = $row ? (int)$row['total'] : 0;
    }
    $stmt->close();
}

// Recent marks added today - using subject_assignment_id
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM marks m 
                       INNER JOIN subject_assignments sa ON m.subject_assignment_id = sa.id 
                       WHERE sa.teacher_id = ? 
                       AND DATE(m.created_at) = CURDATE()");
if ($stmt) {
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $row = $result->fetch_assoc();
        $todayMarks = $row ? (int)$row['total'] : 0;
    }
    $stmt->close();
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Welcome back, <?php echo htmlspecialchars($user['name']); ?>!</h1>
        <p class="text-gray-600">Here's an overview of your teaching activities</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="card stat-card p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-gray-500 text-sm mb-2 font-medium uppercase tracking-wide">Total Subjects</p>
                    <p class="text-4xl font-bold text-indigo-600 counter" data-target="<?php echo $totalSubjects; ?>">0</p>
                    <p class="text-xs text-gray-400 mt-1">Assigned subjects</p>
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
                    <p class="text-gray-500 text-sm mb-2 font-medium uppercase tracking-wide">Total Classes</p>
                    <p class="text-4xl font-bold text-emerald-600 counter" data-target="<?php echo $totalClasses; ?>">0</p>
                    <p class="text-xs text-gray-400 mt-1">Teaching classes</p>
                </div>
                <div class="p-4 bg-gradient-to-br from-emerald-100 to-emerald-50 rounded-xl shadow-sm">
                    <svg class="w-10 h-10 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="card stat-card p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-gray-500 text-sm mb-2 font-medium uppercase tracking-wide">Total Students</p>
                    <p class="text-4xl font-bold text-purple-600 counter" data-target="<?php echo $totalStudents; ?>">0</p>
                    <p class="text-xs text-gray-400 mt-1">Students taught</p>
                </div>
                <div class="p-4 bg-gradient-to-br from-purple-100 to-purple-50 rounded-xl shadow-sm">
                    <svg class="w-10 h-10 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="card stat-card p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-gray-500 text-sm mb-2 font-medium uppercase tracking-wide">Marks Added Today</p>
                    <p class="text-4xl font-bold text-orange-600 counter" data-target="<?php echo $todayMarks; ?>">0</p>
                    <p class="text-xs text-gray-400 mt-1">Today's entries</p>
                </div>
                <div class="p-4 bg-gradient-to-br from-orange-100 to-orange-50 rounded-xl shadow-sm">
                    <svg class="w-10 h-10 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Quick Actions</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="subjects.php" class="flex items-center p-4 border-2 border-gray-200 rounded-lg hover:border-indigo-500 hover:bg-indigo-50 transition-all">
                <svg class="w-6 h-6 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <span class="font-medium">Add New Subject</span>
            </a>
            <a href="marks.php" class="flex items-center p-4 border-2 border-gray-200 rounded-lg hover:border-indigo-500 hover:bg-indigo-50 transition-all">
                <svg class="w-6 h-6 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span class="font-medium">Upload Marks</span>
            </a>
            <a href="classes.php" class="flex items-center p-4 border-2 border-gray-200 rounded-lg hover:border-indigo-500 hover:bg-indigo-50 transition-all">
                <svg class="w-6 h-6 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <span class="font-medium">Manage Classes</span>
            </a>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="card p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Recent Subjects</h2>
        <?php
        $recentSubjects = [];
        $stmt = $conn->prepare("SELECT DISTINCT s.subject_id, s.name, s.code, s.max_marks, s.created_at,
                               c.name as class_name
                               FROM subject_assignments sa
                               INNER JOIN subjects s ON sa.subject_id = s.subject_id
                               INNER JOIN classes c ON sa.class_id = c.id
                               WHERE sa.teacher_id = ? 
                               ORDER BY s.created_at DESC 
                               LIMIT 5");
        if ($stmt) {
            $stmt->bind_param("i", $teacherId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result) {
                $recentSubjects = $result->fetch_all(MYSQLI_ASSOC);
            }
            $stmt->close();
        }

        if (!empty($recentSubjects) && count($recentSubjects) > 0):
        ?>
            <div class="overflow-x-auto">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Subject Name</th>
                            <th>Class</th>
                            <th>Max Marks</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentSubjects as $subject): ?>
                            <tr>
                                <td>
                                    <div class="font-medium text-gray-800"><?php echo htmlspecialchars($subject['name']); ?></div>
                                    <?php if (isset($subject['code']) && $subject['code']): ?>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($subject['code']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?php echo htmlspecialchars($subject['class_name'] ?? 'N/A'); ?></span>
                                </td>
                                <td class="font-semibold text-gray-700"><?php echo $subject['max_marks']; ?></td>
                                <td class="text-gray-600 text-sm"><?php echo date('M d, Y', strtotime($subject['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-8">No subjects added yet. <a href="subjects.php" class="text-indigo-600 hover:underline font-medium">Add your first subject</a></p>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

