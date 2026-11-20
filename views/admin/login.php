<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - API Monitor</title>
    <link rel="stylesheet" href="/css/sstyle.css">
</head>
<body>
    <div class="app-layout" id="app-layout">
        <?php $activeSidebar = 'admin'; require __DIR__ . '/../sidebar.php'; ?>

        <!-- Main Content Area -->
        <div class="app-main">
            <main class="container login-container">
                <section class="card">
                    <h2>Admin Authentication</h2>
                    <p class="text-muted">Enter administrator credentials to manage monitored services.</p>
                    <?php if ($loginError): ?>
                        <p class="notice" role="alert"><?php echo escapeHtml($loginError); ?></p>
                    <?php endif; ?>
                    <form method="post" action="/admin.php">
                        <input type="hidden" name="admin_action" value="login">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input id="username" name="username" type="text" required autofocus value="admin">
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input id="password" name="password" type="password" required value="admin123">
                        </div>
                        <div class="form-actions login-actions">
                            <button class="btn btn-primary btn-block" type="submit">Sign In</button>
                        </div>
                    </form>
                    <div class="login-hint">
                        Default: <code>admin</code> / <code>admin123</code>
                    </div>
                </section>
            </main>
        </div>
    </div>
    <script src="/js/sidebar.js"></script>
</body>
</html>
