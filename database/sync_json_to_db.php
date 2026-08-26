<?php
// Auto-sync JSON files to SQLite DB - Run this manually or via cron
$jsonFiles = [
    'experiences' => 'assets/data/experiences.json',
    'destinations' => 'assets/data/destinations.json',
    'festivals' => 'assets/data/festivals.json',
    'cuisines' => 'assets/data/cuisine.json',
    'natural_wonders' => 'assets/data/natural-wonders.json',
];

$db = new PDO('sqlite:../database.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

foreach ($jsonFiles as $table => $jsonPath) {
    if (file_exists($jsonPath)) {
        $data = json_decode(file_get_contents($jsonPath), true) ?? [];
        
        // Clear table
        $db->exec("DELETE FROM $table");
        
        // Insert all
        $stmt = $db->prepare("INSERT INTO $table (id, name, type, description, image, date, featured, link) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($data as $item) {
            $stmt->execute([
                $item['id'] ?? null,
                $item['name'] ?? '',
                $item['type'] ?? '',
                $item['description'] ?? '',
                $item['image'] ?? '',
                $item['date'] ?? null,
                $item['featured'] ?? false,
                $item['link'] ?? null
            ]);
        }
        echo "Synced $table: " . count($data) . " records<br>";
    } else {
        echo "Missing JSON: $jsonPath<br>";
    }
}
echo "Sync complete!";
?>

