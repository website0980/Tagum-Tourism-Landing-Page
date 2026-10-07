<?php

/**
 * Build hero copy + theme for each calendar month (homepage month strip).
 */
function getPrimaryCarouselSlideForMonth(array $carouselSlides, int $monthNo): ?array
{
    foreach ($carouselSlides as $slide) {
        if ((int) ($slide['event_month'] ?? 0) === $monthNo) {
            return $slide;
        }
    }

    return null;
}

function getValidHeroTheme(?string $value): string
{
    $validThemes = [
        'theme-rich',
        'theme-soft',
        'theme-chinese-new-year',
        'theme-musikahan',
        'theme-musikahan-blue',
        'theme-araw-green',
    ];

    return in_array($value, $validThemes, true) ? $value : 'theme-soft';
}

function normalizeTitleLines($value): array
{
    if (is_array($value)) {
        $clean = array_values(array_filter(
            array_map(function ($line) { return is_scalar($line) ? trim((string) $line) : ''; }, $value),
            function ($line) { return $line !== ''; }
        ));
        if (count($clean) > 0) {
            return $clean;
        }
    }

    $text = trim((string) ($value ?? ''));
    if ($text === '') {
        return ['FLORES', 'DE MAYO'];
    }

    $lines = array_filter(array_map(function ($line) { return trim($line); }, explode("\n", $text)), function ($line) { return $line !== ''; });
    if (count($lines) > 1) {
        return $lines;
    }

    $words = array_filter(array_map(function ($word) { return trim($word); }, explode(' ', $text)), function ($word) { return $word !== ''; });
    if (count($words) <= 2) {
        return $words;
    }

    $half = intdiv(count($words) + 1, 2);
    return [implode(' ', array_slice($words, 0, $half)), implode(' ', array_slice($words, $half))];
}

function heroMonthDefaultProfile(array $homepageSettings): array
{
    $titleLines = normalizeTitleLines((string) ($homepageSettings['hero_title'] ?? 'FLORES\nDE MAYO'));
    return [
        'theme' => getValidHeroTheme((string) ($homepageSettings['hero_theme'] ?? 'theme-soft')),
        'titleLines' => $titleLines,
        'script' => trim((string) ($homepageSettings['hero_script'] ?? 'Faith in Bloom.')),
        'meta1' => trim((string) ($homepageSettings['hero_meta_1'] ?? 'Tradition')),
        'meta2' => trim((string) ($homepageSettings['hero_meta_2'] ?? 'Fortune')),
        'meta3' => trim((string) ($homepageSettings['hero_meta_3'] ?? 'Unity')),
        'description' => trim((string) ($homepageSettings['hero_description'] ?? '')),
        'monthEvent' => '',
        'cta' => trim((string) ($homepageSettings['hero_cta'] ?? 'EXPLORE FESTIVAL')),
        'features' => [
            ['icon' => '✿', 'title' => 'TRADITION', 'text' => 'Floral offerings and cultural practices.'],
            ['icon' => '✦', 'title' => 'FORTUNE', 'text' => 'Celebrating blessings, abundance, and joy.'],
            ['icon' => '◌', 'title' => 'UNITY', 'text' => 'Bringing families and communities together.'],
        ],
    ];
}

