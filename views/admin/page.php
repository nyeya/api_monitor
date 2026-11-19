<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services - API Monitor</title>
    <link rel="stylesheet" href="/css/sstyle.css">
</head>
<body>
    <header class="top-nav">
        <div class="container nav-container">
            <div class="brand">
                <span class="brand-pulse"></span>
                <span class="brand-text">API Monitor</span>
            </div>
            <nav class="nav-links">
                <a href="/index.php">Live Dashboard</a>
                <a href="/status.php">Public Status</a>
                <a href="/admin.php" class="active">Manage Services</a>
                <a href="/admin.php?logout=1" class="logout-link">Sign Out</a>
            </nav>
        </div>
    </header>

    <div class="main-wrapper">
        <main class="container">
            <?php if ($pageError !== null): ?>
                <section class="card">
                    <p class="notice" role="alert"><?php echo escapeHtml($pageError); ?></p>
                </section>
            <?php endif; ?>
            <?php if ($message !== ''): ?>
                <p class="badge badge-green alert-banner" role="status">
                    ✓ <?php echo escapeHtml($message); ?>
                </p>
            <?php endif; ?>

            <?php if ($confirmDelete !== null): ?>
                <?php require __DIR__ . '/confirm-delete.php'; ?>
            <?php else: ?>
                <?php require __DIR__ . '/service-form.php'; ?>
            <?php endif; ?>

            <?php require __DIR__ . '/service-list.php'; ?>
        </main>
    </div>
</body>
</html>