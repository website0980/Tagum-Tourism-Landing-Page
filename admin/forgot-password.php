<?php
require_once __DIR__ . '/config.php';
ensureAdminAuthSchema();

$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Your session expired. Refresh the page and try again.';
    } else {
        $now = time();
        $recentAttempts = array_filter($_SESSION['reset_request_attempts'] ?? [], function ($attempt) use ($now) {
            return $attempt > $now - 900;
        });
        if (count($recentAttempts) >= 3) {
            $error = 'Too many requests. Please try again in 15 minutes.';
        } else {
            $recentAttempts[] = $now;
            $_SESSION['reset_request_attempts'] = $recentAttempts;
            $identifier = trim((string)($_POST['account'] ?? ''));
            if ($identifier !== '' && strlen($identifier) <= 254) {
                submitAdminPasswordChangeRequest($identifier);
            }
            $message = 'If an active admin account matches that username or email, a password-change request has been sent to the Super Admin for review.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Tagum Tourism Admin</title>
    <link rel="stylesheet" href="../../css/admin.css">
</head>
<body class="login-body">
    <main class="login-container">
        <section class="login-box">
            <header class="login-header">
                <h1>Forgot password</h1>
                <p>Tourism Admin</p>
            </header>
            <?php if ($error): ?><div class="error-message"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
            <?php if ($message): ?><div class="alert alert-success" role="status"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
            <form method="POST" class="login-form" autocomplete="on">
                <?php echo csrfField(); ?>
                <div class="form-group">
                    <label for="account">Admin username or email</label>
                    <input class="form-control" type="text" id="account" name="account" required autocomplete="username" maxlength="254">
                </div>
                <button type="submit" class="login-btn">Request password change</button>
            </form>
            <p class="access-help">A Super Admin will review the request and set a temporary password. They will contact you when it is ready.</p>
            <div class="back-to-site"><a href="login.php">Back to login</a></div>
        </section>
    </main>
</body>
</html>
