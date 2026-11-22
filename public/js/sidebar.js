(function () {
    'use strict';

    function initSidebar() {
        const layout = document.getElementById('app-layout');
        const collapseBtn = document.getElementById('sidebar-collapse-btn');
        const mobileToggleBtn = document.getElementById('mobile-sidebar-toggle');
        const backdrop = document.getElementById('sidebar-backdrop');

        if (!layout) return;

        if (window.innerWidth > 900) {
            const isCollapsed = localStorage.getItem('api_monitor_sidebar_collapsed') === 'true';
            if (isCollapsed) {
                layout.classList.add('sidebar-collapsed');
            }
        }

        if (collapseBtn) {
            collapseBtn.addEventListener('click', function () {
                layout.classList.toggle('sidebar-collapsed');
                const collapsed = layout.classList.contains('sidebar-collapsed');
                localStorage.setItem('api_monitor_sidebar_collapsed', collapsed ? 'true' : 'false');
            });
        }

        if (mobileToggleBtn) {
            mobileToggleBtn.addEventListener('click', function () {
                layout.classList.toggle('sidebar-open');
            });
        }

        if (backdrop) {
            backdrop.addEventListener('click', function () {
                layout.classList.remove('sidebar-open');
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && layout.classList.contains('sidebar-open')) {
                layout.classList.remove('sidebar-open');
            }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 900 && layout.classList.contains('sidebar-open')) {
                layout.classList.remove('sidebar-open');
            }
        });

        document.querySelectorAll('.nav-dropdown-toggle').forEach(function (toggleBtn) {
            toggleBtn.addEventListener('click', function () {
                const dropdown = toggleBtn.closest('.nav-dropdown');
                if (!dropdown) return;
                const isOpen = dropdown.classList.toggle('nav-dropdown-open');
                toggleBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSidebar);
    } else {
        initSidebar();
    }
})();
