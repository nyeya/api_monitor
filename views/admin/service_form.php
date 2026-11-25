<?php
$approvedHosts = defined('APPROVED_HOSTS') ? APPROVED_HOSTS : [];
?>
<section class="login-card service-form-card">
    <div class="service-form-header">
        <h2><?php echo $editingService ? 'Edit Monitored Service' : 'Service Configuration'; ?></h2>
        <p class="text-muted">Configure endpoint details, expected HTTP status, and alert thresholds.</p>
    </div>

    <?php if ($editingService && !empty($pageError)): ?>
        <p class="notice" role="alert"><?php echo escapeHtml($pageError); ?></p>
    <?php endif; ?>

    <form method="post" action="<?php echo escapeHtml($formAction); ?>" class="service-form">
        <input type="hidden" name="csrf_token" value="<?php echo escapeHtml($csrfToken); ?>">
        <input type="hidden" name="action" value="<?php echo $editingService ? 'save' : 'add'; ?>">
        <input type="hidden" name="id" value="<?php echo escapeHtml($formValues['id']); ?>">

        <div class="form-section">
            <div class="form-section-title">Endpoint Details</div>

            <div class="form-grid-name-method">
                <div class="form-group">
                    <label for="name">Service Name *</label>
                    <input id="name" name="name" maxlength="150" value="<?php echo escapeHtml($formValues['name']); ?>" placeholder="e.g. JSONPlaceholder API" required>
                </div>
                <div class="form-group">
                    <label for="http_method">HTTP Method</label>
                    <select id="http_method" name="http_method">
                        <option value="GET" <?php echo $formValues['http_method'] === 'GET' ? ' selected' : ''; ?>>GET</option>
                        <option value="HEAD" <?php echo $formValues['http_method'] === 'HEAD' ? ' selected' : ''; ?>>HEAD</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="url">Endpoint URL *</label>
                <input id="url" name="url" type="url" value="<?php echo escapeHtml($formValues['url']); ?>" placeholder="https://jsonplaceholder.typicode.com/posts/1" required>
                <?php if ($approvedHosts): ?>
                    <div class="form-hint approved-hosts-hint">
                        <span class="hint-label">Allowed hosts:</span>
                        <?php foreach ($approvedHosts as $host): ?>
                            <span class="host-chip"><?php echo escapeHtml($host); ?></span>
                        <?php endforeach; ?>
                        <span class="host-chip">localhost/mock_api.php</span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <input id="description" name="description" maxlength="2000" value="<?php echo escapeHtml($formValues['description']); ?>" placeholder="Brief description of what this endpoint verifies">
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title">Checks &amp; Thresholds</div>

            <div class="form-grid-4">
                <div class="form-group">
                    <label for="expected_http_code">Expected Code</label>
                    <input id="expected_http_code" name="expected_http_code" type="number" min="100" max="599" value="<?php echo escapeHtml($formValues['expected_http_code']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="timeout_seconds">Timeout (sec)</label>
                    <input id="timeout_seconds" name="timeout_seconds" type="number" min="1" max="120" value="<?php echo escapeHtml($formValues['timeout_seconds']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="warning_threshold_ms">Slow Threshold (ms)</label>
                    <input id="warning_threshold_ms" name="warning_threshold_ms" type="number" min="1" max="120000" value="<?php echo escapeHtml($formValues['warning_threshold_ms']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="check_interval_seconds">Interval (sec)</label>
                    <input id="check_interval_seconds" name="check_interval_seconds" type="number" min="5" max="86400" value="<?php echo escapeHtml($formValues['check_interval_seconds']); ?>" required>
                </div>
            </div>
        </div>

        <label class="toggle-card" for="service-enabled-toggle">
            <div class="toggle-card-text">
                <span class="toggle-card-title">Monitoring Active</span>
                <span class="toggle-card-desc">Include this endpoint in automated background checks</span>
            </div>
            <span class="toggle-switch">
                <input id="service-enabled-toggle" name="enabled" type="checkbox" value="1" <?php echo $formValues['enabled'] ? ' checked' : ''; ?>>
                <span class="toggle-slider" aria-hidden="true"></span>
            </span>
        </label>

        <div class="form-footer">
            <?php if ($editingService): ?>
                <a class="btn btn-secondary" href="/admin.php">Cancel</a>
            <?php else: ?>
                <a class="btn btn-secondary" href="/admin.php">Cancel</a>
            <?php endif; ?>
            <button class="btn btn-primary" type="submit"><?php echo $editingService ? 'Save Changes' : 'Add Service'; ?></button>
        </div>
    </form>
</section>