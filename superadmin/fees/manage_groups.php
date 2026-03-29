<?php
/**
 * Super Admin - Manage Fee Groups
 * EduTrackr - School Management System
 */
require_once '../../includes/functions.php';
requireSuperAdmin();

$currentPage = 'fees';
$pageTitle = "Manage Fee Groups";

$success = '';
$error = '';

global $conn;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_group'])) {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($name)) {
            $error = 'Fee group name is required.';
        } else {
            $stmt = $conn->prepare("INSERT INTO fee_groups (name, description) VALUES (?, ?)");
            $stmt->bind_param("ss", $name, $description);
            
            if ($stmt->execute()) {
                $success = 'Fee group created successfully!';
            } else {
                $error = 'Error creating fee group: ' . $conn->error;
            }
            $stmt->close();
        }
    } elseif (isset($_POST['edit_group'])) {
        $groupId = intval($_POST['group_id']);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if (empty($name)) {
            $error = 'Fee group name is required.';
        } else {
            $stmt = $conn->prepare("UPDATE fee_groups SET name = ?, description = ? WHERE fee_group_id = ?");
            $stmt->bind_param("ssi", $name, $description, $groupId);
            
            if ($stmt->execute()) {
                $success = 'Fee group updated successfully!';
            } else {
                $error = 'Error updating fee group: ' . $conn->error;
            }
            $stmt->close();
        }
    } elseif (isset($_POST['delete_group'])) {
        $groupId = intval($_POST['group_id']);
        
        // Check if group is used
        $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM fee_installments WHERE fee_group_id = ?");
        $checkStmt->bind_param("i", $groupId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $count = $result->fetch_assoc()['count'];
        $checkStmt->close();
        
        if ($count > 0) {
            $error = 'Cannot delete fee group. It is being used by ' . $count . ' installment(s).';
        } else {
            $stmt = $conn->prepare("DELETE FROM fee_groups WHERE fee_group_id = ?");
            $stmt->bind_param("i", $groupId);
            
            if ($stmt->execute()) {
                $success = 'Fee group deleted successfully.';
            } else {
                $error = 'Error deleting fee group: ' . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Get all fee groups
$feeGroups = getAllFeeGroups();

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Manage Fee Groups</h1>
                <p class="text-gray-600">Create and manage fee categories (e.g., Tuition, Library, Bus)</p>
            </div>
            <a href="index.php" class="btn-primary text-white px-6 py-3 rounded-lg font-medium">
                ← Back to Fees
            </a>
        </div>
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

    <!-- Create Fee Group Form -->
    <div class="card p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Create New Fee Group</h2>
        <form method="POST" action="" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fee Group Name *</label>
                    <input type="text" name="name" required class="input-field w-full px-4 py-3" 
                           placeholder="e.g., Tuition Fee, Library Fee">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <input type="text" name="description" class="input-field w-full px-4 py-3" 
                           placeholder="Optional description">
                </div>
            </div>
            
            <button type="submit" name="create_group" class="btn-primary text-white px-6 py-3 rounded-lg font-medium">
                Create Fee Group
            </button>
        </form>
    </div>

    <!-- Fee Groups List -->
    <div class="card p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">All Fee Groups</h2>
        
        <?php if (count($feeGroups) > 0): ?>
            <div class="overflow-x-auto">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feeGroups as $group): ?>
                            <tr>
                                <td class="font-semibold">#<?php echo $group['fee_group_id']; ?></td>
                                <td>
                                    <div class="font-semibold text-gray-800"><?php echo htmlspecialchars($group['name']); ?></div>
                                </td>
                                <td>
                                    <div class="text-gray-600"><?php echo htmlspecialchars($group['description'] ?? 'N/A'); ?></div>
                                </td>
                                <td class="text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($group['created_at'])); ?>
                                </td>
                                <td>
                                    <div class="flex gap-2">
                                        <button onclick="editGroup(<?php echo $group['fee_group_id']; ?>, '<?php echo htmlspecialchars($group['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($group['description'] ?? '', ENT_QUOTES); ?>')" 
                                                class="px-3 py-1.5 text-sm rounded-lg font-medium bg-blue-100 text-blue-800 hover:bg-blue-200 transition-all">
                                            ✏️ Edit
                                        </button>
                                        <form method="POST" action="" class="inline" 
                                              onsubmit="return confirm('Are you sure you want to delete this fee group?');">
                                            <input type="hidden" name="group_id" value="<?php echo $group['fee_group_id']; ?>">
                                            <button type="submit" name="delete_group" 
                                                    class="px-3 py-1.5 text-sm rounded-lg font-medium bg-red-100 text-red-800 hover:bg-red-200 transition-all">
                                                🗑️ Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-gray-600 text-center py-8">No fee groups created yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">Edit Fee Group</h3>
        <form method="POST" action="">
            <input type="hidden" name="group_id" id="edit_group_id">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fee Group Name *</label>
                    <input type="text" name="name" id="edit_name" required class="input-field w-full px-4 py-3">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <input type="text" name="description" id="edit_description" class="input-field w-full px-4 py-3">
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="submit" name="edit_group" class="btn-primary text-white px-6 py-3 rounded-lg font-medium flex-1">
                    Update
                </button>
                <button type="button" onclick="closeEditModal()" class="px-6 py-3 rounded-lg font-medium border border-gray-300 hover:bg-gray-50">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editGroup(id, name, description) {
    document.getElementById('edit_group_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_description').value = description || '';
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}
</script>

<?php include '../../includes/footer.php'; ?>

