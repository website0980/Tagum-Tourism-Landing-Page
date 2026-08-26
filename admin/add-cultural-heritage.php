<?php
require_once 'config.php';
requireAuth();

$isEdit = false;
$heritage = [
    'id' => '',
    'title' => '',
    'category' => '',
    'description' => '',
    'image' => '',
    'images' => []
];

$id = $_GET['id'] ?? $_POST['id'] ?? null;
if ($id !== null && $id !== '') {
    $id = (int) $id;
    // Load from JSON for edit
    $heritageData = json_decode(file_get_contents('../Cultural Heritage Module/cultural-heritage.json'), true) ?? [];
    foreach ($heritageData as $item) {
        if ((int)$item['id'] === $id) {
            $isEdit = true;
            $heritage = $item;
            break;
        }
    }
}

$errors = [];
$message = '';

// Handle file upload
function uploadHeritageImage($file, $existingPath = '') {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload error'];
    }
    
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.'];
    }
    
    $maxSize = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File size exceeds 5MB limit.'];
    }
    
    $uploadDir = '../images/cultural-heritage/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('heritage_') . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Delete old image if exists
        if ($existingPath && file_exists($existingPath)) {
            unlink($existingPath);
        }
        return ['success' => true, 'path' => 'images/cultural-heritage/' . $filename];
    }
    
    return ['success' => false, 'error' => 'Failed to move uploaded file.'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $heritage = [
        'id' => $_POST['id'] ?? '',
        'title' => $_POST['title'] ?? '',
        'category' => $_POST['category'] ?? '',
        'description' => $_POST['description'] ?? '',
        'image' => $heritage['image'] ?? '',
        'images' => $heritage['images'] ?? []
    ];

    // Handle main image upload
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $result = uploadHeritageImage($_FILES['image_file'], $heritage['image']);
        if ($result['success']) {
            $heritage['image'] = $result['path'];
        } else {
            $errors[] = $result['error'];
        }
    }

    // Handle gallery images upload
    if (isset($_FILES['gallery_files']) && !empty($_FILES['gallery_files']['name'][0])) {
        $galleryPaths = [];
        foreach ($_FILES['gallery_files']['name'] as $key => $name) {
            if ($_FILES['gallery_files']['error'][$key] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['gallery_files']['name'][$key],
                    'type' => $_FILES['gallery_files']['type'][$key],
                    'tmp_name' => $_FILES['gallery_files']['tmp_name'][$key],
                    'error' => $_FILES['gallery_files']['error'][$key],
                    'size' => $_FILES['gallery_files']['size'][$key]
                ];
                $result = uploadHeritageImage($file);
                if ($result['success']) {
                    $galleryPaths[] = $result['path'];
                }
            }
        }
        if (!empty($galleryPaths)) {
            $heritage['images'] = $galleryPaths;
        }
    }

    // Validation
    if (empty($heritage['title'])) $errors[] = 'Title is required';
    if (empty($heritage['category'])) $errors[] = 'Category is required';
    if (empty($heritage['description'])) $errors[] = 'Description is required';

    // Save if no errors
    if (empty($errors)) {
        $heritageData = json_decode(file_get_contents('../Cultural Heritage Module/cultural-heritage.json'), true) ?? [];
        
        if ($isEdit && !empty($heritage['id'])) {
            // Update existing
            foreach ($heritageData as $key => $item) {
                if ((int)$item['id'] === (int)$heritage['id']) {
                    $heritageData[$key] = $heritage;
                    break;
                }
            }
        } else {
            // Add new
            if (empty($heritageData)) {
                $heritage['id'] = 1;
            } else {
                $maxId = max(array_column($heritageData, 'id'));
                $heritage['id'] = $maxId + 1;
            }
            $heritageData[] = $heritage;
        }
        
        file_put_contents('../Cultural Heritage Module/cultural-heritage.json', json_encode($heritageData, JSON_PRETTY_PRINT));
        header('Location: dashboard.php?tab=cultural-heritage&message=' . ($isEdit ? 'updated' : 'added'));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Edit' : 'Add'; ?> Cultural Heritage - Tagum Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="admin-header-content">
            <div class="admin-title">
                <a href="dashboard.php?tab=cultural-heritage" class="back-link" aria-label="Back to Dashboard">
                    <span class="back-link-icon" aria-hidden="true">←</span>
                    <span class="back-link-text">Dashboard</span>
                </a>
                <h1><?php echo $isEdit ? 'Edit Cultural Heritage' : 'Add New Cultural Heritage'; ?></h1>
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
                        <label for="title">Title *</label>
                        <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($heritage['title']); ?>" required class="form-control" placeholder="e.g., Historical Landmarks">
                    </div>

                    <div class="form-group">
                        <label for="category">Category *</label>
                        <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($heritage['category']); ?>" required class="form-control" placeholder="e.g., Historical Sites, Traditions">
                    </div>

                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" required class="form-control form-textarea" rows="6" placeholder="Detailed description of the cultural heritage item"><?php echo htmlspecialchars($heritage['description']); ?></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h2>Media</h2>
                    
                    <div class="form-group">
                        <label for="image_file">Main Image</label>
                        <input type="file" id="image_file" name="image_file" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" class="form-control">
                        <small>Allowed formats: JPG, PNG, GIF, WEBP (Max 5MB)</small>
                    </div>

                    <?php if (!empty($heritage['image'])): ?>
                    <div class="current-image">
                        <img src="<?php echo htmlspecialchars($heritage['image']); ?>" alt="Current image" loading="lazy">
                        <p>Current image: <?php echo htmlspecialchars($heritage['image']); ?></p>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="gallery_files">Gallery Images</label>
                        <input type="file" id="gallery_files" name="gallery_files[]" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" multiple class="form-control">
                        <small>You can select multiple images. Allowed formats: JPG, PNG, GIF, WEBP (Max 5MB each)</small>
                    </div>

                    <?php if (!empty($heritage['images'])): ?>
                    <div class="current-gallery">
                        <p>Current gallery images:</p>
                        <div class="gallery-preview">
                            <?php foreach ($heritage['images'] as $img): ?>
                                <img src="<?php echo htmlspecialchars($img); ?>" alt="Gallery image" loading="lazy">
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $isEdit ? '✏️ Update Cultural Heritage' : '➕ Add Cultural Heritage'; ?>
                        </button>
                        <a href="dashboard.php?tab=cultural-heritage" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
                
                <style>
                    .current-gallery {
                        margin-top: 1rem;
                    }
                    .gallery-preview {
                        display: grid;
                        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
                        gap: 0.5rem;
                        margin-top: 0.5rem;
                    }
                    .gallery-preview img {
                        width: 100%;
                        height: 80px;
                        object-fit: cover;
                        border-radius: 4px;
                    }
                </style>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <footer class="admin-footer">
        <p></p>
    </footer>
</body>
</html>
