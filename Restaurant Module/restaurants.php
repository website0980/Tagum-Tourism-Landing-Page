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
<?php 
include '../navbar.php';
require_once '../admin/config.php';
?>

    <section class="experiences">
        <?php
        $userLat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
        $userLng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;
        $sortByDistance = $userLat !== null && $userLng !== null;
        ?>

        <div class="controls" aria-label="Location controls">
            <button id="get-location" class="btn-sort location-btn">📍 My Location</button>
        </div>

        <h2>Restaurants<?php echo $sortByDistance ? ' (Sorted by Distance)' : ''; ?></h2>

        <div class="experiences-grid" id="restaurant-grid">
            <?php
            $dbFile = '../database.db';
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
                    $row['distance'] = 0.0;
                    if ($sortByDistance && !is_null($row['latitude']) && !is_null($row['longitude'])) {
                        $row['distance'] = haversineDistance($userLat, $userLng, $row['latitude'], $row['longitude']);
                    }
                    $restaurants[] = $row;
                }
                $db->close();
                
                if ($sortByDistance) {
                    usort($restaurants, function($a, $b) {
                        return $a['distance'] <=> $b['distance'];
                    });
                }
            }
            foreach ($restaurants as $restaurant): ?>
            <a href="restaurant-detail.php?id=<?php echo $restaurant['id']; ?>" class="experience-item">
                <?php if (!empty($restaurant['image'])): ?>
                    <?php 
                        $restaurantImagePath = $restaurant['image'];
                        // Fix image path if it has ../../ prefix
                        if (strpos($restaurantImagePath, '../../') === 0) {
                            $restaurantImagePath = str_replace('../../', '../', $restaurantImagePath);
                        }
                    ?>
                    <img src="<?php echo htmlspecialchars($restaurantImagePath ?? '', ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($restaurant['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
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
                <span class="experience-cta distance"><?php echo number_format($restaurant['distance'], 1); ?> km away</span>
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
