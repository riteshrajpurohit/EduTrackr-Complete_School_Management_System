<?php
/**
 * Manage Classes
 * Super Admin - EduTrackr
 */
require_once '../includes/functions.php';
requireSuperAdmin();

$currentPage = 'classes';
$pageTitle = "Manage Classes";

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $conn;
    
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create') {
            $name = sanitizeInput($_POST['name']);
            
            $stmt = $conn->prepare("INSERT INTO classes (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            
            if ($stmt->execute()) {
                $message = "Class created successfully!";
                $messageType = 'success';
            } else {
                $message = "Error creating class: " . $conn->error;
                $messageType = 'error';
            }
            $stmt->close();
            
        } elseif ($_POST['action'] === 'update') {
            $classId = (int)$_POST['class_id'];
            $name = sanitizeInput($_POST['name']);
            
            $stmt = $conn->prepare("UPDATE classes SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $name, $classId);
            
            if ($stmt->execute()) {
                $message = "Class updated successfully!";
                $messageType = 'success';
            } else {
                $message = "Error updating class: " . $conn->error;
                $messageType = 'error';
            }
            $stmt->close();
            
        } elseif ($_POST['action'] === 'delete') {
            $classId = (int)$_POST['class_id'];
            
            // Check if class is used in any related tables
            $usageCount = 0;
            $usageDetails = [];
            
            // Check students
            $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM students WHERE class_id = ?");
            $checkStmt->bind_param("i", $classId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $studentCount = $result->fetch_assoc()['count'];
            $checkStmt->close();
            if ($studentCount > 0) {
                $usageCount += $studentCount;
                $usageDetails[] = "$studentCount student(s)";
            }
            
            // Check subject assignments
            $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM subject_assignments WHERE class_id = ?");
            $checkStmt->bind_param("i", $classId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $subjectCount = $result->fetch_assoc()['count'];
            $checkStmt->close();
            if ($subjectCount > 0) {
                $usageCount += $subjectCount;
                $usageDetails[] = "$subjectCount subject assignment(s)";
            }
            
            // Check exams
            $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM exams WHERE class_id = ?");
            $checkStmt->bind_param("i", $classId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $examCount = $result->fetch_assoc()['count'];
            $checkStmt->close();
            if ($examCount > 0) {
                $usageCount += $examCount;
                $usageDetails[] = "$examCount exam(s)";
            }
            
            // Check fees
            $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM fees_structure WHERE class_id = ?");
            $checkStmt->bind_param("i", $classId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $feeCount = $result->fetch_assoc()['count'];
            $checkStmt->close();
            if ($feeCount > 0) {
                $usageCount += $feeCount;
                $usageDetails[] = "$feeCount fee structure(s)";
            }
            
            // Check timetables
            $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM timetables WHERE class_id = ?");
            $checkStmt->bind_param("i", $classId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $timetableCount = $result->fetch_assoc()['count'];
            $checkStmt->close();
            if ($timetableCount > 0) {
                $usageCount += $timetableCount;
                $usageDetails[] = "$timetableCount timetable(s)";
            }
            
            if ($usageCount > 0) {
                $message = "Cannot delete class. It is being used by: " . implode(", ", $usageDetails) . ".";
                $messageType = 'error';
            } else {
                $stmt = $conn->prepare("DELETE FROM classes WHERE id = ?");
                $stmt->bind_param("i", $classId);
                
                if ($stmt->execute()) {
                    $message = "Class deleted successfully!";
                    $messageType = 'success';
                } else {
                    $message = "Error deleting class: " . $conn->error;
                    $messageType = 'error';
                }
                $stmt->close();
            }
        }
    }
}

// Get all classes with usage statistics
global $conn;
$classes = [];
$result = $conn->query("SELECT c.*,
                       (SELECT COUNT(*) FROM students WHERE class_id = c.id) as student_count,
                       (SELECT COUNT(*) FROM subject_assignments WHERE class_id = c.id) as subject_count,
                       (SELECT COUNT(*) FROM exams WHERE class_id = c.id) as exam_count,
                       (SELECT COUNT(*) FROM fees_structure WHERE class_id = c.id) as fee_count,
                       (SELECT COUNT(*) FROM timetables WHERE class_id = c.id) as timetable_count
                       FROM classes c ORDER BY c.name");
if ($result) {
    $classes = $result->fetch_all(MYSQLI_ASSOC);
    // Calculate total usage
    foreach ($classes as &$class) {
        $class['total_usage'] = $class['student_count'] + $class['subject_count'] + $class['exam_count'] + $class['fee_count'] + $class['timetable_count'];
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Manage Classes / Grades</h1>
            <p class="text-gray-600">Create and manage classes (e.g., Grade 8, Grade 9)</p>
        </div>
        <button onclick="openCreateModal()" class="btn-primary text-white px-6 py-3 rounded-lg font-medium">
            + Create Class
        </button>
    </div>

    <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Classes Table -->
    <div class="card p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">All Classes</h2>
        <?php if (empty($classes)): ?>
            <div class="empty-state">
                <div class="text-6xl mb-4">🏫</div>
                <p class="text-gray-500 text-center py-8">No classes created yet. Create your first class above.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Class Name</th>
                            <th>Students</th>
                            <th>Subjects</th>
                            <th>Exams</th>
                            <th>Fees</th>
                            <th>Timetables</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classes as $class): ?>
                            <tr>
                                <td>
                                    <div class="font-semibold text-gray-800"><?php echo htmlspecialchars($class['name']); ?></div>
                                    <?php if (isset($class['description']) && $class['description']): ?>
                                        <div class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars($class['description']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo $class['student_count']; ?> student(s)
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo $class['subject_count']; ?> subject(s)
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo $class['exam_count']; ?> exam(s)
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo $class['fee_count']; ?> fee(s)
                                    </span>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo $class['timetable_count']; ?> timetable(s)
                                    </span>
                                </td>
                                <td>
                                    <div class="flex gap-2 flex-wrap">
                                        <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($class)); ?>)" 
                                                class="btn-primary text-white px-3 py-1.5 text-sm rounded-lg font-medium">
                                            ✏️ Edit
                                        </button>
                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this class? This action cannot be undone.');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                                            <button type="submit" class="px-3 py-1.5 text-sm rounded-lg font-medium bg-red-100 text-red-800 hover:bg-red-200 transition-all">
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
        <?php endif; ?>
    </div>
</div>

<!-- Create/Edit Modal -->
<div id="classModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
        <h2 id="modalTitle" class="text-2xl font-bold text-gray-800 mb-6">Create Class / Grade</h2>
        <form id="classForm" method="POST">
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="class_id" id="classId">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Class Name</label>
                    <input type="text" name="name" id="className" required
                           class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                           placeholder="e.g., Grade 8">
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
    document.getElementById('modalTitle').textContent = 'Create Class / Grade';
    document.getElementById('formAction').value = 'create';
    document.getElementById('classForm').reset();
    document.getElementById('classId').value = '';
    document.getElementById('classModal').classList.remove('hidden');
}

function openEditModal(classData) {
    document.getElementById('modalTitle').textContent = 'Edit Class / Grade';
    document.getElementById('formAction').value = 'update';
    document.getElementById('classId').value = classData.id;
    document.getElementById('className').value = classData.name;
    document.getElementById('classModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('classModal').classList.add('hidden');
}

document.getElementById('classModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php include '../includes/footer.php'; ?>

