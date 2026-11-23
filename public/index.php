<?php
require_once __DIR__ . '/../config/db.php';
?>
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
        <?php $activeSidebar = 'dashboard'; require __DIR__ . '/../views/sidebar.php'; ?>

        <div class="app-main">
            <main class="container">
                <div class="summary-cards">
                    <div class="summary-card">
                        <span class="summary-icon">📡</span>
                        <div>
                            <div id="stat-total" class="summary-num">0</div>
                            <div class="summary-label">Total Services</div>
                        </div>
                    </div>
                    <div class="summary-card">
                        <span class="summary-icon">🟢</span>
                        <div>
                            <div id="stat-online" class="summary-num summary-num-online">0</div>
                            <div class="summary-label">Online (Healthy)</div>
                        </div>
                    </div>
                    <div class="summary-card">
                        <span class="summary-icon">🟡</span>
                        <div>
                            <div id="stat-slow" class="summary-num summary-num-slow">0</div>
                            <div class="summary-label">Slow Latency</div>
                        </div>
                    </div>
                    <div class="summary-card">
                        <span class="summary-icon">🔴</span>
                        <div>
                            <div id="stat-offline" class="summary-num summary-num-offline">0</div>
                            <div class="summary-label">Offline / Failed</div>
                        </div>
                    </div>
                </div>

                <section class="card">
                    <div class="card-header-row">
                        <div>
                            <h2>Monitored Services</h2>
                            <p class="text-muted">Real-time availability, response time and HTTP status</p>
                        </div>
                        <div class="card-header-actions">
                            <span id="refresh-status" class="refresh-status-text">Auto-refreshing in 30s</span>
                            <button class="btn btn-sm btn-secondary" onclick="refreshDashboard()">Refresh Now</button>
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
                                    <th>Date &amp; Time Checked</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="services-tbody">
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
    <script src="/js/dashboard.js"></script>
</body>

</html>
