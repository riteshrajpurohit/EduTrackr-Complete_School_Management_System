<?php
/**
 * Error Page
 * EduTrackr - School Management System
 */
require_once 'includes/functions.php';
$pageTitle = "Error";
$hideHeader = true;
include 'includes/header.php'; 

$errorMsg = isset($_GET['msg']) ? sanitizeInput($_GET['msg']) : 'An error occurred';
?>

<div class="min-h-screen flex items-center justify-center px-4 py-12">
    <div class="max-w-md w-full text-center">
        <div class="card p-8">
            <div class="text-6xl mb-4">⚠️</div>
            <h1 class="text-2xl font-bold text-gray-800 mb-4">Oops! Something went wrong</h1>
            <p class="text-gray-600 mb-6"><?php echo $errorMsg; ?></p>
            <a href="index.php" class="btn-primary text-white px-6 py-3 rounded-lg font-medium inline-block">
                Go to Home
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

