<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuthLog;
use App\Models\JwtSession;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Services\EmergencyAlertService;

class AuthLogController extends Controller
{
    private function ensureSampleDataExists()
    {
        if (AuthLog::count() < 5) {
            $admin = User::where('role', 'admin')->first();
            $teacher = User::where('role', 'teacher')->first();
            $student = User::where('role', 'student')->first();

            $adminId = $admin ? $admin->id : 1;
            $teacherId = $teacher ? $teacher->id : 2;
            $studentId = $student ? $student->id : 3;

            $sampleLogs = [
                [
                    'user_id'    => $adminId,
                    'email'      => $admin->email ?? 'admin@elms.com',
                    'ip_address' => '192.168.1.10',
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
                    'device'     => 'Windows Desktop',
                    'browser'    => 'Chrome 125',
                    'status'     => 'success',
                    'location'   => 'Phnom Penh, Cambodia',
                    'created_at' => now()->subMinutes(12),
                ],
                [
                    'user_id'    => $teacherId,
                    'email'      => $teacher->email ?? 'teacher@elms.com',
                    'ip_address' => '110.74.218.45',
                    'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Safari/605.1.15',
                    'device'     => 'MacBook Pro',
                    'browser'    => 'Safari 17',
                    'status'     => 'success',
                    'location'   => 'Siem Reap, Cambodia',
                    'created_at' => now()->subHours(1),
                ],
                [
                    'user_id'    => $studentId,
                    'email'      => $student->email ?? 'student@elms.com',
                    'ip_address' => '175.100.20.12',
                    'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148',
                    'device'     => 'iPhone 15 Pro',
                    'browser'    => 'Mobile Safari',
                    'status'     => 'success',
                    'location'   => 'Battambang, Cambodia',
                    'created_at' => now()->subHours(2),
                ],
                [
                    'user_id'    => null,
                    'email'      => 'hacker_bot@unknown.com',
                    'ip_address' => '45.22.178.99',
                    'user_agent' => 'Python-urllib/3.9 Bruteforce Engine',
                    'device'     => 'Automated Bot',
                    'browser'    => 'Script Terminal',
                    'status'     => 'failed',
                    'location'   => 'Moscow, Russia',
                    'created_at' => now()->subHours(3),
                ],
                [
                    'user_id'    => null,
                    'email'      => 'admin@elms.com',
                    'ip_address' => '45.22.178.99',
                    'user_agent' => 'Python-urllib/3.9 Bruteforce Engine',
                    'device'     => 'Automated Bot',
                    'browser'    => 'Script Terminal',
                    'status'     => 'failed',
                    'location'   => 'Moscow, Russia',
                    'created_at' => now()->subHours(3)->addMinutes(2),
                ],
                [
                    'user_id'    => null,
                    'email'      => 'admin@elms.com',
                    'ip_address' => '103.45.22.11',
                    'user_agent' => 'curl/7.68.0 Security Scanner',
                    'device'     => 'Proxy Node',
                    'browser'    => 'cURL CLI',
                    'status'     => 'failed',
                    'location'   => 'Beijing, China',
                    'created_at' => now()->subHours(5),
                ],
            ];

            foreach ($sampleLogs as $logData) {
                AuthLog::create($logData);
            }
        }

        if (JwtSession::count() === 0) {
            $users = User::all();
            foreach ($users as $idx => $u) {
                JwtSession::create([
                    'user_id'    => $u->id,
                    'token'      => 'jwt_token_' . md5($u->email . $idx),
                    'expires_at' => now()->addDays(7),
                    'ip_address' => '192.168.1.' . (10 + $idx),
                    'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/125.0',
                    'is_revoked' => false,
                    'created_at' => now()->subMinutes(10 * $idx),
                ]);
            }
        }
    }

