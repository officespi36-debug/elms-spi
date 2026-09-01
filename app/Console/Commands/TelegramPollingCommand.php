<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\TelegramSecurityPipeline;
use App\Models\User;
use App\Models\Course;
use App\Models\Deadline;
use App\Models\Announcement;
use App\Models\Enrollment;

class TelegramPollingCommand extends Command
{
    protected $signature = 'bot:listen';
    protected $description = 'Start SPI E-LMS Telegram Bot Long Polling Engine with 5-Layer Security';

    public function handle()
    {
        $this->info('🚀 SPI E-LMS Telegram Bot Engine is running...');
        $botToken = config('services.telegram.bot_token');

        if (empty($botToken)) {
            $this->error('❌ Telegram Bot Token is not configured.');
            return self::FAILURE;
        }

        $this->info("🤖 Connected to Bot. Listening for messages & commands...");
        $offset = 0;

        while (true) {
            try {
                // ជាន់ទី ១៖ Stream Offset & Safe Long-Polling
                $response = Http::withoutVerifying()
                    ->timeout(30)
                    ->withOptions([
                        'connect_timeout' => 10,
                        'curl' => [
                            CURLOPT_IPRESOLVE => defined('CURL_IPRESOLVE_V4') ? CURL_IPRESOLVE_V4 : 1,
                        ]
                    ])
                    ->get("https://api.telegram.org/bot{$botToken}/getUpdates", [
                        'offset'          => $offset,
                        'timeout'         => 15,
                        'allowed_updates' => json_encode(['message', 'callback_query']),
                    ]);

                if ($response->successful()) {
                    $updates = $response->json('result', []);

                    foreach ($updates as $update) {
                        $offset = $update['update_id'] + 1;

                        // ដំណើរការត្រួតពិនិត្យ 5-Layer Security Pipeline
                        if (TelegramSecurityPipeline::validate($update)) {
                            $this->handleBotCommand($update);
                        } else {
                            $this->warn("⚠️ Blocked suspicious/unauthorized update ID: {$update['update_id']}");
                        }
                    }
                } else {
                    $this->error('Telegram API Response Error: ' . $response->body());
                    sleep(2);
                }
            } catch (\Exception $e) {
                $this->error('Error: ' . $e->getMessage());
                sleep(2);
            }
        }
    }

    private function handleBotCommand(array $update): void
    {
        $botToken = config('services.telegram.bot_token');

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
                Http::withoutVerifying()->post("https://api.telegram.org/bot{$botToken}/answerCallbackQuery", [
                    'callback_query_id' => $cbId,
                ]);
            }

