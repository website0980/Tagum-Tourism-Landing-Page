<?php

function ensureAccommodationApplicationsTable($db = null) {
    $closeDb = false;
    if ($db === null) {
        $dbFile = dirname(__DIR__) . '/database.db';
        if (!file_exists($dbFile)) {
            return false;
        }
        $db = new SQLite3($dbFile);
        $closeDb = true;
    }

    $schema = file_get_contents(dirname(__DIR__) . '/database/accommodation_form_schema.sql');
    if ($schema) {
        $db->exec($schema);
    }

    // Check if certification_track column exists, add it if missing
    $result = $db->query("PRAGMA table_info(accommodation_applications)");
    $hasCertificationTrack = false;
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        if ($row['name'] === 'certification_track') {
            $hasCertificationTrack = true;
            break;
        }
    }

    if (!$hasCertificationTrack) {
        $db->exec('ALTER TABLE accommodation_applications ADD COLUMN certification_track TEXT NOT NULL DEFAULT "locally_certified"');
    }

    // Add year_started column if missing
    $result = $db->query("PRAGMA table_info(accommodation_applications)");
    $hasYearStarted = false;
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        if ($row['name'] === 'year_started') {
            $hasYearStarted = true;
            break;
        }
    }
    if (!$hasYearStarted) {
        $db->exec('ALTER TABLE accommodation_applications ADD COLUMN year_started TEXT');
    }

    if ($closeDb) {
        $db->close();
    }
    return true;
}

