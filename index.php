<?php
require_once __DIR__ . '/includes/database_path.php';
if (!function_exists('loadHomepageSettings')) {
    require_once __DIR__ . '/admin/config.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tagum Tourism</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/mobile-navbar.css">
<?php include 'navbar.php'; ?>
</head>
<body>

    <?php
    $eventMap = [];
    $carouselImageSets = [];
    $homepageSettings = loadHomepageSettings();
    $defaultMonth = (int) ($homepageSettings['default_month'] ?? 1);
    if ($defaultMonth < 1 || $defaultMonth > 12) {
        $defaultMonth = 1;
    }
    $dbFile = appDatabasePath();
    if (file_exists($dbFile)) {
        try {
            $db = new SQLite3($dbFile);
            $eventsResult = $db->query('SELECT name, event_date FROM events WHERE TRIM(COALESCE(event_date, "")) != "" ORDER BY event_date ASC');
            while ($eventRow = $eventsResult->fetchArray(SQLITE3_ASSOC)) {
                $eventDate = trim((string) ($eventRow['event_date'] ?? ''));
                $ts = strtotime($eventDate);
                if ($ts === false) {
                    continue;
                }
                $monthNum = (int) date('n', $ts);
                $eventMap[$monthNum][] = [
                    'name' => trim((string) ($eventRow['name'] ?? '')),
                    'date' => $eventDate,
                ];
            }
            $db->close();
        } catch (Exception $e) {
            $eventMap = [];
        }
    }

    require_once __DIR__ . '/hero_month_profiles.php';
    $heroPackages = [];
    if (function_exists('loadHeroPackages')) {
        $heroPackages = loadHeroPackages();
    }
    $heroMonthProfiles = buildHeroMonthProfilesFromPackages($heroPackages, $homepageSettings);
    $activeHeroProfile = $heroMonthProfiles[(string) $defaultMonth] ?? heroMonthDefaultProfile($homepageSettings);
    $selectedImages = $activeHeroProfile['images'] ?? [];
    $featuredImage = htmlspecialchars($selectedImages[0] ?? 'images/Background for slide 1.jpg', ENT_QUOTES, 'UTF-8');
    $secondaryImage = htmlspecialchars($selectedImages[1] ?? 'images/Background for slide 2 .jpg', ENT_QUOTES, 'UTF-8');
    $tertiaryImage = htmlspecialchars($selectedImages[2] ?? 'images/Background for slide 3.jpg', ENT_QUOTES, 'UTF-8');
    foreach (range(1, 12) as $packageMonth) {
        $packageProfile = $heroMonthProfiles[(string) $packageMonth] ?? null;
        $carouselImageSets[$packageMonth] = $packageProfile['images'] ?? [];
    }
    if ($defaultMonth < 1 || $defaultMonth > 12) {
        $defaultMonth = 1;
    }
    $heroTheme = in_array((string) ($activeHeroProfile['theme'] ?? 'theme-soft'), ['theme-rich', 'theme-soft', 'theme-chinese-new-year', 'theme-musikahan', 'theme-musikahan-blue', 'theme-araw-green'], true)
        ? (string) $activeHeroProfile['theme']
        : 'theme-soft';
    $isChineseNewYearTheme = $heroTheme === 'theme-chinese-new-year';
    $isMusikahanTheme = $heroTheme === 'theme-musikahan' || $heroTheme === 'theme-musikahan-blue';
    $heroTitleLines = $activeHeroProfile['titleLines'] ?? ['FLORES', 'DE MAYO'];
    $heroScript = trim((string) ($activeHeroProfile['script'] ?? 'Faith in Bloom.'));
    $heroDescription = trim((string) ($activeHeroProfile['description'] ?? ''));
    $heroCta = trim((string) ($activeHeroProfile['cta'] ?? 'EXPLORE FESTIVAL'));
    $heroMeta1 = trim((string) ($activeHeroProfile['meta1'] ?? 'Tradition'));
    $heroMeta2 = trim((string) ($activeHeroProfile['meta2'] ?? 'Fortune'));
    $heroMeta3 = trim((string) ($activeHeroProfile['meta3'] ?? 'Unity'));
    $heroMonthEventLine = trim((string) ($activeHeroProfile['monthEvent'] ?? ''));
    $heroFeatures = $activeHeroProfile['features'] ?? [];
    $heroMonthLabel = strtoupper(date('M', mktime(0, 0, 0, $defaultMonth, 1, 2026)));
    ?>
    <section class="festival-hero <?php echo htmlspecialchars($heroTheme); ?>" id="home">
        <div class="hero-shell">
            <div class="hero-decor hero-decor-cny" aria-hidden="true">
                <div class="cny-lantern" aria-hidden="true">
                    <span class="cny-lantern-cord"></span>
                    <span class="cny-lantern-cap"></span>
                    <span class="cny-lantern-body"></span>
                    <span class="cny-lantern-base"></span>
                    <span class="cny-lantern-tassel"></span>
                </div>
                <div class="cny-lantern cny-lantern-left" aria-hidden="true">
                    <span class="cny-lantern-cord"></span>
                    <span class="cny-lantern-cap"></span>
                    <span class="cny-lantern-body"></span>
                    <span class="cny-lantern-base"></span>
                    <span class="cny-lantern-tassel"></span>
                </div>
                <div class="cny-art-panel" aria-hidden="true">
                    <span class="cny-art-line"><i></i><i></i><i></i><i></i></span>
                    <span class="cny-lantern cny-lantern-side">
                        <span class="cny-lantern-cord"></span>
                        <span class="cny-lantern-cap"></span>
                        <span class="cny-lantern-body"></span>
                        <span class="cny-lantern-base"></span>
                        <span class="cny-lantern-tassel"></span>
                    </span>
                </div>
            </div>
            <div class="hero-decor hero-decor-music" aria-hidden="true">
                <div class="music-note" aria-hidden="true">♪</div>
                <div class="music-note" aria-hidden="true">♫</div>
                <div class="music-note" aria-hidden="true">♩</div>
                <div class="music-note" aria-hidden="true">♬</div>
            </div>
            <div class="hero-inner">
                <div class="hero-copy">
                    <div class="hero-badge" id="hero-badge"><?php echo htmlspecialchars($heroMonthLabel); ?></div>
                    <h1 id="hero-title">
                        <?php foreach ($heroTitleLines as $lineIndex => $line): ?>
                            <?php echo htmlspecialchars($line); ?><?php if ($lineIndex !== count($heroTitleLines) - 1) { echo '<br>'; } ?>
                        <?php endforeach; ?>
                    </h1>
                    <p class="hero-script" id="hero-script"><?php echo htmlspecialchars($heroScript); ?></p>
                    <div class="hero-meta" id="hero-meta" aria-label="Festival values">
                        <span data-meta="1"><?php echo htmlspecialchars($heroMeta1); ?></span>
                        <span class="meta-separator">•</span>
                        <span data-meta="2"><?php echo htmlspecialchars($heroMeta2); ?></span>
                        <span class="meta-separator">•</span>
                        <span data-meta="3"><?php echo htmlspecialchars($heroMeta3); ?></span>
                    </div>
                    <p class="hero-description" id="hero-description"><?php echo htmlspecialchars($heroDescription); ?></p>
                    <p class="hero-month-event" id="hero-month-event"<?php echo $heroMonthEventLine === '' ? ' hidden' : ''; ?>><?php echo htmlspecialchars($heroMonthEventLine); ?></p>
                    <a href="#explore" class="hero-cta" id="hero-cta"><?php echo htmlspecialchars($heroCta); ?> <span>→</span></a>
                </div>

                <div class="hero-visual" aria-label="Festival imagery">
                    <div class="hero-decor-music-staff" aria-hidden="true"></div>
                    <div class="hero-main-image">
                        <img src="<?php echo $featuredImage; ?>" alt="Flores de Mayo festival celebration" loading="eager">
                    </div>
                    <div class="hero-side-stack">
                        <div class="hero-side-card hero-side-card-top">
                            <img src="<?php echo $secondaryImage; ?>" alt="Festival participant portrait" loading="lazy">
                        </div>
                        <div class="hero-side-card hero-side-card-bottom">
                            <img src="<?php echo $tertiaryImage; ?>" alt="Church and community scene" loading="lazy">
                        </div>
                    </div>
                </div>
            </div>

            <div class="hero-features" id="hero-features">
                <?php foreach ($heroFeatures as $featureIndex => $feature): ?>
                    <?php
                    $iconClass = $featureIndex === 0 ? 'feature-icon-pink' : ($featureIndex === 1 ? 'feature-icon-mint' : 'feature-icon-gold');
                    ?>
                    <div class="feature-item">
                        <div class="feature-icon <?php echo $iconClass; ?>">
                            <span class="feature-icon-glyph" aria-hidden="true"><?php echo htmlspecialchars((string) ($feature['icon'] ?? '✿')); ?></span>
                        </div>
                        <div class="feature-copy">
                            <h3 class="feature-title"><?php echo htmlspecialchars((string) ($feature['title'] ?? '')); ?></h3>
                            <p class="feature-text"><?php echo htmlspecialchars((string) ($feature['text'] ?? '')); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="hero-month-strip" aria-label="Festival month navigation">
                <button class="month-nav-btn" type="button" data-nav="prev" aria-label="Previous month">←</button>
                <div class="month-list">
                    <?php foreach (range(1, 12) as $monthNo): ?>
                        <?php
                        $monthEvent = $eventMap[$monthNo][0] ?? ['name' => 'Community festival celebrations', 'date' => ''];
                        $monthShort = strtoupper(date('M', mktime(0, 0, 0, $monthNo, 1, 2026)));
                        ?>
                        <button
                            type="button"
                            class="month-item<?php echo $monthNo === $defaultMonth ? ' month-active' : ''; ?>"
                            data-month="<?php echo (int) $monthNo; ?>"
                            data-event-name="<?php echo htmlspecialchars($monthEvent['name'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-event-date="<?php echo htmlspecialchars($monthEvent['date'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-image-main="<?php echo htmlspecialchars($carouselImageSets[$monthNo][0] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            data-image-top="<?php echo htmlspecialchars($carouselImageSets[$monthNo][1] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            data-image-bottom="<?php echo htmlspecialchars($carouselImageSets[$monthNo][2] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            aria-label="View <?php echo htmlspecialchars($monthShort, ENT_QUOTES, 'UTF-8'); ?> hero package"
                        ><?php echo htmlspecialchars($monthShort); ?></button>
                    <?php endforeach; ?>
                </div>
                <button class="month-nav-btn" type="button" data-nav="next" aria-label="Next month">→</button>
            </div>
        </div>
    </section>
    <script type="application/json" id="hero-month-profiles"><?php echo json_encode($heroMonthProfiles, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>

    <!-- Explore Section -->
    <section class="explore" id="explore">
        <h2>Know More about Tagum City</h2>
        <div class="explore-grid">

            <!-- Event Card -->
            <div class="explore-card">
                <div class="card-image simple-icon-wrap">
                    <svg class="simple-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <circle cx="23" cy="22" r="8" fill="none" stroke="#39d96a" stroke-width="4"/>
                        <circle cx="41" cy="24" r="7" fill="none" stroke="#39d96a" stroke-width="4"/>
                        <path d="M12 46c1.5-7 7.2-10.8 15-10.8S50.5 39 52 46" fill="none" stroke="#39d96a" stroke-width="4" stroke-linecap="round"/>
                    </svg>
                </div>
                <h3>Events</h3>
                <p>Check every events happening in Tagum City</p>
                <a href="Explore Module/events-calendar.php" class="card-link">Learn More →</a>
            </div>
            
            <!-- Festivals Card -->
            <div class="explore-card">
                <div class="card-image simple-icon-wrap">
                    <svg class="simple-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M32 9l4.6 10.8L48 25l-11.4 5.2L32 41l-4.6-10.8L16 25l11.4-5.2L32 9z" fill="none" stroke="#39d96a" stroke-width="4" stroke-linejoin="round"/>
                        <path d="M15 42l2.5-6 6 2.5-2.5 6-6-2.5zm26 0l2.5-6 6 2.5-2.5 6-6-2.5z" fill="none" stroke="#39d96a" stroke-width="4" stroke-linecap="round"/>
                    </svg>
                </div>
                <h3>Festivals</h3>
                <p>Join vibrant local festivals showcasing music, dance, and culture.</p>
                <a href="Explore Module/explore.php?section=festivals" class="card-link">Learn More →</a>
            </div>
        </div>
    </section>

    <!-- Experiences Section -->
    <section class="experiences" id="experiences">
        <h2>Unforgettable Experiences</h2>
        <div class="experiences-grid">
            <?php
            // Load experiences from database with JSON fallback
            $dbFile = appDatabasePath();
            $experiences = [];
            
            if (file_exists($dbFile)) {
                try {
                    $db = new SQLite3($dbFile);
                    $result = $db->query('SELECT * FROM experiences ORDER BY id DESC');
                    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                        $experiences[] = $row;
                    }
                    $db->close();
                } catch (Exception $e) {
                    // Fallback to JSON if database fails
                    $experiences = json_decode(file_get_contents('data/experiences.json'), true) ?? [];
                }
            } else {
                // Fallback to JSON if database doesn't exist
                $experiences = json_decode(file_get_contents('data/experiences.json'), true) ?? [];
            }
            
            // Sort experiences: featured ones first
            usort($experiences, function($a, $b) {
                $aFeatured = isset($a['featured']) && $a['featured'] === true;
                $bFeatured = isset($b['featured']) && $b['featured'] === true;
                if ($aFeatured === $bFeatured) {
                    return 0;
                }
                return $aFeatured ? -1 : 1;
            });
            
            // Display experiences (limit to 8 for the grid)
            $displayExperiences = array_slice($experiences, 0, 8);
            
            foreach ($displayExperiences as $exp):
                $expType = $exp['type'] ?? 'experience';
            ?>
                <a href="Experience Module/experience.php?id=<?php echo $exp['id']; ?>" class="experience-item">
                    <?php if (!empty($exp['image'])): ?>
                        <img src="<?php echo htmlspecialchars($exp['image']); ?>" alt="<?php echo htmlspecialchars($exp['name']); ?>" loading="lazy">
                    <?php else: ?>
                        <img src="assets/images/experience-default.jpg" alt="<?php echo htmlspecialchars($exp['name']); ?>" loading="lazy">
                    <?php endif; ?>
                    <h3><?php echo isset($exp['featured']) && $exp['featured'] === true ? '<span class="mini-star-wrap"><svg class="mini-star-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M32 9l7 15 17 2-12 11 3 17-15-8-15 8 3-17L8 26l17-2L32 9z" fill="none" stroke="#39d96a" stroke-width="4" stroke-linejoin="round"/></svg></span>' : ''; ?><?php echo htmlspecialchars($exp['name']); ?></h3>
                    <p><?php echo htmlspecialchars($exp['description']); ?></p>
                    <span class="experience-cta">View Details →</span>
                </a>
            <?php endforeach; ?>
            
            <?php if (empty($displayExperiences)): ?>
            <a href="Experience Module/experience.php?type=river-tours" class="experience-item">
                <img src="assets/images/experience-1.jpg" alt="River Tours" loading="lazy">
                <h3>River Tours</h3>
                <p>Navigate pristine waterways with expert guides.</p>
                <span class="experience-cta">View Details →</span>
            </a>
            <a href="Experience Module/experience.php?type=mountain-hiking" class="experience-item">
                <img src="assets/images/experience-2.jpg" alt="Hiking" loading="lazy">
                <h3>Mountain Hiking</h3>
                <p>Trek through lush forests and scenic trails.</p>
                <span class="experience-cta">View Details →</span>
            </a>
            <a href="Experience Module/experience.php?type=cultural-events" class="experience-item">
                <img src="assets/images/experience-3.jpg" alt="Cultural Events" loading="lazy">
                <h3>Cultural Events</h3>
                <p>Participate in local festivals and celebrations.</p>
                <span class="experience-cta">View Details →</span>
            </a>
            <a href="Experience Module/experience.php?type=food-tours" class="experience-item">
                <img src="assets/images/experience-4.jpg" alt="Food Tours" loading="lazy">
                <h3>Food Tours</h3>
                <p>Taste the flavors of authentic local cuisine.</p>
                <span class="experience-cta">View Details →</span>
            </a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Featured Destinations Section -->
    <section class="featured" id="featured">
        <h2>Featured Destinations</h2>
        <p class="section-subtitle">Discover our top-picked attractions and experiences</p>
        <div class="featured-grid">
<?php
            $dbFile = appDatabasePath();
            $featuredDestinations = [];
            if (file_exists($dbFile)) {
                try {
                    $db = new SQLite3($dbFile);
                    $result = $db->query('SELECT * FROM destinations WHERE featured = 1 ORDER BY id DESC');
                    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                        $featuredDestinations[] = $row;
                    }
                    $db->close();
                } catch (Exception $e) {
                    // Fallback
                }
            }
            
            // Get type icons
            $typeIcons = [
                'Natural Wonder' => '🏞️',
                'Adventure' => '⛰️',
                'Museum' => '🏛️',
                'Religious' => '⛪',
                'Festival' => '🎉'
            ];
            
            foreach ($featuredDestinations as $dest):
                $linkName = strtolower(str_replace(' ', '-', $dest['name']));
                $locationIcon = '<svg class="mini-location-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M32 4C18.3 4 8 14.2 8 27.3c0 17.5 21.2 30.8 24 34.3 2.8-3.5 24-16.8 24-34.3C56 14.2 45.7 4 32 4zm0 15.7a12 12 0 1 1 0 24 12 12 0 0 1 0-24z" fill="#ffffff" stroke="#39d96a" stroke-width="4" stroke-linejoin="round"/><circle cx="32" cy="28.5" r="8.4" fill="#ffffff" stroke="#39d96a" stroke-width="4"/></svg>';
            ?>
                <a href="Plan Module/destination.php?destination=<?php echo $linkName; ?>" class="featured-card">
                    <div class="featured-icon"><?php echo $locationIcon; ?></div>
                    <h3><?php echo htmlspecialchars($dest['name']); ?></h3>
                    <p><?php echo htmlspecialchars(substr($dest['description'], 0, 100)) . (strlen($dest['description']) > 100 ? '...' : ''); ?></p>
                    <span class="featured-cta">View Details →</span>
                </a>
            <?php endforeach; ?>
            
    <?php if (empty($featuredDestinations)): ?>
                <p style="text-align:center;grid-column:1/-1;">No featured destinations yet.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Cultural Heritage Section -->
    <section class="cultural-heritage-preview" id="cultural-heritage">
        <h2>Cultural Heritage</h2>
        <p class="section-subtitle">Explore the historic sites and heritage that tell the stories of our past</p>
        <div class="cultural-heritage-grid">
            <?php
            $heritageData = [];
            if (file_exists('Cultural Heritage Module/cultural-heritage.json')) {
                $heritageData = json_decode(file_get_contents('Cultural Heritage Module/cultural-heritage.json'), true) ?? [];
            }
            
            // Display first 4 cultural heritage items
            $displayHeritage = array_slice($heritageData, 0, 4);
            
            foreach ($displayHeritage as $item):
                if (!isset($item['id'])) continue;
            ?>
                <a href="Cultural Heritage Module/cultural-heritage.php" class="cultural-heritage-card">
                    <?php if (!empty($item['image'])): ?>
                        <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title'] ?? 'Cultural Heritage'); ?>" loading="lazy">
                    <?php else: ?>
                        <img src="assets/images/cultural-heritage-default.jpg" alt="<?php echo htmlspecialchars($item['title'] ?? 'Cultural Heritage'); ?>" loading="lazy">
                    <?php endif; ?>
                    <div class="cultural-heritage-content">
                        <?php if (!empty($item['category'])): ?>
                            <span class="cultural-heritage-category"><?php echo htmlspecialchars($item['category']); ?></span>
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars($item['title'] ?? 'Untitled'); ?></h3>
                        <p><?php echo htmlspecialchars(substr($item['description'] ?? '', 0, 100)) . (strlen($item['description'] ?? '') > 100 ? '...' : ''); ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
            
            <?php if (empty($displayHeritage)): ?>
                <div style="text-align:center;grid-column:1/-1;padding: 2rem;background: #f9f9f9;border-radius: 8px;">
                    <p style="font-size: 1.1rem;color: #666;margin-bottom: 0.5rem;">🏛️ There is no available data at the moment.</p>
                    <p style="font-size: 0.95rem;color: #888;">The information is still on going research.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php if (!empty($displayHeritage)): ?>
        <div class="cultural-heritage-actions">
            <a href="Cultural Heritage Module/cultural-heritage.php" class="btn btn-primary">View All Cultural Heritage →</a>
        </div>
        <?php endif; ?>
    </section>

    <!-- Planning Section -->
    <section class="planning" id="plan">
        <h2>Plan Your Visit</h2>
        <p class="section-subtitle">Explore our top tourist destinations with comprehensive guides, best travel times, packing lists, and visiting guidelines.</p>
<div class="experiences-grid">
<?php
            $dbFile = appDatabasePath();
            $destinations = [];
            if (file_exists($dbFile)) {
                try {
                    $db = new SQLite3($dbFile);
                    $result = $db->query('SELECT * FROM destinations ORDER BY featured DESC, id DESC LIMIT 8');
                    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                        $destinations[] = $row;
                    }
                    $db->close();
                } catch (Exception $e) {
                    // Fallback static
                }
            }
            
            // Get type icons
            $typeIcons = [
                'Natural Wonder' => '🏞️',
                'Adventure' => '⛰️',
                'Museum' => '🏛️',
                'Religious' => '⛪',
                'Festival' => '🎉',
                'Historical' => '📜',
                'Local Cuisine' => '🍽️'
            ];
            
            foreach ($destinations as $dest):
                $linkName = strtolower(str_replace(' ', '-', $dest['name']));
                $locationIcon = '<svg class="mini-location-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M32 4C18.3 4 8 14.2 8 27.3c0 17.5 21.2 30.8 24 34.3 2.8-3.5 24-16.8 24-34.3C56 14.2 45.7 4 32 4zm0 15.7a12 12 0 1 1 0 24 12 12 0 0 1 0-24z" fill="#ffffff" stroke="#39d96a" stroke-width="4" stroke-linejoin="round"/><circle cx="32" cy="28.5" r="8.4" fill="#ffffff" stroke="#39d96a" stroke-width="4"/></svg>';
            ?>
                <a href="Plan Module/destination.php?destination=<?php echo $linkName; ?>" class="experience-item">
                    <?php if (!empty($dest['image'])): ?>
                        <img src="<?php echo str_replace('../../', '', htmlspecialchars($dest['image'])); ?>" alt="<?php echo htmlspecialchars($dest['name']); ?>" loading="lazy">
                    <?php else: ?>
                        <img src="assets/images/destination-default.jpg" alt="<?php echo htmlspecialchars($dest['name']); ?>" loading="lazy">
                    <?php endif; ?>
                    <h3><?php echo ($dest['featured'] == 1) ? '<span class="mini-star-wrap"><svg class="mini-star-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M32 9l7 15 17 2-12 11 3 17-15-8-15 8 3-17L8 26l17-2L32 9z" fill="none" stroke="#39d96a" stroke-width="4" stroke-linejoin="round"/></svg></span>' : ''; ?><?php echo htmlspecialchars($dest['name']); ?></h3>
                    <p><?php echo htmlspecialchars(substr($dest['description'], 0, 100)) . (strlen($dest['description']) > 100 ? '...' : ''); ?></p>
                    <span class="experience-cta">View Details →</span>
                </a>
            <?php endforeach; ?>
            
            <?php if (empty($destinations)): ?>
            <a href="Plan Module/destination.php?destination=pumauna-waterfalls" class="experience-item">
                <img src="assets/images/destinations/pumauna-waterfalls.jpg" alt="Pumauna Waterfalls" loading="lazy">
                <h3>Pumauna Waterfalls</h3>
                <p>Magnificent cascade with natural pools and scenic hiking trails.</p>
                <span class="experience-cta">View Details →</span>
            </a>
            <a href="Plan Module/destination.php?destination=azuela-springs" class="experience-item">
                <img src="assets/images/destinations/azuela-springs.jpg" alt="Azuela Springs" loading="lazy">
                <h3>Azuela Springs</h3>
                <p>Crystal clear natural pools fed by underground springs.</p>
                <span class="experience-cta">View Details →</span>
            </a>
            <a href="Plan Module/destination.php?destination=mt-kampalilis" class="experience-item">
                <img src="assets/images/destinations/mt-kampalilis.jpg" alt="Mt. Kampalilis" loading="lazy">
                <h3>Mt. Kampalilis</h3>
                <p>Challenge yourself with a scenic mountain trek to 1,240m peak.</p>
                <span class="experience-cta">View Details →</span>
            </a>
            <a href="Plan Module/destination.php?destination=tagum-river" class="experience-item">
                <img src="assets/images/destinations/tagum-river.jpg" alt="Tagum River" loading="lazy">
                <h3>Tagum River</h3>
                <p>Pristine waterway perfect for boating, fishing, and relaxation.</p>
                <span class="experience-cta">View Details →</span>
            </a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Hotels & Restaurants Section -->
    <section class="hotels-restaurants" id="hotels-restaurants">
        <h2 class="section-title">Hotels & Restaurants</h2>
        <div class="explore-grid">
            <!-- Hotel Categories Card -->
            <div class="explore-card">
                <div class="card-image simple-icon-wrap">
                    <svg class="simple-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <rect x="10" y="22" width="44" height="24" rx="4" fill="none" stroke="#39d96a" stroke-width="4"/>
                        <path d="M18 22V14h6v8M30 22V10h6v12M42 22V16h6v6" fill="none" stroke="#39d96a" stroke-width="4" stroke-linecap="round"/>
                        <path d="M10 46h44" stroke="#39d96a" stroke-width="4" stroke-linecap="round"/>
                    </svg>
                </div>
                <h3>Hotel Categories</h3>
                <p>Find perfect accommodations from luxury hotels to cozy stays across various categories.</p>
                <a href="Hotel Module/hotels.php" class="card-link">Explore Hotels →</a>
            </div>
            
            <!-- Restaurant Categories Card -->
            <div class="explore-card">
                <div class="card-image simple-icon-wrap">
                    <svg class="simple-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M18 18h28c0 11-6.2 18.8-14 23.5C24.2 36.8 18 29 18 18z" fill="none" stroke="#39d96a" stroke-width="4" stroke-linejoin="round"/>
                        <path d="M26 18v18M38 18v18M20 30h24" stroke="#39d96a" stroke-width="4" stroke-linecap="round"/>
                    </svg>
                </div>
                <h3>Restaurant Categories</h3>
                <p>Discover diverse dining experiences from local eateries to fine dining.</p>
                <a href="Restaurant Module/restaurants.php" class="card-link">Explore Restaurants →</a>
            </div>
        </div>
    </section>

    <!-- Certification Application Section -->
    <section class="certification-application-section" id="certification">
        <?php
        require_once __DIR__ . '/includes/module_link_banner.php';
        renderIndexCertificationPromo('from_index');
        ?>
    </section>

    <style>
        .hero-accordion-gallery {
            width: 100%;
            height: 82vh;
            min-height: 480px;
            max-height: 760px;
            overflow: hidden;
            background: #04150f;
            aspect-ratio: 4 / 5;
        }
        .accordion-gallery {
            display: flex;
            width: 100%;
            height: 100%;
            overflow: hidden;
            align-items: stretch;
        }
        .gallery-item {
            position: relative;
            flex: 0.7;
            min-width: 0;
            opacity: 1;
            overflow: hidden;
            pointer-events: auto;
            transition: flex 0.6s ease-out, filter 0.3s ease-out;
            filter: brightness(0.8) saturate(0.9);
        }
        .gallery-item.is-active {
            flex: 4.6;
            filter: brightness(1) saturate(1);
        }
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            background: #04150f;
            transition: transform 0.6s ease-out, opacity 0.25s ease-out;
            transform: scale(1.02);
            opacity: 0.94;
            object-position: center center;
        }
        .gallery-item.is-active img {
            object-fit: cover;
            transform: scale(1);
            opacity: 1;
            object-position: center center;
        }
        .card-content {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 2rem;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            color: white;
            z-index: 2;
            opacity: 0;
            transform: translateY(22px);
            transition: opacity 0.2s ease-out, transform 0.2s ease-out;
        }
        .gallery-item.is-active .card-content {
            opacity: 1;
            transform: translateY(0);
        }
        .card-content .slide-tagline {
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
            color: rgba(255,255,255,0.8);
            font-weight: 500;
        }
        .card-content h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: white;
        }
        .card-content p {
            font-size: 1rem;
            line-height: 1.5;
            color: rgba(255,255,255,0.9);
        }
        @media (max-width: 768px) {
            .hero-accordion-gallery {
                height: 62vh;
                min-height: 380px;
                max-height: 520px;
            }
            .card-content {
                padding: 1rem;
            }
            .card-content h3 {
                font-size: 1.1rem;
            }
            .card-content p {
                font-size: 0.85rem;
            }
        }
        .cultural-heritage-preview {
            padding: 4rem 2rem;
            background-color: transparent;
            max-width: 1200px;
            margin: 0 auto;
        }
        .cultural-heritage-preview h2 {
            font-size: 2.5rem;
            color: var(--dark-green, #1d5a3d);
            margin-bottom: 0.5rem;
            text-align: center;
        }
        .cultural-heritage-preview .section-subtitle {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 3rem;
            text-align: center;
        }
        .cultural-heritage-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .cultural-heritage-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-decoration: none;
            display: flex;
            flex-direction: column;
        }
        .cultural-heritage-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }
        .cultural-heritage-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        .cultural-heritage-content {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .cultural-heritage-category {
            display: inline-block;
            background: var(--light-green, #2d7a4d);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
            align-self: flex-start;
        }
        .cultural-heritage-content h3 {
            font-size: 1.25rem;
            color: var(--dark-green, #1d5a3d);
            margin-bottom: 0.5rem;
        }
        .cultural-heritage-content p {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        .cultural-heritage-actions {
            text-align: center;
            margin-top: 2rem;
        }
        @media (max-width: 768px) {
            .cultural-heritage-preview {
                padding: 3rem 1rem;
            }
            .cultural-heritage-preview h2 {
                font-size: 2rem;
            }
            .cultural-heritage-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }
        .hotels-restaurants {
            padding: 4rem 2rem;
            background-color: transparent;
            max-width: 1200px;
            margin: 0 auto;
        }
        .hotels-restaurants .section-title {
            font-size: 2.5rem;
            color: var(--dark-green, #1d5a3d);
            margin-bottom: 3rem;
            text-align: center;
        }
        .certification-application-section {
            padding: 4rem 2rem;
            background-color: transparent;
            max-width: 1200px;
            margin: 0 auto;
        }
        .cert-index-promo {
            background: white;
            border-radius: 12px;
            padding: 2.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .cert-index-promo-inner {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            align-items: center;
            text-align: center;
        }
        .cert-index-promo-icon {
            font-size: 3rem;
        }
        .cert-index-promo-text h3 {
            font-size: 1.75rem;
            color: var(--dark-green, #1d5a3d);
            margin-bottom: 0.75rem;
        }
        .cert-index-promo-text p {
            color: #666;
            max-width: 600px;
            line-height: 1.6;
        }
        .cert-index-promo-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }
        .cert-promo-btn {
            padding: 1rem 2rem;
            font-size: 1rem;
            border-radius: 8px;
            text-decoration: none;
            transition: transform 0.2s, box-shadow 0.2s, background-color 0.2s, color 0.2s;
        }
        .cert-promo-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        .cert-promo-dot {
            background-color: var(--dark-green, #1d5a3d);
            color: #fff;
            border: 2px solid var(--dark-green, #1d5a3d);
        }
        .cert-promo-dot:hover {
            background-color: var(--light-green, #2d7a4d);
            border-color: var(--light-green, #2d7a4d);
            color: #fff;
        }
        .cert-promo-local {
            background-color: #fff;
            color: var(--dark-green, #1d5a3d);
            border: 2px solid var(--dark-green, #1d5a3d);
        }
        .cert-promo-local:hover {
            background-color: var(--dark-green, #1d5a3d);
            color: #fff;
            border-color: var(--dark-green, #1d5a3d);
        }
        @media (max-width: 768px) {
            .cert-index-promo {
                padding: 2rem 1rem;
            }
            .cert-index-promo-text p {
                max-width: 100%;
            }
            .cert-promo-btn {
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            .cert-index-promo-actions {
                flex-direction: column;
                width: 100%;
            }
        }
        @media (min-width: 768px) {
            .cert-index-promo-inner {
                flex-direction: row;
                text-align: left;
                align-items: flex-start;
            }
            .cert-index-promo-actions {
                flex-direction: column;
                margin-left: auto;
            }
        }
    </style>

    <!-- Contact Information Section -->
    <section class="contact-information" id="contact">
        <h2 class="section-title">Contact Information</h2>
        <div class="contact-display">
            <div class="contact-details">
                <div class="contact-row">
                    <span class="contact-icon simple-icon-wrap">
                        <svg class="simple-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <circle cx="32" cy="32" r="20" fill="none" stroke="#39d96a" stroke-width="4"/>
                            <path d="M32 12v40M12 32h40" stroke="#39d96a" stroke-width="4" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <div>
                        <strong>Website:</strong><br><a href="https://tagumcity.gov.ph" target="_blank" rel="noopener noreferrer">tagumcity.gov.ph</a>
                    </div>
                </div>
                <div class="contact-row">
                    <span class="contact-icon simple-icon-wrap">
                        <svg class="simple-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <circle cx="32" cy="32" r="20" fill="none" stroke="#39d96a" stroke-width="4"/>
                            <path d="M32 12v40M12 32h40" stroke="#39d96a" stroke-width="4" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <div>
                        <strong>Facebook:</strong><br><a href="https://www.facebook.com/tagumtourismandcultural" target="_blank" rel="noopener noreferrer">Tagum Tourism and Cultural Office</a>
                    </div>
                </div>
                <div class="contact-row">
                    <span class="contact-icon simple-icon-wrap">
                        <svg class="simple-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <rect x="14" y="16" width="36" height="28" rx="4" fill="none" stroke="#39d96a" stroke-width="4"/>
                            <path d="M18 22l14 12 14-12" fill="none" stroke="#39d96a" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <div>
                        <strong>Email:</strong><br><a href="mailto:tagumtourismcultural@gmail.com" target="_self" rel="noopener noreferrer">tagumtourismcultural@gmail.com</a>
                    </div>
                </div>
                <div class="contact-row">
                    <span class="contact-icon simple-icon-wrap">
                        <svg class="simple-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M22 16a4 4 0 0 1 4-4h5l6 12-5 3c2.7 6.2 7.3 10.8 13.5 13.5l3-5 12 6v5a4 4 0 0 1-4 4C39 60 18 39 22 16z" fill="none" stroke="#39d96a" stroke-width="4" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <div>
                        <strong>Contact Number:</strong><br><a href="tel:09534971605">0953 497 1605</a>
                    </div>
                </div>
                <div class="contact-row">
                    <span class="contact-icon simple-icon-wrap">
                        <svg class="simple-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M32 10c-9.7 0-17 7.4-17 16.8 0 12.7 17 26.2 17 26.2s17-13.5 17-26.2C49 17.4 41.7 10 32 10zm0 22.7A8.8 8.8 0 1 1 32 15.5a8.8 8.8 0 0 1 0 17.2z" fill="none" stroke="#39d96a" stroke-width="4" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <div>
                        <strong>Address:</strong><br> 1st Floor, City of Tagum Cultural Center Bldg., Osmeña St., Tagum City, Philippines, 8100 <br>
                    </div>
                </div>
            </div>
        </div>
        <style>
            .contact-information {
                padding: 4rem 2rem;
                background-color: transparent;
                max-width: 1200px;
                margin: 0 auto;
            }
            .contact-information .section-title {
                font-size: 2.5rem;
                color: var(--dark-green, #1d5a3d);
                margin-bottom: 3rem;
                text-align: center;
            }
            .contact-display {
                max-width: 800px;
                margin: 0 auto;
            }
            .contact-details {
                background: white;
                padding: 3rem;
                border-radius: 12px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            }
            .contact-row {
                display: flex;
                align-items: flex-start;
                gap: 1.5rem;
                margin-bottom: 2rem;
                padding-bottom: 1.5rem;
                border-bottom: 1px solid #eee;
            }
            .contact-row:last-child {
                margin-bottom: 0;
                border-bottom: none;
            }
            .contact-icon {
                font-size: 2.5rem;
                flex-shrink: 0;
                margin-top: 0.2rem;
            }
            .contact-row strong {
                color: var(--dark-green, #1d5a3d);
                font-size: 1.1rem;
            }
            .contact-row div {
                flex: 1;
            }
            .contact-row a {
                color: var(--primary-color, #1d5a3d);
                text-decoration: none;
            }
            .contact-row a:hover {
                text-decoration: underline;
            }

            /* Mobile: prevent email/phone text from overflowing the box */
            @media (max-width: 480px) {
                .contact-details {
                    padding: 1.25rem;
                }

                .contact-row {
                    gap: 0.9rem;
                    margin-bottom: 1.25rem;
                    padding-bottom: 1rem;
                }

                .contact-row div {
                    flex: 1 1 auto;
                    min-width: 0;
                }

                .contact-row a,
                .contact-row div {
                    word-break: break-word;
                    overflow-wrap: anywhere;
                    white-space: normal;
                }
            }
        </style>

<script src="js/script.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const monthButtons = Array.from(document.querySelectorAll('.month-item'));
        const heroSection = document.querySelector('.festival-hero');
        const heroBadge = document.getElementById('hero-badge');
        const heroTitle = document.getElementById('hero-title');
        const heroScript = document.getElementById('hero-script');
        const heroMeta = document.getElementById('hero-meta');
        const heroDescription = document.getElementById('hero-description');
        const heroMonthEvent = document.getElementById('hero-month-event');
        const heroCta = document.getElementById('hero-cta');
        const heroFeatures = document.getElementById('hero-features');
        const heroImages = [
            document.querySelector('.hero-main-image img'),
            document.querySelector('.hero-side-card-top img'),
            document.querySelector('.hero-side-card-bottom img')
        ];
        const prevButton = document.querySelector('.month-nav-btn[data-nav="prev"]');
        const nextButton = document.querySelector('.month-nav-btn[data-nav="next"]');
        const monthNames = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
        const heroThemes = ['theme-soft', 'theme-rich', 'theme-chinese-new-year', 'theme-musikahan', 'theme-musikahan-blue', 'theme-araw-green'];
        let heroMonthProfiles = {};
        const profilesNode = document.getElementById('hero-month-profiles');
        if (profilesNode) {
            try {
                heroMonthProfiles = JSON.parse(profilesNode.textContent || '{}');
            } catch (error) {
                heroMonthProfiles = {};
            }
        }

        function setHeroTitle(titleLines) {
            if (!heroTitle || !Array.isArray(titleLines)) return;
            heroTitle.innerHTML = '';
            titleLines.forEach(function(line, index) {
                if (index > 0) {
                    heroTitle.appendChild(document.createElement('br'));
                }
                heroTitle.appendChild(document.createTextNode(line));
            });
        }

        function applyHeroProfile(profile, selectedButton) {
            if (!profile) return;

            if (heroSection) {
                heroThemes.forEach(function(themeClass) {
                    heroSection.classList.remove(themeClass);
                });
                heroSection.classList.add(profile.theme || 'theme-soft');
            }

            if (heroScript) heroScript.textContent = profile.script || '';
            if (heroDescription) heroDescription.textContent = profile.description || '';
            if (heroMeta) {
                const meta1 = heroMeta.querySelector('[data-meta="1"]');
                const meta2 = heroMeta.querySelector('[data-meta="2"]');
                const meta3 = heroMeta.querySelector('[data-meta="3"]');
                if (meta1) meta1.textContent = profile.meta1 || '';
                if (meta2) meta2.textContent = profile.meta2 || '';
                if (meta3) meta3.textContent = profile.meta3 || '';
            }

            setHeroTitle(profile.titleLines || []);

            if (heroMonthEvent) {
                const monthEventLine = profile.monthEvent || (selectedButton ? selectedButton.dataset.eventName : '') || '';
                heroMonthEvent.textContent = monthEventLine;
                heroMonthEvent.hidden = monthEventLine === '';
            }

            if (heroCta) {
                const ctaText = profile.cta || 'EXPLORE FESTIVAL';
                heroCta.innerHTML = '';
                heroCta.appendChild(document.createTextNode(ctaText + ' '));
                const arrow = document.createElement('span');
                arrow.textContent = '→';
                heroCta.appendChild(arrow);
            }

            if (heroFeatures && Array.isArray(profile.features)) {
                const items = heroFeatures.querySelectorAll('.feature-item');
                profile.features.forEach(function(feature, index) {
                    const item = items[index];
                    if (!item) return;
                    const glyph = item.querySelector('.feature-icon-glyph');
                    const title = item.querySelector('.feature-title');
                    const text = item.querySelector('.feature-text');
                    if (glyph) glyph.textContent = feature.icon || '';
                    if (title) title.textContent = feature.title || '';
                    if (text) text.textContent = feature.text || '';
                });
            }
        }

        function setSelectedMonth(monthNumber) {
            monthButtons.forEach((button) => {
                const isActive = Number(button.dataset.month) === monthNumber;
                button.classList.toggle('month-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });

            if (heroBadge) {
                heroBadge.textContent = monthNames[monthNumber - 1];
            }

            const selectedButton = document.querySelector('.month-item[data-month="' + monthNumber + '"]');
            const profile = heroMonthProfiles[String(monthNumber)];
            applyHeroProfile(profile, selectedButton);

            if (selectedButton) {
                const monthImages = [selectedButton.dataset.imageMain, selectedButton.dataset.imageTop, selectedButton.dataset.imageBottom];
                heroImages.forEach((image, index) => {
                    if (image && monthImages[index]) image.src = monthImages[index];
                });
            }
        }

        monthButtons.forEach((button) => {
            button.addEventListener('click', function() {
                setSelectedMonth(Number(this.dataset.month));
            });
        });

        if (prevButton) {
            prevButton.addEventListener('click', function() {
                const current = document.querySelector('.month-item.month-active');
                const activeMonth = current ? Number(current.dataset.month) : 5;
                const nextMonth = activeMonth <= 1 ? 12 : activeMonth - 1;
                setSelectedMonth(nextMonth);
            });
        }

        if (nextButton) {
            nextButton.addEventListener('click', function() {
                const current = document.querySelector('.month-item.month-active');
                const activeMonth = current ? Number(current.dataset.month) : 5;
                const nextMonth = activeMonth >= 12 ? 1 : activeMonth + 1;
                setSelectedMonth(nextMonth);
            });
        }

        const galleryItems = Array.from(document.querySelectorAll('.gallery-item'));
        const galleryContainer = document.querySelector('.accordion-gallery');
        let currentIndex = 0;
        let autoCycleInterval;
        let isPaused = false;
        const cycleDuration = 10000;

        function activateCard(index) {
            if (!galleryItems[index]) return;
            galleryItems.forEach((item, itemIndex) => {
                item.classList.toggle('is-active', itemIndex === index);
            });
            currentIndex = index;
        }

        function nextCard() {
            const nextIndex = (currentIndex + 1) % galleryItems.length;
            activateCard(nextIndex);
        }

        function startAutoCycle() {
            if (autoCycleInterval) {
                clearInterval(autoCycleInterval);
            }
            if (!isPaused) {
                autoCycleInterval = setInterval(nextCard, cycleDuration);
            }
        }

        function stopAutoCycle() {
            if (autoCycleInterval) {
                clearInterval(autoCycleInterval);
                autoCycleInterval = null;
            }
        }

        if (galleryItems.length) {
            startAutoCycle();
        }
    });
</script>
</body>
</html>

