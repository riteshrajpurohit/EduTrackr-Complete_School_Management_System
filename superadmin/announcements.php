<?php
/**
 * Super Admin - Manage Announcements
 * EduTrackr - School Management System
 */
require_once '../includes/functions.php';
requireSuperAdmin();

$currentPage = 'announcements';
$pageTitle = "Manage Announcements";

$success = '';
$error = '';

global $conn;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_announcement'])) {
        $title = trim($_POST['title'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $roleTarget = $_POST['role_target'] ?? 'all';
        $classId = !empty($_POST['class_id']) ? intval($_POST['class_id']) : null;
        $priority = $_POST['priority'] ?? 'normal';
        
        if (empty($title) || empty($message)) {
            $error = 'Title and message are required.';
        } else {
            // Handle NULL class_id properly
            if ($classId) {
                $stmt = $conn->prepare("INSERT INTO announcements (title, message, posted_by, role_target, class_id, priority) 
                                       VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssisis", $title, $message, $_SESSION['user_id'], $roleTarget, $classId, $priority);
            } else {
                $stmt = $conn->prepare("INSERT INTO announcements (title, message, posted_by, role_target, class_id, priority) 
                                       VALUES (?, ?, ?, ?, NULL, ?)");
                $stmt->bind_param("ssiss", $title, $message, $_SESSION['user_id'], $roleTarget, $priority);
            }
            
            if ($stmt->execute()) {
                $success = 'Announcement created successfully!';
            } else {
                $error = 'Error creating announcement: ' . $conn->error;
            }
            $stmt->close();
        }
    } elseif (isset($_POST['toggle_announcement'])) {
        $announcementId = intval($_POST['announcement_id']);
        $isActive = intval($_POST['is_active']);
        
        $stmt = $conn->prepare("UPDATE announcements SET is_active = ? WHERE id = ?");
        $stmt->bind_param("ii", $isActive, $announcementId);
        $stmt->execute();
        $stmt->close();
        $success = 'Announcement status updated.';
    } elseif (isset($_POST['delete_announcement'])) {
        $announcementId = intval($_POST['announcement_id']);
        $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->bind_param("i", $announcementId);
        $stmt->execute();
        $stmt->close();
        $success = 'Announcement deleted successfully.';
    }
}

// Get all announcements
$announcements = [];
$result = $conn->query("SELECT a.*, u.name as posted_by_name, c.name as class_name
                       FROM announcements a
                       LEFT JOIN users u ON a.posted_by = u.id
                       LEFT JOIN classes c ON a.class_id = c.id
                       ORDER BY a.created_at DESC");
if ($result) {
    $announcements = $result->fetch_all(MYSQLI_ASSOC);
}

// Get all classes for targeting
$classes = getAllClasses();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Manage Announcements</h1>
        <p class="text-gray-600">Create and manage system-wide announcements</p>
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

    <!-- Create Announcement Form -->
    <div class="card p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Create New Announcement</h2>
        <form method="POST" action="" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
                <input type="text" name="title" required class="input-field w-full px-4 py-3" 
                       placeholder="Enter announcement title">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Message *</label>
                <textarea name="message" required rows="4" class="input-field w-full px-4 py-3" 
                          placeholder="Enter announcement message"></textarea>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Target Audience</label>
                    <select name="role_target" class="input-field w-full px-4 py-3">
                        <option value="all">All Users</option>
                        <option value="teacher_student">Teachers & Students</option>
                        <option value="teacher">Teachers Only</option>
                        <option value="student">Students Only</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Class (Optional)</label>
                    <select name="class_id" class="input-field w-full px-4 py-3">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>">
                                <?php echo htmlspecialchars($class['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                    <select name="priority" class="input-field w-full px-4 py-3">
                        <option value="low">Low</option>
                        <option value="normal" selected>Normal</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
            </div>
            
            <button type="submit" name="create_announcement" class="btn-primary text-white px-6 py-3 rounded-lg font-medium">
                Create Announcement
            </button>
        </form>
    </div>

    <!-- Announcements List -->
    <div class="card p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">All Announcements</h2>
        
        <?php if (count($announcements) > 0): ?>
            <div class="space-y-4">
                <?php foreach ($announcements as $announcement): 
                    $priorityColors = [
                        'low' => 'bg-gray-100 text-gray-800',
                        'normal' => 'bg-blue-100 text-blue-800',
                        'high' => 'bg-yellow-100 text-yellow-800',
                        'urgent' => 'bg-red-100 text-red-800'
                    ];
                    $priorityColor = $priorityColors[$announcement['priority']] ?? 'bg-gray-100 text-gray-800';
                ?>
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-all">
                        <div class="flex items-start justify-between mb-2">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-2">
                                    <h3 class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                    <span class="px-2 py-1 rounded text-xs font-medium <?php echo $priorityColor; ?>">
                                        <?php echo strtoupper($announcement['priority']); ?>
                                    </span>
                                    <?php if ($announcement['is_active']): ?>
                                        <span class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">Active</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800">Inactive</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-gray-600 mb-2"><?php echo nl2br(htmlspecialchars($announcement['message'])); ?></p>
                                <div class="text-sm text-gray-500">
                                    <span>Posted by: <?php echo htmlspecialchars($announcement['posted_by_name']); ?></span>
                                    <span class="mx-2">•</span>
                                    <span>Target: <?php echo ucfirst(str_replace('_', ' ', $announcement['role_target'])); ?></span>
                                    <?php if ($announcement['class_name']): ?>
                                        <span class="mx-2">•</span>
                                        <span>Class: <?php echo htmlspecialchars($announcement['class_name'] ?? 'N/A'); ?></span>
                                    <?php endif; ?>
                                    <span class="mx-2">•</span>
                                    <span><?php echo date('M d, Y H:i', strtotime($announcement['created_at'])); ?></span>
                                </div>
                            </div>
                            <div class="flex gap-2 ml-4">
                                <form method="POST" action="" class="inline">
                                    <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                                    <input type="hidden" name="is_active" value="<?php echo $announcement['is_active'] ? 0 : 1; ?>">
                                    <button type="submit" name="toggle_announcement" 
                                            class="px-3 py-1 text-sm rounded <?php echo $announcement['is_active'] ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'; ?>">
                                        <?php echo $announcement['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                </form>
                                <form method="POST" action="" class="inline" onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                                    <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                                    <button type="submit" name="delete_announcement" 
                                            class="px-3 py-1 text-sm rounded bg-red-100 text-red-800">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-gray-600 text-center py-8">No announcements yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