function buildHeroMonthProfilesFromPackages(array $packages, array $homepageSettings): array
{
    $default = heroMonthDefaultProfile($homepageSettings);
    $profiles = [];
    foreach ($packages as $package) {
        $month = max(1, min(12, (int) ($package['package_month'] ?? 0)));
        if (empty($package['active'])) {
            continue;
        }
        $theme = in_array((string) ($package['theme'] ?? ''), ['theme-chinese-new-year', 'theme-musikahan-blue', 'theme-soft', 'theme-araw-green'], true)
            ? (string) $package['theme']
            : 'theme-chinese-new-year';
        $features = $theme === 'theme-musikahan-blue'
            ? [
                ['icon' => '🎵', 'title' => 'MELODY', 'text' => 'Harmonious rhythms and musical traditions.'],
                ['icon' => '🎶', 'title' => 'RHYTHM', 'text' => 'Beats that bring communities together.'],
                ['icon' => '🎸', 'title' => 'HARMONY', 'text' => 'Creating unity through the universal language of music.'],
            ]
            : ($theme === 'theme-soft'
                ? [
                    ['icon' => '🌸', 'title' => 'HERITAGE', 'text' => 'Local traditions passed down through generations.'],
                    ['icon' => '🤝', 'title' => 'COMMUNITY', 'text' => 'Barangay events that welcome visitors and residents.'],
                    ['icon' => '🎭', 'title' => 'CELEBRATION', 'text' => 'Music, food, and culture in the spring air.'],
                ]
                : ($theme === 'theme-araw-green'
                    ? [
                        ['icon' => '🏙️', 'title' => 'FOUNDING', 'text' => 'Honoring the day Tagum City was founded.'],
                        ['icon' => '🎆', 'title' => 'CELEBRATION', 'text' => 'Fireworks, parades, and city-wide festivities.'],
                        ['icon' => '🤝', 'title' => 'COMMUNITY', 'text' => 'Tagumenyos coming together in shared pride.'],
                    ]
                    : [
                        ['icon' => '🏮', 'title' => 'TRADITION', 'text' => 'Lanterns, lion dances, and festive customs.'],
                        ['icon' => '福', 'title' => 'FORTUNE', 'text' => 'Welcoming prosperity and good luck in the new year.'],
                        ['icon' => '團', 'title' => 'UNITY', 'text' => 'Families reunite to celebrate the new year together.'],
                    ]));
        $profiles[(string) $month] = [
            'theme' => $theme,
            'titleLines' => normalizeTitleLines((string) ($package['title'] ?? '')),
            'script' => trim((string) ($package['script'] ?? '')),
            'meta1' => trim((string) ($package['meta_1'] ?? '')),
            'meta2' => trim((string) ($package['meta_2'] ?? '')),
            'meta3' => trim((string) ($package['meta_3'] ?? '')),
            'description' => trim((string) ($package['description'] ?? '')),
            'monthEvent' => '',
            'cta' => trim((string) ($package['cta'] ?? 'EXPLORE FESTIVAL')),
            'features' => $features,
            'images' => [
                trim((string) ($package['image_1'] ?? '')),
                trim((string) ($package['image_2'] ?? '')),
                trim((string) ($package['image_3'] ?? '')),
            ],
        ];
    }
    return $profiles;
}

