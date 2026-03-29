<?php
/**
 * Database Connection File
 * EduTrackr - School Management System
 */

// Database configuration
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
}
if (!defined('DB_USER')) {
    define('DB_USER', 'root');
}
if (!defined('DB_PASS')) {
    define('DB_PASS', 'root'); // Default MAMP password
}
if (!defined('DB_NAME')) {
    define('DB_NAME', 'edutrackr');
}

// Create connection only if not already created
if (!isset($conn)) {
    // Suppress warnings for connection attempt
    $conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        // Store error before setting to null
        $GLOBALS['db_error'] = $conn->connect_error;
        $conn = null;
    } elseif ($conn) {
        // Set charset to utf8mb4
        if (!$conn->set_charset("utf8mb4")) {
            // If charset setting fails, continue anyway
            error_log("Warning: Could not set charset to utf8mb4");
        }
    } else {
        $GLOBALS['db_error'] = 'Failed to create database connection. Please check your database configuration.';
    }
}

