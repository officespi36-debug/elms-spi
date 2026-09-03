<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\LoginSecurityAlertMail;
use App\Models\AuthLog;
use App\Models\User;
use App\Rules\Turnstile;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request, TelegramService $telegramService)
    {
        // 1. Email / Student ID / Phone, Password & Turnstile Validation
        $request->validate([
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['nullable', 'string', 'in:student,teacher,admin'],
            'turnstile_token' => ['required', new Turnstile],
        ], [
            'email.required' => 'សូមបញ្ចូលអាសយដ្ឋានអ៊ីមែល, ID, ឬលេខទូរស័ព្ទ។',
            'password.required' => 'សូមបញ្ចូលពាក្យសម្ងាត់។',
            'password.min' => 'ពាក្យសម្ងាត់ត្រូវមានយ៉ាងតិច ៨ តួអក្សរ។',
            'turnstile_token.required' => 'សូមផ្ទៀងផ្ទាត់សុវត្ថិភាព Cloudflare (Turnstile) ជាមុនសិន។',
        ]);

        $loginInput = trim($request->email);

        $user = User::where(function ($query) use ($loginInput) {
            $query->where('email', $loginInput)
                ->orWhere('student_code', $loginInput)
                ->orWhere('phone', $loginInput);

            $cleanId = ltrim($loginInput, '#');
            if (is_numeric($cleanId)) {
                $query->orWhere('id', (int) $cleanId);
            }
        })->first();

        // Log Helper
        $ip = $request->ip();
        $userAgent = $request->userAgent() ?? '';
        $device = str_contains(strtolower($userAgent), 'mobile') ? 'Mobile' : 'Desktop';
        $browser = $this->getBrowserName($userAgent);

        if (!$user) {
            AuthLog::create([
                'email' => $loginInput,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'device' => $device,
                'browser' => $browser,
                'status' => 'failed',
            ]);

            return back()->withErrors([
                'email' => 'គណនី ឬពាក្យសម្ងាត់មិនត្រឹមត្រូវទេ។',
            ]);
        }

        // 2. Check if Account is Locked (Failed login attempt threshold)
        if ($user->locked_until && $user->locked_until->isFuture()) {
            $diffMinutes = ceil(now()->diffInSeconds($user->locked_until) / 60);

            AuthLog::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'device' => $device,
                'browser' => $browser,
                'status' => 'locked',
            ]);

            return back()->withErrors([
                'email' => "គណនីត្រូវបានផ្អាកបណ្តោះអាសន្ន! ព្យាយាមខុសច្រើនដង។ សូមព្យាយាមម្តងទៀតក្នុងរយៈពេល {$diffMinutes} នាទី។",
            ]);
        }

        // Reset lock if expired
        if ($user->locked_until && $user->locked_until->isPast()) {
            $user->update([
                'locked_until' => null,
                'login_attempts' => 0,
            ]);
        }

        // 3. Verify Password
        if (!Hash::check($request->password, $user->password)) {
            $attempts = $user->login_attempts + 1;
            $updateData = ['login_attempts' => $attempts];

            if ($attempts >= 5) {
                $updateData['locked_until'] = now()->addMinutes(30);
                $updateData['login_attempts'] = 0;
            }

            $user->update($updateData);

            AuthLog::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'device' => $device,
                'browser' => $browser,
                'status' => 'failed',
            ]);

            if ($attempts >= 5) {
                try {
                    $telegramService->sendMessage(
                        "🚨 <b>ACCOUNT LOCKED (30 MINS)</b>\n" .
                        "━━━━━━━━━━━━━━━━━━━━━\n" .
                        "👤 <b>User:</b> {$user->name}\n" .
                        "📧 <b>Email:</b> {$user->email}\n" .
                        "🌐 <b>IP Address:</b> <code>{$ip}</code>\n" .
                        "📱 <b>Device:</b> {$device} ({$browser})\n" .
                        "⚠️ <b>Reason:</b> 5 consecutive failed password attempts\n" .
                        "⏰ <b>Time:</b> " . now()->setTimezone('Asia/Phnom_Penh')->format('Y-m-d h:i A')
                    );
                } catch (\Throwable $e) {}

                return back()->withErrors([
                    'email' => 'អ្នកបានបញ្ចូលពាក្យសម្ងាត់ខុស ៥ ដង! គណនីត្រូវសោរ ៣០ នាទី។',
                ]);
            }

            if ($attempts >= 3) {
                try {
                    $telegramService->sendMessage(
                        "⚠️ <b>SUSPICIOUS LOGIN ATTEMPT</b>\n" .
                        "━━━━━━━━━━━━━━━━━━━━━\n" .
                        "👤 <b>Target:</b> {$user->name} ({$user->email})\n" .
                        "🌐 <b>Attacker IP:</b> <code>{$ip}</code>\n" .
                        "📱 <b>Device:</b> {$device} ({$browser})\n" .
                        "🔢 <b>Failed Attempt:</b> {$attempts}/5\n" .
                        "⏰ <b>Time:</b> " . now()->setTimezone('Asia/Phnom_Penh')->format('Y-m-d h:i A')
                    );
                } catch (\Throwable $e) {}
            }

            $remaining = 5 - $attempts;
            return back()->withErrors([
                'email' => "ពាក្យសម្ងាត់មិនត្រឹមត្រូវទេ! អ្នកនៅសល់ ៖ {$remaining} ដងទៀតមុនពេលគណនីត្រូវបានសោរ។",
            ]);
        }

        // 4. Account Status Verification
        if ($user->is_active === false || $user->status === 'inactive') {
            return back()->withErrors([
                'email' => 'គណនីរបស់អ្នកត្រូវបានបិទដំណើរការ។',
            ]);
        }

        if ($user->status === 'suspended') {
            return back()->withErrors([
                'email' => 'គណនីរបស់អ្នកត្រូវបានព្យួរជាបណ្តោះអាសន្ន។',
            ]);
        }

        if ($user->status === 'pending_payment') {
            return back()->withErrors([
                'email' => 'គណនីរបស់អ្នកកំពុងរង់ចាំការផ្ទៀងផ្ទាត់ការបង់ប្រាក់។',
            ]);
        }

        // 5. Role Match Check (If specified)
        if ($request->filled('role') && $user->role !== $request->role) {
            return back()->withErrors([
                'email' => "គណនីនេះមិនមែនជា Role {$request->role} ទេ។ Role របស់គណនីគឺ ៖ " . strtoupper($user->role),
            ]);
        }

        // 6. Successful Login
        $user->update([
            'login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
        ]);

        AuthLog::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'device' => $device,
            'browser' => $browser,
            'status' => 'success',
        ]);

        // Dispatch Email & Telegram Notifications in Background AFTER sending response to user
        dispatch(function () use ($user, $ip, $device, $browser, $telegramService) {
            // 1. Dispatch Security Login Alert Email to User (Resend API + Mail Fallback)
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
            } catch (\Throwable $e) {
                Log::warning('Login security alert email failed: ' . $e->getMessage());
            }

            // 2. Telegram Admin & Personal Notifications for Login
            try {
                // Admin Group Notification
                $tg = app(TelegramService::class);
                $adminGroupChatId = config('services.telegram.admin_chat_id') ?: env('TELEGRAM_ADMIN_CHAT_ID') ?: config('services.telegram.chat_id') ?: env('TELEGRAM_CHAT_ID') ?: '-5560385465';

                $safeName = htmlspecialchars($user->name ?: 'User', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeAccount = htmlspecialchars($user->email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeRole = strtoupper(htmlspecialchars($user->role ?: 'user', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
                $safeIp = htmlspecialchars($ip, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeDevice = htmlspecialchars($device, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeBrowser = htmlspecialchars($browser, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeTime = now()->setTimezone('Asia/Phnom_Penh')->format('Y-m-d h:i:s A');

                $tg->sendMessage(
                    "<b>🔑 [PASSWORD LOGIN ALERT]</b>\n" .
                    "━━━━━━━━━━━━━━━━━━━━━\n" .
                    "👤 <b>User:</b> {$safeName}\n" .
                    "📧 <b>Account:</b> {$safeAccount}\n" .
                    "🎓 <b>Role:</b> {$safeRole}\n" .
                    "🌐 <b>IP Address:</b> <code>{$safeIp}</code>\n" .
                    "📱 <b>Device:</b> {$safeDevice} ({$safeBrowser})\n" .
                    "⏰ <b>Time:</b> {$safeTime}\n" .
                    "🛡️ <b>Method:</b> Direct Password Authentication",
                    'HTML',
                    $adminGroupChatId
                );

                // Direct User Private Telegram Notification (if account is linked)
                $userChatId = $user->telegram_id ?: $user->telegram_chat_id;
                if (!empty($userChatId) && (string)$userChatId !== (string)$adminGroupChatId) {
                    $tg->sendDirectMessage(
                        $userChatId,
                        "🛡️ <b>ការជូនដំណឹងសុវត្ថិភាព៖ ការចូលប្រើប្រាស់គណនី</b>\n" .
                        "━━━━━━━━━━━━━━━━━━━━━\n\n" .
                        "សួស្តី <b>" . ($user->name_kh ?: $safeName) . "</b> 👋\n" .
                        "គណនីរបស់អ្នកទើបតែបាន Login ចូលប្រើប្រាស់លើ SPI E-LMS ដោយជោគជ័យ ៖\n\n" .
                        "⏰ <b>ម៉ោង៖</b> " . now()->setTimezone('Asia/Phnom_Penh')->format('d-M-Y h:i A') . "\n" .
                        "📱 <b>ឧបករណ៍៖</b> {$safeDevice} ({$safeBrowser})\n" .
                        "🌐 <b>IP Address៖</b> <code>{$safeIp}</code>\n\n" .
                        "⚠️ <i>ប្រសិនបើមិនមែនជាអ្នកទេ សូមចូលទៅប្តូរពាក្យសម្ងាត់ជាបន្ទាន់!</i>",
                        'HTML'
                    );
                }
            } catch (\Throwable $e) {
                Log::warning('Telegram login notify failed: ' . $e->getMessage());
            }
        })->afterResponse();

        // Generate JWT Token safely
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
        } catch (\Throwable $e) {
            Log::warning('JWT token creation exception: ' . $e->getMessage());
        }

        // Login and regenerate session
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->forget('url.intended');

        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        } elseif ($user->role === 'teacher') {
            return redirect()->route('teacher.dashboard');
        } else {
            return redirect()->route('student.dashboard');
        }
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function getBrowserName($userAgent)
    {
        if (str_contains($userAgent, 'Chrome'))
            return 'Chrome';
        if (str_contains($userAgent, 'Firefox'))
            return 'Firefox';
        if (str_contains($userAgent, 'Safari'))
            return 'Safari';
        if (str_contains($userAgent, 'Edge'))
            return 'Edge';
        return 'Unknown';
    }

    public function sendSecurityAlertEmail(User $user, array $loginDetails): bool
    {
        $email = $user->email;
        if (empty($email)) {
            return false;
        }

        $resendApiKey = config('services.resend.key') ?? env('RESEND_API_KEY');
        $fromAddress = config('mail.from.address') ?? env('MAIL_FROM_ADDRESS', 'info@spilms.tech');
        $fromName = config('mail.from.name') ?? env('MAIL_FROM_NAME', 'Saint Paul Institute (E-LMS)');
        $fromHeader = "{$fromName} <{$fromAddress}>";
        $subject = '🛡️ ការជូនដំណឹងសុវត្ថិភាព៖ ការចូលប្រើប្រាស់គណនីថ្មី | Security Alert: New Login';

        try {
            $mailable = new LoginSecurityAlertMail($user, $loginDetails);
            $htmlContent = $mailable->render();
        } catch (\Throwable $e) {
            Log::warning('LoginSecurityAlertMail render failed: ' . $e->getMessage());
            $htmlContent = "<h2>SPI E-LMS Security Alert</h2><p>A new login to your account was detected.</p>";
        }

        $plainText = "SPI E-LMS - Security Alert: A new login to your account ({$email}) was detected.\nIP: {$loginDetails['ip']}\nDevice: {$loginDetails['device']} ({$loginDetails['browser']})\nTime: {$loginDetails['time']}\nIf this wasn't you, please visit https://spilms.tech/forgot-password immediately.";

        $sent = false;

        // 1. Primary: Resend API direct cURL with IPv4
        if ($resendApiKey && function_exists('curl_init')) {
            for ($attempt = 1; $attempt <= 2; $attempt++) {
                try {
                    $ch = curl_init('https://api.resend.com/emails');
                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST           => true,
                        CURLOPT_IPRESOLVE      => defined('CURL_IPRESOLVE_V4') ? CURL_IPRESOLVE_V4 : 1,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_CONNECTTIMEOUT => 5,
                        CURLOPT_TIMEOUT        => 10,
                        CURLOPT_HTTPHEADER     => [
                            'Authorization: Bearer ' . $resendApiKey,
                            'Content-Type: application/json',
                            'Accept: application/json',
                        ],
                        CURLOPT_POSTFIELDS     => json_encode([
                            'from'    => $fromHeader,
                            'to'      => [$email],
                            'subject' => $subject,
                            'html'    => $htmlContent,
                            'text'    => $plainText,
                        ]),
                    ]);

                    $resCurl = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    if ($httpCode >= 200 && $httpCode < 300) {
                        $sent = true;
                        Log::info("Security alert email sent successfully via Resend to {$email}");
                        break;
                    }
                } catch (\Throwable $curlEx) {
                    Log::warning("Security alert Resend cURL exception: " . $curlEx->getMessage());
                }

                if (!$sent && $attempt < 2) {
                    usleep(200000);
                }
            }
        }

        // 2. Secondary Fallback: Laravel Http Client to Resend API
        if (!$sent && $resendApiKey) {
            try {
                $response = Http::withoutVerifying()
                    ->timeout(8)
                    ->withOptions([
                        'curl' => [
                            CURLOPT_IPRESOLVE      => defined('CURL_IPRESOLVE_V4') ? CURL_IPRESOLVE_V4 : 1,
                            CURLOPT_CONNECTTIMEOUT => 5,
                        ],
                    ])
                    ->withToken($resendApiKey)
                    ->post('https://api.resend.com/emails', [
                        'from'    => $fromHeader,
                        'to'      => [$email],
                        'subject' => $subject,
                        'html'    => $htmlContent,
                        'text'    => $plainText,
                    ]);

                if ($response->successful()) {
                    $sent = true;
                    Log::info("Security alert email sent via Resend Http to {$email}");
                }
            } catch (\Throwable $resendEx) {
                Log::warning("Security alert Resend Http exception: " . $resendEx->getMessage());
            }
        }

        // 3. Tertiary Fallback: Laravel Mail Facade
        if (!$sent) {
            try {
                Mail::to($email)->send(new LoginSecurityAlertMail($user, $loginDetails));
                $sent = true;
                Log::info("Security alert email sent via Mail facade to {$email}");
            } catch (\Throwable $mailEx) {
                Log::error("Security alert Mail fallback error: " . $mailEx->getMessage());
            }
        }

        return $sent;
    }
}
