/**
 * API Monitor - Operational Dashboard Client Script
 * Fetches real-time status data, renders status indicators, and handles 30s auto-refresh.
 */

let refreshCountdown = 30;
let countdownTimerId = null;

async function refreshDashboard() {
    try {
        const response = await fetch('/api/status.php');
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        const services = await response.json();
        renderDashboard(services);

        // Reset countdown
        refreshCountdown = 30;
        updateCountdownLabel();
    } catch (err) {
        console.error('Failed to load dashboard status:', err);
        document.getElementById('refresh-status').textContent = 'Connection error. Retrying...';
    }
}


function renderDashboard(services) {
    const tbody = document.getElementById('services-tbody');
    tbody.innerHTML = '';

    if (!services || services.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="table-empty-cell">No services configured yet. Visit <a href="admin.php">Manage Services</a> to add one.</td></tr>';
        updateSummaryCounts(0, 0, 0, 0);
        return;
    }

    let onlineCount = 0;
    let slowCount = 0;
    let offlineCount = 0;

    services.forEach(s => {
        const status = s.current_status;

        if (status === 'ONLINE') onlineCount++;
        else if (status === 'SLOW') slowCount++;
        else if (status === 'OFFLINE') offlineCount++;

        const tr = document.createElement('tr');

        // Visual status indicator styling
        let badgeClass;

        if (status === 'ONLINE') {
            badgeClass = 'badge-green';
        } else if (status === 'SLOW') {
            badgeClass = 'badge-yellow';
        } else if (status === 'OFFLINE') {
            badgeClass = 'badge-red';
        } else {
            badgeClass = 'badge-grey';
        }

        const httpDisplay = s.http_code !== null ? s.http_code : '—';
        const latencyDisplay = s.response_time_ms !== null ? `${Math.round(s.response_time_ms)} ms` : '—';
        const dateDisplay = s.checked_at ? new Date(s.checked_at).toLocaleString() : 'Never';

        tr.innerHTML = `
            <td>
                <strong>${escapeHtml(s.name)}</strong>
                ${s.description ? `<div class="service-desc-subtext">${escapeHtml(s.description)}</div>` : ''}
            </td>
            <td>
                <span class="badge ${badgeClass}">${status}</span>
            </td>
            <td><code>${httpDisplay}</code></td>
            <td>${latencyDisplay}</td>
            <td>${dateDisplay}</td>
            <td>
                <a class="btn btn-sm btn-secondary" href="history.php?id=${s.id}">View History</a>
            </td>
        `;
        tbody.appendChild(tr);
    });

    updateSummaryCounts(services.length, onlineCount, slowCount, offlineCount);
}

function updateSummaryCounts(total, online, slow, offline) {
    document.getElementById('stat-total').textContent = total;
    document.getElementById('stat-online').textContent = online;
    document.getElementById('stat-slow').textContent = slow;
    document.getElementById('stat-offline').textContent = offline;
}

function updateCountdownLabel() {
    document.getElementById('refresh-status').textContent = `Auto-refreshing in ${refreshCountdown}s`;
}


function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str || '';
    return div.innerHTML;
}

// Initial fetch
refreshDashboard();

// 1-second countdown ticker
if (countdownTimerId) clearInterval(countdownTimerId);
countdownTimerId = setInterval(() => {
    refreshCountdown--;
    if (refreshCountdown <= 0) {
        refreshDashboard();
    } else {
        updateCountdownLabel();
    }
}, 1000);
