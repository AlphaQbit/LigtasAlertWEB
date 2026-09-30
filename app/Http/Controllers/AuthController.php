<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth', ['mode' => 'login']);
    }

    public function showRegister(): View
    {
        return view('auth', ['mode' => 'register']);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'These credentials do not match our records.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin'));
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        User::create($validated);

        return redirect()
            ->route('login')
            ->with('status', 'Registration successful. Please log in.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function microsoftRedirect(): RedirectResponse
    {
        return Socialite::driver('microsoft')->redirect();
    }

    public function microsoftCallback(Request $request): RedirectResponse
    {
        try {
            $microsoftUser = Socialite::driver('microsoft')->user();
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('login')->withErrors(['email' => 'Microsoft sign-in failed. Please try again.']);
        }

        $email = $microsoftUser->getEmail();

        if (! $email) {
            return redirect()->route('login')->withErrors(['email' => 'Your Microsoft account did not share an email address.']);
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            ['name' => $microsoftUser->getName() ?: $email, 'password' => Str::password(32)],
        );

        Auth::login($user, remember: true);

        $request->session()->regenerate();

        return redirect()->intended(route('admin'));
    }
}
