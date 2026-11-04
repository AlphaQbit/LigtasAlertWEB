<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LigtasAlert - Authentication</title>
    <script>
    /* apply saved theme before first paint (no flash) */
    (function () {
        var t = null;
        try { t = localStorage.getItem('ligtas-theme'); } catch (e) {}
        if (!t) t = 'dark';
        document.documentElement.setAttribute('data-theme', t);
    })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>

<div class="net-toast" id="net-toast" role="status" hidden>You're offline. Sign-in and alerts need a connection.</div>

<div class="auth-container">

    <!-- BRANDING -->
    <div class="auth-branding">
        <div class="radar" aria-hidden="true"><i></i><i></i><i></i></div>
        <div class="brand-logo">
            <h1 class="brand-title"><span class="bt-light">Ligtas</span><span class="bt-accent">Alert</span></h1>
            <span class="brand-badge"><i aria-hidden="true"></i>MCC System</span>
        </div>

        <div class="brand-copy">
            <p class="brand-tagline">Stay informed. Respond together.</p>
            <p class="brand-description">Sign in to receive timely alerts and help Mabalacat City College stay informed, prepared, and connected during emergencies.</p>
            <ul class="brand-points">
                <li>Real-time alerts</li>
                <li>Campus-wide reach</li>
                <li>Response team ready</li>
            </ul>

            <div class="tip-card">
                <div class="tip-head">
                    <span class="tip-label">Safety tip</span>
                    <span class="tip-clock" id="tip-clock" aria-hidden="true"></span>
                </div>
                <div class="tip-body">
                    <p class="tip is-active">Know your nearest exit.<small>Check the evacuation map posted in every hallway.</small></p>
                    <p class="tip">Earthquake? Drop, cover, and hold on.<small>Stay away from windows until the shaking stops.</small></p>
                    <p class="tip">Keep your phone charged and alerts on.<small>Official announcements reach you here first.</small></p>
                    <p class="tip">Follow the response team's instructions.<small>Move calmly and help others who need assistance.</small></p>
                </div>
                <div class="tip-foot">
                    <button type="button" class="tip-nav" id="tip-prev" aria-label="Previous tip"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg></button>
                    <div class="tip-bar"><span></span></div>
                    <div class="tip-dots">
                        <button type="button" class="tip-dot is-active" aria-label="Tip 1"></button>
                        <button type="button" class="tip-dot" aria-label="Tip 2"></button>
                        <button type="button" class="tip-dot" aria-label="Tip 3"></button>
                        <button type="button" class="tip-dot" aria-label="Tip 4"></button>
                    </div>
                    <button type="button" class="tip-nav" id="tip-next" aria-label="Next tip"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg></button>
                </div>
            </div>
        </div>

        <div class="institutional-logos">
            <div class="logo-item">
                <img src="{{ asset('assets/mcc_cover.jpg') }}" alt="Mabalacat City College" class="logo-img" />
            </div>
            <div class="logo-item">
                <img src="{{ asset('assets/ligtasLOGO.png') }}" alt="LigtasAlert" class="logo-img" />
            </div>
        </div>
    </div>

    <!-- FORMS -->
    <div class="auth-card">
        <div class="auth-deco" aria-hidden="true">
            <span class="ring r1"></span><span class="ring r2"></span><span class="ring r3"></span>
            <svg class="shield" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.5 3.5 5.7v5.5c0 5.1 3.5 9.4 8.5 11.3 5-1.9 8.5-6.2 8.5-11.3V5.7L12 2.5Zm0 3.2 5.5 2.1v3.4c0 3.6-2.2 6.8-5.5 8.4-3.3-1.6-5.5-4.8-5.5-8.4V7.8L12 5.7Z"/></svg>
        </div>
                <button type="button" class="theme-toggle" id="theme-toggle" aria-label="Switch to dark mode" title="Switch theme">
            <span class="tt-thumb" aria-hidden="true"></span>
            <svg class="sun" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path stroke-linecap="round" d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4l1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
            <svg class="moon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12.8A9 9 0 1111.2 3a7 7 0 009.8 9.8z"/></svg>
        </button>

        <div class="auth-form-wrapper">

            <!-- LOGIN FORM -->
            <div id="form-login" class="auth-form login-form {{ ($mode ?? 'login') !== 'register' ? 'active' : '' }}">
                <div class="auth-header login-heading">
                    <h2 class="auth-title">Welcome Back</h2>
                    <p class="auth-subtitle">Sign in to your emergency alert dashboard</p>
                </div>

                <form method="POST" action="{{ route('login.store') }}">
                    @csrf
                    @if (session('status'))
                        <div class="auth-success">{{ session('status') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="auth-error">{{ $errors->first() }}</div>
                    @endif

                    <div class="input-group">
                        <label class="input-label" for="login-email">Email address</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <input id="login-email" type="email" name="email" value="{{ old('email') }}" class="form-input" placeholder="Enter your email address" required autocomplete="email">
                            <svg class="valid-check" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                    </div>

                    <div class="input-group">
                        <label class="input-label" for="login-password">Password</label>
                        <div class="input-wrapper has-toggle">
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <input id="login-password" type="password" name="password" class="form-input" placeholder="Enter your password" required autocomplete="current-password">
                        <button type="button" class="toggle-pass" aria-label="Show password" aria-pressed="false">
                                <svg class="eye" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-off" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17.9 17.9A10.1 10.1 0 0112 19c-6.4 0-10-7-10-7a18 18 0 014.1-4.9M9.9 5.2A9.7 9.7 0 0112 5c6.4 0 10 7 10 7a18 18 0 01-2.2 3.2M1 1l22 22"/></svg>
                            </button>
                        </div>
                        <p class="caps-warn" id="caps-warn" hidden>Caps Lock is on</p>
                    </div>

                    <div class="form-options">
                        <a href="#" class="forgot-link" onclick="return false;">Forgot password?</a>
                    </div>

                    <button type="submit" class="btn-primary">Log In</button>

                    <div class="divider">
                        <span>or continue with</span>
                    </div>

                    <a href="{{ route('microsoft.redirect') }}" class="btn-microsoft">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="18" height="18" aria-hidden="true">
                            <rect x="1" y="1" width="9" height="9" fill="#F25022"/>
                            <rect x="11" y="1" width="9" height="9" fill="#7FBA00"/>
                            <rect x="1" y="11" width="9" height="9" fill="#00A4EF"/>
                            <rect x="11" y="11" width="9" height="9" fill="#FFB900"/>
                        </svg>
                        Continue with Microsoft
                    </a>

                    <div class="auth-footer">
                        Don't have an account? <a href="{{ route('register') }}" class="create-account-link">Create an account</a>
                    </div>
                </form>
            </div>

            <!-- REGISTER FORM -->
            <div id="form-register" class="auth-form {{ ($mode ?? '') === 'register' ? 'active' : '' }}">
                <a class="back-link" href="{{ route('login') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back
                </a>

                <div class="auth-header">
                    <h2 class="auth-title">Create Account</h2>
                    <p class="auth-subtitle">Join the Response Team</p>
                </div>

                <form method="POST" action="{{ route('register.store') }}">
                    @csrf
                    @if (session('status'))
                        <div class="auth-success">{{ session('status') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="auth-error">{{ $errors->first() }}</div>
                    @endif

                    <div class="input-group">
                        <label class="input-label" for="register-email">Email address</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <input id="register-email" type="email" name="email" value="{{ old('email') }}" class="form-input" placeholder="example@edu.ph.com" required autocomplete="email">
                            <svg class="valid-check" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </div>
                    </div>

                    <div class="input-group">
                        <label class="input-label" for="register-password">Password</label>
                        <div class="input-wrapper has-toggle">
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <input id="register-password" type="password" name="password" class="form-input" placeholder="••••••••" required autocomplete="new-password">
                        <button type="button" class="toggle-pass" aria-label="Show password" aria-pressed="false">
                                <svg class="eye" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-off" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17.9 17.9A10.1 10.1 0 0112 19c-6.4 0-10-7-10-7a18 18 0 014.1-4.9M9.9 5.2A9.7 9.7 0 0112 5c6.4 0 10 7 10 7a18 18 0 01-2.2 3.2M1 1l22 22"/></svg>
                            </button>
                        </div>
                        <div class="strength" id="strength" data-level="0" aria-live="polite"><span></span><span></span><span></span><span></span><em></em></div>
                    </div>

                    <div class="input-group">
                        <label class="input-label" for="register-password-confirm">Confirm Password</label>
                        <div class="input-wrapper has-toggle">
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <input id="register-password-confirm" type="password" name="password_confirmation" class="form-input" placeholder="••••••••" required autocomplete="new-password">
                        <button type="button" class="toggle-pass" aria-label="Show password" aria-pressed="false">
                                <svg class="eye" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-off" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M17.9 17.9A10.1 10.1 0 0112 19c-6.4 0-10-7-10-7a18 18 0 014.1-4.9M9.9 5.2A9.7 9.7 0 0112 5c6.4 0 10 7 10 7a18 18 0 01-2.2 3.2M1 1l22 22"/></svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" style="margin-top: 0.5rem;">REGISTER</button>

                    <div class="auth-footer">
                        Already have an account? <a href="{{ route('login') }}">Log in</a>
                    </div>
                </form>
            </div>


        <p class="secure-note">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            Secure sign-in for MCC admin and staff
        </p>

        </div>

        <footer class="auth-site-footer">mcc.edu.ph</footer>
    </div>
</div>

<script>
document.querySelectorAll('.toggle-pass').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var input = btn.parentElement.querySelector('input');
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.setAttribute('aria-pressed', show);
        btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
});
</script>

<script>
document.querySelectorAll('.auth-form form').forEach(function (form) {
    form.addEventListener('submit', function () {
        var btn = form.querySelector('.btn-primary');
        if (btn && form.checkValidity()) btn.classList.add('is-loading');
    });
});
window.addEventListener('pageshow', function () {
    document.querySelectorAll('.btn-primary.is-loading').forEach(function (b) { b.classList.remove('is-loading'); });
});
</script>

<script>
/* live clock (Philippine time) */
(function () {
    var el = document.getElementById('tip-clock');
    if (!el) return;
    function tick() {
        el.textContent = new Date().toLocaleString('en-PH', {
            timeZone: 'Asia/Manila', weekday: 'short', month: 'short', day: 'numeric',
            hour: 'numeric', minute: '2-digit'
        });
    }
    tick(); setInterval(tick, 15000);
})();

/* safety tips carousel: auto-rotate, arrows, dots, pause on hover */
(function () {
    var card = document.querySelector('.tip-card');
    if (!card) return;
    var tips = card.querySelectorAll('.tip'), dots = card.querySelectorAll('.tip-dot'), bar = card.querySelector('.tip-bar span'), n = 0, timer;
    function restartBar() { bar.style.animation = 'none'; void bar.offsetWidth; bar.style.animation = ''; }
    function go(i) {
        tips[n].classList.remove('is-active'); dots[n].classList.remove('is-active');
        n = (i + tips.length) % tips.length;
        tips[n].classList.add('is-active'); dots[n].classList.add('is-active');
        restartBar();
    }
    function start() { clearInterval(timer); timer = setInterval(function () { go(n + 1); }, 6000); }
    document.getElementById('tip-prev').addEventListener('click', function () { go(n - 1); start(); });
    document.getElementById('tip-next').addEventListener('click', function () { go(n + 1); start(); });
    dots.forEach(function (d, i) { d.addEventListener('click', function () { go(i); start(); }); });
    card.addEventListener('mouseenter', function () { clearInterval(timer); });
    card.addEventListener('mouseleave', function () { restartBar(); start(); });
    start();
})();

/* caps-lock hint on the login password */
(function () {
    var input = document.getElementById('login-password'), warn = document.getElementById('caps-warn');
    if (!input || !warn) return;
    function check(e) { warn.hidden = !(e.getModifierState && e.getModifierState('CapsLock')); }
    input.addEventListener('keyup', check);
    input.addEventListener('keydown', check);
    input.addEventListener('blur', function () { warn.hidden = true; });
})();

/* password strength meter on register */
(function () {
    var input = document.getElementById('register-password'), meter = document.getElementById('strength');
    if (!input || !meter) return;
    var labels = ['', 'Weak', 'Fair', 'Good', 'Strong'], label = meter.querySelector('em');
    input.addEventListener('input', function () {
        var v = input.value, score = 0;
        if (v.length >= 8) score++;
        if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
        if (/\d/.test(v)) score++;
        if (/[^A-Za-z0-9]/.test(v) || v.length >= 12) score++;
        if (!v) score = 0;
        meter.dataset.level = score;
        label.textContent = labels[score];
    });
})();
</script>

<script>
/* mouse: card spotlight + gentle parallax (skipped for reduced motion) */
(function () {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    var card = document.querySelector('.auth-form-wrapper');
    var layers = [['.radar', -14], ['.auth-deco', 10]].map(function (l) { return [document.querySelector(l[0]), l[1]]; });
    document.addEventListener('mousemove', function (e) {
        var nx = e.clientX / window.innerWidth - .5, ny = e.clientY / window.innerHeight - .5;
        layers.forEach(function (l) {
            if (l[0]) { l[0].style.setProperty('--px', (nx * l[1]) + 'px'); l[0].style.setProperty('--py', (ny * l[1]) + 'px'); }
        });
        if (card) {
            var r = card.getBoundingClientRect();
            card.style.setProperty('--mx', (e.clientX - r.left) + 'px');
            card.style.setProperty('--my', (e.clientY - r.top) + 'px');
        }
    });
})();

/* button ripple */
document.querySelectorAll('.btn-primary, .btn-microsoft').forEach(function (btn) {
    btn.addEventListener('pointerdown', function (e) {
        var r = btn.getBoundingClientRect(), s = document.createElement('span');
        s.className = 'ripple';
        s.style.left = (e.clientX - r.left) + 'px'; s.style.top = (e.clientY - r.top) + 'px';
        btn.appendChild(s); setTimeout(function () { s.remove(); }, 650);
    });
});

/* offline / online notice */
(function () {
    var t = document.getElementById('net-toast');
    if (!t) return;
    function sync() { t.hidden = navigator.onLine; }
    window.addEventListener('online', sync); window.addEventListener('offline', sync); sync();
})();
</script>

<script>
/* light / dark toggle */
(function () {
    var root = document.documentElement, btn = document.getElementById('theme-toggle');
    if (!btn) return;
    function label() {
        var dark = root.getAttribute('data-theme') === 'dark';
        btn.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
        btn.setAttribute('aria-pressed', dark);
    }
    label();
    btn.addEventListener('click', function () {
        var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        root.classList.add('theme-anim');
        root.setAttribute('data-theme', next);
        try { localStorage.setItem('ligtas-theme', next); } catch (e) {}
        label();
        setTimeout(function () { root.classList.remove('theme-anim'); }, 450);
    });
})();
</script>

</body>
</html>