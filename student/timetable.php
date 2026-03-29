<?php
/**
 * Student - View Timetable
 * EduTrackr - School Management System
 */
require_once '../includes/functions.php';
requireRole('student');

$currentPage = 'timetable';
$pageTitle = "My Timetable";

// Get student data
$student = getStudentData($_SESSION['user_id']);
$classId = isset($student['class_id']) ? $student['class_id'] : null;

global $conn;

// Get timetable for student's class
$timetable = null;
if ($classId) {
    $stmt = $conn->prepare("SELECT t.*, c.name as class_name
                           FROM timetables t
                           JOIN classes c ON t.class_id = c.id
                           WHERE t.class_id = ? AND t.is_active = 1
                           ORDER BY t.upload_date DESC
                           LIMIT 1");
    $stmt->bind_param("i", $classId);
    $stmt->execute();
    $result = $stmt->get_result();
    $timetable = $result->fetch_assoc();
    $stmt->close();
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">My Timetable</h1>
        <p class="text-gray-600">View and download your class timetable</p>
    </div>

    <?php if (!$classId): ?>
        <div class="card p-8 text-center">
            <div class="text-6xl mb-4">📅</div>
            <h2 class="text-2xl font-semibold text-gray-800 mb-2">No Class Assigned</h2>
            <p class="text-gray-600 mb-6">You need to choose a class first to view your timetable.</p>
            <a href="classes.php" class="btn-primary text-white px-6 py-3 rounded-lg font-medium inline-block">
                Choose a Class
            </a>
        </div>
    <?php elseif ($timetable): ?>
        <div class="card p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">
                        <?php echo htmlspecialchars($timetable['class_name']); ?>
                    </h2>
                    <p class="text-gray-600 text-sm mt-1">
                        Uploaded on <?php echo date('F d, Y', strtotime($timetable['upload_date'])); ?>
                    </p>
                </div>
                <a href="../<?php echo htmlspecialchars($timetable['file_path']); ?>" 
                   target="_blank" 
                   class="btn-primary text-white px-6 py-3 rounded-lg font-medium inline-flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Download Timetable
                </a>
            </div>
            
            <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-6 border-2 border-dashed border-gray-300 shadow-inner">
                <?php 
                $fileExtension = strtolower(pathinfo($timetable['file_path'], PATHINFO_EXTENSION));
                $isImage = in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                $isPdf = $fileExtension === 'pdf';
                ?>
                
                <?php if ($isImage): ?>
                    <!-- Display Image -->
                    <div class="text-center">
                        <img src="../<?php echo htmlspecialchars($timetable['file_path']); ?>" 
                             alt="Timetable" 
                             class="max-w-full h-auto mx-auto rounded-xl shadow-2xl transition-transform hover:scale-105"
                             style="max-height: 80vh;">
                    </div>
                <?php elseif ($isPdf): ?>
                    <!-- Display PDF -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                        <iframe src="../<?php echo htmlspecialchars($timetable['file_path']); ?>#toolbar=0" 
                                class="w-full h-screen min-h-[600px] rounded-lg" 
                                style="border: none;">
                            <p class="text-center text-gray-600 py-8">
                                Your browser does not support PDF viewing. 
                                <a href="../<?php echo htmlspecialchars($timetable['file_path']); ?>" 
                                   target="_blank" 
                                   class="text-indigo-600 hover:underline font-medium">
                                    Click here to download
                                </a>
                            </p>
                        </iframe>
                    </div>
                <?php else: ?>
                    <!-- Fallback for other file types -->
                    <div class="text-center py-12">
                        <div class="text-6xl mb-4">📄</div>
                        <p class="text-gray-600 mb-6 text-lg">Preview not available for this file type.</p>
                        <a href="../<?php echo htmlspecialchars($timetable['file_path']); ?>" 
                           target="_blank" 
                           class="btn-primary text-white px-6 py-3 rounded-lg font-medium inline-block">
                            📥 Download Timetable
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="card p-8 text-center">
            <div class="text-6xl mb-4">📋</div>
            <h2 class="text-2xl font-semibold text-gray-800 mb-2">No Timetable Available</h2>
            <p class="text-gray-600">Timetable has not been uploaded for your class yet. Please contact your administrator.</p>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

