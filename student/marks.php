<?php
/**
 * Student - My Marks
 * EduTrackr - School Management System
 */
require_once '../includes/functions.php';
requireRole('student');

$currentPage = 'marks';
$pageTitle = "My Marks";

// Get student data
$student = getStudentData($_SESSION['user_id']);

// Check if student record exists
if (!$student || !isset($student['student_id'])) {
    header('Location: ../error.php?msg=Student record not found. Please contact administrator.');
    exit();
}

// Get all marks
$allMarks = [];
$studentId = $student['student_id'];
$allMarks = getStudentMarks($studentId);

// Calculate statistics
$totalMarks = 0;
$totalMaxMarks = 0;
foreach ($allMarks as $mark) {
    $totalMarks += (float)$mark['marks_obtained'];
    $totalMaxMarks += (int)$mark['max_marks'];
}
$overallPercentage = $totalMaxMarks > 0 ? ($totalMarks / $totalMaxMarks) * 100 : 0;

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">My Marks</h1>
        <p class="text-gray-600">View your academic performance</p>
    </div>

    <?php if (!$student): ?>
        <div class="card p-8 text-center">
            <p class="text-gray-600">Student profile not found.</p>
        </div>
    <?php elseif (count($allMarks) > 0): ?>
        <!-- Overall Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="card stat-card p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-gray-500 text-sm mb-2 font-medium uppercase tracking-wide">Total Subjects</p>
                        <p class="text-4xl font-bold text-indigo-600 counter" data-target="<?php echo count($allMarks); ?>">0</p>
                    </div>
                    <div class="p-4 bg-gradient-to-br from-indigo-100 to-indigo-50 rounded-xl shadow-sm">
                        <svg class="w-10 h-10 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="card stat-card p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-gray-500 text-sm mb-2 font-medium uppercase tracking-wide">Overall Percentage</p>
                        <p class="text-4xl font-bold text-emerald-600 counter" data-target="<?php echo round($overallPercentage); ?>">0</p>
                        <div class="mt-3">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo min($overallPercentage, 100); ?>%"></div>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 bg-gradient-to-br from-emerald-100 to-emerald-50 rounded-xl shadow-sm">
                        <svg class="w-10 h-10 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="card stat-card p-6">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-gray-500 text-sm mb-2 font-medium uppercase tracking-wide">Total Marks</p>
                        <p class="text-3xl font-bold text-purple-600"><?php echo number_format($totalMarks, 2); ?></p>
                        <p class="text-xs text-gray-400 mt-1">out of <?php echo $totalMaxMarks; ?></p>
                    </div>
                    <div class="p-4 bg-gradient-to-br from-purple-100 to-purple-50 rounded-xl shadow-sm">
                        <svg class="w-10 h-10 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Marks Table -->
        <div class="card p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-6">Detailed Marks</h2>
            <div class="overflow-x-auto">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Marks Obtained</th>
                            <th>Max Marks</th>
                            <th>Percentage</th>
                            <th>Grade</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allMarks as $mark): 
                            $percentage = ($mark['marks_obtained'] / $mark['max_marks']) * 100;
                            $markClass = $percentage >= 90 ? 'mark-excellent' : ($percentage >= 75 ? 'mark-good' : ($percentage >= 60 ? 'mark-average' : 'mark-poor'));
                        ?>
                            <tr>
                                <td class="font-semibold text-gray-800"><?php echo htmlspecialchars($mark['subject_name']); ?></td>
                                <td class="<?php echo $markClass; ?>"><?php echo number_format($mark['marks_obtained'], 2); ?></td>
                                <td class="text-gray-600"><?php echo $mark['max_marks']; ?></td>
                                <td>
                                    <div class="flex items-center space-x-3">
                                        <div class="progress-bar" style="width: 100px;">
                                            <div class="progress-fill" style="width: <?php echo min($percentage, 100); ?>%"></div>
                                        </div>
                                        <span class="font-semibold <?php echo $markClass; ?>" style="min-width: 50px;"><?php echo number_format($percentage, 1); ?>%</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?php 
                                        echo $percentage >= 90 ? 'badge-success' : 
                                            ($percentage >= 75 ? 'badge-info' : 
                                            ($percentage >= 60 ? 'badge-warning' : 'badge-error')); 
                                    ?>">
                                        <?php echo htmlspecialchars($mark['grade'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                                <td class="text-sm text-gray-500">
                                    <?php echo $mark['updated_at'] ? date('M d, Y', strtotime($mark['updated_at'])) : 'N/A'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Chart Section -->
        <div class="card p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Performance Chart</h2>
            <canvas id="marksChart" height="80"></canvas>
        </div>

        <script>
            // Create chart using Chart.js
            const ctx = document.getElementById('marksChart').getContext('2d');
            const marksData = <?php echo json_encode(array_map(function($mark) {
                return [
                    'subject' => $mark['subject_name'],
                    'percentage' => ($mark['marks_obtained'] / $mark['max_marks']) * 100
                ];
            }, $allMarks)); ?>;

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: marksData.map(item => item.subject),
                    datasets: [{
                        label: 'Percentage (%)',
                        data: marksData.map(item => item.percentage),
                        backgroundColor: 'rgba(79, 70, 229, 0.6)',
                        borderColor: 'rgba(79, 70, 229, 1)',
                        borderWidth: 2,
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        </script>
    <?php else: ?>
        <div class="card p-8 text-center">
            <div class="text-6xl mb-4">📊</div>
            <h2 class="text-2xl font-semibold text-gray-800 mb-2">No Marks Recorded</h2>
            <p class="text-gray-600">Your marks will appear here once your teachers upload them.</p>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>

