<?php

namespace App\Services;

use App\Models\LessonProgress;
use App\Models\QuizAttempt;
use App\Models\StudyDaily;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardAnalyticsService
{
    public const TIMEZONE = 'Asia/Phnom_Penh';
    public const MAX_DAILY_SECONDS = 86400;
    public const MAX_DELTA_SECONDS = 40;
    public const MIN_PREV_TREND_SECONDS = 1800;

    /**
     * Record a clamped study time delta into study_daily for the user's current date in Asia/Phnom_Penh.
     */
    public function recordDailyDelta(int $userId, string $kind, int $deltaSeconds): void
    {
        if ($deltaSeconds <= 0 || !in_array($kind, ['lesson', 'practice', 'quiz'], true)) {
            return;
        }

        $clampedDelta = min(self::MAX_DELTA_SECONDS, max(0, $deltaSeconds));
        if ($clampedDelta <= 0) {
            return;
        }

        try {
            if (!Schema::hasTable('study_daily')) {
                return;
            }

            $today = Carbon::now(self::TIMEZONE)->toDateString();

            DB::transaction(function () use ($userId, $today, $kind, $clampedDelta) {
                $existingTotalForDay = (int) StudyDaily::where('user_id', $userId)
                    ->whereDate('study_date', $today)
                    ->sum('seconds');

                $remainingDailyCap = max(0, self::MAX_DAILY_SECONDS - $existingTotalForDay);
                $allowedDelta = min($clampedDelta, $remainingDailyCap);
                if ($allowedDelta <= 0) {
                    return;
                }

                $row = StudyDaily::firstOrNew([
                    'user_id' => $userId,
                    'study_date' => $today,
                    'kind' => $kind,
                ]);
                $row->seconds = min(self::MAX_DAILY_SECONDS, (int) ($row->seconds ?? 0) + $allowedDelta);
                $row->save();
            });
        } catch (\Throwable $e) {
            // Non-blocking: never break progress save if study_daily is unavailable
        }
    }

    /**
     * Invalidate cached dashboard/analytics/enrolled payloads for a user.
     */
    public function invalidateUserCache(int $userId): void
    {
        foreach ([7, 30, 90] as $days) {
            Cache::forget("student_analytics_{$userId}_{$days}");
            Cache::forget("student_analytics_{$userId}_{$days}_v2");
        }
        Cache::forget("student_dashboard_payload_{$userId}");
        Cache::forget("student_dashboard_payload_{$userId}_v3");
        Cache::forget("student_dashboard_payload_{$userId}_v4");
        Cache::forget("student_dashboard_payload_{$userId}_v5");
        Cache::forget("student_dashboard_payload_{$userId}_v6");
        Cache::forget("student_stats_{$userId}");
        Cache::forget("student_enrolled_{$userId}");
        Cache::forget("student_enrolled_{$userId}_v2");
        Cache::forget("student_enrolled_{$userId}_v3");
        Cache::forget("student_enrolled_{$userId}_v4");
        Cache::forget("student_enrolled_{$userId}_v5");
        PathProgressService::invalidateForUser($userId);
    }

    /**
     * Get daily series, breakdown, daily average, and trend for 7, 30, or 90 days from study_daily,
     * lesson_progress, and quiz_attempts.
     */
    public function getWindowAnalytics(User $user, int $days = 7): array
    {
        if (!in_array($days, [7, 30, 90], true)) {
            $days = 7;
        }

        return Cache::remember("student_analytics_{$user->id}_{$days}_v2", 300, function () use ($user, $days) {
            $today = Carbon::now(self::TIMEZONE)->startOfDay();
            $start = $today->copy()->subDays($days - 1);
            $prevStart = $start->copy()->subDays($days);
            $prevEnd = $start->copy()->subDay();
            $startDateStr = $start->toDateString();
            $todayDateStr = $today->toDateString();

            $rows = collect();
            $prevTotalSeconds = 0;

            try {
                if (Schema::hasTable('study_daily')) {
                    $rows = StudyDaily::where('user_id', $user->id)
                        ->whereBetween('study_date', [$startDateStr, $todayDateStr])
                        ->get(['study_date', 'kind', 'seconds']);

                    $prevTotalSeconds = (int) StudyDaily::where('user_id', $user->id)
                        ->whereBetween('study_date', [$prevStart->toDateString(), $prevEnd->toDateString()])
                        ->sum('seconds');
                }
            } catch (\Throwable $e) {
                $rows = collect();
                $prevTotalSeconds = 0;
            }

            $sdByDateAndKind = [];
            foreach ($rows as $row) {
                $dateKey = $row->study_date instanceof Carbon
                    ? $row->study_date->format('Y-m-d')
                    : Carbon::parse($row->study_date)->format('Y-m-d');
                $secs = min(self::MAX_DAILY_SECONDS, max(0, (int) $row->seconds));
                $kind = in_array($row->kind, ['lesson', 'practice', 'quiz'], true) ? $row->kind : 'lesson';
                $sdByDateAndKind[$dateKey][$kind] = ($sdByDateAndKind[$dateKey][$kind] ?? 0) + $secs;
            }

            $lpByDate = [];
            $allTimeLessonSec = 0;
            try {
                $lessonProgresses = LessonProgress::where('user_id', $user->id)
                    ->with('lesson:id,duration_seconds')
                    ->get();

                foreach ($lessonProgresses as $lp) {
                    $lpSec = (int) ($lp->seconds_watched ?? 0);
                    if ($lpSec <= 0) {
                        $dur = max(600, (int) ($lp->lesson?->duration_seconds ?? 600));
                        if (!empty($lp->completed_at) || ($lp->percent ?? 0) >= 90) {
                            $lpSec = $dur;
                        } elseif (($lp->percent ?? 0) > 0) {
                            $lpSec = (int) round((($lp->percent ?? 0) / 100) * $dur);
                        }
                    }

                    $refDate = $lp->updated_at ?? $lp->completed_at ?? $lp->created_at ?? $today;
                    $dateKey = Carbon::parse($refDate)->timezone(self::TIMEZONE)->format('Y-m-d');

                    $allTimeLessonSec += $lpSec;
                    if ($dateKey >= $startDateStr && $dateKey <= $todayDateStr) {
                        $lpByDate[$dateKey] = ($lpByDate[$dateKey] ?? 0) + $lpSec;
                    }
                }
            } catch (\Throwable $e) {
                $lpByDate = [];
                $allTimeLessonSec = 0;
            }

            $qaByDateAndKind = [];
            $allTimeQuizByKind = ['practice' => 0, 'quiz' => 0];
            try {
                $quizAttempts = QuizAttempt::where('user_id', $user->id)
                    ->with('quiz:id,duration_minutes,type')
                    ->get();

                foreach ($quizAttempts as $qa) {
                    $qaSec = 0;
                    if ($qa->started_at && $qa->submitted_at) {
                        $qaSec = max(60, min(7200, (int) abs(Carbon::parse($qa->submitted_at)->diffInSeconds(Carbon::parse($qa->started_at)))));
                    }
                    if ($qaSec <= 0) {
                        $qaSec = max(180, min(3600, ((int) ($qa->quiz?->duration_minutes ?? 10)) * 60));
                    }

                    $kind = str_contains(strtolower((string) ($qa->quiz?->type ?? '')), 'practice') ? 'practice' : 'quiz';
                    $refDate = $qa->submitted_at ?? $qa->updated_at ?? $qa->created_at ?? $today;
                    $dateKey = Carbon::parse($refDate)->timezone(self::TIMEZONE)->format('Y-m-d');

                    $allTimeQuizByKind[$kind] += $qaSec;
                    if ($dateKey >= $startDateStr && $dateKey <= $todayDateStr) {
                        $qaByDateAndKind[$dateKey][$kind] = ($qaByDateAndKind[$dateKey][$kind] ?? 0) + $qaSec;
                    }
                }
            } catch (\Throwable $e) {
                $qaByDateAndKind = [];
                $allTimeQuizByKind = ['practice' => 0, 'quiz' => 0];
            }

            $byDate = [];
            $byKind = ['lesson' => 0, 'practice' => 0, 'quiz' => 0];
            $series = [];
            $totalSeconds = 0;

            for ($i = 0; $i < $days; $i++) {
                $d = $start->copy()->addDays($i);
                $key = $d->format('Y-m-d');

                $dayLesson = max($sdByDateAndKind[$key]['lesson'] ?? 0, $lpByDate[$key] ?? 0);
                $dayPractice = max($sdByDateAndKind[$key]['practice'] ?? 0, $qaByDateAndKind[$key]['practice'] ?? 0);
                $dayQuiz = max($sdByDateAndKind[$key]['quiz'] ?? 0, $qaByDateAndKind[$key]['quiz'] ?? 0);

                $secs = min(self::MAX_DAILY_SECONDS, $dayLesson + $dayPractice + $dayQuiz);
                $byDate[$key] = $secs;

                $byKind['lesson'] += $dayLesson;
                $byKind['practice'] += $dayPractice;
                $byKind['quiz'] += $dayQuiz;

                $totalSeconds += $secs;
                $series[] = [
                    'date' => $key,
                    'label' => $days === 7 ? $d->format('D') : $d->format('M j'),
                    'seconds' => $secs,
                    'formatted' => $this->formatDuration($secs),
                ];
            }

            if ($totalSeconds === 0 && ($allTimeLessonSec + $allTimeQuizByKind['practice'] + $allTimeQuizByKind['quiz']) > 0) {
                $byKind['lesson'] = $allTimeLessonSec;
                $byKind['practice'] = $allTimeQuizByKind['practice'];
                $byKind['quiz'] = $allTimeQuizByKind['quiz'];
                $totalSeconds = $allTimeLessonSec + $allTimeQuizByKind['practice'] + $allTimeQuizByKind['quiz'];

                if (!empty($series)) {
                    $lastIdx = count($series) - 1;
                    $series[$lastIdx]['seconds'] = $totalSeconds;
                    $series[$lastIdx]['formatted'] = $this->formatDuration($totalSeconds);
                }
            }

            $lessonSec = $byKind['lesson'];
            $practiceSec = $byKind['practice'];
            $quizSec = $byKind['quiz'];
            $kindSum = $lessonSec + $practiceSec + $quizSec;

            // Ensure breakdown sum matches totalSeconds exactly
            if ($kindSum !== $totalSeconds) {
                $lessonSec = max(0, $totalSeconds - $practiceSec - $quizSec);
            }

            if ($totalSeconds <= 0) {
                $lessonsPct = 0;
                $practicePct = 0;
                $quizPct = 0;
            } else {
                $lessonsPct = (int) round(($lessonSec / $totalSeconds) * 100);
                $practicePct = (int) round(($practiceSec / $totalSeconds) * 100);
                $quizPct = max(0, 100 - $lessonsPct - $practicePct);
            }

            $dailyAvgSeconds = (int) round($totalSeconds / $days);
            $avgHours = round($dailyAvgSeconds / 3600, 1);
            $dailyAverage = $dailyAvgSeconds === 0
                ? '0h / day'
                : ($avgHours >= 0.1
                    ? "{$avgHours}h / day"
                    : max(1, (int) round($dailyAvgSeconds / 60)) . 'm / day');

            // Trend: null if previous period < 1800s (30m)
            $trend = null;
            $trendPct = null;
            if ($prevTotalSeconds >= self::MIN_PREV_TREND_SECONDS) {
                $trendPct = (int) round((($totalSeconds - $prevTotalSeconds) / $prevTotalSeconds) * 100);
                $trend = ($trendPct >= 0 ? "+{$trendPct}%" : "{$trendPct}%") . ' vs last period';
            }

            return [
                'days' => $days,
                'totalSeconds' => $totalSeconds,
                'total' => $this->formatDuration($totalSeconds),
                'dailyAverageSeconds' => $dailyAvgSeconds,
                'dailyAverage' => $dailyAverage,
                'trend' => $trend,
                'trendPct' => $trendPct,
                'series' => $series,
                'breakdown' => [
                    'total' => $this->formatDuration($totalSeconds),
                    'lessons' => $this->formatDuration($lessonSec),
                    'practice' => $this->formatDuration($practiceSec),
                    'quiz' => $this->formatDuration($quizSec),
                    'lessonsSeconds' => $lessonSec,
                    'practiceSeconds' => $practiceSec,
                    'quizSeconds' => $quizSec,
                    'dailyAverage' => $dailyAverage,
                    'lessonsPct' => $lessonsPct,
                    'practicePct' => $practicePct,
                    'quizPct' => $quizPct,
                ],
            ];
        });
    }

    /**
     * Get Quiz Performance from real QuizAttempt records.
     * Returns null metrics when student has zero attempts.
     */
    public function getQuizPerformance(User $user): array
    {
        try {
            $query = QuizAttempt::where('user_id', $user->id)->whereNotNull('score');
            $attemptsCount = (clone $query)->count();

            if ($attemptsCount === 0) {
                return [
                    'hasData' => false,
                    'attemptsCount' => 0,
                    'averageScore' => null,
                    'averageScoreNum' => null,
                    'correctPct' => null,
                    'incorrectPct' => null,
                    'highestScore' => null,
                    'trend' => null,
                ];
            }

            $avgNum = (int) round((float) (clone $query)->avg('score'));
            $maxScore = (int) round((float) (clone $query)->max('score'));
            $recentTwo = (clone $query)->latest('submitted_at')->take(2)->pluck('score');

            $trend = null;
            if ($recentTwo->count() >= 2) {
                $diff = (int) round($recentTwo[0] - $recentTwo[1]);
                $trend = ($diff >= 0 ? "+{$diff}%" : "{$diff}%") . ' vs last test';
            }

            return [
                'hasData' => true,
                'attemptsCount' => $attemptsCount,
                'averageScore' => "{$avgNum}%",
                'averageScoreNum' => $avgNum,
                'correctPct' => "{$avgNum}%",
                'incorrectPct' => max(0, 100 - $avgNum) . '%',
                'highestScore' => "{$maxScore}%",
                'trend' => $trend,
            ];
        } catch (\Throwable $e) {
            return [
                'hasData' => false,
                'attemptsCount' => 0,
                'averageScore' => null,
                'averageScoreNum' => null,
                'correctPct' => null,
                'incorrectPct' => null,
                'highestScore' => null,
                'trend' => null,
            ];
        }
    }

    public function formatDuration(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $h = (int) floor($seconds / 3600);
        $m = (int) floor(($seconds % 3600) / 60);
        return "{$h}h {$m}m";
    }
}
