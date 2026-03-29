<?php
/**
 * Super Admin - Manage Timetables
 * EduTrackr - School Management System
 */
require_once '../includes/functions.php';
requireSuperAdmin();

$currentPage = 'timetables';
$pageTitle = "Manage Timetables";

$success = '';
$error = '';

global $conn;

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['timetable_file'])) {
    $classId = intval($_POST['class_id'] ?? 0);
    $file = $_FILES['timetable_file'];
    
    // Allowed file types
    $allowedTypes = [
        'application/pdf',
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp'
    ];
    
    // Get file extension
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if ($classId == 0) {
        $error = 'Please select a class.';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'File upload error: ' . $file['error'];
    } elseif ($file['size'] > 10 * 1024 * 1024) { // 10MB limit (increased for images)
        $error = 'File size exceeds 10MB limit.';
    } elseif (!in_array($file['type'], $allowedTypes) || !in_array($fileExtension, $allowedExtensions)) {
        $error = 'Only PDF and image files (JPG, PNG, GIF, WEBP) are allowed.';
    } else {
        // Create uploads directory if it doesn't exist
        $uploadDir = '../uploads/timetables/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename (extension already obtained above)
        $fileName = 'timetable_' . $classId . '_' . time() . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            // Deactivate old timetables for this class
            $stmt = $conn->prepare("UPDATE timetables SET is_active = 0 WHERE class_id = ?");
            $stmt->bind_param("i", $classId);
            $stmt->execute();
            $stmt->close();
            
            // Insert new timetable
            $relativePath = 'uploads/timetables/' . $fileName;
            $stmt = $conn->prepare("INSERT INTO timetables (class_id, file_path, file_name, file_size, uploaded_by) 
                                   VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("issii", $classId, $relativePath, $file['name'], $file['size'], $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $success = 'Timetable uploaded successfully!';
            } else {
                $error = 'Error saving timetable: ' . $conn->error;
                unlink($filePath); // Delete uploaded file on error
            }
            $stmt->close();
        } else {
            $error = 'Failed to move uploaded file.';
        }
    }
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_timetable'])) {
    $timetableId = intval($_POST['timetable_id']);
    
    // Get file path
    $stmt = $conn->prepare("SELECT file_path FROM timetables WHERE id = ?");
    $stmt->bind_param("i", $timetableId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($result) {
        // Delete file
        $filePath = '../' . $result['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        
        // Delete database record
        $stmt = $conn->prepare("DELETE FROM timetables WHERE id = ?");
        $stmt->bind_param("i", $timetableId);
        $stmt->execute();
        $stmt->close();
        $success = 'Timetable deleted successfully.';
    }
}

// Get all timetables
$timetables = [];
$result = $conn->query("SELECT t.*, c.name as class_name, u.name as uploaded_by_name
                        FROM timetables t
                        JOIN classes c ON t.class_id = c.id
                        LEFT JOIN users u ON t.uploaded_by = u.id
                        ORDER BY t.upload_date DESC");
if ($result) {
    $timetables = $result->fetch_all(MYSQLI_ASSOC);
}

// Get all classes
$classes = getAllClasses();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="ml-64 min-h-screen p-8">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Manage Timetables</h1>
        <p class="text-gray-600">Upload and manage class timetables (PDF only, max 5MB)</p>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-50 border-2 border-red-300 px-4 py-3 rounded-lg mb-6" style="border-color: #e74c3c;">
            <p class="font-medium" style="color: #e74c3c;"><?php echo $error; ?></p>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-50 border-2 border-green-300 px-4 py-3 rounded-lg mb-6" style="border-color: #2ecc71;">
            <p class="font-medium" style="color: #2ecc71;"><?php echo $success; ?></p>
        </div>
    <?php endif; ?>

    <!-- Upload Form -->
    <div class="card p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Upload New Timetable</h2>
        <form method="POST" action="" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Class *</label>
                <select name="class_id" required class="input-field w-full px-4 py-3">
                    <option value="">Choose a class</option>
                    <?php foreach ($classes as $class): ?>
                        <option value="<?php echo $class['id']; ?>">
                            <?php echo htmlspecialchars($class['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Timetable File (PDF or Image, max 10MB) *</label>
                <input type="file" name="timetable_file" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp" required 
                       class="input-field w-full px-4 py-3">
                <p class="text-xs text-gray-500 mt-1">Accepted formats: PDF, JPG, PNG, GIF, WEBP. Maximum file size: 10MB</p>
            </div>
            
            <button type="submit" class="btn-primary text-white px-6 py-3 rounded-lg font-medium">
                Upload Timetable
            </button>
        </form>
    </div>

    <!-- Timetables List -->
    <div class="card p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">All Timetables</h2>
        
        <?php if (count($timetables) > 0): ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Class</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">File Name</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Size</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Uploaded By</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Date</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Status</th>
                            <th class="text-left py-3 px-4 font-semibold text-gray-700">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($timetables as $timetable): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 px-4">
                                    <?php echo htmlspecialchars($timetable['class_name']); ?>
                                </td>
                                <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($timetable['file_name']); ?></td>
                                <td class="py-3 px-4"><?php echo number_format($timetable['file_size'] / 1024, 2); ?> KB</td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($timetable['uploaded_by_name']); ?></td>
                                <td class="py-3 px-4 text-gray-600"><?php echo date('M d, Y', strtotime($timetable['upload_date'])); ?></td>
                                <td class="py-3 px-4">
                                    <?php if ($timetable['is_active']): ?>
                                        <span class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">Active</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex gap-2">
                                        <?php 
                                        $fileExtension = strtolower(pathinfo($timetable['file_path'], PATHINFO_EXTENSION));
                                        $isImage = in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                        ?>
                                        <a href="../<?php echo htmlspecialchars($timetable['file_path']); ?>" target="_blank" 
                                           class="px-3 py-1 text-sm rounded bg-blue-100 text-blue-800 hover:bg-blue-200">
                                            <?php echo $isImage ? 'View Image' : 'View PDF'; ?>
                                        </a>
                                        <form method="POST" action="" class="inline" 
                                              onsubmit="return confirm('Are you sure you want to delete this timetable?');">
                                            <input type="hidden" name="timetable_id" value="<?php echo $timetable['id']; ?>">
                                            <button type="submit" name="delete_timetable" 
                                                    class="px-3 py-1 text-sm rounded bg-red-100 text-red-800 hover:bg-red-200">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-gray-600 text-center py-8">No timetables uploaded yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

