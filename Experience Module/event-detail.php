<?php
$dbFile = '../database.db';
$event = null;
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (file_exists($dbFile) && $id > 0) {
    try {
        $db = new SQLite3($dbFile);
        $stmt = $db->prepare('SELECT * FROM events WHERE id = ?');
        $stmt->bindValue(1, $id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        if ($row) {
            $event = $row;
        }
        $db->close();
    } catch (Exception $e) {
        // Fallback
    }
}

$pageTitle = $event ? ($event['name'] . ' - Tagum City') : 'Event Not Found - Tagum City';

$lat = null;
$lng = null;
$mapEmbedUrl = '';
$openMapUrl = '';

if ($event) {
    if (isset($event['latitude']) && isset($event['longitude']) && !empty($event['latitude']) && !empty($event['longitude']) && is_numeric($event['latitude']) && is_numeric($event['longitude'])) {
        $lat = (float) $event['latitude'];
        $lng = (float) $event['longitude'];

        $delta = 0.01;
        $left = $lng - $delta;
        $right = $lng + $delta;
        $top = $lat + $delta;
        $bottom = $lat - $delta;

        $mapEmbedUrl = 'https://www.openstreetmap.org/export/embed.html?bbox='
            . rawurlencode($left . ',' . $bottom . ',' . $right . ',' . $top)
            . '&layer=mapnik&marker=' . rawurlencode($lat . ',' . $lng);
        $openMapUrl = 'https://www.openstreetmap.org/?mlat=' . rawurlencode((string) $lat)
            . '&mlon=' . rawurlencode((string) $lng) . '#map=15/' . rawurlencode((string) $lat) . '/' . rawurlencode((string) $lng);
    } elseif (!empty($event['location'])) {
        $query = rawurlencode($event['location'] . ', Tagum City');
        $openMapUrl = 'https://www.openstreetmap.org/search?query=' . $query;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/experience-details.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <style>
        .event-detail-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 1.25rem;
            margin: 1.5rem 0;
        }

        .event-panel {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 1rem 1.1rem;
        }

        .event-panel h3 {
            margin: 0 0 0.75rem;
            color: #1d5a3d;
        }

        .event-description {
            color: #374151;
            line-height: 1.7;
            font-size: 1rem;
        }

        .event-meta-list {
            display: grid;
            gap: 0.6rem;
            color: #374151;
            font-size: 0.98rem;
        }

        .event-meta-list strong {
            color: #111827;
        }

        .event-meta-list a {
            color: #1d5a3d;
            font-weight: 600;
            text-decoration: none;
        }

        .event-meta-list a:hover {
            text-decoration: underline;
        }

        .event-map-wrap {
            margin-top: 1.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            background: #ffffff;
            height: 350px;
        }

        .event-map-wrap #map-container {
            width: 100%;
            height: 100%;
        }

        .event-map-wrap iframe {
            width: 100%;
            height: 320px;
            border: 0;
            display: block;
        }

        .event-map-fallback {
            padding: 1rem 1.1rem;
            color: #374151;
            line-height: 1.6;
        }

        .event-map-actions {
            padding: 0.8rem 1.1rem 1rem;
            border-top: 1px solid #e5e7eb;
        }

        .event-map-actions a {
            color: #1d5a3d;
            font-weight: 600;
            text-decoration: none;
        }

        .event-map-actions a:hover {
            text-decoration: underline;
        }

        @media (max-width: 900px) {
            .event-detail-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<?php include '../navbar.php'; ?>

    <main class="experience-single">
        <div class="container">
            <?php if ($event): ?>
                <article class="experience-detail active">
                    <header class="experience-header">
                        <h1><?php echo htmlspecialchars($event['name']); ?></h1>
                        <div class="experience-meta">
                            <span class="exp-type">Event</span>
                        </div>
                    </header>

                    <?php if (!empty($event['image'])): ?>
                        <div class="experience-image">
                            <img src="../<?php echo htmlspecialchars($event['image']); ?>" alt="<?php echo htmlspecialchars($event['name']); ?>">
                        </div>
                    <?php endif; ?>

                    <div class="event-detail-grid">
                        <div class="event-panel">
                            <h3>Description</h3>
                            <p class="event-description"><?php echo nl2br(htmlspecialchars($event['history'] ?? $event['highlights'] ?? $event['description'] ?? 'No description.')); ?></p>
                            <?php if (!empty($event['highlights'])): ?>
                                <h3 style="margin-top: 1rem;">Highlights</h3>
                                <p class="event-description"><?php echo nl2br(htmlspecialchars($event['highlights'])); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="event-panel">
                            <h3>Event Details</h3>
                            <div class="event-meta-list">
                                <?php if (!empty($event['contact'])): ?>
                                    <div><strong>Contact:</strong> <?php echo htmlspecialchars($event['contact']); ?></div>
                                <?php endif; ?>
                                <?php if (isset($event['latitude']) && isset($event['longitude']) && is_numeric($event['latitude']) && is_numeric($event['longitude'])): ?>
                                    <div><strong>Coordinates:</strong> <?php echo htmlspecialchars(number_format((float)$event['latitude'], 6)); ?>, <?php echo htmlspecialchars(number_format((float)$event['longitude'], 6)); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="event-section">
                        <h3>📍 Location</h3>
                        <p><?php echo htmlspecialchars($event['location'] ?? 'Location not available'); ?></p>
                    </div>
                    <div class="event-map-wrap">
<div class="event-map-container" id="map-container" style="height: 350px;"></div>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
(function() {
    const eventLat = <?php echo $lat ?: 'null'; ?>;
    const eventLng = <?php echo $lng ?: 'null'; ?>;
    const defaultLat = 7.443;
    const defaultLng = 125.807;
    
    let map = L.map('map-container').setView([defaultLat, defaultLng], 13);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    if (eventLat && eventLng) {
        L.marker([eventLat, eventLng]).addTo(map)
            .bindPopup('<?php echo addslashes(htmlspecialchars($event['name'] ?? 'Event Location')); ?>')
            .openPopup();
        map.setView([eventLat, eventLng], 16);
    } else {
        L.marker([defaultLat, defaultLng]).addTo(map)
            .bindPopup('Tagum City - Search for "<?php echo addslashes(htmlspecialchars($event['location'] ?? '')); ?>"');
    }
})();
    </script>
                    </div>

                    <div class="experience-actions">
                        <a href="../Experience Module/events.php" class="smooth-scroll btn btn-secondary">← Back to Events</a>
                    </div>
                </article>
            <?php else: ?>
                <div class="not-found">
                    <h1>Event Not Found</h1>
                    <p>The event you're looking for does not exist.</p>
                    <a href="../Experience Module/events.php" class="smooth-scroll btn btn-primary">← Back to Events</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="../assets/js/experience-details.js"></script>
</body>
</html>
