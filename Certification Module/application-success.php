<?php
require_once dirname(__DIR__) . '/includes/accommodation_form_helpers.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$application = null;

if ($id > 0 && file_exists(dirname(__DIR__) . '/database.db')) {
    ensureAccommodationApplicationsTable();
    $db = new SQLite3(dirname(__DIR__) . '/database.db');
    $stmt = $db->prepare('SELECT establishment_name, application_date, created_at FROM accommodation_applications WHERE id = ?');
    $stmt->bindValue(1, $id, SQLITE3_INTEGER);
    $result = $stmt->execute();
    $application = $result->fetchArray(SQLITE3_ASSOC);
    $db->close();
}

$track = $_GET['track'] ?? null;
if ($track === 'dot') {
    $hotelsUrl = '../Hotel Module/hotels.php?tab=dot';
} else {
    $hotelsUrl = '../Hotel Module/hotels.php?tab=local';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Submitted - Tagum City</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/mobile-navbar.css">
    <link rel="stylesheet" href="../css/accommodation-form.css">
</head>
<body class="cert-form-page">
<?php include dirname(__DIR__) . '/navbar.php'; ?>

<main class="cert-form-main">
    <div class="cert-form-container cert-success-card">
        <div class="cert-success-icon" aria-hidden="true">✓</div>
        <h1>Application Submitted Successfully</h1>
        <?php if ($application): ?>
            <p>Your local certification application for <strong><?php echo htmlspecialchars($application['establishment_name']); ?></strong> has been received.</p>
            <p class="cert-reference">Reference No.: <strong>CTCO-<?php echo str_pad((string) $id, 5, '0', STR_PAD_LEFT); ?></strong></p>
            <p class="cert-success-note">Please bring all required documents listed in Section 7 to the City Tourism and Cultural Office to complete your application.</p>
        <?php else: ?>
            <p>Your application has been received. Please contact the City Tourism and Cultural Office for your reference number.</p>
        <?php endif; ?>
        <div class="cert-form-actions cert-success-actions">
            <a href="<?php echo htmlspecialchars($hotelsUrl); ?>" class="btn btn-primary">Back to Hotels</a>
            <a href="accommodation-form.php" class="btn btn-secondary">Submit Another Application</a>
        </div>
    </div>
</main>
</body>
</html>
