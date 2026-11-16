<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\QuizAttempt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class DashboardMetricsService
{
    public function __construct(
        protected CourseProgressService $courseProgressService
    ) {}

    /**
     * Valid timeframe ranges mapped to number of days.
     */
    private const RANGE_DAYS = [
        '7d'  => 7,
        '30d' => 30,
        '90d' => 90,
    ];

    public static function normalizeRange(?string $range): string
    {
        $r = strtolower(trim((string) $range));
        return array_key_exists($r, self::RANGE_DAYS) ? $r : '7d';
    }

    /**
     * Invalidate all cached dashboard metrics for a student.
     */
    public static function forgetForUser(int $userId): void
    {
        foreach (array_keys(self::RANGE_DAYS) as $r) {
            Cache::forget("student_dashboard_metrics_{$userId}_{$r}");
        }
        Cache::forget("student_dashboard_payload_{$userId}");
        Cache::forget("student_stats_{$userId}");
    }

    /**
     * Single-source-of-truth metrics for KPI strip, My Courses parity, and 3-column Analytics.
     */
    public function getMetrics(User $user, string $range = '7d', bool $forceRefresh = false): array
    {
        $normalizedRange = self::normalizeRange($range);
        $cacheKey = "student_dashboard_metrics_{$user->id}_{$normalizedRange}";

        if ($forceRefresh) {
            self::forgetForUser($user->id);
        }

        return Cache::remember($cacheKey, 120, function () use ($user, $normalizedRange) {
            return $this->computeMetrics($user, $normalizedRange);
        });
    }

    protected function computeMetrics(User $user, string $range): array
    {
        $days = self::RANGE_DAYS[$range];

        // 1. Enrollment Parity via CourseProgressService
        try {
            $enrollments = Enrollment::where('student_id', $user->id)
                ->whereIn('status', ['active', 'completed'])
                ->select(['id', 'student_id', 'course_id', 'status', 'enrolled_at', 'updated_at'])
                ->with([
                    'course:id,title,code,teacher_id,major_id,thumbnail',
                    'course.lessons:id,course_id,title,order,duration_seconds',
                    'course.modules:id,course_id,title,order',
                    'course.modules.lessons:id,module_id,course_id,title,order,duration_seconds',
                ])
                ->get();
        } catch (\Throwable $e) {
            $enrollments = collect();
        }

        $computed = $this->courseProgressService->calculateForEnrollments($user, $enrollments);
        $totalEnrolled = $computed->count();
        $inProgressCount = $computed->where('state', 'in_progress')->count();
        $completedCount = $computed->where('state', 'completed')->count();
        $notStartedCount = $computed->whereIn('state', ['not_started', 'no_content'])->count();

        // 2. Certificates Count from DB
        try {
            $certificatesCount = Certificate::where('student_id', $user->id)
                ->whereIn('status', ['valid', 'issued'])
                ->count();
        } catch (\Throwable $e) {
            $certificatesCount = 0;
        }

        // 3. Study Time Parity (All-Time + Selected Range 7d|30d|90d)
        $now = Carbon::now();
        $rangeStart = $now->copy()->subDays($days - 1)->startOfDay();
        $prevRangeStart = $rangeStart->copy()->subDays($days);

        $allTimeLessonSeconds = 0;
        $rangeLessonSeconds = 0;
        $prevRangeLessonSeconds = 0;

        $allTimeQuizSeconds = 0;
        $rangeQuizSeconds = 0;
        $prevRangeQuizSeconds = 0;

        // Practice seconds are tracked if present; otherwise 0 (never fabricated)
        $allTimePracticeSeconds = 0;
        $rangePracticeSeconds = 0;

        $dailyMap = [];
        if ($days === 7) {
            for ($i = 6; $i >= 0; $i--) {
                $d = $now->copy()->subDays($i);
                $dailyMap[$d->format('Y-m-d')] = [
                    'label'   => $d->format('D'),
                    'seconds' => 0,
                ];
            }
        } else {
            // Group 30d/90d into 6 or 7 even buckets for clean area chart labels
            $bucketCount = $days === 30 ? 6 : 6;
            $step = (int) ceil($days / $bucketCount);
            for ($b = 0; $b < $bucketCount; $b++) {
                $bStart = $rangeStart->copy()->addDays($b * $step);
                $dailyMap[$bStart->format('Y-m-d')] = [
                    'label'   => $bStart->format('M j'),
                    'seconds' => 0,
                ];
            }
        }

        $activeDatesInRange = [];

        try {
            $progressRows = LessonProgress::where('user_id', $user->id)
                ->select(['seconds_watched', 'updated_at', 'created_at'])
                ->get();

            foreach ($progressRows as $row) {
                $secs = max(0, (int) $row->seconds_watched);
                $allTimeLessonSeconds += $secs;

                $ts = $row->updated_at ?? $row->created_at;
                if (!$ts) {
                    continue;
                }
                $dt = Carbon::parse($ts);
                if ($dt->gte($rangeStart)) {
                    $rangeLessonSeconds += $secs;
                    if ($secs > 0) {
                        $activeDatesInRange[$dt->format('Y-m-d')] = true;
                    }
                    $this->addSecondsToBucket($dailyMap, $dt, $secs, $days, $rangeStart);
                } elseif ($dt->gte($prevRangeStart)) {
                    $prevRangeLessonSeconds += $secs;
                }
            }
        } catch (\Throwable $e) {
            // Keep safe 0 defaults
        }

        // 4. Quiz Attempts & Quiz Duration Parity
        $quizAveragePct = null;
        $highestScorePct = null;
        $quizAttemptsCount = 0;
        $quizTrendLabel = null;

        try {
            $quizRows = QuizAttempt::where('user_id', $user->id)
                ->select(['score', 'started_at', 'submitted_at', 'created_at'])
                ->latest('submitted_at')
                ->get();

            $scored = $quizRows->whereNotNull('score')->values();
            $quizAttemptsCount = $scored->count();

            if ($quizAttemptsCount > 0) {
                $quizAveragePct = (int) round((float) $scored->avg('score'));
                $highestScorePct = (int) round((float) $scored->max('score'));
                if ($quizAttemptsCount >= 2) {
                    $diff = (int) round((float) $scored[0]->score - (float) $scored[1]->score);
                    $quizTrendLabel = ($diff >= 0 ? "+{$diff}%" : "{$diff}%") . ' vs last test';
                } else {
                    $quizTrendLabel = '1 attempt';
                }
            }

            foreach ($quizRows as $qa) {
                if ($qa->started_at && $qa->submitted_at) {
                    $dur = max(0, min(7200, Carbon::parse($qa->submitted_at)->diffInSeconds(Carbon::parse($qa->started_at))));
                    $allTimeQuizSeconds += $dur;
                    $subDt = Carbon::parse($qa->submitted_at);
                    if ($subDt->gte($rangeStart)) {
                        $rangeQuizSeconds += $dur;
                        if ($dur > 0) {
                            $activeDatesInRange[$subDt->format('Y-m-d')] = true;
                        }
                        $this->addSecondsToBucket($dailyMap, $subDt, $dur, $days, $rangeStart);
                    } elseif ($subDt->gte($prevRangeStart)) {
                        $prevRangeQuizSeconds += $dur;
                    }
                }
            }
        } catch (\Throwable $e) {
            $quizAveragePct = null;
        }

        // Exact minute-consistent rounding so Lessons + Practice + Quiz === Total with zero drift
        $formatMins = static function (int $mins): string {
            $h = intdiv($mins, 60);
            $m = $mins % 60;
            return "{$h}h {$m}m";
        };

        $allLessonsMin = (int) round($allTimeLessonSeconds / 60);
        $allPracticeMin = (int) round($allTimePracticeSeconds / 60);
        $allQuizMin = (int) round($allTimeQuizSeconds / 60);
        $allTotalMin = $allLessonsMin + $allPracticeMin + $allQuizMin;
        $allTotalSeconds = $allTimeLessonSeconds + $allTimePracticeSeconds + $allTimeQuizSeconds;

        $rangeLessonsMin = (int) round($rangeLessonSeconds / 60);
        $rangePracticeMin = (int) round($rangePracticeSeconds / 60);
        $rangeQuizMin = (int) round($rangeQuizSeconds / 60);
        $rangeTotalMin = $rangeLessonsMin + $rangePracticeMin + $rangeQuizMin;
        $rangeTotalSeconds = $rangeLessonSeconds + $rangePracticeSeconds + $rangeQuizSeconds;
        $prevRangeTotalSeconds = $prevRangeLessonSeconds + $prevRangeQuizSeconds;

        $activeDaysCount = count($activeDatesInRange);
        $dailyAvgSeconds = $activeDaysCount > 0 ? (int) round($rangeTotalSeconds / $activeDaysCount) : 0;
        $dailyAvgHours = round($dailyAvgSeconds / 3600, 1);

        $trendPct = $prevRangeTotalSeconds > 0
            ? (int) round((($rangeTotalSeconds - $prevRangeTotalSeconds) / $prevRangeTotalSeconds) * 100)
            : null;

        $lessonsPct = $rangeTotalMin > 0 ? round(($rangeLessonsMin / $rangeTotalMin) * 100, 1) : 0;
        $practicePct = $rangeTotalMin > 0 ? round(($rangePracticeMin / $rangeTotalMin) * 100, 1) : 0;
        $quizPct = $rangeTotalMin > 0 ? max(0, round(100 - $lessonsPct - $practicePct, 1)) : 0;

        $series = array_map(function ($b) {
            return [
                'label'   => $b['label'],
                'seconds' => $b['seconds'],
                'minutes' => (int) round($b['seconds'] / 60),
            ];
        }, array_values($dailyMap));

        return [
            'range' => $range,
            'kpi'   => [
                'total_enrolled'        => $totalEnrolled,
                'in_progress_count'     => $inProgressCount,
                'completed_count'       => $completedCount,
                'not_started_count'     => $notStartedCount,
                'certificates_count'    => $certificatesCount,
                'study_time_seconds'    => $allTotalSeconds,
                'study_time_formatted'  => $formatMins($allTotalMin),
                'quiz_average_pct'      => $quizAveragePct,
                'quiz_attempts_count'   => $quizAttemptsCount,
            ],
            'analytics' => [
                'learning_activity' => [
                    'range'                => $range,
                    'total_seconds'        => $rangeTotalSeconds,
                    'total_formatted'      => $formatMins($rangeTotalMin),
                    'prev_total_seconds'   => $prevRangeTotalSeconds,
                    'trend_pct'            => $trendPct,
                    'series'               => $series,
                ],
                'study_time' => [
                    'total_seconds'         => $rangeTotalSeconds,
                    'total_formatted'       => $formatMins($rangeTotalMin),
                    'total_minutes'         => $rangeTotalMin,
                    'lessons_formatted'     => $formatMins($rangeLessonsMin),
                    'lessons_minutes'       => $rangeLessonsMin,
                    'lessons_pct'           => $lessonsPct,
                    'practice_formatted'    => $formatMins($rangePracticeMin),
                    'practice_minutes'      => $rangePracticeMin,
                    'practice_pct'          => $practicePct,
                    'quiz_formatted'        => $formatMins($rangeQuizMin),
                    'quiz_minutes'          => $rangeQuizMin,
                    'quiz_pct'              => $quizPct,
                    'active_days_in_range'  => $activeDaysCount,
                    'daily_average_seconds' => $dailyAvgSeconds,
                    'daily_average_label'   => $activeDaysCount > 0 ? "{$dailyAvgHours}h / day" : '0h / day',
                ],
                'quiz_performance' => [
                    'attempts_count'    => $quizAttemptsCount,
                    'average_pct'       => $quizAveragePct,
                    'highest_pct'       => $highestScorePct,
                    'correct_pct'       => $quizAveragePct,
                    'incorrect_pct'     => $quizAveragePct !== null ? max(0, 100 - $quizAveragePct) : null,
                    'trend_label'       => $quizTrendLabel,
                ],
            ],
        ];
    }

    private function addSecondsToBucket(array &$buckets, Carbon $dt, int $secs, int $days, Carbon $rangeStart): void
    {
        if ($secs <= 0 || empty($buckets)) {
            return;
        }
        if ($days === 7) {
            $key = $dt->format('Y-m-d');
            if (isset($buckets[$key])) {
                $buckets[$key]['seconds'] += $secs;
            }
            return;
        }

        $keys = array_keys($buckets);
        $step = (int) ceil($days / count($keys));
        $diffDays = max(0, $rangeStart->diffInDays($dt->copy()->startOfDay()));
        $idx = min(count($keys) - 1, intdiv($diffDays, max(1, $step)));
        $buckets[$keys[$idx]]['seconds'] += $secs;
    }
}
