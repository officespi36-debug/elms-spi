<?php

namespace App\Services;

use App\Models\QuizAttempt;
use App\Models\User;
use Carbon\Carbon;

class QuizResultService
{
    /**
     * Get aggregated Quiz Results analytics and history table from 100% real QuizAttempt records.
     */
    public function getQuizResultsData(
        ?User $user = null,
        string $status = 'all',
        string $course = 'all',
        string $category = 'all',
        string $difficulty = 'all',
        string $dateRange = 'all',
        string $search = '',
        int $page = 1,
        int $perPage = 8
    ): array {
        if (!$user) {
            return $this->emptyPayload($page, $perPage);
        }

        $query = QuizAttempt::where('user_id', $user->id)
            ->with(['quiz.course.major', 'quiz.questions'])
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

        $totalTaken = $realAttempts->count();
        $avgScore = (int) round((float) $realAttempts->avg('score'));
        $highestAttempt = $realAttempts->sortByDesc('score')->first();
        $lowestAttempt = $realAttempts->sortBy('score')->first();
        $passedCount = $realAttempts->where('passed', true)->count();
        $failedCount = $realAttempts->where('passed', false)->whereNotNull('submitted_at')->count();
        $inProgressCount = $realAttempts->whereNull('submitted_at')->count();
        $passRate = $totalTaken > 0 ? (int) round(($passedCount / $totalTaken) * 100) : 0;

        // Month comparison
        $now = Carbon::now('Asia/Phnom_Penh');
        $thisMonthAttempts = $realAttempts->filter(fn ($a) => ($a->submitted_at ?? $a->created_at)?->gte($now->copy()->startOfMonth()));
        $lastMonthAttempts = $realAttempts->filter(function ($a) use ($now) {
            $dt = $a->submitted_at ?? $a->created_at;
            return $dt && $dt->between($now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth());
        });

        $thisMonthAvg = $thisMonthAttempts->isNotEmpty() ? (int) round((float) $thisMonthAttempts->avg('score')) : $avgScore;
        $lastMonthAvg = $lastMonthAttempts->isNotEmpty() ? (int) round((float) $lastMonthAttempts->avg('score')) : 0;
        $monthDiff = $lastMonthAttempts->isNotEmpty() ? ($thisMonthAvg - $lastMonthAvg) : 0;
        $takenDiff = $thisMonthAttempts->count() - $lastMonthAttempts->count();

        $summary = [
            'average_score' => $avgScore,
            'average_note'  => $avgScore >= 70 ? 'Good job! Keep it up.' : 'Keep practicing!',
            'highest_score' => (int) round((float) ($highestAttempt?->score ?? 0)),
            'highest_title' => $highestAttempt?->quiz?->title ?? '-',
            'lowest_score'  => (int) round((float) ($lowestAttempt?->score ?? 0)),
            'lowest_title'  => $lowestAttempt?->quiz?->title ?? '-',
            'quizzes_taken' => $totalTaken,
            'taken_trend'   => ($takenDiff >= 0 ? "+{$takenDiff}" : (string) $takenDiff) . ' vs last month',
            'passed_count'  => $passedCount,
            'passed_note'   => "{$passRate}% pass rate",
        ];

        $b90 = $realAttempts->filter(fn ($a) => (float) $a->score >= 90)->count();
        $b70 = $realAttempts->filter(fn ($a) => (float) $a->score >= 70 && (float) $a->score < 90)->count();
        $b50 = $realAttempts->filter(fn ($a) => (float) $a->score >= 50 && (float) $a->score < 70)->count();
        $bLow = $realAttempts->filter(fn ($a) => (float) $a->score < 50)->count();

        $performanceOverview = [
            'average_score' => $avgScore,
            'distribution'  => [
                ['label' => '90 - 100%', 'count' => $b90,  'percentage' => (int) round(($b90 / $totalTaken) * 100),  'color' => '#10B981', 'class' => 'text-emerald-400'],
                ['label' => '70 - 89%',  'count' => $b70,  'percentage' => (int) round(($b70 / $totalTaken) * 100),  'color' => '#3B82F6', 'class' => 'text-blue-400'],
                ['label' => '50 - 69%',  'count' => $b50,  'percentage' => (int) round(($b50 / $totalTaken) * 100),  'color' => '#F59E0B', 'class' => 'text-amber-400'],
                ['label' => 'Below 50%', 'count' => $bLow, 'percentage' => (int) round(($bLow / $totalTaken) * 100), 'color' => '#EF4444', 'class' => 'text-rose-400'],
            ],
        ];

        $chronological = $realAttempts->reverse()->values()->take(-6);
        $trendPoints = $chronological->map(function (QuizAttempt $a) {
            $dt = $a->submitted_at ?? $a->created_at;
            return [
                'date'       => $dt ? Carbon::parse($dt)->format('M j') : 'Quiz',
                'percentage' => (int) round((float) $a->score),
            ];
        })->values()->all();

        $latestForHighlight = $chronological->last();
        $scoreTrend = [
            'points'    => $trendPoints,
            'highlight' => $latestForHighlight ? [
                'date'       => ($latestForHighlight->submitted_at ?? $latestForHighlight->created_at)?->format('M j, Y') ?? 'Latest',
                'score_text' => 'Score: ' . (int) round((float) $latestForHighlight->score) . '%',
                'x_percent'  => 65,
                'y_percent'  => 38,
            ] : null,
        ];

        // Score by course
        $byCourse = $realAttempts->groupBy(fn (QuizAttempt $a) => $a->quiz?->course?->title ?? 'Course');
        $scoreByCourse = [];
        foreach ($byCourse as $courseTitle => $group) {
            $cAvg = (int) round((float) $group->avg('score'));
            $scoreByCourse[] = [
                'course'     => $courseTitle,
                'percentage' => $cAvg,
                'color'      => $cAvg >= 70 ? 'from-cyan-400 to-blue-500' : 'from-amber-400 to-orange-500',
            ];
        }

        $resultDistribution = [
            'total_quizzes' => $totalTaken,
            'items'         => [
                ['label' => 'Passed',      'count' => $passedCount,     'percentage' => (int) round(($passedCount / $totalTaken) * 100),     'color' => '#10B981'],
                ['label' => 'Failed',      'count' => $failedCount,     'percentage' => (int) round(($failedCount / $totalTaken) * 100),     'color' => '#EF4444'],
                ['label' => 'In Progress', 'count' => $inProgressCount, 'percentage' => (int) round(($inProgressCount / $totalTaken) * 100), 'color' => '#F59E0B'],
            ],
        ];

        $scoreComparison = [
            'this_month'  => $thisMonthAvg,
            'last_month'  => $lastMonthAvg,
            'trend_text'  => ($monthDiff >= 0 ? "+{$monthDiff}%" : "{$monthDiff}%") . ' vs last month',
            'is_improved' => $monthDiff >= 0,
        ];

        // Dynamic real achievements earned from quiz attempts
        $recentAchievements = [];
        $perfectAttempt = $realAttempts->first(fn (QuizAttempt $a) => (float) $a->score >= 100);
        if ($perfectAttempt) {
            $recentAchievements[] = [
                'id'       => 1,
                'title'    => 'Perfect Score',
                'subtitle' => 'Scored 100% in ' . ($perfectAttempt->quiz?->title ?? 'a quiz'),
                'date'     => ($perfectAttempt->submitted_at ?? $perfectAttempt->created_at)?->format('M j, Y') ?? '',
                'icon'     => '🏆',
                'icon_bg'  => 'bg-emerald-500/20 border border-emerald-500/30 text-emerald-300',
            ];
        }
        if ($passedCount >= 1) {
            $firstPass = $realAttempts->where('passed', true)->last();
            $recentAchievements[] = [
                'id'       => 2,
                'title'    => $passedCount >= 10 ? 'Quiz Master' : 'Quiz Achiever',
                'subtitle' => "Passed {$passedCount} quiz" . ($passedCount === 1 ? '' : 'zes'),
                'date'     => ($firstPass?->submitted_at ?? $firstPass?->created_at)?->format('M j, Y') ?? '',
                'icon'     => '🛡️',
                'icon_bg'  => 'bg-purple-500/20 border border-purple-500/30 text-purple-300',
            ];
        }

        $weakTopics = $realAttempts
            ->filter(fn (QuizAttempt $a) => !$a->passed || (float) $a->score < 70)
            ->map(fn (QuizAttempt $a) => $a->quiz?->title)
            ->filter()
            ->unique()
            ->take(5)
            ->values()
            ->all();

        $coursesList = $realAttempts->map(fn ($a) => $a->quiz?->course?->title)->filter()->unique()->values()->all();
        $categoriesList = $realAttempts->map(fn ($a) => $a->quiz?->course?->major?->name)->filter()->unique()->values()->all();

        $allRows = $realAttempts->map(function (QuizAttempt $a) use ($gradingService) {
            $quiz = $a->quiz;
            $courseTitle = $quiz?->course?->title ?? 'Course';
            $categoryName = $quiz?->course?->major?->name ?? 'General';
            $difficultyLabel = match (strtolower((string) ($quiz?->course?->level ?? ''))) {
                'beginner', 'easy' => 'Easy',
                'advanced', 'hard' => 'Hard',
                default            => 'Medium',
            };

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
            $timeTaken = '-';
            if ($a->started_at && $a->submitted_at) {
                $secs = max(1, (int) abs($a->submitted_at->diffInSeconds($a->started_at)));
                $m = intdiv($secs, 60);
                $s = $secs % 60;
                $timeTaken = "{$m}m {$s}s";
            }

            $dt = $a->submitted_at ?? $a->created_at;
            $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $quiz?->course?->code ?: $courseTitle), 0, 2) ?: 'QZ');

