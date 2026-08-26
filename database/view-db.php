<?php
// Standalone DB viewer - no auth
?>
<!DOCTYPE html>
<html>
<head>
    <title>DB Tables</title>
    <style>
        table { border-collapse: collapse; width:100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        img { max-width: 100px; }
    </style>
</head>
<body>
    <h1>All DB Tables</h1>
    <?php
    $db = new PDO('sqlite:database.db');
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%';")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $rows = $db->query("SELECT * FROM $table LIMIT 20;")->fetchAll(PDO::FETCH_ASSOC);
        echo "<h2>$table (" . count($rows) . " rows)</h2>";
        if (empty($rows)) {
            echo "<p>Empty</p>";
        } else {
            echo "<table>";
            echo "<tr>";
            foreach (array_keys($rows[0]) as $col) {
                echo "<th>$col</th>";
            }
            echo "</tr>";
            foreach ($rows as $row) {
                echo "<tr>";
                foreach ($row as $val) {
                    echo "<td>" . htmlspecialchars($val) . "</td>";
                }
                echo "</tr>";
            }
            echo "</table>";
        }
        echo "<hr>";
    }
    ?>
</body>
</html>

