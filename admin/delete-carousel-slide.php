<?php
require_once 'config.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php?tab=carousel');
    exit();
}

$csrf = $_POST['csrf_token'] ?? '';
if (!validateCsrfToken($csrf)) {
    header('Location: dashboard.php?tab=carousel&message=csrf_error');
    exit();
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id > 0) {
    deleteCarouselSlide($id);
}

header('Location: dashboard.php?tab=carousel&message=deleted');
exit();
