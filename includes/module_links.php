<?php
/**
 * Central registry for cross-module links.
 * Update paths and labels here — other modules only reference keys.
 */
function getCertificationTrackOptions() {
    return [
        'dot_accredited' => [
            'label' => 'DOT Accredited',
            'form_label' => 'DOT Accredited Hotels',
            'banner_label' => 'Apply for DOT Accreditation',
            'banner_description' => 'Submit your Tourism Accommodation Establishment application for DOT accreditation.',
            'placement' => 'hotels_dot',
            'query' => 'track=dot',
        ],
        'locally_certified' => [
            'label' => 'Locally Certified',
            'form_label' => 'Locally Certified Hotels',
            'banner_label' => 'Apply for Local Certification',
            'banner_description' => 'Submit your Tourism Accommodation Establishment application for local certification.',
            'placement' => 'hotels_local',
            'query' => 'track=local',
        ],
    ];
}

function normalizeCertificationTrack($track) {
    $track = strtolower(trim((string) $track));
    if (in_array($track, ['dot', 'dot_accredited', 'dot-accredited'], true)) {
        return 'dot_accredited';
    }
    if (in_array($track, ['local', 'locally_certified', 'locally-certified'], true)) {
        return 'locally_certified';
    }
    return '';
}

function getCertificationTrackLabel($track) {
    $options = getCertificationTrackOptions();
    return $options[$track]['label'] ?? 'Unspecified';
}

function getModuleLinksRegistry() {
    return [
        'accommodation_certification' => [
            'short_label' => 'Certification Application',
            'icon' => '📋',
            'paths' => [
                'from_root' => 'Certification Module/accommodation-form.php',
                'from_hotel_module' => '../Certification Module/accommodation-form.php',
                'from_certification_module' => 'accommodation-form.php',
                'from_index' => 'Certification Module/accommodation-form.php',
            ],
            'css_class' => 'module-link-certification',
            'button_class' => 'btn-certification-apply',
            'placements' => ['hotels_dot', 'hotels_local', 'hotels_banner'],
            'tracks' => getCertificationTrackOptions(),
        ],
    ];
}

function getModuleLink($key, $context = 'from_root', $track = null) {
    $registry = getModuleLinksRegistry();
    if (!isset($registry[$key])) {
        return null;
    }
    $item = $registry[$key];
    $url = $item['paths'][$context] ?? $item['paths']['from_root'] ?? '#';
    $normalizedTrack = normalizeCertificationTrack($track);
    if ($normalizedTrack !== '') {
        $trackConfig = $item['tracks'][$normalizedTrack] ?? null;
        if ($trackConfig) {
            $separator = (strpos($url, '?') !== false) ? '&' : '?';
            $url .= $separator . $trackConfig['query'];
            $item['label'] = $trackConfig['banner_label'];
            $item['description'] = $trackConfig['banner_description'];
            $item['track'] = $normalizedTrack;
            $item['track_label'] = $trackConfig['label'];
        }
    } else {
        $item['label'] = 'Accommodation Certification Application';
        $item['description'] = 'Apply for DOT accreditation or local certification for your tourism accommodation establishment.';
    }
    return array_merge($item, ['url' => $url]);
}

function moduleLinkShouldShow($key, $placement) {
    $link = getModuleLink($key);
    if (!$link) {
        return false;
    }
    return in_array($placement, $link['placements'] ?? [], true);
}

function moduleLinkPlacementForTrack($track) {
    $normalized = normalizeCertificationTrack($track);
    $options = getCertificationTrackOptions();
    return $options[$normalized]['placement'] ?? '';
}
