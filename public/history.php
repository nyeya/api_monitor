<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$isAdminLoggedIn = !empty($_SESSION['admin_logged_in']);

require_once __DIR__ . '/../config/db.php';

$serviceId = validateId($_GET['id'] ?? null);
$service = null;
$stats = null;
$history = [];
$pageError = null;

if ($serviceId === false || $serviceId === null) {
    http_response_code(400);
    $pageError = 'A valid service ID is required.';
} else {
    try {
        $pdo = getDbConnection();

        $stmtService = $pdo->prepare('SELECT id, name, description, url, http_method, expected_http_code, warning_threshold_ms FROM services WHERE id = ?');
        $stmtService->execute([$serviceId]);
        $service = $stmtService->fetch();

        if (!$service) {
            http_response_code(404);
            $pageError = 'Service not found.';
        } else {
            $stmtStats = $pdo->prepare("
                SELECT
                    ROUND(AVG(CASE WHEN status != 'OFFLINE' THEN response_time_ms END), 2) AS avg_response_time_ms,
                    ROUND(
                        COUNT(CASE WHEN status != 'OFFLINE' THEN 1 END)::DECIMAL
                        / NULLIF(COUNT(*), 0) * 100,
                        2
                    ) AS uptime_percentage,
                    ROUND(
                        COUNT(CASE WHEN checked_at >= NOW() - INTERVAL '24 hours' AND status != 'OFFLINE' THEN 1 END)::DECIMAL
                        / NULLIF(COUNT(CASE WHEN checked_at >= NOW() - INTERVAL '24 hours' THEN 1 END), 0) * 100,
                        2
                    ) AS uptime_24h,
                    ROUND(
                        COUNT(CASE WHEN checked_at >= NOW() - INTERVAL '7 days' AND status != 'OFFLINE' THEN 1 END)::DECIMAL
                        / NULLIF(COUNT(CASE WHEN checked_at >= NOW() - INTERVAL '7 days' THEN 1 END), 0) * 100,
                        2
                    ) AS uptime_7d,
                    MAX(CASE WHEN status != 'OFFLINE' THEN checked_at END) AS last_successful_check,
                    MAX(CASE WHEN status = 'OFFLINE' THEN checked_at END) AS last_failed_check
                FROM monitoring_history
                WHERE service_id = ?
            ");
            $stmtStats->execute([$serviceId]);
            $stats = $stmtStats->fetch();

            $stmtHistory = $pdo->prepare("
                SELECT id, checked_at, http_code, response_time_ms, status, error_message
                FROM (
                    SELECT id, checked_at, http_code, response_time_ms, status, error_message
                    FROM monitoring_history
                    WHERE service_id = ?
                    ORDER BY checked_at DESC, id DESC
                    LIMIT 50
                ) recent_checks
                ORDER BY checked_at ASC, id ASC
            ");
            $stmtHistory->execute([$serviceId]);
            $history = $stmtHistory->fetchAll();
        }
    } catch (PDOException $exception) {
        error_log('Could not load service history: ' . $exception->getMessage());
        http_response_code(500);
        $pageError = 'Could not load service history.';
    }
}

$responsePoints = array_values(array_filter($history, static function ($point) {
    return $point['response_time_ms'] !== null;
}));
$chartValues = array_map(static function ($point) {
    return (float)$point['response_time_ms'];
}, $responsePoints);
$chartLabels = array_map(static function ($point) {
    return date('H:i:s', strtotime($point['checked_at']));
}, $responsePoints);

$currentStatus = $history ? $history[count($history) - 1]['status'] : 'UNKNOWN';
$statusDot = getStatusDot($currentStatus);
$statusBadgeClass = getStatusBadgeClass($currentStatus);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $service ? escapeHtml($service['name']) . ' - ' : ''; ?>History - API Monitor</title>
    <link rel="stylesheet" href="/css/sstyle.css">
</head>
<body>
    <div class="app-layout" id="app-layout">
        <?php $activeSidebar = 'dashboard'; require __DIR__ . '/../views/sidebar.php'; ?>

        <!-- Main Content Area -->
        <div class="app-main">
            <main class="container">
        <div class="back-link-wrapper">
            <a href="/index.php" class="btn btn-sm btn-secondary">&larr; Back to Dashboard</a>
        </div>

        <?php if ($pageError !== null): ?>
            <section class="card">
                <h2>Service history</h2>
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
                        <strong class="stat-val"><?php echo $stats['uptime_percentage'] === null ? 'No data' : escapeHtml($stats['uptime_percentage']) . '%'; ?></strong>
                    </div>
                    <div class="stat-box">
                        <span class="stat-label">Availability (Last 24h)</span>
                        <strong class="stat-val"><?php echo $stats['uptime_24h'] === null ? 'No data' : escapeHtml($stats['uptime_24h']) . '%'; ?></strong>
                    </div>
                    <div class="stat-box">
                        <span class="stat-label">Availability (Last 7d)</span>
                        <strong class="stat-val"><?php echo $stats['uptime_7d'] === null ? 'No data' : escapeHtml($stats['uptime_7d']) . '%'; ?></strong>
                    </div>
                    <div class="stat-box">
                        <span class="stat-label">Avg Response Time</span>
                        <strong class="stat-val"><?php echo $stats['avg_response_time_ms'] === null ? '—' : escapeHtml($stats['avg_response_time_ms']) . ' ms'; ?></strong>
                    </div>
                    <div class="stat-box">
                        <span class="stat-label">Last Successful Check</span>
                        <strong class="stat-val small-stat"><?php echo $stats['last_successful_check'] ? escapeHtml(date('Y-m-d H:i:s', strtotime($stats['last_successful_check']))) : '—'; ?></strong>
                    </div>
                    <div class="stat-box">
                        <span class="stat-label">Last Detected Failure</span>
                        <strong class="stat-val small-stat<?php echo $stats['last_failed_check'] ? ' stat-val-danger' : ''; ?>">
                            <?php echo $stats['last_failed_check'] ? escapeHtml(date('Y-m-d H:i:s', strtotime($stats['last_failed_check']))) : 'None recorded'; ?>
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
                    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
                    <script>
                        const chartEl = document.getElementById('history-chart');
                        new Chart(chartEl, {
                            type: 'line',
                            data: {
                                labels: JSON.parse(chartEl.dataset.labels),
                                datasets: [{
                                    label: 'Response Time (ms)',
                                    data: JSON.parse(chartEl.dataset.values),
                                    borderColor: '#2563eb',
                                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                                    borderWidth: 2,
                                    fill: true,
                                    tension: 0.25,
                                    pointRadius: 3,
                                    pointHoverRadius: 6
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        title: { display: true, text: 'Milliseconds' }
                                    },
                                    x: {
                                        title: { display: true, text: 'Time Checked' }
                                    }
                                }
                            }
                        });
                    </script>
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
                                    $pBadge = match ($pStatus) {
                                        'ONLINE'  => 'badge-green',
                                        'SLOW'    => 'badge-yellow',
                                        'OFFLINE' => 'badge-red',
                                        default   => 'badge-grey',
                                    };
                                    $pDot = match ($pStatus) {
                                        'ONLINE'  => '🟢',
                                        'SLOW'    => '🟡',
                                        'OFFLINE' => '🔴',
                                        default   => '⚪',
                                    };
                                    ?>
                                    <tr>
                                        <td><?php echo escapeHtml(date('Y-m-d H:i:s', strtotime($point['checked_at']))); ?></td>
                                        <td><span class="badge <?php echo $pBadge; ?>"><?php echo $pDot; ?> <?php echo escapeHtml($pStatus); ?></span></td>
                                        <td><code><?php echo $point['http_code'] === null ? '—' : escapeHtml($point['http_code']); ?></code></td>
                                        <td><?php echo $point['response_time_ms'] === null ? '—' : escapeHtml(round($point['response_time_ms'])) . ' ms'; ?></td>
                                        <td><?php echo escapeHtml($point['error_message'] ?: 'Normal operation'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>
        <?php endif; ?>
            </main>
        </div>
    </div>
    <script src="/js/sidebar.js"></script>
</body>
</html>
