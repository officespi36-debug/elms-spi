<?php

namespace App\Services;

use App\Models\AssignmentSubmission;
use App\Models\Certificate;
use App\Models\CertificateVerificationLog;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\Major;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\StudyDaily;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CertificateService
{
    /**
     * Generate standard sequential Certificate Number: ELMS-YYYY-XXXXXX
     */
    public function generateCertificateNumber(): string
    {
        $year = date('Y');
        $nextSeq = ((int) Certificate::max('id')) + 1;

        do {
            $seqString = str_pad((string) $nextSeq, 6, '0', STR_PAD_LEFT);
            $candidate = "ELMS-{$year}-{$seqString}";
            $nextSeq++;
        } while (Certificate::where('certificate_number', $candidate)->exists());

        return $candidate;
    }

    /**
     * Issue a new certificate or return existing valid/issued certificate.
     */
    public function issue(
        User $student,
        Course $course,
        ?int $templateId = null,
        ?string $grade = null,
        ?int $score = null
    ): Certificate {
        if ($score === null) {
            $quizIds = Quiz::where('course_id', $course->id)->pluck('id');
            if ($quizIds->isNotEmpty()) {
                $avgScore = QuizAttempt::where('user_id', $student->id)
                    ->whereIn('quiz_id', $quizIds)
                    ->whereNotNull('score')
                    ->avg('score');
                $score = $avgScore !== null ? (int) round((float) $avgScore) : 100;
            } else {
                $score = 100;
            }
        }

        $score = max(0, min(100, $score));

        if ($grade === null || trim($grade) === '') {
            $grade = $this->deriveGrade($score);
        }

        $existing = Certificate::where('student_id', $student->id)
            ->where('course_id', $course->id)
            ->first();

        if ($existing) {
            if (!in_array($existing->status, ['valid', 'issued'], true)) {
                $existing->update([
                    'status'            => 'valid',
                    'grade'             => $grade,
                    'score'             => $score,
                    'revoked_at'        => null,
                    'revoked_by'        => null,
                    'revocation_reason' => null,
                    'revocation_note'   => null,
                ]);
            }
            return $existing;
        }

        $certNumber = $this->generateCertificateNumber();
        $verifyCode = strtoupper(Str::random(10));

        return Certificate::create([
            'student_id'         => $student->id,
            'course_id'          => $course->id,
            'major_id'           => $course->major_id ?? $student->major_id,
            'template_id'        => $templateId,
            'certificate_number' => $certNumber,
            'verification_code'  => $verifyCode,
            'grade'              => $grade,
            'score'             => $score,
            'status'             => 'valid',
            'issued_at'          => now(),
            'audit_trail'        => [
                [
                    'action'    => 'issued',
                    'by'        => auth()->user()?->name ?? 'System',
                    'timestamp' => now()->toIso8601String(),
                    'details'   => 'Certificate generated and registered.',
                ],
            ],
        ]);
    }

    /**
     * Derive letter grade from numeric score.
     */
    public function deriveGrade(int|float $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            default      => 'P',
        };
    }

    /**
     * Verify certificate or exact student_code and log verification attempt.
     */
    public function logVerification(?string $codeOrId, ?string $ipAddress = '127.0.0.1', string $source = 'manual_id'): array
    {
        $cleanCode = $this->normalizeQueryCode($codeOrId);

        if ($cleanCode === '') {
            return [
                'status' => 'idle',
                'query'  => '',
            ];
        }

        // 1. Exact match on Certificate (certificate_number or verification_code)
        $cert = Certificate::with(['student.major.department.faculty', 'course.major', 'major.department.faculty', 'template'])
            ->where(function ($q) use ($cleanCode) {
                $q->where('verification_code', $cleanCode)
                  ->orWhere('certificate_number', $cleanCode);
            })
            ->first();

        if ($cert) {
            $isRevoked = ($cert->status === 'revoked');
            $resultStatus = in_array($cert->status, ['valid', 'issued'], true) ? 'valid' : ($isRevoked ? 'revoked' : 'not_found');

            $cert->increment('verifications_count');
            $cert->update(['last_verified_at' => now()]);

            CertificateVerificationLog::create([
                'certificate_id'     => $cert->id,
                'certificate_number' => $cert->certificate_number,
                'result'             => $resultStatus,
                'ip_address'         => $ipAddress,
                'location'           => null,
                'source'             => $source,
            ]);

            $major = $cert->major ?? $cert->course?->major ?? $cert->student?->major;
            $faculty = $major?->department?->faculty;

            return [
                'status'             => $resultStatus,
                'is_student_id'      => false,
                'certificate'        => $cert,
                'student_name'       => $cert->student?->name,
                'student_name_kh'    => $cert->student?->name_kh,
                'student_id'         => $cert->student?->student_code,
                'course_name'        => $cert->course?->title,
                'major_name'         => $major?->name,
                'faculty_name'       => $faculty?->name,
                'grade'              => $cert->grade,
                'score'              => $cert->score,
                'issued_at'          => $cert->issued_at?->format('d F Y'),
                'certificate_number' => $cert->certificate_number,
                'revoked_at'         => $cert->revoked_at?->format('d F Y'),
                'revocation_reason'  => $isRevoked
                    ? ($cert->show_reason_publicly && $cert->revocation_reason ? $cert->revocation_reason : 'Revoked by institution policy')
                    : null,
                'status_label'       => $isRevoked ? 'Revoked' : 'Valid Certificate',
                'institution'        => 'Saint Paul Institute (វិទ្យាស្ថាន សន្តប៉ូល)',
            ];
        }

        // 2. Exact match on User student_code only
        $student = User::whereNotNull('student_code')
            ->where('student_code', '!=', '')
            ->where('student_code', $cleanCode)
            ->with(['major.department.faculty'])
            ->first();

        if ($student) {
            $enrollment = Enrollment::where('student_id', $student->id)
                ->with('course')
                ->latest()
                ->first();
            $major = $student->major;
            $faculty = $major?->department?->faculty;

            CertificateVerificationLog::create([
                'certificate_number' => $student->student_code,
                'result'             => 'valid',
                'ip_address'         => $ipAddress,
                'location'           => null,
                'source'             => 'student_id_scan',
            ]);

            return [
                'status'             => 'valid',
                'is_student_id'      => true,
                'student_name'       => $student->name,
                'student_name_kh'    => $student->name_kh,
                'student_id'         => $student->student_code,
                'course_name'        => $enrollment?->course?->title ?? $major?->name,
                'major_name'         => $major?->name,
                'faculty_name'       => $faculty?->name,
                'grade'              => null,
                'score'              => null,
                'issued_at'          => $student->created_at?->format('d F Y'),
                'certificate_number' => $student->student_code,
                'revoked_at'         => null,
                'revocation_reason'  => null,
                'status_label'       => 'Active Enrolled Student',
                'institution'        => 'Saint Paul Institute (វិទ្យាស្ថាន សន្តប៉ូល)',
            ];
        }

        // 3. Not found
        CertificateVerificationLog::create([
            'certificate_number' => $cleanCode,
            'result'             => 'not_found',
            'ip_address'         => $ipAddress,
            'location'           => null,
            'source'             => $source,
        ]);

        return [
            'status'             => 'not_found',
            'certificate_number' => $cleanCode,
            'message'            => 'Certificate or Student ID not found in official registry.',
        ];
    }

    /**
     * Alias for getMyCertificatesData.
     */
    public function getEarnedCertificatesData(?User $user = null): array
    {
        return $this->getMyCertificatesData($user);
    }

    /**
     * Get structured My Certificates dashboard data from real Eloquent records.
     */
    public function getMyCertificatesData(
        ?User $user = null,
        string $status = 'all',
        string $category = 'all',
        string $course = 'all',
        string $issuer = 'all',
        string $search = '',
        string $sort = 'newest'
    ): array {
        if (!$user) {
            return $this->emptyMyCertificatesData();
        }

        $allCerts = Certificate::where('student_id', $user->id)
            ->with(['student', 'course.teacher', 'course.major', 'major'])
            ->get();

        $enrollments = Enrollment::where('student_id', $user->id)
            ->with([
                'course.modules' => fn ($q) => $q->orderBy('order'),
                'course.modules.lessons' => fn ($q) => $q->orderBy('order'),
                'course.lessons' => fn ($q) => $q->orderBy('order'),
            ])
            ->get();

        $progressService = app(CourseProgressService::class);
        $enrollmentProgress = $enrollments->isNotEmpty()
            ? $progressService->calculateForEnrollments($user, $enrollments)
            : collect();

        $validCerts = $allCerts->filter(fn (Certificate $c) => in_array($c->status, ['valid', 'issued'], true));
        $revokedCerts = $allCerts->filter(fn (Certificate $c) => $c->status === 'revoked');

        $earnedCount = $validCerts->count();
        $revokedCount = $revokedCerts->count();

        $completedCoursesCount = $enrollmentProgress->filter(function (array $p) {
            return ($p['state'] ?? '') === 'completed' || ($p['enrollment']->status ?? '') === 'completed';
        })->count();

        $currentYear = (int) now()->year;
        $thisYearCount = $validCerts->filter(function (Certificate $c) use ($currentYear) {
            $dt = $c->issued_at ?? $c->created_at;
            return $dt && (int) $dt->year === $currentYear;
        })->count();

        $latestValidCert = $validCerts
            ->sortByDesc(fn (Certificate $c) => ($c->issued_at ?? $c->created_at)?->getTimestamp() ?? 0)
            ->first();

        $recentDate = $latestValidCert
            ? (($latestValidCert->issued_at ?? $latestValidCert->created_at)?->format('M j, Y') ?? '—')
            : '—';

        $totalLearningTime = $this->calculateStudentLearningTime($user->id);

        $summary = [
            'total_certificates'  => $earnedCount,
            'total_note'          => 'All time earned',
            'completed_courses'   => $completedCoursesCount,
            'completed_note'      => 'Completed courses',
            'this_year'           => $thisYearCount,
            'this_year_note'      => 'Certificates earned',
            'recent_date'         => $recentDate,
            'recent_note'         => $earnedCount > 0 ? 'Most recent' : 'No certificates yet',
            'total_learning_time' => $totalLearningTime,
            'time_note'           => 'Across all courses',
        ];

        // Compute real statistics donut (Earned, In Progress, Revoked, Not Started)
        $certCourseIds = $allCerts->pluck('course_id')->filter()->unique()->all();
        $inProgressCount = 0;
        $notStartedCount = 0;

        foreach ($enrollmentProgress as $prog) {
            $enrCourseId = $prog['course']?->id ?? null;
            if ($enrCourseId && in_array($enrCourseId, $certCourseIds, true)) {
                continue;
            }
            $pct = (int) ($prog['percent'] ?? 0);
            if ($pct > 0) {
                $inProgressCount++;
            } else {
                $notStartedCount++;
            }
        }

        $statsTotal = $earnedCount + $inProgressCount + $revokedCount + $notStartedCount;
        $pcts = $this->computePercentages([$earnedCount, $inProgressCount, $revokedCount, $notStartedCount], $statsTotal);

        $statistics = [
            'total' => $statsTotal,
            'items' => [
                ['label' => 'Earned',      'count' => $earnedCount,     'percentage' => $pcts[0], 'color' => '#10B981'],
                ['label' => 'In Progress', 'count' => $inProgressCount, 'percentage' => $pcts[1], 'color' => '#3B82F6'],
                ['label' => 'Revoked',     'count' => $revokedCount,    'percentage' => $pcts[2], 'color' => '#EF4444'],
                ['label' => 'Not Started', 'count' => $notStartedCount, 'percentage' => $pcts[3], 'color' => '#F59E0B'],
            ],
        ];

        // Compute real recent_achievements ([] when 0)
        $recentAchievements = [];
        $sortedValid = $validCerts
            ->sortByDesc(fn (Certificate $c) => ($c->issued_at ?? $c->created_at)?->getTimestamp() ?? 0)
            ->values();

        $achStyles = [
            ['icon' => '🏆', 'icon_bg' => 'bg-amber-500/20 text-amber-300 border border-amber-500/30'],
            ['icon' => '🎖️', 'icon_bg' => 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'],
            ['icon' => '🛡️', 'icon_bg' => 'bg-blue-500/20 text-blue-300 border border-blue-500/30'],
            ['icon' => '🎓', 'icon_bg' => 'bg-purple-500/20 text-purple-300 border border-purple-500/30'],
        ];

        foreach ($sortedValid->take(4) as $idx => $cert) {
            $style = $achStyles[$idx % count($achStyles)];
            $dt = $cert->issued_at ?? $cert->created_at;
            $scorePart = $cert->score !== null ? ' · ' . round((float) $cert->score) . '%' : '';
            $recentAchievements[] = [
                'id'          => $cert->id,
                'title'       => $cert->course?->title ?? 'Certificate Earned',
                'description' => 'Grade ' . ($cert->grade ?? 'P') . $scorePart,
                'date'        => $dt ? $dt->format('M j, Y') : '—',
                'icon'        => $style['icon'],
                'icon_bg'     => $style['icon_bg'],
            ];
        }

        if (empty($recentAchievements) && $completedCoursesCount > 0) {
            $latestCompletedEnr = $enrollments
                ->filter(fn (Enrollment $e) => $e->status === 'completed')
                ->sortByDesc(fn (Enrollment $e) => $e->updated_at?->getTimestamp() ?? 0)
                ->first();

            $recentAchievements[] = [
                'id'          => $latestCompletedEnr?->id ?? 1,
                'title'       => $latestCompletedEnr?->course?->title ?? 'Course Completed',
                'description' => "Completed {$completedCoursesCount} " . ($completedCoursesCount === 1 ? 'course' : 'courses'),
                'date'        => $latestCompletedEnr?->updated_at?->format('M j, Y') ?? now()->format('M j, Y'),
                'icon'        => '🎓',
                'icon_bg'     => 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30',
            ];
        }

        // Dynamic filter options from student's real certificates
        $categoryOptions = $allCerts
            ->map(fn (Certificate $c) => $c->major?->name ?? $c->course?->major?->name)
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        $courseOptions = $allCerts
            ->map(fn (Certificate $c) => $c->course ? ['id' => $c->course->id, 'title' => $c->course->title] : null)
            ->filter()
            ->unique('id')
            ->sortBy('title')
            ->values()
            ->all();

        $issuerOptions = $allCerts->isNotEmpty() ? ['Saint Paul Institute'] : [];

        // Filtered query for gallery
        $filteredQuery = Certificate::where('student_id', $user->id)
            ->with(['student', 'course.teacher', 'course.major', 'major']);

        $normalizedStatus = strtolower(trim($status));
        if (in_array($normalizedStatus, ['valid', 'verified', 'issued'], true)) {
            $filteredQuery->whereIn('status', ['valid', 'issued']);
        } elseif (in_array($normalizedStatus, ['revoked', 'expired'], true)) {
            $filteredQuery->where('status', 'revoked');
        }

        if ($category !== 'all' && trim($category) !== '') {
            $catVal = trim($category);
            $filteredQuery->where(function ($q) use ($catVal) {
                if (ctype_digit($catVal)) {
                    $q->where('major_id', (int) $catVal)
                      ->orWhereHas('course', fn ($cq) => $cq->where('major_id', (int) $catVal));
                } else {
                    $q->whereHas('major', fn ($mq) => $mq->where('name', $catVal))
                      ->orWhereHas('course.major', fn ($mq) => $mq->where('name', $catVal));
                }
            });
        }

        if ($course !== 'all' && trim($course) !== '') {
            $courseVal = trim($course);
            if (ctype_digit($courseVal)) {
                $filteredQuery->where('course_id', (int) $courseVal);
            } else {
                $filteredQuery->whereHas('course', fn ($cq) => $cq->where('title', $courseVal));
            }
        }

        if (trim($search) !== '') {
            $term = '%' . trim($search) . '%';
            $filteredQuery->where(function ($q) use ($term) {
                $q->where('certificate_number', 'like', $term)
                  ->orWhere('verification_code', 'like', $term)
                  ->orWhereHas('course', fn ($cq) => $cq->where('title', 'like', $term)->orWhere('title_kh', 'like', $term))
                  ->orWhereHas('major', fn ($mq) => $mq->where('name', 'like', $term));
            });
        }

        if ($sort === 'oldest') {
            $filteredQuery->orderBy('issued_at', 'asc')->orderBy('id', 'asc');
        } elseif ($sort === 'score') {
            $filteredQuery->orderByDesc('score')->orderByDesc('issued_at');
        } else {
            $filteredQuery->orderByDesc('issued_at')->orderByDesc('id');
        }

        $filteredCerts = $filteredQuery->get();

        $palettes = [
            ['accent' => 'purple',  'border' => 'from-purple-500/40 to-indigo-500/40', 'badge' => 'bg-purple-600/20 text-purple-300 border-purple-500/30',   'seal' => '#8B5CF6'],
            ['accent' => 'blue',    'border' => 'from-cyan-500/40 to-blue-500/40',     'badge' => 'bg-blue-600/20 text-blue-300 border-blue-500/30',         'seal' => '#3B82F6'],
            ['accent' => 'emerald', 'border' => 'from-emerald-500/40 to-teal-500/40',  'badge' => 'bg-emerald-600/20 text-emerald-300 border-emerald-500/30', 'seal' => '#10B981'],
            ['accent' => 'amber',   'border' => 'from-amber-500/40 to-orange-500/40',  'badge' => 'bg-amber-600/20 text-amber-300 border-amber-500/30',       'seal' => '#F59E0B'],
        ];

        $certificates = $filteredCerts->values()->map(function (Certificate $cert, int $index) use ($user, $palettes) {
            $palette = $palettes[$index % count($palettes)];
            $isRevoked = ($cert->status === 'revoked');
            $dt = $cert->issued_at ?? $cert->created_at;
            $rawDate = $dt ? $dt->format('M j, Y') : '—';
            $verifyUrl = url("/verify/{$cert->certificate_number}");
            $categoryName = $cert->major?->name ?? $cert->course?->major?->name ?? 'General';

            return [
                'id'                => $cert->id,
                'course_id'         => $cert->course_id,
                'title'             => $cert->course?->title ?? 'Course',
                'title_kh'          => $cert->course?->title_kh,
                'student_name'      => $cert->student?->name ?? $user->name,
                'issuer'            => 'Saint Paul Institute',
                'teacher_name'      => $cert->course?->teacher?->name,
                'issued_date'       => $dt ? 'Issued on ' . $dt->format('M j, Y') : '—',
                'raw_date'          => $rawDate,
                'status'            => $isRevoked ? 'Revoked' : 'Valid',
                'status_type'       => $isRevoked ? 'revoked' : 'valid',
                'cert_number'       => $cert->certificate_number,
                'verification_code' => $cert->verification_code,
                'download_url'      => "/certificate/download/{$cert->id}",
                'verify_url'        => $verifyUrl,
                'qr_url'            => $verifyUrl,
                'accent_color'      => $palette['accent'],
                'border_class'      => $palette['border'],
                'badge_color'       => $palette['badge'],
                'seal_color'        => $palette['seal'],
                'category'          => $categoryName,
                'grade'             => $cert->grade ?? 'P',
                'score'             => $cert->score !== null ? round((float) $cert->score) . '%' : '—',
            ];
        })->all();

        return [
            'summary'             => $summary,
            'statistics'          => $statistics,
            'recent_achievements' => $recentAchievements,
            'certificates'        => $certificates,
            'total_count'         => count($certificates),
            'filter_options'      => [
                'categories' => $categoryOptions,
                'courses'    => $courseOptions,
                'issuers'    => $issuerOptions,
            ],
        ];
    }

    /**
     * Get structured Available Certificates dashboard data from real Eloquent records.
     */
    public function getAvailableCertificatesData(
        ?User $user = null,
        string $status = 'all',
        string $category = 'all',
        string $course = 'all',
        string $level = 'all',
        string $sort = 'progress',
        string $search = '',
        int $page = 1,
        int $perPage = 8
    ): array {
        $enrolledRows = $user
            ? Enrollment::where('student_id', $user->id)->get()->keyBy('course_id')
            : collect();
        $enrolledCourseIds = $enrolledRows->keys()->all();

        $earnedCerts = $user
            ? Certificate::where('student_id', $user->id)
                ->whereIn('status', ['valid', 'issued'])
                ->get()
            : collect();

        $earnedByCourse = $earnedCerts->keyBy('course_id');
        $currentYear = (int) now()->year;
        $earnedThisYear = $earnedCerts->filter(function (Certificate $c) use ($currentYear) {
            $dt = $c->issued_at ?? $c->created_at;
            return $dt && (int) $dt->year === $currentYear;
        })->count();

        $courses = Course::with([
            'major',
            'teacher',
            'modules' => fn ($q) => $q->orderBy('order'),
            'modules.lessons' => fn ($q) => $q->orderBy('order'),
            'lessons' => fn ($q) => $q->orderBy('order'),
            'quizzes',
        ])->where(function ($q) use ($enrolledCourseIds) {
            $q->where('status', 'published');
            if (!empty($enrolledCourseIds)) {
                $q->orWhereIn('id', $enrolledCourseIds);
            }
        })->get();

        // Exclude courses where the student already holds a valid/issued certificate
        $availableCourses = $courses
            ->filter(fn (Course $c) => !$earnedByCourse->has($c->id))
            ->values();

        if ($availableCourses->isEmpty()) {
            return [
                'summary' => [
                    'total_available'  => 0,
                    'total_note'       => 'Certificates available',
                    'in_progress'      => 0,
                    'in_progress_note' => 'You are working on',
                    'almost_there'     => 0,
                    'almost_note'      => '80% or more completed',
                    'not_started'      => 0,
                    'not_started_note' => 'Ready to begin',
                    'earned_this_year' => $earnedThisYear,
                    'earned_note'      => 'Certificates earned',
                ],
                'progress_overview' => [
                    'total' => 0,
                    'items' => [
                        ['label' => 'In Progress',  'count' => 0, 'percentage' => 0, 'color' => '#3B82F6'],
                        ['label' => 'Almost There', 'count' => 0, 'percentage' => 0, 'color' => '#10B981'],
                        ['label' => 'Not Started',  'count' => 0, 'percentage' => 0, 'color' => '#F59E0B'],
                    ],
                ],
                'popular_categories' => [],
                'certificates'       => [],
                'total_count'        => 0,
                'current_page'       => 1,
                'per_page'           => $perPage,
                'last_page'          => 1,
                'filter_options'     => [
                    'categories' => [],
                    'courses'    => [],
                    'levels'     => [],
                ],
            ];
        }

        // Batch-load LessonProgress, QuizAttempt, and AssignmentSubmission for zero N+1 queries
        $allLessonIds = collect();
        $allQuizIds = collect();

        foreach ($availableCourses as $c) {
            if ($c->modules && $c->modules->isNotEmpty()) {
                foreach ($c->modules as $mod) {
                    if ($mod->lessons) {
                        $allLessonIds = $allLessonIds->merge($mod->lessons->pluck('id'));
                    }
                }
            }
            if ($c->lessons && $c->lessons->isNotEmpty()) {
                $allLessonIds = $allLessonIds->merge($c->lessons->pluck('id'));
            }
            if ($c->quizzes && $c->quizzes->isNotEmpty()) {
                $allQuizIds = $allQuizIds->merge($c->quizzes->pluck('id'));
            }
        }

        $uniqueLessonIds = $allLessonIds->unique()->filter()->values()->all();
        $uniqueQuizIds = $allQuizIds->unique()->filter()->values()->all();

        $lessonProgressByLesson = ($user && !empty($uniqueLessonIds))
            ? LessonProgress::where('user_id', $user->id)
                ->whereIn('lesson_id', $uniqueLessonIds)
                ->get()
                ->keyBy('lesson_id')
            : collect();

        $quizAttemptsByQuiz = ($user && !empty($uniqueQuizIds))
            ? QuizAttempt::where('user_id', $user->id)
                ->whereIn('quiz_id', $uniqueQuizIds)
                ->get()
                ->groupBy('quiz_id')
            : collect();

        $submissionsByQuiz = ($user && !empty($uniqueQuizIds) && Schema::hasTable('assignment_submissions'))
            ? AssignmentSubmission::where('user_id', $user->id)
                ->whereIn('quiz_id', $uniqueQuizIds)
                ->get()
                ->groupBy('quiz_id')
            : collect();

        $allItems = [];
        $inProgressCount = 0;
        $almostThereCount = 0;
        $notStartedCount = 0;

        foreach ($availableCourses as $c) {
            $enrollment = $enrolledRows->get($c->id);

            // Collect ordered lessons for this course
            $courseLessons = collect();
            if ($c->modules && $c->modules->isNotEmpty()) {
                foreach ($c->modules->sortBy('order') as $mod) {
                    if ($mod->lessons) {
                        foreach ($mod->lessons->sortBy('order') as $l) {
                            $courseLessons->push($l);
                        }
                    }
                }
            }
            if ($courseLessons->isEmpty() && $c->lessons) {
                $courseLessons = $c->lessons->sortBy('order')->values();
            }

            $totalLessons = $courseLessons->count();
            $doneLessons = 0;
            $watchedSum = 0;
            $anyWatched = false;

            foreach ($courseLessons as $lesson) {
                $lp = $lessonProgressByLesson->get($lesson->id);
                if ($lp) {
                    $isDone = ((int) $lp->percent >= 90 || !empty($lp->completed_at));
                    if ($isDone) {
                        $doneLessons++;
                        $watchedSum += 100;
                    } elseif ((int) $lp->percent > 0) {
                        $watchedSum += min(99, (int) $lp->percent);
                    } elseif ((int) $lp->seconds_watched > 0) {
                        $watchedSum += 5;
                    }
                    if ((int) $lp->percent > 0 || (int) $lp->seconds_watched > 0) {
                        $anyWatched = true;
                    }
                }
            }

            if ($enrollment && $enrollment->status === 'completed') {
                $progress = 100;
            } elseif ($totalLessons > 0 && $doneLessons >= $totalLessons) {
                $progress = 100;
            } elseif ($totalLessons > 0 && ($doneLessons > 0 || $anyWatched)) {
                $progress = max(1, min(99, (int) round($watchedSum / $totalLessons)));
            } else {
                $progress = 0;
            }

            if ($progress >= 80) {
                $statusType = 'almost_there';
                $almostThereCount++;
                $badgeColor = 'bg-emerald-600 text-white';
                $barColor = 'bg-emerald-500';
            } elseif ($progress > 0) {
                $statusType = 'in_progress';
                $inProgressCount++;
                $badgeColor = 'bg-blue-600 text-white';
                $barColor = 'bg-blue-500';
            } else {
                $statusType = 'not_started';
                $notStartedCount++;
                $badgeColor = 'bg-slate-700 text-white';
                $barColor = 'bg-slate-500';
            }

            // Build real requirements checklist
            $lessonsPct = $totalLessons > 0 ? (int) round(($doneLessons / $totalLessons) * 100) : 0;
            $requirements = [
                [
                    'label' => 'Lessons Completed',
                    'value' => "{$doneLessons} / {$totalLessons} ({$lessonsPct}%)",
                    'done'  => $totalLessons > 0 && $doneLessons >= $totalLessons,
                ],
            ];

            $courseQuizzes = $c->quizzes ?? collect();
            $regularQuizzes = $courseQuizzes->filter(fn ($q) => ($q->type ?? '') !== 'assignment');
            $assignmentQuizzes = $courseQuizzes->filter(fn ($q) => ($q->type ?? '') === 'assignment');

            $totalRegularQuizzes = $regularQuizzes->count();
            if ($totalRegularQuizzes > 0) {
                $passedQuizzes = $regularQuizzes->filter(function ($quiz) use ($quizAttemptsByQuiz) {
                    $attempts = $quizAttemptsByQuiz->get($quiz->id, collect());
                    $passingScore = (float) ($quiz->passing_score ?? 70);
                    return $attempts->contains(fn ($att) => (bool) $att->passed || ((float) ($att->score ?? 0) >= $passingScore));
                })->count();
                $quizPct = (int) round(($passedQuizzes / $totalRegularQuizzes) * 100);
                $requirements[] = [
                    'label' => 'Quizzes Passed',
                    'value' => "{$passedQuizzes} / {$totalRegularQuizzes} ({$quizPct}%)",
                    'done'  => $passedQuizzes >= $totalRegularQuizzes,
                ];
            } else {
                $requirements[] = [
                    'label' => 'Quizzes Passed',
                    'value' => 'No quizzes required',
                    'done'  => true,
                ];
            }

            $totalAssignments = $assignmentQuizzes->count();
            if ($totalAssignments > 0) {
                $submittedAssignments = $assignmentQuizzes->filter(function ($aq) use ($submissionsByQuiz) {
                    return $submissionsByQuiz->get($aq->id, collect())->isNotEmpty();
                })->count();
                $assignPct = (int) round(($submittedAssignments / $totalAssignments) * 100);
                $requirements[] = [
                    'label' => 'Assignments Submitted',
                    'value' => "{$submittedAssignments} / {$totalAssignments} ({$assignPct}%)",
                    'done'  => $submittedAssignments >= $totalAssignments,
                ];
            }

            $categoryName = $c->major?->name ?? 'General';
            $levelLabel = $c->level ? ucfirst(strtolower(trim($c->level))) : 'All Levels';
            $isEnrolled = (bool) $enrollment;

            $allItems[] = [
                'id'           => $c->id,
                'title'        => $c->title,
                'title_kh'     => $c->title_kh,
                'issuer'       => $c->teacher?->name ? "{$c->teacher->name} · Saint Paul Institute" : 'Saint Paul Institute',
                'description'  => $c->description ?: 'Complete all lessons and assessments in this course to earn an official certificate.',
                'progress'     => $progress,
                'level'        => $levelLabel,
                'category'     => $categoryName,
                'major_id'     => $c->major_id,
                'status_type'  => $statusType,
                'badge_color'  => $badgeColor,
                'bar_color'    => $barColor,
                'is_enrolled'  => $isEnrolled,
                'course_url'   => $isEnrolled ? "/student/courses/{$c->id}/overview" : "/student/browse/{$c->id}",
                'requirements' => $requirements,
            ];
        }

        $totalAvailable = count($allItems);
        $overviewPcts = $this->computePercentages([$inProgressCount, $almostThereCount, $notStartedCount], $totalAvailable);

        $summary = [
            'total_available'  => $totalAvailable,
            'total_note'       => 'Certificates available',
            'in_progress'      => $inProgressCount,
            'in_progress_note' => 'You are working on',
            'almost_there'     => $almostThereCount,
            'almost_note'      => '80% or more completed',
            'not_started'      => $notStartedCount,
            'not_started_note' => 'Ready to begin',
            'earned_this_year' => $earnedThisYear,
            'earned_note'      => 'Certificates earned',
        ];

        $progressOverview = [
            'total' => $totalAvailable,
            'items' => [
                ['label' => 'In Progress',  'count' => $inProgressCount,  'percentage' => $overviewPcts[0], 'color' => '#3B82F6'],
                ['label' => 'Almost There', 'count' => $almostThereCount, 'percentage' => $overviewPcts[1], 'color' => '#10B981'],
                ['label' => 'Not Started',  'count' => $notStartedCount,  'percentage' => $overviewPcts[2], 'color' => '#F59E0B'],
            ],
        ];

        // Popular categories from real Majors associated with available courses
        $catColors = [
            'bg-purple-600/20 text-purple-300 border-purple-500/30',
            'bg-blue-600/20 text-blue-300 border-blue-500/30',
            'bg-emerald-600/20 text-emerald-300 border-emerald-500/30',
            'bg-amber-600/20 text-amber-300 border-amber-500/30',
            'bg-rose-600/20 text-rose-300 border-rose-500/30',
        ];

        $majorIdsInCourses = collect($allItems)->pluck('major_id')->filter()->unique()->values()->all();
        $majorsById = !empty($majorIdsInCourses)
            ? Major::whereIn('id', $majorIdsInCourses)->get()->keyBy('id')
            : collect();

        $groupedCategories = collect($allItems)
            ->groupBy('category')
            ->map(fn ($group, $name) => [
                'name'     => $name,
                'major_id' => $group->first()['major_id'] ?? null,
                'count'    => $group->count(),
            ])
            ->sortByDesc('count')
            ->values();

        $popularCategories = $groupedCategories->take(6)->values()->map(function ($cat, $idx) use ($catColors, $majorsById) {
            $major = $cat['major_id'] ? $majorsById->get($cat['major_id']) : null;
            return [
                'id'      => $major?->id ?? ($idx + 1),
                'name'    => $major?->name ?? $cat['name'],
                'name_kh' => $major?->name_kh,
                'count'   => $cat['count'],
                'color'   => $catColors[$idx % count($catColors)],
            ];
        })->all();

        $categoryOptions = $groupedCategories->pluck('name')->sort()->values()->all();
        $courseOptions = collect($allItems)
            ->map(fn ($item) => ['id' => $item['id'], 'title' => $item['title']])
            ->sortBy('title')
            ->values()
            ->all();
        $levelOptions = collect($allItems)
            ->pluck('level')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();

        // Apply filters
        $filtered = $allItems;

        if ($status !== 'all' && trim($status) !== '') {
            $st = strtolower(trim($status));
            $filtered = array_values(array_filter($filtered, fn ($item) => strtolower($item['status_type']) === $st));
        }

        if ($category !== 'all' && trim($category) !== '') {
            $cat = strtolower(trim($category));
            $filtered = array_values(array_filter($filtered, fn ($item) => strtolower($item['category']) === $cat || (string) ($item['major_id'] ?? '') === $cat));
        }

        if ($course !== 'all' && trim($course) !== '') {
            $crs = strtolower(trim($course));
            $filtered = array_values(array_filter($filtered, fn ($item) => strtolower($item['title']) === $crs || (string) $item['id'] === $crs));
        }

        if ($level !== 'all' && trim($level) !== '') {
            $lvl = strtolower(trim($level));
            $filtered = array_values(array_filter($filtered, fn ($item) => strtolower($item['level']) === $lvl));
        }

        if (trim($search) !== '') {
            $s = strtolower(trim($search));
            $filtered = array_values(array_filter($filtered, function ($item) use ($s) {
                return str_contains(strtolower($item['title']), $s)
                    || str_contains(strtolower((string) ($item['title_kh'] ?? '')), $s)
                    || str_contains(strtolower($item['description']), $s)
                    || str_contains(strtolower($item['category']), $s);
            }));
        }

        // Sort
        if ($sort === 'title') {
            usort($filtered, fn ($a, $b) => strcasecmp($a['title'], $b['title']));
        } else {
            usort($filtered, fn ($a, $b) => ($b['progress'] <=> $a['progress']) ?: strcasecmp($a['title'], $b['title']));
        }

        // Real pagination
        $filteredCount = count($filtered);
        $perPage = max(1, $perPage);
        $lastPage = max(1, (int) ceil($filteredCount / $perPage));
        $currentPage = max(1, min($page, $lastPage));
        $pagedCertificates = array_slice($filtered, ($currentPage - 1) * $perPage, $perPage);

        return [
            'summary'            => $summary,
            'progress_overview'  => $progressOverview,
            'popular_categories' => $popularCategories,
            'certificates'       => $pagedCertificates,
            'total_count'        => $filteredCount,
            'current_page'       => $currentPage,
            'per_page'           => $perPage,
            'last_page'          => $lastPage,
            'filter_options'     => [
                'categories' => $categoryOptions,
                'courses'    => $courseOptions,
                'levels'     => $levelOptions,
            ],
        ];
    }

    /**
     * Get structured Certificate Verification data from real Eloquent records.
     */
    public function getVerificationPageData(?string $query = null, ?string $source = 'manual'): array
    {
        $cleanQuery = $this->normalizeQueryCode($query);

        if ($cleanQuery === '') {
            return [
                'status'  => 'idle',
                'query'   => '',
                'history' => [],
            ];
        }

        // 1. Exact match on Certificate
        $cert = Certificate::with([
            'student',
            'course.modules' => fn ($q) => $q->orderBy('order'),
            'course.teacher',
            'course.major',
            'major',
            'template',
        ])->where(function ($q) use ($cleanQuery) {
            $q->where('verification_code', $cleanQuery)
              ->orWhere('certificate_number', $cleanQuery);
        })->first();

        if ($cert) {
            $isRevoked = ($cert->status === 'revoked');
            $status = in_array($cert->status, ['valid', 'issued'], true) ? 'valid' : ($isRevoked ? 'revoked' : 'not_found');

            $cert->increment('verifications_count');
            $cert->update(['last_verified_at' => now()]);

            CertificateVerificationLog::create([
                'certificate_id'     => $cert->id,
                'certificate_number' => $cert->certificate_number,
                'result'             => $status,
                'ip_address'         => request()->ip(),
                'location'           => null,
                'source'             => $source ?? 'manual',
            ]);

            $logs = CertificateVerificationLog::where('certificate_id', $cert->id)
                ->latest()
                ->take(5)
                ->get()
                ->map(fn (CertificateVerificationLog $log) => [
                    'date'        => $log->created_at ? $log->created_at->format('F j, Y \a\t h:i A') : '—',
                    'verified_by' => $log->source === 'student_panel' ? 'Student Portal' : 'Public Registry',
                    'ip_address'  => $this->maskIpAddress($log->ip_address),
                    'location'    => $log->location ?: '—',
                    'result'      => strtoupper((string) $log->result),
                ])
                ->all();

            $topics = $cert->course?->modules
                ? $cert->course->modules->pluck('title')->filter()->values()->all()
                : [];

            $teacherName = $cert->course?->teacher?->name ?: 'Academic Registrar';
            $teacherTitle = $cert->course?->teacher ? 'Course Instructor' : 'Saint Paul Institute';
            $issuedFormatted = $cert->issued_at ? $cert->issued_at->format('F j, Y') : '—';

            $canDownload = !$isRevoked
                && auth()->check()
                && ((int) auth()->id() === (int) $cert->student_id || auth()->user()?->role === 'admin');

            return [
                'id'                 => $cert->id,
                'status'             => $status,
                'query'              => $cleanQuery,
                'is_student_id'      => false,
                'certificate_id'     => $cert->certificate_number,
                'verification_code'  => $cert->verification_code,
                'student_name'       => $cert->student?->name ?? '—',
                'student_name_kh'    => $cert->student?->name_kh,
                'course_name'        => $cert->course?->title ?? '—',
                'course_description' => $cert->course?->description,
                'major_name'         => ($cert->major ?? $cert->course?->major)?->name,
                'issuer'             => 'Saint Paul Institute',
                'completion_date'    => $issuedFormatted,
                'issue_date'         => $issuedFormatted,
                'final_score'        => $cert->score !== null ? round((float) $cert->score) . '%' : '—',
                'grade'              => $cert->grade ?? '—',
                'certificate_status' => $isRevoked ? 'Revoked' : 'Valid',
                'revoked_at'         => $cert->revoked_at?->format('F j, Y'),
                'revocation_reason'  => $isRevoked
                    ? ($cert->show_reason_publicly && $cert->revocation_reason ? $cert->revocation_reason : 'Revoked by institution policy.')
                    : null,
                'message'            => $isRevoked
                    ? ($cert->show_reason_publicly && $cert->revocation_reason ? $cert->revocation_reason : 'This certificate has been officially revoked by Saint Paul Institute.')
                    : null,
                'verification_count' => (int) $cert->verifications_count,
                'verified_on'        => now()->format('F j, Y \a\t h:i A'),
                'verification_url'   => url("/verify/{$cert->certificate_number}"),
                'download_url'       => "/certificate/download/{$cert->id}",
                'can_download'       => $canDownload,
                'topics'             => $topics,
                'director_name'      => $teacherName,
                'director_title'     => $teacherTitle,
                'history'            => $logs,
            ];
        }

        // 2. Exact match on User student_code
        $student = User::whereNotNull('student_code')
            ->where('student_code', '!=', '')
            ->where('student_code', $cleanQuery)
            ->with(['major.department.faculty'])
            ->first();

        if ($student) {
            CertificateVerificationLog::create([
                'certificate_number' => $student->student_code,
                'result'             => 'valid',
                'ip_address'         => request()->ip(),
                'location'           => null,
                'source'             => $source ?? 'student_id',
            ]);

            $logs = CertificateVerificationLog::where('certificate_number', $student->student_code)
                ->latest()
                ->take(5)
                ->get()
                ->map(fn (CertificateVerificationLog $log) => [
                    'date'        => $log->created_at ? $log->created_at->format('F j, Y \a\t h:i A') : '—',
                    'verified_by' => 'Public Registry',
                    'ip_address'  => $this->maskIpAddress($log->ip_address),
                    'location'    => $log->location ?: '—',
                    'result'      => strtoupper((string) $log->result),
                ])
                ->all();

            $registeredDate = $student->created_at ? $student->created_at->format('F j, Y') : '—';

            return [
                'id'                 => null,
                'status'             => 'valid',
                'query'              => $cleanQuery,
                'is_student_id'      => true,
                'certificate_id'     => $student->student_code,
                'student_name'       => $student->name,
                'student_name_kh'    => $student->name_kh,
                'course_name'        => $student->major?->name ?? 'Active Enrolled Student',
                'major_name'         => $student->major?->name,
                'issuer'             => 'Saint Paul Institute',
                'completion_date'    => $registeredDate,
                'issue_date'         => $registeredDate,
                'final_score'        => '—',
                'grade'              => 'Active',
                'certificate_status' => 'Valid Student ID',
                'verification_count' => CertificateVerificationLog::where('certificate_number', $student->student_code)->count(),
                'verified_on'        => now()->format('F j, Y \a\t h:i A'),
                'verification_url'   => url("/verify/{$student->student_code}"),
                'download_url'       => null,
                'can_download'       => false,
                'topics'             => [],
                'director_name'      => 'Academic Registrar',
                'director_title'     => 'Saint Paul Institute',
                'history'            => $logs,
            ];
        }

        // 3. Not found
        CertificateVerificationLog::create([
            'certificate_number' => $cleanQuery,
            'result'             => 'not_found',
            'ip_address'         => request()->ip(),
            'location'           => null,
            'source'             => $source ?? 'manual',
        ]);

        return [
            'status'         => 'not_found',
            'query'          => $cleanQuery,
            'certificate_id' => $cleanQuery,
            'message'        => 'We could not find a certificate or student ID matching the code provided.',
            'history'        => [],
        ];
    }

    /**
     * Extract clean certificate/student code from raw query or URL.
     */
    protected function normalizeQueryCode(?string $raw): string
    {
        $clean = trim((string) ($raw ?? ''));
        if ($clean === '') {
            return '';
        }

        foreach (['/certificate/verify/', '/verify-certificate/', '/verify/'] as $segment) {
            if (str_contains($clean, $segment)) {
                $parts = explode($segment, $clean);
                $extracted = trim((string) end($parts), " \t\n\r\0\x0B/");
                $extracted = explode('?', $extracted)[0];
                $extracted = explode('#', $extracted)[0];
                if ($extracted !== '') {
                    return urldecode($extracted);
                }
            }
        }

        return $clean;
    }

    /**
     * Compute formatted learning time string ('0h 00m' when 0).
     */
    protected function calculateStudentLearningTime(int $userId): string
    {
        $studySeconds = 0;
        $progressSeconds = 0;

        try {
            if (Schema::hasTable('study_daily')) {
                $studySeconds = (int) StudyDaily::where('user_id', $userId)->sum('seconds');
            }
            if (Schema::hasTable('lesson_progress')) {
                $progressSeconds = (int) LessonProgress::where('user_id', $userId)->sum('seconds_watched');
            }
        } catch (\Throwable $e) {
            // Ignore table lookup errors safely
        }

        $totalSeconds = max(0, max($studySeconds, $progressSeconds));
        $hours = intdiv($totalSeconds, 3600);
        $minutes = intdiv($totalSeconds % 3600, 60);

        return sprintf('%dh %02dm', $hours, $minutes);
    }

    /**
     * Compute integer percentages summing to 100 when total > 0, or all 0 when total === 0.
     */
    protected function computePercentages(array $counts, int $total): array
    {
        if ($total <= 0) {
            return array_fill(0, count($counts), 0);
        }

        $percentages = [];
        $sum = 0;
        $maxIdx = 0;
        $maxCount = -1;

        foreach ($counts as $idx => $count) {
            $pct = (int) round(($count / $total) * 100);
            $percentages[$idx] = $pct;
            $sum += $pct;
            if ($count > $maxCount) {
                $maxCount = $count;
                $maxIdx = $idx;
            }
        }

        if ($sum !== 100 && $maxCount > 0) {
            $percentages[$maxIdx] = max(0, $percentages[$maxIdx] + (100 - $sum));
        }

        return $percentages;
    }

    /**
     * Mask IPv4/IPv6 address for privacy in public verification history.
     */
    protected function maskIpAddress(?string $ip): string
    {
        if (!$ip) {
            return '—';
        }
        if (preg_match('/^(\d+)\.(\d+)\.(\d+)\.(\d+)$/', $ip, $m)) {
            return "{$m[1]}.{$m[2]}.xxx.xxx";
        }
        return substr($ip, 0, 6) . '••••';
    }

    /**
     * Empty baseline payload when user is unauthenticated.
     */
    protected function emptyMyCertificatesData(): array
    {
        return [
            'summary' => [
                'total_certificates'  => 0,
                'total_note'          => 'All time earned',
                'completed_courses'   => 0,
                'completed_note'      => 'Completed courses',
                'this_year'           => 0,
                'this_year_note'      => 'Certificates earned',
                'recent_date'         => '—',
                'recent_note'         => 'No certificates yet',
                'total_learning_time' => '0h 00m',
                'time_note'           => 'Across all courses',
            ],
            'statistics' => [
                'total' => 0,
                'items' => [
                    ['label' => 'Earned',      'count' => 0, 'percentage' => 0, 'color' => '#10B981'],
                    ['label' => 'In Progress', 'count' => 0, 'percentage' => 0, 'color' => '#3B82F6'],
                    ['label' => 'Revoked',     'count' => 0, 'percentage' => 0, 'color' => '#EF4444'],
                    ['label' => 'Not Started', 'count' => 0, 'percentage' => 0, 'color' => '#F59E0B'],
                ],
            ],
            'recent_achievements' => [],
            'certificates'        => [],
            'total_count'         => 0,
            'filter_options'      => [
                'categories' => [],
                'courses'    => [],
                'issuers'    => [],
            ],
        ];
    }
}
