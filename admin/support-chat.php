<?php
require_once __DIR__ . '/config.php';
requireAuth();
require_once __DIR__ . '/chat_helpers.php';

$user = currentAdminUser();
$isSuperAdmin = (int)$user['is_super_admin'] === 1;
$accounts = $isSuperAdmin ? getAdminChatAccounts() : [];
$selectedAccount = null;
$conversationId = 0;
$error = '';
$draft = '';
$notice = $_SESSION['admin_chat_notice'] ?? '';
unset($_SESSION['admin_chat_notice']);

if ($isSuperAdmin) {
    $requestedUserId = (int)($_GET['user'] ?? ($accounts[0]['id'] ?? 0));
    foreach ($accounts as $account) {
        if ((int)$account['id'] === $requestedUserId) {
            $selectedAccount = $account;
            break;
        }
    }
    if ($selectedAccount) {
        $conversationId = getOrCreateAdminChatConversation((int)$selectedAccount['id']);
    }
} else {
    $selectedAccount = $user;
    $conversationId = getOrCreateAdminChatConversation((int)$user['id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $draft = (string)($_POST['message'] ?? '');
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Refresh and try again.';
    } elseif (!$conversationId || (int)($_POST['conversation_id'] ?? 0) !== $conversationId) {
        $error = 'Select a valid support conversation.';
    } else {
        try {
            sendAdminChatMessage($conversationId, (int)$user['id'], $draft);
            $_SESSION['admin_chat_notice'] = 'Message sent.';
            $query = $isSuperAdmin ? '?user=' . (int)$selectedAccount['id'] : '';
            header('Location: support-chat.php' . $query);
            exit();
        } catch (Throwable $exception) {
            $error = $exception->getMessage();
        }
    }
}

$messages = [];
if ($conversationId) {
    markAdminChatRead($conversationId, (int)$user['id']);
    $messages = loadAdminChatMessages($conversationId, (int)$user['id']);
    if ($isSuperAdmin) {
        $accounts = getAdminChatAccounts();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Support Chat - Tourism Admin</title>
    <link rel="stylesheet" href="../../css/admin.css">
    <style>
        .support-chat-layout { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1rem; min-height: 600px; }
        .support-chat-layout.is-super-admin { grid-template-columns: minmax(220px, 300px) minmax(0, 1fr); }
        .support-chat-accounts, .support-chat-panel { background: #fff; border: 1px solid #d1d5db; border-radius: 8px; min-width: 0; }
        .support-chat-accounts { padding: 1rem; }
        .support-chat-accounts h2, .support-chat-panel h1 { font-size: 1.15rem; margin: 0 0 1rem; color: var(--dark-green); }
        .support-account-link { display: flex; justify-content: space-between; gap: .5rem; padding: .75rem; margin: .25rem 0; border-radius: 6px; color: var(--dark-gray); text-decoration: none; overflow-wrap: anywhere; }
        .support-account-link:hover, .support-account-link.active { background: #ecfdf5; color: var(--dark-green); }
        .support-unread { min-width: 1.5rem; text-align: center; color: #fff; background: var(--dark-green); border-radius: 999px; font-size: .8rem; padding: .05rem .35rem; }
        .support-chat-panel { display: flex; flex-direction: column; padding: 1rem; }
        .support-chat-messages { display: flex; flex: 1; flex-direction: column; gap: .75rem; max-height: 58vh; min-height: 320px; overflow-y: auto; padding: .5rem; background: #f3f4f6; border-radius: 6px; }
        .support-chat-message { max-width: min(78%, 680px); padding: .7rem .9rem; border-radius: 8px; background: #fff; box-shadow: 0 1px 2px rgba(0,0,0,.08); overflow-wrap: anywhere; }
        .support-chat-message.mine { align-self: flex-end; color: #fff; background: var(--dark-green); }
        .support-chat-message strong, .support-chat-message time { display: block; font-size: .78rem; opacity: .82; }
        .support-chat-message p { margin: .3rem 0 0; white-space: pre-wrap; }
        .support-chat-compose { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .6rem; margin-top: .75rem; align-items: end; }
        .support-chat-compose textarea { width: 100%; min-width: 0; min-height: 76px; box-sizing: border-box; resize: vertical; }
        .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0; }
        .support-chat-empty { margin: auto; padding: 2rem 1rem; text-align: center; color: var(--gray); }
        .support-chat-topbar { display: flex; align-items: center; justify-content: space-between; gap: .75rem; }
        .support-chat-links { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: 1rem; }
        @media (max-width: 720px) {
            .support-chat-layout.is-super-admin { grid-template-columns: minmax(0, 1fr); }
            .support-chat-accounts { max-height: 220px; overflow-y: auto; }
            .support-chat-messages { max-height: 48vh; }
            .support-chat-message { max-width: 92%; }
            .support-chat-compose { grid-template-columns: minmax(0, 1fr); align-items: stretch; }
            .support-chat-compose button { width: 100%; min-height: 48px; }
        }
    </style>
</head>
<body>
<header class="admin-header">
    <div class="admin-header-content">
        <div class="admin-title"><span>Super Admin Support Chat</span></div>
        <nav class="admin-nav"><span class="admin-user">Signed in as <strong><?php echo htmlspecialchars($user['username']); ?></strong></span><a href="access-management.php" class="btn btn-primary tab-btn"><?php echo $isSuperAdmin ? 'Accounts &amp; Roles' : 'Admin Accounts'; ?></a><a href="logout.php" class="btn btn-primary tab-btn logout-btn">Logout</a></nav>
    </div>
</header>
<main class="admin-main">
    <div class="support-chat-links"><a href="dashboard.php" class="back-link">Back to dashboard</a><?php if ($isSuperAdmin): ?><a href="access-management.php" class="back-link">Accounts &amp; Roles</a><?php endif; ?></div>
    <?php if ($notice): ?><div class="alert alert-success" role="status"><?php echo htmlspecialchars($notice); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error" role="alert"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <div class="support-chat-layout<?php echo $isSuperAdmin ? ' is-super-admin' : ''; ?>">
        <?php if ($isSuperAdmin): ?>
            <aside class="support-chat-accounts">
                <h2>Admin accounts</h2>
                <?php if (!$accounts): ?><p class="support-chat-empty">No active admin accounts yet.</p><?php endif; ?>
                <?php foreach ($accounts as $account): ?>
                    <a class="support-account-link <?php echo $selectedAccount && (int)$selectedAccount['id'] === (int)$account['id'] ? 'active' : ''; ?>" href="?user=<?php echo (int)$account['id']; ?>">
                        <span><?php echo htmlspecialchars($account['username']); ?></span>
                        <?php if ((int)$account['unread_count'] > 0): ?><span class="support-unread"><?php echo (int)$account['unread_count']; ?></span><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </aside>
        <?php endif; ?>
        <section class="support-chat-panel">
            <div class="support-chat-topbar"><h1><?php echo $isSuperAdmin && $selectedAccount ? 'Conversation with ' . htmlspecialchars($selectedAccount['username']) : ($isSuperAdmin ? 'Choose an admin account' : 'Contact Super Admin'); ?></h1></div>
            <?php if (!$conversationId): ?>
                <p class="support-chat-empty">Create an active admin account to start a support conversation.</p>
            <?php else: ?>
                <div class="support-chat-messages" aria-live="polite" aria-label="Conversation messages">
                    <?php if (!$messages): ?><p class="support-chat-empty">No messages yet. Send a message to start the conversation.</p><?php endif; ?>
                    <?php foreach ($messages as $message): ?>
                        <article class="support-chat-message <?php echo (int)$message['sender_user_id'] === (int)$user['id'] ? 'mine' : ''; ?>">
                            <strong><?php echo htmlspecialchars($message['sender_name']); ?></strong>
                            <p><?php echo nl2br(htmlspecialchars($message['body'])); ?></p>
                            <time datetime="<?php echo date(DATE_ATOM, (int)$message['created_at']); ?>"><?php echo date('M j, Y g:i A', (int)$message['created_at']); ?></time>
                        </article>
                    <?php endforeach; ?>
                </div>
                <form method="POST" class="support-chat-compose">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="conversation_id" value="<?php echo $conversationId; ?>">
                    <label class="sr-only" for="chat-message">Message to Super Admin</label>
                    <textarea class="form-control" id="chat-message" name="message" maxlength="4000" required placeholder="Write a message..."><?php echo htmlspecialchars($draft); ?></textarea>
                    <button class="btn btn-primary" type="submit">Send</button>
                </form>
            <?php endif; ?>
        </section>
    </div>
</main>
</body>
</html>
