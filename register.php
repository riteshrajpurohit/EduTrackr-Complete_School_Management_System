<?php
/**
 * Registration Page
 * EduTrackr - School Management System
 */

// Error reporting (disabled in production)
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Start output buffering to catch any errors
if (!ob_get_level()) {
    ob_start();
}

// Include functions
if (!file_exists('includes/functions.php')) {
    die('Error: includes/functions.php not found. Please check your file structure.');
}

require_once 'includes/functions.php';

// Start session
if (function_exists('startSession')) {
    startSession();
} else {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Redirect if already logged in
if (isLoggedIn()) {
    if (hasRole('teacher')) {
        header('Location: teacher/dashboard.php');
        exit();
    } elseif (hasRole('student')) {
        header('Location: student/dashboard.php');
        exit();
    }
}

$error = '';
$success = '';
$role = isset($_GET['role']) ? sanitizeInput($_GET['role']) : '';
$selectedRole = '';

// Show error from session if exists
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $name = isset($_POST['name']) ? trim($_POST['name']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        $selectedRole = isset($_POST['role']) ? trim($_POST['role']) : '';
        $contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
        $rollNo = isset($_POST['roll_no']) ? trim($_POST['roll_no']) : '';
        
        // Basic validation
        if (empty($name)) {
            $error = 'Please enter your full name';
        } elseif (empty($email)) {
            $error = 'Please enter your email address';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address';
        } elseif (empty($password)) {
            $error = 'Please enter a password';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match';
        } elseif (empty($selectedRole) || !in_array($selectedRole, ['teacher', 'student'])) {
            $error = 'Please select a role (Teacher or Student)';
        } else {
            // Sanitize inputs
            $name = sanitizeInput($name);
            $email = sanitizeInput($email);
            $contact = sanitizeInput($contact);
            $rollNo = sanitizeInput($rollNo);
            
            // Ensure database connection
            global $conn;
            if (!isset($conn) || !$conn) {
                require_once 'includes/db.php';
                global $conn;
            }
            
            // Check database connection
            if (!isset($conn) || !$conn) {
                $dbError = isset($GLOBALS['db_error']) ? $GLOBALS['db_error'] : 'Connection failed';
                $error = 'Database connection error: ' . htmlspecialchars($dbError) . '. Please check your database configuration.';
            } else {
                // Check if email already exists
                $checkEmail = $conn->prepare("SELECT id FROM users WHERE email = ?");
                if ($checkEmail) {
                    $checkEmail->bind_param("s", $email);
                    $checkEmail->execute();
                    $result = $checkEmail->get_result();
                    
                    if ($result->num_rows > 0) {
                        $error = 'This email is already registered. Please use a different email or <a href="login.php">login here</a>.';
                        $checkEmail->close();
                    } else {
                        $checkEmail->close();
                        
                        // Hash password
                        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                        if (!$hashedPassword) {
                            $error = 'Error: Password hashing failed. Please try again.';
                        } else {
                            // Start transaction
                            $conn->autocommit(false);
                            
                            try {
                                // Map role to role_id
                                $roleIdMap = ['teacher' => 2, 'student' => 3];
                                $roleId = isset($roleIdMap[$selectedRole]) ? $roleIdMap[$selectedRole] : null;
                                
                                // Insert user with both role and role_id
                                // Set status to 'active' so users can login immediately
                                if ($roleId) {
                                    $insertUser = $conn->prepare("INSERT INTO users (name, email, password, role, role_id, status) VALUES (?, ?, ?, ?, ?, 'active')");
                                    if (!$insertUser) {
                                        throw new Exception("Failed to prepare user insert: " . $conn->error);
                                    }
                                    // Fix: selectedRole is a string ('teacher' or 'student'), roleId is integer
                                    $insertUser->bind_param("ssssi", $name, $email, $hashedPassword, $selectedRole, $roleId);
                                } else {
                                    $insertUser = $conn->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, 'active')");
                                    if (!$insertUser) {
                                        throw new Exception("Failed to prepare user insert: " . $conn->error);
                                    }
                                    $insertUser->bind_param("ssss", $name, $email, $hashedPassword, $selectedRole);
                                }
                                
                                if (!$insertUser->execute()) {
                                    throw new Exception("Failed to insert user: " . $insertUser->error);
                                }
                                
                                $userId = $conn->insert_id;
                                $insertUser->close();
                                
                                if (!$userId) {
                                    throw new Exception("Failed to get user ID after insertion");
                                }
                                
                                // Create role-specific record
                                if ($selectedRole === 'student') {
                                    // Generate roll number if not provided
                                    if (empty($rollNo)) {
                                        $rollNo = 'STU' . str_pad($userId, 4, '0', STR_PAD_LEFT);
                                    }
                                    
                                    $insertStudent = $conn->prepare("INSERT INTO students (user_id, roll_no, contact) VALUES (?, ?, ?)");
                                    if (!$insertStudent) {
                                        throw new Exception("Failed to prepare student insert: " . $conn->error);
                                    }
                                    
                                    $insertStudent->bind_param("iss", $userId, $rollNo, $contact);
                                    
                                    if (!$insertStudent->execute()) {
                                        throw new Exception("Failed to insert student record: " . $insertStudent->error);
                                    }
                                    
                                    $insertStudent->close();
                                    
                                } elseif ($selectedRole === 'teacher') {
                                    $insertTeacher = $conn->prepare("INSERT INTO teachers (user_id, contact) VALUES (?, ?)");
                                    if (!$insertTeacher) {
                                        throw new Exception("Failed to prepare teacher insert: " . $conn->error);
                                    }
                                    
                                    $insertTeacher->bind_param("is", $userId, $contact);
                                    
                                    if (!$insertTeacher->execute()) {
                                        throw new Exception("Failed to insert teacher record: " . $insertTeacher->error);
                                    }
                                    
                                    $insertTeacher->close();
                                }
                                
                                // Commit transaction
                                if (!$conn->commit()) {
                                    throw new Exception("Transaction commit failed: " . $conn->error);
                                }
                                
                                // Success!
                                $success = 'Registration successful! You can now login.';
                                
                                // Clear form data
                                $name = $email = $contact = $rollNo = '';
                                $selectedRole = '';
                                
                                // Reset autocommit
                                $conn->autocommit(true);
                                
                            } catch (Exception $e) {
                                // Rollback on error
                                $conn->rollback();
                                $conn->autocommit(true);
                                $error = 'Registration failed: ' . htmlspecialchars($e->getMessage());
                            }
                        }
                    }
                } else {
                    $error = 'Database error: Failed to prepare email check query. ' . $conn->error;
                }
            }
        }
    } catch (Exception $e) {
        $error = 'An error occurred: ' . htmlspecialchars($e->getMessage());
    }
}

