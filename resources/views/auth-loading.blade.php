@if (session('auth_loading'))
<style>
    #ligtas-loader {
        --ll-red: #ff6974;
        --ll-red-deep: #e63946;
        --ll-ink: #0b1323;
        --ll-text: #f4f7fc;
        --ll-muted: #acb9cc;
        --ll-size: 280px;

        position: fixed;
        inset: 0;
        z-index: 99999;
        display: grid;
        place-items: center;
        overflow: auto;
        padding: 24px;
        color: var(--ll-text);
        background: var(--ll-ink);
        font-family: "Segoe UI", system-ui, sans-serif;
        transition: opacity .55s ease .15s, visibility .7s ease;
    }

    #ligtas-loader.is-finished {
        opacity: 0;
        visibility: hidden;
        pointer-events: none;
    }

    /* ---------- backdrop ---------- */
    .ll-bg,
    .ll-grid,
    .ll-glow {
        position: absolute;
        inset: 0;
        pointer-events: none;
    }

    .ll-bg {
        overflow: hidden;
    }

    .ll-grid {
        background-image:
            linear-gradient(#ffffff08 1px, transparent 1px),
            linear-gradient(90deg, #ffffff08 1px, transparent 1px);
        background-size: 48px 48px;
        -webkit-mask-image: radial-gradient(circle at center, #000 0%, transparent 70%);
        mask-image: radial-gradient(circle at center, #000 0%, transparent 70%);
        animation: ll-grid-drift 14s linear infinite;
    }

    .ll-glow {
        background: radial-gradient(
            ellipse at center,
            rgba(230, 57, 70, .22),
            transparent 62%
        );
        animation: ll-breathe 3.6s ease-in-out infinite;
    }

    /* ---------- layout ---------- */
    .ll-content {
        position: relative;
        width: min(100%, 440px);
        text-align: center;
        animation: ll-enter .8s cubic-bezier(.2, .8, .2, 1) both;
        transition: transform .7s cubic-bezier(.6, 0, .2, 1);
    }

    #ligtas-loader.is-finished .ll-content {
        transform: scale(1.06);
    }

    .ll-campus {
        margin: 0 0 22px;
        color: #a6b5ca;
        font-size: 11px;
        letter-spacing: .18em;
    }

    /* ---------- radar ---------- */
    .ll-radar {
        position: relative;
        width: var(--ll-size);
        height: var(--ll-size);
        margin: 0 auto;
    }

    .ll-radar > * {
        position: absolute;
    }

    /* concentric range rings */
    .ll-rings {
        inset: 0;
        border-radius: 50%;
        background:
            radial-gradient(circle,
                transparent 0 33%, #ffffff12 33.4% 34%,
                transparent 34.4% 55%, #ffffff12 55.4% 56%,
                transparent 56.4% 77%, #ffffff12 77.4% 78%,
                transparent 78.4% 99%, #ffffff22 99.4% 100%);
    }

    /* crosshair */
    .ll-rings::before,
    .ll-rings::after {
        content: "";
        position: absolute;
        background: #ffffff0d;
    }

    .ll-rings::before {
        left: 50%;
        top: 0;
        bottom: 0;
        width: 1px;
    }

    .ll-rings::after {
        top: 50%;
        left: 0;
        right: 0;
        height: 1px;
    }

    /* tick marks around the edge */
    .ll-ticks {
        inset: -9px;
        border-radius: 50%;
        background: repeating-conic-gradient(#ffffff30 0 .6deg, transparent .6deg 6deg);
        -webkit-mask-image: radial-gradient(circle, transparent 0 94%, #000 94.5%);
        mask-image: radial-gradient(circle, transparent 0 94%, #000 94.5%);
        animation: ll-spin 40s linear infinite reverse;
    }

    /* the sweeping beam */
    .ll-sweep {
        inset: 0;
        border-radius: 50%;
        background: conic-gradient(
            from 0deg,
            transparent 0deg 250deg,
            rgba(255, 105, 116, .05) 285deg,
            rgba(255, 105, 116, .42) 360deg
        );
        -webkit-mask-image: radial-gradient(circle, #000 0 99%, transparent 100%);
        mask-image: radial-gradient(circle, #000 0 99%, transparent 100%);
        animation: ll-spin 3s linear infinite;
    }

    /* signal waves radiating out of the logo */
    .ll-wave {
        inset: 50px;
        border: 1.5px solid #ff697470;
        border-radius: 50%;
        opacity: 0;
        animation: ll-wave 3.2s cubic-bezier(.2, .6, .3, 1) infinite;
    }

    .ll-wave:nth-of-type(2) { animation-delay: 1.07s; }
    .ll-wave:nth-of-type(3) { animation-delay: 2.14s; }

    /* orbiting satellite */
    .ll-orbit {
        inset: 14px;
        border-radius: 50%;
        animation: ll-spin 5.5s linear infinite;
    }

    .ll-orbit::before {
        content: "";
        position: absolute;
        top: -4px;
        left: calc(50% - 4px);
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--ll-red);
        box-shadow: 0 0 12px 3px rgba(255, 105, 116, .8);
    }

    /* blips that light up as the beam passes (3s period, angle/360*3s delay) */
    .ll-blip {
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        transform: rotate(var(--a)) translateY(calc(var(--r) * -1));
    }

    .ll-blip span {
        position: absolute;
        top: -4px;
        left: -4px;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #ffd9dc;
        box-shadow: 0 0 10px 2px rgba(255, 105, 116, .9);
        opacity: 0;
        animation: ll-blip 3s ease-out infinite;
        animation-delay: calc(var(--a-num) / 360 * 3s);
    }

    /* the logo */
    .ll-logo {
        top: 50%;
        left: 50%;
        display: grid;
        place-items: center;
        width: 132px;
        height: 132px;
        margin: -66px 0 0 -66px;
        border: 1px solid #ffffff2b;
        border-radius: 34px;
        background: linear-gradient(145deg, #ffffff1f, #ffffff06);
        -webkit-backdrop-filter: blur(6px);
        backdrop-filter: blur(6px);
        animation: ll-logo-pulse 2.4s ease-in-out infinite;
    }

    .ll-logo img {
        display: block;
        width: 100px;
        height: 100px;
        object-fit: contain;
        animation: ll-logo-pop .9s cubic-bezier(.2, 1.3, .3, 1) both .1s;
    }

    /* ---------- text ---------- */
    .ll-brand {
        margin: 26px 0 18px;
        font-size: 36px;
        font-weight: 800;
        letter-spacing: -1.6px;
    }

    .ll-brand span {
        color: var(--ll-red);
    }

    .ll-content h2 {
        margin: 0 0 8px;
        font-size: 22px;
        font-weight: 600;
    }

    #ll-message {
        min-height: 24px;
        margin: 0;
        color: var(--ll-muted);
        font-size: 14px;
        line-height: 1.7;
    }

    #ll-message.is-swapping {
        animation: ll-text-swap .45s ease both;
    }

    /* ---------- progress ---------- */
    .ll-progress {
        display: flex;
        align-items: center;
        gap: 12px;
        width: min(100%, 260px);
        margin: 24px auto 26px;
    }

    .ll-track {
        position: relative;
        flex: 1;
        height: 4px;
        overflow: hidden;
        border-radius: 99px;
        background: #ffffff14;
    }

    .ll-bar {
        position: absolute;
        inset: 0 auto 0 0;
        width: 0%;
        border-radius: inherit;
        background: linear-gradient(90deg, #b91c2c, var(--ll-red), #ffb3b9);
        box-shadow: 0 0 12px rgba(255, 105, 116, .7);
        transition: width .35s ease-out;
    }

    .ll-bar::after {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, transparent, #ffffffaa, transparent);
        transform: translateX(-100%);
        animation: ll-shine 1.4s ease-in-out infinite;
    }

    #ll-percent {
        min-width: 38px;
        color: var(--ll-muted);
        font-size: 12px;
        font-variant-numeric: tabular-nums;
        text-align: right;
    }

    .ll-footer {
        margin: 0;
        color: #8191a8;
        font-size: 12px;
    }

    #ll-dismiss {
        margin-top: 22px;
        padding: 10px 18px;
        color: #fff;
        background: #ffffff10;
        border: 1px solid #ffffff40;
        border-radius: 10px;
        cursor: pointer;
        font: inherit;
        font-size: 13px;
        transition: background .2s ease, border-color .2s ease;
    }

    #ll-dismiss:hover {
        background: #ffffff1c;
        border-color: #ff6974;
    }

    #ll-dismiss[hidden] {
        display: none;
    }

    #ll-dismiss:focus-visible {
        outline: 2px solid var(--ll-red);
        outline-offset: 4px;
    }

    /* ---------- security chip + checklist ---------- */
    .ll-secure {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        margin: 0 0 16px;
        padding: 6px 12px 6px 10px;
        color: #8ff0b0;
        background: #3ddc8414;
        border: 1px solid #3ddc8440;
        border-radius: 99px;
        font-size: 12px;
    }

    .ll-secure svg {
        width: 14px;
        height: 14px;
        flex: none;
    }

    .ll-secure i {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #3ddc84;
        box-shadow: 0 0 8px #3ddc84;
        animation: ll-live 1.6s ease-in-out infinite;
    }

    .ll-checks {
        display: grid;
        gap: 9px;
        width: min(100%, 290px);
        margin: 22px auto 0;
        padding: 0;
        list-style: none;
        text-align: left;
    }

    .ll-checks li {
        display: flex;
        align-items: center;
        gap: 11px;
        color: #6f7f96;
        font-size: 13px;
        transition: color .3s ease;
    }

    .ll-dot {
        position: relative;
        flex: none;
        width: 18px;
        height: 18px;
        border: 1.5px solid #ffffff30;
        border-radius: 50%;
        transition: background .3s ease, border-color .3s ease, box-shadow .3s ease;
    }

    .ll-checks li.is-active {
        color: var(--ll-text);
    }

    .ll-checks li.is-active .ll-dot {
        border-color: var(--ll-red) transparent var(--ll-red) var(--ll-red);
        animation: ll-spin .8s linear infinite;
    }

    .ll-checks li.is-done {
        color: #b9c6d8;
    }

    .ll-checks li.is-done .ll-dot {
        background: #3ddc84;
        border-color: #3ddc84;
        box-shadow: 0 0 12px #3ddc8470;
        animation: ll-tick .4s cubic-bezier(.2, 1.4, .4, 1);
    }

    .ll-checks li.is-done .ll-dot::after {
        content: "";
        position: absolute;
        left: 5px;
        top: 2px;
        width: 4px;
        height: 8px;
        border: solid #06210f;
        border-width: 0 2px 2px 0;
        transform: rotate(45deg);
    }

    #ligtas-loader.is-ready .ll-secure {
        color: #b6ffd0;
        background: #3ddc8426;
    }

    @keyframes ll-live {
        50% { opacity: .35; }
    }

    @keyframes ll-tick {
        from { transform: scale(.5); }
        to   { transform: scale(1); }
    }

    /* ---------- finish burst ---------- */
    #ligtas-loader.is-ready .ll-sweep {
        animation-duration: .8s;
    }

    #ligtas-loader.is-finished .ll-logo {
        animation: none;
        transform: scale(1.12);
        transition: transform .6s ease;
    }

    /* ---------- keyframes ---------- */
    @keyframes ll-enter {
        from { opacity: 0; transform: translateY(14px) scale(.96); }
        to   { opacity: 1; transform: none; }
    }

    @keyframes ll-spin {
        to { transform: rotate(360deg); }
    }

    @keyframes ll-wave {
        0%   { transform: scale(.55); opacity: 0; }
        15%  { opacity: .9; }
        100% { transform: scale(1.55); opacity: 0; }
    }

    @keyframes ll-blip {
        0%   { opacity: 1; transform: scale(1.6); }
        45%  { opacity: .5; transform: scale(1); }
        100% { opacity: 0; transform: scale(.6); }
    }

    @keyframes ll-logo-pulse {
        0%, 100% {
            transform: translateY(0) scale(1);
            box-shadow: 0 20px 60px #0006, 0 0 28px #e6394630;
        }
        50% {
            transform: translateY(-4px) scale(1.04);
            box-shadow: 0 26px 70px #0006, 0 0 60px #e6394670;
        }
    }

    @keyframes ll-logo-pop {
        from { opacity: 0; transform: scale(.4) rotate(-12deg); }
        to   { opacity: 1; transform: none; }
    }

    @keyframes ll-shine {
        to { transform: translateX(100%); }
    }

    @keyframes ll-breathe {
        0%, 100% { opacity: .65; transform: scale(1); }
        50%      { opacity: 1;   transform: scale(1.12); }
    }

    @keyframes ll-grid-drift {
        to { background-position: 48px 48px; }
    }

    @keyframes ll-text-swap {
        from { opacity: 0; transform: translateY(6px); }
        to   { opacity: 1; transform: none; }
    }

    @media (max-width: 420px) {
        #ligtas-loader { --ll-size: 230px; }
        .ll-logo {
            width: 110px;
            height: 110px;
            margin: -55px 0 0 -55px;
            border-radius: 28px;
        }
        .ll-logo img { width: 82px; height: 82px; }
        .ll-brand { font-size: 31px; }
    }

    @media (prefers-reduced-motion: reduce) {
        #ligtas-loader,
        #ligtas-loader * {
            animation: none !important;
            transition: none !important;
        }

        .ll-blip span { opacity: .8; }
        .ll-sweep { opacity: .5; }
    }
