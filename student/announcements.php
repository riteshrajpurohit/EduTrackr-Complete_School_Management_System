<?php
/**
 * Student - View Announcements
 * EduTrackr - School Management System
 */
require_once '../includes/functions.php';
requireRole('student');

$currentPage = 'announcements';
$pageTitle = "Announcements";

// Get student data
$student = getStudentData($_SESSION['user_id']);
$classId = isset($student['class_id']) ? $student['class_id'] : null;

global $conn;

// Get announcements relevant to student
$announcements = [];
$stmt = $conn->prepare("SELECT a.*, u.name as posted_by_name
                       FROM announcements a
                       LEFT JOIN users u ON a.posted_by = u.id
                       WHERE a.is_active = 1 
                       AND (
                           a.role_target = 'all' 
                           OR a.role_target = 'student' 
                           OR a.role_target = 'teacher_student'
                           OR (a.role_target = 'all' AND a.class_id IS NULL)
                           OR (a.class_id = ?)
                       )
                       ORDER BY 
                           CASE a.priority
                               WHEN 'urgent' THEN 1
                               WHEN 'high' THEN 2
                               WHEN 'normal' THEN 3
                               WHEN 'low' THEN 4
                           END,
                           a.created_at DESC");
$stmt->bind_param("i", $classId);
$stmt->execute();
$result = $stmt->get_result();
$announcements = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Announcements</h1>
        <p class="text-gray-600">Stay updated with latest news and updates</p>
    </div>

    <?php if (count($announcements) > 0): ?>
        <div class="space-y-4">
            <?php foreach ($announcements as $announcement): 
                $priorityColors = [
                    'low' => 'bg-gray-100 text-gray-800 border-gray-300',
                    'normal' => 'bg-blue-50 text-blue-800 border-blue-300',
                    'high' => 'bg-yellow-50 text-yellow-800 border-yellow-300',
                    'urgent' => 'bg-red-50 text-red-800 border-red-300'
                ];
                $priorityColor = $priorityColors[$announcement['priority']] ?? 'bg-gray-100 text-gray-800';
            ?>
                <div class="card announcement-card p-6">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-3">
                                <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                <span class="badge <?php 
                                    echo $announcement['priority'] === 'urgent' ? 'badge-error' : 
                                        ($announcement['priority'] === 'high' ? 'badge-warning' : 
                                        ($announcement['priority'] === 'normal' ? 'badge-info' : 'badge')); 
                                ?>">
                                    <?php echo strtoupper($announcement['priority']); ?>
                                </span>
                            </div>
                            <p class="text-gray-700 mb-4 leading-relaxed text-base"><?php echo nl2br(htmlspecialchars($announcement['message'])); ?></p>
                            <div class="flex items-center text-sm text-gray-500 space-x-4 pt-3 border-t border-gray-100">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <span><strong><?php echo htmlspecialchars($announcement['posted_by_name']); ?></strong></span>
                                </div>
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span><?php echo date('F d, Y \a\t H:i', strtotime($announcement['created_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="card p-8 text-center">
            <div class="text-6xl mb-4">📢</div>
            <h2 class="text-2xl font-semibold text-gray-800 mb-2">No Announcements</h2>
            <p class="text-gray-600">There are no announcements at the moment.</p>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

