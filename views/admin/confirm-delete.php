<section class="card">
    <h2>Confirm Service Deletion</h2>
    <p>Are you sure you want to permanently delete “<strong><?php echo escapeHtml($confirmDelete['name']); ?></strong>” and all of its historical monitoring records?</p>
    <form method="post" action="/admin.php">
        <input type="hidden" name="csrf_token" value="<?php echo escapeHtml($csrfToken); ?>">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?php echo escapeHtml($confirmDelete['id']); ?>">
        <input type="hidden" name="confirm_delete" value="1">
        <div class="form-actions">
            <button class="btn btn-danger" type="submit">Yes, Delete Service</button>
            <a class="btn btn-secondary" href="/admin.php">Cancel</a>
        </div>
    </form>
</section>