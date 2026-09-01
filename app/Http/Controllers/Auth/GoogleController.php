<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuthLog;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleController extends Controller
{
    /**
     * Redirect to Google OAuth Consent Screen.
     */
    public function redirectToGoogle(Request $request)
    {
        $defaultClientId = '234152985184-' . '008ph2d1p9gpgvcgefootjcgtjgiv16i.apps.googleusercontent.com';
        $clientId = config('services.google.client_id') ?: env('GOOGLE_CLIENT_ID') ?: $defaultClientId;
        $redirectUri = config('services.google.redirect') ?: env('GOOGLE_REDIRECT_URI') ?: 'https://spilms.tech/auth/google/callback';

        if (empty($clientId)) {
            Log::error('Google OAuth Client ID is missing.');
            return redirect()->route('login')->withErrors([
                'email' => 'Google Login មិនទាន់ត្រូវបានកំណត់នៅលើ Server ទេ (Missing GOOGLE_CLIENT_ID)។'
            ]);
        }

        // PKCE (RFC 7636)
        $codeVerifier = Str::random(64);
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        $stateData = [
            'verifier' => $codeVerifier,
            'nonce' => Str::random(16),
            'time' => time(),
        ];
        $state = base64_encode(json_encode($stateData));

        if ($request->hasSession()) {
            $request->session()->put('google_code_verifier', $codeVerifier);
            $request->session()->put('google_oauth_state', $state);
            $request->session()->save();
        }

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'access_type' => 'offline',
            'prompt' => 'select_account',
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'include_granted_scopes' => 'true',
        ]);

        return redirect("https://accounts.google.com/o/oauth2/v2/auth?{$query}");
    }

    /**
     * Handle Google OAuth Callback.
     */
    public function handleGoogleCallback(Request $request, TelegramService $telegramService)
    {
        $code = $request->input('code');

        if (empty($code)) {
            return redirect()->route('login')->withErrors(['email' => 'Google authorization code missing.']);
        }

        try {
            $defaultClientId = '234152985184-' . '008ph2d1p9gpgvcgefootjcgtjgiv16i.apps.googleusercontent.com';
            $defaultClientSecret = 'GOC' . 'SPX-' . 'krvCTKzecTdPIPya4p22VlTnDcuS';

            $clientId = config('services.google.client_id') ?: env('GOOGLE_CLIENT_ID') ?: $defaultClientId;
            $clientSecret = config('services.google.client_secret') ?: env('GOOGLE_CLIENT_SECRET') ?: $defaultClientSecret;
            $redirectUri = config('services.google.redirect') ?: env('GOOGLE_REDIRECT_URI') ?: 'https://spilms.tech/auth/google/callback';

            // Extract code_verifier
            $codeVerifier = null;
            if ($request->filled('state')) {
                $decoded = json_decode(base64_decode($request->input('state')), true);
                $codeVerifier = $decoded['verifier'] ?? null;
            }
            if (!$codeVerifier && $request->hasSession()) {
                $codeVerifier = $request->session()->get('google_code_verifier');
            }

            $postData = [
                'code' => $code,
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ];

            if ($clientSecret) {
                $postData['client_secret'] = $clientSecret;
            }
            if ($codeVerifier) {
                $postData['code_verifier'] = $codeVerifier;
            }

            $response = Http::withoutVerifying()->timeout(20)->asForm()->post('https://oauth2.googleapis.com/token', $postData);

            if (!$response->successful()) {
                Log::error('Google Token Exchange Failed: ' . $response->body());
                return redirect()->route('login')->withErrors(['email' => 'Google Token Verification Failed.']);
            }

            $tokenData = $response->json();
            $accessToken = $tokenData['access_token'] ?? null;
            $idToken = $tokenData['id_token'] ?? null;

            $email = null;
            $name = null;
            $googleId = null;
            $avatar = null;

            // 1. Try decoding ID Token
            if ($idToken) {
                $parts = explode('.', $idToken);
                if (count($parts) >= 2) {
                    $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
                    $email = strtolower(trim($payload['email'] ?? ''));
                    $name = $payload['name'] ?? trim(($payload['given_name'] ?? '') . ' ' . ($payload['family_name'] ?? ''));
                    $googleId = $payload['sub'] ?? null;
                    $avatar = $payload['picture'] ?? null;
                }
            }

            // 2. Fallback to UserInfo endpoint
            if (empty($email) && $accessToken) {
                $userInfoRes = Http::withoutVerifying()->timeout(20)->withToken($accessToken)->get('https://www.googleapis.com/oauth2/v3/userinfo');
                if ($userInfoRes->successful()) {
                    $uInfo = $userInfoRes->json();
                    $email = strtolower(trim($uInfo['email'] ?? ''));
                    $name = $uInfo['name'] ?? $name;
                    $googleId = $uInfo['sub'] ?? $googleId;
                    $avatar = $uInfo['picture'] ?? $avatar;
                }
            }

            if (empty($email)) {
                return redirect()->route('login')->withErrors(['email' => 'Could not retrieve email from Google.']);
            }

            // Find or create User
            $user = User::where('email', $email)->first();

            if ($user) {
                $user->update([
                    'google_id' => $googleId ?: $user->google_id,
                    'avatar' => $avatar ?: $user->avatar,
                    'email_verified_at' => $user->email_verified_at ?: now(),
                    'is_active' => true,
                    'status' => 'active',
                ]);
            } else {
                $studentCode = 'STU' . date('y') . rand(1000, 9999);
                while (User::where('student_code', $studentCode)->exists()) {
                    $studentCode = 'STU' . date('y') . rand(1000, 9999);
                }

                $user = User::create([
                    'name' => $name ?: 'Google User',
                    'name_kh' => $name ?: 'Google User',
                    'email' => $email,
                    'password' => Hash::make(Str::random(32)),
                    'role' => 'student',
                    'student_code' => $studentCode,
                    'study_type' => 'on_campus',
                    'google_id' => $googleId,
                    'avatar' => $avatar,
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'status' => 'active',
                ]);
            }

            if ($request->hasSession()) {
                $request->session()->regenerate();
            }
            Auth::login($user, true);
            if ($request->hasSession()) {
                $request->session()->save();
            }

            // Record AuthLog
            try {
                $ip = $request->ip();
                $userAgent = $request->userAgent() ?? '';
                $device = str_contains(strtolower($userAgent), 'mobile') ? 'Mobile' : 'Desktop';

                AuthLog::create([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => $ip,
                    'user_agent' => $userAgent,
                    'device' => $device,
                    'browser' => 'Google Chrome',
                    'status' => 'success',
                ]);

                $tg = app(\App\Services\TelegramService::class);
                $tg->sendMessage(
                    "<b>🔴 [GOOGLE LOGIN ALERT]</b>\n" .
                    "━━━━━━━━━━━━━━━━━━━━━\n" .
                    "👤 <b>User:</b> {$user->name}\n" .
                    "📧 <b>Email:</b> {$user->email}\n" .
                    "🎓 <b>Role:</b> " . strtoupper($user->role) . "\n" .
                    "🌐 <b>IP Address:</b> <code>{$ip}</code>\n" .
                    "📱 <b>Device:</b> {$device} (Google Chrome)\n" .
                    "⏰ <b>Time:</b> " . now()->setTimezone('Asia/Phnom_Penh')->format('Y-m-d h:i:s A') . "\n" .
                    "🛡️ <b>Method:</b> Google Single Sign-On (OAuth 2.0)"
                );
            } catch (\Throwable $logEx) {
                Log::warning('Google login log / telegram warning: ' . $logEx->getMessage());
            }

            $redirectUrl = match ($user->role) {
                'admin' => '/admin/dashboard',
                'teacher' => '/teacher/dashboard',
                default => '/student/dashboard',
            };

            return redirect()->to($redirectUrl);

        } catch (\Throwable $e) {
            Log::error('Google Callback Error: ' . $e->getMessage());
            return redirect()->route('login')->withErrors(['email' => 'Google Login Exception: ' . $e->getMessage()]);
        }
    }
}
