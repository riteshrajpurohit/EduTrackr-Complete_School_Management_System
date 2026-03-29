<?php
/**
 * Teacher - Upload Marks
 * EduTrackr - School Management System
 */
require_once '../includes/functions.php';
requireRole('teacher');

$currentPage = 'marks';
$pageTitle = "Upload Marks";

$success = '';
$error = '';

// Get teacher_id
$teacherId = getTeacherId($_SESSION['user_id']);
if (!$teacherId) {
    header('Location: ../error.php?msg=Teacher record not found');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['upload_marks'])) {
        $subjectAssignmentId = intval($_POST['subject_assignment_id']);
        $marksData = $_POST['marks'];
        
        // Verify teacher owns this subject_assignment
        if (!verifyTeacherSubjectAssignment($teacherId, $subjectAssignmentId)) {
            $error = 'Unauthorized: You do not have permission to upload marks for this subject.';
        } else {
            global $conn;
            $conn->begin_transaction();
            
            try {
                foreach ($marksData as $studentId => $data) {
                    $marksObtained = floatval($data['obtained']);
                    $maxMarks = intval($data['max_marks']);
                    
                    if ($marksObtained >= 0 && $marksObtained <= $maxMarks) {
                        $grade = calculateGrade($marksObtained, $maxMarks);
                        
                        // Check if marks already exist
                        $stmt = $conn->prepare("SELECT id FROM marks WHERE student_id = ? AND subject_assignment_id = ?");
                        $stmt->bind_param("ii", $studentId, $subjectAssignmentId);
                        $stmt->execute();
                        $existing = $stmt->get_result()->fetch_assoc();
                        $stmt->close();
                        
                        if ($existing) {
                            // Update existing marks
                            $stmt = $conn->prepare("UPDATE marks SET marks_obtained = ?, max_marks = ?, grade = ?, created_by = ? WHERE id = ?");
                            $stmt->bind_param("ddsii", $marksObtained, $maxMarks, $grade, $teacherId, $existing['id']);
                        } else {
                            // Insert new marks
                            $stmt = $conn->prepare("INSERT INTO marks (student_id, subject_assignment_id, marks_obtained, max_marks, grade, created_by) VALUES (?, ?, ?, ?, ?, ?)");
                            $stmt->bind_param("iiddsi", $studentId, $subjectAssignmentId, $marksObtained, $maxMarks, $grade, $teacherId);
                        }
                        
                        $stmt->execute();
                        $stmt->close();
                    }
                }
                
                $conn->commit();
                $success = 'Marks uploaded successfully!';
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Error uploading marks: ' . $e->getMessage();
            }
        }
    }
}

// Get teacher's assigned subjects
$subjects = getTeacherSubjects($teacherId);

// Get subject assignment details and students
$selectedSubjectAssignment = null;
$students = [];
$maxMarks = 100;

