<?php
/**
 * Login Page
 * EduTrackr - School Management System
 */
require_once 'includes/functions.php';
startSession();

// Redirect if already logged in
if (isLoggedIn()) {
    startSession();
    $roleId = isset($_SESSION['role_id']) ? $_SESSION['role_id'] : null;
    $role = isset($_SESSION['role']) ? $_SESSION['role'] : null;
    
    if ($roleId == 1 || $role === 'admin' || $role === 'super_admin') {
        header('Location: superadmin/dashboard.php');
    } elseif ($roleId == 2 || $role === 'teacher') {
        header('Location: teacher/dashboard.php');
    } elseif ($roleId == 3 || $role === 'student') {
        header('Location: student/dashboard.php');
    }
    exit();
}

$error = '';
$role = isset($_GET['role']) ? sanitizeInput($_GET['role']) : '';
$selectedRole = isset($_POST['role']) ? sanitizeInput($_POST['role']) : '';

// Show error from session if exists
if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $selectedRole = sanitizeInput($_POST['role']);
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        global $conn;
        
        // Map role string to role_id for query
        $roleMap = ['teacher' => 2, 'student' => 3, 'admin' => 1, 'super_admin' => 1];
        $roleId = isset($roleMap[$selectedRole]) ? $roleMap[$selectedRole] : null;
        
        // Check database connection
        if (!isset($conn) || !$conn) {
            $error = 'Database connection failed. Please contact administrator.';
        } else {
            // Query with role_id if available, fallback to role string
            // Allow login for active users only (registration now sets status to 'active')
            if ($roleId) {
                $stmt = $conn->prepare("SELECT id, name, email, password, role_id, role, status FROM users WHERE email = ? AND (role_id = ? OR role = ?) AND status = 'active'");
                if ($stmt) {
                    $stmt->bind_param("sis", $email, $roleId, $selectedRole);
                } else {
                    $error = 'Database error. Please try again.';
                }
            } else {
                $stmt = $conn->prepare("SELECT id, name, email, password, role_id, role, status FROM users WHERE email = ? AND role = ? AND status = 'active'");
                if ($stmt) {
                    $stmt->bind_param("ss", $email, $selectedRole);
                } else {
                    $error = 'Database error. Please try again.';
                }
            }
        }
        
        if (isset($stmt) && $stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    
                    // Set both role_id and role for backward compatibility
                    if (isset($user['role_id'])) {
                        $_SESSION['role_id'] = $user['role_id'];
                    }
                    if (isset($user['role'])) {
                        $_SESSION['role'] = $user['role'];
                    }
                    
                    // Redirect based on role_id or role
                    $redirectRole = isset($user['role_id']) ? $user['role_id'] : (isset($user['role']) ? $user['role'] : '');
                    
                    if ($redirectRole == 1 || $redirectRole === 'admin' || $redirectRole === 'super_admin') {
                        header('Location: superadmin/dashboard.php');
                    } elseif ($redirectRole == 2 || $redirectRole === 'teacher') {
                        header('Location: teacher/dashboard.php');
                    } elseif ($redirectRole == 3 || $redirectRole === 'student') {
                        header('Location: student/dashboard.php');
                    } else {
                        header('Location: login.php');
                    }
                    exit();
                } else {
                    $error = 'Invalid email or password. Please try again.';
                }
            } else {
                // Check if user exists but status is not active
                $checkStmt = $conn->prepare("SELECT status FROM users WHERE email = ?");
                if ($checkStmt) {
                    $checkStmt->bind_param("s", $email);
                    $checkStmt->execute();
                    $checkResult = $checkStmt->get_result();
                    if ($checkResult->num_rows === 1) {
                        $checkUser = $checkResult->fetch_assoc();
                        if ($checkUser['status'] === 'pending') {
                            $error = 'Your account is pending approval. Please wait for administrator approval.';
                        } elseif ($checkUser['status'] === 'disabled') {
                            $error = 'Your account has been disabled. Please contact administrator.';
                        } else {
                            $error = 'Invalid email or password. Please try again.';
                        }
                    } else {
                        $error = 'Invalid email or password. Please try again.';
                    }
                    $checkStmt->close();
                } else {
                    $error = 'Invalid email or password. Please try again.';
                }
            }
            if (isset($stmt)) {
                $stmt->close();
            }
        } elseif (!isset($error) || empty($error)) {
            $error = 'Database error. Please try again later.';
        }
    }
}
?>
<?php 
$pageTitle = "Login";
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

        <!-- Login Card -->
        <div class="card p-8">
            <div class="text-center mb-8">
                <h2 class="text-2xl font-semibold text-gray-800 mb-2">Welcome Back</h2>
                <p class="text-gray-600">Sign in to your account</p>
            </div>

            <?php if ($error): ?>
                <div class="bg-red-50 border-2 border-red-300 text-red-800 px-4 py-3 rounded-lg mb-6" style="color: #e74c3c; border-color: #e74c3c;">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 mr-2 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20" style="color: #e74c3c;">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="font-medium" style="color: #e74c3c;"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-6">
                <!-- Role Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">I am a</label>
                    <div class="grid grid-cols-3 gap-4">
                        <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:border-indigo-500 transition-colors <?php echo ($role === 'admin' || $role === 'super_admin' || $selectedRole === 'admin' || $selectedRole === 'super_admin') ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'; ?>">
                            <input type="radio" name="role" value="admin" class="mr-2" <?php echo ($role === 'admin' || $role === 'super_admin' || (isset($selectedRole) && ($selectedRole === 'admin' || $selectedRole === 'super_admin'))) ? 'checked' : ''; ?>>
                            <span class="font-medium">Admin</span>
                        </label>
                        <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:border-indigo-500 transition-colors <?php echo ($role === 'teacher' || $selectedRole === 'teacher') ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'; ?>">
                            <input type="radio" name="role" value="teacher" class="mr-2" <?php echo ($role === 'teacher' || (isset($selectedRole) && $selectedRole === 'teacher')) ? 'checked' : ''; ?>>
                            <span class="font-medium">Teacher</span>
                        </label>
                        <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:border-indigo-500 transition-colors <?php echo ($role === 'student' || (isset($selectedRole) && $selectedRole === 'student')) ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'; ?>">
                            <input type="radio" name="role" value="student" class="mr-2" <?php echo ($role === 'student' || (isset($selectedRole) && $selectedRole === 'student')) ? 'checked' : ''; ?>>
                            <span class="font-medium">Student</span>
                        </label>
                    </div>
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" id="email" name="email" required
                           class="input-field w-full"
                           placeholder="your.email@example.com"
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" required
                           class="input-field w-full"
                           placeholder="Enter your password">
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-primary w-full text-white py-3 rounded-lg font-medium">
                    Sign In
                </button>
            </form>

            <div class="mt-6 text-center">
                <p class="text-gray-600">
                    Don't have an account? 
                    <a href="register.php" class="text-indigo-600 hover:text-indigo-800 font-medium">Sign up</a>
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
                    <a href="auth/google.php?action=login<?php echo $role ? '&role=' . urlencode($role) : ''; ?>" class="w-full flex items-center justify-center px-4 py-3 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        <span class="font-medium text-gray-700">Sign in with Google</span>
                    </a>
                </div>
            </div>

            <div class="mt-6 text-center text-sm text-gray-600">
                <p>Demo Credentials:</p>
                <p class="mt-2">
                    <strong>Teacher:</strong> teacher@edutrackr.com / password<br>
                    <strong>Student:</strong> student@edutrackr.com / password
                </p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

