<?php
/**
 * Helper Functions File
 * EduTrackr - School Management System
 */

require_once __DIR__ . '/db.php';

/**
 * Start session if not already started
 */
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']) && (isset($_SESSION['role_id']) || isset($_SESSION['role']));
}

/**
 * Check if user has specific role (supports both role_id and role string)
 */
function hasRole($role) {
    startSession();
    
    // Support role_id (1=super_admin, 2=teacher, 3=student)
    if (isset($_SESSION['role_id'])) {
        $roleMap = [
            'super_admin' => 1,
            'admin' => 1,
            'teacher' => 2,
            'student' => 3
        ];
        
        if (is_numeric($role)) {
            return isset($_SESSION['role_id']) && $_SESSION['role_id'] == $role;
        }
        
        if (isset($roleMap[$role])) {
            return isset($_SESSION['role_id']) && $_SESSION['role_id'] == $roleMap[$role];
        }
    }
    
    // Fallback to old role string check
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Check if user is super admin
 */
function isSuperAdmin() {
    startSession();
    // Check role_id first (new system)
    if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1) {
        return true;
    }
    // Fallback to role string (old system)
    if (isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'super_admin')) {
        return true;
    }
    return false;
}

/**
 * Get current user's role_id
 */
function getCurrentRoleId() {
    startSession();
    return isset($_SESSION['role_id']) ? $_SESSION['role_id'] : null;
}

/**
 * Get base URL
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    $path = dirname(dirname($script));
    return $protocol . '://' . $host . $path;
}

/**
 * Require login - redirect to login if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        // Determine base path based on current script location
        $script = $_SERVER['SCRIPT_NAME'];
        $basePath = dirname($script);
        
        // If we're in a subdirectory (superadmin, teacher, student), go up one level
        if (strpos($basePath, '/superadmin') !== false || 
            strpos($basePath, '/teacher') !== false || 
            strpos($basePath, '/student') !== false ||
            strpos($basePath, '\\superadmin') !== false || 
            strpos($basePath, '\\teacher') !== false || 
            strpos($basePath, '\\student') !== false) {
            $basePath = dirname($basePath);
        }
        
        // Normalize path separators and build redirect URL
        $basePath = str_replace('\\', '/', $basePath);
        $basePath = trim($basePath, '/');
        
        // Build redirect URL
        if (empty($basePath) || $basePath === '.') {
            $redirectUrl = '/login.php';
        } else {
            $redirectUrl = '/' . $basePath . '/login.php';
        }
        
        header('Location: ' . $redirectUrl);
        exit();
    }
}

/**
 * Require specific role
 */
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        // Determine base path based on current script location
        $script = $_SERVER['SCRIPT_NAME'];
        $basePath = dirname($script);
        
        // If we're in a subdirectory, go up one level
        if (strpos($basePath, '/superadmin') !== false || 
            strpos($basePath, '/teacher') !== false || 
            strpos($basePath, '/student') !== false ||
            strpos($basePath, '\\superadmin') !== false || 
            strpos($basePath, '\\teacher') !== false || 
            strpos($basePath, '\\student') !== false) {
            $basePath = dirname($basePath);
        }
        
        // Normalize path separators and build redirect URL
        $basePath = str_replace('\\', '/', $basePath);
        $basePath = trim($basePath, '/');
        
        // Build redirect URL
        if (empty($basePath) || $basePath === '.') {
            $redirectUrl = '/error.php?msg=' . urlencode('Unauthorized access');
        } else {
            $redirectUrl = '/' . $basePath . '/error.php?msg=' . urlencode('Unauthorized access');
        }
        
        header('Location: ' . $redirectUrl);
        exit();
    }
}

/**
 * Require super admin role
 */
