<?php
/**
 * Manage Subjects
 * Super Admin - EduTrackr
 */
require_once '../includes/functions.php';
requireSuperAdmin();

$currentPage = 'subjects';
$pageTitle = "Manage Subjects";

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $conn;
    
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $name = sanitizeInput($_POST['name']);
            $code = sanitizeInput($_POST['code']);
            
            $stmt = $conn->prepare("INSERT INTO subjects (name, code) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $code);
            
            if ($stmt->execute()) {
                $message = "Subject created successfully!";
                $messageType = 'success';
            } else {
                $message = "Error creating subject: " . $conn->error;
                $messageType = 'error';
            }
            $stmt->close();
            
        } elseif ($_POST['action'] === 'update') {
            $subjectId = (int)$_POST['subject_id'];
            $name = sanitizeInput($_POST['name']);
            $code = sanitizeInput($_POST['code']);
            
            $stmt = $conn->prepare("UPDATE subjects SET name = ?, code = ? WHERE subject_id = ?");
            $stmt->bind_param("ssi", $name, $code, $subjectId);
            
            if ($stmt->execute()) {
                $message = "Subject updated successfully!";
                $messageType = 'success';
            } else {
                $message = "Error updating subject: " . $conn->error;
                $messageType = 'error';
            }
            $stmt->close();
            
        } elseif ($_POST['action'] === 'delete') {
            $subjectId = (int)$_POST['subject_id'];
            
            // Check if subject is used in subject_assignments
            $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM subject_assignments WHERE subject_id = ?");
            $checkStmt->bind_param("i", $subjectId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $count = $result->fetch_assoc()['count'];
            $checkStmt->close();
            
            if ($count > 0) {
                $message = "Cannot delete subject. It is assigned to " . $count . " teacher(s).";
                $messageType = 'error';
            } else {
                $stmt = $conn->prepare("DELETE FROM subjects WHERE subject_id = ?");
                $stmt->bind_param("i", $subjectId);
                
                if ($stmt->execute()) {
                    $message = "Subject deleted successfully!";
                    $messageType = 'success';
                } else {
                    $message = "Error deleting subject: " . $conn->error;
                    $messageType = 'error';
                }
                $stmt->close();
            }
        }
    }
}

// Get all subjects
global $conn;
$subjects = [];
$result = $conn->query("SELECT s.*, 
                       (SELECT COUNT(*) FROM subject_assignments WHERE subject_id = s.subject_id) as assignment_count
                       FROM subjects s ORDER BY s.name");
if ($result) {
    $subjects = $result->fetch_all(MYSQLI_ASSOC);
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Manage Subjects</h1>
            <p class="text-gray-600">Create and manage subjects</p>
        </div>
        <button onclick="openCreateModal()" class="btn-primary text-white px-6 py-3 rounded-lg font-medium">
            + Create Subject
        </button>
    </div>

    <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Subjects Table -->
    <div class="card p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">All Subjects</h2>
        <?php if (empty($subjects)): ?>
            <p class="text-gray-500 text-center py-8">No subjects created yet. Create your first subject above.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Subject Name</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Code</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Assignments</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subjects as $subject): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($subject['name']); ?></td>
                                <td class="py-3 px-4">
                                    <?php if ($subject['code']): ?>
                                        <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm">
                                            <?php echo htmlspecialchars($subject['code']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full text-sm">
                                        <?php echo $subject['assignment_count']; ?> assignment(s)
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex gap-2">
                                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($subject)); ?>)" 
                                                class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</button>
                                        <a href="subject_assignments.php?subject_id=<?php echo $subject['subject_id']; ?>" 
                                           class="text-teal-600 hover:text-teal-800 text-sm font-medium">View Assignments</a>
                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this subject?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="subject_id" value="<?php echo $subject['subject_id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
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

<!-- Create/Edit Modal -->
<div id="subjectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
        <h2 id="modalTitle" class="text-2xl font-bold text-gray-800 mb-6">Create Subject</h2>
        <form id="subjectForm" method="POST">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="subject_id" id="subjectId">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Subject Name</label>
                    <input type="text" name="name" id="subjectName" required
                           class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                           placeholder="e.g., Mathematics">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Subject Code (Optional)</label>
                    <input type="text" name="code" id="subjectCode" maxlength="50"
                           class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                           placeholder="e.g., MATH">
                </div>
            </div>
            
            <div class="mt-6 flex gap-4">
                <button type="submit" class="btn-primary text-white px-6 py-3 rounded-lg font-medium flex-1">
                    Save
                </button>
                <button type="button" onclick="closeModal()" class="px-6 py-3 border border-gray-300 rounded-lg font-medium flex-1">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('modalTitle').textContent = 'Create Subject';
    document.getElementById('formAction').value = 'create';
    document.getElementById('subjectForm').reset();
    document.getElementById('subjectId').value = '';
    document.getElementById('subjectModal').classList.remove('hidden');
}

function openEditModal(subjectData) {
    document.getElementById('modalTitle').textContent = 'Edit Subject';
    document.getElementById('formAction').value = 'update';
    document.getElementById('subjectId').value = subjectData.subject_id;
    document.getElementById('subjectName').value = subjectData.name;
    document.getElementById('subjectCode').value = subjectData.code || '';
    document.getElementById('subjectModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('subjectModal').classList.add('hidden');
}

document.getElementById('subjectModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php include '../includes/footer.php'; ?>