</style>

<div
    id="ligtas-loader"
    role="dialog"
    aria-modal="true"
    aria-labelledby="ll-title"
    aria-describedby="ll-message"
    tabindex="-1"
>
    <div class="ll-bg" aria-hidden="true">
        <div class="ll-glow"></div>
        <div class="ll-grid"></div>
    </div>

    <div class="ll-content">
        <p class="ll-campus">Mabalacat City College</p>

        <div class="ll-radar" aria-hidden="true">
            <div class="ll-rings"></div>
            <div class="ll-ticks"></div>
            <div class="ll-sweep"></div>

            <span class="ll-wave"></span>
            <span class="ll-wave"></span>
            <span class="ll-wave"></span>

            <div class="ll-blip" style="--a: 38deg;  --a-num: 38;  --r: 96px;"><span></span></div>
            <div class="ll-blip" style="--a: 132deg; --a-num: 132; --r: 118px;"><span></span></div>
            <div class="ll-blip" style="--a: 214deg; --a-num: 214; --r: 84px;"><span></span></div>
            <div class="ll-blip" style="--a: 301deg; --a-num: 301; --r: 124px;"><span></span></div>

            <div class="ll-orbit"></div>

            <div class="ll-logo">
                <img
                    src="{{ asset('assets/ligtasLOGO.png') }}"
                    width="100"
                    height="100"
                    alt=""
                >
            </div>
        </div>

        <p class="ll-brand">Ligtas<span>Alert</span></p>

        <p class="ll-secure">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 3 4 6v5c0 5 3.4 8.4 8 10 4.6-1.6 8-5 8-10V6l-8-3Z"/>
                <path d="m9 12 2 2 4-4"/>
            </svg>
            Protected campus session
            <i aria-hidden="true"></i>
        </p>

        @php
            $ll_name = auth()->check()
                ? \Illuminate\Support\Str::of((string) auth()->user()->name)
                    ->before('@')->before(' ')->replace(['.', '_'], ' ')->title()->toString()
                : '';
        @endphp

        <h2 id="ll-title">
            {{ $ll_name !== '' ? 'Welcome back, '.$ll_name : 'Loading your dashboard' }}
        </h2>

        <p id="ll-message" role="status" aria-live="polite">
            Securing your session…
        </p>

        <ul class="ll-checks" id="ll-checks" aria-hidden="true">
            <li><span class="ll-dot"></span>Verifying your identity</li>
            <li><span class="ll-dot"></span>Securing your session</li>
            <li><span class="ll-dot"></span>Checking campus alert channels</li>
            <li><span class="ll-dot"></span>Preparing your dashboard</li>
        </ul>

        <div class="ll-progress" aria-hidden="true">
            <div class="ll-track"><div class="ll-bar" id="ll-bar"></div></div>
            <span id="ll-percent">0%</span>
        </div>

        <p class="ll-footer">Stay informed. Respond together.</p>

        <button id="ll-dismiss" type="button" hidden>
            Show dashboard
        </button>
    </div>
