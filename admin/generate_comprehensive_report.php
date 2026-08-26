<?php
/**
 * Comprehensive Feedback Report Generator
 * Generates a professional multi-page PDF report for hotel and restaurant feedback
 */

session_start();

require_once __DIR__ . '/../lib/fpdf.php';
require_once __DIR__ . '/report_helpers.php';

// Check authentication
requireAuth();

// Extend FPDF for custom header and footer
class ComprehensiveReportPDF extends FPDF {
    private $reportDate;
    private $reportTime;
    private $generatedBy;
    
    public function __construct() {
        parent::__construct('P', 'mm', 'A4');
        date_default_timezone_set('Asia/Manila');
        $this->reportDate = date('F j, Y');
        $this->reportTime = date('g:i A');
        $this->generatedBy = isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'Administrator';
    }
    
    // Page header
    function Header() {
        // Title
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 10, 'HOTEL & RESTAURANT FEEDBACK MANAGEMENT SYSTEM', 0, 1, 'C');
        
        // Subtitle
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(50, 50, 50);
        $this->Cell(0, 8, 'COMPREHENSIVE FEEDBACK REPORT', 0, 1, 'C');
        
        // Line separator
        $this->SetDrawColor(0, 0, 0);
        $this->Line(10, 35, 200, 35);
        
