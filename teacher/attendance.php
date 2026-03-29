<?php
/**
 * Teacher - Manage Attendance
 * EduTrackr - School Management System
 */
require_once '../includes/functions.php';
requireRole('teacher');

$currentPage = 'attendance';
$pageTitle = "Manage Attendance";

$success = '';
$error = '';

// Get teacher_id
$teacherId = getTeacherId($_SESSION['user_id']);
if (!$teacherId) {
    header('Location: ../error.php?msg=Teacher record not found');
    exit();
}

global $conn;

// Handle attendance marking/updating
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_attendance'])) {
        $classId = intval($_POST['class_id']);
        $date = $_POST['date'] ?? date('Y-m-d');
        $attendanceData = $_POST['attendance'] ?? [];
        
        // Verify teacher has access to this class
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM subject_assignments 
                               WHERE teacher_id = ? AND class_id = ?");
        $stmt->bind_param("ii", $teacherId, $classId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($result['count'] == 0) {
            $error = 'Unauthorized: You do not have access to this class.';
        } else {
            $conn->begin_transaction();
            try {
                foreach ($attendanceData as $studentId => $data) {
                    $status = $data['status'];
                    $remarks = isset($data['remarks']) ? $data['remarks'] : null;
                    
                    // Check if attendance already exists
                    $stmt = $conn->prepare("SELECT attendance_id FROM attendance 
                                           WHERE student_id = ? AND date = ? AND class_id = ?");
                    $stmt->bind_param("isi", $studentId, $date, $classId);
                    $stmt->execute();
                    $existing = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    
                    if ($existing) {
                        // Update existing
                        $stmt = $conn->prepare("UPDATE attendance SET status = ?, remarks = ?, marked_by = ? 
                                               WHERE attendance_id = ?");
                        $stmt->bind_param("ssii", $status, $remarks, $teacherId, $existing['attendance_id']);
                    } else {
                        // Insert new
                        $stmt = $conn->prepare("INSERT INTO attendance (student_id, class_id, date, status, remarks, marked_by) 
                                               VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("iisssi", $studentId, $classId, $date, $status, $remarks, $teacherId);
                    }
                    $stmt->execute();
                    $stmt->close();
                }
                $conn->commit();
                $success = 'Attendance marked successfully!';
            } catch (Exception $e) {
                $conn->rollback();
                $error = 'Error marking attendance: ' . $e->getMessage();
            }
        }
    }
}

// Get teacher's assigned classes
$teacherClasses = getTeacherClasses($teacherId);

// Get selected class and students
$selectedClass = null;
$students = [];
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$selectedClassId = isset($_GET['class_id']) ? intval($_GET['class_id']) : null;

if ($selectedClassId) {
    // Verify access
    $stmt = $conn->prepare("SELECT c.id, c.name as class_name
                           FROM classes c
                           WHERE c.id = ? AND EXISTS (
                               SELECT 1 FROM subject_assignments sa 
                               WHERE sa.class_id = c.id AND sa.teacher_id = ?
                           )");
    $stmt->bind_param("ii", $selectedClassId, $teacherId);
    $stmt->execute();
    $selectedClass = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($selectedClass) {
        // Get students in this class
        $stmt = $conn->prepare("SELECT s.*, u.name as student_name, u.email
                               FROM students s
                               JOIN users u ON s.user_id = u.id
                               WHERE s.class_id = ?
                               ORDER BY s.roll_no");
        $stmt->bind_param("i", $selectedClassId);
        $stmt->execute();
        $result = $stmt->get_result();
        $students = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        // Get existing attendance for the selected date
        $existingAttendance = [];
        $stmt = $conn->prepare("SELECT student_id, status, remarks FROM attendance 
                               WHERE class_id = ? AND date = ?");
        $stmt->bind_param("is", $selectedClassId, $selectedDate);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $existingAttendance[$row['student_id']] = $row;
        }
        $stmt->close();
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Manage Attendance</h1>
        <p class="text-gray-600">Mark and manage student attendance</p>
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

    <!-- Class Selection -->
    <div class="card p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Select Class</h2>
        <form method="GET" action="" class="flex gap-4 items-end">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">Class</label>
                <select name="class_id" required class="input-field w-full px-4 py-3" onchange="this.form.submit()">
                    <option value="">Select a class</option>
                    <?php foreach ($teacherClasses as $class): ?>
                        <option value="<?php echo $class['id']; ?>" 
                                <?php echo ($selectedClassId == $class['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($class['class_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Date</label>
                <input type="date" name="date" value="<?php echo htmlspecialchars($selectedDate); ?>" 
                       class="input-field px-4 py-3" onchange="this.form.submit()">
            </div>
        </form>
    </div>

    <!-- Attendance Form -->
    <?php if ($selectedClass && count($students) > 0): ?>
        <div class="card p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">
                Attendance for <?php echo htmlspecialchars($selectedClass['class_name']); ?>
                <span class="text-sm font-normal text-gray-600">(<?php echo date('F d, Y', strtotime($selectedDate)); ?>)</span>
            </h2>
            
            <form method="POST" action="">
                <input type="hidden" name="class_id" value="<?php echo $selectedClassId; ?>">
                <input type="hidden" name="date" value="<?php echo htmlspecialchars($selectedDate); ?>">
                
                <div class="overflow-x-auto mb-4">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Roll No</th>
                                <th>Student Name</th>
                                <th>Status</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): 
                                $existing = $existingAttendance[$student['student_id']] ?? null;
                                $currentStatus = $existing ? $existing['status'] : 'present';
                            ?>
                                <tr>
                                    <td class="font-semibold"><?php echo htmlspecialchars($student['roll_no']); ?></td>
                                    <td>
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-full flex items-center justify-center text-white font-bold">
                                                <?php echo strtoupper(substr($student['student_name'], 0, 1)); ?>
                                            </div>
                                            <span class="font-medium text-gray-800"><?php echo htmlspecialchars($student['student_name']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <select name="attendance[<?php echo $student['student_id']; ?>][status]" 
                                                class="input-field px-3 py-2 rounded-lg border-2" required
                                                style="border-color: <?php 
                                                    echo $currentStatus === 'present' ? '#2ecc71' : 
                                                        ($currentStatus === 'absent' ? '#e74c3c' : 
                                                        ($currentStatus === 'late' ? '#f1c40f' : '#95a5a6')); 
                                                ?>">
                                            <option value="present" <?php echo ($currentStatus === 'present') ? 'selected' : ''; ?>>✅ Present</option>
                                            <option value="absent" <?php echo ($currentStatus === 'absent') ? 'selected' : ''; ?>>❌ Absent</option>
                                            <option value="late" <?php echo ($currentStatus === 'late') ? 'selected' : ''; ?>>⏰ Late</option>
                                            <option value="excused" <?php echo ($currentStatus === 'excused') ? 'selected' : ''; ?>>📝 Excused</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="attendance[<?php echo $student['student_id']; ?>][remarks]" 
                                               value="<?php echo htmlspecialchars($existing['remarks'] ?? ''); ?>"
                                               placeholder="Optional remarks" class="input-field px-3 py-2 w-full">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <button type="submit" name="mark_attendance" class="btn-primary text-white px-6 py-3 rounded-lg font-medium">
                    Save Attendance
                </button>
            </form>
        </div>
    <?php elseif ($selectedClassId && count($students) == 0): ?>
        <div class="card p-8 text-center">
            <p class="text-gray-600">No students found in this class.</p>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

