<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once dirname(__DIR__) . '/includes/events_helpers.php';
requireAuth();

$dbFile = __DIR__ . '/../database.db';

// ==================== DB CONNECT ====================
function connectDB($dbFile) {
    try {
        if (!file_exists($dbFile)) {
            throw new Exception("Database file not found.");
        }
        return new SQLite3($dbFile);
    } catch (Exception $e) {
        die("<h3 style='color:red;'>DB Error:</h3><p>{$e->getMessage()}</p>");
    }
}

// ==================== INIT ====================
$isEdit = false;
$siteIndex = $_GET['id'] ?? $_POST['id'] ?? null;
$siteIndex = ($siteIndex !== null && $siteIndex !== '') ? (int)$siteIndex : null;

$errors = [];

    $site = [
    'name' => '',
    'event_date' => '',
    'location' => '',
    'latitude' => null,
    'longitude' => null,
    'history' => '',
    'highlights' => '',
    'image' => ''
];


// ==================== LOAD ====================
if ($siteIndex !== null) {
    $db = connectDB($dbFile);

    $stmt = $db->prepare("SELECT * FROM events WHERE id = :id");
    $stmt->bindValue(':id', $siteIndex, SQLITE3_INTEGER);

    $result = $stmt->execute();
    $data = $result->fetchArray(SQLITE3_ASSOC);

    if ($data) {
        $site = $data;
        $isEdit = true;
    }

    $db->close();
}

// ==================== HANDLE FORM ====================
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validate CSRF token
    $csrf = $_POST['csrf_token'] ?? '';
    if (!validateCsrfToken($csrf)) {
        $errors[] = "Invalid or expired session. Please refresh and try again.";
    }

    $site['name'] = trim($_POST['name'] ?? '');
    $site['event_date'] = trim($_POST['event_date'] ?? '');
    $site['location'] = trim($_POST['location'] ?? '');
$site['latitude'] = $_POST['latitude'] ?? '';
    $site['longitude'] = $_POST['longitude'] ?? '';
    $site['history'] = trim($_POST['history'] ?? '');
    $site['highlights'] = trim($_POST['highlights'] ?? '');
