<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Service - API Monitor</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="app-layout" id="app-layout">
        <?php $activeSidebar = 'add-service'; require __DIR__ . '/../sidebar.php'; ?>

        <div class="app-main">
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

                <?php require __DIR__ . '/service-form.php'; ?>
            </main>
        </div>
    </div>
    <script src="/js/sidebar.js"></script>
</body>
</html>
