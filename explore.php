<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore Tagum City</title>
    <link rel="stylesheet" href="../assets/css/style.css">
<link rel="stylesheet" href="../assets/css/explore-full-page.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
<img src="../assets/images/City of Tagum Logo.png" alt="Tagum City" class="logo-img">
                <span class="logo-text">Tagum City</span>
            </div>
            <ul class="nav-menu">
                <li><a href="../index.php#home" class="nav-link">Home</a></li>
                <li><a href="../index.php#explore" class="nav-link">Explore</a></li>
                <li><a href="../index.php#experiences" class="nav-link">Experiences</a></li>
                <li><a href="../index.php#plan" class="nav-link">Plan</a></li>
            </ul>
        </div>
    </nav>

    <!-- Explore Content Section -->
    <section class="explore-full-page">
        <div class="explore-container">
            <!-- Breadcrumb Navigation -->
            <div class="breadcrumb">
                <a href="../index.php">Home</a> / <span>Explore Tagum City</span>
            </div>

            <!-- Section Tabs -->
            <div class="section-tabs">


                <button class="tab-btn" data-section="cultural-sites">Cultural Sites</button>
                <button class="tab-btn" data-section="local-cuisine">Local Cuisine</button>
                <button class="tab-btn" data-section="festivals">Festivals</button>
            </div>

            <!-- Cultural Sites Content -->
            <div class="section-content" id="cultural-sites">
                <h1>Cultural Sites</h1>
                <div class="content-text">
                    <h2>Experience Rich Heritage</h2>
                    <p>Tagum City has a vibrant cultural heritage influenced by indigenous traditions and colonial history. Explore historical landmarks and experience authentic Filipino culture.</p>
                    
                    <?php
                    $culturalSites = json_decode(file_get_contents('../assets/data/cultural-sites.json'), true);
                    ?>
                    
                    <?php if (!empty($culturalSites)): ?>
                    <div class="cuisine-grid">
                        <?php foreach ($culturalSites as $site): ?>
                            <div class="cuisine-category">
                                <?php if (!empty($site['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($site['image']); ?>" alt="<?php echo htmlspecialchars($site['name']); ?>" class="category-image" onerror="this.style.display='none'">
                                <?php endif; ?>
                                <h3><?php echo htmlspecialchars($site['name']); ?></h3>
                                <p><?php echo htmlspecialchars($site['history'] ?? $site['description'] ?? $site['highlights'] ?? 'Rich cultural landmark of Tagum City'); ?></p>
                                <button class="toggle-items-btn">View Details</button>
                                <div class="items-list">
                                    <?php if (!empty($site['location'])): ?>
                                        <div class="food-item">
                                            <div class="food-info">
                                                <h4>📍 Location</h4>
                                                <p><?php echo htmlspecialchars($site['location']); ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($site['history'])): ?>
                                        <div class="food-item">
                                            <div class="food-info">
                                                <h4>📜 History</h4>
                                                <p><?php echo htmlspecialchars($site['history']); ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($site['highlights'])): ?>
                                        <div class="food-item">
                                            <div class="food-info">
                                                <h4>⭐ Highlights</h4>
                                                <p><?php echo htmlspecialchars($site['highlights']); ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <h3>Must-Visit Sites:</h3>
                    <ul>
                        <li><strong>Tagum City Museum</strong> - Showcases local history and cultural artifacts</li>
                        <li><strong>Our Lady of Peace Shrine</strong> - Historic church and pilgrimage site</li>
                        <li><strong>Cultural Center</strong> - Hub for traditional arts and performances</li>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Local Cuisine Content -->
            <div class="section-content" id="local-cuisine">
                <h1>Local Cuisine</h1>
<div class="content-text">
                    <h2>Savor Authentic Flavors</h2>
                    <p>Tagum's culinary scene is a delicious blend of traditional Filipino recipes, indigenous flavors, and modern cooking techniques. Discover the tastes that define our community.</p>
                    
                    <?php
                    $cuisine = json_decode(file_get_contents('../assets/data/cuisine.json'), true);
                    ?>
                    

                    <div class="cuisine-grid">
                        <?php foreach ($cuisine as $category): ?>
                            <div class="cuisine-category">
                                <?php if (!empty($category['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($category['image']); ?>" alt="<?php echo htmlspecialchars($category['category']); ?>" class="category-image" onerror="this.style.display='none'">
                                <?php endif; ?>
                                <h3><?php echo htmlspecialchars($category['category']); ?></h3>
                                <p><?php echo htmlspecialchars($category['description']); ?></p>
                                <p class="item-count"><?php echo count($category['items']); ?> dishes</p>
                                <button class="toggle-items-btn">View Dishes</button>
                                <div class="items-list">
                                    <?php foreach ($category['items'] as $item): ?>
                                        <div class="food-item">
                                            <?php if (!empty($item['image'])): ?>
                                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="food-image">
                                            <?php endif; ?>
                                            <div class="food-info">
                                                <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                                <p><?php echo htmlspecialchars($item['description']); ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                </div>
            </div>

            <!-- Festivals Content -->
            <div class="section-content" id="festivals">
                <h1>Festivals</h1>
                <div class="content-text">
                    <h2>Celebrate Local Culture</h2>
                    <p>Tagum City comes alive with vibrant festivals throughout the year, showcasing the rich traditions, music, dance, and culinary heritage of the region. Experience the warmth and hospitality of the local community.</p>
                    
                    <?php
                    $festivals = json_decode(file_get_contents('../assets/data/festivals.json'), true);
                    ?>
                    
                    <?php if (!empty($festivals)): ?>
                    <div class="cuisine-grid">
                        <?php foreach ($festivals as $festival): ?>
                            <div class="cuisine-category">
                                <?php if (!empty($festival['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($festival['image']); ?>" alt="<?php echo htmlspecialchars($festival['name']); ?>" class="category-image" onerror="this.style.display='none'">
                                <?php endif; ?>
                                <h3><?php echo htmlspecialchars($festival['name']); ?></h3>
                                <p><?php echo htmlspecialchars($festival['description']); ?></p>
                                <?php if (!empty($festival['date'])): ?>
                                    <p class="item-count">📅 <?php echo htmlspecialchars($festival['date']); ?></p>
                                <?php endif; ?>
                                <button class="toggle-items-btn">View Details</button>
                                <div class="items-list">
                                    <?php if (!empty($festival['highlights'])): ?>
                                        <div class="food-item">
                                            <div class="food-info">
                                                <h4>🎉 Highlights</h4>
                                                <p><?php echo htmlspecialchars($festival['highlights']); ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($festival['activities'])): ?>
                                        <div class="food-item">
                                            <div class="food-info">
                                                <h4>✨ Activities</h4>
                                                <p><?php echo htmlspecialchars($festival['activities']); ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
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

<script src="../assets/js/explore-full-page.js"></script>
    <script src="../assets/js/explore-cuisine-landscape.js"></script>
</body>
</html>