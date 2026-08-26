<?php
/**
 * One-time setup: create carousel_slides table and seed default slides.
 * Run: php database/setup_carousel.php
 */
$dbFile = dirname(__DIR__) . '/database.db';
if (!file_exists($dbFile)) {
    fwrite(STDERR, "database.db not found at $dbFile\n");
    exit(1);
}

$db = new SQLite3($dbFile);
$schema = file_get_contents(__DIR__ . '/carousel_schema.sql');
$db->exec($schema);

$count = (int) $db->querySingle('SELECT COUNT(*) FROM carousel_slides');
if ($count === 0) {
    $defaults = [
        [
            'tagline' => 'Tagumeños: Beauty that Shines from Within.',
            'title' => "Discover\nNatural Beauty",
            'description' => 'Tagumeños are a reflection of true natural beauty radiating warmth, kindness, and genuine smiles that make everyone feel welcome.',
            'image' => 'images/Background for slide 1.jpg',
            'sort_order' => 1,
        ],
        [
            'tagline' => 'Cultural heritage meets modern charm',
            'title' => "Experience\nLocal Culture",
            'description' => 'Immerse yourself in the vibrant traditions, local cuisine, and warm hospitality of Tagum City. Discover authentic experiences that celebrate our rich heritage.',
            'image' => 'images/Background for slide 2 .jpg',
            'sort_order' => 2,
        ],
        [
            'tagline' => 'Tagum Adventures: Feel the Thrill, Live the Moment',
            'title' => "Thrilling\nAdventures",
            'description' => 'Step into the excitement that awaits in Tagum where every journey is filled with adrenaline, discovery, and unforgettable moments. From outdoor explorations to vibrant city experiences, adventure is always just around the corner.',
            'image' => 'images/Background for slide 3.jpg',
            'sort_order' => 3,
        ],
    ];

    $stmt = $db->prepare('INSERT INTO carousel_slides (tagline, title, description, image, sort_order, active) VALUES (?, ?, ?, ?, ?, 1)');
    foreach ($defaults as $slide) {
        $stmt->bindValue(1, $slide['tagline'], SQLITE3_TEXT);
        $stmt->bindValue(2, $slide['title'], SQLITE3_TEXT);
        $stmt->bindValue(3, $slide['description'], SQLITE3_TEXT);
        $stmt->bindValue(4, $slide['image'], SQLITE3_TEXT);
        $stmt->bindValue(5, $slide['sort_order'], SQLITE3_INTEGER);
        $stmt->execute();
    }
    echo "Seeded " . count($defaults) . " default carousel slides.\n";
} else {
    echo "carousel_slides already has $count row(s); skipped seeding.\n";
}

$db->close();
echo "Carousel table ready.\n";
