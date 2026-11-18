<?php

namespace App\Services;

use App\Models\Deadline;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Carbon\Carbon;

class AvailableQuizService
{
    /**
     * Get available quizzes and analytics data from 100% real database records.
     */
    public function getAvailableQuizzesData(
        ?User $user = null,
        string $category = 'all',
        string $course = 'all',
        string $difficulty = 'all',
        string $status = 'available',
        string $search = ''
    ): array {
        if (!$user) {
            return $this->emptyPayload();
        }

        $enrolledCourseIds = Enrollment::where('student_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->pluck('course_id')
            ->unique()
            ->filter()
            ->values()
            ->all();

        $attempts = QuizAttempt::where('user_id', $user->id)
            ->with('quiz.course')
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get();

        $quizStreak = $this->buildQuizStreak($attempts);
        $quizProgress = $this->buildQuizProgress($attempts);
        $recommendedQuizzes = $this->buildRecommendedQuizzes($attempts);

        if (empty($enrolledCourseIds)) {
            return [
                'summary'             => [
                    'available_quizzes' => 0,
                    'available_note'    => 'Quizzes ready to take',
                    'practice_quizzes'  => 0,
                    'practice_note'     => 'Improve your skills',
                    'assessments'       => 0,
                    'assessments_note'  => 'Test your knowledge',
                    'total_points'      => 0,
                    'points_note'       => 'Points you can earn',
                ],
                'practice_quizzes'    => [],
                'assessments'         => [],
                'upcoming_quizzes'    => [],
                'quiz_progress'       => $quizProgress,
                'recommended_quizzes' => $recommendedQuizzes,
                'quiz_streak'         => $quizStreak,
                'courses'             => [],
                'categories'          => [],
            ];
        }

        $allCourseQuizzes = Quiz::whereIn('course_id', $enrolledCourseIds)
            ->where('title', '!=', 'General Question Bank Vault')
            ->with(['course.major', 'module'])
            ->withCount('questions')
            ->withSum('questions', 'points')
            ->latest()
            ->get();

        $coursesList = $allCourseQuizzes
            ->map(fn (Quiz $q) => $q->course?->title)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $categoriesList = $allCourseQuizzes
            ->map(fn (Quiz $q) => $q->course?->major?->name)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $attemptsByQuiz = $attempts->groupBy('quiz_id');

        $publishedQuizzes = $allCourseQuizzes->filter(function (Quiz $q) {
            return ($q->status === 'published' || $q->status === null) && $q->type !== 'assignment';
        })->values();

        $practiceRaw = $publishedQuizzes->filter(fn (Quiz $q) => in_array($q->type, ['practice', 'quiz', null, ''], true))->values();
        $assessmentRaw = $publishedQuizzes->filter(fn (Quiz $q) => in_array($q->type, ['pre_test', 'post_test'], true))->values();

        $totalPoints = (int) $publishedQuizzes->sum(function (Quiz $q) {
            $pts = (float) ($q->questions_sum_points ?? 0);
            return $pts > 0 ? $pts : ((int) $q->questions_count * 5);
        });

        $summary = [
            'available_quizzes' => $publishedQuizzes->count(),
            'available_note'    => $publishedQuizzes->count() > 0 ? 'Quizzes ready to take' : 'No quizzes available yet',
            'practice_quizzes'  => $practiceRaw->count(),
            'practice_note'     => 'Improve your skills',
            'assessments'       => $assessmentRaw->count(),
            'assessments_note'  => 'Pre-test & Post-test exams',
            'total_points'      => $totalPoints,
            'points_note'       => 'Points you can earn',
        ];

        $mapQuizCard = function (Quiz $q, bool $isAssessment = false) use ($attemptsByQuiz): array {
            $courseTitle = $q->course?->title ?? 'Course';
            $catName = $q->course?->major?->name ?? ($q->course?->level ? ucfirst($q->course->level) : 'General');
            $qCount = (int) ($q->questions_count ?? 0);
            $points = (int) round((float) ($q->questions_sum_points ?? 0));
            if ($points <= 0 && $qCount > 0) {
                $points = $qCount * 5;
            }

            $difficultyLabel = match (strtolower((string) ($q->course?->level ?? ''))) {
                'beginner', 'easy' => 'Easy',
                'advanced', 'hard' => 'Hard',
                'expert'           => 'Expert',
                default            => ($isAssessment ? 'Hard' : 'Medium'),
            };

            $quizAttempts = $attemptsByQuiz->get($q->id, collect());
            $attemptsUsed = $quizAttempts->count();
            $maxAttempts = $q->max_attempts !== null && (int) $q->max_attempts > 0 ? (int) $q->max_attempts : null;
            $isExhausted = $maxAttempts !== null && $attemptsUsed >= $maxAttempts;

            $code = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $q->course?->code ?: $courseTitle), 0, 2) ?: 'QZ');

