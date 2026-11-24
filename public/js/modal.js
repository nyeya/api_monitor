(function () {
    'use strict';

    const backdrop = document.getElementById('admin-modal-backdrop');
    if (!backdrop) return;

    const closeUrl = backdrop.dataset.closeUrl || '/admin.php';

    backdrop.addEventListener('click', function (e) {
        if (e.target === backdrop) {
            window.location.href = closeUrl;
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            window.location.href = closeUrl;
        }
    });
})();
