<?php

function adminAuthDatabase(): SQLite3 {
    $databasePath = appDatabasePath();
    $directory = dirname($databasePath);
    if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create the application database directory.');
    }

    $database = new SQLite3($databasePath, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
    $database->enableExceptions(true);
    $database->exec('PRAGMA foreign_keys = ON');
    return $database;
}

function adminPermissionModules(): array {
    return [
        'destinations' => 'Destinations',
        'experiences' => 'Experiences',
        'cultural_heritage' => 'Cultural Heritage',
        'events' => 'Events',
        'festivals' => 'Festivals',
        'hotels' => 'Hotels',
        'restaurants' => 'Restaurants',
        'certification' => 'Certification',
        'carousel' => 'Carousel',
        'feedback' => 'Feedback',
        'reports' => 'Reports',
        'media' => 'Media',
        'user_management' => 'Admin Account Management'
    ];
}

function normalizeAdminPermissions($permissions): array {
    $normalized = [];
    foreach (adminPermissionModules() as $module => $label) {
        $value = is_array($permissions) ? ($permissions[$module] ?? 'none') : 'none';
        $normalized[$module] = in_array($value, ['none', 'view', 'manage'], true) ? $value : 'none';
    }
    return $normalized;
}

function ensureAdminAuthSchema(): void {
    static $schemaReady = false;
    if ($schemaReady) {
        return;
    }

    $database = adminAuthDatabase();
    $database->exec('CREATE TABLE IF NOT EXISTS admin_roles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL COLLATE NOCASE UNIQUE,
        permissions TEXT NOT NULL DEFAULT "{}",
        is_super_admin INTEGER NOT NULL DEFAULT 0,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
    )');
    $database->exec('CREATE TABLE IF NOT EXISTS admin_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL COLLATE NOCASE UNIQUE,
        email TEXT COLLATE NOCASE UNIQUE,
        password_hash TEXT NOT NULL,
        role_id INTEGER NOT NULL,
        is_active INTEGER NOT NULL DEFAULT 1,
        created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (role_id) REFERENCES admin_roles(id)
    )');
    $database->exec('CREATE TABLE IF NOT EXISTS admin_password_change_requests (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        status TEXT NOT NULL DEFAULT "pending" CHECK (status IN ("pending", "completed")),
        requested_at INTEGER NOT NULL,
        completed_at INTEGER,
        completed_by INTEGER,
        FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE,
        FOREIGN KEY (completed_by) REFERENCES admin_users(id)
    )');
    $database->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_admin_password_requests_pending_user ON admin_password_change_requests(user_id) WHERE status = "pending"');
    $database->exec('CREATE INDEX IF NOT EXISTS idx_admin_password_requests_pending_date ON admin_password_change_requests(status, requested_at)');
    $database->exec('CREATE TABLE IF NOT EXISTS admin_chat_conversations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        account_user_id INTEGER NOT NULL UNIQUE,
        created_at INTEGER NOT NULL DEFAULT (unixepoch()),
        updated_at INTEGER NOT NULL DEFAULT (unixepoch()),
        FOREIGN KEY (account_user_id) REFERENCES admin_users(id) ON DELETE CASCADE
    )');
    $database->exec('CREATE TABLE IF NOT EXISTS admin_chat_messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        conversation_id INTEGER NOT NULL,
        sender_user_id INTEGER NOT NULL,
        body TEXT NOT NULL,
        created_at INTEGER NOT NULL DEFAULT (unixepoch()),
        read_at INTEGER,
        FOREIGN KEY (conversation_id) REFERENCES admin_chat_conversations(id) ON DELETE CASCADE,
        FOREIGN KEY (sender_user_id) REFERENCES admin_users(id) ON DELETE CASCADE
    )');
    $database->exec('CREATE INDEX IF NOT EXISTS idx_admin_chat_messages_conversation ON admin_chat_messages(conversation_id, id)');

    $roleQuery = $database->querySingle('SELECT id FROM admin_roles WHERE is_super_admin = 1');
    if (!$roleQuery) {
        $statement = $database->prepare('INSERT INTO admin_roles (name, permissions, is_super_admin) VALUES (:name, :permissions, 1)');
        $statement->bindValue(':name', 'Super Admin', SQLITE3_TEXT);
        $statement->bindValue(':permissions', '{}', SQLITE3_TEXT);
        $statement->execute();
        $roleQuery = $database->lastInsertRowID();
    }

    $allModules = array_keys(adminPermissionModules());
    $editorPermissions = array_fill_keys($allModules, 'none');
    foreach (['destinations', 'experiences', 'cultural_heritage', 'events', 'festivals', 'hotels', 'restaurants', 'carousel', 'media'] as $module) {
        $editorPermissions[$module] = 'manage';
    }
    foreach (['certification', 'feedback', 'reports'] as $module) {
        $editorPermissions[$module] = 'view';
    }
    $adminPermissions = array_fill_keys($allModules, 'manage');
    $adminPermissions['user_management'] = 'none';
    $legacyUserManagerPermissions = array_fill_keys($allModules, 'none');
    $legacyUserManagerPermissions['user_management'] = 'manage';
    $userManagerPermissions = array_fill_keys($allModules, 'manage');
    $defaultRoles = [
        'Admin' => $adminPermissions,
        'Editor' => $editorPermissions,
        'Viewer' => array_fill_keys($allModules, 'view'),
        'User Manager' => $userManagerPermissions
    ];
    foreach ($defaultRoles as $roleName => $permissions) {
        $statement = $database->prepare('INSERT OR IGNORE INTO admin_roles (name, permissions, is_super_admin) VALUES (:name, :permissions, 0)');
        $statement->bindValue(':name', $roleName, SQLITE3_TEXT);
        $statement->bindValue(':permissions', json_encode($permissions, JSON_THROW_ON_ERROR), SQLITE3_TEXT);
        $statement->execute();
    }
    $migrateUserManager = $database->prepare('UPDATE admin_roles SET permissions = :new_permissions WHERE name = :name COLLATE NOCASE AND is_super_admin = 0 AND permissions = :legacy_permissions');
    $migrateUserManager->bindValue(':new_permissions', json_encode($userManagerPermissions, JSON_THROW_ON_ERROR), SQLITE3_TEXT);
    $migrateUserManager->bindValue(':name', 'User Manager', SQLITE3_TEXT);
    $migrateUserManager->bindValue(':legacy_permissions', json_encode($legacyUserManagerPermissions, JSON_THROW_ON_ERROR), SQLITE3_TEXT);
    $migrateUserManager->execute();

    if ((int)$database->querySingle('SELECT COUNT(*) FROM admin_users') === 0) {
        $configuredEmail = getenv('TAGUM_SUPER_ADMIN_EMAIL');
        $email = is_string($configuredEmail) && filter_var($configuredEmail, FILTER_VALIDATE_EMAIL) ? $configuredEmail : null;
        $statement = $database->prepare('INSERT INTO admin_users (username, email, password_hash, role_id) VALUES (:username, :email, :password_hash, :role_id)');
        $statement->bindValue(':username', ADMIN_USERNAME, SQLITE3_TEXT);
        $statement->bindValue(':email', $email, $email === null ? SQLITE3_NULL : SQLITE3_TEXT);
        $statement->bindValue(':password_hash', ADMIN_PASSWORD_HASH, SQLITE3_TEXT);
        $statement->bindValue(':role_id', (int)$roleQuery, SQLITE3_INTEGER);
        $statement->execute();
    }

    $database->close();
    $schemaReady = true;
}

