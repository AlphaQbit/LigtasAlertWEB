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

function filterContacts() {
    const category = document.getElementById('contactCategory');
    const cards = [...document.querySelectorAll('#responderGrid .responder-card')];
    const selectedCategory = category?.value ?? 'all';
    let visibleCount = 0;

    cards.forEach(card => {
        const matchesCategory = selectedCategory === 'all' || card.dataset.category === selectedCategory;
        card.hidden = !matchesCategory;
        if (!card.hidden) visibleCount += 1;
    });

    const emptyMessage = document.getElementById('contactsEmpty');
    if (emptyMessage) emptyMessage.hidden = visibleCount > 0;
}

async function copyContactNumber(number) {
    let field;

    try {
        if (typeof navigator.clipboard?.writeText === 'function') {
            try {
                await navigator.clipboard.writeText(number);
            } catch (clipboardError) {
                // Clipboard API failed (e.g., HTTP/localhost requires HTTPS), fall back to execCommand
                throw clipboardError;
            }
        } else {
            throw new Error('Clipboard API not available');
        }

        toast(`Copied ${number}`);
    } catch (error) {
        // Fallback to document.execCommand('copy') for HTTP/localhost or when Clipboard API fails
        try {
            field = document.createElement('textarea');
            field.value = number;
            field.setAttribute('readonly', '');
            field.style.position = 'fixed';
            field.style.opacity = '0';
            document.body.appendChild(field);
            field.select();
            const copied = document.execCommand('copy');
            if (!copied) throw new Error('Clipboard copy was rejected');

            toast(`Copied ${number}`);
        } catch (execError) {
            toast('Could not copy the number. Please select it and copy manually.', true);
        } finally {
            field?.remove();
        }
    }
}

function openContactsDialog(dialogId) {
    const dialog = document.getElementById(dialogId);
    if (!dialog || typeof dialog.showModal !== 'function') {
        toast('This browser does not support the contact dialog.', true);
        return;
    }

    dialog.showModal();
    dialog.querySelector('input, select, textarea')?.focus();
}

