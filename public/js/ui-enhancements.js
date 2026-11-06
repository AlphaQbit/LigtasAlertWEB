/* =========================================================
   LigtasAlert Admin – UI/UX enhancements (additive only)
   - Does NOT modify admin.js, routes, API calls or the database
   - Only READS the page (#activeCount, #alertsGrid, etc.) and adds
     new elements: alert banner, sound toggle, search/filter bars,
     "last change" pill, keyboard shortcuts.
   - Remove this one <script> tag to switch everything off.
   ========================================================= */
(function () {
    'use strict';
    if (window.__ligtasUi) return;
    window.__ligtasUi = true;

    var $ = function (s, r) { return (r || document).querySelector(s); };
    var $$ = function (s, r) { return [].slice.call((r || document).querySelectorAll(s)); };

    /* ---------- Styles (self-contained) ---------- */
    var style = document.createElement('style');
    style.textContent = [
        '.alert-card[hidden],.ui-banner[hidden],.ui-nomatch[hidden]{display:none!important}',
        '.ui-banner{display:flex;align-items:center;gap:.75rem;padding:.85rem 1.1rem;border-radius:16px;background:linear-gradient(160deg,#F0453F,#C9231E);color:#fff;box-shadow:0 14px 28px -16px rgba(229,50,45,.8)}',
        '.ui-banner-dot{flex:0 0 auto;width:.6rem;height:.6rem;border-radius:50%;background:#fff;animation:sirenPulse 1.6s ease-out infinite}',
        '.ui-banner-text{flex:1;font-family:var(--font-display);font-size:1.05rem;font-weight:700}',
        '.ui-banner-btn{padding:.5rem 1rem;border:0;border-radius:10px;background:#fff;color:var(--primary-hover);font:inherit;font-size:.85rem;font-weight:700;cursor:pointer}',
        '.ui-toolbar{display:flex;flex-wrap:wrap;align-items:center;gap:.6rem}',
        '.ui-search{flex:1 1 220px;max-width:340px;padding:.6rem .85rem;border:1px solid var(--border);border-radius:12px;background:#fff;color:var(--text-primary);font:inherit;font-size:.875rem}',
        '.ui-chips{display:flex;flex-wrap:wrap;gap:.35rem}',
        '.ui-chip{padding:.4rem .85rem;border:1px solid var(--border);border-radius:999px;background:#fff;color:var(--text-secondary);font:inherit;font-size:.8rem;font-weight:700;cursor:pointer;transition:background-color .2s,color .2s,border-color .2s}',
        '.ui-chip:hover{border-color:var(--ink);color:var(--text-primary)}',
        '.ui-chip.on{background:var(--ink);border-color:var(--ink);color:#fff}',
        '.ui-count{margin-left:auto;font-size:.8rem;color:var(--text-secondary)}',
        '.ui-nomatch{padding:1.25rem;text-align:center;border:1px dashed var(--border);border-radius:14px;background:rgba(255,255,255,.6);color:var(--text-secondary)}',
        '.ui-sound{padding:.5rem .9rem;border:1px solid var(--ink-line);border-radius:10px;background:transparent;color:rgba(255,255,255,.8);font:inherit;font-size:.82rem;font-weight:700;cursor:pointer;transition:all .2s}',
        '.ui-sound.on{background:rgba(34,197,94,.18);border-color:rgba(34,197,94,.45);color:#86EFAC}',
        '.ui-status{display:flex;align-items:center;gap:.4rem;font-size:.78rem;color:rgba(255,255,255,.6);white-space:nowrap}',
        '.ui-status i{width:.45rem;height:.45rem;border-radius:50%;background:#22C55E}',
        '@media (max-width:768px){.ui-status{display:none}.ui-banner{flex-wrap:wrap}}'
    ].join('');
    document.head.appendChild(style);

    function init() {
        var alertsGrid = $('#alertsGrid');
        var historyGrid = $('#historyGrid');
        var activityLog = $('#activityLog');
        var activeEl = $('#activeCount');
        var container = $('.container');
        var actions = $('.header-actions');
        if (!container) return;

        /* ---------- 1. Alert banner + sound + tab-title flash ---------- */
        var banner = document.createElement('div');
        banner.className = 'ui-banner';
        banner.hidden = true;
        banner.setAttribute('role', 'alert');
        banner.innerHTML = '<span class="ui-banner-dot"></span><strong class="ui-banner-text"></strong>' +
            '<button type="button" class="ui-banner-btn">View incidents</button>';
        container.prepend(banner);

        $('.ui-banner-btn', banner).addEventListener('click', function () {
            var tab = $('.nav-tab[data-tab="dashboard"]');
            if (tab && !tab.classList.contains('active')) tab.click();
            if (alertsGrid) alertsGrid.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        var soundOn = false;
        try { soundOn = localStorage.getItem('ligtas.sound') === '1'; } catch (e) {}
        var ctx = null;

        function beep() {
            if (!soundOn) return;
            try {
                ctx = ctx || new (window.AudioContext || window.webkitAudioContext)();
                if (ctx.state === 'suspended') ctx.resume();
                [0, 0.22, 0.44].forEach(function (t, i) {
                    var o = ctx.createOscillator(), g = ctx.createGain(), at = ctx.currentTime + t;
                    o.type = 'square';
                    o.frequency.value = i % 2 ? 660 : 880;
                    g.gain.setValueAtTime(0.0001, at);
                    g.gain.exponentialRampToValueAtTime(0.15, at + 0.02);
                    g.gain.exponentialRampToValueAtTime(0.0001, at + 0.18);
                    o.connect(g); g.connect(ctx.destination);
                    o.start(at); o.stop(at + 0.2);
                });
            } catch (e) {}
        }

        var soundBtn = document.createElement('button');
        soundBtn.type = 'button';
        soundBtn.className = 'ui-sound';
        function renderSound() {
            soundBtn.classList.toggle('on', soundOn);
            soundBtn.textContent = soundOn ? 'Sound on' : 'Sound off';
            soundBtn.setAttribute('aria-pressed', String(soundOn));
            soundBtn.title = 'Play a sound when a new active alert arrives';
        }
        soundBtn.addEventListener('click', function () {
            soundOn = !soundOn;
            try { localStorage.setItem('ligtas.sound', soundOn ? '1' : '0'); } catch (e) {}
            renderSound();
            beep(); // preview + satisfies the browser's "user gesture" rule
        });
        renderSound();
        if (actions) actions.prepend(soundBtn);

        var prev = null, baseTitle = document.title, flashTimer = null;

        function updateTitle(n) {
            clearInterval(flashTimer);
            document.title = baseTitle;
            if (n > 0 && document.hidden) {
                var on = false;
                flashTimer = setInterval(function () {
                    on = !on;
                    document.title = on ? '(' + n + ') Active alerts' : baseTitle;
                }, 1000);
            }
        }

        function onCount() {
            var n = parseInt(activeEl.textContent, 10);
            if (isNaN(n)) return;
            prev = n;
            banner.hidden = n < 1;
            $('.ui-banner-text', banner).textContent =
                n + (n === 1 ? ' active alert needs' : ' active alerts need') + ' attention';
            updateTitle(n);
        }

        if (activeEl) {
            new MutationObserver(onCount).observe(activeEl, { childList: true, characterData: true, subtree: true });
            onCount();
        }
        document.addEventListener('visibilitychange', function () { updateTitle(prev || 0); });

        // Beep only when a NEW active alert id appears (not when the facility filter changes)
        var seenIds = null, rebaseline = false;
        var facilitySelect = $('#facility');
        if (facilitySelect) facilitySelect.addEventListener('change', function () { rebaseline = true; });

        function checkNewAlerts() {
            var ids = {};
            $$('.alert-card.active [data-alert-id]', alertsGrid).forEach(function (b) {
                ids[b.getAttribute('data-alert-id')] = true;
            });
            if (seenIds !== null && !rebaseline) {
                var fresh = Object.keys(ids).some(function (id) { return !seenIds[id]; });
                if (fresh) beep();
            }
            rebaseline = false;
            seenIds = ids;
        }
        if (alertsGrid) {
            // first render after page load only sets the baseline (no beep)
            new MutationObserver(checkNewAlerts).observe(alertsGrid, { childList: true });
        }

        /* ---------- 2. "Last change" pill ---------- */
        var lastChange = Date.now();
        var status = document.createElement('div');
        status.className = 'ui-status';
        status.title = 'Time since the dashboard last received data';
        status.innerHTML = '<i></i><span>Updated just now</span>';
        if (actions) actions.prepend(status);

        function touch() { lastChange = Date.now(); }
        [alertsGrid, activityLog, activeEl].forEach(function (el) {
            if (el) new MutationObserver(touch).observe(el, { childList: true, characterData: true, subtree: true });
        });
        setInterval(function () {
            var s = Math.floor((Date.now() - lastChange) / 1000);
            $('span', status).textContent = 'Updated ' + (s < 10 ? 'just now' : s < 60 ? s + 's ago' : Math.floor(s / 60) + 'm ago');
        }, 5000);

        /* ---------- 3. Search + status filter ---------- */
        function buildToolbar(grid, withChips, placeholder) {
            if (!grid) return null;
            var q = '', s = 'all';
            var bar = document.createElement('div');
            bar.className = 'ui-toolbar';
            var html = '<input type="search" class="ui-search" placeholder="' + placeholder + '" aria-label="' + placeholder + '">';
            if (withChips) {
                html += '<div class="ui-chips" role="group" aria-label="Filter by status">' +
                    ['all', 'active', 'acknowledged', 'responding'].map(function (v, i) {
                        return '<button type="button" class="ui-chip' + (i ? '' : ' on') + '" data-s="' + v + '">' +
                            v.charAt(0).toUpperCase() + v.slice(1) + '</button>';
                    }).join('') + '</div>';
            }
            html += '<span class="ui-count" aria-live="polite"></span>';
            bar.innerHTML = html;
            grid.before(bar);

            var none = document.createElement('div');
            none.className = 'ui-nomatch';
            none.hidden = true;
            none.textContent = 'No incidents match your search or filter.';
            bar.after(none);

            var input = $('.ui-search', bar), count = $('.ui-count', bar);

            function apply() {
                var cards = $$('.alert-card', grid), shown = 0;
                cards.forEach(function (c) {
                    var text = c.textContent.toLowerCase();
                    var okSearch = !q || text.indexOf(q) > -1;
                    var okStatus = s === 'all' || c.classList.contains(s) || !!c.querySelector('.badge.' + s) ||
                        (s === 'responding' && text.indexOf('responding') > -1);
                    c.hidden = !(okSearch && okStatus);
                    if (!c.hidden) shown++;
                });
                count.textContent = cards.length ? 'Showing ' + shown + ' of ' + cards.length : '';
                none.hidden = !(cards.length && !shown);
            }

            input.addEventListener('input', function () { q = input.value.trim().toLowerCase(); apply(); });
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') { input.value = ''; q = ''; apply(); input.blur(); }
            });
            $$('.ui-chip', bar).forEach(function (chip) {
                chip.addEventListener('click', function () {
                    s = chip.getAttribute('data-s');
                    $$('.ui-chip', bar).forEach(function (c) { c.classList.toggle('on', c === chip); });
                    apply();
                });
            });
            // Re-apply whenever the existing script redraws the cards
            new MutationObserver(apply).observe(grid, { childList: true });
            apply();
            return input;
        }

        buildToolbar(alertsGrid, true, 'Search incidents  ( / )');
        buildToolbar(historyGrid, false, 'Search resolved incidents  ( / )');

        /* ---------- 4. Keyboard shortcuts ---------- */
        var tabs = $$('.nav-tab');
        tabs.forEach(function (t, i) { t.title = 'Shortcut: ' + (i + 1); });

        document.addEventListener('keydown', function (e) {
            if (e.ctrlKey || e.metaKey || e.altKey) return;
            var t = e.target;
            if (t && (/^(input|textarea|select)$/i.test(t.tagName) || t.isContentEditable)) return;
            if ($('dialog[open]')) return;

            if (e.key >= '1' && e.key <= '4') {
                var tab = tabs[Number(e.key) - 1];
                if (tab) tab.click();
            } else if (e.key === '/') {
                var visible = $$('.ui-search').filter(function (i) { return i.offsetParent !== null; })[0];
                if (visible) { e.preventDefault(); visible.focus(); }
            }
        });

        /* ---------- 5. Hotline carousel: swipe, arrows, dots, auto-scroll ---------- */
        var track = $('#responderGrid');
        if (track) {
            var reduce = !!(window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches);
            var autoOn = true, hovering = false, focused = false, holdUntil = 0;
            var scrollMode = reduce ? 'auto' : 'smooth'; // reduced-motion users still advance, just without animation
            var dragging = false, moved = false, startX = 0, startLeft = 0;

            track.setAttribute('role', 'region');
            track.setAttribute('tabindex', '0');
            track.setAttribute('aria-label', 'Emergency hotlines. Swipe or use the arrow buttons.');

            var cbar = document.createElement('div');
            cbar.className = 'hotline-bar';
            cbar.hidden = true;
            cbar.innerHTML =
                '<button type="button" class="hotline-auto" aria-pressed="' + autoOn + '">' + (autoOn ? 'Auto-scroll on' : 'Auto-scroll off') + '</button>' +
                '<div class="hotline-dots"></div>' +
                '<div class="hotline-arrows">' +
                '<button type="button" class="hotline-arrow" data-dir="-1" aria-label="Previous hotline">&lsaquo;</button>' +
                '<button type="button" class="hotline-arrow" data-dir="1" aria-label="Next hotline">&rsaquo;</button>' +
                '</div>';
            track.insertAdjacentElement('afterend', cbar);

            var dotsEl = $('.hotline-dots', cbar);
            var autoBtn = $('.hotline-auto', cbar);
            var prevBtn = $('[data-dir="-1"]', cbar);
            var nextBtn = $('[data-dir="1"]', cbar);

            var cardsList = function () { return $$('.responder-card', track).filter(function (c) { return !c.hidden; }); };
            var maxScroll = function () { return track.scrollWidth - track.clientWidth; };
            var atEnd = function () { return track.scrollLeft >= maxScroll() - 2; };

            function currentIndex() {
                var cs = cardsList();
                if (!cs.length) return 0;
                if (maxScroll() > 4 && atEnd()) return cs.length - 1;
                var best = 0, bestDist = Infinity;
                cs.forEach(function (c, i) {
                    var d = Math.abs(c.offsetLeft - track.scrollLeft);
                    if (d < bestDist) { bestDist = d; best = i; }
                });
                return best;
            }

            function goTo(i) {
                var cs = cardsList();
                if (!cs.length) return;
                i = Math.max(0, Math.min(cs.length - 1, i));
                track.scrollTo({ left: cs[i].offsetLeft, behavior: scrollMode });
            }

            function update() {
                var i = currentIndex();
                $$('.hotline-dot', dotsEl).forEach(function (d, k) { d.classList.toggle('on', k === i); });
                prevBtn.disabled = track.scrollLeft <= 2;
                nextBtn.disabled = atEnd();
            }

            function render() {
                var cs = cardsList();
                cbar.hidden = !cs.length || maxScroll() <= 4;
                dotsEl.innerHTML = '';
                cs.forEach(function (c, i) {
                    var d = document.createElement('button');
                    d.type = 'button';
                    d.className = 'hotline-dot';
                    d.setAttribute('aria-label', 'Go to hotline ' + (i + 1));
                    d.addEventListener('click', function () { holdUntil = Date.now() + 8000; goTo(i); });
                    dotsEl.appendChild(d);
                });
                update();
            }

            var ticking = false;
            track.addEventListener('scroll', function () {
                if (ticking) return;
                ticking = true;
                requestAnimationFrame(function () { ticking = false; update(); });
            }, { passive: true });

            [prevBtn, nextBtn].forEach(function (b) {
                b.addEventListener('click', function () {
                    holdUntil = Date.now() + 8000;
                    goTo(currentIndex() + Number(b.getAttribute('data-dir')));
                });
            });

            autoBtn.addEventListener('click', function () {
                autoOn = !autoOn;
                autoBtn.setAttribute('aria-pressed', String(autoOn));
                autoBtn.textContent = autoOn ? 'Auto-scroll on' : 'Auto-scroll off';
            });

            // Pause auto-scroll while the admin is interacting
            [track, cbar].forEach(function (el) {
                // mouse hover only (touch taps must not leave it "stuck" paused)
                el.addEventListener('pointerenter', function (e) { if (e.pointerType === 'mouse') hovering = true; });
                el.addEventListener('pointerleave', function () { hovering = false; });
                // keyboard focus only (clicking a button with the mouse must not pause forever)
                el.addEventListener('focusin', function (e) { focused = !!(e.target.matches && e.target.matches(':focus-visible')); });
                el.addEventListener('focusout', function () { focused = false; });
                el.addEventListener('touchstart', function () { holdUntil = Date.now() + 8000; }, { passive: true });
                el.addEventListener('touchend', function () { holdUntil = Date.now() + 8000; }, { passive: true });
            });

            // Mouse drag-to-swipe (touch screens already swipe natively)
            track.addEventListener('pointerdown', function (e) {
                if (e.pointerType !== 'mouse' || e.button !== 0) return;
                dragging = true; moved = false; startX = e.clientX; startLeft = track.scrollLeft;
            });
            window.addEventListener('pointermove', function (e) {
                if (!dragging) return;
                var dx = e.clientX - startX;
                if (Math.abs(dx) > 5) { moved = true; track.classList.add('is-dragging'); }
                if (moved) track.scrollLeft = startLeft - dx;
            });
            window.addEventListener('pointerup', function () {
                if (!dragging) return;
                dragging = false;
                track.classList.remove('is-dragging');
                if (moved) { holdUntil = Date.now() + 8000; goTo(currentIndex()); }
                setTimeout(function () { moved = false; }, 0);
            });
            track.addEventListener('click', function (e) {
                if (moved) { e.preventDefault(); e.stopPropagation(); }
            }, true);

            // Auto-scroll: one card every 4.5s, wraps back to the first
            setInterval(function () {
                if (!autoOn || hovering || focused || dragging || document.hidden) return;
                if (Date.now() < holdUntil || track.offsetParent === null || cbar.hidden) return;
                if (atEnd()) track.scrollTo({ left: 0, behavior: scrollMode });
                else goTo(currentIndex() + 1);
            }, 4500);

            // Re-measure when the tab opens, the window resizes, or the category filter changes
            if (window.ResizeObserver) new ResizeObserver(render).observe(track);
            window.addEventListener('resize', render);
            new MutationObserver(function () { track.scrollLeft = 0; render(); })
                .observe(track, { attributes: true, subtree: true, attributeFilter: ['hidden'] });
            render();
        }

        /* ---------- 6. Login-matching wordmark + light/dark toggle ---------- */
        var titleEl = $('.header-title');
        if (titleEl && !$('.brand-word', titleEl)) {
            [].slice.call(titleEl.childNodes).forEach(function (node) {
                if (node.nodeType === 3 && /Ligtas\s*Alert/i.test(node.textContent)) {
                    var word = document.createElement('span');
                    word.className = 'brand-word';
                    word.innerHTML = '<span class="bt-light">Ligtas</span><span class="bt-accent">Alert</span>';
                    var badge = document.createElement('span');
                    badge.className = 'brand-badge';
                    badge.innerHTML = '<i aria-hidden="true"></i>Admin';
                    node.replaceWith(word);
                    word.after(badge);
                }
            });
        }

        if (actions && !$('#theme-toggle')) {
            var root = document.documentElement;
            var toggle = document.createElement('button');
            toggle.type = 'button';
            toggle.id = 'theme-toggle';
            toggle.className = 'theme-toggle';
            toggle.title = 'Switch theme';
            toggle.innerHTML =
                '<span class="tt-thumb" aria-hidden="true"></span>' +
                '<svg class="sun" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4l1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>' +
                '<svg class="moon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z"/></svg>';
            var avatarEl = $('.admin-profile-avatar', actions);
            actions.insertBefore(toggle, avatarEl || $('form', actions));

            var syncToggle = function () {
                var dark = root.getAttribute('data-theme') === 'dark';
                toggle.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
                toggle.setAttribute('aria-pressed', String(dark));
            };
            syncToggle();
            toggle.addEventListener('click', function () {
                var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                root.classList.add('theme-anim');
                root.setAttribute('data-theme', next);
                try { localStorage.setItem('ligtas-theme', next); } catch (e) {}
                syncToggle();
                setTimeout(function () { root.classList.remove('theme-anim'); }, 450);
            });
        }
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();