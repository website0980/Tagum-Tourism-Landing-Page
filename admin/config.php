<?php
// Admin Module Configuration & Session Management (HARDENED)

// Secure session initialization
function initSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', 'Strict');
        session_start();
    }
}
initSecureSession();

// Cache configuration
define('CACHE_DIR', dirname(__DIR__) . '/cache/');
define('CACHE_DURATION', 300); // 5 minutes in seconds

// Initialize cache directory
if (!is_dir(CACHE_DIR)) {
    mkdir(CACHE_DIR, 0755, true);
}

// Cache functions
function getCache($key) {
    $cacheFile = CACHE_DIR . md5($key) . '.cache';
    if (!file_exists($cacheFile)) {
        return null;
    }
    
    $cacheData = json_decode(file_get_contents($cacheFile), true);
    if (!$cacheData || !isset($cacheData['data']) || !isset($cacheData['timestamp'])) {
        return null;
    }
    
    // Check if cache is expired
    if (time() - $cacheData['timestamp'] > CACHE_DURATION) {
        @unlink($cacheFile);
        return null;
    }
    
    return $cacheData['data'];
}

function setCache($key, $data) {
    $cacheFile = CACHE_DIR . md5($key) . '.cache';
    $cacheData = [
        'data' => $data,
        'timestamp' => time()
    ];
    
    return file_put_contents($cacheFile, json_encode($cacheData)) !== false;
}

function clearCache($pattern = null) {
    if ($pattern === null) {
        // Clear all cache files
        $files = glob(CACHE_DIR . '*.cache');
        foreach ($files as $file) {
            @unlink($file);
        }
        return true;
    }
    
    // Clear cache files matching pattern
    $files = glob(CACHE_DIR . md5($pattern) . '*.cache');
    foreach ($files as $file) {
        @unlink($file);
    }
    return true;
}

function generateCacheKey($query, $params = []) {
    return $query . json_encode($params);
}

// Hashed admin credentials (prevents plaintext leakage)
define('ADMIN_USERNAME', 'PromotionalAdmin');
define('ADMIN_PASSWORD_HASH', '$2y$12$7AwD6yF6sJjeSAoexv3xveoehQUipErVu0/oxycCfHox1616eJXzO'); // default: Tagum2026

// Session timeout: 30 minutes of inactivity
define('SESSION_TIMEOUT', 1800);

define('DESTINATIONS_FILE', dirname(__DIR__) . '/assets/data/destinations.json');
define('EXPERIENCES_FILE', dirname(__DIR__) . '/assets/data/experiences.json');
define('CUISINE_FILE', dirname(__DIR__) . '/assets/data/cuisine.json');
define('CULTURAL_SITES_FILE', dirname(__DIR__) . '/assets/data/cultural-sites.json');
define('FESTIVALS_FILE', dirname(__DIR__) . '/assets/data/festivals.json');

define('IMAGES_DIR', dirname(__DIR__) . '/images/destinations/');
define('IMAGES_URL', '../../images/destinations/');
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('MAX_FILE_SIZE', 100 * 1024 * 1024);

define('EXPERIENCES_IMAGES_DIR', dirname(__DIR__) . '/assets/images/experiences/');
define('EXPERIENCES_IMAGES_URL', '../../assets/images/experiences/');
define('CUISINE_IMAGES_DIR', dirname(__DIR__) . '/assets/images/cuisine/');
define('CUISINE_IMAGES_URL', '../../assets/images/cuisine/');
define('CULTURAL_SITES_IMAGES_DIR', dirname(__DIR__) . '/images/events/');
define('CULTURAL_SITES_IMAGES_URL', '../../images/events/');
define('FESTIVALS_IMAGES_DIR', dirname(__DIR__) . '/images/festivals/');
define('FESTIVALS_IMAGES_URL', '../../images/festivals/');

define('HOTEL_IMAGES_DIR', dirname(__DIR__) . '/assets/images/hotels/');
define('HOTEL_IMAGES_URL', '../../assets/images/hotels/');

// NEW: Restaurant images (reuse hotel logic)
define('RESTAURANT_IMAGES_DIR', dirname(__DIR__) . '/assets/images/restaurants/');
define('RESTAURANT_IMAGES_URL', '../../assets/images/restaurants/');

define('CAROUSEL_IMAGES_DIR', dirname(__DIR__) . '/images/carousel/');
define('CAROUSEL_IMAGES_URL', 'images/carousel/');

function isLoggedIn() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        return false;
    }
    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        logout(false);
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

function requireAuth() {
    if (!isLoggedIn()) {
        // Destroy any partial session data before redirect
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        header('Location: login.php');
        exit();
    }
}

// CSRF Token helpers
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken()) . '">';
}

function loadDestinations() {
    $cacheKey = generateCacheKey('loadDestinations');
    $cached = getCache($cacheKey);
    if ($cached !== null) {
        return $cached;
    }
    
    $dbFile = '../database.db';
    if (!file_exists($dbFile)) return [];
    try {
        $db = new SQLite3($dbFile);
        $query = "SELECT * FROM destinations";
        $result = $db->query($query);
        $destinations = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $destinations[] = $row;
        $db->close();
        setCache($cacheKey, $destinations);
        return $destinations;
    } catch (Exception $e) { return []; }
}

