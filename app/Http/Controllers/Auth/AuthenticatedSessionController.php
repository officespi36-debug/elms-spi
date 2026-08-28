<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\LoginSecurityAlertMail;
use App\Models\AuthLog;
use App\Models\User;
use App\Rules\Turnstile;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request, TelegramService $telegramService)
    {
        // 1. Email / Student ID / Phone, Password & Turnstile Validation
        $request->validate([
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['nullable', 'string', 'in:student,teacher,admin'],
            'turnstile_token' => ['required', new Turnstile],
        ], [
            'email.required' => 'សូមបញ្ចូលអាសយដ្ឋានអ៊ីមែល, ID, ឬលេខទូរស័ព្ទ។',
            'password.required' => 'សូមបញ្ចូលពាក្យសម្ងាត់។',
            'password.min' => 'ពាក្យសម្ងាត់ត្រូវមានយ៉ាងតិច ៨ តួអក្សរ។',
            'turnstile_token.required' => 'សូមផ្ទៀងផ្ទាត់សុវត្ថិភាព Cloudflare (Turnstile) ជាមុនសិន។',
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

        // Log Helper
        $ip = $request->ip();
        $userAgent = $request->userAgent() ?? '';
        $device = str_contains(strtolower($userAgent), 'mobile') ? 'Mobile' : 'Desktop';
        $browser = $this->getBrowserName($userAgent);

        if (!$user) {
            AuthLog::create([
                'email' => $loginInput,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'device' => $device,
                'browser' => $browser,
                'status' => 'failed',
            ]);

            return back()->withErrors([
                'email' => 'គណនី ឬពាក្យសម្ងាត់មិនត្រឹមត្រូវទេ។',
            ]);
        }

        // 2. Check if Account is Locked (Failed login attempt threshold)
        if ($user->locked_until && $user->locked_until->isFuture()) {
            $diffMinutes = ceil(now()->diffInSeconds($user->locked_until) / 60);

            AuthLog::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'device' => $device,
                'browser' => $browser,
                'status' => 'locked',
            ]);

            return back()->withErrors([
                'email' => "គណនីត្រូវបានផ្អាកបណ្តោះអាសន្ន! ព្យាយាមខុសច្រើនដង។ សូមព្យាយាមម្តងទៀតក្នុងរយៈពេល {$diffMinutes} នាទី។",
            ]);
        }

        // Reset lock if expired
        if ($user->locked_until && $user->locked_until->isPast()) {
            $user->update([
                'locked_until' => null,
                'login_attempts' => 0,
            ]);
        }

        // 3. Verify Password
        if (!Hash::check($request->password, $user->password)) {
            $attempts = $user->login_attempts + 1;
            $updateData = ['login_attempts' => $attempts];

            if ($attempts >= 5) {
                $updateData['locked_until'] = now()->addMinutes(30);
                $updateData['login_attempts'] = 0;
            }

            $user->update($updateData);

            AuthLog::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'device' => $device,
                'browser' => $browser,
                'status' => 'failed',
            ]);

            if ($attempts >= 5) {
                return back()->withErrors([
                    'email' => 'អ្នកបានបញ្ចូលពាក្យសម្ងាត់ខុស ៥ ដង! គណនីត្រូវសោរ ៣០ នាទី។',
                ]);
            }

            $remaining = 5 - $attempts;
            return back()->withErrors([
                'email' => "ពាក្យសម្ងាត់មិនត្រឹមត្រូវទេ! អ្នកនៅសល់ ៖ {$remaining} ដងទៀតមុនពេលគណនីត្រូវបានសោរ។",
            ]);
        }

        // 4. Account Status Verification
        if ($user->is_active === false || $user->status === 'inactive') {
            return back()->withErrors([
                'email' => 'គណនីរបស់អ្នកត្រូវបានបិទដំណើរការ។',
            ]);
        }

        if ($user->status === 'suspended') {
            return back()->withErrors([
                'email' => 'គណនីរបស់អ្នកត្រូវបានព្យួរជាបណ្តោះអាសន្ន។',
            ]);
        }

        if ($user->status === 'pending_payment') {
            return back()->withErrors([
                'email' => 'គណនីរបស់អ្នកកំពុងរង់ចាំការផ្ទៀងផ្ទាត់ការបង់ប្រាក់។',
            ]);
        }

        // 5. Role Match Check (If specified)
        if ($request->filled('role') && $user->role !== $request->role) {
            return back()->withErrors([
                'email' => "គណនីនេះមិនមែនជា Role {$request->role} ទេ។ Role របស់គណនីគឺ ៖ " . strtoupper($user->role),
            ]);
        }

        // 6. Successful Login
        $user->update([
            'login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => now(),
        ]);

        AuthLog::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'device' => $device,
            'browser' => $browser,
            'status' => 'success',
        ]);

        // 1. Dispatch Security Login Alert Email to User
        try {
            if (!empty($user->email)) {
                $loginDetails = [
                    'ip' => $ip,
                    'device' => $device,
                    'browser' => $browser,
                    'time' => now()->setTimezone('Asia/Phnom_Penh')->format('d-M-Y h:i A'),
                    'location' => 'Cambodia',
                ];

                Mail::to($user->email)->send(new LoginSecurityAlertMail($user, $loginDetails));
            }
        } catch (\Throwable $e) {
            Log::warning('Login security alert email failed: ' . $e->getMessage());
        }

        // 2. Telegram Admin & Personal Notifications for Login
        try {
            // Admin Group Notification
            $telegramService->sendMessage(
                "<b>🔓 USER LOGIN SUCCESSFUL</b>\n" .
                "----------------------------------------\n" .
                "👤 <b>Name:</b> {$user->name}\n" .
                "📧 <b>Email:</b> {$user->email}\n" .
                "🎓 <b>Role:</b> " . strtoupper($user->role) . "\n" .
                "🌐 <b>IP Address:</b> {$ip}\n" .
                "📱 <b>Device:</b> {$device} ({$browser})\n" .
                "⏰ <b>Time:</b> " . now()->setTimezone('Asia/Phnom_Penh')->format('Y-m-d h:i A') . "\n"
            );

            // Direct User Private Telegram Notification (if account is linked)
            $userChatId = $user->telegram_id ?: $user->telegram_chat_id;
            if (!empty($userChatId)) {
                $telegramService->sendMessage(
                    "🛡️ <b>ការជូនដំណឹងសុវត្ថិភាព៖ ការចូលប្រើប្រាស់គណនី</b>\n" .
                    "━━━━━━━━━━━━━━━━━━━━━\n\n" .
                    "សួស្តី <b>" . ($user->name_kh ?: $user->name) . "</b> 👋\n" .
                    "គណនីរបស់អ្នកទើបតែបាន Login ចូលប្រើប្រាស់លើ SPI E-LMS ដោយជោគជ័យ ៖\n\n" .
                    "⏰ <b>ម៉ោង៖</b> " . now()->setTimezone('Asia/Phnom_Penh')->format('d-M-Y h:i A') . "\n" .
                    "📱 <b>ឧបករណ៍៖</b> {$device} ({$browser})\n" .
                    "🌐 <b>IP Address៖</b> <code>{$ip}</code>\n\n" .
                    "⚠️ <i>ប្រសិនបើមិនមែនជាអ្នកទេ សូមចូលទៅប្តូរពាក្យសម្ងាត់ជាបន្ទាន់!</i>",
                    'HTML',
                    $userChatId
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Telegram login notify failed: ' . $e->getMessage());
        }

        // Generate JWT Token safely
        try {
            if (config('jwt.secret')) {
                $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);
                $payload = \Tymon\JWTAuth\Facades\JWTAuth::setToken($token)->getPayload();

                \App\Models\JwtSession::create([
                    'user_id' => $user->id,
                    'token' => $token,
                    'expires_at' => \Carbon\Carbon::createFromTimestamp($payload->get('exp')),
                    'ip_address' => $ip,
                    'user_agent' => $userAgent,
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('JWT token creation exception: ' . $e->getMessage());
        }

        // Login and regenerate session
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->forget('url.intended');

        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        } elseif ($user->role === 'teacher') {
            return redirect()->route('teacher.dashboard');
        } else {
            return redirect()->route('student.dashboard');
        }
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function getBrowserName($userAgent)
    {
        if (str_contains($userAgent, 'Chrome'))
            return 'Chrome';
        if (str_contains($userAgent, 'Firefox'))
            return 'Firefox';
        if (str_contains($userAgent, 'Safari'))
            return 'Safari';
        if (str_contains($userAgent, 'Edge'))
            return 'Edge';
        return 'Unknown';
    }
}
