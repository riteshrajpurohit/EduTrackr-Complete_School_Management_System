<?php
/**
 * Manage Subject Assignments
 * Super Admin - EduTrackr
 */
require_once '../includes/functions.php';
requireSuperAdmin();

$currentPage = 'subject_assignments';
$pageTitle = "Manage Subject Assignments";

$message = '';
$messageType = '';
$filterSubjectId = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : null;
$filterClassId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $conn;
    
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $subjectId = (int)$_POST['subject_id'];
            $teacherId = (int)$_POST['teacher_id'];
            $classId = (int)$_POST['class_id'];
            
            // Check if combination already exists
            $checkStmt = $conn->prepare("SELECT id FROM subject_assignments WHERE subject_id = ? AND teacher_id = ? AND class_id = ?");
            $checkStmt->bind_param("iii", $subjectId, $teacherId, $classId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows > 0) {
                $message = "This subject is already assigned to this teacher for this class!";
                $messageType = 'error';
            } else {
                $stmt = $conn->prepare("INSERT INTO subject_assignments (subject_id, teacher_id, class_id) VALUES (?, ?, ?)");
                $stmt->bind_param("iii", $subjectId, $teacherId, $classId);
                
                if ($stmt->execute()) {
                    $message = "Subject assignment created successfully!";
                    $messageType = 'success';
                } else {
                    $message = "Error creating assignment: " . $conn->error;
                    $messageType = 'error';
                }
                $stmt->close();
            }
            $checkStmt->close();
            
        } elseif ($_POST['action'] === 'delete') {
            $assignmentId = (int)$_POST['assignment_id'];
            
            // Check if assignment is used in marks
            $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM marks WHERE subject_assignment_id = ?");
            $checkStmt->bind_param("i", $assignmentId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $count = $result->fetch_assoc()['count'];
            $checkStmt->close();
            
            if ($count > 0) {
                $message = "Cannot delete assignment. It has " . $count . " mark entry/entries.";
                $messageType = 'error';
            } else {
                $stmt = $conn->prepare("DELETE FROM subject_assignments WHERE id = ?");
                $stmt->bind_param("i", $assignmentId);
                
                if ($stmt->execute()) {
                    $message = "Subject assignment deleted successfully!";
                    $messageType = 'success';
                } else {
                    $message = "Error deleting assignment: " . $conn->error;
                    $messageType = 'error';
                }
                $stmt->close();
            }
        }
    }
}

// Get all subject assignments
global $conn;
$assignments = [];
$query = "SELECT sa.*, 
          s.name as subject_name, s.code as subject_code,
          u.name as teacher_name, u.email as teacher_email,
          c.name as class_name
          FROM subject_assignments sa
          JOIN subjects s ON sa.subject_id = s.subject_id
          JOIN teachers t ON sa.teacher_id = t.teacher_id
          JOIN users u ON t.user_id = u.id
          JOIN classes c ON sa.class_id = c.id";
          
$conditions = [];
if ($filterSubjectId) {
    $conditions[] = "sa.subject_id = $filterSubjectId";
}
if ($filterClassId) {
    $conditions[] = "sa.class_id = $filterClassId";
}
if (!empty($conditions)) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}
$query .= " ORDER BY c.name, s.name, u.name";

$result = $conn->query($query);
if ($result) {
    $assignments = $result->fetch_all(MYSQLI_ASSOC);
}

// Get dropdown data
$subjects = [];
$result = $conn->query("SELECT * FROM subjects ORDER BY name");
if ($result) {
    $subjects = $result->fetch_all(MYSQLI_ASSOC);
}

$teachers = [];
$result = $conn->query("SELECT t.teacher_id, u.name, u.email 
                       FROM teachers t 
                       JOIN users u ON t.user_id = u.id 
                       WHERE u.status = 'active' AND u.role_id = 2
                       ORDER BY u.name");
if ($result) {
    $teachers = $result->fetch_all(MYSQLI_ASSOC);
}

$classes = getAllClasses();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Manage Subject Assignments</h1>
            <p class="text-gray-600">Assign subjects to teachers for specific classes</p>
        </div>
        <button onclick="openCreateModal()" class="btn-primary text-white px-6 py-3 rounded-lg font-medium">
            + Assign Subject
        </button>
    </div>

    <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card p-4 mb-6">
        <form method="GET" class="flex gap-4 items-end">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Subject</label>
                <select name="subject_id" class="input-field w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">All Subjects</option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?php echo $subject['subject_id']; ?>" <?php echo $filterSubjectId == $subject['subject_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($subject['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Class</label>
                <select name="class_id" class="input-field w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo $class['id']; ?>" <?php echo $filterClassId == $class['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="px-6 py-2 bg-gray-600 text-white rounded-lg font-medium">Filter</button>
            <?php if ($filterSubjectId || $filterClassId): ?>
                <a href="subject_assignments.php" class="px-6 py-2 border border-gray-300 rounded-lg font-medium">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Assignments Table -->
    <div class="card p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">All Subject Assignments</h2>
        <?php if (empty($assignments)): ?>
            <p class="text-gray-500 text-center py-8">No subject assignments created yet. Create your first assignment above.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Subject</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Teacher</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Class</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $assignment): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 px-4">
                                    <div class="font-medium"><?php echo htmlspecialchars($assignment['subject_name']); ?></div>
                                    <?php if ($assignment['subject_code']): ?>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($assignment['subject_code']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-medium"><?php echo htmlspecialchars($assignment['teacher_name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($assignment['teacher_email']); ?></div>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="font-medium"><?php echo htmlspecialchars($assignment['class_name']); ?></div>
                                </td>
                                <td class="py-3 px-4">
                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to remove this assignment?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Modal -->
<div id="assignmentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Assign Subject to Teacher</h2>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
                    <select name="subject_id" required class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="">Select Subject</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject['subject_id']; ?>">
                                <?php echo htmlspecialchars($subject['name']); ?>
                                <?php if ($subject['code']): ?> (<?php echo htmlspecialchars($subject['code']); ?>)<?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Teacher</label>
                    <select name="teacher_id" required class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="">Select Teacher</option>
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?php echo $teacher['teacher_id']; ?>">
                                <?php echo htmlspecialchars($teacher['name']); ?> (<?php echo htmlspecialchars($teacher['email']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Class</label>
                    <select name="class_id" required class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>">
                                <?php echo htmlspecialchars($class['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="mt-6 flex gap-4">
                <button type="submit" class="btn-primary text-white px-6 py-3 rounded-lg font-medium flex-1">
                    Assign
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
    document.getElementById('assignmentModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('assignmentModal').classList.add('hidden');
}

document.getElementById('assignmentModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php include '../includes/footer.php'; ?>

