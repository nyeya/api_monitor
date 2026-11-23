<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $service ? escapeHtml($service['name']) . ' - ' : ''; ?>History - API Monitor</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <div class="app-layout" id="app-layout">
        <?php $activeSidebar = 'dashboard'; require __DIR__ . '/partials/sidebar.php'; ?>

        <div class="app-main">
            <main class="container">
                <div class="back-link-wrapper">
                    <a href="/index.php" class="btn btn-sm btn-secondary">&larr; Back to Dashboard</a>
                </div>

                <?php if ($pageError !== null): ?>
                    <section class="card">
                        <h2>Service History</h2>
                        <p class="notice" role="alert"><?php echo escapeHtml($pageError); ?></p>
                    </section>
                <?php else: ?>
                    <section class="card">
                        <div class="history-header-row">
                            <div>
                                <h2><?php echo escapeHtml($service['name']); ?></h2>
                                <p class="text-muted history-subtitle"><?php echo escapeHtml($service['description'] ?? ''); ?></p>
                                <p class="history-meta-text">
                                    Endpoint: <code><?php echo escapeHtml($service['url']); ?></code> &bull;
                                    Method: <strong><?php echo escapeHtml($service['http_method']); ?></strong> &bull;
                                    Expected HTTP: <strong><?php echo escapeHtml($service['expected_http_code']); ?></strong>
                                </p>
                            </div>
                            <div>
                                <span class="badge <?php echo $statusBadgeClass; ?> history-badge">
                                    <?php echo $statusDot; ?> <?php echo escapeHtml($currentStatus); ?>
                                </span>
                            </div>
                        </div>

                        <div class="stats-grid">
                            <div class="stat-box">
                                <span class="stat-label">Availability (All Time)</span>
                                <strong class="stat-val"><?php echo ($stats['uptime_percentage'] ?? null) === null ? 'No data' : escapeHtml($stats['uptime_percentage']) . '%'; ?></strong>
                            </div>
                            <div class="stat-box">
                                <span class="stat-label">Availability (Last 24h)</span>
                                <strong class="stat-val"><?php echo ($stats['uptime_24h'] ?? null) === null ? 'No data' : escapeHtml($stats['uptime_24h']) . '%'; ?></strong>
                            </div>
                            <div class="stat-box">
                                <span class="stat-label">Availability (Last 7d)</span>
                                <strong class="stat-val"><?php echo ($stats['uptime_7d'] ?? null) === null ? 'No data' : escapeHtml($stats['uptime_7d']) . '%'; ?></strong>
                            </div>
                            <div class="stat-box">
                                <span class="stat-label">Avg Response Time</span>
                                <strong class="stat-val"><?php echo ($stats['avg_response_time_ms'] ?? null) === null ? '—' : escapeHtml($stats['avg_response_time_ms']) . ' ms'; ?></strong>
                            </div>
                            <div class="stat-box">
                                <span class="stat-label">Last Successful Check</span>
                                <strong class="stat-val small-stat"><?php echo !empty($stats['last_successful_check']) ? escapeHtml(date('Y-m-d H:i:s', strtotime($stats['last_successful_check']))) : '—'; ?></strong>
                            </div>
                            <div class="stat-box">
                                <span class="stat-label">Last Detected Failure</span>
                                <strong class="stat-val small-stat<?php echo !empty($stats['last_failed_check']) ? ' stat-val-danger' : ''; ?>">
                                    <?php echo !empty($stats['last_failed_check']) ? escapeHtml(date('Y-m-d H:i:s', strtotime($stats['last_failed_check']))) : 'None recorded'; ?>
                                </strong>
                            </div>
                        </div>
                    </section>

                    <section class="card">
                        <h2>Response Time History (Recent Checks)</h2>
                        <?php if ($chartValues): ?>
                            <div class="chart-container">
                                <canvas
                                    id="history-chart"
                                    data-labels="<?php echo escapeHtml(json_encode($chartLabels, JSON_THROW_ON_ERROR)); ?>"
                                    data-values="<?php echo escapeHtml(json_encode($chartValues, JSON_THROW_ON_ERROR)); ?>"
                                    role="img"
                                    aria-label="Response time chart"
                                >Response time chart</canvas>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No response-time data recorded yet.</p>
                        <?php endif; ?>
                    </section>

                    <section class="card">
                        <h2>Recent Checks Log</h2>
                        <div class="table-wrap">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date &amp; Time</th>
                                        <th>Status</th>
                                        <th>HTTP Code</th>
                                        <th>Response Time</th>
                                        <th>Error / Response Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!$history): ?>
                                        <tr><td colspan="5" class="table-empty-cell">No checks recorded yet.</td></tr>
                                    <?php else: ?>
                                        <?php foreach (array_reverse($history) as $point): ?>
                                            <?php
                                            $pStatus = $point['status'];
                                            $pBadge = getStatusBadgeClass($pStatus);
                                            $pDot = getStatusDot($pStatus);
                                            ?>
                                            <tr>
                                                <td><?php echo escapeHtml(date('Y-m-d H:i:s', strtotime($point['checked_at']))); ?></td>
                                                <td><span class="badge <?php echo $pBadge; ?>"><?php echo $pDot; ?> <?php echo escapeHtml($pStatus); ?></span></td>
                                                <td><code><?php echo $point['http_code'] === null ? '—' : escapeHtml($point['http_code']); ?></code></td>
                                                <td><?php echo $point['response_time_ms'] === null ? '—' : escapeHtml(round($point['response_time_ms'])) . ' ms'; ?></td>
                                                <td><?php echo escapeHtml($point['error_message'] ?: 'Normal operation'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="/js/sidebar.js"></script>
    <?php if ($chartValues): ?>
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
        <script src="/js/history_chart.js"></script>
    <?php endif; ?>
</body>
</html>