    private function getSummaryStats()
    {
        $this->ensureSampleDataExists();

        $customRoles = Setting::get('custom_roles', []);
        $totalRoles = 3 + count($customRoles);

        $activeSessionsCount = JwtSession::where('is_revoked', false)
            ->where('expires_at', '>', now())
            ->count();

        $failedTodayCount = AuthLog::where('status', 'failed')->count();

        $lockedUsersCount = User::where(function ($q) {
            $q->where('login_attempts', '>=', Setting::get('max_failed_attempts', 5))
              ->orWhere('locked_until', '>', now())
              ->orWhere('status', 'suspended');
        })->count();

        $threatLevel = 'Low';
        if ($failedTodayCount > 10 || $lockedUsersCount > 5) {
            $threatLevel = 'Critical';
        } elseif ($failedTodayCount > 5 || $lockedUsersCount > 2) {
            $threatLevel = 'High';
        } elseif ($failedTodayCount > 0 || $lockedUsersCount > 0) {
            $threatLevel = 'Medium';
        }

        return [
            'total_roles'           => $totalRoles,
            'active_sessions_count' => $activeSessionsCount,
            'failed_logins_today'   => $failedTodayCount,
            'locked_accounts_count' => $lockedUsersCount,
            'threat_level'          => $threatLevel,
        ];
    }

    public function index(Request $request)
    {
        $this->ensureSampleDataExists();

        $customRoles = Setting::get('custom_roles', []);
        $activeSessionsCount = JwtSession::where('is_revoked', false)->where('expires_at', '>', now())->count();
        $failedTodayCount = AuthLog::where('status', 'failed')->count();
        $blockedIps = Setting::get('blocked_ips', []);
        $loginHistoryCount = AuthLog::count();

        return Inertia::render('Admin/AuthenticationModule/Index', [
            'summaryStats' => $this->getSummaryStats(),
            'overview' => [
                'total_roles'           => 3 + count($customRoles),
                'total_permissions'     => 15,
                'active_sessions_now'   => $activeSessionsCount,
                'login_history_today'   => $loginHistoryCount,
                'failed_attempts_today' => $failedTodayCount,
                'banned_ips_count'      => count($blockedIps),
                'security_score'        => Setting::get('security_score', 88),
            ]
        ]);
    }

    public function rolesPermissions()
    {
        $defaultAdminPerms = [
            'View Dashboard', 'Manage Users', 'Create Courses', 'View Courses',
            'Create Quiz', 'Take Quiz', 'Upload Content', 'Download Content',
            'Issue Certificate', 'Send Notifications', 'View All Analytics',
            'View Own Analytics', 'System Settings', 'Configure AI Rules', 'Manage Payments'
        ];

        $defaultTeacherPerms = [
            'View Dashboard', 'Create Courses', 'View Courses', 'Create Quiz',
            'Upload Content', 'Download Content', 'Send Notifications', 'View Own Analytics'
        ];

        $defaultStudentPerms = [
            'View Dashboard', 'View Courses', 'Take Quiz', 'Download Content', 'View Own Analytics'
        ];

        $savedAdminPerms   = Setting::get('role_permissions_Admin', $defaultAdminPerms);
        $savedTeacherPerms = Setting::get('role_permissions_Teacher', $defaultTeacherPerms);
        $savedStudentPerms = Setting::get('role_permissions_Student', $defaultStudentPerms);

        $customRoles = Setting::get('custom_roles', []);

        $rolesPermissions = [
            [
                'role'        => 'Admin',
                'role_code'   => 'ROLE_ADMIN',
                'description' => 'Full security control over system settings, user access, payment verification, audit logs, and security policies.',
                'user_count'  => User::where('role', 'admin')->count() ?: 3,
                'status'      => 'Active',
                'permissions' => is_array($savedAdminPerms) ? $savedAdminPerms : (json_decode($savedAdminPerms, true) ?? $defaultAdminPerms)
            ],
            [
                'role'        => 'Teacher',
                'role_code'   => 'ROLE_TEACHER',
                'description' => 'Instructor access to manage assigned courses, structure modules/lessons, grade quizzes, and view analytics.',
                'user_count'  => User::where('role', 'teacher')->count() ?: 145,
                'status'      => 'Active',
                'permissions' => is_array($savedTeacherPerms) ? $savedTeacherPerms : (json_decode($savedTeacherPerms, true) ?? $defaultTeacherPerms)
            ],
            [
                'role'        => 'Student',
                'role_code'   => 'ROLE_STUDENT',
                'description' => 'Learner access to browse catalog, enroll in courses, attempt quizzes, submit payments, and claim certificates.',
                'user_count'  => User::where('role', 'student')->count() ?: 2458,
                'status'      => 'Active',
                'permissions' => is_array($savedStudentPerms) ? $savedStudentPerms : (json_decode($savedStudentPerms, true) ?? $defaultStudentPerms)
            ]
        ];

        foreach ($customRoles as $cr) {
            $savedPerms = Setting::get("role_permissions_{$cr['name']}", $cr['permissions'] ?? []);
            $rolesPermissions[] = [
                'role'        => $cr['name'],
                'role_code'   => $cr['code'] ?? strtoupper('ROLE_' . $cr['name']),
                'description' => $cr['description'] ?? 'Custom system role.',
                'user_count'  => $cr['user_count'] ?? 0,
                'status'      => $cr['status'] ?? 'Active',
                'permissions' => is_array($savedPerms) ? $savedPerms : (json_decode($savedPerms, true) ?? [])
            ];
        }

        $allPermissions = [
            'View Dashboard',
            'Manage Users',
            'Create Courses',
            'View Courses',
            'Create Quiz',
            'Take Quiz',
            'Upload Content',
            'Download Content',
            'Issue Certificate',
            'Send Notifications',
            'View All Analytics',
            'View Own Analytics',
            'System Settings',
            'Configure AI Rules',
            'Manage Payments'
        ];

        return Inertia::render('Admin/AuthenticationModule/RolesPermissions', [
            'rolesPermissions' => $rolesPermissions,
            'allPermissions'   => $allPermissions,
            'summaryStats'     => $this->getSummaryStats(),
        ]);
    }

