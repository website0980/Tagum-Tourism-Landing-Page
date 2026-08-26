<?php
// Admin Dashboard - Tagum City
require_once 'config.php';
requireAuth();

$destinations = loadDestinations();
$experiences = loadExperiences();
$events = loadCulturalSites(); // Events data loaded from cultural sites
$festivals = loadFestivals();
$hotels = loadHotels();
$restaurants = loadRestaurants();
$carouselSlides = loadCarouselSlides();
$certificationApplications = loadAccommodationApplications();
$culturalHeritage = json_decode(file_get_contents('../Cultural Heritage Module/cultural-heritage.json'), true) ?? [];

$message = '';
$messageType = '';

$currentTab = $_GET['tab'] ?? 'destinations';

function normalizeHotelCategory($category) {
    $value = trim((string)($category ?? ''));
    if ($value === '') {
        return 'N/A';
    }
    if (stripos($value, 'dot accredited') !== false) {
        return 'DOT Accredited';
    }
    if (stripos($value, 'locally certified') !== false) {
        return 'Locally Certified';
    }
    return $value;
}

// Handle POST requests - Toggle Featured ONLY (no delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;

    switch ($action) {
case 'toggle-featured':
            if ($currentTab === 'destinations' && $id !== null) {
                toggleDestinationFeatured($id);
                $message = 'Featured status updated!';
                $messageType = 'success';
                $destinations = loadDestinations(); // Reload
            }
            break;

        case 'toggle-featured-experience':
            if ($id !== null && isset($experiences[$id])) {
                $experiences[$id]['featured'] = !($experiences[$id]['featured'] ?? false);
                saveExperiences($experiences);
                $message = 'Featured status updated!';
                $messageType = 'success';
            }
            break;

        case 'toggle-carousel-active':
            if ($id !== null) {
                toggleCarouselSlideActive($id);
                $message = 'Slide visibility updated!';
                $messageType = 'success';
                $carouselSlides = loadCarouselSlides();
            }
            break;

        case 'update-application-status':
            if ($id !== null && isset($_POST['status'])) {
                updateApplicationStatus($id, $_POST['status']);
                $message = 'Application status updated!';
                $messageType = 'success';
                $certificationApplications = loadAccommodationApplications();
            }
            break;

        case 'delete-application':
            if ($id !== null) {
                deleteAccommodationApplication($id);
                $message = 'Application deleted.';
                $messageType = 'success';
                $certificationApplications = loadAccommodationApplications();
            }
            break;

        case 'delete-cultural-heritage':
            if ($id !== null) {
                $culturalHeritage = json_decode(file_get_contents('../Cultural Heritage Module/cultural-heritage.json'), true) ?? [];
                $culturalHeritage = array_filter($culturalHeritage, function($item) use ($id) {
                    return ($item['id'] ?? '') !== $id;
                });
                $culturalHeritage = array_values($culturalHeritage);
                file_put_contents('../Cultural Heritage Module/cultural-heritage.json', json_encode($culturalHeritage, JSON_PRETTY_PRINT));
                $message = 'Cultural heritage item deleted.';
                $messageType = 'success';
                $culturalHeritage = json_decode(file_get_contents('../Cultural Heritage Module/cultural-heritage.json'), true) ?? [];
            }
            break;

    }
}

