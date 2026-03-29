<?php
/**
 * Logout Page
 * EduTrackr - School Management System
 */
require_once 'includes/functions.php';
startSession();

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Redirect to home page
header('Location: index.php');
exit();

