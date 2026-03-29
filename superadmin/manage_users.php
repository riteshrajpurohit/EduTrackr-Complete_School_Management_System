<?php
/**
 * Manage Users (Teachers & Students)
 * Super Admin - EduTrackr
 */
require_once '../includes/functions.php';
requireSuperAdmin();

$currentPage = 'manage_users';
$pageTitle = "Manage Users";

$message = '';
$messageType = '';
$filterType = isset($_GET['type']) ? sanitizeInput($_GET['type']) : '';
$filterClassId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    global $conn;
    
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'create_teacher') {
            $name = sanitizeInput($_POST['name']);
            $email = sanitizeInput($_POST['email']);
            $password = $_POST['password'];
            $contact = sanitizeInput($_POST['contact']);
            
            // Check if email exists
            $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $checkStmt->bind_param("s", $email);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows > 0) {
                $message = "Email already exists!";
                $messageType = 'error';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $roleId = 2; // teacher
                
                $conn->begin_transaction();
                try {
                    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role_id, status) VALUES (?, ?, ?, ?, 'active')");
                    $stmt->bind_param("sssi", $name, $email, $hashedPassword, $roleId);
                    $stmt->execute();
                    $userId = $conn->insert_id;
                    $stmt->close();
                    
                    $stmt = $conn->prepare("INSERT INTO teachers (user_id, contact) VALUES (?, ?)");
                    $stmt->bind_param("is", $userId, $contact);
                    $stmt->execute();
                    $stmt->close();
                    
                    $conn->commit();
                    $message = "Teacher created successfully!";
                    $messageType = 'success';
                } catch (Exception $e) {
                    $conn->rollback();
                    $message = "Error creating teacher: " . $e->getMessage();
                    $messageType = 'error';
                }
            }
            $checkStmt->close();
            
        } elseif ($_POST['action'] === 'create_student') {
            $name = sanitizeInput($_POST['name']);
            $email = sanitizeInput($_POST['email']);
            $password = $_POST['password'];
            $rollNo = sanitizeInput($_POST['roll_no']);
            $contact = sanitizeInput($_POST['contact']);
            $classId = !empty($_POST['class_id']) ? (int)$_POST['class_id'] : null;
            
            // Check if email exists
            $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $checkStmt->bind_param("s", $email);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows > 0) {
                $message = "Email already exists!";
                $messageType = 'error';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                $roleId = 3; // student
                
                $conn->begin_transaction();
                try {
                    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role_id, status) VALUES (?, ?, ?, ?, 'active')");
                    $stmt->bind_param("sssi", $name, $email, $hashedPassword, $roleId);
                    $stmt->execute();
                    $userId = $conn->insert_id;
                    $stmt->close();
                    
                    $stmt = $conn->prepare("INSERT INTO students (user_id, roll_no, class_id, contact) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("isis", $userId, $rollNo, $classId, $contact);
                    $stmt->execute();
                    $stmt->close();
                    
                    $conn->commit();
                    $message = "Student created successfully!";
                    $messageType = 'success';
                } catch (Exception $e) {
                    $conn->rollback();
                    $message = "Error creating student: " . $e->getMessage();
                    $messageType = 'error';
                }
            }
            $checkStmt->close();
            
        } elseif ($_POST['action'] === 'delete_user') {
            $userId = (int)$_POST['user_id'];
            $userType = sanitizeInput($_POST['user_type']);
            
            $conn->begin_transaction();
            try {
                if ($userType === 'teacher') {
                    // Get teacher_id
                    $stmt = $conn->prepare("SELECT teacher_id FROM teachers WHERE user_id = ?");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $teacher = $result->fetch_assoc();
                    $stmt->close();
                    
                    if ($teacher) {
                        // Delete subject assignments
                        $stmt = $conn->prepare("DELETE FROM subject_assignments WHERE teacher_id = ?");
                        $stmt->bind_param("i", $teacher['teacher_id']);
                        $stmt->execute();
                        $stmt->close();
                        
                        // Delete teacher record
                        $stmt = $conn->prepare("DELETE FROM teachers WHERE user_id = ?");
                        $stmt->bind_param("i", $userId);
                        $stmt->execute();
                        $stmt->close();
                    }
                } elseif ($userType === 'student') {
                    // Delete student record (marks will be handled by CASCADE)
                    $stmt = $conn->prepare("DELETE FROM students WHERE user_id = ?");
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();
                    $stmt->close();
                }
                
                // Delete user
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $stmt->close();
                
                $conn->commit();
                $message = ucfirst($userType) . " deleted successfully!";
                $messageType = 'success';
            } catch (Exception $e) {
                $conn->rollback();
                $message = "Error deleting user: " . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// Get users
global $conn;
$users = [];

if ($filterType === 'teacher') {
    $query = "SELECT u.*, t.teacher_id, t.contact
              FROM users u
              JOIN teachers t ON u.id = t.user_id
              WHERE u.role_id = 2
              ORDER BY u.name";
    $result = $conn->query($query);
    if ($result) {
        $users = $result->fetch_all(MYSQLI_ASSOC);
    }
} elseif ($filterType === 'student') {
    $query = "SELECT u.*, s.student_id, s.roll_no, s.class_id, s.contact,
              c.name as class_name
              FROM users u
              JOIN students s ON u.id = s.user_id
              LEFT JOIN classes c ON s.class_id = c.id
              WHERE u.role_id = 3";
    
    if ($filterClassId) {
        $query .= " AND s.class_id = $filterClassId";
    }
    $query .= " ORDER BY u.name";
    
    $result = $conn->query($query);
    if ($result) {
        $users = $result->fetch_all(MYSQLI_ASSOC);
    }
} else {
    // Get all users
    $query = "SELECT u.*, 
              CASE 
                  WHEN u.role_id = 2 THEN (SELECT teacher_id FROM teachers WHERE user_id = u.id)
                  WHEN u.role_id = 3 THEN (SELECT student_id FROM students WHERE user_id = u.id)
              END as role_specific_id
              FROM users u
              WHERE u.role_id IN (2, 3)
              ORDER BY u.role_id, u.name";
    $result = $conn->query($query);
    if ($result) {
        $users = $result->fetch_all(MYSQLI_ASSOC);
    }
}

// Get classes for student enrollment
$classes = getAllClasses();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Manage Users</h1>
            <p class="text-gray-600">Add, edit, and manage teachers & students</p>
        </div>
        <div class="flex gap-3">
            <button onclick="openCreateModal('teacher')" class="btn-primary text-white px-6 py-3 rounded-lg font-medium">
                + Add Teacher
            </button>
            <button onclick="openCreateModal('student')" class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-lg font-medium">
                + Add Student
            </button>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="card p-4 mb-6">
        <form method="GET" class="flex gap-4 items-end">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Type</label>
                <select name="type" class="input-field w-full px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">All Users</option>
                    <option value="teacher" <?php echo $filterType === 'teacher' ? 'selected' : ''; ?>>Teachers</option>
                    <option value="student" <?php echo $filterType === 'student' ? 'selected' : ''; ?>>Students</option>
                </select>
            </div>
            <?php if ($filterType === 'student'): ?>
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Class</label>
                    <select name="class_id" class="input-field w-full px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">All Classes</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>" <?php echo $filterClassId == $class['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <button type="submit" class="px-6 py-2 bg-gray-600 text-white rounded-lg font-medium">Filter</button>
            <?php if ($filterType || $filterClassId): ?>
                <a href="manage_users.php" class="px-6 py-2 border border-gray-300 rounded-lg font-medium">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Users Table -->
    <div class="card p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">All Users</h2>
        <?php if (empty($users)): ?>
            <p class="text-gray-500 text-center py-8">No users found. Add teachers or students above.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Type</th>
                            <th>Details</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): 
                            $userRole = $user['role_id'] == 2 ? 'teacher' : 'student';
                        ?>
                            <tr>
                                <td>
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-gradient-to-br <?php echo $userRole === 'teacher' ? 'from-purple-400 to-purple-600' : 'from-emerald-400 to-emerald-600'; ?> rounded-full flex items-center justify-center text-white font-bold">
                                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                        </div>
                                        <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($user['name']); ?></span>
                                    </div>
                                </td>
                                <td class="text-gray-600"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge <?php echo $userRole === 'teacher' ? 'badge-info' : 'badge-success'; ?>">
                                        <?php echo ucfirst($userRole); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($userRole === 'teacher'): ?>
                                        <div class="text-sm text-gray-600">
                                            <?php if (isset($user['contact'])): ?>
                                                📞 <?php echo htmlspecialchars($user['contact']); ?>
                                            <?php else: ?>
                                                <span class="text-gray-400">No contact</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-sm">
                                            <div class="text-gray-800 font-medium">Roll: <?php echo isset($user['roll_no']) ? htmlspecialchars($user['roll_no']) : 'N/A'; ?></div>
                                            <?php if (isset($user['class_name'])): ?>
                                                <div class="text-gray-500 text-xs mt-1"><?php echo htmlspecialchars($user['class_name']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $user['status'] === 'active' ? 'badge-success' : 'badge-warning'; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="user_type" value="<?php echo $userRole; ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-800 hover:bg-red-50 px-3 py-1 rounded-lg text-sm font-medium transition-all">
                                            🗑️ Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Modal -->
<div id="userModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4 max-h-[90vh] overflow-y-auto">
        <h2 id="modalTitle" class="text-2xl font-bold text-gray-800 mb-6">Add User</h2>
        <form id="userForm" method="POST">
            <input type="hidden" name="action" id="formAction">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                    <input type="text" name="name" required
                           class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" required
                           class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input type="password" name="password" required
                           class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>
                
                <div id="contactField">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Contact</label>
                    <input type="text" name="contact"
                           class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>
                
                <div id="rollNoField" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Roll Number</label>
                    <input type="text" name="roll_no"
                           class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                </div>
                
                <div id="classAssignmentField" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Class</label>
                    <select name="class_id" class="input-field w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                        <option value="">Not Assigned</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>">
                                <?php echo htmlspecialchars($class['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="mt-6 flex gap-4">
                <button type="submit" class="btn-primary text-white px-6 py-3 rounded-lg font-medium flex-1">
                    Create
                </button>
                <button type="button" onclick="closeModal()" class="px-6 py-3 border border-gray-300 rounded-lg font-medium flex-1">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateModal(type) {
    document.getElementById('modalTitle').textContent = type === 'teacher' ? 'Add Teacher' : 'Add Student';
    document.getElementById('formAction').value = type === 'teacher' ? 'create_teacher' : 'create_student';
    document.getElementById('userForm').reset();
    
    if (type === 'teacher') {
        document.getElementById('rollNoField').classList.add('hidden');
        document.getElementById('classAssignmentField').classList.add('hidden');
        document.getElementById('contactField').classList.remove('hidden');
    } else {
        document.getElementById('rollNoField').classList.remove('hidden');
        document.getElementById('classAssignmentField').classList.remove('hidden');
        document.getElementById('contactField').classList.remove('hidden');
    }
    
    document.getElementById('userModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('userModal').classList.add('hidden');
}

document.getElementById('userModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php include '../includes/footer.php'; ?>

