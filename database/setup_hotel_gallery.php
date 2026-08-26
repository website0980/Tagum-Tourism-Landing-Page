<?php
// Creates hotel gallery table in database.db (idempotent).

function ensureHotelGalleryTable(): void {
    $dbPath = dirname(__DIR__) . '/database.db';
    if (!file_exists($dbPath)) {
        throw new RuntimeException('SQLite database file not found: ' . $dbPath);
    }

    // Use sqlite3 extension for compatibility.
    $db = new SQLite3($dbPath);

    // Use a safe idempotent create.
    $db->exec(
        'CREATE TABLE IF NOT EXISTS hotel_gallery (' .
        '  id INTEGER PRIMARY KEY AUTOINCREMENT,' .
        '  hotel_id INTEGER NOT NULL,' .
        '  image TEXT NOT NULL,' .
        '  caption TEXT DEFAULT NULL,' .
        '  sort_order INTEGER NOT NULL DEFAULT 0,' .
        '  created_at DATETIME DEFAULT CURRENT_TIMESTAMP' .
        ');'
    );

    $db->close();
}

// If executed directly, run the setup.
if (realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    ensureHotelGalleryTable();
    echo "hotel_gallery table ready.\n";
}

