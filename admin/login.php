<?php
// Admin Login Page - Hardened
session_start();
require_once 'config.php';

$error = '';

// Brute-force protection: limit attempts
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

// Reset attempts after 15 minutes
if (time() - ($_SESSION['last_attempt_time'] ?? 0) > 900) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

// Max 5 attempts per 15-minute window
if (($_SESSION['login_attempts'] ?? 0) >= 5) {
    $error = 'Too many failed attempts. Please try again in 15 minutes.';
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_SESSION['login_attempts'] ?? 0) < 5) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    // Validate CSRF token
    if (!validateCsrfToken($csrf)) {
        $error = 'Invalid or expired session. Please refresh and try again.';
    } elseif ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD_HASH)) {
        // Successful login: regenerate session ID to prevent fixation
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['last_activity'] = time();
        $_SESSION['login_attempts'] = 0;
        header('Location: dashboard.php');
        exit();
    } else {
        $_SESSION['login_attempts']++;
        $_SESSION['last_attempt_time'] = time();
        $error = 'Invalid username or password';
    }
}

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Tagum City</title>
<link rel="stylesheet" href="../../css/admin.css">
</head>
<body class="login-body">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
<img src="../../images/TagumTourism.jpg" alt="Tagum City" class="logo-img logo-img-small" loading="lazy">
                <h1>Tourism Admin</h1>
                <p>Destination Management System</p>
            </div>

            <form method="POST" class="login-form" autocomplete="off">
                <?php echo csrfField(); ?>
                <?php if ($error): ?>
                    <div class="error-message">
                        <span>⚠️</span>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="username">Username</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        required
                        autofocus
                        placeholder="Enter your username"
                        class="form-control"
                        autocomplete="username"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        placeholder="Enter your password"
                        class="form-control"
                        autocomplete="current-password"
                    >
                </div>

                <button type="submit" class="login-btn" <?php echo (($_SESSION['login_attempts'] ?? 0) >= 5) ? 'disabled' : ''; ?>>Login</button>
            </form>


            <div class="back-to-site">
<a href="../../index.php">← Back to Website</a>
            </div>
        </div>
    </div>
</body>
</html>
