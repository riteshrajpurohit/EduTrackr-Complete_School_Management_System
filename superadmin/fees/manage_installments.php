<?php
/**
 * Super Admin - Manage Fee Installments
 * EduTrackr - School Management System
 */
require_once '../../includes/functions.php';
requireSuperAdmin();

$currentPage = 'fees';
$pageTitle = "Manage Fee Installments";

$success = '';
$error = '';

global $conn;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_installment'])) {
        $classId = intval($_POST['class_id'] ?? 0);
        $feeGroupId = intval($_POST['fee_group_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $dueDate = $_POST['due_date'] ?? null;
        
        if (empty($name) || $classId == 0 || $feeGroupId == 0 || $amount <= 0 || empty($dueDate)) {
            $error = 'All fields are required and amount must be greater than zero.';
        } else {
            $stmt = $conn->prepare("INSERT INTO fee_installments (class_id, fee_group_id, name, amount, due_date) 
                                   VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iisds", $classId, $feeGroupId, $name, $amount, $dueDate);
            
            if ($stmt->execute()) {
                $success = 'Installment created successfully!';
            } else {
                $error = 'Error creating installment: ' . $conn->error;
            }
            $stmt->close();
        }
    } elseif (isset($_POST['edit_installment'])) {
        $installmentId = intval($_POST['installment_id']);
        $classId = intval($_POST['class_id'] ?? 0);
        $feeGroupId = intval($_POST['fee_group_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);
        $dueDate = $_POST['due_date'] ?? null;
        
        if (empty($name) || $classId == 0 || $feeGroupId == 0 || $amount <= 0 || empty($dueDate)) {
            $error = 'All fields are required and amount must be greater than zero.';
        } else {
            $stmt = $conn->prepare("UPDATE fee_installments SET class_id = ?, fee_group_id = ?, name = ?, amount = ?, due_date = ? 
                                   WHERE installment_id = ?");
            $stmt->bind_param("iisdsi", $classId, $feeGroupId, $name, $amount, $dueDate, $installmentId);
            
            if ($stmt->execute()) {
                $success = 'Installment updated successfully!';
            } else {
                $error = 'Error updating installment: ' . $conn->error;
            }
            $stmt->close();
        }
    } elseif (isset($_POST['delete_installment'])) {
        $installmentId = intval($_POST['installment_id']);
        
        // Check if installment is assigned to students
        $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM student_fees WHERE installment_id = ?");
        $checkStmt->bind_param("i", $installmentId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $count = $result->fetch_assoc()['count'];
        $checkStmt->close();
        
        if ($count > 0) {
            $error = 'Cannot delete installment. It is assigned to ' . $count . ' student(s).';
        } else {
            $stmt = $conn->prepare("DELETE FROM fee_installments WHERE installment_id = ?");
            $stmt->bind_param("i", $installmentId);
            
            if ($stmt->execute()) {
                $success = 'Installment deleted successfully.';
            } else {
                $error = 'Error deleting installment: ' . $conn->error;
            }
            $stmt->close();
        }
    }
}

// Get all installments with details
$installments = [];
$result = $conn->query("SELECT fi.*, c.name as class_name, fg.name as fee_group_name
                       FROM fee_installments fi
                       JOIN classes c ON fi.class_id = c.id
                       JOIN fee_groups fg ON fi.fee_group_id = fg.fee_group_id
                       ORDER BY c.name, fi.due_date");
if ($result) {
    $installments = $result->fetch_all(MYSQLI_ASSOC);
}

// Get classes and fee groups for dropdowns
$classes = getAllClasses();
$feeGroups = getAllFeeGroups();

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Manage Fee Installments</h1>
                <p class="text-gray-600">Create installments for each class (e.g., Installment 1, 2, 3)</p>
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

    <!-- Create Installment Form -->
    <div class="card p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Create New Installment</h2>
        <form method="POST" action="" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Class *</label>
                    <select name="class_id" required class="input-field w-full px-4 py-3">
                        <option value="">Choose a class</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>">
                                <?php echo htmlspecialchars($class['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fee Group *</label>
                    <select name="fee_group_id" required class="input-field w-full px-4 py-3">
                        <option value="">Choose a fee group</option>
                        <?php foreach ($feeGroups as $group): ?>
                            <option value="<?php echo $group['fee_group_id']; ?>">
                                <?php echo htmlspecialchars($group['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Installment Name *</label>
                    <input type="text" name="name" required class="input-field w-full px-4 py-3" 
                           placeholder="e.g., Installment 1">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Amount (₹) *</label>
                    <input type="number" step="0.01" min="0" name="amount" required 
                           class="input-field w-full px-4 py-3" placeholder="0.00">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Due Date *</label>
                    <input type="date" name="due_date" required class="input-field w-full px-4 py-3">
                </div>
            </div>
            
            <button type="submit" name="create_installment" class="btn-primary text-white px-6 py-3 rounded-lg font-medium">
                Create Installment
            </button>
        </form>
    </div>

    <!-- Installments List -->
    <div class="card p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">All Installments</h2>
        
        <?php if (count($installments) > 0): ?>
            <div class="overflow-x-auto">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th>Fee Group</th>
                            <th>Installment Name</th>
                            <th>Amount</th>
                            <th>Due Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($installments as $installment): ?>
                            <tr>
                                <td>
                                    <span class="badge badge-info"><?php echo htmlspecialchars($installment['class_name']); ?></span>
                                </td>
                                <td>
                                    <div class="font-semibold text-gray-800"><?php echo htmlspecialchars($installment['fee_group_name']); ?></div>
                                </td>
                                <td class="font-semibold"><?php echo htmlspecialchars($installment['name']); ?></td>
                                <td class="font-bold text-gray-800">₹<?php echo number_format($installment['amount'], 2); ?></td>
                                <td class="text-sm">
                                    <?php 
                                    $dueDate = strtotime($installment['due_date']);
                                    $isOverdue = $dueDate < time();
                                    ?>
                                    <span class="<?php echo $isOverdue ? 'text-red-600 font-semibold' : 'text-gray-600'; ?>">
                                        <?php echo date('M d, Y', $dueDate); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="flex gap-2">
                                        <button onclick="editInstallment(<?php echo $installment['installment_id']; ?>, <?php echo $installment['class_id']; ?>, <?php echo $installment['fee_group_id']; ?>, '<?php echo htmlspecialchars($installment['name'], ENT_QUOTES); ?>', <?php echo $installment['amount']; ?>, '<?php echo $installment['due_date']; ?>')" 
                                                class="px-3 py-1.5 text-sm rounded-lg font-medium bg-blue-100 text-blue-800 hover:bg-blue-200 transition-all">
                                            ✏️ Edit
                                        </button>
                                        <form method="POST" action="" class="inline" 
                                              onsubmit="return confirm('Are you sure you want to delete this installment?');">
                                            <input type="hidden" name="installment_id" value="<?php echo $installment['installment_id']; ?>">
                                            <button type="submit" name="delete_installment" 
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
            <p class="text-gray-600 text-center py-8">No installments created yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">Edit Installment</h3>
        <form method="POST" action="">
            <input type="hidden" name="installment_id" id="edit_installment_id">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Class *</label>
                    <select name="class_id" id="edit_class_id" required class="input-field w-full px-4 py-3">
                        <option value="">Choose a class</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>">
                                <?php echo htmlspecialchars($class['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fee Group *</label>
                    <select name="fee_group_id" id="edit_fee_group_id" required class="input-field w-full px-4 py-3">
                        <option value="">Choose a fee group</option>
                        <?php foreach ($feeGroups as $group): ?>
                            <option value="<?php echo $group['fee_group_id']; ?>">
                                <?php echo htmlspecialchars($group['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Installment Name *</label>
                    <input type="text" name="name" id="edit_name" required class="input-field w-full px-4 py-3">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Amount (₹) *</label>
                    <input type="number" step="0.01" min="0" name="amount" id="edit_amount" required class="input-field w-full px-4 py-3">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Due Date *</label>
                    <input type="date" name="due_date" id="edit_due_date" required class="input-field w-full px-4 py-3">
                </div>
            </div>
            <div class="flex gap-3 mt-6">
                <button type="submit" name="edit_installment" class="btn-primary text-white px-6 py-3 rounded-lg font-medium flex-1">
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
function editInstallment(id, classId, feeGroupId, name, amount, dueDate) {
    document.getElementById('edit_installment_id').value = id;
    document.getElementById('edit_class_id').value = classId;
    document.getElementById('edit_fee_group_id').value = feeGroupId;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_amount').value = amount;
    document.getElementById('edit_due_date').value = dueDate;
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}
</script>

<?php include '../../includes/footer.php'; ?>

