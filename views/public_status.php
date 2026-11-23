<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Status - API Monitor</title>
    <link rel="stylesheet" href="/css/style.css">
</head>

<body>
    <main class="container" style="max-width: 1040px; margin: 2rem auto;">
        <div class="card-header-row" style="margin-bottom: 1.5rem;">
            <div>
                <h1 style="margin: 0; font-size: 1.6rem;">API Monitor</h1>
                <p class="text-muted" style="margin: 0.25rem 0 0;">Live service status</p>
            </div>
            <div class="card-header-actions">
                <a href="/admin.php" class="btn btn-sm btn-secondary">Sign In</a>
            </div>
        </div>

        <div class="summary-cards">
            <div class="summary-card">
                <span class="summary-icon">📡</span>
                <div>
                    <div id="stat-total" class="summary-num">0</div>
                    <div class="summary-label">Monitored Services</div>
                </div>
            </div>
            <div class="summary-card">
                <span class="summary-icon">🟢</span>
                <div>
                    <div id="stat-online" class="summary-num summary-num-online">0</div>
                    <div class="summary-label">Online</div>
                </div>
            </div>
            <div class="summary-card">
                <span class="summary-icon">🟡</span>
                <div>
                    <div id="stat-slow" class="summary-num summary-num-slow">0</div>
                    <div class="summary-label">Degraded / Slow</div>
                </div>
            </div>
            <div class="summary-card">
                <span class="summary-icon">🔴</span>
                <div>
                    <div id="stat-offline" class="summary-num summary-num-offline">0</div>
                    <div class="summary-label">Offline</div>
                </div>
            </div>
        </div>

        <section class="card">
            <div class="card-header-row">
                <div>
                    <h2>Current Service Health</h2>
                    <p class="text-muted">Automatically refreshes every 30 seconds</p>
                </div>
                <div class="card-header-actions">
                    <span id="refresh-status" class="refresh-status-text">Auto-refreshing in 30s</span>
                    <button id="refresh-now-btn" class="btn btn-sm btn-secondary">Refresh Now</button>
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
    </main>

    <script src="/js/status_board.js"></script>
</body>

</html>