// Clear output buffer and get any errors
ob_end_clean();

$pageTitle = "Register";
$hideHeader = true;
include 'includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center px-4 py-12" style="background: #f5f5f5;">
    <div class="max-w-md w-full">
        <!-- School Logo/Title Banner -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center mb-4">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-blue-800 rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                    </svg>
                </div>
            </div>
            <h1 class="text-4xl font-bold mb-2" style="color: #3f51b5;">EduTrackr</h1>
            <p class="text-gray-600 text-lg">School Management System</p>
        </div>

        <!-- Register Card -->
        <div class="card p-8">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-2">Create Account</h2>
                <p class="text-gray-600">Sign up to get started</p>
            </div>

            <?php if ($success): ?>
                <div class="bg-green-50 border-2 border-green-300 px-4 py-3 rounded-lg mb-6" style="border-color: #2ecc71;">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20" style="color: #2ecc71;">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span class="font-medium" style="color: #2ecc71;"><?php echo $success; ?></span>
                    </div>
                    <div class="mt-3">
                        <a href="login.php" class="font-medium underline" style="color: #2ecc71;">Go to Login Page →</a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-50 border-2 border-red-300 px-4 py-3 rounded-lg mb-6" style="border-color: #e74c3c;">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" style="color: #e74c3c;">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="font-medium" style="color: #e74c3c;"><?php echo $error; ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-6" id="registerForm" novalidate>
                <!-- Role Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">I am a <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer transition-colors <?php echo (($role === 'teacher' || $selectedRole === 'teacher')) ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300'; ?>">
                            <input type="radio" name="role" value="teacher" class="mr-2" <?php echo (($role === 'teacher' || $selectedRole === 'teacher')) ? 'checked' : ''; ?> required>
                            <span class="font-medium">Teacher</span>
                        </label>
                        <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer transition-colors <?php echo (($role === 'student' || $selectedRole === 'student')) ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300'; ?>">
                            <input type="radio" name="role" value="student" class="mr-2" <?php echo (($role === 'student' || $selectedRole === 'student')) ? 'checked' : ''; ?> required>
                            <span class="font-medium">Student</span>
                        </label>
                    </div>
                </div>

                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name" required
                           class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="Enter your full name"
                           value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address <span class="text-red-500">*</span></label>
                    <input type="email" id="email" name="email" required
                           class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="your.email@example.com"
                           value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                </div>

                <!-- Contact -->
                <div>
                    <label for="contact" class="block text-sm font-medium text-gray-700 mb-2">Contact Number (Optional)</label>
                    <input type="text" id="contact" name="contact"
                           class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="+1234567890"
                           value="<?php echo isset($contact) ? htmlspecialchars($contact) : ''; ?>">
                </div>

                <!-- Roll Number (Students only) -->
                <div id="rollNoField" style="display: <?php echo ($selectedRole === 'student' || $role === 'student') ? 'block' : 'none'; ?>;">
                    <label for="roll_no" class="block text-sm font-medium text-gray-700 mb-2">Roll Number (Optional)</label>
                    <input type="text" id="roll_no" name="roll_no"
                           class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="Auto-generated if not provided"
                           value="<?php echo isset($rollNo) ? htmlspecialchars($rollNo) : ''; ?>">
                    <p class="text-xs text-gray-500 mt-1">Leave blank to auto-generate</p>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password <span class="text-red-500">*</span></label>
                    <input type="password" id="password" name="password" required minlength="6"
                           class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="Minimum 6 characters">
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password <span class="text-red-500">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6"
                           class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="Re-enter your password">
                </div>

                <!-- Submit Button -->
                <button type="submit" id="submitBtn" class="btn-primary w-full text-white py-3 rounded-lg font-medium">
                    Create Account
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-gray-600">
                    Already have an account? 
                    <a href="login.php" class="text-indigo-600 hover:text-indigo-800 font-medium">Sign in</a>
                </p>
            </div>

            <!-- Google OAuth Button -->
            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-white text-gray-500">Or continue with</span>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="auth/google.php?action=register<?php echo $role ? '&role=' . urlencode($role) : ''; ?>" class="w-full flex items-center justify-center px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        <span class="font-medium text-gray-700">Sign up with Google</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const roleInputs = document.querySelectorAll('input[name="role"]');
    const rollNoField = document.getElementById('rollNoField');
    const form = document.getElementById('registerForm');
    const submitBtn = document.getElementById('submitBtn');
    
    // Show/hide roll number field
    roleInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.value === 'student') {
                rollNoField.style.display = 'block';
            } else {
                rollNoField.style.display = 'none';
                const rollNoInput = document.getElementById('roll_no');
                if (rollNoInput) rollNoInput.value = '';
            }
        });
    });
    
    // Form submission handler
    if (form) {
        form.addEventListener('submit', function(e) {
            // Let HTML5 validation work first
            if (!form.checkValidity()) {
                e.preventDefault();
                form.reportValidity();
                return false;
            }
            
            // Disable button to prevent double submission
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Processing...';
                submitBtn.style.opacity = '0.7';
                submitBtn.style.cursor = 'not-allowed';
            }
            
            // Form will submit normally
            return true;
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>

