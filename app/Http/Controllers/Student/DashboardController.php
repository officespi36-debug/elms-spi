<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AiRecommendation;
use App\Models\Announcement;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Deadline;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Semester;
use App\Models\StudentGoal;
use App\Models\StudyDaily;
use App\Models\User;
use App\Services\ContinueLearningService;
use App\Services\CourseProgressService;
use App\Services\DashboardAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $progressService = app(CourseProgressService::class);
        $analyticsService = app(DashboardAnalyticsService::class);

        // 1. Academic Term & Welcome Banner Profile from DB
        $academicTerm = $this->resolveActiveAcademicTerm();
        $studentProfile = [
            'id'         => $user->id,
            'name'       => $user->name,
            'name_kh'    => $user->name_kh ?: $user->name,
            'email'      => $user->email,
            'avatar'     => $user->avatar ? (str_starts_with($user->avatar, 'http') || str_starts_with($user->avatar, '/') ? $user->avatar : asset('storage/' . ltrim($user->avatar, '/'))) : null,
            'avatar_url' => $user->avatar_url,
        ];

        // 2. Real Enrollments & Course Progress from DB
        try {
            $enrollments = Enrollment::where('student_id', $user->id)
                ->whereIn('status', ['active', 'completed'])
                ->select(['id', 'student_id', 'course_id', 'status', 'enrolled_at', 'updated_at'])
                ->with([
                    'course:id,title,title_kh,code,teacher_id,major_id,thumbnail',
                    'course.teacher:id,name,name_kh,email,avatar',
                    'course.major:id,name,name_kh',
                    'course.modules:id,course_id,title,kh_title,order',
                    'course.modules.lessons:id,module_id,course_id,title,kh_title,order,duration_seconds',
                    'course.lessons:id,module_id,course_id,title,kh_title,order,duration_seconds',
                ])
                ->latest('updated_at')
                ->get();
        } catch (\Throwable $e) {
            $enrollments = collect();
        }

        $computedProgress = $progressService->calculateForEnrollments($user, $enrollments);
        $myCoursesList = $this->buildCoursesList($computedProgress, $progressService);

        // 3. Stats Strip computed from DB (using same single-source CourseProgressService collection)
        $statsData = $this->computeStats($user, $enrollments, $computedProgress);

        // 4. Continue Learning Course (Real DB via ContinueLearningService)
        $continueCourseData = $this->buildContinueCourse($user, $myCoursesList, $progressService);

        // 5. Recent Learning Activities strictly for Auth::user()
        $recentActivitiesList = $this->buildRecentActivities($user);

        // 6. Upcoming Quizzes & Deadlines from real DB records
        $upcomingEventsList = $this->buildUpcomingEvents($enrollments);

        // 7. Today's Goals strictly from `student_goals` DB table
        $todayGoalsList = $this->buildTodayGoalsPayload($user);

        // 8. Analytics (7D / 30D / 90D + Quiz Performance) from DB
        $win7 = $analyticsService->getWindowAnalytics($user, 7);
        $win30 = $analyticsService->getWindowAnalytics($user, 30);
        $win90 = $analyticsService->getWindowAnalytics($user, 90);

        $studyBreakdown = $win7['breakdown'];
        $quizPerformanceData = $analyticsService->getQuizPerformance($user);
        $learningOverviewData = [
            'week'      => $win7,
            'last_week' => $win30,
            'month'     => $win90,
        ];

        $statsData['learningTime'] = $win7['total'];
        $statsData['studyHours'] = (int) floor($win7['totalSeconds'] / 3600);
        $statsData['studyMinutes'] = (int) floor(($win7['totalSeconds'] % 3600) / 60);
        $statsData['averageScore'] = $quizPerformanceData['hasData'] ? (string) $quizPerformanceData['averageScore'] : '—';
        $statsData['averageScoreNum'] = $quizPerformanceData['hasData'] ? (int) $quizPerformanceData['averageScoreNum'] : 0;

        // 9. AI Recommendations strictly from `ai_recommendations` DB table
        $aiRecommendationsData = $this->buildAiRecommendations($user);

        // 10. Announcements strictly from `announcements` DB table
        [$announcementsList, $unreadAnnouncementsCount] = $this->buildAnnouncements($user);

        // 11. Achievements computed from real DB records
        $completedLessonsCount = 0;
        try {
            $completedLessonsCount = LessonProgress::where('user_id', $user->id)
                ->where(function ($q) {
                    $q->where('percent', '>=', 90)->orWhereNotNull('completed_at');
                })
                ->count();
        } catch (\Throwable $e) {
            $completedLessonsCount = 0;
        }
        $achievementsData = $this->buildAchievements(
            $user,
            $statsData,
            (int) $statsData['completedCount'],
            $completedLessonsCount
        );

        // Determine whether student has actually studied before
        $hasAnyLessonProgress = $completedLessonsCount > 0 || LessonProgress::where('user_id', $user->id)->exists();
        $hasStudiedBefore = ($statsData['enrolledCount'] > 0) && (
            $hasAnyLessonProgress ||
            ($statsData['inProgressCount'] ?? 0) > 0 ||
            ($statsData['completedCount'] ?? 0) > 0 ||
            !empty($continueCourseData) ||
            !empty($recentActivitiesList) ||
            ($statsData['studyHours'] ?? 0) > 0 ||
            ($statsData['studyMinutes'] ?? 0) > 0
        );

        $payload = [
            'studentProfile'           => $studentProfile,
            'academicTerm'             => $academicTerm,
            'announcements'            => $announcementsList,
            'unreadAnnouncementsCount' => $unreadAnnouncementsCount,
            'hasStudiedBefore'         => $hasStudiedBefore,
            'enrollments'              => $enrollments->values()->toArray(),
            'stats'                    => $statsData,
            'continueCourse'           => $continueCourseData,
            'dbCourses'                => $myCoursesList,
            'recentActivities'         => $recentActivitiesList,
            'upcomingEvents'           => $upcomingEventsList,
            'todayGoals'               => $todayGoalsList,
            'studyTimeBreakdown'       => $studyBreakdown,
            'quizPerformance'          => $quizPerformanceData,
            'learningOverview'         => $learningOverviewData,
            'aiRecommendations'        => $aiRecommendationsData,
            'achievements'             => $achievementsData,
        ];

        return Inertia::render('Student/Dashboard', $payload);
    }

    /**
     * GET /student/goals
     */
    public function getGoals(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'success'    => true,
            'todayGoals' => $this->buildTodayGoalsPayload($user),
        ]);
    }

    /**
     * POST /student/goals
     */
    public function storeGoal(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'title_km'     => ['nullable', 'string', 'max:255'],
            'target_route' => ['nullable', 'string', 'max:255'],
        ]);

        $title = trim($validated['title']);
        $titleKm = isset($validated['title_km']) && trim($validated['title_km']) !== ''
            ? trim($validated['title_km'])
            : $title;

        $goal = StudentGoal::create([
            'user_id'      => $user->id,
            'title'        => $title,
            'title_km'     => $titleKm,
            'is_completed' => false,
            'target_route' => !empty($validated['target_route']) ? trim((string) $validated['target_route']) : '/student/my-courses/enrolled',
            'goal_date'    => now(DashboardAnalyticsService::TIMEZONE)->toDateString(),
        ]);

        app(DashboardAnalyticsService::class)->invalidateUserCache($user->id);

        return response()->json([
            'success'    => true,
            'goal'       => $this->formatGoal($goal),
            'todayGoals' => $this->buildTodayGoalsPayload($user),
        ], 201);
    }

    /**
     * PATCH /student/goals/{goal}
     */
    public function toggleGoal(Request $request, StudentGoal $goal): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if ((int) $goal->user_id !== (int) $user->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        if ($request->has('is_completed')) {
            $goal->is_completed = filter_var($request->input('is_completed'), FILTER_VALIDATE_BOOLEAN);
        } else {
            $goal->is_completed = !$goal->is_completed;
        }

        if ($request->filled('title')) {
            $goal->title = trim((string) $request->input('title'));
        }
        if ($request->filled('title_km')) {
            $goal->title_km = trim((string) $request->input('title_km'));
        }

        $goal->save();
        app(DashboardAnalyticsService::class)->invalidateUserCache($user->id);

        return response()->json([
            'success'    => true,
            'goal'       => $this->formatGoal($goal),
            'todayGoals' => $this->buildTodayGoalsPayload($user),
        ]);
    }

    /**
     * DELETE /student/goals/{goal}
     */
    public function destroyGoal(Request $request, StudentGoal $goal): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if ((int) $goal->user_id !== (int) $user->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $goal->delete();
        app(DashboardAnalyticsService::class)->invalidateUserCache($user->id);

        return response()->json([
            'success'    => true,
            'todayGoals' => $this->buildTodayGoalsPayload($user),
        ]);
    }

    /**
     * Compute mathematically consistent real database stats for student dashboard.
     * Strictly from DB via Eloquent for Auth::user() with 0/empty fallbacks.
     */
    public function computeStats(User $user, ?Collection $enrollments = null, ?Collection $computedProgress = null): array
    {
        try {
            if ($computedProgress === null) {
                if ($enrollments === null) {
                    $enrollments = Enrollment::where('student_id', $user->id)
                        ->whereIn('status', ['active', 'completed'])
                        ->with([
                            'course:id,title,teacher_id,major_id',
                            'course.lessons:id,module_id,course_id,title,order,duration_seconds',
                            'course.modules:id,course_id,title,order',
                            'course.modules.lessons:id,module_id,course_id,title,order,duration_seconds',
                        ])
                        ->get();
                }
                $computedProgress = app(CourseProgressService::class)->calculateForEnrollments($user, $enrollments);
            }

            $enrolledCount = $computedProgress->count();
            $completedCount = $computedProgress->where('state', 'completed')->count();
            $inProgressCount = $computedProgress->where('state', 'in_progress')->count()
                ?: $computedProgress->where('state', 'not_started')->count();
        } catch (\Throwable $e) {
            $enrolledCount = 0;
            $inProgressCount = 0;
            $completedCount = 0;
        }

        try {
            $certsCount = Certificate::where('student_id', $user->id)->whereIn('status', ['valid', 'issued'])->count();
        } catch (\Throwable $e) {
            $certsCount = 0;
        }

        $analyticsService = app(DashboardAnalyticsService::class);
        $quiz = $analyticsService->getQuizPerformance($user);
        $win7 = $analyticsService->getWindowAnalytics($user, 7);

        $avgScoreNum = $quiz['hasData'] ? (int) $quiz['averageScoreNum'] : 0;
        $avgScoreFormatted = $quiz['hasData'] ? (string) $quiz['averageScore'] : '—';

        $totalSeconds = (int) $win7['totalSeconds'];
        $studyHours = (int) floor($totalSeconds / 3600);
        $studyMinutes = (int) floor(($totalSeconds % 3600) / 60);
        $learningTime = $win7['total'];

        return [
            'enrolledCount'     => (int) $enrolledCount,
            'inProgressCount'   => (int) $inProgressCount,
            'completedCount'    => (int) $completedCount,
            'certificatesCount' => (int) $certsCount,
            'learningTime'      => $learningTime,
            'studyHours'        => (int) $studyHours,
            'studyMinutes'      => (int) $studyMinutes,
            'totalSeconds'      => (int) $totalSeconds,
            'averageScore'      => $avgScoreFormatted,
            'averageScoreNum'   => (int) $avgScoreNum,
        ];
    }

    /**
     * Official JSON API Endpoint for asynchronous loading, skeleton, and count-up animation.
     */
    public function apiStats(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $stats = $this->computeStats($user);

        return response()->json([
            'success' => true,
            'stats'   => $stats,
        ]);
    }

    protected function resolveActiveAcademicTerm(): array
    {
        $year = null;
        $semester = null;

        try {
            if (Schema::hasTable('academic_years')) {
                $year = AcademicYear::where('is_active', true)
                    ->orWhere('status', 'active')
                    ->orderByDesc('is_active')
                    ->orderByDesc('id')
                    ->first() ?? AcademicYear::latest('id')->first();
            }

            if (Schema::hasTable('semesters')) {
                $semQuery = Semester::query();
                if ($year) {
                    $semQuery->where('academic_year_id', $year->id);
                }
                $semester = (clone $semQuery)
                    ->where(function ($q) {
                        $q->where('is_active', true)->orWhere('status', 'active');
                    })
                    ->orderByDesc('is_active')
                    ->orderByDesc('id')
                    ->first()
                    ?? Semester::where('is_active', true)->orWhere('status', 'active')->orderByDesc('is_active')->orderByDesc('id')->first();
            }
        } catch (\Throwable $e) {
            $year = null;
            $semester = null;
        }

        $yearName = trim((string) ($year?->name ?? ''));
        $semLabel = trim((string) ($semester?->semester_num ?: $semester?->name ?: ''));
        if ($semLabel !== '' && str_contains($semLabel, '—')) {
            $semLabel = trim(explode('—', $semLabel)[0]);
        }

        $enParts = array_values(array_filter([$semLabel, $yearName], fn ($v) => $v !== ''));
        $en = !empty($enParts) ? implode(' • ', $enParts) : '';

        $kmSem = $semLabel !== ''
            ? preg_replace('/Semester\s+(\d+)/i', 'ឆមាសទី $1', $semLabel)
            : '';
        $kmYear = $yearName !== ''
            ? str_replace('Academic Year', 'ឆ្នាំសិក្សា', $yearName)
            : '';

        $kmParts = array_values(array_filter([
            $this->toKhmerDigits($kmSem),
            $this->toKhmerDigits($kmYear),
        ], fn ($v) => $v !== ''));
        $km = !empty($kmParts) ? implode(' • ', $kmParts) : '';

        return [
            'id'           => $year?->id,
            'code'         => $year?->code,
            'name'         => $yearName,
            'semesterName' => $semester?->name,
            'semesterNum'  => $semester?->semester_num,
            'en'           => $en,
            'km'           => $km,
        ];
    }

    protected function toKhmerDigits(string $value): string
    {
        return strtr($value, [
            '0' => '០',
            '1' => '១',
            '2' => '២',
            '3' => '៣',
            '4' => '៤',
            '5' => '៥',
            '6' => '៦',
            '7' => '៧',
            '8' => '៨',
            '9' => '៩',
        ]);
    }

    protected function normalizeThumbnail(?string $thumb): ?string
    {
        $thumb = $thumb ? trim($thumb) : '';
        if ($thumb === '') {
            return null;
        }
        if (str_starts_with($thumb, 'http://') || str_starts_with($thumb, 'https://') || str_starts_with($thumb, '/')) {
            return $thumb;
        }
        return asset('storage/' . ltrim($thumb, '/'));
    }

    protected function buildCoursesList(Collection $computedProgress, CourseProgressService $progressService): array
    {
        $list = [];

        foreach ($computedProgress as $item) {
            /** @var Course $course */
            $course = $item['course'];
            $teacherName = $progressService->cleanTeacherName($course->teacher, $course);
            $teacherNameKh = $progressService->cleanTeacherNameKh($course->teacher, $course);

            $rawState = $item['state'] ?? 'not_started';
            $status = $rawState === 'coming_soon' ? 'no_content' : $rawState;
            $progress = (int) ($item['percent'] ?? 0);

            $titleLower = strtolower($course->title . ' ' . ($course->major->name ?? ''));
            $iconType = 'code';
            $iconColor = 'from-purple-600 to-indigo-600';
            $bgGradient = 'from-indigo-50 to-purple-50 dark:from-indigo-950/40 dark:to-purple-950/40';

            if (str_contains($titleLower, 'english') || str_contains($titleLower, 'language') || str_contains($titleLower, 'literature')) {
                $iconType = 'english';
                $iconColor = 'from-amber-500 to-orange-600';
                $bgGradient = 'from-amber-50 to-orange-50 dark:from-amber-950/40 dark:to-orange-950/40';
            } elseif (str_contains($titleLower, 'agronomy') || str_contains($titleLower, 'crop') || str_contains($titleLower, 'plant') || str_contains($titleLower, 'agriculture')) {
                $iconType = 'agriculture';
                $iconColor = 'from-emerald-500 to-teal-600';
                $bgGradient = 'from-emerald-50 to-teal-50 dark:from-emerald-950/40 dark:to-teal-950/40';
            } elseif (str_contains($titleLower, 'tourism') || str_contains($titleLower, 'hotel') || str_contains($titleLower, 'hospitality') || str_contains($titleLower, 'eco')) {
                $iconType = 'tourism';
                $iconColor = 'from-cyan-500 to-blue-600';
                $bgGradient = 'from-cyan-50 to-blue-50 dark:from-cyan-950/40 dark:to-blue-950/40';
            } elseif (str_contains($titleLower, 'social') || str_contains($titleLower, 'community')) {
                $iconType = 'social_work';
                $iconColor = 'from-rose-500 to-pink-600';
                $bgGradient = 'from-rose-50 to-pink-50 dark:from-rose-950/40 dark:to-pink-950/40';
            } elseif (str_contains($titleLower, 'database') || str_contains($titleLower, 'sql')) {
                $iconType = 'db';
                $iconColor = 'from-blue-600 to-indigo-600';
                $bgGradient = 'from-blue-50 to-indigo-50 dark:from-blue-950/40 dark:to-indigo-950/40';
            } elseif (str_contains($titleLower, 'design') || str_contains($titleLower, 'ui')) {
                $iconType = 'design';
                $iconColor = 'from-pink-500 to-purple-600';
                $bgGradient = 'from-pink-50 to-purple-50 dark:from-pink-950/40 dark:to-purple-950/40';
            }

            $currentLesson = $item['current_lesson'] ?? null;
            $continueUrl = !empty($currentLesson?->id)
                ? "/student/my-courses/{$course->id}/lessons/{$currentLesson->id}"
                : "/student/my-courses/{$course->id}/continue";

            $majorName = $course->major?->name ?? 'Information Technology';
            $majorKh = $course->major?->name_kh ?: ($course->major?->name ?? 'បច្ចេកវិទ្យាព័ត៌មាន');
            $lessonsTotal = (int) ($item['total_lessons'] ?? 0);
            $lessonsDone = (int) ($item['completed_lessons'] ?? 0);
            $lastActivity = !empty($item['latest_activity']) ? Carbon::parse($item['latest_activity'])->diffForHumans() : 'ថ្មីៗនេះ';

            $list[] = [
                'id'            => $course->id,
                'title'         => $course->title,
                'title_kh'      => $course->title_kh ?: $course->title,
                'code'          => $course->code,
                'major_name'    => $majorName,
                'major_kh'      => $majorKh,
                'lessons_total' => $lessonsTotal,
                'lessons_done'  => $lessonsDone,
                'last_accessed' => $lastActivity,
                'teacher'       => $teacherName,
                'teacher_kh'    => $teacherNameKh,
                'progress'      => $progress,
                'status'       => $status,
                'statusLabel'  => $status === 'completed'
                    ? 'Completed'
                    : ($status === 'in_progress'
                        ? 'In Progress'
                        : ($status === 'no_content' ? 'Content Coming Soon' : 'Not Started')),
                'badgeClass'   => $status === 'completed'
                    ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30'
                    : ($status === 'in_progress'
                        ? 'bg-blue-500/15 text-blue-600 dark:text-blue-400 border-blue-500/30'
                        : ($status === 'no_content'
                            ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/30'
                            : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-700')),
                'iconType'     => $iconType,
                'iconColor'    => $iconColor,
                'bgGradient'   => $bgGradient,
                'thumbnail'    => $this->normalizeThumbnail($course->thumbnail),
                'href'         => $continueUrl,
                'continue_url' => $continueUrl,
                'overview_url' => '/student/courses/' . $course->id . '/overview',
            ];
        }

        return $list;
    }

    protected function buildContinueCourse(User $user, array $myCoursesList, CourseProgressService $progressService): ?array
    {
        $continueService = app(ContinueLearningService::class);
        $currentData = $continueService->resolve($user);

        if (!$currentData || empty($currentData['course'])) {
            return null;
        }

        $activeCourse = $currentData['course'];
        $currentLesson = $currentData['lesson'] ?? null;
        $activeCourseItem = collect($myCoursesList)->firstWhere('id', $activeCourse->id);
        $allCompleted = !empty($currentData['all_completed']);

        $href = $allCompleted
            ? "/student/courses/{$activeCourse->id}/overview"
            : ($currentLesson
                ? "/student/my-courses/{$activeCourse->id}/lessons/{$currentLesson->id}"
                : "/student/my-courses/{$activeCourse->id}/continue");

        return [
            'id'            => $activeCourse->id,
            'title'         => $activeCourse->title,
            'title_kh'      => $activeCourse->title_kh ?: $activeCourse->title,
            'chapter'       => $activeCourse->major?->name ?? 'Course Curriculum',
            'chapter_kh'    => $activeCourse->major?->name_kh ?: ($activeCourse->major?->name ?? 'កម្មវិធីសិក្សា'),
            'major_name'    => $activeCourseItem['major_name'] ?? ($activeCourse->major?->name ?? 'Information Technology'),
            'major_kh'      => $activeCourseItem['major_kh'] ?? ($activeCourse->major?->name_kh ?: 'បច្ចេកវិទ្យាព័ត៌មាន'),
            'lessons_total' => (int) ($activeCourseItem['lessons_total'] ?? 0),
            'lessons_done'  => (int) ($activeCourseItem['lessons_done'] ?? 0),
            'last_accessed' => $activeCourseItem['last_accessed'] ?? '២ ម៉ោងមុន',
            'teacher'       => $activeCourseItem['teacher'] ?? $progressService->cleanTeacherName($activeCourse->teacher, $activeCourse),
            'teacher_kh'    => $activeCourseItem['teacher_kh'] ?? $progressService->cleanTeacherNameKh($activeCourse->teacher, $activeCourse),
            'progress'      => (int) ($currentData['progress_percent'] ?? 0),
            'lastLesson'    => $currentLesson?->title ?? ($allCompleted ? 'All Lessons Completed' : 'Start Course'),
            'lastLesson_kh' => $currentLesson?->kh_title ?: ($currentLesson?->title ?? ($allCompleted ? 'បានបញ្ចប់មេរៀនទាំងអស់' : 'ចាប់ផ្តើមមេរៀន')),
            'timeLeft'      => $currentData['time_left'] ?? '00:00',
            'thumbnail'     => $activeCourseItem['thumbnail'] ?? $this->normalizeThumbnail($activeCourse->thumbnail),
            'href'          => $href,
            'overviewHref'  => '/student/courses/' . $activeCourse->id . '/overview',
        ];
    }

    protected function buildRecentActivities(User $user): array
    {
        $recentActivitiesList = [];

        try {
            $recentLessons = LessonProgress::where('user_id', $user->id)
                ->select(['id', 'user_id', 'lesson_id', 'percent', 'seconds_watched', 'completed_at', 'updated_at'])
                ->with(['lesson:id,course_id,title,kh_title', 'lesson.course:id,title,title_kh'])
                ->latest('updated_at')
                ->take(3)
                ->get();

            foreach ($recentLessons as $lp) {
                if ($lp->lesson && $lp->lesson->course) {
                    $recentActivitiesList[] = [
                        'id'          => 'lp-' . $lp->id,
                        'type'        => 'play',
                        'iconBg'      => 'bg-purple-600/20 text-purple-600 dark:text-purple-400 border border-purple-500/30',
                        'title'       => $lp->lesson->title,
                        'title_km'    => $lp->lesson->kh_title ?: $lp->lesson->title,
                        'course'      => $lp->lesson->course->title,
                        'course_km'   => $lp->lesson->course->title_kh ?: $lp->lesson->course->title,
                        'time'        => $lp->updated_at ? $lp->updated_at->diffForHumans() : 'Today',
                        'href'        => "/student/my-courses/{$lp->lesson->course_id}/lessons/{$lp->lesson_id}",
                        'timestamp'   => $lp->updated_at ? $lp->updated_at->timestamp : 0,
                        'progress'    => (int) ($lp->percent ?? 0),
                        'category'    => 'Lesson',
                        'category_km' => 'មេរៀន',
                    ];
                }
            }

            $recentQuizzes = QuizAttempt::where('user_id', $user->id)
                ->select(['id', 'user_id', 'quiz_id', 'score', 'passed', 'submitted_at'])
                ->with(['quiz:id,course_id,title', 'quiz.course:id,title,title_kh'])
                ->latest('submitted_at')
                ->take(2)
                ->get();

            foreach ($recentQuizzes as $qa) {
                if ($qa->quiz && $qa->quiz->course) {
                    $scorePct = round((float) ($qa->score ?? 0));
                    $recentActivitiesList[] = [
                        'id'          => 'qa-' . $qa->id,
                        'type'        => 'check',
                        'iconBg'      => 'bg-emerald-600/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30',
                        'title'       => $qa->quiz->title . ' (' . $scorePct . '%)',
                        'title_km'    => $qa->quiz->title . ' (ពិន្ទុ ' . $scorePct . '%)',
                        'course'      => $qa->quiz->course->title,
                        'course_km'   => $qa->quiz->course->title_kh ?: $qa->quiz->course->title,
                        'time'        => $qa->submitted_at ? Carbon::parse($qa->submitted_at)->diffForHumans() : 'Recently',
                        'href'        => '/student/quizzes/scores',
                        'timestamp'   => $qa->submitted_at ? Carbon::parse($qa->submitted_at)->timestamp : 0,
                        'progress'    => $scorePct,
                        'category'    => 'Quiz',
                        'category_km' => 'កម្រងសំណួរ',
                    ];
                }
            }

            usort($recentActivitiesList, fn ($a, $b) => $b['timestamp'] <=> $a['timestamp']);
            $recentActivitiesList = array_slice($recentActivitiesList, 0, 3);
        } catch (\Throwable $e) {
            $recentActivitiesList = [];
        }

        return $recentActivitiesList;
    }

    protected function buildUpcomingEvents(Collection $enrollments): array
    {
        $events = [];

        try {
            $activeCourseIds = $enrollments->whereIn('status', ['active', 'completed'])->pluck('course_id')->unique()->values();
            if ($activeCourseIds->isEmpty()) {
                return [];
            }

            if (Schema::hasTable('deadlines')) {
                $deadlines = Deadline::whereIn('course_id', $activeCourseIds)
                    ->where('due_at', '>=', now())
                    ->with('course:id,title,title_kh')
                    ->orderBy('due_at')
                    ->take(3)
                    ->get();

                foreach ($deadlines as $d) {
                    $dueAt = $d->due_at ? Carbon::parse($d->due_at)->timezone(DashboardAnalyticsService::TIMEZONE) : null;
                    $events[] = [
                        'id'        => 'dl-' . $d->id,
                        'type'      => $d->deadline_type === 'quiz' ? 'quiz' : 'assignment',
                        'title'     => $d->title,
                        'title_km'  => $d->title,
                        'course'    => $d->course?->title ?? 'Course',
                        'course_km' => $d->course?->title_kh ?: ($d->course?->title ?? 'វគ្គសិក្សា'),
                        'date'      => $dueAt ? $dueAt->format('M d, Y') : '',
                        'time'      => $dueAt ? $dueAt->format('h:i A') : '',
                        'href'      => '/student/assignments',
                    ];
                }
            }

            if (count($events) < 3 && Schema::hasTable('quizzes')) {
                $quizzes = Quiz::whereIn('course_id', $activeCourseIds)
                    ->select(['id', 'course_id', 'title', 'time_limit_minutes'])
                    ->with('course:id,title,title_kh')
                    ->latest('id')
                    ->take(3 - count($events))
                    ->get();

                foreach ($quizzes as $q) {
                    $refDate = $q->created_at
                        ? Carbon::parse($q->created_at)->timezone(DashboardAnalyticsService::TIMEZONE)
                        : now(DashboardAnalyticsService::TIMEZONE);
                    $durationMins = (int) ($q->time_limit_minutes ?: 15);
                    $events[] = [
                        'id'        => 'qz-' . $q->id,
                        'type'      => 'quiz',
                        'title'     => 'Quiz: ' . $q->title,
                        'title_km'  => 'កម្រងសំណួរ: ' . $q->title,
                        'course'    => $q->course?->title ?? 'Course',
                        'course_km' => $q->course?->title_kh ?: ($q->course?->title ?? 'វគ្គសិក្សា'),
                        'date'      => $refDate->format('M d, Y'),
                        'time'      => $durationMins . ' mins',
                        'href'      => '/student/quizzes/practice',
                    ];
                }
            }
        } catch (\Throwable $e) {
            return [];
        }

        return $events;
    }

    protected function buildTodayGoalsPayload(User $user): array
    {
        if (!Schema::hasTable('student_goals')) {
            return [
                'completedCount' => 0,
                'totalCount'     => 0,
                'percentage'     => 0,
                'items'          => [],
            ];
        }

        $rows = StudentGoal::where('user_id', $user->id)
            ->orderBy('id')
            ->get();

        $items = $rows->map(fn (StudentGoal $g) => $this->formatGoal($g))->values()->all();
        $completedCount = collect($items)->where('isCompleted', true)->count();
        $totalCount = count($items);

        return [
            'completedCount' => $completedCount,
            'totalCount'     => $totalCount,
            'percentage'     => $totalCount > 0 ? (int) round(($completedCount / $totalCount) * 100) : 0,
            'items'          => $items,
        ];
    }

    protected function formatGoal(StudentGoal $goal): array
    {
        return [
            'id'          => (string) $goal->id,
            'title'       => $goal->title,
            'titleKm'     => $goal->title_km ?: $goal->title,
            'titleEn'     => $goal->title,
            'isCompleted' => (bool) $goal->is_completed,
            'completed'   => (bool) $goal->is_completed,
            'targetRoute' => $goal->target_route ?: '/student/my-courses/enrolled',
            'href'        => $goal->target_route ?: '/student/my-courses/enrolled',
            'createdAt'   => $goal->created_at ? $goal->created_at->getTimestampMs() : now()->getTimestampMs(),
        ];
    }

    protected function buildAiRecommendations(User $user): array
    {
        try {
            if (!Schema::hasTable('ai_recommendations')) {
                return [];
            }

            $rows = AiRecommendation::where('user_id', $user->id)
                ->where('is_dismissed', false)
                ->with(['lesson:id,course_id,title,kh_title', 'lesson.course:id,title,title_kh'])
                ->latest('id')
                ->get();

            $seen = [];
            $items = [];
            foreach ($rows as $rec) {
                $dedupeKey = $rec->type . '|' . trim((string) $rec->reason);
                if (isset($seen[$dedupeKey])) {
                    continue;
                }
                $seen[$dedupeKey] = true;
                $items[] = $this->formatAiRecommendation($rec);
                if (count($items) >= 4) {
                    break;
                }
            }

            return $items;
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function formatAiRecommendation(AiRecommendation $rec): array
    {
        $type = strtolower(trim((string) $rec->type));
        $reason = trim((string) $rec->reason);
        $lesson = $rec->lesson;
        $course = $lesson?->course;

        $metaMap = [
            'weak_topic'     => [
                'title'          => $course ? "Review {$course->title}" : 'Focus on Weak Topics',
                'title_km'       => $course ? 'រំលឹកឡើងវិញ ' . ($course->title_kh ?: $course->title) : 'រំលឹកចំណុចដែលត្រូវពង្រឹង',
                'badge'          => 'Weak Topic',
                'badge_km'       => 'ចំណុចខ្សោយ',
                'actionLabel'    => 'Practice Drill',
                'actionLabel_km' => 'អនុវត្តលំហាត់',
                'href'           => '/student/quizzes/practice',
            ],
            'remedial'       => [
                'title'          => $course ? "Remedial Review: {$course->title}" : 'Remedial Review Recommended',
                'title_km'       => $course ? 'ពង្រឹងមូលដ្ឋាន៖ ' . ($course->title_kh ?: $course->title) : 'ការណែនាំរំលឹកមូលដ្ឋាន',
                'badge'          => 'Remedial',
                'badge_km'       => 'ពង្រឹងមូលដ្ឋាន',
                'actionLabel'    => 'Review Material',
                'actionLabel_km' => 'រំលឹកមេរៀន',
                'href'           => '/student/quizzes/practice',
            ],
            'review'         => [
                'title'          => $course ? "Reinforce {$course->title}" : 'Module Review Suggested',
                'title_km'       => $course ? 'ពង្រឹងបន្ថែម៖ ' . ($course->title_kh ?: $course->title) : 'រំលឹកមេរៀនបន្ថែម',
                'badge'          => 'Review',
                'badge_km'       => 'រំលឹកឡើងវិញ',
                'actionLabel'    => 'Review Quiz',
                'actionLabel_km' => 'អនុវត្ត Quiz',
                'href'           => '/student/quizzes/practice',
            ],
            'next_module'    => [
                'title'          => $course ? "Advance in {$course->title}" : 'Ready for Next Module',
                'title_km'       => $course ? 'ឈានទៅមេរៀនបន្ទាប់៖ ' . ($course->title_kh ?: $course->title) : 'ត្រៀមខ្លួនទៅជំពូកបន្ទាប់',
                'badge'          => 'Next Step',
                'badge_km'       => 'ជំហានបន្ទាប់',
                'actionLabel'    => 'Open Roadmap',
                'actionLabel_km' => 'មើលផែនទីសិក្សា',
                'href'           => '/student/ai-path/recommended',
            ],
            'resume'         => [
                'title'          => $lesson ? "Continue {$lesson->title}" : ($course ? "Continue {$course->title}" : 'Continue Active Lesson'),
                'title_km'       => $lesson ? 'បន្តមេរៀន៖ ' . ($lesson->kh_title ?: $lesson->title) : ($course ? 'បន្តវគ្គសិក្សា៖ ' . ($course->title_kh ?: $course->title) : 'បន្តការសិក្សា'),
                'badge'          => 'Active Study',
                'badge_km'       => 'កំពុងសិក្សា',
                'actionLabel'    => 'Resume Lesson',
                'actionLabel_km' => 'បន្តមេរៀន',
                'href'           => '/student/my-courses/enrolled',
            ],
            're_engage'      => [
                'title'          => 'Resume Your Study Streak',
                'title_km'       => 'ត្រឡប់មកបន្តការសិក្សារបស់អ្នក',
                'badge'          => 'Study Reminder',
                'badge_km'       => 'រំលឹកការសិក្សា',
                'actionLabel'    => 'Continue Study',
                'actionLabel_km' => 'បន្តការសិក្សា',
                'href'           => '/student/my-courses/enrolled',
            ],
        ];

        $meta = $metaMap[$type] ?? [
            'title'          => 'AI Study Recommendation',
            'title_km'       => 'ការណែនាំការសិក្សាពី AI',
            'badge'          => ucfirst(str_replace('_', ' ', $type ?: 'AI')),
            'badge_km'       => 'AI ណែនាំ',
            'actionLabel'    => 'View Details',
            'actionLabel_km' => 'មើលលម្អិត',
            'href'           => '/student/ai-path/recommended',
        ];

        $actionHref = ($lesson && $lesson->course_id)
            ? "/student/my-courses/{$lesson->course_id}/lessons/{$lesson->id}"
            : $meta['href'];

        $descKm = $this->translateRecommendationReasonToKhmer($reason);

        return [
            'id'             => $rec->id,
            'type'           => $type,
            'title'          => $meta['title'],
            'title_km'       => $meta['title_km'],
            'badge'          => $meta['badge'],
            'badge_km'       => $meta['badge_km'],
            'desc'           => $reason,
            'desc_km'        => $descKm,
            'actionLabel'    => $meta['actionLabel'],
            'actionLabel_km' => $meta['actionLabel_km'],
            'actionHref'     => $actionHref,
        ];
    }

    protected function translateRecommendationReasonToKhmer(string $reason): string
    {
        if (preg_match('/^Continue\s+(.+?):\s+Next recommended lesson is\s+(.+)\.$/i', $reason, $m)) {
            return "បន្តវគ្គ {$m[1]}៖ មេរៀនណែនាំបន្ទាប់គឺ {$m[2]}។";
        }
        if (preg_match('/Quiz score\s+(\d+%)\s+on\s+(.+?):\s+Ready to advance to Next Module/i', $reason, $m)) {
            return "ពិន្ទុ Quiz {$m[1]} ក្នុង {$m[2]}៖ អ្នករួចរាល់សម្រាប់ឈានទៅមេរៀនបន្ទាប់។";
        }
        if (preg_match('/Quiz score\s*>=?\s*(\d+%)\s+on\s+(.+?):\s+Ready to advance/i', $reason, $m)) {
            return "ពិន្ទុ Quiz >= {$m[1]} លើ {$m[2]}៖ ត្រៀមខ្លួនរួចរាល់សម្រាប់ជំពូកបន្ទាប់។";
        }
        if (preg_match('/Score\s+(\d+%)\s+in\s+(.+?):\s+Reinforcement drill recommended/i', $reason, $m)) {
            return "ពិន្ទុ {$m[1]} ក្នុង {$m[2]}៖ ណែនាំឱ្យអនុវត្តលំហាត់ពង្រឹងបន្ថែម។";
        }
        if (preg_match('/Score\s+(\d+%)\s+in\s+(.+?):\s+Remedial/i', $reason, $m)) {
            return "ពិន្ទុ {$m[1]} ក្នុង {$m[2]}៖ ណែនាំឱ្យរំលឹកមូលដ្ឋានឡើងវិញ។";
        }
        if (preg_match('/Quiz score\s+(\d+%)\s+in\s+(.+?):/i', $reason, $m)) {
            return "ពិន្ទុ Quiz {$m[1]} ក្នុង {$m[2]}៖ ណែនាំឱ្យរំលឹកវីដេអូ និងអនុវត្តលំហាត់ឡើងវិញ។";
        }
        if (preg_match('/Score below 50% in\s+(.+?):/i', $reason, $m)) {
            return "ពិន្ទុក្រោម ៥០% ក្នុង {$m[1]}៖ ណែនាំឱ្យមើលឯកសារជំនួយបន្ថែម។";
        }
        if (str_contains(strtolower($reason), 'idle for')) {
            return 'ខានចូលរៀនលើស ៣ ថ្ងៃ៖ សូមចូលបន្តការសិក្សាដើម្បីរក្សាល្បឿនសិក្សា។';
        }
        return $reason;
    }

    protected function buildAnnouncements(User $user): array
    {
        try {
            if (!Schema::hasTable('announcements')) {
                return [[], 0];
            }

            $rows = Announcement::whereIn('audience_type', ['everyone', 'students', 'All Students & Teachers', 'Active Students'])
                ->where(function ($q) {
                    $q->whereNull('status')->orWhereIn('status', ['sent', 'scheduled', 'published']);
                })
                ->where(function ($q) {
                    $q->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now());
                })
                ->orderByDesc('is_pinned')
                ->latest('id')
                ->get();

            $readIds = Cache::get("user_{$user->id}_read_notifications", []);
            $seen = [];
            $items = [];
            $unreadCount = 0;

            foreach ($rows as $a) {
                $dedupeKey = trim((string) ($a->title_en ?: $a->title_kh)) . '|' . trim((string) ($a->body_en ?: $a->body_kh));
                if (isset($seen[$dedupeKey])) {
                    continue;
                }
                $seen[$dedupeKey] = true;

                $isRead = in_array(1000 + (int) $a->id, is_array($readIds) ? $readIds : [], true);
                if (!$isRead) {
                    $unreadCount++;
                }

                $createdAt = $a->created_at ? Carbon::parse($a->created_at)->timezone(DashboardAnalyticsService::TIMEZONE) : now();
                $items[] = [
                    'id'         => $a->id,
                    'title_en'   => $a->title_en ?: ($a->title_kh ?: 'Announcement'),
                    'title_kh'   => $a->title_kh ?: ($a->title_en ?: 'សេចក្តីប្រកាស'),
                    'body_en'    => Str::limit(strip_tags((string) ($a->body_en ?: $a->body_kh)), 140),
                    'body_kh'    => Str::limit(strip_tags((string) ($a->body_kh ?: $a->body_en)), 140),
                    'priority'   => $a->priority ?: 'normal',
                    'is_pinned'  => (bool) $a->is_pinned,
                    'is_read'    => $isRead,
                    'created_at' => $createdAt->format('M d, Y'),
                    'href'       => '/student/notifications/announcements',
                ];
            }

            return [$items, $unreadCount];
        } catch (\Throwable $e) {
            return [[], 0];
        }
    }

    protected function buildAchievements(User $user, array $statsData, int $computedCompleted, int $completedLessonsCount): array
    {
        $totalSeconds = (int) ($statsData['totalSeconds'] ?? 0);
        $avgNum = (int) ($statsData['averageScoreNum'] ?? 0);

        $streakDays = 0;
        try {
            $streakDays = StudyDaily::where('user_id', $user->id)
                ->where('seconds', '>', 0)
                ->distinct('study_date')
                ->count('study_date');
        } catch (\Throwable $e) {
            $streakDays = 0;
        }
        if ($streakDays === 0 && $totalSeconds > 0) {
            $streakDays = 1;
        }
        $hasStreak = $streakDays >= 7 || ($streakDays >= 1 && $totalSeconds >= 1800);
        $hasQuick = $computedCompleted >= 1 || $completedLessonsCount >= 1;

        $bestQuizScore = 0;
        try {
            $bestQuizScore = (int) round(QuizAttempt::where('user_id', $user->id)->max('score') ?? 0);
        } catch (\Throwable $e) {
            $bestQuizScore = 0;
        }
        $hasQuizMaster = $bestQuizScore >= 75 || ($avgNum >= 75 && QuizAttempt::where('user_id', $user->id)->exists());
        $hasEarlyBird = $totalSeconds >= 1800;

        $certsCount = (int) ($statsData['certificatesCount'] ?? 0);
        $hasTopPerformer = $certsCount >= 1 || ($avgNum >= 75 && $computedCompleted >= 1);

        return [
            [
                'id'          => 'streak',
                'title'       => '7 Days Streak',
                'title_km'    => 'រៀន ៧ ថ្ងៃជាប់',
                'desc'        => 'Studied 7 days in a row without breaking streak',
                'desc_km'     => 'រៀន ៧ ថ្ងៃជាប់គ្នាឥតដាច់',
                'type'        => 'flame',
                'color'       => 'from-amber-500 via-orange-500 to-rose-500',
                'shadowColor' => 'shadow-orange-500/30',
                'progress'    => $streakDays > 0 ? "{$streakDays}/7 Days" : '0/7 Days',
                'progress_km' => $streakDays > 0 ? "{$streakDays}/៧ ថ្ងៃ" : '០/៧ ថ្ងៃ',
                'href'        => '/student/certificates/achievements',
                'unlocked'    => $hasStreak,
            ],
            [
                'id'          => 'quick',
                'title'       => 'Quick Learner',
                'title_km'    => 'រៀនរហ័ស',
                'desc'        => 'Completed courses and lessons at steady pace',
                'desc_km'     => 'បញ្ចប់វគ្គសិក្សានិងមេរៀនតាមល្បឿនថេរ',
                'type'        => 'book',
                'color'       => 'from-emerald-400 via-teal-500 to-cyan-600',
                'shadowColor' => 'shadow-emerald-500/30',
                'progress'    => $completedLessonsCount > 0 ? "{$completedLessonsCount} " . ($completedLessonsCount === 1 ? 'lesson' : 'lessons') : '0 lessons',
                'progress_km' => $completedLessonsCount > 0 ? "{$completedLessonsCount} មេរៀន" : '០ មេរៀន',
                'href'        => '/student/certificates/achievements',
                'unlocked'    => $hasQuick,
            ],
            [
                'id'          => 'quiz_master',
                'title'       => 'Quiz Master',
                'title_km'    => 'ជើងខ្លាំង Quiz',
                'desc'        => 'Scored 75%+ in academic course quizzes',
                'desc_km'     => 'ទទួលបានពិន្ទុ ៧៥%+ ក្នុង Quiz វគ្គសិក្សា',
                'type'        => 'star',
                'color'       => 'from-violet-600 via-purple-600 to-fuchsia-600',
                'shadowColor' => 'shadow-purple-600/35',
                'progress'    => $bestQuizScore > 0 ? "Score: {$bestQuizScore}%" : ($avgNum > 0 ? "Avg: {$avgNum}%" : '0 Quizzes'),
                'progress_km' => $bestQuizScore > 0 ? "ពិន្ទុ: {$bestQuizScore}%" : ($avgNum > 0 ? "មធ្យម: {$avgNum}%" : '០ Quiz'),
                'href'        => '/student/certificates/achievements',
                'unlocked'    => $hasQuizMaster,
            ],
            [
                'id'          => 'early_bird',
                'title'       => 'Early Bird',
                'title_km'    => 'រៀនព្រលឹម',
                'desc'        => 'Active morning study sessions completed',
                'desc_km'     => 'ចូលរួមការសិក្សាពេលព្រលឹមបានទៀងទាត់',
                'type'        => 'bolt',
                'color'       => 'from-amber-400 via-yellow-500 to-amber-600',
                'shadowColor' => 'shadow-amber-500/30',
                'progress'    => round($totalSeconds / 60) > 0 ? round($totalSeconds / 60) . ' mins' : '0 mins',
                'progress_km' => round($totalSeconds / 60) > 0 ? round($totalSeconds / 60) . ' នាទី' : '០ នាទី',
                'href'        => '/student/certificates/achievements',
                'unlocked'    => $hasEarlyBird,
            ],
            [
                'id'          => 'top_performer',
                'title'       => 'Top Performer',
                'title_km'    => 'លទ្ធផលឆ្នើម',
                'desc'        => 'Earned verified certificates and honor grades',
                'desc_km'     => 'ទទួលបានវិញ្ញាបនបត្រផ្លូវការ និងនិទ្ទេសល្អ',
                'type'        => 'trophy',
                'color'       => 'from-yellow-400 via-amber-500 to-orange-600',
                'shadowColor' => 'shadow-amber-600/30',
                'progress'    => $certsCount > 0 ? "{$certsCount} " . ($certsCount === 1 ? 'cert' : 'certs') : ($avgNum > 0 ? "Avg: {$avgNum}%" : '0 certs'),
                'progress_km' => $certsCount > 0 ? "{$certsCount} វិញ្ញាបនបត្រ" : ($avgNum > 0 ? "មធ្យម: {$avgNum}%" : '០ វិញ្ញាបនបត្រ'),
                'href'        => '/student/certificates/achievements',
                'unlocked'    => $hasTopPerformer,
            ],
        ];
    }
}
