@extends('layouts.admin')

@section('content')
<div class="header">
        <div class="nav-container">
            <div class="nav-header">
                <div class="header-title">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 1.75rem; height: 1.75rem;" class="lucide lucide-shield-check" aria-hidden="true"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path><path d="m9 12 2 2 4-4"></path></svg> LigtasAlert Admin
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
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-out" aria-hidden="true"><path d="m16 17 5-5-5-5"></path><path d="M21 12H9"></path><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path></svg>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
            <div class="nav-tabs">
                <button class="nav-tab active" data-tab="dashboard"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-layout-dashboard" aria-hidden="true" style="width: 1.1rem; height: 1.1rem; vertical-align: -0.125rem; margin-right: 0.35rem;"><rect width="7" height="9" x="3" y="3" rx="1"></rect><rect width="7" height="5" x="14" y="3" rx="1"></rect><rect width="7" height="9" x="14" y="12" rx="1"></rect><rect width="7" height="5" x="3" y="16" rx="1"></rect></svg>Dashboard</button>
                <button class="nav-tab" data-tab="contacts"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-round" aria-hidden="true" style="width: 1.1rem; height: 1.1rem; vertical-align: -0.125rem; margin-right: 0.35rem;"><circle cx="12" cy="8" r="5"></circle><path d="M20 21a8 8 0 0 0-16 0"></path></svg>Contacts</button>
                <button class="nav-tab" data-tab="history"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-clock" aria-hidden="true" style="width: 1.1rem; height: 1.1rem; vertical-align: -0.125rem; margin-right: 0.35rem;"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>History</button>
                <button class="nav-tab" data-tab="reports"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-file-text" aria-hidden="true" style="width: 1.1rem; height: 1.1rem; vertical-align: -0.125rem; margin-right: 0.35rem;"><path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"></path><path d="M14 2v5a1 1 0 0 0 1 1h5"></path><path d="M10 9H8"></path><path d="M16 13H8"></path><path d="M16 17H8"></path></svg>Reports</button>
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