function loadExperiences() {
    $cacheKey = generateCacheKey('loadExperiences');
    $cached = getCache($cacheKey);
    if ($cached !== null) {
        return $cached;
    }
    
    $dbFile = '../database.db';
    if (!file_exists($dbFile)) return [];
    try {
        $db = new SQLite3($dbFile);
        $query = "SELECT * FROM experiences ORDER BY id DESC";
        $result = $db->query($query);
        $experiences = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $experiences[] = $row;
        $db->close();
        setCache($cacheKey, $experiences);
        return $experiences;
    } catch (Exception $e) { return []; }
}

function loadCuisine() {
    $file = dirname(__DIR__) . '/assets/data/cuisine.json';
    if (!file_exists($file)) return [];
    $json = file_get_contents($file);
    $data = json_decode($json, true) ?: [];
    return is_array($data) ? $data : [];
}

function loadCulturalSites() {
    $cacheKey = generateCacheKey('loadCulturalSites');
    $cached = getCache($cacheKey);
    if ($cached !== null) {
        return $cached;
    }
    
    $dbFile = '../database.db';
    if (!file_exists($dbFile)) return [];
    try {
        require_once dirname(__DIR__) . '/includes/events_helpers.php';
        $db = new SQLite3($dbFile);
        ensureEventDateColumn($db);
        $query = "SELECT * FROM events ORDER BY CASE WHEN event_date IS NULL OR event_date = '' THEN 1 ELSE 0 END, event_date ASC, name ASC";
        $result = $db->query($query);
        $culturalSites = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $culturalSites[] = $row;
        $db->close();
        setCache($cacheKey, $culturalSites);
        return $culturalSites;
    } catch (Exception $e) { return []; }
}

function ensureFestivalRelatedEventColumn($db = null) {
    $closeDb = false;
    if ($db === null) {
        $dbFile = dirname(__DIR__) . '/database.db';
        if (!file_exists($dbFile)) {
            return false;
        }
        $db = new SQLite3($dbFile);
        $closeDb = true;
    }

    $columns = [];
    $result = $db->query('PRAGMA table_info(festivals)');
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $columns[] = $row['name'];
    }

    if (!in_array('related_event_id', $columns, true)) {
        $db->exec('ALTER TABLE festivals ADD COLUMN related_event_id INTEGER');
    }

    if (!in_array('related_event_name', $columns, true)) {
        $db->exec('ALTER TABLE festivals ADD COLUMN related_event_name TEXT');
    }

    if ($closeDb) {
        $db->close();
    }
    return true;
}

function loadFestivals() {
    $cacheKey = generateCacheKey('loadFestivals');
    $cached = getCache($cacheKey);
    if ($cached !== null) {
        return $cached;
    }
    
    $dbFile = dirname(__DIR__) . '/database.db';
    if (!file_exists($dbFile)) return [];
    try {
        $db = new SQLite3($dbFile);
        ensureFestivalRelatedEventColumn($db);
        $query = "SELECT * FROM festivals ORDER BY id";
        $result = $db->query($query);
        $festivals = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $festivals[] = $row;
        $db->close();
        setCache($cacheKey, $festivals);
        return $festivals;
    } catch (Exception $e) { return []; }
}

