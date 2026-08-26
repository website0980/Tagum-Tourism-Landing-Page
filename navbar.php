<?php
$isSubdir = (strpos(strtolower($_SERVER['PHP_SELF'] ?? ''), 'module') !== false);
$isHome = basename($_SERVER['PHP_SELF'] ?? '') === 'index.php';
$prefix = $isHome ? '' : '../index.php';
$logoPath = $isSubdir ? '../images/TagumTourism.jpg' : 'images/TagumTourism.jpg';
$adminPath = $isSubdir ? '../admin/login.php' : 'admin/login.php';
$jsPath = $isSubdir ? '../js/navbar.js' : 'js/navbar.js';
?>
<link rel="stylesheet" href="<?php echo $isSubdir ? '../css/mobile-navbar.css' : 'css/mobile-navbar.css'; ?>">
<nav class="navbar">
    <div class="nav-container">
        <div class="logo" style="display: flex; align-items: center; gap: 10px; min-width: 0;">
            <img src="<?php echo $logoPath; ?>" alt="Tagum Tourism Logo" class="logo-img" style="height: 48px; width: 48px; object-fit: contain; order:1;">
            <button class="hamburger" aria-label="Toggle Menu" aria-expanded="false" onclick="toggleMobileMenu(event)" style="order:3; margin-left: 8px;">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
        <ul class="nav-menu" id="mobile-menu">


            <li><a href="<?php echo $prefix; ?>#home" class="nav-link">Home</a></li>
            <li><a href="<?php echo $prefix; ?>#explore" class="nav-link">Explore</a></li>
            <li><a href="<?php echo $prefix; ?>#experiences" class="nav-link">Experiences</a></li>
            <li><a href="<?php echo $prefix; ?>#cultural-heritage" class="nav-link smooth-scroll">Cultural Heritage</a></li>
            <li><a href="<?php echo $prefix; ?>#plan" class="nav-link">Plan</a></li>
            <li><a href="<?php echo $prefix; ?>#hotels-restaurants" class="nav-link hotels-restaurants-link">Hotels & Restaurants</a></li>
            <li><a href="<?php echo $prefix; ?>#contact" class="nav-link">Contact</a></li>

            <li><a href="<?php echo $adminPath; ?>" class="admin-btn nav-link">Admin</a></li>
        </ul>
    </div>
</nav>
<script src="<?php echo $jsPath; ?>"></script>
