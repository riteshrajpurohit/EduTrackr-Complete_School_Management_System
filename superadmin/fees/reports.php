<?php
/**
 * Super Admin - Fee Reports
 * EduTrackr - School Management System
 */
require_once '../../includes/functions.php';
requireSuperAdmin();

$currentPage = 'fees';
$pageTitle = "Fee Reports";

global $conn;

// Get filter parameters
$selectedClassId = isset($_GET['class_id']) ? intval($_GET['class_id']) : null;

// Get classes for filter
$classes = getAllClasses();

// Get reports data
$classReports = [];
$result = $conn->query("SELECT c.id, c.name as class_name,
                       COUNT(DISTINCT s.student_id) as total_students,
                       COUNT(DISTINCT sf.student_fee_id) as total_fees,
                       COALESCE(SUM(sf.amount), 0) as total_due,
                       COALESCE(SUM(CASE WHEN fp.status = 'Paid' THEN fp.amount_paid ELSE 0 END), 0) as total_collected,
                       COALESCE(SUM(sf.amount), 0) - COALESCE(SUM(CASE WHEN fp.status = 'Paid' THEN fp.amount_paid ELSE 0 END), 0) as total_pending
                       FROM classes c
                       LEFT JOIN students s ON c.id = s.class_id
                       LEFT JOIN student_fees sf ON s.student_id = sf.student_id
                       LEFT JOIN fee_payments fp ON sf.student_fee_id = fp.student_fee_id
                       " . ($selectedClassId ? "WHERE c.id = $selectedClassId" : "") . "
                       GROUP BY c.id, c.name
                       ORDER BY c.name");
if ($result) {
    $classReports = $result->fetch_all(MYSQLI_ASSOC);
}

// Overall statistics
$overallStats = [];
$result = $conn->query("SELECT 
                       COUNT(DISTINCT sf.student_fee_id) as total_fees,
                       COALESCE(SUM(sf.amount), 0) as total_due,
                       COALESCE(SUM(CASE WHEN fp.status = 'Paid' THEN fp.amount_paid ELSE 0 END), 0) as total_collected,
                       COUNT(DISTINCT CASE WHEN sf.status = 'Pending' THEN sf.student_fee_id END) as pending_count,
                       COUNT(DISTINCT CASE WHEN sf.status = 'Paid' THEN sf.student_fee_id END) as paid_count
                       FROM student_fees sf
                       LEFT JOIN fee_payments fp ON sf.student_fee_id = fp.student_fee_id");
if ($result) {
    $overallStats = $result->fetch_assoc();
}

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Fee Reports</h1>
                <p class="text-gray-600">View comprehensive fee collection and pending reports</p>
            </div>
            <a href="index.php" class="btn-primary text-white px-6 py-3 rounded-lg font-medium">
                ← Back to Fees
            </a>
        </div>
    </div>

    <!-- Overall Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="card stat-card p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-gray-500 text-sm mb-2 font-medium uppercase tracking-wide">Total Fees</p>
                    <p class="text-3xl font-bold text-indigo-600"><?php echo number_format($overallStats['total_fees'] ?? 0); ?></p>
                </div>
                <div class="p-4 bg-gradient-to-br from-indigo-100 to-indigo-50 rounded-xl shadow-sm">
                    <svg class="w-10 h-10 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="card stat-card p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-gray-500 text-sm mb-2 font-medium uppercase tracking-wide">Total Due</p>
                    <p class="text-3xl font-bold text-red-600">₹<?php echo number_format($overallStats['total_due'] ?? 0, 2); ?></p>
                </div>
                <div class="p-4 bg-gradient-to-br from-red-100 to-red-50 rounded-xl shadow-sm">
                    <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="card stat-card p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-gray-500 text-sm mb-2 font-medium uppercase tracking-wide">Total Collected</p>
                    <p class="text-3xl font-bold text-green-600">₹<?php echo number_format($overallStats['total_collected'] ?? 0, 2); ?></p>
                </div>
                <div class="p-4 bg-gradient-to-br from-green-100 to-green-50 rounded-xl shadow-sm">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="card stat-card p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-gray-500 text-sm mb-2 font-medium uppercase tracking-wide">Pending Fees</p>
                    <p class="text-3xl font-bold text-orange-600"><?php echo number_format($overallStats['pending_count'] ?? 0); ?></p>
                </div>
                <div class="p-4 bg-gradient-to-br from-orange-100 to-orange-50 rounded-xl shadow-sm">
                    <svg class="w-10 h-10 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Filter by Class</h2>
        <form method="GET" action="" class="flex gap-4">
            <select name="class_id" class="input-field px-4 py-3" onchange="this.form.submit()">
                <option value="">All Classes</option>
                <?php foreach ($classes as $class): ?>
                    <option value="<?php echo $class['id']; ?>" <?php echo $selectedClassId == $class['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($class['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <a href="reports.php" class="px-4 py-3 rounded-lg font-medium border border-gray-300 hover:bg-gray-50">
                Clear Filter
            </a>
        </form>
    </div>

    <!-- Class-wise Reports -->
    <div class="card p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Class-wise Fee Report</h2>
        
        <?php if (count($classReports) > 0): ?>
            <div class="overflow-x-auto">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th>Total Students</th>
                            <th>Total Fees</th>
                            <th>Total Due</th>
                            <th>Total Collected</th>
                            <th>Total Pending</th>
                            <th>Collection %</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classReports as $report): 
                            $collectionPercent = $report['total_due'] > 0 ? 
                                ($report['total_collected'] / $report['total_due']) * 100 : 0;
                        ?>
                            <tr>
                                <td>
                                    <span class="badge badge-info font-semibold"><?php echo htmlspecialchars($report['class_name']); ?></span>
                                </td>
                                <td><?php echo $report['total_students']; ?></td>
                                <td><?php echo $report['total_fees']; ?></td>
                                <td class="font-bold text-red-600">₹<?php echo number_format($report['total_due'], 2); ?></td>
                                <td class="font-bold text-green-600">₹<?php echo number_format($report['total_collected'], 2); ?></td>
                                <td class="font-bold text-orange-600">₹<?php echo number_format($report['total_pending'], 2); ?></td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <div class="progress-bar" style="width: 100px;">
                                            <div class="progress-fill" style="width: <?php echo min($collectionPercent, 100); ?>%"></div>
                                        </div>
                                        <span class="text-sm font-semibold"><?php echo number_format($collectionPercent, 1); ?>%</span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-gray-600 text-center py-8">No data available.</p>
        <?php endif; ?>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

