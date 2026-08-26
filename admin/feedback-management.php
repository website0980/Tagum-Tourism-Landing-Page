<?php
require_once __DIR__ . '/../database/setup_feedback.php';
require_once __DIR__ . '/config.php';
requireAuth();

ensureFeedbackTable();

$dbFile = __DIR__ . '/../database.db';
$db = new SQLite3($dbFile);

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['bulk_action'];
    $selectedIds = $_POST['selected_ids'] ?? [];
    
    if (!empty($selectedIds) && in_array($action, ['approve', 'reject'])) {
        $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
        $status = $action === 'approve' ? 'Approved' : 'Rejected';
        
        $stmt = $db->prepare("
            UPDATE feedback 
            SET status = ?, reviewed_by = 'Admin', reviewed_at = CURRENT_TIMESTAMP
            WHERE id IN ($placeholders)
        ");
        
        $stmt->bindValue(1, $status, SQLITE3_TEXT);
        foreach ($selectedIds as $i => $id) {
            $stmt->bindValue($i + 2, (int)$id, SQLITE3_INTEGER);
        }
        $stmt->execute();
    }
}

// Handle individual actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $feedbackId = $_POST['feedback_id'] ?? 0;
    $action = $_POST['action'];
    $moderatorNotes = $_POST['moderator_notes'] ?? '';
    
    if (in_array($action, ['approve', 'reject'])) {
        $status = $action === 'approve' ? 'Approved' : 'Rejected';
        
        $stmt = $db->prepare('
            UPDATE feedback 
            SET status = ?, moderator_notes = ?, reviewed_by = "Admin", reviewed_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ');
        
        $stmt->bindValue(1, $status, SQLITE3_TEXT);
        $stmt->bindValue(2, $moderatorNotes, SQLITE3_TEXT);
        $stmt->bindValue(3, (int)$feedbackId, SQLITE3_INTEGER);
        $stmt->execute();
    }
}

// Apply filters
$where = ['1=1'];
$params = [];

if (!empty($_GET['establishment_type'])) {
    $where[] = 'establishment_type = ?';
    $params[] = $_GET['establishment_type'];
}

if (!empty($_GET['status'])) {
    $where[] = 'status = ?';
    $params[] = $_GET['status'];
}

if (!empty($_GET['rating'])) {
    $where[] = 'overall_rating = ?';
    $params[] = (int)$_GET['rating'];
}

if (!empty($_GET['search'])) {
    $where[] = '(comment LIKE ? OR email LIKE ?)';
    $params[] = '%' . $_GET['search'] . '%';
    $params[] = '%' . $_GET['search'] . '%';
}

$whereClause = implode(' AND ', $where);

// Get total count
$countStmt = $db->prepare("SELECT COUNT(*) FROM feedback WHERE $whereClause");
foreach ($params as $i => $param) {
    $countStmt->bindValue($i + 1, $param);
}
$totalResult = $countStmt->execute();
$totalCount = $totalResult->fetchArray()[0];

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Sort
$orderBy = 'created_at DESC';
if (!empty($_GET['sort'])) {
    $allowedSorts = ['created_at', 'overall_rating', 'recommendation'];
    if (in_array($_GET['sort'], $allowedSorts)) {
        $orderBy = $_GET['sort'] . ' DESC';
    }
}

// Fetch feedback
$query = "SELECT * FROM feedback WHERE $whereClause ORDER BY $orderBy LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($query);
foreach ($params as $i => $param) {
    $stmt->bindValue($i + 1, $param);
}
$result = $stmt->execute();

$feedbackList = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $feedbackList[] = $row;
}

// Get statistics
$stats = [
    'Pending Review' => 0,
    'Approved' => 0,
    'Rejected' => 0,
    'total' => 0
];

$statsResult = $db->query('SELECT status, COUNT(*) as count FROM feedback GROUP BY status');
while ($row = $statsResult->fetchArray(SQLITE3_ASSOC)) {
    $stats[$row['status']] = $row['count'];
    $stats['total'] += $row['count'];
}