        $this->Ln(10);
    }
    
    // Page footer
    function Footer() {
        $this->SetY(-25);
        
        // Footer line
        $this->SetDrawColor(150, 150, 150);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        
        $this->Ln(5);
        
        // Footer text
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 5, 'Hotel & Restaurant Feedback Management System', 0, 1, 'C');
        $this->Cell(0, 5, 'Generated on: ' . $this->reportDate . ' at ' . $this->reportTime, 0, 1, 'C');
        $this->Cell(0, 5, 'Page ' . $this->PageNo() . ' of {nb}', 0, 0, 'C');
    }
    
    // Section title
    function SectionTitle($title) {
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 10, $title, 0, 1, 'L');
        $this->Ln(5);
    }
    
    // Subsection title
    function SubsectionTitle($title) {
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(50, 50, 50);
        $this->Cell(0, 8, $title, 0, 1, 'L');
        $this->Ln(3);
    }
    
    // Summary card
    function SummaryCard($label, $value, $icon = '') {
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(60, 8, $value, 1, 0, 'C');
        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(60, 8, $label, 1, 1, 'C');
    }
    
    // Summary row
    function SummaryRow($label, $value) {
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(80, 7, $label, 0, 0, 'L');
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 7, $value, 0, 1, 'L');
    }
    
    // Rating bar
    function RatingBar($stars, $count, $percentage) {
        // Use monospace font for better star alignment
        $this->SetFont('Courier', '', 10);
        
        // Save current position
        $y = $this->GetY();
        
        // Display stars, count, and percentage with fixed widths
        $this->Cell(40, 8, $stars, 0, 0, 'L');
        
        // Switch back to Arial for numbers
        $this->SetFont('Arial', '', 10);
        $this->Cell(25, 8, $count, 0, 0, 'L');
        $this->Cell(30, 8, $percentage . '%', 0, 0, 'L');
        
        // Draw bar at consistent vertical position aligned with text baseline
        $barWidth = ($percentage / 100) * 80;
        $this->SetFillColor(50, 50, 50);
        $this->Rect(100, $y + 2, $barWidth, 5, 'F');
        
        $this->Ln(10);
    }
    
    // Table header
    function TableHeader($headers) {
        $this->SetFillColor(200, 200, 200);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', 'B', 8);
        
        $colWidths = [40, 35, 25, 15, 50, 25];
        foreach ($headers as $i => $header) {
            $this->Cell($colWidths[$i], 7, $header, 1, 0, 'C', true);
        }
        $this->Ln();
    }
    
    // Table row
    function TableRow($data) {
        $this->SetFillColor(255, 255, 255);
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Arial', '', 8);
        
        $colWidths = [40, 35, 25, 15, 50, 25];
        $x = $this->GetX();
        $y = $this->GetY();
        
        // Name (truncate if needed)
        $this->Cell($colWidths[0], 7, truncateText($data['name'], 25), 1, 0, 'L');
        
        // Email (truncate if needed)
        $this->Cell($colWidths[1], 7, truncateText($data['email'], 20), 1, 0, 'L');
        
        // Date
        $this->Cell($colWidths[2], 7, $data['date'], 1, 0, 'C');
        
        // Rating
        $this->Cell($colWidths[3], 7, $data['rating'], 1, 0, 'C');
        
        // Comment (truncate if needed)
        $this->Cell($colWidths[4], 7, truncateText($data['comment'], 35), 1, 0, 'L');
        
        // Status
        $this->Cell($colWidths[5], 7, $data['status'], 1, 1, 'C');
    }
    
    // Cover page
    function CoverPage($systemSummary) {
        $this->AddPage();
        
        // Center content vertically
        $this->Ln(30);
        
        // Logo
        $logoPath = __DIR__ . '/../images/TagumTourism.jpg';
        if (file_exists($logoPath)) {
            $this->Image($logoPath, 85, 50, 40);
        }
        
        $this->Ln(50);
        
        // Main title
        $this->SetFont('Arial', 'B', 24);
        $this->SetTextColor(0, 0, 0);
        $this->Cell(0, 15, 'COMPREHENSIVE FEEDBACK REPORT', 0, 1, 'C');
        
        $this->Ln(20);
        
        // Report info
        $this->SetFont('Arial', '', 12);
        $this->SetTextColor(50, 50, 50);
        
        $this->Cell(0, 10, 'Generated By: ' . $this->generatedBy, 0, 1, 'C');
        $this->Cell(0, 10, 'Date Generated: ' . $this->reportDate, 0, 1, 'C');
        $this->Cell(0, 10, 'Time Generated: ' . $this->reportTime, 0, 1, 'C');
        
        $this->Ln(30);
        
        // Decorative line
        $this->SetDrawColor(0, 0, 0);
        $this->Line(50, $this->GetY(), 160, $this->GetY());
    }
    
    // Executive summary page
    function ExecutiveSummaryPage($systemSummary, $hotelAnalytics, $restaurantAnalytics) {
        $this->AddPage();
        
        $this->SectionTitle('EXECUTIVE SUMMARY & ANALYTICS');
        
        // System Summary
        $this->SubsectionTitle('SYSTEM SUMMARY');
        $this->SetFont('Arial', '', 9);
        
        $this->SummaryRow('Total Hotels', $systemSummary['total_hotels']);
        $this->SummaryRow('Total Restaurants', $systemSummary['total_restaurants']);
        $this->SummaryRow('Total Hotel Feedback', $systemSummary['total_hotel_feedback']);
        $this->SummaryRow('Total Restaurant Feedback', $systemSummary['total_restaurant_feedback']);
        $this->SummaryRow('Total Feedback Submitted', $systemSummary['total_feedback']);
        $this->SummaryRow('Total Approved Feedback', $systemSummary['approved_feedback']);
        $this->SummaryRow('Total Pending Feedback', $systemSummary['pending_feedback']);
        $this->SummaryRow('Total Rejected Feedback', $systemSummary['rejected_feedback']);
        $this->SummaryRow('Overall Average Rating', $systemSummary['overall_average_rating'] . ' / 5');
        
        $this->Ln(10);
        
        // Hotel Analytics
        $this->SubsectionTitle('HOTEL ANALYTICS');
        $this->SetFont('Arial', '', 9);
        
        if ($hotelAnalytics['highest_rated_hotel']) {
            $this->SummaryRow('Highest Rated Hotel', $hotelAnalytics['highest_rated_hotel']['name'] . ' (' . $hotelAnalytics['highest_rated_hotel']['average'] . ')');
            $this->SummaryRow('Lowest Rated Hotel', $hotelAnalytics['lowest_rated_hotel']['name'] . ' (' . $hotelAnalytics['lowest_rated_hotel']['average'] . ')');
        } else {
            $this->SummaryRow('Highest Rated Hotel', 'N/A');
            $this->SummaryRow('Lowest Rated Hotel', 'N/A');
        }
        $this->SummaryRow('Average Hotel Rating', $hotelAnalytics['average_hotel_rating'] . ' / 5');
        $this->SummaryRow('Total Hotel Reviews', $hotelAnalytics['total_hotel_reviews']);
        
        $this->Ln(10);
        
        // Restaurant Analytics
        $this->SubsectionTitle('RESTAURANT ANALYTICS');
        $this->SetFont('Arial', '', 9);
        
        if ($restaurantAnalytics['highest_rated_restaurant']) {
            $this->SummaryRow('Highest Rated Restaurant', $restaurantAnalytics['highest_rated_restaurant']['name'] . ' (' . $restaurantAnalytics['highest_rated_restaurant']['average'] . ')');
            $this->SummaryRow('Lowest Rated Restaurant', $restaurantAnalytics['lowest_rated_restaurant']['name'] . ' (' . $restaurantAnalytics['lowest_rated_restaurant']['average'] . ')');
        } else {
            $this->SummaryRow('Highest Rated Restaurant', 'N/A');
            $this->SummaryRow('Lowest Rated Restaurant', 'N/A');
        }
        $this->SummaryRow('Average Restaurant Rating', $restaurantAnalytics['average_restaurant_rating'] . ' / 5');
        $this->SummaryRow('Total Restaurant Reviews', $restaurantAnalytics['total_restaurant_reviews']);
    }
    
    // Rating distribution page
    function RatingDistributionPage($ratingDistribution) {
        $this->AddPage();
        $this->SectionTitle('OVERALL RATING DISTRIBUTION');
        $this->SetFont('Arial', '', 9);
        
        $stars = ['*****', '****', '***', '**', '*'];
        for ($i = 5; $i >= 1; $i--) {
            $this->RatingBar($stars[5-$i], $ratingDistribution[$i]['count'], $ratingDistribution[$i]['percentage']);
        }
    }
    
    // Hotel feedback report page
    function HotelFeedbackReportPage($hotelFeedback) {
        $this->AddPage();
        $this->SectionTitle('HOTEL FEEDBACK REPORT');
        
        if (empty($hotelFeedback)) {
            $this->SetFont('Arial', '', 12);
            $this->Cell(0, 10, 'No hotel feedback records available.', 0, 1, 'C');
            return;
        }
        
        $headers = ['Hotel Name', 'Reviewer Email', 'Date', 'Rating', 'Comment', 'Status'];
        $this->TableHeader($headers);
        
        foreach ($hotelFeedback as $feedback) {
            // Check if we need a new page
            if ($this->GetY() > 250) {
                $this->AddPage();
                $this->TableHeader($headers);
            }
            
            $data = [
                'name' => $feedback['establishment_name'] ?? 'Unknown',
                'email' => $feedback['email'],
                'date' => formatDate($feedback['created_at']),
                'rating' => $feedback['overall_rating'] . '/5',
                'comment' => truncateText($feedback['comment'], 40),
                'status' => $feedback['status']
            ];
            
            $this->TableRow($data);
        }
    }
    
    // Restaurant feedback report page
    function RestaurantFeedbackReportPage($restaurantFeedback) {
        $this->AddPage();
        $this->SectionTitle('RESTAURANT FEEDBACK REPORT');
        
        if (empty($restaurantFeedback)) {
            $this->SetFont('Arial', '', 12);
            $this->Cell(0, 10, 'No restaurant feedback records available.', 0, 1, 'C');
            return;
        }
        
        $headers = ['Restaurant Name', 'Reviewer Email', 'Date', 'Rating', 'Comment', 'Status'];
        $this->TableHeader($headers);
        
        foreach ($restaurantFeedback as $feedback) {
            // Check if we need a new page
            if ($this->GetY() > 250) {
                $this->AddPage();
                $this->TableHeader($headers);
            }
            
            $data = [
                'name' => $feedback['establishment_name'] ?? 'Unknown',
                'email' => $feedback['email'],
                'date' => formatDate($feedback['created_at']),
                'rating' => $feedback['overall_rating'] . '/5',
                'comment' => truncateText($feedback['comment'], 40),
                'status' => $feedback['status']
            ];
            
            $this->TableRow($data);
        }
    }
    
    // Final system summary page
    function FinalSystemSummaryPage($systemSummary, $hotelAnalytics, $restaurantAnalytics) {
        $this->AddPage();
        $this->SectionTitle('OVERALL SYSTEM REPORT');
        
        // System Information
        $this->SubsectionTitle('SYSTEM INFORMATION');
        $this->SetFont('Arial', '', 9);
        $this->SummaryRow('Total Hotels Registered', $systemSummary['total_hotels']);
        $this->SummaryRow('Total Restaurants Registered', $systemSummary['total_restaurants']);
        
        $this->Ln(10);
        
        // Feedback Information
        $this->SubsectionTitle('FEEDBACK INFORMATION');
        $this->SummaryRow('Total Hotel Feedback', $systemSummary['total_hotel_feedback']);
        $this->SummaryRow('Total Restaurant Feedback', $systemSummary['total_restaurant_feedback']);
        $this->SummaryRow('Total Feedback Records', $systemSummary['total_feedback']);
        
        $this->Ln(10);
        
        // Status Summary
        $this->SubsectionTitle('STATUS SUMMARY');
        $this->SummaryRow('Approved Feedback', $systemSummary['approved_feedback']);
        $this->SummaryRow('Pending Feedback', $systemSummary['pending_feedback']);
        $this->SummaryRow('Rejected Feedback', $systemSummary['rejected_feedback']);
        
        $this->Ln(10);
        
        // Rating Summary
        $this->SubsectionTitle('RATING SUMMARY');
        $this->SummaryRow('Average Hotel Rating', $hotelAnalytics['average_hotel_rating'] . ' / 5');
        $this->SummaryRow('Average Restaurant Rating', $restaurantAnalytics['average_restaurant_rating'] . ' / 5');
        $this->SummaryRow('Overall Average Rating', $systemSummary['overall_average_rating'] . ' / 5');
        
        $this->Ln(10);
        
        // Report Information
        $this->SubsectionTitle('REPORT INFORMATION');
        $this->SummaryRow('Generated By', $this->generatedBy);
        $this->SummaryRow('Date Generated', $this->reportDate);
        $this->SummaryRow('Time Generated', $this->reportTime);
        
        $this->Ln(20);
        
        // Footer message
        $this->SetFont('Arial', 'I', 10);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 10, 'This report was automatically generated by the Hotel & Restaurant Feedback Management System.', 0, 1, 'C');
    }
}