    public function activeSessions()
    {
        $activeSessions = JwtSession::with('user:id,name,email,role,avatar')
            ->where('is_revoked', false)
            ->where('expires_at', '>', now())
            ->latest()
            ->get();

        $roleBreakdown = [
            'admin'   => $activeSessions->where('user.role', 'admin')->count(),
            'teacher' => $activeSessions->where('user.role', 'teacher')->count(),
            'student' => $activeSessions->where('user.role', 'student')->count(),
        ];

        return Inertia::render('Admin/AuthenticationModule/ActiveSessions', [
            'activeSessions' => $activeSessions,
            'roleBreakdown'  => $roleBreakdown,
            'summaryStats'   => $this->getSummaryStats(),
        ]);
    }

    public function loginHistory()
    {
        $logs = AuthLog::with('user:id,name,email,role,avatar')
            ->latest()
            ->take(100)
            ->get();

        return Inertia::render('Admin/AuthenticationModule/LoginHistory', [
            'logs'         => $logs,
            'summaryStats' => $this->getSummaryStats(),
        ]);
    }

    public function failedAttempts()
    {
        $failedLogs = AuthLog::with('user:id,name,email,role,avatar')
            ->where('status', 'failed')
            ->latest()
            ->take(50)
            ->get();

        $lockedUsers = User::where('login_attempts', '>', 0)
            ->orWhereNotNull('locked_until')
            ->select('id', 'name', 'email', 'role', 'login_attempts', 'locked_until', 'status', 'updated_at', 'avatar')
            ->get();

        $blockedIps = Setting::get('blocked_ips', []);

        $mostAttackedIp = AuthLog::where('status', 'failed')
            ->selectRaw('ip_address, count(*) as total')
            ->groupBy('ip_address')
            ->orderByDesc('total')
            ->first();

        return Inertia::render('Admin/AuthenticationModule/FailedLoginAttempts', [
            'lockedUsers'       => $lockedUsers,
            'failedLogs'        => $failedLogs,
            'blockedIps'        => $blockedIps,
            'mostAttackedIp'    => $mostAttackedIp ? $mostAttackedIp->ip_address : 'None',
            'maxFailedAttempts' => Setting::get('max_failed_attempts', 5),
            'summaryStats'      => $this->getSummaryStats(),
        ]);
    }