function saveFestivals(array $festivals) {
    $dbFile = dirname(__DIR__) . '/database.db';
    if (!file_exists($dbFile)) return false;

    try {
        $db = new SQLite3($dbFile);
        ensureFestivalRelatedEventColumn($db);
        $db->exec('DELETE FROM festivals');

        $order = 1;
        foreach ($festivals as $festival) {
            $id = isset($festival['id']) && is_numeric($festival['id']) ? (int) $festival['id'] : $order;
            $name = $db->escapeString((string) ($festival['name'] ?? ''));
            $description = $db->escapeString((string) ($festival['description'] ?? ''));
            $image = $db->escapeString((string) ($festival['image'] ?? ''));
            $date = $db->escapeString((string) ($festival['date'] ?? ''));
            $highlights = $db->escapeString((string) ($festival['highlights'] ?? ''));
            $activities = $db->escapeString((string) ($festival['activities'] ?? ''));
            $relatedEventId = '';
            if (!empty($festival['related_event_id'])) {
                $relatedEventId = (int) $festival['related_event_id'];
            }
            $relatedEventName = $db->escapeString((string) ($festival['related_event_name'] ?? ''));

            $stmt = $db->prepare('INSERT INTO festivals (id, name, description, image, date, highlights, activities, related_event_id, related_event_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bindValue(1, $id, SQLITE3_INTEGER);
            $stmt->bindValue(2, $name, SQLITE3_TEXT);
            $stmt->bindValue(3, $description, SQLITE3_TEXT);
            $stmt->bindValue(4, $image, SQLITE3_TEXT);
            $stmt->bindValue(5, $date, SQLITE3_TEXT);
            $stmt->bindValue(6, $highlights, SQLITE3_TEXT);
            $stmt->bindValue(7, $activities, SQLITE3_TEXT);
            if ($relatedEventId !== '') {
                $stmt->bindValue(8, $relatedEventId, SQLITE3_INTEGER);
            } else {
                $stmt->bindValue(8, null, SQLITE3_NULL);
            }
            $stmt->bindValue(9, $relatedEventName, SQLITE3_TEXT);
            $stmt->execute();
            $order++;
        }

        $db->close();
        clearCache('loadFestivals');
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// HOTEL FUNCTIONS
function loadHotels() {
    $cacheKey = generateCacheKey('loadHotels');
    $cached = getCache($cacheKey);
    if ($cached !== null) {
        return $cached;
    }
    
    $dbFile = '../database.db';
    if (!file_exists($dbFile)) return [];
    try {
        $db = new SQLite3($dbFile);
        $query = "SELECT * FROM hotel_items ORDER BY id";
        $result = $db->query($query);
        $hotels = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $hotels[] = $row;
        $db->close();
        setCache($cacheKey, $hotels);
        return $hotels;
    } catch (Exception $e) { return []; }
}

// RESTAURANT FUNCTIONS
function formatTime24to12($time24) {
    if (empty($time24)) return '';
    
    // Handle time ranges (e.g., "10:30-14:00")
    if (strpos($time24, '-') !== false) {
        $parts = explode('-', $time24);
        $formatted = [];
        foreach ($parts as $part) {
            $formatted[] = formatSingleTime24to12(trim($part));
        }
        return implode(' to ', $formatted);
    }
    
    return formatSingleTime24to12($time24);
}

function formatSingleTime24to12($time24) {
    if (empty($time24)) return '';
    
    $time = strtotime($time24);
    if ($time === false) return $time24;
    
    $formatted = date('g:ia', $time); // e.g., "8:00am", "10:30pm"
    
    // Remove :00 if it's on the hour
    $formatted = str_replace(':00', '', $formatted);
    
    return $formatted; // e.g., "8am", "10:30pm"
}

function ensureRestaurantTimeColumns($db = null) {
    $closeDb = false;
    if ($db === null) {
        $dbFile = dirname(__DIR__) . '/database.db';
        if (!file_exists($dbFile)) {
            return false;
        }
        $db = new SQLite3($dbFile);
        $closeDb = true;
    }

    $columns = [];
    $result = $db->query('PRAGMA table_info(restaurant_items)');
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $columns[] = $row['name'];
    }

    if (!in_array('opening_time', $columns, true)) {
        $db->exec('ALTER TABLE restaurant_items ADD COLUMN opening_time TEXT');
    }

    if (!in_array('closing_time', $columns, true)) {
        $db->exec('ALTER TABLE restaurant_items ADD COLUMN closing_time TEXT');
    }

    if (!in_array('time_slots', $columns, true)) {
        $db->exec('ALTER TABLE restaurant_items ADD COLUMN time_slots TEXT');
    }

    if ($closeDb) {
        $db->close();
    }
    return true;
}

function loadRestaurants() {
    $cacheKey = generateCacheKey('loadRestaurants');
    $cached = getCache($cacheKey);
    if ($cached !== null) {
        return $cached;
    }
    
    $dbFile = '../database.db';
    if (!file_exists($dbFile)) return [];
    try {
        $db = new SQLite3($dbFile);
        ensureRestaurantTimeColumns($db);
        $query = "SELECT * FROM restaurant_items ORDER BY id";
        $result = $db->query($query);
        $restaurants = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $restaurants[] = $row;
        $db->close();
        setCache($cacheKey, $restaurants);
        return $restaurants;
    } catch (Exception $e) { return []; }
}

// CERTIFICATION APPLICATION FUNCTIONS
function loadAccommodationApplications() {
    $dbFile = '../database.db';
    if (!file_exists($dbFile)) return [];
    try {
        $db = new SQLite3($dbFile);
        $query = "SELECT * FROM accommodation_applications ORDER BY created_at DESC";
        $result = $db->query($query);
        $applications = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) $applications[] = $row;
        $db->close();
        return $applications;
    } catch (Exception $e) { return []; }
}

function loadAccommodationApplicationById($id) {
    $dbFile = '../database.db';
    if (!file_exists($dbFile)) return null;
    try {
        $db = new SQLite3($dbFile);
        $stmt = $db->prepare('SELECT * FROM accommodation_applications WHERE id = ? LIMIT 1');
        $stmt->bindValue(1, $id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        $db->close();
        return $row ?: null;
    } catch (Exception $e) {
        return null;
    }
}

function updateApplicationStatus($id, $status) {
    $dbFile = '../database.db';
    if (!file_exists($dbFile)) return false;
    try {
        $db = new SQLite3($dbFile);
        $stmt = $db->prepare('UPDATE accommodation_applications SET status = ? WHERE id = ?');
        $stmt->bindValue(1, $status, SQLITE3_TEXT);
        $stmt->bindValue(2, $id, SQLITE3_INTEGER);
        $stmt->execute();
        $db->close();
        return true;
    } catch (Exception $e) { return false; }
}

function deleteAccommodationApplication($id) {
    $dbFile = '../database.db';
    if (!file_exists($dbFile)) return false;
    try {
        $db = new SQLite3($dbFile);
        $stmt = $db->prepare('DELETE FROM accommodation_applications WHERE id = ?');
        $stmt->bindValue(1, $id, SQLITE3_INTEGER);
        $stmt->execute();
        $db->close();
        return true;
    } catch (Exception $e) { return false; }
}

function validateImageUpload($file) {
    $errors = [];
    if ($file['error'] !== UPLOAD_ERR_OK) $errors[] = 'Upload error';
    if ($file['size'] > MAX_FILE_SIZE) $errors[] = 'File too large';
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) $errors[] = 'Invalid file type';
    return ['success' => empty($errors), 'error' => implode(', ', $errors)];
}

function processAndResizeImage($sourcePath, $targetPath, $targetWidth = 800, $targetHeight = 600) {
    // Check if GD library is available
    if (!extension_loaded('gd') || !function_exists('gd_info')) {
        // Fall back to simple file copy if GD is not available
        if (copy($sourcePath, $targetPath)) {
            return ['success' => true, 'warning' => 'GD library not available, image copied without processing'];
        }
        return ['success' => false, 'error' => 'GD library not available and file copy failed'];
    }

    try {
        $imageInfo = getimagesize($sourcePath);
        if (!$imageInfo) {
            return ['success' => false, 'error' => 'Invalid image file'];
        }

        $sourceWidth = $imageInfo[0];
        $sourceHeight = $imageInfo[1];
        $mimeType = $imageInfo['mime'];

        // Create image from source
        switch ($mimeType) {
            case 'image/jpeg':
                if (!function_exists('imagecreatefromjpeg')) {
                    return ['success' => false, 'error' => 'JPEG support not available in GD library'];
                }
                $source = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                if (!function_exists('imagecreatefrompng')) {
                    return ['success' => false, 'error' => 'PNG support not available in GD library'];
                }
                $source = imagecreatefrompng($sourcePath);
                break;
            case 'image/gif':
                if (!function_exists('imagecreatefromgif')) {
                    return ['success' => false, 'error' => 'GIF support not available in GD library'];
                }
                $source = imagecreatefromgif($sourcePath);
                break;
            case 'image/webp':
                if (!function_exists('imagecreatefromwebp')) {
                    return ['success' => false, 'error' => 'WebP support not available in GD library'];
                }
                $source = imagecreatefromwebp($sourcePath);
                break;
            default:
                return ['success' => false, 'error' => 'Unsupported image format'];
        }

        if (!$source) {
            return ['success' => false, 'error' => 'Failed to create image from source'];
        }

        // Calculate aspect ratio and scaling
        $sourceRatio = $sourceWidth / $sourceHeight;
        $targetRatio = $targetWidth / $targetHeight;

        // Determine scaling to fill target dimensions
        if ($sourceRatio > $targetRatio) {
            // Source is wider, scale by height
            $scale = $targetHeight / $sourceHeight;
            $newWidth = $sourceWidth * $scale;
            $newHeight = $targetHeight;
            $x = ($targetWidth - $newWidth) / 2;
            $y = 0;
        } else {
            // Source is taller, scale by width
            $scale = $targetWidth / $sourceWidth;
            $newWidth = $targetWidth;
            $newHeight = $sourceHeight * $scale;
            $x = 0;
            $y = ($targetHeight - $newHeight) / 2;
        }

        // Create new image with target dimensions
        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        // Fill background with white color for JPEG, transparent for PNG/GIF
        if ($mimeType === 'image/jpeg' || $mimeType === 'image/webp') {
            $white = imagecolorallocate($target, 255, 255, 255);
            imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $white);
        } else {
            imagealphablending($target, false);
            imagesavealpha($target, true);
            $transparent = imagecolorallocatealpha($target, 255, 255, 255, 127);
            imagefilledrectangle($target, 0, 0, $targetWidth, $targetHeight, $transparent);
        }

        // Scale and position image to fill target dimensions
        imagecopyresampled($target, $source, $x, $y, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);

        // Save the processed image
        $ext = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
        switch ($ext) {
            case 'jpg':
            case 'jpeg':
                if (!function_exists('imagejpeg')) {
                    return ['success' => false, 'error' => 'JPEG save support not available in GD library'];
                }
                imagejpeg($target, $targetPath, 85);
                break;
            case 'png':
                if (!function_exists('imagepng')) {
                    return ['success' => false, 'error' => 'PNG save support not available in GD library'];
                }
                imagepng($target, $targetPath, 9);
                break;
            case 'gif':
                if (!function_exists('imagegif')) {
                    return ['success' => false, 'error' => 'GIF save support not available in GD library'];
                }
                imagegif($target, $targetPath);
                break;
            case 'webp':
                if (!function_exists('imagewebp')) {
                    return ['success' => false, 'error' => 'WebP save support not available in GD library'];
                }
                imagewebp($target, $targetPath, 85);
                break;
            default:
                imagejpeg($target, $targetPath, 85);
        }

        imagedestroy($source);
        imagedestroy($target);

        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Image processing failed: ' . $e->getMessage()];
    }
}

