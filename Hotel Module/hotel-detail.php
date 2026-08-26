<?php
$dbFile = '../database.db';
$hotel = null;
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (file_exists($dbFile) && $id > 0) {
    try {
        $db = new SQLite3($dbFile);
        $stmt = $db->prepare('SELECT * FROM hotel_items WHERE id = ?');
        $stmt->bindValue(1, $id, SQLITE3_INTEGER);
        $result = $stmt->execute();
        $row = $result->fetchArray(SQLITE3_ASSOC);
        if ($row) {
            $hotel = $row;
        }
        $db->close();
    } catch (Exception $e) {
        // Keep fallback state and show "not found" UI.
    }
}

$pageTitle = $hotel ? ($hotel['name'] . ' - Tagum City') : 'Hotel Not Found - Tagum City';

$lat = null;
$lng = null;
$mapEmbedUrl = '';
$openMapUrl = '';
$emailLink = '';
$telLink = '';

if ($hotel) {
    if (isset($hotel['latitude']) && isset($hotel['longitude']) && is_numeric($hotel['latitude']) && is_numeric($hotel['longitude'])) {
        $lat = (float) $hotel['latitude'];
        $lng = (float) $hotel['longitude'];

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
    } elseif (!empty($hotel['location'])) {
        $query = rawurlencode($hotel['location'] . ', Tagum City');
        $openMapUrl = 'https://www.openstreetmap.org/search?query=' . $query;
    }

    if (!empty($hotel['email'])) {
        $emailLink = 'mailto:' . rawurlencode(trim($hotel['email']));
    }

    if (!empty($hotel['contact'])) {
        $digitsOnly = preg_replace('/[^0-9+]/', '', $hotel['contact']);
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
        .hotel-detail-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 1.25rem;
            margin: 1.5rem 0;
        }

        .hotel-panel {
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 1rem 1.1rem;
        }

        .hotel-panel h3 {
            margin: 0 0 0.75rem;
            color: #1d5a3d;
        }

        .hotel-description {
            color: #374151;
            line-height: 1.7;
            font-size: 1rem;
        }

        .hotel-price-tag {
            display: inline-block;
            padding: 0.45rem 0.8rem;
            border-radius: 999px;
            background: #e8f5ee;
            color: #1d5a3d;
            font-weight: 700;
            font-size: 1.05rem;
            margin-bottom: 0.6rem;
        }

        .hotel-meta-list {
            display: grid;
            gap: 0.6rem;
            color: #374151;
            font-size: 0.98rem;
        }

        .hotel-meta-list strong {
            color: #111827;
        }

        .hotel-meta-list a {
            color: #1d5a3d;
            font-weight: 600;
            text-decoration: none;
        }

        .hotel-meta-list a:hover {
            text-decoration: underline;
        }

        .hotel-map-wrap {
            margin-top: 1.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            background: #ffffff;
        }

        .hotel-map-wrap iframe {
            width: 100%;
            height: 320px;
            border: 0;
            display: block;
        }

        .hotel-map-fallback {
            padding: 1rem 1.1rem;
            color: #374151;
            line-height: 1.6;
        }

        .hotel-map-actions {
            padding: 0.8rem 1.1rem 1rem;
            border-top: 1px solid #e5e7eb;
        }

        .hotel-map-actions a {
            color: #1d5a3d;
            font-weight: 600;
            text-decoration: none;
        }

        .hotel-map-actions a:hover {
            text-decoration: underline;
        }

        .hotel-photos {
            margin-top: 1.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #ffffff;
            overflow: hidden;
        }

        .hotel-photos-header {
            padding: 1rem 1.1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .hotel-photos-header h2 {
            margin: 0;
            font-size: 1.25rem;
            color: #1d5a3d;
        }

        .hotel-photos-grid {
            padding: 1rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 0.75rem;
        }

        .hotel-photo-thumb {
            border-radius: 10px;
            overflow: hidden;
            border: 1px solid #eef2f7;
            cursor: pointer;
            height: 200px;
        }

        .hotel-photo-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .hotel-photos-empty {
            padding: 1.25rem 1.1rem;
            color: #374151;
            line-height: 1.6;
        }

        /* Lightbox */
        .hotel-photo-modal {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.72);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 1rem;
        }

        .hotel-photo-modal.active { display: flex; }

        .hotel-photo-modal-inner {
            width: min(900px, 100%);
            background: #0b1220;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
        }

        .hotel-photo-modal-topbar {
            padding: 0.75rem 0.9rem;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            border-bottom: 1px solid rgba(255,255,255,0.12);
        }

        .hotel-photo-modal-close {
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

        .hotel-photo-modal-body {
            position: relative;
        }

        .hotel-photo-modal-body img {
            width: 100%;
            height: min(70vh, 640px);
            object-fit: contain;
            background: #000;
            display: block;
        }

        .hotel-photo-modal-nav {
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

        .hotel-photo-modal-nav:hover {
            background: rgba(255,255,255,0.22);
        }

        .hotel-photo-modal-nav:focus {
            outline: 2px solid rgba(255,255,255,0.35);
            outline-offset: 2px;
        }

        .hotel-photo-modal-nav.prev { left: 14px; }
        .hotel-photo-modal-nav.next { right: 14px; }

        @media (max-width: 900px) {
            .hotel-detail-grid {
                grid-template-columns: 1fr;
            }
            .hotel-photo-modal-nav.prev { left: 10px; }
            .hotel-photo-modal-nav.next { right: 10px; }
        }
    </style>
</head>
<body>
<?php include '../navbar.php'; ?>


    <main class="experience-single">
        <div class="container">
            <?php if ($hotel): ?>
                <article class="experience-detail active">
                    <header class="experience-header">
                        <h1><?php echo htmlspecialchars($hotel['name']); ?></h1>
                        <div class="experience-meta">
                            <span class="exp-type"><?php echo htmlspecialchars($hotel['category'] ?? 'Hotel'); ?></span>
                        </div>
                    </header>

                    <?php if (!empty($hotel['image'])): ?>
                        <div class="experience-image">
                            <?php
                                $detailImagePath = $hotel['image'];
                                if (strpos($detailImagePath, '../../') === 0) {
                                    $detailImagePath = str_replace('../../', '../', $detailImagePath);
                                } elseif (strpos($detailImagePath, '../assets/images/hotels/') !== 0 && strpos($detailImagePath, 'assets/images/hotels/') !== false) {
                                    $detailImagePath = '../' . ltrim($detailImagePath, '/');
                                }
                            ?>
                            <img src="<?php echo htmlspecialchars($detailImagePath); ?>" alt="<?php echo htmlspecialchars($hotel['name']); ?>" loading="lazy">
                        </div>
                    <?php endif; ?>

                    <div class="hotel-detail-grid">
                        <div class="hotel-panel">
                            <h3>Description</h3>
                            <p class="hotel-description"><?php echo nl2br(htmlspecialchars($hotel['description'] ?? 'No description available.')); ?></p>
                            <?php if (!empty($hotel['information'])): ?>
                                <h3 style="margin-top: 1rem;">Information</h3>
                                <p class="hotel-description"><?php echo nl2br(htmlspecialchars($hotel['information'])); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="hotel-panel">
                            <h3>Hotel Details</h3>
                            <div class="hotel-price-tag">
                                PHP <?php echo htmlspecialchars($hotel['price'] ?? 'N/A'); ?>
                            </div>
                            <div class="hotel-meta-list">
                                <div><strong>Category:</strong> <?php echo htmlspecialchars($hotel['category'] ?? 'N/A'); ?></div>
                                <div><strong>Location:</strong> <?php echo htmlspecialchars($hotel['location'] ?? 'Location not available'); ?></div>
                                <?php if (!empty($hotel['contact'])): ?>
                                    <div>
                                        <strong>Contact:</strong>
                                        <?php if (!empty($telLink)): ?>
                                            <a href="<?php echo htmlspecialchars($telLink); ?>"><?php echo htmlspecialchars($hotel['contact']); ?></a>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($hotel['contact']); ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($hotel['email'])): ?>
                                    <div>
                                        <strong>Email:</strong>
                                        <?php if (!empty($emailLink)): ?>
                                            <a href="<?php echo htmlspecialchars($emailLink); ?>"><?php echo htmlspecialchars($hotel['email']); ?></a>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($hotel['email']); ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($lat !== null && $lng !== null): ?>
                                    <div><strong>Coordinates:</strong> <?php echo htmlspecialchars(number_format($lat, 6)); ?>, <?php echo htmlspecialchars(number_format($lng, 6)); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="hotel-map-wrap">
                        <?php if (!empty($mapEmbedUrl)): ?>
                            <iframe
                                src="<?php echo htmlspecialchars($mapEmbedUrl); ?>"
                                loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"
                                title="Map location of <?php echo htmlspecialchars($hotel['name']); ?>">
                            </iframe>
                            <div class="hotel-map-actions">
                                <a href="<?php echo htmlspecialchars($openMapUrl); ?>" target="_blank" rel="noopener noreferrer">Open full map</a>
                            </div>
                        <?php else: ?>
                            <div class="hotel-map-fallback">
                                Map coordinates are not available for this hotel yet.
                                <?php if (!empty($hotel['location'])): ?>
                                    <br>
                                    Location: <?php echo htmlspecialchars($hotel['location']); ?>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($openMapUrl)): ?>
                                <div class="hotel-map-actions">
                                    <a href="<?php echo htmlspecialchars($openMapUrl); ?>" target="_blank" rel="noopener noreferrer">Search this location on map</a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>

                    <?php
                        // Load hotel gallery images from `hotel_gallery` table.
                        $galleryImages = [];
                        if ($hotel && !empty($hotel['id'])) {
                            try {
                                require_once dirname(__DIR__) . '/database/setup_hotel_gallery.php';
                                // Ensure table exists (safe idempotent).
                                ensureHotelGalleryTable();

                                $db = new SQLite3('../database.db');
                                $stmt = $db->prepare('SELECT image FROM hotel_gallery WHERE hotel_id = ? ORDER BY sort_order ASC, id ASC');
                                $stmt->bindValue(1, (int)$hotel['id'], SQLITE3_INTEGER);
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

                    <section class="hotel-photos" aria-label="Hotel photos">
                        <div class="hotel-photos-header">
                            <h2>Hotel Photos</h2>
                        </div>

                        <?php if (!empty($galleryImages)): ?>
                            <div class="hotel-photos-grid">
                                <?php foreach ($galleryImages as $idx => $g): ?>
                                    <?php
                                        $imgPath = $g['image'];
                                        if (strpos($imgPath, '../../') === 0) {
                                            $imgPath = str_replace('../../', '../', $imgPath);
                                        }
                                        // Normalize common stored paths
                                        if (strpos($imgPath, 'assets/images/hotels/') === 0) {
                                            $imgPath = '../' . ltrim($imgPath, '/');
                                        }
                                    ?>
                                    <div class="hotel-photo-thumb"
                                        role="button"
                                        tabindex="0"
                                        data-src="<?php echo htmlspecialchars($imgPath); ?>"
                                        data-index="<?php echo (int)$idx; ?>"
                                        aria-label="Open photo">
                                        <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="Hotel photo" loading="lazy">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="hotel-photos-empty">
                                No additional photos available for this hotel yet.
                            </div>
                        <?php endif; ?>
                    </section>

                    <div class="hotel-photo-modal" id="hotelPhotoModal" aria-hidden="true">
                        <div class="hotel-photo-modal-inner">
                            <div class="hotel-photo-modal-topbar">
                                <button class="hotel-photo-modal-close" type="button" id="hotelPhotoClose" aria-label="Close">✕</button>
                            </div>
                            <div class="hotel-photo-modal-body">
                                <button type="button" class="hotel-photo-modal-nav prev" id="hotelPhotoPrev" aria-label="Previous photo">‹</button>
                                <img id="hotelPhotoModalImg" src="" alt="Hotel photo preview"/>
                                <button type="button" class="hotel-photo-modal-nav next" id="hotelPhotoNext" aria-label="Next photo">›</button>
                            </div>
                        </div>
                    </div>

                    <div class="experience-actions">
                        <a href="../Feedback Module/feedback-form.php?type=hotel&id=<?php echo $hotel['id']; ?>&name=<?php echo urlencode($hotel['name']); ?>" class="btn btn-primary">Write a Review</a>
                        <a href="../Feedback Module/public-reviews.php?type=hotel&id=<?php echo $hotel['id']; ?>" class="btn btn-secondary">View Reviews</a>
                        <a href="hotels.php" class="smooth-scroll btn btn-secondary">← Back to Hotels</a>
                    </div>
                </article>
            <?php else: ?>
                <div class="not-found">
                    <h1>Hotel Not Found</h1>
                    <p>The hotel you're looking for does not exist.</p>
                    <a href="hotels.php" class="btn btn-primary">← Back to Hotels</a>
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
            const modal = document.getElementById('hotelPhotoModal');
            const modalImg = document.getElementById('hotelPhotoModalImg');
            const closeBtn = document.getElementById('hotelPhotoClose');
            const prevBtn = document.getElementById('hotelPhotoPrev');
            const nextBtn = document.getElementById('hotelPhotoNext');

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
                    gallery = Array.from(document.querySelectorAll('.hotel-photo-thumb')).map(t => ({
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

            document.querySelectorAll('.hotel-photo-thumb').forEach(thumb => {
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

