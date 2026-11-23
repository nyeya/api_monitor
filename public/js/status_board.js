/**
 * Shared 30-second auto-refresh status board for both:
 * - Operational Dashboard (/index.php, mode = "dashboard")
 * - Public Status Page (/status.php, mode = "public")
 */
(function () {
    'use strict';

    const tbody = document.getElementById('services-tbody');
    if (!tbody) return;

    const mode = tbody.dataset.mode || 'dashboard';
    const apiUrl = tbody.dataset.api || '/api/dashboard_status.php';
    const isPublic = mode === 'public';
    const colSpan = isPublic ? 5 : 7;

    let countdown = 30;

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    function getStatusPresentation(status) {
        switch (status) {
            case 'ONLINE':       return { badge: 'badge-green',  dot: '🟢' };
            case 'SLOW':         return { badge: 'badge-yellow', dot: '🟡' };
            case 'RATE_LIMITED': return { badge: 'badge-yellow', dot: '🟠' };
            case 'OFFLINE':      return { badge: 'badge-red',    dot: '🔴' };
            default:             return { badge: 'badge-grey',   dot: '⚪' };
        }
    }

    function updateCounts(total, online, slow, offline) {
        document.getElementById('stat-total').textContent = total;
        document.getElementById('stat-online').textContent = online;
        document.getElementById('stat-slow').textContent = slow;
        document.getElementById('stat-offline').textContent = offline;
    }

    function renderRows(services) {
        tbody.innerHTML = '';

        if (!services || services.length === 0) {
            const emptyMsg = isPublic
                ? 'No services available.'
                : 'No services configured yet. Visit <a href="/add_service.php">Add Service</a> to add one.';
            tbody.innerHTML = `<tr><td colspan="${colSpan}" class="table-empty-cell">${emptyMsg}</td></tr>`;
            updateCounts(0, 0, 0, 0);
            return;
        }

        let online = 0, slow = 0, offline = 0;

        services.forEach(s => {
            const status = s.current_status;
            if (status === 'ONLINE') online++;
            else if (status === 'SLOW' || status === 'RATE_LIMITED') slow++;
            else if (status === 'OFFLINE') offline++;

            const { badge, dot } = getStatusPresentation(status);
            const latency = s.response_time_ms !== null ? `${Math.round(s.response_time_ms)} ms` : '—';
            const up24 = s.uptime_24h != null ? `${parseFloat(s.uptime_24h).toFixed(1)}%` : '—';
            const up7d = s.uptime_7d != null ? `${parseFloat(s.uptime_7d).toFixed(1)}%` : '—';
            const checked = s.checked_at ? new Date(s.checked_at).toLocaleString() : 'Never';

            const tr = document.createElement('tr');
            if (isPublic) {
                tr.innerHTML = `
                    <td>
                        <strong>${escapeHtml(s.name)}</strong>
                        ${s.description ? `<div class="service-desc-subtext">${escapeHtml(s.description)}</div>` : ''}
                    </td>
                    <td><span class="badge ${badge}">${dot} ${escapeHtml(status)}</span></td>
                    <td>${latency}</td>
                    <td>
                        <div class="stat-uptime-inline">
                            <div><span class="text-muted">24h:</span> <strong>${up24}</strong></div>
                            <div><span class="text-muted">7d:</span> <strong>${up7d}</strong></div>
                        </div>
                    </td>
                    <td>${checked}</td>
                `;
            } else {
                const httpCode = s.http_code !== null ? s.http_code : '—';
                tr.innerHTML = `
                    <td>
                        <strong>${escapeHtml(s.name)}</strong>
                        ${s.description ? `<div class="service-desc-subtext">${escapeHtml(s.description)}</div>` : ''}
                    </td>
                    <td><span class="badge ${badge}">${dot} ${escapeHtml(status)}</span></td>
                    <td><code>${httpCode}</code></td>
                    <td>${latency}</td>
                    <td>
                        <div class="stat-uptime-inline">
                            <div><span class="text-muted">24h:</span> <strong>${up24}</strong></div>
                            <div><span class="text-muted">7d:</span> <strong>${up7d}</strong></div>
                        </div>
                    </td>
                    <td>${checked}</td>
                    <td><a class="btn btn-sm btn-secondary" href="/history.php?id=${s.id}">View History</a></td>
                `;
            }
            tbody.appendChild(tr);
        });

        updateCounts(services.length, online, slow, offline);
    }

    async function refreshBoard() {
        try {
            const response = await fetch(apiUrl);
            if (!isPublic && response.status === 401) {
                window.location.href = '/admin.php';
                return;
            }
            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            renderRows(await response.json());
            countdown = 30;
            document.getElementById('refresh-status').textContent = `Auto-refreshing in ${countdown}s`;
        } catch (err) {
            console.error('Failed to load status:', err);
            document.getElementById('refresh-status').textContent = 'Connection error. Retrying...';
        }
    }

    const refreshBtn = document.getElementById('refresh-now-btn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', refreshBoard);
    }

    refreshBoard();
    setInterval(() => {
        countdown--;
        if (countdown <= 0) {
            refreshBoard();
        } else {
            document.getElementById('refresh-status').textContent = `Auto-refreshing in ${countdown}s`;
        }
    }, 1000);
})();