function saveUploadedImage($file) {
    $validation = validateImageUpload($file);
    if (!$validation['success']) return $validation;
    if (!is_dir(IMAGES_DIR)) mkdir(IMAGES_DIR, 0755, true);
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = 'dest_' . time() . '_' . uniqid() . '.' . $fileExt;
    $filePath = IMAGES_DIR . $fileName;
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => true, 'fileName' => $fileName, 'path' => IMAGES_URL . $fileName];
    }
    return ['success' => false, 'error' => 'Failed to save image'];
}

function deleteImage($pathOrFileName) {
    $fileName = basename($pathOrFileName);
    if (empty($fileName)) return false;
    $filePath = IMAGES_DIR . $fileName;
    if (file_exists($filePath)) unlink($filePath);
    return !file_exists($filePath);
}

function saveHotelImage($file) {
    $validation = validateImageUpload($file);
    if (!$validation['success']) return $validation;
    if (!is_dir(HOTEL_IMAGES_DIR)) mkdir(HOTEL_IMAGES_DIR, 0755, true);
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = 'hotel_' . time() . '_' . uniqid() . '.' . $fileExt;
    $filePath = HOTEL_IMAGES_DIR . $fileName;
    
    // Process and resize image to uniform dimensions (800x600 for hotels)
    $processResult = processAndResizeImage($file['tmp_name'], $filePath, 800, 600);
    if (!$processResult['success']) {
        return ['success' => false, 'error' => $processResult['error']];
    }
    
    return ['success' => true, 'fileName' => $fileName, 'path' => HOTEL_IMAGES_URL . $fileName];
}

