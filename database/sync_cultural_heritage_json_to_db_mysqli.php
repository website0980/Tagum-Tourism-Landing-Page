<?php
// Sync Cultural Heritage JSON into SQLite (database.db) WITHOUT PDO.
// This avoids the common issue: "PDO could not find driver".
//
// Usage:
//   php database/sync_cultural_heritage_json_to_db_mysqli.php --truncate=1
// (Even though the name says mysqli, it uses SQLite3 extension.)

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

require_once __DIR__ . '/setup_cultural_heritage_tables.php';

// Ensure tables exist
ensureCulturalHeritageTables();

$db = new SQLite3($dbPath);
$db->busyTimeout(5000);

if ($truncate) {
    $db->exec('DELETE FROM cultural_heritage_gallery');
    $db->exec('DELETE FROM cultural_heritage');
}

$heritageStmt = $db->prepare(
    'INSERT INTO cultural_heritage (id, title, category, description, image) VALUES (:id, :title, :category, :description, :image)'
);

$galleryStmt = $db->prepare(
    'INSERT INTO cultural_heritage_gallery (heritage_id, image, caption, sort_order) VALUES (:heritage_id, :image, :caption, :sort_order)'
);

$db->exec('BEGIN');

try {
    $heritageInserted = 0;
    $galleryInserted = 0;

    foreach ($items as $item) {
        if (!is_array($item)) continue;

        $id = $item['id'] ?? null;
        if ($id === null || !is_numeric($id)) {
            continue;
        }

        $title = (string)($item['title'] ?? '');
        $category = (string)($item['category'] ?? '');
        $description = (string)($item['description'] ?? '');
        $image = $item['image'] ?? null;

        if ($title === '' || $category === '' || $description === '') {
            continue;
        }

        if (!$truncate) {
            $delG = $db->prepare('DELETE FROM cultural_heritage_gallery WHERE heritage_id = :hid');
            $delG->bindValue(':hid', (int)$id, SQLITE3_INTEGER);
            $delG->execute();

            $del = $db->prepare('DELETE FROM cultural_heritage WHERE id = :id');
            $del->bindValue(':id', (int)$id, SQLITE3_INTEGER);
            $del->execute();
        }

        $heritageStmt->bindValue(':id', (int)$id, SQLITE3_INTEGER);
        $heritageStmt->bindValue(':title', $title, SQLITE3_TEXT);
        $heritageStmt->bindValue(':category', $category, SQLITE3_TEXT);
        $heritageStmt->bindValue(':description', $description, SQLITE3_TEXT);
        $heritageStmt->bindValue(':image', $image, SQLITE3_TEXT);
        $heritageStmt->execute();

        $heritageInserted++;

        $images = $item['images'] ?? [];
        if (is_array($images)) {
            $sort = 0;
            foreach ($images as $img) {
                if (empty($img)) continue;

                $galleryStmt->bindValue(':heritage_id', (int)$id, SQLITE3_INTEGER);
                $galleryStmt->bindValue(':image', (string)$img, SQLITE3_TEXT);
                $galleryStmt->bindValue(':caption', null, SQLITE3_NULL);
                $galleryStmt->bindValue(':sort_order', $sort, SQLITE3_INTEGER);
                $galleryStmt->execute();

                $galleryInserted++;
                $sort++;
            }
        }
    }

    $db->exec('COMMIT');

    echo "Sync complete!\n";
    echo "- cultural_heritage inserted: {$heritageInserted}\n";
    echo "- cultural_heritage_gallery inserted: {$galleryInserted}\n";
    echo $truncate ? "(full truncate mode)\n" : "(upsert-by-id mode)\n";
} catch (Throwable $e) {
    $db->exec('ROLLBACK');
    fwrite(STDERR, "Sync failed: " . $e->getMessage() . "\n");
    exit(1);
}

