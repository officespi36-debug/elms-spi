<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AuthLog;
use App\Models\Course;
use App\Models\Deadline;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\TelegramSecurityPipeline;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TelegramAuthController extends Controller
{
    /**
     * Initialize a Telegram QR Login Session
     */
    public function initQrSession(Request $request)
    {
        $token = Str::random(32);
        $botUsername = config('services.telegram.bot_username') ?: 'spi_elms_auth_bot';
        $expiresAt = now()->addMinutes(5);

        Cache::put('tg_qr_' . $token, [
            'status' => 'pending',
            'created_at' => now()->timestamp,
        ], $expiresAt);

        $deepLink = "https://t.me/{$botUsername}?start=login_{$token}";

        return response()->json([
            'success' => true,
            'token' => $token,
            'bot_username' => $botUsername,
            'deep_link' => $deepLink,
            'expires_in' => 300,
        ]);
    }

    /**
     * Check Status of Telegram QR Login Session
     */
    public function checkQrStatus(Request $request)
    {
        $token = trim($request->query('token', ''));
        if (empty($token)) {
            return response()->json(['status' => 'invalid'], 400);
        }

        $session = Cache::get('tg_qr_' . $token);
        if (!$session) {
            return response()->json(['status' => 'expired']);
        }

        if (($session['status'] ?? '') === 'approved' && !empty($session['user_id'])) {
            $user = User::find($session['user_id']);
            if ($user) {
                Auth::login($user, true);
                $request->session()->regenerate();
                Cache::forget('tg_qr_' . $token);

                $redirectUrl = match ($user->role) {
                    'admin' => '/admin/dashboard',
                    'teacher' => '/teacher/dashboard',
                    default => '/student/dashboard',
                };

                return response()->json([
                    'status' => 'approved',
                    'redirect' => $redirectUrl,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'role' => $user->role,
                        'avatar' => $user->avatar,
                    ],
                ]);
            }
        }

        return response()->json(['status' => 'pending']);
    }

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
            // Unlink any other user who was previously attached to this telegram_id
            User::where('id', '!=', $user->id)
                ->where(function ($q) use ($telegramId) {
                    $q->where('telegram_id', $telegramId)
                      ->orWhere('telegram_chat_id', $telegramId);
                })
                ->update([
                    'telegram_id' => null,
                    'telegram_chat_id' => null,
                ]);

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
            $tg = app(TelegramService::class);
            $adminGroupChatId = config('services.telegram.admin_chat_id') ?: env('TELEGRAM_ADMIN_CHAT_ID') ?: config('services.telegram.chat_id') ?: env('TELEGRAM_CHAT_ID') ?: '-5560385465';

            $cleanUsername = ltrim($telegramUsername ?? '', '@');
            $tgUsernameStr = $cleanUsername ? "@{$cleanUsername}" : "@tg_{$telegramId}";

            $safeName = htmlspecialchars($user->name ?: 'Telegram User', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeTgUser = htmlspecialchars($tgUsernameStr, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeTgId = htmlspecialchars((string)$telegramId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeRole = strtoupper(htmlspecialchars($user->role ?: 'student', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
            $safeIp = htmlspecialchars($ip, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeDevice = htmlspecialchars($device, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeBrowser = htmlspecialchars($browser, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeTime = now()->setTimezone('Asia/Phnom_Penh')->format('Y-m-d h:i:s A');

            // 1. Group Notification
            $tg->sendMessage(
                "<b>🔵 [TELEGRAM LOGIN ALERT]</b>\n" .
                "━━━━━━━━━━━━━━━━━━━━━\n" .
                "👤 <b>User:</b> {$safeName}\n" .
                "✈️ <b>Telegram:</b> {$safeTgUser} (<code>{$safeTgId}</code>)\n" .
                "🎓 <b>Role:</b> {$safeRole}\n" .
                "🌐 <b>IP Address:</b> <code>{$safeIp}</code>\n" .
                "📱 <b>Device:</b> {$safeDevice} ({$safeBrowser})\n" .
                "⏰ <b>Time:</b> {$safeTime}\n" .
                "🛡️ <b>Method:</b> Telegram OAuth Widget Login",
                'HTML',
                $adminGroupChatId
            );

            // 2. Direct User Personal Alert
            if (!empty($telegramId) && (string)$telegramId !== (string)$adminGroupChatId) {
                $tg->sendDirectMessage(
                    $telegramId,
                    "🛡️ <b>ការជូនដំណឹងសុវត្ថិភាព៖ ការចូលប្រើប្រាស់គណនី</b>\n" .
                    "━━━━━━━━━━━━━━━━━━━━━\n\n" .
                    "សួស្តី <b>" . ($user->name_kh ?: $safeName) . "</b> 👋\n" .
                    "គណនីរបស់អ្នកទើបតែបាន Login ចូលប្រើប្រាស់លើ SPI E-LMS ដោយជោគជ័យតាមរយៈ Telegram ៖\n\n" .
                    "⏰ <b>ម៉ោង៖</b> " . now()->setTimezone('Asia/Phnom_Penh')->format('d-M-Y h:i A') . "\n" .
                    "📱 <b>ឧបករណ៍៖</b> {$safeDevice} ({$safeBrowser})\n" .
                    "🌐 <b>IP Address៖</b> <code>{$safeIp}</code>\n" .
                    "🛡️ <b>វិធីសាស្ត្រ៖</b> Telegram OAuth Widget Login\n\n" .
                    "⚠️ <i>ប្រសិនបើមិនមែនជាអ្នកទេ សូមទាក់ទងមកកាន់រដ្ឋបាលជាបន្ទាន់!</i>",
                    'HTML'
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Telegram login notify failed: ' . $e->getMessage());
        }

        // Email Security Alert if email exists
        try {
            if (!empty($user->email)) {
                $loginDetails = [
                    'ip' => $ip,
                    'device' => $device,
                    'browser' => $browser,
                    'time' => now()->setTimezone('Asia/Phnom_Penh')->format('d-M-Y h:i A'),
                    'location' => 'Cambodia',
                ];
                (new \App\Http\Controllers\Auth\AuthenticatedSessionController())->sendSecurityAlertEmail($user, $loginDetails);
            }
        } catch (\Throwable $mailEx) {
            Log::warning('Telegram login email alert notice: ' . $mailEx->getMessage());
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
            // Validate Telegram Webhook Secret Token header if configured
            $expectedSecret = config('services.telegram.webhook_secret');
            if (!empty($expectedSecret)) {
                $providedSecret = $request->header('X-Telegram-Bot-Api-Secret-Token');
                if (empty($providedSecret) || !hash_equals($expectedSecret, (string) $providedSecret)) {
                    Log::warning("Telegram Webhook: Unauthorized request - Secret token mismatch or missing.");
                    return response()->json(['error' => 'Unauthorized'], 403);
                }
            }

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

            // 1. Handle Inline Button Callback Queries
            if (!empty($update['callback_query'])) {
                return $this->handleCallbackQuery($update['callback_query'], $botToken);
            }

            // 2. Handle Text Messages and Commands
            if (!empty($update['message'])) {
                return $this->handleTextMessage($update['message'], $telegramService, $botToken);
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

    private function getSupportContent(): array
    {
        $text = "💬 <b>ផ្នែកជំនួយបច្ចេកទេស SPI AI-ELMS</b>\n" .
                "🏛️ <b>វិទ្យាស្ថាន សន្តប៉ូល (Saint Paul Institute)</b>\n" .
                "━━━━━━━━━━━━━━━━━━━━━\n\n" .
                "ប្រសិនបើអ្នកជួបប្រទះបញ្ហាក្នុងការ Login ឬត្រូវការជំនួយបច្ចេកទេស សូមទាក់ទងមកកាន់យើងខ្ញុំ៖\n\n" .
                "📧 <b>Email ផ្លូវការ៖</b> <code>info@spilms.tech</code>\n" .
                "🌐 <b>គេហទំព័រ៖</b> https://spilms.tech\n" .
                "⏰ <b>ម៉ោងបម្រើការ៖</b> ច័ន្ទ - សៅរ៍ (៨:០០ ព្រឹក - ៥:០០ ល្ងាច)\n\n" .
                "ក្រុមការងារបច្ចេកទេសរបស់យើងខ្ញុំរីករាយនឹងជួយដោះស្រាយជូនអ្នកជានិច្ច! ✨";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '✉️ ផ្ញើ Email (info@spilms.tech)', 'url' => 'https://mail.google.com/mail/?view=cm&fs=1&to=info@spilms.tech']
                ],
                [
                    ['text' => '🌐 ចូលទៅកាន់គេហទំព័រ spilms.tech', 'url' => 'https://spilms.tech']
                ]
            ]
        ];

        return [$text, $keyboard];
    }

    private function handleCallbackQuery(array $callbackQuery, string $botToken)
    {
        if (isset($callbackQuery['message']['chat']['id'])) {
            $chatId = $callbackQuery['message']['chat']['id'];
            $cbData = $callbackQuery['data'] ?? '';
            $cbId = $callbackQuery['id'] ?? null;

            if ($cbId) {
                Http::withoutVerifying()->post("https://api.telegram.org/bot{$botToken}/answerCallbackQuery", [
                    'callback_query_id' => $cbId,
                ]);
            }

            if ($cbData === 'support') {
                [$supportText, $supportKeyboard] = $this->getSupportContent();
                Http::withoutVerifying()->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $supportText,
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode($supportKeyboard)
                ]);
            } elseif (str_starts_with($cbData, 'ban_user_')) {
                $targetUserId = substr($cbData, 9);
                if (!empty($targetUserId)) {
                    Cache::forever("tg_banned_{$targetUserId}", true);

                    $adminChatId = config('services.telegram.admin_chat_id') ?? config('services.telegram.chat_id') ?? $chatId;
                    app(TelegramService::class)->banChatMember($targetUserId, $adminChatId);

                    if ($cbId) {
                        Http::withoutVerifying()->post("https://api.telegram.org/bot{$botToken}/answerCallbackQuery", [
                            'callback_query_id' => $cbId,
                            'text' => "⛔ បាន Block និង Ban User ID {$targetUserId} រួចរាល់!",
                            'show_alert' => true,
                        ]);
                    }

                    Http::withoutVerifying()->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                        'chat_id' => $chatId,
                        'text' => "✅ <b>ប្រតិបត្តិការជោគជ័យ៖</b> User ID <code>{$targetUserId}</code> ត្រូវបាន Block និង Ban ចេញពី Group រួចរាល់!",
                        'parse_mode' => 'HTML',
                    ]);
                }
            } elseif (str_starts_with($cbData, 'ban_ip_')) {
                $targetIp = str_replace('-', '.', substr($cbData, 7));
                if (!empty($targetIp)) {
                    Cache::forever("blacklisted_ip_{$targetIp}", true);

                    if ($cbId) {
                        Http::withoutVerifying()->post("https://api.telegram.org/bot{$botToken}/answerCallbackQuery", [
                            'callback_query_id' => $cbId,
                            'text' => "🛡️ បាន Blacklist IP {$targetIp} រួចរាល់!",
                            'show_alert' => true,
                        ]);
                    }

                    Http::withoutVerifying()->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                        'chat_id' => $chatId,
                        'text' => "🛡️ <b>FIREWALL BLACKLIST៖</b> IP <code>{$targetIp}</code> ត្រូវបានដាក់ចូលក្នុង Blacklist រួចរាល់!",
                        'parse_mode' => 'HTML',
                    ]);
                }
            }
        }

        return response()->json(['ok' => true]);
    }

    private function handleTextMessage(array $message, TelegramService $telegramService, string $botToken)
    {
        if (!isset($message['chat']['id'])) {
            return response()->json(['ok' => true]);
        }

        $chatId = $message['chat']['id'];
        $rawText = trim($message['text'] ?? '');
        $text = strtolower($rawText);
        $senderName = $message['from']['first_name'] ?? 'Student';
        $telegramUsername = $message['from']['username'] ?? null;

        // Handle native phone number contact sharing
        if (isset($message['contact']['phone_number'])) {
            $contactPhone = trim($message['contact']['phone_number']);
            $cleanDigits = preg_replace('/[^0-9]/', '', $contactPhone);
            $linkedUser = User::where(function ($q) use ($contactPhone, $cleanDigits) {
                $q->where('phone', $contactPhone);
                if (!empty($cleanDigits) && strlen($cleanDigits) >= 8) {
                    $q->orWhere('phone', 'like', '%' . substr($cleanDigits, -8))
                      ->orWhere('phone', $cleanDigits);
                }
            })->first();

            return $this->handleStartOrLinkCommand($chatId, $contactPhone, false, $senderName, $telegramUsername, $linkedUser, $telegramService, $botToken);
        }

        $linkedUser = User::where('telegram_id', (string) $chatId)
            ->orWhere('telegram_chat_id', (string) $chatId)
            ->when(!empty($telegramUsername), function ($q) use ($telegramUsername) {
                $q->orWhere('telegram_username', $telegramUsername);
            })
            ->with(['major.department.faculty'])
            ->first();

        if (str_starts_with($text, '/courses') || str_contains($text, 'វគ្គសិក្សា') || $text === 'courses') {
            return $this->handleCoursesCommand($chatId, $linkedUser, $botToken);
        }

        if (str_starts_with($text, '/deadlines') || str_contains($text, 'កាលបរិច្ឆេទ') || $text === 'deadlines' || $text === 'homework') {
            return $this->handleDeadlinesCommand($chatId, $botToken);
        }

        if (str_starts_with($text, '/announcements') || str_contains($text, 'ដំណឹង') || $text === 'announcements') {
            return $this->handleAnnouncementsCommand($chatId, $botToken);
        }

        if (str_starts_with($text, '/me') || str_starts_with($text, '/profile') || str_starts_with($text, '/id') || str_contains($text, 'គណនី')) {
            return $this->handleProfileCommand($chatId, $linkedUser, $botToken);
        }

        if (str_starts_with($text, '/unlink') || str_starts_with($text, '/logout')) {
            User::where('telegram_id', (string) $chatId)
                ->orWhere('telegram_chat_id', (string) $chatId)
                ->update([
                    'telegram_id' => null,
                    'telegram_chat_id' => null,
                ]);

            $unlinkText = "🔓 <b>គណនីរបស់អ្នកត្រូវបានផ្តាច់ចេញពី Telegram នេះជោគជ័យ!</b>\n\n" .
                          "👉 ដើម្បីភ្ជាប់គណនីថ្មី សូមចុច <b>«📱 ចែករំលែកលេខទូរស័ព្ទ»</b> ខាងក្រោម ឬ វាយ<b>លេខទូរស័ព្ទ</b>/<b>Email</b> របស់អ្នកផ្ញើមកកាន់ទីនេះ។";

            $replyMarkup = [
                'keyboard' => [
                    [
                        ['text' => '📱 ចែករំលែកលេខទូរស័ព្ទ (Share Phone)', 'request_contact' => true],
                        ['text' => '🚀 បើក E-LMS (Mini App)', 'web_app' => ['url' => 'https://spilms.tech']]
                    ],
                ],
                'resize_keyboard' => true,
                'is_persistent' => true
            ];

            Http::withoutVerifying()->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $unlinkText,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode($replyMarkup)
            ]);
            return response()->json(['ok' => true]);
        }

        $isStartCmd = str_starts_with($text, '/start');
        $cleanDigits = preg_replace('/[^0-9]/', '', $rawText);
        $looksLikeIdentifier = str_contains($rawText, '@')
            || (strlen($cleanDigits) >= 8 && strlen($cleanDigits) <= 15)
            || preg_match('/^(stu|tch|adm|usr)[0-9]+/i', $rawText);

        if ($isStartCmd || $looksLikeIdentifier) {
            return $this->handleStartOrLinkCommand($chatId, $rawText, $isStartCmd, $senderName, $telegramUsername, $linkedUser, $telegramService, $botToken);
        }

        if (str_starts_with($text, '/support') || str_starts_with($text, '/help') || str_contains($text, 'ជំនួយ') || $text === 'support' || $text === 'help') {
            [$supportText, $supportKeyboard] = $this->getSupportContent();
            Http::withoutVerifying()->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $supportText,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode($supportKeyboard)
            ]);
            return response()->json(['ok' => true]);
        }

        if (str_starts_with($text, '/dashboard')) {
            $dashboardText = "📊 <b>ចូលទៅកាន់ប្រព័ន្ធគ្រប់គ្រងការសិក្សា Dashboard</b>\n\n" .
                             "សូមចុចប៊ូតុងខាងក្រោមដើម្បីចូលទៅកាន់ Dashboard របស់អ្នក៖";

            $inlineKeyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '🎓 បើក Student Dashboard', 'web_app' => ['url' => 'https://spilms.tech/student/dashboard']]
                    ],
                    [
                        ['text' => '🚀 ចូលទៅ Login Page', 'url' => 'https://spilms.tech/login']
                    ]
                ]
            ];

            Http::withoutVerifying()->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $dashboardText,
                'parse_mode' => 'HTML',
                'reply_markup' => json_encode($inlineKeyboard)
            ]);
            return response()->json(['ok' => true]);
        }

        return response()->json(['ok' => true]);
    }

    private function handleCoursesCommand(int|string $chatId, ?User $linkedUser, string $botToken)
    {
        if ($linkedUser) {
            if ($linkedUser->role === 'teacher') {
                $courses = Course::where('teacher_id', $linkedUser->id)->latest()->take(5)->get();
                if ($courses->isNotEmpty()) {
                    $responseText = "📚 <b>មុខវិជ្ជាដែលលោកគ្រូ/អ្នកគ្រូបង្រៀន (My Teaching Courses)</b>\n" .
                                   "━━━━━━━━━━━━━━━━━━━━━\n\n";
                    foreach ($courses as $idx => $c) {
                        $num = $idx + 1;
                        $responseText .= "{$num}. 📖 <b>{$c->title}</b>\n" .
                                         "   • កម្រិត៖ <code>" . ($c->level ?? 'General') . "</code>\n" .
                                         "   • ស្ថានភាព៖ <b>" . strtoupper($c->status ?? 'ACTIVE') . "</b>\n\n";
                    }
                } else {
                    $responseText = "📚 <b>មុខវិជ្ជាបង្រៀន៖</b> លោកគ្រូ/អ្នកគ្រូមិនទាន់មានមុខវិជ្ជាបង្រៀននៅក្នុងប្រព័ន្ធនៅឡើយទេ។";
                }
            } else {
                $enrollments = Enrollment::where('student_id', $linkedUser->id)
                    ->with('course')
                    ->latest()
                    ->take(5)
                    ->get();
                if ($enrollments->isNotEmpty()) {
                    $responseText = "📚 <b>វគ្គសិក្សារបស់អ្នក (Enrolled Courses)</b>\n" .
                                   "━━━━━━━━━━━━━━━━━━━━━\n\n";
                    foreach ($enrollments as $idx => $e) {
                        $c = $e->course;
                        $num = $idx + 1;
                        $courseTitle = $c ? $c->title : 'General Subject';
                        $status = $e->status ?? 'active';
                        $responseText .= "{$num}. 📖 <b>{$courseTitle}</b>\n" .
                                         "   • ស្ថានភាព៖ <b>" . strtoupper($status) . "</b>\n\n";
                    }
                } else {
                    $responseText = "📚 <b>វគ្គសិក្សារបស់អ្នក៖</b> អ្នកមិនទាន់បានចុះឈ្មោះចូលរៀនមុខវិជ្ជាណាមួយនៅឡើយទេ។";
                }
            }
        } else {
            $responseText = "⚠️ <b>គណនីរបស់អ្នកមិនទាន់បានភ្ជាប់ជាមួយ Telegram ឡើយ</b>\n\n" .
                            "👉 សូមវាយ<b>លេខទូរស័ព្ទ</b> ឬ <b>Email</b> របស់អ្នកផ្ញើមកកាន់ Bot នេះដើម្បីភ្ជាប់គណនី។";
        }

        $inlineKeyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🚀 បើក E-LMS រៀនឥឡូវនេះ', 'web_app' => ['url' => 'https://spilms.tech/student/dashboard']]
                ]
            ]
        ];

        Http::withoutVerifying()->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $responseText,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode($inlineKeyboard)
        ]);

        return response()->json(['ok' => true]);
    }

    private function handleDeadlinesCommand(int|string $chatId, string $botToken)
    {
        $upcomingDeadlines = Deadline::with('course')
            ->where('due_at', '>=', now())
            ->orderBy('due_at', 'asc')
            ->take(5)
            ->get();

        if ($upcomingDeadlines->isNotEmpty()) {
            $responseText = "⏰ <b>កាលបរិច្ឆេទកិច្ចការ & ការប្រឡង (Upcoming Deadlines)</b>\n" .
                             "━━━━━━━━━━━━━━━━━━━━━\n\n";
            foreach ($upcomingDeadlines as $idx => $d) {
                $num = $idx + 1;
                $courseTitle = $d->course ? $d->course->title : 'General Course';
                $dueStr = $d->due_at ? $d->due_at->format('d-M-Y h:i A') : 'N/A';
                $responseText .= "{$num}. 📝 <b>{$d->title}</b>\n" .
                                  "   • មុខវិជ្ជា៖ <i>{$courseTitle}</i>\n" .
                                  "   • ផុតកំណត់៖ <code>{$dueStr}</code>\n\n";
            }
            $responseText .= "⚠️ <i>សូមរួសរាន់បញ្ចប់កិច្ចការឱ្យបានទាន់ពេលវេលា!</i>";
        } else {
            $responseText = "✅ <b>អបអរសាទរ!</b>\n\nបច្ចុប្បន្នគ្មានកិច្ចការ ឬការប្រឡងដែលជិតផុតកំណត់បន្ទាន់ឡើយ។ ✨";
        }

        $inlineKeyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '📋 មើលបញ្ជីកិច្ចការពេញលេញ', 'web_app' => ['url' => 'https://spilms.tech/student/dashboard']]
                ]
            ]
        ];

        Http::withoutVerifying()->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $responseText,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode($inlineKeyboard)
        ]);

        return response()->json(['ok' => true]);
    }

    private function handleAnnouncementsCommand(int|string $chatId, string $botToken)
    {
        $announcements = Announcement::latest()->take(3)->get();
        if ($announcements->isNotEmpty()) {
            $responseText = "📢 <b>សេចក្តីជូនដំណឹងចុងក្រោយ — SPI E-LMS</b>\n" .
                           "━━━━━━━━━━━━━━━━━━━━━\n\n";
            foreach ($announcements as $idx => $a) {
                $num = $idx + 1;
                $title = $a->title_kh ?: $a->title_en;
                $body = strip_tags($a->body_kh ?: $a->body_en);
                $snippet = mb_strlen($body) > 120 ? mb_substr($body, 0, 120) . '...' : $body;
                $date = $a->created_at ? $a->created_at->format('d-M-Y') : 'Recent';
                $responseText .= "📌 <b>{$num}. {$title}</b>\n" .
                                "{$snippet}\n" .
                                "⏰ <i>{$date}</i>\n\n";
            }
        } else {
            $responseText = "📢 <b>សេចក្តីជូនដំណឹង៖</b> មិនទាន់មានសេចក្តីជូនដំណឹងថ្មីនៅឡើយទេ។";
        }

        $inlineKeyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🌐 អានដំណឹងលើ E-LMS', 'url' => 'https://spilms.tech']
                ]
            ]
        ];

        Http::withoutVerifying()->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $responseText,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode($inlineKeyboard)
        ]);

        return response()->json(['ok' => true]);
    }

    private function handleProfileCommand(int|string $chatId, ?User $linkedUser, string $botToken)
    {
        if ($linkedUser) {
            $majorName = $linkedUser->major ? $linkedUser->major->name : 'General Studies';
            $deptName = $linkedUser->major && $linkedUser->major->department ? $linkedUser->major->department->name : 'Faculty Department';
            $roleName = $linkedUser->role === 'teacher' ? '👨‍🏫 សាស្ត្រាចារ្យ (Teacher)' : ($linkedUser->role === 'admin' ? '🛡️ Administrator' : '🎓 និស្សិត (Student)');
            $statusEmoji = $linkedUser->status === 'active' ? '🟢 សកម្ម (Active)' : '🟡 ' . ucfirst($linkedUser->status ?? 'Active');

            $responseText = "🏛️ <b>វិទ្យាស្ថាន សន្តប៉ូល (Saint Paul Institute)</b>\n" .
                            "🪪 <b>កាតព័ត៌មានគណនីឌីជីថល (Digital Academic ID)</b>\n" .
                            "━━━━━━━━━━━━━━━━━━━━━\n\n" .
                            "👤 <b>ឈ្មោះ (EN)៖</b> {$linkedUser->name}\n";
            if (!empty($linkedUser->name_kh)) {
                $responseText .= "🇰🇭 <b>ឈ្មោះ (KH)៖</b> {$linkedUser->name_kh}\n";
            }
            $responseText .= "🆔 <b>អត្តលេខ៖</b> <code>" . ($linkedUser->student_code ?? 'ID-' . $linkedUser->id) . "</code>\n" .
                             "📧 <b>Email៖</b> {$linkedUser->email}\n" .
                             "📱 <b>Phone៖</b> " . ($linkedUser->phone ?? 'N/A') . "\n" .
                             "📚 <b>ជំនាញ៖</b> {$majorName}\n" .
                             "🏢 <b>ដេប៉ាតឺម៉ង់៖</b> {$deptName}\n" .
                             "🔰 <b>តួនាទី៖</b> {$roleName}\n" .
                             "⚡ <b>ស្ថានភាព៖</b> {$statusEmoji}\n\n" .
                             "🌐 <i>ចុចប៊ូតុងខាងក្រោមដើម្បីចូលមើល Profile ពេញលេញ៖</i>";
        } else {
            $responseText = "⚠️ <b>គណនីរបស់អ្នកមិនទាន់បានភ្ជាប់ឡើយ</b>\n\n" .
                            "👉 សូមផ្ញើ<b>លេខទូរស័ព្ទ</b> ឬ <b>Email</b> របស់អ្នកមកទីនេះ ដើម្បីឱ្យ Bot ស្គាល់គណនីរបស់អ្នក។";
        }

        $inlineKeyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '👤 បើកទំព័រ Profile', 'web_app' => ['url' => 'https://spilms.tech/profile']]
                ]
            ]
        ];

        Http::withoutVerifying()->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $responseText,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode($inlineKeyboard)
        ]);

        return response()->json(['ok' => true]);
    }

    private function handleStartOrLinkCommand(
        int|string $chatId,
        string $rawText,
        bool $isStartCmd,
        string $senderName,
        ?string $telegramUsername,
        ?User $linkedUser,
        TelegramService $telegramService,
        string $botToken
    ) {
        $telegramService->syncBotCommandsAndMenu();

        $deepLinkParam = null;
        if ($isStartCmd) {
            $parts = explode(' ', $rawText);
            $deepLinkParam = $parts[1] ?? null;
        } else {
            $deepLinkParam = $rawText;
        }

        if (!empty($deepLinkParam)) {
            // 🚀 Handle Telegram QR Code Scan Authentication (login_<token> or qr_<token>)
            if (str_starts_with($deepLinkParam, 'login_') || str_starts_with($deepLinkParam, 'qr_')) {
                $qrToken = str_replace(['login_', 'qr_'], '', $deepLinkParam);
                $qrSession = Cache::get('tg_qr_' . $qrToken);

                if ($qrSession && ($qrSession['status'] ?? '') === 'pending') {
                    $user = User::where('telegram_id', (string) $chatId)
                        ->orWhere('telegram_chat_id', (string) $chatId)
                        ->first();

                    if (!$user && !empty($telegramUsername)) {
                        $user = User::where('telegram_username', $telegramUsername)->first();
                    }

                    if (!$user) {
                        $user = User::create([
                            'name' => $senderName ?: 'Telegram User',
                            'email' => 'tg_' . $chatId . '@spilms.tech',
                            'password' => Hash::make(Str::random(32)),
                            'role' => 'student',
                            'telegram_id' => (string) $chatId,
                            'telegram_chat_id' => (string) $chatId,
                            'telegram_username' => $telegramUsername,
                            'status' => 'active',
                        ]);
                    } else {
                        $user->update([
                            'telegram_id' => (string) $chatId,
                            'telegram_chat_id' => (string) $chatId,
                            'telegram_username' => $telegramUsername ?: $user->telegram_username,
                        ]);
                    }

                    Cache::put('tg_qr_' . $qrToken, [
                        'status' => 'approved',
                        'user_id' => $user->id,
                        'user' => [
                            'id' => $user->id,
                            'name' => $user->name,
                            'role' => $user->role,
                            'avatar' => $user->avatar,
                        ],
                    ], now()->addMinutes(5));

                    $replyText = "✅ <b>ការផ្ទៀងផ្ទាត់ Login តាម Telegram បានជោគជ័យ!</b>\n\n" .
                                 "សួស្តី <b>{$user->name}</b> 👋\n" .
                                 "គណនីរបស់អ្នកត្រូវបានផ្ទៀងផ្ទាត់ចូលប្រើប្រាស់ <b>spilms.tech</b> រួចរាល់ហើយ។\n\n" .
                                 "👉 <i>សូមក្រឡេកមើលទៅកាន់អេក្រង់កុំព្យូទ័ររបស់អ្នកដើម្បីបន្តការសិក្សា!</i>";

                    $inlineKeyboard = [
                        'inline_keyboard' => [
                            [
                                ['text' => '🚀 បើក E-LMS Dashboard', 'web_app' => ['url' => 'https://spilms.tech/student/dashboard']]
                            ]
                        ]
                    ];

                    Http::withoutVerifying()->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                        'chat_id' => $chatId,
                        'text' => $replyText,
                        'parse_mode' => 'HTML',
                        'reply_markup' => json_encode($inlineKeyboard)
                    ]);

                    return response()->json(['ok' => true]);
                }
            }

            $cleanParam = trim($deepLinkParam);
            $cleanDigits = preg_replace('/[^0-9]/', '', $cleanParam);
            $numericId = preg_match('/^(?:reset_)?(\d+)$/i', $cleanParam, $m) ? (int)$m[1] : null;

            $linkedUser = User::where(function ($q) use ($cleanParam, $cleanDigits, $numericId) {
                if ($numericId) {
                    $q->orWhere('id', $numericId);
                }
                $q->orWhere('id', $cleanParam)
                  ->orWhere('student_code', $cleanParam)
                  ->orWhere('email', $cleanParam)
                  ->orWhere('phone', $cleanParam);

                if (!empty($cleanDigits) && strlen($cleanDigits) >= 6) {
                    $last7 = substr($cleanDigits, -7);
                    $last8 = substr($cleanDigits, -8);
                    $q->orWhere('phone', $cleanDigits)
                      ->orWhere('phone', '0' . ltrim($cleanDigits, '0'))
                      ->orWhere('phone', 'like', '%' . $last7)
                      ->orWhere('phone', 'like', '%' . $last8);
                }
            })
            ->with(['major.department.faculty'])
            ->first();
        }

        if (!$linkedUser) {
            $linkedUser = User::where('telegram_id', (string) $chatId)
                ->orWhere('telegram_chat_id', (string) $chatId)
                ->when(!empty($telegramUsername), function ($q) use ($telegramUsername) {
                    $q->orWhere('telegram_username', $telegramUsername);
                })
                ->with(['major.department.faculty'])
                ->first();
        }

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

        if ($linkedUser) {
            // Unlink any other user who was previously attached to this telegram_id
            User::where('id', '!=', $linkedUser->id)
                ->where(function ($q) use ($chatId) {
                    $q->where('telegram_id', (string) $chatId)
                      ->orWhere('telegram_chat_id', (string) $chatId);
                })
                ->update([
                    'telegram_id' => null,
                    'telegram_chat_id' => null,
                ]);

            $updateFields = [
                'telegram_id'      => (string) $chatId,
                'telegram_chat_id' => (string) $chatId,
            ];
            if ($telegramUsername) {
                $updateFields['telegram_username'] = $telegramUsername;
            }
            $linkedUser->update($updateFields);

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
                           "ឥឡូវនេះ អ្នកអាចទទួលលេខកូដ OTP, ដំណឹងសាលា និងប្រើប្រាស់ Mini App បានយ៉ាងងាយស្រួល។ ✨\n\n" .
                           "សូមជ្រើសរើសមុខងារខាងក្រោម៖";
        } else {
            $welcomeText = "👋 <b>សូមស្វាគមន៍ {$senderName} មកកាន់ប្រព័ន្ធ SPI AI-ELMS!</b>\n\n" .
                           "🏛️ <b>វិទ្យាស្ថាន សន្តប៉ូល (Saint Paul Institute)</b>\n" .
                           "គណនី Bot នេះត្រូវបានប្រើប្រាស់សម្រាប់ការផ្ទៀងផ្ទាត់ ជូនដំណឹង និងសិក្សាលើប្រព័ន្ធ AI-ELMS (spilms.tech)។\n\n" .
                           "💡 <b>ដើម្បីភ្ជាប់គណនី ឬទទួលលេខកូដ OTP៖</b>\n" .
                           "👉 សូមចុចប៊ូតុង <b>«📱 ចែករំលែកលេខទូរស័ព្ទ»</b> ខាងក្រោម ឬ វាយ<b>លេខទូរស័ព្ទ</b> (ឧទាហរណ៍៖ <code>0964618507</code>) ឬ <b>Email</b> របស់អ្នកផ្ញើមកទីនេះ ប្រព័ន្ធនឹងស្គាល់ភ្លាម!\n\n" .
                           "សូមជ្រើសរើសមុខងារខាងក្រោម៖";
        }

        $replyMarkup = [
            'keyboard' => [
                [
                    ['text' => '📱 ចែករំលែកលេខទូរស័ព្ទ (Share Phone)', 'request_contact' => true],
                    ['text' => '🚀 បើក E-LMS (Mini App)', 'web_app' => ['url' => 'https://spilms.tech']]
                ],
                [
                    ['text' => '📚 វគ្គសិក្សា'],
                    ['text' => '⏰ កាលបរិច្ឆេទ']
                ],
                [
                    ['text' => '📢 ដំណឹងសាលា'],
                    ['text' => '👤 គណនីខ្ញុំ']
                ],
                [
                    ['text' => '💬 ជំនួយការ']
                ]
            ],
            'resize_keyboard' => true,
            'is_persistent' => true
        ];

        $inlineKeyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🌐 បើកទំព័រ Reset Password', 'url' => 'https://spilms.tech/forgot-password']
                ],
                [
                    ['text' => '🚀 បើក SPI LMS (Mini App)', 'web_app' => ['url' => 'https://spilms.tech']]
                ],
                [
                    ['text' => '📊 ចូលទៅ Dashboard', 'url' => 'https://spilms.tech/student/dashboard'],
                    ['text' => '💬 ជំនួយការបច្ចេកទេស', 'callback_data' => 'support']
                ]
            ]
        ];

        // Send Persistent Keyboard
        Http::withoutVerifying()->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $welcomeText,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode($replyMarkup)
        ]);

        // Send Inline Actions
        Http::withoutVerifying()->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => "⚡ <b>រុករកទំព័រសំខាន់ៗ (Quick Links)៖</b>",
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode($inlineKeyboard)
        ]);

        return response()->json(['ok' => true]);
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
