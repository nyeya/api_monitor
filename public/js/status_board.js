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

    const refreshStatusEl = document.getElementById('refresh-status');
    const refreshBtn = document.getElementById('refresh-now-btn');
    const searchInput = document.getElementById('service-search');
    const healthBanner = document.getElementById('system-health-banner');
    const healthBannerDot = document.getElementById('health-banner-dot');
    const healthBannerText = document.getElementById('health-banner-text');
    const lastUpdatedText = document.getElementById('last-updated-text');

    let countdown = 30;
    let isRefreshing = false;
    let allServices = [];
    let activeStatusFilter = 'ALL';
    let searchQuery = '';

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str == null ? '' : String(str);
        return div.innerHTML;
    }

    function getStatusPresentation(status) {
        switch (status) {
            case 'ONLINE':
                return { badge: 'badge-green', dot: 'dot-green', label: 'ONLINE' };
            case 'SLOW':
                return { badge: 'badge-yellow', dot: 'dot-yellow', label: 'SLOW' };
            case 'RATE_LIMITED':
                return { badge: 'badge-orange', dot: 'dot-orange', label: 'Rate Limited' };
            case 'OFFLINE':
                return { badge: 'badge-red', dot: 'dot-red', label: 'OFFLINE' };
            case 'DISABLED':
                return { badge: 'badge-grey', dot: 'dot-grey', label: 'DISABLED' };
            default:
                return { badge: 'badge-grey', dot: 'dot-grey', label: status || 'UNKNOWN' };
        }
    }

    function getUptimeClass(val) {
        if (val == null) return '';
        const num = parseFloat(val);
        if (Number.isNaN(num)) return '';
        if (num >= 99.0) return 'uptime-good';
        if (num >= 95.0) return 'uptime-warn';
        return 'uptime-bad';
    }

    function formatRelativeTime(isoString) {
        if (!isoString) return { relative: 'Never', exact: '' };
        const date = new Date(isoString);
        if (Number.isNaN(date.getTime())) return { relative: 'Unknown', exact: '' };

        const diffSec = Math.max(0, Math.floor((Date.now() - date.getTime()) / 1000));
        let relative;
        if (diffSec < 10) relative = 'Just now';
        else if (diffSec < 60) relative = `${diffSec}s ago`;
        else if (diffSec < 3600) relative = `${Math.floor(diffSec / 60)}m ago`;
        else if (diffSec < 86400) relative = `${Math.floor(diffSec / 3600)}h ago`;
        else relative = `${Math.floor(diffSec / 86400)}d ago`;

        return { relative, exact: date.toLocaleString() };
    }

    function updateCounts(total, online, slow, offline) {
        const elTotal = document.getElementById('stat-total');
        const elOnline = document.getElementById('stat-online');
        const elSlow = document.getElementById('stat-slow');
        const elOffline = document.getElementById('stat-offline');
        if (elTotal) elTotal.textContent = total;
        if (elOnline) elOnline.textContent = online;
        if (elSlow) elSlow.textContent = slow;
        if (elOffline) elOffline.textContent = offline;
    }

    function updateHealthBanner(total, online, slow, offline) {
        if (!healthBanner || !healthBannerDot || !healthBannerText) return;

        healthBanner.className = 'status-banner';
        healthBannerDot.className = 'status-dot';

        if (total === 0) {
            healthBanner.classList.add('status-banner-neutral');
            healthBannerDot.classList.add('dot-grey');
            healthBannerText.textContent = 'No services are currently being monitored.';
        } else if (offline > 0) {
            healthBanner.classList.add('status-banner-down');
            healthBannerDot.classList.add('dot-red');
            const noun = offline === 1 ? 'service is' : 'services are';
            healthBannerText.textContent = `${offline} ${noun} offline — disruption detected`;
        } else if (slow > 0) {
            healthBanner.classList.add('status-banner-warn');
            healthBannerDot.classList.add('dot-yellow');
            const noun = slow === 1 ? 'service' : 'services';
            healthBannerText.textContent = `Degraded performance on ${slow} ${noun}`;
        } else if (online > 0) {
            healthBanner.classList.add('status-banner-ok');
            healthBannerDot.classList.add('dot-green');
            healthBannerText.textContent = 'All monitored systems operational';
        } else {
            healthBanner.classList.add('status-banner-neutral');
            healthBannerDot.classList.add('dot-grey');
            healthBannerText.textContent = 'Waiting for initial monitoring checks';
        }

        if (lastUpdatedText) {
            lastUpdatedText.textContent = `Updated ${new Date().toLocaleTimeString()}`;
        }
    }

    function matchesFilter(service) {
        const status = service.current_status;

        if (activeStatusFilter === 'ONLINE' && status !== 'ONLINE') return false;
        if (activeStatusFilter === 'SLOW' && status !== 'SLOW' && status !== 'RATE_LIMITED') return false;
        if (activeStatusFilter === 'OFFLINE' && status !== 'OFFLINE') return false;
        if (activeStatusFilter === 'OTHER' && ['ONLINE', 'SLOW', 'RATE_LIMITED', 'OFFLINE'].includes(status)) return false;

        if (searchQuery !== '') {
            const haystack = [
                service.name || '',
                service.description || '',
                !isPublic ? (service.error_message || '') : ''
            ].join(' ').toLowerCase();
            if (!haystack.includes(searchQuery)) return false;
        }

        return true;
    }

    function setActiveFilter(filterValue) {
        activeStatusFilter = filterValue || 'ALL';

        document.querySelectorAll('[data-filter-status]').forEach(el => {
            el.classList.toggle('active', el.dataset.filterStatus === activeStatusFilter);
        });

        renderTable();
    }

    function renderTable() {
        tbody.innerHTML = '';

        if (!allServices || allServices.length === 0) {
            const emptyMsg = isPublic
                ? 'No services available.'
                : 'No services configured yet';
            tbody.innerHTML = `<tr><td colspan="${colSpan}" class="table-empty-cell">${emptyMsg}</td></tr>`;
            return;
        }

        const filtered = allServices.filter(matchesFilter);

        if (filtered.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="${colSpan}" class="table-empty-cell">
                        No services match your current filter.
                        <button type="button" class="btn btn-sm btn-secondary clear-filter-btn" id="clear-filters-btn">Clear filters</button>
                    </td>
                </tr>
            `;
            const clearBtn = document.getElementById('clear-filters-btn');
            if (clearBtn) {
                clearBtn.addEventListener('click', () => {
                    searchQuery = '';
                    if (searchInput) searchInput.value = '';
                    setActiveFilter('ALL');
                });
            }
            return;
        }

        filtered.forEach(s => {
            const status = s.current_status;
            const { badge, dot, label } = getStatusPresentation(status);
            const hasLatency = s.response_time_ms !== null && s.response_time_ms !== undefined;
            const latencyNum = hasLatency ? Math.round(Number(s.response_time_ms)) : null;
            const isSlowLatency = !isPublic && latencyNum !== null && s.warning_threshold_ms && latencyNum > Number(s.warning_threshold_ms);
            const latencyHtml = latencyNum !== null
                ? `<span class="${isSlowLatency ? 'latency-slow' : ''}">${latencyNum} ms</span>`
                : '<span class="text-muted">—</span>';

            const up24Val = s.uptime_24h != null ? `${parseFloat(s.uptime_24h).toFixed(1)}%` : '—';
            const up7dVal = s.uptime_7d != null ? `${parseFloat(s.uptime_7d).toFixed(1)}%` : '—';
            const up24Class = getUptimeClass(s.uptime_24h);
            const up7dClass = getUptimeClass(s.uptime_7d);

            const checked = formatRelativeTime(s.checked_at);
            const checkedHtml = checked.exact
                ? `<span title="${escapeHtml(checked.exact)}">${escapeHtml(checked.relative)}</span><div class="service-desc-subtext">${escapeHtml(checked.exact)}</div>`
                : '<span class="text-muted">Never</span>';

            const tr = document.createElement('tr');
            if (isPublic) {
                tr.innerHTML = `
                    <td>
                        <strong>${escapeHtml(s.name)}</strong>
                        ${s.description ? `<div class="service-desc-subtext">${escapeHtml(s.description)}</div>` : ''}
                    </td>
                    <td>
                        <span class="badge ${badge}">
                            <span class="status-dot ${dot}" aria-hidden="true"></span>
                            ${escapeHtml(label)}
                        </span>
                    </td>
                    <td class="nowrap">${latencyHtml}</td>
                    <td class="nowrap">
                        <div class="stat-uptime-inline">
                            <div><span class="text-muted">24h:</span> <strong class="${up24Class}">${up24Val}</strong></div>
                            <div><span class="text-muted">7d:</span> <strong class="${up7dClass}">${up7dVal}</strong></div>
                        </div>
                    </td>
                    <td class="nowrap">${checkedHtml}</td>
                `;
            } else {
                const httpCode = s.http_code !== null && s.http_code !== undefined ? s.http_code : '—';
                const errorHtml = (status !== 'ONLINE' && status !== 'DISABLED' && s.error_message)
                    ? `<div class="error-subtext" title="${escapeHtml(s.error_message)}">${escapeHtml(s.error_message)}</div>`
                    : '';

                tr.innerHTML = `
                    <td>
                        <strong>${escapeHtml(s.name)}</strong>
                        ${s.description ? `<div class="service-desc-subtext">${escapeHtml(s.description)}</div>` : ''}
                    </td>
                    <td>
                        <span class="badge ${badge}">
                            <span class="status-dot ${dot}" aria-hidden="true"></span>
                            ${escapeHtml(label)}
                        </span>
                        ${errorHtml}
                    </td>
                    <td><code>${escapeHtml(httpCode)}</code></td>
                    <td class="nowrap">${latencyHtml}</td>
                    <td class="nowrap">
                        <div class="stat-uptime-inline">
                            <div><span class="text-muted">24h:</span> <strong class="${up24Class}">${up24Val}</strong></div>
                            <div><span class="text-muted">7d:</span> <strong class="${up7dClass}">${up7dVal}</strong></div>
                        </div>
                    </td>
                    <td class="nowrap">${checkedHtml}</td>
                    <td class="nowrap">
                        <a class="btn btn-sm btn-secondary" href="/history.php?id=${encodeURIComponent(s.id)}">View History</a>
                    </td>
                `;
            }
            tbody.appendChild(tr);
        });
    }

    function applyServicesData(services) {
        allServices = Array.isArray(services) ? services : [];

        let online = 0;
        let slow = 0;
        let offline = 0;

        allServices.forEach(s => {
            const status = s.current_status;
            if (status === 'ONLINE') online++;
            else if (status === 'SLOW' || status === 'RATE_LIMITED') slow++;
            else if (status === 'OFFLINE') offline++;
        });

        updateCounts(allServices.length, online, slow, offline);
        updateHealthBanner(allServices.length, online, slow, offline);
        renderTable();
    }

    async function refreshBoard() {
        if (isRefreshing) return;
        isRefreshing = true;

        if (refreshBtn) {
            refreshBtn.disabled = true;
            refreshBtn.textContent = 'Refreshing...';
        }

        try {
            const response = await fetch(apiUrl, { cache: 'no-store' });
            if (!isPublic && response.status === 401) {
                window.location.href = '/admin.php';
                return;
            }
            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            applyServicesData(await response.json());
            countdown = 30;
            if (refreshStatusEl) {
                refreshStatusEl.textContent = `Auto-refreshing in ${countdown}s`;
            }
        } catch (err) {
            console.error('Failed to load status:', err);
            if (refreshStatusEl) {
                refreshStatusEl.textContent = 'Connection error. Retrying...';
            }
        } finally {
            isRefreshing = false;
            if (refreshBtn) {
                refreshBtn.disabled = false;
                refreshBtn.textContent = 'Refresh Now';
            }
        }
    }

    document.querySelectorAll('[data-filter-status]').forEach(btn => {
        btn.addEventListener('click', () => {
            setActiveFilter(btn.dataset.filterStatus);
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            searchQuery = searchInput.value.trim().toLowerCase();
            renderTable();
        });
    }

    if (refreshBtn) {
        refreshBtn.addEventListener('click', refreshBoard);
    }

    refreshBoard();
    setInterval(() => {
        if (isRefreshing) return;
        countdown--;
        if (countdown <= 0) {
            refreshBoard();
        } else if (refreshStatusEl) {
            refreshStatusEl.textContent = `Auto-refreshing in ${countdown}s`;
        }
    }, 1000);
})();
