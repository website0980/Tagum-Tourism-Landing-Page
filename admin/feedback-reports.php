<?php
session_start();
require_once __DIR__ . '/../database/setup_feedback.php';
require_once __DIR__ . '/config.php';
requireAuth();

ensureFeedbackTable();

$dbFile = __DIR__ . '/../database.db';
$db = new SQLite3($dbFile);

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

if (!empty($_GET['date_from'])) {
    $where[] = 'created_at >= ?';
    $params[] = $_GET['date_from'];
}

if (!empty($_GET['date_to'])) {
    $where[] = 'created_at <= ?';
    $params[] = $_GET['date_to'] . ' 23:59:59';
}

$whereClause = implode(' AND ', $where);

// Get filtered feedback
$query = "SELECT * FROM feedback WHERE $whereClause ORDER BY created_at DESC";
$stmt = $db->prepare($query);
foreach ($params as $i => $param) {
    $stmt->bindValue($i + 1, $param);
}
$result = $stmt->execute();

$feedbackList = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $feedbackList[] = $row;
}

// Calculate statistics
$stats = [
    'total' => count($feedbackList),
    'approved' => 0,
    'pending' => 0,
    'rejected' => 0,
    'anonymous' => 0,
    'avg_rating' => 0,
    'nps' => 0
];

$totalRating = 0;
$totalApproved = 0;
$promoters = 0;
$detractors = 0;

foreach ($feedbackList as $feedback) {
    if ($feedback['status'] === 'Approved') {
        $stats['approved']++;
        $totalRating += $feedback['overall_rating'];
        $totalApproved++;
        
        if (in_array($feedback['recommendation'], ['Definitely Yes', 'Probably Yes'])) {
            $promoters++;
        } elseif (in_array($feedback['recommendation'], ['Probably No', 'Definitely No'])) {
            $detractors++;
        }
    } elseif ($feedback['status'] === 'Pending Review') {
        $stats['pending']++;
    } elseif ($feedback['status'] === 'Rejected') {
        $stats['rejected']++;
    }
    
    if ($feedback['anonymous']) {
        $stats['anonymous']++;
    }
}

if ($totalApproved > 0) {
    $stats['avg_rating'] = round($totalRating / $totalApproved, 1);
    $stats['nps'] = round((($promoters - $detractors) / $totalApproved) * 100);
}

