<?php
require_once __DIR__ . '/config.php';
requireAuth();

$currentUser = currentAdminUser();
$isSuperAdmin = (int)$currentUser['is_super_admin'] === 1;
$isUserManager = adminCan('user_management', 'manage', $currentUser);
if (!$isSuperAdmin && !$isUserManager) {
    http_response_code(403);
    exit('You do not have permission to manage admin accounts.');
}
$message = '';
$messageType = 'success';

$countOtherSuperAdmins = static function (SQLite3 $database, int $userId): int {
    $statement = $database->prepare('SELECT COUNT(*) FROM admin_users u JOIN admin_roles r ON r.id = u.role_id WHERE u.is_active = 1 AND r.is_super_admin = 1 AND u.id != :user_id');
    $statement->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    return (int)$statement->execute()->fetchArray(SQLITE3_NUM)[0];
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Your session expired. Refresh and try again.';
        $messageType = 'error';
    } else {
        $database = adminAuthDatabase();
        try {
            $action = (string)($_POST['action'] ?? '');
            if ($action === 'complete_password_change') {
                if (!$isSuperAdmin) {
                    throw new RuntimeException('Only a Super Admin can complete password-change requests.');
                }
                completeAdminPasswordChangeRequest(
                    (int)($_POST['request_id'] ?? 0),
                    (int)$currentUser['id'],
                    (string)($_POST['temporary_password'] ?? '')
                );
                $message = 'Temporary password set. Give it to the account owner through a verified secure channel.';
            } elseif (!$isSuperAdmin && in_array($action, ['save_role', 'delete_role'], true)) {
                throw new RuntimeException('Only a Super Admin can create, edit, or delete roles.');
            } elseif ($action === 'save_role') {
                $roleId = (int)($_POST['role_id'] ?? 0);
                $name = trim((string)($_POST['name'] ?? ''));
                if (!preg_match('/\A[\pL\pN _-]{2,60}\z/u', $name)) {
                    throw new RuntimeException('Role names must be 2 to 60 letters, numbers, spaces, underscores, or hyphens.');
                }
                $permissions = json_encode(normalizeAdminPermissions($_POST['permissions'] ?? []), JSON_THROW_ON_ERROR);
                if ($roleId > 0) {
                    $existing = $database->prepare('SELECT is_super_admin FROM admin_roles WHERE id = :id');
                    $existing->bindValue(':id', $roleId, SQLITE3_INTEGER);
                    $role = $existing->execute()->fetchArray(SQLITE3_ASSOC);
                    if (!$role || (int)$role['is_super_admin'] === 1) {
                        throw new RuntimeException('The built-in Super Admin role cannot be edited.');
                    }
                    $statement = $database->prepare('UPDATE admin_roles SET name = :name, permissions = :permissions WHERE id = :id');
                    $statement->bindValue(':id', $roleId, SQLITE3_INTEGER);
                } else {
                    $statement = $database->prepare('INSERT INTO admin_roles (name, permissions, is_super_admin) VALUES (:name, :permissions, 0)');
                }
                $statement->bindValue(':name', $name, SQLITE3_TEXT);
                $statement->bindValue(':permissions', $permissions, SQLITE3_TEXT);
                $statement->execute();
                $message = $roleId > 0 ? 'Role updated.' : 'Role created.';
            } elseif ($action === 'delete_role') {
                $roleId = (int)($_POST['role_id'] ?? 0);
                $statement = $database->prepare('SELECT is_super_admin FROM admin_roles WHERE id = :id');
                $statement->bindValue(':id', $roleId, SQLITE3_INTEGER);
                $role = $statement->execute()->fetchArray(SQLITE3_ASSOC);
                $users = $database->prepare('SELECT COUNT(*) FROM admin_users WHERE role_id = :id');
                $users->bindValue(':id', $roleId, SQLITE3_INTEGER);
                if (!$role || (int)$role['is_super_admin'] === 1 || (int)$users->execute()->fetchArray(SQLITE3_NUM)[0] > 0) {
                    throw new RuntimeException('That role cannot be deleted while built in or assigned to an account.');
                }
                $delete = $database->prepare('DELETE FROM admin_roles WHERE id = :id');
                $delete->bindValue(':id', $roleId, SQLITE3_INTEGER);
                $delete->execute();
                $message = 'Role deleted.';
            } elseif ($action === 'save_user') {
                $userId = (int)($_POST['user_id'] ?? 0);
                $isNew = $userId === 0;
                $existingUser = null;
                if (!$isNew) {
                    $lookup = $database->prepare('SELECT u.*, r.is_super_admin, r.permissions FROM admin_users u JOIN admin_roles r ON r.id = u.role_id WHERE u.id = :id');
                    $lookup->bindValue(':id', $userId, SQLITE3_INTEGER);
                    $existingUser = $lookup->execute()->fetchArray(SQLITE3_ASSOC);
                    if (!$existingUser) {
                        throw new RuntimeException('Account not found.');
                    }
                    if (!$isSuperAdmin && ((int)$existingUser['id'] === (int)$currentUser['id']
                        || (int)$existingUser['is_super_admin'] === 1
                        || adminCan('user_management', 'manage', $existingUser))) {
                        throw new RuntimeException('User Managers cannot change their own account, Super Admins, or other User Managers.');
                    }
                }

                $username = trim((string)($_POST['username'] ?? ''));
                $emailInput = trim((string)($_POST['email'] ?? ''));
                $email = $emailInput === '' ? null : $emailInput;
                $roleId = (int)($_POST['role_id'] ?? ($existingUser['role_id'] ?? 0));
                $isActive = $isNew ? 1 : ((string)($_POST['is_active'] ?? '0') === '1' ? 1 : 0);
                $password = (string)($_POST['password'] ?? '');
                if (!preg_match('/\A[a-zA-Z0-9._-]{3,60}\z/', $username)) {
                    throw new RuntimeException('Usernames must be 3 to 60 characters and use letters, numbers, dots, underscores, or hyphens.');
                }
                if ($email !== null && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new RuntimeException('Enter a valid email address or leave it blank.');
                }
                $roleCheck = $database->prepare('SELECT id, is_super_admin, permissions FROM admin_roles WHERE id = :id');
                $roleCheck->bindValue(':id', $roleId, SQLITE3_INTEGER);
                $selectedRole = $roleCheck->execute()->fetchArray(SQLITE3_ASSOC);
                if (!$selectedRole) {
                    throw new RuntimeException('Select an existing role.');
                }
                if (!$isSuperAdmin && ((int)$selectedRole['is_super_admin'] === 1
                    || adminCan('user_management', 'manage', $selectedRole))) {
                    throw new RuntimeException('Only a Super Admin can assign Super Admin or User Manager access.');
                }
                if ($isNew && strlen($password) < 12) {
                    throw new RuntimeException('New account passwords must be at least 12 characters.');
                }
                if (!$isNew && $password !== '' && strlen($password) < 12) {
                    throw new RuntimeException('A replacement password must be at least 12 characters.');
                }
                if (!$isNew && $userId === (int)$currentUser['id']) {
                    $roleId = (int)$existingUser['role_id'];
                    $isActive = 1;
                }
                if (!$isNew && (int)$existingUser['is_super_admin'] === 1
                    && ((int)$selectedRole['is_super_admin'] !== 1 || $isActive !== 1)
                    && $countOtherSuperAdmins($database, $userId) === 0) {
                    throw new RuntimeException('At least one active Super Admin must remain.');
                }

                if ($isNew) {
                    $statement = $database->prepare('INSERT INTO admin_users (username, email, password_hash, role_id, is_active) VALUES (:username, :email, :password_hash, :role_id, :is_active)');
                    $statement->bindValue(':password_hash', password_hash($password, PASSWORD_DEFAULT), SQLITE3_TEXT);
                } else {
                    $sql = 'UPDATE admin_users SET username = :username, email = :email, role_id = :role_id, is_active = :is_active, updated_at = CURRENT_TIMESTAMP';
                    if ($password !== '') {
                        $sql .= ', password_hash = :password_hash';
                    }
                    $sql .= ' WHERE id = :id';
                    $statement = $database->prepare($sql);
                    $statement->bindValue(':id', $userId, SQLITE3_INTEGER);
                    if ($password !== '') {
                        $statement->bindValue(':password_hash', password_hash($password, PASSWORD_DEFAULT), SQLITE3_TEXT);
                    }
                }
                $statement->bindValue(':username', $username, SQLITE3_TEXT);
                $statement->bindValue(':email', $email, $email === null ? SQLITE3_NULL : SQLITE3_TEXT);
                $statement->bindValue(':role_id', $roleId, SQLITE3_INTEGER);
                $statement->bindValue(':is_active', $isActive, SQLITE3_INTEGER);
                $statement->execute();
                $message = $isNew ? 'Admin account created.' : 'Admin account updated.';
            } else {
                throw new RuntimeException('Unknown management action.');
            }
        } catch (Throwable $exception) {
            $message = $exception instanceof SQLite3Exception
                ? 'That username, email, or role name is already in use, or the change could not be saved.'
                : $exception->getMessage();
            $messageType = 'error';
        }
        $database->close();
    }
}

