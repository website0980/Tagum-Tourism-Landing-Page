<?php
declare(strict_types=1);

/**
 * Import existing JSON data from /assets/data into MySQL.
 *
 * Usage (PowerShell):
 *   php .\database\import_json_to_mysql.php --host=localhost --db=tagum_admin --user=root --pass= --truncate=1
 *
 * Notes:
 * - This script expects the schema from database/tagum_admin_schema.sql to already be imported.
 * - If --truncate=1 is passed, tables will be cleared before import.
 */

function arg(array $argv, string $name, ?string $default = null): ?string {
    foreach ($argv as $a) {
        if (str_starts_with($a, "--{$name}=")) {
            return substr($a, strlen("--{$name}="));
        }
    }
    return $default;
}

function readJson(string $path): array {
    if (!file_exists($path)) return [];
    $raw = file_get_contents($path);
    if ($raw === false) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

$host = arg($argv, 'host', 'localhost');
$db   = arg($argv, 'db', 'tagum_admin');
$user = arg($argv, 'user', 'root');
$pass = arg($argv, 'pass', '');
$truncate = arg($argv, 'truncate', '0') === '1';

$projectRoot = realpath(__DIR__ . '/..');
if ($projectRoot === false) {
    fwrite(STDERR, "Could not resolve project root.\n");
    exit(1);
}

$dataDir = $projectRoot . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'data';

$pdo = new PDO(
    "mysql:host={$host};dbname={$db};charset=utf8mb4",
    $user,
    $pass,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

// Helpful for bulk inserts
$pdo->exec("SET SESSION sql_mode = 'STRICT_ALL_TABLES'");

try {
    $pdo->beginTransaction();

    if ($truncate) {
        // Disable foreign keys for truncation order
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
        foreach ([
            'cuisine_items',
            'cuisine_categories',
            'destinations',
            'experiences',
            'natural_wonders',
            'cultural_sites',
            'festivals',
            'media_files',
        ] as $t) {
            $pdo->exec("TRUNCATE TABLE {$t}");
        }
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    }

    // -------------------
    // Destinations
    // -------------------
    $destinations = readJson($dataDir . DIRECTORY_SEPARATOR . 'destinations.json');
    $stmtDest = $pdo->prepare(
        "INSERT INTO destinations
            (name, type, description, location, accessibility, features, facilities, entrance_fee, contact, best_time, what_to_pack, visiting_rules, image, featured)
         VALUES
            (:name, :type, :description, :location, :accessibility, :features, :facilities, :entrance_fee, :contact, :best_time, :what_to_pack, :visiting_rules, :image, :featured)"
    );
    $countDest = 0;
    foreach ($destinations as $d) {
        if (!is_array($d)) continue;
        $name = trim((string)($d['name'] ?? ''));
        if ($name === '') continue;
        $stmtDest->execute([
            ':name' => $name,
            ':type' => (string)($d['type'] ?? ''),
            ':description' => (string)($d['description'] ?? ''),
            ':location' => $d['location'] ?? null,
            ':accessibility' => $d['accessibility'] ?? null,
            ':features' => $d['features'] ?? null,
            ':facilities' => $d['facilities'] ?? null,
            ':entrance_fee' => $d['entrance_fee'] ?? null,
            ':contact' => $d['contact'] ?? null,
            ':best_time' => $d['best_time'] ?? null,
            ':what_to_pack' => $d['what_to_pack'] ?? null,
            ':visiting_rules' => $d['visiting_rules'] ?? null,
            ':image' => $d['image'] ?? null,
            ':featured' => !empty($d['featured']) ? 1 : 0,
        ]);
        $countDest++;
    }

    // -------------------
    // Experiences
    // -------------------
    $experiences = readJson($dataDir . DIRECTORY_SEPARATOR . 'experiences.json');
    $stmtExp = $pdo->prepare(
        "INSERT INTO experiences
            (id, name, type, description, date, link, image, featured)
         VALUES
            (:id, :name, :type, :description, :date, :link, :image, :featured)
         ON DUPLICATE KEY UPDATE
            name=VALUES(name),
            type=VALUES(type),
            description=VALUES(description),
            date=VALUES(date),
            link=VALUES(link),
            image=VALUES(image),
            featured=VALUES(featured)"
    );
    $countExp = 0;
    foreach ($experiences as $e) {
        if (!is_array($e)) continue;
        $name = trim((string)($e['name'] ?? ''));
        if ($name === '') continue;
        $id = (int)($e['id'] ?? 0);
        if ($id <= 0) $id = null;

        $date = $e['date'] ?? null;
        if (is_string($date) && trim($date) === '') $date = null;

        $stmtExp->execute([
            ':id' => $id,
            ':name' => $name,
            ':type' => (string)($e['type'] ?? ''),
            ':description' => (string)($e['description'] ?? ''),
            ':date' => $date,
            ':link' => $e['link'] ?? null,
            ':image' => $e['image'] ?? null,
            ':featured' => !empty($e['featured']) ? 1 : 0,
        ]);
        $countExp++;
    }

    // -------------------
    // Cuisine categories + items
    // -------------------
    $cuisine = readJson($dataDir . DIRECTORY_SEPARATOR . 'cuisine.json');
    $stmtCat = $pdo->prepare(
        "INSERT INTO cuisine_categories (category, description, image)
         VALUES (:category, :description, :image)
         ON DUPLICATE KEY UPDATE
            description=VALUES(description),
            image=VALUES(image)"
    );
    $stmtCatId = $pdo->prepare("SELECT id FROM cuisine_categories WHERE category = ?");
    $stmtItem = $pdo->prepare(
        "INSERT INTO cuisine_items (category_id, name, description, image, sort_order)
         VALUES (:category_id, :name, :description, :image, :sort_order)"
    );
    $countCats = 0;
    $countItems = 0;
    foreach ($cuisine as $cat) {
        if (!is_array($cat)) continue;
        $category = trim((string)($cat['category'] ?? ''));
        if ($category === '') continue;

        $stmtCat->execute([
            ':category' => $category,
            ':description' => (string)($cat['description'] ?? ''),
            ':image' => $cat['image'] ?? null,
        ]);
        $stmtCatId->execute([$category]);
        $row = $stmtCatId->fetch();
        if (!$row) continue;
        $categoryId = (int)$row['id'];
        $countCats++;

        $items = $cat['items'] ?? [];
        if (!is_array($items)) $items = [];
        $sort = 0;
        foreach ($items as $it) {
            if (!is_array($it)) continue;
            $itemName = trim((string)($it['name'] ?? ''));
            if ($itemName === '') continue;
            $stmtItem->execute([
                ':category_id' => $categoryId,
                ':name' => $itemName,
                ':description' => $it['description'] ?? null,
                ':image' => $it['image'] ?? null,
                ':sort_order' => $sort,
            ]);
            $sort++;
            $countItems++;
        }
    }

    // -------------------
    // Natural wonders
    // -------------------
    $wonders = readJson($dataDir . DIRECTORY_SEPARATOR . 'natural-wonders.json');
    $stmtW = $pdo->prepare(
        "INSERT INTO natural_wonders (name, description, image, location, features, best_time)
         VALUES (:name, :description, :image, :location, :features, :best_time)"
    );
    $countW = 0;
    foreach ($wonders as $w) {
        if (!is_array($w)) continue;
        $name = trim((string)($w['name'] ?? ''));
        if ($name === '') continue;
        $stmtW->execute([
            ':name' => $name,
            ':description' => (string)($w['description'] ?? ''),
            ':image' => $w['image'] ?? null,
            ':location' => $w['location'] ?? null,
            ':features' => $w['features'] ?? null,
            ':best_time' => $w['best_time'] ?? null,
        ]);
        $countW++;
    }

    // -------------------
    // Cultural sites
    // -------------------
    $sites = readJson($dataDir . DIRECTORY_SEPARATOR . 'cultural-sites.json');
    $stmtS = $pdo->prepare(
        "INSERT INTO cultural_sites (name, description, image, location, history, highlights)
         VALUES (:name, :description, :image, :location, :history, :highlights)"
    );
    $countS = 0;
    foreach ($sites as $s) {
        if (!is_array($s)) continue;
        $name = trim((string)($s['name'] ?? ''));
        if ($name === '') continue;
        $stmtS->execute([
            ':name' => $name,
            ':description' => (string)($s['description'] ?? ''),
            ':image' => $s['image'] ?? null,
            ':location' => $s['location'] ?? null,
            ':history' => $s['history'] ?? null,
            ':highlights' => $s['highlights'] ?? null,
        ]);
        $countS++;
    }

    // -------------------
    // Festivals
    // -------------------
    $festivals = readJson($dataDir . DIRECTORY_SEPARATOR . 'festivals.json');
    $stmtF = $pdo->prepare(
        "INSERT INTO festivals (name, description, date, highlights, activities, image)
         VALUES (:name, :description, :date, :highlights, :activities, :image)"
    );
    $countF = 0;
    foreach ($festivals as $f) {
        if (!is_array($f)) continue;
        $name = trim((string)($f['name'] ?? ''));
        if ($name === '') continue;
        $stmtF->execute([
            ':name' => $name,
            ':description' => (string)($f['description'] ?? ''),
            ':date' => $f['date'] ?? null,
            ':highlights' => $f['highlights'] ?? null,
            ':activities' => $f['activities'] ?? null,
            ':image' => $f['image'] ?? null,
        ]);
        $countF++;
    }

    $pdo->commit();

    echo "Import complete.\n";
    echo "- destinations: {$countDest}\n";
    echo "- experiences: {$countExp}\n";
    echo "- cuisine categories: {$countCats}\n";
    echo "- cuisine items: {$countItems}\n";
    echo "- natural wonders: {$countW}\n";
    echo "- cultural sites: {$countS}\n";
    echo "- festivals: {$countF}\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    fwrite(STDERR, "Import failed: " . $e->getMessage() . "\n");
    exit(1);
}

