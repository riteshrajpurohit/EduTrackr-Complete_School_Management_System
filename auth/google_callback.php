<?php
/**
 * Google OAuth Callback
 * EduTrackr - School Management System
 */
require_once '../includes/functions.php';
startSession();

$error = '';

// Check for errors from Google
if (isset($_GET['error'])) {
    $error = 'Google OAuth error: ' . htmlspecialchars($_GET['error']);
    header('Location: ../error.php?msg=' . urlencode($error));
    exit();
}

// Verify state token
if (!isset($_GET['state']) || !isset($_SESSION['google_oauth_state']) || $_GET['state'] !== $_SESSION['google_oauth_state']) {
    header('Location: ../error.php?msg=Invalid state parameter');
    exit();
}

// Get authorization code
if (!isset($_GET['code'])) {
    header('Location: ../error.php?msg=Authorization code not received');
    exit();
}

$code = $_GET['code'];
$role = isset($_SESSION['google_oauth_role']) ? $_SESSION['google_oauth_role'] : '';
$action = isset($_SESSION['google_oauth_action']) ? $_SESSION['google_oauth_action'] : 'login';

// Clear state
unset($_SESSION['google_oauth_state']);

require_once '../includes/google_config.php';

// Validate Google OAuth configuration
if (empty(GOOGLE_CLIENT_ID) || empty(GOOGLE_CLIENT_SECRET)) {
    header('Location: ../error.php?msg=Google OAuth is not properly configured');
    exit();
}

// Exchange code for access token
$tokenData = getGoogleAccessToken($code);

if (!$tokenData || !isset($tokenData['access_token'])) {
    header('Location: ../error.php?msg=Failed to get access token from Google');
    exit();
}

// Get user info from Google
$userInfo = getGoogleUserInfo($tokenData['access_token']);

if (!$userInfo || !isset($userInfo['email'])) {
    header('Location: ../error.php?msg=Failed to get user information from Google');
    exit();
}

global $conn;

// Check if user exists (check both role_id and role for backward compatibility)
$stmt = $conn->prepare("SELECT id, name, email, password, role_id, role, status FROM users WHERE email = ?");
$stmt->bind_param("s", $userInfo['email']);
$stmt->execute();
$result = $stmt->get_result();
$existingUser = $result->fetch_assoc();
$stmt->close();

if ($action === 'register') {
    // Registration flow
    if ($existingUser) {
        // User already exists
        if ($existingUser['status'] === 'pending') {
            $_SESSION['error'] = 'Your account is pending approval. Please wait for admin approval.';
            header('Location: ../login.php');
        } else {
            $_SESSION['error'] = 'Email already registered. Please login instead.';
            header('Location: ../login.php');
        }
        exit();
    }
    
    // Create new user with status='pending' and role_id=NULL (to be assigned by super admin)
    $name = $userInfo['name'] ?? $userInfo['email'];
    $email = $userInfo['email'];
    $hashedPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT); // Random password for OAuth users
    
    $conn->begin_transaction();
    
    try {
        // Insert user with status='pending' and no role_id (super admin will assign)
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role_id, status) VALUES (?, ?, ?, NULL, 'pending')");
        $stmt->bind_param("sss", $name, $email, $hashedPassword);
        $stmt->execute();
        $userId = $conn->insert_id;
        $stmt->close();
        
        $conn->commit();
        
        // Inform user they need approval
        $_SESSION['error'] = 'Your account has been created and is pending approval. Please wait for the administrator to approve your account.';
        header('Location: ../login.php');
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        header('Location: ../error.php?msg=Registration failed: ' . urlencode($e->getMessage()));
        exit();
    }
    
} else {
    // Login flow
    if (!$existingUser) {
        // User doesn't exist, redirect to registration
        $_SESSION['error'] = 'Account not found. Please register first.';
        header('Location: ../register.php');
        exit();
    }
    
    // Check if account is pending
    if ($existingUser['status'] === 'pending') {
        $_SESSION['error'] = 'Your account is pending approval. Please wait for administrator approval.';
        header('Location: ../login.php');
        exit();
    }
    
    // Check if account is disabled
    if ($existingUser['status'] === 'disabled') {
        $_SESSION['error'] = 'Your account has been disabled. Please contact administrator.';
        header('Location: ../login.php');
        exit();
    }
    
    // Set session variables
    $_SESSION['user_id'] = $existingUser['id'];
    $_SESSION['name'] = $existingUser['name'];
    $_SESSION['email'] = $existingUser['email'];
    
    // Set both role_id and role for backward compatibility
    if (isset($existingUser['role_id'])) {
        $_SESSION['role_id'] = $existingUser['role_id'];
    }
    if (isset($existingUser['role'])) {
        $_SESSION['role'] = $existingUser['role'];
    }
    
    // Redirect based on role_id or role
    $redirectRole = isset($existingUser['role_id']) ? $existingUser['role_id'] : $existingUser['role'];
    
    if ($redirectRole == 1 || $redirectRole === 'admin' || $redirectRole === 'super_admin') {
        header('Location: ../superadmin/dashboard.php');
    } elseif ($redirectRole == 2 || $redirectRole === 'teacher') {
        header('Location: ../teacher/dashboard.php');
    } elseif ($redirectRole == 3 || $redirectRole === 'student') {
        header('Location: ../student/dashboard.php');
    } else {
        header('Location: ../error.php?msg=Invalid user role');
    }
    exit();
}

