<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services - API Monitor</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="app-layout" id="app-layout">
        <?php $activeSidebar = 'admin'; require __DIR__ . '/../partials/sidebar.php'; ?>

        <div class="app-main">
            <main class="container">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Manage Services</h1>
                        <p class="text-muted page-subtitle">Configure monitored endpoints, thresholds, and check intervals</p>
                    </div>
                    <div class="page-header-actions">
                        <a href="/add_service.php" class="btn btn-primary btn-sm">+ Add Service</a>
                    </div>
                </div>

                <?php if ($pageError !== null && !$editingService && $confirmDelete === null): ?>
                    <div class="alert-banner alert-error" role="alert"><?php echo escapeHtml($pageError); ?></div>
                <?php endif; ?>
                <?php if ($message !== ''): ?>
                    <div class="alert-banner alert-success" role="status">
                        <span aria-hidden="true">&#10003;</span> <?php echo escapeHtml($message); ?>
                    </div>
                <?php endif; ?>

                <?php require __DIR__ . '/service_table.php'; ?>
            </main>
        </div>
    </div>

    <?php if ($confirmDelete !== null || $editingService): ?>
        <div class="modal-backdrop" id="admin-modal-backdrop" data-close-url="/admin.php" role="dialog" aria-modal="true">
            <div class="modal-dialog <?php echo $confirmDelete !== null ? 'modal-dialog-sm' : ''; ?>">
                <a href="/admin.php" class="modal-close-btn" title="Close" aria-label="Close">&times;</a>

                <?php if ($confirmDelete !== null): ?>
                    <?php require __DIR__ . '/confirm_delete.php'; ?>
                <?php else: ?>
                    <?php require __DIR__ . '/service_form.php'; ?>
                <?php endif; ?>
            </div>
        </div>
        <script src="/js/modal.js"></script>
    <?php endif; ?>

    <script src="/js/sidebar.js"></script>
</body>
</html>
