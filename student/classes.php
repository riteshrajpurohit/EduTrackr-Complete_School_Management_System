<?php
/**
 * Student - Choose Class
 * EduTrackr - School Management System
 */
require_once '../includes/functions.php';
requireRole('student');

$currentPage = 'classes';
$pageTitle = "Choose Class";

$success = '';
$error = '';

// Get student data
$student = getStudentData($_SESSION['user_id']);

// Check database connection
global $conn;
if (!isset($conn) || !$conn) {
    header('Location: ../error.php?msg=Database connection failed. Please check your database configuration.');
    exit();
}

// Handle class selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['select_class'])) {
    $classId = intval($_POST['class_id']);
    
    if (empty($classId)) {
        $error = 'Please select a class';
    } else {
        // Check if student record exists
        if (!$student || !isset($student['student_id'])) {
            // Create new student record if it doesn't exist
            $rollNo = 'STU' . str_pad($_SESSION['user_id'], 4, '0', STR_PAD_LEFT);
            $stmt = $conn->prepare("INSERT INTO students (user_id, roll_no, class_id) VALUES (?, ?, ?)");
            $stmt->bind_param("isi", $_SESSION['user_id'], $rollNo, $classId);
        } else {
            // Update existing student record
            $stmt = $conn->prepare("UPDATE students SET class_id = ? WHERE student_id = ?");
            $stmt->bind_param("ii", $classId, $student['student_id']);
        }
        
        if ($stmt->execute()) {
            $success = 'Class selected successfully!';
            // Refresh student data
            $student = getStudentData($_SESSION['user_id']);
        } else {
            $error = 'Error selecting class: ' . $conn->error;
        }
        $stmt->close();
    }
}

// Get all available classes
$classes = getAllClasses();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Choose Class</h1>
        <p class="text-gray-600">Select your class to view subjects and marks</p>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
            <?php echo $success; ?>
            <a href="dashboard.php" class="ml-4 text-green-800 hover:underline font-medium">Go to Dashboard →</a>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if ($student && isset($student['class_id']) && $student['class_id']): ?>
        <!-- Current Class -->
        <div class="card p-6 mb-8 bg-indigo-50 border-2 border-indigo-200">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800 mb-2">Current Class</h2>
                    <p class="text-2xl font-bold text-indigo-600">
                        <?php echo htmlspecialchars($student['class_name'] ?? 'N/A'); ?>
                    </p>
                </div>
                <div class="p-4 bg-indigo-100 rounded-full">
                    <svg class="w-12 h-12 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Class Selection Form -->
    <div class="card p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">
            <?php echo ($student && isset($student['class_id']) && $student['class_id']) ? 'Change Class' : 'Select Your Class'; ?>
        </h2>
        
        <?php if (count($classes) > 0): ?>
            <form method="POST" action="" class="space-y-4">
                <div>
                    <label for="class_id" class="block text-sm font-medium text-gray-700 mb-2">Available Classes</label>
                    <select id="class_id" name="class_id" required
                            class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg">
                        <option value="">Select a class</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>"
                                    <?php echo ($student && isset($student['class_id']) && $student['class_id'] == $class['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['name']); ?>
                                <?php if ($class['teacher_name']): ?>
                                    - <?php echo htmlspecialchars($class['teacher_name']); ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Select your class</p>
                </div>
                
                <button type="submit" name="select_class" class="btn-primary text-white px-6 py-3 rounded-lg font-medium">
                    <?php echo ($student && isset($student['class_id']) && $student['class_id']) ? 'Update Class' : 'Select Class'; ?>
                </button>
            </form>
        <?php else: ?>
            <div class="text-center py-8">
                <div class="text-6xl mb-4">🏫</div>
                <p class="text-gray-600">No classes available at the moment.</p>
                <p class="text-gray-500 text-sm mt-2">Please contact your administrator to create classes.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

