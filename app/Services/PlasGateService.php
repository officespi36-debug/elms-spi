<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PlasGateService
{
    protected string $apiUrl;
    protected ?string $privateKey;
    protected ?string $secretKey;
    protected string $sender;

    public function __construct()
    {
        $this->apiUrl = config('services.plasgate.api_url') 
            ?: env('PLASGATE_API_URL') 
            ?: Setting::get('plasgate_api_url', 'https://cloudapi.plasgate.com/rest/send');

        $this->privateKey = config('services.plasgate.private_key') 
            ?: env('PLASGATE_PRIVATE_KEY') 
            ?: Setting::get('plasgate_private_key');

        $this->secretKey = config('services.plasgate.secret_key') 
            ?: env('PLASGATE_SECRET_KEY') 
            ?: Setting::get('plasgate_secret_key');

        $this->sender = config('services.plasgate.sender_name') 
            ?: env('PLASGATE_SENDER_NAME') 
            ?: Setting::get('plasgate_sender_name', 'SMS Info');
    }

    /**
     * Standardize any Cambodian phone number into international format: 855XXXXXXXX
     * Supports:
     * - Smart: 010, 015, 016, 069, 070, 086, 087, 093, 096, 098
     * - Cellcard: 011, 012, 014, 017, 061, 076, 077, 078, 085, 089, 092, 095, 099
     * - Metfone: 031, 060, 066, 067, 068, 071, 088, 090, 097
     * - Seatel/Cootel/Kingtel: 018, 038
     * 
     * Handled input examples:
     * - "096 461 8507" -> "855964618507"
     * - "0888010546"   -> "855888010546"
     * - "855964618507"  -> "855964618507"
     * - "+855 096 461 8507" -> "855964618507"
     * - "964618507"    -> "855964618507"
     */
    public static function formatCambodianPhone(string $phone): string
    {
        // 1. Strip all non-digit characters (+, spaces, hyphens, brackets)
        $clean = preg_replace('/[^0-9]/', '', $phone);

        // 2. Remove leading 0 if prefixed with 8550 (e.g. 8550964618507 -> 855964618507)
        if (str_starts_with($clean, '8550')) {
            $clean = '855' . substr($clean, 4);
        } elseif (str_starts_with($clean, '855')) {
            // Already starts with 855, keep as is
        } elseif (str_starts_with($clean, '0')) {
            // Standard local number (e.g. 0964618507 -> 855964618507)
            $clean = '855' . substr($clean, 1);
        } else {
            // Without leading 0 (e.g. 964618507 -> 855964618507)
            $clean = '855' . $clean;
        }

        return $clean;
    }

    /**
     * Standardize any Cambodian phone number into standard local format: 0XXXXXXXX
     * (e.g. "855964618507" -> "0964618507")
     */
    public static function toLocalPhone(string $phone): string
    {
        $intl = static::formatCambodianPhone($phone);
        if (str_starts_with($intl, '855')) {
            return '0' . substr($intl, 3);
        }
        return $intl;
    }

    /**
     * Check if PlasGate Gateway has valid API credentials configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->privateKey) && !empty($this->secretKey);
    }

    /**
     * Send 6-digit OTP code to the recipient phone number via PlasGate Official REST API.
     */
    public function sendOtp(string $phone, string $otpCode): bool
    {
        $formattedPhone = static::formatCambodianPhone($phone);
        $message = "[Saint Paul Institute E-LMS] លេខកូដផ្ទៀងផ្ទាត់ OTP របស់អ្នកគឺ៖ {$otpCode} (មានសុពលភាព ៥ នាទី)។ សូមកុំចែករំលែកលេខកូដនេះទៅកាន់អ្នកដទៃ។";

        if (!$this->isConfigured()) {
            Log::warning('PlasGate SMS Gateway: Missing API Keys. Running in local/mock mode.');
            return false;
        }

        $url = $this->apiUrl . (str_contains($this->apiUrl, '?') ? '&' : '?') . 'private_key=' . urlencode(trim((string) $this->privateKey));
        $payload = [
            'sender'  => trim($this->sender),
            'to'      => $formattedPhone,
            'content' => $message,
        ];

        // Attempt sending with automatic retry on transient network glitch (up to 2 attempts)
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            // 1. Primary Attempt: Native PHP cURL with IPv4 forced & robust timeout
            if (function_exists('curl_init')) {
                try {
                    $ch = curl_init($url);
                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST           => true,
                        CURLOPT_POSTFIELDS     => json_encode($payload),
                        CURLOPT_HTTPHEADER     => [
                            'X-Secret: ' . trim((string) $this->secretKey),
                            'Content-Type: application/json',
                            'Accept: application/json',
                        ],
                        CURLOPT_IPRESOLVE      => defined('CURL_IPRESOLVE_V4') ? CURL_IPRESOLVE_V4 : 1,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_CONNECTTIMEOUT => 12,
                        CURLOPT_TIMEOUT        => 22,
                    ]);

                    $result = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curlErr = curl_error($ch);
                    curl_close($ch);

                    if ($httpCode >= 200 && $httpCode < 300) {
                        $decoded = json_decode((string) $result, true);
                        Log::info("PlasGate SMS Sent Successfully (Attempt {$attempt})", [
                            'to'       => $formattedPhone,
                            'httpCode' => $httpCode,
                            'response' => $decoded ?: $result,
                        ]);
                        return true;
                    }

                    Log::warning("PlasGate Direct cURL returned non-200 (Attempt {$attempt})", [
                        'httpCode' => $httpCode,
                        'error'    => $curlErr,
                        'response' => $result,
                    ]);
                } catch (\Throwable $curlEx) {
                    Log::warning("PlasGate Direct cURL Exception (Attempt {$attempt}): " . $curlEx->getMessage());
                }
            }

            // 2. Secondary Attempt: Laravel Http Client
            try {
                $response = Http::withoutVerifying()
                    ->timeout(18)
                    ->withOptions([
                        'curl' => [
                            CURLOPT_IPRESOLVE      => defined('CURL_IPRESOLVE_V4') ? CURL_IPRESOLVE_V4 : 1,
                            CURLOPT_CONNECTTIMEOUT => 10,
                        ],
                    ])
                    ->withHeaders([
                        'X-Secret'     => trim((string) $this->secretKey),
                        'Content-Type' => 'application/json',
                        'Accept'       => 'application/json',
                    ])
                    ->post($url, $payload);

                if ($response->successful()) {
                    Log::info("PlasGate SMS Sent Successfully via Laravel Http (Attempt {$attempt})", [
                        'to'       => $formattedPhone,
                        'status'   => $response->status(),
                        'response' => $response->json(),
                    ]);
                    return true;
                }

                Log::error("PlasGate Http Client Failed (Attempt {$attempt})", [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
            } catch (\Throwable $httpEx) {
                Log::error("PlasGate Http Client Exception (Attempt {$attempt}): " . $httpEx->getMessage());
            }

            if ($attempt < 2) {
                usleep(800000); // 0.8s pause before retry
            }
        }

        return false;
    }
}
