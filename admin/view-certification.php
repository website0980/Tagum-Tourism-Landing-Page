<?php
require_once 'config.php';
requireAuth();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$application = loadAccommodationApplicationById($id);
if (!$application) {
    header('HTTP/1.1 404 Not Found');
    echo '<p>Certification application not found.</p>';
    exit;
}

// Convert JSON fields to arrays if needed
$roomTypes = [];
$amenities = [];
if (!empty($application['room_types'])) {
    $roomTypes = json_decode($application['room_types'], true) ?: [];
}
if (!empty($application['amenities'])) {
    $amenities = json_decode($application['amenities'], true) ?: [];
}
$trackLabel = 'N/A';
switch ($application['certification_track']) {
    case 'dot_accredited':
        $trackLabel = 'DOT Accredited';
        break;
    case 'locally_certified':
        $trackLabel = 'Locally Certified';
        break;
    default:
        $trackLabel = ucwords(str_replace(['_', '-'], ' ', (string) $application['certification_track']));
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certification Application</title>
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .view-cert-page { padding: 20px 0; max-width: 1000px; margin: 0 auto; font-size: 0.95rem; line-height: 1.5; }
        .view-cert-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 18px; padding: 18px 20px; border: 1px solid #ddd; border-radius: 10px; background: #fafafa; }
        .view-cert-heading { display: flex; flex-direction: column; gap: 6px; }
        .view-cert-title { margin: 0; font-size: 1.2rem; font-weight: 700; }
        .view-cert-meta { margin: 0; font-size: 0.95rem; color: #555; }
        .view-cert-actions { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; margin-top: 0; }
        .view-cert-section { border: 1px solid #ddd; padding: 16px; margin-bottom: 16px; background: #fff; }
        .view-cert-section h3 { margin: 0 0 10px; font-size: 1rem; }
        .view-cert-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .view-cert-grid-full { grid-column: 1 / -1; }
        .view-cert-field { margin-bottom: 10px; }
        .view-cert-label, .field-label { display: block; font-weight: bold; margin-bottom: 4px; font-size: 0.95rem; }
        .view-cert-value, .field-value { white-space: pre-wrap; font-size: 0.95rem; }
        .view-cert-table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 0.95rem; }
        .view-cert-table th,
        .view-cert-table td { border: 1px solid #ccc; padding: 8px; text-align: left; font-size: 0.95rem; }
        .view-cert-actions { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
        .btn-plain { display: inline-block; padding: 8px 14px; border: 1px solid #007acc; background: #007acc; color: white; text-decoration: none; border-radius: 4px; }
        .btn-plain:hover { opacity: 0.9; }
        .print-help { font-size: 0.9rem; color: #555; }
        .print-form-container { border: none; padding: 0; background: transparent; }
        .form-header { text-align: center; margin-bottom: 16px; border-bottom: 1px solid #ddd; padding-bottom: 16px; }
        .form-header .logo-container { margin-bottom: 8px; }
        .form-logo { max-width: 80px; height: auto; display: inline-block; }
        .form-header-top { font-size: 10px; margin-bottom: 4px; }
        .form-title { font-size: 16px; font-weight: bold; text-transform: uppercase; margin: 4px 0; }
        .form-subtitle { font-size: 12px; font-weight: bold; margin: 4px 0; }
        .application-date { font-size: 10px; margin: 8px 0; }
        .section { margin-bottom: 16px; border: 1px solid #ddd; padding: 16px; background: #fff; break-inside: avoid-page; page-break-inside: avoid; overflow: visible; }
        .section-title { font-weight: bold; font-size: 1rem; margin-bottom: 10px; background: #fafafa; padding: 8px 10px; break-inside: avoid-page; page-break-inside: avoid; }
        .section-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .field-row { display: flex; flex-direction: column; gap: 4px; break-inside: avoid-page; page-break-inside: avoid; overflow: visible; }
        .field-label { font-size: 0.92rem; font-weight: bold; }
        .field-value { font-size: 0.92rem; min-height: 40px; padding: 10px; border: 1px solid #ddd; background: #fff; white-space: pre-wrap; }
        .signature { display: flex; gap: 24px; align-items: flex-start; margin-top: 16px; justify-content: space-between; flex-wrap: wrap; }
        .sig-col { display: flex; flex-direction: column; align-items: center; min-width: 220px; }
        .sig-col--right { min-width: 140px; }
        .signature-line { border-top: 1px solid #ddd; width: 180px; height: 0; margin-top: 24px; }
        .sig-col--right .signature-line { width: 120px; }
        .sig-label { font-size: 8px; text-align: center; margin-top: 6px; }
        @media (max-width: 900px) { .view-cert-grid, .section-grid { grid-template-columns: 1fr; } }
        @page {
            margin: 4mm;
        }
        @media print {
            html, body { width: 100%; height: auto; margin: 0; padding: 0; }
            body { font-family: Arial, sans-serif; font-size: 9px; line-height: 1.2; background: #fff; }
            .admin-header, .view-cert-header, .view-cert-actions { display: none !important; }
            .admin-main { padding: 0; }
            .admin-container { padding: 0; }
            .print-form-container { border: 2px solid #000; padding: 8px; background: #fff; }
            .form-header { text-align: center; margin-bottom: 6px; border-bottom: 2px solid #000; padding-bottom: 6px; }
            .form-header-top { font-size: 9px; margin-bottom: 3px; }
            .form-logo { max-width: 80px; height: auto; display: inline-block; }
            .form-title { font-size: 13px; font-weight: bold; margin: 2px 0; text-transform: uppercase; }
            .form-subtitle { font-size: 10px; font-weight: bold; margin: 2px 0; }
            .application-date { font-size: 9px; margin-top: 6px; margin-bottom: 4px; text-align: center; font-weight: normal; }
            .view-cert-page { padding: 0; max-width: 100%; margin: 0; }
            .section { margin-bottom: 6px; border: 1px solid #000; padding: 4px; page-break-inside: avoid; break-inside: avoid-page; }
            .section-title { font-weight: bold; font-size: 9px; margin-bottom: 4px; background: #e8e8e8; padding: 2px 4px; border-bottom: 1px solid #000; }
            .section-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
            .field-row { margin-bottom: 4px; }
            .field-label { display: block; font-size: 9px; font-weight: bold; margin-bottom: 2px; }
            .field-value { display: block; font-size: 9px; padding: 6px 8px; border: 1px solid #000; min-height: 28px; background: #fff; }
            .view-cert-table { width: 100%; border-collapse: collapse; font-size: 8px; margin-top: 3px; }
            .view-cert-table th, .view-cert-table td { border: 1px solid #000; padding: 4px 6px; font-size: 8px; }
            .view-cert-table th { background: #e8e8e8; font-weight: bold; }
            .requirements-list { font-size: 7.5px; margin-left: 12px; margin-top: 2px; }
            .signature { display: flex; gap: 10px; align-items: flex-start; margin-top: 8px; flex-wrap: wrap; }
            .sig-col { display: flex; flex-direction: column; align-items: center; }
            .sig-col--right { margin-left: auto; }
            .signature-line { border-top: 1px solid #000; width: 180px; margin-top: 20px; height: 0; display: block; }
            .sig-col--right .signature-line { width: 120px; }
            .sig-label { font-size: 8px; text-align: center; margin-top: 6px; }
        }
        @media (max-width: 700px) { .view-cert-grid, .section-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="admin-header-content">
            <div class="admin-title">
                <a href="dashboard.php?tab=certification" class="back-link" aria-label="Back to Certification">
                    <span class="back-link-icon" aria-hidden="true">←</span>
                    <span class="back-link-text">Back to Certification</span>
                </a>
                <h1>Certification Application</h1>
            </div>
        </div>
    </header>
    <main class="admin-main"> 
        <div class="admin-container view-cert-page">
            <div class="view-cert-header">
                <div class="view-cert-heading">
                    <h1 class="view-cert-title">Certification Application #<?php echo htmlspecialchars($application['id']); ?></h1>
                    <p class="view-cert-meta">Submitted on <?php echo htmlspecialchars($application['created_at'] ?? 'N/A'); ?></p>
                </div>
                <div class="view-cert-actions">
                    <a href="javascript:window.print()" class="btn-plain">Print</a>
                </div>
            </div>

            <div class="print-form-container">
                <div class="form-header">
                    <div class="logo-container">
                        <img src="../images/City%20of%20Tagum.png" alt="City of Tagum" class="form-logo" loading="lazy">
                    </div>
                    <div class="form-header-top">Republic of the Philippines | Province of Davao del Norte | City of Tagum</div>
                    <div class="form-title">LOCAL CERTIFICATION APPLICATION FORM</div>
                    <div class="form-subtitle">Tourism Accommodation Establishment</div>
                    <div class="application-date">Application Date: <?php echo htmlspecialchars($application['application_date'] ?? '________________________'); ?></div>
                </div>

                <div class="section">
                    <div class="section-title">I. Establishment Details</div>
                    <div class="section-grid">
                        <div class="field-row">
                            <span class="field-label">Establishment</span>
                            <span class="field-value"><?php echo htmlspecialchars($application['establishment_name']); ?></span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Owner</span>
                            <span class="field-value"><?php echo htmlspecialchars($application['owner_name']); ?></span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Address</span>
                            <span class="field-value"><?php echo htmlspecialchars($application['address']); ?></span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Track</span>
                            <span class="field-value"><?php echo htmlspecialchars($trackLabel); ?></span>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <div class="section-title">II. Contact Details</div>
                    <div class="section-grid">
                        <div class="field-row">
                            <span class="field-label">Telephone</span>
                            <span class="field-value"><?php echo htmlspecialchars($application['telephone']); ?></span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Mobile</span>
                            <span class="field-value"><?php echo htmlspecialchars($application['mobile_number']); ?></span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Email</span>
                            <span class="field-value"><?php echo htmlspecialchars($application['email']); ?></span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Facebook</span>
                            <span class="field-value"><?php echo htmlspecialchars($application['facebook']); ?></span>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <div class="section-title">III. Accommodation Details</div>
                    <div class="section-grid">
                        <div class="field-row">
                            <span class="field-label">Category</span>
                            <span class="field-value"><?php echo htmlspecialchars($application['category']); ?></span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Total Rooms</span>
                            <span class="field-value"><?php echo htmlspecialchars($application['total_rooms']); ?></span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Total Capacity</span>
                            <span class="field-value"><?php echo htmlspecialchars($application['total_capacity']); ?></span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Total Employees</span>
                            <span class="field-value"><?php echo htmlspecialchars($application['total_employees']); ?></span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Male Employees</span>
                            <span class="field-value"><?php echo htmlspecialchars($application['male_employees']); ?></span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Female Employees</span>
                            <span class="field-value"><?php echo htmlspecialchars($application['female_employees']); ?></span>
                        </div>
                        <div class="field-row">
                            <span class="field-label">Year Started/Established</span>
                            <span class="field-value"><?php echo htmlspecialchars($application['year_started'] ?? ''); ?></span>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <div class="section-title">IV. Room Types</div>
                    <?php if (!empty($roomTypes)): ?>
                        <table class="view-cert-table">
                            <thead>
                                <tr><th>Type</th><th>Rate</th><th>Number</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($roomTypes as $room): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($room['type']); ?></td>
                                        <td><?php echo htmlspecialchars($room['rate']); ?></td>
                                        <td><?php echo htmlspecialchars($room['number']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <span class="field-value">No room type details submitted.</span>
                    <?php endif; ?>
                </div>

                <div class="section">
                    <div class="section-title">V. Amenities</div>
                    <div style="margin-top: 5px;">
                        <label style="font-size: 9px; font-weight: bold;">Amenities (Swimming Pool, Bar, Restaurant, etc.):</label>
                        <table class="view-cert-table">
                            <thead>
                            </thead>
                            <tbody>
                                <?php if (!empty($amenities)): ?>
                                    <?php foreach ($amenities as $amenity): ?>
                                        <tr><td><?php echo htmlspecialchars($amenity); ?></td></tr>
                                    <?php endforeach; ?>
                                    <?php for ($i = count($amenities); $i < 3; $i++): ?>
                                        <tr><td>&nbsp;</td></tr>
                                    <?php endfor; ?>
                                <?php else: ?>
                                    <tr><td>&nbsp;</td></tr>
                                    <tr><td>&nbsp;</td></tr>
                                    <tr><td>&nbsp;</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="section">
                    <div class="section-title">VI. SUBMISSION REQUIREMENTS</div>
                    <div class="requirements-list" style="font-size: 8.5px; line-height: 1.35; margin-top: 6px;">
                        <p style="margin-bottom: 4px;">7. Submit copy of the following documents for New and Renewal Application:</p>
                        <p style="margin-left: 12px; margin-bottom: 2px;">7.1 Picture of Establishment</p>
                        <p style="margin-left: 24px; margin-bottom: 2px;">a. Rooms and Amenities</p>
                        <p style="margin-left: 12px; margin-bottom: 2px;">7.2 Permit Application Form</p>
                        <p style="margin-left: 12px; margin-bottom: 2px;">7.3 Pre-requisites</p>
                        <p style="margin-left: 24px; margin-bottom: 2px;">a. Valid Mayor’s Permit and/or Business License (New Application & Renewal)</p>
                        <p style="margin-left: 24px; margin-bottom: 2px;">b. Valid Comprehensive General Liability Insurance Policy with a minimum amount of coverage (P200,000) – (New Application)</p>
                        <p style="margin-left: 12px; margin-bottom: 2px;">7.4 Other Requirements</p>
                        <p style="margin-left: 24px; margin-bottom: 2px;">a. Fire Safety Inspection Certificate.</p>
                        <p style="margin-left: 24px; margin-bottom: 2px;">b. And other requirements prescribed by the Business License Office</p>
                        <p style="margin-left: 36px; margin-bottom: 2px; font-style: italic;">e.g.: (DTI Business Name Certificate (for Sole Proprietor) or SEC Registration Certificate and Articles of Incorporation and its By-Laws (for Partnerships & Corporations) or Articles of Cooperation and Its By-Laws (for Cooperatives), etc.)</p>
                        <p style="margin-top: 6px; margin-bottom: 2px;">Additional requirements for Renewal Application:</p>
                        <p style="margin-left: 12px; margin-bottom: 2px;">1. Previous Certificate/Sticker</p>
                        <p style="margin-left: 12px; margin-bottom: 2px;">2. Application Date</p>
                    </div>
                </div>

                <div class="signature">
                    <div class="sig-col">
                        <span class="signature-line"></span>
                        <span class="sig-label">Signature over Printed Name</span>
                    </div>
                    <div class="sig-col sig-col--right">
                        <span class="signature-line"></span>
                        <span class="sig-label">Date</span>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>