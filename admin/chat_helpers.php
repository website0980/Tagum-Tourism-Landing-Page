<?php

function getAdminChatAccounts(): array {
    ensureAdminAuthSchema();
    $database = adminAuthDatabase();
    $result = $database->query('SELECT u.id, u.username, u.email, c.id AS conversation_id, c.updated_at,
            SUM(CASE WHEN m.sender_user_id = u.id AND m.read_at IS NULL THEN 1 ELSE 0 END) AS unread_count
        FROM admin_users u
        JOIN admin_roles r ON r.id = u.role_id
        LEFT JOIN admin_chat_conversations c ON c.account_user_id = u.id
        LEFT JOIN admin_chat_messages m ON m.conversation_id = c.id
        WHERE u.is_active = 1 AND r.is_super_admin = 0
        GROUP BY u.id
        ORDER BY unread_count DESC, c.updated_at DESC, u.username COLLATE NOCASE');
    $accounts = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $accounts[] = $row;
    }
    $database->close();
    return $accounts;
}

function getOrCreateAdminChatConversation(int $accountUserId): int {
    ensureAdminAuthSchema();
    $account = getAdminUserById($accountUserId);
    if (!$account || !(int)$account['is_active'] || (int)$account['is_super_admin'] === 1) {
        throw new RuntimeException('Chat is only available for active non-Super Admin accounts.');
    }

    $database = adminAuthDatabase();
    $insert = $database->prepare('INSERT OR IGNORE INTO admin_chat_conversations (account_user_id, created_at, updated_at) VALUES (:user_id, :now, :now)');
    $insert->bindValue(':user_id', $accountUserId, SQLITE3_INTEGER);
    $insert->bindValue(':now', time(), SQLITE3_INTEGER);
    $insert->execute();
    $lookup = $database->prepare('SELECT id FROM admin_chat_conversations WHERE account_user_id = :user_id');
    $lookup->bindValue(':user_id', $accountUserId, SQLITE3_INTEGER);
    $conversationId = (int)$lookup->execute()->fetchArray(SQLITE3_NUM)[0];
    $database->close();
    return $conversationId;
}

function canAccessAdminChat(int $conversationId, int $userId): bool {
    $user = getAdminUserById($userId);
    if (!$user || !(int)$user['is_active']) {
        return false;
    }
    if ((int)$user['is_super_admin'] === 1) {
        return true;
    }

    $database = adminAuthDatabase();
    $statement = $database->prepare('SELECT 1 FROM admin_chat_conversations WHERE id = :conversation_id AND account_user_id = :user_id');
    $statement->bindValue(':conversation_id', $conversationId, SQLITE3_INTEGER);
    $statement->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $allowed = (bool)$statement->execute()->fetchArray(SQLITE3_NUM);
    $database->close();
    return $allowed;
}

function loadAdminChatMessages(int $conversationId, int $userId): array {
    if (!canAccessAdminChat($conversationId, $userId)) {
        throw new RuntimeException('You do not have access to this conversation.');
    }
    $database = adminAuthDatabase();
    $statement = $database->prepare('SELECT m.id, m.sender_user_id, m.body, m.created_at, m.read_at, u.username AS sender_name
        FROM admin_chat_messages m JOIN admin_users u ON u.id = m.sender_user_id
        WHERE m.conversation_id = :conversation_id ORDER BY m.id ASC');
    $statement->bindValue(':conversation_id', $conversationId, SQLITE3_INTEGER);
    $result = $statement->execute();
    $messages = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $messages[] = $row;
    }
    $database->close();
    return $messages;
}

function sendAdminChatMessage(int $conversationId, int $senderUserId, string $body): void {
    $body = trim($body);
    if ($body === '' || strlen($body) > 4000) {
        throw new RuntimeException('Messages must contain 1 to 4,000 bytes of text.');
    }
    if (!canAccessAdminChat($conversationId, $senderUserId)) {
        throw new RuntimeException('You do not have access to this conversation.');
    }

    $database = adminAuthDatabase();
    $now = time();
    try {
        $database->exec('BEGIN IMMEDIATE');
        $insert = $database->prepare('INSERT INTO admin_chat_messages (conversation_id, sender_user_id, body, created_at) VALUES (:conversation_id, :sender_user_id, :body, :created_at)');
        $insert->bindValue(':conversation_id', $conversationId, SQLITE3_INTEGER);
        $insert->bindValue(':sender_user_id', $senderUserId, SQLITE3_INTEGER);
        $insert->bindValue(':body', $body, SQLITE3_TEXT);
        $insert->bindValue(':created_at', $now, SQLITE3_INTEGER);
        $insert->execute();
        $update = $database->prepare('UPDATE admin_chat_conversations SET updated_at = :updated_at WHERE id = :conversation_id');
        $update->bindValue(':updated_at', $now, SQLITE3_INTEGER);
        $update->bindValue(':conversation_id', $conversationId, SQLITE3_INTEGER);
        $update->execute();
        $database->exec('COMMIT');
    } catch (Throwable $exception) {
        $database->exec('ROLLBACK');
        $database->close();
        throw $exception;
    }
    $database->close();
}

function markAdminChatRead(int $conversationId, int $userId): void {
    if (!canAccessAdminChat($conversationId, $userId)) {
        throw new RuntimeException('You do not have access to this conversation.');
    }
    $database = adminAuthDatabase();
    $statement = $database->prepare('UPDATE admin_chat_messages SET read_at = :read_at WHERE conversation_id = :conversation_id AND sender_user_id != :user_id AND read_at IS NULL');
    $statement->bindValue(':read_at', time(), SQLITE3_INTEGER);
    $statement->bindValue(':conversation_id', $conversationId, SQLITE3_INTEGER);
    $statement->bindValue(':user_id', $userId, SQLITE3_INTEGER);
    $statement->execute();
    $database->close();
}
