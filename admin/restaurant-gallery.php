<?php
require_once 'config.php';
requireAuth();

// Ensure gallery table exists (idempotent)
require_once dirname(__DIR__) . '/database/setup_restaurant_gallery.php';
ensureRestaurantGalleryTable();

$restaurants = loadRestaurants();
$restaurantId = isset($_GET['restaurant_id']) ? (int)$_GET['restaurant_id'] : (isset($_POST['restaurant_id']) ? (int)$_POST['restaurant_id'] : 0);

$errors = [];
$message = '';

// Paths for uploaded gallery images
require_once __DIR__ . '/config.php'; // for RESTAURANT_IMAGES_DIR/RESTAURANT_IMAGES_URL

function getRestaurantName($restaurants, int $id): string {
    foreach ($restaurants as $r) {
        if ((int)$r['id'] === $id) return (string)($r['name'] ?? '');
    }
    return '';
}

function normalizeStoredImagePath(string $path): string {
    // Keep whatever is stored; UI will normalize if needed.
    return $path;
}

function handleUploadForRestaurant(int $restaurantId): void {
    global $errors;
    if ($restaurantId <= 0) {
        throw new RuntimeException('Invalid restaurant id');
    }

    if (empty($_FILES['gallery_files']) || empty($_FILES['gallery_files']['name'][0])) {
        return;
    }

    // Ensure directory exists
    if (!is_dir(RESTAURANT_IMAGES_DIR)) {
        if (!mkdir(RESTAURANT_IMAGES_DIR, 0755, true)) {
            throw new RuntimeException('Failed to create directory: ' . RESTAURANT_IMAGES_DIR);
        }
    }

    $dbFile = '../database.db';
    if (!file_exists($dbFile)) {
        throw new RuntimeException('Database not found');
    }

    $db = new SQLite3($dbFile);
    $db->busyTimeout(60000); // Wait up to 60 seconds for database to unlock
    $db->exec('PRAGMA journal_mode = WAL;'); // Enable WAL mode for better concurrency

    // Prevent hitting PHP's max_file_uploads with huge multi-upload batches.
    $maxFilesPerUpload = 10;
    $count = count($_FILES['gallery_files']['name']);
    if ($count > $maxFilesPerUpload) {
        $errors[] = 'You selected ' . $count . ' files. Upload is limited to ' . $maxFilesPerUpload . ' images per request.';
        $count = $maxFilesPerUpload;
    }

    for ($i = 0; $i < $count; $i++) {
        $file = [
            'name' => $_FILES['gallery_files']['name'][$i],
            'type' => $_FILES['gallery_files']['type'][$i],
            'tmp_name' => $_FILES['gallery_files']['tmp_name'][$i],
            'error' => $_FILES['gallery_files']['error'][$i],
            'size' => $_FILES['gallery_files']['size'][$i],
        ];

        if (empty($file['name'])) {
            continue;
        }

        // If a single file fails to upload, skip it but report the error.
        $errCode = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errCode !== UPLOAD_ERR_OK) {
            $errors[] = 'Photo #'.($i+1).' upload failed (error code: '.$errCode.').';
            continue;
        }

        $validation = validateImageUpload($file);
        if (!$validation['success']) {
            throw new RuntimeException('Upload failed: ' . ($validation['error'] ?? 'Invalid image'));
        }

        // Save into RESTAURANT_IMAGES_DIR so gallery renders immediately.
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileName = 'restaurant_gallery_' . time() . '_' . uniqid() . '.' . $fileExt;
        $filePathAbs = RESTAURANT_IMAGES_DIR . $fileName;
        $filePathWeb = RESTAURANT_IMAGES_URL . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $filePathAbs)) {
            throw new RuntimeException('Failed to move uploaded file: ' . $file['name']);
        }

        $caption = '';
        // Optional captions: restaurant-gallery-caption-0,1,...
        $captionKey = 'gallery_captions_' . $i;
        if (isset($_POST[$captionKey])) {
            $caption = trim((string)$_POST[$captionKey]);
        }

        $stmt = $db->prepare('INSERT INTO restaurant_gallery (restaurant_id, image, caption, sort_order) VALUES (?, ?, ?, ?)');
        $sortOrder = (int)$i;
        $stmt->bindValue(1, $restaurantId, SQLITE3_INTEGER);
        $stmt->bindValue(2, normalizeStoredImagePath($filePathWeb), SQLITE3_TEXT);
        $stmt->bindValue(3, $caption, SQLITE3_TEXT);
        $stmt->bindValue(4, $sortOrder, SQLITE3_INTEGER);
        $stmt->execute();
    }

    $db->close();
}

