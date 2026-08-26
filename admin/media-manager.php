<?php
require_once 'config.php';
requireAuth();

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'tagum_media';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Create table if not exists
$pdo->exec("CREATE TABLE IF NOT EXISTS media_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type ENUM('image','audio','video') NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Initialize variables
$message = '';
$edit_file = null;

// Handle Edit Form: load existing file
if(isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM media_files WHERE id=?");
    $stmt->execute([$edit_id]);
    $edit_file = $stmt->fetch(PDO::FETCH_ASSOC);
}

    function mediaWebPathToAbsPath(string $webPath): string {
        $base = realpath(__DIR__ . '/../../assets/media/');
        if ($base === false) return '';
        $file = basename($webPath);
        if ($file === '') return '';
        return $base . DIRECTORY_SEPARATOR . $file;
    }

    // Re-enabled for media picker integration
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        $file_path = $_POST['current_file'] ?? ''; // stored as web path (e.g. ../assets/media/abc.jpg)
        $type = $edit_file['type'] ?? 'image';

        if(isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] == 0) {
            $file = $_FILES['file_upload'];
            $upload_dir_abs = __DIR__ . '/../../assets/media/';
            $upload_dir_web = '../assets/media/';
            if(!is_dir($upload_dir_abs)) mkdir($upload_dir_abs, 0777, true);

            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_types = ['jpg','jpeg','png','gif'];
            $allowed_mimes = ['image/jpeg','image/png','image/gif'];

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if(in_array($file_ext, $allowed_types) && in_array($mime, $allowed_mimes)) {
                $type = 'image';
                $file_name = uniqid() . '_' . time() . '.' . $file_ext;
                $file_path_abs = $upload_dir_abs . $file_name;
                $file_path = $upload_dir_web . $file_name;

                if(move_uploaded_file($file['tmp_name'], $file_path_abs)) {
                    if(isset($_POST['edit_id']) && !empty($_POST['current_file'])) {
                        $old_abs = mediaWebPathToAbsPath((string)$_POST['current_file']);
                        if ($old_abs && file_exists($old_abs)) {
                            unlink($old_abs);
                        }
                    }
                } else {
                    $message = '<div class="alert alert-error">Upload failed!</div>';
                }
            } else {
                $message = '<div class="alert alert-error">Images only!</div>';
            }
        }

        if(isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
            $stmt = $pdo->prepare("UPDATE media_files SET name=?, type=?, file_path=? WHERE id=?");
            $stmt->execute([$_POST['name'], $type, $file_path, $_POST['edit_id']]);
            $message = '<div class="alert alert-success">Updated!</div>';
        } else {
            $stmt = $pdo->prepare("INSERT INTO media_files (name,type,file_path) VALUES (?,?,?)");
            $stmt->execute([$_POST['name'], $type, $file_path]);
            $message = '<div class="alert alert-success">Uploaded! ID: ' . $pdo->lastInsertId() . '</div>';
        }
    }

// Handle Delete
if(isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("SELECT file_path FROM media_files WHERE id=?");
    $stmt->execute([$_POST['delete_id']]);
    $row = $stmt->fetch();
    if($row) {
        $abs = mediaWebPathToAbsPath((string)$row['file_path']);
        if ($abs && file_exists($abs)) unlink($abs);
    }
    $stmt = $pdo->prepare("DELETE FROM media_files WHERE id=?");
    $stmt->execute([$_POST['delete_id']]);
    $message = '<div class="alert alert-success">File deleted!</div>';
}

// Load all media
$stmt = $pdo->query("SELECT * FROM media_files ORDER BY upload_date DESC");
$media_files = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Manager - Tagum Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <header class="admin-header">
        <div class="admin-header-content">
            <div class="admin-title">
                <h1>Media Manager</h1>
            </div>
            <div class="admin-nav">
                <a href="dashboard.php" class="btn btn-primary">← Back to Dashboard</a>
            </div>
        </div>
    </header>

    <main class="admin-main">
        <div class="admin-container">
            <?php echo $message ?? ''; ?>

            <!-- Upload Form -->
            <div class="form-section">
                <h2><?php echo $edit_file ? 'Edit File' : 'Upload New Image'; ?></h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="edit_id" value="<?php echo $edit_file['id'] ?? ''; ?>">
                    <?php if ($edit_file): ?>
                        <div class="current-file-info">
                            <img src="<?php echo htmlspecialchars($edit_file['file_path']); ?>" alt="Current" style="max-width:200px;">
                            <p>Current: <?php echo htmlspecialchars(basename($edit_file['file_path'])); ?></p>
                        </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label for="file_upload">Select Image</label>
                        <input type="file" id="file_upload" name="file_upload" accept="image/*" class="form-control" required>
                        <small>JPG, PNG, GIF (Max 10MB)</small>
                    </div>
                    <button type="submit" class="btn btn-primary">Upload/Replace</button>
                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                </form>

            <!-- Files List -->
            <div class="table-section">
                <h2>Media Files (<?php echo count($media_files); ?>)</h2>
                <?php if (empty($media_files)): ?>
                    <div class="empty-state">
                        <p>📁 No files uploaded</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="destinations-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Preview</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>File</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($media_files as $file): ?>
                                    <tr>
                                        <td><?php echo $file['id']; ?></td>
                                        <td class="table-image">
                                            <?php if ($file['type'] === 'image'): ?>
                                                <img src="<?php echo htmlspecialchars($file['file_path']); ?>" alt="Preview" style="max-width:60px;height:60px;object-fit:cover;border-radius:4px;">
                                            <?php else: ?>
                                                <span class="media-icon"><?php echo strtoupper(substr($file['type'], 0, 1)); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($file['name']); ?></td>
                                        <th><?php echo ucfirst($file['type']); ?></th>
                                        <td><?php echo htmlspecialchars(basename($file['file_path'])); ?></td>
                                        <td><?php echo date('M j, Y H:i', strtotime($file['upload_date'])); ?></td>
                                        <td>
                                            <a href="<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank" class="btn btn-small btn-primary">View</a>
                                            <a href="?edit=<?php echo $file['id']; ?>&current_file=<?php echo urlencode($file['file_path']); ?>" class="btn btn-small btn-edit">Edit</a>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="delete_id" value="<?php echo $file['id']; ?>">
                                                <button type="submit" class="btn btn-small btn-delete" onclick="return confirm('Delete?')">Delete</button>
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
    </main>

    <footer class="admin-footer">
        <p>&copy; 2026 Tagum City Admin</p>
    </footer>

    <style>
        .media-icon { width:60px; height:60px; background:var(--light-gray); border-radius:6px; display:flex; align-items:center; justify-content:center; font-weight:bold; color:var(--dark-green); font-size:1.2rem; }
        .current-file-info { background:var(--light-gray); padding:1rem; border-radius:8px; margin-bottom:1rem; text-align:center; }
        .hint-text { font-style:italic; color:var(--gray); font-size:0.9rem; }
        .table-section { margin-top:2rem; }
    </style>
</body>
</html>
