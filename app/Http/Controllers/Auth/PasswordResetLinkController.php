<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create()
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    /**
     * Handle an incoming password reset link or verification code request.
     */
    public function store(Request $request, TelegramService $telegramService)
    {
        $input = trim($request->input('email') ?? $request->input('identifier') ?? '');

        if (empty($input)) {
            $msg = 'សូមបញ្ចូលអាសយដ្ឋានអ៊ីមែល, ID, ឬលេខទូរស័ព្ទ។';
            if ($request->is('api/*') || (!$request->header('X-Inertia') && $request->wantsJson())) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->withErrors(['email' => $msg]);
        }

        $cleanDigits = preg_replace('/[^0-9]/', '', $input);

        $user = User::where(function ($query) use ($input, $cleanDigits) {
            $cleanUser = ltrim($input, '@');
            $cleanId = ltrim($input, '#');

            $query->where('email', $input)
                ->orWhere('student_code', $input)
                ->orWhere('phone', $input)
                ->orWhere('telegram_username', $cleanUser)
                ->orWhere('telegram_id', $input)
                ->orWhere('telegram_chat_id', $input);

            if (!empty($cleanDigits) && strlen($cleanDigits) >= 6) {
                $last7 = substr($cleanDigits, -7);
                $last8 = substr($cleanDigits, -8);
                $query->orWhere('phone', $cleanDigits)
                    ->orWhere('phone', '0' . ltrim($cleanDigits, '0'))
                    ->orWhere('phone', 'like', '%' . $last7)
                    ->orWhere('phone', 'like', '%' . $last8);
            }

            if (is_numeric($cleanId)) {
                $query->orWhere('id', (int) $cleanId);
            }
        })->first();

        if (!$user) {
            $msg = 'រកមិនឃើញគណនីដែលប្រើព័ត៌មាននេះទេ!';
            if ($request->is('api/*') || (!$request->header('X-Inertia') && $request->wantsJson())) {
                return response()->json(['success' => false, 'message' => $msg], 404);
            }
            return back()->withErrors(['email' => $msg]);
        }

        // Generate 6-digit verification code
        $code = (string) rand(100000, 999999);
        $expiresAt = now()->addMinutes(5);

        // Store code in database and session for validation (valid for 5 minutes)
        try {
            $user->update([
                'otp_code' => $code,
                'otp_expires_at' => $expiresAt,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('OTP database save note: ' . $e->getMessage());
        }

        session([
            'reset_code'       => $code,
            'reset_user_id'    => $user->id,
            'reset_user_email' => $user->email,
            'reset_expires_at' => $expiresAt->timestamp,
        ]);

        $channel = strtolower(trim($request->input('channel') ?? 'telegram'));
        if (!in_array($channel, ['telegram', 'email'])) {
            $channel = 'telegram';
        }

        $telegramTargetId = $user->telegram_chat_id ?? $user->telegram_id ?? null;
        $hasTelegram = !empty($telegramTargetId);
        $botUsername = $telegramService->getBotUsername();
        $linkTelegramUrl = "https://t.me/{$botUsername}?start={$user->id}";

        $sentDirectly = false;
        $sentEmail = false;
        $sentSms = false;

        $plasgate = new \App\Services\PlasGateService();
        $hasPhone = !empty($user->phone);

        if ($channel === 'email') {
            $sentEmail = $this->sendOtpEmail($user, $code);
            $statusMsg = $sentEmail
                ? "លេខកូដ OTP 6 ខ្ទង់ ត្រូវបានផ្ញើទៅកាន់ Email របស់អ្នក ({$user->email}) រួចរាល់ហើយ!"
                : "លេខកូដ OTP ត្រូវបានបង្កើតរួចរាល់ហើយ ប៉ុន្តែមានបញ្ហាក្នុងការផ្ញើ Email។ សូមពិនិត្យមើលម្ដងទៀត ឬផ្ញើតាម Telegram។";
        } else {
            // Channel: Telegram
            if ($hasTelegram) {
                $sentDirectly = $telegramService->sendPasswordResetOtp($user, $code);
            }

            // Also backup to Email as redundancy if user has email
            if (!empty($user->email)) {
                $sentEmail = $this->sendOtpEmail($user, $code);
            }

            // Also deliver via PlasGate SMS if user has phone
            if ($hasPhone && $plasgate->isConfigured()) {
                try {
                    $sentSms = $plasgate->sendOtp($user->phone, $code);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('PlasGate Password Reset SMS note: ' . $e->getMessage());
                }
            }

            if ($sentDirectly) {
                $statusMsg = "លេខកូដ OTP 6 ខ្ទង់ ត្រូវបានផ្ញើទៅកាន់ Telegram Bot (@{$botUsername}) របស់អ្នករួចរាល់ហើយ!";
            } else {
                $statusMsg = "សូមចុចប៊ូតុង «បើក Telegram» ខាងក្រោម រួចចុច START ដើម្បីទទួលលេខកូដ OTP ៦ ខ្ទង់!";
            }
        }

        // Also broadcast to admin monitoring channel
        $deliveryNotes = [];
        if ($sentDirectly) $deliveryNotes[] = "✅ Direct Telegram ({$telegramTargetId})";
        if ($sentEmail)    $deliveryNotes[] = "✅ Email ({$user->email})";
        if ($sentSms)      $deliveryNotes[] = "✅ SMS ({$user->phone})";
        if (empty($deliveryNotes)) {
            $deliveryNotes[] = "⏳ Pending user clicking /start in Telegram";
        }

        $telegramService->sendMessage(
            "<b>🔑 PASSWORD RESET REQUEST</b>\n" .
            "----------------------------------------\n" .
            "👤 <b>User:</b> {$user->name} ({$user->email})\n" .
            "📱 <b>Channel:</b> " . strtoupper($channel) . "\n" .
            "🔢 <b>Verification Code (OTP):</b> <code>{$code}</code>\n" .
            "✈️ <b>Delivery:</b> " . implode(', ', $deliveryNotes) . "\n" .
            "⏰ <b>Requested At:</b> " . now()->format('Y-m-d H:i:s') . "\n"
        );

        $payload = [
            'success'           => true,
            'message'           => $statusMsg,
            'status'            => $statusMsg,
            'channel'           => $channel,
            'sent_to_email'     => $sentEmail,
            'sent_to_telegram'  => $sentDirectly,
            'sent_to_sms'       => $sentSms,
            'has_telegram'      => $hasTelegram,
            'link_telegram_url' => $linkTelegramUrl,
            'telegram_url'      => $linkTelegramUrl,
            'telegram_bot_name' => $botUsername,
            'user'              => [
                'id'           => $user->id,
                'name'         => $user->name,
                'email'        => $user->email,
                'phone'        => $user->phone,
                'student_code' => $user->student_code,
            ],
            'reset_user'        => [
                'id'           => $user->id,
                'name'         => $user->name,
                'email'        => $user->email,
                'phone'        => $user->phone,
                'student_code' => $user->student_code,
                'telegram_id'  => $user->telegram_id,
            ],
        ];

        if ($request->is('api/*') || (!$request->header('X-Inertia') && $request->wantsJson())) {
            return response()->json($payload);
        }

        return back()->with($payload);
    }

    /**
     * Send OTP Verification code to user's email via Resend API / Mail
     */
    protected function sendOtpEmail(User $user, string $code): bool
    {
        $email = $user->email;
        if (empty($email)) {
            return false;
        }

        $resendApiKey = config('services.resend.key') ?? env('RESEND_API_KEY');
        $fromAddress = config('mail.from.address') ?? env('MAIL_FROM_ADDRESS', 'info@spilms.tech');
        $fromName = config('mail.from.name') ?? env('MAIL_FROM_NAME', 'Saint Paul Institute (E-LMS)');
        $fromHeader = "{$fromName} <{$fromAddress}>";
        $subject = "{$code} is your SPI AI-ELMS Password Reset Code";

        $htmlContent = view('emails.otp', ['otp' => $code, 'user' => $user])->render();
        $plainText = "Your SPI AI-ELMS Password Reset code is: {$code}. It will expire in 5 minutes.";

        $sent = false;

        // 1. Primary: Resend cURL API with IPv4
        if ($resendApiKey) {
            for ($attempt = 1; $attempt <= 2; $attempt++) {
                try {
                    $ch = curl_init('https://api.resend.com/emails');
                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST           => true,
                        CURLOPT_IPRESOLVE      => defined('CURL_IPRESOLVE_V4') ? CURL_IPRESOLVE_V4 : 1,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_CONNECTTIMEOUT => 10,
                        CURLOPT_TIMEOUT        => 25,
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
                    curl_close($ch);

                    if ($httpCode >= 200 && $httpCode < 300) {
                        $sent = true;
                        \Illuminate\Support\Facades\Log::info("Password reset OTP email sent successfully via Resend to {$email}");
                        break;
                    }
                } catch (\Throwable $curlEx) {
                    \Illuminate\Support\Facades\Log::warning("Password reset Resend cURL exception: " . $curlEx->getMessage());
                }

                if (!$sent && $attempt < 2) {
                    usleep(300000);
                }
            }
        }

        // 2. Secondary Fallback: Laravel Mail Facade
        if (!$sent) {
            try {
                \Illuminate\Support\Facades\Mail::send('emails.otp', ['otp' => $code, 'user' => $user], function ($message) use ($email, $fromAddress, $fromName, $subject) {
                    $message->to($email)
                        ->from($fromAddress, $fromName)
                        ->subject($subject);
                });
                $sent = true;
                \Illuminate\Support\Facades\Log::info("Password reset OTP email sent via Mail facade to {$email}");
            } catch (\Throwable $mailEx) {
                \Illuminate\Support\Facades\Log::warning("Password reset Mail fallback error: " . $mailEx->getMessage());
            }
        }

        return $sent;
    }

    /**
     * Verify the entered 6-digit OTP code before displaying new password form.
     */
    public function verifyOtp(Request $request)
    {
        $input = trim($request->input('email') ?? $request->input('identifier') ?? '');
        $code = trim($request->input('code') ?? $request->input('otpCode') ?? '');

        if (empty($input) || empty($code)) {
            $msg = 'សូមបញ្ចូលព័ត៌មានគណនី និងលេខកូដ OTP ៦ ខ្ទង់។';
            if ($request->is('api/*') || (!$request->header('X-Inertia') && $request->wantsJson())) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->withErrors(['code' => $msg]);
        }

        $cleanDigits = preg_replace('/[^0-9]/', '', $input);

        $user = User::where(function ($query) use ($input, $cleanDigits) {
            $cleanUser = ltrim($input, '@');
            $cleanId = ltrim($input, '#');

            $query->where('email', $input)
                ->orWhere('student_code', $input)
                ->orWhere('phone', $input)
                ->orWhere('telegram_username', $cleanUser)
                ->orWhere('telegram_id', $input)
                ->orWhere('telegram_chat_id', $input);

            if (!empty($cleanDigits) && strlen($cleanDigits) >= 6) {
                $last7 = substr($cleanDigits, -7);
                $last8 = substr($cleanDigits, -8);
                $query->orWhere('phone', $cleanDigits)
                    ->orWhere('phone', '0' . ltrim($cleanDigits, '0'))
                    ->orWhere('phone', 'like', '%' . $last7)
                    ->orWhere('phone', 'like', '%' . $last8);
            }

            if (is_numeric($cleanId)) {
                $query->orWhere('id', (int) $cleanId);
            }
        })->first();

        if (!$user) {
            $msg = 'រកមិនឃើញគណនីនេះទេ។';
            if ($request->is('api/*') || (!$request->header('X-Inertia') && $request->wantsJson())) {
                return response()->json(['success' => false, 'message' => $msg], 404);
            }
            return back()->withErrors(['code' => $msg]);
        }

        $sessionCode = session('reset_code');
        $sessionUserId = session('reset_user_id');
        $sessionExpiresAt = session('reset_expires_at');

        $isValidOtp = false;

        // 1. Check in database
        if (!empty($user->otp_code) && $user->otp_code === $code) {
            if (!$user->otp_expires_at || $user->otp_expires_at->isFuture()) {
                $isValidOtp = true;
            }
        }

        // 2. Check in session fallback
        if (!$isValidOtp && $sessionCode === $code && $sessionUserId == $user->id) {
            if (!$sessionExpiresAt || now()->timestamp <= $sessionExpiresAt) {
                $isValidOtp = true;
            }
        }

        if (!$isValidOtp) {
            $msg = 'លេខកូដ OTP មិនត្រឹមត្រូវ ឬផុតសុពលភាព (៥ នាទី)!';
            if ($request->is('api/*') || (!$request->header('X-Inertia') && $request->wantsJson())) {
                return response()->json(['success' => false, 'message' => $msg], 400);
            }
            return back()->withErrors(['code' => $msg]);
        }

        session(['reset_otp_verified' => true]);

        $successMsg = 'លេខកូដ OTP ត្រូវបានផ្ទៀងផ្ទាត់ត្រឹមត្រូវ!';

        if ($request->is('api/*') || (!$request->header('X-Inertia') && $request->wantsJson())) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
            ]);
        }

        return back()->with([
            'success'            => true,
            'status'             => $successMsg,
            'message'            => $successMsg,
            'reset_otp_verified' => true,
        ]);
    }

    /**
     * Reset user password using verification code.
     */
    public function resetPassword(Request $request)
    {
        $input = trim($request->input('email') ?? $request->input('identifier') ?? '');
        $code = trim($request->input('code') ?? $request->input('otpCode') ?? '');
        $password = $request->input('password') ?? $request->input('newPassword') ?? '';
        $passwordConfirmation = $request->input('password_confirmation') ?? $password;

        if (empty($input) || empty($code) || empty($password)) {
            $msg = 'សូមបញ្ចូលព័ត៌មានឱ្យបានគ្រប់ជ្រុងជ្រោយ (អ៊ីមែល/ID, លេខកូដ OTP, និងពាក្យសម្ងាត់ថ្មី)។';
            if ($request->is('api/*') || (!$request->header('X-Inertia') && $request->wantsJson())) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->withErrors(['email' => $msg]);
        }

        if (strlen($password) < 8) {
            $msg = 'ពាក្យសម្ងាត់ត្រូវមានយ៉ាងតិច 8 តួអក្សរ។';
            if ($request->is('api/*') || (!$request->header('X-Inertia') && $request->wantsJson())) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->withErrors(['password' => $msg]);
        }

        if ($request->has('password_confirmation') && $password !== $passwordConfirmation) {
            $msg = 'ការបញ្ជាក់ពាក្យសម្ងាត់មិនត្រូវគ្នាទេ។';
            if ($request->is('api/*') || (!$request->header('X-Inertia') && $request->wantsJson())) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->withErrors(['password' => $msg]);
        }

        $cleanDigits = preg_replace('/[^0-9]/', '', $input);

        $user = User::where(function ($query) use ($input, $cleanDigits) {
            $cleanUser = ltrim($input, '@');
            $cleanId = ltrim($input, '#');

            $query->where('email', $input)
                ->orWhere('student_code', $input)
                ->orWhere('phone', $input)
                ->orWhere('telegram_username', $cleanUser)
                ->orWhere('telegram_id', $input)
                ->orWhere('telegram_chat_id', $input);

            if (!empty($cleanDigits) && strlen($cleanDigits) >= 6) {
                $last7 = substr($cleanDigits, -7);
                $last8 = substr($cleanDigits, -8);
                $query->orWhere('phone', $cleanDigits)
                    ->orWhere('phone', '0' . ltrim($cleanDigits, '0'))
                    ->orWhere('phone', 'like', '%' . $last7)
                    ->orWhere('phone', 'like', '%' . $last8);
            }

            if (is_numeric($cleanId)) {
                $query->orWhere('id', (int) $cleanId);
            }
        })->first();

        if (!$user) {
            $msg = 'រកមិនឃើញគណនីនេះទេ។';
            if ($request->is('api/*') || (!$request->header('X-Inertia') && $request->wantsJson())) {
                return response()->json(['success' => false, 'message' => $msg], 404);
            }
            return back()->withErrors(['email' => $msg]);
        }

        $sessionCode = session('reset_code');
        $sessionUserId = session('reset_user_id');
        $sessionExpiresAt = session('reset_expires_at');

        $isValidOtp = false;

        // 1. Check in database
        if (!empty($user->otp_code) && $user->otp_code === $code) {
            if (!$user->otp_expires_at || $user->otp_expires_at->isFuture()) {
                $isValidOtp = true;
            }
        }

        // 2. Check in session fallback
        if (!$isValidOtp && $sessionCode === $code && $sessionUserId == $user->id) {
            if (!$sessionExpiresAt || now()->timestamp <= $sessionExpiresAt) {
                $isValidOtp = true;
            }
        }

        if (!$isValidOtp) {
            $msg = 'លេខកូដ OTP មិនត្រឹមត្រូវ ឬផុតសុពលភាព (៥ នាទី)!';
            if ($request->is('api/*') || (!$request->header('X-Inertia') && $request->wantsJson())) {
                return response()->json(['success' => false, 'message' => $msg], 400);
            }
            return back()->withErrors(['code' => $msg]);
        }

        // Update password and clear OTP
        try {
            $user->update([
                'password'       => Hash::make($password),
                'otp_code'       => null,
                'otp_expires_at' => null,
                'locked_until'   => null,
                'login_attempts' => 0,
            ]);
        } catch (\Throwable $e) {
            $user->update([
                'password'       => Hash::make($password),
                'locked_until'   => null,
                'login_attempts' => 0,
            ]);
        }

        // Clear reset session
        session()->forget(['reset_code', 'reset_user_id', 'reset_user_email', 'reset_expires_at']);

        $successMsg = 'ផ្លាស់ប្តូរលេខសម្ងាត់ជោគជ័យ! លោកអ្នកអាច Login បាន។';

        if ($request->is('api/*') || (!$request->header('X-Inertia') && $request->wantsJson())) {
            return response()->json([
                'success'  => true,
                'message'  => $successMsg,
                'redirect' => '/login',
            ]);
        }

        return redirect('/login')->with('status', $successMsg);
    }
}