if (isset($_GET['subject_assignment_id'])) {
    $subjectAssignmentId = intval($_GET['subject_assignment_id']);
    
    // Verify teacher owns this subject_assignment
    if (!verifyTeacherSubjectAssignment($teacherId, $subjectAssignmentId)) {
        $error = 'Unauthorized: You do not have permission to access this subject.';
    } else {
        global $conn;
        
        // Get subject assignment details
        $stmt = $conn->prepare("SELECT sa.*, s.name as subject_name, s.code as subject_code,
                               c.name as class_name
                               FROM subject_assignments sa
                               JOIN subjects s ON sa.subject_id = s.subject_id
                               JOIN classes c ON sa.class_id = c.id
                               WHERE sa.id = ? AND sa.teacher_id = ?");
        $stmt->bind_param("ii", $subjectAssignmentId, $teacherId);
        $stmt->execute();
        $selectedSubjectAssignment = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($selectedSubjectAssignment) {
            // Get students in this class
            $students = getStudentsByClass($selectedSubjectAssignment['class_id']);
            
            // Get existing marks
            foreach ($students as &$student) {
                $stmt = $conn->prepare("SELECT * FROM marks WHERE student_id = ? AND subject_assignment_id = ?");
                $stmt->bind_param("ii", $student['student_id'], $subjectAssignmentId);
                $stmt->execute();
                $result = $stmt->get_result();
                $student['existing_marks'] = $result->fetch_assoc();
                $stmt->close();
            }
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Upload Marks</h1>
        <p class="text-gray-600">Upload and manage student marks for your assigned subjects</p>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- Subject Selection -->
    <div class="card p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Select Subject</h2>
        <form method="GET" action="" class="flex gap-4">
            <div class="flex-1">
                <select name="subject_assignment_id" required
                        class="input-field w-full px-4 py-2 border border-gray-300 rounded-lg"
                        onchange="this.form.submit()">
                    <option value="">Select a subject</option>
                    <?php foreach ($subjects as $subject): ?>
                        <option value="<?php echo $subject['id']; ?>" 
                                <?php echo (isset($_GET['subject_assignment_id']) && $_GET['subject_assignment_id'] == $subject['id']) ? 'selected' : ''; ?>>
                            <?php 
                            echo htmlspecialchars($subject['subject_name']); 
                            if (isset($subject['class_name'])) {
                                echo ' - ' . htmlspecialchars($subject['class_name']);
                            }
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <!-- Marks Upload Form -->
    <?php if ($selectedSubjectAssignment && count($students) > 0): ?>
        <div class="card p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">
                Upload Marks - <?php echo htmlspecialchars($selectedSubjectAssignment['subject_name']); ?>
            </h2>
            <p class="text-gray-600 mb-4">
                Class: <?php echo htmlspecialchars($selectedSubjectAssignment['class_name']); ?>
            </p>
            
            <form method="POST" action="">
                <input type="hidden" name="subject_assignment_id" value="<?php echo $selectedSubjectAssignment['id']; ?>">
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Roll No</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Student Name</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Marks Obtained</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Max Marks</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr class="border-b border-gray-100">
                                    <td class="py-3 px-4"><?php echo htmlspecialchars($student['roll_no']); ?></td>
                                    <td class="py-3 px-4"><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td class="py-3 px-4">
                                        <input type="number" 
                                               name="marks[<?php echo $student['student_id']; ?>][obtained]" 
                                               value="<?php echo isset($student['existing_marks']) ? $student['existing_marks']['marks_obtained'] : ''; ?>"
                                               step="0.01" min="0"
                                               class="input-field w-24 px-3 py-2 border border-gray-300 rounded-lg">
                                    </td>
                                    <td class="py-3 px-4">
                                        <input type="number" 
                                               name="marks[<?php echo $student['student_id']; ?>][max_marks]" 
                                               value="<?php echo isset($student['existing_marks']) ? $student['existing_marks']['max_marks'] : 100; ?>"
                                               min="1"
                                               class="input-field w-24 px-3 py-2 border border-gray-300 rounded-lg">
                                    </td>
                                    <td class="py-3 px-4">
                                        <span class="px-3 py-1 rounded-full text-sm font-medium
                                            <?php 
                                            if (isset($student['existing_marks']) && $student['existing_marks']['grade']) {
                                                $grade = $student['existing_marks']['grade'];
                                                echo ($grade === 'A+' || $grade === 'A') ? 'bg-green-100 text-green-800' : 
                                                     (($grade === 'B' || $grade === 'C') ? 'bg-blue-100 text-blue-800' : 
                                                     (($grade === 'D') ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'));
                                                echo '">' . $grade;
                                            } else {
                                                echo 'text-gray-400">-';
                                            }
                                            ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-6">
                    <button type="submit" name="upload_marks" class="btn-primary text-white px-6 py-3 rounded-lg font-medium">
                        Save Marks
                    </button>
                </div>
            </form>
        </div>
    <?php elseif ($selectedSubjectAssignment && count($students) == 0): ?>
        <div class="card p-6">
            <p class="text-gray-500 text-center py-8">No students enrolled in this class assignment yet.</p>
        </div>
    <?php else: ?>
        <div class="card p-6">
            <p class="text-gray-500 text-center py-8">Please select a subject above to upload marks.</p>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
