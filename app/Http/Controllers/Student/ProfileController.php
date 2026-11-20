<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicRecord;
use App\Models\AcademicYear;
use App\Models\AssignmentSubmission;
use App\Models\Certificate;
use App\Models\IdCard;
use App\Models\LessonProgress;
use App\Models\Payment;
use App\Models\ProfileChangeRequest;
use App\Models\QuizAttempt;
use App\Models\Semester;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    protected const VALID_TABS = ['id-card', 'personal', 'academic', 'security', 'history'];

    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $user->load(['major.department.faculty', 'academicYear']);

        // 1. Tab Whitelist Validation (default: 'id-card')
        $requestedTab = $request->query('tab', 'id-card');
        $activeTab = in_array($requestedTab, self::VALID_TABS, true) ? $requestedTab : 'id-card';

        // 2. Fetch truly active academic term without duplicate years
        $termInfo = $this->resolveAcademicTerm($user);

        // 3. Ensure official AcademicRecord exists
        $academicRecord = AcademicRecord::with(['faculty', 'department', 'major', 'semester', 'academicYear'])
            ->where('user_id', $user->id)
            ->first();

        if (!$academicRecord) {
            $academicRecord = AcademicRecord::create([
                'user_id'          => $user->id,
                'faculty_id'       => $user->major?->department?->faculty_id,
                'department_id'    => $user->major?->department_id,
                'major_id'         => $user->major_id,
                'academic_year_id' => $user->academic_year_id ?: $termInfo['active_year_id'],
                'semester_id'      => $termInfo['active_semester_id'],
                'gpa'              => null,
                'total_credits'    => null,
                'degree_level'     => 'Bachelor of Science (B.Sc.)',
                'study_type'       => $user->study_type ?: 'Full-time',
            ]);
            $academicRecord->load(['faculty', 'department', 'major', 'semester', 'academicYear']);
        }

        // Intake year and program duration calculation (Bachelor = 4 years, Associate = 2 years)
        $intakeYear = 2026;
        if (preg_match('/(\d{4})/', (string) ($academicRecord->academicYear?->name ?: $user->academicYear?->name ?: $termInfo['year']), $ym)) {
            $intakeYear = (int) $ym[1];
        }
        $degreeLevel = $academicRecord->degree_level ?: 'Bachelor of Science (B.Sc.)';
        $durationYears = (stripos($degreeLevel, 'associate') !== false || str_contains($degreeLevel, 'បរិញ្ញាបត្ររង')) ? 2 : 4;
        $calculatedExpiresAt = Carbon::create($intakeYear + $durationYears, 11, 14, 23, 59, 59);

        // 4. Ensure official IdCard record exists (with proper registrar approval check)
        $idCard = IdCard::firstOrCreate(
            ['user_id' => $user->id],
            [
                'card_no'     => IdCard::generateCardNo($user),
                'token'       => IdCard::generateUniqueToken(),
                'issued_at'   => Carbon::create($intakeYear, 11, 14, 0, 0, 0),
                'expires_at'  => $calculatedExpiresAt,
                'status'      => 'active',
                'approved_by' => null,
                'approved_at' => null,
            ]
        );

        // Check if card is expired
        if ($idCard->expires_at && $idCard->expires_at->isPast() && $idCard->status === 'active') {
            $idCard->update(['status' => 'expired']);
        }

        // Calculate days remaining & expiry warning status
        $daysRemaining = $idCard->expires_at ? (int) ceil(now()->floatDiffInDays($idCard->expires_at, false)) : 999;
        $isExpiringSoon = ($idCard->status === 'active' && $daysRemaining >= 0 && $daysRemaining <= 90);

        // Check if approval was performed by a genuine registrar/admin
        $approver = $idCard->approved_by ? User::find($idCard->approved_by) : null;
        $isOfficial = ($idCard->status === 'active'
            && !empty($idCard->approved_at)
            && $approver !== null
            && in_array($approver->role, ['admin', 'registrar', 'super_admin'], true));

        // Live verified requires an active card, valid token, and official approval
        $isLiveVerified = ($isOfficial && !empty($idCard->token) && $idCard->status === 'active');

        // 5. Canonical Academic Fallbacks
        $facultyName = $academicRecord->faculty?->name ?: ($user->major?->department?->faculty?->name ?: 'Faculty of Science & Technology');
        $facultyNameKh = $academicRecord->faculty?->name_kh ?: ($user->major?->department?->faculty?->name_kh ?: 'មហាវិទ្យាល័យវិទ្យាសាស្ត្រ និងបច្ចេកវិទ្យា');
        $departmentName = $academicRecord->department?->name ?: ($user->major?->department?->name ?: 'Department of Information Technology');
        $departmentNameKh = $academicRecord->department?->name_kh ?: ($user->major?->department?->name_kh ?: 'ដេប៉ាតឺម៉ង់បច្ចេកវិទ្យាព័ត៌មាន');
        $majorName = $academicRecord->major?->name ?: ($user->major?->name ?: 'Information Technology');
        $majorNameKh = $academicRecord->major?->name_kh ?: ($user->major?->name_kh ?: 'បច្ចេកវិទ្យាព័ត៌មាន (IT)');

        // 6. Security: Active sessions from database session driver
        $activeSessions = [];
        try {
            $rawSessions = DB::table('sessions')
                ->where('user_id', $user->id)
                ->orderByDesc('last_activity')
                ->get();

            $currentSessionId = session()->getId();

            foreach ($rawSessions as $sess) {
                $agent = $this->parseUserAgent((string) $sess->user_agent);
                $lastActive = Carbon::createFromTimestamp($sess->last_activity);

                $activeSessions[] = [
                    'id'          => $sess->id,
                    'is_current'  => $sess->id === $currentSessionId,
                    'ip_address'  => $this->maskIp((string) $sess->ip_address),
                    'browser'     => $agent['browser'],
                    'platform'    => $agent['platform'],
                    'device'      => $agent['device'],
                    'last_active' => $lastActive->diffForHumans(),
                ];
            }
        } catch (\Throwable $e) {
        }

        // 7. Security: Recent login history from auth_logs table
        $loginHistory = [];
        try {
            $logs = $user->authLogs()
                ->latest()
                ->limit(10)
                ->get();

            foreach ($logs as $log) {
                $loginHistory[] = [
                    'id'         => $log->id,
                    'ip_address' => $this->maskIp((string) $log->ip_address),
                    'device'     => $log->device ?: 'Desktop',
                    'browser'    => $log->browser ?: 'Browser',
                    'status'     => $log->status ?: 'Success',
                    'time'       => $log->created_at ? $log->created_at->format('M j, Y · h:i A') : '—',
                ];
            }
        } catch (\Throwable $e) {
        }

        // 8. Pending profile change requests
        $changeRequests = $user->profileChangeRequests()
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'id'          => $r->id,
                'field_name'  => $r->field_name,
                'old_value'   => $r->old_value,
                'new_value'   => $r->new_value,
                'reason'      => $r->reason,
                'status'      => $r->status,
                'created_at'  => $r->created_at?->format('M j, Y · h:i A'),
            ]);

        // 9. Profile completion percentage calculation with missing fields
        $completionFields = [
            ['key' => 'name', 'filled' => !empty($user->name), 'label_en' => 'Full Name', 'label_km' => 'ឈ្មោះពេញ', 'tab' => 'personal'],
            ['key' => 'name_kh', 'filled' => !empty($user->name_kh), 'label_en' => 'Khmer Name', 'label_km' => 'ឈ្មោះជាភាសាខ្មែរ', 'tab' => 'personal'],
            ['key' => 'phone', 'filled' => !empty($user->phone), 'label_en' => 'Phone Number', 'label_km' => 'លេខទូរស័ព្ទ', 'tab' => 'personal'],
            ['key' => 'avatar', 'filled' => !empty($user->avatar), 'label_en' => 'Photo', 'label_km' => 'រូបថតផ្ទាល់ខ្លួន', 'tab' => 'personal'],
            ['key' => 'dob', 'filled' => !empty($user->dob), 'label_en' => 'Date of Birth', 'label_km' => 'ថ្ងៃខែឆ្នាំកំណើត', 'tab' => 'personal'],
            ['key' => 'gender', 'filled' => !empty($user->gender), 'label_en' => 'Gender', 'label_km' => 'ភេទ', 'tab' => 'personal'],
            ['key' => 'bio', 'filled' => !empty($user->bio), 'label_en' => 'Bio', 'label_km' => 'ជីវប្រវត្តិសង្ខេប', 'tab' => 'personal'],
        ];

        $filledCount = count(array_filter($completionFields, fn ($f) => $f['filled']));
        $completionPercent = (int) round(($filledCount / count($completionFields)) * 100);
        $missingFields = array_values(array_filter($completionFields, fn ($f) => !$f['filled']));

        $prefs = is_array($user->preferences) ? $user->preferences : [];
        $language = $prefs['locale'] ?? $prefs['language'] ?? app()->getLocale() ?? 'km';

        $formatDateEn = fn (?Carbon $d) => $d ? $d->locale('en')->translatedFormat('M j, Y') : '—';
        $formatDateKm = fn (?Carbon $d) => $d ? $this->toKhmerDigits($d->locale('km')->translatedFormat('d F Y')) : '—';

        return Inertia::render('Student/Profile/Index', [
            'activeTab' => $activeTab,
            'profile'   => [
                'id'                 => $user->id,
                'name'               => $user->name ?? '',
                'name_kh'            => $user->name_kh ?? '',
                'email'              => $user->email ?? '',
                'student_id'         => $user->student_code ?: ('STU' . str_pad((string) $user->id, 6, '0', STR_PAD_LEFT)),
                'avatar_url'         => $user->avatar_url,
                'phone'              => $user->phone ?? '',
                'telegram_username'  => $user->telegram_username ?? '',
                'gender'             => $user->gender ?? 'male',
                'dob'                => $user->dob ? Carbon::parse($user->dob)->format('Y-m-d') : '',
                'bio'                => $user->bio ?? '',
                'status'             => $user->status ?: ($user->is_active ? 'active' : 'inactive'),
                'is_active'          => (bool) $user->is_active,
                'language'           => $language,
                'completion_percent' => $completionPercent,
                'missing_fields'     => $missingFields,
            ],
            'idCard' => [
                'card_no'          => $idCard->card_no,
                'token'            => $idCard->token,
                'verify_url'       => route('id.verify', ['token' => $idCard->token]),
                'issued_at'        => $formatDateEn($idCard->issued_at),
                'issued_at_kh'     => $formatDateKm($idCard->issued_at),
                'expires_at'       => $formatDateEn($idCard->expires_at),
                'expires_at_kh'    => $formatDateKm($idCard->expires_at),
                'status'           => $idCard->status,
                'is_official'      => $isOfficial,
                'is_live_verified' => $isLiveVerified,
                'days_remaining'   => $daysRemaining,
                'is_expiring_soon' => $isExpiringSoon,
                'approved_at'      => $formatDateEn($idCard->approved_at),
                'approved_at_kh'   => $formatDateKm($idCard->approved_at),
                'institution'      => config('app.institution', 'Saint Paul Institute'),
                'institution_kh'   => config('app.institution_kh', 'វិទ្យាស្ថាន សន្តប៉ូល'),
            ],
            'academic' => [
                'faculty'        => $facultyName,
                'faculty_kh'     => $facultyNameKh,
                'department'     => $departmentName,
                'department_kh'  => $departmentNameKh,
                'major'          => $majorName,
                'major_kh'       => $majorNameKh,
                'intake_year'    => (string) $intakeYear,
                'intake_year_kh' => $this->toKhmerDigits((string) $intakeYear),
                'term'           => $termInfo['en'],
                'term_kh'        => $termInfo['km'],
                'degree_level'   => $academicRecord->degree_level ?: 'Bachelor of Science (B.Sc.)',
                'study_type'     => $academicRecord->study_type ?: 'Full-time',
                'gpa'            => $academicRecord->gpa !== null ? (string) $academicRecord->gpa : null,
                'total_credits'  => $academicRecord->total_credits,
            ],
            'security' => [
                'active_sessions' => $activeSessions,
                'login_history'   => $loginHistory,
                'two_factor'      => [
                    'supported'   => false,
                    'status_text' => 'Protected via Telegram & Email Authentication',
                ],
            ],
            'changeRequests' => $changeRequests,
            'activityLogs'   => $this->buildActivityLogs($user),
        ]);
    }

    /**
     * Update editable personal profile fields only.
     * Locked fields (name, student_code, major_id) are rejected and ignored.
     */
    public function updatePersonal(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name_kh'           => 'nullable|string|max:255',
            'phone'             => 'nullable|string|max:50',
            'telegram_username' => 'nullable|string|max:100',
            'gender'            => 'nullable|string|in:male,female,other',
            'dob'               => 'nullable|date',
            'bio'               => 'nullable|string|max:1000',
            'avatar'            => 'nullable|image|mimes:jpeg,png,webp|max:2048',
        ]);

        // Secure Avatar Processing: max 2MB, jpg/png/webp, hash filename, delete old
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $ext = strtolower($file->getClientOriginalExtension());
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                $ext = 'jpg';
            }

            $hashedName = 'av_' . bin2hex(random_bytes(16)) . '.' . $ext;
            $destDir = public_path('uploads/avatars');

            if (!file_exists($destDir)) {
                mkdir($destDir, 0755, true);
            }

            // Delete previous local avatar
            if ($user->avatar && str_starts_with($user->avatar, '/uploads/avatars/')) {
                $oldPath = public_path(ltrim($user->avatar, '/'));
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }

            $file->move($destDir, $hashedName);
            $user->avatar = '/uploads/avatars/' . $hashedName;
        }

        $user->fill([
            'name_kh'           => $validated['name_kh'] ?? $user->name_kh,
            'phone'             => $validated['phone'] ?? null,
            'telegram_username' => $validated['telegram_username'] ?? null,
            'gender'            => $validated['gender'] ?? $user->gender,
            'dob'               => $validated['dob'] ?? $user->dob,
            'bio'               => $validated['bio'] ?? null,
        ]);
        $user->save();

        return back()->with('success', 'ព័ត៌មានផ្ទាល់ខ្លួនត្រូវបានកែប្រែដោយជោគជ័យ!');
    }

    /**
     * Submit request to update locked fields (name, student_code, major, dob).
     */
    public function storeChangeRequest(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'field_name' => 'required|string|in:name,name_kh,student_code,major,dob',
            'new_value'  => 'required|string|max:255',
            'reason'     => 'required|string|max:500',
        ]);

        $oldVal = match ($validated['field_name']) {
            'name'         => $user->name,
            'name_kh'      => $user->name_kh,
            'student_code' => $user->student_code,
            'major'        => $user->major?->name,
            'dob'          => $user->dob,
            default        => '',
        };

        ProfileChangeRequest::create([
            'user_id'    => $user->id,
            'field_name' => $validated['field_name'],
            'old_value'  => (string) $oldVal,
            'new_value'  => $validated['new_value'],
            'reason'     => $validated['reason'],
            'status'     => 'pending',
        ]);

        return back()->with('success', 'សំណើកែតម្រូវទិន្នន័យត្រូវបានបញ្ជូនទៅរដ្ឋបាលដោយជោគជ័យ!');
    }

    /**
     * Regenerate 40-character Crypto Token for ID Card QR code with audit logging.
     */
    public function regenerateIdCardToken(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $idCard = IdCard::where('user_id', $user->id)->first();
        if (!$idCard) {
            $idCard = IdCard::create([
                'user_id'    => $user->id,
                'card_no'    => IdCard::generateCardNo($user),
                'token'      => IdCard::generateUniqueToken(),
                'issued_at'  => now(),
                'expires_at' => now()->addYears(4),
                'status'     => 'active',
            ]);
        } else {
            $oldToken = $idCard->token;
            $idCard->update([
                'token' => IdCard::generateUniqueToken(),
            ]);
        }

        Log::info("Student ID Card token regenerated for user {$user->id} ({$user->name}) from IP {$request->ip()}");

        try {
            DB::table('auth_logs')->insert([
                'user_id'    => $user->id,
                'action'     => 'id_card_token_regenerated',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status'     => 'success',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
        }

        return back()->with('success', 'កូដសម្ងាត់ QR ថ្មីត្រូវបានបង្កើតដោយជោគជ័យ! តំណចាស់លែងដំណើរការទៀតហើយ។');
    }

    /**
     * Report Lost ID Card.
     * Sets status = 'revoked', logs audit event, and returns notice.
     */
    public function reportLostIdCard(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $idCard = IdCard::where('user_id', $user->id)->first();
        if ($idCard) {
            $idCard->update([
                'status' => 'revoked',
            ]);
        }

        Log::warning("Student ID Card reported lost/revoked by user {$user->id} ({$user->name}) from IP {$request->ip()}");

        try {
            DB::table('auth_logs')->insert([
                'user_id'    => $user->id,
                'action'     => 'id_card_reported_lost',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status'     => 'revoked',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
        }

        return back()->with('success', 'កាតត្រូវបានរាយការណ៍ថាបានបាត់រួចរាល់ហើយ! កាតចាស់ត្រូវបានផ្អាកសុពលភាព (Revoked) ភ្លាមៗដើម្បីសុវត្ថិភាព។ សូមទាក់ទងការិយាល័យសិក្សាដើម្បីស្នើសុំកាតថ្មី។');
    }

    /**
     * Update Student Password and disconnect other sessions.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => ['required', 'string', 'min:8', 'confirmed', 'regex:/[A-Za-z]/', 'regex:/[0-9]/'],
        ]);

        /** @var User $user */
        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['ពាក្យសម្ងាត់បច្ចុប្បន្នមិនត្រឹមត្រូវទេ (Current password does not match).'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        try {
            Auth::logoutOtherDevices($request->password);
        } catch (\Throwable $e) {
        }

        return back()->with('success', 'ពាក្យសម្ងាត់ត្រូវបានប្តូរដោយជោគជ័យ និងបានចាកចេញពីឧបករណ៍ផ្សេងទៀត!');
    }

    /**
     * Sign out of all other sessions on other devices.
     */
    public function logoutOtherSessions(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        /** @var User $user */
        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['ពាក្យសម្ងាត់មិនត្រឹមត្រូវទេ មិនអាចបញ្ជាក់ការចាកចេញបានឡើយ។'],
            ]);
        }

        try {
            Auth::logoutOtherDevices($request->password);

            $currentSessionId = session()->getId();
            DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $currentSessionId)
                ->delete();
        } catch (\Throwable $e) {
        }

        return back()->with('success', 'បានចាកចេញពីឧបករណ៍ផ្សេងទៀតទាំងអស់ដោយជោគជ័យ!');
    }

    /**
     * Export the student's personal activity history as CSV.
     */
    public function exportHistoryCsv(Request $request): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();
        $logs = $this->buildActivityLogs($user);

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="student-activity-history-' . date('Y-m-d') . '.csv"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // UTF-8 BOM
            fputcsv($handle, ['Type', 'Action (English)', 'Action (Khmer)', 'Detail', 'Timestamp']);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log['type'],
                    $log['action'],
                    $log['action_kh'] ?? '',
                    $log['detail'],
                    $log['time'],
                ]);
            }
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Public Digital ID Card Verification.
     * Displays ONLY minimal verified information (Name, Program, Status, Expiry).
     * Strictly NO phone, email, date of birth, home address, or photo.
     */
    public function verifyPublicIdCard(Request $request, string $token)
    {
        $card = IdCard::with(['user.major.department.faculty', 'user.academicRecord.major'])
            ->where('token', $token)
            ->first();

        $isValid = false;
        $studentData = null;

        if ($card && $card->status === 'active' && $card->user && $card->user->is_active) {
            if (!$card->expires_at || $card->expires_at->isFuture()) {
                $isValid = true;
                $majorName = $card->user->academicRecord?->major?->name ?: ($card->user->major?->name ?: 'Information Technology');
                $majorNameKh = $card->user->academicRecord?->major?->name_kh ?: ($card->user->major?->name_kh ?: 'បច្ចេកវិទ្យាព័ត៌មាន (IT)');

                $studentData = [
                    'name'         => $card->user->name,
                    'name_kh'      => $card->user->name_kh,
                    'program'      => $majorName,
                    'program_kh'   => $majorNameKh,
                    'status'       => 'Active Enrolled Student',
                    'status_kh'    => 'និស្សិតផ្លូវការកំពុងសិក្សា',
                    'expires_at'   => $card->expires_at ? $card->expires_at->format('M d, Y') : '—',
                    'verified_at'  => now()->format('M d, Y · h:i A'),
                    'institution'  => config('app.institution', 'Saint Paul Institute'),
                ];
            }
        }

        return Inertia::render('Student/Profile/VerifyIdCard', [
            'isValid'     => $isValid,
            'student'     => $studentData,
            'message'     => $isValid ? 'កាតសម្គាល់ខ្លួនផ្លូវការត្រូវបានផ្ទៀងផ្ទាត់ដោយជោគជ័យ' : 'កាតសម្គាល់ខ្លួនមិនត្រឹមត្រូវ ឬផុតសុពលភាព (Invalid or Revoked ID Card)',
        ])->withHeaders([
            'X-Robots-Tag'  => 'noindex, nofollow',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Fallback for standard update route.
     */
    public function update(Request $request)
    {
        return $this->updatePersonal($request);
    }

    /**
     * Helper to parse user agent for active sessions display.
     */
    protected function parseUserAgent(string $ua): array
    {
        $platform = 'Unknown Platform';
        if (preg_match('/windows|win32/i', $ua)) {
            $platform = 'Windows PC';
        } elseif (preg_match('/macintosh|mac os x/i', $ua)) {
            $platform = 'macOS';
        } elseif (preg_match('/iphone|ipad|ipod/i', $ua)) {
            $platform = 'iOS Device';
        } elseif (preg_match('/android/i', $ua)) {
            $platform = 'Android Phone';
        } elseif (preg_match('/linux/i', $ua)) {
            $platform = 'Linux';
        }

        $browser = 'Web Browser';
        if (preg_match('/edg/i', $ua)) {
            $browser = 'Microsoft Edge';
        } elseif (preg_match('/chrome|crios/i', $ua)) {
            $browser = 'Google Chrome';
        } elseif (preg_match('/firefox|fxios/i', $ua)) {
            $browser = 'Mozilla Firefox';
        } elseif (preg_match('/safari/i', $ua)) {
            $browser = 'Apple Safari';
        }

        $device = (preg_match('/mobile|android|iphone|ipad/i', $ua)) ? 'Mobile' : 'Desktop';

        return [
            'platform' => $platform,
            'browser'  => $browser,
            'device'   => $device,
        ];
    }

    /**
     * Helper to mask IP address for privacy.
     */
    protected function maskIp(?string $ip): string
    {
        if (empty($ip)) {
            return '—';
        }
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            return $parts[0] . '.' . $parts[1] . '.xxx.xxx';
        }
        return substr($ip, 0, 8) . '...';
    }

    /**
     * Build authentic student activity logs.
     */
    protected function buildActivityLogs(User $user): array
    {
        $logs = [];

        // 1. LessonProgress
        try {
            $progressItems = LessonProgress::where('user_id', $user->id)
                ->with(['lesson.course:id,title,title_kh', 'lesson.module.course:id,title,title_kh'])
                ->latest('updated_at')
                ->take(15)
                ->get();

            foreach ($progressItems as $lp) {
                $lesson = $lp->lesson;
                $course = $lesson?->course ?? $lesson?->module?->course;
                $ts = $lp->completed_at ?? $lp->updated_at ?? $lp->created_at;
                $parsed = $ts ? Carbon::parse($ts) : null;
                $isCompleted = $lp->completed_at !== null || (int) $lp->percent >= 100;

                $lessonTitle = $lesson?->title ?? 'Lesson';
                $lessonTitleKh = $lesson?->kh_title ?: $lessonTitle;
                $courseTitle = $course?->title;
                $courseTitleKh = $course?->title_kh ?: $courseTitle;

                $logs[] = [
                    'id'         => 'lp_' . $lp->id,
                    'type'       => 'lesson',
                    'action'     => $isCompleted ? 'Completed Lesson' : 'Studied Lesson',
                    'action_kh'  => $isCompleted ? 'បានបញ្ចប់មេរៀន' : 'បានសិក្សាមេរៀន',
                    'detail'     => $lessonTitle . ($courseTitle ? " • {$courseTitle}" : '') . ' (' . (int) ($lp->percent ?? 0) . '%)',
                    'detail_kh'  => $lessonTitleKh . ($courseTitleKh ? " • {$courseTitleKh}" : '') . ' (' . (int) ($lp->percent ?? 0) . '%)',
                    'time'       => $parsed ? $parsed->format('M j, Y · h:i A') : '—',
                    'timestamp'  => $parsed ? $parsed->toIso8601String() : '',
                ];
            }
        } catch (\Throwable $e) {
        }

        // 2. QuizAttempt
        try {
            $attempts = QuizAttempt::where('user_id', $user->id)
                ->with(['quiz.course:id,title,title_kh'])
                ->latest('submitted_at')
                ->latest('created_at')
                ->take(15)
                ->get();

            foreach ($attempts as $qa) {
                $quizTitle = $qa->quiz?->title ?? 'Quiz';
                $courseTitle = $qa->quiz?->course?->title;
                $courseTitleKh = $qa->quiz?->course?->title_kh ?: $courseTitle;
                $ts = $qa->submitted_at ?? $qa->updated_at ?? $qa->created_at;
                $parsed = $ts ? Carbon::parse($ts) : null;
                $scoreText = $qa->score !== null ? number_format((float) $qa->score, 0) . '%' : 'In Progress';

                $logs[] = [
                    'id'         => 'qa_' . $qa->id,
                    'type'       => 'quiz',
                    'action'     => $qa->passed ? 'Passed Quiz Assessment' : 'Completed Quiz Attempt',
                    'action_kh'  => $qa->passed ? 'បានប្រឡងជាប់កម្រងសំណួរ' : 'បានធ្វើកម្រងសំណួរ',
                    'detail'     => $quizTitle . ($courseTitle ? " • {$courseTitle}" : '') . " — Score: {$scoreText}",
                    'detail_kh'  => $quizTitle . ($courseTitleKh ? " • {$courseTitleKh}" : '') . " — ពិន្ទុ៖ {$scoreText}",
                    'time'       => $parsed ? $parsed->format('M j, Y · h:i A') : '—',
                    'timestamp'  => $parsed ? $parsed->toIso8601String() : '',
                ];
            }
        } catch (\Throwable $e) {
        }

        // 3. AssignmentSubmission
        try {
            $submissions = AssignmentSubmission::where('user_id', $user->id)
                ->with(['quiz:id,title', 'course:id,title,title_kh'])
                ->latest('submitted_at')
                ->latest('created_at')
                ->take(15)
                ->get();

            foreach ($submissions as $sub) {
                $title = $sub->title ?: ($sub->quiz?->title ?: 'Assignment');
                $courseTitle = $sub->course?->title;
                $courseTitleKh = $sub->course?->title_kh ?: $courseTitle;
                $ts = $sub->graded_at ?? $sub->submitted_at ?? $sub->created_at;
                $parsed = $ts ? Carbon::parse($ts) : null;
                $isGraded = $sub->status === 'graded' || $sub->score !== null;

                $logs[] = [
                    'id'         => 'sub_' . $sub->id,
                    'type'       => 'assignment',
                    'action'     => $isGraded ? 'Assignment Graded' : 'Submitted Assignment',
                    'action_kh'  => $isGraded ? 'កិច្ចការត្រូវបានដាក់ពិន្ទុ' : 'បានបញ្ជូនកិច្ចការផ្ទះ',
                    'detail'     => $title . ($courseTitle ? " • {$courseTitle}" : '') . ($sub->score !== null ? " (Score: {$sub->score})" : ''),
                    'detail_kh'  => $title . ($courseTitleKh ? " • {$courseTitleKh}" : '') . ($sub->score !== null ? " (ពិន្ទុ៖ {$sub->score})" : ''),
                    'time'       => $parsed ? $parsed->format('M j, Y · h:i A') : '—',
                    'timestamp'  => $parsed ? $parsed->toIso8601String() : '',
                ];
            }
        } catch (\Throwable $e) {
        }

        // 4. Payment
        try {
            $payments = Payment::where('student_id', $user->id)
                ->with('course:id,title,title_kh')
                ->latest()
                ->take(10)
                ->get();

            foreach ($payments as $pay) {
                $courseTitle = $pay->course?->title ?? 'Course Enrollment';
                $courseTitleKh = $pay->course?->title_kh ?: $courseTitle;
                $ts = $pay->verified_at ?? $pay->created_at;
                $parsed = $ts ? Carbon::parse($ts) : null;
                $isVerified = in_array(strtolower((string) $pay->status), ['verified', 'completed', 'paid', 'approved'], true);
                $amt = '$' . number_format((float) $pay->amount, 2);

                $logs[] = [
                    'id'         => 'pay_' . $pay->id,
                    'type'       => 'payment',
                    'action'     => $isVerified ? 'Payment Verified' : 'Payment Submitted',
                    'action_kh'  => $isVerified ? 'ការបង់ប្រាក់ត្រូវបានបញ្ជាក់' : 'បានដាក់ស្នើការបង់ប្រាក់',
                    'detail'     => "{$courseTitle} ({$amt})",
                    'detail_kh'  => "{$courseTitleKh} ({$amt})",
                    'time'       => $parsed ? $parsed->format('M j, Y · h:i A') : '—',
                    'timestamp'  => $parsed ? $parsed->toIso8601String() : '',
                ];
            }
        } catch (\Throwable $e) {
        }

        // 5. Certificate
        try {
            $certs = Certificate::where('student_id', $user->id)
                ->with('course:id,title,title_kh')
                ->latest('issued_at')
                ->latest('created_at')
                ->take(10)
                ->get();

            foreach ($certs as $cert) {
                $courseTitle = $cert->course?->title ?? 'Course';
                $courseTitleKh = $cert->course?->title_kh ?: $courseTitle;
                $ts = $cert->issued_at ?? $cert->created_at;
                $parsed = $ts ? Carbon::parse($ts) : null;

                $logs[] = [
                    'id'         => 'cert_' . $cert->id,
                    'type'       => 'certificate',
                    'action'     => 'Certificate Issued',
                    'action_kh'  => 'ទទួលបានវិញ្ញាបនបត្រ',
                    'detail'     => "{$courseTitle} (ID: {$cert->certificate_number})",
                    'detail_kh'  => "{$courseTitleKh} (លេខកូដ៖ {$cert->certificate_number})",
                    'time'       => $parsed ? $parsed->format('M j, Y · h:i A') : '—',
                    'timestamp'  => $parsed ? $parsed->toIso8601String() : '',
                ];
            }
        } catch (\Throwable $e) {
        }

        usort($logs, function ($a, $b) {
            return strcmp((string) ($b['timestamp'] ?? ''), (string) ($a['timestamp'] ?? ''));
        });

        return array_slice($logs, 0, 30);
    }

    /**
     * Resolve academic term and year without duplicating year labels.
     */
    protected function resolveAcademicTerm($user): array
    {
        $activeYear = AcademicYear::where('is_active', true)
            ->orWhere('status', 'active')
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->first();

        $activeSemester = Semester::where('is_active', true)
            ->orWhere('status', 'active')
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->first();

        // Base year string
        $yearString = $activeYear?->name
            ?: ($activeSemester?->academicYear?->name
                ?: ($user->academicYear?->name ?: ($user->academic_year ?: 'Academic Year 2026 – 2027')));

        // Extract clean year range like "2024–2025" or "2026–2027"
        $yearRange = '';
        if (preg_match('/(\d{4})\s*[-–—]\s*(\d{4})/', $yearString, $m)) {
            $yearRange = $m[1] . '–' . $m[2];
        } else {
            $yearRange = trim(str_ireplace(['Academic Year', 'ឆ្នាំសិក្សា'], '', $yearString));
        }

        // Clean semester name, stripping any trailing year if already attached (e.g. "Semester 2 — 2024-2025")
        $rawSem = $activeSemester?->semester_num ?: ($activeSemester?->name ?: '');
        if ($rawSem !== '' && str_contains($rawSem, '—')) {
            $rawSem = trim(explode('—', $rawSem)[0]);
        }
        if ($rawSem !== '' && str_contains($rawSem, '-')) {
            $rawSem = trim(explode('-', $rawSem)[0]);
        }
        if ($rawSem === '') {
            $rawSem = 'Semester 2';
        }

        $semEn = $rawSem;
        $semKm = preg_replace('/Semester\s+(\d+)/i', 'ឆមាសទី $1', $rawSem);
        $semKm = $this->toKhmerDigits($semKm);

        $yearKm = $this->toKhmerDigits($yearRange);

        $en = $yearRange !== '' ? "{$semEn} • {$yearRange}" : $semEn;
        $km = $yearRange !== '' ? "{$semKm} • ឆ្នាំសិក្សា {$yearKm}" : $semKm;

        return [
            'en'                 => $en,
            'km'                 => $km,
            'year'               => $yearRange,
            'semester'           => $semEn,
            'active_semester_id' => $activeSemester?->id,
            'active_year_id'     => $activeYear?->id,
        ];
    }

    /**
     * Convert western arabic numerals to Khmer numerals.
     */
    protected function toKhmerDigits(string $str): string
    {
        $en = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $kh = ['០', '១', '២', '៣', '៤', '៥', '៦', '៧', '៨', '៩'];
        return str_replace($en, $kh, $str);
    }
}