function getAdminUserById(int $userId): ?array {
    ensureAdminAuthSchema();
    $database = adminAuthDatabase();
    $statement = $database->prepare('SELECT u.*, r.name AS role_name, r.permissions, r.is_super_admin
        FROM admin_users u JOIN admin_roles r ON r.id = u.role_id WHERE u.id = :id');
    $statement->bindValue(':id', $userId, SQLITE3_INTEGER);
    $result = $statement->execute()->fetchArray(SQLITE3_ASSOC);
    $database->close();
    return $result ?: null;
}

function getAdminUserByLogin(string $login): ?array {
    ensureAdminAuthSchema();
    $database = adminAuthDatabase();
    $statement = $database->prepare('SELECT u.*, r.name AS role_name, r.permissions, r.is_super_admin
        FROM admin_users u JOIN admin_roles r ON r.id = u.role_id
        WHERE (u.username = :username COLLATE NOCASE OR u.email = :email COLLATE NOCASE) AND u.is_active = 1 LIMIT 1');
    $statement->bindValue(':username', trim($login), SQLITE3_TEXT);
    $statement->bindValue(':email', trim($login), SQLITE3_TEXT);
    $result = $statement->execute()->fetchArray(SQLITE3_ASSOC);
    $database->close();
    return $result ?: null;
}

function submitAdminPasswordChangeRequest(string $identifier): bool {
    $identifier = trim($identifier);
    if ($identifier === '' || strlen($identifier) > 254) {
        return false;
    }
    ensureAdminAuthSchema();
    $database = adminAuthDatabase();
    $lookup = $database->prepare('SELECT u.id FROM admin_users u JOIN admin_roles r ON r.id = u.role_id
        WHERE (u.username = :username COLLATE NOCASE OR u.email = :email COLLATE NOCASE)
            AND u.is_active = 1 AND r.is_super_admin = 0 LIMIT 1');
    $lookup->bindValue(':username', $identifier, SQLITE3_TEXT);
    $lookup->bindValue(':email', $identifier, SQLITE3_TEXT);
    $user = $lookup->execute()->fetchArray(SQLITE3_ASSOC);
    if (!$user) {
        $database->close();
        return false;
    }

    $pending = $database->prepare('SELECT id FROM admin_password_change_requests WHERE user_id = :user_id AND status = "pending"');
    $pending->bindValue(':user_id', (int)$user['id'], SQLITE3_INTEGER);
    if ($pending->execute()->fetchArray(SQLITE3_ASSOC)) {
        $database->close();
        return true;
    }

    $recent = $database->prepare('SELECT COUNT(*) FROM admin_password_change_requests WHERE user_id = :user_id AND requested_at >= :since');
    $recent->bindValue(':user_id', (int)$user['id'], SQLITE3_INTEGER);
    $recent->bindValue(':since', time() - 3600, SQLITE3_INTEGER);
    if ((int)$recent->execute()->fetchArray(SQLITE3_NUM)[0] >= 3) {
        $database->close();
        return false;
    }

    $insert = $database->prepare('INSERT INTO admin_password_change_requests (user_id, requested_at) VALUES (:user_id, :requested_at)');
    $insert->bindValue(':user_id', (int)$user['id'], SQLITE3_INTEGER);
    $insert->bindValue(':requested_at', time(), SQLITE3_INTEGER);
    $insert->execute();
    $database->close();
    return true;
}

function getPendingAdminPasswordChangeRequests(): array {
    ensureAdminAuthSchema();
    $database = adminAuthDatabase();
    $result = $database->query('SELECT r.id, r.user_id, r.requested_at, u.username, u.email, a.name AS role_name
        FROM admin_password_change_requests r
        JOIN admin_users u ON u.id = r.user_id
        JOIN admin_roles a ON a.id = u.role_id
        WHERE r.status = "pending" AND u.is_active = 1
        ORDER BY r.requested_at ASC');
    $requests = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $requests[] = $row;
    }
    $database->close();
    return $requests;
}

function countPendingAdminPasswordChangeRequests(): int {
    ensureAdminAuthSchema();
    $database = adminAuthDatabase();
    $count = (int)$database->querySingle('SELECT COUNT(*) FROM admin_password_change_requests r JOIN admin_users u ON u.id = r.user_id WHERE r.status = "pending" AND u.is_active = 1');
    $database->close();
    return $count;
}

function completeAdminPasswordChangeRequest(int $requestId, int $adminId, string $temporaryPassword): void {
    $admin = getAdminUserById($adminId);
    if (!$admin || (int)$admin['is_super_admin'] !== 1) {
        throw new RuntimeException('Only a Super Admin can complete password-change requests.');
    }
    if (strlen($temporaryPassword) < 12) {
        throw new RuntimeException('Temporary passwords must be at least 12 characters.');
    }

    $database = adminAuthDatabase();
    try {
        $database->exec('BEGIN IMMEDIATE');
        $lookup = $database->prepare('SELECT r.user_id FROM admin_password_change_requests r
            JOIN admin_users u ON u.id = r.user_id
            JOIN admin_roles a ON a.id = u.role_id
            WHERE r.id = :request_id AND r.status = "pending" AND u.is_active = 1 AND a.is_super_admin = 0');
        $lookup->bindValue(':request_id', $requestId, SQLITE3_INTEGER);
        $request = $lookup->execute()->fetchArray(SQLITE3_ASSOC);
        if (!$request) {
            throw new RuntimeException('That password-change request is no longer pending.');
        }

        $updateUser = $database->prepare('UPDATE admin_users SET password_hash = :password_hash, updated_at = CURRENT_TIMESTAMP WHERE id = :user_id AND is_active = 1');
        $updateUser->bindValue(':password_hash', password_hash($temporaryPassword, PASSWORD_DEFAULT), SQLITE3_TEXT);
        $updateUser->bindValue(':user_id', (int)$request['user_id'], SQLITE3_INTEGER);
        $updateUser->execute();
        if ($database->changes() !== 1) {
            throw new RuntimeException('The admin account could not be updated.');
        }

        $complete = $database->prepare('UPDATE admin_password_change_requests SET status = "completed", completed_at = :completed_at, completed_by = :admin_id WHERE id = :request_id AND status = "pending"');
        $complete->bindValue(':completed_at', time(), SQLITE3_INTEGER);
        $complete->bindValue(':admin_id', $adminId, SQLITE3_INTEGER);
        $complete->bindValue(':request_id', $requestId, SQLITE3_INTEGER);
        $complete->execute();
        if ($database->changes() !== 1) {
            throw new RuntimeException('The password-change request could not be completed.');
        }
        $database->exec('COMMIT');
    } catch (Throwable $exception) {
        $database->exec('ROLLBACK');
        $database->close();
        throw $exception;
    }
    $database->close();
}

function currentAdminUser(): ?array {
    if (empty($_SESSION['admin_user_id'])) {
        return null;
    }
    return getAdminUserById((int)$_SESSION['admin_user_id']);
}

function isLoggedIn(): bool {
    if (($_SESSION['admin_logged_in'] ?? false) !== true || empty($_SESSION['admin_user_id'])) {
        return false;
    }
    if (isset($_SESSION['last_activity']) && time() - (int)$_SESSION['last_activity'] > SESSION_TIMEOUT) {
        logout(false);
        return false;
    }

    $user = currentAdminUser();
    if (!$user || !(int)$user['is_active']) {
        unset($_SESSION['admin_logged_in'], $_SESSION['admin_user_id'], $_SESSION['admin_username'], $_SESSION['admin_password_fingerprint']);
        return false;
    }
    $passwordFingerprint = hash('sha256', $user['password_hash']);
    if (empty($_SESSION['admin_password_fingerprint'])
        || !hash_equals($passwordFingerprint, (string)$_SESSION['admin_password_fingerprint'])) {
        unset($_SESSION['admin_logged_in'], $_SESSION['admin_user_id'], $_SESSION['admin_username'], $_SESSION['admin_password_fingerprint']);
        return false;
    }
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['last_activity'] = time();
    return true;
}

function adminCan(string $module, string $level = 'view', ?array $user = null): bool {
    $user = $user ?? currentAdminUser();
    if (!$user) {
        return false;
    }
    if ((int)$user['is_super_admin'] === 1) {
        return true;
    }
    if (!array_key_exists($module, adminPermissionModules())) {
        return false;
    }

    $permissions = json_decode($user['permissions'] ?? '{}', true);
    $granted = is_array($permissions) ? ($permissions[$module] ?? 'none') : 'none';
    return $level === 'manage' ? $granted === 'manage' : in_array($granted, ['view', 'manage'], true);
}

function adminRoutePermission(): ?array {
    $script = basename(parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH) ?: '');
    $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    $dashboardTabs = [
        'destinations' => 'destinations',
        'experiences' => 'experiences',
        'cultural-heritage' => 'cultural_heritage',
        'events' => 'events',
        'festivals' => 'festivals',
        'hotels' => 'hotels',
        'restaurants' => 'restaurants',
        'certification' => 'certification',
        'carousel' => 'carousel'
    ];

    if ($script === 'dashboard.php') {
        if ($method === 'POST') {
            $actionModules = [
                'toggle-featured' => 'destinations',
                'toggle-featured-experience' => 'experiences',
                'toggle-carousel-active' => 'carousel',
                'update-application-status' => 'certification',
                'delete-application' => 'certification',
                'delete-cultural-heritage' => 'cultural_heritage'
            ];
            $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
            return isset($actionModules[$action]) ? [$actionModules[$action], 'manage'] : null;
        }
        $tab = is_string($_GET['tab'] ?? null) ? $_GET['tab'] : 'destinations';
        $module = $dashboardTabs[$tab] ?? null;
        return $module ? [$module, 'view'] : null;
    }

    $routes = [
        'add-carousel-slide.php' => ['carousel', 'manage'],
        'delete-carousel-slide.php' => ['carousel', 'manage'],
        'hero-packages.php' => ['carousel', 'manage'],
        'add-cultural-heritage.php' => ['cultural_heritage', 'manage'],
        'add-destination.php' => ['destinations', 'manage'],
        'edit-destination.php' => ['destinations', 'manage'],
        'add-events.php' => ['events', 'manage'],
        'add-experience.php' => ['experiences', 'manage'],
        'add-festival.php' => ['festivals', 'manage'],
        'add-hotel.php' => ['hotels', 'manage'],
        'hotel-gallery.php' => ['hotels', $method === 'POST' ? 'manage' : 'view'],
        'add-restaurant.php' => ['restaurants', 'manage'],
        'restaurant-gallery.php' => ['restaurants', $method === 'POST' ? 'manage' : 'view'],
        'feedback-details.php' => ['feedback', $method === 'POST' ? 'manage' : 'view'],
        'feedback-management.php' => ['feedback', $method === 'POST' ? 'manage' : 'view'],
        'feedback-reports.php' => ['reports', 'view'],
        'generate_comprehensive_report.php' => ['reports', 'view'],
        'final-media-crud.php' => ['media', $method === 'POST' ? 'manage' : 'view'],
        'media-crud.php' => ['media', $method === 'POST' ? 'manage' : 'view'],
        'media-manager.php' => ['media', $method === 'POST' ? 'manage' : 'view'],
        'view-certification.php' => ['certification', $method === 'POST' ? 'manage' : 'view']
    ];
    return $routes[$script] ?? null;
}

function renderAdminAccessDenied(string $title, string $message): void {
    http_response_code(403);
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Access Restricted - Tourism Admin</title><style>
        :root{color-scheme:light;--green:#1d5a3d;--green-soft:#e7f4ec;--ink:#1f2937;--muted:#596579;--line:#d7e1da}
        *{box-sizing:border-box}body{margin:0;min-height:100vh;padding:32px;display:grid;place-items:center;background:linear-gradient(145deg,#f3f6f4 0%,#e6f2eb 100%);font-family:Segoe UI,Tahoma,sans-serif;color:var(--ink)}
        main{width:min(100%,760px);padding:48px;background:#fff;border:1px solid var(--line);border-top:8px solid var(--green);border-radius:8px;box-shadow:0 18px 48px rgba(24,64,43,.12)}
        .status{display:inline-block;margin-bottom:20px;padding:7px 10px;background:var(--green-soft);color:var(--green);font-size:13px;font-weight:700}
        h1{margin:0 0 16px;font-size:36px;line-height:1.15}p{margin:0 0 14px;color:var(--muted);font-size:17px;line-height:1.65}.actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:28px}
        a{display:inline-flex;min-height:48px;align-items:center;justify-content:center;padding:0 18px;border:1px solid var(--green);border-radius:6px;background:var(--green);color:#fff;text-decoration:none;font-size:15px;font-weight:700}
        a.secondary{background:#fff;color:var(--green)}a:focus-visible{outline:3px solid #83c59b;outline-offset:3px}
        @media(max-width:560px){body{padding:16px}main{padding:28px 22px}h1{font-size:30px}.actions{display:grid}.actions a{width:100%}}
    </style></head><body><main><span class="status">403 / ACCESS RESTRICTED</span><h1>' . $safeTitle . '</h1><p>' . $safeMessage . '</p><p>Contact your local Super Admin to request access or help with this module.</p><div class="actions"><a href="dashboard.php" class="secondary">Return to dashboard</a><a href="support-chat.php">Contact Super Admin</a></div></main></body></html>';
    exit();
}

function shouldRedirectDashboardToAdminAccounts(array $user, ?array $required): bool {
    return (int)$user['is_super_admin'] !== 1
        && adminCan('user_management', 'manage', $user)
        && (!$required || !adminCan($required[0], $required[1], $user));
}

function requireAuth(): void {
    if (!isLoggedIn()) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
        header('Location: login.php');
        exit();
    }

    $user = currentAdminUser();
    $script = basename(parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH) ?: '');
    $required = adminRoutePermission();
    if ($script === 'access-management.php') {
        if (!(int)$user['is_super_admin'] && !adminCan('user_management', 'manage', $user)) {
            renderAdminAccessDenied('Admin account management is restricted', 'Your assigned role cannot manage admin accounts.');
        }
        return;
    }
    if ($script === 'support-chat.php') {
        return;
    }

    if ($script === 'dashboard.php' && strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET'
        && shouldRedirectDashboardToAdminAccounts($user, $required)) {
        header('Location: access-management.php');
        exit();
    }
    if ($required && adminCan($required[0], $required[1], $user)) {
        return;
    }
    if (!$required && (int)$user['is_super_admin'] === 1) {
        return;
    }

    renderAdminAccessDenied('You do not have access to this module', 'Your account role does not include permission to open this admin module.');
}

function requireSuperAdmin(): void {
    requireAuth();
    $user = currentAdminUser();
    if (!$user || (int)$user['is_super_admin'] !== 1) {
        renderAdminAccessDenied('Super Admin access is required', 'Only a Super Admin can manage accounts and roles.');
    }
}
