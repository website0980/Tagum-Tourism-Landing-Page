<?php
requireAuth(); // from config

// SQLite PDO
$pdo = new PDO('sqlite:database.db');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tables = [
    'festivals' => 'SELECT * FROM festivals ORDER BY created_at DESC',
    'destinations' => 'SELECT * FROM destinations ORDER BY created_at DESC',
    'experiences' => 'SELECT * FROM experiences ORDER BY created_at DESC',
    'cuisines' => 'SELECT * FROM cuisines ORDER BY created_at DESC',
    'natural_wonders' => 'SELECT * FROM natural_wonders ORDER BY created_at DESC',
    'cultural_sites' => 'SELECT * FROM cultural_sites ORDER BY created_at DESC',
    'media_files' => 'SELECT * FROM media_files ORDER BY uploaded_at DESC'
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>DB Table - Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .db-table { font-size: 12px; }
        .db-table img { max-width: 50px; max-height: 50px; }
    </style>
</head>
<body>
    <header class="admin-header">
        <a href="dashboard.php" class="back-link">← Dashboard</a>
        <h1>Database Tables</h1>
    </header>
    <main class="admin-main">
        <?php foreach ($tables as $table => $query): ?>
            <?php $rows = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC); ?>
            <div class="admin-container">
                <h2><?php echo ucfirst($table); ?> (<?php echo count($rows); ?> rows)</h2>
                <?php if (empty($rows)): ?>
                    <p>No data.</p>
                <?php else: ?>
                    <table class="db-table">
                        <thead>
                            <tr>
                                <?php foreach (array_keys($rows[0]) as $col): ?>
                                    <th><?php echo htmlspecialchars($col); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <?php foreach ($row as $val): ?>
                                        <td><?php echo is_string($val) ? htmlspecialchars($val) : $val; ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </main>
</body>
</html>

