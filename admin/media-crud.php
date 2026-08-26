<?php
require_once 'config.php';
requireAuth();

// Database connection (update credentials)
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
    type ENUM('image') NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$message = '';

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
                <h1>Media Files Manager</h1>
            </div>
            <div class="admin-nav">
                <a href="dashboard.php" class="btn btn-primary">← Dashboard</a>
            </div>
        </div>
    </header>

    <main class="admin-main">
        <div class="admin-container">
            <?php echo $message; ?>

            <!-- Upload Form -->
            <div class="form-section">
                <h2>Upload New Media</h2>
                <form method="POST" enctype="multipart/form-data">
                    <?php if (!empty($_GET['current_file'])): ?>
                            <div class="current-file-info">
                                <img src="<?php echo htmlspecialchars($_GET['current_file']); ?>" alt="Current" style="max-width: 200px; max-height: 150px; border-radius: 8px;">
                                <p><strong>Current file:</strong> <?php echo htmlspecialchars(basename($_GET['current_file'])); ?></p>
                                <input type="hidden" name="current_file" value="<?php echo htmlspecialchars($_GET['current_file']); ?>">
                            </div>
                        <?php else: ?>
                            <div class="form-group">
                                <label for="file">Select File</label>
                                <input type="file" id="file" name="file" class="form-control">
                            </div>
                        <?php endif; ?>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </form>
            </div>

            <!-- Media Files Table -->
            <?php if (!empty($media_files)): ?>
                <div class="table-responsive">
                    <h2>Uploaded Media (<?php echo count($media_files); ?>)</h2>
                    <table class="destinations-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Preview</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Path</th>
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
                                            <img src="<?php echo htmlspecialchars($file['file_path']); ?>" alt="Preview">
                                        <?php else: ?>
                                            <span class="media-icon"><?php echo strtoupper($file['type'][0]); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($file['name']); ?></strong></td>
                                    <td><span class="type-badge"><?php echo ucfirst($file['type']); ?></span></td>
                                    <td><?php echo htmlspecialchars(basename($file['file_path'])); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($file['upload_date'])); ?></td>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($file['file_path']); ?>" target="_blank" class="btn btn-small btn-primary">View</a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this file?');">
                                            <input type="hidden" name="delete_id" value="<?php echo $file['id']; ?>">
                                            <button type="submit" class="btn btn-small btn-delete">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>📁 No media files uploaded yet</p>
                    <a href="#upload" class="btn btn-primary">Upload your first file</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <footer class="admin-footer">
        <p>&copy; 2026 Tagum City Admin. All rights reserved.</p>
    </footer>

    <style>
        .media-icon {
            width: 60px;
            height: 60px;
            background: var(--light-gray);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: var(--dark-green);
            font-size: 1.2rem;
        }
        .preview-img {
            max-width: 60px;
            max-height: 60px;
            object-fit: cover;
            border-radius: 6px;
        }
        .current-image-preview {
            background: var(--light-gray);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .file-status {
            background: var(--success);
            color: var(--white);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
        }
    </style>
</body>
</html>

