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
        <?php $activeSidebar = 'add_service'; require __DIR__ . '/../partials/sidebar.php'; ?>

        <div class="app-main">
            <main class="container form-page-container">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Add New Service</h1>
                        <p class="text-muted page-subtitle">Register a new API or web endpoint for automated health checks</p>
                    </div>
                    <div class="page-header-actions">
                        <a href="/admin.php" class="btn btn-sm btn-secondary">&larr; Services List</a>
                    </div>
                </div>

                <?php if ($pageError !== null): ?>
                    <div class="alert-banner alert-error" role="alert"><?php echo escapeHtml($pageError); ?></div>
                <?php endif; ?>
                <?php if ($message !== ''): ?>
                    <div class="alert-banner alert-success" role="status">
                        <span aria-hidden="true">&#10003;</span> <?php echo escapeHtml($message); ?>
                        <a href="/admin.php" class="alert-link">View all services &rarr;</a>
                    </div>
                <?php endif; ?>

                <?php require __DIR__ . '/service_form.php'; ?>
            </main>
        </div>
    </div>
    <script src="/js/sidebar.js"></script>
</body>
</html>
