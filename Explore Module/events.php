<?php
require_once dirname(__DIR__) . '/includes/database_path.php';
$tab = $_GET['tab'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - Tagum City</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/explore-full-page.css">
    <style>
        .logo-img-small { width: 32px !important; height: 32px !important; }
        body { padding-top: 0; }
        .experiences { padding: 2rem; max-width: 1200px; margin: 0 auto; }
        .experiences h2 { font-size: 2.5rem; color: #1d5a3d; margin-bottom: 2rem; text-align: center; }
        .experiences-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; }
        .experience-item { background-color: #f3f4f6; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); transition: all 0.3s ease; text-decoration: none; color: inherit; display: block; }
        .experience-item:hover { transform: translateY(-8px); box-shadow: 0 12px 24px rgba(29, 90, 61, 0.2); }
        .experience-item img { width: 100%; height: 220px; object-fit: cover; }
        .experience-item h3 { color: #1d5a3d; padding: 1.25rem 1.25rem 0.75rem; font-size: 1.3rem; font-weight: 600; }
        .experience-item .meta { padding: 0 1.25rem 0.5rem; font-size: 0.9rem; color: #6b7280; }
        .experience-item .distance { color: #1d5a3d; font-weight: 600; }
        .experience-item p { color: #6b7280; padding: 0 1.25rem 1rem; line-height: 1.6; }
        .experience-cta { display: block; color: #1d5a3d; font-weight: 600; padding: 0 1.25rem 1.25rem; font-size: 1rem; }
        .experience-item.no-data { text-align: center; padding: 3rem 2rem; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); }
        .controls { max-width: 1200px; margin: 0 auto 2rem; padding: 0 2rem; display: flex; flex-wrap: wrap; gap: 1rem; align-items: center; justify-content: center; }
        .btn-sort { padding: 0.75rem 1rem; background: #1d5a3d; color: white; border: none; border-radius: 25px; cursor: pointer; transition: all 0.3s; font-weight: 500; font-size: 1rem; }
        .home-btn { background: #3b82f6; font-size: 1rem; padding: 0.75rem 1rem; }
        .location-btn { background: #3b82f6; }
        .loading { opacity: 0.5; pointer-events: none; }
        .events-by-month { max-width: 1100px; margin: 0 auto; }
        .month-group { margin-bottom: 1rem; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08); background: #fff; }
        .month-header { width: 100%; text-align: left; background: #fff; border: none; padding: 1.25rem 1.5rem; cursor: pointer; }
        .month-header-top { display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; }
        .month-label { font-size: 1.35rem; font-weight: 700; color: #1d5a3d; }
        .month-count { background: #e8f5ee; color: #1d5a3d; padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.9rem; font-weight: 600; }
        .month-chevron { margin-left: auto; transition: transform 0.3s ease; color: #1d5a3d; }
        .month-group.is-open .month-chevron { transform: rotate(180deg); }
        .month-preview { margin: 0.75rem 0 0; color: #6b7280; line-height: 1.5; }
        .month-events { padding: 0 1.5rem 1.5rem; }
        .event-month-card { height: auto; min-height: 420px; }
        .event-date-badge { display: inline-block; width: fit-content; }
        .event-location { color: #6b7280; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <img src="../assets/images/CityofTagum.png" alt="Tagum City" class="logo-img-small">
                <span class="logo-text">Tagum City</span>
            </div>
            <ul class="nav-menu">
                <li><a href="../index.php#home" class="nav-link">Home</a></li>
                <li><a href="../index.php#explore" class="nav-link">Explore</a></li>
                <li><a href="../index.php#experience" class="nav-link">Experience</a></li>
                <li><a href="../index.php#plan" class="nav-link">Plan</a></li>
                <li><a href="../index.php#contact" class="nav-link">Contact Us</a></li>
            </ul>
        </div>
    </nav>

    <section class="experiences">
        <div class="controls" style="display:none;">
            <button id="get-home" class="btn-sort home-btn"><span class="btn-icon"><svg class="simple-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M12 30L32 14l20 16v20a4 4 0 0 1-4 4H32V36H20v18H16a4 4 0 0 1-4-4V30z" fill="none" stroke="currentColor" stroke-width="4" stroke-linejoin="round"/></svg></span> Home</button>
            <button id="get-location" class="btn-sort location-btn"><span class="btn-icon"><svg class="simple-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M32 8c-9.7 0-17 7.4-17 16.7 0 12.8 17 30.3 17 30.3s17-17.5 17-30.3C49 15.4 41.7 8 32 8zm0 22.9A8.7 8.7 0 1 1 32 13.5a8.7 8.7 0 0 1 0 17.4z" fill="none" stroke="currentColor" stroke-width="4" stroke-linejoin="round"/></svg></span> Scan</button>
        </div>

        <?php
        require_once dirname(__DIR__) . '/includes/events_helpers.php';
        $events = loadEvents(appDatabasePath());
        $userLat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
        $userLng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;
        $sortByDistance = $userLat !== null && $userLng !== null;

        function haversineDistance($lat1, $lon1, $lat2, $lon2) {
            $earthRadius = 6371;
            $dLat = deg2rad($lat2 - $lat1);
            $dLon = deg2rad($lon2 - $lon1);
            $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
            $c = 2 * atan2(sqrt($a), sqrt(1-$a));
            return $earthRadius * $c;
        }

        if ($sortByDistance) {
            foreach ($events as &$event) {
                if (!is_null($event['latitude']) && !is_null($event['longitude'])) {
                    $event['distance'] = haversineDistance($userLat, $userLng, $event['latitude'], $event['longitude']);
                } else {
                    $event['distance'] = 999999;
                }
            }
            unset($event);
            usort($events, function($a, $b) {
                return ($a['distance'] ?? 999999) <=> ($b['distance'] ?? 999999);
            });
        }
        ?>

        <h2>Events by Month</h2>
        <?php if (!empty($events)): ?>
            <?php
            $detailBase = 'event1-detail.php';
            include dirname(__DIR__) . '/includes/events_month_view.php';
            ?>
        <?php else: ?>
            <div class="experience-item no-data">
                <img src="../assets/images/experience-default.jpg" alt="No data" loading="lazy">
                <h3>No Events Found</h3>
                <p>Add events from admin panel.</p>
            </div>
        <?php endif; ?>
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
                btn.textContent = '📍 Scan';
                btn.classList.remove('loading');
            }
        };
    </script>
    <script src="../js/explore-full-page.js"></script>
</body>
</html>