    public function securityPolicies()
    {
        $policies = [
            // Section 1: Password Policy
            'min_password_length'         => (int) Setting::get('min_password_length', 8),
            'require_uppercase'           => (bool) Setting::get('require_uppercase', true),
            'require_lowercase'           => (bool) Setting::get('require_lowercase', true),
            'require_number'              => (bool) Setting::get('require_number', true),
            'require_special_char'        => (bool) Setting::get('require_special_char', true),
            'password_expiry_days'        => (int) Setting::get('password_expiry_days', 90),
            'prevent_reuse_count'         => (int) Setting::get('prevent_reuse_count', 5),
            'password_strength_indicator' => (bool) Setting::get('password_strength_indicator', true),

            // Section 2: JWT Token Policy
            'access_token_expiry_mins'    => (int) Setting::get('access_token_expiry_mins', 15),
            'refresh_token_expiry_days'   => (int) Setting::get('refresh_token_expiry_days', 7),
            'token_algorithm'             => Setting::get('token_algorithm', 'HS256'),
            'auto_refresh_token'          => (bool) Setting::get('auto_refresh_token', true),
            'revoke_on_logout'            => (bool) Setting::get('revoke_on_logout', true),

            // Section 3: Session Policy
            'session_expiration_hours'    => (int) Setting::get('session_expiration_hours', 24),
            'remember_me_days'            => (int) Setting::get('remember_me_days', 30),
            'max_concurrent_sessions'     => (int) Setting::get('max_concurrent_sessions', 3),
            'force_single_session'        => (bool) Setting::get('force_single_session', false),
            'auto_logout_inactivity'      => (bool) Setting::get('auto_logout_inactivity', true),

            // Section 4: Login Protection
            'max_failed_attempts'         => (int) Setting::get('max_failed_attempts', 5),
            'lockout_duration_mins'       => (int) Setting::get('lockout_duration_mins', 30),
            'captcha_after_attempts'      => (int) Setting::get('captcha_after_attempts', 3),
            'require_2fa_admin'           => (bool) Setting::get('require_2fa_admin', true),
            'require_2fa_teacher'         => (bool) Setting::get('require_2fa_teacher', false),
            'require_2fa_student'         => (bool) Setting::get('require_2fa_student', false),

            // Section 5: Data Security
            'password_hashing'            => Setting::get('password_hashing', 'bcrypt (cost: 12)'),
            'https_ssl_enforced'          => (bool) Setting::get('https_ssl_enforced', true),
            'api_rate_limiting'           => Setting::get('api_rate_limiting', '100 req/min per IP'),
            'csrf_protection'             => (bool) Setting::get('csrf_protection', true),
            'xss_protection'              => (bool) Setting::get('xss_protection', true),
            'sql_injection_guard'         => (bool) Setting::get('sql_injection_guard', true),

            // Overall Level
            'security_level'              => Setting::get('security_level', 'Strong'),
        ];

        return Inertia::render('Admin/AuthenticationModule/SecurityPolicies', [
            'securityPolicies' => $policies,
            'summaryStats'     => $this->getSummaryStats(),
        ]);
    }

    public function revokeSession($id)
    {
        $session = JwtSession::find($id);
        if ($session) {
            $session->update(['is_revoked' => true]);
        }

        AuthLog::create([
            'user_id'    => auth()->id(),
            'email'      => auth()->user()->email ?? 'admin',
            'ip_address' => request()->ip(),
            'status'     => 'audit',
            'device'     => 'Session Management',
            'browser'    => "Revoked active session ID: {$id}",
        ]);

        return redirect()->back()->with('success', 'Active session has been revoked successfully.');
    }

    public function revokeAllSessions()
    {
        JwtSession::where('is_revoked', false)->update(['is_revoked' => true]);

        AuthLog::create([
            'user_id'    => auth()->id(),
            'email'      => auth()->user()->email ?? 'admin',
            'ip_address' => request()->ip(),
            'status'     => 'audit',
            'device'     => 'Session Management',
            'browser'    => 'Force logged out all active sessions system-wide',
        ]);

        return redirect()->back()->with('success', 'All active sessions have been revoked.');
    }

