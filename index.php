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

    <!-- Hero Accordion Gallery Section -->
    <?php
    $carouselSlides = [];
    $dbFile = 'database.db';
    if (file_exists($dbFile)) {
        try {
            $db = new SQLite3($dbFile);
            $schema = @file_get_contents('database/carousel_schema.sql');
            if ($schema) {
                $db->exec($schema);
            }
            $result = $db->query('SELECT * FROM carousel_slides WHERE active = 1 ORDER BY sort_order ASC, id ASC');
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $carouselSlides[] = $row;
            }
            $db->close();
        } catch (Exception $e) {
            $carouselSlides = [];
        }
    }
    ?>
    <section class="hero-accordion-gallery" id="home">
        <div class="accordion-gallery">
            <?php if (!empty($carouselSlides)): ?>
                <?php foreach ($carouselSlides as $index => $slide): ?>
                    <div class="gallery-item<?php echo $index === 0 ? ' is-active' : ''; ?>" data-index="<?php echo $index; ?>">
                        <img src="<?php echo htmlspecialchars($slide['image']); ?>" alt="<?php echo htmlspecialchars($slide['title']); ?>">
                        <a href="<?php echo htmlspecialchars($slide['btn_primary_link'] ?? '#plan'); ?>" class="card-overlay"></a>
                        <div class="card-content">
                            <?php if (!empty($slide['tagline'])): ?>
                                <p class="slide-tagline"><?php echo htmlspecialchars($slide['tagline']); ?></p>
                            <?php endif; ?>
                            <h3><?php echo nl2br(htmlspecialchars($slide['title'])); ?></h3>
                            <p><?php echo htmlspecialchars($slide['description']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback slides when database is empty -->
                <div class="gallery-item is-active" data-index="0">
                    <img src="images/Background for slide 1.jpg" alt="Natural Beauty">
                    <a href="#plan" class="card-overlay"></a>
                    <div class="card-content">
                        <p class="slide-tagline">Tagumeños: Beauty that Shines from Within.</p>
                        <h3>Discover Natural Beauty</h3>
                        <p>Tagumeños are a reflection of true natural beauty radiating warmth, kindness, and genuine smiles.</p>
                    </div>
                </div>
                <div class="gallery-item" data-index="1">
                    <img src="images/Background for slide 2 .jpg" alt="Cultural Heritage">
                    <a href="#cultural-heritage" class="card-overlay"></a>
                    <div class="card-content">
                        <h3>Rich Cultural Heritage</h3>
                        <p>Experience the vibrant traditions and cultural treasures that make Tagum City unique.</p>
                    </div>
                </div>
                <div class="gallery-item" data-index="2">
                    <img src="images/Background for slide 3.jpg" alt="Adventure">
                    <a href="#experiences" class="card-overlay"></a>
                    <div class="card-content">
                        <h3>Unforgettable Adventures</h3>
                        <p>From mountain trekking to river tours, discover thrilling experiences in nature.</p>
                    </div>
                </div>
                <div class="gallery-item" data-index="3">
                    <img src="images/destinations/dest_1778551486_6a028abed84a2.jpg" alt="Destinations">
                    <a href="#featured" class="card-overlay"></a>
                    <div class="card-content">
                        <h3>Featured Destinations</h3>
                        <p>Explore our top-picked attractions and hidden gems waiting to be discovered.</p>
                    </div>
                </div>
                <div class="gallery-item" data-index="4">
                    <img src="images/events/event_1778551039_6a0288ff81b81.jpg" alt="Events">
                    <a href="#explore" class="card-overlay"></a>
                    <div class="card-content">
                        <h3>Vibrant Events</h3>
                        <p>Join local festivals and celebrations showcasing music, dance, and community spirit.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Explore Section -->
    <section class="explore" id="explore">
        <h2>Know More about Tagum City</h2>
        <div class="explore-grid">

            <!-- Event Card -->
            <div class="explore-card">
                <div class="card-image">👥</div>
                <h3>Events</h3>
                <p>Check every events happening in Tagum City</p>
                <a href="Explore Module/events-calendar.php" class="card-link">Learn More →</a>
            </div>
            
            <!-- Festivals Card -->
            <div class="explore-card">
                <div class="card-image">🎉</div>
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
            $dbFile = 'database.db';
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
                    <h3><?php echo isset($exp['featured']) && $exp['featured'] === true ? '⭐ ' : ''; ?><?php echo htmlspecialchars($exp['name']); ?></h3>
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
            $dbFile = 'database.db';
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
                $icon = '📍';
                $linkName = strtolower(str_replace(' ', '-', $dest['name']));
            ?>
                <a href="Plan Module/destination.php?destination=<?php echo $linkName; ?>" class="featured-card">
                    <div class="featured-icon"><?php echo $icon; ?></div>
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
            $dbFile = 'database.db';
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
                $icon = '📍';
                $linkName = strtolower(str_replace(' ', '-', $dest['name']));
            ?>
                <a href="Plan Module/destination.php?destination=<?php echo $linkName; ?>" class="experience-item">
                    <?php if (!empty($dest['image'])): ?>
                        <img src="<?php echo str_replace('../../', '', htmlspecialchars($dest['image'])); ?>" alt="<?php echo htmlspecialchars($dest['name']); ?>" loading="lazy">
                    <?php else: ?>
                        <img src="assets/images/destination-default.jpg" alt="<?php echo htmlspecialchars($dest['name']); ?>" loading="lazy">
                    <?php endif; ?>
                    <h3><?php echo ($dest['featured'] == 1) ? '⭐ ' : ''; ?><?php echo htmlspecialchars($dest['name']); ?></h3>
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
                <div class="card-image">🏨</div>
                <h3>Hotel Categories</h3>
                <p>Find perfect accommodations from luxury hotels to cozy stays across various categories.</p>
                <a href="Hotel Module/hotels.php" class="card-link">Explore Hotels →</a>
            </div>
            
            <!-- Restaurant Categories Card -->
            <div class="explore-card">
                <div class="card-image">🍴</div>
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
            height: 100vh;
            max-height: 700px;
            overflow: hidden;
        }
        .accordion-gallery {
            display: flex;
            width: 100%;
            height: 100%;
        }
        .gallery-item {
            flex: 0.7;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            transition: flex 1.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .gallery-item.is-active {
            flex: 4.5;
        }
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 1.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .gallery-item.is-active img {
            transform: scale(1.05);
        }
        .card-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
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
            transform: translateY(20px);
            transition: opacity 0.5s ease, transform 0.5s ease;
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
                height: 60vh;
                max-height: 400px;
            }
            .gallery-item {
                flex: 0.5;
            }
            .gallery-item.is-active {
                flex: 3;
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
                    <span class="contact-icon">🌐</span>
                    <div>
                        <strong>Website:</strong><br><a href="https://tagumcity.gov.ph" target="_blank" rel="noopener noreferrer">tagumcity.gov.ph</a>
                    </div>
                </div>
                <div class="contact-row">
                    <span class="contact-icon">🌐</span>
                    <div>
                        <strong>Facebook:</strong><br><a href="https://www.facebook.com/tagumtourismandcultural" target="_blank" rel="noopener noreferrer">Tagum Tourism and Cultural Office</a>
                    </div>
                </div>
                <div class="contact-row">
                    <span class="contact-icon">✉️</span>
                    <div>
                        <strong>Email:</strong><br><a href="mailto:tagumtourismcultural@gmail.com" target="_self" rel="noopener noreferrer">tagumtourismcultural@gmail.com</a>
                    </div>
                </div>
                <div class="contact-row">
                    <span class="contact-icon">📞</span>
                    <div>
                        <strong>Contact Number:</strong><br><a href="tel:09534971605">0953 497 1605</a>
                    </div>
                </div>
                <div class="contact-row">
                    <span class="contact-icon">📍</span>
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
    // Accordion Gallery Auto-Cycling Logic
    document.addEventListener('DOMContentLoaded', function() {
        const galleryItems = document.querySelectorAll('.gallery-item');
        const galleryContainer = document.querySelector('.accordion-gallery');
        let currentIndex = 0;
        let autoCycleInterval;
        let isPaused = false;
        const cycleDuration = 5000; // 5 seconds

        function activateCard(index) {
            galleryItems.forEach(item => item.classList.remove('is-active'));
            galleryItems[index].classList.add('is-active');
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

        // Start auto-cycling on page load
        startAutoCycle();

        // User interaction handlers - pause on hover, resume when leaving entire gallery
        galleryItems.forEach((item, index) => {
            // Pause and activate on hover/focus
            item.addEventListener('mouseenter', () => {
                isPaused = true;
                stopAutoCycle();
                activateCard(index);
            });

            item.addEventListener('focusin', () => {
                isPaused = true;
                stopAutoCycle();
                activateCard(index);
            });
        });

        // Resume when mouse leaves the entire gallery
        galleryContainer.addEventListener('mouseleave', () => {
            isPaused = false;
            startAutoCycle();
        });

        // Handle focus out on container
        galleryContainer.addEventListener('focusout', () => {
            isPaused = false;
            startAutoCycle();
        });
    });
</script>
</body>
</html>