// Generate the report
$db = getDatabaseConnection();

try {
    // Gather all data
    $systemSummary = getSystemSummary($db);
    $hotelAnalytics = getHotelAnalytics($db);
    $restaurantAnalytics = getRestaurantAnalytics($db);
    $ratingDistribution = getRatingDistribution($db);
    $hotelFeedback = getHotelFeedback($db);
    $restaurantFeedback = getRestaurantFeedback($db);
    
    // Create PDF
    $pdf = new ComprehensiveReportPDF();
    $pdf->AliasNbPages();
    
    // Generate pages
    $pdf->CoverPage($systemSummary);
    $pdf->ExecutiveSummaryPage($systemSummary, $hotelAnalytics, $restaurantAnalytics);
    $pdf->RatingDistributionPage($ratingDistribution);
    $pdf->HotelFeedbackReportPage($hotelFeedback);
    $pdf->RestaurantFeedbackReportPage($restaurantFeedback);
    $pdf->FinalSystemSummaryPage($systemSummary, $hotelAnalytics, $restaurantAnalytics);
    
    // Close database connection
    $db->close();
    
    // Generate filename
    $filename = 'Comprehensive_Feedback_Report_' . date('Y-m-d') . '.pdf';
    
    // Output PDF for download
    $pdf->Output('D', $filename);
    
} catch (Exception $e) {
    $db->close();
    die('Error generating report: ' . $e->getMessage());
}
