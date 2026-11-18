<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\QuizAttempt;
use App\Models\StudyDaily;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AiContextService
{
    public function __construct(
        protected ContinueLearningService $continueLearningService,
        protected CourseProgressService $courseProgressService
    ) {}

    /**
     * Check whether the student has an active or completed enrollment in the given course.
     */
    public function isCourseEnrolled(User $user, int $courseId): bool
    {
        return Enrollment::where('student_id', $user->id)
            ->where('course_id', $courseId)
            ->whereIn('status', ['active', 'completed'])
            ->exists();
    }

    /**
     * Build non-PII learning context strictly from real database records.
     */
    public function getStudentContext(User $user, ?int $selectedCourseId = null): array
    {
        $enrollments = Enrollment::where('student_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->whereHas('course')
            ->with([
                'course:id,code,title,title_kh,level,major_id',
                'course.modules:id,course_id,title,kh_title,order',
                'course.modules.lessons:id,module_id,course_id,title,kh_title,order,duration_seconds,summary,content',
                'course.lessons:id,module_id,course_id,title,kh_title,order,duration_seconds,summary,content',
            ])
            ->latest('updated_at')
            ->get();

        $recentActivity = $this->buildRecentActivity($user);

        if ($enrollments->isEmpty()) {
            return [
                'has_course'       => false,
                'active_course'    => null,
                'enrolled_courses' => [],
                'recent_activity'  => $recentActivity,
            ];
        }

        $progressItems = $this->courseProgressService->calculateForEnrollments($user, $enrollments);

        $enrolledCourses = $progressItems->map(function (array $item) {
            /** @var Course $course */
            $course = $item['course'];
            $lesson = $item['current_lesson'] ?? null;

            $moduleTitle = $item['current_module_title'] ?? null;
            if ($lesson && $course->relationLoaded('modules')) {
                $mod = $course->modules->firstWhere('id', $lesson->module_id);
                if ($mod) {
                    $moduleTitle = $mod->kh_title ?: $mod->title;
                }
            }

            $rawLessonBody = strip_tags((string) ($lesson?->summary ?: $lesson?->content ?: ''));
            $lessonExcerpt = Str::limit(trim(preg_replace('/\s+/u', ' ', $rawLessonBody) ?? ''), 500);

            return [
                'id'               => (int) $course->id,
                'code'             => $course->code ?: mb_strtoupper(mb_substr($course->title, 0, 2)),
                'title'            => $course->title,
                'title_kh'         => $course->title_kh ?: $course->title,
                'module_title'     => $moduleTitle,
                'lesson_id'        => $lesson?->id ? (int) $lesson->id : null,
                'lesson_title'     => $lesson?->title,
                'lesson_title_kh'  => $lesson?->kh_title ?: $lesson?->title,
                'lesson_excerpt'   => $lessonExcerpt,
                'progress_percent' => (int) ($item['percent'] ?? 0),
                'lessons_done'     => (int) ($item['lessons_done'] ?? 0),
                'lessons_total'    => (int) ($item['lessons_total'] ?? 0),
                'last_activity_ms' => (int) ($item['last_activity_ms'] ?? 0),
                'state'            => (string) ($item['state'] ?? 'not_started'),
            ];
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
    }

    /**
     * Build sanitized course context payload safe for external AI provider calls (ZERO PII).
     */
    public function buildSafeAiCoursePayload(?array $activeCourse): ?array
    {
        if (!$activeCourse) {
            return null;
        }

        return [
            'course_title'     => (string) ($activeCourse['title_kh'] ?: $activeCourse['title'] ?? ''),
            'module_title'     => (string) ($activeCourse['module_title'] ?? ''),
            'lesson_title'     => (string) ($activeCourse['lesson_title_kh'] ?: $activeCourse['lesson_title'] ?? ''),
            'lesson_excerpt'   => (string) ($activeCourse['lesson_excerpt'] ?? ''),
            'progress_percent' => (int) ($activeCourse['progress_percent'] ?? 0),
        ];
    }

    /**
     * Fetch genuine recent learning activities from lesson_progress, quiz_attempts, and study_daily.
     * Returns empty array when student has no activity yet.
     */
    protected function buildRecentActivity(User $user): array
    {
        $items = [];

        $recentLessons = LessonProgress::with('lesson:id,title,kh_title')
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

        $recentQuiz = QuizAttempt::with('quiz:id,title')
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

        $weekSeconds = 0;
        try {
            $weekSeconds = (int) StudyDaily::where('user_id', $user->id)
                ->where('study_date', '>=', now()->startOfWeek()->toDateString())
                ->sum('seconds');
        } catch (\Throwable $e) {
            $weekSeconds = 0;
        }

        if ($weekSeconds === 0) {
            $weekSeconds = (int) LessonProgress::where('user_id', $user->id)
                ->where('updated_at', '>=', now()->startOfWeek())
                ->sum('seconds_watched');
        }

        if ($weekSeconds > 0) {
            $hours = intdiv($weekSeconds, 3600);
            $mins = intdiv($weekSeconds % 3600, 60);
            $formatted = $hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m";
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
