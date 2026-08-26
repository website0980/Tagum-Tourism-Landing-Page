<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Details - Tagum City</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/experience-details.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <style>
        .event-detail-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .event-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .event-image {
            width: 100%;
            max-height: 500px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 2rem;
        }
        .event-content {
            line-height: 1.8;
            color: #555;
        }
        .event-section {
            margin: 2rem 0;
            padding: 1.5rem;
            background: #f9f9f9;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color, #1d5a3d);
        }
        .event-section h3 {
            color: var(--primary-color, #1d5a3d);
            margin-bottom: 1rem;
        }
        .back-btn {
            display: inline-block;
            margin-bottom: 1rem;
            padding: 0.75rem 1.5rem;
            background: var(--primary-color, #1d5a3d);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            transition: background 0.3s;
        }
        .back-btn:hover {
            background: var(--dark-green, #145233);
        }
        .event-map-container {
            margin: 2rem 0;
            height: 420px;
            position: relative;
        }
        .event-map-wrap {
            height: 100%;
            border: 2px solid #1d5a3d;
            border-radius: 12px;
            overflow: hidden;
            background: #f8fafc;
            box-shadow: 0 6px 20px rgba(29,90,61,0.2);
        }
        #map-container {
            height: 100%;
            width: 100%;
        }
    </style>
</head>
<body>
<?php include '../navbar.php'; ?>

    <!-- Event Detail -->
    <main class="main-content" style="padding-top: 2rem; padding-bottom: 3rem;">
        <section class="event-detail-section">
            <div class="event-detail-container" style="position: relative; z-index: 1;">
            <a href="explore.php?section=events" class="back-btn">← Back to Events</a>

            <?php
            $dbFile = '../database.db';
            $eventId = $_GET['id'] ?? null;
            $event = null;

            if ($eventId && is_numeric($eventId)) {
                if (file_exists($dbFile)) {
                    try {
                        $db = new SQLite3($dbFile);
                        $stmt = $db->prepare('SELECT * FROM events WHERE id = :id');
                        $stmt->bindValue(':id', (int)$eventId, SQLITE3_INTEGER);
                        $result = $stmt->execute();
                        $event = $result->fetchArray(SQLITE3_ASSOC);
                        $db->close();
                    } catch (Exception $e) {
                        $event = null;
                    }
                }
            }

            if ($event):
            ?>

            <div class="event-header">
                <h1><?php echo htmlspecialchars($event['name']); ?></h1>
                <?php if (!empty($event['event_date'])): ?>
                    <p style="font-size: 1.1rem; color: #1d5a3d; font-weight: 600; margin-top: 0.5rem;">
                        📅 <?php echo htmlspecialchars(date('F j, Y', strtotime($event['event_date']))); ?>
                    </p>
                <?php endif; ?>
            </div>

            <?php
            $imagePath = $event['image'] ?? '';
            // Fix image path for Explore Module subdirectory
            if (strpos($imagePath, 'images/') === 0) {
                $imagePath = '../' . $imagePath;
            } elseif (strpos($imagePath, 'assets/') === 0) {
                $imagePath = '../' . $imagePath;
            }
            if (!empty($imagePath)): ?>
                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($event['name']); ?>" class="event-image" onerror="this.style.display='none'">
            <?php endif; ?>

            <div class="event-content">
                <?php if (!empty($event['history'])): ?>
                    <div class="event-section">
                        <h3>📝 About</h3>
                        <p><?php echo nl2br(htmlspecialchars($event['history'])); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($event['location'])): ?>
                    <div class="event-section">
                        <h3>📍 Location</h3>
                        <p><?php echo htmlspecialchars($event['location']); ?></p>
        <div class="event-map-container">
            <div class="event-map-wrap">
                <div id="map-container"></div>
                <div id="map-loading" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
                    <div style="font-size: 2rem;">🗺️</div>
                    <p style="margin-top: 0.5rem; color: #666;">Loading map...</p>
                </div>
            </div>
        </div>
        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($event['highlights'])): ?>
                    <div class="event-section">
                        <h3>⭐ Highlights</h3>
                        <p><?php echo nl2br(htmlspecialchars($event['highlights'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <?php else: ?>
                <div class="event-section">
                    <h2>Event Not Found</h2>
                    <p>Sorry, we couldn't find the event you're looking for. <a href="explore.php?section=events">Go back to events</a></p>
                </div>
            <?php endif; ?>
        </div>
        </div>
    </section>
    </main>

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

    <script src="../assets/js/navbar.js"></script>
    <script src="../assets/js/script.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
(function() {
    const eventLat = <?php echo isset($event['latitude']) && is_numeric($event['latitude']) ? $event['latitude'] : 'null'; ?>;
    const eventLng = <?php echo isset($event['longitude']) && is_numeric($event['longitude']) ? $event['longitude'] : 'null'; ?>;
    const defaultLat = 7.443;
    const defaultLng = 125.807;
    const mapContainer = document.getElementById('map-container');
    const mapLoading = document.getElementById('map-loading');
    
    if (!mapContainer) {
        console.error('Map container not found');
        return;
    }
    
    try {
        let map = L.map('map-container').setView([defaultLat, defaultLng], 13);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);
        
        if (eventLat !== null && eventLng !== null) {
            L.marker([eventLat, eventLng]).addTo(map)
                .bindPopup('<?php echo addslashes(htmlspecialchars($event['name'] ?? 'Event')); ?>')
                .openPopup();
            map.setView([eventLat, eventLng], 16);
        } else {
            L.marker([defaultLat, defaultLng]).addTo(map)
                .bindPopup('Tagum City<br><?php echo addslashes(htmlspecialchars($event['location'] ?? 'No location')); ?>');
        }
        
        // Hide loading indicator when map is ready
        if (mapLoading) {
            mapLoading.style.display = 'none';
        }
    } catch (error) {
        console.error('Map loading error:', error);
        if (mapLoading) {
            mapLoading.innerHTML = '<div style="font-size: 2rem;">⚠️</div><p style="margin-top: 0.5rem; color: #666;">Map unavailable</p>';
        }
    }
})();
    </script>
</body>
</html>
