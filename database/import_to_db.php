<?php
// No require config - hardcode paths
define('ROOT_DIR', __DIR__ . '/..');

// Simple SQLite PDO
$db = new PDO('sqlite:database.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Function to insert if not exists
function insertIfNew($db, $table, $data) {
    $columns = implode(',', array_keys($data));
    $placeholders = ':' . implode(',:', array_keys($data));
    $stmt = $db->prepare("INSERT OR IGNORE INTO $table ($columns) VALUES ($placeholders)");
    $stmt->execute($data);
}

// Festivals
$festivals = json_decode(file_get_contents('assets/data/festivals.json'), true) ?: [];
foreach ($festivals as $f) {
    $db->prepare('INSERT OR IGNORE INTO festivals (name, description, date, highlights, activities, image_path) VALUES (?, ?, ?, ?, ?, ?)')->execute([
        $f['name'],
        $f['description'],
        $f['date'] ?? '',
        $f['highlights'] ?? '',
        $f['activities'] ?? '',
        $f['image'] ?? ''
    ]);
}

// Destinations
$destinations = json_decode(file_get_contents('assets/data/destinations.json'), true) ?: [];
foreach ($destinations as $d) {
    $featured = isset($d['featured']) ? (int)$d['featured'] : 0;
    $db->prepare('INSERT OR IGNORE INTO destinations (name, description, type, featured, image_path) VALUES (?, ?, ?, ?, ?)')->execute([
        $d['name'],
        $d['description'] ?? '',
        $d['type'] ?? '',
        $featured,
        $d['image'] ?? ''
    ]);
}

// Experiences
$experiences = json_decode(file_get_contents('assets/data/experiences.json'), true) ?: [];
foreach ($experiences as $e) {
    $featured = isset($e['featured']) ? (int)$e['featured'] : 0;
    $db->prepare('INSERT OR IGNORE INTO experiences (name, description, type, date, featured, image_path) VALUES (?, ?, ?, ?, ?, ?)')->execute([
        $e['name'],
        $e['description'] ?? '',
        $e['type'] ?? '',
        $e['date'] ?? '',
        $featured,
        $e['image'] ?? ''
    ]);
}

// Natural Wonders
$naturals = json_decode(file_get_contents('assets/data/natural-wonders.json'), true) ?: [];
foreach ($naturals as $n) {
    $db->prepare('INSERT OR IGNORE INTO natural_wonders (name, description, location, best_time, image_path) VALUES (?, ?, ?, ?, ?)')->execute([
        $n['name'],
        $n['description'] ?? '',
        $n['location'] ?? '',
        $n['best_time'] ?? '',
        $n['image'] ?? ''
    ]);
}

// Cultural Sites
$cultural = json_decode(file_get_contents('assets/data/cultural-sites.json'), true) ?: [];
foreach ($cultural as $c) {
    $db->prepare('INSERT OR IGNORE INTO cultural_sites (name, description, location, features, image_path) VALUES (?, ?, ?, ?, ?)')->execute([
        $c['name'],
        $c['description'] ?? '',
        $c['location'] ?? '',
        $c['features'] ?? '',
        $c['image'] ?? ''
    ]);
}

// Cuisine (complex)
$cuisine = json_decode(file_get_contents('assets/data/cuisine.json'), true) ?: [];
foreach ($cuisine as $cat) {
    $stmt = $db->prepare('INSERT OR IGNORE INTO cuisines (category, description, image_path) VALUES (?, ?, ?) RETURNING id');
    $stmt->execute([$cat['category'], $cat['description'] ?? '', $cat['image'] ?? '']);
    $cuisineId = $db->lastInsertId();
    
    if (isset($cat['items']) && is_array($cat['items'])) {
        foreach ($cat['items'] as $item) {
            $db->prepare('INSERT OR IGNORE INTO cuisine_items (cuisine_id, name, description, image_path) VALUES (?, ?, ?, ?)')->execute([
                $cuisineId,
                $item['name'],
                $item['description'] ?? '',
                $item['image'] ?? ''
            ]);
        }
    }
}

echo "Import complete. Check DB with sqlite3 database.db 'SELECT * FROM festivals;'";
?>

