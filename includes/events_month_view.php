<?php
/**
 * Renders events grouped by month with expand/collapse.
 * Expects: $events (array), optional $detailBase (default: event-detail.php)
 */
if (!function_exists('groupEventsByMonth')) {
    require_once dirname(__DIR__) . '/includes/events_helpers.php';
}

$detailBase = $detailBase ?? 'event-detail.php';
$eventsByMonth = groupEventsByMonth($events);
$openMonthKey = getDefaultOpenMonthKey($eventsByMonth);
?>

<?php if (!empty($eventsByMonth)): ?>
<div class="events-by-month">
    <?php foreach ($eventsByMonth as $monthKey => $monthData): ?>
        <?php
        $isOpen = ($monthKey === $openMonthKey);
        $eventNames = array_map(function ($event) {
            return $event['name'] ?? 'Untitled Event';
        }, $monthData['events']);
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
                    <span class="month-count"><?php echo $count; ?> event<?php echo $count === 1 ? '' : 's'; ?></span>
                    <span class="month-chevron" aria-hidden="true">▼</span>
                </div>
                <p class="month-preview"><?php echo htmlspecialchars($preview); ?></p>
            </button>

            <div class="month-events"<?php echo $isOpen ? '' : ' hidden'; ?>>
                <div class="cuisine-grid events-month-grid">
                    <?php foreach ($monthData['events'] as $site): ?>
                        <?php 
                        $imagePath = fixEventImagePath($site['image'] ?? '');
                        // Fix image path for Explore Module subdirectory
                        if (strpos($imagePath, 'images/') === 0) {
                            $imagePath = '../' . $imagePath;
                        } elseif (strpos($imagePath, 'assets/') === 0) {
                            $imagePath = '../' . $imagePath;
                        }
                        ?>
                        <div class="cuisine-category event-month-card">
                            <?php if (!empty($imagePath)): ?>
                                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($site['name']); ?>" class="category-image" onerror="this.style.display='none'">
                            <?php endif; ?>
                            <h3><?php echo htmlspecialchars($site['name']); ?></h3>
                            <?php if (!empty($site['event_date'])): ?>
                                <p class="item-count event-date-badge"><?php echo htmlspecialchars(formatEventDate($site['event_date'])); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($site['location'])): ?>
                                <p class="event-location">📍 <?php echo htmlspecialchars($site['location']); ?></p>
                            <?php endif; ?>
                            <a href="<?php echo htmlspecialchars($detailBase); ?>?id=<?php echo (int) $site['id']; ?>" class="read-more-btn btn btn-primary">Read More</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