function buildHeroMonthProfiles(array $carouselSlides, array $homepageSettings, array $eventMap): array
{
    $default = heroMonthDefaultProfile($homepageSettings);
    $profiles = [];

    $january = [
        'theme' => 'theme-chinese-new-year',
        'titleLines' => ['CHINESE', 'NEW YEAR'],
        'script' => 'Prosperity in Bloom.',
        'meta1' => 'Tradition',
        'meta2' => 'Fortune',
        'meta3' => 'Unity',
        'description' => 'Welcome the lunar new year with lanterns, lion dances, and festive gatherings that honor heritage and good fortune across Tagum City.',
        'monthEvent' => '',
        'cta' => 'EXPLORE FESTIVAL',
        'features' => [
            ['icon' => '🏮', 'title' => 'TRADITION', 'text' => 'Lanterns, lion dances, and festive customs.'],
            ['icon' => '福', 'title' => 'FORTUNE', 'text' => 'Welcoming prosperity and good luck in the new year.'],
            ['icon' => '團', 'title' => 'UNITY', 'text' => 'Families reunite to celebrate the new year together.'],
        ],
    ];

    $februarySlide = getPrimaryCarouselSlideForMonth($carouselSlides, 2);
    $february = [
        'theme' => 'theme-musikahan-blue',
        'titleLines' => ['Musikahan'],
        'script' => trim((string) ($februarySlide['tagline'] ?? 'Harmony in Rhythm.')),
        'meta1' => 'Melody',
        'meta2' => 'Rhythm',
        'meta3' => 'Harmony',
        'description' => trim((string) ($februarySlide['description'] ?? 'A celebration of music, rhythm, and harmony bringing together local artists and musicians in Tagum City.')),
        'monthEvent' => 'Music • Culture • Tagum',
        'cta' => trim((string) ($februarySlide['btn_primary_text'] ?? 'EXPLORE FESTIVAL')),
        'features' => [
            ['icon' => '🎵', 'title' => 'MELODY', 'text' => 'Harmonious rhythms and musical traditions.'],
            ['icon' => '🎶', 'title' => 'RHYTHM', 'text' => 'Beats that bring communities together.'],
            ['icon' => '🎸', 'title' => 'HARMONY', 'text' => 'Creating unity through the universal language of music.'],
        ],
    ];
    if ($februarySlide && trim((string) ($februarySlide['title'] ?? '')) !== '') {
        $slideTitle = trim((string) $februarySlide['title']);
        $february['titleLines'] = normalizeTitleLines($slideTitle);
    }

    $marchSlide = getPrimaryCarouselSlideForMonth($carouselSlides, 3);
    $march = array_merge($default, [
        'theme' => 'theme-soft',
        'titleLines' => ['SPRING', 'IN TAGUM'],
        'script' => 'Culture in Bloom.',
        'meta1' => 'Heritage',
        'meta2' => 'Community',
        'meta3' => 'Celebration',
        'description' => trim((string) ($marchSlide['description'] ?? 'As the city awakens for festival season, discover outdoor events, local crafts, and community gatherings across Tagum.')),
        'monthEvent' => '',
        'cta' => 'EXPLORE FESTIVAL',
        'features' => [
            ['icon' => '🌸', 'title' => 'HERITAGE', 'text' => 'Local traditions passed down through generations.'],
            ['icon' => '🤝', 'title' => 'COMMUNITY', 'text' => 'Barangay events that welcome visitors and residents.'],
            ['icon' => '🎭', 'title' => 'CELEBRATION', 'text' => 'Music, food, and culture in the spring air.'],
        ],
    ]);

    $may = array_merge($default, [
        'theme' => 'theme-soft',
        'titleLines' => $default['titleLines'],
        'monthEvent' => '',
        'features' => [
            ['icon' => '✿', 'title' => 'TRADITION', 'text' => 'Floral offerings and cultural practices.'],
            ['icon' => '✦', 'title' => 'FORTUNE', 'text' => 'Celebrating blessings, abundance, and joy.'],
            ['icon' => '◌', 'title' => 'UNITY', 'text' => 'Bringing families and communities together.'],
        ],
    ]);

    $overrides = [
        1 => $january,
        2 => $february,
        3 => $march,
        5 => $may,
    ];

    foreach (range(1, 12) as $monthNo) {
        if (isset($overrides[$monthNo])) {
            $profile = $overrides[$monthNo];
        } else {
            $profile = $default;
            $slide = getPrimaryCarouselSlideForMonth($carouselSlides, $monthNo);

            if ($slide) {
                $slideTitle = trim((string) ($slide['title'] ?? ''));
                if ($slideTitle !== '') {
                    $profile['titleLines'] = normalizeTitleLines($slideTitle);
                }
                $profile['script'] = trim((string) ($slide['tagline'] ?? $profile['script']));
                $profile['description'] = trim((string) ($slide['description'] ?? $profile['description']));
                $profile['cta'] = trim((string) ($slide['btn_primary_text'] ?? $profile['cta']));
            }
        }

        $profile['monthEvent'] = '';
        $event = $eventMap[$monthNo][0] ?? null;
        if ($event) {
            $profile['monthEvent'] = trim((string) ($event['name'] ?? ''));
        }

        $profile['theme'] = getValidHeroTheme((string) ($profile['theme'] ?? 'theme-soft'));
        $profiles[(string) $monthNo] = $profile;
    }

    return $profiles;
}