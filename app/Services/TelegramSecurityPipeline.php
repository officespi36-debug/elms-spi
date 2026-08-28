<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramSecurityPipeline
{
    /**
     * ត្រួតពិនិត្យ Update ឆ្លងកាត់ប្រព័ន្ធសុវត្ថិភាព ៥ ជាន់
     */
    public static function validate(array $update): bool
    {
        $message = $update['message'] ?? $update['callback_query']['message'] ?? null;
        $from = $update['message']['from'] ?? $update['callback_query']['from'] ?? null;

        if (!$from) {
            return false;
        }

        $userId = $from['id'];
        $text = $update['message']['text'] ?? $update['callback_query']['data'] ?? '';

        // ជាន់ទី ៥៖ ពិនិត្យ Active Ban
        try {
            if (Cache::has("tg_banned_{$userId}")) {
                return false;
            }
        } catch (\Throwable $e) {
            Log::warning("BanCheck Cache error: " . $e->getMessage());
        }

        // ជាន់ទី ២៖ Rate Limiting & Anti-Flood (អតិបរមា ៥ សារ ក្នុង ៥ វិនាទី)
        try {
            $rateKey = "tg_rate_{$userId}";
            $hits = (int) Cache::get($rateKey, 0);
            if ($hits >= 5) {
                self::triggerForensicAlert($from, 'Anti-Flood Violation (Spam/DoS)', 'CRITICAL', 'Sent > 5 messages in 5s');
                return false;
            }
            Cache::put($rateKey, $hits + 1, now()->addSeconds(5));
        } catch (\Throwable $e) {
            Log::warning("RateLimit Cache error: " . $e->getMessage());
        }

        // ជាន់ទី ៤៖ Input Sanitization & Threat Scanner (SQLi, XSS, RCE, Path Traversal)
        if (!empty($text)) {
            $threatSignatures = [
                'SQL Injection'               => '/(\b(SELECT|UNION|INSERT|UPDATE|DELETE|DROP|ALTER|TABLE|TRUNCATE)\b)/i',
                'Cross-Site Scripting (XSS)'  => '/(<\s*script\b[^>]*>|javascript:|onerror\s*=|onload\s*=)/i',
                'Directory Traversal'         => '/(\.\.\/|\.\.\\\\)/',
                'Remote Code Execution (RCE)' => '/\b(base64_decode|eval|system|exec|passthru|shell_exec)\s*\(/i',
            ];

            foreach ($threatSignatures as $type => $pattern) {
                if (preg_match($pattern, $text)) {
                    self::triggerForensicAlert($from, $type, 'CRITICAL', $text);
                    return false;
                }
            }
        }

        // ជាន់ទី ៣៖ LMS Identity Whitelist Check
        try {
            $isLinked = DB::table('users')
                ->where('telegram_chat_id', (string)$userId)
                ->orWhere('telegram_id', (string)$userId)
                ->exists();

            $rawCmd = strtolower(trim($text));
            $cleanCmd = preg_replace('/^(\/\w+)@\w+/i', '$1', $rawCmd);

            $cleanDigits = preg_replace('/[^0-9]/', '', $cleanCmd);
            $looksLikeIdentifier = str_contains($cleanCmd, '@')
                || (strlen($cleanDigits) >= 8 && strlen($cleanDigits) <= 15)
                || preg_match('/^(stu|tch|adm|usr)[0-9]+/i', $cleanCmd);

            $isCommandOrAction = !empty($update['callback_query'])
                || !empty($update['message']['contact'])
                || str_starts_with($cleanCmd, '/start')
                || str_starts_with($cleanCmd, '/login')
                || str_starts_with($cleanCmd, '/logout')
                || str_starts_with($cleanCmd, '/unlink')
                || str_starts_with($cleanCmd, '/switch')
                || str_starts_with($cleanCmd, '/dashboard')
                || str_starts_with($cleanCmd, '/courses')
                || str_starts_with($cleanCmd, '/deadlines')
                || str_starts_with($cleanCmd, '/announcements')
                || str_starts_with($cleanCmd, '/me')
                || str_starts_with($cleanCmd, '/profile')
                || str_starts_with($cleanCmd, '/id')
                || str_starts_with($cleanCmd, '/schedule')
                || str_starts_with($cleanCmd, '/support')
                || str_starts_with($cleanCmd, '/help')
                || str_contains($cleanCmd, 'វគ្គសិក្សា')
                || str_contains($cleanCmd, 'កាលបរិច្ឆេទ')
                || str_contains($cleanCmd, 'ដំណឹង')
                || str_contains($cleanCmd, 'គណនី')
                || str_contains($cleanCmd, 'ជំនួយ')
                || $cleanCmd === 'support'
                || $cleanCmd === 'help'
                || str_contains($cleanCmd, 'hello')
                || str_contains($cleanCmd, 'hi')
                || str_contains($cleanCmd, 'សួស្តី')
                || $looksLikeIdentifier;

            if (!$isLinked && !$isCommandOrAction) {
                self::sendMessage(
                    $userId,
                    "⚠️ *ការអនុញ្ញាតត្រូវបានបដិសេធ*\n\nសូមភ្ជាប់គណនី LMS របស់អ្នកជាមុនសិនដោយចុចពាក្យបញ្ជា `/start` ឬ `/login`",
                    'Markdown'
                );
                return false;
            }
        } catch (\Throwable $e) {
            Log::warning("LMS Whitelist DB check error: " . $e->getMessage());
        }

        return true;
    }

    /**
     * កត់ត្រាព័ត៌មានលម្អិត បិទគណនី និងបាញ់ Alert ចូល Admin Group
     */
    public static function triggerForensicAlert(array $from, string $threatType, string $severity, string $payload): void
    {
        $userId = $from['id'];
        
        try {
            Cache::put("tg_banned_{$userId}", true, now()->addHours(24));
        } catch (\Throwable $e) {
            Log::warning("Auto-Ban Cache error: " . $e->getMessage());
        }

        $adminChatId = config('services.telegram.admin_chat_id') ?? config('services.telegram.chat_id');
        $rawName = trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? '')) ?: 'N/A';
        $rawUser = isset($from['username']) && !empty($from['username']) ? '@' . $from['username'] : 'None';
        $lang = strtoupper($from['language_code'] ?? 'KM');

        Log::warning("TELEGRAM_SECURITY_ALERT: User [{$userId}] trigger [{$threatType}]. Payload: {$payload}");

        if (!$adminChatId) {
            return;
        }

        // Escape special Markdown characters for reliable Telegram rendering
        $name = str_replace(['_', '*', '`', '['], ['\_', '\*', '\`', '\['], $rawName);
        $user = str_replace(['_', '*', '`', '['], ['\_', '\*', '\`', '\['], $rawUser);
        $safeThreat = str_replace(['_', '*', '`', '['], ['\_', '\*', '\`', '\['], $threatType);
        $safePayload = str_replace(['`'], ["'"], substr($payload, 0, 500));

        $alert = "🚨 *SPI E-LMS: SECURITY INCIDENT REPORT* 🚨\n"
            . "━━━━━━━━━━━━━━━━━━━━━\n"
            . "👤 *ATTACKER PROFILE*\n"
            . " • *Name:* {$name}\n"
            . " • *Username:* {$user}\n"
            . " • *Telegram ID:* `{$userId}`\n"
            . " • *Client Lang:* `{$lang}`\n\n"
            . "🎯 *THREAT ANALYSIS*\n"
            . " • *Classification:* `{$safeThreat}`\n"
            . " • *Severity:* `[{$severity}]`\n"
            . " • *Action:* Auto-Banned for 24 Hours\n"
            . " • *Timestamp:* `" . now()->toDateTimeString() . "`\n\n"
            . "📝 *INTERCEPTED PAYLOAD*\n"
            . "```text\n" . $safePayload . "\n```\n"
            . "━━━━━━━━━━━━━━━━━━━━━\n"
            . "🛡️ *Status:* Traffic Blocked | LMS Protected";

        self::sendMessage($adminChatId, $alert, 'Markdown');
    }

    /**
     * Backward compatibility wrapper for triggerAlertAndBan
     */
    public static function triggerAlertAndBan(int|string $userId, string $username, string $threat, string $payload): void
    {
        $from = [
            'id'            => $userId,
            'username'      => $username,
            'first_name'    => $username,
            'language_code' => 'KM',
        ];
        self::triggerForensicAlert($from, $threat, 'CRITICAL', $payload);
    }

    /**
     * ផ្ញើសារចេញទៅកាន់ Telegram API ជាមួយនឹង Support Reply Markup (Buttons)
     */
    public static function sendMessage($chatId, string $text, ?string $mode = null, ?array $replyMarkup = null): void
    {
        $token = config('services.telegram.bot_token');
        if (!$token || !$chatId) {
            return;
        }

        $payload = [
            'chat_id'                  => (string)$chatId,
            'text'                     => $text,
            'disable_web_page_preview' => false,
        ];
        if ($mode) {
            $payload['parse_mode'] = $mode;
        }
        if (!empty($replyMarkup)) {
            $payload['reply_markup'] = json_encode($replyMarkup);
        }

        try {
            Http::withoutVerifying()
                ->timeout(15)
                ->post("https://api.telegram.org/bot{$token}/sendMessage", $payload);
        } catch (\Throwable $e) {
            Log::error("Telegram sendMessage exception: " . $e->getMessage());
        }
    }
}
