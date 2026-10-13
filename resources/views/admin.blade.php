@extends('layouts.admin')

@section('content')
<div class="header">
        <div class="nav-container">
            <div class="nav-header">
                <div class="header-title">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="width: 1.75rem; height: 1.75rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4" /><circle cx="12" cy="16" r="1.5" fill="currentColor" stroke="none"/></svg> LigtasAlert Admin
                </div>
                <div class="header-actions">
                    <div class="facility-selector">
                        <label for="facility">Facility:</label>
                        <select id="facility">
                            <option value="all">All Facilities</option>
                        </select>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn-logout">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" /></svg>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
            <div class="nav-tabs">
                <button class="nav-tab active" data-tab="dashboard">Dashboard</button>
                <button class="nav-tab" data-tab="contacts">Contacts</button>
                <button class="nav-tab" data-tab="history">History</button>
                <button class="nav-tab" data-tab="reports">Reports</button>
            </div>
        </div>
    </div>

    <div class="container">
        <div id="dashboardTab" class="tab-content active">
            <div class="stats-grid">
                <div class="stat-card active">
                    <div class="stat-number" id="activeCount">-</div>
                    <div class="stat-label">Active Alerts</div>
                </div>
                <div class="stat-card standby">
                    <div class="stat-number" id="respondersCount">-</div>
                    <div class="stat-label">Responders On Duty</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="resolvedCount">-</div>
                    <div class="stat-label">Resolved Today</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="avgResponseTime">-</div>
                    <div class="stat-label">Avg Response Time</div>
                </div>
            </div>

            <div class="alerts-section">
                <div class="section-title">Active Incidents</div>
                <div class="alerts-grid" id="alertsGrid">
                    <div class="loading">No active alerts</div>
                </div>
            </div>

            <div class="alerts-section">
                <div class="section-title">Recent Activity</div>
                <div class="activity-log" id="activityLog">
                    <div class="loading">No recent activity</div>
                </div>
            </div>
        </div>

        <div id="contactsTab" class="tab-content" style="display: none;">
            <div class="alerts-section">
                <div>
                    <div class="section-title">Mabalacat City Emergency Hotlines</div>
                    <p class="contacts-description">Call a responder directly for emergency assistance.</p>
                </div>
                <div class="responder-grid">
                    <section class="responder-card">
                        <div class="responder-heading">
                            <span class="responder-mark" aria-hidden="true">M</span>
                            <div>
                                <h3 class="responder-name">MGA Center</h3>
                                <p class="responder-type">Emergency Hotline</p>
                            </div>
                        </div>
                        <a class="responder-phone" href="tel:+639192426041">0919-242-6041</a>
                    </section>

                    <section class="responder-card">
                        <div class="responder-heading">
                            <span class="responder-mark" aria-hidden="true">P</span>
                            <div>
                                <h3 class="responder-name">PELCO II</h3>
                                <p class="responder-type">Electric Cooperative</p>
                            </div>
                        </div>
                        <a class="responder-phone" href="tel:+639190590423">0919-059-0423</a>
                    </section>

                    <section class="responder-card">
                        <div class="responder-heading">
                            <span class="responder-mark" aria-hidden="true">C</span>
                            <div>
                                <h3 class="responder-name">CDRRMO</h3>
                                <p class="responder-type">Disaster Risk Reduction and Management</p>
                            </div>
                        </div>
                        <a class="responder-phone" href="tel:+639276117634">0927-611-7634</a>
                    </section>

                    <section class="responder-card">
                        <div class="responder-heading">
                            <span class="responder-mark" aria-hidden="true">P</span>
                            <div>
                                <h3 class="responder-name">PNP</h3>
                                <p class="responder-type">Philippine National Police</p>
                            </div>
                        </div>
                        <div class="responder-numbers">
                            <a class="responder-phone" href="tel:+639989673262">0998-967-3262</a>
                            <a class="responder-phone" href="tel:+639485524384">0948-552-4384</a>
                        </div>
                    </section>

                    <section class="responder-card">
                        <div class="responder-heading">
                            <span class="responder-mark" aria-hidden="true">B</span>
                            <div>
                                <h3 class="responder-name">BFP</h3>
                                <p class="responder-type">Bureau of Fire Protection</p>
                            </div>
                        </div>
                        <div class="responder-numbers">
                            <a class="responder-phone" href="tel:+639339909960">0933-990-9960</a>
                            <a class="responder-phone" href="tel:+639633636212">0963-363-6212</a>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <div id="historyTab" class="tab-content" style="display: none;">
            <div class="alerts-section">
                <div class="section-title">Resolved Incidents</div>
                <div class="alerts-grid history-grid" id="historyGrid">
                    <div class="loading">No resolved incidents</div>
                </div>
            </div>
        </div>

        <div id="reportsTab" class="tab-content" style="display: none;">
            <div class="reports-header">
                <div class="section-title">Incident Reports</div>
                <div class="reports-controls">
                    <input type="date" id="reportFrom" class="form-input" style="max-width:14rem;">
                    <input type="date" id="reportTo" class="form-input" style="max-width:14rem;">
                    <select id="reportFacility" class="form-input" style="max-width:16rem;">
                        <option value="">All facilities</option>
                    </select>
                    <button class="btn-primary" id="reportRefresh" style="width:auto;padding:0.65rem 1.25rem;">Refresh</button>
                    <a class="btn-logout" id="reportCsv" href="#" style="text-decoration:none;">Export CSV</a>
                </div>
            </div>

            <div class="stats-grid" id="reportStats">
                <div class="stat-card"><div class="stat-number" id="reportTotal">-</div><div class="stat-label">Total Incidents</div></div>
                <div class="stat-card active"><div class="stat-number" id="reportActive">-</div><div class="stat-label">Active</div></div>
                <div class="stat-card standby"><div class="stat-number" id="reportOpen">-</div><div class="stat-label">Open</div></div>
                <div class="stat-card"><div class="stat-number" id="reportAvg">-</div><div class="stat-label">Avg Response</div></div>
            </div>

            <div class="reports-grid">
                <div class="alerts-section">
                    <div class="section-title">By Status</div>
                    <div class="report-bars" id="reportByStatus"></div>
                </div>
                <div class="alerts-section">
                    <div class="section-title">By Emergency Type</div>
                    <div class="report-bars" id="reportByType"></div>
                </div>
                <div class="alerts-section">
                    <div class="section-title">By Facility</div>
                    <div class="report-bars" id="reportByFacility"></div>
                </div>
                <div class="alerts-section">
                    <div class="section-title">Response Time (seconds)</div>
                    <div class="report-bars" id="reportResponseTimes"></div>
                </div>
            </div>

            <div class="alerts-section">
                <div class="section-title">Recent Incidents</div>
                <div class="report-table" id="reportRecent">
                    <div class="loading">No data</div>
                </div>
            </div>
        </div>
    </div>
@endsection