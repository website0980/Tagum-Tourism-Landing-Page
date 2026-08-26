<?php
// Add/Edit Hotel Page - Matches festival pattern
require_once 'config.php';
requireAuth();

$hotels = loadHotels();

$isEdit = false;
$hotelIndex = $_GET['id'] ?? $_POST['id'] ?? null;
if ($hotelIndex !== null && $hotelIndex !== '') {
    $hotelIndex = (int) $hotelIndex;
}
$errors = [];
$message = '';
$originalImage = '';
    $hotel = [
    'name' => '',
    'description' => '',
    'price' => '',
    'category' => '',
    'location' => '',
    'contact' => '',
    'information' => '',
'latitude' => '',
    'longitude' => '',
    'email' => '',
    'image' => ''
];

// If editing, load the hotel
if ($hotelIndex !== null && $hotelIndex !== '') {
    foreach ($hotels as $h) {
        if ((int)$h['id'] === $hotelIndex) {
            $isEdit = true;
            $originalImage = $hotel['image'];
            $hotel = $h;
            break;
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hotel = [
        'name' => trim($_POST['name'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
'price' => trim($_POST['price'] ?? ''),  // Range as text
        'category' => trim($_POST['category'] ?? ''),
        'location' => trim($_POST['location'] ?? ''),
        'contact' => trim($_POST['contact'] ?? ''),
        'information' => trim($_POST['information'] ?? ''),
        'latitude' => is_numeric($_POST['latitude'] ?? null) ? (float)$_POST['latitude'] : null,
        'longitude' => is_numeric($_POST['longitude'] ?? null) ? (float)$_POST['longitude'] : null,

'image' => $_POST['image'] ?? '',
        'email' => trim($_POST['email'] ?? '')
    ];

    // Preserve original image if editing, no new upload, and hidden input empty
    if ($isEdit && empty($hotel['image']) && empty($_FILES['image_file']['name'] ?? '')) {
        $hotel['image'] = $originalImage;
    }

    // Validate image file if uploaded (do not save/delete until validation passes)
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $validation = validateImageUpload($_FILES['image_file']);
        if (!$validation['success']) {
            $errors[] = $validation['error'];
        }
    }

    // Validation
    if (empty($hotel['name'])) $errors[] = 'Hotel name is required';
    if (empty($hotel['category'])) $errors[] = 'Category is required';
    if (!empty($hotel['email']) && !filter_var($hotel['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format';

    // Save if no errors (file operations only after validation)
    if (empty($errors)) {
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $result = saveHotelImage($_FILES['image_file']);
            if ($result['success']) {
                if (!empty($hotel['image'])) {
                    deleteHotelImage($hotel['image']);
                }
                $hotel['image'] = $result['path'];
            } else {
                $errors[] = $result['error'] ?? 'Image upload failed';
            }
        }
        if (empty($errors)) {
            $dbFile = '../database.db';
            if (file_exists($dbFile)) {
                $db = new SQLite3($dbFile);
                if ($isEdit) {
$stmt = $db->prepare('UPDATE hotel_items SET name = ?, description = ?, price = ?, category = ?, location = ?, contact = ?, information = ?, latitude = ?, longitude = ?, image = ?, email = ? WHERE id = ?');
                    $stmt->bindValue(1, $hotel['name'], SQLITE3_TEXT);
                    $stmt->bindValue(2, $hotel['description'], SQLITE3_TEXT);
                    $stmt->bindValue(3, $hotel['price'], SQLITE3_TEXT);
                    $stmt->bindValue(4, $hotel['category'], SQLITE3_TEXT);
                    $stmt->bindValue(5, $hotel['location'], SQLITE3_TEXT);
                    $stmt->bindValue(6, $hotel['contact'], SQLITE3_TEXT);
                    $stmt->bindValue(7, $hotel['information'], SQLITE3_TEXT);
                    $stmt->bindValue(8, $hotel['latitude'], SQLITE3_FLOAT);
                    $stmt->bindValue(9, $hotel['longitude'], SQLITE3_FLOAT);
                    $stmt->bindValue(10, $hotel['image'], SQLITE3_TEXT);
                    $stmt->bindValue(11, $hotel['email'], SQLITE3_TEXT);
                    $stmt->bindValue(12, $hotelIndex, SQLITE3_INTEGER);
                    $stmt->execute();
                } else {
$stmt = $db->prepare('INSERT INTO hotel_items (name, description, price, category, location, contact, information, latitude, longitude, image, email) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'); 
                    $stmt->bindValue(1, $hotel['name'], SQLITE3_TEXT);
                    $stmt->bindValue(2, $hotel['description'], SQLITE3_TEXT);
                    $stmt->bindValue(3, $hotel['price'], SQLITE3_TEXT);
                    $stmt->bindValue(4, $hotel['category'], SQLITE3_TEXT);
                    $stmt->bindValue(5, $hotel['location'], SQLITE3_TEXT);
                    $stmt->bindValue(6, $hotel['contact'], SQLITE3_TEXT);
                    $stmt->bindValue(7, $hotel['information'], SQLITE3_TEXT);
                    $stmt->bindValue(8, $hotel['latitude'], SQLITE3_FLOAT);
                    $stmt->bindValue(9, $hotel['longitude'], SQLITE3_FLOAT);
                    $stmt->bindValue(10, $hotel['image'], SQLITE3_TEXT);
                    $stmt->bindValue(11, $hotel['email'], SQLITE3_TEXT);
                    $stmt->execute();
                }
                $db->close();
                
                // Send notification email to hotel contact
                if (!empty($hotel['email']) && filter_var($hotel['email'], FILTER_VALIDATE_EMAIL)) {
                    $subject = $isEdit ? 'Hotel Updated - Tagum Admin' : 'New Hotel Added - Tagum Admin';
                    $body = "Dear Hotel Owner,\n\n";
                    $body .= "Your hotel '{$hotel['name']}' has been " . ($isEdit ? 'updated' : 'added') . " successfully.\n\n";
                    $body .= "Details:\n";
                    $body .= "- Location: {$hotel['location']}\n";
                    $body .= "- Category: {$hotel['category']}\n";
                    $body .= "- Price: {$hotel['price']}\n";
                    $body .= "- Contact: {$hotel['contact']}\n\n";
                    $body .= "Admin Team\nTagum City Tourism";
                    $headers = "From: no-reply@tagumcity.com\r\n";
                    $headers .= "Reply-To: admin@tagumcity.com\r\n";
                    $headers .= "X-Mailer: PHP/" . phpversion();
                    mail($hotel['email'], $subject, $body, $headers);
                }
            }
            $message = $isEdit ? 'updated' : 'added';
            header('Location: dashboard.php?tab=hotels&message=' . $message);
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
    <title><?php echo $isEdit ? 'Edit' : 'Add'; ?> Hotel - Tagum Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
    <body class="edit-mode" <?php if ($isEdit) echo 'class="edit-mode"'; ?>>
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="admin-header-content">
            <div class="admin-title">
                <a href="dashboard.php?tab=hotels" class="back-link" aria-label="Back to Dashboard">
                    <span class="back-link-icon" aria-hidden="true">←</span>
                    <span class="back-link-text">Dashboard</span>
                </a>
                <h1><?php echo $isEdit ? 'Edit' : 'Add New'; ?> Hotel</h1>
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
            <form method="POST" enctype="multipart/form-data" class="admin-form destination-form">
                <?php if ($isEdit && $hotelIndex !== null): ?>
                <input type="hidden" name="id" value="<?php echo (int) $hotelIndex; ?>">
                <?php endif; ?>
                <div class="form-section">
                    <h2>Hotel Information</h2>
                    
                    <div class="form-group">
                        <label for="name">Hotel Name *</label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            value="<?php echo htmlspecialchars($hotel['name']); ?>" 
                            required
                            class="form-control"
                            placeholder="e.g., Big 8 Rufina's Leisure Center"
                        >
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="category">Hotel Category *</label>
                            <select id="category" name="category" required class="form-control">
                                <option value="">-- Select Category --</option>
<option value="DOT Accredited" <?php echo $hotel['category'] === 'DOT Accredited' ? 'selected' : ''; ?>>DOT Accredited</option>
                                <option value="Locally Certified" <?php echo $hotel['category'] === 'Locally Certified' ? 'selected' : ''; ?>>Locally Certified</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Location <small>(Optional - Auto-filled)</small></label>
                            <input type="text" id="location-input" class="form-control" placeholder="e.g., Tagum City Hall, Philippines" value="<?php echo htmlspecialchars($hotel['location'] ?? ''); ?>">
                            <input type="hidden" name="location" id="location" value="<?php echo htmlspecialchars($hotel['location'] ?? ''); ?>">
                            <div id="coords-display" class="mt-2 p-2 bg-light border rounded small" style="display:none;">
                                Lat: <span id="lat-display">-</span> | Lng: <span id="lng-display">-</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Price Range <small>(₱)</small></label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input 
                                    type="text" 
                                    id="price" 
                                    name="price" 
                                    value="<?php echo htmlspecialchars($hotel['price'] ?? ''); ?>" 
                                    class="form-control"
                                    placeholder="2,800.00 - 5,500.00"
                                >
                            </div>
                            <small class="form-text text-muted">Enter range (e.g., "2,800 - 5,500" or "3,000")</small>
                        </div>
                        <div class="form-group">

                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Location Picker</label>
                            <div style="display: flex; gap: 10px; align-items: center; margin-bottom: 10px;">
<div id="map" style="height: 300px; border: 2px solid var(--light-gray); border-radius: 8px; flex: 1;"></div>
                                <button type="button" id="clear-marker" class="btn btn-secondary btn-small">🗑️ Remove Pin</button>
                            </div>
                        </div>
                        <div class="form-group">
<label for="latitude">Latitude <small>(Optional - Auto-filled)</small></label>
                            <input 
                                type="number" 
                                id="latitude" 
                                name="latitude" 
                                step="any"
                                value="<?php echo htmlspecialchars($hotel['latitude'] ?? ''); ?>" 
                                class="form-control"
                            >
                        </div>
                        <div class="form-group">
<label for="longitude">Longitude <small>(Optional - Auto-filled)</small></label>
                            <input 
                                type="number" 
                                id="longitude" 
                                name="longitude" 
                                step="any"
                                value="<?php echo htmlspecialchars($hotel['longitude'] ?? ''); ?>" 
                                class="form-control"
                            >
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="contact">Contact Number</label>
                            <input 
                                type="text" 
                                id="contact" 
                                name="contact" 
                                value="<?php echo htmlspecialchars($hotel['contact'] ?? ''); ?>" 
                                class="form-control"
                                placeholder="e.g., +63 912 345 6789"
                            >
                        </div>
                        <div class="form-group">
                            <label for="email">Email (for notifications)</label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                value="<?php echo htmlspecialchars($hotel['email'] ?? ''); ?>" 
                                class="form-control"
                                placeholder="e.g., info@hotel.com"
                            >
                        </div>
                       

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea 
                            id="description" 
                            name="description" 
                            class="form-control form-textarea"
                            rows="5"
                            placeholder="Describe the hotel features, amenities, location benefits"
                        ><?php echo htmlspecialchars($hotel['description']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="information">Information</label>
                        <textarea 
                            id="information" 
                            name="information" 
                            class="form-control form-textarea"
                            rows="3"
                            placeholder="Additional info, highlights, key features"
                        ><?php echo htmlspecialchars($hotel['information'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h2>Hotel Image</h2>
                    <?php $image = $hotel['image'] ?? ''; ?>
                    <?php include 'media-picker.php'; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $isEdit ? '✏️ Update Hotel' : '➕ Add Hotel'; ?>
                    </button>
                    <a href="dashboard.php?tab=hotels" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </main>

    <footer class="admin-footer">
        <p></p>
    </footer>

    <!-- Leaflet CSS & JS (FREE OpenStreetMap) -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script src="../assets/js/admin.js"></script>
    
    <script>
    let map, marker;
    const defaultLat = <?php echo is_numeric($hotel['latitude'] ?? null) ? (float)$hotel['latitude'] : 7.443; ?>;
    const defaultLng = <?php echo is_numeric($hotel['longitude'] ?? null) ? (float)$hotel['longitude'] : 125.807; ?>;

    const locationInput = document.getElementById('location-input');
    const latDisplay = document.getElementById('lat-display');
    const lngDisplay = document.getElementById('lng-display');
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    const hiddenLocation = document.getElementById('location');
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
            hiddenLocation.value = addr;
            locationInput.value = addr;
        } catch (e) {
            const fallback = `${lat.toFixed(4)}, ${lng.toFixed(4)}`;
            hiddenLocation.value = fallback;
            locationInput.value = fallback;
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
            hiddenLocation.value = data[0].display_name || query;
            locationInput.value = hiddenLocation.value;
        } catch (e) {
            console.log('Location search failed');
        }
    }

    async function updateCoordsFromMarker(doReverseGeocode = false) {
        if (!marker) return;
        const pos = marker.getLatLng();
        setCoordsUI(pos.lat, pos.lng);
        if (doReverseGeocode) {
            await reverseGeocode(pos.lat, pos.lng);
        }
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
            hiddenLocation.value = '';
            coordsDisplay.style.display = 'none';
            map.setView([defaultLat, defaultLng], 12);
        };

        locationInput.addEventListener('input', debounce((e) => {
            forwardGeocode(e.target.value);
        }, 700));

        latInput.addEventListener('input', debounce(() => {
            const lat = parseFloat(latInput.value);
            const lng = parseFloat(lngInput.value);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
            map.setView([lat, lng], 16);
            ensureMarker(lat, lng);
            setCoordsUI(lat, lng);
        }, 400));

        lngInput.addEventListener('input', debounce(() => {
            const lat = parseFloat(latInput.value);
            const lng = parseFloat(lngInput.value);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
            map.setView([lat, lng], 16);
            ensureMarker(lat, lng);
            setCoordsUI(lat, lng);
        }, 400));
    }

    window.addEventListener('DOMContentLoaded', initMap);
    </script>
</body>
</html>
