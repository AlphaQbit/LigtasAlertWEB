// ponytail: polling instead of WebSockets. No Reverb/Echo here, and a 5s
// refresh demos identically to a socket for one rescuer's screen. Swap the
// setInterval for a server-sent event stream if you need sub-second updates.
const POLL_MS = 5000;

const STATUS_LABELS = {
    active: 'Active',
    responding: 'Responding',
    acknowledged: 'Acknowledged',
    resolved: 'Resolved',
};

// Next steps a rescuer can take, matching Alert::TRANSITIONS on the backend.
const NEXT_STATUS = {
    active: ['responding', 'acknowledged', 'resolved'],
    responding: ['acknowledged', 'resolved'],
    acknowledged: ['responding', 'resolved'],
    resolved: [],
};

const BUTTON_LABELS = {
    responding: 'Start Response',
    acknowledged: 'Acknowledge',
    resolved: 'Resolve',
};

const BUTTON_CLASS = {
    responding: 'btn',
    acknowledged: 'btn primary',
    resolved: 'btn danger',
};

function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

function relativeTime(iso) {
    if (!iso) return '—';
    const seconds = Math.max(0, (Date.now() - new Date(iso)) / 1000);
    if (seconds < 60) return `${Math.floor(seconds)}s ago`;
    if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
    return `${Math.floor(seconds / 3600)}h ago`;
}

function timeOfDay(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function toast(message, isError = false) {
    let el = document.querySelector('.toast');
    if (!el) {
        el = document.createElement('div');
        el.className = 'toast';
        document.body.appendChild(el);
    }
    el.textContent = message;
    el.style.borderLeft = `4px solid ${isError ? 'var(--danger)' : 'var(--primary)'}`;
    el.style.display = 'block';
    setTimeout(() => { el.style.display = 'none'; }, 3000);
}

function renderStats(stats) {
    document.getElementById('activeCount').textContent = stats.active ?? 0;
    document.getElementById('respondersCount').textContent = stats.responding ?? 0;
    document.getElementById('resolvedCount').textContent = stats.resolved_today ?? 0;

    const avg = document.getElementById('avgResponseTime');
    avg.textContent = stats.avg_response_seconds != null
        ? `${Math.round(stats.avg_response_seconds)}s`
        : '—';
}

function renderAlerts(alerts) {
    const grid = document.getElementById('alertsGrid');

    if (!alerts.length) {
        grid.innerHTML = '<div class="loading">No alerts yet</div>';
        return;
    }

    grid.innerHTML = alerts.map(alert => {
        const actions = (NEXT_STATUS[alert.status] || []).map(next => `
            <button class="${BUTTON_CLASS[next]}"
                    data-alert-id="${alert.id}"
                    data-next-status="${next}">
                ${BUTTON_LABELS[next]}
            </button>`).join('');

        return `
            <div class="alert-card ${escapeHtml(alert.status)}">
                <div class="alert-header">
                    <div class="alert-type">${escapeHtml(alert.type)}</div>
                    <span class="badge ${escapeHtml(alert.status)}">${STATUS_LABELS[alert.status] ?? alert.status}</span>
                </div>
                <div class="alert-details">
                    <div class="detail-row">
                        <span class="detail-label">Facility</span>
                        <span class="detail-value">${escapeHtml(alert.facility)}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Location</span>
                        <span class="detail-value">${escapeHtml(alert.location)}</span>
                    </div>
                    ${alert.details ? `
                    <div class="detail-row">
                        <span class="detail-label">Details</span>
                        <span class="detail-value">${escapeHtml(alert.details)}</span>
                    </div>` : ''}
                    <div class="detail-row">
                        <span class="detail-label">Sent</span>
                        <span class="detail-value">${relativeTime(alert.created_at)}</span>
                    </div>
                </div>
                ${actions ? `<div class="alert-actions">${actions}</div>` : ''}
            </div>`;
    }).join('');
}

function renderActivity(alerts) {
    const log = document.getElementById('activityLog');

    if (!alerts.length) {
        log.innerHTML = '<div class="loading">No recent activity</div>';
        return;
    }

    log.innerHTML = alerts.map(alert => `
        <div class="activity-item">
            <span class="activity-time">${timeOfDay(alert.created_at)}</span>
            <span class="activity-icon ${alert.status === 'resolved' ? 'resolved' : 'alert'}"></span>
            <div class="activity-text">
                <strong>${escapeHtml(alert.type)}</strong> at ${escapeHtml(alert.location)}
                — ${STATUS_LABELS[alert.status] ?? alert.status}
                ${alert.response_seconds != null ? `(${alert.response_seconds}s response)` : ''}
            </div>
        </div>`).join('');
}

async function refresh() {
    const facility = document.getElementById('facility')?.value;
    const query = facility && facility !== 'all' ? `?facility=${encodeURIComponent(facility)}` : '';

    try {
        const response = await fetch(`/admin/alerts${query}`, {
            headers: { Accept: 'application/json' },
        });

        if (response.status === 401) {
            window.location = '/login';
            return;
        }

        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const payload = await response.json();
        populateFacilities(payload.data);
        renderStats(payload.stats);
        renderAlerts(payload.data);
        renderActivity(payload.data);
    } catch (error) {
        toast(`Could not reach the server: ${error.message}`, true);
    }
}

async function updateStatus(alertId, status) {
    try {
        const response = await fetch(`/admin/alerts/${alertId}/status`, {
            method: 'PATCH',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({ status }),
        });

        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            toast(payload.message || `Could not update alert (HTTP ${response.status})`, true);
            return;
        }

        toast(`Alert marked ${STATUS_LABELS[status] ?? status}`);
        await refresh();
    } catch (error) {
        toast(`Could not update alert: ${error.message}`, true);
    }
}

function switchTab(tabName) {
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.classList.toggle('active', tab.dataset.tab === tabName);
    });

    document.querySelectorAll('.tab-content').forEach(content => {
        content.style.display = content.id === `${tabName}Tab` ? 'block' : 'none';
    });
}

function populateFacilities(alerts) {
    const select = document.getElementById('facility');
    if (!select || select.dataset.populated) return;

    [...new Set(alerts.map(alert => alert.facility))].sort().forEach(facility => {
        const option = document.createElement('option');
        option.value = facility;
        option.textContent = facility;
        select.appendChild(option);
    });

    select.dataset.populated = 'true';
}

function init() {
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.addEventListener('click', () => switchTab(tab.dataset.tab));
    });

    document.getElementById('alertsGrid').addEventListener('click', event => {
        const button = event.target.closest('[data-next-status]');
        if (button) {
            button.disabled = true;
            updateStatus(button.dataset.alertId, button.dataset.nextStatus);
        }
    });

    document.getElementById('facility')?.addEventListener('change', refresh);

    refresh();
    setInterval(refresh, POLL_MS);
}

document.addEventListener('DOMContentLoaded', init);