function handleDeleteForRestaurant(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    if (!isset($_POST['delete_gallery_id'])) return;

    $deleteId = (int)$_POST['delete_gallery_id'];
    if ($deleteId <= 0) return;

    $dbFile = '../database.db';
    if (!file_exists($dbFile)) return;

    $db = new SQLite3($dbFile);

    $stmt = $db->prepare('SELECT image FROM restaurant_gallery WHERE id = ? LIMIT 1');
    $stmt->bindValue(1, $deleteId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);

    if ($row && !empty($row['image'])) {
        $fileName = basename((string)$row['image']);
        $absPath = RESTAURANT_IMAGES_DIR . $fileName;
        if (file_exists($absPath)) unlink($absPath);
    }

    $stmt2 = $db->prepare('DELETE FROM restaurant_gallery WHERE id = ?');
    $stmt2->bindValue(1, $deleteId, SQLITE3_INTEGER);
    $stmt2->execute();

    $db->close();
}

function loadGalleryForRestaurant(int $restaurantId): array {
    if ($restaurantId <= 0) return [];
    $dbFile = '../database.db';
    if (!file_exists($dbFile)) return [];

    $db = new SQLite3($dbFile);
    $stmt = $db->prepare('SELECT id, image, caption, sort_order FROM restaurant_gallery WHERE restaurant_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->bindValue(1, $restaurantId, SQLITE3_INTEGER);
    $result = $stmt->execute();

    $out = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $out[] = $row;
    }
    $db->close();
    return $out;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // basic CSRF protection
    $tokenOk = true;
    if (isset($_POST['csrf_token'])) {
        $tokenOk = validateCsrfToken($_POST['csrf_token']);
    }
    if (!$tokenOk) {
        $errors[] = 'Invalid CSRF token.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'upload_gallery') {
            try {
                handleUploadForRestaurant($restaurantId);
                $message = 'Photos uploaded successfully.';
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
        if ($action === 'delete_gallery_item') {
            try {
                handleDeleteForRestaurant();
                $message = 'Photo removed.';
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
    }
}

$restaurantName = getRestaurantName($restaurants, $restaurantId);
$gallery = loadGalleryForRestaurant($restaurantId);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Gallery Upload - Tagum Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .restaurant-gallery-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; }
        .gallery-card { border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; display: flex; flex-direction: column; height: 100%; }
        .gallery-card img { width: 100%; height: 200px; object-fit: cover; display: block; }
        .gallery-card .gallery-meta { padding: .75rem; flex-shrink: 0; }
        .gallery-card .gallery-meta .caption { font-size: .9rem; color: #6b7280; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .gallery-actions { padding: .75rem; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end; flex-shrink: 0; }
        .form-row { display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; }
        input[type=file] { width: 100%; }
        .caption-input { width: 100%; }
    </style>
</head>
<body>
<header class="admin-header">
    <div class="admin-header-content">
        <div class="admin-title">
            <a href="dashboard.php?tab=restaurants" class="back-link" aria-label="Back to Restaurants">
                <span class="back-link-icon" aria-hidden="true">←</span>
                <span class="back-link-text">Restaurants</span>
            </a>
            <h1 style="margin: 0; font-size: 1.25rem;">Restaurant Photo Gallery</h1>
        </div>
        <div class="admin-nav">
            <span class="admin-user">Welcome, <strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong></span>
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

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <div class="form-section">
            <h2>Select Restaurant</h2>
            <form method="GET" class="form-row" style="width:100%;">
                <select name="restaurant_id" class="form-control" style="flex: 1; min-width: 260px;" onchange="this.form.submit()">
                    <option value="">-- Select --</option>
                    <?php foreach ($restaurants as $r): ?>
                        <option value="<?php echo (int)$r['id']; ?>" <?php echo ((int)$r['id'] === $restaurantId) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($r['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <noscript>
                    <button class="btn btn-primary" type="submit">Load</button>
                </noscript>
            </form>
        </div>

        <?php if ($restaurantId > 0): ?>
            <div class="form-section">
                <h2>Upload Photos</h2>
                <p style="margin-top:-0.5rem; color:#6b7280;">Restaurant: <strong><?php echo htmlspecialchars($restaurantName); ?></strong></p>

                <form method="POST" enctype="multipart/form-data">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="upload_gallery">
                    <input type="hidden" name="restaurant_id" value="<?php echo (int)$restaurantId; ?>">

                    <div class="form-group">
                        <label>Choose photos (multiple allowed)</label>
                        <input type="file" name="gallery_files[]" accept="image/*" multiple required>
                        <small style="display:block; margin-top:.25rem; color:#6b7280;">Max 10 images per upload to avoid server "max allowable file uploads exceeded".</small>
                    </div>

                    <div class="form-group" style="margin-top:1rem;">
                        <label>Captions (optional)</label>
                        <div style="color:#6b7280; font-size:.9rem;">Captions are aligned by file index. (If you don't fill them, captions stay blank.)</div>
                        <div id="captionInputs" style="margin-top:.5rem;"></div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="margin-top:1rem;">Upload</button>
                </form>
            </div>

            <div class="form-section">
                <h2>Current Gallery (<?php echo count($gallery); ?>)</h2>
                <?php if (empty($gallery)): ?>
                    <div class="empty-state"><p>📭 No gallery photos yet.</p></div>
                <?php else: ?>
                    <div class="restaurant-gallery-grid">
                        <?php foreach ($gallery as $item): ?>
                            <div class="gallery-card">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="Restaurant photo" loading="lazy">
                                <div class="gallery-meta">
                                    <div class="caption"><?php echo htmlspecialchars($item['caption'] ?? ''); ?></div>
                                </div>
                                <div class="gallery-actions">
                                    <form method="POST" style="margin:0;">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="delete_gallery_item">
                                        <input type="hidden" name="restaurant_id" value="<?php echo (int)$restaurantId; ?>">
                                        <input type="hidden" name="delete_gallery_id" value="<?php echo (int)$item['id']; ?>">
                                        <button type="submit" class="btn btn-small btn-delete" onclick="return confirm('Delete this photo?')">Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
    // Build caption inputs based on selected files
    const fileInput = document.querySelector('input[type="file"][name="gallery_files[]"]');
    const captionWrap = document.getElementById('captionInputs');

    function rebuildCaptions(count) {
        if (!captionWrap) return;
        captionWrap.innerHTML = '';
        for (let i = 0; i < count; i++) {
            const row = document.createElement('div');
            row.style.marginTop = '0.5rem';
            row.innerHTML = `
                <input class="form-control caption-input" type="text" name="gallery_captions_${i}" placeholder="Caption for photo #${i + 1} (optional)">
            `;
            captionWrap.appendChild(row);
        }
    }

    if (fileInput) {
        fileInput.addEventListener('change', (e) => {
            const files = e.target.files;
            rebuildCaptions(files ? files.length : 0);
        });
    }
</script>
<script src="upload-progress.js"></script>
</body>
</html>
