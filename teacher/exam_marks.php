<?php
/**
 * Teacher - Add Exam Marks
 * EduTrackr - School Management System
 */
require_once '../includes/functions.php';
requireRole('teacher');

$currentPage = 'exam_marks';
$pageTitle = "Add Exam Marks";

$success = '';
$error = '';

// Get teacher_id
$teacherId = getTeacherId($_SESSION['user_id']);
if (!$teacherId) {
    header('Location: ../error.php?msg=Teacher record not found');
    exit();
}

global $conn;

// Get exam_id from URL
$examId = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : 0;

// Handle marks submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_marks'])) {
    $examId = intval($_POST['exam_id']);
    $subjectAssignmentId = intval($_POST['subject_assignment_id']);
    $marksData = $_POST['marks'] ?? [];
    
    // Verify teacher owns this subject_assignment
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM subject_assignments 
                           WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $subjectAssignmentId, $teacherId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($result['count'] == 0) {
        $error = 'Unauthorized: You do not have permission to add marks for this subject.';
    } else {
        $conn->begin_transaction();
        try {
            foreach ($marksData as $studentId => $data) {
                $marksObtained = floatval($data['obtained'] ?? 0);
                $maxMarks = intval($data['max_marks'] ?? 100);
                
                if ($marksObtained >= 0 && $marksObtained <= $maxMarks) {
                    $grade = calculateGrade($marksObtained, $maxMarks);
                    
                    // Check if marks already exist
                    $stmt = $conn->prepare("SELECT id FROM exam_marks 
                                           WHERE exam_id = ? AND student_id = ? AND subject_assignment_id = ?");
                    $stmt->bind_param("iii", $examId, $studentId, $subjectAssignmentId);
                    $stmt->execute();
                    $existing = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    
                    if ($existing) {
                        // Update existing marks
                        $stmt = $conn->prepare("UPDATE exam_marks SET marks_obtained = ?, max_marks = ?, grade = ?, created_by = ? 
                                               WHERE id = ?");
                        $stmt->bind_param("ddsii", $marksObtained, $maxMarks, $grade, $teacherId, $existing['id']);
                    } else {
                        // Insert new marks
                        $stmt = $conn->prepare("INSERT INTO exam_marks (exam_id, student_id, subject_assignment_id, marks_obtained, max_marks, grade, created_by) 
                                               VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("iiiddsi", $examId, $studentId, $subjectAssignmentId, $marksObtained, $maxMarks, $grade, $teacherId);
                    }
                    $stmt->execute();
                    $stmt->close();
                }
            }
            $conn->commit();
            $success = 'Exam marks saved successfully!';
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Error saving marks: ' . $e->getMessage();
        }
    }
}

// Get exam details
$exam = null;
if ($examId > 0) {
    $stmt = $conn->prepare("SELECT e.*, c.name as class_name
                           FROM exams e
                           JOIN classes c ON e.class_id = c.id
                           WHERE e.exam_id = ?");
    $stmt->bind_param("i", $examId);
    $stmt->execute();
    $result = $stmt->get_result();
    $exam = $result->fetch_assoc();
    $stmt->close();
}

// Get teacher's subjects for this exam's class
$teacherSubjects = [];
$selectedSubjectAssignment = null;
$students = [];
$existingMarks = [];

if ($exam) {
    // Get teacher's subjects for this class
    $stmt = $conn->prepare("SELECT sa.*, s.name as subject_name, s.code as subject_code
                           FROM subject_assignments sa
                           JOIN subjects s ON sa.subject_id = s.subject_id
                           WHERE sa.teacher_id = ? AND sa.class_id = ?
                           ORDER BY s.name");
    $stmt->bind_param("ii", $teacherId, $exam['class_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $teacherSubjects = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Get selected subject
    $subjectAssignmentId = isset($_GET['subject_assignment_id']) ? intval($_GET['subject_assignment_id']) : null;
    if ($subjectAssignmentId) {
        foreach ($teacherSubjects as $subj) {
            if ($subj['id'] == $subjectAssignmentId) {
                $selectedSubjectAssignment = $subj;
                break;
            }
        }
        
        if ($selectedSubjectAssignment) {
            // Get students in this class
            $stmt = $conn->prepare("SELECT s.*, u.name as student_name
                                   FROM students s
                                   JOIN users u ON s.user_id = u.id
                                   WHERE s.class_id = ?
                                   ORDER BY s.roll_no");
            $stmt->bind_param("i", $exam['class_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $students = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            // Get existing marks
            $stmt = $conn->prepare("SELECT student_id, marks_obtained, max_marks, grade 
                                   FROM exam_marks 
                                   WHERE exam_id = ? AND subject_assignment_id = ?");
            $stmt->bind_param("ii", $examId, $subjectAssignmentId);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $existingMarks[$row['student_id']] = $row;
            }
            $stmt->close();
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Add Exam Marks</h1>
        <p class="text-gray-600">Enter marks for students in your subjects</p>
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

    <?php if (!$exam): ?>
        <div class="card p-8 text-center">
            <div class="text-6xl mb-4">📝</div>
            <h2 class="text-2xl font-semibold text-gray-800 mb-2">No Exam Selected</h2>
            <p class="text-gray-600">Please select an exam from the admin panel to add marks.</p>
        </div>
    <?php else: ?>
        <!-- Exam Info -->
        <div class="card p-6 mb-8 bg-indigo-50 border-2 border-indigo-200">
            <h2 class="text-xl font-semibold text-gray-800 mb-2"><?php echo htmlspecialchars($exam['name']); ?></h2>
            <p class="text-gray-700">
                <span class="font-medium">Class:</span> <?php echo htmlspecialchars($exam['class_name']); ?>
                <?php if ($exam['start_date']): ?>
                    <span class="mx-2">•</span>
                    <span class="font-medium">Dates:</span> <?php echo date('M d', strtotime($exam['start_date'])); ?>
                    <?php if ($exam['end_date']): ?>
                        - <?php echo date('M d, Y', strtotime($exam['end_date'])); ?>
                    <?php endif; ?>
                <?php endif; ?>
            </p>
        </div>

        <!-- Subject Selection -->
        <?php if (count($teacherSubjects) > 0): ?>
            <div class="card p-6 mb-8">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Select Subject</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <?php foreach ($teacherSubjects as $subject): ?>
                        <a href="?exam_id=<?php echo $examId; ?>&subject_assignment_id=<?php echo $subject['id']; ?>" 
                           class="p-4 border-2 rounded-lg transition-all <?php echo ($selectedSubjectAssignment && $selectedSubjectAssignment['id'] == $subject['id']) ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300'; ?>">
                            <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($subject['subject_name']); ?></h3>
                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($subject['subject_code']); ?></p>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="card p-8 text-center">
                <p class="text-gray-600">You don't have any subjects assigned to this class.</p>
            </div>
        <?php endif; ?>

        <!-- Marks Entry Form -->
        <?php if ($selectedSubjectAssignment && count($students) > 0): ?>
            <div class="card p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">
                    Enter Marks: <?php echo htmlspecialchars($selectedSubjectAssignment['subject_name']); ?>
                </h2>
                
                <form method="POST" action="">
                    <input type="hidden" name="exam_id" value="<?php echo $examId; ?>">
                    <input type="hidden" name="subject_assignment_id" value="<?php echo $subjectAssignmentId; ?>">
                    
                    <div class="overflow-x-auto mb-4">
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
                                <?php foreach ($students as $student): 
                                    $existing = $existingMarks[$student['student_id']] ?? null;
                                    $marksObtained = $existing ? $existing['marks_obtained'] : '';
                                    $maxMarks = $existing ? $existing['max_marks'] : 100;
                                    $grade = $existing ? $existing['grade'] : '';
                                ?>
                                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                                        <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($student['roll_no']); ?></td>
                                        <td class="py-3 px-4"><?php echo htmlspecialchars($student['student_name']); ?></td>
                                        <td class="py-3 px-4">
                                            <input type="number" step="0.01" min="0" 
                                                   name="marks[<?php echo $student['student_id']; ?>][obtained]" 
                                                   value="<?php echo $marksObtained; ?>"
                                                   class="input-field px-3 py-2 w-24" required>
                                        </td>
                                        <td class="py-3 px-4">
                                            <input type="number" min="1" 
                                                   name="marks[<?php echo $student['student_id']; ?>][max_marks]" 
                                                   value="<?php echo $maxMarks; ?>"
                                                   class="input-field px-3 py-2 w-24" required>
                                        </td>
                                        <td class="py-3 px-4">
                                            <?php if ($grade): ?>
                                                <span class="px-3 py-1 rounded-full text-sm font-medium
                                                    <?php 
                                                    echo ($grade === 'A+' || $grade === 'A') ? 'bg-green-100 text-green-800' : 
                                                         (($grade === 'B' || $grade === 'C') ? 'bg-blue-100 text-blue-800' : 
                                                         (($grade === 'D') ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'));
                                                    ?>">
                                                    <?php echo $grade; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-gray-400">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <button type="submit" name="save_marks" class="btn-primary text-white px-6 py-3 rounded-lg font-medium">
                        Save Marks
                    </button>
                </form>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

