<section class="card">
    <h2><?php echo $editingService ? 'Edit Monitored Service' : 'Add New Service'; ?></h2>
    <p class="text-muted">Configure endpoint parameters, expectations, and monitoring intervals.</p>
    <form method="post" action="<?php echo escapeHtml($formAction); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo escapeHtml($csrfToken); ?>">
        <input type="hidden" name="action" value="<?php echo $editingService ? 'save' : 'add'; ?>">
        <input type="hidden" name="id" value="<?php echo escapeHtml($formValues['id']); ?>">

        <div class="form-row">
            <div class="form-group">
                <label for="name">Service Name *</label>
                <input id="name" name="name" maxlength="150" value="<?php echo escapeHtml($formValues['name']); ?>" placeholder="e.g. JSONPlaceholder API" required>
            </div>
            <div class="form-group">
                <label for="url">Endpoint URL *</label>
                <input id="url" name="url" type="url" value="<?php echo escapeHtml($formValues['url']); ?>" placeholder="https://api.example.com/health" required>
            </div>
        </div>

        <div class="form-group">
            <label for="description">Description</label>
            <input id="description" name="description" maxlength="2000" value="<?php echo escapeHtml($formValues['description']); ?>" placeholder="Brief description of service purpose">
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="http_method">HTTP Method</label>
                <select id="http_method" name="http_method">
                    <option value="GET"<?php echo $formValues['http_method'] === 'GET' ? ' selected' : ''; ?>>GET</option>
                    <option value="HEAD"<?php echo $formValues['http_method'] === 'HEAD' ? ' selected' : ''; ?>>HEAD</option>
                </select>
            </div>
            <div class="form-group">
                <label for="expected_http_code">Expected HTTP Code</label>
                <input id="expected_http_code" name="expected_http_code" type="number" min="100" max="599" value="<?php echo escapeHtml($formValues['expected_http_code']); ?>" required>
            </div>
            <div class="form-group">
                <label for="timeout_seconds">Timeout (sec)</label>
                <input id="timeout_seconds" name="timeout_seconds" type="number" min="1" max="120" value="<?php echo escapeHtml($formValues['timeout_seconds']); ?>" required>
            </div>
            <div class="form-group">
                <label for="warning_threshold_ms">Warning Threshold (ms)</label>
                <input id="warning_threshold_ms" name="warning_threshold_ms" type="number" min="1" max="120000" value="<?php echo escapeHtml($formValues['warning_threshold_ms']); ?>" required>
            </div>
            <div class="form-group">
                <label for="check_interval_seconds">Check Interval (sec)</label>
                <input id="check_interval_seconds" name="check_interval_seconds" type="number" min="5" max="86400" value="<?php echo escapeHtml($formValues['check_interval_seconds']); ?>" required>
            </div>
        </div>

        <label class="checkbox-label">
            <input name="enabled" type="checkbox" value="1"<?php echo $formValues['enabled'] ? ' checked' : ''; ?>>
            Monitoring Active (Enabled)
        </label>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?php echo $editingService ? 'Update Service' : 'Add Service'; ?></button>
        </div>
    </form>
</section>