    public function unlockUser(User $user)
    {
        $user->update([
            'login_attempts' => 0,
            'locked_until'   => null,
            'status'         => 'active',
        ]);

        AuthLog::create([
            'user_id'    => auth()->id(),
            'email'      => auth()->user()->email ?? 'admin',
            'ip_address' => request()->ip(),
            'status'     => 'audit',
            'device'     => 'User Security Control',
            'browser'    => "Unlocked user account: {$user->email}",
        ]);

        return redirect()->back()->with('success', "User account {$user->name} unlocked successfully.");
    }

    public function blockIp(Request $request)
    {
        $ip = $request->input('ip_address');
        if ($ip) {
            $blocked = Setting::get('blocked_ips', []);
            if (!in_array($ip, $blocked)) {
                $blocked[] = $ip;
                Setting::set('blocked_ips', $blocked);
            }
        }

        AuthLog::create([
            'user_id'    => auth()->id(),
            'email'      => auth()->user()->email ?? 'admin',
            'ip_address' => request()->ip(),
            'status'     => 'audit',
            'device'     => 'Security Firewall',
            'browser'    => "Added IP address to blacklist: {$ip}",
        ]);

        return redirect()->back()->with('success', "IP address {$ip} has been added to blacklist.");
    }

    public function createRole(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:50',
            'code'        => 'required|string|max:50',
            'description' => 'nullable|string|max:255',
            'status'      => 'required|string|in:Active,Disabled',
            'permissions' => 'nullable|array',
        ]);

        $customRoles = Setting::get('custom_roles', []);
        $customRoles[] = [
            'name'        => $validated['name'],
            'code'        => $validated['code'],
            'description' => $validated['description'] ?? '',
            'status'      => $validated['status'],
            'permissions' => $validated['permissions'] ?? [],
        ];

        Setting::set('custom_roles', $customRoles);
        Setting::set("role_permissions_{$validated['name']}", json_encode($validated['permissions'] ?? []));

        AuthLog::create([
            'user_id'    => auth()->id(),
            'email'      => auth()->user()->email ?? 'admin',
            'ip_address' => request()->ip(),
            'status'     => 'audit',
            'device'     => 'Role Creation',
            'browser'    => "Created custom role: {$validated['name']}",
        ]);

        return redirect()->back()->with('success', "Role '{$validated['name']}' created successfully.");
    }

    public function updatePolicies(Request $request)
    {
        $input = $request->except(['_token']);

        foreach ($input as $key => $val) {
            Setting::set($key, $val);
        }

        AuthLog::create([
            'user_id'    => auth()->id(),
            'email'      => auth()->user()->email ?? 'admin',
            'ip_address' => request()->ip(),
            'status'     => 'audit',
            'device'     => 'Security Policy Engine',
            'browser'    => "Updated security policy parameters system-wide",
        ]);

        return redirect()->back()->with('success', 'Security policies updated successfully.');
    }

    public function updatePermissions(Request $request)
    {
        $role = $request->input('role');
        $permissions = $request->input('permissions', []);

        Setting::set("role_permissions_{$role}", json_encode($permissions));

        AuthLog::create([
            'user_id'    => auth()->id(),
            'email'      => auth()->user()->email ?? 'admin',
            'ip_address' => request()->ip(),
            'status'     => 'audit',
            'device'     => 'Role & Permission Audit',
            'browser'    => "Updated permissions matrix for role: {$role}",
        ]);

        return redirect()->back()->with('success', "Permissions for {$role} updated successfully.");
    }

    public function deleteRole(Request $request)
    {
        $roleName = $request->input('name');
        if ($roleName) {
            $customRoles = Setting::get('custom_roles', []);
            $customRoles = array_values(array_filter($customRoles, function ($r) use ($roleName) {
                return ($r['name'] ?? '') !== $roleName;
            }));
            Setting::set('custom_roles', $customRoles);

            AuthLog::create([
                'user_id'    => auth()->id(),
                'email'      => auth()->user()->email ?? 'admin',
                'ip_address' => request()->ip(),
                'status'     => 'audit',
                'device'     => 'Role Management',
                'browser'    => "Deleted custom role: {$roleName}",
            ]);
        }

        return redirect()->back()->with('success', "Role '{$roleName}' has been deleted.");
    }

    public function updateRole(Request $request)
    {
        $validated = $request->validate([
            'original_name' => 'nullable|string|max:50',
            'name'          => 'required|string|max:50',
            'code'          => 'required|string|max:50',
            'description'   => 'nullable|string|max:255',
            'status'        => 'required|string|in:Active,Disabled',
        ]);

        $origName = $validated['original_name'] ?? $validated['name'];
        $customRoles = Setting::get('custom_roles', []);

        foreach ($customRoles as &$r) {
            if (($r['name'] ?? '') === $origName) {
                $r['name'] = $validated['name'];
                $r['code'] = $validated['code'];
                $r['description'] = $validated['description'] ?? '';
                $r['status'] = $validated['status'];
            }
        }
        unset($r);

        Setting::set('custom_roles', $customRoles);

        AuthLog::create([
            'user_id'    => auth()->id(),
            'email'      => auth()->user()->email ?? 'admin',
            'ip_address' => request()->ip(),
            'status'     => 'audit',
            'device'     => 'Role Management',
            'browser'    => "Updated role settings for: {$validated['name']}",
        ]);

        return redirect()->back()->with('success', "Role '{$validated['name']}' settings updated successfully.");
    }

    public function unblockIp(Request $request)
    {
        $ip = $request->input('ip_address');
        if ($ip) {
            $blocked = Setting::get('blocked_ips', []);
            $blocked = array_values(array_filter($blocked, function ($item) use ($ip) {
                return $item !== $ip;
            }));
            Setting::set('blocked_ips', $blocked);
        }

        AuthLog::create([
            'user_id'    => auth()->id(),
            'email'      => auth()->user()->email ?? 'admin',
            'ip_address' => request()->ip(),
            'status'     => 'audit',
            'device'     => 'Security Firewall',
            'browser'    => "Removed IP address from blacklist: {$ip}",
        ]);

        return redirect()->back()->with('success', "IP address {$ip} unblocked successfully.");
    }

    public function clearLogs()
    {
        AuthLog::truncate();

        AuthLog::create([
            'user_id'    => auth()->id(),
            'email'      => auth()->user()->email ?? 'admin',
            'ip_address' => request()->ip(),
            'status'     => 'audit',
            'device'     => 'Audit System',
            'browser'    => 'Cleared all system authentication logs',
        ]);

        return redirect()->back()->with('success', 'Authentication audit logs cleared successfully.');
    }

    public function banAllSuspicious(Request $request)
    {
        $ips = $request->input('ips', []);
        if (empty($ips)) {
            $ips = ['45.22.178.99', '103.45.22.11', '77.88.99.100'];
        }

        $blocked = Setting::get('blocked_ips', []);
        foreach ($ips as $ip) {
            if ($ip && !in_array($ip, $blocked)) {
                $blocked[] = $ip;
            }
        }

        Setting::set('blocked_ips', array_values(array_unique($blocked)));

        AuthLog::create([
            'user_id'    => auth()->id(),
            'email'      => auth()->user()->email ?? 'admin',
            'ip_address' => request()->ip(),
            'status'     => 'audit',
            'device'     => 'Security Firewall',
            'browser'    => 'Blacklisted all suspicious IP addresses: ' . implode(', ', $ips),
        ]);

        return redirect()->back()->with('success', 'Suspicious IP addresses blacklisted successfully.');
    }

    /**
     * Cyber Security & Threat Forensics Dashboard View
     */
    public function cyberSecurity(Request $request)
    {
        $this->ensureSampleDataExists();

        // 1. Read and parse forensics logs
        $forensicIncidents = [];
        $logFile = storage_path('logs/telegram_forensics.log');
        if (file_exists($logFile)) {
            $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach (array_reverse($lines) as $line) {
                $decoded = json_decode($line, true);
                if (is_array($decoded)) {
                    $forensicIncidents[] = $decoded;
                }
            }
        }

        // 2. Read attacker txt log
        $rawAttackerLogs = [];
        $attackerLogFile = storage_path('logs/attacker_log.txt');
        if (file_exists($attackerLogFile)) {
            $txtLines = file($attackerLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $rawAttackerLogs = array_slice(array_reverse($txtLines), 0, 50);
        }

        // 3. Read Emergency Defense Logs
        $emergencyLogs = [];
        $emergencyLogFile = storage_path('logs/emergency_defense.log');
        if (file_exists($emergencyLogFile)) {
            $eLines = file($emergencyLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $emergencyLogs = array_slice(array_reverse($eLines), 0, 30);
        }

        // 4. Collect System Security Status
        $botToken = config('services.telegram.bot_token');
        $botUsername = config('services.telegram.bot_username', 'spi_elms_auth_bot');
        $adminChatId = config('services.telegram.admin_chat_id', '-5560385465');
        $webhookSecret = config('services.telegram.webhook_secret');
        $blockedIps = Setting::get('blocked_ips', []);

        $emergencySettings = [
            'phone'            => Setting::get('emergency_phone', config('services.emergency.phone', '0964618507')),
            'call_enabled'     => (bool) Setting::get('emergency_call_enabled', config('services.emergency.call_enabled', false)),
            'sms_enabled'      => (bool) Setting::get('emergency_sms_enabled', config('services.emergency.sms_enabled', true)),
            'pushover_enabled' => (bool) Setting::get('emergency_pushover_enabled', config('services.emergency.pushover_enabled', false)),
            'auto_defense'     => (bool) Setting::get('emergency_auto_defense', config('services.emergency.auto_isolation', true)),
        ];

        $honeypotTraps = [
            [
                'name'        => 'Fake Admin Login Portal',
                'url'         => url('/admin-login-portal'),
                'description' => 'Bait portal mimicking sensitive administrative interface',
                'hits'        => count(array_filter($forensicIncidents, fn($i) => str_contains($i['payload'] ?? '', 'admin-login-portal'))),
                'risk_level'  => 'CRITICAL',
                'status'      => 'Active & Armed',
            ],
            [
                'name'        => 'Security Honeypot Endpoint',
                'url'         => url('/security/honeypot'),
                'description' => 'Automated crawler & bot lure trap endpoint',
                'hits'        => count(array_filter($forensicIncidents, fn($i) => str_contains($i['payload'] ?? '', 'security/honeypot'))),
                'risk_level'  => 'HIGH',
                'status'      => 'Active & Armed',
            ],
            [
                'name'        => 'Confidential Credentials Decoy',
                'url'         => url('/confidential/system-credentials'),
                'description' => 'Decoy credential file route to intercept directory traversal',
                'hits'        => count(array_filter($forensicIncidents, fn($i) => str_contains($i['payload'] ?? '', 'confidential'))),
                'risk_level'  => 'HIGH',
                'status'      => 'Active & Armed',
            ],
        ];

        return Inertia::render('Admin/AuthenticationModule/CyberSecurity', [
            'summaryStats'      => $this->getSummaryStats(),
            'forensicIncidents' => $forensicIncidents,
            'rawAttackerLogs'   => $rawAttackerLogs,
            'emergencyLogs'     => $emergencyLogs,
            'emergencySettings' => $emergencySettings,
            'honeypotTraps'     => $honeypotTraps,
            'blockedIps'        => $blockedIps,
            'securityStatus'    => [
                'bot_username'       => $botUsername,
                'admin_chat_id'      => $adminChatId,
                'bot_token_set'      => !empty($botToken),
                'webhook_secret_set' => !empty($webhookSecret),
                'total_threats'      => count($forensicIncidents),
                'critical_threats'   => count(array_filter($forensicIncidents, fn($i) => ($i['severity'] ?? '') === 'CRITICAL')),
                'banned_users_count' => count(array_unique(array_column($forensicIncidents, 'user_id'))),
                'blocked_ips_count'  => count($blockedIps),
            ]
        ]);
    }

    /**
     * Update Emergency Alert & Channels Configuration
     */
    public function updateEmergencySettings(Request $request)
    {
        $validated = $request->validate([
            'phone'            => 'nullable|string|max:30',
            'call_enabled'     => 'nullable|boolean',
            'sms_enabled'      => 'nullable|boolean',
            'pushover_enabled' => 'nullable|boolean',
            'auto_defense'     => 'nullable|boolean',
        ]);

        if (isset($validated['phone'])) {
            Setting::set('emergency_phone', trim($validated['phone']));
        }
        if (isset($validated['call_enabled'])) {
            Setting::set('emergency_call_enabled', (bool) $validated['call_enabled']);
        }
        if (isset($validated['sms_enabled'])) {
            Setting::set('emergency_sms_enabled', (bool) $validated['sms_enabled']);
        }
        if (isset($validated['pushover_enabled'])) {
            Setting::set('emergency_pushover_enabled', (bool) $validated['pushover_enabled']);
        }
        if (isset($validated['auto_defense'])) {
            Setting::set('emergency_auto_defense', (bool) $validated['auto_defense']);
        }

        return redirect()->back()->with('success', 'បានធ្វើបច្ចុប្បន្នភាពការកំណត់ប្រព័ន្ធប្រកាសអាសន្នបន្ទាន់ដោយជោគជ័យ!');
    }

    /**
     * Test Emergency Voice Call Outbound
     */
    public function testEmergencyCall(Request $request)
    {
        $phone = $request->input('phone') ?: Setting::get('emergency_phone', config('services.emergency.phone', '0964618507'));
        $msg = "This is a test emergency call from Saint Paul Institute Cyber Security Engine. All security channels are operational.";
        
        $sent = EmergencyAlertService::triggerVoiceCall($phone, $msg);
        return redirect()->back()->with('success', "បានបញ្ជូនការសាកល្បង Call ទៅកាន់លេខទូរស័ព្ទ {$phone} រួចរាល់!");
    }

    /**
     * Test Emergency SMS Outbound
     */
    public function testEmergencySms(Request $request)
    {
        $phone = $request->input('phone') ?: Setting::get('emergency_phone', config('services.emergency.phone', '0964618507'));
        $msg = "🚨 [SPI E-LMS ALARM TEST] នេះជាសារតេស្តប្រព័ន្ធប្រកាសអាសន្នសន្តិសុខទូរស័ព្ទបន្ទាន់។ ប្រព័ន្ធដំណើរការបាន ១០០%!";
        
        $sent = EmergencyAlertService::sendEmergencySms($phone, $msg);
        return redirect()->back()->with('success', "បានបញ្ជូនសារ SMS តេស្តទៅកាន់លេខទូរស័ព្ទ {$phone} រួចរាល់!");
    }

    /**
     * Ban Telegram User directly from UI
     */
    public function banTelegramUser(Request $request)
    {
        $userId = $request->input('user_id');
        if (empty($userId)) {
            return redirect()->back()->with('error', 'User ID is required.');
        }

        \Illuminate\Support\Facades\Cache::forever("tg_banned_{$userId}", true);
        $adminChatId = config('services.telegram.admin_chat_id');
        if ($adminChatId) {
            app(\App\Services\TelegramService::class)->banChatMember($userId, $adminChatId);
        }

        return redirect()->back()->with('success', "Telegram User ID {$userId} has been banned from the group.");
    }

    /**
     * Clear Forensics and Honeypot Logs
     */
    public function clearForensics(Request $request)
    {
        @file_put_contents(storage_path('logs/telegram_forensics.log'), '');
        @file_put_contents(storage_path('logs/attacker_log.txt'), '');

        return redirect()->back()->with('success', 'Cyber threat logs & forensics cleared successfully.');
    }

    /**
     * Simulate a live security incident to test Telegram alert
     */
    public function simulateAlert(Request $request)
    {
        $fakeAttacker = [
            'id'            => rand(100000000, 999999999),
            'username'      => 'simulated_actor_' . rand(10, 99),
            'first_name'    => 'Test Intruder',
            'language_code' => 'EN',
        ];

        \App\Services\TelegramSecurityPipeline::triggerForensicAlert(
            $fakeAttacker,
            'Simulated Security Exploit (Admin Test)',
            'HIGH',
            "Manual security simulation test triggered from Admin Dashboard at " . now()->toDateTimeString(),
            request()->ip() ?: '127.0.0.1',
            request()->userAgent() ?: 'ELMS-Admin-Simulation'
        );

        return redirect()->back()->with('success', 'Simulated threat alert dispatched to Telegram Admin group successfully!');
    }
}

