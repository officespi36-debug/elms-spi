<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AssignmentSubmission;
use App\Models\Certificate;
use App\Models\LessonProgress;
use App\Models\Major;
use App\Models\Payment;
use App\Models\QuizAttempt;
use App\Models\Semester;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();
        $user->load(['major.department.faculty', 'academicYear']);

        $activeSemester = Semester::with('academicYear')
            ->where('is_active', true)
            ->first();

        $semesterLabel = null;
        if ($activeSemester) {
            $yearName = $activeSemester->academicYear?->name
                ?: ($user->academicYear?->name ?: $user->academic_year);
            $semesterLabel = $yearName
                ? ($activeSemester->name . ' • ' . $yearName)
                : $activeSemester->name;
        } elseif ($user->academicYear?->name) {
            $semesterLabel = $user->academicYear->name;
        } elseif (!empty($user->academic_year)) {
            $semesterLabel = (string) $user->academic_year;
        }

        $prefs = is_array($user->preferences) ? $user->preferences : [];
        $notifPrefs = is_array($prefs['notification_prefs'] ?? null) ? $prefs['notification_prefs'] : [];

        $language = $prefs['locale'] ?? $prefs['language'] ?? app()->getLocale() ?? 'km';
        $emailNotifications = array_key_exists('email_notifications', $prefs)
            ? (bool) $prefs['email_notifications']
            : (array_key_exists('email', $notifPrefs) ? (bool) $notifPrefs['email'] : true);
        $appNotifications = array_key_exists('app_notifications', $prefs)
            ? (bool) $prefs['app_notifications']
            : (array_key_exists('push', $notifPrefs) ? (bool) $notifPrefs['push'] : true);

        // Fallbacks for Faculty, Department, and Major when student profile is unassigned
        $faculty = $user->major?->department?->faculty?->name;
        $facultyKh = $user->major?->department?->faculty?->name_kh;
        $department = $user->major?->department?->name;
        $departmentKh = $user->major?->department?->name_kh;
        $major = $user->major?->name;
        $majorKh = $user->major?->name_kh;

        if (!$major) {
            $firstCourse = $user->enrollments()
                ->with(['course.major.department.faculty'])
                ->first()?->course;

            if ($firstCourse?->major) {
                $major = $firstCourse->major->name;
                $majorKh = $firstCourse->major->name_kh ?: $major;
                $department = $firstCourse->major->department?->name;
                $departmentKh = $firstCourse->major->department?->name_kh ?: $department;
                $faculty = $firstCourse->major->department?->faculty?->name;
                $facultyKh = $firstCourse->major->department?->faculty?->name_kh ?: $faculty;
            } else {
                $major = 'Information Technology';
                $majorKh = 'បច្ចេកវិទ្យាព័ត៌មាន (IT)';
                $department = 'Department of Information Technology';
                $departmentKh = 'ដេប៉ាតឺម៉ង់បច្ចេកវិទ្យាព័ត៌មាន';
                $faculty = 'Faculty of Science & Technology';
                $facultyKh = 'មហាវិទ្យាល័យវិទ្យាសាស្ត្រ និងបច្ចេកវិទ្យា';
            }
        }

        $availableMajors = Major::where('is_active', true)
            ->with('department.faculty')
            ->select('id', 'department_id', 'name', 'name_kh', 'code')
            ->get();

        return Inertia::render('Student/Profile/Index', [
            'profile' => [
                'id'                  => $user->id,
                'name'                => $user->name ?? '',
                'name_kh'             => $user->name_kh ?? '',
                'email'               => $user->email ?? '',
                'avatar_url'          => $user->avatar_url,
                'student_id'          => $user->student_code ?: ('STU' . str_pad((string) $user->id, 6, '0', STR_PAD_LEFT)),
                'faculty'             => $faculty,
                'faculty_kh'          => $facultyKh ?: $faculty,
                'department'          => $department,
                'department_kh'       => $departmentKh ?: $department,
                'major'               => $major,
                'major_kh'            => $majorKh ?: $major,
                'major_id'            => $user->major_id,
                'phone'               => $user->phone ?? '',
                'gender'              => $user->gender ?? 'male',
                'dob'                 => $user->dob ? Carbon::parse($user->dob)->format('Y-m-d') : '',
                'telegram_username'   => $user->telegram_username ?? '',
                'study_type'          => $user->study_type ?: 'Full-time',
                'study_type_kh'       => ($user->study_type === 'Part-time') ? 'ក្រៅម៉ោង' : 'ពេញម៉ោង',
                'degree_level'        => 'Bachelor of Science (B.Sc.)',
                'degree_level_kh'     => 'ថ្នាក់បរិញ្ញាបត្រ (B.Sc.)',
                'bio'                 => $user->bio ?? '',
                'semester'            => $semesterLabel ?: 'Semester 2 — 2024-2025 • Academic Year 2024 — 2025',
                'status'              => $user->status ?: ($user->is_active ? 'active' : 'inactive'),
                'language'            => $language,
                'email_notifications' => $emailNotifications,
                'app_notifications'   => $appNotifications,
            ],
            'availableMajors' => $availableMajors,
            'activityLogs'    => $this->buildActivityLogs($user),
        ]);
    }

    public function update(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name'                => 'required|string|max:255',
            'name_kh'             => 'nullable|string|max:255',
            'email'               => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone'               => 'nullable|string|max:50',
            'telegram_username'   => 'nullable|string|max:100',
            'gender'              => 'nullable|string|in:male,female,other',
            'dob'                 => 'nullable|date',
            'bio'                 => 'nullable|string|max:1000',
            'major_id'            => 'nullable|integer',
            'language'            => 'nullable|string|in:km,en',
            'email_notifications' => 'nullable|boolean',
            'app_notifications'   => 'nullable|boolean',
            'avatar'              => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
        ]);

        $prefs = is_array($user->preferences) ? $user->preferences : [];
        if (array_key_exists('language', $validated) && $validated['language'] !== null) {
            $prefs['locale'] = $validated['language'];
            $prefs['language'] = $validated['language'];
            if ($request->hasSession()) {
                $request->session()->put('locale', $validated['language']);
                app()->setLocale($validated['language']);
            }
        }

        if (array_key_exists('email_notifications', $validated) && $validated['email_notifications'] !== null) {
            $prefs['email_notifications'] = (bool) $validated['email_notifications'];
            $prefs['notification_prefs'] = array_merge(
                is_array($prefs['notification_prefs'] ?? null) ? $prefs['notification_prefs'] : [],
                ['email' => (bool) $validated['email_notifications']]
            );
        }

        if (array_key_exists('app_notifications', $validated) && $validated['app_notifications'] !== null) {
            $prefs['app_notifications'] = (bool) $validated['app_notifications'];
            $prefs['notification_prefs'] = array_merge(
                is_array($prefs['notification_prefs'] ?? null) ? $prefs['notification_prefs'] : [],
                ['push' => (bool) $validated['app_notifications']]
            );
        }

        // Handle Avatar File Upload if present in request
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $destinationPath = public_path('uploads/avatars');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            $file->move($destinationPath, $filename);
            $user->avatar = '/uploads/avatars/' . $filename;
        }

        $fillData = [
            'name'              => $validated['name'],
            'name_kh'           => $validated['name_kh'] ?? $user->name_kh,
            'email'             => $validated['email'],
            'phone'             => $validated['phone'] ?? null,
            'telegram_username' => $validated['telegram_username'] ?? null,
            'gender'            => $validated['gender'] ?? $user->gender,
            'dob'               => $validated['dob'] ?? $user->dob,
            'bio'               => $validated['bio'] ?? null,
            'preferences'       => $prefs,
        ];

        if (!empty($validated['major_id'])) {
            $fillData['major_id'] = (int) $validated['major_id'];
        }

        $user->fill($fillData);
        $user->save();

        return back()->with('success', 'ព័ត៌មានផ្ទាល់ខ្លួនត្រូវបានកែប្រែដោយជោគជ័យ (Profile Updated Successfully!)');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        /** @var User $user */
        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors([
                'current_password' => 'ពាក្យសម្ងាត់បច្ចុប្បន្នមិនត្រឹមត្រូវទេ (The provided current password does not match).',
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', 'កូដសម្ងាត់ត្រូវបានប្តូរដោយជោគជ័យ (Password Changed Successfully!)');
    }

    /**
     * Build authentic student activity logs from MySQL records:
     * LessonProgress, QuizAttempt, AssignmentSubmission, Payment, Certificate.
     * Returns [] when 0 records exist.
     *
     * @param User $user
     * @return array<int, array<string, mixed>>
     */
    protected function buildActivityLogs(User $user): array
    {
        $logs = [];

        // 1. LessonProgress
        try {
            $progressItems = LessonProgress::where('user_id', $user->id)
                ->with(['lesson.course:id,title,title_kh', 'lesson.module.course:id,title,title_kh'])
                ->latest('updated_at')
                ->take(10)
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
                ->take(10)
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
                ->take(10)
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

        return array_slice($logs, 0, 25);
    }
}