</div>

<script>
(function () {
    var loader = document.getElementById('ligtas-loader');
    var message = document.getElementById('ll-message');
    var bar = document.getElementById('ll-bar');
    var percent = document.getElementById('ll-percent');
    var dismissButton = document.getElementById('ll-dismiss');
    var previousFocus = document.activeElement;
    var previousOverflow = document.body.style.overflow;

    var MIN_VISIBLE_MS = 3200;   // keep the animation on screen long enough to enjoy
    var SLOW_AFTER_MS = 15000;   // offer a manual way out if something stalls
    var startedAt = Date.now();

    var finished = false;
    var pageLoaded = false;
    var progress = 0;
    var stageIndex = -1;
    var slowTimer = null;
    var ticker = null;

    // Messages appear as progress passes each threshold.
    var stages = [
        { at: 0,   text: 'Verifying your identity…' },
        { at: 22,  text: 'Securing your session…' },
        { at: 48,  text: 'Checking campus alert channels…' },
        { at: 74,  text: 'Preparing your dashboard…' },
        { at: 100, text: 'Campus systems secured. You are in.' }
    ];

    var checks = document.querySelectorAll('#ll-checks li');

    function setMessage(text) {
        message.textContent = text;
        message.classList.remove('is-swapping');
        void message.offsetWidth; // restart the swap animation
        message.classList.add('is-swapping');
    }

    function render() {
        var value = Math.round(progress);
        bar.style.width = progress + '%';
        percent.textContent = value + '%';

        var activeSet = false;
        for (var c = 0; c < checks.length; c++) {
            var done = progress >= stages[c + 1].at;
            checks[c].classList.toggle('is-done', done);
            checks[c].classList.toggle('is-active', !done && !activeSet);
            if (!done) activeSet = true;
        }

        for (var i = stages.length - 1; i >= 0; i--) {
            if (progress >= stages[i].at) {
                if (i !== stageIndex && !slowTimerFired) {
                    stageIndex = i;
                    setMessage(stages[i].text);
                }
                break;
            }
        }
    }

    var slowTimerFired = false;

    function finish() {
        if (finished) return;
        finished = true;

        clearTimeout(slowTimer);
        clearInterval(ticker);
        document.body.style.overflow = previousOverflow;

        loader.classList.add('is-finished');

        if (previousFocus && previousFocus.isConnected) {
            previousFocus.focus();
        }

        setTimeout(function () {
            loader.remove();
        }, 900);
    }

    function complete() {
        if (finished) return;

        slowTimerFired = false;
        clearTimeout(slowTimer);
        progress = 100;
        loader.classList.add('is-ready');
        dismissButton.hidden = true;
        render();

        // Let the bar reach 100% and the final message be seen.
        setTimeout(finish, 700);
    }

    function onReadyToFinish() {
        pageLoaded = true;
        var wait = Math.max(0, MIN_VISIBLE_MS - (Date.now() - startedAt));
        setTimeout(complete, wait);
    }

    document.body.style.overflow = 'hidden';
    loader.focus();
    render();

    // Progress eases toward 90% and only hits 100% once the page has loaded.
    ticker = setInterval(function () {
        if (finished || pageLoaded) return;
        progress += (90 - progress) * 0.045 + 0.15;
        if (progress > 90) progress = 90;
        render();
    }, 80);

    // Offer recovery if an external resource stalls.
    // This does not automatically end the loading screen.
    slowTimer = setTimeout(function () {
        if (finished) return;
        slowTimerFired = true;
        setMessage(
            'Some resources are taking longer than usual. ' +
            'You can open the dashboard now.'
        );
        dismissButton.hidden = false;
    }, SLOW_AFTER_MS);

    dismissButton.addEventListener('click', complete);

    loader.addEventListener('keydown', function (event) {
        if (event.key === 'Tab') {
            event.preventDefault();

            if (dismissButton.hidden) {
                loader.focus();
            } else {
                dismissButton.focus();
            }
        }
    });

    // Fires when the document and its load-blocking resources have loaded.
    if (document.readyState === 'complete') {
        onReadyToFinish();
    } else {
        window.addEventListener('load', onReadyToFinish, { once: true });
    }

    // Back/forward cache restore: nothing to wait for.
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) finish();
    });
})();
</script>

<noscript>
    <style>
        #ligtas-loader { display: none !important; }
    </style>
</noscript>
@endif