// Export functionality
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    error_reporting(0);
    $filename = 'comprehensive_feedback_report_' . date('Y-m-d') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Report Title
    fputcsv($output, ['HOTEL & RESTAURANT FEEDBACK MANAGEMENT SYSTEM'], ',', '"', '\\');
    fputcsv($output, ['COMPREHENSIVE FEEDBACK REPORT'], ',', '"', '\\');
    fputcsv($output, ['Generated: ' . date('F j, Y g:i A')], ',', '"', '\\');
    fputcsv($output, [], ',', '"', '\\');
    
    // ========================================
    // SECTION 1: EXECUTIVE SUMMARY & ANALYTICS
    // ========================================
    fputcsv($output, ['SECTION 1: EXECUTIVE SUMMARY & ANALYTICS'], ',', '"', '\\');
    fputcsv($output, [], ',', '"', '\\');
    
    // System Summary
    fputcsv($output, ['--- SYSTEM SUMMARY ---'], ',', '"', '\\');
    fputcsv($output, ['Metric', 'Value'], ',', '"', '\\');
    fputcsv($output, ['Total Hotels', $stats['total'] ?? 0], ',', '"', '\\');
    fputcsv($output, ['Total Restaurants', $stats['total'] ?? 0], ',', '"', '\\');
    fputcsv($output, ['Total Approved Feedback', $stats['approved']], ',', '"', '\\');
    fputcsv($output, ['Total Pending Feedback', $stats['pending']], ',', '"', '\\');
    fputcsv($output, ['Total Rejected Feedback', $stats['rejected']], ',', '"', '\\');
    fputcsv($output, ['Average Rating', $stats['avg_rating'] . ' / 5'], ',', '"', '\\');
    fputcsv($output, [], ',', '"', '\\');
    
    // ========================================
    // SECTION 2: OVERALL RATING DISTRIBUTION
    // ========================================
    fputcsv($output, ['SECTION 2: OVERALL RATING DISTRIBUTION'], ',', '"', '\\');
    fputcsv($output, [], ',', '"', '\\');
    fputcsv($output, ['Rating', 'Count', 'Percentage'], ',', '"', '\\');
    
    // Calculate rating distribution
    $ratingCounts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    foreach ($feedbackList as $feedback) {
        if (isset($feedback['overall_rating'])) {
            $rating = (int)$feedback['overall_rating'];
            if (isset($ratingCounts[$rating])) {
                $ratingCounts[$rating]++;
            }
        }
    }
    $totalRatings = array_sum($ratingCounts);
    
    for ($i = 5; $i >= 1; $i--) {
        $count = $ratingCounts[$i];
        $percentage = $totalRatings > 0 ? round(($count / $totalRatings) * 100, 1) : 0;
        $stars = str_repeat('*', $i);
        fputcsv($output, [$stars, $count, $percentage . '%'], ',', '"', '\\');
    }
    fputcsv($output, [], ',', '"', '\\');
    
    // ========================================
    // SECTION 3: HOTEL FEEDBACK REPORT
    // ========================================
    fputcsv($output, ['SECTION 3: HOTEL FEEDBACK REPORT'], ',', '"', '\\');
    fputcsv($output, [], ',', '"', '\\');
    fputcsv($output, ['Establishment Name', 'Reviewer Email', 'Date', 'Rating', 'Comment', 'Status'], ',', '"', '\\');
    
    foreach ($feedbackList as $feedback) {
        if ($feedback['establishment_type'] === 'hotel') {
            $stmt = $db->prepare('SELECT name FROM hotel_items WHERE id = ?');
            $stmt->bindValue(1, $feedback['establishment_id'], SQLITE3_INTEGER);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);
            $establishmentName = $row['name'] ?? 'Unknown';
            $date = date('F j, Y', strtotime($feedback['created_at']));
            
            fputcsv($output, [
                $establishmentName,
                $feedback['email'],
                $date,
                $feedback['overall_rating'] . '/5',
                $feedback['comment'] ?? '',
                $feedback['status']
            ], ',', '"', '\\');
        }
    }
    fputcsv($output, [], ',', '"', '\\');
    
    // ========================================
    // SECTION 4: RESTAURANT FEEDBACK REPORT
    // ========================================
    fputcsv($output, ['SECTION 4: RESTAURANT FEEDBACK REPORT'], ',', '"', '\\');
    fputcsv($output, [], ',', '"', '\\');
    fputcsv($output, ['Establishment Name', 'Reviewer Email', 'Date', 'Rating', 'Comment', 'Status'], ',', '"', '\\');
    
    foreach ($feedbackList as $feedback) {
        if ($feedback['establishment_type'] === 'restaurant') {
            $stmt = $db->prepare('SELECT name FROM restaurant_items WHERE id = ?');
            $stmt->bindValue(1, $feedback['establishment_id'], SQLITE3_INTEGER);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);
            $establishmentName = $row['name'] ?? 'Unknown';
            $date = date('F j, Y', strtotime($feedback['created_at']));
            
            fputcsv($output, [
                $establishmentName,
                $feedback['email'],
                $date,
                $feedback['overall_rating'] . '/5',
                $feedback['comment'] ?? '',
                $feedback['status']
            ], ',', '"', '\\');
        }
    }
    fputcsv($output, [], ',', '"', '\\');
    
    // ========================================
    // SECTION 5: OVERALL SYSTEM REPORT
    // ========================================
    fputcsv($output, ['SECTION 5: OVERALL SYSTEM REPORT'], ',', '"', '\\');
    fputcsv($output, [], ',', '"', '\\');
    fputcsv($output, ['--- SYSTEM METRICS ---'], ',', '"', '\\');
    fputcsv($output, ['Metric', 'Value'], ',', '"', '\\');
    fputcsv($output, ['Total Feedback Records', $stats['total']], ',', '"', '\\');
    fputcsv($output, ['Approved Feedback', $stats['approved']], ',', '"', '\\');
    fputcsv($output, ['Pending Feedback', $stats['pending']], ',', '"', '\\');
    fputcsv($output, ['Rejected Feedback', $stats['rejected']], ',', '"', '\\');
    fputcsv($output, ['Average Rating', $stats['avg_rating'] . ' / 5'], ',', '"', '\\');
    fputcsv($output, ['NPS Score', ($stats['nps'] >= 0 ? '+' : '') . $stats['nps']], ',', '"', '\\');
    fputcsv($output, ['Anonymous Reviews', $stats['anonymous']], ',', '"', '\\');
    fputcsv($output, [], ',', '"', '\\');
    fputcsv($output, ['--- REPORT INFORMATION ---'], ',', '"', '\\');
    fputcsv($output, ['Report Generated', date('F j, Y g:i A')], ',', '"', '\\');
    fputcsv($output, ['Generated By', isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Administrator'], ',', '"', '\\');
    
    fclose($output);
    $db->close();
    exit;
}

$db->close();

// PDF Export - Redirect to the dedicated comprehensive report generator
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    // Build query params to pass filter context to the report generator
    $params = ['format' => 'pdf'];
    
    // Pass filter parameters
    if (!empty($_GET['establishment_type'])) {
        $params['establishment_type'] = $_GET['establishment_type'];
    }
    if (!empty($_GET['status'])) {
        $params['status'] = $_GET['status'];
    }
    if (!empty($_GET['rating'])) {
        $params['rating'] = $_GET['rating'];
    }
    if (!empty($_GET['date_from'])) {
        $params['date_from'] = $_GET['date_from'];
    }
    if (!empty($_GET['date_to'])) {
        $params['date_to'] = $_GET['date_to'];
    }
    
    $redirectUrl = 'generate_comprehensive_report.php?' . http_build_query($params);
    header('Location: ' . $redirectUrl);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Reports - Admin</title>
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
        <h1>Feedback Reports</h1>
        <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
    </div>

    <!-- Statistics Summary -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['total']; ?></div>
            <div class="stat-label">Total Feedback</div>
            <div class="stat-icon">📊</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['approved']; ?></div>
            <div class="stat-label">Approved Reviews</div>
            <div class="stat-icon">✓</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['pending']; ?></div>
            <div class="stat-label">Pending Reviews</div>
            <div class="stat-icon">⏳</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['rejected']; ?></div>
            <div class="stat-label">Rejected Reviews</div>
            <div class="stat-icon">✗</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['avg_rating']; ?> / 5</div>
            <div class="stat-label">Average Rating</div>
            <div class="stat-icon">⭐</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['nps'] >= 0 ? '+' : ''; ?><?php echo $stats['nps']; ?></div>
            <div class="stat-label">NPS Score</div>
            <div class="stat-icon">📈</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats['anonymous']; ?></div>
            <div class="stat-label">Anonymous Reviews</div>
            <div class="stat-icon">👤</div>
        </div>
    </div>

    <!-- Report Filters -->
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
                <label>Date From:</label>
                <input type="date" name="date_from" value="<?php echo htmlspecialchars($_GET['date_from'] ?? ''); ?>">
            </div>
            <div class="filter-group">
                <label>Date To:</label>
                <input type="date" name="date_to" value="<?php echo htmlspecialchars($_GET['date_to'] ?? ''); ?>">
            </div>
            <button type="submit" class="btn btn-primary">Apply Filters</button>
            <a href="feedback-reports.php" class="btn btn-secondary">Clear</a>
        </form>
    </div>

    <!-- Export Options -->
    <div class="export-section">
        <h3>Export Report</h3>
        <div class="export-buttons">
            <form method="GET" action="" style="display: inline;">
                <?php foreach ($_GET as $key => $value): ?>
                    <?php if ($key !== 'export'): ?>
                        <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
                    <?php endif; ?>
                <?php endforeach; ?>
                <input type="hidden" name="export" value="csv">
                <button type="submit" class="btn btn-primary">📄 Download CSV</button>
            </form>
            <form method="GET" action="" style="display: inline;">
                <?php foreach ($_GET as $key => $value): ?>
                    <?php if ($key !== 'export'): ?>
                        <input type="hidden" name="<?php echo htmlspecialchars($key); ?>" value="<?php echo htmlspecialchars($value); ?>">
                    <?php endif; ?>
                <?php endforeach; ?>
                <input type="hidden" name="export" value="pdf">
                <button type="submit" class="btn btn-primary">📑 Download PDF</button>
            </form>
        </div>
    </div>

    <!-- Report Preview -->
    <div class="report-preview">
        <h3>Report Preview (<?php echo count($feedbackList); ?> records)</h3>
        <div class="feedback-table-container">
            <table class="feedback-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Establishment</th>
                        <th>Rating</th>
                        <th>Recommendation</th>
                        <th>Status</th>
                        <th>Anonymous</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($feedbackList)): ?>
                        <tr>
                            <td colspan="6" class="no-data">No feedback found matching the filters</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach (array_slice($feedbackList, 0, 50) as $feedback): ?>
                            <tr>
                                <td><?php echo date('M j, Y', strtotime($feedback['created_at'])); ?></td>
                                <td><?php echo ucfirst($feedback['establishment_type']); ?> #<?php echo $feedback['establishment_id']; ?></td>
                                <td><?php echo $feedback['overall_rating']; ?>/5</td>
                                <td><?php echo htmlspecialchars($feedback['recommendation']); ?></td>
                                <td><?php echo htmlspecialchars($feedback['status']); ?></td>
                                <td><?php echo $feedback['anonymous'] ? 'Yes' : 'No'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (count($feedbackList) > 50): ?>
                            <tr>
                                <td colspan="6" class="no-data">
                                    ... and <?php echo count($feedbackList) - 50; ?> more records (download full report to see all)
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.export-section {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    margin-bottom: 2rem;
}

.export-section h3 {
    font-size: 1.25rem;
    color: #1d5a3d;
    margin-bottom: 1rem;
}

.export-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.report-preview {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.report-preview h3 {
    font-size: 1.25rem;
    color: #1d5a3d;
    margin-bottom: 1rem;
}
</style>
</body>
</html>
