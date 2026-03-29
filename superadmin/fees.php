<?php
/**
 * Super Admin - Fees Management (Redirect to new system)
 * EduTrackr - School Management System
 */
require_once '../includes/functions.php';
requireSuperAdmin();

// Redirect to new fees management system
header('Location: fees/index.php');
exit();

