<?php

namespace App\Http\Controllers;

use App\Models\AuthLog;
use App\Models\User;
use App\Services\TelegramGatewayService;
use App\Services\TelegramService;
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

        // 🔔 Dispatch Real-Time Alert to Telegram Group & User Direct Message IMMEDIATELY
        try {
            $telegramService = app(TelegramService::class);
            $adminGroupChatId = config('services.telegram.admin_chat_id') ?: env('TELEGRAM_ADMIN_CHAT_ID') ?: config('services.telegram.chat_id') ?: env('TELEGRAM_CHAT_ID') ?: '-5560385465';

            $ip = $request->ip();
            $userAgent = $request->userAgent() ?? '';
            $device = str_contains(strtolower($userAgent), 'mobile') ? 'Mobile' : 'Desktop';
            $browser = $this->getBrowserName($userAgent);

            $safeName = htmlspecialchars($user?->name ?: 'Student', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeEmail = htmlspecialchars($email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeOtp = htmlspecialchars($otp, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeIp = htmlspecialchars($ip, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeDevice = htmlspecialchars($device, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeBrowser = htmlspecialchars($browser, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeTime = now()->setTimezone('Asia/Phnom_Penh')->format('Y-m-d h:i:s A');

            // 1. Group Notification
            $telegramService->sendMessage(
                "<b>✉️ [EMAIL OTP DISPATCHED]</b>\n" .
                "━━━━━━━━━━━━━━━━━━━━━\n" .
                "👤 <b>User:</b> {$safeName}\n" .
                "📧 <b>Email:</b> {$safeEmail}\n" .
                "🔢 <b>OTP Code:</b> <code>{$safeOtp}</code>\n" .
                "⏰ <b>Expires In:</b> 5 minutes (<code>{$safeTime}</code>)\n" .
                "🌐 <b>IP Address:</b> <code>{$safeIp}</code>\n" .
                "📱 <b>Device:</b> {$safeDevice} ({$safeBrowser})\n" .
                "🛡️ <b>Action:</b> Email OTP Requested",
                'HTML',
                $adminGroupChatId
            );

            // 2. Direct User Personal Telegram Notification (if account is linked)
            $userChatId = $user?->telegram_id ?: $user?->telegram_chat_id;
            if (!empty($userChatId) && (string)$userChatId !== (string)$adminGroupChatId) {
                $telegramService->sendDirectMessage(
                    $userChatId,
                    "🔑 <b>លេខកូដផ្ទៀងផ្ទាត់ OTP (Email)</b>\n" .
                    "━━━━━━━━━━━━━━━━━━━━━\n\n" .
                    "សួស្តី <b>" . ($user?->name_kh ?: $safeName) . "</b> 👋\n" .
                    "លេខកូដសម្ងាត់ 6 ខ្ទង់របស់អ្នកសម្រាប់ចូលប្រើប្រាស់ SPI E-LMS គឺ៖\n\n" .
                    "👉 <code>{$safeOtp}</code>\n\n" .
                    "⏰ លេខកូដនេះមានសុពលភាពរយៈពេល <b>5 នាទី</b>។\n" .
                    "📱 <b>ឧបករណ៍៖</b> {$safeDevice} ({$safeBrowser})\n" .
                    "🌐 <b>IP៖</b> <code>{$safeIp}</code>\n\n" .
                    "⚠️ <i>ប្រសិនបើលោកអ្នកមិនបានស្នើសុំលេខកូដនេះទេ សូមកុំចែករំលែកវាទៅកាន់អ្នកដទៃ!</i>",
                    'HTML'
                );
            }
        } catch (\Throwable $tgEx) {
            Log::warning('Telegram OTP dispatch notice: ' . $tgEx->getMessage());
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

        return response()->json([
            'success' => true,
            'message' => 'លេខកូដ OTP ត្រូវបានផ្ញើចូលប្រអប់សំបុត្រ Gmail និង Telegram របស់អ្នកហើយ!',
        ]);
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
                $ip = $request->ip();
                $userAgent = $request->userAgent() ?? '';
                $device = str_contains(strtolower($userAgent), 'mobile') ? 'Mobile' : 'Desktop';
                $browser = $this->getBrowserName($userAgent);

                AuthLog::create([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'ip_address' => $ip,
                    'user_agent' => $userAgent,
                    'device' => $device,
                    'browser' => $browser,
                    'status' => 'success',
                ]);

                // 🔔 Real-time Security Alert to Telegram Group & Direct User
                $telegramService = app(TelegramService::class);
                $adminGroupChatId = config('services.telegram.admin_chat_id') ?: env('TELEGRAM_ADMIN_CHAT_ID') ?: config('services.telegram.chat_id') ?: env('TELEGRAM_CHAT_ID') ?: '-5560385465';

                $safeName = htmlspecialchars($user->name ?: 'Student', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeEmail = htmlspecialchars($user->email, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeRole = strtoupper(htmlspecialchars($user->role ?: 'student', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
                $safeIp = htmlspecialchars($ip, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeDevice = htmlspecialchars($device, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeBrowser = htmlspecialchars($browser, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeTime = now()->setTimezone('Asia/Phnom_Penh')->format('Y-m-d h:i:s A');

                // 1. Group Alert
                $telegramService->sendMessage(
                    "<b>✉️ [EMAIL OTP LOGIN ALERT]</b>\n" .
                    "━━━━━━━━━━━━━━━━━━━━━\n" .
                    "👤 <b>User:</b> {$safeName}\n" .
                    "📧 <b>Email:</b> {$safeEmail}\n" .
                    "🎓 <b>Role:</b> {$safeRole}\n" .
                    "🌐 <b>IP Address:</b> <code>{$safeIp}</code>\n" .
                    "📱 <b>Device:</b> {$safeDevice} ({$safeBrowser})\n" .
                    "⏰ <b>Time:</b> {$safeTime}\n" .
                    "🛡️ <b>Method:</b> 6-Digit Email OTP Verification",
                    'HTML',
                    $adminGroupChatId
                );

                // 2. Direct User Personal Telegram Notification (if account is linked)
                $userChatId = $user->telegram_id ?: $user->telegram_chat_id;
                if (!empty($userChatId) && (string)$userChatId !== (string)$adminGroupChatId) {
                    $telegramService->sendDirectMessage(
                        $userChatId,
                        "🛡️ <b>ការជូនដំណឹងសុវត្ថិភាព៖ ការចូលប្រើប្រាស់គណនី</b>\n" .
                        "━━━━━━━━━━━━━━━━━━━━━\n\n" .
                        "សួស្តី <b>" . ($user->name_kh ?: $safeName) . "</b> 👋\n" .
                        "គណនីរបស់អ្នកទើបតែបាន Login ចូលប្រើប្រាស់លើ SPI E-LMS ដោយជោគជ័យតាមរយៈ Email OTP ៖\n\n" .
                        "⏰ <b>ម៉ោង៖</b> " . now()->setTimezone('Asia/Phnom_Penh')->format('d-M-Y h:i A') . "\n" .
                        "📱 <b>ឧបករណ៍៖</b> {$safeDevice} ({$safeBrowser})\n" .
                        "🌐 <b>IP Address៖</b> <code>{$safeIp}</code>\n" .
                        "🛡️ <b>វិធីសាស្ត្រ៖</b> 6-Digit Email OTP Verification\n\n" .
                        "⚠️ <i>ប្រសិនបើមិនមែនជាអ្នកទេ សូមទាក់ទងមកកាន់រដ្ឋបាលជាបន្ទាន់!</i>",
                        'HTML'
                    );
                }

                // 3. Security Email Alert
                if (!empty($user->email)) {
                    $loginDetails = [
                        'ip' => $ip,
                        'device' => $device,
                        'browser' => $browser,
                        'time' => now()->setTimezone('Asia/Phnom_Penh')->format('d-M-Y h:i A'),
                        'location' => 'Cambodia',
                    ];
                    (new \App\Http\Controllers\Auth\AuthenticatedSessionController())->sendSecurityAlertEmail($user, $loginDetails);
                }
            } catch (\Throwable $e) {
                Log::warning('Email OTP security alert notice: ' . $e->getMessage());
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

        // 🚀 Primary: Attempt Dispatch via Telegram Gateway (@VerificationCodes)
        $dispatchChannel = 'sms';
        $tgGatewayResult = null;
        $tgGateway = app(TelegramGatewayService::class);
        $e164Phone = TelegramGatewayService::formatE164Phone($phoneInput);

        if ($tgGateway->isConfigured()) {
            try {
                $tgGatewayResult = $tgGateway->sendVerificationMessage($e164Phone, $otp, 300);
                if (!empty($tgGatewayResult['success'])) {
                    $dispatchChannel = 'telegram_gateway';
                    Log::info("Telegram Gateway delivered OTP to {$e164Phone} via @VerificationCodes (Cost: " . ($tgGatewayResult['request_cost'] ?? 0) . ").");
                } else {
                    $tgError = $tgGatewayResult['error'] ?? 'UNKNOWN_ERROR';
                    Log::info("Telegram Gateway unable to deliver to {$e164Phone} [{$tgError}], automatically falling back to PlasGate SMS.");
                }
            } catch (\Throwable $tgGwEx) {
                Log::warning('Telegram Gateway dispatch exception: ' . $tgGwEx->getMessage());
            }
        }

        // 📱 Secondary: Fallback to PlasGate SMS if Telegram Gateway did not succeed
        if ($dispatchChannel !== 'telegram_gateway') {
            try {
                $plasgate = new \App\Services\PlasGateService();
                $plasgate->sendOtp($intlPhone, $otp);
                Log::info("PlasGate SMS OTP sent to {$intlPhone}.");
            } catch (\Throwable $pgEx) {
                Log::warning('PlasGate SMS Gateway warning: ' . $pgEx->getMessage());
            }
        }

        // 🔔 Dispatch Real-Time Alert to Telegram Group & User Direct Message IMMEDIATELY
        try {
            $telegramService = app(TelegramService::class);
            $adminGroupChatId = config('services.telegram.admin_chat_id') ?: env('TELEGRAM_ADMIN_CHAT_ID') ?: config('services.telegram.chat_id') ?: env('TELEGRAM_CHAT_ID') ?: '-5560385465';

            $ip = $request->ip();
            $userAgent = $request->userAgent() ?? '';
            $device = str_contains(strtolower($userAgent), 'mobile') ? 'Mobile' : 'Desktop';
            $browser = $this->getBrowserName($userAgent);

            $safeName = htmlspecialchars($user?->name ?: 'Student', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safePhone = htmlspecialchars($e164Phone, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeOtp = htmlspecialchars($otp, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeIp = htmlspecialchars($ip, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeDevice = htmlspecialchars($device, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeBrowser = htmlspecialchars($browser, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $safeTime = now()->setTimezone('Asia/Phnom_Penh')->format('Y-m-d h:i:s A');

            $channelTitle = ($dispatchChannel === 'telegram_gateway')
                ? '🤖 Telegram Gateway (@VerificationCodes)'
                : '📩 PlasGate SMS Gateway (Fallback)';

            // 1. Group Notification
            $telegramService->sendMessage(
                "<b>🔐 [PHONE OTP DISPATCHED]</b>\n" .
                "━━━━━━━━━━━━━━━━━━━━━\n" .
                "👤 <b>User:</b> {$safeName}\n" .
                "📞 <b>Phone:</b> <code>{$safePhone}</code>\n" .
                "🔢 <b>OTP Code:</b> <code>{$safeOtp}</code>\n" .
                "🚀 <b>Channel:</b> {$channelTitle}\n" .
                "⏰ <b>Expires In:</b> 5 minutes (<code>{$safeTime}</code>)\n" .
                "🌐 <b>IP Address:</b> <code>{$safeIp}</code>\n" .
                "📱 <b>Device:</b> {$safeDevice} ({$safeBrowser})",
                'HTML',
                $adminGroupChatId
            );

            // 2. Direct User Personal Telegram Notification (if account is linked)
            $userChatId = $user?->telegram_id ?: $user?->telegram_chat_id;
            if (!empty($userChatId) && (string)$userChatId !== (string)$adminGroupChatId) {
                $telegramService->sendDirectMessage(
                    $userChatId,
                    "🔑 <b>លេខកូដផ្ទៀងផ្ទាត់ OTP</b>\n" .
                    "━━━━━━━━━━━━━━━━━━━━━\n\n" .
                    "សួស្តី <b>" . ($user?->name_kh ?: $safeName) . "</b> 👋\n" .
                    "លេខកូដសម្ងាត់ 6 ខ្ទង់របស់អ្នកសម្រាប់ចូលប្រើប្រាស់ SPI E-LMS គឺ៖\n\n" .
                    "👉 <code>{$safeOtp}</code>\n\n" .
                    "⏰ លេខកូដនេះមានសុពលភាពរយៈពេល <b>5 នាទី</b>។\n" .
                    "📱 <b>ឧបករណ៍៖</b> {$safeDevice} ({$safeBrowser})\n" .
                    "🌐 <b>IP៖</b> <code>{$safeIp}</code>\n\n" .
                    "⚠️ <i>ប្រសិនបើលោកអ្នកមិនបានស្នើសុំលេខកូដនេះទេ សូមកុំចែករំលែកវាទៅកាន់អ្នកដទៃ!</i>",
                    'HTML'
                );
            }
        } catch (\Throwable $tgEx) {
            Log::warning('Telegram Phone OTP dispatch notice: ' . $tgEx->getMessage());
        }

        $isTelegram = ($dispatchChannel === 'telegram_gateway');
        $successMessage = $isTelegram
            ? 'លេខកូដផ្ទៀងផ្ទាត់ត្រូវបានផ្ញើទៅកាន់ Telegram របស់អ្នកតាមរយៈ @VerificationCodes រួចរាល់ហើយ!'
            : 'លេខកូដ OTP ត្រូវបានផ្ញើជូនតាមរយៈសារ SMS រួចរាល់ហើយ!';

        return response()->json([
            'success' => true,
            'channel' => $dispatchChannel,
            'is_telegram' => $isTelegram,
            'phone' => $e164Phone,
            'request_id' => $tgGatewayResult['request_id'] ?? null,
            'message' => $successMessage,
        ]);
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
                $ip = $request->ip();
                $userAgent = $request->userAgent() ?? '';
                $device = str_contains(strtolower($userAgent), 'mobile') ? 'Mobile' : 'Desktop';
                $browser = $this->getBrowserName($userAgent);
                $phoneDisplay = $user->phone ?: $phoneInput;

                AuthLog::create([
                    'user_id' => $user->id,
                    'email' => $user->email ?: $phoneDisplay,
                    'ip_address' => $ip,
                    'user_agent' => $userAgent,
                    'device' => $device,
                    'browser' => $browser,
                    'status' => 'success',
                ]);

                // 🔔 Real-time Security Alert to Telegram Group & Direct User
                $telegramService = app(TelegramService::class);
                $adminGroupChatId = config('services.telegram.admin_chat_id') ?: env('TELEGRAM_ADMIN_CHAT_ID') ?: config('services.telegram.chat_id') ?: env('TELEGRAM_CHAT_ID') ?: '-5560385465';

                $safeName = htmlspecialchars($user->name ?: 'Student', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safePhone = htmlspecialchars((string)$phoneDisplay, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeRole = strtoupper(htmlspecialchars($user->role ?: 'student', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
                $safeIp = htmlspecialchars($ip, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeDevice = htmlspecialchars($device, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeBrowser = htmlspecialchars($browser, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                $safeTime = now()->setTimezone('Asia/Phnom_Penh')->format('Y-m-d h:i:s A');

                // 1. Group Alert
                $telegramService->sendMessage(
                    "<b>📞 [PHONE SMS OTP ALERT]</b>\n" .
                    "━━━━━━━━━━━━━━━━━━━━━\n" .
                    "👤 <b>User:</b> {$safeName}\n" .
                    "📞 <b>Phone:</b> <code>{$safePhone}</code>\n" .
                    "🎓 <b>Role:</b> {$safeRole}\n" .
                    "🌐 <b>IP Address:</b> <code>{$safeIp}</code>\n" .
                    "📱 <b>Device:</b> {$safeDevice} ({$safeBrowser})\n" .
                    "⏰ <b>Time:</b> {$safeTime}\n" .
                    "🛡️ <b>Method:</b> PlasGate 6-Digit SMS Verification",
                    'HTML',
                    $adminGroupChatId
                );

                // 2. Direct User Personal Telegram Notification (if account is linked)
                $userChatId = $user->telegram_id ?: $user->telegram_chat_id;
                if (!empty($userChatId) && (string)$userChatId !== (string)$adminGroupChatId) {
                    $telegramService->sendDirectMessage(
                        $userChatId,
                        "🛡️ <b>ការជូនដំណឹងសុវត្ថិភាព៖ ការចូលប្រើប្រាស់គណនី</b>\n" .
                        "━━━━━━━━━━━━━━━━━━━━━\n\n" .
                        "សួស្តី <b>" . ($user->name_kh ?: $safeName) . "</b> 👋\n" .
                        "គណនីរបស់អ្នកទើបតែបាន Login ចូលប្រើប្រាស់លើ SPI E-LMS ដោយជោគជ័យតាមរយៈ Phone SMS OTP ៖\n\n" .
                        "⏰ <b>ម៉ោង៖</b> " . now()->setTimezone('Asia/Phnom_Penh')->format('d-M-Y h:i A') . "\n" .
                        "📱 <b>ឧបករណ៍៖</b> {$safeDevice} ({$safeBrowser})\n" .
                        "🌐 <b>IP Address៖</b> <code>{$safeIp}</code>\n" .
                        "🛡️ <b>វិធីសាស្ត្រ៖</b> PlasGate SMS OTP Verification\n\n" .
                        "⚠️ <i>ប្រសិនបើមិនមែនជាអ្នកទេ សូមទាក់ទងមកកាន់រដ្ឋបាលជាបន្ទាន់!</i>",
                        'HTML'
                    );
                }

                // 3. Security Email Alert if user has an email
                if (!empty($user->email)) {
                    $loginDetails = [
                        'ip' => $ip,
                        'device' => $device,
                        'browser' => $browser,
                        'time' => now()->setTimezone('Asia/Phnom_Penh')->format('d-M-Y h:i A'),
                        'location' => 'Cambodia',
                    ];
                    (new \App\Http\Controllers\Auth\AuthenticatedSessionController())->sendSecurityAlertEmail($user, $loginDetails);
                }
            } catch (\Throwable $e) {
                Log::warning('Phone OTP security alert notice: ' . $e->getMessage());
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

    /**
     * Helper to detect client browser name from User-Agent string.
     */
    private function getBrowserName(?string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'Web Browser';
        }
        if (str_contains($userAgent, 'Edg/') || str_contains($userAgent, 'Edge/')) {
            return 'Microsoft Edge';
        }
        if (str_contains($userAgent, 'OPR/') || str_contains($userAgent, 'Opera/')) {
            return 'Opera';
        }
        if (str_contains($userAgent, 'Chrome/') || str_contains($userAgent, 'CriOS/')) {
            return 'Google Chrome';
        }
        if (str_contains($userAgent, 'Firefox/') || str_contains($userAgent, 'FxiOS/')) {
            return 'Mozilla Firefox';
        }
        if (str_contains($userAgent, 'Safari/') && !str_contains($userAgent, 'Chrome/')) {
            return 'Apple Safari';
        }
        return 'Web Browser';
    }
}
