<?php

require_once dirname(__DIR__) . '/includes/accommodation_form_helpers.php';

$errors = [];
$successId = null;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$data = parseAccommodationFormPost($_POST);
$errors = validateAccommodationApplication($data);

if (!empty($errors)) {
    return;
}

$successId = saveAccommodationApplication($data);

if ($successId) {
    // Preserve certification track so application-success can send user back to correct hotel tab.
    $track = $data['certification_track'] ?? null;
    $trackParam = ($track === 'dot_accredited') ? 'dot' : 'local';
    header('Location: application-success.php?id=' . (int)$successId . '&track=' . $trackParam);
    exit;
}

$errors[] = 'Unable to save your application. Please try again.';