$database = adminAuthDatabase();
$roles = [];
$result = $database->query('SELECT * FROM admin_roles ORDER BY is_super_admin DESC, name COLLATE NOCASE');
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $row['permissions_array'] = normalizeAdminPermissions(json_decode($row['permissions'], true));
    $roles[] = $row;
}
$assignableRoles = array_values(array_filter($roles, function ($role) use ($isSuperAdmin) {
    return $isSuperAdmin || ((int)$role['is_super_admin'] !== 1 && !adminCan('user_management', 'manage', $role));
}));
$users = [];
$result = $database->query('SELECT u.*, r.name AS role_name, r.is_super_admin, r.permissions FROM admin_users u JOIN admin_roles r ON r.id = u.role_id ORDER BY u.username COLLATE NOCASE');
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    if ($isSuperAdmin || ((int)$row['is_super_admin'] !== 1
        && (int)$row['id'] !== (int)$currentUser['id']
        && !adminCan('user_management', 'manage', $row))) {
        $users[] = $row;
    }
}
$database->close();
$modules = adminPermissionModules();
$passwordRequests = $isSuperAdmin ? getPendingAdminPasswordChangeRequests() : [];
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
$dashboardReturnUrl = 'support-chat.php';
$dashboardReturnLabel = 'Open Support Chat';
if ($isSuperAdmin) {
    $dashboardReturnUrl = 'dashboard.php';
    $dashboardReturnLabel = 'Back to Dashboard';
} else {
    foreach ($dashboardTabs as $tab => $module) {
        if (adminCan($module, 'view', $currentUser)) {
            $dashboardReturnUrl = 'dashboard.php?tab=' . rawurlencode($tab);
            $dashboardReturnLabel = 'Back to Dashboard';
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounts and Roles - Tourism Admin</title>
    <link rel="stylesheet" href="../../css/admin.css">
    <style>
        .access-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.25rem; }
        .access-form { border: 1px solid #d1d5db; border-radius: 8px; padding: 1rem; margin: 1rem 0; }
        .access-form h3 { margin-bottom: 0.75rem; }
        .access-form .form-group { margin-bottom: 0.8rem; }
        .access-users-table { width: 100%; min-width: 960px; border-collapse: collapse; background: #fff; }
        .access-users-table th, .access-users-table td { padding: 0.85rem 1rem; border-bottom: 1px solid #e5e7eb; text-align: left; vertical-align: middle; }
        .access-users-table th { background: #f3f4f6; color: #374151; font-size: 0.9rem; white-space: nowrap; }
        .access-users-table th:nth-child(1) { width: 15%; }
        .access-users-table th:nth-child(2) { width: 19%; }
        .access-users-table th:nth-child(3) { width: 14%; }
        .access-users-table th:nth-child(4) { width: 12%; }
        .access-users-table th:nth-child(5) { width: 24%; }
        .access-users-table th:nth-child(6) { width: 16%; }
        .access-users-table .form-control { width: 100%; min-width: 0; }
        .access-users-table td[colspan] { line-height: 1.5; color: #4b5563; }
        .account-summary { margin: 0 0 1.5rem; padding: 1rem 1.25rem; border: 1px solid #d1d5db; border-radius: 8px; background: #f9fafb; }
        .account-summary h3 { margin: 0 0 .8rem; color: var(--dark-green); }
        .account-summary dl { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .5rem 1rem; margin: 0; }
        .account-summary dt { color: #596579; font-size: .82rem; }
        .account-summary dd { margin: .15rem 0 0; overflow-wrap: anywhere; font-weight: 600; }
        .account-summary-note { margin: .8rem 0 0; color: #596579; font-size: .9rem; }
        .account-empty { margin: 0; padding: 1.25rem; border: 1px dashed #cbd5d1; border-radius: 6px; color: #596579; background: #f9fafb; }
        .permission-table select { min-width: 100px; }
        .access-actions { display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap; }
        .access-actions form { display: inline; }
        .status-active { color: #166534; font-weight: 600; }
        .status-inactive { color: #991b1b; font-weight: 600; }
        .access-help { color: #4b5563; margin: 0.5rem 0 1rem; }
        .password-field { display: flex; align-items: center; gap: 0.5rem; }
        .password-field .form-control { flex: 1; min-width: 0; }
        @media (max-width: 640px) {
            .admin-main { padding: 1rem; }
            .admin-container { padding: 1rem; }
            .account-summary dl { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
    </style>
</head>
<body>
<header class="admin-header">
    <div class="admin-header-content">
        <div class="admin-title"><span><?php echo $isSuperAdmin ? 'Account and Role Management' : 'Admin Account Management'; ?></span></div>
        <nav class="admin-nav"><span class="admin-user"><?php echo $isSuperAdmin ? 'Super Admin' : 'User Manager'; ?>: <strong><?php echo htmlspecialchars($currentUser['username']); ?></strong></span><a href="support-chat.php" class="btn btn-primary tab-btn">Support Chat</a><a href="logout.php" class="btn btn-primary tab-btn logout-btn">Logout</a></nav>
    </div>
</header>
<main class="admin-main">
    <div class="admin-header-actions"><a href="<?php echo htmlspecialchars($dashboardReturnUrl); ?>" class="back-link"><?php echo htmlspecialchars($dashboardReturnLabel); ?></a></div>
    <?php if ($message): ?><div class="alert alert-<?php echo htmlspecialchars($messageType); ?>" role="status"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

    <?php if ($isSuperAdmin): ?>
        <section class="admin-container" id="password-requests" style="margin-bottom:1.5rem;">
            <h2>Password-change requests<?php if ($passwordRequests): ?> (<?php echo count($passwordRequests); ?> pending)<?php endif; ?></h2>
            <p class="access-help">Set a new password of at least 12 characters, then give it to the account owner through a verified secure channel. Passwords are stored as hashes and are not displayed again after saving.</p>
            <?php if (!$passwordRequests): ?>
                <p class="account-empty">No pending password-change requests.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="access-users-table">
                        <thead><tr><th>Username</th><th>Email</th><th>Role</th><th>Requested</th><th>New password</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php foreach ($passwordRequests as $request): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($request['username']); ?></td>
                                <td><?php echo htmlspecialchars($request['email'] ?: 'No email set'); ?></td>
                                <td><?php echo htmlspecialchars($request['role_name']); ?></td>
                                <td><?php echo date('M j, Y g:i A', (int)$request['requested_at']); ?></td>
                                <td colspan="2">
                                    <form method="POST" class="password-field">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="complete_password_change">
                                        <input type="hidden" name="request_id" value="<?php echo (int)$request['id']; ?>">
                                        <input class="form-control" type="password" name="temporary_password" required minlength="12" autocomplete="new-password" placeholder="At least 12 characters">
                                        <button class="btn btn-primary" type="submit">Set password</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <section class="admin-container">
        <?php if (!$isSuperAdmin): ?>
            <section class="account-summary" aria-labelledby="account-summary-heading">
                <h3 id="account-summary-heading">Your account</h3>
                <dl>
                    <div><dt>Username</dt><dd><?php echo htmlspecialchars($currentUser['username']); ?></dd></div>
                    <div><dt>Email</dt><dd><?php echo htmlspecialchars($currentUser['email'] ?: 'No email set'); ?></dd></div>
                    <div><dt>Role</dt><dd><?php echo htmlspecialchars($currentUser['role_name']); ?></dd></div>
                    <div><dt>Status</dt><dd>Active</dd></div>
                </dl>
                <p class="account-summary-note">Profile details are read-only. Contact a Super Admin to change them.</p>
            </section>
        <?php endif; ?>
        <h2><?php echo $isSuperAdmin ? 'Admin accounts' : 'Other admin accounts'; ?></h2>
        <p class="access-help">Use a unique username and email. New passwords must be at least 12 characters. A blank email disables password-reset email for that account.<?php if ($isUserManager && !$isSuperAdmin): ?> You can manage regular accounts but cannot edit roles, Super Admins, or User Managers.<?php endif; ?></p>
        <?php if (empty($users)): ?>
            <p class="account-empty">No other admin accounts to manage yet.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="access-users-table">
                <thead><tr><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>New password</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($users as $account): ?>
                    <tr>
                        <?php if (!$isSuperAdmin && (int)$account['id'] === (int)$currentUser['id']): ?>
                            <td><strong><?php echo htmlspecialchars($account['username']); ?></strong></td>
                            <td><?php echo htmlspecialchars($account['email'] ?: 'No email set'); ?></td>
                            <td><?php echo htmlspecialchars($account['role_name']); ?></td>
                            <td><span class="status-active">Active</span></td>
                            <td colspan="2">Your account details are read-only. Contact a Super Admin to change them.</td>
                        <?php else: ?>
                        <td>
                            <form id="user-form-<?php echo (int)$account['id']; ?>" method="POST">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="save_user">
                            <input type="hidden" name="user_id" value="<?php echo (int)$account['id']; ?>">
                            </form>
                            <input class="form-control" form="user-form-<?php echo (int)$account['id']; ?>" name="username" required minlength="3" maxlength="60" value="<?php echo htmlspecialchars($account['username']); ?>">
                        </td>
                        <td><input class="form-control" form="user-form-<?php echo (int)$account['id']; ?>" type="email" name="email" maxlength="254" value="<?php echo htmlspecialchars($account['email'] ?? ''); ?>"></td>
                        <td>
                                <?php if ((int)$account['id'] === (int)$currentUser['id']): ?>
                                    <input type="hidden" form="user-form-<?php echo (int)$account['id']; ?>" name="role_id" value="<?php echo (int)$account['role_id']; ?>">
                                    <select class="form-control" disabled><option><?php echo htmlspecialchars($account['role_name']); ?></option></select>
                                <?php else: ?>
                                    <select class="form-control" form="user-form-<?php echo (int)$account['id']; ?>" name="role_id" required>
                                        <?php foreach ($assignableRoles as $role): ?><option value="<?php echo (int)$role['id']; ?>" <?php echo (int)$role['id'] === (int)$account['role_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($role['name']); ?></option><?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </td>
                            <td>
                                <input type="hidden" form="user-form-<?php echo (int)$account['id']; ?>" name="is_active" value="0">
                                <label><input type="checkbox" form="user-form-<?php echo (int)$account['id']; ?>" name="is_active" value="1" <?php echo (int)$account['is_active'] === 1 ? 'checked' : ''; ?> <?php echo (int)$account['id'] === (int)$currentUser['id'] ? 'disabled' : ''; ?>> Active</label>
                                <?php if ((int)$account['id'] === (int)$currentUser['id']): ?><input type="hidden" form="user-form-<?php echo (int)$account['id']; ?>" name="is_active" value="1"><?php endif; ?>
                            </td>
                            <td><input class="form-control" form="user-form-<?php echo (int)$account['id']; ?>" type="password" name="password" minlength="12" autocomplete="new-password" placeholder="Leave blank to keep"></td>
                            <td><button class="btn btn-primary" form="user-form-<?php echo (int)$account['id']; ?>" type="submit">Save</button></td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        <form method="POST" class="access-form">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="save_user">
            <h3>Create admin account</h3>
            <div class="access-grid">
                <div class="form-group"><label>Username</label><input class="form-control" name="username" required minlength="3" maxlength="60"></div>
                <div class="form-group"><label>Email for password reset</label><input class="form-control" type="email" name="email" required maxlength="254"></div>
                <div class="form-group"><label>Role</label><select class="form-control" name="role_id" required><?php foreach ($assignableRoles as $role): ?><option value="<?php echo (int)$role['id']; ?>"><?php echo htmlspecialchars($role['name']); ?></option><?php endforeach; ?></select></div>
                <div class="form-group"><label for="initial-password">Initial password</label><div class="password-field"><input class="form-control" id="initial-password" type="password" name="password" required minlength="12" autocomplete="new-password"><button class="btn btn-primary" type="button" data-password-toggle="initial-password" aria-controls="initial-password" aria-pressed="false">Show</button></div></div>
            </div>
            <button type="submit" class="btn btn-primary">Create account</button>
        </form>
    </section>

    <?php if ($isSuperAdmin): ?>
    <section class="admin-container" style="margin-top:1.5rem;">
        <h2>Roles and module permissions</h2>
        <p class="access-help">Super Admin always has full access. Custom roles can view a module or manage it. Manage includes view access. The built-in Super Admin role cannot be changed or deleted.</p>
        <div class="access-grid">
            <?php foreach ($roles as $role): ?>
                <form method="POST" class="access-form">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="save_role">
                    <input type="hidden" name="role_id" value="<?php echo (int)$role['id']; ?>">
                    <h3><?php echo htmlspecialchars($role['name']); ?><?php echo (int)$role['is_super_admin'] === 1 ? ' (built in)' : ''; ?></h3>
                    <div class="form-group"><label>Role name</label><input class="form-control" name="name" required minlength="2" maxlength="60" value="<?php echo htmlspecialchars($role['name']); ?>" <?php echo (int)$role['is_super_admin'] === 1 ? 'disabled' : ''; ?>></div>
                    <?php if ((int)$role['is_super_admin'] === 1): ?>
                        <p class="access-help">This role grants full access to every module and account setting.</p>
                    <?php else: ?>
                        <div class="table-responsive"><table class="permission-table"><thead><tr><th>Module</th><th>Access</th></tr></thead><tbody>
                            <?php foreach ($modules as $module => $label): ?>
                                <tr><td><?php echo htmlspecialchars($label); ?></td><td><select class="form-control" name="permissions[<?php echo htmlspecialchars($module); ?>]"><option value="none" <?php echo $role['permissions_array'][$module] === 'none' ? 'selected' : ''; ?>>None</option><option value="view" <?php echo $role['permissions_array'][$module] === 'view' ? 'selected' : ''; ?>>View</option><option value="manage" <?php echo $role['permissions_array'][$module] === 'manage' ? 'selected' : ''; ?>>Manage</option></select></td></tr>
                            <?php endforeach; ?>
                        </tbody></table></div>
                        <div class="access-actions"><button type="submit" class="btn btn-primary">Save role</button></div>
                    <?php endif; ?>
                </form>
                <?php if ((int)$role['is_super_admin'] !== 1): ?>
                    <form method="POST" class="access-form" onsubmit="return confirm('Delete this role?');">
                        <?php echo csrfField(); ?><input type="hidden" name="action" value="delete_role"><input type="hidden" name="role_id" value="<?php echo (int)$role['id']; ?>">
                        <h3>Delete <?php echo htmlspecialchars($role['name']); ?></h3><p class="access-help">Roles assigned to an account cannot be deleted.</p><button type="submit" class="btn btn-primary">Delete role</button>
                    </form>
                <?php endif; ?>
            <?php endforeach; ?>
            <form method="POST" class="access-form">
                <?php echo csrfField(); ?><input type="hidden" name="action" value="save_role"><h3>Create custom role</h3>
                <div class="form-group"><label>Role name</label><input class="form-control" name="name" required minlength="2" maxlength="60"></div>
                <div class="table-responsive"><table class="permission-table"><thead><tr><th>Module</th><th>Access</th></tr></thead><tbody>
                    <?php foreach ($modules as $module => $label): ?><tr><td><?php echo htmlspecialchars($label); ?></td><td><select class="form-control" name="permissions[<?php echo htmlspecialchars($module); ?>]"><option value="none" selected>None</option><option value="view">View</option><option value="manage">Manage</option></select></td></tr><?php endforeach; ?>
                </tbody></table></div>
                <button type="submit" class="btn btn-primary">Create role</button>
            </form>
        </div>
    </section>
    <?php endif; ?>
</main>
<script>
document.querySelectorAll('[data-password-toggle]').forEach(function(button) {
    button.addEventListener('click', function() {
        const passwordInput = document.getElementById(button.dataset.passwordToggle);
        const isVisible = passwordInput.type === 'password';
        passwordInput.type = isVisible ? 'text' : 'password';
        button.textContent = isVisible ? 'Hide' : 'Show';
        button.setAttribute('aria-pressed', String(isVisible));
    });
});
</script>
</body>
</html>
