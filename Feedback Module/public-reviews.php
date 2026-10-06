<?php
require_once dirname(__DIR__) . '/includes/database_path.php';
require_once __DIR__ . '/../database/setup_feedback.php';
ensureFeedbackTable();

$establishmentType = $_GET['type'] ?? 'hotel';
$establishmentId = $_GET['id'] ?? 0;

// Validate establishment type
if (!in_array($establishmentType, ['hotel', 'restaurant'])) {
    $establishmentType = 'hotel';
}

$dbFile = appDatabasePath();
$db = new SQLite3($dbFile);

// Fetch only approved reviews
if ($establishmentId > 0) {
    // Show reviews for specific establishment
    $stmt = $db->prepare('
        SELECT id, overall_rating, comment, anonymous, recommendation, created_at
        FROM feedback
        WHERE establishment_type = ? 
        AND establishment_id = ? 
        AND status = "Approved"
        ORDER BY created_at DESC
    ');

    $stmt->bindValue(1, $establishmentType, SQLITE3_TEXT);
    $stmt->bindValue(2, (int)$establishmentId, SQLITE3_INTEGER);
    $result = $stmt->execute();
} else {
    // Show all approved reviews
    $stmt = $db->prepare('
        SELECT id, overall_rating, comment, anonymous, recommendation, created_at
        FROM feedback
        WHERE status = "Approved"
        ORDER BY created_at DESC
    ');
    $result = $stmt->execute();
}

$reviews = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $reviews[] = $row;
}

$db->close();

// Calculate overall satisfaction score
$averageRating = 0;
$totalReviews = count($reviews);

if ($totalReviews > 0) {
    $totalRating = 0;
    foreach ($reviews as $review) {
        $totalRating += $review['overall_rating'];
    }
    $averageRating = round($totalRating / $totalReviews, 1);
}

// Calculate NPS (Net Promoter Score)
$nps = 0;
if ($totalReviews > 0) {
    $promoters = 0;
    $detractors = 0;
    
    foreach ($reviews as $review) {
        if ($review['recommendation'] === 'Definitely Yes' || $review['recommendation'] === 'Probably Yes') {
            $promoters++;
        } elseif ($review['recommendation'] === 'Probably No' || $review['recommendation'] === 'Definitely No') {
            $detractors++;
        }
    }
    
    $nps = round((($promoters - $detractors) / $totalReviews) * 100);
}

function getRecommendationClass($recommendation) {
    if (in_array($recommendation, ['Definitely Yes', 'Probably Yes'])) {
        return 'positive';
    } elseif ($recommendation === 'Not Sure') {
        return 'neutral';
    } else {
        return 'negative';
    }
}

function renderStars($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '<span class="star">★</span>';
        } else {
            $stars .= '<span class="star empty">★</span>';
        }
    }
    return $stars;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Reviews</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/mobile-navbar.css">
    <link rel="stylesheet" href="../css/feedback.css">
    <script src="../js/navbar.js"></script>
</head>
<body class="reviews-page">
<?php include __DIR__ . '/../navbar.php'; ?>

<main class="reviews-section">
    <div class="reviews-header">
        <h2>Customer Reviews</h2>
        <p>See what our customers are saying</p>
        
        <?php if ($totalReviews > 0): ?>
            <div class="reviews-summary">
                <div class="summary-item">
                    <div class="summary-value">
                        <span class="summary-rating"><?php echo renderStars(round($averageRating)); ?></span>
                        <span class="summary-number"><?php echo $averageRating; ?> / 5</span>
                    </div>
                    <div class="summary-label">Overall Satisfaction</div>
                    <div class="summary-count">Based on <?php echo $totalReviews; ?> approved reviews</div>
                </div>
                <div class="summary-item">
                    <div class="summary-value">
                        <span class="summary-nps"><?php echo $nps >= 0 ? '+' : ''; ?><?php echo $nps; ?></span>
                    </div>
                    <div class="summary-label">Net Promoter Score</div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if (empty($reviews)): ?>
        <div class="no-reviews">
            <p>No reviews yet. Be the first to share your experience!</p>
            <a href="feedback-form.php?type=<?php echo $establishmentType; ?>&id=<?php echo $establishmentId; ?>" class="btn btn-primary">Write a Review</a>
        </div>
    <?php else: ?>
        <div class="reviews-grid">
            <?php foreach ($reviews as $review): ?>
                <div class="review-card">
                    <div class="review-header">
                        <div class="reviewer-name">
                            <?php echo $review['anonymous'] ? 'Anonymous' : 'Verified Customer'; ?>
                        </div>
                        <div class="review-date">
                            <?php echo date('F j, Y', strtotime($review['created_at'])); ?>
                        </div>
                    </div>
                    
                    <div class="review-rating">
                        <?php echo renderStars($review['overall_rating']); ?>
                    </div>
                    
                    <?php if (!empty($review['comment'])): ?>
                        <div class="review-comment">
                            <?php echo nl2br(htmlspecialchars($review['comment'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="review-recommendation <?php echo getRecommendationClass($review['recommendation']); ?>">
                        Recommendation: <?php echo htmlspecialchars($review['recommendation']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="reviews-actions">
            <a href="feedback-form.php?type=<?php echo $establishmentType; ?>&id=<?php echo $establishmentId; ?>" class="btn btn-primary">Write a Review</a>
        </div>
    <?php endif; ?>
</main>

<style>
.reviews-summary {
    display: flex;
    gap: 2rem;
    justify-content: center;
    margin-top: 2rem;
    flex-wrap: wrap;
}

.summary-item {
    text-align: center;
    padding: 1.5rem 2rem;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    min-width: 200px;
}

.summary-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1d5a3d;
    margin-bottom: 0.5rem;
}

.summary-rating {
    display: flex;
    gap: 0.25rem;
    font-size: 1.5rem;
}

.summary-number {
    margin-left: 0.5rem;
}

.summary-nps {
    font-size: 2rem;
}

.summary-label {
    font-size: 0.875rem;
    color: #6b7280;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.summary-count {
    font-size: 0.875rem;
    color: #9ca3af;
    margin-top: 0.25rem;
}

.reviews-actions {
    text-align: center;
    margin-top: 3rem;
}

@media (max-width: 768px) {
    .reviews-summary {
        flex-direction: column;
        gap: 1rem;
    }
    
    .summary-item {
        width: 100%;
    }
}
</style>
</body>
</html>
