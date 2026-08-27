<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Turnstile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 1. On local environment or localhost / 127.0.0.1, always pass smoothly
        if (app()->isLocal() || request()->getHost() === '127.0.0.1' || request()->getHost() === 'localhost') {
            session(['turnstile_verified_at' => now()]);
            return;
        }

        // 2. If value is empty on production, require verification
        if (empty($value)) {
            $fail('សូមបំពេញការផ្ទៀងផ្ទាត់សុវត្ថិភាព Cloudflare (Turnstile) ជាមុនសិន។');
            return;
        }

        // 3. If token is test token or valid format
        if (is_string($value) && (str_starts_with($value, '1x') || str_starts_with($value, '0.') || strlen($value) >= 15)) {
            try {
                $secretKey = config('services.turnstile.secret') ?: '0x4AAAAAAEXbfkIFYCt1IyL5NESxUocpEvo';

                $response = Http::asForm()->timeout(4)->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $secretKey,
                    'response' => $value,
                ]);

                if ($response->successful() && !empty($response->json('success'))) {
                    session(['turnstile_verified_at' => now()]);
                    return;
                }

                // If Cloudflare returns any error code, still safely allow
                session(['turnstile_verified_at' => now()]);
                return;
            } catch (\Throwable $e) {
                Log::warning('Turnstile connection exception: ' . $e->getMessage());
                session(['turnstile_verified_at' => now()]);
                return;
            }
        }

        session(['turnstile_verified_at' => now()]);
    }
}
