<?php
/**
 * Report Helper Functions for Comprehensive Feedback Report
 * Contains all database queries and data processing functions
 */

require_once __DIR__ . '/../database/setup_feedback.php';

function requireAuth() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        die('Access denied. Please log in as administrator.');
    }
}

function getDatabaseConnection() {
    $dbFile = __DIR__ . '/../database.db';
    return new SQLite3($dbFile);
}

/**
 * Get system summary statistics
 */
function getSystemSummary($db) {
    ensureFeedbackTable();
    
    $summary = [];
    
    // Total Hotels
    $result = $db->query('SELECT COUNT(*) as count FROM hotel_items');
    $summary['total_hotels'] = $result->fetchArray()['count'];
    
    // Total Restaurants
    $result = $db->query('SELECT COUNT(*) as count FROM restaurant_items');
    $summary['total_restaurants'] = $result->fetchArray()['count'];
    
    // Total Hotel Feedback
    $stmt = $db->prepare('SELECT COUNT(*) as count FROM feedback WHERE establishment_type = ?');
    $stmt->bindValue(1, 'hotel', SQLITE3_TEXT);
    $result = $stmt->execute();
    $summary['total_hotel_feedback'] = $result->fetchArray()['count'];
    
    // Total Restaurant Feedback
    $stmt = $db->prepare('SELECT COUNT(*) as count FROM feedback WHERE establishment_type = ?');
    $stmt->bindValue(1, 'restaurant', SQLITE3_TEXT);
    $result = $stmt->execute();
    $summary['total_restaurant_feedback'] = $result->fetchArray()['count'];
    
    // Total Feedback Submitted
    $result = $db->query('SELECT COUNT(*) as count FROM feedback');
    $summary['total_feedback'] = $result->fetchArray()['count'];
    
    // Status counts
    $result = $db->query('SELECT status, COUNT(*) as count FROM feedback GROUP BY status');
    $statusCounts = ['Pending Review' => 0, 'Approved' => 0, 'Rejected' => 0];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $statusCounts[$row['status']] = $row['count'];
    }
    $summary['approved_feedback'] = $statusCounts['Approved'];
    $summary['pending_feedback'] = $statusCounts['Pending Review'];
    $summary['rejected_feedback'] = $statusCounts['Rejected'];
    
    // Overall Average Rating
    $result = $db->query('SELECT AVG(overall_rating) as avg FROM feedback');
    $avgRating = $result->fetchArray()['avg'] ?? 0;
    $summary['overall_average_rating'] = round($avgRating, 1);
    
    return $summary;
}

/**
 * Get hotel analytics
 */
function getHotelAnalytics($db) {
    $analytics = [];
    
    // Get hotel feedback with names
    $stmt = $db->prepare('
        SELECT h.name, h.id, f.overall_rating 
        FROM hotel_items h 
        LEFT JOIN feedback f ON h.id = f.establishment_id AND f.establishment_type = "hotel"
    ');
    $result = $stmt->execute();
    
    $hotelRatings = [];
    $totalHotelReviews = 0;
    $sumHotelRatings = 0;
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        if (!isset($hotelRatings[$row['id']])) {
            $hotelRatings[$row['id']] = [
                'name' => $row['name'],
                'ratings' => [],
                'count' => 0
            ];
        }
        if ($row['overall_rating']) {
            $hotelRatings[$row['id']]['ratings'][] = $row['overall_rating'];
            $hotelRatings[$row['id']]['count']++;
            $totalHotelReviews++;
            $sumHotelRatings += $row['overall_rating'];
        }
    }
    
    // Calculate averages per hotel
    $hotelAverages = [];
    foreach ($hotelRatings as $id => $data) {
        if (count($data['ratings']) > 0) {
            $avg = array_sum($data['ratings']) / count($data['ratings']);
            $hotelAverages[$id] = [
                'name' => $data['name'],
                'average' => round($avg, 1),
                'count' => $data['count']
            ];
        }
    }
    
    // Sort by average rating
    usort($hotelAverages, function($a, $b) {
        return $b['average'] <=> $a['average'];
    });
    
    $analytics['highest_rated_hotel'] = !empty($hotelAverages) ? $hotelAverages[0] : null;
    $analytics['lowest_rated_hotel'] = !empty($hotelAverages) ? end($hotelAverages) : null;
    $analytics['average_hotel_rating'] = $totalHotelReviews > 0 ? round($sumHotelRatings / $totalHotelReviews, 1) : 0;
    $analytics['total_hotel_reviews'] = $totalHotelReviews;
    
    return $analytics;
}

/**
 * Get restaurant analytics
 */
