<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\QuizAttempt;
use App\Models\StudyDaily;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class LearningActivityService
{
    public const TIMEZONE = 'Asia/Phnom_Penh';
    public const MAX_DAILY_SECONDS = 86400;

    /**
     * Get complete student learning activity analytics strictly from real DB records.
     */
    public function getStudentLearningActivity(?User $user = null, string $range = '7d', string $granularity = 'daily'): array
    {
        $now = Carbon::now(self::TIMEZONE);
        $today = $now->copy()->startOfDay();

        [$windowStart, $windowEnd, $windowDays, $normalizedRange] = $this->resolveWindow($user, $range, $today);
        $normalizedGranularity = in_array($granularity, ['daily', 'weekly', 'monthly'], true) ? $granularity : 'daily';

        if (!$user) {
            return $this->emptyAnalytics($windowStart, $windowEnd, $windowDays, $normalizedRange, $normalizedGranularity, $today);
        }

        $studyDailyRows = collect();
        $lessonProgresses = collect();
        $quizAttempts = collect();
        $enrolledCount = 0;
        $certsCount = 0;

        try {
            if (Schema::hasTable('study_daily')) {
                $studyDailyRows = StudyDaily::where('user_id', $user->id)
                    ->get(['study_date', 'kind', 'seconds']);
            }
        } catch (\Throwable $e) {
            $studyDailyRows = collect();
        }

        try {
            $lessonProgresses = LessonProgress::where('user_id', $user->id)
                ->with(['lesson.course'])
                ->get();
        } catch (\Throwable $e) {
            $lessonProgresses = collect();
        }

        try {
            $quizAttempts = QuizAttempt::where('user_id', $user->id)
                ->with(['quiz.course'])
                ->get();
        } catch (\Throwable $e) {
            $quizAttempts = collect();
        }

        try {
            $enrolledCount = Enrollment::where('student_id', $user->id)
                ->whereIn('status', ['active', 'completed'])
                ->count();
        } catch (\Throwable $e) {
            $enrolledCount = 0;
        }

        try {
            $certsCount = Certificate::where('student_id', $user->id)
                ->whereIn('status', ['valid', 'issued'])
                ->count();
        } catch (\Throwable $e) {
            $certsCount = 0;
        }

        // If range is 'all', tighten windowStart to earliest real activity if any exists
        if ($normalizedRange === 'all') {
            $earliest = $this->findEarliestActivityDate($studyDailyRows, $lessonProgresses, $quizAttempts, $today);
            if ($earliest && $earliest->lt($windowStart)) {
                $windowStart = $earliest->copy()->startOfDay();
                $windowDays = max(1, (int) $windowStart->diffInDays($windowEnd) + 1);
            }
        }

        // Index all daily seconds & sessions by Y-m-d across all time so window, previous window, heatmap, week overview, and streak are consistent
        $sdByDateKind = [];
        foreach ($studyDailyRows as $row) {
            $dKey = $row->study_date instanceof Carbon
                ? $row->study_date->format('Y-m-d')
                : Carbon::parse($row->study_date)->format('Y-m-d');
            $kind = in_array($row->kind, ['lesson', 'practice', 'quiz'], true) ? $row->kind : 'lesson';
            $secs = min(self::MAX_DAILY_SECONDS, max(0, (int) $row->seconds));
            $sdByDateKind[$dKey][$kind] = ($sdByDateKind[$dKey][$kind] ?? 0) + $secs;
        }

        $lpByDate = [];
        $lpSessionsByDate = [];
        $completedLessonsByDate = [];
        $totalCompletedLessonsAllTime = 0;
        $activityFeed = [];

        foreach ($lessonProgresses as $lp) {
            $sec = (int) ($lp->seconds_watched ?? 0);
            $percent = (int) ($lp->percent ?? 0);
            $isCompleted = !empty($lp->completed_at) || $percent >= 90;
            if ($isCompleted) {
                $totalCompletedLessonsAllTime++;
            }

            if ($sec <= 0 && ($isCompleted || $percent > 0)) {
                $dur = max(300, (int) ($lp->lesson?->duration_seconds ?? 600));
                $sec = $isCompleted ? $dur : (int) round(($percent / 100) * $dur);
            }

            if ($sec <= 0 && $percent <= 0 && !$isCompleted) {
                continue;
            }

            $refTime = $lp->updated_at ?? $lp->completed_at ?? $lp->created_at ?? $now;
            $dt = Carbon::parse($refTime)->timezone(self::TIMEZONE);
            $dKey = $dt->format('Y-m-d');

            $lpByDate[$dKey] = ($lpByDate[$dKey] ?? 0) + $sec;
            $lpSessionsByDate[$dKey] = ($lpSessionsByDate[$dKey] ?? 0) + 1;

            if ($isCompleted) {
                $compTime = $lp->completed_at ? Carbon::parse($lp->completed_at)->timezone(self::TIMEZONE) : $dt;
                $cKey = $compTime->format('Y-m-d');
                $completedLessonsByDate[$cKey] = ($completedLessonsByDate[$cKey] ?? 0) + 1;
            }

            $lessonTitle = $lp->lesson?->title ?? 'Course Lesson';
            $courseTitle = $lp->lesson?->course?->title ?? 'Enrolled Course';
            $courseId = $lp->lesson?->course_id;
            $lessonId = $lp->lesson_id;

            $activityFeed[] = [
                'id'             => 'lp_' . $lp->id,
                'timestamp'      => $dt->getTimestamp(),
                'title'          => $lessonTitle,
                'title_kh'       => $lp->lesson?->kh_title ?: $lessonTitle,
                'description'    => $isCompleted ? 'Completed lesson' : 'Watched lesson content',
                'description_kh' => $isCompleted ? 'បានបញ្ចប់មេរៀន' : 'បានសិក្សាមេរៀន',
                'course'         => $courseTitle,
                'course_kh'      => $lp->lesson?->course?->title_kh ?: $courseTitle,
                'type'           => 'Lesson',
                'duration'       => $this->formatShortDuration($sec),
                'time'           => $this->formatActivityTimestamp($dt, $today),
                'progress'       => $isCompleted ? 100 : min(99, max(1, $percent)),
                'icon'           => '▶',
                'icon_bg'        => 'from-purple-500 to-indigo-600',
                'progress_color' => 'bg-purple-500',
                'href'           => ($courseId && $lessonId) ? "/student/my-courses/{$courseId}/lessons/{$lessonId}" : '/student/my-courses/enrolled',
            ];
        }

        $qaByDateKind = [];
        $qaSessionsByDate = [];
        $quizzesTakenByDate = [];
        $totalSubmittedQuizzesAllTime = 0;
        $maxQuizScore = 0;

        foreach ($quizAttempts as $qa) {
            if ($qa->score === null && empty($qa->submitted_at)) {
                continue;
            }

            $totalSubmittedQuizzesAllTime++;
            $scoreVal = (int) round((float) ($qa->score ?? 0));
            if ($scoreVal > $maxQuizScore) {
                $maxQuizScore = $scoreVal;
            }

            $qaSec = 0;
            if ($qa->started_at && $qa->submitted_at) {
                $qaSec = max(60, min(7200, (int) abs(Carbon::parse($qa->submitted_at)->diffInSeconds(Carbon::parse($qa->started_at)))));
            }
            if ($qaSec <= 0) {
                $limitMins = (int) ($qa->quiz?->time_limit_minutes ?? 10);
                $qaSec = max(120, min(3600, $limitMins * 30));
            }

            $quizTypeRaw = strtolower((string) ($qa->quiz?->type ?? 'quiz'));
            $isPractice = str_contains($quizTypeRaw, 'practice') || str_contains($quizTypeRaw, 'coding');
            $kind = $isPractice ? 'practice' : 'quiz';

            $refTime = $qa->submitted_at ?? $qa->updated_at ?? $qa->created_at ?? $now;
            $dt = Carbon::parse($refTime)->timezone(self::TIMEZONE);
            $dKey = $dt->format('Y-m-d');

            $qaByDateKind[$dKey][$kind] = ($qaByDateKind[$dKey][$kind] ?? 0) + $qaSec;
            $qaSessionsByDate[$dKey] = ($qaSessionsByDate[$dKey] ?? 0) + 1;
            $quizzesTakenByDate[$dKey] = ($quizzesTakenByDate[$dKey] ?? 0) + 1;

            $quizTitle = $qa->quiz?->title ?? 'Course Quiz';
            $courseTitle = $qa->quiz?->course?->title ?? 'Enrolled Course';

            $activityFeed[] = [
                'id'             => 'qa_' . $qa->id,
                'timestamp'      => $dt->getTimestamp(),
                'title'          => $quizTitle,
                'title_kh'       => $quizTitle,
                'description'    => "Scored {$scoreVal}% on " . ($isPractice ? 'practice drill' : 'quiz assessment'),
                'description_kh' => "ទទួលបានពិន្ទុ {$scoreVal}% លើការប្រឡង",
                'course'         => $courseTitle,
                'course_kh'      => $qa->quiz?->course?->title_kh ?: $courseTitle,
                'type'           => $isPractice ? 'Practice' : 'Quiz',
                'duration'       => $this->formatShortDuration($qaSec),
                'time'           => $this->formatActivityTimestamp($dt, $today),
                'progress'       => min(100, max(0, $scoreVal)),
                'icon'           => $isPractice ? '💻' : '📝',
                'icon_bg'        => $isPractice ? 'from-emerald-500 to-teal-600' : 'from-blue-500 to-indigo-600',
                'progress_color' => $isPractice ? 'bg-emerald-500' : 'bg-blue-500',
                'href'           => $qa->quiz_id ? "/student/quiz/{$qa->quiz_id}" : '/student/quizzes/history',
            ];
        }

        // Helper closure to compute merged daily stats for any date key
        $getDayMetrics = function (string $dKey) use ($sdByDateKind, $lpByDate, $qaByDateKind, $lpSessionsByDate, $qaSessionsByDate): array {
            $lessonSec = max($sdByDateKind[$dKey]['lesson'] ?? 0, $lpByDate[$dKey] ?? 0);
            $practiceSec = max($sdByDateKind[$dKey]['practice'] ?? 0, $qaByDateKind[$dKey]['practice'] ?? 0);
            $quizSec = max($sdByDateKind[$dKey]['quiz'] ?? 0, $qaByDateKind[$dKey]['quiz'] ?? 0);

            $totalSec = min(self::MAX_DAILY_SECONDS, $lessonSec + $practiceSec + $quizSec);
            if ($lessonSec + $practiceSec + $quizSec > self::MAX_DAILY_SECONDS && ($lessonSec + $practiceSec + $quizSec) > 0) {
                $scale = self::MAX_DAILY_SECONDS / ($lessonSec + $practiceSec + $quizSec);
                $lessonSec = (int) round($lessonSec * $scale);
                $practiceSec = (int) round($practiceSec * $scale);
                $quizSec = max(0, self::MAX_DAILY_SECONDS - $lessonSec - $practiceSec);
            }

            $sessions = ($lpSessionsByDate[$dKey] ?? 0) + ($qaSessionsByDate[$dKey] ?? 0);
            if ($sessions === 0 && $totalSec > 0) {
                // StudyDaily rows existed without a matching timestamp in lp/qa on that exact day
                $sessions = count(array_filter([
                    $sdByDateKind[$dKey]['lesson'] ?? 0,
                    $sdByDateKind[$dKey]['practice'] ?? 0,
                    $sdByDateKind[$dKey]['quiz'] ?? 0,
                ], fn ($v) => $v > 0));
            }

            return [
                'seconds'  => $totalSec,
                'lesson'   => $lessonSec,
                'practice' => $practiceSec,
                'quiz'     => $quizSec,
                'sessions' => $sessions,
            ];
        };

        // 1. Aggregate selected window
        $windowTotalSec = 0;
        $windowLessonSec = 0;
        $windowPracticeSec = 0;
        $windowQuizSec = 0;
        $windowSessions = 0;
        $windowActiveDays = 0;
        $windowCompletedLessons = 0;
        $windowQuizzesTaken = 0;
        $peakDaySec = 0;
        $peakDayDate = null;
        $dailyRecords = [];

        $cursor = $windowStart->copy();
        while ($cursor->lte($windowEnd)) {
            $dKey = $cursor->format('Y-m-d');
            $m = $getDayMetrics($dKey);
            $windowTotalSec += $m['seconds'];
            $windowLessonSec += $m['lesson'];
            $windowPracticeSec += $m['practice'];
            $windowQuizSec += $m['quiz'];
            $windowSessions += $m['sessions'];
            $windowCompletedLessons += ($completedLessonsByDate[$dKey] ?? 0);
            $windowQuizzesTaken += ($quizzesTakenByDate[$dKey] ?? 0);

            if ($m['seconds'] > 0) {
                $windowActiveDays++;
                if ($m['seconds'] > $peakDaySec) {
                    $peakDaySec = $m['seconds'];
                    $peakDayDate = $cursor->copy();
                }
            }

            $dailyRecords[] = [
                'carbon'   => $cursor->copy(),
                'date_key' => $dKey,
                'seconds'  => $m['seconds'],
                'sessions' => $m['sessions'],
            ];

            $cursor->addDay();
        }

        // Previous period comparison
        $prevEnd = $windowStart->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays(max(1, $windowDays) - 1);
        $prevTotalSec = 0;
        $prevSessions = 0;
        $pCursor = $prevStart->copy();
        while ($pCursor->lte($prevEnd)) {
            $pm = $getDayMetrics($pCursor->format('Y-m-d'));
            $prevTotalSec += $pm['seconds'];
            $prevSessions += $pm['sessions'];
            $pCursor->addDay();
        }

        $dailyAvgSec = $windowDays > 0 ? (int) round($windowTotalSec / $windowDays) : 0;
        $prevDailyAvgSec = $windowDays > 0 ? (int) round($prevTotalSec / $windowDays) : 0;

        $dateRangeLabel = $windowStart->format('M j') . ' - ' . $windowEnd->format('M j, Y');
        $periodSuffix = $normalizedRange === '7d' ? 'vs last 7 days' : 'vs prior period';

        $activeDaysNote = 'No activity yet';
        if ($windowActiveDays > 0) {
            if ($windowActiveDays >= $windowDays) {
                $activeDaysNote = 'Perfect!';
            } elseif ($windowActiveDays >= ceil($windowDays * 0.5)) {
                $activeDaysNote = 'Consistent!';
            } else {
                $activeDaysNote = 'Building habit';
            }
        }

        $summary = [
            'date_range_label'      => $dateRangeLabel,
            'total_study_time'      => $this->formatDuration($windowTotalSec),
            'total_seconds'         => $windowTotalSec,
            'time_trend'            => $this->formatPercentTrend($windowTotalSec, $prevTotalSec, $periodSuffix),
            'daily_average'         => $this->formatDuration($dailyAvgSec),
            'daily_avg_trend'       => $this->formatPercentTrend($dailyAvgSec, $prevDailyAvgSec, $periodSuffix),
            'total_sessions'        => $windowSessions,
            'study_sessions'        => $windowSessions,
            'sessions_trend'        => $this->formatCountTrend($windowSessions, $prevSessions, $periodSuffix),
            'active_days'           => "{$windowActiveDays} / {$windowDays}",
            'active_days_count'     => $windowActiveDays,
            'window_days'           => $windowDays,
            'active_days_note'      => $activeDaysNote,
            'peak_day'              => $this->formatDuration($peakDaySec),
            'longest_session'       => $this->formatDuration($peakDaySec),
            'longest_session_date'  => $peakDayDate ? $peakDayDate->format('M j, Y') : '—',
            'completed_lessons'     => $windowCompletedLessons,
            'quizzes_taken'         => $windowQuizzesTaken,
            'enrolled_courses'      => $enrolledCount,
        ];

        // 2. Trend Chart (Daily / Weekly / Monthly buckets)
        $trendPoints = $this->buildTrendPoints($dailyRecords, $normalizedGranularity);
        $maxHoursRaw = 0.0;
        $peakPoint = null;
        foreach ($trendPoints as $idx => $pt) {
            if ($pt['hours'] > $maxHoursRaw) {
                $maxHoursRaw = $pt['hours'];
                $peakPoint = array_merge($pt, ['index' => $idx]);
            }
        }
        $maxHoursAxis = $maxHoursRaw > 0 ? max(2.0, ceil($maxHoursRaw * 1.2)) : 4.0;

        $trendChart = [
            'granularity' => $normalizedGranularity,
            'max_hours'   => $maxHoursAxis,
            'peak_point'  => $windowTotalSec > 0 ? $peakPoint : null,
            'points'      => $trendPoints,
        ];

        // 3. Activity Breakdown (Lesson, Quiz, Practice)
        $sumKind = $windowLessonSec + $windowQuizSec + $windowPracticeSec;
        if ($sumKind <= 0) {
            $lessonPct = 0;
            $quizPct = 0;
            $practicePct = 0;
        } else {
            $lessonPct = (int) round(($windowLessonSec / $sumKind) * 100);
            $quizPct = (int) round(($windowQuizSec / $sumKind) * 100);
            $practicePct = max(0, 100 - $lessonPct - $quizPct);
        }

        $activityBreakdown = [
            [
                'id'         => 1,
                'key'        => 'lesson',
                'name'       => 'Watching Lessons',
                'name_kh'    => 'មើលមេរៀន',
                'seconds'    => $windowLessonSec,
                'percentage' => $lessonPct,
                'duration'   => $this->formatDuration($windowLessonSec),
                'color'      => '#8B5CF6',
                'bg_class'   => 'bg-purple-500',
                'code'       => '▶',
            ],
            [
                'id'         => 2,
                'key'        => 'quiz',
                'name'       => 'Doing Quizzes',
                'name_kh'    => 'ធ្វើតេស្ត Quiz',
                'seconds'    => $windowQuizSec,
                'percentage' => $quizPct,
                'duration'   => $this->formatDuration($windowQuizSec),
                'color'      => '#3B82F6',
                'bg_class'   => 'bg-blue-500',
                'code'       => '📝',
            ],
            [
                'id'         => 3,
                'key'        => 'practice',
                'name'       => 'Practice & Exercises',
                'name_kh'    => 'អនុវត្តលំហាត់',
                'seconds'    => $windowPracticeSec,
                'percentage' => $practicePct,
                'duration'   => $this->formatDuration($windowPracticeSec),
                'color'      => '#10B981',
                'bg_class'   => 'bg-emerald-500',
                'code'       => '💻',
            ],
        ];

        // 4. Calendar Heatmap (Last 5 Weeks Mon-Sun ending on current week's Sunday)
        $endOfCurrentWeek = $today->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay();
        $heatmapStart = $endOfCurrentWeek->copy()->subDays(34); // 5 weeks * 7 days = 35 days
        $weeks = [];
        for ($w = 0; $w < 5; $w++) {
            $wkStart = $heatmapStart->copy()->addDays($w * 7);
            $wkEnd = $wkStart->copy()->addDays(6);
            $cells = [];
            for ($d = 0; $d < 7; $d++) {
                $cellDate = $wkStart->copy()->addDays($d);
                $cKey = $cellDate->format('Y-m-d');
                $cm = $cellDate->gt($today)
                    ? ['seconds' => 0, 'sessions' => 0]
                    : $getDayMetrics($cKey);
                $hours = round($cm['seconds'] / 3600, 2);
                $cells[] = [
                    'day'      => $cellDate->format('D'),
                    'date'     => $cellDate->format('M j'),
                    'iso_date' => $cKey,
                    'hours'    => $hours,
                    'duration' => $this->formatDuration($cm['seconds']),
                    'level'    => $this->resolveHeatmapLevel($cm['seconds']),
                    'sessions' => $cm['sessions'],
                ];
            }
            $weeks[] = [
                'label' => $wkStart->format('M j') . ' - ' . $wkEnd->format('M j'),
                'cells' => $cells,
            ];
        }

        $calendarHeatmap = [
            'days'  => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'weeks' => $weeks,
        ];

        // 5. Recent Learning Activity Table
        usort($activityFeed, fn ($a, $b) => $b['timestamp'] <=> $a['timestamp']);
        $recentActivities = array_slice($activityFeed, 0, 10);

        // 6. This Week Overview (Mon - Sun of current week vs previous week)
        $thisWeekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
        $thisWeekEnd = $today->copy()->endOfWeek(Carbon::SUNDAY);
        $lastWeekStart = $thisWeekStart->copy()->subWeek();
        $lastWeekEnd = $thisWeekEnd->copy()->subWeek();

        $twSec = 0;
        $twSessions = 0;
        $twActive = 0;
        $twLessons = 0;
        $twQuizzes = 0;
        for ($i = 0; $i < 7; $i++) {
            $d = $thisWeekStart->copy()->addDays($i);
            if ($d->gt($today)) {
                continue;
            }
            $k = $d->format('Y-m-d');
            $m = $getDayMetrics($k);
            $twSec += $m['seconds'];
            $twSessions += $m['sessions'];
            if ($m['seconds'] > 0) {
                $twActive++;
            }
            $twLessons += ($completedLessonsByDate[$k] ?? 0);
            $twQuizzes += ($quizzesTakenByDate[$k] ?? 0);
        }

        $lwSec = 0;
        $lwSessions = 0;
        $lwActive = 0;
        $lwLessons = 0;
        $lwQuizzes = 0;
        for ($i = 0; $i < 7; $i++) {
            $d = $lastWeekStart->copy()->addDays($i);
            $k = $d->format('Y-m-d');
            $m = $getDayMetrics($k);
            $lwSec += $m['seconds'];
            $lwSessions += $m['sessions'];
            if ($m['seconds'] > 0) {
                $lwActive++;
            }
            $lwLessons += ($completedLessonsByDate[$k] ?? 0);
            $lwQuizzes += ($quizzesTakenByDate[$k] ?? 0);
        }

        $twAvgSessionSec = $twSessions > 0 ? (int) round($twSec / $twSessions) : 0;
        $lwAvgSessionSec = $lwSessions > 0 ? (int) round($lwSec / $lwSessions) : 0;

        $thisWeekOverview = [
            'date_range' => $thisWeekStart->format('M j') . ' - ' . $thisWeekEnd->format('M j, Y'),
            'metrics'    => [
                [
                    'label'       => 'Total Time',
                    'label_kh'    => 'ម៉ោងសិក្សាសរុប',
                    'value'       => $this->formatDuration($twSec),
                    'trend'       => $this->formatPercentTrend($twSec, $lwSec, 'vs last week'),
                    'is_positive' => $twSec >= $lwSec,
                ],
                [
                    'label'       => 'Sessions',
                    'label_kh'    => 'ចំនួនដងសិក្សា',
                    'value'       => (string) $twSessions,
                    'trend'       => $this->formatCountTrend($twSessions, $lwSessions, 'vs last week'),
                    'is_positive' => $twSessions >= $lwSessions,
                ],
                [
                    'label'       => 'Avg. Session',
                    'label_kh'    => 'មធ្យមក្នុងមួយដង',
                    'value'       => $this->formatDuration($twAvgSessionSec),
                    'trend'       => $this->formatPercentTrend($twAvgSessionSec, $lwAvgSessionSec, 'vs last week'),
                    'is_positive' => $twAvgSessionSec >= $lwAvgSessionSec,
                ],
                [
                    'label'       => 'Active Days',
                    'label_kh'    => 'ថ្ងៃសកម្ម',
                    'value'       => (string) $twActive,
                    'trend'       => $this->formatCountTrend($twActive, $lwActive, 'vs last week'),
                    'is_positive' => $twActive >= $lwActive,
                ],
                [
                    'label'       => 'Completed Lessons',
                    'label_kh'    => 'មេរៀនបានបញ្ចប់',
                    'value'       => (string) $twLessons,
                    'trend'       => $this->formatCountTrend($twLessons, $lwLessons, 'vs last week'),
                    'is_positive' => $twLessons >= $lwLessons,
                ],
                [
                    'label'       => 'Quizzes Taken',
                    'label_kh'    => 'តេស្តបានធ្វើ',
                    'value'       => (string) $twQuizzes,
                    'trend'       => $this->formatCountTrend($twQuizzes, $lwQuizzes, 'vs last week'),
                    'is_positive' => $twQuizzes >= $lwQuizzes,
                ],
            ],
        ];

        // 7. Learning Streak (Current streak, Best streak, Last 7 days array)
        $learningStreak = $this->computeLearningStreak($sdByDateKind, $lpByDate, $qaByDateKind, $today);

        // 8. Recent Achievements (Strictly unlocked from real DB milestones; [] when 0)
        $allTimeSeconds = 0;
        $allDateKeys = array_unique(array_merge(
            array_keys($sdByDateKind),
            array_keys($lpByDate),
            array_keys($qaByDateKind)
        ));
        foreach ($allDateKeys as $dk) {
            $allTimeSeconds += $getDayMetrics($dk)['seconds'];
        }

        $recentAchievements = $this->buildRealAchievements(
            $totalCompletedLessonsAllTime,
            $totalSubmittedQuizzesAllTime,
            $maxQuizScore,
            $learningStreak['best_streak'],
            $allTimeSeconds,
            $certsCount,
            $today
        );

        return [
            'summary'             => $summary,
            'trend_chart'         => $trendChart,
            'activity_breakdown'  => $activityBreakdown,
            'calendar_heatmap'    => $calendarHeatmap,
            'recent_activities'   => $recentActivities,
            'this_week_overview'  => $thisWeekOverview,
            'learning_streak'     => $learningStreak,
            'recent_achievements' => $recentAchievements,
        ];
    }

    protected function resolveWindow(?User $user, string $range, Carbon $today): array
    {
        $r = strtolower(trim($range));
        return match ($r) {
            '30d' => [$today->copy()->subDays(29), $today->copy(), 30, '30d'],
            '90d', '3m' => [$today->copy()->subDays(89), $today->copy(), 90, '90d'],
            '6m', '180d' => [$today->copy()->subDays(179), $today->copy(), 180, '6m'],
            '1y', '365d' => [$today->copy()->subDays(364), $today->copy(), 365, '1y'],
            'this_month' => [
                $today->copy()->startOfMonth(),
                $today->copy(),
                max(1, (int) $today->copy()->startOfMonth()->diffInDays($today) + 1),
                'this_month',
            ],
            'all' => [
                $user && $user->created_at
                    ? Carbon::parse($user->created_at)->timezone(self::TIMEZONE)->startOfDay()
                    : $today->copy()->subDays(29),
                $today->copy(),
                max(1, (int) ($user && $user->created_at
                    ? Carbon::parse($user->created_at)->timezone(self::TIMEZONE)->startOfDay()->diffInDays($today) + 1
                    : 30)),
                'all',
            ],
            default => [$today->copy()->subDays(6), $today->copy(), 7, '7d'],
        };
    }

    protected function findEarliestActivityDate($studyDailyRows, $lessonProgresses, $quizAttempts, Carbon $today): ?Carbon
    {
        $earliest = null;

        foreach ($studyDailyRows as $r) {
            $d = Carbon::parse($r->study_date, self::TIMEZONE)->startOfDay();
            if (!$earliest || $d->lt($earliest)) {
                $earliest = $d;
            }
        }
        foreach ($lessonProgresses as $lp) {
            $ref = $lp->created_at ?? $lp->updated_at;
            if ($ref) {
                $d = Carbon::parse($ref)->timezone(self::TIMEZONE)->startOfDay();
                if (!$earliest || $d->lt($earliest)) {
                    $earliest = $d;
                }
            }
        }
        foreach ($quizAttempts as $qa) {
            $ref = $qa->submitted_at ?? $qa->created_at;
            if ($ref) {
                $d = Carbon::parse($ref)->timezone(self::TIMEZONE)->startOfDay();
                if (!$earliest || $d->lt($earliest)) {
                    $earliest = $d;
                }
            }
        }

        return $earliest ?? $today->copy()->subDays(6);
    }

    protected function buildTrendPoints(array $dailyRecords, string $granularity): array
    {
        if (empty($dailyRecords)) {
            return [];
        }

        if ($granularity === 'daily') {
            return array_map(function (array $rec) {
                /** @var Carbon $c */
                $c = $rec['carbon'];
                $sec = (int) $rec['seconds'];
                return [
                    'date'     => $c->format('M j'),
                    'label'    => $c->format('M j'),
                    'hours'    => round($sec / 3600, 2),
                    'seconds'  => $sec,
                    'duration' => $this->formatDuration($sec),
                ];
            }, $dailyRecords);
        }

        $buckets = [];
        foreach ($dailyRecords as $rec) {
            /** @var Carbon $c */
            $c = $rec['carbon'];
            if ($granularity === 'monthly') {
                $bKey = $c->format('Y-m');
                $label = $c->format('M Y');
            } else {
                $wkStart = $c->copy()->startOfWeek(Carbon::MONDAY);
                $bKey = $wkStart->format('Y-m-d');
                $label = $wkStart->format('M j');
            }

            if (!isset($buckets[$bKey])) {
                $buckets[$bKey] = [
                    'label'   => $label,
                    'seconds' => 0,
                ];
            }
            $buckets[$bKey]['seconds'] += (int) $rec['seconds'];
        }

        $points = [];
        foreach ($buckets as $b) {
            $sec = (int) $b['seconds'];
            $points[] = [
                'date'     => $b['label'],
                'label'    => $b['label'],
                'hours'    => round($sec / 3600, 2),
                'seconds'  => $sec,
                'duration' => $this->formatDuration($sec),
            ];
        }

        return $points;
    }

    protected function computeLearningStreak(array $sdByDateKind, array $lpByDate, array $qaByDateKind, Carbon $today): array
    {
        $activeDatesMap = [];

        foreach ($sdByDateKind as $dKey => $kinds) {
            if (array_sum($kinds) > 0) {
                $activeDatesMap[$dKey] = true;
            }
        }
        foreach ($lpByDate as $dKey => $sec) {
            if ($sec > 0) {
                $activeDatesMap[$dKey] = true;
            }
        }
        foreach ($qaByDateKind as $dKey => $kinds) {
            if (array_sum($kinds) > 0) {
                $activeDatesMap[$dKey] = true;
            }
        }

        // Last 7 days ending today
        $days7 = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = $today->copy()->subDays($i);
            $k = $d->format('Y-m-d');
            $days7[] = [
                'label'    => $d->format('M j'),
                'day_name' => $d->format('D'),
                'date'     => $d->format('j'),
                'active'   => isset($activeDatesMap[$k]),
            ];
        }

        if (empty($activeDatesMap)) {
            return [
                'current_streak'   => 0,
                'best_streak'      => 0,
                'best_streak_date' => '—',
                'days'             => $days7,
            ];
        }

        $sortedDates = array_keys($activeDatesMap);
        sort($sortedDates);

        // Compute best streak
        $bestStreak = 0;
        $bestStreakEndDate = null;
        $run = 0;
        $prevCarbon = null;

        foreach ($sortedDates as $dStr) {
            $cur = Carbon::createFromFormat('Y-m-d', $dStr, self::TIMEZONE)->startOfDay();
            if ($prevCarbon && (int) $prevCarbon->diffInDays($cur) === 1) {
                $run++;
            } else {
                $run = 1;
            }
            if ($run >= $bestStreak) {
                $bestStreak = $run;
                $bestStreakEndDate = $cur->copy();
            }
            $prevCarbon = $cur;
        }

        // Compute current streak (active today or yesterday)
        $currentStreak = 0;
        $check = $today->copy();
        if (!isset($activeDatesMap[$check->format('Y-m-d')])) {
            $check->subDay();
        }
        while (isset($activeDatesMap[$check->format('Y-m-d')])) {
            $currentStreak++;
            $check->subDay();
        }

        return [
            'current_streak'   => $currentStreak,
            'best_streak'      => $bestStreak,
            'best_streak_date' => $bestStreakEndDate ? $bestStreakEndDate->format('M Y') : '—',
            'days'             => $days7,
        ];
    }

    protected function buildRealAchievements(
        int $completedLessons,
        int $submittedQuizzes,
        int $maxQuizScore,
        int $bestStreak,
        int $allTimeSeconds,
        int $certsCount,
        Carbon $today
    ): array {
        $achievements = [];

        if ($completedLessons >= 1) {
            $achievements[] = [
                'id'             => 'first_lesson',
                'title'          => $completedLessons >= 10 ? 'Lesson Explorer' : 'First Step Taken',
                'title_kh'       => $completedLessons >= 10 ? 'អ្នកសិក្សាសកម្ម' : 'ជំហានដំបូង',
                'description'    => "Completed {$completedLessons} " . ($completedLessons === 1 ? 'lesson' : 'lessons'),
                'description_kh' => "បានបញ្ចប់ {$completedLessons} មេរៀន",
                'date'           => $today->format('M j, Y'),
                'icon'           => '📘',
                'icon_bg'        => 'from-purple-500/20 to-indigo-500/20 text-purple-400 border border-purple-500/30',
            ];
        }

        if ($bestStreak >= 3) {
            $achievements[] = [
                'id'             => 'streak',
                'title'          => $bestStreak >= 7 ? 'Consistent Learner' : 'Streak Builder',
                'title_kh'       => $bestStreak >= 7 ? 'អ្នកសិក្សាទៀងទាត់' : 'រក្សាទម្លាប់រៀន',
                'description'    => "Studied {$bestStreak} days in a row",
                'description_kh' => "បានសិក្សា {$bestStreak} ថ្ងៃជាប់គ្នា",
                'date'           => $today->format('M j, Y'),
                'icon'           => '🔥',
                'icon_bg'        => 'from-amber-500/20 to-orange-500/20 text-amber-400 border border-amber-500/30',
            ];
        }

        if ($submittedQuizzes >= 1) {
            $achievements[] = [
                'id'             => 'quiz',
                'title'          => $maxQuizScore >= 85 ? 'Quiz Ace' : 'Quiz Challenger',
                'title_kh'       => $maxQuizScore >= 85 ? 'ជើងខ្លាំង Quiz' : 'អ្នកប្រឡងសកម្ម',
                'description'    => "Best quiz score: {$maxQuizScore}% ({$submittedQuizzes} taken)",
                'description_kh' => "ពិន្ទុខ្ពស់បំផុត {$maxQuizScore}%",
                'date'           => $today->format('M j, Y'),
                'icon'           => '🏆',
                'icon_bg'        => 'from-emerald-500/20 to-teal-500/20 text-emerald-400 border border-emerald-500/30',
            ];
        }

        if ($allTimeSeconds >= 36000) {
            $hrs = (int) floor($allTimeSeconds / 3600);
            $achievements[] = [
                'id'             => 'time_master',
                'title'          => 'Dedicated Scholar',
                'title_kh'       => 'អ្នកសិក្សាឧស្សាហ៍',
                'description'    => "Logged {$hrs}+ hours of study time",
                'description_kh' => "បានសិក្សាសរុបជាង {$hrs} ម៉ោង",
                'date'           => $today->format('M j, Y'),
                'icon'           => '⏱',
                'icon_bg'        => 'from-blue-500/20 to-cyan-500/20 text-blue-400 border border-blue-500/30',
            ];
        }

        if ($certsCount >= 1) {
            $achievements[] = [
                'id'             => 'certified',
                'title'          => 'Certified Graduate',
                'title_kh'       => 'ទទួលបានវិញ្ញាបនបត្រ',
                'description'    => "Earned {$certsCount} official " . ($certsCount === 1 ? 'certificate' : 'certificates'),
                'description_kh' => "ទទួលបានវិញ្ញាបនបត្រចំនួន {$certsCount}",
                'date'           => $today->format('M j, Y'),
                'icon'           => '🎓',
                'icon_bg'        => 'from-amber-500/20 to-yellow-500/20 text-amber-300 border border-amber-500/30',
            ];
        }

        return array_slice($achievements, 0, 4);
    }

    protected function resolveHeatmapLevel(int $seconds): int
    {
        if ($seconds <= 0) {
            return 0;
        }
        $hours = $seconds / 3600;
        if ($hours >= 5) {
            return 4;
        }
        if ($hours >= 3) {
            return 3;
        }
        if ($hours >= 1) {
            return 2;
        }
        return 1;
    }

    public function formatDuration(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $h = (int) floor($seconds / 3600);
        $m = (int) floor(($seconds % 3600) / 60);
        return sprintf('%dh %02dm', $h, $m);
    }

    protected function formatShortDuration(int $seconds): string
    {
        $seconds = max(0, $seconds);
        if ($seconds === 0) {
            return '0m';
        }
        $h = (int) floor($seconds / 3600);
        $m = max(1, (int) round(($seconds % 3600) / 60));
        if ($h > 0) {
            return sprintf('%dh %02dm', $h, $m === 60 ? 0 : $m);
        }
        return "{$m}m";
    }

    protected function formatActivityTimestamp(Carbon $dt, Carbon $today): string
    {
        if ($dt->isSameDay($today)) {
            return 'Today, ' . $dt->format('h:i A');
        }
        if ($dt->isSameDay($today->copy()->subDay())) {
            return 'Yesterday, ' . $dt->format('h:i A');
        }
        return $dt->format('M j, Y');
    }

    protected function formatPercentTrend(int $current, int $previous, string $suffix): string
    {
        if ($current === 0 && $previous === 0) {
            return "0% {$suffix}";
        }
        if ($previous <= 0) {
            return "+100% {$suffix}";
        }
        $pct = (int) round((($current - $previous) / $previous) * 100);
        $sign = $pct >= 0 ? '+' : '';
        return "{$sign}{$pct}% {$suffix}";
    }

    protected function formatCountTrend(int $current, int $previous, string $suffix): string
    {
        $diff = $current - $previous;
        if ($diff === 0) {
            return "0 {$suffix}";
        }
        $sign = $diff > 0 ? '+' : '';
        return "{$sign}{$diff} {$suffix}";
    }

    protected function emptyAnalytics(
        Carbon $windowStart,
        Carbon $windowEnd,
        int $windowDays,
        string $range,
        string $granularity,
        Carbon $today
    ): array {
        $dailyRecords = [];
        $c = $windowStart->copy();
        while ($c->lte($windowEnd)) {
            $dailyRecords[] = [
                'carbon'   => $c->copy(),
                'date_key' => $c->format('Y-m-d'),
                'seconds'  => 0,
                'sessions' => 0,
            ];
            $c->addDay();
        }

        $thisWeekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
        $thisWeekEnd = $today->copy()->endOfWeek(Carbon::SUNDAY);

        $endOfCurrentWeek = $today->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay();
        $heatmapStart = $endOfCurrentWeek->copy()->subDays(34);
        $weeks = [];
        for ($w = 0; $w < 5; $w++) {
            $wkStart = $heatmapStart->copy()->addDays($w * 7);
            $wkEnd = $wkStart->copy()->addDays(6);
            $cells = [];
            for ($d = 0; $d < 7; $d++) {
                $cd = $wkStart->copy()->addDays($d);
                $cells[] = [
                    'day'      => $cd->format('D'),
                    'date'     => $cd->format('M j'),
                    'iso_date' => $cd->format('Y-m-d'),
                    'hours'    => 0.0,
                    'duration' => '0h 00m',
                    'level'    => 0,
                    'sessions' => 0,
                ];
            }
            $weeks[] = [
                'label' => $wkStart->format('M j') . ' - ' . $wkEnd->format('M j'),
                'cells' => $cells,
            ];
        }

        $days7 = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = $today->copy()->subDays($i);
            $days7[] = [
                'label'    => $d->format('M j'),
                'day_name' => $d->format('D'),
                'date'     => $d->format('j'),
                'active'   => false,
            ];
        }

        return [
            'summary' => [
                'date_range_label'      => $windowStart->format('M j') . ' - ' . $windowEnd->format('M j, Y'),
                'total_study_time'      => '0h 00m',
                'total_seconds'         => 0,
                'time_trend'            => '0% vs prior period',
                'daily_average'         => '0h 00m',
                'daily_avg_trend'       => '0% vs prior period',
                'total_sessions'        => 0,
                'study_sessions'        => 0,
                'sessions_trend'        => '0 vs prior period',
                'active_days'           => "0 / {$windowDays}",
                'active_days_count'     => 0,
                'window_days'           => $windowDays,
                'active_days_note'      => 'No activity yet',
                'peak_day'              => '0h 00m',
                'longest_session'       => '0h 00m',
                'longest_session_date'  => '—',
                'completed_lessons'     => 0,
                'quizzes_taken'         => 0,
                'enrolled_courses'      => 0,
            ],
            'trend_chart' => [
                'granularity' => $granularity,
                'max_hours'   => 4.0,
                'peak_point'  => null,
                'points'      => $this->buildTrendPoints($dailyRecords, $granularity),
            ],
            'activity_breakdown' => [
                ['id' => 1, 'key' => 'lesson', 'name' => 'Watching Lessons', 'name_kh' => 'មើលមេរៀន', 'seconds' => 0, 'percentage' => 0, 'duration' => '0h 00m', 'color' => '#8B5CF6', 'bg_class' => 'bg-purple-500', 'code' => '▶'],
                ['id' => 2, 'key' => 'quiz', 'name' => 'Doing Quizzes', 'name_kh' => 'ធ្វើតេស្ត Quiz', 'seconds' => 0, 'percentage' => 0, 'duration' => '0h 00m', 'color' => '#3B82F6', 'bg_class' => 'bg-blue-500', 'code' => '📝'],
                ['id' => 3, 'key' => 'practice', 'name' => 'Practice & Exercises', 'name_kh' => 'អនុវត្តលំហាត់', 'seconds' => 0, 'percentage' => 0, 'duration' => '0h 00m', 'color' => '#10B981', 'bg_class' => 'bg-emerald-500', 'code' => '💻'],
            ],
            'calendar_heatmap' => [
                'days'  => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                'weeks' => $weeks,
            ],
            'recent_activities'  => [],
            'this_week_overview' => [
                'date_range' => $thisWeekStart->format('M j') . ' - ' . $thisWeekEnd->format('M j, Y'),
                'metrics'    => [
                    ['label' => 'Total Time', 'label_kh' => 'ម៉ោងសិក្សាសរុប', 'value' => '0h 00m', 'trend' => '0% vs last week', 'is_positive' => true],
                    ['label' => 'Sessions', 'label_kh' => 'ចំនួនដងសិក្សា', 'value' => '0', 'trend' => '0 vs last week', 'is_positive' => true],
                    ['label' => 'Avg. Session', 'label_kh' => 'មធ្យមក្នុងមួយដង', 'value' => '0h 00m', 'trend' => '0% vs last week', 'is_positive' => true],
                    ['label' => 'Active Days', 'label_kh' => 'ថ្ងៃសកម្ម', 'value' => '0', 'trend' => '0 vs last week', 'is_positive' => true],
                    ['label' => 'Completed Lessons', 'label_kh' => 'មេរៀនបានបញ្ចប់', 'value' => '0', 'trend' => '0 vs last week', 'is_positive' => true],
                    ['label' => 'Quizzes Taken', 'label_kh' => 'តេស្តបានធ្វើ', 'value' => '0', 'trend' => '0 vs last week', 'is_positive' => true],
                ],
            ],
            'learning_streak' => [
                'current_streak'   => 0,
                'best_streak'      => 0,
                'best_streak_date' => '—',
                'days'             => $days7,
            ],
            'recent_achievements' => [],
        ];
    }
}
