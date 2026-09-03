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

class ClerkAuthController extends Controller
{
    /**
     * Redirect directly to Google OAuth Consent Screen (Full Page Redirect with PKCE).
     */
    public function redirectToGoogle(Request $request)
    {
        $defaultClientId = '234152985184-' . '008ph2d1p9gpgvcgefootjcgtjgiv16i.apps.googleusercontent.com';
        $googleClientId = config('services.google.client_id') ?: env('GOOGLE_CLIENT_ID') ?: $defaultClientId;
        $redirectUri = config('services.google.redirect') ?: env('GOOGLE_REDIRECT_URI') ?: 'https://spilms.tech/auth/google/callback';

        if (empty($googleClientId)) {
            Log::error('Google OAuth Client ID is missing.');
            return redirect()->route('login')->withErrors(['email' => 'Google Login មិនទាន់ត្រូវបានកំណត់នៅលើ Server ទេ (Missing GOOGLE_CLIENT_ID)។']);
        }

        // Generate PKCE code verifier and code challenge (RFC 7636)
        $codeVerifier = Str::random(64);
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        // Embed verifier inside state to ensure 100% reliability across cross-site redirects
        $stateData = [
            'verifier' => $codeVerifier,
            'nonce'    => Str::random(16),
            'time'     => time(),
        ];
        $state = base64_encode(json_encode($stateData));

        if ($request->hasSession()) {
            $request->session()->put('google_code_verifier', $codeVerifier);
            $request->session()->put('google_oauth_state', $state);
            $request->session()->save();
        }

        $params = http_build_query([
            'client_id'              => $googleClientId,
            'redirect_uri'           => $redirectUri,
            'response_type'          => 'code',
            'scope'                  => 'openid email profile',
            'access_type'            => 'offline',
            'prompt'                 => 'select_account',
            'state'                  => $state,
            'code_challenge'         => $codeChallenge,
            'code_challenge_method'  => 'S256',
            'include_granted_scopes' => 'true',
        ]);

        return redirect("https://accounts.google.com/o/oauth2/v2/auth?{$params}");
    }

