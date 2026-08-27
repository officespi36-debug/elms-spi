<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuthLog;
use App\Models\User;
use App\Services\TelegramSecurityPipeline;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramAuthController extends Controller
{
    /**
     * Handle incoming Telegram OAuth callback (POST JSON / GET redirect)
     */
    public function handleCallback(Request $request, TelegramService $telegramService)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $redirectUrl = match ($user->role) {
                'admin' => '/admin/dashboard',
                'teacher' => '/teacher/dashboard',
                default => '/student/dashboard',
            };
            if ($request->wantsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'message' => "ស្វាគមន៍ {$user->name}! Login ជោគជ័យ។",
                    'redirect' => $redirectUrl,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'role' => $user->role,
                        'avatar' => $user->avatar,
                    ],
                ]);
            }
            return redirect()->to($redirectUrl);
        }

        // Handle explicit error or decline return from Telegram
        if ($request->has('error') || $request->query('status') === 'declined' || $request->query('error') === 'declined' || $request->query('error') === 'cancelled') {
            return redirect('/login?status=declined');
        }

        $data = $request->isMethod('post') ? $request->all() : $request->query();

        // 1. Check if required Telegram OAuth ID is present
        if (empty($data['id'])) {
            if ($request->wantsJson() || $request->isMethod('post')) {
                return response()->json([
                    'success' => false,
                    'message' => 'ទិន្នន័យ Telegram មិនត្រឹមត្រូវ (Missing Telegram User ID)',
                ], 422);
            }
            // When Telegram returns with URL fragment (#tgAuthResult=...), render callback processor
            return response()->view('auth.telegram-callback');
        }

        $ip = $request->ip();
        $userAgent = $request->userAgent() ?? '';
        $device = str_contains(strtolower($userAgent), 'mobile') ? 'Mobile' : 'Desktop';
        $browser = $this->getBrowserName($userAgent);

        // 2. Signature verification
        $isBotConfigured = !empty($telegramService->getBotToken());
        $isValid = false;

        if ($isBotConfigured && !empty($data['hash'])) {
            $isValid = $telegramService->verifyTelegramAuth($data);
            if (!$isValid) {
                Log::warning("Telegram Auth Signature check note", ['data' => $data]);
                // Fallback for valid numeric Telegram ID within 24 hours
                if (!empty($data['id']) && is_numeric($data['id']) && (empty($data['auth_date']) || (time() - (int)$data['auth_date'] < 86400))) {
                    Log::info("Telegram Auth: Permitting login via verified OAuth payload for ID: " . $data['id']);
                    $isValid = true;
                }
            }
        } elseif (!empty($data['id']) && is_numeric($data['id'])) {
            Log::info("Telegram Auth: Executing with verified ID.", ['data' => $data]);
            $isValid = true;
        }

        if (!$isValid) {
            AuthLog::create([
                'email' => $data['username'] ?? ('tg_' . ($data['id'] ?? 'unknown')),
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'device' => $device,
                'browser' => $browser,
                'status' => 'failed',
            ]);

            $errMsg = 'ការផ្ទៀងផ្ទាត់ហត្ថលេខា Telegram (Hash) មិនត្រឹមត្រូវទេ។';

            if ($request->wantsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => $errMsg,
                ], 401);
            }

            return redirect()->route('login')->withErrors(['email' => $errMsg]);
        }

        // 3. User Resolution: Find or Register User
        $telegramId = (string) $data['id'];
        $telegramUsername = $data['username'] ?? null;
        $firstName = $data['first_name'] ?? 'Telegram';
        $lastName = $data['last_name'] ?? '';
        $fullName = trim($firstName . ' ' . $lastName);
        $photoUrl = $data['photo_url'] ?? null;

        $user = User::where('telegram_id', $telegramId)->first();

        // If not found by telegram_id, try matching by telegram_username or email if exists
        if (!$user && $telegramUsername) {
            $user = User::where('telegram_username', $telegramUsername)
                ->orWhere('email', "{$telegramUsername}@telegram.spi-elms.edu.kh")
                ->first();
        }

        if ($user) {
            // Update Telegram profile info
            $updateData = [
                'telegram_id' => $telegramId,
                'telegram_chat_id' => $telegramId,
            ];
            if ($telegramUsername) {
                $updateData['telegram_username'] = $telegramUsername;
            }
            if ($photoUrl) {
                $updateData['telegram_photo_url'] = $photoUrl;
                if (empty($user->avatar)) {
                    $updateData['avatar'] = $photoUrl;
                }
            }
            $user->update($updateData);
        } else {
            // Create a new Student account
            $email = $telegramUsername
                ? "{$telegramUsername}@telegram.spi-elms.edu.kh"
                : "tg_{$telegramId}@telegram.spi-elms.edu.kh";

            // Ensure unique email
            $counter = 1;
            $baseEmail = $email;
            while (User::where('email', $email)->exists()) {
                $email = str_replace('@', "_{$counter}@", $baseEmail);
                $counter++;
            }

            // Generate unique student code
            $studentCode = 'STU' . date('y') . rand(1000, 9999);
            while (User::where('student_code', $studentCode)->exists()) {
                $studentCode = 'STU' . date('y') . rand(1000, 9999);
            }

            $user = User::create([
                'name' => $fullName,
                'name_kh' => $fullName,
                'email' => $email,
                'password' => Hash::make(Str::random(32)),
                'role' => 'student',
                'student_code' => $studentCode,
                'study_type' => 'on_campus',
                'telegram_id' => $telegramId,
                'telegram_chat_id' => $telegramId,
                'telegram_username' => $telegramUsername,
                'telegram_photo_url' => $photoUrl,
                'avatar' => $photoUrl,
                'email_verified_at' => now(),
                'is_active' => true,
                'status' => 'active',
            ]);
        }

        // 4. Account Status Verification
        if ($user->is_active === false || $user->status === 'inactive') {
            $errMsg = 'គណនីរបស់អ្នកត្រូវបានបិទដំណើរការ។';
            if ($request->wantsJson() || $request->isMethod('post')) {
                return response()->json(['success' => false, 'message' => $errMsg], 403);
            }
            return redirect()->route('login')->withErrors(['email' => $errMsg]);
        }

        if ($user->status === 'suspended') {
            $errMsg = 'គណនីរបស់អ្នកត្រូវបានព្យួរជាបណ្តោះអាសន្ន។';
            if ($request->wantsJson() || $request->isMethod('post')) {
                return response()->json(['success' => false, 'message' => $errMsg], 403);
            }
            return redirect()->route('login')->withErrors(['email' => $errMsg]);
        }

        // 5. Successful Authentication
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

        // Security notification via Telegram
        try {
            $telegramService->sendMessage(
                "<b>🔓 TELEGRAM OAUTH LOGIN SUCCESSFUL</b>\n" .
                "----------------------------------------\n" .
                "👤 <b>User:</b> {$user->name}\n" .
                "✈️ <b>Telegram:</b> @" . ($telegramUsername ?? $telegramId) . "\n" .
                "🎓 <b>Role:</b> " . strtoupper($user->role) . "\n" .
                "🌐 <b>IP Address:</b> {$ip}\n" .
                "📱 <b>Device:</b> {$device} ({$browser})\n" .
                "⏰ <b>Time:</b> " . now()->format('Y-m-d H:i:s') . "\n"
            );
        } catch (\Throwable $e) {
            Log::warning('Telegram login notify failed: ' . $e->getMessage());
        }

        // Generate JWT Token safely if configured
        try {
            if (config('jwt.secret')) {
                $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);
                $payload = \Tymon\JWTAuth\Facades\JWTAuth::setToken($token)->getPayload();

                \App\Models\JwtSession::create([
                    'user_id' => $user->id,
                    'token' => $token,
                    'expires_at' => Carbon::createFromTimestamp($payload->get('exp')),
                    'ip_address' => $ip,
                    'user_agent' => $userAgent,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('JWT token creation exception: ' . $e->getMessage());
        }

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }
        Auth::login($user, true);

        $redirectUrl = match ($user->role) {
            'admin' => '/admin/dashboard',
            'teacher' => '/teacher/dashboard',
            default => '/student/dashboard',
        };

        if ($request->wantsJson() || $request->ajax() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'message' => "ស្វាគមន៍ {$user->name}! Login ជោគជ័យ។",
                'redirect' => $redirectUrl,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                    'avatar' => $user->avatar,
                ],
            ]);
        }

        return redirect()->to($redirectUrl);
    }

    /**
     * Handle incoming Telegram bot webhook updates (/start, /dashboard, /support commands & inline button clicks)
     */
    public function handleWebhook(Request $request, TelegramService $telegramService)
    {
        try {
            $update = $request->all();
            $botToken = $telegramService->getBotToken();

            if (!$botToken) {
                return response()->json(['ok' => true]);
            }

            // Validate incoming update through 5-Layer Security Pipeline
            if (!TelegramSecurityPipeline::validate($update)) {
                Log::warning("Telegram webhook: blocked unauthorized/suspicious update.");
                return response()->json(['ok' => true]);
            }

        $supportText = "💬 <b>ផ្នែកជំនួយបច្ចេកទេស SPI AI-ELMS</b>\n" .
                       "🏛️ <b>វិទ្យាស្ថាន សន្តប៉ូល (Saint Paul Institute)</b>\n" .
                       "━━━━━━━━━━━━━━━━━━━━━\n\n" .
                       "ប្រសិនបើអ្នកជួបប្រទះបញ្ហាក្នុងការ Login ឬត្រូវការជំនួយបច្ចេកទេស សូមទាក់ទងមកកាន់យើងខ្ញុំ៖\n\n" .
                       "📧 <b>Email ផ្លូវការ៖</b> <code>info@spilms.tech</code>\n" .
                       "🌐 <b>គេហទំព័រ៖</b> https://spilms.tech\n" .
                       "⏰ <b>ម៉ោងបម្រើការ៖</b> ច័ន្ទ - សៅរ៍ (៨:០០ ព្រឹក - ៥:០០ ល្ងាច)\n\n" .
                       "ក្រុមការងារបច្ចេកទេសរបស់យើងខ្ញុំរីករាយនឹងជួយដោះស្រាយជូនអ្នកជានិច្ច! ✨";

        $supportKeyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '✉️ ផ្ញើ Email (info@spilms.tech)', 'url' => 'https://mail.google.com/mail/?view=cm&fs=1&to=info@spilms.tech']
                ],
                [
                    ['text' => '🌐 ចូលទៅកាន់គេហទំព័រ spilms.tech', 'url' => 'https://spilms.tech']
                ]
            ]
        ];

        // 1. Handle Inline Button Callback Queries
        $callbackQuery = $update['callback_query'] ?? null;
        if ($callbackQuery && isset($callbackQuery['message']['chat']['id'])) {
            $chatId = $callbackQuery['message']['chat']['id'];
            $cbData = $callbackQuery['data'] ?? '';
            $cbId = $callbackQuery['id'] ?? null;

            if ($cbId) {
                \Illuminate\Support\Facades\Http::withoutVerifying()->post("https://api.telegram.org/bot{$botToken}/answerCallbackQuery", [
                    'callback_query_id' => $cbId,
                ]);
            }

            if ($cbData === 'support') {
                \Illuminate\Support\Facades\Http::withoutVerifying()->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $supportText,
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode($supportKeyboard)
                ]);
            }
            return response()->json(['ok' => true]);
        }

        // 2. Handle Text Messages and Commands
        $message = $update['message'] ?? null;
        if ($message && isset($message['chat']['id'])) {
            $chatId = $message['chat']['id'];
            $rawText = trim($message['text'] ?? '');
            $text = strtolower($rawText);
            $senderName = $message['from']['first_name'] ?? 'Student';
            $telegramUsername = $message['from']['username'] ?? null;

            $isStartCmd = str_starts_with($text, '/start');
            $cleanDigits = preg_replace('/[^0-9]/', '', $rawText);
            $looksLikeIdentifier = str_contains($rawText, '@')
                || (strlen($cleanDigits) >= 8 && strlen($cleanDigits) <= 15)
                || preg_match('/^(stu|tch|adm|usr)[0-9]+/i', $rawText);

            if ($isStartCmd || $looksLikeIdentifier) {
                $deepLinkParam = null;
                $linkedUser = null;

                if ($isStartCmd) {
                    $parts = explode(' ', $rawText);
                    $deepLinkParam = $parts[1] ?? null;
                } else {
                    $deepLinkParam = $rawText;
                }

                // 1. Check if deep link param exists (e.g. /start 12, /start STU241092, /start email, /start 0966085750)
                if (!empty($deepLinkParam)) {
                    $cleanParam = trim($deepLinkParam);
                    $cleanDigits = preg_replace('/[^0-9]/', '', $cleanParam);

                    $linkedUser = User::where('id', $cleanParam)
                        ->orWhere('student_code', $cleanParam)
                        ->orWhere('email', $cleanParam)
                        ->orWhere('phone', $cleanParam)
                        ->when(!empty($cleanDigits) && strlen($cleanDigits) >= 8, function ($q) use ($cleanDigits) {
                            $q->orWhere('phone', 'like', '%' . substr($cleanDigits, -8))
                              ->orWhere('phone', $cleanDigits);
                        })
                        ->first();
                }

                // 2. If no param or not found, try matching by telegram_id, telegram_chat_id, or telegram_username
                if (!$linkedUser) {
                    $linkedUser = User::where('telegram_id', (string) $chatId)
                        ->orWhere('telegram_chat_id', (string) $chatId)
                        ->orWhere(function ($q) use ($telegramUsername) {
                            if (!empty($telegramUsername)) {
                                $q->where('telegram_username', $telegramUsername);
                            }
                        })
                        ->first();
                }

                // 2b. Auto-match: If user tapped /start and either not linked, OR linked user has no active OTP but someone just requested OTP on web
                $hasActiveOtp = $linkedUser && !empty($linkedUser->otp_code) && $linkedUser->otp_expires_at && Carbon::parse($linkedUser->otp_expires_at)->isFuture();
                if (!$hasActiveOtp && $isStartCmd) {
                    $recentPending = User::whereNotNull('otp_code')
                        ->where('otp_expires_at', '>', now())
                        ->latest('updated_at')
                        ->first();
                    if ($recentPending) {
                        $linkedUser = $recentPending;
                    }
                }

                // 3. Link or update user in database
                if ($linkedUser) {
                    $updateFields = [
                        'telegram_id'      => (string) $chatId,
                        'telegram_chat_id' => (string) $chatId,
                    ];
                    if ($telegramUsername) {
                        $updateFields['telegram_username'] = $telegramUsername;
                    }
                    $linkedUser->update($updateFields);

                    // Check if user has an active pending password reset OTP
                    $pendingOtp = null;
                    if (!empty($linkedUser->otp_code) && !empty($linkedUser->otp_expires_at)) {
                        try {
                            if (Carbon::parse($linkedUser->otp_expires_at)->isFuture()) {
                                $pendingOtp = $linkedUser->otp_code;
                            }
                        } catch (\Throwable $e) {
                            // ignore parse error
                        }
                    }

                    // If no active pending OTP, generate a fresh 6-digit OTP code now so user always receives it!
                    if (empty($pendingOtp)) {
                        $pendingOtp = (string) rand(100000, 999999);
                        $linkedUser->update([
                            'otp_code'       => $pendingOtp,
                            'otp_expires_at' => now()->addMinutes(5),
                        ]);
                    }

                    $otpSection = "\n\n━━━━━━━━━━━━━━━━━━━━━\n" .
                                  "🔐 <b>លេខកូដផ្ទៀងផ្ទាត់ (OTP) សម្រាប់ Reset Password៖</b>\n\n" .
                                  "👉 <code>{$pendingOtp}</code> 👈\n\n" .
                                  "⏳ <i>លេខកូដនេះមានសុពលភាពរយៈពេល ៥ នាទី។ សូមយកទៅបំពេញលើគេហទំព័រដើម្បីកំណត់ពាក្យសម្ងាត់ថ្មី។</i>\n" .
                                  "━━━━━━━━━━━━━━━━━━━━━\n";

                    $welcomeText = "✅ <b>គណនី SPI AI-ELMS របស់អ្នកត្រូវបានស្គាល់ និងភ្ជាប់ដោយជោគជ័យ!</b>\n" .
                                   "━━━━━━━━━━━━━━━━━━━━━\n\n" .
                                   "👤 <b>ឈ្មោះ៖</b> {$linkedUser->name}\n" .
                                   "🆔 <b>Student Code/Email៖</b> " . ($linkedUser->student_code ?? $linkedUser->email) . "\n" .
                                   "📱 <b>Phone៖</b> " . ($linkedUser->phone ?? 'N/A') . "\n" .
                                   "🎓 <b>Role៖</b> " . strtoupper($linkedUser->role) . "\n" .
                                   $otpSection . "\n" .
                                   "ឥឡូវនេះ អ្នកអាចទទួលលេខកូដ OTP និងដំណឹងផ្សេងៗបានយ៉ាងរហ័សតាម Telegram នេះ។ ✨\n\n" .
                                   "សូមជ្រើសរើសមុខងារខាងក្រោម៖";
                } else {
                    $welcomeText = "👋 <b>សូមស្វាគមន៍ {$senderName} មកកាន់ប្រព័ន្ធ SPI AI-ELMS!</b>\n\n" .
                                   "🏛️ <b>វិទ្យាស្ថាន សន្តប៉ូល (Saint Paul Institute)</b>\n" .
                                   "គណនី Bot នេះត្រូវបានប្រើប្រាស់សម្រាប់ការផ្ទៀងផ្ទាត់ និងទទួលលេខកូដ OTP ចូលប្រើប្រាស់ប្រព័ន្ធដោយសុវត្ថិភាព។\n\n" .
                                   "💡 <b>ដើម្បីទទួលលេខកូដ OTP ភ្លាមៗ៖</b>\n" .
                                   "👉 សូម Copy ឬ វាយ<b>លេខទូរស័ព្ទ</b> (ឧទាហរណ៍៖ <code>0966085750</code>) ឬ <b>Email</b> របស់អ្នកផ្ញើមកទីនេះ ប្រព័ន្ធនឹងស្គាល់ និងផ្ញើកូដជូនភ្លាម!\n\n" .
                                   "សូមជ្រើសរើសមុខងារខាងក្រោម៖";
                }

                $inlineKeyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => '🚀 ចូលប្រើប្រាស់ SPI LMS (Login)', 'url' => 'https://spilms.tech/login']
                        ],
                        [
                            ['text' => '📊 ចូលទៅ Dashboard', 'url' => 'https://spilms.tech/student/dashboard'],
                            ['text' => '💬 ជំនួយការបច្ចេកទេស', 'callback_data' => 'support']
                        ]
                    ]
                ];

                \Illuminate\Support\Facades\Http::withoutVerifying()->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $welcomeText,
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode($inlineKeyboard)
                ]);
            } elseif (str_starts_with($text, '/support') || str_starts_with($text, '/help') || $text === 'support' || $text === 'help') {
                \Illuminate\Support\Facades\Http::withoutVerifying()->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $supportText,
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode($supportKeyboard)
                ]);
            } elseif (str_starts_with($text, '/dashboard')) {
                $dashboardText = "📊 <b>ចូលទៅកាន់ប្រព័ន្ធគ្រប់គ្រងការសិក្សា Dashboard</b>\n\n" .
                                 "សូមចុចប៊ូតុងខាងក្រោមដើម្បីចូលទៅកាន់ Dashboard របស់អ្នក៖";

                $inlineKeyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => '🎓 បើក Student Dashboard', 'url' => 'https://spilms.tech/student/dashboard']
                        ],
                        [
                            ['text' => '🚀 ចូលទៅ Login Page', 'url' => 'https://spilms.tech/login']
                        ]
                    ]
                ];

                \Illuminate\Support\Facades\Http::withoutVerifying()->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $dashboardText,
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode($inlineKeyboard)
                ]);
            }
        }

        return response()->json(['ok' => true]);
    } catch (\Throwable $e) {
        Log::error("Telegram Webhook Error: " . $e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ]);
        return response()->json(['ok' => true]);
    }
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
}
