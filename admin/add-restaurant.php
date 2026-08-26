<?php
// Add/Edit Restaurant Page - Copy of hotel pattern
require_once 'config.php';
requireAuth();

// Temp loadHotels renamed to loadRestaurants in config later
$restaurants = loadRestaurants();

$isEdit = false;
$restaurantIndex = $_GET['id'] ?? $_POST['id'] ?? null;
if ($restaurantIndex !== null && $restaurantIndex !== '') {
    $restaurantIndex = (int) $restaurantIndex;
}
$errors = [];
$message = '';
$originalImage = '';
$restaurant = [
    'name' => '',
    'description' => '',
    'price' => '',
    'location' => '',
    'contact' => '',
    'information' => '',
    'latitude' => '',
    'longitude' => '',
    'opening_time' => '',
    'closing_time' => '',
    'time_slots' => '',
    'image' => ''
];

// If editing, load the restaurant
if ($restaurantIndex !== null && $restaurantIndex !== '') {
    foreach ($restaurants as $r) {
        if ((int)$r['id'] === $restaurantIndex) {
            $isEdit = true;
            $restaurant = $r;
            $originalImage = $restaurant['image'];
            break;
        }
    }
}

    // Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: temporarily show whether POST is received
    // (Remove later if not needed)
    // echo '<div style="padding:8px;background:#fee;border:1px solid #f99;">POST received</div>';
    $restaurant = [
        'name' => trim($_POST['name'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'price' => '',
        'category' => 'Restaurant',
        'location' => trim($_POST['location'] ?? ''),
        'contact' => trim($_POST['contact'] ?? ''),
        'information' => '',
        'latitude' => is_numeric($_POST['latitude'] ?? null) ? (float)$_POST['latitude'] : null,
        'longitude' => is_numeric($_POST['longitude'] ?? null) ? (float)$_POST['longitude'] : null,
        'opening_time' => trim($_POST['opening_time'] ?? ''),
        'closing_time' => trim($_POST['closing_time'] ?? ''),
        'time_slots' => trim($_POST['time_slots'] ?? ''),
        'rating' => 4.0,
        'image' => $_POST['image'] ?? ''
    ];

    // Preserve original image if editing
    if ($isEdit && empty($restaurant['image']) && empty($_FILES['image_file']['name'] ?? '')) {
        $restaurant['image'] = $originalImage;
    }

    // Validate image
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $validation = validateImageUpload($_FILES['image_file']);
        if (!$validation['success']) {
            $errors[] = $validation['error'];
        }
    }

    // Basic validation
    if (empty($restaurant['name'])) $errors[] = 'Restaurant name is required';

// Save if no errors
    if (empty($errors)) {
        // Only run image upload if a new file was selected.
        if (isset($_FILES['image_file']) && ($_FILES['image_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $result = saveRestaurantImage($_FILES['image_file']);
            if ($result['success']) {
                // Delete old image only when replacing
                if ($isEdit && !empty($restaurant['image'])) {
                    deleteRestaurantImage($restaurant['image']);
                }
                $restaurant['image'] = $result['path'];
            } else {
                $errors[] = $result['error'] ?? 'Image upload failed';
            }
        }

        if (empty($errors)) {
            $dbFile = '../database.db';
            if (!file_exists($dbFile)) {
                $errors[] = 'Database file not found: ' . $dbFile;
            } else {
                try {
                    $db = new SQLite3($dbFile);
                    if ($isEdit) {
                        $stmt = $db->prepare('UPDATE restaurant_items SET name = ?, description = ?, location = ?, contact = ?, latitude = ?, longitude = ?, opening_time = ?, closing_time = ?, time_slots = ?, image = ? WHERE id = ?');
                        $stmt->bindValue(1, $restaurant['name'], SQLITE3_TEXT);
                        $stmt->bindValue(2, $restaurant['description'], SQLITE3_TEXT);
                        $stmt->bindValue(3, $restaurant['location'], SQLITE3_TEXT);
                        $stmt->bindValue(4, $restaurant['contact'], SQLITE3_TEXT);
                        $stmt->bindValue(5, $restaurant['latitude'], SQLITE3_FLOAT);
                        $stmt->bindValue(6, $restaurant['longitude'], SQLITE3_FLOAT);
                        $stmt->bindValue(7, $restaurant['opening_time'], SQLITE3_TEXT);
                        $stmt->bindValue(8, $restaurant['closing_time'], SQLITE3_TEXT);
                        $stmt->bindValue(9, $restaurant['time_slots'], SQLITE3_TEXT);
                        $stmt->bindValue(10, $restaurant['image'], SQLITE3_TEXT);
                        $stmt->bindValue(11, $restaurantIndex, SQLITE3_INTEGER);
                        $stmt->execute();

                        // If nothing was updated, treat it as failure (useful for debugging).
                        $changes = $db->changes();
                        if ($changes <= 0) {
                            $errors[] = 'Update failed. ID: ' . $restaurantIndex . ' (changes=' . $changes . ')';
                        }
                    } else {
                        $stmt = $db->prepare('INSERT INTO restaurant_items (name, description, location, contact, latitude, longitude, opening_time, closing_time, time_slots, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                        $stmt->bindValue(1, $restaurant['name'], SQLITE3_TEXT);
                        $stmt->bindValue(2, $restaurant['description'], SQLITE3_TEXT);
                        $stmt->bindValue(3, $restaurant['location'], SQLITE3_TEXT);
                        $stmt->bindValue(4, $restaurant['contact'], SQLITE3_TEXT);
                        $stmt->bindValue(5, $restaurant['latitude'], SQLITE3_FLOAT);
                        $stmt->bindValue(6, $restaurant['longitude'], SQLITE3_FLOAT);
                        $stmt->bindValue(7, $restaurant['opening_time'], SQLITE3_TEXT);
                        $stmt->bindValue(8, $restaurant['closing_time'], SQLITE3_TEXT);
                        $stmt->bindValue(9, $restaurant['time_slots'], SQLITE3_TEXT);
                        $stmt->bindValue(10, $restaurant['image'], SQLITE3_TEXT);
                        $stmt->execute();
                    }

                    $db->close();
                } catch (Throwable $e) {
                    $errors[] = 'Database error: ' . $e->getMessage();
                }
            }

            if (empty($errors)) {
                clearCache('loadRestaurants');
                $message = $isEdit ? 'updated' : 'added';
                header('Location: dashboard.php?tab=restaurants&message=' . $message);
                exit();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Edit' : 'Add'; ?> Restaurant - Tourism Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body class="edit-mode" <?php if ($isEdit) echo 'class="edit-mode"'; ?>>
    <header class="admin-header">
        <div class="admin-header-content">
            <div class="admin-title">
                <a href="dashboard.php?tab=restaurants" class="back-link" aria-label="Back to Dashboard">
                    <span class="back-link-icon" aria-hidden="true">←</span>
                    <span class="back-link-text">Dashboard</span>
                </a>
                <h1><?php echo $isEdit ? 'Edit' : 'Add New'; ?> Restaurant</h1>
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

            <form method="POST" enctype="multipart/form-data" class="admin-form">
                <?php if ($isEdit && $restaurantIndex !== null): ?>
                <input type="hidden" name="id" value="<?php echo (int) $restaurantIndex; ?>">
                <?php endif; ?>
                <div class="form-section">
                    <h2>Restaurant Information</h2>
                    <div class="form-group">
                        <label for="name">Restaurant Name *</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($restaurant['name']); ?>" required class="form-control" placeholder="e.g., Grand Palace Filipino">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="location">Location <small>(Type place name or pick from map)</small></label>
                            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($restaurant['location'] ?? ''); ?>" class="form-control" placeholder="e.g., Gaisano">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Location Picker</label>
                            <button type="button" id="use-my-location" class="btn btn-secondary btn-small">📍 Use My Location</button>
                            <div id="map-container" style="height: 300px; border: 2px solid var(--light-gray); border-radius: 8px; margin-top: 0.5rem;"></div>
                        </div>
                        <div class="form-group">
                            <label for="latitude">Latitude <small>(Auto-filled)</small></label>
                            <input 
                                type="number" 
                                id="latitude" 
                                name="latitude" 
                                step="any"
                                value="<?php echo htmlspecialchars($restaurant['latitude'] ?? ''); ?>" 
                                class="form-control"
                                readonly
                            >
                        </div>
                        <div class="form-group">
                            <label for="longitude">Longitude <small>(Auto-filled)</small></label>
                            <input 
                                type="number" 
                                id="longitude" 
                                name="longitude" 
                                step="any"
                                value="<?php echo htmlspecialchars($restaurant['longitude'] ?? ''); ?>" 
                                class="form-control"
                                readonly
                            >
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contact">Contact</label>
                            <input type="text" id="contact" name="contact" value="<?php echo htmlspecialchars($restaurant['contact'] ?? ''); ?>" class="form-control" placeholder="+63 912 345 6789">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="opening_time">Opening Time</label>
                            <input type="time" id="opening_time" name="opening_time" value="<?php echo htmlspecialchars($restaurant['opening_time'] ?? ''); ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="closing_time">Closing Time</label>
                            <input type="time" id="closing_time" name="closing_time" value="<?php echo htmlspecialchars($restaurant['closing_time'] ?? ''); ?>" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="time_slots">Additional Time Slots (for buffets, etc.)</label>
                        <small>Format: 10:30-14:00, 17:00-20:00 (use 24-hour format, separate with commas)</small>
                        <textarea id="time_slots" name="time_slots" class="form-control form-textarea" rows="2" placeholder="e.g., 10:30-14:00, 17:00-20:00"><?php echo htmlspecialchars($restaurant['time_slots'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control form-textarea" rows="5" placeholder="Detailed description, menu highlights"><?php echo htmlspecialchars($restaurant['description'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="form-section">
                    <h2>Restaurant Image</h2>
                    <?php $image = $restaurant['image'] ?? ''; ?>
                    <?php include 'media-picker.php'; ?>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><?php echo $isEdit ? '✏️ Update Restaurant' : '➕ Add Restaurant'; ?></button>
                    <a href="dashboard.php?tab=restaurants" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </main>
    <footer class="admin-footer"><p></p></footer>
    <!-- Leaflet CSS & JS (FREE OpenStreetMap) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script src="../js/admin.js"></script>
    
    <script>
    // Leaflet Map with two-way location sync
    let map, marker;
    const defaultLat = <?php echo is_numeric($restaurant['latitude'] ?? null) ? (float)$restaurant['latitude'] : 7.443; ?>;
    const defaultLng = <?php echo is_numeric($restaurant['longitude'] ?? null) ? (float)$restaurant['longitude'] : 125.807; ?>;
    const locationInput = document.getElementById('location');
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    
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
            marker.on('dragend', () => updateCoords(true));
        } else {
            marker.setLatLng([lat, lng]);
        }
    }

    function setCoords(lat, lng) {
        latInput.value = lat.toFixed(6);
        lngInput.value = lng.toFixed(6);
    }

    async function reverseGeocode(lat, lng) {
        try {
            const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`);
            const data = await res.json();
            locationInput.value = data.display_name || `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
        } catch (e) {
            locationInput.value = `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
        }
    }

    async function geocodeLocation(query) {
        if (!query || query.trim().length < 3) return;
        try {
            const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1&countrycodes=ph`);
            const data = await res.json();
            if (!data || !data.length) return;
            const lat = parseFloat(data[0].lat);
            const lng = parseFloat(data[0].lon);
            map.setView([lat, lng], 16);
            ensureMarker(lat, lng);
            setCoords(lat, lng);
            locationInput.value = data[0].display_name || query;
        } catch (e) {
            console.log('Location lookup failed');
        }
    }

    async function updateCoords(doReverse = false) {
        if (!marker) return;
        const lat = marker.getLatLng().lat;
        const lng = marker.getLatLng().lng;
        setCoords(lat, lng);
        if (doReverse) {
            await reverseGeocode(lat, lng);
        }
    }
    
    function initMap() {
        map = L.map('map-container').setView([defaultLat, defaultLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        ensureMarker(defaultLat, defaultLng);
        setCoords(defaultLat, defaultLng);

        map.on('click', function(e) {
            ensureMarker(e.latlng.lat, e.latlng.lng);
            marker.bindPopup('Selected location').openPopup();
            updateCoords(true);
        });

        locationInput.addEventListener('input', debounce((e) => {
            geocodeLocation(e.target.value);
        }, 700));

        latInput.addEventListener('input', debounce(() => {
            const lat = parseFloat(latInput.value);
            const lng = parseFloat(lngInput.value);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
            map.setView([lat, lng], 16);
            ensureMarker(lat, lng);
            setCoords(lat, lng);
        }, 400));

        lngInput.addEventListener('input', debounce(() => {
            const lat = parseFloat(latInput.value);
            const lng = parseFloat(lngInput.value);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
            map.setView([lat, lng], 16);
            ensureMarker(lat, lng);
            setCoords(lat, lng);
        }, 400));
    }
    
    document.getElementById('use-my-location').onclick = function() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    map.setView([lat, lng], 13);
                    ensureMarker(lat, lng);
                    updateCoords(true);
                    marker.bindPopup('Your location').openPopup();
                },
                function() {
                    alert('Location access denied. Using Tagum default.');
                }
            );
        } else {
            alert('Geolocation not supported.');
        }
    };
    
    document.addEventListener('DOMContentLoaded', initMap);
    </script>

    <!-- Removed debug click listener. Actual fix is handled in media-picker inputs. -->
</body>
</html>