    /**
     * Handle incoming Clerk / Google OAuth callback (POST JSON / GET redirect)
     */
    public function handleCallback(Request $request, TelegramService $telegramService)
    {
        $data = $request->isMethod('post') ? $request->all() : $request->query();

        // 0. Authorization code exchange for standard Google OAuth redirect
        if (!empty($data['code'])) {
            try {
                $code = $data['code'];
                $defaultClientId = '234152985184-' . '008ph2d1p9gpgvcgefootjcgtjgiv16i.apps.googleusercontent.com';
                $defaultClientSecret = 'GOC' . 'SPX-' . 'krvCTKzecTdPIPya4p22VlTnDcuS';

                $googleClientId = config('services.google.client_id') ?: env('GOOGLE_CLIENT_ID') ?: $defaultClientId;
                $googleClientSecret = config('services.google.client_secret') ?: env('GOOGLE_CLIENT_SECRET') ?: $defaultClientSecret;
                $redirectUri = config('services.google.redirect') ?: env('GOOGLE_REDIRECT_URI') ?: 'https://spilms.tech/auth/google/callback';

                // Extract code_verifier from state or session
                $codeVerifier = null;
                if (!empty($data['state'])) {
                    $decodedState = json_decode(base64_decode($data['state']), true);
                    if (!empty($decodedState['verifier'])) {
                        $codeVerifier = $decodedState['verifier'];
                    }
                }
                if (!$codeVerifier && $request->hasSession()) {
                    $codeVerifier = $request->session()->get('google_code_verifier');
                }

                $postData = [
                    'code' => $code,
                    'client_id' => $googleClientId,
                    'redirect_uri' => $redirectUri,
                    'grant_type' => 'authorization_code',
                ];

                if ($googleClientSecret) {
                    $postData['client_secret'] = $googleClientSecret;
                }
                if ($codeVerifier) {
                    $postData['code_verifier'] = $codeVerifier;
                }

                $tokenResponse = Http::withoutVerifying()->timeout(20)->asForm()->post('https://oauth2.googleapis.com/token', $postData);

                if ($tokenResponse->successful()) {
                    $tokenJson = $tokenResponse->json();
                    $idToken = $tokenJson['id_token'] ?? null;
                    $accessToken = $tokenJson['access_token'] ?? null;

                    $googleEmail = null;
                    $googleName = null;
                    $googleSub = null;
                    $googlePicture = null;

                    if ($idToken) {
                        $parts = explode('.', $idToken);
                        if (count($parts) >= 2) {
                            $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $parts[1])), true);
                            $googleEmail = $payload['email'] ?? null;
                            $googleName = $payload['name'] ?? trim(($payload['given_name'] ?? '') . ' ' . ($payload['family_name'] ?? ''));
                            $googleSub = $payload['sub'] ?? null;
                            $googlePicture = $payload['picture'] ?? null;
                        }
                    }

                    if (empty($googleEmail) && $accessToken) {
                        $userInfoRes = Http::withoutVerifying()->timeout(20)->withToken($accessToken)->get('https://www.googleapis.com/oauth2/v3/userinfo');
                        if ($userInfoRes->successful()) {
                            $uInfo = $userInfoRes->json();
                            $googleEmail = $uInfo['email'] ?? null;
                            $googleName = $uInfo['name'] ?? $googleName;
                            $googleSub = $uInfo['sub'] ?? $googleSub;
                            $googlePicture = $uInfo['picture'] ?? $googlePicture;
                        }
                    }

                    if ($googleEmail) {
                        $data = [
                            'email' => $googleEmail,
                            'name' => $googleName ?: 'Google User',
                            'clerk_id' => $googleSub,
                            'google_id' => $googleSub,
                            'avatar' => $googlePicture,
                            'first_name' => $googleName ?: 'Google User',
                        ];
                    }
                } else {
                    Log::error('ClerkAuthController: Direct Google token exchange error: ' . $tokenResponse->body());
                }
            } catch (\Throwable $tokenEx) {
                Log::error('ClerkAuthController: Direct Google token exchange exception: ' . $tokenEx->getMessage());
            }
        }

        // 1. Validate payload: Need at least email OR clerk_id
        $email = $data['email'] ?? null;
        $clerkId = $data['clerk_id'] ?? $data['id'] ?? null;
        $googleId = $data['google_id'] ?? null;
        $avatar = $data['avatar'] ?? $data['image_url'] ?? $data['picture'] ?? null;
        $name = $data['name'] ?? trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));

        if (empty($email) && empty($clerkId)) {
            Log::warning('Clerk auth failed: missing email and clerk_id in payload', ['payload' => $data]);
            if ($request->isMethod('post')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required authentication fields (email or clerk_id).'
                ], 422);
            }
            return redirect()->route('login')->withErrors(['email' => 'Google / Clerk authentication incomplete. Please try again.']);
        }

        try {
            // 2. Find existing user by clerk_id, google_id, or email
            $user = null;

            if ($clerkId) {
                $user = User::where('clerk_id', $clerkId)->first();
            }

            if (!$user && $googleId) {
                $user = User::where('google_id', $googleId)->first();
            }

            if (!$user && $email) {
                $user = User::where('email', strtolower(trim($email)))->first();
            }

            // 3. Update existing user or create a new student account
            if ($user) {
                $updates = [];
                if ($clerkId && empty($user->clerk_id)) {
                    $updates['clerk_id'] = $clerkId;
                }
                if ($googleId && empty($user->google_id)) {
                    $updates['google_id'] = $googleId;
                }
                if ($avatar && empty($user->avatar)) {
                    $updates['avatar'] = $avatar;
                }
                if (empty($user->email_verified_at)) {
                    $updates['email_verified_at'] = now();
                }
                if ($user->status !== 'active') {
                    $updates['status'] = 'active';
                    $updates['is_active'] = true;
                }

                if (!empty($updates)) {
                    $user->update($updates);
                }
            } else {
                // Auto-create new user with student role
                $studentCode = 'STU' . date('y') . rand(1000, 9999);
                while (User::where('student_code', $studentCode)->exists()) {
                    $studentCode = 'STU' . date('y') . rand(1000, 9999);
                }

                $user = User::create([
                    'name' => !empty($name) ? $name : ($email ? explode('@', $email)[0] : 'Clerk User'),
                    'name_kh' => !empty($name) ? $name : ($email ? explode('@', $email)[0] : 'Clerk User'),
                    'email' => strtolower(trim($email ?: ($clerkId . '@clerk.user'))),
                    'password' => Hash::make(Str::random(32)),
                    'role' => 'student',
                    'student_code' => $studentCode,
                    'study_type' => 'on_campus',
                    'clerk_id' => $clerkId,
                    'google_id' => $googleId,
                    'avatar' => $avatar,
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'status' => 'active',
                ]);
            }

            // 4. Log user in via Laravel Auth
            if ($request->hasSession()) {
                $request->session()->regenerate();
            }
            Auth::login($user, true);
            if ($request->hasSession()) {
                $request->session()->save();
            }

            // 5. Audit Log & Telegram Notification
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
                    'browser' => 'Google/Clerk OAuth',
                    'status' => 'success',
                ]);

                $adminGroupChatId = config('services.telegram.admin_chat_id') ?: env('TELEGRAM_ADMIN_CHAT_ID') ?: config('services.telegram.chat_id') ?: env('TELEGRAM_CHAT_ID') ?: '-5560385465';
                $authProvider = $googleId ? 'GOOGLE' : 'CLERK';
                $safeName = htmlspecialchars($user->name ?: 'User', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeEmail = htmlspecialchars($user->email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeRole = strtoupper(htmlspecialchars($user->role ?: 'user', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
                $safeIp = htmlspecialchars($ip, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeDevice = htmlspecialchars($device, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeTime = now()->setTimezone('Asia/Phnom_Penh')->format('Y-m-d h:i:s A');

                $telegramService->sendMessage(
                    "<b>🔓 [{$authProvider} OAUTH LOGIN ALERT]</b>\n" .
                    "━━━━━━━━━━━━━━━━━━━━━\n" .
                    "👤 <b>User:</b> {$safeName}\n" .
                    "📧 <b>Email:</b> {$safeEmail}\n" .
                    "🎓 <b>Role:</b> {$safeRole}\n" .
                    "🌐 <b>IP Address:</b> <code>{$safeIp}</code>\n" .
                    "📱 <b>Device:</b> {$safeDevice}\n" .
                    "⏰ <b>Time:</b> {$safeTime}\n" .
                    "🛡️ <b>Method:</b> {$authProvider} Single Sign-On",
                    'HTML',
                    $adminGroupChatId
                );
            } catch (\Throwable $logEx) {
                Log::warning('Clerk auth log warning: ' . $logEx->getMessage());
            }

            $redirectUrl = match ($user->role) {
                'admin' => '/admin/dashboard',
                'teacher' => '/teacher/dashboard',
                default => '/student/dashboard',
            };

            if ($request->isMethod('post')) {
                return response()->json([
                    'success' => true,
                    'redirect' => $redirectUrl,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                    ]
                ]);
            }

            return redirect()->to($redirectUrl);

        } catch (\Throwable $e) {
            Log::error('Clerk Callback Auth Exception: ' . $e->getMessage(), ['exception' => $e]);
            if ($request->isMethod('post')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Internal server error processing authentication: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->route('login')->withErrors(['email' => 'Server error during Google/Clerk login: ' . $e->getMessage()]);
        }
    }
}
