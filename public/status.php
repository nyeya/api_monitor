<?php
require_once __DIR__ . '/../config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Status - API Monitor</title>
    <link rel="stylesheet" href="/css/sstyle.css">
</head>
<body>
    <div class="app-layout" id="app-layout">
        <?php $activeSidebar = 'status'; require __DIR__ . '/../views/sidebar.php'; ?>

        <!-- Main Content Area -->
        <div class="app-main">
            <main class="container">
                <section id="hero-banner" class="status-hero hero-operational">
                    <div>
                        <h2 id="hero-title">Checking System Status...</h2>
                        <p id="hero-subtitle">Fetching latest telemetry...</p>
                    </div>
                    <div class="card-header-actions">
                        <span id="hero-timer" class="refresh-status-text">Refreshing in 30s</span>
                    </div>
                </section>

                <section class="card">
                    <div class="card-header-row">
                        <div>
                            <h2>Service Health</h2>
                            <p class="text-muted">Public operational status and availability</p>
                        </div>
                        <button class="btn btn-sm btn-secondary" onclick="fetchPublicStatus()">Refresh</button>
                    </div>

                    <div id="services-list">
                        <p class="table-empty-cell">Loading service statuses...</p>
                    </div>
                </section>

                <footer class="status-footer">
                    API Monitor &bull; Public Status Page &bull; Auto-refreshes every 30 seconds
                </footer>
            </main>
        </div>
    </div>

    <script src="/js/sidebar.js"></script>
    <script>
        let countdown = 30;
        let countdownInterval;

        function updateCountdownDisplay() {
            const timer = document.getElementById('hero-timer');
            if (timer) {
                timer.textContent = `Auto-refresh in ${countdown}s`;
            }
        }

        async function fetchPublicStatus() {
            try {
                const response = await fetch('/api/public_status.php');
                const data = await response.json();
                renderStatus(data);
                countdown = 30;
                updateCountdownDisplay();
            } catch (err) {
                console.error('Failed to load public status:', err);
            }
        }

        function renderStatus(data) {
            const hero = document.getElementById('hero-banner');
            const title = document.getElementById('hero-title');
            const sub = document.getElementById('hero-subtitle');

            hero.className = 'status-hero';
            if (data.system_status === 'OPERATIONAL') {
                hero.classList.add('hero-operational');
                title.textContent = 'All Systems Operational';
                sub.textContent = 'All monitored endpoints are healthy and within latency thresholds.';
            } else if (data.system_status === 'DEGRADED') {
                hero.classList.add('hero-degraded');
                title.textContent = 'Degraded Performance Detected';
                sub.textContent = 'One or more services are experiencing elevated response times.';
            } else {
                hero.classList.add('hero-outage');
                title.textContent = 'Active Service Outage Detected';
                sub.textContent = 'One or more critical services are currently unavailable.';
            }

            const list = document.getElementById('services-list');
            list.innerHTML = '';

            if (!data.services || data.services.length === 0) {
                list.innerHTML = '<p class="table-empty-cell">No public services configured.</p>';
                return;
            }

            data.services.forEach(s => {
                const row = document.createElement('div');
                row.className = 'service-row';

                let dotClass = 'dot-grey';
                let label = 'Unknown';
                let badgeClass = 'badge-grey';

                if (s.current_status === 'ONLINE') {
                    dotClass = 'dot-green';
                    label = 'Operational';
                    badgeClass = 'badge-green';
                } else if (s.current_status === 'SLOW') {
                    dotClass = 'dot-yellow';
                    label = 'Degraded';
                    badgeClass = 'badge-yellow';
                } else if (s.current_status === 'OFFLINE') {
                    dotClass = 'dot-red';
                    label = 'Outage';
                    badgeClass = 'badge-red';
                } else if (s.current_status === 'DISABLED') {
                    dotClass = 'dot-grey';
                    label = 'Maintenance';
                    badgeClass = 'badge-grey';
                }

                const checkedStr = s.checked_at ? new Date(s.checked_at).toLocaleString() : 'Never';
                const latency = s.response_time_ms !== null ? `${Math.round(s.response_time_ms)} ms` : '—';
                const uptime = s.uptime_24h !== null ? `${s.uptime_24h}%` : 'No data';

                row.innerHTML = `
                    <div>
                        <strong class="service-name-text">${escapeHtml(s.name)}</strong>
                        <div class="service-meta">
                            ${escapeHtml(s.description || 'Public service endpoint')} &bull;
                            Last checked: ${checkedStr} &bull; Latency: ${latency}
                        </div>
                    </div>
                    <div class="service-status-align">
                        <span class="badge ${badgeClass}"><span class="pulse-dot ${dotClass}"></span>${label}</span>
                        <div class="service-meta service-meta-top">24h Uptime: <strong>${uptime}</strong></div>
                    </div>
                `;
                list.appendChild(row);
            });
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str || '';
            return div.innerHTML;
        }

        fetchPublicStatus();
        countdownInterval = setInterval(() => {
            countdown--;
            if (countdown <= 0) {
                fetchPublicStatus();
            } else {
                updateCountdownDisplay();
            }
        }, 1000);
    </script>
</body>
</html>
