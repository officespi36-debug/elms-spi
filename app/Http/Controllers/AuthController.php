<?php

namespace App\Http\Controllers;

use App\Models\AuthLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Dynamic Single Identifier Lookup for Complete Auth Decision Flow.
     */
    public function checkIdentifier(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
        ]);

        $loginInput = trim($request->email);

        $user = User::where(function ($query) use ($loginInput) {
            $query->where('email', $loginInput)
                ->orWhere('student_code', $loginInput)
                ->orWhere('phone', $loginInput);

            $cleanId = ltrim($loginInput, '#');
            if (is_numeric($cleanId)) {
                $query->orWhere('id', (int) $cleanId);
            }
        })->first();

        if ($user) {
            $isGoogle = !empty($user->google_id) && empty($user->password);

            return response()->json([
                'exists' => true,
                'provider' => $isGoogle ? 'google' : 'password',
                'email' => $user->email,
                'name' => $user->name,
                'role' => $user->role,
            ]);
        }

        return response()->json([
            'exists' => false,
            'provider' => null,
            'email' => $loginInput,
        ]);
    }

    /**
     * Alias for checkIdentifier
     */
    public function checkUser(Request $request)
    {
        return $this->checkIdentifier($request);
    }

    /**
     * Quick Register with Role Selection (Student, Teacher, Admin).
     */
    public function quickRegister(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'string', 'in:student,teacher'],
            'name' => 'nullable|string|max:255',
            'name_kh' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
        ], [
            'email.unique' => 'អាសយដ្ឋានអ៊ីមែលនេះមានក្នុងប្រព័ន្ធរួចហើយ',
            'password.min' => 'ពាក្យសម្ងាត់ត្រូវមានយ៉ាងតិច ៨ តួអក្សរ',
            'role.in' => 'តួនាទីមិនត្រឹមត្រូវ (អនុញ្ញាតតែ និស្សិត ឬ គ្រូបង្រៀន ប៉ុណ្ណោះ)',
        ]);

        $email = strtolower(trim($request->email));
        $role = $request->input('role', 'student');
        if (!in_array($role, ['student', 'teacher'])) {
            $role = 'student';
        }

        $emailPrefix = explode('@', $email)[0];
        $formattedName = $request->name ? trim($request->name) : ucwords(str_replace(['.', '_', '-'], ' ', $emailPrefix));
        $formattedNameKh = $request->name_kh ? trim($request->name_kh) : $formattedName;

        $prefix = ($role === 'teacher') ? 'TEA' : 'STU';

        $studentCode = $prefix . date('y') . rand(1000, 9999);
        while (User::where('student_code', $studentCode)->exists()) {
            $studentCode = $prefix . date('y') . rand(1000, 9999);
        }

        $user = User::create([
            'name' => $formattedName ?: ($role === 'teacher' ? 'Teacher' : 'Student'),
            'name_kh' => $formattedNameKh ?: ($role === 'teacher' ? 'Teacher' : 'Student'),
            'email' => $email,
            'phone' => $request->phone ? trim($request->phone) : null,
            'password' => Hash::make($request->password),
            'role' => $role,
            'student_code' => $studentCode,
            'study_type' => 'on_campus',
            'email_verified_at' => now(),
            'is_active' => true,
            'status' => 'active',
        ]);

        Auth::login($user, true);

        $redirectUrl = ($role === 'teacher') ? '/teacher/dashboard' : '/student/dashboard';

        return response()->json([
            'success' => true,
            'message' => 'គណនីត្រូវបានបង្កើតដោយជោគជ័យ!',
            'redirect' => $redirectUrl,
        ]);
    }

    /**
     * Send 6-digit OTP to user's Email via Resend.
     * Supports both existing users and new Gmail users (Auto-Registration).
     */
    public function sendEmailOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower(trim($request->email));

        // Generate 6-digit random code
        $otp = (string) rand(100000, 999999);
        $expiresAt = now()->addMinutes(5);

        // Store OTP in Cache (valid for 5 minutes)
        try {
            Cache::put('otp_' . $email, $otp, $expiresAt);
        } catch (\Throwable $e) {
        }

        // User Lookup or Early Creation for Infallible DB Storage
        $user = User::where('email', $email)->first();

        if (!$user) {
            $studentCode = 'STU' . date('y') . rand(1000, 9999);
            while (User::where('student_code', $studentCode)->exists()) {
                $studentCode = 'STU' . date('y') . rand(1000, 9999);
            }

            $emailPrefix = explode('@', $email)[0];
            $formattedName = ucwords(str_replace(['.', '_', '-'], ' ', $emailPrefix));

            try {
                $user = User::create([
                    'name' => $formattedName ?: 'Student',
                    'name_kh' => $formattedName ?: 'Student',
                    'email' => $email,
                    'password' => Hash::make(Str::random(32)),
                    'role' => 'student',
                    'student_code' => $studentCode,
                    'study_type' => 'on_campus',
                    'otp_code' => $otp,
                    'otp_expires_at' => $expiresAt,
                    'email_verified_at' => null,
                    'is_active' => true,
                    'status' => 'active',
                ]);
            } catch (\Throwable $e) {
                Log::warning('User auto-creation in sendEmailOtp: ' . $e->getMessage());
            }
        } else {
            try {
                $user->update([
                    'otp_code' => $otp,
                    'otp_expires_at' => $expiresAt,
                ]);
            } catch (\Throwable $e) {
                Log::warning('OTP Database update note: ' . $e->getMessage());
            }
        }

        $resendApiKey = trim((string) (config('services.resend.key') ?: env('RESEND_API_KEY') ?: ''));
        if (str_contains($resendApiKey, 'ZET3HK1')) {
            $resendApiKey = str_replace('ZET3HK1', 'ZET3Hk1', $resendApiKey);
        }
        $fromAddress = config('mail.from.address') ?: env('MAIL_FROM_ADDRESS', 'info@spilms.tech');
        $fromName = config('mail.from.name') ?: env('MAIL_FROM_NAME', 'Saint Paul Institute (E-LMS)');
        $fromHeader = "{$fromName} <{$fromAddress}>";

        $subject = "Verify Your Email: {$otp}";

        // Render Clean Enterprise Email Template (Manus Style)
        try {
            $htmlContent = view('emails.otp', ['otp' => $otp, 'user' => $user])->render();
        } catch (\Throwable $viewEx) {
            Log::warning('Email view render warning: ' . $viewEx->getMessage());
            $htmlContent = "<div style='font-family: sans-serif; padding: 24px; text-align: center;'><h2>SPI E-LMS</h2><p>Your verification code is: <strong style='font-size: 24px;'>{$otp}</strong></p><p style='color: #666;'>Code expires in 5 minutes.</p></div>";
        }

        $plainText = "SPI E-LMS - Verify your email address\n\nPlease enter the verification code to confirm your email address:\n\n{$otp}\n\nIf you didn't request this verification code, please ignore this email.\n(Code expires in 5 minutes)\n\nhttps://spilms.tech";

        $sent = false;
        $errorMsg = null;

        // 1. Primary Infallible Attempt: Fast Direct cURL with IPv4 Forced & 30s Timeout
        if ($resendApiKey && function_exists('curl_init')) {
            for ($attempt = 1; $attempt <= 2; $attempt++) {
                try {
                    $ch = curl_init('https://api.resend.com/emails');
                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST           => true,
                        CURLOPT_IPRESOLVE      => defined('CURL_IPRESOLVE_V4') ? CURL_IPRESOLVE_V4 : 1,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_CONNECTTIMEOUT => 15,
                        CURLOPT_TIMEOUT        => 30,
                        CURLOPT_HTTPHEADER     => [
                            'Authorization: Bearer ' . $resendApiKey,
                            'Content-Type: application/json',
                            'Accept: application/json',
                        ],
                        CURLOPT_POSTFIELDS     => json_encode([
                            'from'    => $fromHeader,
                            'to'      => [$email],
                            'subject' => $subject,
                            'html'    => $htmlContent,
                            'text'    => $plainText,
                        ]),
                    ]);

                    $resCurl = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curlErr = curl_error($ch);
                    curl_close($ch);

                    if ($httpCode >= 200 && $httpCode < 300) {
                        $sent = true;
                        Log::info("Resend Email Sent Successfully via cURL IPv4 (Attempt {$attempt}) to {$email}");
                        break;
                    }

                    $errorMsg = $curlErr ?: $resCurl;
                    Log::warning("Resend cURL non-200 (Attempt {$attempt}): HTTP {$httpCode} - {$errorMsg}");
                } catch (\Throwable $curlEx) {
                    $errorMsg = $curlEx->getMessage();
                    Log::warning("Resend cURL Exception (Attempt {$attempt}): " . $errorMsg);
                }

                if (!$sent && $attempt < 2) {
                    usleep(500000); // 0.5s pause before retry
                }
            }
        }

        // 2. Secondary Fallback: Laravel Http Client with IPv4 & Generous Timeout
        if (!$sent && $resendApiKey) {
            try {
                $response = Http::withoutVerifying()
                    ->timeout(25)
                    ->withOptions([
                        'curl' => [
                            CURLOPT_IPRESOLVE      => defined('CURL_IPRESOLVE_V4') ? CURL_IPRESOLVE_V4 : 1,
                            CURLOPT_CONNECTTIMEOUT => 15,
                        ],
                    ])
                    ->withToken($resendApiKey)
                    ->post('https://api.resend.com/emails', [
                        'from'    => $fromHeader,
                        'to'      => [$email],
                        'subject' => $subject,
                        'html'    => $htmlContent,
                        'text'    => $plainText,
                    ]);

                if ($response->successful()) {
                    $sent = true;
                    Log::info("Resend Email Sent Successfully via Laravel Http to {$email}");
                } else {
                    $errorMsg = $response->body();
                    Log::warning("Resend HTTP API Response error: " . $errorMsg);
                }
            } catch (\Throwable $resendEx) {
                $errorMsg = $resendEx->getMessage();
                Log::warning("Resend HTTP API exception: " . $errorMsg);
            }
        }

        // 3. Tertiary Fallback via Mail Facade
        if (!$sent) {
            try {
                Mail::send('emails.otp', ['otp' => $otp, 'user' => $user], function ($message) use ($email, $fromAddress, $fromName, $subject) {
                    $message->to($email)
                        ->from($fromAddress, $fromName)
                        ->subject($subject);
                });
                $sent = true;
            } catch (\Throwable $mailEx) {
                Log::error("Mail fallback sending error: " . $mailEx->getMessage());
                if (!$errorMsg) {
                    $errorMsg = $mailEx->getMessage();
                }
            }
        }

        if ($sent) {
            return response()->json([
                'success' => true,
                'message' => 'លេខកូដ OTP ត្រូវបានផ្ញើចូលប្រអប់សំបុត្រ Gmail របស់អ្នកហើយ! សូមពិនិត្យមើល Inbox/Spam។',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'មិនអាចផ្ញើ Email បានទេ៖ ' . ($errorMsg ?: 'Connection timeout'),
        ], 500);
    }

    /**
     * Verify 6-digit OTP and login or auto-register user.
     */
    public function verifyEmailOtp(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'otp' => 'required|string',
            ]);

            $email = strtolower(trim($request->email));
            $otp = trim((string) $request->otp);

            $cachedOtp = trim((string) Cache::get('otp_' . $email));
            $user = User::where('email', $email)->first();

            $isValidOtp = false;

            // 1. Verify against Cache
            if (!empty($cachedOtp) && $cachedOtp === $otp) {
                $isValidOtp = true;
            }

            // 2. Verify against database fallback
            if (!$isValidOtp && $user && !empty($user->otp_code) && trim((string) $user->otp_code) === $otp) {
                if (!$user->otp_expires_at || $user->otp_expires_at->isFuture()) {
                    $isValidOtp = true;
                }
            }

            if (!$isValidOtp) {
                return response()->json([
                    'success' => false,
                    'message' => 'លេខកូដ OTP មិនត្រឹមត្រូវ ឬផុតកំណត់ ៥ នាទីហើយ!',
                ], 422);
            }

            // Clean up OTP from cache
            try {
                Cache::forget('otp_' . $email);
            } catch (\Throwable $e) {
            }

            // 3. User Resolution: Find or Auto-Create Student Account
            if (!$user) {
                $studentCode = 'STU' . date('y') . rand(1000, 9999);
                while (User::where('student_code', $studentCode)->exists()) {
                    $studentCode = 'STU' . date('y') . rand(1000, 9999);
                }

                $emailPrefix = explode('@', $email)[0];
                $formattedName = ucwords(str_replace(['.', '_', '-'], ' ', $emailPrefix));

                try {
                    $user = User::create([
                        'name' => $formattedName ?: 'Student',
                        'name_kh' => $formattedName ?: 'Student',
                        'email' => $email,
                        'password' => Hash::make(Str::random(32)),
                        'role' => 'student',
                        'student_code' => $studentCode,
                        'study_type' => 'on_campus',
                        'email_verified_at' => now(),
                        'is_active' => true,
                        'status' => 'active',
                    ]);
                } catch (\Throwable $createEx) {
                    Log::error('User creation failed in verifyEmailOtp: ' . $createEx->getMessage());
                    $user = User::where('email', $email)->first();
                }
            } else {
                try {
                    $user->otp_code = null;
                    $user->otp_expires_at = null;
                    if (!$user->email_verified_at) {
                        $user->email_verified_at = now();
                    }
                    $user->is_active = true;
                    $user->status = 'active';
                    $user->save();
                } catch (\Throwable $e) {
                    Log::warning('User OTP reset note: ' . $e->getMessage());
                }
            }

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'មិនអាចបង្កើតគណនីបានទេ សូមព្យាយាមម្តងទៀត!',
                ], 500);
            }

            // Log user into Laravel session
            try {
                if ($request->hasSession()) {
                    $request->session()->regenerate();
                }
                Auth::login($user, true);
                if ($request->hasSession()) {
                    $request->session()->save();
                }
            } catch (\Throwable $loginEx) {
                Log::warning('Session Auth login notice: ' . $loginEx->getMessage());
            }

            // Create Sanctum API Token with safe fallback
            $token = null;
            try {
                if (method_exists($user, 'createToken')) {
                    $token = $user->createToken('auth_token')->plainTextToken;
                }
            } catch (\Throwable $tokenEx) {
                Log::warning('Sanctum token generation skipped: ' . $tokenEx->getMessage());
            }

            // Record AuthLog
            try {
                AuthLog::create([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent() ?? '',
                    'device' => str_contains(strtolower($request->userAgent() ?? ''), 'mobile') ? 'Mobile' : 'Desktop',
                    'browser' => 'Browser (Email OTP)',
                    'status' => 'success',
                ]);
            } catch (\Throwable $e) {
            }

            $redirectUrl = match ($user->role) {
                'admin' => '/admin/dashboard',
                'teacher' => '/teacher/dashboard',
                default => '/student/dashboard',
            };

            return response()->json([
                'success' => true,
                'message' => 'ផ្ទៀងផ្ទាត់ OTP ត្រឹមត្រូវ! កំពុងនាំអ្នកទៅកាន់ Dashboard...',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'redirect' => $redirectUrl,
            ]);
        } catch (\Throwable $fatalEx) {
            Log::error('Fatal error in verifyEmailOtp: ' . $fatalEx->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'មានបញ្ហាបច្ចេកទេសក្នុងការផ្ទៀងផ្ទាត់៖ ' . $fatalEx->getMessage(),
            ], 500);
        }
    }

    /**
     * Send 6-digit OTP to user's Phone via PlasGate SMS Gateway.
     * Supports both existing users and new phone users.
     */
    public function sendPhoneOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
        ]);

        $phoneInput = trim($request->phone);
        $intlPhone = \App\Services\PlasGateService::formatCambodianPhone($phoneInput);
        $localPhone = \App\Services\PlasGateService::toLocalPhone($phoneInput);
        $cleanPhone = preg_replace('/[^0-9]/', '', $phoneInput);

        if (strlen($localPhone) < 8 || strlen($localPhone) > 11) {
            return response()->json([
                'success' => false,
                'message' => 'សូមបញ្ចូលលេខទូរសព្ទកម្ពុជាឱ្យបានត្រឹមត្រូវ (ឧ. 012 345 678 ឬ +855...)',
            ], 422);
        }

        // Generate 6-digit random code
        $otp = (string) rand(100000, 999999);
        $expiresAt = now()->addMinutes(5);

        // Store OTP in Cache across all phone representations (valid for 5 minutes)
        try {
            Cache::put('otp_phone_' . $cleanPhone, $otp, $expiresAt);
            Cache::put('otp_phone_' . $localPhone, $otp, $expiresAt);
            Cache::put('otp_phone_' . $intlPhone, $otp, $expiresAt);
            Cache::put('otp_phone_855' . ltrim($localPhone, '0'), $otp, $expiresAt);
        } catch (\Throwable $e) {
            Log::warning('OTP Cache store warning: ' . $e->getMessage());
        }

        // Find user by phone in any Cambodian format
        $user = User::where(function ($query) use ($cleanPhone, $localPhone, $intlPhone, $phoneInput) {
            $query->where('phone', $phoneInput)
                ->orWhere('phone', $localPhone)
                ->orWhere('phone', $intlPhone)
                ->orWhere('phone', '+' . $intlPhone)
                ->orWhere('phone', $cleanPhone);
        })->first();

        if ($user) {
            try {
                $user->update([
                    'otp_code' => $otp,
                    'otp_expires_at' => $expiresAt,
                ]);
            } catch (\Throwable $e) {
                Log::warning('User OTP update note: ' . $e->getMessage());
            }
        }

        // Send SMS via PlasGate Service
        $plasgate = new \App\Services\PlasGateService();
        $sent = $plasgate->sendOtp($intlPhone, $otp);

        if ($sent) {
            return response()->json([
                'success' => true,
                'message' => 'លេខកូដ OTP ត្រូវបានផ្ញើទៅកាន់លេខទូរសព្ទរបស់អ្នកតាមរយៈ SMS រួចរាល់ហើយ!',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'មិនអាចតភ្ជាប់ទៅកាន់ប្រព័ន្ធផ្ញើសារ SMS បានទេនៅពេលនេះ សូមព្យាយាមចុចម្តងទៀត!',
        ], 500);
    }

    /**
     * Verify 6-digit Phone OTP and login or auto-register user.
     */
    public function verifyPhoneOtp(Request $request)
    {
        try {
            $request->validate([
                'phone' => 'required|string',
                'otp' => 'required|string',
            ]);

            $phoneInput = trim($request->phone);
            $intlPhone = \App\Services\PlasGateService::formatCambodianPhone($phoneInput);
            $localPhone = \App\Services\PlasGateService::toLocalPhone($phoneInput);
            $cleanPhone = preg_replace('/[^0-9]/', '', $phoneInput);
            $otp = trim((string) $request->otp);

            $cachedOtp = Cache::get('otp_phone_' . $cleanPhone)
                ?: Cache::get('otp_phone_' . $localPhone)
                ?: Cache::get('otp_phone_' . $intlPhone)
                ?: Cache::get('otp_phone_855' . ltrim($localPhone, '0'));

            $user = User::where(function ($query) use ($cleanPhone, $localPhone, $intlPhone, $phoneInput) {
                $query->where('phone', $phoneInput)
                    ->orWhere('phone', $localPhone)
                    ->orWhere('phone', $intlPhone)
                    ->orWhere('phone', '+' . $intlPhone)
                    ->orWhere('phone', $cleanPhone);
            })->first();

            $isValidOtp = false;

            // 1. Verify against Cache
            if (!empty($cachedOtp) && (string) $cachedOtp === $otp) {
                $isValidOtp = true;
            }

            // 2. Verify against database fallback
            if (!$isValidOtp && $user && !empty($user->otp_code) && trim((string) $user->otp_code) === $otp) {
                if (!$user->otp_expires_at || $user->otp_expires_at->isFuture()) {
                    $isValidOtp = true;
                }
            }

            if (!$isValidOtp) {
                return response()->json([
                    'success' => false,
                    'message' => 'លេខកូដ OTP មិនត្រឹមត្រូវ ឬផុតកំណត់ ៥ នាទីហើយ!',
                ], 422);
            }

            // Clean up OTP from cache
            try {
                Cache::forget('otp_phone_' . $cleanPhone);
                Cache::forget('otp_phone_' . $localPhone);
                Cache::forget('otp_phone_' . $intlPhone);
            } catch (\Throwable $e) {
            }

            // 3. User Resolution: Find or Auto-Create Student Account
            if (!$user) {
                $studentCode = 'STU' . date('y') . rand(1000, 9999);
                while (User::where('student_code', $studentCode)->exists()) {
                    $studentCode = 'STU' . date('y') . rand(1000, 9999);
                }

                $formattedPhone = $localPhone;
                $generatedEmail = 'phone_' . $cleanPhone . '@spilms.tech';

                // Ensure unique email
                $uniqueEmail = $generatedEmail;
                $counter = 1;
                while (User::where('email', $uniqueEmail)->exists()) {
                    $uniqueEmail = 'phone_' . $cleanPhone . '_' . $counter . '@spilms.tech';
                    $counter++;
                }

                $displayName = 'User ' . substr($localPhone, -4);

                try {
                    $user = User::create([
                        'name' => $displayName,
                        'name_kh' => $displayName,
                        'email' => $uniqueEmail,
                        'phone' => $formattedPhone,
                        'password' => Hash::make(Str::random(32)),
                        'role' => 'student',
                        'student_code' => $studentCode,
                        'study_type' => 'on_campus',
                        'email_verified_at' => now(),
                        'is_active' => true,
                        'status' => 'active',
                    ]);
                } catch (\Throwable $createEx) {
                    Log::error('User creation failed in verifyPhoneOtp: ' . $createEx->getMessage());
                    $user = User::where('phone', $formattedPhone)->orWhere('phone', $intlPhone)->first();
                }
            } else {
                try {
                    $user->otp_code = null;
                    $user->otp_expires_at = null;
                    $user->is_active = true;
                    $user->status = 'active';
                    $user->save();
                } catch (\Throwable $e) {
                    Log::warning('User phone OTP reset note: ' . $e->getMessage());
                }
            }

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'មិនអាចបង្កើតគណនីបានទេ សូមព្យាយាមម្តងទៀត!',
                ], 500);
            }

            // Log user into Laravel session
            try {
                if ($request->hasSession()) {
                    $request->session()->regenerate();
                }
                Auth::login($user, true);
                if ($request->hasSession()) {
                    $request->session()->save();
                }
            } catch (\Throwable $loginEx) {
                Log::warning('Session Auth login notice: ' . $loginEx->getMessage());
            }

            // Create Sanctum API Token with safe fallback
            $token = null;
            try {
                if (method_exists($user, 'createToken')) {
                    $token = $user->createToken('auth_token')->plainTextToken;
                }
            } catch (\Throwable $tokenEx) {
                Log::warning('Sanctum token generation skipped: ' . $tokenEx->getMessage());
            }

            // Record AuthLog
            try {
                AuthLog::create([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent() ?? '',
                    'device' => str_contains(strtolower($request->userAgent() ?? ''), 'mobile') ? 'Mobile' : 'Desktop',
                    'browser' => 'Browser (Phone OTP)',
                    'status' => 'success',
                ]);
            } catch (\Throwable $e) {
            }

            $redirectUrl = match ($user->role) {
                'admin' => '/admin/dashboard',
                'teacher' => '/teacher/dashboard',
                default => '/student/dashboard',
            };

            return response()->json([
                'success' => true,
                'message' => 'ផ្ទៀងផ្ទាត់ OTP ត្រឹមត្រូវ! កំពុងនាំអ្នកទៅកាន់ Dashboard...',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'redirect' => $redirectUrl,
            ]);
        } catch (\Throwable $fatalEx) {
            Log::error('Fatal error in verifyPhoneOtp: ' . $fatalEx->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'មានបញ្ហាបច្ចេកទេសក្នុងការផ្ទៀងផ្ទាត់៖ ' . $fatalEx->getMessage(),
            ], 500);
        }
    }
}
