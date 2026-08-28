<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\TelegramSecurityPipeline;
use App\Models\User;

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
                    ->timeout(35)
                    ->get("https://api.telegram.org/bot{$botToken}/getUpdates", [
                        'offset'          => $offset,
                        'timeout'         => 30,
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
        $text = strtolower($rawText);
        $cleanCmd = preg_replace('/^(\/\w+)@\w+/i', '$1', $text);
        $senderName = $message['from']['first_name'] ?? 'Student';
        $telegramUsername = $message['from']['username'] ?? null;

        // Handle native contact sharing
        if (isset($message['contact']['phone_number'])) {
            $rawText = trim($message['contact']['phone_number']);
            $text = strtolower($rawText);
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

            $linkedUser = null;

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

            $this->info("Handled start/link for {$senderName} (Chat: {$chatId})");
            TelegramSecurityPipeline::sendMessage($chatId, $welcomeText, 'HTML', $replyMarkup);
            TelegramSecurityPipeline::sendMessage($chatId, "⚡ <b>រុករកទំព័រសំខាន់ៗ (Quick Links)៖</b>", 'HTML', $inlineKeyboard);
        } elseif (str_starts_with($cleanCmd, '/support') || str_starts_with($cleanCmd, '/help') || $cleanCmd === 'support' || $cleanCmd === 'help') {
            TelegramSecurityPipeline::sendMessage($chatId, $supportText, 'HTML', $supportKeyboard);
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
}
