<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * Authentication for the rescuee app. Returns Sanctum bearer tokens instead
 * of session cookies, so the same endpoints work in the browser and on a
 * device without any CORS cookie juggling.
 */
class AuthController extends Controller
{
    /**
     * The redirect this controller uses for the whole app flow.
     *
     * Both microsoftStart() and microsoftCallback() must agree on this, and it
     * must be the app route rather than config('services.microsoft.redirect'),
     * which points at the web dashboard's /auth/microsoft/callback. If they
     * disagree, authorize is asked for one redirect_uri and the token exchange
     * sends another, and Microsoft rejects it with AADSTS500112.
     */
    private const MICROSOFT_REDIRECT_PATH = '/api/auth/microsoft/callback';

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $user->password = null;

        return $this->tokenResponse($user, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'These credentials do not match our records.',
            ], 401);
        }

        /** @var User $user */
        $user = Auth::user();
        $user->password = null;

        return $this->tokenResponse($user);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->password = null;

        return response()->json(['user' => $user]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Signed out.']);
    }

    /**
     * Step one of Microsoft sign-in: hand the app the URL to open in a browser
     * and a state token to poll back with. The code exchange happens on the
     * server, so the client secret never leaves Laravel.
     */
    public function microsoftStart(): JsonResponse
    {
        $state = Str::random(32);
        cache()->put("microsoft_state_{$state}", true, now()->addMinutes(10));

        $url = Socialite::driver('microsoft')
            ->stateless()
            ->redirectUrl(url(self::MICROSOFT_REDIRECT_PATH))
            ->redirect();

        // The provider is stateless, so its URL has no state param. We carry
        // our own, minted above and verified against the cache on return.
        $target = $url->getTargetUrl();
        $target .= str_contains($target, '?') ? '&' : '?';
        $target .= 'state='.rawurlencode($state);

        return response()->json([
            'url' => $target,
            'state' => $state,
        ]);
    }

    /**
     * The browser lands here after Microsoft authorizes. Exchange the code,
     * find or create the user, stash the token under the state key, and show
     * the rescuer a page they can close.
     */
    public function microsoftCallback(Request $request): View
    {
        $state = (string) $request->query('state', '');
        $error = $request->query('error');

        if ($error || ! $state || cache()->get("microsoft_state_{$state}") === null) {
            return view('auth.microsoft_done', [
                'message' => 'Sign-in did not complete. Go back to the app and try again.',
            ]);
        }

        try {
            $microsoftUser = Socialite::driver('microsoft')
                ->stateless()
                ->redirectUrl(url(self::MICROSOFT_REDIRECT_PATH))
                ->user();
        } catch (Throwable $exception) {
            report($exception);

            return view('auth.microsoft_done', [
                'message' => 'Microsoft sign-in failed. Go back to the app and try again.',
            ]);
        }

        $email = $microsoftUser->getEmail();

        if (! $email) {
            return view('auth.microsoft_done', [
                'message' => 'Your Microsoft account did not share an email address.',
            ]);
        }

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $microsoftUser->getName() ?: $email,
                'password' => Str::random(40),
            ],
        );

        $token = $user->createToken('mobile')->plainTextToken;
        cache()->put("microsoft_token_{$state}", $token, now()->addMinutes(10));

        return view('auth.microsoft_done', [
            'message' => 'Signed in. You can close this tab and return to the app.',
        ]);
    }

    /**
     * The app polls this with its state until the callback has delivered a
     * token. Fails on purpose after the 10-minute cache window.
     */
    public function microsoftFinish(Request $request): JsonResponse
    {
        $state = (string) $request->query('state', '');

        $token = cache()->get("microsoft_token_{$state}");

        if (! $token) {
            return response()->json(['pending' => true], 404);
        }

        cache()->forget("microsoft_token_{$state}");
        cache()->forget("microsoft_state_{$state}");

        // Use Sanctum's own lookup: it splits "id|plaintext" and hashes only
        // the plaintext half. Hashing the whole string here silently never
        // matches, which would strand the rescuee on the sign-in screen.
        $accessToken = PersonalAccessToken::findToken($token);

        if (! $accessToken) {
            return response()->json(['pending' => true], 404);
        }

        /** @var User $user */
        $user = $accessToken->tokenable;
        $user->password = null;

        return response()->json(['user' => $user, 'token' => $token]);
    }

    private function tokenResponse(User $user, int $status = 200): JsonResponse
    {
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json(['user' => $user, 'token' => $token], $status);
    }
}
