#!/usr/bin/env php
<?php

if ($argc < 3) {
    echo "Usage:\n";
    echo "php json_to_sql.php <json_file> <table_prefix>\n";
    echo "Example:\n";
    echo "php json_to_sql.php cuisine.json cuisine\n";
    exit(1);
}

$jsonFile = $argv[1];
$tablePrefix = $argv[2];

if (!file_exists($jsonFile)) {
    die("File not found: $jsonFile\n");
}

$json = file_get_contents($jsonFile);
$data = json_decode($json, true);

if (!$data) {
    die("Invalid JSON\n");
}

/**
 * SQLite-safe escape
 * Converts:
 *   '  →  ''
 */
function esc($value) {
    if ($value === null || $value === '') {
        return "NULL";
    }

    // Ensure string
    $value = (string)$value;

    // Escape single quotes for SQLite
    $value = str_replace("'", "''", $value);

    return "'{$value}'";
}

function is_assoc($array) {
    return array_keys($array) !== range(0, count($array) - 1);
}

$mainTable = "{$tablePrefix}_main";
$itemTable = "{$tablePrefix}_items";

$mainId = 1;
$itemId = 1;

$sql = [];

foreach ($data as $entry) {

    $items = $entry['items'] ?? null;
    unset($entry['items']);

    // MAIN TABLE INSERT
    $columns = array_keys($entry);
    $values = array_map(fn($v) => esc($v), array_values($entry));

    $sql[] = sprintf(
        "INSERT INTO %s (id, %s) VALUES (%d, %s);",
        $mainTable,
        implode(", ", $columns),
        $mainId,
        implode(", ", $values)
    );

    // NESTED ITEMS
    if ($items && is_array($items)) {
        foreach ($items as $item) {
            $itemColumns = array_keys($item);
            $itemValues = array_map(fn($v) => esc($v), array_values($item));

            $sql[] = sprintf(
                "INSERT INTO %s (id, %s, parent_id) VALUES (%d, %s, %d);",
                $itemTable,
                implode(", ", $itemColumns),
                $itemId,
                implode(", ", $itemValues),
                $mainId
            );

            $itemId++;
        }
    }

    $mainId++;
}

echo implode("\n", $sql) . "\n";
