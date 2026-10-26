<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LigtasAlert - Authentication</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>

<div class="auth-container">
        <div class="auth-branding">
            <div class="brand-logo">
                <div class="brand-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2.5 3.5 5.7v5.5c0 5.1 3.5 9.4 8.5 11.3 5-1.9 8.5-6.2 8.5-11.3V5.7L12 2.5Zm0 3.2 5.5 2.1v3.4c0 3.6-2.2 6.8-5.5 8.4-3.3-1.6-5.5-4.8-5.5-8.4V7.8L12 5.7Z"/>
                    </svg>
                </div>
                <h1 class="brand-title">LigtasAlert</h1>
            </div>
            <div class="brand-copy">
                <p class="brand-tagline">Stay informed. Respond together.</p>
                <p class="brand-description">Sign in to receive timely alerts and help Mabalacat City College stay informed, prepared, and connected during emergencies.</p>
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

        <div class="auth-card">
            <div class="auth-form-wrapper">
                <!-- LOGIN FORM -->
                <div id="form-login" class="auth-form login-form {{ ($mode ?? '') === 'login' ? 'active' : '' }}">
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
                                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <input id="login-email" type="email" name="email" value="{{ old('email') }}" class="form-input" placeholder="Enter your email address" required autocomplete="email">
                            </div>
                        </div>

                        <div class="input-group">
                            <label class="input-label" for="login-password">Password</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <input id="login-password" type="password" name="password" class="form-input" placeholder="Enter your password" required autocomplete="current-password">
                            </div>
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
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back
                    </a>

                    <div class="auth-header">
                        <h1 class="auth-title">Create Account</h1>
                        <div class="auth-subtitle">Join the Response Team</div>
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
                            <label class="input-label">Email address</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                <input type="email" name="email" value="{{ old('email') }}" class="form-input" placeholder="example@edu.ph.com" required autocomplete="email">
                            </div>
                        </div>
                        <div class="input-group">
                            <label class="input-label">Password</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <input type="password" name="password" class="form-input" placeholder="••••••••" required autocomplete="new-password">
                            </div>
                        </div>

                        <div class="input-group">
                            <label class="input-label">Confirm Password</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <input type="password" name="password_confirmation" class="form-input" placeholder="••••••••" required autocomplete="new-password">
                            </div>
                        </div>

                        <button type="submit" class="btn-primary" style="margin-top: 1rem;">REGISTER</button>

                        <div class="auth-footer">
                            Already have an account? <a href="{{ route('login') }}">Log in</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <footer class="auth-site-footer">mcc.edu.ph</footer>

</body>
</html>
