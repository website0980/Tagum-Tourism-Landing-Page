<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cultural Heritage - Tagum City</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/mobile-navbar.css">
    <link rel="stylesheet" href="../css/cultural-heritage.css">
    <script src="../js/navbar.js"></script>
</head>
<body>
<?php include '../navbar.php'; ?>

    <!-- Main Content -->
    <main class="cultural-heritage">
        <div class="container">
            <header class="cultural-header">
                <h1>Cultural Heritage</h1>
                <p class="cultural-subtitle">Discover the rich cultural heritage of Tagum City</p>
            </header>

            <div class="cultural-grid">
                <?php
                // Load cultural heritage data from JSON
                $heritageData = json_decode(file_get_contents('cultural-heritage.json'), true) ?? [];

                foreach ($heritageData as $item):
                    if (!isset($item['id'])) continue;
                ?>
                    <article class="cultural-item">
                        <?php if (!empty($item['image'])): ?>
                            <div class="cultural-image">
                                <img src="<?php echo htmlspecialchars('../' . $item['image']); ?>" alt="<?php echo htmlspecialchars($item['title'] ?? 'Cultural Heritage'); ?>" loading="lazy">
                            </div>
                        <?php endif; ?>
                        
                        <div class="cultural-content">
                            <h2><?php echo htmlspecialchars($item['title'] ?? 'Untitled'); ?></h2>
                            <?php if (!empty($item['category'])): ?>
                                <span class="cultural-category"><?php echo htmlspecialchars($item['category']); ?></span>
                            <?php endif; ?>
                            
                            <div class="cultural-description">
                                <?php echo nl2br(htmlspecialchars($item['description'] ?? '')); ?>
                            </div>

                            <?php if (!empty($item['images']) && is_array($item['images']) && !empty(array_filter($item['images']))): ?>
                                <div class="cultural-gallery">
                                    <h3 class="cultural-gallery-title">Gallery</h3>
                                    <div class="cultural-gallery-grid">
                                        <?php foreach ($item['images'] as $img): ?>
                                            <?php if (!empty($img)): ?>
                                                <img src="<?php echo htmlspecialchars('../' . $img); ?>" alt="Gallery image" loading="lazy">
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>

                <?php if (empty($heritageData)): ?>
                    <div class="empty-state">
                        <h2>No Cultural Heritage Content Yet</h2>
                        <p>Cultural heritage information will be displayed here soon.</p>
                        <a href="../index.php" class="btn btn-primary">← Back to Home</a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="cultural-actions">
                <a href="../index.php" class="btn btn-secondary">← Back to Home</a>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <p></p>
        </div>
    </footer>
</body>
</html>
