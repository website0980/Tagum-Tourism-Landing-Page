<?php
/**
 * Shared helpers for events listing and month grouping.
 */

function ensureEventDateColumn($db = null) {
    $closeDb = false;
    if ($db === null) {
        $dbFile = dirname(__DIR__) . '/database.db';
        if (!file_exists($dbFile)) {
            return false;
        }
        $db = new SQLite3($dbFile);
        $closeDb = true;
    }

    $columns = [];
    $result = $db->query('PRAGMA table_info(events)');
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $columns[] = $row['name'];
    }

    if (!in_array('event_date', $columns, true)) {
        $db->exec('ALTER TABLE events ADD COLUMN event_date TEXT');
    }

    if ($closeDb) {
        $db->close();
    }
    return true;
}

function loadEvents($dbFile = null) {
    $dbFile = $dbFile ?: dirname(__DIR__) . '/database.db';
    if (!file_exists($dbFile)) {
        return [];
    }

    try {
        $db = new SQLite3($dbFile);
        ensureEventDateColumn($db);
        $result = $db->query("
            SELECT * FROM events
            ORDER BY
                CASE WHEN event_date IS NULL OR event_date = '' THEN 1 ELSE 0 END,
                event_date ASC,
                name ASC
        ");
        $events = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $events[] = $row;
        }
        $db->close();
        return $events;
    } catch (Exception $e) {
        return [];
    }
}

function formatEventDate($date) {
    if (empty($date)) {
        return '';
    }
    $ts = strtotime($date);
    if ($ts === false) {
        return $date;
    }
    return date('F j, Y', $ts);
}

function formatEventMonthLabel($monthKey) {
    if ($monthKey === 'undated') {
        return 'Date To Be Announced';
    }
    $ts = strtotime($monthKey . '-01');
    if ($ts === false) {
        return $monthKey;
    }
    return date('F Y', $ts);
}

function groupEventsByMonth(array $events) {
    $grouped = [];

    foreach ($events as $event) {
        $date = trim((string) ($event['event_date'] ?? ''));
        if ($date === '') {
            $key = 'undated';
        } else {
            $ts = strtotime($date);
            $key = $ts !== false ? date('Y-m', $ts) : 'undated';
        }

        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'label' => formatEventMonthLabel($key),
                'events' => [],
                'sort' => $key === 'undated' ? '9999-12' : $key,
            ];
        }
        $grouped[$key]['events'][] = $event;
    }

    uasort($grouped, function ($a, $b) {
        return $a['sort'] <=> $b['sort'];
    });

    return $grouped;
}

function fixEventImagePath($imagePath) {
    if (empty($imagePath)) {
        return '';
    }
    if (strpos($imagePath, '../../') === 0) {
        $imagePath = str_replace('../../', '../', $imagePath);
    }
    if (strpos($imagePath, 'cultural-sites') !== false) {
        $imagePath = str_replace('cultural-sites', 'events', $imagePath);
    }
    return $imagePath;
}

function getDefaultOpenMonthKey(array $eventsByMonth) {
    if (empty($eventsByMonth)) {
        return null;
    }

    $currentMonth = date('Y-m');
    if (isset($eventsByMonth[$currentMonth])) {
        return $currentMonth;
    }

    foreach ($eventsByMonth as $key => $monthData) {
        if ($key !== 'undated' && $key >= $currentMonth) {
            return $key;
        }
    }

    return array_key_first($eventsByMonth);
}

function normalizeFestivalRelationText($text) {
    $text = strtolower(trim((string) $text));
    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

function getFestivalRelatedEventOrder(array $festival, array $events) {
    $explicitEventId = null;
    if (isset($festival['related_event_id']) && $festival['related_event_id'] !== '' && $festival['related_event_id'] !== null) {
        $explicitEventId = (int) $festival['related_event_id'];
    }

    if ($explicitEventId !== null) {
        foreach ($events as $index => $event) {
            if ((int) ($event['id'] ?? 0) === $explicitEventId) {
                return $index;
            }
        }
    }

    $explicitEventName = trim((string) ($festival['related_event_name'] ?? ''));
    if ($explicitEventName !== '') {
        foreach ($events as $index => $event) {
            $eventName = trim((string) ($event['name'] ?? ''));
            if ($eventName !== '' && (stripos($eventName, $explicitEventName) !== false || stripos($explicitEventName, $eventName) !== false)) {
                return $index;
            }
        }
    }

    $festivalName = trim((string) ($festival['name'] ?? ''));
    $festivalDescription = trim((string) ($festival['description'] ?? ''));
    $festivalText = $festivalName . ' ' . $festivalDescription;

    if ($festivalText === '') {
        return PHP_INT_MAX;
    }

    $festivalNorm = normalizeFestivalRelationText($festivalText);
    if ($festivalNorm === '') {
        return PHP_INT_MAX;
    }

    $stopWords = ['festival', 'event', 'the', 'and', 'of', 'for', 'in', 'to', 'a', 'an', 'on', 'with', 'city', 'tagum'];
    $bestIndex = PHP_INT_MAX;
    $bestScore = -1;

    foreach ($events as $index => $event) {
        $eventName = trim((string) ($event['name'] ?? ''));
        $eventDescription = trim((string) ($event['description'] ?? ''));
        $eventText = $eventName . ' ' . $eventDescription;
        if ($eventText === '') {
            continue;
        }

        $eventNorm = normalizeFestivalRelationText($eventText);
        if ($eventNorm === '') {
            continue;
        }

        $score = 0;
        if ($festivalNorm === $eventNorm) {
            $score = 1000;
        } elseif (strpos($festivalNorm, $eventNorm) !== false || strpos($eventNorm, $festivalNorm) !== false) {
            $score = 900;
        } else {
            $festivalTokens = array_values(array_unique(array_filter(explode(' ', $festivalNorm), function ($token) use ($stopWords) {
                return $token !== '' && !in_array($token, $stopWords, true);
            })));
            $eventTokens = array_values(array_unique(array_filter(explode(' ', $eventNorm), function ($token) use ($stopWords) {
                return $token !== '' && !in_array($token, $stopWords, true);
            })));

            $commonTokens = array_intersect($festivalTokens, $eventTokens);
            if (!empty($commonTokens)) {
                $score = 100 + (count($commonTokens) * 20);
            }
        }

        if ($score > $bestScore || ($score === $bestScore && $index < $bestIndex)) {
            $bestScore = $score;
            $bestIndex = $index;
        }
    }

    return $bestScore > 0 ? $bestIndex : PHP_INT_MAX;
}

function sortFestivalsByRelatedEvents(array $festivals, array $events) {
    usort($festivals, function ($a, $b) use ($events) {
        $aOrder = getFestivalRelatedEventOrder($a, $events);
        $bOrder = getFestivalRelatedEventOrder($b, $events);

        if ($aOrder !== $bOrder) {
            return $aOrder <=> $bOrder;
        }

        return strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
    });

    return $festivals;
}
