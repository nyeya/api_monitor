<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - API Monitor</title>
    <link rel="stylesheet" href="/css/style.css">
</head>

<body>
    <main class="container login-container" style="margin-top: 4rem;">
        <div style="text-align: center; margin-bottom: 1.5rem;">
            <a href="/status.php" style="text-decoration: none; color: inherit; display: inline-flex; align-items: center; gap: 0.45rem;">
                <span class="brand-pulse"></span>
                <strong style="font-size: 1.35rem;">API Monitor</strong>
            </a>
        </div>

        <section class="card">
            <h2>Sign In</h2>
            <p class="text-muted">Enter your credentials to continue.</p>

            <?php if ($loginError): ?>
                <p class="notice" role="alert"><?php echo escapeHtml($loginError); ?></p>
            <?php endif; ?>

            <form method="post" action="/admin.php">
                <input type="hidden" name="admin_action" value="login">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text" required autofocus placeholder="Username" autocomplete="username" value="admin">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required placeholder="Password" autocomplete="current-password" value="admin123">
                </div>
                <div class="form-actions login-actions">
                    <button class="btn btn-primary btn-block" type="submit">Sign In</button>
                </div>
            </form>
        </section>
    </main>
</body>

</html>