if (isset($_GET['message']) && $currentTab === 'carousel') {
    $msg = $_GET['message'];
    if ($msg === 'added') {
        $message = 'Carousel slide added successfully!';
        $messageType = 'success';
    } elseif ($msg === 'updated') {
        $message = 'Carousel slide updated successfully!';
        $messageType = 'success';
    } elseif ($msg === 'deleted') {
        $message = 'Carousel slide deleted.';
        $messageType = 'success';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Tagum City</title>
    <link rel="stylesheet" href="../../css/admin.css">
</head>
<body>
    <!-- Admin Header -->
    <header class="admin-header">
        <div class="admin-header-content">
            <div class="admin-title">
                <img src="../../images/TagumTourism.jpg" alt="Tagum City Logo" class="admin-logo" loading="lazy">
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
        <div class="admin-container">
            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Tab Navigation Buttons -->
            <div class="tab-buttons">
                <a href="?tab=destinations" class="btn btn-primary tab-btn <?php echo $currentTab === 'destinations' ? 'active' : ''; ?>">Destinations</a>
                <a href="?tab=experiences" class="btn btn-primary tab-btn <?php echo $currentTab === 'experiences' ? 'active' : ''; ?>">Experiences</a>
                <a href="?tab=cultural-heritage" class="btn btn-primary tab-btn <?php echo $currentTab === 'cultural-heritage' ? 'active' : ''; ?>">Cultural Heritage</a>
                <a href="?tab=events" class="btn btn-primary tab-btn <?php echo $currentTab === 'events' ? 'active' : ''; ?>">Events</a>
                <a href="?tab=festivals" class="btn btn-primary tab-btn <?php echo $currentTab === 'festivals' ? 'active' : ''; ?>">Festivals</a>
                <a href="?tab=hotels" class="btn btn-primary tab-btn <?php echo $currentTab === 'hotels' ? 'active' : ''; ?>">Hotels</a>
                <a href="?tab=restaurants" class="btn btn-primary tab-btn <?php echo $currentTab === 'restaurants' ? 'active' : ''; ?>">Restaurants</a>
                <a href="?tab=certification" class="btn btn-primary tab-btn <?php echo $currentTab === 'certification' ? 'active' : ''; ?>">Certification</a>
                <a href="?tab=carousel" class="btn btn-primary tab-btn <?php echo $currentTab === 'carousel' ? 'active' : ''; ?>">Carousel</a>
                <a href="feedback-management.php" class="btn btn-primary tab-btn">Feedback Management</a>
                <a href="feedback-reports.php" class="btn btn-primary tab-btn">Feedback Reports</a>
                <div class="admin-search-wrapper">
                    <input type="text" id="adminSearch" class="admin-search-input" placeholder="🔍 Search <?php echo ucfirst($currentTab); ?>..." onkeyup="filterAdminTable()">
                </div>
            </div>

            <?php if ($currentTab === 'destinations'): ?>
                <div class="dashboard-header">
                    <h2>Manage Destinations</h2>
                    <a href="add-destination.php" class="btn btn-primary">+ Add New Destination</a>
                </div>

                <div class="table-responsive">
                    <?php if (empty($destinations)): ?>
                        <div class="empty-state">
                            <p>📭 No destinations found</p>
                            <a href="add-destination.php" class="btn btn-primary">Add your first destination</a>
                        </div>
                    <?php else: ?>
                        <table class="destinations-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Featured</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($destinations as $destination): ?>
                                    <tr>
                                        <td class="table-image">
                                            <?php if (!empty($destination['image'])): ?>
                                                <img src="<?php echo htmlspecialchars($destination['image']); ?>" alt="<?php echo htmlspecialchars($destination['name']); ?>" loading="lazy">
                                            <?php else: ?>
                                                <span class="no-image">No Image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                             <strong><?php echo htmlspecialchars($destination['name']); ?></strong>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle-featured">
                                                <input type="hidden" name="id" value="<?php echo $destination['id']; ?>">
                                                <button type="submit" class="featured-btn <?php echo ($destination['featured'] ?? false) ? 'active' : ''; ?>">
                                                    ⭐ <?php echo ($destination['featured'] ?? false) ? 'Featured' : 'Not Featured'; ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td class="action-buttons">
                                            <a href="add-destination.php?id=<?php echo $destination['id']; ?>" class="btn btn-small btn-edit">Edit</a>
                                        </td>
                                    </tr>
<?php endforeach; ?>

                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($currentTab === 'experiences'): ?>
                <div class="dashboard-header">
                    <h2>Manage Experiences</h2>
                    <a href="add-experience.php" class="btn btn-primary">+ Add New Experience</a>
                </div>

                <div class="table-responsive">
                    <?php if (empty($experiences)): ?>
                        <div class="empty-state">
                            <p>📭 No experiences found</p>
                            <a href="add-experience.php" class="btn btn-primary">Add your first experience</a>
                        </div>
                    <?php else: ?>
                        <table class="destinations-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Featured</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
<?php foreach ($experiences as $index => $experience): ?>


                                    <tr>
                                        <td class="table-image">
                                            <?php if (!empty($experience['image'])): ?>
                                                <img src="<?php echo htmlspecialchars($experience['image']); ?>" alt="<?php echo htmlspecialchars($experience['name']); ?>" loading="lazy">
                                            <?php else: ?>
                                                <span class="no-image">No Image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($experience['name']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="type-badge"><?php echo htmlspecialchars($experience['type'] ?? 'N/A'); ?></span>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($experience['date'] ?? 'N/A'); ?>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle-featured-experience">
<input type="hidden" name="id" value="<?php echo $experience['id']; ?>">
                                                <button type="submit" class="featured-btn <?php echo ($experience['featured'] ?? false) ? 'active' : ''; ?>">
                                                    ⭐ <?php echo ($experience['featured'] ?? false) ? 'Featured' : 'Not Featured'; ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td class="action-buttons">
<a href="add-experience.php?id=<?php echo $experience['id']; ?>" class="btn btn-small btn-edit">Edit</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($currentTab === 'cultural-heritage'): ?>
                <div class="dashboard-header">
                    <h2>Manage Cultural Heritage</h2>
                    <a href="add-cultural-heritage.php" class="btn btn-primary">+ Add New Cultural Heritage</a>
                </div>

                <div class="table-responsive">
                    <?php if (empty($culturalHeritage)): ?>
                        <div class="empty-state">
                            <p>🏛️ No cultural heritage items found</p>
                            <a href="add-cultural-heritage.php" class="btn btn-primary">Add your first cultural heritage item</a>
                        </div>
<?php else: ?>
                        <table class="destinations-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($culturalHeritage as $item): ?>
                                    <tr>
                                        <td class="table-image">
                                            <?php if (!empty($item['image'])): ?>
                                                <img src="<?php echo htmlspecialchars('../' . $item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" loading="lazy">
                                            <?php else: ?>
                                                <span class="no-image">No Image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="type-badge"><?php echo htmlspecialchars($item['category'] ?? 'N/A'); ?></span>
                                        </td>
                                        <td class="action-buttons">
                                            <a href="add-cultural-heritage.php?id=<?php echo $item['id']; ?>" class="btn btn-small btn-edit">Edit</a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this cultural heritage item?');">
                                                <input type="hidden" name="action" value="delete-cultural-heritage">
                                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                <button type="submit" class="btn btn-small btn-delete">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($currentTab === 'events'): ?>
                <div class="dashboard-header">
                    <h2>Manage Events</h2>
                    <a href="add-events.php" class="btn btn-primary">+ Add New Event</a>
                </div>

                <div class="table-responsive">
                    <?php if (empty($events)): ?>
                        <div class="empty-state">
                            <p>📅 No events found</p>
                            <a href="add-events.php" class="btn btn-primary">Add your first event</a>
                        </div>
                    <?php else: ?>
                        <table class="destinations-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Date</th>
                                    <th>Location</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($events as $index => $site): ?>
                                    <tr>
                <td class="table-image">
                                            <?php if (!empty($site['image'])): ?>
<img src="<?php echo htmlspecialchars($site['image']); ?>" alt="<?php echo htmlspecialchars($site['name']); ?>" loading="lazy">
                                            <?php else: ?>
                                                <span class="no-image">No Image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($site['name']); ?></strong>
                                        </td>
                                        <td>
                                            <?php
                                            if (!empty($site['event_date'])) {
                                                echo htmlspecialchars(date('M j, Y', strtotime($site['event_date'])));
                                            } else {
                                                echo '<span class="no-image">Not set</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($site['location'] ?? 'N/A'); ?>
                                        </td>

                                        <td class="action-buttons">
                                            <a href="add-events.php?id=<?php echo $site['id']; ?>" class="btn btn-small btn-edit">Edit</a>
</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($currentTab === 'festivals'): ?>
                <div class="dashboard-header">
                    <h2>Manage Festivals</h2>
                    <a href="add-festival.php" class="btn btn-primary">+ Add New Festival</a>
                </div>

                <div class="table-responsive">
                    <?php if (empty($festivals)): ?>
                        <div class="empty-state">
                            <p>🎉 No festivals found</p>
                            <a href="add-festival.php" class="btn btn-primary">Add your first festival</a>
                        </div>
                    <?php else: ?>
                        <table class="destinations-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Date</th>
                                    <th>Highlights</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($festivals as $index => $festival): ?>
                                    <tr>
                                        <td class="table-image">
                                            <?php if (!empty($festival['image'])): ?>
                                                <img src="<?php echo htmlspecialchars($festival['image']); ?>" alt="<?php echo htmlspecialchars($festival['name']); ?>" loading="lazy">
                                            <?php else: ?>
                                                <span class="no-image">No Image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($festival['name']); ?></strong>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($festival['date'] ?? 'N/A'); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars(substr($festival['highlights'] ?? '', 0, 50)); ?><?php echo strlen($festival['highlights'] ?? '') > 50 ? '...' : ''; ?>
                                        </td>
                                        <td class="action-buttons">
                                            <a href="add-festival.php?id=<?php echo $index; ?>" class="btn btn-small btn-edit">Edit</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($currentTab === 'hotels'): ?>
                <?php
                // Calculate category counts
                $categoryCounts = [
                    'all' => count($hotels),
                    'dot_accredited' => 0,
                    'locally_certified' => 0
                ];
                foreach ($hotels as $hotel) {
                    $category = normalizeHotelCategory($hotel['category'] ?? '');
                    if ($category === 'DOT Accredited') {
                        $categoryCounts['dot_accredited']++;
                    } elseif ($category === 'Locally Certified') {
                        $categoryCounts['locally_certified']++;
                    }
                }
                $currentCategoryFilter = $_GET['category_filter'] ?? 'all';
                $filteredHotels = $currentCategoryFilter === 'all'
                    ? $hotels
                    : array_filter($hotels, function($hotel) use ($currentCategoryFilter) {
                        $category = normalizeHotelCategory($hotel['category'] ?? '');
                        if ($currentCategoryFilter === 'dot_accredited') {
                            return $category === 'DOT Accredited';
                        } elseif ($currentCategoryFilter === 'locally_certified') {
                            return $category === 'Locally Certified';
                        }
                        return false;
                    });
                ?>
                <div class="dashboard-header">
                    <h2>Manage Hotels</h2>
                    <a href="add-hotel.php" class="btn btn-primary">+ Add New Hotel</a>
                </div>

                <div class="status-filters">
                    <a href="?tab=hotels&category_filter=all" class="status-filter-btn <?php echo $currentCategoryFilter === 'all' ? 'active' : ''; ?>">
                        All <span class="count"><?php echo $categoryCounts['all']; ?></span>
                    </a>
                    <a href="?tab=hotels&category_filter=dot_accredited" class="status-filter-btn <?php echo $currentCategoryFilter === 'dot_accredited' ? 'active' : ''; ?>">
                        DOT Accredited <span class="count"><?php echo $categoryCounts['dot_accredited']; ?></span>
                    </a>
                    <a href="?tab=hotels&category_filter=locally_certified" class="status-filter-btn <?php echo $currentCategoryFilter === 'locally_certified' ? 'active' : ''; ?>">
                        Locally Certified <span class="count"><?php echo $categoryCounts['locally_certified']; ?></span>
                    </a>
                </div>

                <style>
                    .type-badge {
                        background: transparent;
                        color: var(--dark-gray);
                        border: none;
                        padding: 0;
                    }
                </style>

                <div class="table-responsive">
                    <?php if (empty($filteredHotels)): ?>
                        <div class="empty-state">
                            <p>🏨 No hotels found</p>
                            <a href="add-hotel.php" class="btn btn-primary">Add your first hotel</a>
                        </div>
                    <?php else: ?>
                        <table class="destinations-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filteredHotels as $hotel): ?>
                                    <tr>
                                        <td class="table-image">
                                            <?php if (!empty($hotel['image'])): ?>
                                                <img src="<?php echo htmlspecialchars($hotel['image']); ?>" alt="<?php echo htmlspecialchars($hotel['name']); ?>" loading="lazy">
                                            <?php else: ?>
                                                <span class="no-image">No Image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($hotel['name']); ?></strong>
                                        </td>
                                        <td>
                                            <span class="type-badge"><?php echo htmlspecialchars(normalizeHotelCategory($hotel['category'] ?? '')); ?></span>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($hotel['price']); ?></strong>
                                        </td>
                                        <td class="action-buttons">
                                            <a href="add-hotel.php?id=<?php echo $hotel['id']; ?>" class="btn btn-small btn-edit">Edit</a>
                                            <a href="hotel-gallery.php?hotel_id=<?php echo (int)$hotel['id']; ?>" class="btn btn-small btn-primary" style="padding:0.35rem 0.6rem;">Manage Photos</a>


                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endif; ?>


            <?php if ($currentTab === 'restaurants'): ?>
                <div class="dashboard-header">
                    <h2>Manage Restaurants</h2>
                    <a href="add-restaurant.php" class="btn btn-primary">+ Add New Restaurant</a>
                </div>

                <div class="table-responsive">
                    <?php if (empty($restaurants)): ?>
                        <div class="empty-state">
                            <p>🍽️ No restaurants found</p>
                            <a href="add-restaurant.php" class="btn btn-primary">Add your first restaurant</a>
                        </div>
                    <?php else: ?>
                        <table class="destinations-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($restaurants as $restaurant): ?>
                                    <tr>
                                        <td class="table-image">
                                            <?php if (!empty($restaurant['image'])): ?>
                                                <img src="<?php echo htmlspecialchars($restaurant['image']); ?>" alt="<?php echo htmlspecialchars($restaurant['name']); ?>" loading="lazy">
                                            <?php else: ?>
                                                <span class="no-image">No Image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($restaurant['name']); ?></strong>
                                        </td>
                                        <td class="action-buttons">
                                            <a href="add-restaurant.php?id=<?php echo $restaurant['id']; ?>" class="btn btn-small btn-edit">Edit</a>
                                            <a href="restaurant-gallery.php?restaurant_id=<?php echo (int)$restaurant['id']; ?>" class="btn btn-small btn-primary" style="padding:0.35rem 0.6rem;">Manage Photos</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($currentTab === 'certification'): ?>
                <?php
                // Calculate status counts
                $statusCounts = [
                    'all' => count($certificationApplications),
                    'pending' => 0,
                    'approved' => 0,
                    'rejected' => 0
                ];
                foreach ($certificationApplications as $app) {
                    $status = $app['status'] ?? 'pending';
                    if (isset($statusCounts[$status])) {
                        $statusCounts[$status]++;
                    }
                }
                $currentFilter = $_GET['status_filter'] ?? 'all';
                $filteredApplications = $currentFilter === 'all'
                    ? $certificationApplications
                    : array_filter($certificationApplications, function($app) use ($currentFilter) {
                        return ($app['status'] ?? 'pending') === $currentFilter;
                    });
                ?>
                <div class="dashboard-header">
                    <h2>Manage Certification Applications</h2>
                </div>

                <div class="status-filters">
                    <a href="?tab=certification&status_filter=all" class="status-filter-btn <?php echo $currentFilter === 'all' ? 'active' : ''; ?>">
                        All <span class="count"><?php echo $statusCounts['all']; ?></span>
                    </a>
                    <a href="?tab=certification&status_filter=pending" class="status-filter-btn <?php echo $currentFilter === 'pending' ? 'active' : ''; ?>">
                        Pending <span class="count"><?php echo $statusCounts['pending']; ?></span>
                    </a>
                    <a href="?tab=certification&status_filter=approved" class="status-filter-btn <?php echo $currentFilter === 'approved' ? 'active' : ''; ?>">
                        Approved <span class="count"><?php echo $statusCounts['approved']; ?></span>
                    </a>
                    <a href="?tab=certification&status_filter=rejected" class="status-filter-btn <?php echo $currentFilter === 'rejected' ? 'active' : ''; ?>">
                        Rejected <span class="count"><?php echo $statusCounts['rejected']; ?></span>
                    </a>
                </div>

                <div class="table-responsive">
                    <?php if (empty($filteredApplications)): ?>
                        <div class="empty-state">
                            <p>📋 No certification applications found</p>
                        </div>
                    <?php else: ?>
                        <table class="destinations-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Track</th>
                                    <th>Establishment</th>
                                    <th>Owner</th>
                                    <th>Category</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($filteredApplications as $app): ?>
                                    <tr>
                                        <td><?php echo (int) $app['id']; ?></td>
                                        <td>
                                            <span class="type-badge badge-track">
                                                <?php echo ($app['certification_track'] === 'dot_accredited') ? 'DOT Accredited' : 'Locally Certified'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($app['establishment_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($app['owner_name']); ?></td>
                                        <td><?php echo htmlspecialchars($app['category']); ?></td>
                                        <td><?php echo htmlspecialchars($app['application_date']); ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="update-application-status">
                                                <input type="hidden" name="id" value="<?php echo $app['id']; ?>">
                                                <select name="status" onchange="this.form.submit()" class="status-select">
                                                    <option value="pending" <?php echo ($app['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="approved" <?php echo ($app['status'] === 'approved') ? 'selected' : ''; ?>>Approved</option>
                                                    <option value="rejected" <?php echo ($app['status'] === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td class="action-buttons">
                                            <a href="view-certification.php?id=<?php echo $app['id']; ?>" class="btn btn-small btn-view">View</a>
                                            <button type="button" onclick="window.open('view-certification.php?id=<?php echo $app['id']; ?>', '_blank').print()" class="btn btn-small btn-print">Print</button>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this application?');">
                                                <input type="hidden" name="action" value="delete-application">
                                                <input type="hidden" name="id" value="<?php echo $app['id']; ?>">
                                                <button type="submit" class="btn btn-small btn-delete">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                <style>
                    .status-select {
                        padding: 0.25rem 0.5rem;
                        border-radius: 4px;
                        border: 1px solid #ddd;
                        font-size: 0.875rem;
                    }
                    .badge-track {
                        background-color: transparent;
                        color: var(--dark-green);
                        font-weight: 600;
                    }
                </style>
            <?php endif; ?>

            <?php if ($currentTab === 'carousel'): ?>
                <div class="dashboard-header">
                    <h2>Manage Homepage Carousel</h2>
                    <a href="add-carousel-slide.php" class="btn btn-primary">+ Add Carousel Slide</a>
                </div>

                <div class="table-responsive">
                    <?php if (empty($carouselSlides)): ?>
                        <div class="empty-state">
                            <p>No carousel slides found</p>
                            <a href="add-carousel-slide.php" class="btn btn-primary">Add your first slide</a>
                        </div>
                    <?php else: ?>
                        <table class="destinations-table">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Main Heading</th>
                                    <th>Tagline</th>
                                    <th>Order</th>
                                    <th>Visible</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($carouselSlides as $carouselSlide): ?>
                                    <?php
                                    $carouselImg = $carouselSlide['image'] ?? '';
                                    if ($carouselImg && strpos($carouselImg, 'http') !== 0 && strpos($carouselImg, '../') !== 0) {
                                        $carouselImg = '../' . ltrim($carouselImg, '/');
                                    }
                                    $titlePreview = str_replace("\n", ' ', $carouselSlide['title'] ?? '');
                                    ?>
                                    <tr>
                                        <td class="table-image">
                                            <?php if (!empty($carouselSlide['image'])): ?>
                                                <img src="<?php echo htmlspecialchars($carouselImg); ?>" alt="<?php echo htmlspecialchars($titlePreview); ?>" loading="lazy">
                                            <?php else: ?>
                                                <span class="no-image">No Image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong><?php echo htmlspecialchars($titlePreview); ?></strong></td>
                                        <td><?php echo htmlspecialchars($carouselSlide['tagline'] ?? ''); ?></td>
                                        <td><?php echo (int) ($carouselSlide['sort_order'] ?? 0); ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle-carousel-active">
                                                <input type="hidden" name="id" value="<?php echo $carouselSlide['id']; ?>">
                                                <button type="submit" class="featured-btn <?php echo !empty($carouselSlide['active']) ? 'active' : ''; ?>">
                                                    <?php echo !empty($carouselSlide['active']) ? 'Visible' : 'Hidden'; ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td class="action-buttons">
                                            <a href="add-carousel-slide.php?id=<?php echo $carouselSlide['id']; ?>" class="btn btn-small btn-edit">Edit</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="admin-footer">
    </footer>

    <script src="../assets/js/admin.js"></script>
    <script>
    function filterAdminTable() {
        const input = document.getElementById('adminSearch');
        const filter = input.value.toLowerCase();
        const table = document.querySelector('.destinations-table');
        if (!table) return;
        const tbody = table.getElementsByTagName('tbody')[0];
        const rows = tbody.getElementsByTagName('tr');

        for (let i = 0; i < rows.length; i++) {
            const rowText = rows[i].textContent.toLowerCase();
            if (rowText.indexOf(filter) > -1) {
                rows[i].style.display = '';
            } else {
                rows[i].style.display = 'none';
            }
        }
    }
    </script>
</body>
</html>