function requireSuperAdmin() {
    requireLogin();
    if (!isSuperAdmin()) {
        // Determine base path based on current script location
        $script = $_SERVER['SCRIPT_NAME'];
        $basePath = dirname($script);
        
        // If we're in a subdirectory, go up one level
        if (strpos($basePath, '/superadmin') !== false || 
            strpos($basePath, '/teacher') !== false || 
            strpos($basePath, '/student') !== false ||
            strpos($basePath, '\\superadmin') !== false || 
            strpos($basePath, '\\teacher') !== false || 
            strpos($basePath, '\\student') !== false) {
            $basePath = dirname($basePath);
        }
        
        // Normalize path separators and build redirect URL
        $basePath = str_replace('\\', '/', $basePath);
        $basePath = trim($basePath, '/');
        
        // Build redirect URL
        if (empty($basePath) || $basePath === '.') {
            $redirectUrl = '/error.php?msg=' . urlencode('Super Admin access required');
        } else {
            $redirectUrl = '/' . $basePath . '/error.php?msg=' . urlencode('Super Admin access required');
        }
        
        header('Location: ' . $redirectUrl);
        exit();
    }
}

/**
 * Require role_id (numeric check)
 */
function requireRoleId($roleId) {
    requireLogin();
    startSession();
    if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != $roleId) {
        // Determine base path based on current script location
        $script = $_SERVER['SCRIPT_NAME'];
        $basePath = dirname($script);
        
        // If we're in a subdirectory, go up one level
        if (strpos($basePath, '/superadmin') !== false || 
            strpos($basePath, '/teacher') !== false || 
            strpos($basePath, '/student') !== false ||
            strpos($basePath, '\\superadmin') !== false || 
            strpos($basePath, '\\teacher') !== false || 
            strpos($basePath, '\\student') !== false) {
            $basePath = dirname($basePath);
        }
        
        // Normalize path separators and build redirect URL
        $basePath = str_replace('\\', '/', $basePath);
        $basePath = trim($basePath, '/');
        
        // Build redirect URL
        if (empty($basePath) || $basePath === '.') {
            $redirectUrl = '/error.php?msg=' . urlencode('Unauthorized access');
        } else {
            $redirectUrl = '/' . $basePath . '/error.php?msg=' . urlencode('Unauthorized access');
        }
        
        header('Location: ' . $redirectUrl);
        exit();
    }
}

/**
 * Sanitize input data
 */
