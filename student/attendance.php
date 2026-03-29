<?php
/**
 * Student - Mark Attendance
 * EduTrackr - School Management System
 */
require_once '../includes/functions.php';
requireRole('student');

$currentPage = 'attendance';
$pageTitle = "Mark Attendance";

$success = '';
$error = '';

// Get student data
$student = getStudentData($_SESSION['user_id']);
if (!$student || !isset($student['student_id'])) {
    header('Location: ../error.php?msg=Student record not found. Please contact administrator.');
    exit();
}

$studentId = $student['student_id'];
$classId = isset($student['class_id']) ? $student['class_id'] : null;

// Check if class is assigned
if (!$classId) {
    $error = 'Please select a class first. <a href="classes.php" class="text-indigo-600 hover:underline">Choose Class</a>';
}

// Handle attendance marking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    if (!$classId) {
        $error = 'Class required to mark attendance.';
    } else {
        $date = date('Y-m-d');
        $status = isset($_POST['status']) ? $_POST['status'] : 'present';
        
        global $conn;
        
        // Check if already marked for today
        $stmt = $conn->prepare("SELECT attendance_id FROM attendance WHERE student_id = ? AND date = ? AND class_id = ?");
        $stmt->bind_param("isi", $studentId, $date, $classId);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($existing) {
            // Update existing attendance
            $stmt = $conn->prepare("UPDATE attendance SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE attendance_id = ?");
            $stmt->bind_param("si", $status, $existing['attendance_id']);
        } else {
            // Insert new attendance
            $stmt = $conn->prepare("INSERT INTO attendance (student_id, class_id, date, status) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $studentId, $classId, $date, $status);
        }
        
        if ($stmt->execute()) {
            $success = 'Attendance marked successfully for today!';
        } else {
            $error = 'Error marking attendance: ' . $conn->error;
        }
        $stmt->close();
    }
}

// Get today's attendance status
$todayAttendance = null;
$today = date('Y-m-d');
if ($classId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE student_id = ? AND date = ? AND class_id = ?");
    $stmt->bind_param("isi", $studentId, $today, $classId);
    $stmt->execute();
    $result = $stmt->get_result();
    $todayAttendance = $result->fetch_assoc();
    $stmt->close();
}

// Get attendance history (last 30 days)
$attendanceHistory = [];
if ($classId) {
    global $conn;
    $stmt = $conn->prepare("SELECT date, status, remarks FROM attendance 
                           WHERE student_id = ? AND class_id = ? 
                           AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                           ORDER BY date DESC");
    $stmt->bind_param("ii", $studentId, $classId);
    $stmt->execute();
    $result = $stmt->get_result();
    $attendanceHistory = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Calculate attendance stats
$totalDays = 0;
$presentDays = 0;
$absentDays = 0;
$lateDays = 0;
foreach ($attendanceHistory as $record) {
    $totalDays++;
    if ($record['status'] === 'present') {
        $presentDays++;
    } elseif ($record['status'] === 'absent') {
        $absentDays++;
    } elseif ($record['status'] === 'late') {
        $lateDays++;
    }
}
$attendancePercentage = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Mark Attendance</h1>
        <p class="text-gray-600">Mark your daily attendance</p>
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

    <?php if (!$classId): ?>
        <div class="card p-8 text-center">
            <div class="text-6xl mb-4">📅</div>
            <h2 class="text-2xl font-semibold text-gray-800 mb-2">No Class Assigned</h2>
            <p class="text-gray-600 mb-6">You need to choose a class first to mark attendance.</p>
            <a href="classes.php" class="btn-primary text-white px-6 py-3 rounded-lg font-medium inline-block">
                Choose a Class
            </a>
        </div>
    <?php else: ?>
        <!-- Attendance Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm mb-1">Total Days</p>
                        <p class="text-3xl font-bold text-indigo-600"><?php echo $totalDays; ?></p>
                    </div>
                    <div class="p-3 bg-indigo-100 rounded-full">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm mb-1">Present</p>
                        <p class="text-3xl font-bold text-green-600"><?php echo $presentDays; ?></p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-full">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm mb-1">Absent</p>
                        <p class="text-3xl font-bold text-red-600"><?php echo $absentDays; ?></p>
                    </div>
                    <div class="p-3 bg-red-100 rounded-full">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="card p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm mb-1">Percentage</p>
                        <p class="text-3xl font-bold text-purple-600"><?php echo $attendancePercentage; ?>%</p>
                    </div>
                    <div class="p-3 bg-purple-100 rounded-full">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mark Today's Attendance -->
        <div class="card p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Today's Attendance - <?php echo date('F d, Y'); ?></h2>
            
            <?php if ($todayAttendance): ?>
                <div class="bg-blue-50 border-2 border-blue-200 px-4 py-3 rounded-lg mb-4">
                    <p class="font-medium text-blue-800">
                        You have already marked attendance for today as: 
                        <span class="uppercase font-bold"><?php echo htmlspecialchars($todayAttendance['status']); ?></span>
                    </p>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Attendance Status</label>
                    <select name="status" required class="input-field w-full px-4 py-3">
                        <option value="present" <?php echo ($todayAttendance && $todayAttendance['status'] === 'present') ? 'selected' : ''; ?>>Present</option>
                        <option value="absent" <?php echo ($todayAttendance && $todayAttendance['status'] === 'absent') ? 'selected' : ''; ?>>Absent</option>
                        <option value="late" <?php echo ($todayAttendance && $todayAttendance['status'] === 'late') ? 'selected' : ''; ?>>Late</option>
                        <option value="excused" <?php echo ($todayAttendance && $todayAttendance['status'] === 'excused') ? 'selected' : ''; ?>>Excused</option>
                    </select>
                </div>
                
                <button type="submit" name="mark_attendance" class="btn-primary text-white px-6 py-3 rounded-lg font-medium">
                    <?php echo $todayAttendance ? 'Update Attendance' : 'Mark Attendance'; ?>
                </button>
            </form>
        </div>

        <!-- Attendance History -->
        <?php if (count($attendanceHistory) > 0): ?>
            <div class="card p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Attendance History (Last 30 Days)</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Date</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-700">Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendanceHistory as $record): 
                                $statusColors = [
                                    'present' => 'bg-green-100 text-green-800',
                                    'absent' => 'bg-red-100 text-red-800',
                                    'late' => 'bg-yellow-100 text-yellow-800',
                                    'excused' => 'bg-blue-100 text-blue-800'
                                ];
                                $statusColor = $statusColors[$record['status']] ?? 'bg-gray-100 text-gray-800';
                            ?>
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="py-3 px-4"><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                    <td class="py-3 px-4">
                                        <span class="px-3 py-1 rounded-full text-sm font-medium <?php echo $statusColor; ?>">
                                            <?php echo strtoupper($record['status']); ?>
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-gray-600"><?php echo htmlspecialchars($record['remarks'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

