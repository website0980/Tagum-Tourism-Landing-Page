<?php
require_once 'config.php';
requireAuth();

// Ensure gallery table exists (idempotent)
require_once dirname(__DIR__) . '/database/setup_hotel_gallery.php';
ensureHotelGalleryTable();

$hotels = loadHotels();
$hotelId = isset($_GET['hotel_id']) ? (int)$_GET['hotel_id'] : (isset($_POST['hotel_id']) ? (int)$_POST['hotel_id'] : 0);

$errors = [];
$message = '';

// Paths for uploaded gallery images
require_once __DIR__ . '/config.php'; // for HOTEL_IMAGES_DIR/HOTEL_IMAGES_URL

function getHotelName($hotels, int $id): string {
    foreach ($hotels as $h) {
        if ((int)$h['id'] === $id) return (string)($h['name'] ?? '');
    }
    return '';
}

function normalizeStoredImagePath(string $path): string {
    // Keep whatever is stored; UI will normalize if needed.
    return $path;
}

function handleUploadForHotel(int $hotelId): void {
    if ($hotelId <= 0) {
        throw new RuntimeException('Invalid hotel id');
    }

    if (empty($_FILES['gallery_files']) || empty($_FILES['gallery_files']['name'][0])) {
        return;
    }

    $dbFile = '../database.db';
    if (!file_exists($dbFile)) {
        throw new RuntimeException('Database not found');
    }

    $db = new SQLite3($dbFile);

    // Prevent hitting PHP's max_file_uploads with huge multi-upload batches.
    // Your server warning indicates max allowable uploads exceeded.
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

        // Save into HOTEL_IMAGES_DIR so gallery renders immediately.
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileName = 'hotel_gallery_' . time() . '_' . uniqid() . '.' . $fileExt;
        $filePathAbs = HOTEL_IMAGES_DIR . $fileName;
        $filePathWeb = HOTEL_IMAGES_URL . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $filePathAbs)) {
            throw new RuntimeException('Failed to move uploaded file: ' . $file['name']);
        }


        $caption = '';
        // Optional captions: hotel-gallery-caption-0,1,...
        $captionKey = 'gallery_captions_' . $i;
        if (isset($_POST[$captionKey])) {
            $caption = trim((string)$_POST[$captionKey]);
        }

        $stmt = $db->prepare('INSERT INTO hotel_gallery (hotel_id, image, caption, sort_order) VALUES (?, ?, ?, ?)');
        $sortOrder = (int)$i;
        $stmt->bindValue(1, $hotelId, SQLITE3_INTEGER);
        $stmt->bindValue(2, normalizeStoredImagePath($filePathWeb), SQLITE3_TEXT);
        $stmt->bindValue(3, $caption, SQLITE3_TEXT);
        $stmt->bindValue(4, $sortOrder, SQLITE3_INTEGER);
        $stmt->execute();
    }

    $db->close();
}

