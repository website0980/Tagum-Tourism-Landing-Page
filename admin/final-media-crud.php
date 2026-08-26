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

// Handle Upload / Update
if($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Default to existing file if editing
    $file_path = $_POST['current_file'] ?? '';
    $type = $edit_file['type'] ?? 'image';

    // Uploads disabled - image upload removed

    // Insert or Update DB
    if(isset($_POST['edit_id']) && !empty($_POST['edit_id'])) {
        $stmt = $pdo->prepare("UPDATE media_files SET name=?, type=?, file_path=? WHERE id=?");
        $stmt->execute([$_POST['name'], $type, $file_path, $_POST['edit_id']]);
        $message = '<div class="alert alert-success">File updated successfully!</div>';
    } else {
        $stmt = $pdo->prepare("INSERT INTO media_files (name,type,file_path) VALUES (?,?,?)");
        $stmt->execute([$_POST['name'], $type, $file_path]);
        $message = '<div class="alert alert-success">File uploaded successfully!</div>';
    }
}

// Handle Delete
if(isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("SELECT file_path FROM media_files WHERE id=?");
    $stmt->execute([$_POST['delete_id']]);
    $row = $stmt->fetch();
    if($row && file_exists($row['file_path'])) unlink($row['file_path']);
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
    <title>Media CRUD - Tagum Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <header class="admin-header">
        <div class="admin-header-content">
            <div class="admin-title">
                <h2><?php echo $edit_file ? 'Edit Media' : 'Upload New Media'; ?></h2>
            </div>
            <div class="admin-nav">
                <a href="dashboard.php" class="btn btn-primary">Dashboard</a>
            </div>
        </div>
    </header>

    <main class="admin-main">
        <div class="admin-container">
            <?php if($message): echo $message; endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="edit_id" value="<?php echo $edit_file['id'] ?? ''; ?>">
                <input type="hidden" name="current_file" value="<?php echo $edit_file['file_path'] ?? ''; ?>">

                <label>Name:</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($edit_file['name'] ?? ''); ?>" optional>

                <label>File (optional when editing)</label>
                <!-- Uploads disabled -->
                <?php if($edit_file): ?>
                    <p>Current file: <?php echo htmlspecialchars(basename($edit_file['file_path'])); ?></p>
                <?php endif; ?>

                <button type="submit"><?php echo $edit_file ? 'Update' : 'Upload'; ?></button>
                <a href="media-crud.php" class="btn btn-secondary">Cancel</a>
            </form>

            <!-- Files List -->
            <h2>All Files</h2>
            <table>
                <tr><th>ID</th><th>Name</th><th>Type</th><th>File</th><th>Date</th><th>Actions</th></tr>
                <?php foreach ($media_files as $file): ?>
                    <tr>
                        <td><?php echo $file['id']; ?></td>
                        <td><?php echo htmlspecialchars($file['name']); ?></td>
                        <td><?php echo ucfirst($file['type']); ?></td>
                        <td><?php echo htmlspecialchars(basename($file['file_path'])); ?></td>
                        <td><?php echo $file['upload_date']; ?></td>
                        <td>
                            <a href="?edit=<?php echo $file['id']; ?>">Edit</a>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="delete_id" value="<?php echo $file['id']; ?>">
                                <button onclick="return confirm('Delete?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </main>
</body>
</html>
