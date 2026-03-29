<?php
/**
 * Super Admin - Manage Exams
 * EduTrackr - School Management System
 */
require_once '../includes/functions.php';
requireSuperAdmin();

$currentPage = 'exams';
$pageTitle = "Manage Exams";

$success = '';
$error = '';

global $conn;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_exam'])) {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $classId = intval($_POST['class_id'] ?? 0);
        $startDate = $_POST['start_date'] ?? null;
        $endDate = $_POST['end_date'] ?? null;
        
        if (empty($name) || $classId == 0) {
            $error = 'Exam name and class are required.';
        } else {
            $stmt = $conn->prepare("INSERT INTO exams (name, description, class_id, start_date, end_date, created_by) 
                                   VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssissi", $name, $description, $classId, $startDate, $endDate, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $success = 'Exam created successfully!';
            } else {
                $error = 'Error creating exam: ' . $conn->error;
            }
            $stmt->close();
        }
    } elseif (isset($_POST['toggle_publish'])) {
        $examId = intval($_POST['exam_id']);
        $isPublished = intval($_POST['is_published']);
        
        $stmt = $conn->prepare("UPDATE exams SET is_published = ? WHERE exam_id = ?");
        $stmt->bind_param("ii", $isPublished, $examId);
        $stmt->execute();
        $stmt->close();
        $success = 'Exam publication status updated.';
    } elseif (isset($_POST['delete_exam'])) {
        $examId = intval($_POST['exam_id']);
        $stmt = $conn->prepare("DELETE FROM exams WHERE exam_id = ?");
        $stmt->bind_param("i", $examId);
        $stmt->execute();
        $stmt->close();
        $success = 'Exam deleted successfully.';
    }
}

// Get all exams
$exams = [];
$result = $conn->query("SELECT e.*, c.name as class_name,
                        u.name as created_by_name,
                        (SELECT COUNT(*) FROM exam_marks WHERE exam_id = e.exam_id) as marks_count
                        FROM exams e
                        JOIN classes c ON e.class_id = c.id
                        LEFT JOIN users u ON e.created_by = u.id
                        ORDER BY e.created_at DESC");
if ($result) {
    $exams = $result->fetch_all(MYSQLI_ASSOC);
}

// Get all classes
$classes = getAllClasses();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Manage Exams</h1>
        <p class="text-gray-600">Create and manage examinations for classes</p>
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

    <!-- Create Exam Form -->
    <div class="card p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Create New Exam</h2>
        <form method="POST" action="" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Exam Name *</label>
                    <input type="text" name="name" required class="input-field w-full px-4 py-3" 
                           placeholder="e.g., Mid-Term Exam, Final Exam">
                </div>
                
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
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" rows="3" class="input-field w-full px-4 py-3" 
                          placeholder="Enter exam description (optional)"></textarea>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                    <input type="date" name="start_date" class="input-field w-full px-4 py-3">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                    <input type="date" name="end_date" class="input-field w-full px-4 py-3">
                </div>
            </div>
            
            <button type="submit" name="create_exam" class="btn-primary text-white px-6 py-3 rounded-lg font-medium">
                Create Exam
            </button>
        </form>
    </div>

    <!-- Exams List -->
    <div class="card p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">All Exams</h2>
        
        <?php if (count($exams) > 0): ?>
            <div class="overflow-x-auto">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Exam Name</th>
                            <th>Class</th>
                            <th>Dates</th>
                            <th>Marks Entered</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exams as $exam): ?>
                            <tr>
                                <td>
                                    <div class="font-semibold text-gray-800"><?php echo htmlspecialchars($exam['name']); ?></div>
                                    <?php if ($exam['description']): ?>
                                        <div class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars($exam['description']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?php echo htmlspecialchars($exam['class_name']); ?></span>
                                </td>
                                <td class="text-sm">
                                    <?php if ($exam['start_date']): ?>
                                        <div class="text-gray-700">📅 <?php echo date('M d, Y', strtotime($exam['start_date'])); ?></div>
                                    <?php endif; ?>
                                    <?php if ($exam['end_date']): ?>
                                        <div class="text-gray-500 text-xs mt-1">→ <?php echo date('M d, Y', strtotime($exam['end_date'])); ?></div>
                                    <?php endif; ?>
                                    <?php if (!$exam['start_date'] && !$exam['end_date']): ?>
                                        <span class="text-gray-400">Not set</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge badge-info">
                                        <?php echo $exam['marks_count']; ?> entries
                                    </span>
                                </td>
                                <td>
                                    <?php if ($exam['is_published']): ?>
                                        <span class="badge badge-success">Published</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 rounded text-sm font-medium bg-yellow-100 text-yellow-800">Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-sm text-gray-600">
                                    <?php echo htmlspecialchars($exam['created_by_name']); ?>
                                    <div class="text-xs text-gray-500 mt-1">
                                        <?php echo date('M d, Y', strtotime($exam['created_at'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex gap-2 flex-wrap">
                                        <a href="exam_marks.php?exam_id=<?php echo $exam['exam_id']; ?>" 
                                           class="btn-primary text-white px-3 py-1.5 text-sm rounded-lg font-medium">
                                            📊 View Marks
                                        </a>
                                        <form method="POST" action="" class="inline">
                                            <input type="hidden" name="exam_id" value="<?php echo $exam['exam_id']; ?>">
                                            <input type="hidden" name="is_published" value="<?php echo $exam['is_published'] ? 0 : 1; ?>">
                                            <button type="submit" name="toggle_publish" 
                                                    class="px-3 py-1.5 text-sm rounded-lg font-medium transition-all <?php echo $exam['is_published'] ? 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200' : 'bg-green-100 text-green-800 hover:bg-green-200'; ?>">
                                                <?php echo $exam['is_published'] ? '📤 Unpublish' : '✅ Publish'; ?>
                                            </button>
                                        </form>
                                        <form method="POST" action="" class="inline" 
                                              onsubmit="return confirm('Are you sure you want to delete this exam? All marks will be deleted.');">
                                            <input type="hidden" name="exam_id" value="<?php echo $exam['exam_id']; ?>">
                                            <button type="submit" name="delete_exam" 
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
            <p class="text-gray-600 text-center py-8">No exams created yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

