<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>LigtasAlert - Rescuee Dashboard</title>
<script>
/* apply saved theme before first paint (same key as the login page) */
(function () {
    var t = null;
    try { t = localStorage.getItem('ligtas-theme'); } catch (e) {}
    if (!t) t = 'dark';
    document.documentElement.setAttribute('data-theme', t);
})();
</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,700;12..96,800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body>
@include('auth-loading')
@yield('content')

<footer class="site-footer">
    <div class="site-footer-inner">
        <div class="sf-brand">
            <img class="sf-logo" src="{{ asset('assets/ligtasLOGO.png') }}" alt="">
            <div>
                <strong class="sf-word"><span class="bt-light">Ligtas</span><span class="bt-accent">Alert</span></strong>
                <span class="sf-sub">MCC System &middot; Admin</span>
            </div>
        </div>
        <p class="sf-tag">Stay informed. Respond together.</p>
        <div class="sf-college">
            <div class="sf-meta">
                <strong>Mabalacat City College</strong>
                <span>mcc.edu.ph &middot; &copy; {{ date('Y') }}</span>
            </div>
            <div class="sf-logo-tile">
                <img src="{{ asset('assets/mcc_cover.jpg') }}" alt="Mabalacat City College">
            </div>
        </div>
    </div>
</footer>

<script src="{{ asset('js/lucide.min.js') }}"></script>
<script src="{{ asset('js/admin.js') }}"></script>
<script src="{{ asset('js/ui-enhancements.js') }}"></script>
</body>
</html>