function sanitizeInput($data) {
    if (is_null($data) || !is_string($data)) {
        return '';
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Calculate grade from marks
 */
function calculateGrade($marks, $maxMarks) {
    $percentage = ($marks / $maxMarks) * 100;
    
    if ($percentage >= 90) return 'A+';
    if ($percentage >= 80) return 'A';
    if ($percentage >= 70) return 'B';
    if ($percentage >= 60) return 'C';
    if ($percentage >= 50) return 'D';
    return 'F';
}

/**
 * Get user data by ID
 */
function getUserData($userId) {
    global $conn;
    if (!$conn) {
        return null;
    }
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data;
}

/**
 * Get student data by user ID
 */
function getStudentData($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT s.*, u.name, u.email, 
                           c.name as class_name
                           FROM students s 
                           JOIN users u ON s.user_id = u.id 
                           LEFT JOIN classes c ON s.class_id = c.id
                           WHERE s.user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Get teacher data by user ID
 */
function getTeacherData($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT t.*, u.name, u.email 
                           FROM teachers t 
                           JOIN users u ON t.user_id = u.id 
                           WHERE t.user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Get teacher_id from user_id
 */
function getTeacherId($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row ? $row['teacher_id'] : null;
}

/**
 * Get classes assigned to a teacher (via subject_assignments)
 */
function getTeacherClasses($teacherId) {
    global $conn;
    $stmt = $conn->prepare("SELECT DISTINCT c.id, c.name as class_name, c.description
                           FROM classes c
                           JOIN subject_assignments sa ON c.id = sa.class_id
                           WHERE sa.teacher_id = ?
                           ORDER BY c.name");
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get subjects assigned to a teacher for a specific class
 */
function getTeacherSubjects($teacherId, $classId = null) {
    global $conn;
    if ($classId) {
        $stmt = $conn->prepare("SELECT sa.*, s.name as subject_name, s.code as subject_code, c.name as class_name
                              FROM subject_assignments sa
                              JOIN subjects s ON sa.subject_id = s.subject_id
                              JOIN classes c ON sa.class_id = c.id
                              WHERE sa.teacher_id = ? AND sa.class_id = ?
                              ORDER BY s.name");
        $stmt->bind_param("ii", $teacherId, $classId);
    } else {
        $stmt = $conn->prepare("SELECT sa.*, s.name as subject_name, s.code as subject_code, c.name as class_name
                              FROM subject_assignments sa
                              JOIN subjects s ON sa.subject_id = s.subject_id
                              JOIN classes c ON sa.class_id = c.id
                              WHERE sa.teacher_id = ?
                              ORDER BY c.name, s.name");
        $stmt->bind_param("i", $teacherId);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Verify if teacher owns a subject_assignment
 */
function verifyTeacherSubjectAssignment($teacherId, $subjectAssignmentId) {
    global $conn;
    $stmt = $conn->prepare("SELECT id FROM subject_assignments WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $subjectAssignmentId, $teacherId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

/**
 * Get students in a class
 */
function getStudentsByClass($classId) {
    global $conn;
    $stmt = $conn->prepare("SELECT s.*, u.name, u.email
                           FROM students s
                           JOIN users u ON s.user_id = u.id
                           WHERE s.class_id = ?
                           ORDER BY s.roll_no");
    $stmt->bind_param("i", $classId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get all classes
 */
function getAllClasses() {
    global $conn;
    if (!$conn) {
        return [];
    }
    $result = $conn->query("SELECT c.*, u.name as teacher_name 
                           FROM classes c 
                           LEFT JOIN users u ON c.teacher_id = u.id 
                           ORDER BY c.name");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Get subjects by class (uses class_id)
 */
function getSubjectsByClass($classId) {
    global $conn;
    if (!$conn) {
        return [];
    }
    // Get subjects for a specific class via subject_assignments
    $stmt = $conn->prepare("SELECT DISTINCT s.subject_id, s.name, s.code, s.max_marks,
                           sa.id as subject_assignment_id,
                           t.teacher_id, u.name as teacher_name
                           FROM subject_assignments sa
                           INNER JOIN subjects s ON sa.subject_id = s.subject_id
                           INNER JOIN teachers t ON sa.teacher_id = t.teacher_id
                           INNER JOIN users u ON t.user_id = u.id
                           WHERE sa.class_id = ?
                           ORDER BY s.name");
    $stmt->bind_param("i", $classId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

/**
 * Get marks for a student (updated for new schema)
 */
function getStudentMarks($studentId, $subjectId = null) {
    global $conn;
    if (!$conn) {
        return $subjectId ? null : [];
    }
    
    if ($subjectId) {
        // Get marks for specific subject using subject_assignment_id
        $stmt = $conn->prepare("SELECT m.*, s.name as subject_name, s.code as subject_code, s.max_marks as subject_max_marks 
                               FROM marks m 
                               LEFT JOIN subject_assignments sa ON m.subject_assignment_id = sa.id
                               LEFT JOIN subjects s ON sa.subject_id = s.subject_id
                               WHERE m.student_id = ? AND sa.subject_id = ?
                               LIMIT 1");
        $stmt->bind_param("ii", $studentId, $subjectId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        return $data;
    } else {
        // Get all marks for student
        $stmt = $conn->prepare("SELECT m.*, s.name as subject_name, s.code as subject_code, s.max_marks as subject_max_marks 
                               FROM marks m 
                               LEFT JOIN subject_assignments sa ON m.subject_assignment_id = sa.id
                               LEFT JOIN subjects s ON sa.subject_id = s.subject_id
                               WHERE m.student_id = ? 
                               ORDER BY s.name");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    }
}

/**
 * Redirect function
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Get all fee groups
 */
function getAllFeeGroups() {
    global $conn;
    if (!$conn) {
        return [];
    }
    $result = $conn->query("SELECT * FROM fee_groups ORDER BY name");
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

/**
 * Get fee installments for a class
 */
function getFeeInstallmentsByClass($classId) {
    global $conn;
    if (!$conn) {
        return [];
    }
    $stmt = $conn->prepare("SELECT fi.*, fg.name as fee_group_name 
                           FROM fee_installments fi
                           JOIN fee_groups fg ON fi.fee_group_id = fg.fee_group_id
                           WHERE fi.class_id = ?
                           ORDER BY fi.due_date, fi.name");
    $stmt->bind_param("i", $classId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

/**
 * Get extra fees for a class or student
 */
function getExtraFees($classId = null, $studentId = null) {
    global $conn;
    if (!$conn) {
        return [];
    }
    
    if ($studentId) {
        // Get extra fees for specific student
        $stmt = $conn->prepare("SELECT ef.*, fg.name as fee_group_name 
                               FROM extra_fees ef
                               JOIN fee_groups fg ON ef.fee_group_id = fg.fee_group_id
                               WHERE ef.student_id = ?
                               ORDER BY ef.assigned_at DESC");
        $stmt->bind_param("i", $studentId);
    } elseif ($classId) {
        // Get extra fees for entire class
        $stmt = $conn->prepare("SELECT ef.*, fg.name as fee_group_name 
                               FROM extra_fees ef
                               JOIN fee_groups fg ON ef.fee_group_id = fg.fee_group_id
                               WHERE ef.class_id = ? AND ef.student_id IS NULL
                               ORDER BY ef.assigned_at DESC");
        $stmt->bind_param("i", $classId);
    } else {
        return [];
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

/**
 * Get student fees (all fees assigned to a student)
 */
function getStudentFees($studentId) {
    global $conn;
    if (!$conn) {
        return [];
    }
    $stmt = $conn->prepare("SELECT sf.*, 
                           fg.name as fee_group_name,
                           fi.name as installment_name,
                           ef.description as extra_fee_description,
                           (SELECT SUM(amount_paid) FROM fee_payments WHERE student_fee_id = sf.student_fee_id AND status = 'Paid') as paid_amount
                           FROM student_fees sf
                           JOIN fee_groups fg ON sf.fee_group_id = fg.fee_group_id
                           LEFT JOIN fee_installments fi ON sf.installment_id = fi.installment_id
                           LEFT JOIN extra_fees ef ON sf.extra_fee_id = ef.extra_fee_id
                           WHERE sf.student_id = ?
                           ORDER BY sf.due_date, sf.created_at");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

/**
 * Assign installments to a student (when student joins a class)
 */
function assignInstallmentsToStudent($studentId, $classId) {
    global $conn;
    if (!$conn) {
        return false;
    }
    
    // Get all installments for the class
    $installments = getFeeInstallmentsByClass($classId);
    
    foreach ($installments as $installment) {
        // Check if already assigned
        $checkStmt = $conn->prepare("SELECT student_fee_id FROM student_fees WHERE student_id = ? AND installment_id = ?");
        $checkStmt->bind_param("ii", $studentId, $installment['installment_id']);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows == 0) {
            // Assign installment
            $stmt = $conn->prepare("INSERT INTO student_fees (student_id, fee_group_id, installment_id, amount, due_date, status) 
                                   VALUES (?, ?, ?, ?, ?, 'Pending')");
            $stmt->bind_param("iiids", $studentId, $installment['fee_group_id'], $installment['installment_id'], 
                            $installment['amount'], $installment['due_date']);
            $stmt->execute();
            $stmt->close();
        }
        $checkStmt->close();
    }
    
    // Assign class-level extra fees
    $extraFees = getExtraFees($classId);
    foreach ($extraFees as $extraFee) {
        // Check if already assigned
        $checkStmt = $conn->prepare("SELECT student_fee_id FROM student_fees WHERE student_id = ? AND extra_fee_id = ?");
        $checkStmt->bind_param("ii", $studentId, $extraFee['extra_fee_id']);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        
        if ($result->num_rows == 0) {
            // Assign extra fee
            $stmt = $conn->prepare("INSERT INTO student_fees (student_id, fee_group_id, extra_fee_id, amount, due_date, status) 
                                   VALUES (?, ?, ?, ?, CURDATE(), 'Pending')");
            $stmt->bind_param("iiid", $studentId, $extraFee['fee_group_id'], $extraFee['extra_fee_id'], $extraFee['amount']);
            $stmt->execute();
            $stmt->close();
        }
        $checkStmt->close();
    }
    
    return true;
}

/**
 * Get fee payment history for a student
 */
function getStudentPaymentHistory($studentId) {
    global $conn;
    if (!$conn) {
        return [];
    }
    $stmt = $conn->prepare("SELECT fp.*, 
                           sf.amount as fee_amount,
                           fg.name as fee_group_name,
                           fi.name as installment_name,
                           ef.description as extra_fee_description
                           FROM fee_payments fp
                           JOIN student_fees sf ON fp.student_fee_id = sf.student_fee_id
                           JOIN fee_groups fg ON sf.fee_group_id = fg.fee_group_id
                           LEFT JOIN fee_installments fi ON sf.installment_id = fi.installment_id
                           LEFT JOIN extra_fees ef ON sf.extra_fee_id = ef.extra_fee_id
                           WHERE sf.student_id = ?
                           ORDER BY fp.payment_date DESC, fp.created_at DESC");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $data;
}

/**
 * Generate transaction ID based on payment mode
 */
function generateTransactionId($paymentMode, $details = []) {
    $prefix = '';
    $suffix = '';
    
    switch (strtolower($paymentMode)) {
        case 'cash':
            $prefix = 'CASH';
            $suffix = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
            break;
            
        case 'upi':
            $prefix = 'UPI';
            $suffix = date('YmdHis') . rand(100, 999);
            break;
            
        case 'debit_card':
        case 'credit_card':
            $prefix = 'CARD';
            $last4 = isset($details['card_last4']) ? $details['card_last4'] : str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            $suffix = $last4 . '-' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
            break;
            
        case 'net_banking':
            $prefix = 'NET';
            $bankCode = isset($details['bank_code']) ? $details['bank_code'] : 'BNK';
            $suffix = $bankCode . '-' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
            break;
            
        default:
            $prefix = 'PAY';
            $suffix = date('YmdHis') . rand(100, 999);
    }
    
    return $prefix . '-' . $suffix;
}

/**
 * Generate receipt number
 */
function generateReceiptNumber($studentId, $paymentDate = null) {
    global $conn;
    
    if (!$paymentDate) {
        $paymentDate = date('Y-m-d');
    }
    
    $datePart = date('Ymd', strtotime($paymentDate));
    $studentPart = str_pad($studentId, 3, '0', STR_PAD_LEFT);
    
    // Get the last receipt number for today to get the increment
    $stmt = $conn->prepare("SELECT receipt_number FROM fee_payments 
                           WHERE receipt_number LIKE ? 
                           ORDER BY payment_id DESC LIMIT 1");
    $pattern = 'RCT-' . $datePart . '-' . $studentPart . '-%';
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $increment = 1;
    if ($result->num_rows > 0) {
        $lastReceipt = $result->fetch_assoc()['receipt_number'];
        $parts = explode('-', $lastReceipt);
        if (count($parts) == 4) {
            $increment = intval($parts[3]) + 1;
        }
    }
    $stmt->close();
    
    $incrementPart = str_pad($increment, 4, '0', STR_PAD_LEFT);
    
    return 'RCT-' . $datePart . '-' . $studentPart . '-' . $incrementPart;
}