function getRestaurantAnalytics($db) {
    $analytics = [];
    
    // Get restaurant feedback with names
    $stmt = $db->prepare('
        SELECT r.name, r.id, f.overall_rating 
        FROM restaurant_items r 
        LEFT JOIN feedback f ON r.id = f.establishment_id AND f.establishment_type = "restaurant"
    ');
    $result = $stmt->execute();
    
    $restaurantRatings = [];
    $totalRestaurantReviews = 0;
    $sumRestaurantRatings = 0;
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        if (!isset($restaurantRatings[$row['id']])) {
            $restaurantRatings[$row['id']] = [
                'name' => $row['name'],
                'ratings' => [],
                'count' => 0
            ];
        }
        if ($row['overall_rating']) {
            $restaurantRatings[$row['id']]['ratings'][] = $row['overall_rating'];
            $restaurantRatings[$row['id']]['count']++;
            $totalRestaurantReviews++;
            $sumRestaurantRatings += $row['overall_rating'];
        }
    }
    
    // Calculate averages per restaurant
    $restaurantAverages = [];
    foreach ($restaurantRatings as $id => $data) {
        if (count($data['ratings']) > 0) {
            $avg = array_sum($data['ratings']) / count($data['ratings']);
            $restaurantAverages[$id] = [
                'name' => $data['name'],
                'average' => round($avg, 1),
                'count' => $data['count']
            ];
        }
    }
    
    // Sort by average rating
    usort($restaurantAverages, function($a, $b) {
        return $b['average'] <=> $a['average'];
    });
    
    $analytics['highest_rated_restaurant'] = !empty($restaurantAverages) ? $restaurantAverages[0] : null;
    $analytics['lowest_rated_restaurant'] = !empty($restaurantAverages) ? end($restaurantAverages) : null;
    $analytics['average_restaurant_rating'] = $totalRestaurantReviews > 0 ? round($sumRestaurantRatings / $totalRestaurantReviews, 1) : 0;
    $analytics['total_restaurant_reviews'] = $totalRestaurantReviews;
    
    return $analytics;
}

/**
 * Get rating distribution
 */
function getRatingDistribution($db) {
    $distribution = [
        5 => ['count' => 0, 'percentage' => 0],
        4 => ['count' => 0, 'percentage' => 0],
        3 => ['count' => 0, 'percentage' => 0],
        2 => ['count' => 0, 'percentage' => 0],
        1 => ['count' => 0, 'percentage' => 0]
    ];
    
    $result = $db->query('SELECT overall_rating, COUNT(*) as count FROM feedback GROUP BY overall_rating');
    $total = 0;
    $counts = [];
    
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $rating = (int)$row['overall_rating'];
        $count = $row['count'];
        $distribution[$rating]['count'] = $count;
        $total += $count;
    }
    
    // Calculate percentages
    if ($total > 0) {
        foreach ($distribution as $rating => $data) {
            $distribution[$rating]['percentage'] = round(($data['count'] / $total) * 100, 1);
        }
    }
    
    return $distribution;
}

/**
 * Get all hotel feedback with establishment names
 */
function getHotelFeedback($db) {
    $stmt = $db->prepare('
        SELECT f.id, h.name as establishment_name, f.email, f.created_at, f.overall_rating, f.comment, f.status
        FROM feedback f
        LEFT JOIN hotel_items h ON f.establishment_id = h.id
        WHERE f.establishment_type = "hotel"
        ORDER BY f.created_at DESC
    ');
    $result = $stmt->execute();
    
    $feedback = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $feedback[] = $row;
    }
    
    return $feedback;
}

/**
 * Get all restaurant feedback with establishment names
 */
function getRestaurantFeedback($db) {
    $stmt = $db->prepare('
        SELECT f.id, r.name as establishment_name, f.email, f.created_at, f.overall_rating, f.comment, f.status
        FROM feedback f
        LEFT JOIN restaurant_items r ON f.establishment_id = r.id
        WHERE f.establishment_type = "restaurant"
        ORDER BY f.created_at DESC
    ');
    $result = $stmt->execute();
    
    $feedback = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $feedback[] = $row;
    }
    
    return $feedback;
}

/**
 * Format date for display
 */
function formatDate($dateString) {
    if (empty($dateString)) return 'N/A';
    $date = new DateTime($dateString);
    return $date->format('F j, Y');
}

/**
 * Format time for display
 */
function formatTime($dateString) {
    if (empty($dateString)) return 'N/A';
    $date = new DateTime($dateString);
    return $date->format('g:i A');
}

/**
 * Truncate text for PDF display
 */
function truncateText($text, $maxLength = 50) {
    if (empty($text)) return '';
    if (strlen($text) <= $maxLength) return $text;
    return substr($text, 0, $maxLength) . '...';
}