$site['image'] = $_POST['image'] ?? $site['image'] ?? '';

    if (empty($site['name'])) {
        $errors[] = "Event name is required";
    }
    if (empty($site['event_date'])) {
        $errors[] = "Event date is required (used to group events by month on the website)";
    }

    if (empty($errors)) {

        // IMAGE UPLOAD - Preserve if no new file
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $upload = saveCulturalSiteImage($_FILES['image_file']);
            if ($upload['success']) {
                if (!empty($site['image'])) {
                    deleteCulturalSiteImage($site['image']);
                }
                $site['image'] = $upload['path'];
            } else {
                $errors[] = $upload['error'];
            }
        } elseif ($isEdit && empty($_POST['image'] ?? '')) {
            // Keep original if editing and no new image
        }

        if (empty($errors)) {

            $db = connectDB($dbFile);
            ensureEventDateColumn($db);

            if ($isEdit) {
            $stmt = $db->prepare("
                    UPDATE events SET
                        name = :name,
                        event_date = :event_date,
                        location = :location,
                        latitude = :latitude,
                        longitude = :longitude,
                        history = :history,
                        highlights = :highlights,
                        image = :image
                    WHERE id = :id
                ");
                $stmt->bindValue(':id', $siteIndex, SQLITE3_INTEGER);

            } else {
                    $stmt = $db->prepare("
                    INSERT INTO events (name, event_date, location, latitude, longitude, history, highlights, image)
                    VALUES (:name, :event_date, :location, :latitude, :longitude, :history, :highlights, :image)
                ");
            }

$stmt->bindValue(':name', $site['name'], SQLITE3_TEXT);
            $stmt->bindValue(':event_date', $site['event_date'], SQLITE3_TEXT);
            $stmt->bindValue(':location', $site['location'], SQLITE3_TEXT);
            $stmt->bindValue(':latitude', $site['latitude'], SQLITE3_FLOAT);
            $stmt->bindValue(':longitude', $site['longitude'], SQLITE3_FLOAT);
            $stmt->bindValue(':history', $site['history'], SQLITE3_TEXT);
            $stmt->bindValue(':highlights', $site['highlights'], SQLITE3_TEXT);
            $stmt->bindValue(':image', $site['image'], SQLITE3_TEXT);

$stmt->execute();
            $db->close();

            header("Location: dashboard.php?tab=events&message=" . ($isEdit ? "updated" : "added"));
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Edit' : 'Add'; ?> Event - Tourism Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body>

<header class="admin-header">
    <div class="admin-header-content">
        <div class="admin-title">
            <a href="dashboard.php?tab=events" class="back-link" aria-label="Back to Dashboard">
                <span class="back-link-icon" aria-hidden="true">←</span>
                <span class="back-link-text">Dashboard</span>
            </a>
            <h1><?= $isEdit ? 'Edit' : 'Add New'; ?> Event</h1>
        </div>
    </div>
</header>

<main class="admin-main">
<div class="admin-container">

<?php if (!empty($errors)): ?>
<div class="alert alert-error">
    <ul>
        <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="destination-form">
<?php echo csrfField(); ?>

<?php if ($isEdit): ?>
<input type="hidden" name="id" value="<?= $siteIndex ?>">
<?php endif; ?>

<div class="form-group">
<label>Event Name *</label>
<input type="text" name="name" value="<?= htmlspecialchars($site['name']) ?>" required class="form-control">
</div>

<div class="form-group">
<label>Event Date *</label>
<input type="date" name="event_date" value="<?= htmlspecialchars($site['event_date'] ?? '') ?>" required class="form-control">
<small>Events are grouped by month on the Explore page based on this date.</small>
</div>

    <div class="form-group">
<label>Location <small>(Optional - Auto-filled)</small></label>
<input type="text" id="location-input" name="location" value="<?= htmlspecialchars($site['location']) ?>" class="form-control" placeholder="e.g., Tagum City Hall">
<input type="hidden" id="latitude" name="latitude" value="<?= htmlspecialchars($site['latitude'] ?? '') ?>">
<input type="hidden" id="longitude" name="longitude" value="<?= htmlspecialchars($site['longitude'] ?? '') ?>">
<div id="coords-display" class="mt-2 p-2 bg-light border rounded small" style="display:none;">
    Lat: <span id="lat-display">-</span> | Lng: <span id="lng-display">-</span>
</div>
<div id="map" style="height: 300px; border: 2px solid #e5e7eb; border-radius: 8px; margin-top: 10px;"></div>
<button type="button" id="clear-marker" class="btn btn-secondary btn-small mt-2">🗑️ Remove Pin</button>
</div>

<div class="form-group">
<label>Event Details</label>
<textarea name="history" class="form-textarea"><?= htmlspecialchars($site['history']) ?></textarea>
</div>

<div class="form-group">
<label>Highlights</label>
<textarea name="highlights" class="form-textarea"><?= htmlspecialchars($site['highlights']) ?></textarea>
</div>

<div class="form-group">
<label>Image <?php if ($isEdit && !empty($site['image'])): ?> (Current: <?= htmlspecialchars(basename($site['image'])); ?>) <?php endif; ?></label>
<input type="file" name="image_file" class="form-control">
<?php if ($isEdit && !empty($site['image'])): ?>
<p><small>To replace, upload new. To keep current, leave empty & save.</small></p>
<?php endif; ?>
</div>

<div class="form-actions">
<button type="submit" class="btn btn-primary">Save</button>
<a href="dashboard.php?tab=events" class="btn btn-secondary">Cancel</a>
</div>

</form>

</div>
</main>

    <footer class="admin-footer">
<p></p>
</footer>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
    let map, marker;
    const defaultLat = <?php echo is_numeric($site['latitude'] ?? null) ? (float)$site['latitude'] : 7.443; ?>;
    const defaultLng = <?php echo is_numeric($site['longitude'] ?? null) ? (float)$site['longitude'] : 125.807; ?>;
    const locationInput = document.getElementById('location-input');
    const latDisplay = document.getElementById('lat-display');
    const lngDisplay = document.getElementById('lng-display');
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    const coordsDisplay = document.getElementById('coords-display');

    function debounce(func, wait) {
        let timeout;
        return (...args) => {
            clearTimeout(timeout);
            timeout = setTimeout(() => func(...args), wait);
        };
    }

    function ensureMarker(lat, lng) {
        if (!marker) {
            marker = L.marker([lat, lng], { draggable: true }).addTo(map);
            marker.on('dragend', () => updateCoordsFromMarker(true));
        } else {
            marker.setLatLng([lat, lng]);
        }
    }

    function setCoordsUI(lat, lng) {
        latInput.value = lat.toFixed(6);
        lngInput.value = lng.toFixed(6);
        latDisplay.textContent = lat.toFixed(6);
        lngDisplay.textContent = lng.toFixed(6);
        coordsDisplay.style.display = 'block';
    }

    async function reverseGeocode(lat, lng) {
        try {
            const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`);
            const data = await res.json();
            const addr = data.display_name || `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
            locationInput.value = addr;
        } catch (e) {
            locationInput.value = `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
        }
    }

    async function forwardGeocode(query) {
        if (!query || query.trim().length < 3) return;
        try {
            const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1&countrycodes=ph`);
            const data = await res.json();
            if (!data || !data.length) return;
            const lat = parseFloat(data[0].lat);
            const lng = parseFloat(data[0].lon);
            map.setView([lat, lng], 16);
            ensureMarker(lat, lng);
            setCoordsUI(lat, lng);
            locationInput.value = data[0].display_name || query;
        } catch (e) {
            console.log('Geocode failed');
        }
    }

    async function updateCoordsFromMarker(reverse = false) {
        if (!marker) return;
        const pos = marker.getLatLng();
        setCoordsUI(pos.lat, pos.lng);
        if (reverse) await reverseGeocode(pos.lat, pos.lng);
    }

    function initMap() {
        map = L.map('map').setView([defaultLat, defaultLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);
        ensureMarker(defaultLat, defaultLng);
        setCoordsUI(defaultLat, defaultLng);

        map.on('click', async (e) => {
            ensureMarker(e.latlng.lat, e.latlng.lng);
            await updateCoordsFromMarker(true);
        });

        locationInput.addEventListener('input', debounce((e) => {
            forwardGeocode(e.target.value);
        }, 700));

        document.getElementById('clear-marker').onclick = () => {
            if (marker) {
                map.removeLayer(marker);
                marker = null;
            }
            latInput.value = '';
            lngInput.value = '';
            latDisplay.textContent = '-';
            lngDisplay.textContent = '-';
            locationInput.value = '';
            coordsDisplay.style.display = 'none';
            map.setView([defaultLat, defaultLng], 12);
        };
    }

    window.addEventListener('DOMContentLoaded', initMap);
    </script>
</body>
</html>
