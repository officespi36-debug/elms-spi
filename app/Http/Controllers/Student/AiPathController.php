<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AiRecommendation;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\Semester;
use App\Models\StudentGoal;
use App\Models\StudyDaily;
use App\Models\User;
use App\Services\AiLearningPathService;
use App\Services\AiRecommendationService;
use App\Services\ContinueLearningService;
use App\Services\CourseProgressService;
use App\Services\DashboardAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AiPathController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'recommended');
        return Inertia::render('Student/AiLearningPath/Index', array_merge(
            ['activeTab' => $tab],
            $this->buildAiPathPayload($request->user(), $request)
        ));
    }

    public function recommended(Request $request)
    {
        return Inertia::render(
            'Student/AiLearningPath/RecommendedLesson',
            $this->buildAiPathPayload($request->user(), $request)
        );
    }

    public function review(Request $request)
    {
        return Inertia::render(
            'Student/AiLearningPath/ReviewLesson',
            $this->buildAiPathPayload($request->user(), $request)
        );
    }

    public function weakTopics(Request $request)
    {
        return Inertia::render(
            'Student/AiLearningPath/WeakTopics',
            $this->buildAiPathPayload($request->user(), $request)
        );
    }

    public function nextModule(Request $request)
    {
        return Inertia::render(
            'Student/AiLearningPath/NextModule',
            $this->buildAiPathPayload($request->user(), $request)
        );
    }

    public function nextCourse(Request $request)
    {
        return Inertia::render(
            'Student/AiLearningPath/NextCourse',
            $this->buildAiPathPayload($request->user(), $request)
        );
    }

    /**
     * Re-analyze the student's real academic data via Real AI API (Gemini/OpenAI/Workers AI)
     * and persist the result in `ai_recommendations`.
     */
    public function reanalyze(Request $request, AiLearningPathService $aiPathService): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if (!$user) {
            return response()->json(['ok' => false, 'message' => 'Unauthenticated'], 401);
        }

        try {
            $snapshot = $this->buildAcademicSnapshotForAi($user);
            $aiPathService->analyzeAndPersist($user, $snapshot);
            app(DashboardAnalyticsService::class)->invalidateUserCache($user->id);

            return response()->json(array_merge(
                ['ok' => true],
                $this->buildAiPathPayload($user, $request)
            ));
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'AI_PROVIDER_UNAVAILABLE') {
                return response()->json([
                    'ok'         => false,
                    'error_code' => 'provider_unavailable',
                    'message'    => 'សេវា AI មិនអាចឆ្លើយតបបានទេនៅពេលនេះ។ សូមព្យាយាមម្ដងទៀត។',
                    'message_en' => 'AI service is temporarily unavailable. Please try again.',
                ], 503);
            }

            Log::error('AI Path reanalyze failure: ' . $e->getMessage());
            return response()->json([
                'ok'         => false,
                'message'    => 'មានបញ្ហាក្នុងការវិភាគគន្លងសិក្សា។ សូមព្យាយាមម្ដងទៀត។',
                'message_en' => 'Unable to analyze your learning path right now.',
            ], 500);
        } catch (\Throwable $e) {
            Log::error('AI Path reanalyze exception: ' . $e->getMessage());
            return response()->json([
                'ok'         => false,
                'message'    => 'មានបញ្ហាក្នុងការវិភាគគន្លងសិក្សា។ សូមព្យាយាមម្ដងទៀត។',
                'message_en' => 'Unable to analyze your learning path right now.',
            ], 500);
        }
    }

    /**
     * Build strictly non-PII academic snapshot from `elms-system` for Real AI API analysis.
     */
    protected function buildAcademicSnapshotForAi(User $user): array
    {
        $user->loadMissing('major.department.faculty');

        $progressService = app(CourseProgressService::class);
        $enrollments = Enrollment::where('student_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->whereHas('course')
            ->with([
                'course',
                'course.major.department.faculty',
                'course.modules' => fn ($q) => $q->orderBy('order'),
                'course.modules.lessons' => fn ($q) => $q->orderBy('order'),
                'course.lessons' => fn ($q) => $q->orderBy('order'),
            ])
            ->latest('updated_at')
            ->get();

        $enrolledIds = $enrollments->pluck('course_id')->unique()->values()->all();
        $hasEnrollments = !empty($enrolledIds);
        $progressItems = $hasEnrollments
            ? $progressService->calculateForEnrollments($user, $enrollments)
            : collect();

        $firstCourse = $progressItems->first()['course'] ?? null;
        $major = $user->major ?: $firstCourse?->major;
        $faculty = $major?->department?->faculty;
        $activeYear = $this->resolveActiveAcademicYear();

        $enrolledSummary = $progressItems->map(function (array $item) {
            /** @var Course $c */
            $c = $item['course'];
            $nextLesson = $item['current_lesson'] ?? null;
            $lessonIds = collect();
            foreach ($c->modules ?? collect() as $mod) {
                $lessonIds = $lessonIds->merge(($mod->lessons ?? collect())->pluck('id'));
            }
            $lessonIds = $lessonIds->merge(($c->lessons ?? collect())->pluck('id'))->unique()->values()->all();

            return [
                'course_id'        => (int) $c->id,
                'title'            => (string) ($c->title_kh ?: $c->title),
                'title_en'         => (string) $c->title,
                'progress_percent' => (int) ($item['percent'] ?? 0),
                'lessons_done'     => (int) ($item['lessons_done'] ?? 0),
                'lessons_total'    => (int) ($item['lessons_total'] ?? 0),
                'next_lesson_id'   => $nextLesson?->id ? (int) $nextLesson->id : null,
                'next_lesson'      => $nextLesson ? (string) ($nextLesson->kh_title ?: $nextLesson->title) : null,
                'lesson_ids'       => $lessonIds,
            ];
        })->values()->all();

        $quizAttempts = QuizAttempt::with('quiz.course')
            ->where('user_id', $user->id)
            ->whereNotNull('score')
            ->latest('submitted_at')
            ->latest('id')
            ->get()
            ->unique('quiz_id')
            ->take(6)
            ->map(fn (QuizAttempt $att) => [
                'quiz_title'   => (string) ($att->quiz?->title ?? 'Quiz'),
                'course_title' => (string) ($att->quiz?->course?->title_kh ?: ($att->quiz?->course?->title ?? '')),
                'score_pct'    => (int) round((float) $att->score),
                'passed'       => (bool) $att->passed,
            ])
            ->values()
            ->all();

        $catalogCourses = Course::with('major')
            ->where('status', 'published')
            ->when(!empty($enrolledIds), fn ($q) => $q->whereNotIn('id', $enrolledIds))
            ->when($user->major_id, fn ($q) => $q->orderByRaw('CASE WHEN major_id = ? THEN 0 ELSE 1 END', [$user->major_id]))
            ->latest()
            ->take(5)
            ->get()
            ->map(fn (Course $c) => [
                'course_id' => (int) $c->id,
                'title'     => (string) ($c->title_kh ?: $c->title),
                'title_en'  => (string) $c->title,
                'major'     => (string) ($c->major?->name_kh ?: ($c->major?->name ?? 'General')),
                'level'     => (string) ($c->level ?: 'beginner'),
            ])
            ->values()
            ->all();

        [$studyTimeWeek, $streakDays] = $this->computeStudyTimeAndStreak($user);

        return [
            'faculty'                   => $faculty ? ($faculty->name_kh ?: $faculty->name) : null,
            'department'                => $major?->department ? ($major->department->name_kh ?: $major->department->name) : null,
            'major'                     => $major ? ($major->name_kh ?: $major->name) : null,
            'academic_year'             => $activeYear?->name,
            'academic_year_end'         => $activeYear?->end_date ? Carbon::parse($activeYear->end_date)->toDateString() : null,
            'has_enrollments'           => $hasEnrollments,
            'overall_progress_percent'  => $progressItems->isNotEmpty() ? (int) round($progressItems->avg('percent')) : 0,
            'completed_lessons'         => (int) $progressItems->sum('lessons_done'),
            'total_lessons'             => (int) $progressItems->sum('lessons_total'),
            'study_time_week'           => $studyTimeWeek,
            'streak_days'               => $streakDays,
            'enrolled_courses'          => $enrolledSummary,
            'quiz_attempts'             => $quizAttempts,
            'available_catalog_courses' => $catalogCourses,
        ];
    }

    /**
     * Build complete real-DB payload for AI Learning Path pages.
     */
    protected function buildAiPathPayload(?User $user, ?Request $request = null): array
    {
        $csrfToken = $request?->session()?->token() ?? csrf_token();

        $emptyLearningPath = [
            'hasEnrollments'       => false,
            'enrolledCoursesCount' => 0,
            'metrics'              => [
                'trackName_en'        => 'No Track Selected Yet',
                'trackName_km'        => 'មិនទាន់ជ្រើសរើសជំនាញសិក្សា',
                'facultyName_en'      => null,
                'facultyName_km'      => null,
                'targetLabel_en'      => 'Enroll in a course to set milestones',
                'targetLabel_km'      => 'ចុះឈ្មោះវគ្គសិក្សាដើម្បីចាប់ផ្តើមគោលដៅ',
                'overallProgress'     => 0,
                'completedMilestones' => 0,
                'totalMilestones'     => 0,
                'unitLabel_km'        => 'មេរៀន',
                'unitLabel_en'        => 'lessons',
                'pacingBadge_en'      => 'Ready to Start',
                'pacingBadge_km'      => 'ត្រៀមចាប់ផ្តើម',
                'pacingSub_en'        => 'No active courses yet',
                'pacingSub_km'        => 'មិនទាន់មានវគ្គសិក្សាសកម្ម',
                'pacingTone'          => 'neutral',
                'studyTimeWeek'       => '0h 0m',
                'streakDays'          => 0,
            ],
            'aiBanner'             => [
                'badge_en'        => 'Get Started',
                'badge_km'        => 'ចាប់ផ្តើមដំបូង',
                'title_en'        => 'Enroll in your first SPI course to unlock your personalized roadmap',
                'title_km'        => 'ចុះឈ្មោះវគ្គសិក្សាដំបូងរបស់អ្នក ដើម្បីបង្កើតផែនទីសិក្សា AI ផ្ទាល់ខ្លួន',
                'desc_en'         => 'Once enrolled, AI analyzes your real lesson progress, quiz scores, and study rhythm to guide every next step.',
                'desc_km'         => 'បន្ទាប់ពីចុះឈ្មោះ AI នឹងវិភាគវឌ្ឍនភាពមេរៀន ពិន្ទុ Quiz និងពេលវេលាសិក្សាពិតរបស់អ្នក ដើម្បីណែនាំជំហានបន្ទាប់។',
                'primaryLabel_en' => 'Browse Catalog',
                'primaryLabel_km' => 'ស្វែងរកវគ្គសិក្សា',
                'primaryHref'     => '/student/browse',
                'askAiUrl'        => '/student/ai-tutor',
                'tone'            => 'neutral',
                'analyzedAt'      => null,
                'provider'        => null,
                'isFromAiDb'      => false,
            ],
            'journeySteps'         => [],
            'suggestedCourses'     => [],
            'currentFocus'         => null,
            'aiNextStep'           => null,
            'skillProgress'        => [],
            'weakTopics'           => [],
            'practiceQuestions'    => [],
            'todayGoals'           => [
                'completedCount'   => 0,
                'totalCount'       => 0,
                'percentage'       => 0,
                'items'            => [],
                'quickSuggestions' => [],
            ],
        ];

        $emptyBase = [
            'csrfToken'          => $csrfToken,
            'recommendedCourses' => [],
            'recommendedLessons' => [],
            'reviewLessons'      => [],
            'weakTopics'         => [],
            'nextModule'         => null,
            'nextCourse'         => null,
            'aiRecommendations'  => [],
            'learningPath'       => $emptyLearningPath,
        ];

        if (!$user) {
            return $emptyBase;
        }

        try {
            $user->loadMissing('major.department.faculty');

            $progressService = app(CourseProgressService::class);
            $recService = app(AiRecommendationService::class);
            $rules = $recService->getActiveRules();
            $reviewBelow = max(70, (int) ($rules['review_current']['max_score'] ?? 70));

            $enrollments = Enrollment::where('student_id', $user->id)
                ->whereIn('status', ['active', 'completed'])
                ->whereHas('course')
                ->with([
                    'course',
                    'course.major.department.faculty',
                    'course.teacher',
                    'course.modules' => fn ($q) => $q->orderBy('order'),
                    'course.modules.chapters' => fn ($q) => $q->orderBy('order'),
                    'course.modules.chapters.lessons' => fn ($q) => $q->orderBy('order'),
                    'course.modules.lessons' => fn ($q) => $q->orderBy('order'),
                    'course.lessons' => fn ($q) => $q->orderBy('order'),
                ])
                ->latest('updated_at')
                ->get();

            $enrolledIds = $enrollments->pluck('course_id')->unique()->values()->all();
            $hasEnrollments = !empty($enrolledIds);

            $progressItems = $hasEnrollments
                ? $progressService->calculateForEnrollments($user, $enrollments)
                : collect();

            // Published catalog recommendations (excluding already enrolled)
            $recommendedCourses = Course::with(['teacher', 'major'])
                ->where('status', 'published')
                ->when(!empty($enrolledIds), fn ($q) => $q->whereNotIn('id', $enrolledIds))
                ->when($user->major_id, fn ($q) => $q->orderByRaw('CASE WHEN major_id = ? THEN 0 ELSE 1 END', [$user->major_id]))
                ->latest()
                ->take(6)
                ->get()
                ->map(function (Course $c) use ($progressService) {
                    $teacherEn = $progressService->cleanTeacherName($c->teacher, $c);
                    $teacherKm = $progressService->cleanTeacherNameKh($c->teacher, $c);
                    $rawDescEn = trim(strip_tags((string) ($c->description ?: 'Published SPI course available for enrollment.')));
                    $rawDescKm = trim(strip_tags((string) ($c->description_kh ?: $rawDescEn)));
                    return [
                        'id'            => $c->id,
                        'title'         => $c->title_kh ?: $c->title,
                        'title_en'      => $c->title,
                        'title_km'      => $c->title_kh ?: $c->title,
                        'desc_en'       => Str::limit($rawDescEn, 140),
                        'desc_km'       => Str::limit($rawDescKm, 140),
                        'major'         => $c->major?->name_kh ?: ($c->major?->name ?? 'General'),
                        'major_en'      => $c->major?->name ?? 'General',
                        'major_km'      => $c->major?->name_kh ?: ($c->major?->name ?? 'ទូទៅ'),
                        'instructor'    => $teacherKm ?: $teacherEn,
                        'instructor_en' => $teacherEn,
                        'instructor_km' => $teacherKm,
                        'level'         => $c->level ?: 'beginner',
                        'url'           => "/student/courses/{$c->id}/overview",
                    ];
                })
                ->values()
                ->all();

            // Lesson progress map across enrolled courses
            $allLessonIds = collect();
            foreach ($enrollments as $enr) {
                if (!$enr->course) {
                    continue;
                }
                foreach ($enr->course->modules ?? collect() as $mod) {
                    $allLessonIds = $allLessonIds->merge(($mod->lessons ?? collect())->pluck('id'));
                }
                $allLessonIds = $allLessonIds->merge(($enr->course->lessons ?? collect())->pluck('id'));
            }
            $allLessonIds = $allLessonIds->unique()->filter()->values()->all();

            $progressRecords = empty($allLessonIds)
                ? collect()
                : LessonProgress::where('user_id', $user->id)
                    ->whereIn('lesson_id', $allLessonIds)
                    ->get()
                    ->keyBy('lesson_id');

            $completedLessonIds = $progressRecords
                ->filter(fn (LessonProgress $lp) => !empty($lp->completed_at) || (int) $lp->percent >= 90)
                ->keys()
                ->all();

            $nextLessons = !empty($enrolledIds)
                ? Lesson::with(['course', 'module'])
                    ->whereIn('course_id', $enrolledIds)
                    ->when(!empty($completedLessonIds), fn ($q) => $q->whereNotIn('id', $completedLessonIds))
                    ->orderBy('order')
                    ->take(5)
                    ->get()
                    ->map(fn (Lesson $l) => [
                        'id'       => $l->id,
                        'title'    => $l->kh_title ?: $l->title,
                        'course'   => $l->course?->title_kh ?: ($l->course?->title ?? ''),
                        'module'   => $l->module?->kh_title ?: ($l->module?->title ?? ''),
                        'duration' => $this->formatSecondsShort((int) ($l->duration_seconds ?: 900)),
                        'url'      => "/student/my-courses/{$l->course_id}/lessons/{$l->id}",
                    ])
                    ->values()
                    ->all()
                : [];

            $reviewLessons = !empty($completedLessonIds)
                ? Lesson::with('course')
                    ->whereIn('id', array_slice($completedLessonIds, 0, 5))
                    ->get()
                    ->map(fn (Lesson $l) => [
                        'id'     => $l->id,
                        'title'  => $l->kh_title ?: $l->title,
                        'course' => $l->course?->title_kh ?: ($l->course?->title ?? ''),
                        'url'    => "/student/my-courses/{$l->course_id}/lessons/{$l->id}",
                    ])
                    ->values()
                    ->all()
                : [];

            // Latest quiz attempts (per quiz_id so mastered retries clear old weak flags)
            $allUserAttempts = QuizAttempt::with(['quiz', 'quiz.course'])
                ->where('user_id', $user->id)
                ->whereNotNull('score')
                ->latest('submitted_at')
                ->latest('id')
                ->get();

            $latestAttemptsByQuiz = $allUserAttempts->unique('quiz_id')->values();

            $weakTopics = $latestAttemptsByQuiz
                ->filter(fn (QuizAttempt $att) => $att->quiz && (float) $att->score < $reviewBelow)
                ->sortBy(fn (QuizAttempt $att) => (float) $att->score)
                ->take(5)
                ->map(function (QuizAttempt $att) {
                    $quiz = $att->quiz;
                    $course = $quiz?->course;
                    $scorePct = (int) round((float) $att->score);
                    $severity = $scorePct < 50 ? 'high' : ($scorePct < 60 ? 'medium' : 'low');
                    $topicTitle = (string) ($quiz?->title ?: 'Quiz Review');
                    $courseEn = (string) ($course?->title ?: 'Course');
                    $courseKm = (string) ($course?->title_kh ?: $courseEn);
                    $promptText = "Explain the key concepts for \"{$topicTitle}\" in {$courseEn} and give me practice hints.";

                    $askUrl = '/student/ai-tutor?' . http_build_query(array_filter([
                        'course' => $course?->id,
                        'prompt' => $promptText,
                    ]));

                    return [
                        'id'          => (int) $att->id,
                        'quizId'      => (int) ($quiz?->id ?? 0),
                        'courseId'    => $course?->id ? (int) $course->id : null,
                        'title'       => $topicTitle,
                        'topic_en'    => $topicTitle,
                        'topic_km'    => $topicTitle,
                        'course'      => $courseKm,
                        'course_en'   => $courseEn,
                        'course_km'   => $courseKm,
                        'score'       => $scorePct,
                        'severity'    => $severity,
                        'reason_en'   => "Latest attempt scored {$scorePct}%. Focused revision is recommended before moving ahead.",
                        'reason_km'   => "លទ្ធផលសាកល្បងចុងក្រោយទទួលបាន {$scorePct}% — ណែនាំឲ្យរំលឹកឡើងវិញមុននឹងបន្តមេរៀនថ្មី។",
                        'action_en'   => 'Review the lesson notes or practice with AI Tutor',
                        'action_km'   => 'រំលឹកមេរៀនឡើងវិញ ឬសាកសួរជំនួយការសិក្សា AI',
                        'practiceUrl' => $quiz ? "/student/quiz/{$quiz->id}" : '/student/quizzes/practice',
                        'askAiUrl'    => $askUrl,
                    ];
                })
                ->values()
                ->all();

            // Active AI Recommendations saved in `ai_recommendations`
            $aiRecs = Schema::hasTable('ai_recommendations')
                ? AiRecommendation::with(['lesson.course'])
                    ->where('user_id', $user->id)
                    ->where('is_dismissed', false)
                    ->latest('id')
                    ->take(10)
                    ->get()
                : collect();

            /** @var AiRecommendation|null $latestPathRec */
            $latestPathRec = $aiRecs->firstWhere('type', 'ai_path') ?? $aiRecs->first();

            // Current-week study duration & streak from real activity
            [$studyTimeWeek, $streakDays] = $this->computeStudyTimeAndStreak($user);

            // Track / Faculty / Active AcademicYear End Date metrics
            $firstEnrolledCourse = $progressItems->first()['course'] ?? null;
            $trackMajor = $user->major ?: $firstEnrolledCourse?->major;
            $faculty = $trackMajor?->department?->faculty;

            if ($trackMajor) {
                $trackNameEn = (string) ($trackMajor->name ?: 'Academic Track');
                $trackNameKm = (string) ($trackMajor->name_kh ?: $trackNameEn);
            } elseif ($firstEnrolledCourse) {
                $trackNameEn = (string) $firstEnrolledCourse->title;
                $trackNameKm = (string) ($firstEnrolledCourse->title_kh ?: $trackNameEn);
            } else {
                $trackNameEn = 'No Major Selected Yet';
                $trackNameKm = 'មិនទាន់ជ្រើសរើសជំនាញសិក្សា';
            }

            $facultyNameEn = $faculty ? (string) $faculty->name : null;
            $facultyNameKm = $faculty ? (string) ($faculty->name_kh ?: $faculty->name) : null;

            $activeYear = $this->resolveActiveAcademicYear();
            $activeChildSemester = ($activeYear && Schema::hasTable('semesters'))
                ? Semester::where('academic_year_id', $activeYear->id)
                    ->where(function ($q) {
                        $q->where('is_active', true)->orWhere('status', 'active');
                    })
                    ->where(function ($q) {
                        $q->whereNull('end_date')->orWhereDate('end_date', '>=', '2026-01-01');
                    })
                    ->orderByDesc('is_active')
                    ->latest('id')
                    ->first()
                : null;

            $endDateValue = $activeChildSemester?->end_date ?: $activeYear?->end_date;

            if ($activeYear && !empty($endDateValue)) {
                $parsedEnd = Carbon::parse($endDateValue);
                $endFmtEn = $parsedEnd->format('M d, Y');
                $endFmtKm = $parsedEnd->format('d/m/Y');
                $yearName = (string) $activeYear->name;
                $yearNameKm = str_replace('Academic Year', 'ឆ្នាំសិក្សា', $yearName);
                $targetLabelEn = "Target ({$yearName}): {$endFmtEn}";
                $targetLabelKm = "គោលដៅបញ្ចប់ ({$yearNameKm}): {$endFmtKm}";
            } elseif ($activeYear) {
                $yearName = (string) $activeYear->name;
                $targetLabelEn = $yearName;
                $targetLabelKm = str_replace('Academic Year', 'ឆ្នាំសិក្សា', $yearName);
            } elseif ($hasEnrollments) {
                $count = count($enrolledIds);
                $targetLabelEn = $count . ' Enrolled ' . ($count === 1 ? 'Course' : 'Courses');
                $targetLabelKm = "កំពុងសិក្សា {$count} វគ្គសិក្សា";
            } else {
                $targetLabelEn = 'Enroll in a course to set milestones';
                $targetLabelKm = 'ចុះឈ្មោះវគ្គសិក្សាដើម្បីចាប់ផ្តើមគោលដៅ';
            }

            $overallProgress = $progressItems->isNotEmpty()
                ? (int) round($progressItems->avg('percent'))
                : 0;

            // Card 3: Count completed lessons / chapters vs total lessons / chapters (X / Y)
            [$completedMilestones, $totalMilestones, $unitLabelKm, $unitLabelEn] = $this->computeCompletedUnits(
                $hasEnrollments,
                $progressItems,
                $progressRecords
            );

            // Build ordered Journey Steps (Green COMPLETED / Purple CURRENT / Yellow AI_RECOMMENDED)
            $journeySteps = $this->buildJourneySteps(
                $hasEnrollments,
                $progressItems,
                $progressRecords,
                $recommendedCourses,
                $latestAttemptsByQuiz,
                $latestPathRec
            );

            [$pacingBadgeEn, $pacingBadgeKm, $pacingSubEn, $pacingSubKm, $pacingTone] = $this->resolvePacingMeta(
                $hasEnrollments,
                $overallProgress,
                $completedMilestones,
                $totalMilestones,
                $unitLabelKm,
                $unitLabelEn,
                $streakDays
            );

            // Current Learning Focus
            $currentFocus = $this->buildCurrentFocus($user, $progressItems, $progressRecords);

            // Real Practice Drill Questions (from MCQ/True-False questions of enrolled courses)
            $practiceQuestions = $this->buildPracticeQuestions($enrolledIds, $weakTopics);

            // AI Recommended Next Step card
            $aiNextStep = $this->buildAiNextStep(
                $hasEnrollments,
                $weakTopics,
                $latestPathRec,
                $currentFocus,
                $recommendedCourses,
                $practiceQuestions
            );

            // Skill Progress per enrolled course
            $skillProgress = $this->buildSkillProgress($progressItems, $latestAttemptsByQuiz);

            // Top AI Banner (from `ai_recommendations` first, else real DB fallback)
            $aiBanner = $this->buildAiBanner(
                $hasEnrollments,
                $overallProgress,
                $weakTopics,
                $latestPathRec,
                $currentFocus,
                $recommendedCourses
            );

            // Real Daily Goals from `student_goals`
            $todayGoals = $this->buildTodayGoals($user, $currentFocus, $weakTopics, $hasEnrollments);

            return [
                'csrfToken'          => $csrfToken,
                'recommendedCourses' => $recommendedCourses,
                'recommendedLessons' => $nextLessons,
                'reviewLessons'      => $reviewLessons,
                'weakTopics'         => $weakTopics,
                'nextModule'         => $nextLessons[0] ?? null,
                'nextCourse'         => $recommendedCourses[0] ?? null,
                'aiRecommendations'  => $aiRecs,
                'learningPath'       => [
                    'hasEnrollments'       => $hasEnrollments,
                    'enrolledCoursesCount' => count($enrolledIds),
                    'metrics'              => [
                        'trackName_en'        => $trackNameEn,
                        'trackName_km'        => $trackNameKm,
                        'facultyName_en'      => $facultyNameEn,
                        'facultyName_km'      => $facultyNameKm,
                        'targetLabel_en'      => $targetLabelEn,
                        'targetLabel_km'      => $targetLabelKm,
                        'overallProgress'     => $overallProgress,
                        'completedMilestones' => $completedMilestones,
                        'totalMilestones'     => $totalMilestones,
                        'unitLabel_km'        => $unitLabelKm,
                        'unitLabel_en'        => $unitLabelEn,
                        'pacingBadge_en'      => $pacingBadgeEn,
                        'pacingBadge_km'      => $pacingBadgeKm,
                        'pacingSub_en'        => $pacingSubEn,
                        'pacingSub_km'        => $pacingSubKm,
                        'pacingTone'          => $pacingTone,
                        'studyTimeWeek'       => $studyTimeWeek,
                        'streakDays'          => $streakDays,
                    ],
                    'aiBanner'             => $aiBanner,
                    'journeySteps'         => $journeySteps,
                    'suggestedCourses'     => array_slice($recommendedCourses, 0, 3),
                    'currentFocus'         => $currentFocus,
                    'aiNextStep'           => $aiNextStep,
                    'skillProgress'        => $skillProgress,
                    'weakTopics'           => $weakTopics,
                    'practiceQuestions'    => $practiceQuestions,
                    'todayGoals'           => $todayGoals,
                ],
            ];
        } catch (\Throwable $e) {
            Log::warning('AI Path payload warning: ' . $e->getMessage());
            return $emptyBase;
        }
    }

    /**
     * Resolve active academic year exclusively from `academic_years` (never stale 2024-2025 placeholders).
     */
    protected function resolveActiveAcademicYear(): ?AcademicYear
    {
        if (!Schema::hasTable('academic_years')) {
            return null;
        }

        return AcademicYear::where(function ($q) {
                $q->where('is_active', true)->orWhere('status', 'active');
            })
            ->whereNotIn('code', ['AY-2022-2023', 'AY-2023-2024', 'AY-2024-2025'])
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', '2026-01-01');
            })
            ->orderByDesc('is_active')
            ->latest('id')
            ->first();
    }

    /**
     * Count completed lessons (or chapters if no direct lessons exist) vs total for enrolled courses.
     */
    protected function computeCompletedUnits(
        bool $hasEnrollments,
        Collection $progressItems,
        Collection $progressRecords
    ): array {
        if (!$hasEnrollments || $progressItems->isEmpty()) {
            return [0, 0, 'មេរៀន', 'lessons'];
        }

        $doneLessons = (int) $progressItems->sum('lessons_done');
        $totalLessons = (int) $progressItems->sum('lessons_total');

        if ($totalLessons > 0) {
            return [$doneLessons, $totalLessons, 'មេរៀន', 'lessons'];
        }

        // Secondary fallback: count chapters if courses only define chapters without lessons yet
        $doneChapters = 0;
        $totalChapters = 0;
        foreach ($progressItems as $item) {
            /** @var Course $course */
            $course = $item['course'];
            foreach ($course->modules ?? collect() as $mod) {
                foreach ($mod->chapters ?? collect() as $chap) {
                    $totalChapters++;
                    $chapLessons = $chap->lessons ?? collect();
                    if ($chapLessons->isNotEmpty()) {
                        $allDone = $chapLessons->every(function (Lesson $l) use ($progressRecords) {
                            $lp = $progressRecords->get($l->id);
                            return $lp && (!empty($lp->completed_at) || (int) $lp->percent >= 90);
                        });
                        if ($allDone) {
                            $doneChapters++;
                        }
                    }
                }
            }
        }

        if ($totalChapters > 0) {
            return [$doneChapters, $totalChapters, 'ជំពូក', 'chapters'];
        }

        return [0, 0, 'មេរៀន', 'lessons'];
    }

    protected function buildJourneySteps(
        bool $hasEnrollments,
        Collection $progressItems,
        Collection $progressRecords,
        array $recommendedCourses,
        Collection $latestAttemptsByQuiz,
        ?AiRecommendation $latestPathRec = null
    ): array {
        $aiPayload = is_array($latestPathRec?->ai_payload) ? $latestPathRec->ai_payload : [];
        $aiRecCourseId = isset($aiPayload['recommended_course_id']) && is_numeric($aiPayload['recommended_course_id'])
            ? (int) $aiPayload['recommended_course_id']
            : null;
        $aiRecLessonId = isset($aiPayload['recommended_lesson_id']) && is_numeric($aiPayload['recommended_lesson_id'])
            ? (int) $aiPayload['recommended_lesson_id']
            : ($latestPathRec?->lesson_id ? (int) $latestPathRec->lesson_id : null);

        // Order recommended catalog courses prioritizing any AI-selected course_id
        $orderedCatalog = collect($recommendedCourses)
            ->sortByDesc(fn (array $c) => $aiRecCourseId && (int) $c['id'] === $aiRecCourseId ? 1 : 0)
            ->values()
            ->all();

        // When unenrolled: show up to 3 real published catalog courses marked AI_RECOMMENDED (Yellow), or [] if DB has none
        if (!$hasEnrollments || $progressItems->isEmpty()) {
            $starterSteps = [];
            foreach (array_slice($orderedCatalog, 0, 3) as $c) {
                $starterSteps[] = [
                    'id'               => 'catalog-' . $c['id'],
                    'course_id'        => (int) $c['id'],
                    'lesson_id'        => null,
                    'title_en'         => (string) ($c['title_en'] ?? $c['title']),
                    'title_km'         => (string) ($c['title_km'] ?? $c['title']),
                    'category_en'      => (string) ($c['major_en'] ?? 'SPI Course'),
                    'category_km'      => (string) ($c['major_km'] ?? 'វគ្គសិក្សា SPI'),
                    'status'           => 'AI_RECOMMENDED',
                    'isCourseStep'     => true,
                    'progress'         => 0,
                    'score'            => null,
                    'duration'         => ucfirst((string) ($c['level'] ?? 'beginner')),
                    'desc_en'          => (string) ($c['desc_en'] ?? 'Published course available in the SPI catalog.'),
                    'desc_km'          => (string) ($c['desc_km'] ?? 'វគ្គសិក្សាផ្លូវការក្នុងប្រព័ន្ធ SPI ដែល AI ណែនាំសម្រាប់ចាប់ផ្តើម។'),
                    'skills_en'        => [],
                    'skills_km'        => [],
                    'prerequisites_en' => 'None',
                    'prerequisites_km' => 'មិនទាមទារ',
                    'learnUrl'         => (string) $c['url'],
                    'overviewUrl'      => (string) $c['url'],
                    'askAiUrl'         => '/student/ai-tutor',
                ];
            }
            return $starterSteps;
        }

        $quizScoresByModule = [];
        $quizScoresByCourse = [];
        foreach ($latestAttemptsByQuiz as $att) {
            if ($att->quiz && $att->score !== null) {
                if ($att->quiz->module_id) {
                    $quizScoresByModule[$att->quiz->module_id][] = (float) $att->score;
                }
                if ($att->quiz->course_id) {
                    $quizScoresByCourse[$att->quiz->course_id][] = (float) $att->score;
                }
            }
        }

        $chaptersCount = 0;
        $modulesCount = 0;
        $lessonsCount = 0;
        foreach ($progressItems as $item) {
            /** @var Course $c */
            $c = $item['course'];
            foreach ($c->modules ?? collect() as $mod) {
                $modulesCount++;
                foreach ($mod->chapters ?? collect() as $chap) {
                    if (($chap->lessons ?? collect())->isNotEmpty()) {
                        $chaptersCount++;
                    }
                }
            }
            $lessonsCount += (int) ($item['lessons_total'] ?? 0);
        }

        $rawSteps = [];
        $prevTitleEn = 'None';
        $prevTitleKm = 'មិនទាមទារ';

        if ($chaptersCount >= 2) {
            foreach ($progressItems as $item) {
                /** @var Course $course */
                $course = $item['course'];
                $courseEn = (string) $course->title;
                $courseKm = (string) ($course->title_kh ?: $courseEn);
                foreach (($course->modules ?? collect())->sortBy('order') as $mod) {
                    foreach (($mod->chapters ?? collect())->sortBy('order') as $chap) {
                        $chapLessons = ($chap->lessons ?? collect())->sortBy('order')->values();
                        if ($chapLessons->isEmpty()) {
                            continue;
                        }
                        [$stepProgress, $targetLesson, $totalSecs] = $this->computeGroupProgress(
                            $chapLessons,
                            $progressRecords,
                            ($item['state'] ?? '') === 'completed'
                        );
                        $titleEn = (string) ($chap->title ?: ($mod->title ?: $courseEn));
                        $titleKm = (string) ($chap->kh_title ?: $titleEn);
                        $modScores = $quizScoresByModule[$mod->id] ?? [];
                        $score = !empty($modScores) ? (int) round(array_sum($modScores) / count($modScores)) : null;

                        $rawSteps[] = [
                            'id'               => 'chap-' . $chap->id,
                            'course_id'        => (int) $course->id,
                            'lesson_id'        => $targetLesson ? (int) $targetLesson->id : null,
                            'lesson_ids'       => $chapLessons->pluck('id')->map(fn ($id) => (int) $id)->all(),
                            'title_en'         => $titleEn,
                            'title_km'         => $titleKm,
                            'category_en'      => $courseEn,
                            'category_km'      => $courseKm,
                            'isCourseStep'     => false,
                            'progress'         => $stepProgress,
                            'score'            => $score,
                            'duration'         => $totalSecs > 0 ? $this->formatSecondsShort($totalSecs) : (max(1, $chapLessons->count()) * 15 . ' min'),
                            'desc_en'          => Str::limit(trim(strip_tags((string) ($mod->description ?: "Chapter in {$courseEn}."))), 140),
                            'desc_km'          => Str::limit(trim(strip_tags((string) ($mod->kh_description ?: $mod->description ?: "ជំពូកក្នុងវគ្គ {$courseKm}។"))), 140),
                            'skills_en'        => $chapLessons->take(4)->map(fn (Lesson $l) => (string) $l->title)->values()->all(),
                            'skills_km'        => $chapLessons->take(4)->map(fn (Lesson $l) => (string) ($l->kh_title ?: $l->title))->values()->all(),
                            'prerequisites_en' => $prevTitleEn,
                            'prerequisites_km' => $prevTitleKm,
                            'learnUrl'         => $targetLesson ? "/student/my-courses/{$course->id}/lessons/{$targetLesson->id}" : "/student/my-courses/{$course->id}/continue",
                            'overviewUrl'      => "/student/courses/{$course->id}/overview",
                            'askAiUrl'         => '/student/ai-tutor?' . http_build_query(array_filter(['course' => $course->id, 'lesson' => $targetLesson?->id])),
                        ];
                        $prevTitleEn = $titleEn;
                        $prevTitleKm = $titleKm;
                    }
                }
            }
        } elseif ($modulesCount >= 2) {
            foreach ($progressItems as $item) {
                /** @var Course $course */
                $course = $item['course'];
                $courseEn = (string) $course->title;
                $courseKm = (string) ($course->title_kh ?: $courseEn);
                foreach (($course->modules ?? collect())->sortBy('order') as $mod) {
                    $modLessons = ($mod->lessons ?? collect())->sortBy('order')->values();
                    [$modProgress, $targetLesson, $totalSecs] = $this->computeGroupProgress(
                        $modLessons,
                        $progressRecords,
                        ($item['state'] ?? '') === 'completed'
                    );
                    $durationStr = !empty($mod->estimated_duration)
                        ? (string) $mod->estimated_duration
                        : ($totalSecs > 0 ? $this->formatSecondsShort($totalSecs) : max(1, $modLessons->count()) * 15 . ' min');
                    $titleEn = (string) ($mod->title ?: $courseEn);
                    $titleKm = (string) ($mod->kh_title ?: $titleEn);
                    $modScores = $quizScoresByModule[$mod->id] ?? [];
                    $score = !empty($modScores) ? (int) round(array_sum($modScores) / count($modScores)) : null;

                    $rawSteps[] = [
                        'id'               => 'mod-' . $mod->id,
                        'course_id'        => (int) $course->id,
                        'lesson_id'        => $targetLesson ? (int) $targetLesson->id : null,
                        'lesson_ids'       => $modLessons->pluck('id')->map(fn ($id) => (int) $id)->all(),
                        'title_en'         => $titleEn,
                        'title_km'         => $titleKm,
                        'category_en'      => $courseEn,
                        'category_km'      => $courseKm,
                        'isCourseStep'     => false,
                        'progress'         => $modProgress,
                        'score'            => $score,
                        'duration'         => $durationStr,
                        'desc_en'          => Str::limit(trim(strip_tags((string) ($mod->description ?: "Lessons in {$courseEn}."))), 140),
                        'desc_km'          => Str::limit(trim(strip_tags((string) ($mod->kh_description ?: $mod->description ?: "មេរៀនក្នុងវគ្គ {$courseKm}។"))), 140),
                        'skills_en'        => $modLessons->take(4)->map(fn (Lesson $l) => (string) $l->title)->values()->all(),
                        'skills_km'        => $modLessons->take(4)->map(fn (Lesson $l) => (string) ($l->kh_title ?: $l->title))->values()->all(),
                        'prerequisites_en' => $prevTitleEn,
                        'prerequisites_km' => $prevTitleKm,
                        'learnUrl'         => $targetLesson ? "/student/my-courses/{$course->id}/lessons/{$targetLesson->id}" : "/student/my-courses/{$course->id}/continue",
                        'overviewUrl'      => "/student/courses/{$course->id}/overview",
                        'askAiUrl'         => '/student/ai-tutor?' . http_build_query(array_filter(['course' => $course->id, 'lesson' => $targetLesson?->id])),
                    ];
                    $prevTitleEn = $titleEn;
                    $prevTitleKm = $titleKm;
                }
            }
        } elseif ($lessonsCount >= 2) {
            // Lesson-level roadmap when a course has 1 module (or flat lessons) with >= 2 lessons
            foreach ($progressItems as $item) {
                /** @var Course $course */
                $course = $item['course'];
                $courseEn = (string) $course->title;
                $courseKm = (string) ($course->title_kh ?: $courseEn);
                $ordered = $this->collectCourseLessonsOrdered($course);

                foreach ($ordered as $lesson) {
                    /** @var LessonProgress|null $lp */
                    $lp = $progressRecords->get($lesson->id);
                    $isCourseCompleted = ($item['state'] ?? '') === 'completed';
                    if ($isCourseCompleted || ($lp && (!empty($lp->completed_at) || (int) $lp->percent >= 90))) {
                        $lPct = 100;
                    } else {
                        $lPct = min(99, max(0, (int) ($lp?->percent ?? 0)));
                    }

                    $lTitleEn = (string) $lesson->title;
                    $lTitleKm = (string) ($lesson->kh_title ?: $lTitleEn);
                    $lDesc = Str::limit(trim(strip_tags((string) ($lesson->summary ?: $lesson->ai_summary ?: $lesson->content ?: "Lesson in {$courseEn}."))), 140);

                    $rawSteps[] = [
                        'id'               => 'lesson-' . $lesson->id,
                        'course_id'        => (int) $course->id,
                        'lesson_id'        => (int) $lesson->id,
                        'lesson_ids'       => [(int) $lesson->id],
                        'title_en'         => $lTitleEn,
                        'title_km'         => $lTitleKm,
                        'category_en'      => $courseEn,
                        'category_km'      => $courseKm,
                        'isCourseStep'     => false,
                        'progress'         => $lPct,
                        'score'            => null,
                        'duration'         => $this->formatSecondsShort((int) ($lesson->duration_seconds ?: 900)),
                        'desc_en'          => $lDesc !== '' ? $lDesc : "Lesson in {$courseEn}.",
                        'desc_km'          => $lDesc !== '' ? $lDesc : "មេរៀនក្នុងវគ្គ {$courseKm}។",
                        'skills_en'        => is_array($lesson->objectives) ? array_slice(array_map('strval', $lesson->objectives), 0, 4) : [],
                        'skills_km'        => is_array($lesson->objectives) ? array_slice(array_map('strval', $lesson->objectives), 0, 4) : [],
                        'prerequisites_en' => $prevTitleEn,
                        'prerequisites_km' => $prevTitleKm,
                        'learnUrl'         => "/student/my-courses/{$course->id}/lessons/{$lesson->id}",
                        'overviewUrl'      => "/student/courses/{$course->id}/overview",
                        'askAiUrl'         => '/student/ai-tutor?' . http_build_query(['course' => $course->id, 'lesson' => $lesson->id]),
                    ];
                    $prevTitleEn = $lTitleEn;
                    $prevTitleKm = $lTitleKm;
                }
            }
        } else {
            // Course-level roadmap fallback
            foreach ($progressItems as $item) {
                /** @var Course $course */
                $course = $item['course'];
                $courseEn = (string) $course->title;
                $courseKm = (string) ($course->title_kh ?: $courseEn);
                $lessons = $this->collectCourseLessonsOrdered($course);
                $totalSecs = (int) $lessons->sum('duration_seconds');
                $targetLesson = $item['current_lesson'] ?? $lessons->first();
                $rawDescEn = strip_tags((string) ($course->description ?: 'Complete the lessons and assessments in this course.'));
                $rawDescKm = strip_tags((string) ($course->description_kh ?: $rawDescEn));
                $courseScores = $quizScoresByCourse[$course->id] ?? [];
                $score = !empty($courseScores) ? (int) round(array_sum($courseScores) / count($courseScores)) : null;

                $rawSteps[] = [
                    'id'               => 'course-' . $course->id,
                    'course_id'        => (int) $course->id,
                    'lesson_id'        => $targetLesson ? (int) $targetLesson->id : null,
                    'lesson_ids'       => $lessons->pluck('id')->map(fn ($id) => (int) $id)->all(),
                    'title_en'         => $courseEn,
                    'title_km'         => $courseKm,
                    'category_en'      => (string) ($course->major?->name ?? 'Enrolled Course'),
                    'category_km'      => (string) ($course->major?->name_kh ?: ($course->major?->name ?? 'វគ្គសិក្សាដែលបានចុះឈ្មោះ')),
                    'isCourseStep'     => true,
                    'progress'         => (int) ($item['percent'] ?? 0),
                    'score'            => $score,
                    'duration'         => $totalSecs > 0 ? $this->formatSecondsShort($totalSecs) : max(1, (int) ($item['lessons_total'] ?? 1)) * 20 . ' min',
                    'desc_en'          => Str::limit(trim($rawDescEn), 140),
                    'desc_km'          => Str::limit(trim($rawDescKm), 140),
                    'skills_en'        => $lessons->take(4)->map(fn (Lesson $l) => (string) $l->title)->values()->all(),
                    'skills_km'        => $lessons->take(4)->map(fn (Lesson $l) => (string) ($l->kh_title ?: $l->title))->values()->all(),
                    'prerequisites_en' => $prevTitleEn,
                    'prerequisites_km' => $prevTitleKm,
                    'learnUrl'         => $targetLesson ? "/student/my-courses/{$course->id}/lessons/{$targetLesson->id}" : "/student/my-courses/{$course->id}/continue",
                    'overviewUrl'      => "/student/courses/{$course->id}/overview",
                    'askAiUrl'         => '/student/ai-tutor?' . http_build_query(array_filter(['course' => $course->id, 'lesson' => $targetLesson?->id])),
                ];
                $prevTitleEn = $courseEn;
                $prevTitleKm = $courseKm;
            }
        }

        // Assign statuses: Green (COMPLETED), Purple (CURRENT), Yellow (AI_RECOMMENDED), or UPCOMING
        $cappedSteps = array_slice($rawSteps, 0, !empty($orderedCatalog) ? 5 : 6);
        $currentIndex = null;
        $aiRecommendedIndex = null;

        foreach ($cappedSteps as $idx => $step) {
            if ((int) $step['progress'] >= 100) {
                continue;
            }
            if ($currentIndex === null) {
                $currentIndex = $idx;
                continue;
            }
            if ($aiRecLessonId && in_array($aiRecLessonId, $step['lesson_ids'] ?? [], true)) {
                $aiRecommendedIndex = $idx;
                break;
            }
        }

        if ($aiRecommendedIndex === null && empty($orderedCatalog)) {
            foreach ($cappedSteps as $idx => $step) {
                if ((int) $step['progress'] < 100 && $idx !== $currentIndex) {
                    $aiRecommendedIndex = $idx;
                    break;
                }
            }
        }

        $finalSteps = [];
        foreach ($cappedSteps as $idx => $step) {
            if ((int) $step['progress'] >= 100) {
                $status = 'COMPLETED';
            } elseif ($idx === $currentIndex) {
                $status = 'CURRENT';
            } elseif ($idx === $aiRecommendedIndex) {
                $status = 'AI_RECOMMENDED';
            } else {
                $status = 'UPCOMING';
            }
            unset($step['lesson_ids']);
            $step['status'] = $status;
            $finalSteps[] = $step;
        }

        // Append next real published course from `courses` table as AI_RECOMMENDED (Yellow)
        if (!empty($orderedCatalog)) {
            $nextRec = $orderedCatalog[0];
            $finalSteps[] = [
                'id'               => 'rec-course-' . $nextRec['id'],
                'course_id'        => (int) $nextRec['id'],
                'lesson_id'        => null,
                'title_en'         => (string) ($nextRec['title_en'] ?? $nextRec['title']),
                'title_km'         => (string) ($nextRec['title_km'] ?? $nextRec['title']),
                'category_en'      => (string) ($nextRec['major_en'] ?? 'Next Recommended'),
                'category_km'      => (string) ($nextRec['major_km'] ?? 'វគ្គណែនាំបន្ទាប់'),
                'status'           => 'AI_RECOMMENDED',
                'isCourseStep'     => true,
                'progress'         => 0,
                'score'            => null,
                'duration'         => ucfirst((string) ($nextRec['level'] ?? 'beginner')),
                'desc_en'          => (string) ($nextRec['desc_en'] ?? 'Recommended next course from the SPI catalog to continue your track.'),
                'desc_km'          => (string) ($nextRec['desc_km'] ?? 'វគ្គសិក្សាបន្ទាប់ពីតារាងវគ្គសិក្សាពិត ដែល AI ណែនាំសម្រាប់អ្នក។'),
                'skills_en'        => [],
                'skills_km'        => [],
                'prerequisites_en' => $prevTitleEn,
                'prerequisites_km' => $prevTitleKm,
                'learnUrl'         => (string) $nextRec['url'],
                'overviewUrl'      => (string) $nextRec['url'],
                'askAiUrl'         => '/student/ai-tutor',
            ];
        }

        return $finalSteps;
    }

    protected function collectCourseLessonsOrdered(Course $course): Collection
    {
        $lessons = collect();
        foreach (($course->modules ?? collect())->sortBy('order') as $mod) {
            foreach (($mod->lessons ?? collect())->sortBy('order') as $l) {
                $lessons->push($l);
            }
        }
        if ($lessons->isEmpty()) {
            $lessons = ($course->lessons ?? collect())->sortBy('order')->values();
        }
        return $lessons->unique('id')->values();
    }

    protected function computeGroupProgress(Collection $groupLessons, Collection $progressRecords, bool $courseCompleted): array
    {
        $lCount = $groupLessons->count();
        $doneCount = 0;
        $pctSum = 0;
        $totalSecs = 0;
        $nextIncomplete = null;

        foreach ($groupLessons as $lesson) {
            $totalSecs += (int) ($lesson->duration_seconds ?? 0);
            /** @var LessonProgress|null $lp */
            $lp = $progressRecords->get($lesson->id);
            $isDone = $lp && (!empty($lp->completed_at) || (int) $lp->percent >= 90);
            if ($isDone) {
                $doneCount++;
                $pctSum += 100;
            } else {
                if (!$nextIncomplete) {
                    $nextIncomplete = $lesson;
                }
                if ($lp && (int) $lp->percent > 0) {
                    $pctSum += min(99, (int) $lp->percent);
                }
            }
        }

        $pct = $lCount > 0 ? (int) round($pctSum / $lCount) : 0;
        if ($courseCompleted || ($lCount > 0 && $doneCount >= $lCount)) {
            $pct = 100;
        }

        return [$pct, $nextIncomplete ?: $groupLessons->first(), $totalSecs];
    }

    protected function resolvePacingMeta(
        bool $hasEnrollments,
        int $overallProgress,
        int $completedMilestones,
        int $totalMilestones,
        string $unitLabelKm,
        string $unitLabelEn,
        int $streakDays
    ): array {
        if (!$hasEnrollments) {
            return [
                'Ready to Start',
                'ត្រៀមចាប់ផ្តើម',
                'Enroll in a course to begin',
                'ចុះឈ្មោះវគ្គសិក្សាដើម្បីចាប់ផ្តើម',
                'neutral',
            ];
        }

        $subKm = "បានរៀនចប់ {$completedMilestones} ក្នុងចំណោម {$totalMilestones} {$unitLabelKm}";
        $subEn = "{$completedMilestones} of {$totalMilestones} {$unitLabelEn} completed";

        if ($overallProgress >= 100 || ($totalMilestones > 0 && $completedMilestones >= $totalMilestones)) {
            return [
                'Completed',
                'បានបញ្ចប់',
                $subEn,
                $subKm,
                'emerald',
            ];
        }

        if ($overallProgress >= 50 || $streakDays >= 3) {
            return [
                'On Schedule',
                'ទាន់កាលវិភាគ',
                $subEn,
                $subKm,
                'emerald',
            ];
        }

        if ($overallProgress > 0 || $streakDays > 0 || $completedMilestones > 0) {
            return [
                'In Progress',
                'កំពុងដំណើរការ',
                $subEn,
                $subKm,
                'blue',
            ];
        }

        return [
            'Needs Start',
            'គួរចាប់ផ្តើមរៀន',
            $subEn,
            $subKm,
            'amber',
        ];
    }

    protected function buildCurrentFocus(User $user, Collection $progressItems, Collection $progressRecords): ?array
    {
        if ($progressItems->isEmpty()) {
            return null;
        }

        $continueData = app(ContinueLearningService::class)->resolve($user);
        $activeCourse = $continueData['course'] ?? null;
        $activeLesson = $continueData['lesson'] ?? null;

        $activeItem = $activeCourse
            ? $progressItems->first(fn (array $item) => (int) $item['course']->id === (int) $activeCourse->id)
            : null;

        if (!$activeItem) {
            $activeItem = $progressItems
                ->filter(fn (array $item) => ($item['state'] ?? '') !== 'completed')
                ->sortByDesc('last_activity_ms')
                ->first()
                ?? $progressItems->first();
            $activeCourse = $activeItem['course'];
            $activeLesson = $activeItem['current_lesson'] ?? null;
        }

        /** @var Course $activeCourse */
        $courseEn = (string) $activeCourse->title;
        $courseKm = (string) ($activeCourse->title_kh ?: $courseEn);

        $modObj = ($activeLesson && $activeCourse->relationLoaded('modules'))
            ? $activeCourse->modules->firstWhere('id', $activeLesson->module_id)
            : null;
        $modEn = (string) ($modObj?->title ?: ($activeItem['current_module_title'] ?? 'Course Curriculum'));
        $modKm = (string) ($modObj?->kh_title ?: $modEn);

        $allCompleted = !empty($continueData['all_completed']) || (($activeItem['state'] ?? '') === 'completed');

        $lessonTitleEn = $activeLesson?->title
            ?: ($allCompleted ? 'All Lessons Completed' : 'Start First Lesson');
        $lessonTitleKm = $activeLesson?->kh_title
            ?: ($activeLesson?->title ?: ($allCompleted ? 'បានបញ្ចប់មេរៀនទាំងអស់' : 'ចាប់ផ្តើមមេរៀនដំបូង'));

        $remainingSec = (int) ($activeLesson?->duration_seconds ?? 0);
        if ($activeLesson && $progressRecords->has($activeLesson->id)) {
            $pct = (int) ($progressRecords->get($activeLesson->id)->percent ?? 0);
            if ($remainingSec > 0 && $pct > 0 && $pct < 100) {
                $remainingSec = (int) round($remainingSec * ((100 - $pct) / 100));
            }
        }

        $remainingEn = $remainingSec > 0 ? ($this->formatSecondsShort($remainingSec) . ' left') : ($allCompleted ? 'Completed' : 'Ready');
        $remainingKm = $remainingSec > 0 ? ('នៅសល់ ' . $this->formatSecondsShort($remainingSec)) : ($allCompleted ? 'បានបញ្ចប់' : 'ត្រៀមរួច');

        $lastActiveEn = $activeItem['last_activity_at'] ?: 'Not started yet';
        $lastActiveKm = $activeItem['last_activity_at'] ?: 'មិនទាន់ចាប់ផ្តើម';

        $learnUrl = $activeLesson
            ? "/student/my-courses/{$activeCourse->id}/lessons/{$activeLesson->id}"
            : "/student/my-courses/{$activeCourse->id}/continue";

        return [
            'courseId'       => (int) $activeCourse->id,
            'courseTitle_en' => $courseEn,
            'courseTitle_km' => $courseKm,
            'moduleTitle_en' => $modEn,
            'moduleTitle_km' => $modKm,
            'lessonId'       => $activeLesson?->id ? (int) $activeLesson->id : null,
            'lessonTitle_en' => (string) $lessonTitleEn,
            'lessonTitle_km' => (string) $lessonTitleKm,
            'progress'       => (int) ($activeItem['percent'] ?? 0),
            'lessonsDone'    => (int) ($activeItem['lessons_done'] ?? 0),
            'lessonsTotal'   => (int) ($activeItem['lessons_total'] ?? 0),
            'lastActive_en'  => $lastActiveEn,
            'lastActive_km'  => $lastActiveKm,
            'remaining_en'   => $remainingEn,
            'remaining_km'   => $remainingKm,
            'allCompleted'   => $allCompleted,
            'learnUrl'       => $learnUrl,
            'overviewUrl'    => "/student/courses/{$activeCourse->id}/overview",
            'askAiUrl'       => '/student/ai-tutor?' . http_build_query(array_filter([
                'course' => $activeCourse->id,
                'lesson' => $activeLesson?->id,
            ])),
        ];
    }

    protected function buildPracticeQuestions(array $enrolledIds, array $weakTopics): array
    {
        if (empty($enrolledIds) || !Schema::hasTable('questions') || !Schema::hasTable('quizzes')) {
            return [];
        }

        $weakQuizIds = collect($weakTopics)
            ->pluck('quizId')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $questions = Question::with('quiz')
            ->whereIn('type', ['mcq', 'multiple_choice', 'true_false'])
            ->whereHas('quiz', fn ($q) => $q->whereIn('course_id', $enrolledIds))
            ->get()
            ->sortByDesc(fn (Question $q) => in_array((int) $q->quiz_id, $weakQuizIds, true) ? 1 : 0)
            ->values();

        $items = [];
        foreach ($questions as $q) {
            $rawOpts = is_array($q->options) ? array_values(array_filter(array_map('strval', $q->options), fn ($v) => trim($v) !== '')) : [];
            if ($q->type === 'true_false' && empty($rawOpts)) {
                $rawOpts = ['True', 'False'];
            }
            if (count($rawOpts) < 2) {
                continue;
            }

            $correctIndex = $this->resolveCorrectOptionIndex($q->correct_answer, $rawOpts);
            if ($correctIndex === null) {
                continue;
            }

            $items[] = [
                'id'           => (int) $q->id,
                'quizId'       => (int) $q->quiz_id,
                'quizTitle'    => (string) ($q->quiz?->title ?? 'Practice Drill'),
                'question'     => (string) $q->question,
                'options'      => $rawOpts,
                'correctIndex' => $correctIndex,
            ];

            if (count($items) >= 5) {
                break;
            }
        }

        return $items;
    }

    protected function resolveCorrectOptionIndex(mixed $rawCorrect, array $options): ?int
    {
        if (empty($options) || $rawCorrect === null) {
            return null;
        }

        $candidates = [];
        if (is_array($rawCorrect)) {
            foreach ($rawCorrect as $item) {
                if (is_scalar($item)) {
                    $candidates[] = trim((string) $item);
                }
            }
        } elseif (is_scalar($rawCorrect)) {
            $candidates[] = trim((string) $rawCorrect);
        } elseif (is_bool($rawCorrect)) {
            $candidates[] = $rawCorrect ? 'True' : 'False';
        }

        if (empty($candidates)) {
            return null;
        }

        $target = mb_strtolower($candidates[0], 'UTF-8');

        foreach ($options as $idx => $opt) {
            if (mb_strtolower(trim($opt), 'UTF-8') === $target) {
                return (int) $idx;
            }
        }

        if (is_numeric($target)) {
            $num = (int) $target;
            if ($num >= 0 && $num < count($options)) {
                return $num;
            }
        }
        if (strlen($target) === 1 && $target >= 'a' && $target <= 'z') {
            $letterIdx = ord($target) - ord('a');
            if ($letterIdx >= 0 && $letterIdx < count($options)) {
                return $letterIdx;
            }
        }

        return null;
    }

    protected function buildAiNextStep(
        bool $hasEnrollments,
        array $weakTopics,
        ?AiRecommendation $latestPathRec,
        ?array $currentFocus,
        array $recommendedCourses,
        array $practiceQuestions
    ): ?array {
        $canDrill = !empty($practiceQuestions);
        $aiPayload = is_array($latestPathRec?->ai_payload) ? $latestPathRec->ai_payload : [];

        if (!empty($aiPayload) && (!empty($aiPayload['next_step_title_km']) || !empty($aiPayload['next_step_title_en']))) {
            $recCourseId = isset($aiPayload['recommended_course_id']) && is_numeric($aiPayload['recommended_course_id'])
                ? (int) $aiPayload['recommended_course_id']
                : null;
            $recLessonId = isset($aiPayload['recommended_lesson_id']) && is_numeric($aiPayload['recommended_lesson_id'])
                ? (int) $aiPayload['recommended_lesson_id']
                : null;

            $primaryHref = '/student/browse';
            $primaryLabelKm = 'ស្វែងរកវគ្គសិក្សា';
            $primaryLabelEn = 'Browse Catalog';
            $askAiUrl = '/student/ai-tutor';

            if ($hasEnrollments) {
                $primaryHref = $currentFocus['learnUrl'] ?? '/student/my-courses/enrolled';
                $primaryLabelKm = $canDrill ? 'ចាប់ផ្តើមលំហាត់ខ្លី' : 'បន្តមេរៀន';
                $primaryLabelEn = $canDrill ? 'Start Quick Drill' : 'Resume Lesson';
                $askAiUrl = $currentFocus['askAiUrl'] ?? '/student/ai-tutor';

                if ($recCourseId && $recLessonId) {
                    $primaryHref = "/student/my-courses/{$recCourseId}/lessons/{$recLessonId}";
                    $askAiUrl = '/student/ai-tutor?' . http_build_query(['course' => $recCourseId, 'lesson' => $recLessonId]);
                } elseif (!empty($weakTopics)) {
                    $primaryHref = (string) $weakTopics[0]['practiceUrl'];
                    $askAiUrl = (string) $weakTopics[0]['askAiUrl'];
                }
            } elseif ($recCourseId) {
                $primaryHref = "/student/courses/{$recCourseId}/overview";
                $primaryLabelKm = 'មើលវគ្គសិក្សា';
                $primaryLabelEn = 'View Course';
            }

            return [
                'badge_en'         => (string) ($aiPayload['badge_en'] ?? 'AI Next Step'),
                'badge_km'         => (string) ($aiPayload['badge_km'] ?? 'ជំហានបន្ទាប់ពី AI'),
                'title_en'         => (string) ($aiPayload['next_step_title_en'] ?: $aiPayload['next_step_title_km']),
                'title_km'         => (string) ($aiPayload['next_step_title_km'] ?: $aiPayload['next_step_title_en']),
                'reason_en'        => (string) ($aiPayload['next_step_reason_en'] ?: ($aiPayload['desc_en'] ?? '')),
                'reason_km'        => (string) ($aiPayload['next_step_reason_km'] ?: ($aiPayload['desc_km'] ?? '')),
                'primaryLabel_en'  => $primaryLabelEn,
                'primaryLabel_km'  => $primaryLabelKm,
                'primaryHref'      => $primaryHref,
                'askAiUrl'         => $askAiUrl,
                'canPracticeDrill' => $hasEnrollments && $canDrill,
            ];
        }

        if (!empty($weakTopics)) {
            $topWeak = $weakTopics[0];
            return [
                'badge_en'         => 'Weak Topic Focus',
                'badge_km'         => 'ពង្រឹងចំណុចខ្សោយ',
                'title_en'         => "Review {$topWeak['topic_en']} ({$topWeak['score']}%)",
                'title_km'         => "រំលឹកឡើងវិញ {$topWeak['topic_km']} ({$topWeak['score']}%)",
                'reason_en'        => $topWeak['reason_en'],
                'reason_km'        => $topWeak['reason_km'],
                'primaryLabel_en'  => $canDrill ? 'Start Quick Drill' : 'Retake Quiz',
                'primaryLabel_km'  => $canDrill ? 'ចាប់ផ្តើមលំហាត់ខ្លី' : 'សាកល្បង Quiz ម្តងទៀត',
                'primaryHref'      => (string) $topWeak['practiceUrl'],
                'askAiUrl'         => (string) $topWeak['askAiUrl'],
                'canPracticeDrill' => $canDrill,
            ];
        }

        if ($latestPathRec && $latestPathRec->lesson) {
            $lesson = $latestPathRec->lesson;
            $course = $lesson->course;
            $lTitleEn = (string) $lesson->title;
            $lTitleKm = (string) ($lesson->kh_title ?: $lTitleEn);
            $cId = (int) $lesson->course_id;
            return [
                'badge_en'         => 'AI Recommendation',
                'badge_km'         => 'AI ណែនាំ',
                'title_en'         => "Next Up: {$lTitleEn}",
                'title_km'         => "ជំហានបន្ទាប់៖ {$lTitleKm}",
                'reason_en'        => (string) ($latestPathRec->reason ?: 'Selected from your active learning history.'),
                'reason_km'        => (string) ($latestPathRec->reason ?: 'បានជ្រើសរើសផ្អែកលើវឌ្ឍនភាពសិក្សារបស់អ្នក។'),
                'primaryLabel_en'  => $canDrill ? 'Start Quick Drill' : 'Open Lesson',
                'primaryLabel_km'  => $canDrill ? 'ចាប់ផ្តើមលំហាត់ខ្លី' : 'ចូលមើលមេរៀន',
                'primaryHref'      => $course ? "/student/my-courses/{$cId}/lessons/{$lesson->id}" : '/student/my-courses/enrolled',
                'askAiUrl'         => '/student/ai-tutor?' . http_build_query(['course' => $cId, 'lesson' => $lesson->id]),
                'canPracticeDrill' => $canDrill,
            ];
        }

        if ($currentFocus) {
            return [
                'badge_en'         => 'Next Milestone',
                'badge_km'         => 'ជំហានបន្ទាប់',
                'title_en'         => "Focus on {$currentFocus['lessonTitle_en']}",
                'title_km'         => "បន្តមេរៀន៖ {$currentFocus['lessonTitle_km']}",
                'reason_en'        => "Currently at {$currentFocus['progress']}% in {$currentFocus['courseTitle_en']}. Continuing keeps your path momentum strong.",
                'reason_km'        => "អ្នកសម្រេចបាន {$currentFocus['progress']}% ក្នុង {$currentFocus['courseTitle_km']}។ បន្តរៀនដើម្បីបញ្ចប់ជំហាននេះ។",
                'primaryLabel_en'  => $canDrill ? 'Start Quick Drill' : 'Resume Lesson',
                'primaryLabel_km'  => $canDrill ? 'ចាប់ផ្តើមលំហាត់ខ្លី' : 'បន្តមេរៀន',
                'primaryHref'      => (string) $currentFocus['learnUrl'],
                'askAiUrl'         => (string) $currentFocus['askAiUrl'],
                'canPracticeDrill' => $canDrill,
            ];
        }

        if (!$hasEnrollments && !empty($recommendedCourses)) {
            $firstRec = $recommendedCourses[0];
            return [
                'badge_en'         => 'Recommended Starter Course',
                'badge_km'         => 'វគ្គសិក្សាណែនាំដំបូង',
                'title_en'         => (string) ($firstRec['title_en'] ?? $firstRec['title']),
                'title_km'         => (string) ($firstRec['title_km'] ?? $firstRec['title']),
                'reason_en'        => 'A published course aligned with your track to kickstart your personalized AI learning path.',
                'reason_km'        => 'វគ្គសិក្សាស្របតាមជំនាញរបស់អ្នក ដើម្បីចាប់ផ្តើមគន្លងសិក្សាជាមួយ AI។',
                'primaryLabel_en'  => 'View Course',
                'primaryLabel_km'  => 'មើលវគ្គសិក្សា',
                'primaryHref'      => (string) $firstRec['url'],
                'askAiUrl'         => '/student/ai-tutor',
                'canPracticeDrill' => false,
            ];
        }

        return null;
    }

    protected function buildSkillProgress(Collection $progressItems, Collection $latestAttemptsByQuiz): array
    {
        if ($progressItems->isEmpty()) {
            return [];
        }

        $quizScoresByCourse = [];
        foreach ($latestAttemptsByQuiz as $att) {
            if ($att->quiz && $att->quiz->course_id && $att->score !== null) {
                $quizScoresByCourse[(int) $att->quiz->course_id][] = (float) $att->score;
            }
        }

        return $progressItems->take(6)->map(function (array $item) use ($quizScoresByCourse) {
            /** @var Course $course */
            $course = $item['course'];
            $lessonPct = (int) ($item['percent'] ?? 0);
            $scores = $quizScoresByCourse[(int) $course->id] ?? [];
            $quizAvg = !empty($scores) ? (int) round(array_sum($scores) / count($scores)) : null;

            $level = $quizAvg !== null
                ? (int) round(($lessonPct * 0.6) + ($quizAvg * 0.4))
                : $lessonPct;
            $level = max(0, min(100, $level));

            $isWarning = $quizAvg !== null && $quizAvg < 60;
            if ($isWarning) {
                $statusEn = 'Needs Attention';
                $statusKm = 'ត្រូវការពង្រឹងបន្ថែម';
            } elseif ($level >= 85) {
                $statusEn = 'Mastered';
                $statusKm = 'ស្ទាត់ជំនាញ';
            } elseif ($level >= 70) {
                $statusEn = 'Proficient';
                $statusKm = 'ល្អប្រសើរ';
            } elseif ($level >= 40) {
                $statusEn = 'In Progress';
                $statusKm = 'កំពុងអភិវឌ្ឍ';
            } elseif ($level > 0) {
                $statusEn = 'Developing';
                $statusKm = 'កំពុងចាប់ផ្តើម';
            } else {
                $statusEn = 'Not Started';
                $statusKm = 'មិនទាន់ចាប់ផ្តើម';
            }

            return [
                'courseId'     => (int) $course->id,
                'name_en'      => (string) $course->title,
                'name_km'      => (string) ($course->title_kh ?: $course->title),
                'level'        => $level,
                'status_en'    => $statusEn,
                'status_km'    => $statusKm,
                'lessonsDone'  => (int) ($item['lessons_done'] ?? 0),
                'lessonsTotal' => (int) ($item['lessons_total'] ?? 0),
                'quizAvg'      => $quizAvg,
                'isWarning'    => $isWarning,
                'href'         => "/student/courses/{$course->id}/overview",
            ];
        })->values()->all();
    }

    /**
     * Build Top AI Banner prioritizing persisted `ai_recommendations` rows from `elms-system`.
     */
    protected function buildAiBanner(
        bool $hasEnrollments,
        int $overallProgress,
        array $weakTopics,
        ?AiRecommendation $latestPathRec,
        ?array $currentFocus,
        array $recommendedCourses
    ): array {
        $analyzedAt = $latestPathRec?->created_at
            ? Carbon::parse($latestPathRec->created_at)->timezone(DashboardAnalyticsService::TIMEZONE)->format('d/m/Y H:i')
            : null;

        if ($latestPathRec) {
            $payload = is_array($latestPathRec->ai_payload) ? $latestPathRec->ai_payload : [];

            if (!empty($payload) && (!empty($payload['title_km']) || !empty($payload['title_en']))) {
                $recCourseId = isset($payload['recommended_course_id']) && is_numeric($payload['recommended_course_id'])
                    ? (int) $payload['recommended_course_id']
                    : null;
                $recLessonId = isset($payload['recommended_lesson_id']) && is_numeric($payload['recommended_lesson_id'])
                    ? (int) $payload['recommended_lesson_id']
                    : ($latestPathRec->lesson_id ? (int) $latestPathRec->lesson_id : null);

                if (!$hasEnrollments) {
                    $primaryLabelEn = 'Browse Courses';
                    $primaryLabelKm = 'ស្វែងរកវគ្គសិក្សា';
                    $primaryHref = '/student/browse';
                    $askAiUrl = '/student/ai-tutor';
                } elseif (!empty($weakTopics)) {
                    $primaryLabelEn = 'Practice Weak Topic';
                    $primaryLabelKm = 'ពង្រឹងចំណុចខ្សោយ';
                    $primaryHref = (string) $weakTopics[0]['practiceUrl'];
                    $askAiUrl = (string) $weakTopics[0]['askAiUrl'];
                } elseif ($recCourseId && $recLessonId) {
                    $primaryLabelEn = 'Continue Learning';
                    $primaryLabelKm = 'បន្តការសិក្សា';
                    $primaryHref = "/student/my-courses/{$recCourseId}/lessons/{$recLessonId}";
                    $askAiUrl = '/student/ai-tutor?' . http_build_query(['course' => $recCourseId, 'lesson' => $recLessonId]);
                } elseif ($currentFocus) {
                    $primaryLabelEn = 'Continue Learning';
                    $primaryLabelKm = 'បន្តការសិក្សា';
                    $primaryHref = (string) $currentFocus['learnUrl'];
                    $askAiUrl = (string) $currentFocus['askAiUrl'];
                } else {
                    $primaryLabelEn = 'Browse Courses';
                    $primaryLabelKm = 'ស្វែងរកវគ្គសិក្សា';
                    $primaryHref = '/student/browse';
                    $askAiUrl = '/student/ai-tutor';
                }

                return [
                    'badge_en'        => (string) ($payload['badge_en'] ?? 'AI Recommendation'),
                    'badge_km'        => (string) ($payload['badge_km'] ?? 'ការណែនាំពី AI'),
                    'title_en'        => (string) ($payload['title_en'] ?: $payload['title_km']),
                    'title_km'        => (string) ($payload['title_km'] ?: $payload['title_en']),
                    'desc_en'         => (string) ($payload['desc_en'] ?: $payload['desc_km']),
                    'desc_km'         => (string) ($payload['desc_km'] ?: $payload['desc_en']),
                    'primaryLabel_en' => $primaryLabelEn,
                    'primaryLabel_km' => $primaryLabelKm,
                    'primaryHref'     => $primaryHref,
                    'askAiUrl'        => $askAiUrl,
                    'tone'            => (string) ($payload['tone'] ?? ($hasEnrollments ? 'purple' : 'neutral')),
                    'analyzedAt'      => $analyzedAt,
                    'provider'        => $payload['provider'] ?? null,
                    'isFromAiDb'      => true,
                ];
            }

            // Standard `ai_recommendations` row (without structured `ai_payload`)
            if ($hasEnrollments && trim((string) $latestPathRec->reason) !== '') {
                $lesson = $latestPathRec->lesson;
                $primaryHref = ($lesson && $lesson->course_id)
                    ? "/student/my-courses/{$lesson->course_id}/lessons/{$lesson->id}"
                    : ($currentFocus['learnUrl'] ?? '/student/my-courses/enrolled');
                $askAiUrl = ($lesson && $lesson->course_id)
                    ? '/student/ai-tutor?' . http_build_query(['course' => $lesson->course_id, 'lesson' => $lesson->id])
                    : ($currentFocus['askAiUrl'] ?? '/student/ai-tutor');

                return [
                    'badge_en'        => ucfirst(str_replace('_', ' ', (string) ($latestPathRec->type ?: 'AI Recommendation'))),
                    'badge_km'        => 'ការណែនាំពី AI',
                    'title_en'        => (string) $latestPathRec->reason,
                    'title_km'        => (string) $latestPathRec->reason,
                    'desc_en'         => $currentFocus
                        ? "Active course progress: {$currentFocus['progress']}% ({$currentFocus['lessonsDone']}/{$currentFocus['lessonsTotal']} lessons)."
                        : 'Saved recommendation from your recent study history.',
                    'desc_km'         => $currentFocus
                        ? "វឌ្ឍនភាពបច្ចុប្បន្ន៖ {$currentFocus['progress']}% ({$currentFocus['lessonsDone']}/{$currentFocus['lessonsTotal']} មេរៀន)។"
                        : 'ការណែនាំដែលបានរក្សាទុកពីទិន្នន័យសិក្សារបស់អ្នក។',
                    'primaryLabel_en' => 'Continue Learning',
                    'primaryLabel_km' => 'បន្តការសិក្សា',
                    'primaryHref'     => $primaryHref,
                    'askAiUrl'        => $askAiUrl,
                    'tone'            => $latestPathRec->type === 'weak_topic' ? 'amber' : 'purple',
                    'analyzedAt'      => $analyzedAt,
                    'provider'        => null,
                    'isFromAiDb'      => true,
                ];
            }
        }

        if (!$hasEnrollments) {
            $firstRec = $recommendedCourses[0] ?? null;
            return [
                'badge_en'        => 'Ready to Begin',
                'badge_km'        => 'ត្រៀមចាប់ផ្តើម',
                'title_en'        => 'Enroll in your first course to unlock your personalized roadmap',
                'title_km'        => 'ចុះឈ្មោះវគ្គសិក្សាដំបូង ដើម្បីបើកផែនទីសិក្សាផ្ទាល់ខ្លួនរបស់អ្នក',
                'desc_en'         => $firstRec
                    ? "Recommended starter course: {$firstRec['title_en']}. Or chat with AI Tutor anytime for general study guidance."
                    : 'Browse the SPI course catalog to start building your milestones, skill mastery, and daily study plan.',
                'desc_km'         => $firstRec
                    ? "វគ្គសិក្សាណែនាំដំបូង៖ {$firstRec['title_km']}។ ឬអ្នកអាចសាកសួរជំនួយការសិក្សា AI បានគ្រប់ពេល។"
                    : 'ស្វែងរកវគ្គសិក្សាក្នុងប្រព័ន្ធ SPI ដើម្បីចាប់ផ្តើមកត់ត្រាវឌ្ឍនភាព និងផែនការសិក្សាប្រចាំថ្ងៃ។',
                'primaryLabel_en' => 'Browse Courses',
                'primaryLabel_km' => 'ស្វែងរកវគ្គសិក្សា',
                'primaryHref'     => '/student/browse',
                'askAiUrl'        => '/student/ai-tutor',
                'tone'            => 'neutral',
                'analyzedAt'      => null,
                'provider'        => null,
                'isFromAiDb'      => false,
            ];
        }

        if (!empty($weakTopics)) {
            $weak = $weakTopics[0];
            return [
                'badge_en'        => 'Action Required',
                'badge_km'        => 'គួរពង្រឹងបន្ថែម',
                'title_en'        => "Focus on {$weak['topic_en']} ({$weak['score']}%)",
                'title_km'        => "ផ្តោតលើ {$weak['topic_km']} ({$weak['score']}%)",
                'desc_en'         => "Based on your {$weak['course_en']} quiz attempt, reviewing this topic first will boost your overall readiness.",
                'desc_km'         => "ផ្អែកលើលទ្ធផល Quiz ក្នុងវគ្គ {$weak['course_km']} ការរំលឹកប្រធានបទនេះជាមុននឹងជួយបង្កើនការយល់ដឹងរបស់អ្នក។",
                'primaryLabel_en' => 'Practice Now',
                'primaryLabel_km' => 'អនុវត្តឥឡូវនេះ',
                'primaryHref'     => (string) $weak['practiceUrl'],
                'askAiUrl'        => (string) $weak['askAiUrl'],
                'tone'            => 'amber',
                'analyzedAt'      => null,
                'provider'        => null,
                'isFromAiDb'      => false,
            ];
        }

        if ($currentFocus && empty($currentFocus['allCompleted'])) {
            return [
                'badge_en'        => 'On Track',
                'badge_km'        => 'កំពុងបន្តល្អ',
                'title_en'        => "Continue {$currentFocus['lessonTitle_en']}",
                'title_km'        => "បន្តមេរៀន៖ {$currentFocus['lessonTitle_km']}",
                'desc_en'         => "You are at {$currentFocus['progress']}% in {$currentFocus['courseTitle_en']} ({$currentFocus['lessonsDone']}/{$currentFocus['lessonsTotal']} lessons). Keep going!",
                'desc_km'         => "អ្នកសម្រេចបាន {$currentFocus['progress']}% ក្នុង {$currentFocus['courseTitle_km']} ({$currentFocus['lessonsDone']}/{$currentFocus['lessonsTotal']} មេរៀន)។",
                'primaryLabel_en' => 'Continue Learning',
                'primaryLabel_km' => 'បន្តការសិក្សា',
                'primaryHref'     => (string) $currentFocus['learnUrl'],
                'askAiUrl'        => (string) $currentFocus['askAiUrl'],
                'tone'            => 'purple',
                'analyzedAt'      => null,
                'provider'        => null,
                'isFromAiDb'      => false,
            ];
        }

        return [
            'badge_en'        => 'Milestone Achieved',
            'badge_km'        => 'ជោគជ័យ',
            'title_en'        => "All enrolled courses completed ({$overallProgress}%)!",
            'title_km'        => "អ្នកបានបញ្ចប់វគ្គសិក្សាទាំងអស់ ({$overallProgress}%)!",
            'desc_en'         => 'Explore new courses in the catalog or review completed quizzes to keep your skills sharp.',
            'desc_km'         => 'ស្វែងរកវគ្គសិក្សាថ្មីៗបន្ថែម ឬរំលឹក Quiz ចាស់ៗដើម្បីពង្រឹងចំណេះដឹង។',
            'primaryLabel_en' => 'Explore Next Course',
            'primaryLabel_km' => 'ស្វែងរកវគ្គថ្មី',
            'primaryHref'     => !empty($recommendedCourses[0]['url']) ? (string) $recommendedCourses[0]['url'] : '/student/browse',
            'askAiUrl'        => '/student/ai-tutor',
            'tone'            => 'emerald',
            'analyzedAt'      => null,
            'provider'        => null,
            'isFromAiDb'      => false,
        ];
    }

    protected function buildTodayGoals(
        User $user,
        ?array $currentFocus,
        array $weakTopics,
        bool $hasEnrollments
    ): array {
        $items = [];
        if (Schema::hasTable('student_goals')) {
            $rows = StudentGoal::where('user_id', $user->id)
                ->orderBy('id')
                ->get();

            $items = $rows->map(fn (StudentGoal $g) => [
                'id'          => (string) $g->id,
                'title'       => (string) $g->title,
                'titleKm'     => (string) ($g->title_km ?: $g->title),
                'titleEn'     => (string) $g->title,
                'isCompleted' => (bool) $g->is_completed,
                'targetRoute' => (string) ($g->target_route ?: '/student/my-courses/enrolled'),
            ])->values()->all();
        }

        $completedCount = collect($items)->where('isCompleted', true)->count();
        $totalCount = count($items);

        $suggestions = [];
        if ($currentFocus && empty($currentFocus['allCompleted'])) {
            $suggestions[] = [
                'title'        => "Complete lesson: {$currentFocus['lessonTitle_en']}",
                'title_km'     => "បញ្ចប់មេរៀន៖ {$currentFocus['lessonTitle_km']}",
                'target_route' => (string) $currentFocus['learnUrl'],
            ];
        }
        if (!empty($weakTopics)) {
            $weak = $weakTopics[0];
            $suggestions[] = [
                'title'        => "Review weak topic: {$weak['topic_en']}",
                'title_km'     => "រំលឹកចំណុចខ្សោយ៖ {$weak['topic_km']}",
                'target_route' => (string) $weak['practiceUrl'],
            ];
        }
        if (!$hasEnrollments) {
            $suggestions[] = [
                'title'        => 'Browse catalog and enroll in first course',
                'title_km'     => 'ស្វែងរក និងចុះឈ្មោះវគ្គសិក្សាដំបូង',
                'target_route' => '/student/browse',
            ];
            $suggestions[] = [
                'title'        => 'Ask AI Tutor for study roadmap advice',
                'title_km'     => 'សួរជំនួយការសិក្សា AI អំពីគម្រោងសិក្សា',
                'target_route' => '/student/ai-tutor',
            ];
        }

        return [
            'completedCount'   => $completedCount,
            'totalCount'       => $totalCount,
            'percentage'       => $totalCount > 0 ? (int) round(($completedCount / $totalCount) * 100) : 0,
            'items'            => $items,
            'quickSuggestions' => array_slice($suggestions, 0, 3),
        ];
    }

    /**
     * Compute real study duration this week (Asia/Phnom_Penh) and consecutive active day streak.
     */
    protected function computeStudyTimeAndStreak(User $user): array
    {
        $tz = DashboardAnalyticsService::TIMEZONE;
        $nowLocal = Carbon::now($tz);
        $weekStartLocal = $nowLocal->copy()->startOfWeek();
        $weekStartDateStr = $weekStartLocal->toDateString();
        $weekStartAppTz = $weekStartLocal->copy()->setTimezone(config('app.timezone', 'UTC'));

        $sdByDate = [];
        $progressByDate = [];
        $hasRealProgressThisWeek = false;
        $activeDates = [];

        if (Schema::hasTable('study_daily')) {
            try {
                $sdRows = StudyDaily::where('user_id', $user->id)
                    ->where('seconds', '>', 0)
                    ->get(['study_date', 'seconds']);

                foreach ($sdRows as $row) {
                    $dateKey = $row->study_date instanceof Carbon
                        ? $row->study_date->format('Y-m-d')
                        : Carbon::parse($row->study_date)->format('Y-m-d');
                    $secs = min(DashboardAnalyticsService::MAX_DAILY_SECONDS, max(0, (int) $row->seconds));
                    if ($secs >= 60) {
                        $activeDates[$dateKey] = true;
                    }
                    if ($dateKey >= $weekStartDateStr) {
                        $sdByDate[$dateKey] = ($sdByDate[$dateKey] ?? 0) + $secs;
                    }
                }
            } catch (\Throwable $e) {
                // StudyDaily fallback
            }
        }

        if (Schema::hasTable('lesson_progress')) {
            try {
                $lpRows = LessonProgress::with('lesson')
                    ->where('user_id', $user->id)
                    ->where('updated_at', '>=', $weekStartAppTz)
                    ->get();

                foreach ($lpRows as $lp) {
                    $sec = (int) ($lp->seconds_watched ?? 0);
                    if ($sec <= 0 && ($lp->completed_at !== null || (int) $lp->percent > 0)) {
                        $dur = max(300, (int) ($lp->lesson?->duration_seconds ?? 600));
                        $sec = ($lp->completed_at !== null || (int) $lp->percent >= 90)
                            ? $dur
                            : (int) round(((int) $lp->percent / 100) * $dur);
                    }
                    if ($sec > 0) {
                        $hasRealProgressThisWeek = true;
                        $dateKey = Carbon::parse($lp->updated_at ?? $lp->completed_at ?? $lp->created_at)
                            ->timezone($tz)
                            ->format('Y-m-d');
                        $progressByDate[$dateKey] = ($progressByDate[$dateKey] ?? 0) + $sec;
                        $activeDates[$dateKey] = true;
                    }
                }
            } catch (\Throwable $e) {
                // Ignore
            }
        }

        if (Schema::hasTable('quiz_attempts')) {
            try {
                $qaRows = QuizAttempt::with('quiz')
                    ->where('user_id', $user->id)
                    ->where(function ($q) use ($weekStartAppTz) {
                        $q->where('submitted_at', '>=', $weekStartAppTz)
                            ->orWhere('updated_at', '>=', $weekStartAppTz);
                    })
                    ->get();

                foreach ($qaRows as $qa) {
                    if ($qa->started_at && $qa->submitted_at) {
                        $sec = max(60, min(7200, (int) abs(Carbon::parse($qa->submitted_at)->diffInSeconds(Carbon::parse($qa->started_at)))));
                    } else {
                        $sec = max(180, min(3600, ((int) ($qa->quiz?->time_limit_minutes ?? 10)) * 60));
                    }
                    $hasRealProgressThisWeek = true;
                    $dateKey = Carbon::parse($qa->submitted_at ?? $qa->updated_at ?? $qa->created_at)
                        ->timezone($tz)
                        ->format('Y-m-d');
                    $progressByDate[$dateKey] = ($progressByDate[$dateKey] ?? 0) + $sec;
                    $activeDates[$dateKey] = true;
                }
            } catch (\Throwable $e) {
                // Ignore
            }
        }

        $weekSeconds = 0;
        foreach (array_unique(array_merge(array_keys($sdByDate), array_keys($progressByDate))) as $dateKey) {
            $weekSeconds += min(
                DashboardAnalyticsService::MAX_DAILY_SECONDS,
                max($sdByDate[$dateKey] ?? 0, $progressByDate[$dateKey] ?? 0)
            );
        }

        if ($weekSeconds >= 60 || ($weekSeconds > 0 && $hasRealProgressThisWeek)) {
            $hours = intdiv($weekSeconds, 3600);
            $mins = intdiv($weekSeconds % 3600, 60);
            if ($hours === 0 && $mins === 0) {
                $mins = 1;
            }
            $formattedWeek = "{$hours}h {$mins}m";
        } else {
            $formattedWeek = '0h 0m';
        }

        $streakDays = 0;
        $todayKey = $nowLocal->toDateString();
        $yesterdayKey = $nowLocal->copy()->subDay()->toDateString();
        $cursor = isset($activeDates[$todayKey])
            ? $nowLocal->copy()
            : (isset($activeDates[$yesterdayKey]) ? $nowLocal->copy()->subDay() : null);

        if ($cursor !== null) {
            for ($i = 0; $i < 365; $i++) {
                $dKey = $cursor->toDateString();
                if (!isset($activeDates[$dKey])) {
                    break;
                }
                $streakDays++;
                $cursor->subDay();
            }
        }

        return [$formattedWeek, $streakDays];
    }

    protected function formatSecondsShort(int $seconds): string
    {
        if ($seconds <= 0) {
            return '15 min';
        }
        $hours = intdiv($seconds, 3600);
        $mins = max(1, intdiv($seconds % 3600, 60));
        return $hours > 0 ? "{$hours}h {$mins}m" : "{$mins} min";
    }
}
