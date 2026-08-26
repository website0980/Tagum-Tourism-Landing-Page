<?php
require_once __DIR__ . '/../database/setup_feedback.php';
require_once __DIR__ . '/config.php';
requireAuth();
 
ensureFeedbackTable();
 
$feedbackId = $_GET['id'] ?? 0;
 
$dbFile = __DIR__ . '/../database.db';
$db = new SQLite3($dbFile);
 
$stmt = $db->prepare('SELECT * FROM feedback WHERE id = ?');
$stmt->bindValue(1, (int)$feedbackId, SQLITE3_INTEGER);
$result = $stmt->execute();
 
$feedback = $result->fetchArray(SQLITE3_ASSOC);
$db->close();
 
if (!$feedback) {
    echo '<p>Feedback not found.</p>';
    exit;
}
 
function renderStars($rating) {
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $stars .= '★';
        } else {
            $stars .= '☆';
        }
    }
    return $stars;
}
?>
<div class="feedback-detail">
    <div class="detail-section">
        <h3>Establishment Information</h3>
        <div class="detail-row">
            <span class="detail-label">Type:</span>
            <span class="detail-value"><?php echo ucfirst($feedback['establishment_type']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">ID:</span>
            <span class="detail-value"><?php echo $feedback['establishment_id']; ?></span>
        </div>
    </div>
 
    <div class="detail-section">
        <h3>Customer Information</h3>
        <div class="detail-row">
            <span class="detail-label">Email:</span>
            <span class="detail-value"><?php echo htmlspecialchars($feedback['email']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Anonymous:</span>
            <span class="detail-value"><?php echo $feedback['anonymous'] ? 'Yes' : 'No'; ?></span>
        </div>
    </div>
 
    <div class="detail-section">
        <h3>Feedback Details</h3>
        <div class="detail-row">
            <span class="detail-label">Overall Rating:</span>
            <span class="detail-value rating-stars"><?php echo renderStars($feedback['overall_rating']); ?> (<?php echo $feedback['overall_rating']; ?>/5)</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Recommendation:</span>
            <span class="detail-value"><?php echo htmlspecialchars($feedback['recommendation']); ?></span>
        </div>
        <?php if (!empty($feedback['comment'])): ?>
            <div class="detail-row">
                <span class="detail-label">Comment:</span>
                <span class="detail-value comment-text"><?php echo nl2br(htmlspecialchars($feedback['comment'])); ?></span>
            </div>
        <?php endif; ?>
    </div>
 
    <div class="detail-section">
        <h3>Moderation Information</h3>
        <div class="detail-row">
            <span class="detail-label">Current Status:</span>
            <span class="detail-value status-badge status-<?php echo strtolower(str_replace(' ', '-', $feedback['status'])); ?>"><?php echo htmlspecialchars($feedback['status']); ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Submitted:</span>
            <span class="detail-value"><?php echo date('F j, Y g:i A', strtotime($feedback['created_at'])); ?></span>
        </div>
        <?php if ($feedback['reviewed_at']): ?>
            <div class="detail-row">
                <span class="detail-label">Reviewed:</span>
                <span class="detail-value"><?php echo date('F j, Y g:i A', strtotime($feedback['reviewed_at'])); ?></span>
            </div>
        <?php endif; ?>
        <?php if ($feedback['reviewed_by']): ?>
            <div class="detail-row">
                <span class="detail-label">Reviewed By:</span>
                <span class="detail-value"><?php echo htmlspecialchars($feedback['reviewed_by']); ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($feedback['moderator_notes'])): ?>
            <div class="detail-row">
                <span class="detail-label">Moderator Notes:</span>
                <span class="detail-value comment-text"><?php echo nl2br(htmlspecialchars($feedback['moderator_notes'])); ?></span>
            </div>
        <?php endif; ?>
    </div>
 
    <?php if ($feedback['status'] === 'Pending Review'): ?>
        <div class="detail-section">
            <h3>Moderation Actions</h3>
            <form method="POST" action="feedback-management.php">
                <input type="hidden" name="feedback_id" value="<?php echo $feedback['id']; ?>">
                <input type="hidden" name="action" id="actionInput">
                
 
                <div class="form-group">
                    <label for="moderator_notes">Moderator Notes (Internal Only):</label>
                    <textarea id="moderator_notes" name="moderator_notes" rows="3" placeholder="Add internal notes about this review..."></textarea>
                </div>
                
                <div class="action-buttons">
                    <button type="submit" name="action" value="approve" class="btn btn-success">Approve Review</button>
                    <button type="submit" name="action" value="reject" class="btn btn-danger">Reject Review</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>
 
<style>
.feedback-detail {
    max-height: 70vh;
    overflow-y: auto;
}
 
.detail-section {
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid #e5e7eb;
}
 
.detail-section:last-child {
    border-bottom: none;
}
 
.detail-section h3 {
    font-size: 1.1rem;
    color: #1d5a3d;
    margin-bottom: 1rem;
    font-weight: 600;
}
 
.detail-row {
    display: flex;
    margin-bottom: 0.75rem;
}
 
.detail-label {
    font-weight: 600;
    color: #374151;
    min-width: 150px;
}
 
.detail-value {
    color: #6b7280;
    flex: 1;
}
 
.rating-stars {
    color: #fbbf24;
    font-size: 1.2rem;
}
 
.comment-text {
    white-space: pre-wrap;
    line-height: 1.6;
}
 
.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
}
 
.status-pending-review {
    background: #fef3c7;
    color: #92400e;
}
 
.status-approved {
    background: #d1fae5;
    color: #065f46;
}
 
.status-rejected {
    background: #fee2e2;
    color: #991b1b;
}
 
.form-group {
    margin-bottom: 1rem;
}
 
.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #374151;
}
 
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-family: inherit;
    resize: vertical;
}
 
.action-buttons {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
}
 
.btn-success {
    background: #10b981;
    color: white;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s ease;
}
 
.btn-success:hover {
    background: #059669;
}
 
.btn-danger {
    background: #ef4444;
    color: white;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s ease;
}
 
.btn-danger:hover {
    background: #dc2626;
}
</style>
 
<script>
function submitAction(action) {
    document.getElementById('actionInput').value = action;
    document.querySelector('form').submit();
}
</script>