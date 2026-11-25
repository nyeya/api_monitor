<section class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Service</th>
                    <th>Endpoint URL</th>
                    <th>Method</th>
                    <th>Exp. Code</th>
                    <th>Limits</th>
                    <th>Interval</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$services): ?>
                    <tr>
                        <td colspan="9" class="table-empty-cell">No services configured yet. <a href="/add_service.php">Add your first service</a>.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($services as $svc): ?>
                        <?php $enabled = filter_var($svc['enabled'], FILTER_VALIDATE_BOOLEAN); ?>
                        <tr>
                            <td class="text-muted">#<?php echo escapeHtml($svc['id']); ?></td>
                            <td>
                                <strong><?php echo escapeHtml($svc['name']); ?></strong>
                                <?php if (!empty($svc['description'])): ?>
                                    <div class="service-desc-subtext"><?php echo escapeHtml($svc['description']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code class="table-endpoint-code" title="<?php echo escapeHtml($svc['url']); ?>"><?php echo escapeHtml($svc['url']); ?></code>
                            </td>
                            <td><code><?php echo escapeHtml($svc['http_method']); ?></code></td>
                            <td><code><?php echo escapeHtml($svc['expected_http_code']); ?></code></td>
                            <td class="nowrap">
                                <span><?php echo escapeHtml($svc['timeout_seconds']); ?>s</span>
                                <span class="text-muted">/ <?php echo escapeHtml($svc['warning_threshold_ms']); ?>ms</span>
                            </td>
                            <td class="nowrap"><?php echo escapeHtml(formatDurationSeconds((int)$svc['check_interval_seconds'])); ?></td>
                            <td>
                                <span class="badge <?php echo $enabled ? 'badge-green' : 'badge-grey'; ?>">
                                    <span class="status-dot <?php echo $enabled ? 'dot-green' : 'dot-grey'; ?>" aria-hidden="true"></span>
                                    <?php echo $enabled ? 'Active' : 'Disabled'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="table-action-btns">
                                    <a class="btn btn-secondary btn-sm" href="/admin.php?edit=<?php echo escapeHtml($svc['id']); ?>">Edit</a>
                                    <form method="post" action="/admin.php" class="inline-form">
                                        <input type="hidden" name="csrf_token" value="<?php echo escapeHtml($csrfToken); ?>">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?php echo escapeHtml($svc['id']); ?>">
                                        <button class="btn btn-secondary btn-sm" type="submit"><?php echo $enabled ? 'Disable' : 'Enable'; ?></button>
                                    </form>
                                    <a class="btn btn-danger btn-sm" href="/admin.php?confirm_delete=<?php echo escapeHtml($svc['id']); ?>">Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>