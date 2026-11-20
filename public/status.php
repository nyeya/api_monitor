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
    <header class="top-nav">
        <div class="container nav-container">
            <div class="brand">
                <span class="brand-pulse"></span>
                <span class="brand-text">API Monitor</span>
            </div>
            <nav class="nav-links">
                <a href="/index.php">Live Dashboard</a>
                <a href="/status.php" class="active">Public Status</a>
                <a href="/admin.php">Manage Services</a>
            </nav>
        </div>
    </header>

    <div class="main-wrapper">
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

            data.services.forEach(svc => {
                const item = document.createElement('div');
                item.className = 'status-service-item';

                let dotClass = 'dot-grey';
                let statusLabel = svc.current_status;
                if (svc.current_status === 'ONLINE') dotClass = 'dot-green';
                else if (svc.current_status === 'SLOW') dotClass = 'dot-yellow';
                else if (svc.current_status === 'OFFLINE') dotClass = 'dot-red';

                const uptimeVal = svc.uptime_24h !== null ? `${svc.uptime_24h}%` : '—';
                const latencyVal = svc.response_time_ms !== null ? `${Math.round(svc.response_time_ms)}ms` : '—';

                item.innerHTML = `
                    <div class="service-left">
                        <span class="status-indicator-dot ${dotClass}"></span>
                        <div>
                            <div class="service-title">${escapeHtml(svc.name)}</div>
                            ${svc.description ? `<div class="service-desc">${escapeHtml(svc.description)}</div>` : ''}
                        </div>
                    </div>
                    <div class="service-right">
                        <div class="service-stat">
                            <span class="stat-value">${uptimeVal}</span>
                            <span class="stat-label">24h Uptime</span>
                        </div>
                        <div class="service-stat">
                            <span class="stat-value">${latencyVal}</span>
                            <span class="stat-label">Latency</span>
                        </div>
                        <span class="badge ${getStatusBadgeClass(svc.current_status)}">${statusLabel}</span>
                    </div>
                `;
                list.appendChild(item);
            });
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        function getStatusBadgeClass(status) {
            if (status === 'ONLINE') return 'badge-green';
            if (status === 'SLOW') return 'badge-yellow';
            if (status === 'OFFLINE') return 'badge-red';
            return 'badge-grey';
        }

        fetchPublicStatus();
        countdownInterval = setInterval(() => {
            countdown--;
            updateCountdownDisplay();
            if (countdown <= 0) {
                fetchPublicStatus();
            }
        }, 1000);
    </script>
</body>
</html>