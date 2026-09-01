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
     * Geo-IP Lookup to extract Country, City, ISP, Coordinates from IP Address
     */
    public static function lookupGeoIp(?string $ip): array
    {
        if (empty($ip) || in_array($ip, ['127.0.0.1', '::1', 'localhost']) || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
            return [
                'status'   => 'local',
                'country'  => 'Localhost / Internal Network',
                'city'     => 'Local Server',
                'isp'      => 'Internal / Private IP',
                'lat'      => 11.5564, // Phnom Penh Default fallback coordinates
                'lon'      => 104.9282,
                'query'    => $ip ?? '127.0.0.1',
            ];
        }

        try {
            $response = Http::withoutVerifying()->timeout(4)->get("http://ip-api.com/json/{$ip}?fields=status,message,country,countryCode,regionName,city,zip,lat,lon,timezone,isp,org,as,query");
            if ($response->successful() && $response->json('status') === 'success') {
                return $response->json();
            }
        } catch (\Throwable $e) {
            Log::warning("Geo-IP Lookup exception for [{$ip}]: " . $e->getMessage());
        }

        return [
            'status'   => 'fail',
            'country'  => 'Unknown Location',
            'city'     => 'Unknown City',
            'isp'      => 'Unknown ISP/Proxy',
            'lat'      => 11.5564,
            'lon'      => 104.9282,
            'query'    => $ip,
        ];
    }

    /**
     * កត់ត្រាព័ត៌មានលម្អិត បិទគណនី និងបាញ់ Alert ជាមួយប៊ូតុង Interactive (Location & Ban) ចូល Admin Group
     */
    public static function triggerForensicAlert(array $from, string $threatType, string $severity, string $payload, ?string $ip = null, ?string $userAgent = null): void
    {
        $userId = $from['id'] ?? 'N/A';
        
        try {
            if ($userId !== 'N/A') {
                Cache::put("tg_banned_{$userId}", true, now()->addHours(24));
            }
        } catch (\Throwable $e) {
            Log::warning("Auto-Ban Cache error: " . $e->getMessage());
        }

        $adminChatId = Cache::get('telegram_admin_chat_id') ?? config('services.telegram.admin_chat_id') ?? config('services.telegram.chat_id') ?? '-5560385465';
        $rawName = trim(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? '')) ?: 'N/A';
        $rawUser = isset($from['username']) && !empty($from['username']) ? '@' . $from['username'] : 'None';
        $lang = strtoupper($from['language_code'] ?? 'KM');
        $clientIp = $ip ?? request()->ip() ?? 'Unknown';
        $clientUa = $userAgent ?? request()->userAgent() ?? 'Unknown';

        // 1. Perform Geo-IP Resolution
        $geo = self::lookupGeoIp($clientIp);
        $country = $geo['country'] ?? 'Unknown Country';
        $city = $geo['city'] ?? 'Unknown City';
        $isp = $geo['isp'] ?? 'Unknown ISP';
        $lat = $geo['lat'] ?? 11.5564;
        $lon = $geo['lon'] ?? 104.9282;

        // 2. Persist structured forensic records to logs and attacker_log.txt
        try {
            $forensicEntry = [
                'timestamp'   => now()->toIso8601String(),
                'user_id'     => $userId,
                'name'        => $rawName,
                'username'    => $rawUser,
                'client_lang' => $lang,
                'ip'          => $clientIp,
                'country'     => $country,
                'city'        => $city,
                'isp'         => $isp,
                'coordinates' => "{$lat},{$lon}",
                'user_agent'  => $clientUa,
                'threat_type' => $threatType,
                'severity'    => $severity,
                'payload'     => $payload,
                'action'      => 'AUTO_BANNED_24H',
            ];

            // Append to telegram_forensics.log
            file_put_contents(storage_path('logs/telegram_forensics.log'), json_encode($forensicEntry, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND | LOCK_EX);

            // Append to formatted attacker_log.txt for quick human audit
            $txtRecord = sprintf(
                "[%s] THREAT: %s | ID: %s | USER: %s | IP: %s (%s, %s - %s) | GEO: %s,%s | PAYLOAD: %s\n",
                now()->toDateTimeString(),
                $threatType,
                $userId,
                $rawUser,
                $clientIp,
                $city,
                $country,
                $isp,
                $lat,
                $lon,
                substr(str_replace(["\r", "\n"], ' ', $payload), 0, 150)
            );
            file_put_contents(storage_path('logs/attacker_log.txt'), $txtRecord, FILE_APPEND | LOCK_EX);

            // 3. Multi-Channel Emergency Alarm (Voice Call, SMS, Push, and Auto-Defense Isolation)
            \App\Services\EmergencyAlertService::triggerEmergencyPipeline($forensicEntry);
        } catch (\Throwable $e) {
            Log::warning("Forensic Log write error: " . $e->getMessage());
        }

        Log::warning("TELEGRAM_SECURITY_ALERT: User [{$userId}] IP [{$clientIp}] trigger [{$threatType}]. Payload: {$payload}");

        if (!$adminChatId) {
            return;
        }

        // 3. Escape special Markdown characters for reliable Telegram rendering
        $name = str_replace(['_', '*', '`', '['], ['\_', '\*', '\`', '\['], $rawName);
        $user = str_replace(['_', '*', '`', '['], ['\_', '\*', '\`', '\['], $rawUser);
        $safeThreat = str_replace(['_', '*', '`', '['], ['\_', '\*', '\`', '\['], $threatType);
        $safePayload = str_replace(['`'], ["'"], substr($payload, 0, 500));
        $safeIp = str_replace(['_', '*', '`', '['], ['\_', '\*', '\`', '\['], $clientIp);
        $safeGeo = str_replace(['_', '*', '`', '['], ['\_', '\*', '\`', '\['], "{$city}, {$country} ({$isp})");

        $alert = "🚨 *SPI E-LMS: SECURITY INCIDENT REPORT* 🚨\n"
            . "━━━━━━━━━━━━━━━━━━━━━\n"
            . "👤 *ATTACKER DIGITAL FOOTPRINT*\n"
            . " • *Name:* {$name}\n"
            . " • *Username:* {$user}\n"
            . " • *Telegram ID:* `{$userId}`\n"
            . " • *IP Address:* `{$safeIp}`\n"
            . " • *Location:* `{$safeGeo}`\n"
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

        // 4. Create Interactive Inline Keyboard with Google Maps & Instant Ban buttons
        $inlineButtons = [];

        // Row 1: Google Maps Button
        $mapUrl = "https://maps.google.com/?q={$lat},{$lon}";
        $row1 = [
            ['text' => '📍 មើលទីតាំង (Google Maps)', 'url' => $mapUrl]
        ];

        // Row 2: Ban Attacker Button (if valid user ID)
        $row2 = [];
        if ($userId !== 'N/A' && is_numeric($userId)) {
            $row2[] = ['text' => '⛔ Block & Ban ភ្លាមៗ', 'callback_data' => "ban_user_{$userId}"];
        }
        if ($clientIp !== 'Unknown' && $clientIp !== '127.0.0.1') {
            $row2[] = ['text' => '🛡️ Blacklist IP', 'callback_data' => "ban_ip_" . str_replace('.', '-', $clientIp)];
        }

        $inlineButtons[] = $row1;
        if (!empty($row2)) {
            $inlineButtons[] = $row2;
        }

        $replyMarkup = ['inline_keyboard' => $inlineButtons];

        self::sendMessage($adminChatId, $alert, 'Markdown', $replyMarkup);
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
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot{$token}/sendMessage");
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
            curl_setopt($ch, CURLOPT_RESOLVE, ["api.telegram.org:443:149.154.166.110"]);

            $result = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);

            if ($err) {
                Log::error("Telegram sendMessage cURL error: " . $err);
            }
        } catch (\Throwable $e) {
            Log::error("Telegram sendMessage exception: " . $e->getMessage());
        }
    }
}
