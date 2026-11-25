<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Status - API Monitor</title>
    <link rel="stylesheet" href="/css/style.css">
</head>

<body>
    <main class="container public-container">
        <header class="public-header">
            <div class="public-brand">
                <span class="brand-pulse"></span>
                <div>
                    <h1 class="page-title">API Monitor</h1>
                    <p class="text-muted page-subtitle">Public live service health &amp; uptime status</p>
                </div>
            </div>
            <div class="card-header-actions">
                <a href="/admin.php" class="btn btn-sm btn-secondary">Admin Sign In</a>
            </div>
        </header>

        <div class="summary-cards">
            <button type="button" class="summary-card summary-card-interactive active" data-filter-status="ALL">
                <span class="summary-icon summary-icon-total" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                </span>
                <div class="summary-content">
                    <div id="stat-total" class="summary-num">0</div>
                    <div class="summary-label">Monitored Services</div>
                </div>
            </button>
            <button type="button" class="summary-card summary-card-interactive" data-filter-status="ONLINE">
                <span class="summary-icon summary-icon-online" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                </span>
                <div class="summary-content">
                    <div id="stat-online" class="summary-num summary-num-online">0</div>
                    <div class="summary-label">Online</div>
                </div>
            </button>
            <button type="button" class="summary-card summary-card-interactive" data-filter-status="SLOW">
                <span class="summary-icon summary-icon-slow" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </span>
                <div class="summary-content">
                    <div id="stat-slow" class="summary-num summary-num-slow">0</div>
                    <div class="summary-label">Degraded / Slow</div>
                </div>
            </button>
            <button type="button" class="summary-card summary-card-interactive" data-filter-status="OFFLINE">
                <span class="summary-icon summary-icon-offline" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                </span>
                <div class="summary-content">
                    <div id="stat-offline" class="summary-num summary-num-offline">0</div>
                    <div class="summary-label">Offline</div>
                </div>
            </button>
        </div>

        <section class="card">
            <div class="card-header-row">
                <div>
                    <h2>Current Service Health</h2>
                    <p class="text-muted">Automatically refreshes every 30 seconds</p>
                </div>
                <div class="card-header-actions">
                    <span id="refresh-status" class="refresh-status-text">Auto-refreshing in 30s</span>
                    <button id="refresh-now-btn" class="btn btn-sm btn-secondary" type="button">Refresh Now</button>
                </div>
            </div>

            <div class="table-toolbar">
                <div class="search-box">
                    <svg class="search-icon" viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="search" id="service-search" class="search-input" placeholder="Search services..." aria-label="Search services">
                </div>
                <div class="filter-pills" id="status-filter-pills" role="group" aria-label="Filter by status">
                    <button type="button" class="filter-pill active" data-filter-status="ALL">All</button>
                    <button type="button" class="filter-pill" data-filter-status="ONLINE">Online</button>
                    <button type="button" class="filter-pill" data-filter-status="SLOW">Degraded</button>
                    <button type="button" class="filter-pill" data-filter-status="OFFLINE">Offline</button>
                </div>
            </div>

            <div class="table-wrap">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Service</th>
                            <th>Status</th>
                            <th>Response Time</th>
                            <th>Availability</th>
                            <th>Last Checked</th>
                        </tr>
                    </thead>
                    <tbody id="services-tbody" data-mode="public" data-api="/api/public_status.php">
                        <tr>
                            <td colspan="5" class="table-empty-cell">Loading service status...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <script src="/js/status_board.js"></script>
</body>

</html>