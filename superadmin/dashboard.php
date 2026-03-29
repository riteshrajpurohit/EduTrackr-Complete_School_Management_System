<?php
/**
 * Super Admin Dashboard
 * EduTrackr - School Management System
 */
require_once '../includes/functions.php';
requireSuperAdmin();

$currentPage = 'dashboard';
$pageTitle = "Principal Console";

// Get statistics
global $conn;

// Check database connection
if (!isset($conn) || !$conn) {
    header('Location: ../error.php?msg=' . urlencode('Database connection failed. Please check your database configuration.'));
    exit();
}

// Total classes
$totalClasses = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM classes");
if ($result) {
    $row = $result->fetch_assoc();
    $totalClasses = $row ? (int)$row['total'] : 0;
    $result->close();
}

// Total subjects
$totalSubjects = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM subjects");
if ($result) {
    $row = $result->fetch_assoc();
    $totalSubjects = $row ? (int)$row['total'] : 0;
    $result->close();
}

// Total teachers
$totalTeachers = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM teachers");
if ($result) {
    $row = $result->fetch_assoc();
    $totalTeachers = $row ? (int)$row['total'] : 0;
    $result->close();
}

// Total students
$totalStudents = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM students");
if ($result) {
    $row = $result->fetch_assoc();
    $totalStudents = $row ? (int)$row['total'] : 0;
    $result->close();
}

// Pending users
$pendingUsers = 0;
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE status = 'pending'");
if ($result) {
    $row = $result->fetch_assoc();
    $pendingUsers = $row ? (int)$row['total'] : 0;
    $result->close();
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Principal Console</h1>
        <p class="text-gray-600">Manage academic structure & staff</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="card stat-card p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-gray-500 text-sm mb-2 font-medium uppercase tracking-wide">Total Classes</p>
                    <p class="text-4xl font-bold text-indigo-600 counter" data-target="<?php echo $totalClasses; ?>">0</p>
                    <p class="text-xs text-gray-400 mt-1">Active classes</p>
                </div>
                <div class="icon-bubble">
                    <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="card stat-card p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-gray-500 text-sm mb-2 font-medium uppercase tracking-wide">Total Subjects</p>
                    <p class="text-4xl font-bold text-emerald-600 counter" data-target="<?php echo $totalSubjects; ?>">0</p>
                    <p class="text-xs text-gray-400 mt-1">Available subjects</p>
                </div>
                <div class="icon-bubble">
                    <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="card stat-card p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-gray-500 text-sm mb-2 font-medium uppercase tracking-wide">Total Teachers</p>
                    <p class="text-4xl font-bold text-purple-600 counter" data-target="<?php echo $totalTeachers; ?>">0</p>
                    <p class="text-xs text-gray-400 mt-1">Registered teachers</p>
                </div>
                <div class="icon-bubble">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="card stat-card p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-gray-500 text-sm mb-2 font-medium uppercase tracking-wide">Total Students</p>
                    <p class="text-4xl font-bold text-orange-600 counter" data-target="<?php echo $totalStudents; ?>">0</p>
                    <p class="text-xs text-gray-400 mt-1">Enrolled students</p>
                </div>
                <div class="icon-bubble">
                    <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Quick Actions</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="classes.php" class="flex items-center p-4 border-2 border-gray-200 rounded-lg hover:border-indigo-500 hover:bg-indigo-50 transition-all">
                <svg class="w-6 h-6 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <span class="font-medium">Create Class</span>
            </a>
            <a href="manage_users.php" class="flex items-center p-4 border-2 border-gray-200 rounded-lg hover:border-indigo-500 hover:bg-indigo-50 transition-all">
                <svg class="w-6 h-6 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <span class="font-medium">Add Teacher</span>
            </a>
            <?php if ($pendingUsers > 0): ?>
                <a href="pending_approvals.php" class="flex items-center p-4 border-2 border-orange-200 rounded-lg hover:border-orange-500 hover:bg-orange-50 transition-all">
                    <svg class="w-6 h-6 text-orange-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <span class="font-medium">Pending Approvals (<?php echo $pendingUsers; ?>)</span>
                </a>
            <?php else: ?>
                <a href="subject_assignments.php" class="flex items-center p-4 border-2 border-gray-200 rounded-lg hover:border-indigo-500 hover:bg-indigo-50 transition-all">
                    <svg class="w-6 h-6 text-indigo-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    <span class="font-medium">Assign Subjects</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Recent Teachers</h2>
            <?php
            $recentTeachers = [];
            $result = $conn->query("SELECT t.*, u.name, u.email FROM teachers t 
                                   JOIN users u ON t.user_id = u.id 
                                   ORDER BY t.created_at DESC LIMIT 5");
            if ($result) {
                $recentTeachers = $result->fetch_all(MYSQLI_ASSOC);
                $result->close();
            }

            if (!empty($recentTeachers)):
            ?>
                <div class="space-y-3">
                    <?php foreach ($recentTeachers as $teacher): ?>
                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:border-indigo-300 hover:bg-indigo-50 transition-all cursor-pointer">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-purple-400 to-purple-600 rounded-full flex items-center justify-center text-white font-bold">
                                    <?php echo strtoupper(substr($teacher['name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($teacher['name']); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($teacher['email']); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-center py-8">No teachers registered yet.</p>
            <?php endif; ?>
        </div>

        <div class="card p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Recent Classes</h2>
            <?php
            $recentClasses = [];
            $result = $conn->query("SELECT * FROM classes ORDER BY id DESC LIMIT 5");
            if ($result) {
                $recentClasses = $result->fetch_all(MYSQLI_ASSOC);
                $result->close();
            }

            if (!empty($recentClasses)):
            ?>
                <div class="space-y-3">
                    <?php foreach ($recentClasses as $class): ?>
                        <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:border-indigo-300 hover:bg-indigo-50 transition-all group">
                            <div class="flex items-center space-x-3">
                                <div class="w-10 h-10 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($class['name']); ?></p>
                                    <?php if (!empty($class['description'])): ?>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($class['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <a href="subject_assignments.php?class_id=<?php echo $class['id']; ?>" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium opacity-0 group-hover:opacity-100 transition-opacity">View Subjects →</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-500 text-center py-8">No classes created yet. <a href="classes.php" class="text-indigo-600 hover:underline font-medium">Create your first class</a></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

