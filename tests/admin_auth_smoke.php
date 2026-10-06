<?php
$databasePath = tempnam(sys_get_temp_dir(), 'tagum-admin-auth-');
if ($databasePath === false) {
    throw new RuntimeException('Could not allocate a temporary SQLite database.');
}
unlink($databasePath);
putenv('TAGUM_DATABASE_PATH=' . $databasePath);
putenv('TAGUM_SUPER_ADMIN_EMAIL=superadmin@example.test');
require_once dirname(__DIR__) . '/admin/config.php';
require_once dirname(__DIR__) . '/admin/chat_helpers.php';

function authSmokeAssert(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

try {
    ensureAdminAuthSchema();
    $superAdmin = getAdminUserByLogin(ADMIN_USERNAME);
    authSmokeAssert($superAdmin !== null && (int)$superAdmin['is_super_admin'] === 1, 'Super Admin bootstrap failed.');
    authSmokeAssert($superAdmin['email'] === 'superadmin@example.test', 'Initial Super Admin email was not applied.');
    $database = adminAuthDatabase();
    $defaultRoles = [];
    $roleResult = $database->query('SELECT id, name, permissions, is_super_admin FROM admin_roles');
    while ($role = $roleResult->fetchArray(SQLITE3_ASSOC)) {
        $defaultRoles[$role['name']] = [
            'id' => (int)$role['id'],
            'is_super_admin' => (int)$role['is_super_admin'],
            'permissions' => $role['permissions']
        ];
    }
    $database->close();
    foreach (['Super Admin', 'Admin', 'Editor', 'Viewer', 'User Manager'] as $roleName) {
        authSmokeAssert(isset($defaultRoles[$roleName]), $roleName . ' default role was not created.');
    }
    $editorRole = ['is_super_admin' => 0, 'permissions' => $defaultRoles['Editor']['permissions']];
    $viewerRole = ['is_super_admin' => 0, 'permissions' => $defaultRoles['Viewer']['permissions']];
    $userManagerRole = ['is_super_admin' => 0, 'permissions' => $defaultRoles['User Manager']['permissions']];
    $adminRole = ['is_super_admin' => 0, 'permissions' => $defaultRoles['Admin']['permissions']];
    authSmokeAssert(adminCan('destinations', 'manage', $editorRole), 'Default Editor role cannot manage content.');
    authSmokeAssert(adminCan('feedback', 'view', $editorRole) && !adminCan('feedback', 'manage', $editorRole), 'Default Editor feedback permissions are incorrect.');
    authSmokeAssert(adminCan('reports', 'view', $viewerRole) && !adminCan('reports', 'manage', $viewerRole), 'Default Viewer role is not read-only.');
    authSmokeAssert(adminCan('user_management', 'manage', $userManagerRole), 'User Manager role lacks account-management access.');
    authSmokeAssert(adminCan('destinations', 'manage', $userManagerRole) && adminCan('feedback', 'manage', $userManagerRole), 'User Manager cannot manage the content and feedback modules.');
    authSmokeAssert(!adminCan('user_management', 'manage', $adminRole), 'Regular Admin unexpectedly received account-management access.');
    authSmokeAssert(!shouldRedirectDashboardToAdminAccounts($userManagerRole, ['destinations', 'view']), 'User Manager was redirected from an allowed dashboard tab.');
    authSmokeAssert(!shouldRedirectDashboardToAdminAccounts($userManagerRole, ['user_management', 'manage']), 'User Manager was redirected from an allowed management route.');
    authSmokeAssert(!shouldRedirectDashboardToAdminAccounts($superAdmin, ['destinations', 'view']), 'Super Admin was incorrectly redirected from the dashboard.');
    $accountOnlyPermissions = array_fill_keys(array_keys(adminPermissionModules()), 'none');
    $accountOnlyPermissions['user_management'] = 'manage';
    $accountOnlyManager = ['is_super_admin' => 0, 'permissions' => json_encode($accountOnlyPermissions)];
    authSmokeAssert(shouldRedirectDashboardToAdminAccounts($accountOnlyManager, ['destinations', 'view']), 'Account-only managers are not redirected away from denied dashboard tabs.');

    $_SERVER['SCRIPT_NAME'] = '/admin/forgot-password.php';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = ['account' => 'not-an-account', 'csrf_token' => generateCsrfToken()];
    ob_start();
    require dirname(__DIR__) . '/admin/forgot-password.php';
    $forgotResponse = ob_get_clean();
    authSmokeAssert(strpos($forgotResponse, 'If an active admin account matches that username or email') !== false, 'Password request response disclosed account existence.');

    $database = adminAuthDatabase();
    $role = $database->prepare('INSERT INTO admin_roles (name, permissions, is_super_admin) VALUES (:name, :permissions, 0)');
    $role->bindValue(':name', 'Smoke Editor', SQLITE3_TEXT);
    $role->bindValue(':permissions', json_encode(['destinations' => 'view']), SQLITE3_TEXT);
    $role->execute();
    $roleId = $database->lastInsertRowID();
    $account = $database->prepare('INSERT INTO admin_users (username, email, password_hash, role_id) VALUES (:username, :email, :password_hash, :role_id)');
    $account->bindValue(':username', 'smoke_editor', SQLITE3_TEXT);
    $account->bindValue(':email', 'editor@example.test', SQLITE3_TEXT);
    $account->bindValue(':password_hash', password_hash('Initial-password-123', PASSWORD_DEFAULT), SQLITE3_TEXT);
    $account->bindValue(':role_id', $roleId, SQLITE3_INTEGER);
    $account->execute();
    $editor = getAdminUserById((int)$database->lastInsertRowID());
    $database->close();
    $editorOriginalRoleId = (int)$editor['role_id'];
    $oldEditorFingerprint = hash('sha256', $editor['password_hash']);
    authSmokeAssert(adminCan('destinations', 'view', $editor), 'Role view permission failed.');
    authSmokeAssert(!adminCan('destinations', 'manage', $editor), 'Role incorrectly received manage permission.');
    authSmokeAssert(!adminCan('hotels', 'view', $editor), 'Role incorrectly received unrelated module access.');

    $_SERVER['SCRIPT_NAME'] = '/admin/forgot-password.php';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = ['account' => 'smoke_editor', 'csrf_token' => generateCsrfToken()];
    ob_start();
    require dirname(__DIR__) . '/admin/forgot-password.php';
    $passwordRequestResponse = ob_get_clean();
    authSmokeAssert(strpos($passwordRequestResponse, 'request has been sent to the Super Admin') !== false, 'Password-change request was not acknowledged.');
    authSmokeAssert(count(getPendingAdminPasswordChangeRequests()) === 1, 'Password-change request was not queued.');
    authSmokeAssert(submitAdminPasswordChangeRequest('editor@example.test'), 'Duplicate request was not handled.');
    authSmokeAssert(count(getPendingAdminPasswordChangeRequests()) === 1, 'Duplicate submission created multiple pending requests.');

    $conversationId = getOrCreateAdminChatConversation((int)$editor['id']);
    sendAdminChatMessage($conversationId, (int)$editor['id'], 'Please help with a content update.');
    $chatAccounts = getAdminChatAccounts();
    $editorInbox = array_values(array_filter($chatAccounts, function ($account) use ($editor) {
        return (int)$account['id'] === (int)$editor['id'];
    }));
    authSmokeAssert(count($editorInbox) === 1 && (int)$editorInbox[0]['unread_count'] === 1, 'Super Admin inbox unread count failed.');

    $database = adminAuthDatabase();
    $otherAccount = $database->prepare('INSERT INTO admin_users (username, email, password_hash, role_id) VALUES (:username, :email, :password_hash, :role_id)');
    $otherAccount->bindValue(':username', 'other_admin', SQLITE3_TEXT);
    $otherAccount->bindValue(':email', 'other@example.test', SQLITE3_TEXT);
    $otherAccount->bindValue(':password_hash', password_hash('Other-password-123', PASSWORD_DEFAULT), SQLITE3_TEXT);
    $otherAccount->bindValue(':role_id', $defaultRoles['Viewer']['id'], SQLITE3_INTEGER);
    $otherAccount->execute();
    $otherUserId = (int)$database->lastInsertRowID();
    $managerAccount = $database->prepare('INSERT INTO admin_users (username, email, password_hash, role_id) VALUES (:username, :email, :password_hash, :role_id)');
    $managerAccount->bindValue(':username', 'smoke_manager', SQLITE3_TEXT);
    $managerAccount->bindValue(':email', 'manager@example.test', SQLITE3_TEXT);
    $managerAccount->bindValue(':password_hash', password_hash('Manager-password-123', PASSWORD_DEFAULT), SQLITE3_TEXT);
    $managerAccount->bindValue(':role_id', $defaultRoles['User Manager']['id'], SQLITE3_INTEGER);
    $managerAccount->execute();
    $managerUserId = (int)$database->lastInsertRowID();
    $database->close();
    $manager = getAdminUserById($managerUserId);
    authSmokeAssert(!canAccessAdminChat($conversationId, $otherUserId), 'An unrelated account accessed another account chat.');
    markAdminChatRead($conversationId, (int)$superAdmin['id']);
    $superMessages = loadAdminChatMessages($conversationId, (int)$superAdmin['id']);
    authSmokeAssert(count($superMessages) === 1 && $superMessages[0]['body'] === 'Please help with a content update.', 'Super Admin could not read the account chat.');
    sendAdminChatMessage($conversationId, (int)$superAdmin['id'], 'I can help with that.');
    authSmokeAssert(count(loadAdminChatMessages($conversationId, (int)$editor['id'])) === 2, 'Admin account could not read the Super Admin reply.');
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_user_id'] = (int)$editor['id'];
    $_SESSION['admin_username'] = $editor['username'];
    $_SESSION['admin_password_fingerprint'] = hash('sha256', $editor['password_hash']);
    $_SESSION['last_activity'] = time();
    $_SERVER['SCRIPT_NAME'] = '/admin/support-chat.php';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = [];
    $_POST = [];
    ob_start();
    require dirname(__DIR__) . '/admin/support-chat.php';
    $accountChatHtml = ob_get_clean();
    authSmokeAssert(strpos($accountChatHtml, 'Contact Super Admin') !== false && strpos($accountChatHtml, 'Please help with a content update.') !== false, 'Regular Admin session could not access its Support Chat.');

    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_user_id'] = (int)$manager['id'];
    $_SESSION['admin_username'] = $manager['username'];
    $_SESSION['admin_password_fingerprint'] = hash('sha256', $manager['password_hash']);
    $_SESSION['last_activity'] = time();
    $_SERVER['SCRIPT_NAME'] = '/admin/access-management.php';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = [];
    $_POST = [];
    ob_start();
    require dirname(__DIR__) . '/admin/access-management.php';
    $managerPage = ob_get_clean();
    authSmokeAssert(strpos($managerPage, 'Admin Account Management') !== false, 'User Manager could not access account management.');
    authSmokeAssert(strpos($managerPage, 'Back to Dashboard') !== false && strpos($managerPage, 'dashboard.php?tab=destinations') !== false, 'User Manager dashboard button is missing or points to a denied tab.');
    authSmokeAssert(strpos($managerPage, 'Roles and module permissions') === false, 'User Manager can see role management.');
    authSmokeAssert(strpos($managerPage, 'Your account') !== false
        && strpos($managerPage, 'manager@example.test') !== false
        && strpos($managerPage, 'Profile details are read-only') !== false,
        'User Manager profile details are not displayed separately.');
    authSmokeAssert(strpos($managerPage, 'Other admin accounts') !== false
        && strpos($managerPage, 'smoke_editor') !== false,
        'Other admin accounts are missing from the management list.');
    authSmokeAssert(strpos($managerPage, ADMIN_USERNAME) === false, 'User Manager can see the Super Admin account.');

    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [
        'action' => 'save_role',
        'name' => 'Manager Created Role',
        'permissions' => ['destinations' => 'manage'],
        'csrf_token' => generateCsrfToken()
    ];
    ob_start();
    require dirname(__DIR__) . '/admin/access-management.php';
    $roleDeniedResponse = ob_get_clean();
    authSmokeAssert(strpos($roleDeniedResponse, 'Only a Super Admin can create') !== false, 'User Manager was allowed to create a role.');

    $_POST = [
        'action' => 'save_user',
        'username' => 'managed_admin',
        'email' => 'managed@example.test',
        'role_id' => $defaultRoles['Admin']['id'],
        'password' => 'Managed-password-123',
        'csrf_token' => generateCsrfToken()
    ];
    ob_start();
    require dirname(__DIR__) . '/admin/access-management.php';
    $createdAccountResponse = ob_get_clean();
    $managedAccount = getAdminUserByLogin('managed_admin');
    authSmokeAssert($managedAccount !== null && (int)$managedAccount['is_active'] === 1, 'User Manager could not create an active regular account.');
    authSmokeAssert(password_verify('Managed-password-123', $managedAccount['password_hash']), 'Managed account password was not saved.');

    $_POST = [
        'action' => 'save_user',
        'user_id' => (int)$editor['id'],
        'username' => $editor['username'],
        'email' => $editor['email'],
        'role_id' => $defaultRoles['User Manager']['id'],
        'is_active' => '1',
        'password' => '',
        'csrf_token' => generateCsrfToken()
    ];
    ob_start();
    require dirname(__DIR__) . '/admin/access-management.php';
    $elevationDeniedResponse = ob_get_clean();
    authSmokeAssert(strpos($elevationDeniedResponse, 'Only a Super Admin can assign') !== false, 'User Manager was allowed to grant User Manager access.');
    authSmokeAssert((int)getAdminUserById((int)$editor['id'])['role_id'] === $editorOriginalRoleId, 'Privilege escalation changed the account role.');

    $_SERVER['SCRIPT_NAME'] = '/admin/dashboard.php';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = ['tab' => 'events'];
    $_POST = [];
    authSmokeAssert(adminRoutePermission() === ['events', 'view'], 'Dashboard view route permission failed.');
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = ['action' => 'delete-application'];
    authSmokeAssert(adminRoutePermission() === ['certification', 'manage'], 'Dashboard action permission did not match the affected module.');
    $_GET = ['tab' => []];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    authSmokeAssert(adminRoutePermission() === ['destinations', 'view'], 'Malformed tab parameter was not handled safely.');
    $pendingRequests = getPendingAdminPasswordChangeRequests();
    authSmokeAssert(count($pendingRequests) === 1 && $pendingRequests[0]['username'] === 'smoke_editor', 'Super Admin request list is missing the requester.');
    $managerCannotCompleteRequest = false;
    try {
        completeAdminPasswordChangeRequest((int)$pendingRequests[0]['id'], (int)$manager['id'], 'Temporary-password-123');
    } catch (RuntimeException $exception) {
        $managerCannotCompleteRequest = true;
    }
    authSmokeAssert($managerCannotCompleteRequest, 'A non-Super Admin completed a password-change request.');

    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_user_id'] = (int)$superAdmin['id'];
    $_SESSION['admin_username'] = $superAdmin['username'];
    $_SESSION['admin_password_fingerprint'] = hash('sha256', $superAdmin['password_hash']);
    $_SESSION['last_activity'] = time();
    $_SERVER['SCRIPT_NAME'] = '/admin/access-management.php';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = [];
    $_POST = [];
    ob_start();
    require dirname(__DIR__) . '/admin/access-management.php';
    $requestQueueHtml = ob_get_clean();
    authSmokeAssert(strpos($requestQueueHtml, 'Password-change requests') !== false && strpos($requestQueueHtml, 'smoke_editor') !== false, 'Super Admin request queue did not render.');

    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [
        'action' => 'complete_password_change',
        'request_id' => (int)$pendingRequests[0]['id'],
        'temporary_password' => 'Temporary-password-123',
        'csrf_token' => generateCsrfToken()
    ];
    ob_start();
    require dirname(__DIR__) . '/admin/access-management.php';
    $completedRequestHtml = ob_get_clean();
    $updatedEditor = getAdminUserById((int)$editor['id']);
    authSmokeAssert(password_verify('Temporary-password-123', $updatedEditor['password_hash']), 'Super Admin did not set the requested password.');
    authSmokeAssert(count(getPendingAdminPasswordChangeRequests()) === 0, 'Completed request remained pending.');
    authSmokeAssert(strpos($completedRequestHtml, 'Temporary password set') !== false, 'Super Admin completion was not acknowledged.');
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_user_id'] = (int)$editor['id'];
    $_SESSION['admin_username'] = $editor['username'];
    $_SESSION['admin_password_fingerprint'] = $oldEditorFingerprint;
    $_SESSION['last_activity'] = time();
    authSmokeAssert(!isLoggedIn(), 'Super Admin password change did not invalidate the old session.');

    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_user_id'] = (int)$superAdmin['id'];
    $_SESSION['admin_username'] = $superAdmin['username'];
    $_SESSION['admin_password_fingerprint'] = hash('sha256', $superAdmin['password_hash']);
    $_SESSION['last_activity'] = time();
    $_SERVER['SCRIPT_NAME'] = '/admin/support-chat.php';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = ['user' => (int)$editor['id']];
    $_POST = [];
    ob_start();
    require dirname(__DIR__) . '/admin/support-chat.php';
    $chatHtml = ob_get_clean();
    authSmokeAssert(strpos($chatHtml, 'Super Admin Support Chat') !== false && strpos($chatHtml, 'I can help with that.') !== false, 'Support chat UI did not render the account conversation.');

    $_SERVER['SCRIPT_NAME'] = '/admin/access-management.php';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = [
        'action' => 'save_role',
        'name' => 'Smoke Manager',
        'permissions' => ['carousel' => 'manage'],
        'csrf_token' => generateCsrfToken()
    ];
    ob_start();
    require dirname(__DIR__) . '/admin/access-management.php';
    $managementResponse = ob_get_clean();
    $database = adminAuthDatabase();
    $createdRole = $database->querySingle('SELECT id FROM admin_roles WHERE name = "Smoke Manager"');
    $database->close();
    authSmokeAssert($createdRole !== false, 'Super Admin could not create a custom role.');
    authSmokeAssert(strpos($managementResponse, 'Account and Role Management') !== false, 'Access management screen did not render.');
    authSmokeAssert(strpos($managementResponse, 'data-password-toggle="initial-password"') !== false, 'Initial password visibility toggle did not render.');

    echo "PASS: roles, permissions, reset flow, private account chat, and password visibility control\n";
} finally {
    foreach ([$databasePath, $databasePath . '-wal', $databasePath . '-shm'] as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
    putenv('TAGUM_DATABASE_PATH');
    putenv('TAGUM_SUPER_ADMIN_EMAIL');
}
