<?php
/**
 * Landing Page - Redirects to Login
 * EduTrackr - School Management System
 */
require_once 'includes/functions.php';
startSession();

// Always redirect to login page first
// If user is already logged in, login.php will handle the redirect to appropriate dashboard
header('Location: login.php');
exit();

