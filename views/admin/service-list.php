<section class="card">
    <h2>Configured Monitoring Services</h2>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Endpoint URL</th>
                    <th>Method</th>
                    <th>Exp. Code</th>
                    <th>Timeout</th>
                    <th>Interval</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$services): ?>
                    <tr><td colspan="9" class="table-empty-cell">No services configured yet.</td></tr>
                <?php else: ?>
                    <?php foreach ($services as $svc): ?>
                        <?php $enabled = filter_var($svc['enabled'], FILTER_VALIDATE_BOOLEAN); ?>
                        <tr>
                            <td><?php echo escapeHtml($svc['id']); ?></td>
                            <td><strong><?php echo escapeHtml($svc['name']); ?></strong></td>
                            <td><code class="table-endpoint-code"><?php echo escapeHtml($svc['url']); ?></code></td>
                            <td><?php echo escapeHtml($svc['http_method']); ?></td>
                            <td><?php echo escapeHtml($svc['expected_http_code']); ?></td>
                            <td><?php echo escapeHtml($svc['timeout_seconds']); ?>s</td>
                            <td><?php echo escapeHtml($svc['check_interval_seconds']); ?>s</td>
                            <td>
                                <span class="badge <?php echo $enabled ? 'badge-green' : 'badge-grey'; ?>">
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