// Calculate average rating and NPS
$avgRatingResult = $db->query('SELECT AVG(overall_rating) as avg FROM feedback WHERE status = "Approved"');
$avgRating = $avgRatingResult->fetchArray()['avg'] ?? 0;
$avgRating = round($avgRating, 1);

$npsResult = $db->query('
    SELECT 
        SUM(CASE WHEN recommendation IN ("Definitely Yes", "Probably Yes") THEN 1 ELSE 0 END) as promoters,
        SUM(CASE WHEN recommendation IN ("Probably No", "Definitely No") THEN 1 ELSE 0 END) as detractors,
        COUNT(*) as total
    FROM feedback WHERE status = "Approved"
');
$npsData = $npsResult->fetchArray();
$nps = 0;
if ($npsData['total'] > 0) {
    $nps = round((($npsData['promoters'] - $npsData['detractors']) / $npsData['total']) * 100);
}

$db->close();

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

function getStatusBadge($status) {
    $classes = [
        'Pending Review' => 'badge-pending',
        'Approved' => 'badge-approved',
        'Rejected' => 'badge-rejected'
    ];
    return '<span class="badge ' . ($classes[$status] ?? '') . '">' . htmlspecialchars($status) . '</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Management - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="../css/feedback-admin.css">
</head>
<body class="admin-body">
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="admin-header-content">
            <div class="admin-title">
                <img src="../images/TagumTourism.jpg" alt="Tagum City Logo" class="admin-logo" loading="lazy">
                <span>Tourism Admin Dashboard</span>
            </div>
            <div class="admin-nav">
                <span class="admin-user">Welcome, <strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong></span>
                <a href="logout.php" class="btn btn-primary tab-btn logout-btn">Logout</a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="admin-main">
    <div class="admin-header">
        <h1>Feedback Management</h1>
        <div class="admin-header-actions">
            <a href="generate_comprehensive_report.php" class="btn btn-primary">Generate Comprehensive Report</a>
            <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['Pending Review']; ?></div>
            <div class="stat-label">Pending Reviews</div>
            <div class="stat-icon">⏳</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['Approved']; ?></div>
            <div class="stat-label">Approved Reviews</div>
            <div class="stat-icon">✓</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['Rejected']; ?></div>
            <div class="stat-label">Rejected Reviews</div>
            <div class="stat-icon">✗</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total Feedback</div>
            <div class="stat-icon">📊</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo renderStars(round($avgRating)); ?> <?php echo $avgRating; ?></div>
            <div class="stat-label">Average Rating</div>
            <div class="stat-icon">⭐</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $nps >= 0 ? '+' : ''; ?><?php echo $nps; ?></div>
            <div class="stat-label">NPS Score</div>
            <div class="stat-icon">📈</div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <form method="GET" class="filters-form">
            <div class="filter-group">
                <label>Establishment Type:</label>
                <select name="establishment_type">
                    <option value="">All</option>
                    <option value="hotel" <?php echo ($_GET['establishment_type'] ?? '') === 'hotel' ? 'selected' : ''; ?>>Hotel</option>
                    <option value="restaurant" <?php echo ($_GET['establishment_type'] ?? '') === 'restaurant' ? 'selected' : ''; ?>>Restaurant</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Status:</label>
                <select name="status">
                    <option value="">All</option>
                    <option value="Pending Review" <?php echo ($_GET['status'] ?? '') === 'Pending Review' ? 'selected' : ''; ?>>Pending Review</option>
                    <option value="Approved" <?php echo ($_GET['status'] ?? '') === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="Rejected" <?php echo ($_GET['status'] ?? '') === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Rating:</label>
                <select name="rating">
                    <option value="">All</option>
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($_GET['rating'] ?? '') == $i ? 'selected' : ''; ?>><?php echo $i; ?> Stars</option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Sort By:</label>
                <select name="sort">
                    <option value="created_at" <?php echo ($_GET['sort'] ?? '') === 'created_at' ? 'selected' : ''; ?>>Date</option>
                    <option value="overall_rating" <?php echo ($_GET['sort'] ?? '') === 'overall_rating' ? 'selected' : ''; ?>>Rating</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Search:</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="Search comments or email...">
            </div>
            <button type="submit" class="btn btn-primary">Apply Filters</button>
            <a href="feedback-management.php" class="btn btn-secondary">Clear</a>
        </form>
    </div>

    <!-- Bulk Actions -->
    <div class="bulk-actions">
        <form method="POST" id="bulkForm">
            <select name="bulk_action" id="bulkAction">
                <option value="">Bulk Action</option>
                <option value="approve">Approve Selected</option>
                <option value="reject">Reject Selected</option>
            </select>
            <button type="submit" class="btn btn-primary" id="bulkSubmit" disabled>Apply</button>
        </form>
    </div>

    <!-- Feedback Table -->
    <div class="feedback-table-container">
        <form method="POST" id="feedbackForm">
            <table class="feedback-table">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"> <label for="selectAll" style="cursor:pointer;">Select All</label></th>
                        <th>Date</th>
                        <th>Establishment</th>
                        <th>Email</th>
                        <th>Anonymous</th>
                        <th>Rating</th>
                        <th>Recommendation</th>
                        <th>Comment</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($feedbackList)): ?>
                        <tr>
                            <td colspan="10" class="no-data">No feedback found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($feedbackList as $feedback): ?>
                            <tr data-id="<?php echo $feedback['id']; ?>">
                                <td>
                                    <input type="checkbox" name="selected_ids[]" value="<?php echo $feedback['id']; ?>" class="row-checkbox">
                                </td>
                                <td><?php echo date('M j, Y', strtotime($feedback['created_at'])); ?></td>
                                <td>
                                    <?php echo ucfirst($feedback['establishment_type']); ?> #<?php echo $feedback['establishment_id']; ?>
                                </td>
                                <td><?php echo htmlspecialchars($feedback['email']); ?></td>
                                <td><?php echo $feedback['anonymous'] ? 'Yes' : 'No'; ?></td>
                                <td><?php echo renderStars($feedback['overall_rating']); ?></td>
                                <td><?php echo htmlspecialchars($feedback['recommendation']); ?></td>
                                <td>
                                    <div class="comment-preview">
                                        <?php echo htmlspecialchars(substr($feedback['comment'] ?? '', 0, 50)); ?>
                                        <?php if (strlen($feedback['comment'] ?? '') > 50): ?>
                                            <span class="comment-ellipsis">...</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td><?php echo getStatusBadge($feedback['status']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-view" onclick="viewFeedback(<?php echo $feedback['id']; ?>)">View</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>
    </div>

    <!-- Pagination -->
    <?php if ($totalCount > $perPage): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="btn btn-secondary">← Previous</a>
            <?php endif; ?>
            
            <span class="page-info">Page <?php echo $page; ?> of <?php echo ceil($totalCount / $perPage); ?></span>
            
            <?php if ($page < ceil($totalCount / $perPage)): ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="btn btn-secondary">Next →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- View Feedback Modal -->
<div id="feedbackModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Feedback Details</h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
</div>

<script>
// Select all checkbox
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.row-checkbox').forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    updateBulkSubmit();
});

// Individual checkboxes
document.querySelectorAll('.row-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateBulkSubmit);
});

function updateBulkSubmit() {
    const checked = document.querySelectorAll('.row-checkbox:checked');
    document.getElementById('bulkSubmit').disabled = checked.length === 0;
}

// View feedback
function viewFeedback(id) {
    const modal = document.getElementById('feedbackModal');
    const modalBody = document.getElementById('modalBody');
    
    modalBody.innerHTML = '<p>Loading...</p>';
    modal.style.display = 'flex';
    
    fetch('feedback-details.php?id=' + id)
        .then(response => response.text())
        .then(html => {
            modalBody.innerHTML = html;
        })
        .catch(error => {
            modalBody.innerHTML = '<p>Error loading feedback details.</p>';
        });
}

function closeModal() {
    document.getElementById('feedbackModal').style.display = 'none';
}

// Close modal on outside click
window.onclick = function(event) {
    const modal = document.getElementById('feedbackModal');
    if (event.target === modal) {
        closeModal();
    }
}
</script>
</body>
</html>