            if ($cbData === 'support') {
                TelegramSecurityPipeline::sendMessage($chatId, $supportText, 'HTML', $supportKeyboard);
            } elseif (str_starts_with($cbData, 'ban_user_')) {
                $targetUserId = substr($cbData, 9);
                if (!empty($targetUserId)) {
                    \Illuminate\Support\Facades\Cache::forever("tg_banned_{$targetUserId}", true);

                    $adminChatId = config('services.telegram.admin_chat_id') ?? config('services.telegram.chat_id') ?? $chatId;
                    app(\App\Services\TelegramService::class)->banChatMember($targetUserId, $adminChatId);

                    if ($cbId) {
                        Http::withoutVerifying()->post("https://api.telegram.org/bot{$botToken}/answerCallbackQuery", [
                            'callback_query_id' => $cbId,
                            'text' => "⛔ បាន Block និង Ban User ID {$targetUserId} រួចរាល់!",
                            'show_alert' => true,
                        ]);
                    }

                    TelegramSecurityPipeline::sendMessage(
                        $chatId,
                        "✅ <b>ប្រតិបត្តិការជោគជ័យ៖</b> User ID <code>{$targetUserId}</code> ត្រូវបាន Block និង Ban ចេញពី Group រួចរាល់!",
                        'HTML'
                    );
                    $this->safeLog('warn', "⛔ Banned user ID {$targetUserId} via interactive Telegram button.");
                }
            } elseif (str_starts_with($cbData, 'ban_ip_')) {
                $targetIp = str_replace('-', '.', substr($cbData, 7));
                if (!empty($targetIp)) {
                    \Illuminate\Support\Facades\Cache::forever("blacklisted_ip_{$targetIp}", true);

                    if ($cbId) {
                        Http::withoutVerifying()->post("https://api.telegram.org/bot{$botToken}/answerCallbackQuery", [
                            'callback_query_id' => $cbId,
                            'text' => "🛡️ បាន Blacklist IP {$targetIp} រួចរាល់!",
                            'show_alert' => true,
                        ]);
                    }

                    TelegramSecurityPipeline::sendMessage(
                        $chatId,
                        "🛡️ <b>FIREWALL BLACKLIST៖</b> IP <code>{$targetIp}</code> ត្រូវបានដាក់ចូលក្នុង Blacklist រួចរាល់!",
                        'HTML'
                    );
                    $this->safeLog('warn', "🛡️ Blacklisted IP {$targetIp} via interactive Telegram button.");
                }
            }
            return;
        }

        // 2. Handle Text Messages, Contacts, and Commands
        $message = $update['message'] ?? null;
        if (!$message || !isset($message['chat']['id'])) {
            return;
        }

        $chatId = $message['chat']['id'];
        $rawText = trim($message['text'] ?? '');
        $text = mb_strtolower($rawText, 'UTF-8');
        $cleanCmd = preg_replace('/^(\/\w+)@\w+/i', '$1', $text);
        $senderName = $message['from']['first_name'] ?? 'Student';
        $telegramUsername = $message['from']['username'] ?? null;

        // Initialize $linkedUser for this chat (so it is never undefined)
        $linkedUser = User::where('telegram_id', (string) $chatId)
            ->orWhere('telegram_chat_id', (string) $chatId)
            ->orWhere(function ($q) use ($telegramUsername) {
                if (!empty($telegramUsername)) {
                    $q->where('telegram_username', $telegramUsername);
                }
            })
            ->first();

        // Handle native contact sharing
        if (isset($message['contact']['phone_number'])) {
            $rawText = trim($message['contact']['phone_number']);
            $text = mb_strtolower($rawText, 'UTF-8');
            $cleanCmd = $text;
        }

        if (str_starts_with($cleanCmd, '/unlink') || str_starts_with($cleanCmd, '/logout')) {
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

            TelegramSecurityPipeline::sendMessage($chatId, $unlinkText, 'HTML', $replyMarkup);
            return;
        }

        $isStartCmd = str_starts_with($cleanCmd, '/start');
        $cleanDigits = preg_replace('/[^0-9]/', '', $rawText);
        $looksLikeIdentifier = str_contains($rawText, '@')
            || (strlen($cleanDigits) >= 8 && strlen($cleanDigits) <= 15)
            || preg_match('/^(stu|tch|adm|usr)[0-9]+/i', $rawText);

        if ($isStartCmd || $looksLikeIdentifier) {
            $deepLinkParam = null;
            if ($isStartCmd) {
                $parts = explode(' ', $rawText);
                $deepLinkParam = $parts[1] ?? null;
            } else {
                $deepLinkParam = $rawText;
            }

            if (!empty($deepLinkParam)) {
                $cleanParam = trim($deepLinkParam);
                $paramDigits = preg_replace('/[^0-9]/', '', $cleanParam);
                $numericId = preg_match('/^(?:reset_)?(\d+)$/i', $cleanParam, $m) ? (int)$m[1] : null;

                $linkedUser = User::where(function ($q) use ($cleanParam, $paramDigits, $numericId) {
                    if ($numericId) {
                        $q->orWhere('id', $numericId);
                    }
                    $q->orWhere('id', $cleanParam)
                      ->orWhere('student_code', $cleanParam)
                      ->orWhere('email', $cleanParam)
                      ->orWhere('phone', $cleanParam);

                    if (!empty($paramDigits) && strlen($paramDigits) >= 6) {
                        $last7 = substr($paramDigits, -7);
                        $last8 = substr($paramDigits, -8);
                        $q->orWhere('phone', $paramDigits)
                          ->orWhere('phone', '0' . ltrim($paramDigits, '0'))
                          ->orWhere('phone', 'like', '%' . $last7)
                          ->orWhere('phone', 'like', '%' . $last8);
                    }
                })->first();
            }

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

            $hasActiveOtp = $linkedUser && !empty($linkedUser->otp_code) && $linkedUser->otp_expires_at && \Carbon\Carbon::parse($linkedUser->otp_expires_at)->isFuture();
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
                        if (\Carbon\Carbon::parse($linkedUser->otp_expires_at)->isFuture()) {
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
                               "ឥឡូវនេះ អ្នកអាចទទួលលេខកូដ OTP (សម្រាប់ Forgot Password) និងដំណឹងផ្សេងៗបានយ៉ាងរហ័សតាម Telegram នេះ។ ✨\n\n" .
                               "សូមជ្រើសរើសមុខងារខាងក្រោម៖";
            } else {
                $welcomeText = "👋 <b>សូមស្វាគមន៍ {$senderName} មកកាន់ប្រព័ន្ធ SPI AI-ELMS!</b>\n\n" .
                               "🏛️ <b>វិទ្យាស្ថាន សន្តប៉ូល (Saint Paul Institute)</b>\n" .
                               "គណនី Bot នេះត្រូវបានប្រើប្រាស់សម្រាប់ការផ្ទៀងផ្ទាត់ និងទទួលលេខកូដ OTP ចូលប្រើប្រាស់ប្រព័ន្ធដោយសុវត្ថិភាព។\n\n" .
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
                        ['text' => '🚀 ចូលប្រើប្រាស់ SPI LMS (Login)', 'url' => 'https://spilms.tech/login']
                    ],
                    [
                        ['text' => '📊 ចូលទៅ Dashboard', 'url' => 'https://spilms.tech/student/dashboard'],
                        ['text' => '💬 ជំនួយការបច្ចេកទេស', 'callback_data' => 'support']
                    ]
                ]
            ];

            TelegramSecurityPipeline::sendMessage($chatId, $welcomeText, 'HTML', $replyMarkup);
            TelegramSecurityPipeline::sendMessage($chatId, "⚡ <b>រុករកទំព័រសំខាន់ៗ (Quick Links)៖</b>", 'HTML', $inlineKeyboard);
            $this->safeLog('info', "Handled start/link for {$senderName} (Chat: {$chatId})");
        } elseif (str_starts_with($cleanCmd, '/courses') || str_contains($cleanCmd, 'វគ្គសិក្សា') || str_contains($cleanCmd, 'វត្តសិក្សា') || $cleanCmd === 'courses') {
            $this->handleCoursesCommand($chatId, $linkedUser, $botToken);
        } elseif (str_starts_with($cleanCmd, '/deadlines') || str_contains($cleanCmd, 'កាលបរិច្ឆេទ') || $cleanCmd === 'deadlines' || $cleanCmd === 'homework') {
            $this->handleDeadlinesCommand($chatId, $botToken);
        } elseif (str_starts_with($cleanCmd, '/announcements') || str_contains($cleanCmd, 'ដំណឹង') || $cleanCmd === 'announcements') {
            $this->handleAnnouncementsCommand($chatId, $botToken);
        } elseif (str_starts_with($cleanCmd, '/me') || str_starts_with($cleanCmd, '/profile') || str_starts_with($cleanCmd, '/id') || str_contains($cleanCmd, 'គណនី') || $cleanCmd === 'profile') {
            $this->handleProfileCommand($chatId, $linkedUser, $botToken);
        } elseif (str_starts_with($cleanCmd, '/support') || str_starts_with($cleanCmd, '/help') || str_contains($cleanCmd, 'ជំនួយ') || $cleanCmd === 'support' || $cleanCmd === 'help') {
            TelegramSecurityPipeline::sendMessage($chatId, $supportText, 'HTML', $supportKeyboard);
        } elseif (str_starts_with($cleanCmd, '/unlink')) {
            if ($linkedUser) {
                DB::table('users')->where('id', $linkedUser->id)->update([
                    'telegram_chat_id' => null,
                    'telegram_id' => null
                ]);
                $unlinkText = "🔓 <b>ផ្តាច់គណនីជោគជ័យ!</b>\n\n" .
                              "គណនី Telegram របស់អ្នកត្រូវបានផ្តាច់ចេញពីគណនី SPI AI-ELMS (<b>{$linkedUser->name}</b>) រួចរាល់ហើយ។\n\n" .
                              "ប្រសិនបើអ្នកចង់ភ្ជាប់ឡើងវិញ សូមវាយពាក្យបញ្ជា /start ឡើងវិញ។";
            } else {
                $unlinkText = "ℹ️ <b>គណនីមិនទាន់បានភ្ជាប់៖</b>\n\n" .
                              "គណនី Telegram របស់អ្នកពុំទាន់ត្រូវបានភ្ជាប់ទៅកាន់គណនី SPI AI-ELMS ណាមួយនៅឡើយទេ។\n\n" .
                              "👉 សូមវាយពាក្យបញ្ជា /start ដើម្បីចាប់ផ្តើមភ្ជាប់គណនី។";
            }
            TelegramSecurityPipeline::sendMessage($chatId, $unlinkText, 'HTML');
            $this->safeLog('info', "Handled unlink command for {$senderName} (Chat: {$chatId})");
        } elseif (str_starts_with($cleanCmd, '/dashboard')) {
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

            TelegramSecurityPipeline::sendMessage($chatId, $dashboardText, 'HTML', $inlineKeyboard);
        } elseif (str_starts_with($cleanCmd, '/login')) {
            $loginText = "🔐 <b>ចូលប្រើប្រាស់ប្រព័ន្ធ SPI E-LMS</b>\n\n" .
                         "សូមចុចប៊ូតុងខាងក្រោមដើម្បី Login ចូលប្រព័ន្ធ៖";

            $inlineKeyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '🚀 Login ចូលប្រព័ន្ធ', 'url' => 'https://spilms.tech/login']
                    ]
                ]
            ];

            TelegramSecurityPipeline::sendMessage($chatId, $loginText, 'HTML', $inlineKeyboard);
        } elseif (str_contains($cleanCmd, 'hello') || str_contains($cleanCmd, 'hi') || str_contains($cleanCmd, 'សួស្តី')) {
            $greetingText = "👋 <b>សួស្តី {$senderName}!</b>\n\nតើខ្ញុំអាចជួយអ្វីអ្នកបានដែរទេ? សូមចុចពាក្យបញ្ជា /start ឬ /dashboard ដើម្បីប្រើប្រាស់មុខងាររបស់ប្រព័ន្ធ។ ✨";
            TelegramSecurityPipeline::sendMessage($chatId, $greetingText, 'HTML');
        }
    }

    private function handleCoursesCommand(int|string $chatId, ?User $linkedUser, string $botToken): void
    {
        try {
            if (!$linkedUser) {
                $linkedUser = User::where('telegram_id', (string) $chatId)
                    ->orWhere('telegram_chat_id', (string) $chatId)
                    ->first();
            }

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
                                "👉 សូមចុច <b>«📱 ចែករំលែកលេខទូរស័ព្ទ»</b> ឬ វាយ<b>លេខទូរស័ព្ទ</b> ឬ <b>Email</b> របស់អ្នកផ្ញើមកកាន់ Bot នេះដើម្បីភ្ជាប់គណនី។";
            }

            $inlineKeyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '🚀 បើក E-LMS រៀនឥឡូវនេះ', 'web_app' => ['url' => 'https://spilms.tech/student/dashboard']]
                    ]
                ]
            ];

            TelegramSecurityPipeline::sendMessage($chatId, $responseText, 'HTML', $inlineKeyboard);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("handleCoursesCommand error: " . $e->getMessage());
            TelegramSecurityPipeline::sendMessage($chatId, "📚 <b>វគ្គសិក្សា (SPI E-LMS)</b>\n\nសូមចូលទៅកាន់ Dashboard របស់អ្នកដើម្បីពិនិត្យមើលវគ្គសិក្សា៖", 'HTML', [
                'inline_keyboard' => [
                    [['text' => '🌐 បើក E-LMS Dashboard', 'url' => 'https://spilms.tech/student/dashboard']]
                ]
            ]);
        }
    }

    private function handleDeadlinesCommand(int|string $chatId, string $botToken): void
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

        TelegramSecurityPipeline::sendMessage($chatId, $responseText, 'HTML', $inlineKeyboard);
    }

    private function handleAnnouncementsCommand(int|string $chatId, string $botToken): void
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

        TelegramSecurityPipeline::sendMessage($chatId, $responseText, 'HTML', $inlineKeyboard);
    }

    private function handleProfileCommand(int|string $chatId, ?User $linkedUser, string $botToken): void
    {
        try {
            if (!$linkedUser) {
                $linkedUser = User::where('telegram_id', (string) $chatId)
                    ->orWhere('telegram_chat_id', (string) $chatId)
                    ->first();
            }

            if ($linkedUser) {
                $majorName = $linkedUser->major ? $linkedUser->major->name : 'General Studies';
                $deptName = ($linkedUser->major && $linkedUser->major->department) ? $linkedUser->major->department->name : 'Faculty Department';
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
                                "👉 សូមចុច <b>«📱 ចែករំលែកលេខទូរស័ព្ទ»</b> ខាងក្រោម ឬ វាយ<b>លេខទូរស័ព្ទ</b> ឬ <b>Email</b> របស់អ្នកមកទីនេះ ដើម្បីឱ្យ Bot ស្គាល់គណនីរបស់អ្នក។";
            }

            $inlineKeyboard = [
                'inline_keyboard' => [
                    [
                        ['text' => '👤 បើកទំព័រ Profile', 'web_app' => ['url' => 'https://spilms.tech/profile']]
                    ]
                ]
            ];

            TelegramSecurityPipeline::sendMessage($chatId, $responseText, 'HTML', $inlineKeyboard);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("handleProfileCommand error: " . $e->getMessage());
            TelegramSecurityPipeline::sendMessage($chatId, "👤 <b>ព័ត៌មានគណនី (SPI E-LMS Profile)</b>\n\nសូមចូលទៅកាន់ Profile របស់អ្នកនៅលើប្រព័ន្ធ៖", 'HTML', [
                'inline_keyboard' => [
                    [['text' => '👤 បើកទំព័រ Profile', 'url' => 'https://spilms.tech/profile']]
                ]
            ]);
        }
    }

    private function safeLog(string $level, string $message): void
    {
        if ($this->output) {
            if ($level === 'info') $this->info($message);
            elseif ($level === 'warn') $this->warn($message);
            elseif ($level === 'error') $this->error($message);
            else $this->line($message);
        } else {
            \Illuminate\Support\Facades\Log::info("TelegramPolling: {$message}");
        }
    }
}
