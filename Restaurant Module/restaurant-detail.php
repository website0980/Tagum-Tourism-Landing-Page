<?php
$dbFile = '../database.db';
$restaurant = null;
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (file_exists($dbFile) && $id > 0) {
    try {
        $db = new SQLite3($dbFile);
        $stmt = $db->prepare('SELECT * FROM restaurant_items WHERE id = ?');
        $stmt->bindValue(1, $id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        if ($row) {
            $restaurant = $row;
        }
        $db->close();
    } catch (Exception $e) {
        // Keep fallback state and show not-found UI.
    }
}

$pageTitle = $restaurant ? ($restaurant['name'] . ' - Tagum City') : 'Restaurant Not Found - Tagum City';

$lat = null;
$lng = null;
$mapEmbedUrl = '';
$openMapUrl = '';
$emailLink = '';
$telLink = '';

if ($restaurant) {
    if (isset($restaurant['latitude']) && isset($restaurant['longitude']) && is_numeric($restaurant['latitude']) && is_numeric($restaurant['longitude'])) {
        $lat = (float) $restaurant['latitude'];
        $lng = (float) $restaurant['longitude'];

        $delta = 0.01;
        $left = $lng - $delta;
        $right = $lng + $delta;
        $top = $lat + $delta;
        $bottom = $lat - $delta;

        $mapEmbedUrl = 'https://www.openstreetmap.org/export/embed.html?bbox='
            . rawurlencode($left . ',' . $bottom . ',' . $right . ',' . $top)
            . '&layer=mapnik&marker=' . rawurlencode($lat . ',' . $lng);
        $openMapUrl = 'https://www.openstreetmap.org/?mlat=' . rawurlencode((string) $lat)
            . '&mlon=' . rawurlencode((string) $lng) . '#map=15/' . rawurlencode((string) $lat) . '/' . rawurlencode((string) $lng);
    } elseif (!empty($restaurant['location'])) {
        $query = rawurlencode($restaurant['location'] . ', Tagum City');
        $openMapUrl = 'https://www.openstreetmap.org/search?query=' . $query;
    }

    if (!empty($restaurant['email'])) {
        $emailLink = 'mailto:' . rawurlencode(trim($restaurant['email']));
    }

    if (!empty($restaurant['contact'])) {
        $digitsOnly = preg_replace('/[^0-9+]/', '', $restaurant['contact']);
        if (!empty($digitsOnly)) {
            $telLink = 'tel:' . $digitsOnly;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/experience-details.css">
    <style>
        .restaurant-detail-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 1.25rem;
            margin: 1.5rem 0;
        }

        .restaurant-panel {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 1rem 1.1rem;
        }

        .restaurant-panel h3 {
            margin: 0 0 0.75rem;
            color: #1d5a3d;
        }

        .restaurant-description {
            color: #374151;
            line-height: 1.7;
            font-size: 1rem;
        }

        .restaurant-meta-list {
            display: grid;
            gap: 0.6rem;
            color: #374151;
            font-size: 0.98rem;
        }

        .restaurant-meta-list strong {
            color: #111827;
        }

        .restaurant-meta-list a {
            color: #1d5a3d;
            font-weight: 600;
            text-decoration: none;
        }

        .restaurant-meta-list a:hover {
            text-decoration: underline;
        }

        .restaurant-map-wrap {
            margin-top: 1.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            background: #ffffff;
        }

        .restaurant-map-wrap iframe {
            width: 100%;
            height: 320px;
            border: 0;
            display: block;
        }

        .restaurant-map-fallback {
            padding: 1rem 1.1rem;
            color: #374151;
            line-height: 1.6;
        }

        .restaurant-map-actions {
            padding: 0.8rem 1.1rem 1rem;
            border-top: 1px solid #e5e7eb;
        }

        .restaurant-map-actions a {
            color: #1d5a3d;
            font-weight: 600;
            text-decoration: none;
        }

        .restaurant-map-actions a:hover {
            text-decoration: underline;
        }

        .restaurant-photos {
            margin-top: 1.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
            overflow: hidden;
        }

        .restaurant-photos-header {
            padding: 1rem 1.1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .restaurant-photos-header h2 {
            margin: 0;
            font-size: 1.25rem;
            color: #1d5a3d;
        }

        .restaurant-photos-grid {
            padding: 1rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 0.75rem;
        }

        .restaurant-photo-thumb {
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #eef2f7;
            cursor: pointer;
            height: 200px;
        }

        .restaurant-photo-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .restaurant-photos-empty {
            padding: 1.25rem 1.1rem;
            color: #374151;
            line-height: 1.6;
        }

        /* Lightbox */
        .restaurant-photo-modal {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.72);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 1rem;
        }

        .restaurant-photo-modal.active { display: flex; }

        .restaurant-photo-modal-inner {
            width: min(900px, 100%);
            background: #0b1220;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
        }

        .restaurant-photo-modal-topbar {
            padding: 0.75rem 0.9rem;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            border-bottom: 1px solid rgba(255,255,255,0.12);
        }

        .restaurant-photo-modal-close {
            appearance: none;
            border: 0;
            background: rgba(255,255,255,0.12);
            color: #fff;
            width: 38px;
            height: 38px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 1.05rem;
        }

        .restaurant-photo-modal-body {
            position: relative;
        }

        .restaurant-photo-modal-body img {
            width: 100%;
            height: min(70vh, 640px);
            object-fit: contain;
            background: #000;
            display: block;
        }

        .restaurant-photo-modal-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 44px;
            height: 44px;
            border-radius: 12px;
            border: 0;
            background: rgba(255,255,255,0.14);
            color: #fff;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            line-height: 1;
            backdrop-filter: blur(6px);
        }

        .restaurant-photo-modal-nav:hover {
            background: rgba(255,255,255,0.22);
        }

        .restaurant-photo-modal-nav:focus {
            outline: 2px solid rgba(255,255,255,0.35);
            outline-offset: 2px;
        }

        .restaurant-photo-modal-nav.prev { left: 14px; }
        .restaurant-photo-modal-nav.next { right: 14px; }

        @media (max-width: 900px) {
            .restaurant-detail-grid {
                grid-template-columns: 1fr;
            }
            .restaurant-photo-modal-nav.prev { left: 10px; }
            .restaurant-photo-modal-nav.next { right: 10px; }
        }
    </style>
</head>
<body>
<?php include '../navbar.php'; ?>

    <main class="experience-single">
        <div class="container">
            <?php if ($restaurant): ?>
                <article class="experience-detail active">
                    <header class="experience-header">
                        <h1><?php echo htmlspecialchars($restaurant['name']); ?></h1>
                        <div class="experience-meta">
                            <span class="exp-type"><?php echo htmlspecialchars($restaurant['category'] ?? 'Restaurant'); ?></span>
                        </div>
                    </header>

                    <?php if (!empty($restaurant['image'])): ?>
                        <div class="experience-image">
                            <?php
                                $detailImagePath = $restaurant['image'];
                                // Fix image path for Restaurant Module subdirectory
                                if (strpos($detailImagePath, 'images/') === 0) {
                                    $detailImagePath = '../' . $detailImagePath;
                                } elseif (strpos($detailImagePath, 'assets/') === 0) {
                                    $detailImagePath = '../' . $detailImagePath;
                                } elseif (strpos($detailImagePath, '../../') === 0) {
                                    $detailImagePath = str_replace('../../', '../', $detailImagePath);
                                }
                            ?>
                            <img src="<?php echo htmlspecialchars($detailImagePath); ?>" alt="<?php echo htmlspecialchars($restaurant['name']); ?>" loading="lazy">
                        </div>
                    <?php endif; ?>

                    <div class="restaurant-detail-grid">
                        <div class="restaurant-panel">
                            <h3>Description</h3>
                            <?php
                                $descriptionText = trim($restaurant['description'] ?? '');
                                if ($descriptionText === '') {
                                    $descriptionText = 'No description available.';
                                }
                            ?>
                            <p class="restaurant-description"><?php echo nl2br(htmlspecialchars($descriptionText)); ?></p>
                            <?php if (!empty($restaurant['information'])): ?>
                                <h3 style="margin-top: 1rem;">Information</h3>
                                <p class="restaurant-description"><?php echo nl2br(htmlspecialchars($restaurant['information'])); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="restaurant-panel">
                            <h3>Restaurant Details</h3>
                            <div class="restaurant-meta-list">
                                <?php if (!empty($restaurant['category'])): ?>
                                    <div><strong>Category:</strong> <?php echo htmlspecialchars($restaurant['category']); ?></div>
                                <?php endif; ?>
                                <div><strong>Location:</strong> <?php echo htmlspecialchars($restaurant['location'] ?? 'Location not available'); ?></div>
                                <?php if (!empty($restaurant['contact'])): ?>
                                    <div>
                                        <strong>Contact:</strong>
                                        <?php if (!empty($telLink)): ?>
                                            <a href="<?php echo htmlspecialchars($telLink); ?>"><?php echo htmlspecialchars($restaurant['contact']); ?></a>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($restaurant['contact']); ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($restaurant['email'])): ?>
                                    <div>
                                        <strong>Email:</strong>
                                        <?php if (!empty($emailLink)): ?>
                                            <a href="<?php echo htmlspecialchars($emailLink); ?>"><?php echo htmlspecialchars($restaurant['email']); ?></a>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($restaurant['email']); ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($lat !== null && $lng !== null): ?>
                                    <div><strong>Coordinates:</strong> <?php echo htmlspecialchars(number_format($lat, 6)); ?>, <?php echo htmlspecialchars(number_format($lng, 6)); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="restaurant-map-wrap">
                        <?php if (!empty($mapEmbedUrl)): ?>
                            <iframe
                                src="<?php echo htmlspecialchars($mapEmbedUrl); ?>"
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                                title="Map location of <?php echo htmlspecialchars($restaurant['name']); ?>">
                            </iframe>
                            <div class="restaurant-map-actions">
                                <a href="<?php echo htmlspecialchars($openMapUrl); ?>" target="_blank" rel="noopener noreferrer">Open full map</a>
                            </div>
                        <?php else: ?>
                            <div class="restaurant-map-fallback">
                                Map coordinates are not available for this restaurant yet.
                                <?php if (!empty($restaurant['location'])): ?>
                                    <br>
                                    Location: <?php echo htmlspecialchars($restaurant['location']); ?>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($openMapUrl)): ?>
                                <div class="restaurant-map-actions">
                                    <a href="<?php echo htmlspecialchars($openMapUrl); ?>" target="_blank" rel="noopener noreferrer">Search this location on map</a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <?php
                        // Load restaurant gallery images from `restaurant_gallery` table.
                        $galleryImages = [];
                        if ($restaurant && !empty($restaurant['id'])) {
                            try {
                                require_once dirname(__DIR__) . '/database/setup_restaurant_gallery.php';
                                // Ensure table exists (safe idempotent).
                                ensureRestaurantGalleryTable();

                                $db = new SQLite3('../database.db');
                                $stmt = $db->prepare('SELECT image FROM restaurant_gallery WHERE restaurant_id = ? ORDER BY sort_order ASC, id ASC');
                                $stmt->bindValue(1, (int)$restaurant['id'], SQLITE3_INTEGER);
                                $result = $stmt->execute();
                                while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                                    if (!empty($row['image'])) {
                                        $galleryImages[] = [
                                            'image' => $row['image'],
                                        ];
                                    }
                                }
                                $db->close();
                            } catch (Exception $e) {
                                // No gallery.
                            }
                        }
                    ?>

                    <section class="restaurant-photos" aria-label="Restaurant photos">
                        <div class="restaurant-photos-header">
                            <h2>Restaurant Photos</h2>
                        </div>

                        <?php if (!empty($galleryImages)): ?>
                            <div class="restaurant-photos-grid">
                                <?php foreach ($galleryImages as $idx => $g): ?>
                                    <?php
                                        $imgPath = $g['image'];
                                        // Fix image path for Restaurant Module subdirectory
                                        if (strpos($imgPath, 'images/') === 0) {
                                            $imgPath = '../' . $imgPath;
                                        } elseif (strpos($imgPath, 'assets/') === 0) {
                                            $imgPath = '../' . $imgPath;
                                        } elseif (strpos($imgPath, '../../') === 0) {
                                            $imgPath = str_replace('../../', '../', $imgPath);
                                        }
                                    ?>
                                    <div class="restaurant-photo-thumb"
                                        role="button"
                                        tabindex="0"
                                        data-src="<?php echo htmlspecialchars($imgPath); ?>"
                                        data-index="<?php echo (int)$idx; ?>"
                                        aria-label="Open photo">
                                        <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="Restaurant photo" loading="lazy">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="restaurant-photos-empty">
                                No additional photos available for this restaurant yet.
                            </div>
                        <?php endif; ?>
                    </section>

                    <div class="restaurant-photo-modal" id="restaurantPhotoModal" aria-hidden="true">
                        <div class="restaurant-photo-modal-inner">
                            <div class="restaurant-photo-modal-topbar">
                                <button class="restaurant-photo-modal-close" type="button" id="restaurantPhotoClose" aria-label="Close">✕</button>
                            </div>
                            <div class="restaurant-photo-modal-body">
                                <button type="button" class="restaurant-photo-modal-nav prev" id="restaurantPhotoPrev" aria-label="Previous photo">‹</button>
                                <img id="restaurantPhotoModalImg" src="" alt="Restaurant photo preview"/>
                                <button type="button" class="restaurant-photo-modal-nav next" id="restaurantPhotoNext" aria-label="Next photo">›</button>
                            </div>
                        </div>
                    </div>

                    <div class="experience-actions">
                        <a href="../Feedback Module/feedback-form.php?type=restaurant&id=<?php echo $restaurant['id']; ?>&name=<?php echo urlencode($restaurant['name']); ?>" class="btn btn-primary">Write a Review</a>
                        <a href="../Feedback Module/public-reviews.php?type=restaurant&id=<?php echo $restaurant['id']; ?>" class="btn btn-secondary">View Reviews</a>
                        <a href="restaurants.php" class="smooth-scroll btn btn-secondary">← Back to Restaurants</a>
                    </div>
                </article>
            <?php else: ?>
                <div class="not-found">
                    <h1>Restaurant Not Found</h1>
                    <p>The restaurant you're looking for does not exist.</p>
                    <a href="restaurants.php" class="btn btn-primary">← Back to Restaurants</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

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

    <script src="../assets/js/experience-details.js"></script>

    <script>
        (function () {
            const modal = document.getElementById('restaurantPhotoModal');
            const modalImg = document.getElementById('restaurantPhotoModalImg');
            const closeBtn = document.getElementById('restaurantPhotoClose');
            const prevBtn = document.getElementById('restaurantPhotoPrev');
            const nextBtn = document.getElementById('restaurantPhotoNext');

            if (!modal || !modalImg || !closeBtn || !prevBtn || !nextBtn) return;

            let currentIndex = -1;
            let gallery = [];

            function openFromThumb(thumb) {
                const src = thumb.getAttribute('data-src');

                if (!src) return;

                currentIndex = parseInt(thumb.getAttribute('data-index') || '-1', 10);
                modalImg.src = src;
                modal.classList.add('active');
                modal.setAttribute('aria-hidden', 'false');

                // Keep gallery array synced if needed
                if (!Array.isArray(gallery) || gallery.length === 0) {
                    gallery = Array.from(document.querySelectorAll('.restaurant-photo-thumb')).map(t => ({
                        src: t.getAttribute('data-src') || ''
                    }));
                }
            }

            function showAtIndex(index) {
                if (!gallery || gallery.length === 0) return;
                if (index < 0 || index >= gallery.length) {
                    index = (index + gallery.length) % gallery.length; // wrap
                }

                const item = gallery[index];
                if (!item || !item.src) return;

                currentIndex = index;
                modalImg.src = item.src;
            }

            function nextPhoto() {
                if (!gallery || gallery.length === 0) return;
                showAtIndex(currentIndex + 1);
            }

            function prevPhoto() {
                if (!gallery || gallery.length === 0) return;
                showAtIndex(currentIndex - 1);
            }

            function closeModal() {
                modal.classList.remove('active');
                modal.setAttribute('aria-hidden', 'true');
                modalImg.src = '';
            }

            prevBtn.addEventListener('click', prevPhoto);
            nextBtn.addEventListener('click', nextPhoto);

            document.querySelectorAll('.restaurant-photo-thumb').forEach(thumb => {
                thumb.addEventListener('click', function () {
                    openFromThumb(this);
                });

                thumb.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        openFromThumb(this);
                    }
                });
            });

            closeBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', function (e) {
                if (e.target === modal) closeModal();
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') closeModal();
                if (!modal.classList.contains('active')) return;
                if (e.key === 'ArrowRight') nextPhoto();
                if (e.key === 'ArrowLeft') prevPhoto();
            });
        })();
    </script>
</body>
</html>
