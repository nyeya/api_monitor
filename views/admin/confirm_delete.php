<section class="login-card service-form-card">
    <div class="service-form-header">
        <h2>Confirm Service Deletion</h2>
        <p class="text-muted">This action cannot be undone.</p>
    </div>
    <p class="delete-confirm-text">
        Are you sure you want to permanently delete <strong>&ldquo;<?php echo escapeHtml($confirmDelete['name']); ?>&rdquo;</strong>?
        All historical monitoring records for this service will also be removed.
    </p>
    <form method="post" action="/admin.php">
        <input type="hidden" name="csrf_token" value="<?php echo escapeHtml($csrfToken); ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?php echo escapeHtml($confirmDelete['id']); ?>">
        <input type="hidden" name="confirm_delete" value="1">
        <div class="form-footer">
            <a class="btn btn-secondary" href="/admin.php">Cancel</a>
            <button class="btn btn-danger" type="submit">Yes, Delete Service</button>
        </div>
    </form>
</section>