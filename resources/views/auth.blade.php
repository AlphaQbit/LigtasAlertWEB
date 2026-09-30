<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LigtasAlert - Authentication</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>

    <div class="auth-wrapper">
        <div class="auth-brand">LigtasAlert</div>
        
        <div class="auth-card">
            
            <!-- LOGIN FORM -->
            <div id="form-login" class="auth-form {{ ($mode ?? '') === 'login' ? 'active' : '' }}">
                <div class="auth-header">
                    <div class="auth-icon-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4" />
                            <circle cx="12" cy="16" r="1.5" fill="currentColor" stroke="none"/>
                        </svg>
                    </div>
                    <h1 class="auth-title">Welcome back</h1>
                    <div class="auth-subtitle">Emergency Response System</div>
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
                        <label class="input-label">Email address</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-input" placeholder="name@example.com" required autocomplete="email">
                        </div>
                    </div>

                    <div class="input-group">
                        <label class="input-label">Password</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <input type="password" name="password" class="form-input" placeholder="••••••••" required autocomplete="current-password">
                        </div>
                    </div>

                    <a href="#" class="forgot-link" onclick="return false;">Forgot password?</a>

                    <a href="{{ route('microsoft.redirect') }}" class="btn-microsoft">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 21 21" width="18" height="18" aria-hidden="true">
                            <rect x="1" y="1" width="9" height="9" fill="#F25022"/>
                            <rect x="11" y="1" width="9" height="9" fill="#7FBA00"/>
                            <rect x="1" y="11" width="9" height="9" fill="#00A4EF"/>
                            <rect x="11" y="11" width="9" height="9" fill="#FFB900"/>
                        </svg>
                        Continue with Microsoft
                    </a>

                    <div class="auth-divider"><span>or</span></div>
                    <button type="submit" class="btn-primary">LOG IN</button>
                    
                    <div class="auth-footer">
                        New to LigtasAlert? <a href="{{ route('register') }}">Register</a>
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
                        <label class="input-label">Full Name</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <input type="text" name="name" value="{{ old('name') }}" class="form-input" placeholder="John Doe" required autocomplete="name">
                        </div>
                    </div>

                    <div class="input-group">
                        <label class="input-label">Email address</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-input" placeholder="name@example.com" required autocomplete="email">
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


</body>
</html>
