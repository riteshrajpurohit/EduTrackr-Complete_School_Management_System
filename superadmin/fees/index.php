<?php
/**
 * Super Admin - Fees Management Dashboard
 * EduTrackr - School Management System
 */
require_once '../../includes/functions.php';
requireSuperAdmin();

$currentPage = 'fees';
$pageTitle = "Fees Management";

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Fees Management</h1>
        <p class="text-gray-600">Manage fee groups, installments, extra fees, and payments</p>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <?php
        global $conn;
        
        // Total Fee Groups
        $result = $conn->query("SELECT COUNT(*) as count FROM fee_groups");
        $feeGroupsCount = $result->fetch_assoc()['count'];
        
        // Total Installments
        $result = $conn->query("SELECT COUNT(*) as count FROM fee_installments");
        $installmentsCount = $result->fetch_assoc()['count'];
        
        // Total Pending Fees
        $result = $conn->query("SELECT COUNT(*) as count FROM student_fees WHERE status = 'Pending'");
        $pendingCount = $result->fetch_assoc()['count'];
        
        // Total Collected
        $result = $conn->query("SELECT COALESCE(SUM(amount_paid), 0) as total FROM fee_payments WHERE status = 'Paid'");
        $totalCollected = $result->fetch_assoc()['total'];
        ?>
        
        <div class="card stat-card p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-gray-500 text-sm mb-2 font-medium uppercase tracking-wide">Fee Groups</p>
                    <p class="text-3xl font-bold text-indigo-600"><?php echo $feeGroupsCount; ?></p>
                </div>
                <div class="p-4 bg-gradient-to-br from-indigo-100 to-indigo-50 rounded-xl shadow-sm">
                    <svg class="w-10 h-10 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="card stat-card p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-gray-500 text-sm mb-2 font-medium uppercase tracking-wide">Installments</p>
                    <p class="text-3xl font-bold text-blue-600"><?php echo $installmentsCount; ?></p>
                </div>
                <div class="p-4 bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl shadow-sm">
                    <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="card stat-card p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-gray-500 text-sm mb-2 font-medium uppercase tracking-wide">Pending Fees</p>
                    <p class="text-3xl font-bold text-orange-600"><?php echo $pendingCount; ?></p>
                </div>
                <div class="p-4 bg-gradient-to-br from-orange-100 to-orange-50 rounded-xl shadow-sm">
                    <svg class="w-10 h-10 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
            </div>
        </div>
        
        <div class="card stat-card p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1">
                    <p class="text-gray-500 text-sm mb-2 font-medium uppercase tracking-wide">Total Collected</p>
                    <p class="text-3xl font-bold text-green-600">₹<?php echo number_format($totalCollected, 2); ?></p>
                </div>
                <div class="p-4 bg-gradient-to-br from-green-100 to-green-50 rounded-xl shadow-sm">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Management Links -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <a href="manage_groups.php" class="card p-6 hover:shadow-lg transition-all">
            <div class="flex items-center space-x-4">
                <div class="p-4 bg-indigo-100 rounded-xl">
                    <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Manage Fee Groups</h3>
                    <p class="text-sm text-gray-600">Create and manage fee categories</p>
                </div>
            </div>
        </a>
        
        <a href="manage_installments.php" class="card p-6 hover:shadow-lg transition-all">
            <div class="flex items-center space-x-4">
                <div class="p-4 bg-blue-100 rounded-xl">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Manage Installments</h3>
                    <p class="text-sm text-gray-600">Set up class-based installments</p>
                </div>
            </div>
        </a>
        
        <a href="manage_extra_fees.php" class="card p-6 hover:shadow-lg transition-all">
            <div class="flex items-center space-x-4">
                <div class="p-4 bg-purple-100 rounded-xl">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Manage Extra Fees</h3>
                    <p class="text-sm text-gray-600">Add extra fees per class or student</p>
                </div>
            </div>
        </a>
        
        <a href="student_ledger.php" class="card p-6 hover:shadow-lg transition-all">
            <div class="flex items-center space-x-4">
                <div class="p-4 bg-green-100 rounded-xl">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Student Fee Ledger</h3>
                    <p class="text-sm text-gray-600">View and manage student fees</p>
                </div>
            </div>
        </a>
        
        <a href="payments.php" class="card p-6 hover:shadow-lg transition-all">
            <div class="flex items-center space-x-4">
                <div class="p-4 bg-yellow-100 rounded-xl">
                    <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Record Payments</h3>
                    <p class="text-sm text-gray-600">Mark fees as paid or partial</p>
                </div>
            </div>
        </a>
        
        <a href="reports.php" class="card p-6 hover:shadow-lg transition-all">
            <div class="flex items-center space-x-4">
                <div class="p-4 bg-red-100 rounded-xl">
                    <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Fee Reports</h3>
                    <p class="text-sm text-gray-600">View comprehensive fee reports</p>
                </div>
            </div>
        </a>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

