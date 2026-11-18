<?php

namespace App\Services;

use App\Models\QuizAttempt;
use App\Models\User;
use Carbon\Carbon;

class QuizAttemptHistoryService
{
    /**
     * Get quiz attempt history dataset from 100% real QuizAttempt records.
     */
    public function getAttemptsHistoryData(
        ?User $user = null,
        string $status = 'all',
        string $dateRange = 'all',
        string $search = '',
        int $page = 1,
        int $perPage = 10
    ): array {
        if (!$user) {
            return $this->emptyPayload($page, $perPage);
        }

        $query = QuizAttempt::where('user_id', $user->id)
            ->with(['quiz.course', 'quiz.questions'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id');

        if ($dateRange === '7d') {
            $query->where('created_at', '>=', now()->subDays(7));
        } elseif ($dateRange === '30d') {
            $query->where('created_at', '>=', now()->subDays(30));
        } elseif ($dateRange === 'this_month') {
            $query->where('created_at', '>=', now()->startOfMonth());
        }

        $realAttempts = $query->get();

        if ($realAttempts->isEmpty()) {
            return $this->emptyPayload($page, $perPage);
        }

        $gradingService = app(GradingService::class);
        $totalAttempts = $realAttempts->count();
        $avgScore = (int) round((float) $realAttempts->avg('score'));

        $highestAttempt = $realAttempts->sortByDesc('score')->first();
        $lowestAttempt = $realAttempts->sortBy('score')->first();
        $passedCount = $realAttempts->where('passed', true)->count();
        $passRate = $totalAttempts > 0 ? (int) round(($passedCount / $totalAttempts) * 100) : 0;

        // Trend vs previous 30 days
        $last30 = $realAttempts->filter(fn ($a) => ($a->submitted_at ?? $a->created_at)?->gte(now()->subDays(30)));
        $prev30 = $realAttempts->filter(function ($a) {
            $dt = $a->submitted_at ?? $a->created_at;
            return $dt && $dt->lt(now()->subDays(30)) && $dt->gte(now()->subDays(60));
        });

        $trendText = "{$totalAttempts} attempt" . ($totalAttempts === 1 ? '' : 's') . ' recorded';
        if ($last30->isNotEmpty() && $prev30->isNotEmpty()) {
            $diff = (int) round((float) $last30->avg('score') - (float) $prev30->avg('score'));
            $trendText = ($diff >= 0 ? "+{$diff}%" : "{$diff}%") . ' vs last 30 days';
        }

        $summary = [
            'total_attempts' => $totalAttempts,
            'total_note'     => 'All time attempts',
            'average_score'  => $avgScore,
            'average_trend'  => $trendText,
            'highest_score'  => (int) round((float) ($highestAttempt?->score ?? 0)),
            'highest_title'  => $highestAttempt?->quiz?->title ?? '-',
            'lowest_score'   => (int) round((float) ($lowestAttempt?->score ?? 0)),
            'lowest_title'   => $lowestAttempt?->quiz?->title ?? '-',
            'passed_count'   => $passedCount,
            'passed_note'    => "{$passRate}% of all attempts",
        ];

        $b90 = $realAttempts->filter(fn ($a) => (float) $a->score >= 90)->count();
        $b70 = $realAttempts->filter(fn ($a) => (float) $a->score >= 70 && (float) $a->score < 90)->count();
        $b50 = $realAttempts->filter(fn ($a) => (float) $a->score >= 50 && (float) $a->score < 70)->count();
        $bLow = $realAttempts->filter(fn ($a) => (float) $a->score < 50)->count();

        $performanceOverview = [
            'average_score' => $avgScore,
            'distribution'  => [
                ['label' => '90 - 100%', 'count' => $b90,  'percentage' => (int) round(($b90 / $totalAttempts) * 100),  'color' => '#10B981', 'class' => 'text-emerald-400'],
                ['label' => '70 - 89%',  'count' => $b70,  'percentage' => (int) round(($b70 / $totalAttempts) * 100),  'color' => '#3B82F6', 'class' => 'text-blue-400'],
                ['label' => '50 - 69%',  'count' => $b50,  'percentage' => (int) round(($b50 / $totalAttempts) * 100),  'color' => '#F59E0B', 'class' => 'text-amber-400'],
                ['label' => 'Below 50%', 'count' => $bLow, 'percentage' => (int) round(($bLow / $totalAttempts) * 100), 'color' => '#EF4444', 'class' => 'text-rose-400'],
            ],
        ];

        // Chronological score trend (latest up to 8 points)
        $chronological = $realAttempts->reverse()->values()->take(-8);
        $scoreTrend = [
            'points' => $chronological->map(function (QuizAttempt $a) {
                $dt = $a->submitted_at ?? $a->created_at;
                return [
                    'date'       => $dt ? Carbon::parse($dt)->format('M j') : 'Attempt',
                    'percentage' => (int) round((float) $a->score),
                ];
            })->values()->all(),
        ];

        // Recent Activity (top 5)
        $recentActivity = $realAttempts->take(5)->map(function (QuizAttempt $a) {
            $title = $a->quiz?->title ?? 'Quiz';
            $courseTitle = $a->quiz?->course?->title ?? 'Course';
            $dt = $a->submitted_at ?? $a->created_at;
            $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $a->quiz?->course?->code ?: $courseTitle), 0, 2) ?: 'QZ');
            return [
                'id'       => $a->id,
                'quiz_id'  => $a->quiz_id,
                'title'    => $title,
                'score'    => (int) round((float) $a->score),
                'date_str' => $dt ? Carbon::parse($dt)->format('h:i A M j') : 'Recently',
                'code'     => $code,
                'icon_bg'  => 'from-purple-500 to-indigo-600 text-white',
            ];
        })->values()->all();

