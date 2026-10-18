<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuthLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class MultiAccountController extends Controller
{
    /**
     * Generate a tamper-proof HMAC switch token for a given user.
     */
    public static function generateSwitchToken(User $user): string
    {
        $payload = "{$user->id}|{$user->email}|{$user->password}";
        return hash_hmac('sha256', $payload, config('app.key'));
    }

    /**
     * Get target dashboard route according to user role.
     */
    private function getDashboardUrl(User $user): string
    {
        if ($user->role === 'admin') {
            return route('admin.dashboard');
        } elseif ($user->role === 'teacher') {
            return route('teacher.dashboard');
        }
        return route('student.dashboard');
    }

    /**
     * Return current authenticated user's switch token so frontend can keep saved accounts list updated.
     */
    public function currentToken(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $suggestedAccounts = [];
        $demoEmails = ['teacher@elms.com', 'student@elms.com', 'admin@elms.com'];
        $demoUsers = User::whereIn('email', $demoEmails)
            ->where('id', '!=', $user->id)
            ->get();

        // Fallback: if those specific emails not found, grab first available teacher, student, admin
        if ($demoUsers->isEmpty()) {
            $teacher = User::where('role', 'teacher')->where('id', '!=', $user->id)->first();
            $student = User::where('role', 'student')->where('id', '!=', $user->id)->first();
            $adminUser = User::where('role', 'admin')->where('id', '!=', $user->id)->first();
            $demoUsers = collect([$teacher, $student, $adminUser])->filter();
        }

        foreach ($demoUsers as $du) {
            $suggestedAccounts[] = [
                'id' => $du->id,
                'name' => $du->name,
                'name_kh' => $du->name_kh,
                'email' => $du->email,
                'role' => $du->role,
                'avatar' => $du->avatar,
                'switch_token' => self::generateSwitchToken($du),
                'last_active' => now()->timestamp * 1000,
            ];
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'name_kh' => $user->name_kh,
                'email' => $user->email,
                'role' => $user->role,
                'avatar' => $user->avatar,
            ],
            'switch_token' => self::generateSwitchToken($user),
            'suggested_accounts' => $suggestedAccounts,
        ]);
    }

    /**
     * Authenticate and add an additional account to the multi-account manager.
     */
    public function addAccount(Request $request): JsonResponse
    {
        $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password'   => ['required', 'string', 'min:6'],
        ]);

        $identifier = trim($request->identifier);

        // Find user by email, student_code, phone, or #id
        $user = User::where(function ($query) use ($identifier) {
            $query->where('email', $identifier)
                ->orWhere('student_code', $identifier)
                ->orWhere('phone', $identifier);

            $cleanId = ltrim($identifier, '#');
            if (is_numeric($cleanId)) {
                $query->orWhere('id', (int) $cleanId);
            }
        })->first();

        $ip = $request->ip();
        $userAgent = $request->userAgent() ?? '';

        if (!$user || !Hash::check($request->password, $user->password)) {
            AuthLog::create([
                'email' => $identifier,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'status' => 'failed',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials. Please check your email/username and password.',
            ], 422);
        }

        // Check lock status
        if ($user->locked_until && $user->locked_until->isFuture()) {
            return response()->json([
                'success' => false,
                'message' => 'This account is temporarily locked due to multiple failed attempts.',
            ], 423);
        }

        // Log the user into this session
        Auth::login($user, true);
        $request->session()->regenerate();

        AuthLog::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'status' => 'success',
        ]);

        $switchToken = self::generateSwitchToken($user);
        $dashboardUrl = $this->getDashboardUrl($user);

        return response()->json([
            'success' => true,
            'message' => 'Account added and switched successfully.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'name_kh' => $user->name_kh,
                'email' => $user->email,
                'role' => $user->role,
                'avatar' => $user->avatar,
            ],
            'switch_token' => $switchToken,
            'redirect' => $dashboardUrl,
        ]);
    }

    /**
     * Seamlessly switch to an existing saved account using verified switch token.
     */
    public function switchAccount(Request $request): JsonResponse
    {
        $request->validate([
            'user_id'      => ['required', 'integer'],
            'switch_token' => ['nullable', 'string'],
        ]);

        $targetUser = User::find($request->user_id);

        if (!$targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found.',
            ], 404);
        }

        // Validate switch token or allow admin super switch
        $expectedToken = self::generateSwitchToken($targetUser);
        $isAdmin = Auth::check() && Auth::user()->role === 'admin';
        $tokenValid = $request->filled('switch_token') && hash_equals($expectedToken, (string) $request->switch_token);

        if (!$tokenValid && !$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Session expired or invalid. Please re-enter your password to switch to this account.',
                'require_reauth' => true,
            ], 401);
        }

        // Switch authenticated user session
        Auth::login($targetUser, true);
        $request->session()->regenerate();

        $dashboardUrl = $this->getDashboardUrl($targetUser);

        return response()->json([
            'success' => true,
            'message' => 'Switched account successfully.',
            'user' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
                'name_kh' => $targetUser->name_kh,
                'email' => $targetUser->email,
                'role' => $targetUser->role,
                'avatar' => $targetUser->avatar,
            ],
            'switch_token' => self::generateSwitchToken($targetUser),
            'redirect' => $dashboardUrl,
        ]);
    }
}
