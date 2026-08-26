<?php
// Persist DOT vs Local tab across navigation using session.
// This is more reliable than relying only on ?tab=... which can be lost.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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
            <button id="get-location" class="btn-sort location-btn">📍 My Location</button>
        </div>

        <?php
        $dbFile = '../database.db';
        $hotels = [];
        $userLat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
        $userLng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;
        $sortByDistance = $userLat !== null && $userLng !== null;

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
                if ($sortByDistance && !is_null($row['latitude']) && !is_null($row['longitude'])) {
                    $row['distance'] = haversineDistance($userLat, $userLng, $row['latitude'], $row['longitude']);
                } else {
                    $row['distance'] = 0.0;
                }
                $hotels[] = $row;
            }
            $db->close();
        }
        
        if ($sortByDistance) {
            usort($hotels, function ($a, $b) {
                return $a['distance'] <=> $b['distance'];
            });
        }
        ?>

        <h2><?php echo ($tab === 'local') ? 'Locally Certified Hotels' : 'DOT Accredited Hotels'; ?></h2>
        <div class="experiences-grid" id="hotel-grid">
            <?php foreach ($hotels as $hotel): ?>
                <a href="hotel-detail.php?id=<?php echo $hotel['id']; ?>" class="experience-item">
                    <?php if (!empty($hotel['image'])): ?>
                        <?php 
                            $hotelImagePath = $hotel['image'];
                            // Fix image path for Hotel Module subdirectory
                            if (strpos($hotelImagePath, 'images/') === 0) {
                                $hotelImagePath = '../' . $hotelImagePath;
                            } elseif (strpos($hotelImagePath, 'assets/') === 0) {
                                $hotelImagePath = '../' . $hotelImagePath;
                            } elseif (strpos($hotelImagePath, '../../') === 0) {
                                $hotelImagePath = str_replace('../../', '../', $hotelImagePath);
                            }
                        ?>
                        <img src="<?php echo htmlspecialchars($hotelImagePath); ?>" alt="<?php echo htmlspecialchars($hotel['name']); ?>" loading="lazy">
                    <?php endif; ?>
                    <h3><?php echo htmlspecialchars($hotel['name']); ?></h3>
                    <div class="meta">
                        <span class="price">PHP <?php echo htmlspecialchars($hotel['price']); ?></span>
                    </div>
                    <p><?php echo htmlspecialchars(substr($hotel['description'], 0, 100)); ?>...</p>
                    <span class="experience-cta distance"><?php echo number_format($hotel['distance'], 1); ?> km away</span>
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
