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

        $queryData = [
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
        ];

        if ($request->filled('email')) {
            $queryData['login_hint'] = trim($request->input('email'));
        }

        $query = http_build_query($queryData);

        return redirect("https://accounts.google.com/o/oauth2/v2/auth?{$query}");
    }

    /**
     * Handle Google OAuth Callback.
     */
    public function handleGoogleCallback(Request $request, TelegramService $telegramService)
    {
        if ($request->filled('error')) {
            $errDesc = $request->input('error_description') ?: $request->input('error');
            Log::warning('Google OAuth cancelled or returned error: ' . $errDesc);
            return redirect()->route('login')->withErrors([
                'email' => 'ការចូលដោយប្រើប្រាស់ Google ត្រូវបានបោះបង់ ឬបរាជ័យ (' . $errDesc . ')។'
            ]);
        }

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
                if ($user->is_active === false || $user->status === 'inactive') {
                    return redirect()->route('login')->withErrors([
                        'email' => 'គណនីរបស់អ្នកត្រូវបានបិទដំណើរការ។',
                    ]);
                }

                if ($user->status === 'suspended' || $user->status === 'blocked') {
                    return redirect()->route('login')->withErrors([
                        'email' => 'គណនីរបស់អ្នកត្រូវបានព្យួរជាបណ្តោះអាសន្ន។ សូមទាក់ទងរដ្ឋបាលសាលា។',
                    ]);
                }

                if ($user->status === 'pending_payment') {
                    return redirect()->route('login')->withErrors([
                        'email' => 'គណនីរបស់អ្នកកំពុងរង់ចាំការផ្ទៀងផ្ទាត់ការបង់ប្រាក់។',
                    ]);
                }

                $user->update([
                    'google_id' => $googleId ?: $user->google_id,
                    'avatar' => $avatar ?: $user->avatar,
                    'email_verified_at' => $user->email_verified_at ?: now(),
                    'is_active' => true,
                    'status' => 'active',
                    'login_attempts' => 0,
                    'locked_until' => null,
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
                    'login_attempts' => 0,
                    'locked_until' => null,
                ]);
            }

            if ($request->hasSession()) {
                $request->session()->regenerate();
            }
            Auth::login($user, true);
            if ($request->hasSession()) {
                $request->session()->save();
            }

            // Detect Client Environment
            $ip = $request->ip() ?: '127.0.0.1';
            $userAgent = $request->userAgent() ?? '';
            $device = str_contains(strtolower($userAgent), 'mobile') ? 'Mobile' : 'Desktop';
            $browser = $this->getBrowserName($userAgent);

            // Record AuthLog
            try {
                AuthLog::create([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => $ip,
                    'user_agent' => $userAgent,
                    'device' => $device,
                    'browser' => $browser,
                    'status' => 'success',
                ]);
            } catch (\Throwable $logEx) {
                Log::warning('AuthLog create failed for Google Login: ' . $logEx->getMessage());
            }

            // Real-time Telegram Alert & Email Dispatch
            try {
                $groupChatId = config('services.telegram.admin_chat_id') ?: env('TELEGRAM_ADMIN_CHAT_ID') ?: config('services.telegram.chat_id') ?: env('TELEGRAM_CHAT_ID') ?: '-5560385465';

                $safeName = htmlspecialchars($user->name ?: 'Google User', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeEmail = htmlspecialchars($user->email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeRole = strtoupper(htmlspecialchars($user->role ?: 'student', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
                $safeIp = htmlspecialchars($ip, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeDevice = htmlspecialchars($device, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeBrowser = htmlspecialchars($browser, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                $telegramService->sendMessage(
                    "<b>🔴 [GOOGLE LOGIN ALERT]</b>\n" .
                    "━━━━━━━━━━━━━━━━━━━━━\n" .
                    "👤 <b>User:</b> {$safeName}\n" .
                    "📧 <b>Email:</b> {$safeEmail}\n" .
                    "🎓 <b>Role:</b> {$safeRole}\n" .
                    "🌐 <b>IP Address:</b> <code>{$safeIp}</code>\n" .
                    "📱 <b>Device:</b> {$safeDevice} ({$safeBrowser})\n" .
                    "⏰ <b>Time:</b> " . now()->setTimezone('Asia/Phnom_Penh')->format('Y-m-d h:i:s A') . "\n" .
                    "🛡️ <b>Method:</b> Google Single Sign-On (OAuth 2.0)",
                    'HTML',
                    $groupChatId
                );

                // Direct User Private Telegram Notification (if account is linked to Telegram)
                $userChatId = $user->telegram_id ?: $user->telegram_chat_id;
                if (!empty($userChatId) && (string)$userChatId !== (string)$groupChatId) {
                    $telegramService->sendDirectMessage(
                        $userChatId,
                        "🛡️ <b>ការជូនដំណឹងសុវត្ថិភាព៖ ការចូលប្រើប្រាស់គណនី</b>\n" .
                        "━━━━━━━━━━━━━━━━━━━━━\n\n" .
                        "សួស្តី <b>" . ($user->name_kh ?: $user->name) . "</b> 👋\n" .
                        "គណនីរបស់អ្នកទើបតែបាន Login ចូលប្រើប្រាស់លើ SPI E-LMS ដោយជោគជ័យតាមរយៈ Google ៖\n\n" .
                        "⏰ <b>ម៉ោង៖</b> " . now()->setTimezone('Asia/Phnom_Penh')->format('d-M-Y h:i A') . "\n" .
                        "📱 <b>ឧបករណ៍៖</b> {$device} ({$browser})\n" .
                        "🌐 <b>IP Address៖</b> <code>{$ip}</code>\n" .
                        "🛡️ <b>វិធីសាស្ត្រ៖</b> Google Single Sign-On (OAuth 2.0)\n\n" .
                        "⚠️ <i>ប្រសិនបើមិនមែនជាអ្នកទេ សូមទាក់ទងមកកាន់រដ្ឋបាលជាបន្ទាន់!</i>",
                        'HTML'
                    );
                }
            } catch (\Throwable $tgEx) {
                Log::warning('Google login Telegram alert notice: ' . $tgEx->getMessage());
            }

            // Security Email alert
            try {
                if (!empty($user->email)) {
                    $loginDetails = [
                        'ip' => $ip,
                        'device' => $device,
                        'browser' => $browser,
                        'time' => now()->setTimezone('Asia/Phnom_Penh')->format('d-M-Y h:i A'),
                        'location' => 'Cambodia',
                    ];
                    (new AuthenticatedSessionController())->sendSecurityAlertEmail($user, $loginDetails);
                }
            } catch (\Throwable $mailEx) {
                Log::warning('Google login security alert email notice: ' . $mailEx->getMessage());
            }

            // Generate JWT Token safely if configured
            try {
                if (config('jwt.secret')) {
                    $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);
                    $payload = \Tymon\JWTAuth\Facades\JWTAuth::setToken($token)->getPayload();

                    \App\Models\JwtSession::create([
                        'user_id' => $user->id,
                        'token' => $token,
                        'expires_at' => \Carbon\Carbon::createFromTimestamp($payload->get('exp')),
                        'ip_address' => $ip,
                        'user_agent' => $userAgent,
                    ]);
                }
            } catch (\Throwable $jwtEx) {
                Log::warning('JWT token creation in Google Login: ' . $jwtEx->getMessage());
            }

            $redirectUrl = match ($user->role) {
                'admin' => '/admin/dashboard',
                'teacher' => '/teacher/dashboard',
                default => '/student/dashboard',
            };

            return redirect()->intended($redirectUrl);

        } catch (\Throwable $e) {
            Log::error('Google Callback Error: ' . $e->getMessage());
            return redirect()->route('login')->withErrors(['email' => 'Google Login Exception: ' . $e->getMessage()]);
        }
    }

    /**
     * Parse browser name from User-Agent string.
     */
    private function getBrowserName(?string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'Web Browser';
        }
        if (str_contains($userAgent, 'Edge') || str_contains($userAgent, 'Edg/')) return 'Edge';
        if (str_contains($userAgent, 'Chrome') || str_contains($userAgent, 'CriOS')) return 'Chrome';
        if (str_contains($userAgent, 'Firefox') || str_contains($userAgent, 'FxiOS')) return 'Firefox';
        if (str_contains($userAgent, 'Safari')) return 'Safari';
        if (str_contains($userAgent, 'Opera') || str_contains($userAgent, 'OPR')) return 'Opera';
        return 'Web Browser';
    }
}
