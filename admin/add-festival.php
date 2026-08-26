<?php
// Add/Edit Festival Page
require_once 'config.php';
require_once dirname(__DIR__) . '/includes/events_helpers.php';
requireAuth();

$festivals = loadFestivals();
$events = loadEvents(dirname(__DIR__) . '/database.db');
$isEdit = false;
$festivalIndex = $_GET['id'] ?? $_GET['edit'] ?? $_POST['id'] ?? null;
if ($festivalIndex !== null && $festivalIndex !== '') {
    $festivalIndex = (int) $festivalIndex;
}
$errors = [];
$message = '';

$originalImage = '';
$festival = [
    'name' => '',
    'description' => '',
    'image' => '',
    'date' => '',
    'highlights' => '',
    'activities' => '',
    'related_event_id' => ''
];

// If editing, load the festival
if ($festivalIndex !== null && $festivalIndex !== '' && isset($festivals[$festivalIndex])) {
    $isEdit = true;
    $originalImage = $festival['image'];
    $festival = $festivals[$festivalIndex];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $festival = [
        'name' => $_POST['name'] ?? '',
        'description' => $_POST['description'] ?? '',
        'image' => $_POST['image'] ?? '',
        'date' => $_POST['date'] ?? '',
        'highlights' => $_POST['highlights'] ?? '',
        'activities' => $_POST['activities'] ?? '',
        'related_event_id' => $_POST['related_event_id'] ?? ''
    ];

    // Preserve original image if editing, no new upload, and hidden input empty
    if ($isEdit && empty($festival['image']) && empty($_FILES['image_file']['name'] ?? '')) {
        $festival['image'] = $originalImage;
    }

    // Validate category image file if uploaded (do not save/delete until validation passes)
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $validation = validateImageUpload($_FILES['image_file']);
        if (!$validation['success']) {
            $errors[] = $validation['error'];
        }
    }

    // Validation
    if (empty($festival['name'])) $errors[] = 'Festival name is required';
    if (empty($festival['description'])) $errors[] = 'Description is required';
    
    // Save if no errors (file operations only after validation)
    if (empty($errors)) {
        if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $result = saveFestivalImage($_FILES['image_file']);
            if ($result['success']) {
                if (!empty($festival['image'])) {
                    deleteFestivalImage($festival['image']);
                }
                $festival['image'] = $result['path'];
            } else {
                $errors[] = $result['error'] ?? 'Image upload failed';
            }
        }
        if (empty($errors)) {
            if ($isEdit) {
                $festivals[$festivalIndex] = $festival;
            } else {
                $festivals[] = $festival;
            }
            
            saveFestivals($festivals);
            header('Location: dashboard.php?tab=festivals&message=' . ($isEdit ? 'updated' : 'added'));
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isEdit ? 'Edit' : 'Add'; ?> Festival - Tagum Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body class="edit-mode" <?php if ($isEdit) echo 'class="edit-mode"'; ?>>
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="admin-header-content">
            <div class="admin-title">
                <a href="dashboard.php?tab=festivals" class="back-link" aria-label="Back to Dashboard">
                    <span class="back-link-icon" aria-hidden="true">←</span>
                    <span class="back-link-text">Dashboard</span>
                </a>
                <h1><?php echo $isEdit ? 'Edit' : 'Add New'; ?> Festival</h1>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="admin-main">
        <div class="admin-container">
            <!-- Errors -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" enctype="multipart/form-data" class="destination-form">
                <?php if ($isEdit && $festivalIndex !== null && $festivalIndex !== ''): ?>
                <input type="hidden" name="id" value="<?php echo (int) $festivalIndex; ?>">
                <?php endif; ?>
                <div class="form-section">
                    <h2>Festival Information</h2>
                    
                    <div class="form-group">
                        <label for="name">Festival Name *</label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            value="<?php echo htmlspecialchars($festival['name']); ?>" 
                            required
                            class="form-control"
                            placeholder="e.g., Kadayawan Festival, Sinulog"
                        >
                    </div>

                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea 
                            id="description" 
                            name="description" 
                            required
                            class="form-control form-textarea"
                            rows="3"
                            placeholder="Brief description of the festival"
                        ><?php echo htmlspecialchars($festival['description']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="date">Date</label>
                        <input 
                            type="text" 
                            id="date" 
                            name="date" 
                            value="<?php echo htmlspecialchars($festival['date']); ?>" 
                            class="form-control"
                            placeholder="e.g., August 15-17, Monthly, Annual"
                        >
                    </div>

                    <div class="form-group">
                        <label for="related_event_id">Related Event</label>
                        <select id="related_event_id" name="related_event_id" class="form-control">
                            <option value="">No related event</option>
                            <?php foreach ($events as $event): ?>
                                <?php $eventId = (int) ($event['id'] ?? 0); ?>
                                <option value="<?php echo $eventId; ?>" <?php echo ((string) ($festival['related_event_id'] ?? '') === (string) $eventId) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string) ($event['name'] ?? '')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small>Choose the event this festival should appear next to on the Explore page.</small>
                    </div>

                    <div class="form-section">
                        <h2>Festival Image</h2>
                        <?php $image = $festival['image'] ?? ''; ?>
                        <?php include 'media-picker.php'; ?>
                    </div>

                </div>

                <div class="form-section">
                    <h2>Details</h2>
                    
                    <div class="form-group">
                        <label for="highlights">Highlights *</label>
                        <textarea 
                            id="highlights" 
                            name="highlights" 
                            class="form-control form-textarea"
                            rows="4"
                            placeholder="Key highlights and attractions of the festival"
                        ><?php echo htmlspecialchars($festival['highlights']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="activities">Activities</label>
                        <textarea 
                            id="activities" 
                            name="activities" 
                            class="form-control form-textarea"
                            rows="4"
                            placeholder="List of activities, events, and schedule"
                        ><?php echo htmlspecialchars($festival['activities']); ?></textarea>
                    </div>

                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $isEdit ? '✏️ Update Festival' : '➕ Add Festival'; ?>
                    </button>
                    <a href="dashboard.php?tab=festivals" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <footer class="admin-footer">
        <p></p>
    </footer>

    <script src="../assets/js/admin.js"></script>
</body>
</html>

