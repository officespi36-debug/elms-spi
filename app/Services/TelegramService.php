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
        $this->chatId = config('services.telegram.chat_id') ?? env('TELEGRAM_CHAT_ID') ?? config('services.telegram.admin_chat_id') ?? '-5560385465';
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
     * Send a direct text/HTML message to a specific Telegram Chat ID with multi-IP auto-failover
     */
    public function sendDirectMessage(string|int $chatId, string $text, string $parseMode = 'HTML', ?array $replyMarkup = null): bool
    {
        if (empty($this->botToken) || empty($chatId)) {
            Log::info("Telegram Direct Message (Token or ChatId missing):\nChat ID: {$chatId}\n" . strip_tags($text));
            return false;
        }

        $payload = [
            'chat_id'                  => (string) $chatId,
            'text'                     => $text,
            'parse_mode'               => $parseMode,
            'disable_web_page_preview' => true,
        ];

        if (!empty($replyMarkup)) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        $workingIp = \Illuminate\Support\Facades\Cache::get('tg_working_ip');
        $allIps = ['149.154.166.110', '149.154.167.220', '149.154.165.120', '149.154.167.199'];
        $ips = $workingIp ? array_unique(array_merge([$workingIp], $allIps)) : $allIps;
        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";

        foreach ($ips as $ip) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_RESOLVE, ["api.telegram.org:443:{$ip}"]);

            $body = curl_exec($ch);
            $err = curl_error($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if (!$err && $code === 200) {
                \Illuminate\Support\Facades\Cache::forever('tg_working_ip', $ip);
                return true;
            }
        }

        Log::error("Telegram Direct Message Error across all IPs");
        return false;
    }

    /**
     * Send OTP verification code directly to user's Telegram chat
     */
    public function sendPasswordResetOtp(User $user, string $otpCode): bool
    {
        $targetChatId = $user->telegram_id ?? $user->telegram_chat_id ?? null;

        if (empty($targetChatId)) {
            return false;
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
     * Send a general text/HTML message to Telegram channel/group with multi-IP auto-failover
     */
    public function sendMessage(string $text, string $parseMode = 'HTML', string|int|null $chatId = null): bool
    {
        $target = $chatId ?? $this->chatId ?? config('services.telegram.admin_chat_id') ?? '-5560385465';

        if (empty($this->botToken) || empty($target)) {
            Log::info("Telegram Notification (Simulated/Token missing):\n" . strip_tags($text));
            return false;
        }

        $payload = [
            'chat_id'                  => (string) $target,
            'text'                     => $text,
            'parse_mode'               => $parseMode,
            'disable_web_page_preview' => true,
        ];

        $workingIp = \Illuminate\Support\Facades\Cache::get('tg_working_ip');
        $allIps = ['149.154.166.110', '149.154.167.220', '149.154.165.120', '149.154.167.199'];
        $ips = $workingIp ? array_unique(array_merge([$workingIp], $allIps)) : $allIps;
        $url = "https://api.telegram.org/bot{$this->botToken}/sendMessage";

        foreach ($ips as $ip) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_RESOLVE, ["api.telegram.org:443:{$ip}"]);

            $body = curl_exec($ch);
            $err = curl_error($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if (!$err && $code === 200) {
                \Illuminate\Support\Facades\Cache::forever('tg_working_ip', $ip);
                return true;
            }
        }

        Log::error("Telegram Notification Error across all IPs");
        return false;
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
     * Register Bot Name, Description, Menu Slash Commands, and native WebApp Menu Button with Telegram API
     */
    public function syncBotCommandsAndMenu(): bool
    {
        if (empty($this->botToken)) {
            return false;
        }

        try {
            // 1. Restore official Bot Name
            Http::withoutVerifying()->timeout(5)->post("https://api.telegram.org/bot{$this->botToken}/setMyName", [
                'name' => 'SPI AI-ELMS Auth Bot',
            ]);

            // 2. Restore official Bot Description
            $botDescription = "🏛️ វិទ្យាស្ថាន សន្តប៉ូល (Saint Paul Institute)\n" .
                "ប្រព័ន្ធគ្រប់គ្រងការសិក្សាឆ្លាតវៃ SPI AI-ELMS (spilms.tech)\n\n" .
                "🤖 មុខងាររបស់ Bot៖\n" .
                "• 🔐 ផ្ញើលេខកូដផ្ទៀងផ្ទាត់ OTP (Password Reset)\n" .
                "• 🎓 ភ្ជាប់គណនីនិស្សិត និងសាស្ត្រាចារ្យ\n" .
                "• 📚 ពិនិត្យមើលវគ្គសិក្សា (Courses)\n" .
                "• ⏰ ជូនដំណឹងកាលបរិច្ឆេទកិច្ចការ & ប្រឡង (Deadlines)\n" .
                "• 📢 ទទួលដំណឹងសេចក្តីប្រកាសផ្លូវការរបស់សាលា\n" .
                "• 🚀 ដំណើរការ SPI LMS Web Mini App\n\n" .
                "📍 ទំនាក់ទំនង៖ info@spilms.tech | https://spilms.tech";

            Http::withoutVerifying()->timeout(5)->post("https://api.telegram.org/bot{$this->botToken}/setMyDescription", [
                'description' => $botDescription,
            ]);

            // 3. Restore official Bot Short Description
            Http::withoutVerifying()->timeout(5)->post("https://api.telegram.org/bot{$this->botToken}/setMyShortDescription", [
                'short_description' => 'Bot ផ្លូវការរបស់វិទ្យាស្ថាន សន្តប៉ូល (SPI AI-ELMS) សម្រាប់ទទួល OTP, ដំណឹងសាលា និង Mini App។',
            ]);

            // 4. Set slash command list
            $commands = [
                ['command' => 'start', 'description' => '🚀 ចាប់ផ្តើម និងភ្ជាប់គណនី (Start & Connect)'],
                ['command' => 'courses', 'description' => '📚 វគ្គសិក្សារបស់ខ្ញុំ (My Enrolled Courses)'],
                ['command' => 'deadlines', 'description' => '⏰ កាលបរិច្ឆេទកិច្ចការ & ប្រឡង (Upcoming Deadlines)'],
                ['command' => 'announcements', 'description' => '📢 ដំណឹង និងសេចក្តីជូនដំណឹងសាលា (Campus News)'],
                ['command' => 'me', 'description' => '👤 កាតព័ត៌មានគណនី (My Profile & ID)'],
                ['command' => 'help', 'description' => '💬 ជំនួយបច្ចេកទេស និងទំនាក់ទំនង (Technical Support)'],
                ['command' => 'unlink', 'description' => '🔓 ផ្តាច់គណនីចេញពី Telegram (Unlink Account)'],
            ];

            Http::withoutVerifying()->timeout(5)->post("https://api.telegram.org/bot{$this->botToken}/setMyCommands", [
                'commands' => $commands,
            ]);

            // 5. Set native WebApp launcher button next to input bar
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
     * Set up Telegram Webhook with secret token for endpoint verification
     */
    public function setupWebhook(string $url, ?string $secretToken = null): array
    {
        if (empty($this->botToken)) {
            return ['ok' => false, 'description' => 'Bot token not configured'];
        }

        $secret = $secretToken ?? config('services.telegram.webhook_secret');
        $payload = [
            'url' => $url,
            'allowed_updates' => ['message', 'callback_query'],
            'drop_pending_updates' => true,
        ];

        if (!empty($secret)) {
            $payload['secret_token'] = $secret;
        }

        try {
            $response = Http::withoutVerifying()->timeout(10)->post("https://api.telegram.org/bot{$this->botToken}/setWebhook", $payload);
            return $response->json();
        } catch (\Throwable $e) {
            Log::error("Telegram setupWebhook error: " . $e->getMessage());
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    /**
     * Get current Webhook Information
     */
    public function getWebhookInfo(): array
    {
        if (empty($this->botToken)) {
            return ['ok' => false, 'description' => 'Bot token not configured'];
        }

        try {
            $response = Http::withoutVerifying()->timeout(10)->get("https://api.telegram.org/bot{$this->botToken}/getWebhookInfo");
            return $response->json();
        } catch (\Throwable $e) {
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    /**
     * Delete Webhook (switch back to polling or clean state)
     */
    public function deleteWebhook(): array
    {
        if (empty($this->botToken)) {
            return ['ok' => false, 'description' => 'Bot token not configured'];
        }

        try {
            $response = Http::withoutVerifying()->timeout(10)->post("https://api.telegram.org/bot{$this->botToken}/deleteWebhook", [
                'drop_pending_updates' => true,
            ]);
            return $response->json();
        } catch (\Throwable $e) {
            return ['ok' => false, 'description' => $e->getMessage()];
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
     * Audit Administrators in a Telegram Group
     */
    public function getChatAdministrators(string|int|null $chatId = null): array
    {
        $target = $chatId ?? $this->chatId;
        if (empty($this->botToken) || empty($target)) {
            return [];
        }

        try {
            $res = Http::withoutVerifying()->timeout(10)->get("https://api.telegram.org/bot{$this->botToken}/getChatAdministrators", [
                'chat_id' => (string)$target,
            ]);
            return $res->json('result', []);
        } catch (\Throwable $e) {
            Log::error("getChatAdministrators error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ban / Kick a malicious user or bot from a Telegram Group
     */
    public function banChatMember(int|string $userId, string|int|null $chatId = null, int $untilDate = 0, bool $revokeMessages = true): bool
    {
        $target = $chatId ?? $this->chatId;
        if (empty($this->botToken) || empty($target) || empty($userId)) {
            return false;
        }

        try {
            $payload = [
                'chat_id'         => (string)$target,
                'user_id'         => (int)$userId,
                'revoke_messages' => $revokeMessages,
            ];
            if ($untilDate > 0) {
                $payload['until_date'] = $untilDate;
            }

            $res = Http::withoutVerifying()->timeout(10)->post("https://api.telegram.org/bot{$this->botToken}/banChatMember", $payload);
            return $res->json('ok', false);
        } catch (\Throwable $e) {
            Log::error("banChatMember error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Restrict member permissions (mute, disable link sending, etc.)
     */
    public function restrictChatMember(int|string $userId, array $permissions, string|int|null $chatId = null, int $untilDate = 0): bool
    {
        $target = $chatId ?? $this->chatId;
        if (empty($this->botToken) || empty($target) || empty($userId)) {
            return false;
        }

        try {
            $payload = [
                'chat_id'     => (string)$target,
                'user_id'     => (int)$userId,
                'permissions' => json_encode($permissions),
            ];
            if ($untilDate > 0) {
                $payload['until_date'] = $untilDate;
            }

            $res = Http::withoutVerifying()->timeout(10)->post("https://api.telegram.org/bot{$this->botToken}/restrictChatMember", $payload);
            return $res->json('ok', false);
        } catch (\Throwable $e) {
            Log::error("restrictChatMember error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a spam message from a Telegram Group
     */
    public function deleteMessage(int $messageId, string|int|null $chatId = null): bool
    {
        $target = $chatId ?? $this->chatId;
        if (empty($this->botToken) || empty($target) || empty($messageId)) {
            return false;
        }

        try {
            $res = Http::withoutVerifying()->timeout(5)->post("https://api.telegram.org/bot{$this->botToken}/deleteMessage", [
                'chat_id'    => (string)$target,
                'message_id' => $messageId,
            ]);
            return $res->json('ok', false);
        } catch (\Throwable $e) {
            return false;
        }
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
