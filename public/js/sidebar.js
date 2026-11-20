/**
 * API Monitor - Sidebar Navigation & Responsive Drawer Interactions
 * Handles desktop collapse/expand toggling with localStorage persistence,
 * and mobile slide-out drawer behavior.
 */
(function () {
    'use strict';

    function initSidebar() {
        const layout = document.getElementById('app-layout');
        const collapseBtn = document.getElementById('sidebar-collapse-btn');
        const mobileToggleBtn = document.getElementById('mobile-sidebar-toggle');
        const backdrop = document.getElementById('sidebar-backdrop');

        if (!layout) return;

        // Apply saved collapse preference on desktop screens (> 900px)
        if (window.innerWidth > 900) {
            const isCollapsed = localStorage.getItem('api_monitor_sidebar_collapsed') === 'true';
            if (isCollapsed) {
                layout.classList.add('sidebar-collapsed');
            }
        }

        // Toggle collapsed rail mode on desktop
        if (collapseBtn) {
            collapseBtn.addEventListener('click', function () {
                layout.classList.toggle('sidebar-collapsed');
                const collapsed = layout.classList.contains('sidebar-collapsed');
                localStorage.setItem('api_monitor_sidebar_collapsed', collapsed ? 'true' : 'false');
            });
        }

        // Mobile drawer toggle
        if (mobileToggleBtn) {
            mobileToggleBtn.addEventListener('click', function () {
                layout.classList.toggle('sidebar-open');
            });
        }

        // Close mobile drawer on backdrop tap
        if (backdrop) {
            backdrop.addEventListener('click', function () {
                layout.classList.remove('sidebar-open');
            });
        }

        // Close mobile drawer with Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && layout.classList.contains('sidebar-open')) {
                layout.classList.remove('sidebar-open');
            }
        });

        // Close mobile drawer when window resized to desktop
        window.addEventListener('resize', function () {
            if (window.innerWidth > 900 && layout.classList.contains('sidebar-open')) {
                layout.classList.remove('sidebar-open');
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSidebar);
    } else {
        initSidebar();
    }
})();
