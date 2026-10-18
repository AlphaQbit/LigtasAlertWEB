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
            <svg class="contacts-icon-library" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
                <symbol id="contact-icon-heart-pulse" viewBox="0 0 24 24"><path d="M2 12h4l3-9 4 18 3-9h6"></path></symbol>
                <symbol id="contact-icon-zap" viewBox="0 0 24 24"><path d="M13 2 3 14h8l-1 8 11-12h-8l1-8z"></path></symbol>
                <symbol id="contact-icon-shield-plus" viewBox="0 0 24 24"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"></path><path d="M12 8v8"></path><path d="M8 12h8"></path></symbol>
                <symbol id="contact-icon-shield-check" viewBox="0 0 24 24"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"></path><path d="m9 12 2 2 4-4"></path></symbol>
                <symbol id="contact-icon-flame" viewBox="0 0 24 24"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.5 1-3 3.5-3 5.5a6 6 0 0 0 12 0c0-4-2.5-7-5-9-.5 3-2 4-2 6a2.5 2.5 0 0 1-1 2Z"></path></symbol>
                <symbol id="contact-icon-phone" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.2 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72l.45 2.98a2 2 0 0 1-.57 1.68L7.7 9.67a16 16 0 0 0 6 6l1.29-1.29a2 2 0 0 1 1.68-.57l2.98.45A2 2 0 0 1 22 16.92Z"></path></symbol>
                <symbol id="contact-icon-copy" viewBox="0 0 24 24"><rect x="8" y="8" width="13" height="13" rx="2"></rect><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"></path></symbol>
                <symbol id="contact-icon-book-open-check" viewBox="0 0 24 24"><path d="M12 7v14"></path><path d="M3 18V5a2 2 0 0 1 2-2h3a4 4 0 0 1 4 4 4 4 0 0 1 4-4h3a2 2 0 0 1 2 2v8"></path><path d="M3 18a2 2 0 0 0 2 2h3a4 4 0 0 1 4 1"></path><path d="m16 19 2 2 4-4"></path></symbol>
                <symbol id="contact-icon-clock" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></symbol>
                <symbol id="contact-icon-chevron-down" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"></path></symbol>
                <symbol id="contact-icon-shield-alert" viewBox="0 0 24 24"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"></path><path d="M12 8v4"></path><path d="M12 16h.01"></path></symbol>
                <symbol id="contact-icon-triangle-alert" viewBox="0 0 24 24"><path d="m10.3 3.9-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3.1l-8-14a2 2 0 0 0-3.4 0Z"></path><path d="M12 9v4"></path><path d="M12 17h.01"></path></symbol>
                <symbol id="contact-icon-user-plus" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="10" cy="7" r="4"></circle><path d="M20 8v6"></path><path d="M23 11h-6"></path></symbol>
                <symbol id="contact-icon-x" viewBox="0 0 24 24"><path d="m18 6-12 12"></path><path d="m6 6 12 12"></path></symbol>
            </svg>
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
                    <section class="responder-card" data-category="medical" data-search="mga center emergency hotline medical ambulance">
                        <div class="responder-heading">
                            <span class="responder-mark responder-mark-medical" aria-hidden="true"><svg><use href="#contact-icon-heart-pulse"></use></svg></span>
                            <div class="responder-info">
                                <h3 class="responder-name">MGA Center</h3>
                                <p class="responder-type">Medical Emergency Hotline</p>
                            </div>
                        </div>
                        <span class="availability-badge"><span aria-hidden="true"></span>24/7 Available</span>
                        <div class="responder-numbers">
                            <div class="responder-number-row">
                                <a class="responder-phone" href="tel:+639192426041">0919-242-6041</a>
                                <a class="contact-action contact-call" href="tel:+639192426041"><svg aria-hidden="true"><use href="#contact-icon-phone"></use></svg>Call Now</a>
                                <button class="contact-action contact-copy" type="button" data-copy-number="0919-242-6041"><svg aria-hidden="true"><use href="#contact-icon-copy"></use></svg>Copy Number</button>
                            </div>
                        </div>
                    </section>

                    <section class="responder-card" data-category="utilities" data-search="pelco ii electric cooperative power electricity utility">
                        <div class="responder-heading">
                            <span class="responder-mark responder-mark-utility" aria-hidden="true"><svg><use href="#contact-icon-zap"></use></svg></span>
                            <div class="responder-info">
                                <h3 class="responder-name">PELCO II</h3>
                                <p class="responder-type">Electric Cooperative</p>
                            </div>
                        </div>
                        <span class="availability-badge"><span aria-hidden="true"></span>24/7 Available</span>
                        <div class="responder-numbers">
                            <div class="responder-number-row">
                                <a class="responder-phone" href="tel:+639190590423">0919-059-0423</a>
                                <a class="contact-action contact-call" href="tel:+639190590423"><svg aria-hidden="true"><use href="#contact-icon-phone"></use></svg>Call Now</a>
                                <button class="contact-action contact-copy" type="button" data-copy-number="0919-059-0423"><svg aria-hidden="true"><use href="#contact-icon-copy"></use></svg>Copy Number</button>
                            </div>
                        </div>
                    </section>

                    <section class="responder-card" data-category="disaster" data-search="cdrrmo disaster risk reduction management rescue disaster response">
                        <div class="responder-heading">
                            <span class="responder-mark responder-mark-disaster" aria-hidden="true"><svg><use href="#contact-icon-shield-plus"></use></svg></span>
                            <div class="responder-info">
                                <h3 class="responder-name">CDRRMO</h3>
                                <p class="responder-type">Disaster Risk Reduction and Management</p>
                            </div>
                        </div>
                        <span class="availability-badge"><span aria-hidden="true"></span>24/7 Available</span>
                        <div class="responder-numbers">
                            <div class="responder-number-row">
                                <a class="responder-phone" href="tel:+639276117634">0927-611-7634</a>
                                <a class="contact-action contact-call" href="tel:+639276117634"><svg aria-hidden="true"><use href="#contact-icon-phone"></use></svg>Call Now</a>
                                <button class="contact-action contact-copy" type="button" data-copy-number="0927-611-7634"><svg aria-hidden="true"><use href="#contact-icon-copy"></use></svg>Copy Number</button>
                            </div>
                        </div>
                    </section>

                    <section class="responder-card" data-category="security" data-search="pnp philippine national police security police">
                        <div class="responder-heading">
                            <span class="responder-mark responder-mark-security" aria-hidden="true"><svg><use href="#contact-icon-shield-check"></use></svg></span>
                            <div class="responder-info">
                                <h3 class="responder-name">PNP</h3>
                                <p class="responder-type">Philippine National Police</p>
                            </div>
                        </div>
                        <span class="availability-badge"><span aria-hidden="true"></span>24/7 Available</span>
                        <div class="responder-numbers">
                            <div class="responder-number-row">
                                <a class="responder-phone" href="tel:+639989673262">0998-967-3262</a>
                                <a class="contact-action contact-call" href="tel:+639989673262"><svg aria-hidden="true"><use href="#contact-icon-phone"></use></svg>Call Now</a>
                                <button class="contact-action contact-copy" type="button" data-copy-number="0998-967-3262"><svg aria-hidden="true"><use href="#contact-icon-copy"></use></svg>Copy Number</button>
                            </div>
                            <div class="responder-number-row">
                                <a class="responder-phone" href="tel:+639485524384">0948-552-4384</a>
                                <a class="contact-action contact-call" href="tel:+639485524384"><svg aria-hidden="true"><use href="#contact-icon-phone"></use></svg>Call Now</a>
                                <button class="contact-action contact-copy" type="button" data-copy-number="0948-552-4384"><svg aria-hidden="true"><use href="#contact-icon-copy"></use></svg>Copy Number</button>
                            </div>
                        </div>
                    </section>

                    <section class="responder-card" data-category="fire" data-search="bfp bureau fire protection fire">
                        <div class="responder-heading">
                            <span class="responder-mark responder-mark-fire" aria-hidden="true"><svg><use href="#contact-icon-flame"></use></svg></span>
                            <div class="responder-info">
                                <h3 class="responder-name">BFP</h3>
                                <p class="responder-type">Bureau of Fire Protection</p>
                            </div>
                        </div>
                        <span class="availability-badge"><span aria-hidden="true"></span>24/7 Available</span>
                        <div class="responder-numbers">
                            <div class="responder-number-row">
                                <a class="responder-phone" href="tel:+639339909960">0933-990-9960</a>
                                <a class="contact-action contact-call" href="tel:+639339909960"><svg aria-hidden="true"><use href="#contact-icon-phone"></use></svg>Call Now</a>
                                <button class="contact-action contact-copy" type="button" data-copy-number="0933-990-9960"><svg aria-hidden="true"><use href="#contact-icon-copy"></use></svg>Copy Number</button>
                            </div>
                            <div class="responder-number-row">
                                <a class="responder-phone" href="tel:+639633636212">0963-363-6212</a>
                                <a class="contact-action contact-call" href="tel:+639633636212"><svg aria-hidden="true"><use href="#contact-icon-phone"></use></svg>Call Now</a>
                                <button class="contact-action contact-copy" type="button" data-copy-number="0963-363-6212"><svg aria-hidden="true"><use href="#contact-icon-copy"></use></svg>Copy Number</button>
                            </div>
                        </div>
                    </section>
                </div>
                <p class="contacts-empty" id="contactsEmpty" role="status" hidden>No hotlines in this category.</p>
            </div>

            <section class="guidelines-section" aria-labelledby="guidelinesTitle">
                <div class="guidelines-heading">
                    <div>
                        <h2 class="section-title" id="guidelinesTitle"><svg aria-hidden="true"><use href="#contact-icon-book-open-check"></use></svg>Emergency Action Guidelines</h2>
                        <p class="contacts-description">A quick reference while help is on the way.</p>
                    </div>
                    <span class="guidelines-note"><svg aria-hidden="true"><use href="#contact-icon-clock"></use></svg>Stay calm. Stay safe.</span>
                </div>
                <div class="guidelines-grid">
                    <details class="guideline-card">
                        <summary><span class="guideline-icon guideline-fire"><svg aria-hidden="true"><use href="#contact-icon-flame"></use></svg></span><span class="guideline-title">Fire outbreak <small>Call BFP</small></span><svg class="guideline-chevron" aria-hidden="true"><use href="#contact-icon-chevron-down"></use></svg></summary>
                        <div class="guideline-content"><ol><li>Warn people nearby and leave using the nearest safe exit.</li><li>Stay low under smoke and do not use elevators.</li><li>Call BFP from a safe location. Never re-enter a burning building.</li></ol></div>
                    </details>
                    <details class="guideline-card">
                        <summary><span class="guideline-icon guideline-utility"><svg aria-hidden="true"><use href="#contact-icon-zap"></use></svg></span><span class="guideline-title">Power interruption or hazard <small>Call PELCO II</small></span><svg class="guideline-chevron" aria-hidden="true"><use href="#contact-icon-chevron-down"></use></svg></summary>
                        <div class="guideline-content"><ol><li>Stay clear of downed wires and damaged electrical equipment.</li><li>Keep others away from the hazard and move to a safe location.</li><li>Call PELCO II to report the outage or electrical danger.</li></ol></div>
                    </details>
                    <details class="guideline-card">
                        <summary><span class="guideline-icon guideline-medical"><svg aria-hidden="true"><use href="#contact-icon-heart-pulse"></use></svg></span><span class="guideline-title">Medical emergency <small>Call MGA Center</small></span><svg class="guideline-chevron" aria-hidden="true"><use href="#contact-icon-chevron-down"></use></svg></summary>
                        <div class="guideline-content"><ol><li>Check that the scene is safe before approaching.</li><li>Call MGA Center and share your exact location and the person’s condition.</li><li>Follow the dispatcher’s instructions; avoid moving someone with a serious injury.</li></ol></div>
                    </details>
                    <details class="guideline-card">
                        <summary><span class="guideline-icon guideline-security"><svg aria-hidden="true"><use href="#contact-icon-shield-alert"></use></svg></span><span class="guideline-title">Security threat <small>Call PNP</small></span><svg class="guideline-chevron" aria-hidden="true"><use href="#contact-icon-chevron-down"></use></svg></summary>
                        <div class="guideline-content"><ol><li>Move away from danger and avoid confronting the threat.</li><li>Call PNP when safe and share your location and observations.</li><li>Follow directions from emergency responders.</li></ol></div>
                    </details>
                    <details class="guideline-card">
                        <summary><span class="guideline-icon guideline-disaster"><svg aria-hidden="true"><use href="#contact-icon-shield-plus"></use></svg></span><span class="guideline-title">Disaster or evacuation <small>Call CDRRMO</small></span><svg class="guideline-chevron" aria-hidden="true"><use href="#contact-icon-chevron-down"></use></svg></summary>
                        <div class="guideline-content"><ol><li>Follow official evacuation instructions and use marked safe routes.</li><li>Bring essential medication and keep family members together.</li><li>Call CDRRMO for local disaster assistance or rescue.</li></ol></div>
                    </details>
                </div>
            </section>

            <section class="contacts-admin-actions" aria-label="Contact administration">
                <div>
                    <h2>Keep hotline information current (TEST ONLY)</h2>
                    <p>Flag a number that needs checking or prepare a contact suggestion.</p>
                </div>
                <div class="contacts-admin-buttons">
                    <button class="contacts-secondary-button" type="button" data-open-contact-dialog="reportHotlineDialog"><svg aria-hidden="true"><use href="#contact-icon-triangle-alert"></use></svg>Report unresponsive hotline</button>
                    <button class="contacts-primary-button" type="button" data-open-contact-dialog="addContactDialog"><svg aria-hidden="true"><use href="#contact-icon-user-plus"></use></svg>Add custom contact</button>
                </div>
                <p class="contacts-demo-note">Admin actions are a preview only; changes are not saved or submitted.</p>
            </section>

            <dialog class="contacts-dialog" id="reportHotlineDialog" aria-labelledby="reportHotlineTitle">
                <form method="dialog" class="contacts-dialog-close-form"><button class="contacts-dialog-close" aria-label="Close dialog"><svg aria-hidden="true"><use href="#contact-icon-x"></use></svg></button></form>
                <div class="contacts-dialog-heading">
                    <span class="contacts-dialog-icon"><svg aria-hidden="true"><use href="#contact-icon-triangle-alert"></use></svg></span>
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
                <form method="dialog" class="contacts-dialog-close-form"><button class="contacts-dialog-close" aria-label="Close dialog"><svg aria-hidden="true"><use href="#contact-icon-x"></use></svg></button></form>
                <div class="contacts-dialog-heading">
                    <span class="contacts-dialog-icon"><svg aria-hidden="true"><use href="#contact-icon-user-plus"></use></svg></span>
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