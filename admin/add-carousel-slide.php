<?php
require_once 'config.php';
requireAuth();

$isEdit = false;
$slide = [
    'id' => '',
    'tagline' => '',
    'title' => '',
    'description' => '',
    'image' => '',
    'sort_order' => 0,
    'active' => 1,
];

$id = $_GET['id'] ?? $_POST['id'] ?? null;
if ($id !== null && $id !== '') {
    $id = (int) $id;
    $existing = getCarouselSlideById($id);
    if ($existing) {
        $isEdit = true;
        $slide = $existing;
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrf)) {
        $errors[] = 'Invalid or expired session. Please refresh and try again.';
    }

    $slide = [
        'id' => $_POST['id'] ?? '',
        'tagline' => trim($_POST['tagline'] ?? ''),
        'title' => trim($_POST['title'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'image' => $_POST['image'] ?? ($slide['image'] ?? ''),
        'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        'active' => isset($_POST['active']) ? 1 : 0,
    ];

    if (empty($slide['title'])) {
        $errors[] = 'Main heading is required';
    }
    if (empty($slide['description'])) {
        $errors[] = 'Description is required';
    }
    if (!$isEdit && empty($slide['image']) && (!isset($_FILES['image_file']) || $_FILES['image_file']['error'] !== UPLOAD_ERR_OK)) {
        $errors[] = 'Background image is required';
    }

    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $validation = validateImageUpload($_FILES['image_file']);
        if (!$validation['success']) {
            $errors[] = $validation['error'];
        }
    }

    if (empty($errors)) {
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $result = saveCarouselImage($_FILES['image_file']);
            if ($result['success']) {
                if (!empty($slide['image']) && strpos($slide['image'], 'images/carousel/') === 0) {
                    deleteCarouselImage($slide['image']);
                }
                $slide['image'] = $result['path'];
            } else {
                $errors[] = $result['error'] ?? 'Image upload failed';
            }
        }

        if (empty($errors)) {
            $saved = saveCarouselSlide($slide, $isEdit ? (int) $id : null);
            if ($saved) {
                header('Location: dashboard.php?tab=carousel&message=' . ($isEdit ? 'updated' : 'added'));
                exit();
            }
            $errors[] = 'Failed to save carousel slide';
        }
    }
}

function adminImageSrc($path) {
    if (empty($path)) return '';
    if (strpos($path, 'http') === 0 || strpos($path, '../') === 0) {
        return $path;
    }
    return '../' . ltrim($path, '/');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Edit' : 'Add'; ?> Carousel Slide - Tagum Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <header class="admin-header">
        <div class="admin-header-content">
            <div class="admin-title">
                <a href="dashboard.php?tab=carousel" class="back-link" aria-label="Back to Dashboard">
                    <span class="back-link-icon" aria-hidden="true">←</span>
                    <span class="back-link-text">Dashboard</span>
                </a>
                <h1><?php echo $isEdit ? 'Edit Carousel Slide' : 'Add Carousel Slide'; ?></h1>
            </div>
        </div>
    </header>

    <main class="admin-main">
        <div class="admin-container">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="destination-form">
                <?php echo csrfField(); ?>
                <?php if ($isEdit && $id !== null): ?>
                    <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
                <?php endif; ?>

                <div class="form-section">
                    <h2>Slide Text</h2>
                    <p class="image-option-hint">Use a new line in the main heading for a line break (e.g. "Discover" on one line and "Natural Beauty" on the next).</p>

                    <div class="form-group">
                        <label for="tagline">Small Text (Tagline)</label>
                        <input type="text" id="tagline" name="tagline" value="<?php echo htmlspecialchars($slide['tagline']); ?>" class="form-control" placeholder="e.g., Tagumeños: Beauty that Shines from Within.">
                    </div>

                    <div class="form-group">
                        <label for="title">Main Heading (Big Text) *</label>
                        <textarea id="title" name="title" required class="form-control form-textarea" rows="2" placeholder="Discover&#10;Natural Beauty"><?php echo htmlspecialchars($slide['title']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="description">Description (Smaller Text) *</label>
                        <textarea id="description" name="description" required class="form-control form-textarea" rows="4" placeholder="Supporting paragraph shown below the heading"><?php echo htmlspecialchars($slide['description']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="sort_order">Display Order</label>
                        <input type="number" id="sort_order" name="sort_order" value="<?php echo (int) ($slide['sort_order'] ?? 0); ?>" min="0" class="form-control" style="max-width:120px;">
                        <small>Lower numbers appear first in the carousel.</small>
                    </div>
                </div>

                <div class="form-section">
                    <h2>Background Image</h2>
                    <?php
                    $image = !empty($slide['image']) ? adminImageSrc($slide['image']) : '';
                    $storedImage = $slide['image'] ?? '';
                    include 'media-picker.php';
                    ?>

                    <div class="form-group checkbox">
                        <input type="checkbox" id="active" name="active" <?php echo !empty($slide['active']) ? 'checked' : ''; ?>>
                        <label for="active">Show this slide on the homepage</label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <?php echo $isEdit ? 'Update Slide' : 'Add Slide'; ?>
                        </button>
                        <a href="dashboard.php?tab=carousel" class="btn btn-secondary">Cancel</a>
                        <?php if ($isEdit): ?>
                            <button type="submit" formaction="delete-carousel-slide.php" formmethod="POST" class="btn btn-secondary" style="margin-left:auto;color:#c0392b;" onclick="return confirm('Delete this carousel slide?');">Delete Slide</button>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <footer class="admin-footer"></footer>
    <script src="../assets/js/admin.js"></script>
</body>
</html>
