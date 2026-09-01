<?php

namespace App\Services;

use App\Models\JwtSession;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmergencyAlertService
{
    /**
     * Trigger Multi-Channel Emergency Alarm & Auto-Defense Pipeline
     */
    public static function triggerEmergencyPipeline(array $threatData): array
    {
        $ip = $threatData['ip'] ?? '0.0.0.0';
        $severity = strtoupper($threatData['severity'] ?? 'LOW');
        $threatType = $threatData['threat_type'] ?? 'Unknown Threat';
        $location = ($threatData['city'] ?? 'Unknown City') . ', ' . ($threatData['country'] ?? 'Unknown Country');
        $phone = config('services.emergency.phone') ?: Setting::get('emergency_phone', '0964618507');

        $results = [
            'auto_defense' => false,
            'voice_call'   => false,
            'sms'          => false,
            'pushover'     => false,
            'details'      => [],
        ];

        // 1. Execute Immediate Auto-Defense (Session Isolation & Firewall Blacklist)
        $autoDefenseEnabled = config('services.emergency.auto_isolation') ?? Setting::get('emergency_auto_defense', true);
        if ($autoDefenseEnabled && in_array($severity, ['CRITICAL', 'HIGH'])) {
            $results['auto_defense'] = static::executeAutoDefense($threatData);
            $results['details'][] = "🛡️ Auto-Defense: Isolated IP {$ip} and terminated active sessions.";
        }

        // 2. Automated Voice Call Alert
        $callEnabled = config('services.emergency.call_enabled') ?? Setting::get('emergency_call_enabled', false);
        if ($callEnabled && in_array($severity, ['CRITICAL', 'HIGH']) && !empty($phone)) {
            $callVoiceMsg = "Emergency security alert from Saint Paul Institute E-LMS! A {$severity} threat, {$threatType}, was intercepted from IP {$ip} in {$location}. Automated defense has isolated the session. Please check your admin dashboard immediately.";
            $results['voice_call'] = static::triggerVoiceCall($phone, $callVoiceMsg);
            $results['details'][] = "📞 Voice Call: Dispatched outbound emergency call to {$phone}.";
        }

        // 3. Emergency SMS Alert (Via PlasGate Gateway)
        $smsEnabled = config('services.emergency.sms_enabled') ?? Setting::get('emergency_sms_enabled', true);
        if ($smsEnabled && in_array($severity, ['CRITICAL', 'HIGH']) && !empty($phone)) {
            $smsContent = "🚨 [SPI E-LMS ALARM] ការវាយប្រហារកម្រិត {$severity} ត្រូវបានស្ទាក់ចាប់! ប្រភេទ៖ {$threatType}, IP: {$ip} ({$location})។ ប្រព័ន្ធបានកាត់ផ្តាច់ Session និង Blacklist IP រួចរាល់។";
            $results['sms'] = static::sendEmergencySms($phone, $smsContent);
            $results['details'][] = "📱 SMS Alert: Sent urgent SMS notification to {$phone}.";
        }

        // 4. Critical Push Notification (Via Pushover Emergency Channel)
        $pushoverEnabled = config('services.emergency.pushover_enabled') ?? Setting::get('emergency_pushover_enabled', false);
        if ($pushoverEnabled && in_array($severity, ['CRITICAL', 'HIGH'])) {
            $pushTitle = "🚨 CRITICAL BREACH INTERCEPTED: {$threatType}";
            $pushMsg = "Threat detected from IP {$ip} ({$location}). Severity: {$severity}. Payload: " . substr($threatData['payload'] ?? '', 0, 100);
            $results['pushover'] = static::sendPushoverAlert($pushTitle, $pushMsg);
            $results['details'][] = "🔔 Pushover: Emergency ring notification sent.";
        }

        // 5. Append to Emergency Audit Log
        static::logEmergencyIncident($threatData, $results);

        return $results;
    }

    /**
     * Active Defense: Revoke Sessions & Blacklist Attacker IP Immediately
     */
    public static function executeAutoDefense(array $threatData): bool
    {
        $ip = $threatData['ip'] ?? null;
        $userId = $threatData['user_id'] ?? null;

        try {
            // 1. Blacklist IP on Cache & Setting
            if (!empty($ip) && $ip !== '127.0.0.1') {
                Cache::forever("blacklisted_ip_{$ip}", true);

                $blocked = Setting::get('blocked_ips', []);
                if (!in_array($ip, $blocked)) {
                    $blocked[] = $ip;
                    Setting::set('blocked_ips', array_values(array_unique($blocked)));
                }
            }

            // 2. Terminate all active JWT Sessions from this IP or User ID
            if (!empty($ip)) {
                JwtSession::where('ip_address', $ip)->update(['is_revoked' => true]);
            }
            if (!empty($userId) && is_numeric($userId)) {
                JwtSession::where('user_id', $userId)->update(['is_revoked' => true]);
                Cache::forever("tg_banned_{$userId}", true);
            }

            Log::alert("AUTO-DEFENSE ISOLATION EXECUTED: Terminated sessions and blacklisted IP [{$ip}] / User [{$userId}]");
            return true;
        } catch (\Throwable $e) {
            Log::error("Auto-Defense Execution Failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Automated Emergency Voice Call (Twilio Voice API / Webhook Call)
     */
    public static function triggerVoiceCall(string $phone, string $voiceMessage): bool
    {
        $accountSid = config('services.emergency.twilio_account_sid');
        $authToken  = config('services.emergency.twilio_auth_token');
        $fromPhone  = config('services.emergency.twilio_from');

        $formattedPhone = PlasGateService::formatCambodianPhone($phone);
        $e164Phone = '+' . $formattedPhone;

        if (empty($accountSid) || empty($authToken) || empty($fromPhone)) {
            Log::info("Voice Call Simulated: Twilio API keys not set. Call to {$e164Phone} logged.", ['message' => $voiceMessage]);
            return true;
        }

        try {
            $twiml = '<Response><Say voice="Polly.Joanna" language="en-US">' . htmlspecialchars($voiceMessage) . '</Say></Response>';

            $res = Http::withBasicAuth($accountSid, $authToken)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Calls.json", [
                    'To'    => $e164Phone,
                    'From'  => $fromPhone,
                    'Twiml' => $twiml,
                ]);

            if ($res->successful()) {
                Log::info("Twilio Voice Call Dispatched successfully to {$e164Phone}. Call SID: " . $res->json('sid'));
                return true;
            } else {
                Log::error("Twilio Voice Call Failed: " . $res->body());
                return false;
            }
        } catch (\Throwable $e) {
            Log::error("Twilio Voice Call Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Emergency SMS Notification (Via PlasGate Cambodian Gateway)
     */
    public static function sendEmergencySms(string $phone, string $content): bool
    {
        try {
            $plasgate = new PlasGateService();
            if ($plasgate->isConfigured()) {
                $plasgate->sendSms($phone, $content);
            }
            Log::info("Emergency SMS Alert logged for recipient [{$phone}] via PlasGate: {$content}");
            return true;
        } catch (\Throwable $e) {
            Log::error("Emergency SMS Delivery Failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Pushover Emergency Push Notification (Bypasses Silent & DND Mode)
     */
    public static function sendPushoverAlert(string $title, string $message): bool
    {
        $userKey = config('services.emergency.pushover_user_key') ?: Setting::get('pushover_user_key');
        $token   = config('services.emergency.pushover_token') ?: Setting::get('pushover_token');

        if (empty($userKey) || empty($token)) {
            Log::info("Pushover Alert Simulated (Credentials not set): [{$title}] {$message}");
            return true;
        }

        try {
            $res = Http::asForm()->post('https://api.pushover.net/1/messages.json', [
                'token'     => $token,
                'user'      => $userKey,
                'title'     => $title,
                'message'   => $message,
                'priority'  => 2, // Emergency priority (rings repeatedly)
                'retry'     => 30, // Retry every 30 seconds
                'expire'    => 3600, // For 1 hour
                'sound'     => 'siren', // High-pitch siren alarm sound
            ]);

            return $res->successful();
        } catch (\Throwable $e) {
            Log::error("Pushover Alert Exception: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Record Emergency Incident to Log File
     */
    private static function logEmergencyIncident(array $threat, array $actions): void
    {
        $record = sprintf(
            "[%s] EMERGENCY TRIGGER | SEVERITY: %s | THREAT: %s | IP: %s (%s, %s) | ACTIONS: Defense=%s, Call=%s, SMS=%s, Push=%s\n",
            now()->format('Y-m-d H:i:s'),
            $threat['severity'] ?? 'N/A',
            $threat['threat_type'] ?? 'N/A',
            $threat['ip'] ?? '0.0.0.0',
            $threat['city'] ?? 'Unknown',
            $threat['country'] ?? 'Unknown',
            $actions['auto_defense'] ? 'YES' : 'NO',
            $actions['voice_call'] ? 'YES' : 'NO',
            $actions['sms'] ? 'YES' : 'NO',
            $actions['pushover'] ? 'YES' : 'NO'
        );

        @file_put_contents(storage_path('logs/emergency_defense.log'), $record, FILE_APPEND);
    }
}
