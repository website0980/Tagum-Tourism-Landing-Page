<?php
require_once dirname(__DIR__) . '/includes/database_path.php';
// Persist DOT vs Local tab across navigation using session.
// This is more reliable than relying only on ?tab=... which can be lost.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/includes/listing_images.php';
$tab = $_GET['tab'] ?? ($_SESSION['last_hotel_tab'] ?? 'dot');
$_SESSION['last_hotel_tab'] = $tab;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Categories - Tagum City</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/explore-full-page.css">
    <link rel="stylesheet" href="../css/hotels.css">
    <link rel="stylesheet" href="../css/accommodation-form.css">
</head>
<body>
<?php include '../navbar.php'; ?>

    <section class="experiences">
        <?php
        $userLatValue = $_GET['lat'] ?? null;
        $userLngValue = $_GET['lng'] ?? null;
        $userLat = is_numeric($userLatValue) && (float)$userLatValue >= -90 && (float)$userLatValue <= 90 ? (float)$userLatValue : null;
        $userLng = is_numeric($userLngValue) && (float)$userLngValue >= -180 && (float)$userLngValue <= 180 ? (float)$userLngValue : null;
        $sortByDistance = $userLat !== null && $userLng !== null;
        ?>
        <?php
        require_once dirname(__DIR__) . '/includes/module_link_banner.php';
        if ($tab === 'dot') {
            renderModuleLinkBanner('accommodation_certification', 'from_hotel_module', 'hotels_dot');
        }
        if ($tab === 'local') {
            renderModuleLinkBanner('accommodation_certification', 'from_hotel_module', 'hotels_local');
        }
        ?>

        <div class="tab-container" style="text-align: center; margin-bottom: 1rem;">
            <a href="?tab=dot" class="btn btn-primary tab-btn <?php echo ($tab === 'dot') ? 'active' : ''; ?>">DOT Accredited</a>
            <a href="?tab=local" class="btn btn-primary tab-btn <?php echo ($tab === 'local') ? 'active' : ''; ?>">Locally Certified</a>
        </div>

        <div class="controls" aria-label="Location controls">
            <button id="get-location" class="btn-sort location-btn" aria-describedby="location-instruction"><span class="btn-icon"><svg class="simple-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M32 8c-9.7 0-17 7.4-17 16.7 0 12.8 17 30.3 17 30.3s17-17.5 17-30.3C49 15.4 41.7 8 32 8zm0 22.9A8.7 8.7 0 1 1 32 13.5a8.7 8.7 0 0 1 0 17.4z" fill="none" stroke="currentColor" stroke-width="4" stroke-linejoin="round"/></svg></span> My Location</button>
            <p class="location-hint" id="location-instruction"><?php echo $sortByDistance ? 'Showing distances from your shared location.' : 'Share your location to calculate distances.'; ?></p>
        </div>

        <?php
        $dbFile = appDatabasePath();
        $hotels = [];

        function haversineDistance($lat1, $lon1, $lat2, $lon2) {
            $earthRadius = 6371;
            $dLat = deg2rad($lat2 - $lat1);
            $dLon = deg2rad($lon2 - $lon1);
            $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            return $earthRadius * $c;
        }

        if (file_exists($dbFile)) {
            $db = new SQLite3($dbFile);
            $stmt = $db->prepare('SELECT * FROM hotel_items WHERE category LIKE ?');
            $searchTerm = ($tab === 'dot') ? 'DOT Accredited%' : (($tab === 'local') ? 'Locally Certified%' : '%');
            $stmt->bindValue(1, $searchTerm, SQLITE3_TEXT);
            $result = $stmt->execute();
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $row['distance'] = null;
                if ($sortByDistance && isset($row['latitude'], $row['longitude']) && is_numeric($row['latitude']) && is_numeric($row['longitude']) && (float)$row['latitude'] >= -90 && (float)$row['latitude'] <= 90 && (float)$row['longitude'] >= -180 && (float)$row['longitude'] <= 180) {
                    $row['distance'] = haversineDistance($userLat, $userLng, $row['latitude'], $row['longitude']);
                }
                $hotels[] = $row;
            }
            $db->close();
        }
        
        if ($sortByDistance) {
            usort($hotels, function ($a, $b) {
                if ($a['distance'] === null || $b['distance'] === null) {
                    return $a['distance'] === $b['distance'] ? 0 : ($a['distance'] === null ? 1 : -1);
                }
                return $a['distance'] <=> $b['distance'];
            });
        }
        ?>

        <h2><?php echo ($tab === 'local') ? 'Locally Certified Hotels' : 'DOT Accredited Hotels'; ?></h2>
        <div class="experiences-grid" id="hotel-grid">
            <?php foreach ($hotels as $hotel): ?>
                <a href="hotel-detail.php?id=<?php echo $hotel['id']; ?>" class="experience-item">
                    <?php if (!empty($hotel['image'])): ?>
                        <img src="<?php echo htmlspecialchars(listingImagePath($hotel['image'])); ?>" alt="<?php echo htmlspecialchars($hotel['name']); ?>" loading="lazy" decoding="async" width="640" height="480">
                    <?php endif; ?>
                    <h3><?php echo htmlspecialchars($hotel['name']); ?></h3>
                    <div class="meta">
                        <span class="price">PHP <?php echo htmlspecialchars($hotel['price']); ?></span>
                    </div>
                    <p><?php echo htmlspecialchars(substr($hotel['description'], 0, 100)); ?>...</p>
                    <?php if ($sortByDistance): ?>
                        <span class="experience-cta distance"><?php echo $hotel['distance'] === null ? 'Location unavailable' : number_format($hotel['distance'], 1) . ' km away'; ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>

            <?php if (empty($hotels)): ?>
                <div class="experience-item no-data">
                    <img src="../assets/images/experience-default.jpg" alt="No data" loading="lazy">
                    <h3>No Hotels Found</h3>
                    <p>Add hotels from admin panel.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <script>
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const tab = this.getAttribute('href').split('tab=')[1];
                window.location.search = `?tab=${tab}`;
            });
        });

        // document.getElementById('get-home').onclick = function () {
        //     window.location.href = '../index.php';
        // };

        document.getElementById('get-location').onclick = function () {
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
                    const url = new URL(window.location);
                    url.searchParams.set('lat', position.coords.latitude);
                    url.searchParams.set('lng', position.coords.longitude);
                    window.history.replaceState({}, '', url);
                    location.reload();
                },
                () => {
                    alert('Location access denied.');
                    resetBtn();
                },
                { timeout: 10000, enableHighAccuracy: true }
            );

            function resetBtn() {
                btn.textContent = '📍 Scan';
                btn.classList.remove('loading');
            }
        };
    </script>
</body>
</html>
