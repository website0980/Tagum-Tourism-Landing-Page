<?php
// Start session at the very beginning
session_start();

// Load destinations from SQLite database (same as admin)
$dbFile = dirname(__DIR__) . '/database.db';
$destinations = [];

if (file_exists($dbFile)) {
    try {
        $db = new SQLite3($dbFile);
        $query = "SELECT * FROM destinations ORDER BY id";
        $result = $db->query($query);
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $row['featured'] = (bool) ($row['featured'] ?? false);
            $destinations[] = $row;
        }
        $db->close();
    } catch (Exception $e) {
        $destinations = [];
    }
}

// Get destination from URL parameter
$destinationParam = $_GET['destination'] ?? null;
$selectedDestination = null;
$selectedIndex = 0;

// Find the selected destination
if ($destinationParam !== null && !empty($destinations)) {
    foreach ($destinations as $index => $dest) {
        // Match by name (URL-friendly format)
        $friendlyName = strtolower(str_replace(' ', '-', $dest['name']));
        if ($friendlyName === strtolower($destinationParam)) {
            $selectedDestination = $dest;
            $selectedIndex = $index;
            break;
        }
    }
}

// Default to first destination if not found
if ($selectedDestination === null && !empty($destinations)) {
    $selectedDestination = $destinations[0];
}

// If no destinations exist, show empty state
if (empty($destinations)) {
    $selectedDestination = [
        'name' => 'No Destinations',
        'description' => 'No destinations have been added yet. Please check back soon!',
        'location' => '',
        'accessibility' => '',
        'features' => '',
        'facilities' => '',
        'entrance_fee' => '',
        'contact' => '',
        'best_time' => '',
        'what_to_pack' => '',
        'visiting_rules' => '',
        'image' => '',
        'featured' => false
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($selectedDestination['name'] ?? 'Destination'); ?> - Tagum City</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/plan-details.css">
    <link rel="stylesheet" href="../css/mobile-navbar.css">
    <script src="../js/navbar.js"></script>
</head>
<body>
    <?php include '../navbar.php'; ?>


    <!-- Plan Details Section -->
    <section class="plan-details">
        <div class="plan-container">
            <!-- Breadcrumb Buttons -->


            <!-- Destination Selector -->
            <?php if (!empty($destinations)): ?>
                <div class="destination-selector">
                    <label for="destination-select">Choose a destination:</label>
                    <select id="destination-select" onchange="navigateToDestination(this.value)">
                        <option value="">All Destinations</option>
                        <optgroup label="⭐ Featured">
                        <?php foreach ($destinations as $dest): ?>
                            <?php if (isset($dest['featured']) && $dest['featured'] === true): ?>
                                <option value="<?php echo strtolower(str_replace(' ', '-', $dest['name'])); ?>" <?php echo ($dest['name'] === $selectedDestination['name']) ? 'selected' : ''; ?>>
                                    ⭐ <?php echo htmlspecialchars($dest['name']); ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="All Destinations">
                        <?php foreach ($destinations as $dest): ?>
                            <option value="<?php echo strtolower(str_replace(' ', '-', $dest['name'])); ?>" <?php echo ($dest['name'] === $selectedDestination['name']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dest['name']); ?>
                            </option>
                        <?php endforeach; ?>
                        </optgroup>
                    </select>
                </div>
            <?php endif; ?>

            <!-- Destination Detail -->
            <div class="destination-detail active">
                <h1><?php echo (isset($selectedDestination['featured']) && $selectedDestination['featured']) ? '⭐ ' : ''; ?><?php echo htmlspecialchars($selectedDestination['name']); ?></h1>
                <?php
                if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true):
                ?>
                <?php endif; ?>
                
                <?php if (!empty($selectedDestination['image'])): ?>
                    <?php
                    // Resolve image path relative to the Plan Module/ directory
                    $destImage = trim((string)$selectedDestination['image']);
                    if ($destImage !== '' && stripos($destImage, 'http') !== 0 && strpos($destImage, '/') !== 0 && strpos($destImage, '../') !== 0) {
                        $destImage = '../' . $destImage;
                    }
                    ?>
                    <div class="image-showcase">
                        <img src="<?php echo htmlspecialchars($destImage); ?>" alt="<?php echo htmlspecialchars($selectedDestination['name']); ?>" class="destination-image">
                    </div>
                <?php endif; ?>
                
                <div class="destination-content">
                    <?php if (!empty($selectedDestination['description'])): ?>
                        <h2><?php echo htmlspecialchars($selectedDestination['description']); ?></h2>
                    <?php endif; ?>

                    <?php if (!empty($selectedDestination['location']) || !empty($selectedDestination['accessibility']) || !empty($selectedDestination['features']) || !empty($selectedDestination['facilities']) || !empty($selectedDestination['contact'])): ?>
                        <h3>📖 Destination Guide</h3>
                        <?php if (!empty($selectedDestination['location'])): ?>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($selectedDestination['location']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($selectedDestination['accessibility'])): ?>
                            <p><strong>Accessibility:</strong> <?php echo htmlspecialchars($selectedDestination['accessibility']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($selectedDestination['features'])): ?>
                            <p><strong>Main Features:</strong></p>
                            <ul>
                                <?php foreach (explode("\n", $selectedDestination['features']) as $feature): ?>
                                    <?php if (trim($feature)): ?>
                                        <li><?php echo htmlspecialchars(trim($feature)); ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if (!empty($selectedDestination['facilities'])): ?>
                            <p><strong>Facilities:</strong></p>
                            <ul>
                                <?php foreach (explode("\n", $selectedDestination['facilities']) as $facility): ?>
                                    <?php if (trim($facility)): ?>
                                        <li><?php echo htmlspecialchars(trim($facility)); ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if (!empty($selectedDestination['entrance_fee'])): ?>
                            <p><strong>Entrance Fee:</strong> <?php echo htmlspecialchars($selectedDestination['entrance_fee']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($selectedDestination['contact'])): ?>
                            <p><strong>Contact:</strong> <?php echo htmlspecialchars($selectedDestination['contact']); ?></p>
                        <?php endif; ?>

                        <?php if (!empty($selectedDestination['best_time'])): ?>
                            <h3>🗓️ Best Time to Visit</h3>
                            <p><?php echo htmlspecialchars($selectedDestination['best_time']); ?></p>
                        <?php endif; ?>

                        <?php if (!empty($selectedDestination['what_to_pack'])): ?>
                            <h3>🎒 What to Pack</h3>
                            <ul>
                                <?php foreach (explode("\n", $selectedDestination['what_to_pack']) as $item): ?>
                                    <?php if (trim($item)): ?>
                                        <li><?php echo htmlspecialchars(trim($item)); ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>

                        <?php if (!empty($selectedDestination['visiting_rules'])): ?>
                            <h3>✅ Visiting Rules</h3>
                            <ul>
                                <?php foreach (explode("\r\n", $selectedDestination['visiting_rules']) as $rule): ?>
                                    <?php if (trim($rule)): ?>
                                        <li><?php echo htmlspecialchars(trim($rule)); ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">

            <div class="footer-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Contact Us</a>
            </div>
        </div>
    </footer>

    <script>
        function navigateToDestination(destinationName) {
            window.location.href = '?destination=' + encodeURIComponent(destinationName);
        }
    </script>
</body>
</html>
