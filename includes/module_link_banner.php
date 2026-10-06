<?php
/**
 * Reusable module link banner/button.
 */
if (!function_exists('getModuleLink')) {
    require_once __DIR__ . '/module_links.php';
}

function renderModuleLinkBanner($moduleKey, $context, $placement, $variant = 'banner', $track = null) {
    if ($track === null) {
        if ($placement === 'hotels_dot') {
            $track = 'dot';
        } elseif ($placement === 'hotels_local') {
            $track = 'local';
        }
    }
    $expectedPlacement = $track ? moduleLinkPlacementForTrack($track) : $placement;
    if ($expectedPlacement !== '' && $expectedPlacement !== $placement) {
        return;
    }
    if (!moduleLinkShouldShow($moduleKey, $placement)) {
        return;
    }
    $link = getModuleLink($moduleKey, $context, $track);
    if (!$link) {
        return;
    }
    $label = htmlspecialchars($link['label']);
    $desc = htmlspecialchars($link['description']);
    $url = htmlspecialchars($link['url']);
    $icon = $link['icon'] ?? '';
    $cssClass = htmlspecialchars($link['css_class']);
    $btnClass = htmlspecialchars($link['button_class']);

    if ($variant === 'compact') {
        echo '<a href="' . $url . '" class="' . $btnClass . ' ' . $cssClass . ' module-link-compact">';
        echo '<span class="module-link-icon">' . $icon . '</span> ' . $label;
        echo '</a>';
        return;
    }

    echo '<aside class="module-link-banner ' . $cssClass . '" role="complementary">';
    echo '<div class="module-link-banner-content">';
    echo '<span class="module-link-banner-icon" aria-hidden="true">' . $icon . '</span>';
    echo '<div class="module-link-banner-text">';
    echo '<h3>' . $label . '</h3>';
    echo '<p>' . $desc . '</p>';
    echo '</div>';
    echo '<a href="' . $url . '" class="btn btn-primary ' . $btnClass . '">Start Application</a>';
    echo '</div></aside>';
}

function renderIndexCertificationPromo($context = 'from_index') {
    if (!moduleLinkShouldShow('accommodation_certification', 'hotels_banner')) {
        return;
    }
    $tracks = getCertificationTrackOptions();
    $dotLink = getModuleLink('accommodation_certification', $context, 'dot');
    $localLink = getModuleLink('accommodation_certification', $context, 'local');
    if (!$dotLink || !$localLink) {
        return;
    }
    ?>
    <div class="cert-index-promo" id="certification-application">
        <div class="cert-index-promo-inner">
            <div class="cert-index-promo-icon" aria-hidden="true">
                <svg class="mini-doc-icon" viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M19 14h18l9 9v27a4 4 0 0 1-4 4H19a4 4 0 0 1-4-4V18a4 4 0 0 1 4-4zm18 5v9h9" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M22 31h18M22 42h18" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
                </svg>
            </div>
            <div class="cert-index-promo-text">
                <h3>Accommodation Certification Application</h3>
                <p>Own a hotel, resort, or accommodation business? Apply online for DOT accreditation or local certification through the City Tourism and Cultural Office.</p>
            </div>
            <div class="cert-index-promo-actions">
                <a href="<?php echo htmlspecialchars($dotLink['url']); ?>" class="btn btn-primary cert-promo-btn cert-promo-dot">
                    DOT Accredited Application
                </a>
                <a href="<?php echo htmlspecialchars($localLink['url']); ?>" class="btn btn-primary cert-promo-btn cert-promo-local">
                    Locally Certified Application
                </a>
            </div>
        </div>
    </div>
    <?php
}
