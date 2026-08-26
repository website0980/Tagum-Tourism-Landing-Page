<?php
// Creates cultural heritage tables in database.db (idempotent).

function ensureCulturalHeritageTables(): void {
    $dbPath = dirname(__DIR__) . '/database.db';
    if (!file_exists($dbPath)) {
        throw new RuntimeException('SQLite database file not found: ' . $dbPath);
    }

    // Use SQLite3 extension for compatibility.
    $db = new SQLite3($dbPath);

    // Main table
    $db->exec(
        'CREATE TABLE IF NOT EXISTS cultural_heritage (' .
        '  id INTEGER PRIMARY KEY AUTOINCREMENT,' .
        '  title TEXT NOT NULL,' .
        '  category TEXT NOT NULL,' .
        '  description TEXT NOT NULL,' .
        '  image TEXT DEFAULT NULL,' .
        '  created_at DATETIME DEFAULT CURRENT_TIMESTAMP' .
        ');'
    );

    // Gallery images table (one row per image)
    $db->exec(
        'CREATE TABLE IF NOT EXISTS cultural_heritage_gallery (' .
        '  id INTEGER PRIMARY KEY AUTOINCREMENT,' .
        '  heritage_id INTEGER NOT NULL,' .
        '  image TEXT NOT NULL,' .
        '  caption TEXT DEFAULT NULL,' .
        '  sort_order INTEGER NOT NULL DEFAULT 0,' .
        '  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,' .
        '  FOREIGN KEY (heritage_id) REFERENCES cultural_heritage(id) ON DELETE CASCADE' .
        ');'
    );

    $db->close();
}

// If executed directly, run the setup.
if (realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__)) {
    ensureCulturalHeritageTables();
    echo "cultural_heritage tables ready.\n";
}

