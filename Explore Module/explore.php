<?php
$requestedSection = isset($_GET['section']) && in_array($_GET['section'], ['events', 'festivals'], true)
    ? $_GET['section']
    : 'events';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Know More about Tagum City</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/explore-full-page.css">
    <link rel="stylesheet" href="../css/mobile-navbar.css">
    <script src="../js/navbar.js"></script>
</head>
<body>
<?php include '../navbar.php'; ?>

    <!-- Explore Content Section -->
    <section class="explore-full-page">
        <div class="explore-container">
            <!-- Section Tabs -->
            <div class="section-tabs">
                <button class="tab-btn <?php echo $requestedSection === 'events' ? 'active' : ''; ?>" data-section="events">Events</button>
                <button class="tab-btn <?php echo $requestedSection === 'festivals' ? 'active' : ''; ?>" data-section="festivals">Festivals</button>
            </div>

            <!-- Events Content -->
            <div class="section-content <?php echo $requestedSection === 'events' ? 'active' : ''; ?>" id="events">
                <h1>Events</h1>
                <div class="content-text">
                    <h2>Discover Local Events</h2>
                    <div class="calendar-view-link">
                        <a href="events-calendar.php" class="calendar-view-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            View Calendar
                        </a>
                    </div>
                    <p>Browse events by month. Click a month to see what's happening each group shows the event names at a glance, then expands to full details.</p>
                </div>

                <?php
                require_once dirname(__DIR__) . '/includes/events_helpers.php';
                $events = loadEvents(dirname(__DIR__) . '/database.db');
                ?>

                <div class="events-section-wrapper">
                    <?php if (!empty($events)): ?>
                        <?php include dirname(__DIR__) . '/includes/events_month_view.php'; ?>
                    <?php else: ?>
                        <div class="content-text">
                            <h3>Featured Events:</h3>
                            <ul>
                                <li><strong>Araw ng Tagum Festival</strong> - Annual celebration of the city's founding</li>
                                <li><strong>Davao Food Festival</strong> - Showcase of local culinary traditions</li>
                                <li><strong>Tagum Sports Fest</strong> - Community sports and recreation events</li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Festivals Content -->
            <div class="section-content <?php echo $requestedSection === 'festivals' ? 'active' : ''; ?>" id="festivals">
                <h1>Festivals</h1>
                <div class="content-text">
                    <h2>Celebrate Local Culture</h2>
                    <div class="calendar-view-link">
                        <a href="events-calendar.php" class="calendar-view-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                            View Calendar
                        </a>
                    </div>
                    <p>Tagum City comes alive with vibrant festivals throughout the year, showcasing the rich traditions, music, dance, and culinary heritage of the region. Experience the warmth and hospitality of the local community.</p>
                    
                    <?php
                    $dbFile = dirname(__DIR__) . '/database.db';
                    $festivals = [];
                    $events = loadEvents(dirname(__DIR__) . '/database.db');
                    if (file_exists($dbFile)) {
                        try {
                            $db = new SQLite3($dbFile);
                            $result = $db->query('SELECT * FROM festivals ORDER BY id DESC');
                            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                                $festivals[] = $row;
                            }
                            $db->close();
                        } catch (Exception $e) {
                            // Fallback to JSON if database fails
                            $festivals = json_decode(file_get_contents('../data/festivals.json'), true) ?? [];
                        }
                    } else {
                        // Fallback to JSON if database doesn't exist
                        $festivals = json_decode(file_get_contents('../data/festivals.json'), true) ?? [];
                    }

                    $festivals = sortFestivalsByRelatedEvents($festivals, $events);

                    // ---- Group/sort festivals by “kind” (keyword-based, since DB currently has no explicit type/category column) ----
                    // Purpose: user can immediately see WHAT kind of festival it is (music, food, community, etc.).
                    $festivalTypeMap = [
                        'Music & Singing' => ['music', 'hymig', 'handog', 'sing', 'singing', 'singer', 'song', 'opm', 'band', 'musik', 'musical', 'harmony', 'chant'],
                        'Community / Barangay' => ['barangay', 'community', 'brgy', 'local residents', 'residents', 'neighborhood', 'barangay-based', 'barangay-based'],
                        'Food & Culture' => ['food', 'sinigang', 'kadayawan', 'culture', 'cultural', 'tradition', 'heritage', 'dance', 'cuisine'],
                        'Sports & Recreation' => ['sports', 'recreation', 'run', 'marathon', 'athlete', 'fitness', 'game', 'tournament'],
                        'Arts & Dance' => ['dance', 'art', 'arts', 'drama', 'theater', 'performance'],
                    ];

                    $normalizeFestivalText = function ($text) {
                        $text = strtolower(trim((string)$text));
                        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
                        $text = preg_replace('/\s+/', ' ', $text);
                        return trim($text);
                    };

                    $getFestivalType = function (array $festival) use ($festivalTypeMap, $normalizeFestivalText) {
                        $haystack = $festival['name'] . ' ' . ($festival['description'] ?? '');
                        $haystack = $normalizeFestivalText($haystack);

                        foreach ($festivalTypeMap as $type => $keywords) {
                            foreach ($keywords as $kw) {
                                if ($kw !== '' && strpos($haystack, strtolower($kw)) !== false) {
                                    return $type;
                                }
                            }
                        }

                        return 'Other Festivals';
                    };

                    $groupedFestivals = [];
                    foreach ($festivals as $festival) {
                        $type = $getFestivalType($festival);
                        if (!isset($groupedFestivals[$type])) {
                            $groupedFestivals[$type] = [];
                        }
                        $groupedFestivals[$type][] = $festival;
                    }

                    // Sort festivals within each type: upcoming first (if date exists), then earliest date, then name.
                    foreach ($groupedFestivals as $type => &$items) {
                        usort($items, function ($a, $b) {
                            $dateA = !empty($a['date']) ? strtotime($a['date']) : null;
                            $dateB = !empty($b['date']) ? strtotime($b['date']) : null;
                            $tsNow = strtotime(date('Y-m-d'));

                            $isFutureA = $dateA !== null && $dateA >= $tsNow;
                            $isFutureB = $dateB !== null && $dateB >= $tsNow;

                            if ($isFutureA !== $isFutureB) {
                                return ($isFutureA ? -1 : 1);
                            }
                            if ($dateA !== null && $dateB !== null && $dateA !== $dateB) {
                                return $dateA <=> $dateB;
                            }
                            return strcmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
                        });
                        $items = $items;
                    }
                    unset($items);

                    // Keep a consistent type order, but only show types that have festivals.
                    $typeDisplayOrder = array_keys($festivalTypeMap);
                    $typeDisplayOrder[] = 'Other Festivals';
                    $orderedGrouped = [];
                    foreach ($typeDisplayOrder as $type) {
                        if (!empty($groupedFestivals[$type])) {
                            $orderedGrouped[$type] = $groupedFestivals[$type];
                        }
                    }
                    // Append any unknown types
                    foreach ($groupedFestivals as $type => $items) {
                        if (!isset($orderedGrouped[$type])) {
                            $orderedGrouped[$type] = $items;
                        }
                    }
                    ?>

                    <style>
                        .festival-kind-block{margin-top:2rem;}
                        .festival-kind-header{
                            display:flex;align-items:center;gap:.75rem;
                            margin:1.75rem 0 1rem;
                            color:#1d5a3d;
                        }
                        .festival-kind-header .pill{
                            background:#e8f5ee;
                            color:#1d5a3d;
                            padding:.35rem .8rem;
                            border-radius:999px;
                            font-weight:700;
                            font-size:.95rem;
                        }
                        .festival-kind-header h3{margin:0;font-size:1.35rem;}
                    </style>

                    <?php if (!empty($festivals)): ?>
                        <?php
                        // Build a month-like grouping for festivals so the UI matches Events layout.
                        // We use festival['date'] when available, otherwise bucket as “undated”.
                        $festivalsByMonth = [];
                        foreach ($festivals as $festival) {
                            $dateRaw = trim((string)($festival['date'] ?? ''));
                            if ($dateRaw === '') {
                                $monthKey = 'undated';
                            } else {
                                $ts = strtotime($dateRaw);
                                $monthKey = $ts !== false ? date('Y-m', $ts) : 'undated';
                            }

                            if (!isset($festivalsByMonth[$monthKey])) {
                                $label = $monthKey === 'undated'
                                    ? 'Date To Be Announced'
                                    : date('F Y', strtotime($monthKey . '-01'));
                                $festivalsByMonth[$monthKey] = [
                                    'label' => $label,
                                    'events' => [],
                                    'sort' => $monthKey === 'undated' ? '9999-12' : $monthKey,
                                ];
                            }
                            $festivalsByMonth[$monthKey]['events'][] = $festival;
                        }
                        uasort($festivalsByMonth, function($a,$b){ return ($a['sort'] ?? '') <=> ($b['sort'] ?? ''); });
                        $currentMonth = date('Y-m');
                        $openMonthKey = isset($festivalsByMonth[$currentMonth]) ? $currentMonth : array_key_first($festivalsByMonth);
                        ?>

                        <div class="events-by-month">
                            <?php foreach ($festivalsByMonth as $monthKey => $monthData): ?>
                                <?php
                                $isOpen = ($monthKey === $openMonthKey);
                                $eventNames = array_map(function($f){ return $f['name'] ?? 'Untitled Festival'; }, $monthData['events']);
                                $preview = implode(', ', $eventNames);
                                $count = count($monthData['events']);
                                ?>
                                <div class="month-group<?php echo $isOpen ? ' is-open' : ''; ?>">
                                    <button
                                        type="button"
                                        class="month-header"
                                        aria-expanded="<?php echo $isOpen ? 'true' : 'false'; ?>"
                                        data-month="<?php echo htmlspecialchars($monthKey); ?>"
                                    >
                                        <div class="month-header-top">
                                            <span class="month-label"><?php echo htmlspecialchars($monthData['label']); ?></span>
                                            <span class="month-count"><?php echo $count; ?> festival<?php echo $count === 1 ? '' : 's'; ?></span>
                                            <span class="month-chevron" aria-hidden="true">▼</span>
                                        </div>
                                        <p class="month-preview"><?php echo htmlspecialchars($preview); ?></p>
                                    </button>

                                    <div class="month-events"<?php echo $isOpen ? '' : ' hidden'; ?> >
                                        <div class="cuisine-grid events-month-grid">
                                            <?php foreach ($monthData['events'] as $festival): ?>
                                                <?php
                                                $imagePath = $festival['image'] ?? '';
                                                // Fix image path for Explore Module subdirectory
                                                if (strpos($imagePath, 'images/') === 0) {
                                                    $imagePath = '../' . $imagePath;
                                                } elseif (strpos($imagePath, 'assets/') === 0) {
                                                    $imagePath = '../' . $imagePath;
                                                }
                                                $dateShow = '';
                                                if (!empty($festival['date'])) {
                                                    $ts = strtotime($festival['date']);
                                                    $dateShow = $ts !== false ? date('F j, Y', $ts) : $festival['date'];
                                                }
                                                ?>
                                                <div class="cuisine-category event-month-card">
                                                    <?php if (!empty($imagePath)): ?>
                                                        <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($festival['name']); ?>" class="category-image" loading="lazy" onerror="this.style.display='none'">
                                                    <?php endif; ?>
                                                    <h3><?php echo htmlspecialchars($festival['name']); ?></h3>
                                                    <?php if ($dateShow !== ''): ?>
                                                        <p class="item-count event-date-badge">📅 <?php echo htmlspecialchars($dateShow); ?></p>
                                                    <?php endif; ?>
                                                    <?php if (!empty($festival['description'])): ?>
                                                        <p style="color:#6b7280; padding:0 1.25rem 1rem; line-height:1.6;">“<?php echo htmlspecialchars(substr($festival['description'],0,110)); ?>...”</p>
                                                    <?php endif; ?>
                                                    <a href="festival-detail.php?id=<?php echo (int)$festival['id']; ?>" class="read-more-btn btn btn-primary">Read More</a>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
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

<script src="../js/explore-full-page.js"></script>
    <script src="../js/explore-cuisine-landscape.js"></script>
</body>
</html>
