<?php
require_once __DIR__ . '/../database/setup_feedback.php';
ensureFeedbackTable();

$establishmentType = $_GET['type'] ?? 'hotel';
$establishmentId = $_GET['id'] ?? 0;
$establishmentName = $_GET['name'] ?? 'Establishment';

// Validate establishment type
if (!in_array($establishmentType, ['hotel', 'restaurant'])) {
    $establishmentType = 'hotel';
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $overallRating = $_POST['overall_rating'] ?? '';
    $comment = $_POST['comment'] ?? '';
    $anonymous = isset($_POST['anonymous']) ? 1 : 0;
    $email = $_POST['email'] ?? '';
    $recommendation = $_POST['recommendation'] ?? '';
    $dataPrivacyConsent = isset($_POST['data_privacy_consent']) ? 1 : 0;

    // Validation
    if (empty($overallRating) || !is_numeric($overallRating) || $overallRating < 1 || $overallRating > 5) {
        $errors[] = 'Please select an overall rating.';
    }

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($recommendation)) {
        $errors[] = 'Please select a recommendation option.';
    }

    if (!$dataPrivacyConsent) {
        $errors[] = 'You must agree to the Data Privacy consent before submitting.';
    }

    if (empty($errors)) {
        $dbFile = __DIR__ . '/../database.db';
        $db = new SQLite3($dbFile);

        $stmt = $db->prepare('
            INSERT INTO feedback 
            (establishment_type, establishment_id, overall_rating, comment, anonymous, email, recommendation, data_privacy_consent, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, "Pending Review")
        ');

        $stmt->bindValue(1, $establishmentType, SQLITE3_TEXT);
        $stmt->bindValue(2, (int)$establishmentId, SQLITE3_INTEGER);
        $stmt->bindValue(3, (int)$overallRating, SQLITE3_INTEGER);
        $stmt->bindValue(4, $comment, SQLITE3_TEXT);
        $stmt->bindValue(5, $anonymous, SQLITE3_INTEGER);
        $stmt->bindValue(6, $email, SQLITE3_TEXT);
        $stmt->bindValue(7, $recommendation, SQLITE3_TEXT);
        $stmt->bindValue(8, $dataPrivacyConsent, SQLITE3_INTEGER);

        $result = $stmt->execute();
        $db->close();

        if ($result) {
            $success = true;
        } else {
            $errors[] = 'An error occurred while submitting your feedback. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share Your Feedback - <?php echo htmlspecialchars($establishmentName); ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/mobile-navbar.css">
    <link rel="stylesheet" href="../css/feedback.css">
    <script src="../js/navbar.js"></script>
</head>
<body class="feedback-page">
<?php include __DIR__ . '/../navbar.php'; ?>

<main class="feedback-main">
    <div class="feedback-container">
        <div class="feedback-header">
            <h1>Share Your Feedback</h1>
            <p class="feedback-subtitle">Your opinion helps us improve our services</p>
            <p class="establishment-name"><?php echo htmlspecialchars($establishmentName); ?></p>
        </div>

        <?php if ($success): ?>
            <div class="feedback-success">
                <div class="success-icon">✓</div>
                <h2>Thank You</h2>
                <p>Thank you for sharing your feedback. Your response has been received.</p>
                <a href="<?php echo $establishmentType === 'hotel' ? '../Hotel Module/hotels.php' : '../Restaurant Module/restaurants.php'; ?>" class="btn btn-primary">Back to <?php echo ucfirst($establishmentType); ?>s</a>
            </div>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="feedback-errors">
                    <strong>Please correct the following:</strong>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" class="feedback-form" id="feedbackForm">
                <input type="hidden" name="establishment_type" value="<?php echo htmlspecialchars($establishmentType); ?>">
                <input type="hidden" name="establishment_id" value="<?php echo (int)$establishmentId; ?>">

                <!-- Overall Rating -->
                <div class="form-section">
                    <label class="form-label required">Overall Satisfaction <span class="required-star">*</span></label>
                    <div class="star-rating" id="starRating">
                        <input type="radio" name="overall_rating" value="5" id="star5" required>
                        <label for="star5" class="star" title="5 stars">★</label>
                        <input type="radio" name="overall_rating" value="4" id="star4">
                        <label for="star4" class="star" title="4 stars">★</label>
                        <input type="radio" name="overall_rating" value="3" id="star3">
                        <label for="star3" class="star" title="3 stars">★</label>
                        <input type="radio" name="overall_rating" value="2" id="star2">
                        <label for="star2" class="star" title="2 stars">★</label>
                        <input type="radio" name="overall_rating" value="1" id="star1">
                        <label for="star1" class="star" title="1 star">★</label>
                    </div>
                    <p class="rating-text" id="ratingText">Select your rating</p>
                </div>

                <!-- Comment -->
                <div class="form-section">
                    <label class="form-label" for="comment">Comment (Optional)</label>
                    <textarea 
                        id="comment" 
                        name="comment" 
                        class="form-textarea" 
                        rows="4"
                        placeholder="Tell us about your experience. Your comments help us improve our services."
                    ></textarea>
                </div>

                <!-- Anonymous -->
                <div class="form-section">
                    <label class="checkbox-label">
                        <input type="checkbox" name="anonymous" id="anonymous">
                        <span class="checkbox-text">Submit Anonymously</span>
                    </label>
                    <p class="form-help">If checked, your review will display as "Anonymous" to the public.</p>
                </div>

                <!-- Email -->
                <div class="form-section">
                    <label class="form-label required" for="email">Email Address <span class="required-star">*</span></label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-input" 
                        required
                        placeholder="your.email@example.com"
                    >
                    <p class="form-help">Your email address is required to verify your feedback, reduce fraudulent or duplicate submissions, and maintain the integrity of our review system. Your email address will never be displayed publicly.</p>
                </div>

                <!-- Recommendation -->
                <div class="form-section">
                    <label class="form-label required">Would you recommend this <?php echo $establishmentType; ?> to others? <span class="required-star">*</span></label>
                    <div class="recommendation-options">
                        <label class="radio-label">
                            <input type="radio" name="recommendation" value="Definitely Yes" required>
                            <span class="radio-text">Definitely Yes</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="recommendation" value="Probably Yes">
                            <span class="radio-text">Probably Yes</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="recommendation" value="Not Sure">
                            <span class="radio-text">Not Sure</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="recommendation" value="Probably No">
                            <span class="radio-text">Probably No</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="recommendation" value="Definitely No">
                            <span class="radio-text">Definitely No</span>
                        </label>
                    </div>
                </div>

                <!-- Data Privacy Consent -->
                <div class="form-section privacy-section">
                    <label class="checkbox-label required">
                        <input type="checkbox" name="data_privacy_consent" id="dataPrivacyConsent" required>
                        <span class="checkbox-text privacy-text">
                            I have read and agree to the collection and processing of my personal information in accordance with the Data Privacy Act of 2012 (Republic Act No. 10173). I understand that my email address will be used solely for feedback verification, moderation, and communication regarding my submitted feedback when necessary. My personal information will remain confidential and will never be displayed publicly.
                        </span>
                    </label>
                </div>

                <!-- Submit Button -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary feedback-submit-btn" id="submitBtn" disabled>Submit Feedback</button>
                    <a href="<?php echo $establishmentType === 'hotel' ? '../Hotel Module/hotels.php' : '../Restaurant Module/restaurants.php'; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        <?php endif; ?>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Star rating interaction
    const starRating = document.getElementById('starRating');
    const ratingText = document.getElementById('ratingText');
    const stars = starRating.querySelectorAll('.star');
    const ratingTexts = ['', 'Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];

    stars.forEach((star, index) => {
        star.addEventListener('click', function() {
            const value = 5 - index;
            document.getElementById('star' + value).checked = true;
            updateStars(value);
            ratingText.textContent = ratingTexts[value];
        });

        star.addEventListener('mouseenter', function() {
            const value = 5 - index;
            highlightStars(value);
        });
    });

    starRating.addEventListener('mouseleave', function() {
        const checked = starRating.querySelector('input:checked');
        const value = checked ? parseInt(checked.value) : 0;
        updateStars(value);
    });

    function highlightStars(value) {
        stars.forEach((star, index) => {
            if (5 - index <= value) {
                star.classList.add('hovered');
            } else {
                star.classList.remove('hovered');
            }
        });
    }

    function updateStars(value) {
        stars.forEach((star, index) => {
            if (5 - index <= value) {
                star.classList.add('active');
            } else {
                star.classList.remove('active', 'hovered');
            }
        });
    }

    // Enable/disable submit button based on privacy consent
    const privacyConsent = document.getElementById('dataPrivacyConsent');
    const submitBtn = document.getElementById('submitBtn');

    privacyConsent.addEventListener('change', function() {
        submitBtn.disabled = !this.checked;
    });

    // Form validation
    const form = document.getElementById('feedbackForm');
    form.addEventListener('submit', function(e) {
        const rating = form.querySelector('input[name="overall_rating"]:checked');
        if (!rating) {
            e.preventDefault();
            alert('Please select an overall rating.');
            return false;
        }
    });
});
</script>
</body>
</html>
