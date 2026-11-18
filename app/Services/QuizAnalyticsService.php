<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class QuizAnalyticsService
{
    public const TIMEZONE = 'Asia/Phnom_Penh';

    /**
     * Get complete quiz performance analytics for a student strictly from real DB records.
     */
    public function getStudentAnalytics(?User $user = null, string $period = 'this_month', string $granularity = 'daily'): array
    {
        $now = Carbon::now(self::TIMEZONE);
        $today = $now->copy()->startOfDay();
        $normalizedPeriod = in_array($period, ['this_week', 'this_month', 'last_3_months', 'last_6_months', 'all_time'], true)
            ? $period
            : 'this_month';
        $normalizedGranularity = in_array($granularity, ['daily', 'weekly', 'monthly'], true)
            ? $granularity
            : 'daily';

        if (!$user) {
            return $this->emptyAnalytics($today, collect());
        }

        $allAttempts = collect();
        $enrollments = collect();

        try {
            $allAttempts = QuizAttempt::with([
                'quiz.course.subject',
                'quiz.course.major',
                'quiz.questions',
            ])
                ->where('user_id', $user->id)
                ->whereNotNull('score')
                ->orderByDesc('submitted_at')
                ->orderByDesc('id')
                ->get();
        } catch (\Throwable $e) {
            $allAttempts = collect();
        }

        try {
            $enrollments = Enrollment::where('student_id', $user->id)
                ->whereIn('status', ['active', 'completed'])
                ->with(['course.subject', 'course.quizzes'])
                ->get();
        } catch (\Throwable $e) {
            $enrollments = collect();
        }

        // Weekly summary always compares current week vs last week across allAttempts
        $weeklySummary = $this->buildWeeklySummary($allAttempts, $today);

        // Filter attempts for the selected period
        $periodStart = $this->resolvePeriodStart($normalizedPeriod, $today);
        $periodAttempts = $periodStart
            ? $allAttempts->filter(function (QuizAttempt $att) use ($periodStart) {
                $dt = $att->submitted_at ?? $att->created_at;
                return $dt && Carbon::parse($dt)->timezone(self::TIMEZONE)->gte($periodStart);
            })->values()
            : $allAttempts->values();

        if ($periodAttempts->isEmpty()) {
            $empty = $this->emptyAnalytics($today, $enrollments);
            $empty['weekly_summary'] = $weeklySummary;
            return $empty;
        }

        return $this->aggregateRealAnalytics(
            $periodAttempts,
            $allAttempts,
            $enrollments,
            $weeklySummary,
            $normalizedGranularity,
            $today
        );
    }

    /**
     * Aggregate analytics strictly from real database records (1, 2, or N attempts).
     */
    protected function aggregateRealAnalytics(
        Collection $attempts,
        Collection $allAttempts,
        Collection $enrollments,
        array $weeklySummary,
        string $granularity,
        Carbon $today
    ): array {
        $totalCompleted = $attempts->count();
        $scores = $attempts->map(fn ($a) => (float) $a->score);
        $avgScore = (int) round($scores->average() ?? 0);

        $highestAttempt = $attempts->sortByDesc(fn ($a) => (float) $a->score)->first();
        $lowestAttempt = $attempts->sortBy(fn ($a) => (float) $a->score)->first();

        $highRef = $highestAttempt?->submitted_at ?? $highestAttempt?->created_at;
        $lowRef = $lowestAttempt?->submitted_at ?? $lowestAttempt?->created_at;

        $highest = [
            'score'  => (int) round((float) ($highestAttempt?->score ?? 0)),
            'quiz'   => $highestAttempt?->quiz?->title ?? 'Quiz',
            'course' => $highestAttempt?->quiz?->course?->title ?? 'Course',
            'date'   => $highRef ? Carbon::parse($highRef)->timezone(self::TIMEZONE)->format('M j, Y') : '—',
        ];

        $lowest = [
            'score'  => (int) round((float) ($lowestAttempt?->score ?? 0)),
            'quiz'   => $lowestAttempt?->quiz?->title ?? 'Quiz',
            'course' => $lowestAttempt?->quiz?->course?->title ?? 'Course',
            'date'   => $lowRef ? Carbon::parse($lowRef)->timezone(self::TIMEZONE)->format('M j, Y') : '—',
        ];

        $totalGradableQuestions = 0;
        $totalCorrectQuestions = 0;

        $difficultyStats = [
            'easy'   => ['total' => 0, 'correct' => 0],
            'medium' => ['total' => 0, 'correct' => 0],
            'hard'   => ['total' => 0, 'correct' => 0],
            'expert' => ['total' => 0, 'correct' => 0],
        ];

        $courseStats = [];
        $quizWeakCandidates = [];
        $attemptComputedMap = [];

        foreach ($attempts as $attempt) {
            $quiz = $attempt->quiz;
            $scoreInt = (int) round((float) ($attempt->score ?? 0));

            $durationSec = 0;
            if ($attempt->started_at && $attempt->submitted_at) {
                $durationSec = max(60, min(7200, (int) abs(Carbon::parse($attempt->submitted_at)->diffInSeconds(Carbon::parse($attempt->started_at)))));
            } else {
                $durationSec = max(120, ((int) ($quiz?->time_limit_minutes ?? 10)) * 30);
            }

            $answers = is_array($attempt->answers)
                ? $attempt->answers
                : (json_decode((string) ($attempt->answers ?? '[]'), true) ?: []);

            $questions = $quiz?->questions ?? collect();
            $attGradable = 0;
            $attCorrect = 0;
            $questionLog = [];

            foreach ($questions->values() as $qIdx => $q) {
                /** @var Question $q */
                $eval = $this->evaluateQuestionAnswer($q, $answers[$q->id] ?? ($answers[(string) $q->id] ?? null));
                if ($eval !== null) {
                    $attGradable++;
                    $totalGradableQuestions++;
                    if ($eval) {
                        $attCorrect++;
                        $totalCorrectQuestions++;
                    }

                    $diff = $this->resolveQuestionDifficulty($q, $quiz?->type, $quiz?->course?->level);
                    $difficultyStats[$diff]['total']++;
                    if ($eval) {
                        $difficultyStats[$diff]['correct']++;
                    }
                }

                $questionLog[] = [
                    'index'    => $qIdx + 1,
                    'question' => Str::limit(strip_tags((string) $q->question), 90),
                    'correct'  => $eval === true,
                    'pending'  => $eval === null,
                    'points'   => (float) ($q->points ?? 1),
                ];
            }

            $attAccuracy = $attGradable > 0
                ? (int) round(($attCorrect / $attGradable) * 100)
                : $scoreInt;

            $attemptComputedMap[$attempt->id] = [
                'accuracy'     => $attAccuracy,
                'duration_sec' => $durationSec,
                'question_log' => $questionLog,
            ];

            // Topic / Course grouping strictly from real Course / Subject / Major
            $topicName = $quiz?->course?->subject?->name
                ?: ($quiz?->course?->title ?: ($quiz?->title ?: 'General'));
            $topicCode = $this->buildShortCode($quiz?->course?->code ?: $topicName);

            if (!isset($courseStats[$topicName])) {
                $courseStats[$topicName] = [
                    'name'      => $topicName,
                    'code'      => $topicCode,
                    'scores'    => [],
                    'course_id' => $quiz?->course_id,
                ];
            }
            $courseStats[$topicName]['scores'][] = (float) $attempt->score;

            // Track per-quiz scores for weak topic detection (< 70%)
            if ($quiz) {
                $qKey = 'quiz_' . $quiz->id;
                if (!isset($quizWeakCandidates[$qKey])) {
                    $quizWeakCandidates[$qKey] = [
                        'quiz_id'   => $quiz->id,
                        'course_id' => $quiz->course_id,
                        'title'     => $quiz->title,
                        'course'    => $quiz->course?->title ?? 'Course',
                        'code'      => $topicCode,
                        'scores'    => [],
                    ];
                }
                $quizWeakCandidates[$qKey]['scores'][] = (float) $attempt->score;
            }
        }

        $overallAccuracy = $totalGradableQuestions > 0
            ? (int) round(($totalCorrectQuestions / $totalGradableQuestions) * 100)
            : $avgScore;

        //Difficulty Breakdown (0 when 0 questions in that bucket)
        $difficultyBreakdown = [
            'average' => $overallAccuracy,
            'easy'    => $difficultyStats['easy']['total'] > 0
                ? (int) round(($difficultyStats['easy']['correct'] / $difficultyStats['easy']['total']) * 100)
                : 0,
            'medium'  => $difficultyStats['medium']['total'] > 0
                ? (int) round(($difficultyStats['medium']['correct'] / $difficultyStats['medium']['total']) * 100)
                : 0,
            'hard'    => $difficultyStats['hard']['total'] > 0
                ? (int) round(($difficultyStats['hard']['correct'] / $difficultyStats['hard']['total']) * 100)
                : 0,
            'expert'  => $difficultyStats['expert']['total'] > 0
                ? (int) round(($difficultyStats['expert']['correct'] / $difficultyStats['expert']['total']) * 100)
                : 0,
            'counts'  => [
                'easy'   => $difficultyStats['easy']['total'],
                'medium' => $difficultyStats['medium']['total'],
                'hard'   => $difficultyStats['hard']['total'],
                'expert' => $difficultyStats['expert']['total'],
            ],
        ];

        // Weak Topics (< 70% average score, [] if none)
        $weakTopics = [];
        foreach ($quizWeakCandidates as $cand) {
            $qAvg = (int) round(array_sum($cand['scores']) / max(1, count($cand['scores'])));
            if ($qAvg < 70) {
                $weakTopics[] = [
                    'id'             => count($weakTopics) + 1,
                    'quiz_id'        => $cand['quiz_id'],
                    'course_id'      => $cand['course_id'],
                    'title'          => $cand['title'],
                    'course'         => $cand['course'],
                    'code'           => $cand['code'],
                    'score'          => $qAvg,
                    'color'          => $qAvg < 40 ? 'rose' : ($qAvg < 55 ? 'blue' : 'purple'),
                    'recommendation' => "Review lessons in {$cand['course']} and retake {$cand['title']} to reach 70%+.",
                ];
            }
        }
        usort($weakTopics, fn ($a, $b) => $a['score'] <=> $b['score']);

        // Recent Results Table (Real accuracy, real time, real question_log)
        $recentResults = $attempts->take(10)->map(function (QuizAttempt $att) use ($attemptComputedMap) {
            $q = $att->quiz;
            $score = (int) round((float) ($att->score ?? 0));
            $comp = $attemptComputedMap[$att->id] ?? ['accuracy' => $score, 'duration_sec' => 300, 'question_log' => []];

            $res = 'Excellent';
            $badge = 'emerald';
            if ($score < 60) {
                $res = 'Needs Review';
                $badge = 'rose';
            } elseif ($score < 75) {
                $res = 'Fair';
                $badge = 'amber';
            } elseif ($score < 90) {
                $res = 'Good';
                $badge = 'blue';
            }

            $ref = $att->submitted_at ?? $att->created_at;
            $dt = $ref ? Carbon::parse($ref)->timezone(self::TIMEZONE) : Carbon::now(self::TIMEZONE);
            $mins = max(1, (int) round($comp['duration_sec'] / 60));

            return [
                'id'           => $att->id,
                'quiz_id'      => $q?->id ?? 0,
                'course_id'    => $q?->course_id ?? 0,
                'title'        => $q?->title ?? 'Quiz Assessment',
                'course'       => $q?->course?->title ?? 'Course',
                'score'        => $score,
                'accuracy'     => $comp['accuracy'],
                'time'         => "{$mins}m",
                'date'         => $dt->format('M j, Y'),
                'date_badge'   => strtoupper($dt->format('M')) . ' ' . $dt->format('d'),
                'result'       => $res,
                'badge'        => $badge,
                'badge_color'  => $score >= 80 ? 'text-emerald-400' : ($score >= 60 ? 'text-blue-400' : 'text-rose-400'),
                'icon'         => $this->buildShortCode($q?->course?->code ?: ($q?->title ?? 'QZ')),
                'question_log' => $comp['question_log'],
            ];
        })->values()->all();

        $recentQuizzes = array_map(function (array $row) {
            return [
                'id'          => $row['id'],
                'quiz_id'     => $row['quiz_id'],
                'title'       => $row['title'],
                'course'      => $row['course'],
                'score'       => $row['score'],
                'date_badge'  => $row['date_badge'],
                'badge_color' => $row['badge_color'],
            ];
        }, array_slice($recentResults, 0, 4));

        // Topics grouped by real course / subject
        $palette = ['#10B981', '#06B6D4', '#8B5CF6', '#3B82F6', '#F59E0B', '#EF4444'];
        $topicsFormatted = [];
        $idx = 0;
        foreach ($courseStats as $stat) {
            $tScore = (int) round(array_sum($stat['scores']) / max(1, count($stat['scores'])));
            $color = $tScore >= 80 ? '#10B981' : ($tScore >= 65 ? '#3B82F6' : ($tScore >= 50 ? '#F59E0B' : '#EF4444'));
            $topicsFormatted[] = [
                'name'    => $stat['name'],
                'code'    => $stat['code'],
                'score'   => $tScore,
                'color'   => $color ?: $palette[$idx % count($palette)],
                'quizzes' => count($stat['scores']),
            ];
            $idx++;
        }
        usort($topicsFormatted, fn ($a, $b) => $b['score'] <=> $a['score']);

        // Trend Chart grouped strictly from real attempts
        $trendChart = $this->buildRealTrendChart($attempts, $granularity);

        // AI Insight derived from real strongest & weakest topics
        $aiInsight = $this->buildAiInsight($topicsFormatted, $weakTopics, $enrollments);

        $scoreStatus = 'Needs Practice';
        if ($avgScore >= 85) {
            $scoreStatus = 'Excellent Performance';
        } elseif ($avgScore >= 70) {
            $scoreStatus = 'Good Performance';
        } elseif ($avgScore >= 50) {
            $scoreStatus = 'Fair Performance';
        }

        return [
            'summary' => [
                'average_score'   => $avgScore,
                'score_status'    => $scoreStatus,
                'score_trend'     => $weeklySummary['score_change'] . ' this week',
                'quizzes_taken'   => $totalCompleted,
                'quizzes_status'  => 'Total Quizzes',
                'quizzes_trend'   => $weeklySummary['quizzes_change'] . ' this week',
                'highest_score'   => $highest,
                'lowest_score'    => $lowest,
                'accuracy'        => $overallAccuracy,
                'accuracy_status' => 'Question Accuracy',
                'accuracy_trend'  => $weeklySummary['accuracy_change'] . ' this week',
            ],
            'weekly_summary' => $weeklySummary,
            'trend_chart'    => $trendChart,
            'difficulty'     => $difficultyBreakdown,
            'weak_topics'    => $weakTopics,
            'recent_results' => $recentResults,
            'recent_quizzes' => $recentQuizzes,
            'topics'         => $topicsFormatted,
            'ai_insight'     => $aiInsight,
        ];
    }

    protected function resolvePeriodStart(string $period, Carbon $today): ?Carbon
    {
        return match ($period) {
            'this_week'     => $today->copy()->startOfWeek(Carbon::MONDAY),
            'this_month'    => $today->copy()->startOfMonth(),
            'last_3_months' => $today->copy()->subDays(89),
            'last_6_months' => $today->copy()->subDays(179),
            'all_time'      => null,
            default         => $today->copy()->startOfMonth(),
        };
    }

    protected function buildWeeklySummary(Collection $allAttempts, Carbon $today): array
    {
        $thisWeekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
        $thisWeekEnd = $today->copy()->endOfWeek(Carbon::SUNDAY);
        $lastWeekStart = $thisWeekStart->copy()->subWeek();
        $lastWeekEnd = $thisWeekEnd->copy()->subWeek();

        $tw = $allAttempts->filter(function (QuizAttempt $a) use ($thisWeekStart, $thisWeekEnd) {
            $dt = $a->submitted_at ?? $a->created_at;
            if (!$dt) {
                return false;
            }
            $c = Carbon::parse($dt)->timezone(self::TIMEZONE);
            return $c->betweenIncluded($thisWeekStart, $thisWeekEnd);
        });

        $lw = $allAttempts->filter(function (QuizAttempt $a) use ($lastWeekStart, $lastWeekEnd) {
            $dt = $a->submitted_at ?? $a->created_at;
            if (!$dt) {
                return false;
            }
            $c = Carbon::parse($dt)->timezone(self::TIMEZONE);
            return $c->betweenIncluded($lastWeekStart, $lastWeekEnd);
        });

        $twCount = $tw->count();
        $lwCount = $lw->count();

        $twAvg = $twCount > 0 ? (int) round($tw->avg(fn ($a) => (float) $a->score)) : 0;
        $lwAvg = $lwCount > 0 ? (int) round($lw->avg(fn ($a) => (float) $a->score)) : 0;

        $twAcc = $this->computeCollectionAccuracy($tw, $twAvg);
        $lwAcc = $this->computeCollectionAccuracy($lw, $lwAvg);

        $twSec = $this->sumAttemptsSeconds($tw);
        $lwSec = $this->sumAttemptsSeconds($lw);

        $scoreDiff = $twAvg - $lwAvg;
        $quizDiff = $twCount - $lwCount;
        $accDiff = $twAcc - $lwAcc;
        $secDiffMins = (int) round(($twSec - $lwSec) / 60);

        return [
            'date_range'      => $thisWeekStart->format('M j') . ' - ' . $thisWeekEnd->format('M j, Y'),
            'avg_score'       => $twAvg,
            'score_change'    => ($scoreDiff >= 0 ? '+' : '') . $scoreDiff . '%',
            'quizzes_taken'   => $twCount,
            'quizzes_change'  => ($quizDiff >= 0 ? '+' : '') . $quizDiff,
            'correct_answers' => $twAcc,
            'accuracy_change' => ($accDiff >= 0 ? '+' : '') . $accDiff . '%',
            'time_spent'      => $this->formatDuration($twSec),
            'time_change'     => ($secDiffMins >= 0 ? '+' : '') . $secDiffMins . 'm',
        ];
    }

    protected function computeCollectionAccuracy(Collection $attempts, int $fallbackAvg): int
    {
        if ($attempts->isEmpty()) {
            return 0;
        }
        $total = 0;
        $correct = 0;
        foreach ($attempts as $att) {
            $answers = is_array($att->answers)
                ? $att->answers
                : (json_decode((string) ($att->answers ?? '[]'), true) ?: []);
            foreach ($att->quiz?->questions ?? [] as $q) {
                $eval = $this->evaluateQuestionAnswer($q, $answers[$q->id] ?? ($answers[(string) $q->id] ?? null));
                if ($eval !== null) {
                    $total++;
                    if ($eval) {
                        $correct++;
                    }
                }
            }
        }
        return $total > 0 ? (int) round(($correct / $total) * 100) : $fallbackAvg;
    }

    protected function sumAttemptsSeconds(Collection $attempts): int
    {
        $sec = 0;
        foreach ($attempts as $att) {
            if ($att->started_at && $att->submitted_at) {
                $sec += max(60, min(7200, (int) abs(Carbon::parse($att->submitted_at)->diffInSeconds(Carbon::parse($att->started_at)))));
            } else {
                $sec += max(120, ((int) ($att->quiz?->time_limit_minutes ?? 10)) * 30);
            }
        }
        return $sec;
    }

    protected function buildRealTrendChart(Collection $attempts, string $granularity): array
    {
        $chronological = $attempts->sortBy(fn (QuizAttempt $a) => ($a->submitted_at ?? $a->created_at)?->getTimestamp() ?? 0)->values();

        $buckets = [];
        foreach ($chronological as $att) {
            $ref = $att->submitted_at ?? $att->created_at;
            $dt = $ref ? Carbon::parse($ref)->timezone(self::TIMEZONE) : Carbon::now(self::TIMEZONE);

            if ($granularity === 'monthly') {
                $bKey = $dt->format('Y-m');
                $label = $dt->format('M Y');
            } elseif ($granularity === 'weekly') {
                $wk = $dt->copy()->startOfWeek(Carbon::MONDAY);
                $bKey = $wk->format('Y-m-d');
                $label = $wk->format('M j');
            } else {
                $bKey = $dt->format('Y-m-d');
                $label = $dt->format('M j');
            }

            $durSec = ($att->started_at && $att->submitted_at)
                ? max(60, min(7200, (int) abs(Carbon::parse($att->submitted_at)->diffInSeconds(Carbon::parse($att->started_at)))))
                : max(120, ((int) ($att->quiz?->time_limit_minutes ?? 10)) * 30);

            if (!isset($buckets[$bKey])) {
                $buckets[$bKey] = [
                    'date'    => $label,
                    'scores'  => [],
                    'seconds' => 0,
                ];
            }
            $buckets[$bKey]['scores'][] = (float) $att->score;
            $buckets[$bKey]['seconds'] += $durSec;
        }

        $points = [];
        foreach ($buckets as $b) {
            $avg = (int) round(array_sum($b['scores']) / max(1, count($b['scores'])));
            $high = (int) round(max($b['scores']));
            $low = (int) round(min($b['scores']));
            $mins = max(1, (int) round($b['seconds'] / 60));

            $points[] = [
                'date'    => $b['date'],
                'score'   => $avg,
                'quizzes' => count($b['scores']),
                'highest' => $high,
                'lowest'  => $low,
                'time'    => "{$mins}m",
            ];
        }

        return [
            'labels' => array_column($points, 'date'),
            'scores' => array_column($points, 'score'),
            'points' => $points,
        ];
    }

    protected function buildAiInsight(array $topicsFormatted, array $weakTopics, Collection $enrollments): array
    {
        if (empty($topicsFormatted)) {
            if ($enrollments->isEmpty()) {
                return [
                    'primary'    => 'No quiz attempts recorded yet.',
                    'secondary'  => 'Enroll in a course and take your first quiz to unlock personalized AI insights.',
                    'study_plan' => [],
                ];
            }

            $plan = [];
            $days = ['Day 1', 'Day 2', 'Day 3'];
            foreach ($enrollments->take(3)->values() as $idx => $enr) {
                $cTitle = $enr->course?->title ?? 'Enrolled Course';
                $plan[] = [
                    'day'      => $days[$idx] ?? 'Next',
                    'title'    => "Complete lessons & take quiz in {$cTitle}",
                    'duration' => '30 minutes',
                    'type'     => 'Course Assessment',
                ];
            }

            return [
                'primary'    => 'Ready for your first quiz assessment!',
                'secondary'  => 'Complete a practice or module quiz in your enrolled courses to measure mastery.',
                'study_plan' => $plan,
            ];
        }

        $best = $topicsFormatted[0];
        $primary = "Strongest performance in {$best['name']} ({$best['score']}%).";

        if (!empty($weakTopics)) {
            $worst = $weakTopics[0];
            $secondary = "Focus on {$worst['title']} ({$worst['score']}%) to raise your overall average above 70%.";
        } else {
            $secondary = 'Great consistency! All attempted quiz topics are scoring 70% or above.';
        }

        $studyPlan = [];
        $dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $planIdx = 0;

        foreach (array_slice($weakTopics, 0, 3) as $wt) {
            $studyPlan[] = [
                'day'      => $dayNames[$planIdx % count($dayNames)],
                'title'    => "Review {$wt['course']}: {$wt['title']}",
                'duration' => '30 minutes',
                'type'     => 'Targeted Weak Topic Review',
            ];
            $planIdx++;
        }

        foreach ($enrollments->take(max(0, 4 - count($studyPlan))) as $enr) {
            if (!$enr->course) {
                continue;
            }
            $studyPlan[] = [
                'day'      => $dayNames[$planIdx % count($dayNames)],
                'title'    => "Practice Quiz in {$enr->course->title}",
                'duration' => '25 minutes',
                'type'     => 'Active Course Practice',
            ];
            $planIdx++;
        }

        return [
            'primary'    => $primary,
            'secondary'  => $secondary,
            'study_plan' => $studyPlan,
        ];
    }

    protected function evaluateQuestionAnswer(Question $q, mixed $userAns): ?bool
    {
        if ($q->type === 'essay') {
            return null;
        }

        $correct = $q->correct_answer;
        if ($userAns === null) {
            return false;
        }

        if ($q->type === 'fill_blank') {
            $target = is_array($correct) ? (string) ($correct[0] ?? '') : (string) $correct;
            $given = is_array($userAns) ? (string) ($userAns[0] ?? '') : (string) $userAns;
            return strtolower(trim($given)) === strtolower(trim($target));
        }

        if ($userAns === $correct) {
            return true;
        }

        if (is_array($correct) && count($correct) === 1 && !is_array($userAns)) {
            return (string) $userAns === (string) $correct[0];
        }

        if (!is_array($correct) && is_array($userAns) && count($userAns) === 1) {
            return (string) $userAns[0] === (string) $correct;
        }

        return (string) json_encode($userAns) === (string) json_encode($correct);
    }

    protected function resolveQuestionDifficulty(Question $q, ?string $quizType, ?string $courseLevel): string
    {
        $pts = (float) ($q->points ?? 1);
        $qType = strtolower((string) $quizType);
        $cLevel = strtolower((string) $courseLevel);

        if ($pts >= 4 || str_contains($cLevel, 'advanced') && $pts >= 3) {
            return 'expert';
        }
        if ($pts >= 3 || str_contains($qType, 'post')) {
            return 'hard';
        }
        if ($pts >= 2 || str_contains($cLevel, 'intermediate')) {
            return 'medium';
        }
        return 'easy';
    }

    protected function buildShortCode(string $text): string
    {
        $clean = trim(preg_replace('/[^A-Za-z0-9]/', '', $text) ?? '');
        if ($clean === '') {
            return 'QZ';
        }
        return strtoupper(substr($clean, 0, 3));
    }

    protected function formatDuration(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $h = (int) floor($seconds / 3600);
        $m = (int) floor(($seconds % 3600) / 60);
        return sprintf('%dh %02dm', $h, $m);
    }

    protected function emptyAnalytics(Carbon $today, Collection $enrollments): array
    {
        $thisWeekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
        $thisWeekEnd = $today->copy()->endOfWeek(Carbon::SUNDAY);

        return [
            'summary' => [
                'average_score'   => 0,
                'score_status'    => 'No Quizzes Yet',
                'score_trend'     => '0% this week',
                'quizzes_taken'   => 0,
                'quizzes_status'  => 'No Attempts Yet',
                'quizzes_trend'   => '0 this week',
                'highest_score'   => [
                    'score'  => 0,
                    'quiz'   => '—',
                    'course' => '—',
                    'date'   => '—',
                ],
                'lowest_score'    => [
                    'score'  => 0,
                    'quiz'   => '—',
                    'course' => '—',
                    'date'   => '—',
                ],
                'accuracy'        => 0,
                'accuracy_status' => 'No Data Yet',
                'accuracy_trend'  => '0% this week',
            ],
            'weekly_summary' => [
                'date_range'      => $thisWeekStart->format('M j') . ' - ' . $thisWeekEnd->format('M j, Y'),
                'avg_score'       => 0,
                'score_change'    => '0%',
                'quizzes_taken'   => 0,
                'quizzes_change'  => '0',
                'correct_answers' => 0,
                'accuracy_change' => '0%',
                'time_spent'      => '0h 00m',
                'time_change'     => '0m',
            ],
            'trend_chart' => [
                'labels' => [],
                'scores' => [],
                'points' => [],
            ],
            'difficulty' => [
                'average' => 0,
                'easy'    => 0,
                'medium'  => 0,
                'hard'    => 0,
                'expert'  => 0,
                'counts'  => [
                    'easy'   => 0,
                    'medium' => 0,
                    'hard'   => 0,
                    'expert' => 0,
                ],
            ],
            'weak_topics'    => [],
            'recent_results' => [],
            'recent_quizzes' => [],
            'topics'         => [],
            'ai_insight'     => $this->buildAiInsight([], [], $enrollments),
        ];
    }
}