function saveAccommodationApplication(array $data) {
    $dbFile = dirname(__DIR__) . '/database.db';
    if (!file_exists($dbFile)) {
        return false;
    }

    try {
        $db = new SQLite3($dbFile);
        ensureAccommodationApplicationsTable($db);

        $stmt = $db->prepare('
            INSERT INTO accommodation_applications (
                certification_track, application_type, application_date, establishment_name, owner_name, address,
                telephone, mobile_number, email, facebook, category,
                total_rooms, total_capacity, total_employees, male_employees, female_employees, year_started,
                room_types, amenities, previous_certificate, renewal_date, applicant_signature
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');

        $stmt->bindValue(1, $data['certification_track'] ?? 'locally_certified', SQLITE3_TEXT);
        $stmt->bindValue(2, $data['application_type'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(3, $data['application_date'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(4, $data['establishment_name'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(5, $data['owner_name'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(6, $data['address'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(7, $data['telephone'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(8, $data['mobile_number'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(9, $data['email'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(10, $data['facebook'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(11, $data['category'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(12, isset($data['total_rooms']) ? (int) $data['total_rooms'] : null, SQLITE3_INTEGER);
        $stmt->bindValue(13, isset($data['total_capacity']) ? (int) $data['total_capacity'] : null, SQLITE3_INTEGER);
        $stmt->bindValue(14, isset($data['total_employees']) ? (int) $data['total_employees'] : null, SQLITE3_INTEGER);
        $stmt->bindValue(15, isset($data['male_employees']) ? (int) $data['male_employees'] : null, SQLITE3_INTEGER);
        $stmt->bindValue(16, isset($data['female_employees']) ? (int) $data['female_employees'] : null, SQLITE3_INTEGER);
        $stmt->bindValue(17, trim($data['year_started'] ?? ''), SQLITE3_TEXT);
        $stmt->bindValue(18, json_encode($data['room_types'] ?? []), SQLITE3_TEXT);
        $stmt->bindValue(19, json_encode($data['amenities'] ?? []), SQLITE3_TEXT);
        $stmt->bindValue(20, $data['previous_certificate'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(21, $data['renewal_date'] ?? '', SQLITE3_TEXT);
        $stmt->bindValue(22, $data['applicant_signature'] ?? '', SQLITE3_TEXT);

        $stmt->execute();
        $id = $db->lastInsertRowID();
        $db->close();
        return $id;
    } catch (Exception $e) {
        return false;
    }
}

function parseAccommodationFormPost(array $post) {
    $roomTypes = [];
    if (!empty($post['room_type_name']) && is_array($post['room_type_name'])) {
        foreach ($post['room_type_name'] as $i => $name) {
            $name = trim($name);
            $rate = trim($post['room_type_rate'][$i] ?? '');
            $number = trim($post['room_type_number'][$i] ?? '');
            if ($name !== '' || $rate !== '' || $number !== '') {
                $roomTypes[] = ['type' => $name, 'rate' => $rate, 'number' => $number];
            }
        }
    }

    $amenities = [];
    if (!empty($post['amenity_name']) && is_array($post['amenity_name'])) {
        foreach ($post['amenity_name'] as $i => $name) {
            $name = trim($name);
            if ($name !== '') {
                $amenities[] = $name;
            }
        }
    }

    $category = trim($post['category'] ?? '');
    $otherCategoryText = trim($post['other_category_text'] ?? '');
    if ($category === 'Others') {
        $category = $otherCategoryText !== '' ? 'Others - ' . $otherCategoryText : 'Others';
    }

    return [
        'certification_track' => trim($post['certification_track'] ?? 'locally_certified'),
        'application_type' => trim($post['application_type'] ?? ''),
        'application_date' => trim($post['application_date'] ?? ''),
        'establishment_name' => trim($post['establishment_name'] ?? ''),
        'owner_name' => trim($post['owner_name'] ?? ''),
        'address' => trim($post['address'] ?? ''),
        'telephone' => trim($post['telephone'] ?? ''),
        'mobile_number' => trim($post['mobile_number'] ?? ''),
        'email' => trim($post['email'] ?? ''),
        'facebook' => trim($post['facebook'] ?? ''),
        'category' => $category,
        'other_category_text' => $otherCategoryText,
        'year_started' => trim($post['year_started'] ?? ''),
        'total_rooms' => $post['total_rooms'] ?? null,
        'total_capacity' => $post['total_capacity'] ?? null,
        'total_employees' => $post['total_employees'] ?? null,
        'male_employees' => $post['male_employees'] ?? null,
        'female_employees' => $post['female_employees'] ?? null,
        'room_types' => $roomTypes,
        'amenities' => $amenities,
        'previous_certificate' => trim($post['previous_certificate'] ?? ''),
        'renewal_date' => trim($post['renewal_date'] ?? ''),
    ];
}

function validateAccommodationApplication(array $data) {
    $errors = [];

    if (!in_array($data['certification_track'] ?? '', ['dot_accredited', 'locally_certified'], true)) {
        $errors[] = 'Please select DOT Accredited or Locally Certified.';
    }
    if (!in_array($data['application_type'] ?? '', ['new', 'renewal'], true)) {
        $errors[] = 'Please select New Application or Renewal.';
    }
    if (empty($data['application_date'])) {
        $errors[] = 'Date of application is required.';
    }
    if (empty($data['establishment_name'])) {
        $errors[] = 'Name of establishment is required.';
    }
    if (empty($data['owner_name'])) {
        $errors[] = 'Name of owner is required.';
    }
    if (empty($data['address'])) {
        $errors[] = 'Address is required.';
    }
    if (empty($data['category'])) {
        $errors[] = 'Please select an establishment category.';
    }
    if ((trim($data['category'] ?? '') === 'Others' || stripos(trim($data['category'] ?? ''), 'Others -') === 0) && empty(trim($data['other_category_text'] ?? ''))) {
        $errors[] = 'Please specify the establishment category when Others is selected.';
    }
    // Renewal previously required previous certificate/sticker number.
    // Requirement removed.
    // if (($data['application_type'] ?? '') === 'renewal' && empty($data['previous_certificate'])) {
    //     $errors[] = 'Previous certificate/sticker number is required for renewal.';
    // }


    return $errors;
}
