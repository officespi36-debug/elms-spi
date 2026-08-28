<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    protected ?string $botToken;
    protected ?string $botUsername;
    protected ?string $chatId;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token') ?? env('TELEGRAM_BOT_TOKEN');
        $this->botUsername = config('services.telegram.bot_username') ?? env('TELEGRAM_BOT_USERNAME', 'spi_elms_auth_bot');
        $this->chatId = config('services.telegram.chat_id') ?? env('TELEGRAM_CHAT_ID');
    }

    public function getBotToken(): ?string
    {
        return $this->botToken;
    }

    public function getBotUsername(): string
    {
        return $this->botUsername ?? 'spi_elms_auth_bot';
    }

    /**
     * Verify Telegram OAuth Login Widget Hash (HMAC-SHA-256)
     */
    public function verifyTelegramAuth(array $authData): bool
    {
        if (empty($authData['hash'])) {
            return false;
        }

        $checkHash = (string) $authData['hash'];
        $botToken = $this->botToken;

        if (empty($botToken)) {
            Log::warning("Telegram Auth Verification: Bot token is not configured.");
            return false;
        }

        // 1. Gather all user data attributes (exclude hash)
        $dataCheckArr = [];
        $allowedKeys = ['id', 'first_name', 'last_name', 'username', 'photo_url', 'auth_date'];
        
        foreach ($authData as $key => $value) {
            if ($key !== 'hash' && in_array($key, $allowedKeys, true) && $value !== null && $value !== '') {
                $dataCheckArr[] = $key . '=' . $value;
            }
        }

        // 2. Sort key-value pairs alphabetically
        sort($dataCheckArr);
        $dataCheckString = implode("\n", $dataCheckArr);

        // 3. Secret key = raw SHA256 of bot token
        $secretKey = hash('sha256', $botToken, true);

        // 4. Compute HMAC-SHA256 signature
        $calculatedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

        // 5. Compare signatures safely
        if (!hash_equals($calculatedHash, $checkHash)) {
            Log::warning("Telegram Auth Verification: Invalid hash signature.", [
                'expected' => $calculatedHash,
                'received' => $checkHash,
            ]);
            return false;
        }

        // 6. Check auth timestamp validity (valid within 24 hours)
        if (isset($authData['auth_date']) && (time() - (int) $authData['auth_date'] > 86400)) {
            Log::warning("Telegram Auth Verification: Auth session expired (> 24 hours).");
            return false;
        }

        return true;
    }

    /**
     * Send a direct text/HTML message to a specific Telegram Chat ID
     */
    public function sendDirectMessage(string|int $chatId, string $text, string $parseMode = 'HTML', ?array $replyMarkup = null): bool
    {
        if (empty($this->botToken) || empty($chatId)) {
            Log::info("Telegram Direct Message (Token or ChatId missing):\nChat ID: {$chatId}\n" . strip_tags($text));
            return false;
        }

        try {
            $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
            $payload = [
                'chat_id'                  => (string) $chatId,
                'text'                     => $text,
                'parse_mode'               => $parseMode,
                'disable_web_page_preview' => true,
            ];

            if (!empty($replyMarkup)) {
                $payload['reply_markup'] = json_encode($replyMarkup);
            }

            $response = Http::withoutVerifying()->timeout(2)->post($url, $payload);

            if ($response->failed()) {
                Log::error("Telegram API Direct Message Error: " . $response->body());
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error("Telegram Direct Message Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send OTP verification code directly to user's Telegram chat
     */
    public function sendPasswordResetOtp(User $user, string $otpCode): bool
    {
        $targetChatId = $user->telegram_id ?? $user->telegram_chat_id ?? null;

        // Fallback to active admin/tester Telegram chat ID if account has no direct link yet
        if (empty($targetChatId)) {
            $targetChatId = '5496354981';
        }

        $text = "🔐 <b>SPI AI-ELMS — Password Reset OTP</b>\n" .
                "━━━━━━━━━━━━━━━━━━━━━\n\n" .
                "សួស្តី <b>{$user->name}</b>!\n\n" .
                "លេខកូដផ្ទៀងផ្ទាត់ (OTP) សម្រាប់ផ្លាស់ប្តូរពាក្យសម្ងាត់របស់អ្នកគឺ៖\n\n" .
                "👉 <code>{$otpCode}</code> 👈\n\n" .
                "⏳ <i>លេខកូដនេះមានសុពលភាពរយៈពេល ៥ នាទី។</i>\n" .
                "⚠️ <i>សូមកុំចែករំលែកលេខកូដនេះទៅកាន់អ្នកដទៃជាដាច់ខាត។</i>\n\n" .
                "🏛️ <i>វិទ្យាស្ថាន សន្តប៉ូល (Saint Paul Institute)</i>";

        $keyboard = [
            'inline_keyboard' => [
                [
                    ['text' => '🌐 បើកទំព័រ Reset Password', 'url' => 'https://spilms.tech/forgot-password']
                ]
            ]
        ];

        return $this->sendDirectMessage($targetChatId, $text, 'HTML', $keyboard);
    }

    /**
     * Send a general text/HTML message to Telegram channel/group
     */
    public function sendMessage(string $text, string $parseMode = 'HTML', string|int|null $chatId = null): bool
    {
        $target = $chatId ?? $this->chatId;

        if (empty($this->botToken) || empty($target)) {
            Log::info("Telegram Notification (Simulated/Token missing):\n" . strip_tags($text));
            return false;
        }

        try {
            $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
            $response = Http::withoutVerifying()->timeout(2)->post($url, [
                'chat_id'    => (string) $target,
                'text'       => $text,
                'parse_mode' => $parseMode,
                'disable_web_page_preview' => true,
            ]);

            if ($response->failed()) {
                Log::error("Telegram API Error: " . $response->body());
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error("Telegram Notification Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send structured Telegram alert for new Student registration
     */
    public function notifyNewStudentRegistration(User $user, array $paymentDetails = []): bool
    {
        $majorName = $user->major ? $user->major->name : 'N/A';
        $departmentName = $user->major && $user->major->department ? $user->major->department->name : 'N/A';
        $facultyName = $user->major && $user->major->department && $user->major->department->faculty ? $user->major->department->faculty->name : 'N/A';

        $studyTypeLabel = ($user->study_type === 'online') ? '🌐 Online / Distance Learning' : '🏫 On-Campus / Face-to-Face';
        $paymentMethod = $paymentDetails['method'] ?? 'N/A';
        $amount = isset($paymentDetails['amount']) ? '$' . number_format($paymentDetails['amount'], 2) : '$360.00';
        $hasReceipt = !empty($paymentDetails['receipt']) ? '✅ Uploaded' : '❌ Not Uploaded';

        $msg = "<b>🎓 NEW STUDENT REGISTRATION — E.LMS</b>\n";
        $msg .= "----------------------------------------\n";
        $msg .= "🆔 <b>Student ID:</b> <code>{$user->student_code}</code>\n";
        $msg .= "👤 <b>Full Name (EN):</b> {$user->name}\n";
        if (!empty($user->name_kh)) {
            $msg .= "👤 <b>Full Name (KH):</b> {$user->name_kh}\n";
        }
        $msg .= "📧 <b>Email:</b> {$user->email}\n";
        $msg .= "📱 <b>Phone:</b> {$user->phone}\n";
        $msg .= "📚 <b>Major:</b> {$majorName}\n";
        $msg .= "🏢 <b>Department:</b> {$departmentName}\n";
        $msg .= "🏫 <b>Faculty:</b> {$facultyName}\n";
        $msg .= "🎓 <b>Study Type:</b> {$studyTypeLabel}\n";
        $msg .= "----------------------------------------\n";
        $msg .= "💳 <b>Payment Method:</b> {$paymentMethod}\n";
        $msg .= "💰 <b>Amount:</b> {$amount}\n";
        $msg .= "📄 <b>Receipt Uploaded:</b> {$hasReceipt}\n";
        $msg .= "⚙️ <b>Account Status:</b> <code>{$user->status}</code>\n";
        $msg .= "⏰ <b>Time:</b> " . now()->format('Y-m-d H:i:s') . "\n";

        return $this->sendMessage($msg);
    }

    /**
     * Send notification when Admin creates a Teacher Account
     */
    public function notifyTeacherCreated(User $teacher, string $tempPassword): bool
    {
        $majorName = $teacher->major ? $teacher->major->name : 'N/A';
        $departmentName = $teacher->major && $teacher->major->department ? $teacher->major->department->name : 'N/A';

        $msg = "<b>👨‍🏫 NEW TEACHER ACCOUNT CREATED</b>\n";
        $msg .= "----------------------------------------\n";
        $msg .= "👤 <b>Name:</b> {$teacher->name}\n";
        $msg .= "📧 <b>Email:</b> {$teacher->email}\n";
        $msg .= "📱 <b>Phone:</b> {$teacher->phone}\n";
        $msg .= "📚 <b>Assigned Major:</b> {$majorName}\n";
        $msg .= "🏢 <b>Department:</b> {$departmentName}\n";
        $msg .= "🎓 <b>Qualification:</b> " . ($teacher->qualification ?? 'N/A') . "\n";
        $msg .= "💡 <b>Expertise:</b> " . ($teacher->expertise ?? 'N/A') . "\n";
        $msg .= "🔑 <b>Temp Password:</b> <code>{$tempPassword}</code>\n";
        $msg .= "⏰ <b>Created At:</b> " . now()->format('Y-m-d H:i:s') . "\n";

        return $this->sendMessage($msg);
    }

    /**
     * Register Bot Menu Slash Commands and native WebApp Menu Button with Telegram API
     */
    public function syncBotCommandsAndMenu(): bool
    {
        if (empty($this->botToken)) {
            return false;
        }

        try {
            // 1. Set slash command list
            $commands = [
                ['command' => 'start', 'description' => '🚀 ចាប់ផ្តើម និងភ្ជាប់គណនី (Start & Connect)'],
                ['command' => 'courses', 'description' => '📚 វគ្គសិក្សារបស់ខ្ញុំ (My Enrolled Courses)'],
                ['command' => 'deadlines', 'description' => '⏰ កាលបរិច្ឆេទកិច្ចការ & ប្រឡង (Upcoming Deadlines)'],
                ['command' => 'announcements', 'description' => '📢 ដំណឹង និងសេចក្តីជូនដំណឹងសាលា (Campus News)'],
                ['command' => 'me', 'description' => '👤 កាតព័ត៌មានគណនី (My Profile & ID)'],
                ['command' => 'help', 'description' => '💬 ជំនួយបច្ចេកទេស និងទំនាក់ទំនង (Technical Support)'],
            ];

            Http::withoutVerifying()->timeout(5)->post("https://api.telegram.org/bot{$this->botToken}/setMyCommands", [
                'commands' => $commands,
            ]);

            // 2. Set native WebApp launcher button next to input bar
            Http::withoutVerifying()->timeout(5)->post("https://api.telegram.org/bot{$this->botToken}/setChatMenuButton", [
                'menu_button' => [
                    'type' => 'web_app',
                    'text' => '🎓 SPI LMS',
                    'web_app' => ['url' => 'https://spilms.tech']
                ]
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::warning("Telegram syncBotCommandsAndMenu error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Returns persistent bottom keyboard for seamless navigation
     */
    public function getPersistentReplyKeyboard(): array
    {
        return [
            'keyboard' => [
                [
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
    }

    /**
     * Send campus announcement to Telegram group
     */
    public function notifyAnnouncement(\App\Models\Announcement $announcement): bool
    {
        $title = $announcement->title_kh ?: $announcement->title_en;
        $body = strip_tags($announcement->body_kh ?: $announcement->body_en);
        $snippet = mb_strlen($body) > 200 ? mb_substr($body, 0, 200) . '...' : $body;
        $priorityBadge = $announcement->priority === 'urgent' ? '🔴 [បន្ទាន់ / URGENT]' : '📢 [ដំណឹងផ្លូវការ]';

        $msg = "<b>{$priorityBadge} សេចក្តីជូនដំណឹង — SPI E-LMS</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━━\n\n";
        $msg .= "📌 <b>ចំណងជើង៖</b> <b>{$title}</b>\n\n";
        $msg .= "📝 <b>ខ្លឹមសារសង្ខេប៖</b>\n{$snippet}\n\n";
        $msg .= "🏛️ <i>វិទ្យាស្ថាន សន្តប៉ូល (Saint Paul Institute)</i>\n";
        $msg .= "⏰ <i>កាលបរិច្ឆេទ៖ " . now()->format('d-M-Y H:i') . "</i>";

        return $this->sendMessage($msg, 'HTML', $this->chatId);
    }

    /**
     * Send homework/quiz deadline alert
     */
    public function notifyDeadlineCreated(\App\Models\Deadline $deadline): bool
    {
        $courseName = $deadline->course ? $deadline->course->title : 'General Course';
        $dueFormatted = $deadline->due_at ? $deadline->due_at->format('d-M-Y h:i A') : 'N/A';

        $msg = "<b>⏰ ការរំលឹកកាលបរិច្ឆេទកិច្ចការ — SPI E-LMS</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━━━━━━\n\n";
        $msg .= "📚 <b>មុខវិជ្ជា៖</b> {$courseName}\n";
        $msg .= "📝 <b>កិច្ចការ/ប្រឡង៖</b> <b>{$deadline->title}</b>\n";
        $msg .= "⏳ <b>ថ្ងៃផុតកំណត់៖</b> <code>{$dueFormatted}</code>\n\n";
        $msg .= "⚠️ <i>សូមនិស្សិតទាំងអស់រួសរាន់បញ្ចប់ និងដាក់ស្នើឱ្យបានទាន់ពេលវេលា!</i>\n";
        $msg .= "🏛️ <i>Saint Paul Institute</i>";

        return $this->sendMessage($msg, 'HTML', $this->chatId);
    }

    /**
     * Send security login alert
     */
    public function notifyLoginAlert(User $user, string $ipAddress, string $userAgent): bool
    {
        $msg = "<b>🔐 LOGIN NOTIFICATION</b>\n";
        $msg .= "----------------------------------------\n";
        $msg .= "👤 <b>User:</b> {$user->name} ({$user->email})\n";
        $msg .= "🔰 <b>Role:</b> " . strtoupper($user->role) . "\n";
        $msg .= "🌐 <b>IP Address:</b> {$ipAddress}\n";
        $msg .= "💻 <b>User Agent:</b> " . substr($userAgent, 0, 100) . "\n";
        $msg .= "⏰ <b>Time:</b> " . now()->format('Y-m-d H:i:s') . "\n";

        return $this->sendMessage($msg);
    }
}
