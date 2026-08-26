<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Experience Details - Tagum City'); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/mobile-navbar.css">
    <link rel="stylesheet" href="../css/experience-details.css">
    <script src="../js/navbar.js"></script>
</head>
<body>
<?php include '../navbar.php'; ?>

    <!-- Main Content -->
    <main class="experience-single">
        <div class="container">
            <?php
            // Load experiences from database with JSON fallback
            $dbFile = '../database.db';
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
                    $experiences = json_decode(file_get_contents('../data/experiences.json'), true) ?? [];
                }
            } else {
                // Fallback to JSON if database doesn't exist
                $experiences = json_decode(file_get_contents('../data/experiences.json'), true) ?? [];
            }

            // Get URL params
            $type = $_GET['type'] ?? '';
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

            // Find matching experience
            $experience = null;
            foreach ($experiences as $exp) {
                if ((int)$exp['id'] === $id) {
                    $experience = $exp;
                    break;
                }
            }

            $pageTitle = $experience ? htmlspecialchars($experience['name']) : 'Experience Not Found';

            if ($experience):
            ?>
<article class="experience-detail active">
                    <header class="experience-header">
                        <h1><?php echo (isset($experience['featured']) && $experience['featured']) ? '⭐ ' : ''; ?><?php echo htmlspecialchars($experience['name']); ?></h1>
                        <div class="experience-meta">
                            <span class="exp-type"><?php echo htmlspecialchars($experience['type'] ?? 'Experience'); ?></span>
                            <?php if (isset($experience['date']) && $experience['date']): ?>
                                <span class="exp-date">📅 <?php echo htmlspecialchars($experience['date']); ?></span>
                            <?php endif; ?>
                        </div>
                    </header>

                    <?php if (!empty($experience['image'])): ?>
                        <div class="experience-image">
                            <img src="<?php echo htmlspecialchars($experience['image']); ?>" alt="<?php echo htmlspecialchars($experience['name']); ?>" loading="lazy">
                        </div>
                    <?php endif; ?>

                    <div class="experience-content">
                        <div class="experience-description">
                            <?php echo nl2br(htmlspecialchars($experience['description'] ?? '')); ?>
                        </div>
                    </div>

                    <div class="experience-actions">
                        <a href="../index.php#experiences" class="smooth-scroll btn btn-secondary">← Back to Experiences</a>
                        <a href="../index.php#plan" class="smooth-scroll btn btn-primary">Plan Your Visit</a>
                    </div>
                </article>
            <?php else: ?>
                <div class="not-found">
                    <h1>Experience Not Found</h1>
                    <p>The experience you're looking for doesn't exist or has been removed.</p>
                    <a href="../index.php#experiences" class="btn btn-primary">← Back to Experiences</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <p></p>
        </div>
    </footer>

    <script src="../assets/js/experience-details.js"></script>
</body>
</html>
