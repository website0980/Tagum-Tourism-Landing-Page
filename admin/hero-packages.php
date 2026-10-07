<?php
require_once 'config.php';
requireAuth();

// Resolve an image path stored in the DB to a URL that works from the /admin/ directory.
// Handles full URLs, absolute paths, already-prefixed relative paths, and bare relative paths.
function adminImagePath($path) {
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }
    // Full URL (http:// or https://) - leave as-is
    if (stripos($path, 'http') === 0) {
        return $path;
    }
    // Absolute path from site root (e.g. /images/...) - leave as-is
    if (strpos($path, '/') === 0) {
        return $path;
    }
    // Already prefixed to escape the /admin/ directory - leave as-is
    if (strpos($path, '../') === 0) {
        return $path;
    }
    // Bare relative path (e.g. images/destinations/...) - prepend ../ to escape /admin/
    return '../' . $path;
}

$packageMonth = (int) ($_GET['month'] ?? 0);
if ($packageMonth < 1 || $packageMonth > 12) {
    header('Location: dashboard.php?tab=carousel');
    exit();
}

$package = loadHeroPackage($packageMonth);
$package = array_merge([
    'active' => 0,
    'title' => '',
    'script' => '',
    'description' => '',
    'meta_1' => '',
    'meta_2' => '',
    'meta_3' => '',
    'cta' => '',
    'theme' => 'theme-chinese-new-year',
    'image_1' => '',
    'image_2' => '',
    'image_3' => '',
], $package);
$imageFields = ['image_1', 'image_2', 'image_3'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid or expired session. Please refresh and try again.';
    }

    $packageData = [
        'month' => $packageMonth,
        'active' => isset($_POST['active']) ? 1 : 0,
        'title' => trim((string) ($_POST['title'] ?? '')),
        'script' => trim((string) ($_POST['script'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
        'meta_1' => trim((string) ($_POST['meta_1'] ?? '')),
        'meta_2' => trim((string) ($_POST['meta_2'] ?? '')),
        'meta_3' => trim((string) ($_POST['meta_3'] ?? '')),
        'cta' => trim((string) ($_POST['cta'] ?? '')),
        'theme' => in_array($_POST['theme'] ?? '', ['theme-chinese-new-year', 'theme-musikahan-blue', 'theme-soft', 'theme-araw-green'], true)
            ? $_POST['theme']
            : 'theme-chinese-new-year',
    ];

    foreach (['title', 'script', 'description', 'meta_1', 'meta_2', 'meta_3', 'cta'] as $field) {
        if ($packageData[$field] === '') {
            $errors[] = 'Please complete every text field.';
        }
    }

    $uploadedImages = [];
    foreach ($imageFields as $field) {
        if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
            $validation = validateImageUpload($_FILES[$field]);
            if (!$validation['success']) {
                $errors[] = $validation['error'];
            } else {
                $uploadedImages[$field] = saveHeroPackageImage($_FILES[$field]);
                if (!$uploadedImages[$field]['success']) {
                    $errors[] = $uploadedImages[$field]['error'];
                }
            }
        }
    }

    if (!$package) {
        foreach ($imageFields as $field) {
            if (empty($uploadedImages[$field])) {
                $errors[] = 'Upload one image in each of the three picture slots.';
            }
        }
    }

    if (empty($errors)) {
        $packageData['image_1'] = $uploadedImages['image_1']['path'] ?? $package['image_1'];
        $packageData['image_2'] = $uploadedImages['image_2']['path'] ?? $package['image_2'];
        $packageData['image_3'] = $uploadedImages['image_3']['path'] ?? $package['image_3'];
        if (saveHeroPackage($packageData)) {
            header('Cache-Control: no-store, no-cache, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            header('Location: dashboard.php?tab=carousel&message=hero_package_saved&month=' . $packageMonth, true, 303);
            exit();
        }
        $errors[] = 'The package could not be saved.';
    }
}

$monthName = date('F', mktime(0, 0, 0, $packageMonth, 1, 2026));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $monthName; ?> Hero Package - Tagum Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
    <header class="admin-header">
        <div class="admin-header-content">
            <div class="admin-title">
                <a href="dashboard.php?tab=carousel" class="back-link">← Carousel</a>
                <h1><?php echo $monthName; ?> Hero Package</h1>
            </div>
        </div>
    </header>

    <main class="admin-main">
        <div class="admin-container">
            <?php if ($errors): ?>
                <div class="alert alert-error">
                    <?php foreach ($errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="hero-package-form">
                <?php echo csrfField(); ?>
                <div class="hero-package-layout">
                    <section class="hero-package-card hero-package-content-card">
                        <div class="hero-package-heading">
                            <div>
                                <span class="hero-package-eyebrow">Monthly hero</span>
                                <h2>Package details</h2>
                            </div>
                            <label class="package-active-control">
                                <input type="checkbox" name="active" value="1" <?php echo !empty($package['active']) ? 'checked' : ''; ?>>
                                <span>Show this package on the website</span>
                            </label>
                        </div>

                        <div class="hero-package-grid">
                            <div class="form-group">
                                <label for="title">Hero title</label>
                                <input id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($package['title']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="script">Hero script</label>
                                <input id="script" name="script" class="form-control" value="<?php echo htmlspecialchars($package['script']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" class="form-textarea" rows="3" required><?php echo htmlspecialchars($package['description']); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="cta">Button text</label>
                                <input id="cta" name="cta" class="form-control" value="<?php echo htmlspecialchars($package['cta']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="meta_1">Label 1</label>
                                <input id="meta_1" name="meta_1" class="form-control" value="<?php echo htmlspecialchars($package['meta_1']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="meta_2">Label 2</label>
                                <input id="meta_2" name="meta_2" class="form-control" value="<?php echo htmlspecialchars($package['meta_2']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="meta_3">Label 3</label>
                                <input id="meta_3" name="meta_3" class="form-control" value="<?php echo htmlspecialchars($package['meta_3']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="theme">Hero style</label>
                                <select id="theme" name="theme" class="form-control">
                                    <option value="theme-chinese-new-year" <?php echo $package['theme'] === 'theme-chinese-new-year' ? 'selected' : ''; ?>>Chinese New Year</option>
                                    <option value="theme-musikahan-blue" <?php echo $package['theme'] === 'theme-musikahan-blue' ? 'selected' : ''; ?>>Musikahan lavender and blue</option>
                                    <option value="theme-soft" <?php echo $package['theme'] === 'theme-soft' ? 'selected' : ''; ?>>Spring soft green</option>
                                    <option value="theme-araw-green" <?php echo $package['theme'] === 'theme-araw-green' ? 'selected' : ''; ?>>Araw ng Tagum green and gold</option>
                                </select>
                            </div>
                        </div>
                    </section>

                    <section class="hero-package-card">
                        <div class="hero-package-heading">
                            <div>
                                <span class="hero-package-eyebrow">Three-image package</span>
                                <h2>Hero pictures</h2>
                            </div>
                            <span class="package-limit">3 / 3 slots</span>
                        </div>

                        <?php foreach ($imageFields as $index => $field): ?>
                            <div class="package-image-slot">
                                <div class="package-image-slot-heading">
                                    <strong>Picture <?php echo $index + 1; ?></strong>
                                    <span><?php echo $index === 0 ? 'Main picture' : ($index === 1 ? 'Side picture' : 'Supporting picture'); ?></span>
                                </div>
                                <?php $imagePath = $package[$field] ?? ''; ?>
                                <?php if ($imagePath): ?>
                                    <div class="package-image-preview">
                                        <img src="<?php echo htmlspecialchars(adminImagePath($imagePath)); ?>" alt="Current <?php echo $monthName; ?> picture <?php echo $index + 1; ?>">
                                    </div>
                                <?php endif; ?>
                                <span class="package-image-status" data-image-status="<?php echo $imagePath ? 'uploaded' : 'empty'; ?>">
                                    <?php echo $imagePath ? '✓ Image already uploaded' : '○ No image uploaded'; ?>
                                </span>
                                <label class="package-upload-label" for="<?php echo $field; ?>">
                                    <?php echo $imagePath ? 'Replace picture' : 'Upload picture'; ?>
                                    <input id="<?php echo $field; ?>" name="<?php echo $field; ?>" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
                                </label>
                                <small>JPG, PNG, WebP, or GIF. Maximum 10 MB.</small>
                            </div>
                        <?php endforeach; ?>

                        <div class="hero-package-actions">
                            <button type="submit" class="btn btn-primary">Save <?php echo $monthName; ?> Package</button>
                            <a href="dashboard.php?tab=carousel" class="btn btn-secondary">Cancel</a>
                        </div>
                    </section>
                </div>
            </form>
        </div>
    </main>

    <script>
        document.querySelectorAll('.hero-package-form').forEach((form) => {
            const submitButton = form.querySelector('button[type="submit"]');

            form.querySelectorAll('input[type="file"]').forEach((input) => {
                input.addEventListener('change', () => {
                    const status = input.closest('.package-image-slot').querySelector('[data-image-status]');
                    if (input.files.length > 0) {
                        status.dataset.imageStatus = 'uploading';
                        status.textContent = '⏳ Uploading image…';
                        status.classList.add('is-uploading');
                    }
                });
            });

            form.addEventListener('submit', () => {
                form.querySelectorAll('[data-image-status="uploading"]').forEach((status) => {
                    status.textContent = '⏳ Uploading image…';
                    status.classList.add('is-uploading');
                });
                submitButton.disabled = true;
                submitButton.textContent = 'Uploading package…';
            });
        });
    </script>
</body>
</html>
