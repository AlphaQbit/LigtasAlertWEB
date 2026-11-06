@extends('layouts.admin')

@section('content')
@php
    $profileName = auth()->user()->name;
    $profileEmail = auth()->user()->email;
    $microsoftProfile = session('microsoft_profile');

    if (is_array($microsoftProfile) && ($microsoftProfile['user_id'] ?? null) === auth()->id()) {
        $profileName = $microsoftProfile['name'] ?? $profileName;
        $profileEmail = $microsoftProfile['email'] ?? $profileEmail;
        $profilePhoto = $microsoftProfile['photo'] ?? null;
    } else {
        $profilePhoto = null;
    }

    $profileInitials = collect(preg_split('/\s+/', trim($profileName)) ?: [])
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->implode('');
@endphp
<div class="header">
        <div class="nav-container">
            <div class="nav-header">
                <div class="header-title">
                    <img class="header-logo" src="{{ asset('assets/ligtasLOGO.png') }}" alt="">
                    LigtasAlert Admin
                </div>
                <div class="header-actions">
                    <div class="facility-selector">
                        <label for="facility">Facility:</label>
                        <select id="facility">
                            <option value="all">All Facilities</option>
                        </select>
                    </div>
                    <div class="admin-profile-avatar" role="img" aria-label="Administrator profile picture for {{ $profileName }}" title="{{ $profileName }} ({{ $profileEmail }})">
                        @if ($profilePhoto)
                            <img src="{{ $profilePhoto }}" alt="" onerror="this.hidden = true; this.nextElementSibling.hidden = false;">
                        @endif
                        <span class="admin-profile-initials" @if ($profilePhoto) hidden @endif>{{ $profileInitials }}</span>
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
        
            {{-- Replace the WHOLE <div id="contactsTab" ...> ... </div> block (everything up to, but not including, <div id="historyTab">) --}}
            @php
                // Put photos/logos in public/assets/contacts/ using the file names below (.jpg). Missing file = coloured name panel.
                $hotlines = [
                    ['cat' => 'medical',   'label' => 'MGA',    'img' => 'mga-center', 'name' => 'MGA Center', 'type' => 'Medical Emergency Hotline',             'tag' => 'Medical',   'uses' => ['Ambulance', 'Injuries', 'Sudden illness'],            'search' => 'mga center emergency hotline medical ambulance',                       'numbers' => ['0919-242-6041']],
                    ['cat' => 'utilities', 'label' => 'PELCO',  'img' => 'pelco-ii',   'name' => 'PELCO II',   'type' => 'Electric Cooperative',                   'tag' => 'Utilities', 'uses' => ['Power outage', 'Downed wires', 'Electrical hazard'],  'search' => 'pelco ii electric cooperative power electricity utility',              'numbers' => ['0919-059-0423']],
                    ['cat' => 'disaster',  'label' => 'CDRRMO', 'img' => 'cdrrmo',     'name' => 'CDRRMO',     'type' => 'Disaster Risk Reduction and Management', 'tag' => 'Disaster',  'uses' => ['Rescue', 'Evacuation', 'Disaster response'],          'search' => 'cdrrmo disaster risk reduction management rescue disaster response',  'numbers' => ['0927-611-7634']],
                    ['cat' => 'security',  'label' => 'PNP',    'img' => 'pnp',        'name' => 'PNP',        'type' => 'Philippine National Police',             'tag' => 'Security',  'uses' => ['Crime', 'Threats', 'Public safety'],                  'search' => 'pnp philippine national police security police',                      'numbers' => ['0998-967-3262', '0948-552-4384']],
                    ['cat' => 'fire',      'label' => 'BFP',    'img' => 'bfp',        'name' => 'BFP',        'type' => 'Bureau of Fire Protection',              'tag' => 'Fire',      'uses' => ['Fire outbreak', 'Smoke or burning', 'Fire rescue'],   'search' => 'bfp bureau fire protection fire',                                     'numbers' => ['0933-990-9960', '0963-363-6212']],
                ];
                $guides = [
                    ['tone' => 'fire',     'title' => 'Fire outbreak',                'call' => 'Call BFP',       'steps' => ['Warn people nearby and leave using the nearest safe exit.', 'Stay low under smoke and do not use elevators.', 'Call BFP from a safe location. Never re-enter a burning building.']],
                    ['tone' => 'utility',  'title' => 'Power interruption or hazard', 'call' => 'Call PELCO II',  'steps' => ['Stay clear of downed wires and damaged electrical equipment.', 'Keep others away from the hazard and move to a safe location.', 'Call PELCO II to report the outage or electrical danger.']],
                    ['tone' => 'medical',  'title' => 'Medical emergency',            'call' => 'Call MGA Center','steps' => ['Check that the scene is safe before approaching.', 'Call MGA Center and share your exact location and the person’s condition.', 'Follow the dispatcher’s instructions; avoid moving someone with a serious injury.']],
                    ['tone' => 'security', 'title' => 'Security threat',              'call' => 'Call PNP',       'steps' => ['Move away from danger and avoid confronting the threat.', 'Call PNP when safe and share your location and observations.', 'Follow directions from emergency responders.']],
                    ['tone' => 'disaster', 'title' => 'Disaster or evacuation',       'call' => 'Call CDRRMO',    'steps' => ['Follow official evacuation instructions and use marked safe routes.', 'Bring essential medication and keep family members together.', 'Call CDRRMO for local disaster assistance or rescue.']],
                ];
            @endphp
            <div id="contactsTab" class="tab-content" style="display: none;">
                <div class="alerts-section">
                    <div class="contacts-header">
                        <div>
                            <div class="section-title">Mabalacat City Emergency Hotlines</div>
                            <p class="contacts-description">Find the right local team and call directly for help.</p>
                        </div>
                        <div class="contacts-controls">
                            <label class="contacts-category-field" for="contactCategory">
                                <span>Category</span>
                                <select id="contactCategory" class="contacts-filter">
                                    <option value="all">All categories</option>
                                    <option value="medical">Medical</option>
                                    <option value="security">Security</option>
                                    <option value="utilities">Utilities</option>
                                    <option value="disaster">Disaster</option>
                                    <option value="fire">Fire</option>
                                </select>
                            </label>
                        </div>
                    </div>

                    <div class="responder-grid" id="responderGrid">
                        @foreach ($hotlines as $h)
                        <section class="responder-card" data-category="{{ $h['cat'] }}" data-search="{{ $h['search'] }}">
                            <div class="responder-photo" data-label="{{ $h['label'] }}">
                                <img src="{{ asset('assets/contacts/' . $h['img'] . '.jpg') }}" alt="{{ $h['name'] }}" loading="lazy" onerror="this.parentNode.classList.add('no-photo'); this.remove();">
                                <span class="responder-tag">{{ $h['tag'] }}</span>
                            </div>
                            <div class="responder-body">
                                <div class="responder-heading">
                                    <div class="responder-info">
                                        <h3 class="responder-name">{{ $h['name'] }}</h3>
                                        <p class="responder-type">{{ $h['type'] }}</p>
                                    </div>
                                </div>
                                <div class="responder-uses">
                                    <span class="responder-uses-label">Call for</span>
                                    <ul>@foreach ($h['uses'] as $use)<li>{{ $use }}</li>@endforeach</ul>
                                </div>
                                <div class="responder-numbers">
                                    @foreach ($h['numbers'] as $n)
                                    @php $tel = '+63' . substr(preg_replace('/\D/', '', $n), 1); @endphp
                                    <div class="responder-number-row">
                                        <a class="responder-phone" href="tel:{{ $tel }}"><small>Tap to call</small>{{ $n }}</a>
                                        <button class="contact-action contact-copy" type="button" data-copy-number="{{ $n }}">Copy</button>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </section>
                        @endforeach
                    </div>
                    <p class="contacts-empty" id="contactsEmpty" role="status" hidden>No hotlines in this category.</p>
                </div>

                <section class="guidelines-section" aria-labelledby="guidelinesTitle">
                    <div class="guidelines-heading">
                        <div>
                            <h2 class="section-title" id="guidelinesTitle">Emergency Action Guidelines</h2>
                            <p class="contacts-description">A quick reference while help is on the way.</p>
                        </div>
                        <span class="guidelines-note">Stay calm. Stay safe.</span>
                    </div>
                    <div class="guidelines-grid">
                        @foreach ($guides as $g)
                        <details class="guideline-card tone-{{ $g['tone'] }}">
                            <summary><span class="guideline-title">{{ $g['title'] }} <small>{{ $g['call'] }}</small></span></summary>
                            <div class="guideline-content"><ol>@foreach ($g['steps'] as $step)<li>{{ $step }}</li>@endforeach</ol></div>
                        </details>
                        @endforeach
                    </div>
                </section>

                <section class="contacts-admin-actions" aria-label="Contact administration">
                    <div>
                        <h2>Keep hotline information current (TEST ONLY)</h2>
                        <p>Flag a number that needs checking or prepare a contact suggestion.</p>
                    </div>
                    <div class="contacts-admin-buttons">
                        <button class="contacts-secondary-button" type="button" data-open-contact-dialog="reportHotlineDialog">Report unresponsive hotline</button>
                        <button class="contacts-primary-button" type="button" data-open-contact-dialog="addContactDialog">Add custom contact</button>
                    </div>
                    <p class="contacts-demo-note">Admin actions are a preview only; changes are not saved or submitted.</p>
                </section>

                <dialog class="contacts-dialog" id="reportHotlineDialog" aria-labelledby="reportHotlineTitle">
                    <form method="dialog" class="contacts-dialog-close-form"><button class="contacts-dialog-close" aria-label="Close dialog">&times;</button></form>
                    <div class="contacts-dialog-heading">
                        <div><h2 id="reportHotlineTitle">Report an unresponsive hotline</h2><p>Prepare a note for the admin team.</p></div>
                    </div>
                    <form method="dialog" class="contacts-dialog-form" data-preview-form="report">
                        <label for="reportHotline">Hotline</label>
                        <select id="reportHotline" name="hotline" required>
                            <option value="">Choose a hotline</option>
                            <option>MGA Center - 0919-242-6041</option>
                            <option>PELCO II - 0919-059-0423</option>
                            <option>CDRRMO - 0927-611-7634</option>
                            <option>PNP - 0998-967-3262</option>
                            <option>PNP - 0948-552-4384</option>
                            <option>BFP - 0933-990-9960</option>
                            <option>BFP - 0963-363-6212</option>
                        </select>
                        <label for="reportDetails">What happened?</label>
                        <textarea id="reportDetails" name="details" rows="3" placeholder="Add a short note" required></textarea>
                        <p class="contacts-dialog-notice" role="status">Preview only. This report will not be sent or stored.</p>
                        <button class="contacts-primary-button" type="submit">Preview report</button>
                    </form>
                </dialog>

                <dialog class="contacts-dialog" id="addContactDialog" aria-labelledby="addContactTitle">
                    <form method="dialog" class="contacts-dialog-close-form"><button class="contacts-dialog-close" aria-label="Close dialog">&times;</button></form>
                    <div class="contacts-dialog-heading">
                        <div><h2 id="addContactTitle">Suggest a custom contact</h2><p>Preview the contact details before sharing with your team.</p></div>
                    </div>
                    <form method="dialog" class="contacts-dialog-form" data-preview-form="contact">
                        <label for="customContactName">Contact or agency name</label>
                        <input id="customContactName" name="name" type="text" maxlength="100" placeholder="e.g. Community Rescue Team" required>
                        <label for="customContactPhone">Phone number</label>
                        <input id="customContactPhone" name="phone" type="tel" maxlength="24" placeholder="e.g. 09XX-XXX-XXXX" required>
                        <label for="customContactCategory">Category</label>
                        <select id="customContactCategory" name="category" required>
                            <option value="">Choose a category</option>
                            <option>Medical</option><option>Security</option><option>Utilities</option><option>Disaster</option><option>Other</option>
                        </select>
                        <p class="contacts-dialog-notice" role="status">Preview only. This contact will not be saved or added to the hotline list.</p>
                        <button class="contacts-primary-button" type="submit">Preview contact</button>
                    </form>
                </dialog>
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