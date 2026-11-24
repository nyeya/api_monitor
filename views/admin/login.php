<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - API Monitor</title>
    <link rel="stylesheet" href="/css/style.css">
</head>

<body class="login-body">
    <main class="container login-container">
        <div class="login-brand">
            <a href="/status.php" class="login-brand-link">
                <span class="brand-pulse"></span>
                <strong>API Monitor</strong>
            </a>
        </div>

        <section class="card login-card">
            <h2>Admin Sign In</h2>
            <p class="text-muted">Enter your administrator credentials to access the monitoring dashboard.</p>

            <?php if ($loginError): ?>
                <p class="notice" role="alert"><?php echo escapeHtml($loginError); ?></p>
            <?php endif; ?>

            <form method="post" action="/admin.php">
                <input type="hidden" name="admin_action" value="login">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" required autofocus placeholder="Enter username" autocomplete="username" value="<?php echo escapeHtml($submittedUsername ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required placeholder="Enter password" autocomplete="current-password">
                </div>
                <div class="form-actions login-actions">
                    <button class="btn btn-primary btn-block" type="submit">Sign In</button>
                </div>
            </form>

            <p class="login-hint">
                <a href="/status.php" class="subtle-link">&larr; View Public Status Page</a>
            </p>
        </section>
    </main>
</body>

</html>