<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Monitor - Operational Dashboard</title>
    <link rel="stylesheet" href="/css/style.css">
</head>

<body>
    <div class="app-layout" id="app-layout">
        <?php $activeSidebar = 'dashboard'; require __DIR__ . '/partials/sidebar.php'; ?>

        <div class="app-main">
            <main class="container">
                <div class="page-header">
                    <div>
                        <h1 class="page-title">Operational Dashboard</h1>
                        <p class="text-muted page-subtitle">Live availability, response latency, and endpoint health</p>
                    </div>
                    <div class="page-header-actions">
                        <a href="/add_service.php" class="btn btn-primary btn-sm">+ Add Service</a>
                    </div>
                </div>

                <div id="system-health-banner" class="status-banner status-banner-neutral" role="status" aria-live="polite">
                    <span class="status-dot dot-grey" id="health-banner-dot" aria-hidden="true"></span>
                    <span id="health-banner-text">Checking service telemetry...</span>
                    <span id="last-updated-text" class="status-banner-meta"></span>
                </div>

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
                            <div class="summary-label">Total Services</div>
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
                            <div class="summary-label">Online (Healthy)</div>
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
                            <div class="summary-label">Slow / Limited</div>
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
                            <div class="summary-label">Offline / Failed</div>
                        </div>
                    </button>
                </div>

                <section class="card">
                    <div class="card-header-row">
                        <div>
                            <h2>Monitored Services</h2>
                            <p class="text-muted">Click any status card or filter below to narrow results</p>
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
                            <input type="search" id="service-search" class="search-input" placeholder="Search services by name or description..." aria-label="Search monitored services">
                        </div>
                        <div class="filter-pills" id="status-filter-pills" role="group" aria-label="Filter by status">
                            <button type="button" class="filter-pill active" data-filter-status="ALL">All</button>
                            <button type="button" class="filter-pill" data-filter-status="ONLINE">Online</button>
                            <button type="button" class="filter-pill" data-filter-status="SLOW">Slow</button>
                            <button type="button" class="filter-pill" data-filter-status="OFFLINE">Offline</button>
                            <button type="button" class="filter-pill" data-filter-status="OTHER">Disabled / Unknown</button>
                        </div>
                    </div>

                    <div class="table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Service Name</th>
                                    <th>Current Status</th>
                                    <th>HTTP Code</th>
                                    <th>Response Time</th>
                                    <th>Availability</th>
                                    <th>Last Checked</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="services-tbody" data-mode="dashboard" data-api="/api/dashboard_status.php">
                                <tr>
                                    <td colspan="7" class="table-empty-cell">Loading monitored endpoints...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <script src="/js/sidebar.js"></script>
    <script src="/js/status_board.js"></script>
</body>

</html>
