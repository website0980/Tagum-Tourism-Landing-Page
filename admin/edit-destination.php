<?php
require_once 'config.php';
requireAuth();

$pdo = $config['pdo']; // Assume PDO connection in config

$id = $_GET['id'] ?? $_POST['id'] ?? 0;
$destination = [];
$current_image = '';

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM destinations WHERE id = ?");
    $stmt->execute([$id]);
    $destination = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_image = $destination['image'] ?? '';
}

$errors = [];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string)($_POST['name'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));

    if ($name === '') $errors[] = 'Name is required';
    if ($description === '') $errors[] = 'Description is required';

    $data = [
        'name' => $name,
        'description' => $description,
        // add other fields
        'image' => $current_image
    ];

    $has_upload = isset($_FILES['image_file']) && is_array($_FILES['image_file']) && ($_FILES['image_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    $uploaded_tmp_path = null;
    $uploaded_new_rel_path = null;
    $uploaded_new_abs_path = null;

    // Validate upload (do not move/delete anything yet)
    if ($has_upload) {
        if ($_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload error';
        } else {
            $file_ext = strtolower(pathinfo((string)$_FILES['image_file']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($file_ext, $allowed, true)) {
                $errors[] = 'Invalid file type';
            }
        }
    }

    // Update database
    if (empty($errors)) {
        try {
            // If we have a new file, move it first to a unique filename
            if ($has_upload) {
                $upload_dir = __DIR__ . '/../../uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                $file_ext = strtolower(pathinfo((string)$_FILES['image_file']['name'], PATHINFO_EXTENSION));
                $new_filename = bin2hex(random_bytes(16)) . '.' . $file_ext;
                $uploaded_new_abs_path = $upload_dir . $new_filename;
                $uploaded_new_rel_path = 'uploads/' . $new_filename;
                $uploaded_tmp_path = (string)$_FILES['image_file']['tmp_name'];

                if (!move_uploaded_file($uploaded_tmp_path, $uploaded_new_abs_path)) {
                    throw new RuntimeException('Upload failed');
                }

                $data['image'] = $uploaded_new_rel_path;
            }

            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE destinations SET name=?, description=?, image=? WHERE id=?");
            $stmt->execute([$data['name'], $data['description'], $data['image'], $id]);
            $pdo->commit();

            // Delete old image only after DB update succeeds
            if ($has_upload && $current_image) {
                $old_abs = __DIR__ . '/../../' . ltrim($current_image, '/\\');
                if (file_exists($old_abs)) {
                    @unlink($old_abs);
                }
                $current_image = $data['image'];
            }

            $message = 'Updated successfully';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // If we moved a new file but DB update failed, remove the new file to avoid orphans
            if ($uploaded_new_abs_path && file_exists($uploaded_new_abs_path)) {
                @unlink($uploaded_new_abs_path);
            }
            $errors[] = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Destination</title>
</head>
<body>
    <?php if ($message): ?>
        <p style="color:green;"><?php echo $message; ?></p>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <ul style="color:red;">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
        <label>Name:</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($destination['name'] ?? ''); ?>"><br>
        
        <label>Description:</label>
        <textarea name="description"><?php echo htmlspecialchars($destination['description'] ?? ''); ?></textarea><br>

        <?php if ($current_image): ?>
            <div>
                <img src="../<?php echo htmlspecialchars($current_image); ?>" style="max-width:200px;">
                <p>Current: <?php echo basename($current_image); ?></p>
            </div>
        <?php endif; ?>

        <label>New Image (optional):</label>
        <input type="file" name="image_file" accept="image/*"><br>
        <small>Leave empty to keep current image</small><br>

        <button type="submit">Save Changes</button>
    </form>
</body>
</html>