function handleDeleteForHotel(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    if (!isset($_POST['delete_gallery_id'])) return;

    $deleteId = (int)$_POST['delete_gallery_id'];
    if ($deleteId <= 0) return;

    $dbFile = '../database.db';
    if (!file_exists($dbFile)) return;

    $db = new SQLite3($dbFile);

    $stmt = $db->prepare('SELECT image FROM hotel_gallery WHERE id = ? LIMIT 1');
    $stmt->bindValue(1, $deleteId, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $row = $result->fetchArray(SQLITE3_ASSOC);

    if ($row && !empty($row['image'])) {
        $fileName = basename((string)$row['image']);
        $absPath = HOTEL_IMAGES_DIR . $fileName;
        if (file_exists($absPath)) unlink($absPath);
    }

    $stmt2 = $db->prepare('DELETE FROM hotel_gallery WHERE id = ?');
    $stmt2->bindValue(1, $deleteId, SQLITE3_INTEGER);
    $stmt2->execute();

    $db->close();
}

function loadGalleryForHotel(int $hotelId): array {
    if ($hotelId <= 0) return [];
    $dbFile = '../database.db';
    if (!file_exists($dbFile)) return [];

    $db = new SQLite3($dbFile);
    $stmt = $db->prepare('SELECT id, image, caption, sort_order FROM hotel_gallery WHERE hotel_id = ? ORDER BY sort_order ASC, id ASC');
    $stmt->bindValue(1, $hotelId, SQLITE3_INTEGER);
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
                handleUploadForHotel($hotelId);
                $message = 'Photos uploaded successfully.';
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
        if ($action === 'delete_gallery_item') {
            try {
                handleDeleteForHotel();
                $message = 'Photo removed.';
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
    }
}

$hotelName = getHotelName($hotels, $hotelId);
$gallery = loadGalleryForHotel($hotelId);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Gallery Upload - Tagum Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .hotel-gallery-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; }
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
            <a href="dashboard.php?tab=hotels" class="back-link" aria-label="Back to Hotels">
                <span class="back-link-icon" aria-hidden="true">←</span>
                <span class="back-link-text">Hotels</span>
            </a>
            <h1 style="margin: 0; font-size: 1.25rem;">Hotel Photo Gallery</h1>
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
            <h2>Select Hotel</h2>
            <form method="GET" class="form-row" style="width:100%;">
                <select name="hotel_id" class="form-control" style="flex: 1; min-width: 260px;" onchange="this.form.submit()">
                    <option value="">-- Select --</option>
                    <?php foreach ($hotels as $h): ?>
                        <option value="<?php echo (int)$h['id']; ?>" <?php echo ((int)$h['id'] === $hotelId) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($h['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <noscript>
                    <button class="btn btn-primary" type="submit">Load</button>
                </noscript>
            </form>
        </div>

        <?php if ($hotelId > 0): ?>
            <div class="form-section">
                <h2>Upload Photos</h2>
                <p style="margin-top:-0.5rem; color:#6b7280;">Hotel: <strong><?php echo htmlspecialchars($hotelName); ?></strong></p>

                <form method="POST" enctype="multipart/form-data">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="upload_gallery">
                    <input type="hidden" name="hotel_id" value="<?php echo (int)$hotelId; ?>">

                    <div class="form-group">
                        <label>Choose photos (multiple allowed)</label>
                        <input type="file" name="gallery_files[]" accept="image/*" multiple required>
                        <small style="display:block; margin-top:.25rem; color:#6b7280;">Max 10 images per upload to avoid server “max allowable file uploads exceeded”.</small>
                    </div>


                    <div class="form-group" style="margin-top:1rem;">
                        <label>Captions (optional)</label>
                        <div style="color:#6b7280; font-size:.9rem;">Captions are aligned by file index. (If you don't fill them, captions stay blank.)</div>
                        <div id="captionInputs" style="margin-top:.5rem;"></div>
                    </div>

                    <div id="uploadProgress" style="display:none; margin-top:1rem; padding:1rem; background:transparent; border-radius:8px; border:1px solid #e5e7eb;">
                        <div style="display:flex; align-items:center; gap:0.75rem;">
                            <div style="flex:1; height:8px; background:#e5e7eb; border-radius:4px; overflow:hidden;">
                                <div id="progressBar" style="width:0%; height:100%; background:#1d5a3d; transition:width 0.3s ease;"></div>
                            </div>
                            <span id="progressText" style="font-weight:600; color:#1d5a3d; min-width:50px;">0%</span>
                        </div>
                        <div id="uploadStatus" style="margin-top:0.5rem; font-size:0.9rem; color:#6b7280;"></div>
                    </div>
<button type="submit" class="btn btn-primary" style="margin-top:1rem;">Upload</button>
                </form>
            </div>

            <div class="form-section">
                <h2>Current Gallery (<?php echo count($gallery); ?>)</h2>
                <?php if (empty($gallery)): ?>
                    <div class="empty-state"><p>📭 No gallery photos yet.</p></div>
                <?php else: ?>
                    <div class="hotel-gallery-grid">
                        <?php foreach ($gallery as $item): ?>
                            <div class="gallery-card">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="Hotel photo" loading="lazy">
                                <div class="gallery-meta">
                                    <div class="caption"><?php echo htmlspecialchars($item['caption'] ?? ''); ?></div>
                                </div>
                                <div class="gallery-actions">
                                    <form method="POST" style="margin:0;">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="delete_gallery_item">
                                        <input type="hidden" name="hotel_id" value="<?php echo (int)$hotelId; ?>">
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