function previewContactAction(form) {
    const notice = form.querySelector('.contacts-dialog-notice');
    if (!notice) return;

    const values = new FormData(form);
    if (form.dataset.previewForm === 'report') {
        notice.textContent = `Preview ready for ${values.get('hotline')}. This report was not submitted or stored.`;
        toast('Preview only — report not submitted.');
        return;
    }

    notice.textContent = `Preview ready: ${values.get('name')} · ${values.get('phone')} · ${values.get('category')}. This contact was not saved.`;
    toast('Preview only — contact not saved.');
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

function renderAlertCards(alerts, grid, emptyMessage, includeActions) {
    if (!alerts.length) {
        grid.innerHTML = `<div class="loading"><span class="lucide lucide-siren" aria-hidden="true" style="display:block;margin:0 auto 0.5rem;"></span>${emptyMessage}</div>`;
        if (typeof lucide !== 'undefined') lucide.createIcons();
        return;
    }

    grid.innerHTML = alerts.map(alert => {
        const actions = includeActions ? (NEXT_STATUS[alert.status] || []).map(next => `
            <button class="${BUTTON_CLASS[next]}"
                    data-alert-id="${alert.id}"
                    data-next-status="${next}">
                ${BUTTON_LABELS[next]}
            </button>`).join('') : '';
        const resolvedAt = alert.status === 'resolved' && alert.resolved_at ? `
                    <div class="detail-row">
                        <span class="detail-label">Resolved</span>
                        <span class="detail-value">${relativeTime(alert.resolved_at)}</span>
                    </div>` : '';

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
                    ${resolvedAt}
                </div>
                ${actions ? `<div class="alert-actions">${actions}</div>` : ''}
            </div>`;
    }).join('');
}

function renderAlerts(alerts) {
    const activeAlerts = alerts.filter(alert => alert.status !== 'resolved');
    renderAlertCards(activeAlerts, document.getElementById('alertsGrid'), 'No active incidents', true);
}

function renderHistory(alerts) {
    const resolvedAlerts = alerts.filter(alert => alert.status === 'resolved');
    renderAlertCards(resolvedAlerts, document.getElementById('historyGrid'), 'No resolved incidents', false);
}

function renderActivity(alerts) {
    const log = document.getElementById('activityLog');

    if (!alerts.length) {
        log.innerHTML = '<div class="loading"><span class="lucide lucide-clock" aria-hidden="true" style="display:block;margin:0 auto 0.5rem;"></span>No recent activity</div>';
        if (typeof lucide !== 'undefined') lucide.createIcons();
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
        populateFacilities([...payload.data, ...payload.history]);
        renderStats(payload.stats);
        renderAlerts(payload.data);
        renderHistory(payload.history);
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

    if (tabName === 'reports') {
        loadReports();
    }
}

function populateFacilities(alerts) {
    const select = document.getElementById('facility');
    if (!select) return;

    // Rebuild every refresh instead of guarding with a flag. The first poll
    // only knows about alerts that existed when the page loaded; a building
    // added later never appears until a hard refresh clears the guard.
    const current = select.value;
    select.innerHTML = '<option value="all">All Facilities</option>';

    [...new Set(alerts.map(alert => alert.facility))].sort().forEach(facility => {
        const option = document.createElement('option');
        option.value = facility;
        option.textContent = facility;
        select.appendChild(option);
    });

    if (current && current !== 'all') {
        select.value = current;
    }
}

function init() {
    document.querySelectorAll('.nav-tab').forEach(tab => {
        tab.addEventListener('click', () => switchTab(tab.dataset.tab));
    });

    const contactsTab = document.getElementById('contactsTab');
    document.getElementById('contactCategory')?.addEventListener('change', filterContacts);
    contactsTab?.addEventListener('click', event => {
        const copyButton = event.target.closest('[data-copy-number]');
        if (copyButton) {
            copyContactNumber(copyButton.dataset.copyNumber);
            return;
        }

        const openButton = event.target.closest('[data-open-contact-dialog]');
        if (openButton) openContactsDialog(openButton.dataset.openContactDialog);
    });
    contactsTab?.addEventListener('submit', event => {
        const form = event.target.closest('[data-preview-form]');
        if (!form) return;

        event.preventDefault();
        previewContactAction(form);
    });
    contactsTab?.querySelectorAll('.contacts-dialog').forEach(dialog => {
        dialog.addEventListener('close', () => {
            dialog.querySelector('[data-preview-form]')?.reset();
            const notice = dialog.querySelector('.contacts-dialog-notice');
            if (notice) {
                notice.textContent = notice.closest('[data-preview-form]')?.dataset.previewForm === 'report'
                    ? 'Preview only. This report will not be sent or stored.'
                    : 'Preview only. This contact will not be saved or added to the hotline list.';
            }
        });
    });
    filterContacts();

    document.getElementById('alertsGrid').addEventListener('click', event => {
        const button = event.target.closest('[data-next-status]');
        if (button) {
            button.disabled = true;
            updateStatus(button.dataset.alertId, button.dataset.nextStatus);
        }
    });

    document.getElementById('facility')?.addEventListener('change', refresh);

    const reportRefresh = document.getElementById('reportRefresh');
    if (reportRefresh) {
        reportRefresh.addEventListener('click', loadReports);
    }
    ['reportFrom', 'reportTo', 'reportFacility'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', loadReports);
    });

    refresh();
    setInterval(refresh, POLL_MS);
}

document.addEventListener('DOMContentLoaded', init);

/* ---------------------------------------------------------------------------
 * Incident Reports tab. Polls /admin/reports with the same session cookie as
 * the dashboard, so every number is real - no denormalized copies, no sample
 * data. The CSV link is a plain GET to /admin/reports/csv, which returns the
 * rows the browser already has permission to see.
 * ------------------------------------------------------------------------- */
async function loadReports() {
    const params = new URLSearchParams();
    const from = document.getElementById('reportFrom').value;
    const to = document.getElementById('reportTo').value;
    const facility = document.getElementById('reportFacility').value;
    if (from) params.set('from', from);
    if (to) params.set('to', to);
    if (facility) params.set('facility', facility);

    const csvLink = document.getElementById('reportCsv');
    csvLink.href = `/admin/reports/csv?${params.toString()}`;

    let payload;
    try {
        const response = await fetch(`/admin/reports?${params.toString()}`, {
            headers: { Accept: 'application/json' },
        });
        if (response.status === 401) {
            window.location = '/login';
            return;
        }
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        payload = await response.json();
    } catch (error) {
        toast(`Could not load reports: ${error.message}`, true);
        return;
    }

    renderReportStats(payload.summary);
    renderReportBars('reportByStatus', payload.by_status, 'status');
    renderReportBars('reportByType', payload.by_type, 'type');
    renderReportBars('reportByFacility', payload.by_facility, 'facility');
    renderReportResponseTimes(payload.response_times);
    renderReportRecent(payload.recent);

    populateReportFacilities(payload.by_facility);
}

function renderReportStats(summary) {
    const set = (id, value) => {
        const el = document.getElementById(id);
        if (el) el.textContent = value ?? '-';
    };
    set('reportTotal', summary.total);
    set('reportActive', summary.active);
    set('reportOpen', summary.open);
    set('reportAvg', summary.avg != null ? `${summary.avg}s` : '-');
}

function renderReportBars(containerId, rows, labelKey) {
    const container = document.getElementById(containerId);
    if (!container) return;

    if (!rows.length) {
        container.innerHTML = '<div class="loading"><span class="lucide lucide-chart-no-axes-column" aria-hidden="true" style="display:block;margin:0 auto 0.5rem;"></span>No data in this range</div>';
        if (typeof lucide !== 'undefined') lucide.createIcons();
        return;
    }

    const max = Math.max(...rows.map(r => r.count), 1);
    container.innerHTML = rows.map(row => {
        const pct = Math.max(4, (row.count / max) * 100);
        return `
            <div class="report-bar-row">
                <span class="report-bar-label" title="${escapeHtml(row.key)}">${escapeHtml(row.key)}</span>
                <span class="report-bar-track"><span class="report-bar-fill" style="width:${pct}%"></span></span>
                <span class="report-bar-count">${row.count}</span>
            </div>`;
    }).join('');
}

function renderReportResponseTimes(times) {
    const container = document.getElementById('reportResponseTimes');
    if (!container) return;

    if (times.count === 0) {
        container.innerHTML = '<div class="loading">No acknowledged incidents yet</div>';
        return;
    }

    const rows = [
        { key: 'min', count: times.min },
        { key: 'avg', count: times.avg },
        { key: 'max', count: times.max },
    ];
    renderReportBars('reportResponseTimes', rows, 'key');
}

function renderReportRecent(alerts) {
    const container = document.getElementById('reportRecent');
    if (!container) return;

    if (!alerts.length) {
        container.innerHTML = '<div class="loading">No incidents in this range</div>';
        return;
    }

    const statusClass = (status) => ({
        active: 'active', responding: 'responding',
        acknowledged: 'acknowledged', resolved: 'resolved',
    }[status] || '');

    container.innerHTML = `
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Facility</th><th>Type</th><th>Location</th>
                    <th>Status</th><th>Acknowledged</th><th>Resolved</th>
                    <th>Response</th><th>Created</th>
                </tr>
            </thead>
            <tbody>
                ${alerts.map(a => `
                    <tr>
                        <td>${a.id}</td>
                        <td>${escapeHtml(a.facility)}</td>
                        <td>${escapeHtml(a.type)}</td>
                        <td>${escapeHtml(a.location)}</td>
                        <td><span class="badge-sm ${statusClass(a.status)}">${escapeHtml(a.status)}</span></td>
                        <td>${a.acknowledged_at ? formatIso(a.acknowledged_at) : '-'}</td>
                        <td>${a.resolved_at ? formatIso(a.resolved_at) : '-'}</td>
                        <td>${a.response_seconds != null ? `${a.response_seconds}s` : '-'}</td>
                        <td>${formatIso(a.created_at)}</td>
                    </tr>`).join('')}
            </tbody>
        </table>`;
}

function populateReportFacilities(byFacility) {
    const select = document.getElementById('reportFacility');
    if (!select) return;

    const current = select.value;
    select.innerHTML = '<option value="">All facilities</option>';
    [...new Set(byFacility.map(f => f.key))].sort().forEach(facility => {
        const option = document.createElement('option');
        option.value = facility;
        option.textContent = facility;
        select.appendChild(option);
    });
    if (current && current !== '') select.value = current;
}

function formatIso(iso) {
    if (!iso) return '-';
    const d = new Date(iso);
    return Number.isNaN(d.getTime()) ? iso : d.toLocaleString();
}