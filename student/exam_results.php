<?php
/**
 * Student - View Exam Results
 * EduTrackr - School Management System
 */
require_once '../includes/functions.php';
requireRole('student');

$currentPage = 'exam_results';
$pageTitle = "Exam Results";

// Get student data
$student = getStudentData($_SESSION['user_id']);
if (!$student || !isset($student['student_id'])) {
    header('Location: ../error.php?msg=Student record not found. Please contact administrator.');
    exit();
}

$studentId = $student['student_id'];
$classId = isset($student['class_id']) ? $student['class_id'] : null;

global $conn;

// Get all published exams for student's class
$exams = [];
if ($classId) {
    $stmt = $conn->prepare("SELECT e.*, c.name as class_name
                           FROM exams e
                           JOIN classes c ON e.class_id = c.id
                           WHERE e.class_id = ? AND e.is_published = 1
                           ORDER BY e.created_at DESC");
    $stmt->bind_param("i", $classId);
    $stmt->execute();
    $result = $stmt->get_result();
    $exams = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Get selected exam results
$selectedExam = null;
$examResults = [];
$overallStats = null;

$examId = isset($_GET['exam_id']) ? intval($_GET['exam_id']) : null;
if ($examId && $classId) {
    // Get exam details
    $stmt = $conn->prepare("SELECT e.*, c.name as class_name
                           FROM exams e
                           JOIN classes c ON e.class_id = c.id
                           WHERE e.exam_id = ? AND e.class_id = ? AND e.is_published = 1");
    $stmt->bind_param("ii", $examId, $classId);
    $stmt->execute();
    $result = $stmt->get_result();
    $selectedExam = $result->fetch_assoc();
    $stmt->close();
    
    if ($selectedExam) {
        // Get student's marks for this exam
        $stmt = $conn->prepare("SELECT em.*, s.name as subject_name, s.code as subject_code
                               FROM exam_marks em
                               JOIN subject_assignments sa ON em.subject_assignment_id = sa.id
                               JOIN subjects s ON sa.subject_id = s.subject_id
                               WHERE em.exam_id = ? AND em.student_id = ?
                               ORDER BY s.name");
        $stmt->bind_param("ii", $examId, $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $examResults = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Calculate overall stats
        $totalMarks = 0;
        $totalMaxMarks = 0;
        foreach ($examResults as $result) {
            $totalMarks += (float)$result['marks_obtained'];
            $totalMaxMarks += (int)$result['max_marks'];
        }
        $overallPercentage = $totalMaxMarks > 0 ? round(($totalMarks / $totalMaxMarks) * 100, 2) : 0;
        $overallGrade = calculateGrade($overallPercentage, 100);
        $overallStats = [
            'total_marks' => $totalMarks,
            'total_max_marks' => $totalMaxMarks,
            'percentage' => $overallPercentage,
            'grade' => $overallGrade
        ];
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Exam Results</h1>
        <p class="text-gray-600">View your examination results</p>
    </div>

    <?php if (!$classId): ?>
        <div class="card p-8 text-center">
            <div class="text-6xl mb-4">📊</div>
            <h2 class="text-2xl font-semibold text-gray-800 mb-2">No Class Assigned</h2>
            <p class="text-gray-600 mb-6">You need to choose a class first to view exam results.</p>
            <a href="classes.php" class="btn-primary text-white px-6 py-3 rounded-lg font-medium inline-block">
                Choose a Class
            </a>
        </div>
    <?php elseif (count($exams) == 0): ?>
        <div class="card p-8 text-center">
            <div class="text-6xl mb-4">📝</div>
            <h2 class="text-2xl font-semibold text-gray-800 mb-2">No Published Exams</h2>
            <p class="text-gray-600">No exam results are available at the moment.</p>
        </div>
    <?php else: ?>
        <!-- Exam Selection -->
        <div class="card p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Select Exam</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($exams as $exam): ?>
                    <a href="?exam_id=<?php echo $exam['exam_id']; ?>" 
                       class="p-4 border-2 rounded-lg transition-all <?php echo ($selectedExam && $selectedExam['exam_id'] == $exam['exam_id']) ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300'; ?>">
                        <h3 class="font-semibold text-gray-800 mb-1"><?php echo htmlspecialchars($exam['name']); ?></h3>
                        <?php if ($exam['start_date']): ?>
                            <p class="text-sm text-gray-600">
                                <?php echo date('M d', strtotime($exam['start_date'])); ?>
                                <?php if ($exam['end_date']): ?>
                                    - <?php echo date('M d, Y', strtotime($exam['end_date'])); ?>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Exam Results -->
        <?php if ($selectedExam && count($examResults) > 0): ?>
            <!-- Overall Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="stat-card">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-gray-600 text-sm font-medium">Total Marks</p>
                        <span class="text-2xl">📊</span>
                    </div>
                    <p class="text-3xl font-bold text-indigo-600" data-target="<?php echo number_format($overallStats['total_marks'], 2); ?>">
                        0
                    </p>
                    <p class="text-sm text-gray-500 mt-1">/ <?php echo $overallStats['total_max_marks']; ?></p>
                </div>
                <div class="stat-card">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-gray-600 text-sm font-medium">Overall Percentage</p>
                        <span class="text-2xl">📈</span>
                    </div>
                    <p class="text-3xl font-bold text-green-600" data-target="<?php echo $overallStats['percentage']; ?>">0</p>
                    <div class="progress-bar mt-3">
                        <div class="progress-fill" style="width: <?php echo min($overallStats['percentage'], 100); ?>%"></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-gray-600 text-sm font-medium">Overall Grade</p>
                        <span class="text-2xl">⭐</span>
                    </div>
                    <p class="text-3xl font-bold text-purple-600"><?php echo $overallStats['grade']; ?></p>
                    <span class="badge badge-success mt-2">Excellent</span>
                </div>
                <div class="stat-card">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-gray-600 text-sm font-medium">Subjects</p>
                        <span class="text-2xl">📚</span>
                    </div>
                    <p class="text-3xl font-bold text-blue-600" data-target="<?php echo count($examResults); ?>">0</p>
                </div>
            </div>

            <!-- Detailed Results -->
            <div class="card p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">
                    <?php echo htmlspecialchars($selectedExam['name']); ?> - Detailed Results
                </h2>
                <div class="overflow-x-auto">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Marks Obtained</th>
                                <th>Max Marks</th>
                                <th>Percentage</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($examResults as $result): 
                                $percentage = ($result['marks_obtained'] / $result['max_marks']) * 100;
                                $markClass = $percentage >= 90 ? 'mark-excellent' : ($percentage >= 75 ? 'mark-good' : ($percentage >= 60 ? 'mark-average' : 'mark-poor'));
                            ?>
                                <tr>
                                    <td>
                                        <div class="font-medium text-gray-800"><?php echo htmlspecialchars($result['subject_name']); ?></div>
                                        <span class="text-sm text-gray-500">(<?php echo htmlspecialchars($result['subject_code']); ?>)</span>
                                    </td>
                                    <td class="<?php echo $markClass; ?> font-semibold"><?php echo number_format($result['marks_obtained'], 2); ?></td>
                                    <td class="text-gray-600"><?php echo $result['max_marks']; ?></td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <div class="progress-bar flex-1">
                                                <div class="progress-fill" style="width: <?php echo min($percentage, 100); ?>%"></div>
                                            </div>
                                            <span class="font-medium <?php echo $markClass; ?>"><?php echo number_format($percentage, 1); ?>%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge 
                                            <?php 
                                            $grade = $result['grade'];
                                            echo ($grade === 'A+' || $grade === 'A') ? 'badge-success' : 
                                                 (($grade === 'B' || $grade === 'C') ? 'badge-info' : 
                                                 (($grade === 'D') ? 'badge-warning' : 'badge-error'));
                                            ?>">
                                            <?php echo $grade; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php elseif ($selectedExam && count($examResults) == 0): ?>
            <div class="card p-8 text-center">
                <p class="text-gray-600">No marks have been entered for this exam yet.</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