function deleteHotelImage($pathOrFileName) {
    $fileName = basename($pathOrFileName);
    if (empty($fileName)) return false;
    $filePath = HOTEL_IMAGES_DIR . $fileName;
    if (file_exists($filePath)) unlink($filePath);
    return !file_exists($filePath);
}

function saveRestaurantImage($file) {
    $validation = validateImageUpload($file);
    if (!$validation['success']) return $validation;
    if (!is_dir(RESTAURANT_IMAGES_DIR)) mkdir(RESTAURANT_IMAGES_DIR, 0755, true);
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = 'restaurant_' . time() . '_' . uniqid() . '.' . $fileExt;
    $filePath = RESTAURANT_IMAGES_DIR . $fileName;
    
    // Process and resize image to uniform dimensions (800x600 for restaurants)
    $processResult = processAndResizeImage($file['tmp_name'], $filePath, 800, 600);
    if (!$processResult['success']) {
        return ['success' => false, 'error' => $processResult['error']];
    }
    
    return ['success' => true, 'fileName' => $fileName, 'path' => RESTAURANT_IMAGES_URL . $fileName];
}

function deleteRestaurantImage($pathOrFileName) {
    $fileName = basename($pathOrFileName);
    if (empty($fileName)) return false;
    $filePath = RESTAURANT_IMAGES_DIR . $fileName;
    if (file_exists($filePath)) unlink($filePath);
    return !file_exists($filePath);
}

function saveCulturalSiteImage($file) {
    $validation = validateImageUpload($file);
    if (!$validation['success']) return $validation;
    if (!is_dir(CULTURAL_SITES_IMAGES_DIR)) mkdir(CULTURAL_SITES_IMAGES_DIR, 0755, true);
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = 'event_' . time() . '_' . uniqid() . '.' . $fileExt;
    $filePath = CULTURAL_SITES_IMAGES_DIR . $fileName;
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => true, 'fileName' => $fileName, 'path' => CULTURAL_SITES_IMAGES_URL . $fileName];
    }
    return ['success' => false, 'error' => 'Failed to save image'];
}

function deleteCulturalSiteImage($pathOrFileName) {
    $fileName = basename($pathOrFileName);
    if (empty($fileName)) return false;
    $filePath = CULTURAL_SITES_IMAGES_DIR . $fileName;
    if (file_exists($filePath)) unlink($filePath);
    return !file_exists($filePath);
}

function saveFestivalImage($file) {
    $validation = validateImageUpload($file);
    if (!$validation['success']) return $validation;
    if (!is_dir(FESTIVALS_IMAGES_DIR)) mkdir(FESTIVALS_IMAGES_DIR, 0755, true);
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = 'festival_' . time() . '_' . uniqid() . '.' . $fileExt;
    $filePath = FESTIVALS_IMAGES_DIR . $fileName;
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => true, 'fileName' => $fileName, 'path' => FESTIVALS_IMAGES_URL . $fileName];
    }
    return ['success' => false, 'error' => 'Failed to save image'];
}

function deleteFestivalImage($pathOrFileName) {
    $fileName = basename($pathOrFileName);
    if (empty($fileName)) return false;
    $filePath = FESTIVALS_IMAGES_DIR . $fileName;
    if (file_exists($filePath)) unlink($filePath);
    return !file_exists($filePath);
}