            return [
                'id'              => $q->id,
                'title'           => $q->title,
                'course'          => $courseTitle,
                'category'        => $catName,
                'difficulty'      => $difficultyLabel,
                'questions_count' => $qCount,
                'time_limit'      => (int) ($q->time_limit_minutes ?? 0),
                'passing_score'   => (int) round((float) ($q->passing_score ?? 50)),
                'points'          => $points,
                'attempts_used'   => $attemptsUsed,
                'max_attempts'    => $maxAttempts,
                'can_attempt'     => !$isExhausted,
                'code'            => $code,
                'icon'            => $q->type === 'pre_test' ? '🟦' : ($q->type === 'post_test' ? '🟥' : '🛡️'),
                'icon_bg'         => $isAssessment ? 'from-purple-600 to-indigo-600' : 'from-cyan-500 to-indigo-600 text-white',
                'badge_color'     => $difficultyLabel === 'Easy' ? 'emerald' : ($difficultyLabel === 'Medium' ? 'amber' : 'rose'),
                'description'     => $q->module?->title
                    ? "{$q->module->title} — {$courseTitle}"
                    : "Course quiz for {$courseTitle}",
                'instructions'    => ($q->time_limit_minutes ? "Time limit: {$q->time_limit_minutes} minutes. " : 'Untimed quiz. ')
                    . 'Passing score: ' . (int) round((float) ($q->passing_score ?? 50)) . '%.',
                'status'          => 'available',
            ];
        };

        $practiceQuizzes = $practiceRaw->map(fn (Quiz $q) => $mapQuizCard($q, false))->values()->all();
        $assessments = $assessmentRaw->map(fn (Quiz $q) => $mapQuizCard($q, true))->values()->all();

        $filterCards = function (array $cards) use ($search, $category, $course, $difficulty, $status): array {
            if ($status === 'upcoming') {
                return [];
            }
            if ($search !== '') {
                $s = mb_strtolower(trim($search), 'UTF-8');
                $cards = array_values(array_filter($cards, function ($item) use ($s) {
                    return str_contains(mb_strtolower($item['title'], 'UTF-8'), $s)
                        || str_contains(mb_strtolower($item['course'], 'UTF-8'), $s)
                        || str_contains(mb_strtolower($item['description'], 'UTF-8'), $s);
                }));
            }
            if ($category !== 'all') {
                $cards = array_values(array_filter($cards, fn ($item) => strcasecmp($item['category'] ?? '', $category) === 0));
            }
            if ($course !== 'all') {
                $cards = array_values(array_filter($cards, fn ($item) => strcasecmp($item['course'] ?? '', $course) === 0));
            }
            if ($difficulty !== 'all') {
                $cards = array_values(array_filter($cards, fn ($item) => strcasecmp($item['difficulty'] ?? '', $difficulty) === 0));
            }
            return $cards;
        };

        $practiceQuizzes = $filterCards($practiceQuizzes);
        $assessments = $filterCards($assessments);

        // Upcoming quizzes from future Deadlines on enrolled courses
        $upcomingDeadlines = Deadline::whereIn('course_id', $enrolledCourseIds)
            ->where('due_at', '>', now())
            ->where(function ($q) {
                $q->where('linked_type', 'quiz')
                  ->orWhereIn('deadline_type', ['quiz', 'exam', 'pre_test', 'post_test']);
            })
            ->with('course:id,title')
            ->orderBy('due_at')
            ->take(6)
            ->get();

        $upcomingQuizzes = $upcomingDeadlines->map(function (Deadline $dl) {
            return [
                'id'           => $dl->linked_id ?: $dl->id,
                'title'        => $dl->title,
                'course'       => $dl->course?->title ?? 'Course',
                'available_in' => $dl->due_at ? $dl->due_at->diffForHumans(null, true) : 'Soon',
                'points'       => 0,
                'icon'         => '📅',
                'icon_bg'      => 'bg-blue-500/20 text-blue-300 border border-blue-500/30',
            ];
        })->values()->all();

        return [
            'summary'             => $summary,
            'practice_quizzes'    => $practiceQuizzes,
            'assessments'         => $assessments,
            'upcoming_quizzes'    => $upcomingQuizzes,
            'quiz_progress'       => $quizProgress,
            'recommended_quizzes' => $recommendedQuizzes,
            'quiz_streak'         => $quizStreak,
            'courses'             => $coursesList,
            'categories'          => $categoriesList,
        ];
    }

    protected function buildQuizProgress($attempts): array
    {
        $totalCount = $attempts->count();
        if ($totalCount === 0) {
            return [
                'average_score'   => 0,
                'quizzes_count'   => 0,
                'quizzes_trend'   => '0 this month',
                'correct_answers' => 0,
                'correct_trend'   => '0%',
            ];
        }

        $avgScore = (int) round((float) $attempts->avg('score'));
        $now = Carbon::now('Asia/Phnom_Penh');
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        $thisMonthAttempts = $attempts->filter(function ($a) use ($startOfMonth) {
            $dt = $a->submitted_at ?? $a->created_at;
            return $dt && Carbon::parse($dt)->gte($startOfMonth);
        });

        $lastMonthAttempts = $attempts->filter(function ($a) use ($startOfLastMonth, $endOfLastMonth) {
            $dt = $a->submitted_at ?? $a->created_at;
            return $dt && Carbon::parse($dt)->between($startOfLastMonth, $endOfLastMonth);
        });

        $thisMonthCount = $thisMonthAttempts->count();
        $trendDiff = 0;
        if ($thisMonthAttempts->isNotEmpty() && $lastMonthAttempts->isNotEmpty()) {
            $trendDiff = (int) round((float) $thisMonthAttempts->avg('score') - (float) $lastMonthAttempts->avg('score'));
        }

        return [
            'average_score'   => $avgScore,
            'quizzes_count'   => $totalCount,
            'quizzes_trend'   => "+{$thisMonthCount} this month",
            'correct_answers' => $avgScore,
            'correct_trend'   => ($trendDiff >= 0 ? "+{$trendDiff}%" : "{$trendDiff}%"),
        ];
    }

    protected function buildRecommendedQuizzes($attempts): array
    {
        if ($attempts->isEmpty()) {
            return [];
        }

        $byQuiz = $attempts->groupBy('quiz_id');
        $weak = [];

        foreach ($byQuiz as $quizId => $group) {
            $latest = $group->first();
            $quiz = $latest?->quiz;
            if (!$quiz) {
                continue;
            }
            $bestScore = (int) round((float) $group->max('score'));
            $passing = (int) round((float) ($quiz->passing_score ?? 70));

            if ($bestScore < $passing) {
                $weak[] = [
                    'id'          => $quiz->id,
                    'title'       => $quiz->title,
                    'note'        => $quiz->course?->title ?? 'Review recommended',
                    'score'       => $bestScore,
                    'color_class' => $bestScore < 50 ? 'text-rose-400 font-bold' : 'text-amber-400 font-bold',
                    'icon'        => '📙',
                ];
            }
        }

        usort($weak, fn ($a, $b) => $a['score'] <=> $b['score']);
        return array_slice($weak, 0, 4);
    }

    protected function buildQuizStreak($attempts): array
    {
        $tz = 'Asia/Phnom_Penh';
        $today = Carbon::now($tz)->startOfDay();

        $activeDates = [];
        foreach ($attempts as $a) {
            $dt = $a->submitted_at ?? $a->created_at;
            if ($dt) {
                $key = Carbon::parse($dt)->timezone($tz)->format('Y-m-d');
                $activeDates[$key] = true;
            }
        }

        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = $today->copy()->subDays($i);
            $key = $d->format('Y-m-d');
            $days[] = [
                'label'  => $d->format('M j'),
                'date'   => $d->format('j'),
                'active' => isset($activeDates[$key]),
            ];
        }

        $streak = 0;
        $cursor = isset($activeDates[$today->format('Y-m-d')])
            ? $today->copy()
            : $today->copy()->subDay();

        while (isset($activeDates[$cursor->format('Y-m-d')])) {
            $streak++;
            $cursor->subDay();
        }

        return [
            'streak_days' => $streak,
            'days'        => $days,
        ];
    }

    protected function emptyPayload(): array
    {
        return [
            'summary'             => [
                'available_quizzes' => 0,
                'available_note'    => 'No quizzes available yet',
                'practice_quizzes'  => 0,
                'practice_note'     => 'Improve your skills',
                'assessments'       => 0,
                'assessments_note'  => 'Test your knowledge',
                'total_points'      => 0,
                'points_note'       => 'Points you can earn',
            ],
            'practice_quizzes'    => [],
            'assessments'         => [],
            'upcoming_quizzes'    => [],
            'quiz_progress'       => [
                'average_score'   => 0,
                'quizzes_count'   => 0,
                'quizzes_trend'   => '0 this month',
                'correct_answers' => 0,
                'correct_trend'   => '0%',
            ],
            'recommended_quizzes' => [],
            'quiz_streak'         => $this->buildQuizStreak(collect()),
            'courses'             => [],
            'categories'          => [],
        ];
    }
}