            return [
                'id'            => $a->id,
                'quiz_id'       => $a->quiz_id,
                'title'         => $quiz?->title ?? 'Quiz',
                'subtitle'      => $courseTitle,
                'course'        => $courseTitle,
                'category'      => $categoryName,
                'difficulty'    => $difficultyLabel,
                'score'         => $totalQ > 0 ? "{$correctCount} / {$totalQ}" : "{$pct}%",
                'percentage'    => $pct,
                'correct_total' => $totalQ > 0 ? "{$correctCount} / {$totalQ}" : '-',
                'time_taken'    => $timeTaken,
                'completed_on'  => $dt ? Carbon::parse($dt)->format('M j, Y h:i A') : '-',
                'result'        => $a->passed ? 'Passed' : 'Failed',
                'result_type'   => $a->passed ? 'passed' : 'failed',
                'code'          => $code,
                'icon_bg'       => 'from-purple-500 to-indigo-600 text-white',
                'questions'     => $questionBreakdown,
            ];
        })->values()->all();

        if ($status !== 'all') {
            if ($status === 'needs_improvement') {
                $allRows = array_values(array_filter($allRows, fn ($r) => $r['percentage'] < 70));
            } else {
                $allRows = array_values(array_filter($allRows, fn ($r) => strcasecmp($r['result_type'], $status) === 0));
            }
        }

        if ($course !== 'all') {
            $allRows = array_values(array_filter($allRows, fn ($r) => strcasecmp($r['course'], $course) === 0));
        }

        if ($category !== 'all') {
            $allRows = array_values(array_filter($allRows, fn ($r) => strcasecmp($r['category'] ?? '', $category) === 0));
        }

        if ($difficulty !== 'all') {
            $allRows = array_values(array_filter($allRows, fn ($r) => strcasecmp($r['difficulty'] ?? '', $difficulty) === 0));
        }

        if ($search !== '') {
            $s = mb_strtolower(trim($search), 'UTF-8');
            $allRows = array_values(array_filter($allRows, fn ($r) => str_contains(mb_strtolower($r['title'], 'UTF-8'), $s) || str_contains(mb_strtolower($r['course'], 'UTF-8'), $s)));
        }

        $filteredTotal = count($allRows);
        $safePage = max(1, $page);
        $offset = ($safePage - 1) * $perPage;
        $pagedRows = array_slice($allRows, $offset, $perPage);

        return [
            'summary'              => $summary,
            'performance_overview' => $performanceOverview,
            'score_trend'          => $scoreTrend,
            'score_by_course'      => $scoreByCourse,
            'result_distribution'  => $resultDistribution,
            'score_comparison'     => $scoreComparison,
            'recent_achievements'  => $recentAchievements,
            'weak_topics'          => $weakTopics,
            'results'              => $pagedRows,
            'total_count'          => $filteredTotal,
            'current_page'         => $safePage,
            'per_page'             => $perPage,
            'courses'              => $coursesList,
            'categories'           => $categoriesList,
        ];
    }

    protected function emptyPayload(int $page = 1, int $perPage = 8): array
    {
        return [
            'summary'              => [
                'average_score' => 0,
                'average_note'  => 'No quiz results yet',
                'highest_score' => 0,
                'highest_title' => '-',
                'lowest_score'  => 0,
                'lowest_title'  => '-',
                'quizzes_taken' => 0,
                'taken_trend'   => '0 vs last month',
                'passed_count'  => 0,
                'passed_note'   => '0% pass rate',
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
                'points'    => [],
                'highlight' => null,
            ],
            'score_by_course'      => [],
            'result_distribution'  => [
                'total_quizzes' => 0,
                'items'         => [
                    ['label' => 'Passed',      'count' => 0, 'percentage' => 0, 'color' => '#10B981'],
                    ['label' => 'Failed',      'count' => 0, 'percentage' => 0, 'color' => '#EF4444'],
                    ['label' => 'In Progress', 'count' => 0, 'percentage' => 0, 'color' => '#F59E0B'],
                ],
            ],
            'score_comparison'     => [
                'this_month'  => 0,
                'last_month'  => 0,
                'trend_text'  => '0% vs last month',
                'is_improved' => true,
            ],
            'recent_achievements'  => [],
            'weak_topics'          => [],
            'results'              => [],
            'total_count'          => 0,
            'current_page'         => $page,
            'per_page'             => $perPage,
            'courses'              => [],
            'categories'           => [],
        ];
    }
}
