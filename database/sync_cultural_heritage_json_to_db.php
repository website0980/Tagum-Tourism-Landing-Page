<?php
// Sync Cultural Heritage JSON into SQLite (database.db).
// Run manually:
//   php database/sync_cultural_heritage_json_to_db.php
// Optional:
//   php database/sync_cultural_heritage_json_to_db.php --truncate=1

declare(strict_types=1);

function arg(array $argv, string $name, string $default = ''): string {
    foreach ($argv as $a) {
        if (str_starts_with($a, "--{$name}=")) {
            return substr($a, strlen("--{$name}="));
        }
    }
    return $default;
}

$truncate = arg($argv, 'truncate', '0') === '1';

$projectRoot = realpath(__DIR__ . '/..');
if ($projectRoot === false) {
    fwrite(STDERR, "Could not resolve project root.\n");
    exit(1);
}

$dbPath = $projectRoot . '/database.db';
if (!file_exists($dbPath)) {
    fwrite(STDERR, "SQLite database file not found: {$dbPath}\n");
    exit(1);
}

$jsonPath = $projectRoot . '/Cultural Heritage Module/cultural-heritage.json';
if (!file_exists($jsonPath)) {
    fwrite(STDERR, "Cultural heritage JSON file not found: {$jsonPath}\n");
    exit(1);
}

$raw = file_get_contents($jsonPath);
if ($raw === false) {
    fwrite(STDERR, "Could not read JSON file.\n");
    exit(1);
}

$items = json_decode($raw, true);
if (!is_array($items)) {
    fwrite(STDERR, "Invalid JSON format.\n");
    exit(1);
}

// Ensure tables exist
require_once __DIR__ . '/setup_cultural_heritage_tables.php';

$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// If truncating, clear both tables
if ($truncate) {
    $db->exec('DELETE FROM cultural_heritage_gallery');
    $db->exec('DELETE FROM cultural_heritage');
}

// We'll use prepared statements.
$insertHeritage = $db->prepare(
    'INSERT INTO cultural_heritage (id, title, category, description, image) VALUES (?, ?, ?, ?, ?)'
);

$insertGallery = $db->prepare(
    'INSERT INTO cultural_heritage_gallery (heritage_id, image, caption, sort_order) VALUES (?, ?, ?, ?)'
);

$db->beginTransaction();

try {
    $heritageInserted = 0;
    $galleryInserted = 0;

    foreach ($items as $item) {
        if (!is_array($item)) continue;

        $id = $item['id'] ?? null;
        if ($id === null || !is_numeric($id)) {
            // The table uses AUTOINCREMENT, but JSON has ids; skip invalid ones.
            continue;
        }

        $title = (string)($item['title'] ?? '');
        $category = (string)($item['category'] ?? '');
        $description = (string)($item['description'] ?? '');
        $image = $item['image'] ?? null;

        if ($title === '' || $category === '' || $description === '') {
            continue;
        }

        // Clear existing row(s) for this id when not truncating.
        if (!$truncate) {
            $db->prepare('DELETE FROM cultural_heritage_gallery WHERE heritage_id = ?')->execute([$id]);
            $db->prepare('DELETE FROM cultural_heritage WHERE id = ?')->execute([$id]);
        }

        $insertHeritage->execute([$id, $title, $category, $description, $image]);
        $heritageInserted++;

        $images = $item['images'] ?? [];
        if (is_array($images)) {
            $sort = 0;
            foreach ($images as $img) {
                if (empty($img)) continue;
                $insertGallery->execute([$id, $img, null, $sort]);
                $galleryInserted++;
                $sort++;
            }
        }
    }

    $db->commit();

    echo "Sync complete!\n";
    echo "- cultural_heritage inserted: {$heritageInserted}\n";
    echo "- cultural_heritage_gallery inserted: {$galleryInserted}\n";
    echo $truncate ? "(full truncate mode)\n" : "(upsert-by-id mode)\n";
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    fwrite(STDERR, "Sync failed: " . $e->getMessage() . "\n");
    exit(1);
}

