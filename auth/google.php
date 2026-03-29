<?php
/**
 * Google OAuth Initiation
 * EduTrackr - School Management System
 */
require_once '../includes/functions.php';
startSession();

// Get role from query parameter (for registration flow)
$role = isset($_GET['role']) ? sanitizeInput($_GET['role']) : '';
$action = isset($_GET['action']) ? sanitizeInput($_GET['action']) : 'login'; // login or register

// Store role and action in session for callback
$_SESSION['google_oauth_role'] = $role;
$_SESSION['google_oauth_action'] = $action;

// Generate state token for CSRF protection
$state = bin2hex(random_bytes(16));
$_SESSION['google_oauth_state'] = $state;

// Load Google OAuth configuration
if (!file_exists('../includes/google_config.php')) {
    header('Location: ../error.php?msg=Google OAuth configuration file not found');
    exit();
}

require_once '../includes/google_config.php';

// Validate Google OAuth credentials
if (empty(GOOGLE_CLIENT_ID) || empty(GOOGLE_CLIENT_SECRET)) {
    header('Location: ../error.php?msg=Google OAuth credentials are not configured');
    exit();
}

// Redirect to Google OAuth
$authUrl = getGoogleAuthUrl($state);
header('Location: ' . $authUrl);
exit();