// DB CRUD functions for hotels and restaurants
function deleteHotel($id) {
    $dbFile = '../database.db';
    if (!file_exists($dbFile)) return false;
    try {
        $db = new SQLite3($dbFile);
        // Get image first
        $stmt = $db->prepare('SELECT image FROM hotel_items WHERE id = ?');
        $stmt->bindValue(1, $id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        if ($row && !empty($row['image'])) {
            deleteHotelImage($row['image']);
        }
        $stmt = $db->prepare('DELETE FROM hotel_items WHERE id = ?');
        $stmt->bindValue(1, $id, SQLITE3_INTEGER);
        $stmt->execute();
        $db->close();
        clearCache('loadHotels');
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function toggleHotelFeatured($id) {
    $dbFile = '../database.db';
    if (!file_exists($dbFile)) return false;
    try {
        $db = new SQLite3($dbFile);
        $stmt = $db->prepare('UPDATE hotel_items SET featured = NOT COALESCE(featured, 0) WHERE id = ?');
        $stmt->bindValue(1, $id, SQLITE3_INTEGER);
        $stmt->execute();
        $db->close();
        clearCache('loadHotels');
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function deleteRestaurant($id) {
    $dbFile = '../database.db';
    if (!file_exists($dbFile)) return false;
    try {
        $db = new SQLite3($dbFile);
        ensureRestaurantTimeColumns($db);
        $stmt = $db->prepare('SELECT image FROM restaurant_items WHERE id = ?');
        $stmt->bindValue(1, $id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        if ($row && !empty($row['image'])) {
            deleteRestaurantImage($row['image']);
        }
        $stmt = $db->prepare('DELETE FROM restaurant_items WHERE id = ?');
        $stmt->bindValue(1, $id, SQLITE3_INTEGER);
        $stmt->execute();
        $db->close();
        clearCache('loadRestaurants');
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function toggleRestaurantFeatured($id) {
    $dbFile = '../database.db';
    if (!file_exists($dbFile)) return false;
    try {
        $db = new SQLite3($dbFile);
        $stmt = $db->prepare('UPDATE restaurant_items SET featured = NOT COALESCE(featured, 0) WHERE id = ?');
        $stmt->bindValue(1, $id, SQLITE3_INTEGER);
        $stmt->execute();
        $db->close();
        clearCache('loadRestaurants');
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function saveDestination($data, $id = null) {
    $dbFile = '../database.db';
    if (!file_exists($dbFile)) return false;
    try {
        $db = new SQLite3($dbFile);
        if ($id === null) {
            // INSERT new
            $stmt = $db->prepare('INSERT INTO destinations (name, description, location, accessibility, features, facilities, entrance_fee, best_time, what_to_pack, visiting_rules, image, featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bindValue(1, $data['name'] ?? '', SQLITE3_TEXT);
            $stmt->bindValue(2, $data['description'] ?? '', SQLITE3_TEXT);
            $stmt->bindValue(3, $data['location'] ?? '', SQLITE3_TEXT);
            $stmt->bindValue(4, $data['accessibility'] ?? '', SQLITE3_TEXT);
            $stmt->bindValue(5, $data['features'] ?? '', SQLITE3_TEXT);
            $stmt->bindValue(6, $data['facilities'] ?? '', SQLITE3_TEXT);
            $stmt->bindValue(7, $data['entrance_fee'] ?? '', SQLITE3_TEXT);
            $stmt->bindValue(8, $data['best_time'] ?? '', SQLITE3_TEXT);
            $stmt->bindValue(9, $data['what_to_pack'] ?? '', SQLITE3_TEXT);
            $stmt->bindValue(10, $data['visiting_rules'] ?? '', SQLITE3_TEXT);
            $stmt->bindValue(11, $data['image'] ?? '', SQLITE3_TEXT);
            $stmt->bindValue(12, isset($data['featured']) ? (int)$data['featured'] : 0, SQLITE3_INTEGER);
            $result = $stmt->execute();
            $newId = $db->lastInsertRowID();
            $db->close();
            clearCache('loadDestinations');
            return $newId;
        } else {
            // UPDATE existing
            $stmt = $db->prepare('UPDATE destinations SET name=?, description=?, location=?, accessibility=?, features=?, facilities=?, entrance_fee=?, best_time=?, what_to_pack=?, visiting_rules=?, image=?, featured=? WHERE id=?');
            $stmt->bindValue(1, $data['name'] ?? '', SQLITE3_TEXT);
            $stmt->bindValue(2, $data['description'] ?? '', SQLITE3_TEXT);
            $stmt->bindValue(3, $data['location'] ?? '', SQLITE3_TEXT);
            $stmt->bindValue(4, $data['accessibility'] ?? '', SQLITE3_TEXT);
            $stmt->bindValue(5, $data['features'] ?? '', SQLITE3_TEXT);
            $stmt->bindValue(6, $data['facilities'] ?? '', SQLITE3_TEXT);
            $stmt->bindValue(7, $data['entrance_fee'] ?? '', SQLITE3_TEXT);
            $stmt->bindValue(8, $data['best_time'] ?? '', SQLITE3_TEXT);
            $stmt->bindValue(9, $data['what_to_pack'] ?? '', SQLITE3_TEXT);
            $stmt->bindValue(10, $data['visiting_rules'] ?? '', SQLITE3_TEXT);
            $stmt->bindValue(11, $data['image'] ?? '', SQLITE3_TEXT);
            $stmt->bindValue(12, isset($data['featured']) ? (int)$data['featured'] : 0, SQLITE3_INTEGER);
            $stmt->bindValue(13, $id, SQLITE3_INTEGER);
            $result = $stmt->execute();
            $affected = $db->changes();
            $db->close();
            clearCache('loadDestinations');
            return $affected > 0;
        }
    } catch (Exception $e) {
        return false;
    }
}

function toggleDestinationFeatured($id) {
    $dbFile = '../database.db';
    if (!file_exists($dbFile)) return false;
    try {
        $db = new SQLite3($dbFile);
        $stmt = $db->prepare('UPDATE destinations SET featured = NOT COALESCE(featured, 0) WHERE id = ?');
        $stmt->bindValue(1, $id, SQLITE3_INTEGER);
        $stmt->execute();
        $db->close();
        clearCache('loadDestinations');
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function deleteDestination($id) {
    $dbFile = '../database.db';
    if (!file_exists($dbFile)) return false;
    try {
        $db = new SQLite3($dbFile);
        // Get image first
        $stmt = $db->prepare('SELECT image FROM destinations WHERE id = ?');
        $stmt->bindValue(1, $id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        if ($row && !empty($row['image'])) {
            deleteImage($row['image']);
        }
        // Delete record
        $stmt = $db->prepare('DELETE FROM destinations WHERE id = ?');
        $stmt->bindValue(1, $id, SQLITE3_INTEGER);
        $stmt->execute();
        $db->close();
        clearCache('loadDestinations');
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Deprecated full wipe function (logs warning)
function saveDestinations($destinations) {
    error_log('saveDestinations() deprecated - use saveDestination() instead');
    return false;
}

function saveExperiences($experiences) {
    $file = EXPERIENCES_FILE;
    $json = json_encode($experiences, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (file_put_contents($file, $json) !== false) {
        return true;
    }
    return false;
}

// CAROUSEL FUNCTIONS
function ensureCarouselTable() {
    $dbFile = '../database.db';
    if (!file_exists($dbFile)) return false;
    try {
        $db = new SQLite3($dbFile);
        $schema = file_get_contents(dirname(__DIR__) . '/database/carousel_schema.sql');
        if ($schema) {
            $db->exec($schema);
        }
        $count = (int) $db->querySingle('SELECT COUNT(*) FROM carousel_slides');
        if ($count === 0) {
            $defaults = [
                ['Tagumeños: Beauty that Shines from Within.', "Discover\nNatural Beauty", 'Tagumeños are a reflection of true natural beauty radiating warmth, kindness, and genuine smiles that make everyone feel welcome.', 'images/Background for slide 1.jpg', 1],
                ['Cultural heritage meets modern charm', "Experience\nLocal Culture", 'Immerse yourself in the vibrant traditions, local cuisine, and warm hospitality of Tagum City. Discover authentic experiences that celebrate our rich heritage.', 'images/Background for slide 2 .jpg', 2],
                ['Tagum Adventures: Feel the Thrill, Live the Moment', "Thrilling\nAdventures", 'Step into the excitement that awaits in Tagum where every journey is filled with adrenaline, discovery, and unforgettable moments. From outdoor explorations to vibrant city experiences, adventure is always just around the corner.', 'images/Background for slide 3.jpg', 3],
            ];
            $stmt = $db->prepare('INSERT INTO carousel_slides (tagline, title, description, image, sort_order, active) VALUES (?, ?, ?, ?, ?, 1)');
            foreach ($defaults as $row) {
                $stmt->bindValue(1, $row[0], SQLITE3_TEXT);
                $stmt->bindValue(2, $row[1], SQLITE3_TEXT);
                $stmt->bindValue(3, $row[2], SQLITE3_TEXT);
                $stmt->bindValue(4, $row[3], SQLITE3_TEXT);
                $stmt->bindValue(5, $row[4], SQLITE3_INTEGER);
                $stmt->execute();
            }
        }
        $db->close();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function loadCarouselSlides($activeOnly = false) {
    ensureCarouselTable();
    $dbFile = '../database.db';
    if (!file_exists($dbFile)) return [];
    try {
        $db = new SQLite3($dbFile);
        $query = $activeOnly
            ? 'SELECT * FROM carousel_slides WHERE active = 1 ORDER BY sort_order ASC, id ASC'
            : 'SELECT * FROM carousel_slides ORDER BY sort_order ASC, id ASC';
        $result = $db->query($query);
        $slides = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $slides[] = $row;
        }
        $db->close();
        return $slides;
    } catch (Exception $e) {
        return [];
    }
}

function getCarouselSlideById($id) {
    ensureCarouselTable();
    $dbFile = '../database.db';
    if (!file_exists($dbFile)) return null;
    try {
        $db = new SQLite3($dbFile);
        $stmt = $db->prepare('SELECT * FROM carousel_slides WHERE id = ?');
        $stmt->bindValue(1, (int)$id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        $db->close();
        return $row ?: null;
    } catch (Exception $e) {
        return null;
    }
}

function saveCarouselImage($file) {
    $validation = validateImageUpload($file);
    if (!$validation['success']) return $validation;
    if (!is_dir(CAROUSEL_IMAGES_DIR)) mkdir(CAROUSEL_IMAGES_DIR, 0755, true);
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = 'carousel_' . time() . '_' . uniqid() . '.' . $fileExt;
    $filePath = CAROUSEL_IMAGES_DIR . $fileName;
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => true, 'fileName' => $fileName, 'path' => CAROUSEL_IMAGES_URL . $fileName];
    }
    return ['success' => false, 'error' => 'Failed to save image'];
}

function deleteCarouselImage($pathOrFileName) {
    $fileName = basename($pathOrFileName);
    if (empty($fileName)) return false;
    $filePath = CAROUSEL_IMAGES_DIR . $fileName;
    if (file_exists($filePath)) unlink($filePath);
    return !file_exists($filePath);
}

function saveCarouselSlide($data, $id = null) {
    ensureCarouselTable();
    $dbFile = '../database.db';
    if (!file_exists($dbFile)) return false;
    try {
        $db = new SQLite3($dbFile);
        if ($id === null) {
            $stmt = $db->prepare('INSERT INTO carousel_slides (tagline, title, description, image, btn_primary_text, btn_primary_link, btn_secondary_text, btn_secondary_link, sort_order, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bindValue(1, $data['tagline'] ?? '', SQLITE3_TEXT);
            $stmt->bindValue(2, $data['title'] ?? '', SQLITE3_TEXT);
            $stmt->bindValue(3, $data['description'] ?? '', SQLITE3_TEXT);
            $stmt->bindValue(4, $data['image'] ?? '', SQLITE3_TEXT);
            $stmt->bindValue(5, $data['btn_primary_text'] ?? 'Explore Now', SQLITE3_TEXT);
            $stmt->bindValue(6, $data['btn_primary_link'] ?? '#plan', SQLITE3_TEXT);
            $stmt->bindValue(7, $data['btn_secondary_text'] ?? 'Learn More', SQLITE3_TEXT);
            $stmt->bindValue(8, $data['btn_secondary_link'] ?? '#explore', SQLITE3_TEXT);
            $stmt->bindValue(9, isset($data['sort_order']) ? (int)$data['sort_order'] : 0, SQLITE3_INTEGER);
            $stmt->bindValue(10, isset($data['active']) ? (int)$data['active'] : 1, SQLITE3_INTEGER);
            $stmt->execute();
            $newId = $db->lastInsertRowID();
            $db->close();
            return $newId;
        }
        $stmt = $db->prepare('UPDATE carousel_slides SET tagline=?, title=?, description=?, image=?, btn_primary_text=?, btn_primary_link=?, btn_secondary_text=?, btn_secondary_link=?, sort_order=?, active=? WHERE id=?');
        $stmt->bindValue(1, $data['tagline'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(2, $data['title'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(3, $data['description'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(4, $data['image'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(5, $data['btn_primary_text'] ?? 'Explore Now', SQLITE3_TEXT);
        $stmt->bindValue(6, $data['btn_primary_link'] ?? '#plan', SQLITE3_TEXT);
        $stmt->bindValue(7, $data['btn_secondary_text'] ?? 'Learn More', SQLITE3_TEXT);
        $stmt->bindValue(8, $data['btn_secondary_link'] ?? '#explore', SQLITE3_TEXT);
        $stmt->bindValue(9, isset($data['sort_order']) ? (int)$data['sort_order'] : 0, SQLITE3_INTEGER);
        $stmt->bindValue(10, isset($data['active']) ? (int)$data['active'] : 1, SQLITE3_INTEGER);
        $stmt->bindValue(11, (int)$id, SQLITE3_INTEGER);
        $stmt->execute();
        $affected = $db->changes();
        $db->close();
        return $affected > 0;
    } catch (Exception $e) {
        return false;
    }
}

function deleteCarouselSlide($id) {
    ensureCarouselTable();
    $dbFile = '../database.db';
    if (!file_exists($dbFile)) return false;
    try {
        $db = new SQLite3($dbFile);
        $stmt = $db->prepare('SELECT image FROM carousel_slides WHERE id = ?');
        $stmt->bindValue(1, (int)$id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        if ($row && !empty($row['image']) && strpos($row['image'], 'images/carousel/') !== false) {
            deleteCarouselImage($row['image']);
        }
        $stmt = $db->prepare('DELETE FROM carousel_slides WHERE id = ?');
        $stmt->bindValue(1, (int)$id, SQLITE3_INTEGER);
        $stmt->execute();
        $db->close();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function toggleCarouselSlideActive($id) {
    ensureCarouselTable();
    $dbFile = '../database.db';
    if (!file_exists($dbFile)) return false;
    try {
        $db = new SQLite3($dbFile);
        $stmt = $db->prepare('UPDATE carousel_slides SET active = NOT COALESCE(active, 0) WHERE id = ?');
        $stmt->bindValue(1, (int)$id, SQLITE3_INTEGER);
        $stmt->execute();
        $db->close();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function logout($redirect = true) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']);
    }
    session_destroy();
    if ($redirect) {
        header('Location: login.php');
        exit();
    }
}
?>


