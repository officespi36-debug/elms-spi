<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\QuizAttempt;
use App\Models\StudyDaily;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiContextService
{
    public function __construct(
        protected CourseProgressService $courseProgressService
    ) {}

    /**
     * Check whether the student has an active or completed enrollment in the given course.
     */
    public function isCourseEnrolled(User $user, int $courseId): bool
    {
        try {
            return Enrollment::where('student_id', $user->id)
                ->where('course_id', $courseId)
                ->whereIn('status', ['active', 'completed'])
                ->exists();
        } catch (\Throwable $e) {
            Log::warning('AI Tutor enrollment check warning: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Build non-PII learning context strictly from real database records (courses, modules, chapters, lessons).
     */
    public function getStudentContext(User $user, ?int $selectedCourseId = null, ?int $selectedLessonId = null): array
    {
        $recentActivity = $this->buildRecentActivity($user);

        $emptyFallback = [
            'has_course'       => false,
            'active_course'    => null,
            'enrolled_courses' => [],
            'recent_activity'  => $recentActivity,
        ];

        try {
            $enrollments = Enrollment::where('student_id', $user->id)
                ->whereIn('status', ['active', 'completed'])
                ->whereHas('course')
                ->with([
                    'course',
                    'course.modules',
                    'course.modules.chapters',
                    'course.modules.lessons',
                    'course.lessons',
                    'course.lessons.chapter',
                ])
                ->latest('updated_at')
                ->get();

            if ($enrollments->isEmpty()) {
                return $emptyFallback;
            }

            $progressItems = $this->courseProgressService->calculateForEnrollments($user, $enrollments);
            $courseModelsById = [];

            $enrolledCourses = $progressItems->map(function (array $item) use (&$courseModelsById) {
                /** @var Course $course */
                $course = $item['course'];
                $orderedLessons = $this->collectOrderedCourseLessons($course);
                $courseModelsById[(int) $course->id] = [
                    'course'  => $course,
                    'lessons' => $orderedLessons,
                ];

                $defaultLesson = $item['current_lesson'] ?? $orderedLessons->first();
                $lessonCtx = $this->extractLessonContext(
                    $course,
                    $defaultLesson,
                    $item['current_module_title'] ?? null
                );

                $rawDesc = strip_tags((string) ($course->description_kh ?: $course->description ?: ''));
                $courseDesc = Str::limit(trim(preg_replace('/\s+/u', ' ', $rawDesc) ?? ''), 600);

                $courseOutline = $orderedLessons
                    ->take(12)
                    ->map(fn (Lesson $l) => (string) ($l->kh_title ?: $l->title))
                    ->filter()
                    ->values()
                    ->all();

                $lessonOptions = $orderedLessons
                    ->map(function (Lesson $l) use ($course) {
                        $mod = $course->relationLoaded('modules')
                            ? $course->modules->firstWhere('id', $l->module_id)
                            : null;
                        $chap = $this->resolveChapterForLesson($l, $mod);

                        return [
                            'id'            => (int) $l->id,
                            'title'         => (string) $l->title,
                            'title_kh'      => (string) ($l->kh_title ?: $l->title),
                            'module_title'  => $mod ? ($mod->kh_title ?: $mod->title) : null,
                            'chapter_title' => $chap ? ($chap->kh_title ?: $chap->title) : null,
                        ];
                    })
                    ->values()
                    ->all();

                return array_merge([
                    'id'                 => (int) $course->id,
                    'code'               => $course->code ?: mb_strtoupper(mb_substr($course->title, 0, 2)),
                    'title'              => $course->title,
                    'title_kh'           => $course->title_kh ?: $course->title,
                    'course_description' => $courseDesc,
                    'course_outline'     => $courseOutline,
                    'lessons'            => $lessonOptions,
                    'progress_percent'   => (int) ($item['percent'] ?? 0),
                    'lessons_done'       => (int) ($item['lessons_done'] ?? 0),
                    'lessons_total'      => (int) ($item['lessons_total'] ?? 0),
                    'last_activity_ms'   => (int) ($item['last_activity_ms'] ?? 0),
                    'state'              => (string) ($item['state'] ?? 'not_started'),
                ], $lessonCtx);
            })->values();

            $activeCourse = null;
            if ($selectedCourseId !== null) {
                $activeCourse = $enrolledCourses->firstWhere('id', $selectedCourseId);
            }

            // Derive continue-learning active course directly from already computed $progressItems to avoid duplicate queries
            if (!$activeCourse) {
                $activeCourse = $enrolledCourses
                    ->filter(fn (array $c) => ($c['state'] ?? '') !== 'completed' && ($c['last_activity_ms'] ?? 0) > 0)
                    ->sortByDesc('last_activity_ms')
                    ->first()
                    ?? $enrolledCourses->first(fn (array $c) => ($c['state'] ?? '') !== 'completed')
                    ?? $enrolledCourses->first();
            }

            if ($activeCourse && $selectedLessonId !== null && isset($courseModelsById[$activeCourse['id']])) {
                /** @var Lesson|null $pickedLesson */
                $pickedLesson = $courseModelsById[$activeCourse['id']]['lessons']->firstWhere('id', $selectedLessonId);
                if ($pickedLesson) {
                    $overrideCtx = $this->extractLessonContext(
                        $courseModelsById[$activeCourse['id']]['course'],
                        $pickedLesson,
                        $activeCourse['module_title'] ?? null
                    );
                    $activeCourse = array_merge($activeCourse, $overrideCtx);
                }
            }

            $stripMeta = static function (array $course): array {
                unset($course['last_activity_ms'], $course['state']);
                return $course;
            };

            return [
                'has_course'       => $activeCourse !== null,
                'active_course'    => $activeCourse ? $stripMeta($activeCourse) : null,
                'enrolled_courses' => $enrolledCourses->map($stripMeta)->all(),
                'recent_activity'  => $recentActivity,
            ];
        } catch (\Throwable $e) {
            Log::warning('AI Tutor student context query warning: ' . $e->getMessage());
            return $emptyFallback;
        }
    }

    /**
     * Build sanitized course/chapter/lesson context payload safe for external AI provider calls (ZERO PII).
     */
    public function buildSafeAiCoursePayload(?array $activeCourse): ?array
    {
        if (!$activeCourse) {
            return null;
        }

        return [
            'course_title'       => (string) ($activeCourse['title_kh'] ?: $activeCourse['title'] ?? ''),
            'course_description' => (string) ($activeCourse['course_description'] ?? ''),
            'module_title'       => (string) ($activeCourse['module_title'] ?? ''),
            'chapter_title'      => (string) ($activeCourse['chapter_title'] ?? ''),
            'lesson_title'       => (string) ($activeCourse['lesson_title_kh'] ?: $activeCourse['lesson_title'] ?? ''),
            'lesson_objectives'  => (string) ($activeCourse['lesson_objectives'] ?? ''),
            'lesson_excerpt'     => (string) ($activeCourse['lesson_excerpt'] ?? ''),
            'course_outline'     => is_array($activeCourse['course_outline'] ?? null) ? $activeCourse['course_outline'] : [],
            'progress_percent'   => (int) ($activeCourse['progress_percent'] ?? 0),
        ];
    }

    /**
     * Collect ordered, deduplicated lessons across sorted course modules or direct course lessons.
     */
    protected function collectOrderedCourseLessons(Course $course): Collection
    {
        $lessons = collect();

        if ($course->relationLoaded('modules') && $course->modules->isNotEmpty()) {
            foreach ($course->modules->sortBy('order') as $mod) {
                if ($mod->relationLoaded('lessons') && $mod->lessons->isNotEmpty()) {
                    foreach ($mod->lessons->sortBy('order') as $l) {
                        $lessons->push($l);
                    }
                }
            }
        }

        if ($lessons->isEmpty() && $course->relationLoaded('lessons')) {
            $lessons = $course->lessons->sortBy('order')->values();
        }

        return $lessons->unique('id')->values();
    }

    protected function resolveChapterForLesson(Lesson $lesson, $module)
    {
        if ($lesson->relationLoaded('chapter') && $lesson->chapter) {
            return $lesson->chapter;
        }

        if ($lesson->chapter_id && $module && $module->relationLoaded('chapters')) {
            return $module->chapters->firstWhere('id', $lesson->chapter_id);
        }

        return null;
    }

    protected function extractLessonContext(Course $course, ?Lesson $lesson, ?string $fallbackModuleTitle): array
    {
        $mod = null;
        $moduleTitle = $fallbackModuleTitle;
        if ($lesson && $course->relationLoaded('modules')) {
            $mod = $course->modules->firstWhere('id', $lesson->module_id);
            if ($mod) {
                $moduleTitle = $mod->kh_title ?: $mod->title;
            }
        }

        $chap = $lesson ? $this->resolveChapterForLesson($lesson, $mod) : null;
        $chapterTitle = $chap ? ($chap->kh_title ?: $chap->title) : null;

        $objectivesText = '';
        if ($lesson && !empty($lesson->objectives) && is_array($lesson->objectives)) {
            $objectivesText = implode('; ', array_filter(array_map('strval', $lesson->objectives)));
        } elseif ($mod && !empty($mod->learning_objectives)) {
            $objectivesText = strip_tags((string) $mod->learning_objectives);
        }
        $objectivesText = Str::limit(trim(preg_replace('/\s+/u', ' ', $objectivesText) ?? ''), 400);

        $excerptParts = [];
        foreach ([$lesson?->summary, $lesson?->ai_summary, $lesson?->content] as $part) {
            $cleanPart = trim(preg_replace('/\s+/u', ' ', strip_tags((string) ($part ?? ''))) ?? '');
            if ($cleanPart !== '' && !in_array($cleanPart, $excerptParts, true)) {
                $excerptParts[] = $cleanPart;
            }
        }
        $lessonExcerpt = Str::limit(implode("\n", $excerptParts), 1800);

        return [
            'module_title'      => $moduleTitle,
            'chapter_title'     => $chapterTitle,
            'lesson_id'         => $lesson?->id ? (int) $lesson->id : null,
            'lesson_title'      => $lesson?->title,
            'lesson_title_kh'   => $lesson?->kh_title ?: $lesson?->title,
            'lesson_objectives' => $objectivesText,
            'lesson_excerpt'    => $lessonExcerpt,
        ];
    }

    /**
     * Fetch genuine recent learning activities and current-week study duration (Xh Ym in Asia/Phnom_Penh).
     * Returns empty array when student has no activity yet.
     */
    protected function buildRecentActivity(User $user): array
    {
        $items = [];

        try {
            $recentLessons = LessonProgress::with('lesson')
                ->where('user_id', $user->id)
                ->where(function ($q) {
                    $q->where('percent', '>', 0)->orWhereNotNull('completed_at');
                })
                ->latest('updated_at')
                ->limit(2)
                ->get();

            foreach ($recentLessons as $lp) {
                $lessonTitle = $lp->lesson?->kh_title ?: ($lp->lesson?->title ?? 'Lesson');
                $isDone = $lp->completed_at !== null || (int) $lp->percent >= 90;
                $items[] = [
                    'type'     => 'lesson',
                    'title_km' => ($isDone ? 'បានបញ្ចប់មេរៀន៖ ' : 'បានមើលមេរៀន៖ ') . $lessonTitle,
                    'title_en' => ($isDone ? 'Completed: ' : 'Viewed: ') . ($lp->lesson?->title ?: $lessonTitle),
                    'time'     => $lp->updated_at ? Carbon::parse($lp->updated_at)->diffForHumans() : '',
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('AI Tutor recent lessons query warning: ' . $e->getMessage());
        }

        try {
            $recentQuiz = QuizAttempt::with('quiz')
                ->where('user_id', $user->id)
                ->whereNotNull('score')
                ->latest('submitted_at')
                ->first();

            if ($recentQuiz && $recentQuiz->quiz) {
                $items[] = [
                    'type'     => 'quiz',
                    'title_km' => 'បានធ្វើកម្រងសំណួរ៖ ' . $recentQuiz->quiz->title,
                    'title_en' => 'Completed Quiz: ' . $recentQuiz->quiz->title,
                    'time'     => $recentQuiz->submitted_at ? Carbon::parse($recentQuiz->submitted_at)->diffForHumans() : '',
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('AI Tutor recent quiz query warning: ' . $e->getMessage());
        }

        $tz = DashboardAnalyticsService::TIMEZONE;
        $weekStartLocal = Carbon::now($tz)->startOfWeek();
        $weekStartDateStr = $weekStartLocal->toDateString();
        $weekStartAppTz = $weekStartLocal->copy()->setTimezone(config('app.timezone', 'UTC'));

        $sdByDate = [];
        $progressByDate = [];
        $hasRealProgressThisWeek = false;

        try {
            $sdRows = StudyDaily::where('user_id', $user->id)
                ->where('study_date', '>=', $weekStartDateStr)
                ->get(['study_date', 'seconds']);

            foreach ($sdRows as $row) {
                $dateKey = $row->study_date instanceof Carbon
                    ? $row->study_date->format('Y-m-d')
                    : Carbon::parse($row->study_date)->format('Y-m-d');
                $sdByDate[$dateKey] = ($sdByDate[$dateKey] ?? 0) + min(DashboardAnalyticsService::MAX_DAILY_SECONDS, max(0, (int) $row->seconds));
            }
        } catch (\Throwable $e) {
            // StudyDaily unavailable or empty
        }

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
                }
            }
        } catch (\Throwable $e) {
            // Ignore secondary fallback failure
        }

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
                    $sec = max(180, min(3600, ((int) ($qa->quiz?->time_limit_minutes ?? $qa->quiz?->duration_minutes ?? 10)) * 60));
                }

                $hasRealProgressThisWeek = true;
                $dateKey = Carbon::parse($qa->submitted_at ?? $qa->updated_at ?? $qa->created_at)
                    ->timezone($tz)
                    ->format('Y-m-d');
                $progressByDate[$dateKey] = ($progressByDate[$dateKey] ?? 0) + $sec;
            }
        } catch (\Throwable $e) {
            // Ignore secondary fallback failure
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
            $formatted = "{$hours}h {$mins}m";
            $items[] = [
                'type'     => 'study',
                'title_km' => "បានសិក្សាសប្តាហ៍នេះ៖ {$formatted}",
                'title_en' => "Studied this week: {$formatted}",
                'time'     => '',
            ];
        }

        return $items;
    }
}
