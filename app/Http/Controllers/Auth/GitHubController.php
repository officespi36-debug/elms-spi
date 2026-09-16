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

class GitHubController extends Controller
{
    /**
     * Redirect to GitHub OAuth Consent Screen.
     */
    public function redirectToGitHub(Request $request)
    {
        $clientId = config('services.github.client_id') ?: env('GITHUB_CLIENT_ID') ?: 'Ov23liDMj4TYTQFJpXpo';
        $redirectUri = config('services.github.redirect') ?: env('GITHUB_REDIRECT_URI') ?: 'https://spilms.tech/auth/github/callback';

        if (empty($clientId)) {
            Log::error('GitHub OAuth Client ID is missing.');
            return redirect()->route('login')->withErrors([
                'email' => 'GitHub Login មិនទាន់ត្រូវបានកំណត់នៅលើ Server ទេ (Missing GITHUB_CLIENT_ID)។'
            ]);
        }

        $stateData = [
            'nonce' => Str::random(16),
            'time' => time(),
        ];
        $state = base64_encode(json_encode($stateData));

        if ($request->hasSession()) {
            $request->session()->put('github_oauth_state', $state);
            $request->session()->save();
        }

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => 'read:user user:email',
            'state' => $state,
            'allow_signup' => 'true',
        ]);

        return redirect("https://github.com/login/oauth/authorize?{$query}");
    }

    /**
     * Handle GitHub OAuth Callback.
     */
    public function handleGitHubCallback(Request $request, TelegramService $telegramService)
    {
        if ($request->filled('error')) {
            $errDesc = $request->input('error_description') ?: $request->input('error');
            Log::warning('GitHub OAuth cancelled or returned error: ' . $errDesc);
            return redirect()->route('login')->withErrors([
                'email' => 'ការចូលដោយប្រើប្រាស់ GitHub ត្រូវបានបោះបង់ ឬបរាជ័យ (' . $errDesc . ')។'
            ]);
        }

        $code = $request->input('code');

        if (empty($code)) {
            return redirect()->route('login')->withErrors(['email' => 'GitHub authorization code missing.']);
        }

        try {
            $clientId = config('services.github.client_id') ?: env('GITHUB_CLIENT_ID') ?: 'Ov23liDNj4TYTQF3pkpc';
            $clientSecret = config('services.github.client_secret') ?: env('GITHUB_CLIENT_SECRET') ?: '34d618e0c33ba012ca079038c5e79d55e7c4d6bf';
            $redirectUri = config('services.github.redirect') ?: env('GITHUB_REDIRECT_URI') ?: 'https://spilms.tech/auth/github/callback';

            // Exchange authorization code for access token
            $response = Http::withoutVerifying()
                ->timeout(20)
                ->withHeaders([
                    'Accept' => 'application/json',
                ])
                ->post('https://github.com/login/oauth/access_token', [
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'code' => $code,
                    'redirect_uri' => $redirectUri,
                ]);

            if (!$response->successful()) {
                Log::error('GitHub Token Exchange Failed: ' . $response->body());
                return redirect()->route('login')->withErrors(['email' => 'GitHub Token Verification Failed.']);
            }

            $tokenData = $response->json();
            $accessToken = $tokenData['access_token'] ?? null;

            if (empty($accessToken)) {
                Log::error('GitHub Access Token missing in response: ' . json_encode($tokenData));
                return redirect()->route('login')->withErrors(['email' => 'Could not retrieve access token from GitHub.']);
            }

            // Fetch GitHub user profile
            $userResponse = Http::withoutVerifying()
                ->timeout(20)
                ->withHeaders([
                    'Authorization' => "Bearer {$accessToken}",
                    'Accept' => 'application/vnd.github+json',
                    'User-Agent' => 'SPI-ELMS',
                ])
                ->get('https://api.github.com/user');

            if (!$userResponse->successful()) {
                Log::error('GitHub User Profile Fetch Failed: ' . $userResponse->body());
                return redirect()->route('login')->withErrors(['email' => 'Could not retrieve profile from GitHub.']);
            }

            $ghUser = $userResponse->json();
            $githubId = (string) ($ghUser['id'] ?? '');
            $githubUsername = $ghUser['login'] ?? null;
            $name = $ghUser['name'] ?? $githubUsername ?? 'GitHub User';
            $avatar = $ghUser['avatar_url'] ?? null;
            $email = !empty($ghUser['email']) ? strtolower(trim($ghUser['email'])) : null;

            // If email is private on GitHub profile, fetch from /user/emails endpoint
            if (empty($email)) {
                $emailsResponse = Http::withoutVerifying()
                    ->timeout(20)
                    ->withHeaders([
                        'Authorization' => "Bearer {$accessToken}",
                        'Accept' => 'application/vnd.github+json',
                        'User-Agent' => 'SPI-ELMS',
                    ])
                    ->get('https://api.github.com/user/emails');

                if ($emailsResponse->successful()) {
                    $emails = $emailsResponse->json();
                    if (is_array($emails)) {
                        // Find primary verified email
                        foreach ($emails as $em) {
                            if (!empty($em['primary']) && !empty($em['verified']) && !empty($em['email'])) {
                                $email = strtolower(trim($em['email']));
                                break;
                            }
                        }
                        // Fallback: any verified email
                        if (empty($email)) {
                            foreach ($emails as $em) {
                                if (!empty($em['verified']) && !empty($em['email'])) {
                                    $email = strtolower(trim($em['email']));
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            // Final fallback email format if user has zero public/accessible emails
            if (empty($email)) {
                $email = strtolower($githubUsername) . '@users.noreply.github.com';
            }

            // Find existing user by github_id or email
            $user = User::where('github_id', $githubId)->first();

            if (!$user) {
                $user = User::where('email', $email)->first();
            }

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
                    'github_id' => $githubId ?: $user->github_id,
                    'github_username' => $githubUsername ?: $user->github_username,
                    'avatar' => $user->avatar ?: $avatar,
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
                    'name' => $name ?: 'GitHub User',
                    'name_kh' => $name ?: 'GitHub User',
                    'email' => $email,
                    'password' => Hash::make(Str::random(32)),
                    'role' => 'student',
                    'student_code' => $studentCode,
                    'study_type' => 'on_campus',
                    'github_id' => $githubId,
                    'github_username' => $githubUsername,
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
                Log::warning('AuthLog create failed for GitHub Login: ' . $logEx->getMessage());
            }

            // Real-time Telegram Alert
            try {
                $groupChatId = config('services.telegram.admin_chat_id') ?: env('TELEGRAM_ADMIN_CHAT_ID') ?: config('services.telegram.chat_id') ?: env('TELEGRAM_CHAT_ID') ?: '-5560385465';

                $safeName = htmlspecialchars($user->name ?: 'GitHub User', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeEmail = htmlspecialchars($user->email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeRole = strtoupper(htmlspecialchars($user->role ?: 'student', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
                $safeIp = htmlspecialchars($ip, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeDevice = htmlspecialchars($device, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeBrowser = htmlspecialchars($browser, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                $telegramService->sendMessage(
                    "<b>🐙 [GITHUB LOGIN ALERT]</b>\n" .
                    "━━━━━━━━━━━━━━━━━━━━━\n" .
                    "👤 <b>User:</b> {$safeName}\n" .
                    "📧 <b>Email:</b> {$safeEmail}\n" .
                    "🎓 <b>Role:</b> {$safeRole}\n" .
                    "🌐 <b>IP Address:</b> <code>{$safeIp}</code>\n" .
                    "📱 <b>Device:</b> {$safeDevice} ({$safeBrowser})\n" .
                    "⏰ <b>Time:</b> " . now()->setTimezone('Asia/Phnom_Penh')->format('Y-m-d h:i:s A') . "\n" .
                    "🛡️ <b>Method:</b> GitHub Single Sign-On (OAuth 2.0)",
                    'HTML',
                    $groupChatId
                );
            } catch (\Throwable $tgEx) {
                Log::warning('Telegram alert notice in GitHub Login: ' . $tgEx->getMessage());
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
                Log::warning('JWT token creation in GitHub Login: ' . $jwtEx->getMessage());
            }

            $redirectUrl = match ($user->role) {
                'admin' => '/admin/dashboard',
                'teacher' => '/teacher/dashboard',
                default => '/student/dashboard',
            };

            return redirect()->intended($redirectUrl);

        } catch (\Throwable $e) {
            Log::error('GitHub Callback Error: ' . $e->getMessage());
            return redirect()->route('login')->withErrors(['email' => 'GitHub Login Exception: ' . $e->getMessage()]);
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
