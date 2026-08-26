<?php
// Helper functions for adding/updating experiences in database.db
function db_connect() {

    // Use an absolute DB path to avoid directory issues.
    $dbFile = dirname(__DIR__) . '/database.db';

    if (!file_exists($dbFile)) {
        throw new RuntimeException('SQLite database file not found: ' . $dbFile);
    }

    // This project originally uses PDO for experiences, but your PHP build
    // doesn't have the PDO SQLite driver enabled.
    // Fall back to the built-in SQLite3 extension while keeping the
    // module flow/design intact.
    if (in_array('sqlite', PDO::getAvailableDrivers(), true)) {
        $pdo = new PDO('sqlite:' . $dbFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }

    if (!extension_loaded('sqlite3')) {
        throw new RuntimeException(
            'Neither PDO SQLite nor the SQLite3 PHP extension is available. ' .
            'Enable sqlite PDO extension or sqlite3 extension in php.ini.'
        );
    }

    return new SQLite3($dbFile);
}



function db_save_experience($experience, $isEdit = false) {
    $db = db_connect();

    $isSqlite3 = $db instanceof SQLite3;

    if ($isEdit && !empty($experience['id'])) {
        if ($isSqlite3) {
            $stmt = $db->prepare('UPDATE experiences SET name = :name, type = :type, description = :description, date = :date, image = :image, featured = :featured WHERE id = :id');
            $stmt->bindValue(':name', $experience['name'], SQLITE3_TEXT);
            $stmt->bindValue(':type', $experience['type'], SQLITE3_TEXT);
            $stmt->bindValue(':description', $experience['description'], SQLITE3_TEXT);
            $stmt->bindValue(':date', $experience['date'], SQLITE3_TEXT);
            $stmt->bindValue(':image', $experience['image'], SQLITE3_TEXT);
            $stmt->bindValue(':featured', $experience['featured'] ? 1 : 0, SQLITE3_INTEGER);
            $stmt->bindValue(':id', (int)$experience['id'], SQLITE3_INTEGER);
            $stmt->execute();
            return (int)$experience['id'];
        }

        // PDO path
        $stmt = $db->prepare('UPDATE experiences SET name = :name, type = :type, description = :description, date = :date, image = :image, featured = :featured WHERE id = :id');
        $stmt->execute([
            ':name' => $experience['name'],
            ':type' => $experience['type'],
            ':description' => $experience['description'],
            ':date' => $experience['date'],
            ':image' => $experience['image'],
            ':featured' => $experience['featured'] ? 1 : 0,
            ':id' => $experience['id']
        ]);
        return $experience['id'];
    }

    // Insert new
    if ($isSqlite3) {
        $stmt = $db->prepare('INSERT INTO experiences (name, type, description, date, image, featured) VALUES (:name, :type, :description, :date, :image, :featured)');
        $stmt->bindValue(':name', $experience['name'], SQLITE3_TEXT);
        $stmt->bindValue(':type', $experience['type'], SQLITE3_TEXT);
        $stmt->bindValue(':description', $experience['description'], SQLITE3_TEXT);
        $stmt->bindValue(':date', $experience['date'], SQLITE3_TEXT);
        $stmt->bindValue(':image', $experience['image'], SQLITE3_TEXT);
        $stmt->bindValue(':featured', $experience['featured'] ? 1 : 0, SQLITE3_INTEGER);
        $stmt->execute();
        $lastId = $db->lastInsertRowID();
        return $lastId;
    }

    // PDO path
    $stmt = $db->prepare('INSERT INTO experiences (name, type, description, date, image, featured) VALUES (:name, :type, :description, :date, :image, :featured)');
    $stmt->execute([
        ':name' => $experience['name'],
        ':type' => $experience['type'],
        ':description' => $experience['description'],
        ':date' => $experience['date'],
        ':image' => $experience['image'],
        ':featured' => $experience['featured'] ? 1 : 0
    ]);
    return $db->lastInsertId();
}