        // Weak topics (quiz titles where score < passing_score or < 70)
        $weakTopics = $realAttempts
            ->filter(fn (QuizAttempt $a) => !$a->passed || (float) $a->score < 70)
            ->map(fn (QuizAttempt $a) => $a->quiz?->title)
            ->filter()
            ->unique()
            ->take(5)
            ->values()
            ->all();

        // Build full table rows
        $allRows = $realAttempts->map(function (QuizAttempt $a) use ($gradingService) {
            $quiz = $a->quiz;
            $courseTitle = $quiz?->course?->title ?? 'Course';
            $questions = $quiz?->questions ?? collect();
            $totalQ = $questions->count();
            $answers = is_array($a->answers) ? $a->answers : [];

            $correctCount = 0;
            $questionBreakdown = [];

            if ($quiz && $totalQ > 0) {
                [, , $breakdown] = $gradingService->grade($quiz, $answers);
                foreach ($questions as $q) {
                    $qRes = $breakdown[$q->id] ?? ['correct' => false, 'points' => $q->points ?? 1];
                    $isCorrect = $qRes['correct'] === true;
                    if ($isCorrect) {
                        $correctCount++;
                    }
                    $rawUserAns = $answers[$q->id] ?? $answers[(string) $q->id] ?? null;
                    $userAnsStr = is_array($rawUserAns) ? implode(', ', $rawUserAns) : (string) ($rawUserAns ?? 'Not answered');
                    $rawCorr = $q->correct_answer;
                    $corrStr = is_array($rawCorr) ? implode(', ', $rawCorr) : (string) ($rawCorr ?? '');

                    $questionBreakdown[] = [
                        'q'           => $q->question,
                        'user_ans'    => $userAnsStr !== '' ? $userAnsStr : 'Not answered',
                        'correct_ans' => $corrStr,
                        'is_correct'  => $isCorrect,
                        'points'      => $isCorrect ? (float) ($q->points ?? 1) : 0,
                    ];
                }
            }

            $pct = (int) round((float) $a->score);
            $maxAtt = ($quiz && $quiz->max_attempts !== null && (int) $quiz->max_attempts > 0)
                ? (string) $quiz->max_attempts
                : '∞';
            $attNum = (int) ($a->attempt_number ?: 1);

            $timeTaken = '-';
            if ($a->started_at && $a->submitted_at) {
                $secs = max(1, (int) abs($a->submitted_at->diffInSeconds($a->started_at)));
                $m = intdiv($secs, 60);
                $s = $secs % 60;
                $timeTaken = "{$m}m {$s}s";
            }

            $dt = $a->submitted_at ?? $a->created_at;
            $isInProgress = $a->submitted_at === null;
            $statusType = $isInProgress ? 'in_progress' : ($a->passed ? 'passed' : 'failed');
            $statusLabel = $isInProgress ? 'In Progress' : ($a->passed ? 'Passed' : 'Failed');

            $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $quiz?->course?->code ?: $courseTitle), 0, 2) ?: 'QZ');

            return [
                'id'          => $a->id,
                'quiz_id'     => $a->quiz_id,
                'title'       => $quiz?->title ?? 'Quiz',
                'subtitle'    => $courseTitle,
                'course'      => $courseTitle,
                'attempt'     => "{$attNum} / {$maxAtt}",
                'score'       => $totalQ > 0 ? "{$correctCount} / {$totalQ}" : "{$pct}%",
                'percentage'  => $pct,
                'time_taken'  => $timeTaken,
                'date'        => $dt ? Carbon::parse($dt)->format('M j, Y') : '-',
                'time'        => $dt ? Carbon::parse($dt)->format('h:i A') : '',
                'status'      => $statusLabel,
                'status_type' => $statusType,
                'code'        => $code,
                'icon_bg'     => 'from-purple-500 to-indigo-600 text-white',
                'questions'   => $questionBreakdown,
            ];
        })->values()->all();

        // Filter by status tab
        if ($status !== 'all') {
            if ($status === 'completed') {
                $allRows = array_values(array_filter($allRows, fn ($r) => $r['status_type'] !== 'in_progress'));
            } else {
                $allRows = array_values(array_filter($allRows, fn ($r) => strcasecmp($r['status_type'], $status) === 0));
            }
        }

        // Filter by search
        if ($search !== '') {
            $s = mb_strtolower(trim($search), 'UTF-8');
            $allRows = array_values(array_filter($allRows, function ($row) use ($s) {
                return str_contains(mb_strtolower($row['title'], 'UTF-8'), $s)
                    || str_contains(mb_strtolower($row['course'], 'UTF-8'), $s)
                    || str_contains(mb_strtolower($row['status'], 'UTF-8'), $s);
            }));
        }

        $filteredCount = count($allRows);
        $safePage = max(1, $page);
        $offset = ($safePage - 1) * $perPage;
        $pagedRows = array_slice($allRows, $offset, $perPage);

        return [
            'summary'              => $summary,
            'performance_overview' => $performanceOverview,
            'score_trend'          => $scoreTrend,
            'recent_activity'      => $recentActivity,
            'weak_topics'          => $weakTopics,
            'attempts'             => $pagedRows,
            'total_count'          => $filteredCount,
            'current_page'         => $safePage,
            'per_page'             => $perPage,
        ];
    }

    protected function emptyPayload(int $page = 1, int $perPage = 10): array
    {
        return [
            'summary'              => [
                'total_attempts' => 0,
                'total_note'     => 'No attempts yet',
                'average_score'  => 0,
                'average_trend'  => '0 attempts',
                'highest_score'  => 0,
                'highest_title'  => '-',
                'lowest_score'   => 0,
                'lowest_title'   => '-',
                'passed_count'   => 0,
                'passed_note'    => '0% pass rate',
            ],
            'performance_overview' => [
                'average_score' => 0,
                'distribution'  => [
                    ['label' => '90 - 100%', 'count' => 0, 'percentage' => 0, 'color' => '#10B981', 'class' => 'text-emerald-400'],
                    ['label' => '70 - 89%',  'count' => 0, 'percentage' => 0, 'color' => '#3B82F6', 'class' => 'text-blue-400'],
                    ['label' => '50 - 69%',  'count' => 0, 'percentage' => 0, 'color' => '#F59E0B', 'class' => 'text-amber-400'],
                    ['label' => 'Below 50%', 'count' => 0, 'percentage' => 0, 'color' => '#EF4444', 'class' => 'text-rose-400'],
                ],
            ],
            'score_trend'          => [
                'points' => [],
            ],
            'recent_activity'      => [],
            'weak_topics'          => [],
            'attempts'             => [],
            'total_count'          => 0,
            'current_page'         => $page,
            'per_page'             => $perPage,
        ];
    }
}
