<?php
// CLEAN ORIGINAL - NO DEBUG - WORKS
require_once 'config.php';
require_once 'db_experience_helpers.php';
requireAuth();


$isEdit = false;
$experience = [
    'id' => '',
    'name' => '',
    'type' => '',
    'description' => '',
    'image' => '',
    'date' => '',
    'featured' => false
];

$id = $_GET['id'] ?? $_POST['id'] ?? null;
if ($id !== null && $id !== '') {
    $id = (int) $id;
    // Load from DB for edit
    $db = new SQLite3('../database.db');
    $stmt = $db->prepare('SELECT * FROM experiences WHERE id = :id');
    $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);
    if ($row) {
        $isEdit = true;
        $experience = $row;
        $experience['featured'] = !empty($row['featured']);
    }
    $db->close();
}
$errors = [];
$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $experience = [
        'id' => $_POST['id'] ?? '',
        'name' => $_POST['name'] ?? '',
        'type' => $_POST['type'] ?? '',
        'description' => $_POST['description'] ?? '',
        'date' => $_POST['date'] ?? '',
        'image' => $_POST['image'] ?? '',
        'featured' => isset($_POST['featured']) ? true : false
    ];

    // Image optional - PHP validation only if uploaded
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $validation = validateImageUpload($_FILES['image_file']);
        if (!$validation['success']) {
            $errors[] = $validation['error'];
        }
    }

    // Validation
    if (empty($experience['name'])) $errors[] = 'Experience name is required';
    if (empty($experience['type'])) $errors[] = 'Experience type is required';
    if (empty($experience['description'])) $errors[] = 'Description is required';
    if (empty($experience['date'])) $errors[] = 'Date is required';

    // Save if no errors
    if (empty($errors)) {
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $result = saveExperienceImage($_FILES['image_file']);
            if ($result['success']) {
                if (!empty($experience['image'])) {
                    deleteExperienceImage($experience['image']);
                }
                $experience['image'] = $result['path'];
            } else {
                $errors[] = $result['error'] ?? 'Image upload failed';
            }
        }
        if (empty($errors)) {
            // Save to DB
            $savedId = db_save_experience($experience, $isEdit);
            header('Location: dashboard.php?tab=experiences&message=' . ($isEdit ? 'updated' : 'added'));
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Edit' : 'Add'; ?> Experience - Tagum Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="admin-header-content">
            <div class="admin-title">
                <a href="dashboard.php?tab=experiences" class="back-link" aria-label="Back to Dashboard">
                    <span class="back-link-icon" aria-hidden="true">←</span>
                    <span class="back-link-text">Dashboard</span>
                </a>
                <h1><?php echo $isEdit ? 'Edit Experience' : 'Add New Experience'; ?></h1>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="admin-main">
        <div class="admin-container">
            <!-- Errors -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" enctype="multipart/form-data" class="destination-form">
                <?php if ($isEdit && $id !== null && $id !== ''): ?>
                <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
                <?php endif; ?>
                <div class="form-section">
                    <h2>Basic Information</h2>
                    
                    <div class="form-group">
                        <label for="name">Experience Name *</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($experience['name']); ?>" required class="form-control" placeholder="e.g., River Tours">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="type">Experience Type *</label>
                            <input type="text" id="type" name="type" value="<?php echo htmlspecialchars($experience['type']); ?>" required class="form-control" placeholder="Type your experience type (e.g. river-tours, wellness)">
                        </div>

                        <div class="form-group">
                            <label for="date">Date *</label>
                            <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($experience['date'] ?? ''); ?>" required class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" required class="form-control form-textarea" rows="4" placeholder="Brief description of the experience"><?php echo htmlspecialchars($experience['description']); ?></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h2>Media & Settings</h2>
                    <?php $image = $experience['image'] ?? ''; ?>
                    <?php include 'media-picker.php'; ?> 

                    <div class="form-group checkbox">
                        <input type="checkbox" id="featured" name="featured" <?php echo ($experience['featured'] ?? false) ? 'checked' : ''; ?>>
                        <label for="featured">Mark as Featured Experience</label>
                    </div>

                    <div class="form-actions">
<button type="submit" class="btn btn-primary" onclick="this.removeAttribute('fdprocessedid')" style="position: relative; z-index: 9999;">
                            <?php echo $isEdit ? '✏️ Update Experience' : '➕ Add Experience'; ?>
                        </button>
                        <a href="dashboard.php?tab=experiences" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <footer class="admin-footer">
        <p></p>
    </footer>

    <script src="../assets/js/admin.js"></script>
</body>
</html>
