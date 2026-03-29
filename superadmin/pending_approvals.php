<?php
/**
 * Pending OAuth Approvals
 * Super Admin - EduTrackr
 */
require_once '../includes/functions.php';
requireSuperAdmin();

$currentPage = 'pending_approvals';
$pageTitle = "Pending Approvals";

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $conn;
    
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'approve') {
            $userId = (int)$_POST['user_id'];
            $roleId = (int)$_POST['role_id'];
            
            $conn->begin_transaction();
            try {
                // Update user role and status
                $stmt = $conn->prepare("UPDATE users SET role_id = ?, status = 'active' WHERE id = ?");
                $stmt->bind_param("ii", $roleId, $userId);
                $stmt->execute();
                $stmt->close();
                
                // Create role-specific record
                if ($roleId == 2) { // teacher
                    // Check if teacher record exists
                    $checkStmt = $conn->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
                    $checkStmt->bind_param("i", $userId);
                    $checkStmt->execute();
                    $result = $checkStmt->get_result();
                    
                    if ($result->num_rows == 0) {
                        $stmt = $conn->prepare("INSERT INTO teachers (user_id) VALUES (?)");
                        $stmt->bind_param("i", $userId);
                        $stmt->execute();
                        $stmt->close();
                    }
                    $checkStmt->close();
                } elseif ($roleId == 3) { // student
                    // Check if student record exists
                    $checkStmt = $conn->prepare("SELECT student_id FROM students WHERE user_id = ?");
                    $checkStmt->bind_param("i", $userId);
                    $checkStmt->execute();
                    $result = $checkStmt->get_result();
                    
                    if ($result->num_rows == 0) {
                        $rollNo = 'STU' . str_pad($userId, 4, '0', STR_PAD_LEFT);
                        $stmt = $conn->prepare("INSERT INTO students (user_id, roll_no) VALUES (?, ?)");
                        $stmt->bind_param("is", $userId, $rollNo);
                        $stmt->execute();
                        $stmt->close();
                    }
                    $checkStmt->close();
                }
                
                $conn->commit();
                $message = "User approved successfully!";
                $messageType = 'success';
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Error approving user: " . $e->getMessage();
                $messageType = 'error';
            }
            
        } elseif ($_POST['action'] === 'reject') {
            $userId = (int)$_POST['user_id'];
            
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND status = 'pending'");
            $stmt->bind_param("i", $userId);
            
            if ($stmt->execute()) {
                $message = "User rejected and removed!";
                $messageType = 'success';
            } else {
                $message = "Error rejecting user: " . $conn->error;
                $messageType = 'error';
            }
            $stmt->close();
        }
    }
}

// Get pending users
global $conn;
$pendingUsers = [];
$result = $conn->query("SELECT * FROM users WHERE status = 'pending' ORDER BY created_at DESC");
if ($result) {
    $pendingUsers = $result->fetch_all(MYSQLI_ASSOC);
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Pending OAuth Approvals</h1>
        <p class="text-gray-600">Review and approve users who signed up via Google OAuth</p>
    </div>

    <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Pending Users Table -->
    <div class="card p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">
            Pending Users (<?php echo count($pendingUsers); ?>)
        </h2>
        <?php if (empty($pendingUsers)): ?>
            <p class="text-gray-500 text-center py-8">No pending approvals. All users have been processed.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Name</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Email</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Signup Date</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingUsers as $user): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($user['name']); ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="py-3 px-4 text-gray-600">
                                    <?php echo date('M d, Y H:i', strtotime($user['created_at'])); ?>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex gap-3">
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="role_id" value="2">
                                            <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium">
                                                Approve as Teacher
                                            </button>
                                        </form>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="role_id" value="3">
                                            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-medium">
                                                Approve as Student
                                            </button>
                                        </form>
                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to reject this user?');">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium">
                                                Reject
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

