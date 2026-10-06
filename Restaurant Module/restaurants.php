<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurants - Tagum City</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/explore-full-page.css">
    <link rel="stylesheet" href="../css/restaurants.css">
</head>
<body>
<?php require_once dirname(__DIR__) . '/includes/database_path.php';

include '../navbar.php';
require_once '../admin/config.php';
require_once dirname(__DIR__) . '/includes/listing_images.php';
?>

    <section class="experiences">
        <?php
        $userLatValue = $_GET['lat'] ?? null;
        $userLngValue = $_GET['lng'] ?? null;
        $userLat = is_numeric($userLatValue) && (float)$userLatValue >= -90 && (float)$userLatValue <= 90 ? (float)$userLatValue : null;
        $userLng = is_numeric($userLngValue) && (float)$userLngValue >= -180 && (float)$userLngValue <= 180 ? (float)$userLngValue : null;
        $sortByDistance = $userLat !== null && $userLng !== null;
        ?>

        <div class="controls" aria-label="Location controls">
            <button id="get-location" class="btn-sort location-btn" aria-describedby="location-instruction">📍 My Location</button>
            <p class="location-hint" id="location-instruction"><?php echo $sortByDistance ? 'Showing distances from your shared location.' : 'Share your location to calculate distances.'; ?></p>
        </div>

        <h2>Restaurants<?php echo $sortByDistance ? ' (Sorted by Distance)' : ''; ?></h2>

        <div class="experiences-grid" id="restaurant-grid">
            <?php
            $dbFile = appDatabasePath();
            $restaurants = [];
            
            function haversineDistance($lat1, $lon1, $lat2, $lon2) {
                $earthRadius = 6371; // km
                $dLat = deg2rad($lat2 - $lat1);
                $dLon = deg2rad($lon2 - $lon1);
                $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
                $c = 2 * atan2(sqrt($a), sqrt(1-$a));
                return $earthRadius * $c;
            }
            
            if (file_exists($dbFile)) {
                $db = new SQLite3($dbFile);
                $result = $db->query('SELECT * FROM restaurant_items ORDER BY id DESC');
                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                    $row['distance'] = null;
                    if ($sortByDistance && isset($row['latitude'], $row['longitude']) && is_numeric($row['latitude']) && is_numeric($row['longitude']) && (float)$row['latitude'] >= -90 && (float)$row['latitude'] <= 90 && (float)$row['longitude'] >= -180 && (float)$row['longitude'] <= 180) {
                        $row['distance'] = haversineDistance($userLat, $userLng, $row['latitude'], $row['longitude']);
                    }
                    $restaurants[] = $row;
                }
                $db->close();
                
                if ($sortByDistance) {
                    usort($restaurants, function($a, $b) {
                        if ($a['distance'] === null || $b['distance'] === null) {
                            return $a['distance'] === $b['distance'] ? 0 : ($a['distance'] === null ? 1 : -1);
                        }
                        return $a['distance'] <=> $b['distance'];
                    });
                }
            }
            foreach ($restaurants as $restaurant): ?>
            <a href="restaurant-detail.php?id=<?php echo $restaurant['id']; ?>" class="experience-item">
                <?php if (!empty($restaurant['image'])): ?>
                    <img src="<?php echo htmlspecialchars(listingImagePath($restaurant['image'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($restaurant['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" loading="lazy" decoding="async" width="640" height="480">
                <?php else: ?>
                    <img src="../assets/images/experience-default.jpg" alt="<?php echo htmlspecialchars($restaurant['category'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
                <?php endif; ?>
                <h3><?php echo htmlspecialchars($restaurant['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars(substr($restaurant['description'] ?? '', 0, 100)); ?></p>
                <?php 
                $hasTimeSlots = !empty($restaurant['time_slots']);
                $hasRegularHours = !empty($restaurant['opening_time']) || !empty($restaurant['closing_time']);
                
                if ($hasTimeSlots || $hasRegularHours): 
                ?>
                    <div class="restaurant-hours">
                        <span class="hours-label">🕐 Hours:</span>
                        <div class="hours-time">
                            <?php if ($hasRegularHours): ?>
                                <span class="regular-hours"><?php echo formatTime24to12($restaurant['opening_time'] ?? ''); ?> to <?php echo formatTime24to12($restaurant['closing_time'] ?? ''); ?></span>
                            <?php endif; ?>
                            <?php if ($hasTimeSlots): 
                                $slots = explode(',', $restaurant['time_slots']);
                                foreach ($slots as $slot): 
                                    $slot = trim($slot);
                                    if (!empty($slot)): 
                            ?>
                                        <span class="time-slot"><?php echo formatTime24to12($slot); ?></span>
                            <?php 
                                    endif;
                                endforeach; 
                            endif; 
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if ($sortByDistance): ?>
                    <span class="experience-cta distance"><?php echo $restaurant['distance'] === null ? 'Location unavailable' : number_format($restaurant['distance'], 1) . ' km away'; ?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
            <?php if (empty($restaurants)): ?>
            <div class="experience-item no-data">
                <img src="../assets/images/experience-default.jpg" alt="No data" loading="lazy">
                <h3>No Restaurants Found</h3>
                <p>Add restaurants from admin panel.</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <script>
        // get-home button hidden
        // document.getElementById('get-home').onclick = function() {
        //     window.location.href = '../index.php';
        // };

        document.getElementById('get-location').onclick = function() {
            const btn = this;
            btn.textContent = '📍 Getting Location...';
            btn.classList.add('loading');
            
            if (!navigator.geolocation) {
                alert('Geolocation not supported.');
                resetBtn();
                return;
            }
            
            navigator.geolocation.getCurrentPosition(
                position => {
                    location.href = `?lat=${position.coords.latitude}&lng=${position.coords.longitude}`;
                },
                error => {
                    let msg = 'Location access denied.';
                    if (error.code === error.TIMEOUT) msg = 'Location timeout.';
                    alert(msg);
                    resetBtn();
                },
                { timeout: 10000, enableHighAccuracy: true }
            );
            
            function resetBtn() {
                btn.textContent = '📍 My Location';
                btn.classList.remove('loading');
            }
        };
    </script>
</body>
</html>
