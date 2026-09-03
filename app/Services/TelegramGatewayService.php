<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class TelegramGatewayService
{
    protected ?string $token;
    protected string $apiUrl;

    public function __construct(?string $token = null)
    {
        $this->token = $token
            ?: config('services.telegram_gateway.token')
            ?: env('TELEGRAM_GATEWAY_TOKEN')
            ?: Setting::get('telegram_gateway_token');

        $this->apiUrl = rtrim(config('services.telegram_gateway.api_url') ?: 'https://gatewayapi.telegram.org', '/');
    }

    public function isConfigured(): bool
    {
        return !empty($this->token);
    }

    /**
     * Format phone number to E.164 standard (+855XXXXXXXX)
     */
    public static function formatE164Phone(string $phone): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($clean, '8550')) {
            $clean = '855' . substr($clean, 4);
        } elseif (str_starts_with($clean, '855')) {
            // Already starts with 855
        } elseif (str_starts_with($clean, '0')) {
            $clean = '855' . substr($clean, 1);
        } else {
            $clean = '855' . $clean;
        }

        return '+' . $clean;
    }

    /**
     * Send verification message (OTP) via Telegram Gateway (@VerificationCodes)
     *
     * @param string $phone Phone number in local or international format
     * @param string|null $code Custom verification code (if null, Telegram can generate it)
     * @param int $ttl Time to live in seconds (60 - 3600)
     * @param array $extra Optional payload, callback_url, etc.
     * @return array ['success' => bool, 'request_id' => string|null, 'error' => string|null, 'delivery_status' => string|null, 'raw' => array]
     */
    public function sendVerificationMessage(string $phone, ?string $code = null, int $ttl = 300, array $extra = []): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'error' => 'NOT_CONFIGURED',
                'message' => 'Telegram Gateway token is missing',
            ];
        }

        $e164 = self::formatE164Phone($phone);
        $payload = array_merge([
            'phone_number' => $e164,
            'ttl' => max(60, min(3600, $ttl)),
        ], $extra);

        if ($code !== null) {
            $payload['code'] = (string) $code;
        } else {
            $payload['code_length'] = 6;
        }

        $result = $this->callApi('sendVerificationMessage', $payload);

        if ($result['ok'] ?? false) {
            return [
                'success' => true,
                'request_id' => $result['result']['request_id'] ?? null,
                'phone_number' => $result['result']['phone_number'] ?? $e164,
                'request_cost' => $result['result']['request_cost'] ?? 0,
                'delivery_status' => $result['result']['delivery_status']['status'] ?? 'sent',
                'raw' => $result,
            ];
        }

        return [
            'success' => false,
            'error' => $result['error'] ?? 'UNKNOWN_ERROR',
            'message' => $result['error'] ?? 'Failed to send Telegram Gateway verification',
            'raw' => $result,
        ];
    }

    /**
     * Check if a phone number can receive a verification message
     */
    public function checkSendAbility(string $phone): array
    {
        if (!$this->isConfigured()) {
            return ['can_send' => false, 'error' => 'NOT_CONFIGURED'];
        }

        $e164 = self::formatE164Phone($phone);
        $result = $this->callApi('checkSendAbility', ['phone_number' => $e164]);

        return [
            'can_send' => (bool) ($result['ok'] ?? false),
            'raw' => $result,
        ];
    }

    /**
     * Check verification delivery and read status
     */
    public function checkVerificationStatus(string $requestId): array
    {
        return $this->callApi('checkVerificationStatus', ['request_id' => $requestId]);
    }

    /**
     * Revoke verification message (e.g. if expired or already verified)
     */
    public function revokeVerificationMessage(string $requestId): array
    {
        return $this->callApi('revokeVerificationMessage', ['request_id' => $requestId]);
    }

    /**
     * Internal cURL helper for Telegram Gateway API
     */
    protected function callApi(string $method, array $data = []): array
    {
        $url = $this->apiUrl . '/' . ltrim($method, '/');
        $json = json_encode($data);

        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_FRESH_CONNECT => true,
                CURLOPT_IPRESOLVE => defined('CURL_IPRESOLVE_V4') ? CURL_IPRESOLVE_V4 : 1,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $this->token,
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
                CURLOPT_POSTFIELDS => $json,
            ]);

            $response = curl_exec($ch);
            $err = curl_error($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($err) {
                Log::error("TelegramGatewayService cURL error [{$method}]: " . $err);
                return ['ok' => false, 'error' => 'CURL_ERROR', 'description' => $err];
            }

            $decoded = json_decode((string) $response, true);
            if (!is_array($decoded)) {
                Log::warning("TelegramGatewayService non-JSON response [HTTP {$code}]: " . substr((string) $response, 0, 200));
                return ['ok' => false, 'error' => 'INVALID_JSON_RESPONSE', 'http_code' => $code];
            }

            return $decoded;
        } catch (\Throwable $e) {
            Log::error("TelegramGatewayService exception [{$method}]: " . $e->getMessage());
            return ['ok' => false, 'error' => 'EXCEPTION', 'description' => $e->getMessage()];
        }
    }
}
