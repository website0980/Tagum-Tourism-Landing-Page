<?php
// Load real data from the SQLite database
$dbFile = __DIR__ . '/database.db';
$events = [];
$festivals = [];

if (file_exists($dbFile)) {
    try {
        $db = new SQLite3($dbFile);

        // Events
        $result = $db->query("SELECT * FROM events ORDER BY event_date ASC");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $events[] = $row;
        }

        // Festivals
        $result = $db->query("SELECT * FROM festivals ORDER BY id ASC");
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $festivals[] = $row;
        }

        $db->close();
    } catch (Exception $e) {
        // Fall back to JSON files if DB fails
        $festivals = json_decode(file_get_contents(__DIR__ . '/data/festivals.json'), true) ?? [];
    }
}

// Helper to resolve image paths relative to the site root (explore.php is at root)
function exploreImagePath($path) {
    $path = trim((string)$path);
    if ($path === '') return '';
    if (stripos($path, 'http') === 0) return $path;
    if (strpos($path, '/') === 0) return $path;
    // Strip leading ../ since explore.php is at the site root
    while (strpos($path, '../') === 0) {
        $path = substr($path, 3);
    }
    return $path;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore Tagum City</title>
    <link rel="stylesheet" href="css/style.css">
<link rel="stylesheet" href="css/explore-full-page.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
<img src="images/City of Tagum.png" alt="Tagum City" class="logo-img">
                <span class="logo-text">Tagum City</span>
            </div>
            <ul class="nav-menu">
                <li><a href="index.php#home" class="nav-link">Home</a></li>
                <li><a href="index.php#explore" class="nav-link">Explore</a></li>
                <li><a href="index.php#experiences" class="nav-link">Experiences</a></li>
                <li><a href="index.php#plan" class="nav-link">Plan</a></li>
            </ul>
        </div>
    </nav>

    <!-- Explore Content Section -->
    <section class="explore-full-page">
        <div class="explore-container">
            <!-- Breadcrumb Navigation -->
            <div class="breadcrumb">
                <a href="../index.php">Home</a> / <span>Explore Tagum City</span>
            </div>

            <!-- Section Tabs -->
            <div class="section-tabs">
                <button class="tab-btn" data-section="events">Events</button>
                <button class="tab-btn" data-section="festivals">Festivals</button>
            </div>

            <!-- Events Content -->
            <div class="section-content" id="events">
                <h1>Events</h1>
                <div class="content-text">
                    <h2>Discover Local Events</h2>
                    <p>Browse events happening in Tagum City. Click an event to see full details.</p>

                    <?php if (!empty($events)): ?>
                    <div class="cuisine-grid">
                        <?php foreach ($events as $event): ?>
                            <div class="cuisine-category">
                                <?php if (!empty($event['image'])): ?>
                                    <img src="<?php echo htmlspecialchars(exploreImagePath($event['image'])); ?>" alt="<?php echo htmlspecialchars($event['name']); ?>" class="category-image" onerror="this.style.display='none'">
                                <?php endif; ?>
                                <h3><?php echo htmlspecialchars($event['name']); ?></h3>
                                <?php if (!empty($event['event_date'])): ?>
                                    <p class="item-count">📅 <?php echo htmlspecialchars(date('F j, Y', strtotime($event['event_date']))); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($event['location'])): ?>
                                    <p>📍 <?php echo htmlspecialchars($event['location']); ?></p>
                                <?php endif; ?>
                                <button class="toggle-items-btn">View Details</button>
                                <div class="items-list">
                                    <?php if (!empty($event['Description'])): ?>
                                        <div class="food-item">
                                            <div class="food-info">
                                                <h4>📖 Details</h4>
                                                <p><?php echo htmlspecialchars($event['Description']); ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($event['highlights'])): ?>
                                        <div class="food-item">
                                            <div class="food-info">
                                                <h4>⭐ Highlights</h4>
                                                <p><?php echo htmlspecialchars($event['highlights']); ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <h3>Featured Events:</h3>
                    <ul>
                        <li><strong>Araw ng Tagum Festival</strong> - Annual celebration of the city's founding</li>
                        <li><strong>Davao Food Festival</strong> - Showcase of local culinary traditions</li>
                        <li><strong>Tagum Sports Fest</strong> - Community sports and recreation events</li>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Festivals Content -->
            <div class="section-content" id="festivals">
                <h1>Festivals</h1>
                <div class="content-text">
                    <h2>Celebrate Local Culture</h2>
                    <p>Tagum City comes alive with vibrant festivals throughout the year, showcasing the rich traditions, music, dance, and culinary heritage of the region. Experience the warmth and hospitality of the local community.</p>
                    
                    <?php if (!empty($festivals)): ?>
                    <div class="cuisine-grid">
                        <?php foreach ($festivals as $festival): ?>
                            <div class="cuisine-category">
                                <?php if (!empty($festival['image'])): ?>
                                    <img src="<?php echo htmlspecialchars(exploreImagePath($festival['image'])); ?>" alt="<?php echo htmlspecialchars($festival['name']); ?>" class="category-image" onerror="this.style.display='none'">
                                <?php endif; ?>
                                <h3><?php echo htmlspecialchars($festival['name']); ?></h3>
                                <p><?php echo htmlspecialchars($festival['description'] ?? ''); ?></p>
                                <?php if (!empty($festival['date'])): ?>
                                    <p class="item-count">📅 <?php echo htmlspecialchars($festival['date']); ?></p>
                                <?php endif; ?>
                                <button class="toggle-items-btn">View Details</button>
                                <div class="items-list">
                                    <?php if (!empty($festival['highlights'])): ?>
                                        <div class="food-item">
                                            <div class="food-info">
                                                <h4>🎉 Highlights</h4>
                                                <p><?php echo htmlspecialchars($festival['highlights']); ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($festival['activities'])): ?>
                                        <div class="food-item">
                                            <div class="food-info">
                                                <h4>✨ Activities</h4>
                                                <p><?php echo htmlspecialchars($festival['activities']); ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <h3>Annual Festivals:</h3>
                    <ul>
                        <li><strong>Kadayawan Festival</strong> - Week-long celebration of thanksgiving</li>
                        <li><strong>Araw ng Tagum</strong> - Foundation day festivities</li>
                        <li><strong>Sinigang Festival</strong> - Food and cultural festival</li>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <p></p>
            <div class="footer-links">
                <a href="#"></a>
                <a href="#"></a>
                <a href="#"></a>
            </div>
        </div>
    </footer>

<script src="js/explore-full-page.js"></script>
</body>
</html>