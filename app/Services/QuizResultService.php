<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class QuizResultService
{
    /**
     * Get aggregated Quiz Results analytics and history table.
     */
    public function getQuizResultsData(?User $user = null, string $status = 'all', string $course = 'all', string $category = 'all', string $difficulty = 'all', string $dateRange = 'all', string $search = '', int $page = 1, int $perPage = 8): array
    {
        $realAttempts = collect();
        if ($user) {
            try {
                $realAttempts = QuizAttempt::where('user_id', $user->id)
                    ->with('quiz.course')
                    ->latest()
                    ->get();
            } catch (\Throwable $e) {
                \Log::warning('QuizResultService DB error: ' . $e->getMessage());
            }
        }

        return $this->buildResultsPayload($user, $realAttempts, $status, $course, $category, $difficulty, $dateRange, $search, $page, $perPage);
    }

    /**
     * Build the structured payload for Quiz Results dashboard.
     */
    protected function buildResultsPayload(?User $user, Collection $realAttempts, string $status, string $course, string $category, string $difficulty, string $dateRange, string $search, int $page, int $perPage): array
    {
        // 1. Summary Cards
        $summary = [
            'average_score' => 72,
            'average_note'  => 'Good job! Keep it up.',
            'highest_score' => 95,
            'highest_title' => 'JavaScript Advanced Quiz',
            'lowest_score'  => 35,
            'lowest_title'  => 'SQL JOINS Quiz',
            'quizzes_taken' => 12,
            'taken_trend'   => '+3 vs last month',
            'passed_count'  => 8,
            'passed_note'   => '67% pass rate',
        ];

        // 2. Performance Overview Donut
        $performanceOverview = [
            'average_score' => 72,
            'distribution'  => [
                ['label' => '90 - 100%', 'count' => 2, 'percentage' => 17, 'color' => '#10B981', 'class' => 'text-emerald-400'],
                ['label' => '70 - 89%',  'count' => 6, 'percentage' => 50, 'color' => '#3B82F6', 'class' => 'text-blue-400'],
                ['label' => '50 - 69%',  'count' => 3, 'percentage' => 25, 'color' => '#F59E0B', 'class' => 'text-amber-400'],
                ['label' => 'Below 50%', 'count' => 1, 'percentage' => 8,  'color' => '#EF4444', 'class' => 'text-rose-400'],
            ],
        ];

        // 3. Score Trend Line Chart
        $scoreTrend = [
            'points' => [
                ['date' => 'May 1',  'percentage' => 50],
                ['date' => 'May 8',  'percentage' => 35],
                ['date' => 'May 15', 'percentage' => 55],
                ['date' => 'May 22', 'percentage' => 40],
                ['date' => 'May 29', 'percentage' => 75],
                ['date' => 'Jun 1',  'percentage' => 95],
            ],
            'highlight' => [
                'date'       => 'May 24, 2025',
                'score_text' => 'Score: 75%',
                'x_percent'  => 65,
                'y_percent'  => 38,
            ]
        ];

        // 4. Score by Course
        $scoreByCourse = [
            ['course' => 'JavaScript Advanced',   'percentage' => 85, 'color' => 'from-cyan-400 to-blue-500'],
            ['course' => 'React.js Fundamentals', 'percentage' => 68, 'color' => 'from-cyan-400 to-blue-500'],
            ['course' => 'Database Design',       'percentage' => 45, 'color' => 'from-amber-400 to-orange-500'],
            ['course' => 'Web Development',       'percentage' => 78, 'color' => 'from-cyan-400 to-blue-500'],
            ['course' => 'Python Programming',    'percentage' => 70, 'color' => 'from-cyan-400 to-blue-500'],
        ];

        // 5. Result Distribution
        $resultDistribution = [
            'total_quizzes' => 12,
            'items' => [
                ['label' => 'Passed',      'count' => 8, 'percentage' => 67, 'color' => '#10B981'],
                ['label' => 'Failed',      'count' => 3, 'percentage' => 25, 'color' => '#EF4444'],
                ['label' => 'In Progress', 'count' => 1, 'percentage' => 8,  'color' => '#F59E0B'],
            ]
        ];

        // 6. Average Score Comparison
        $scoreComparison = [
            'this_month'  => 72,
            'last_month'  => 64,
            'trend_text'  => '+8% vs last month',
            'is_improved' => true,
        ];

        // 7. Recent Achievements
        $recentAchievements = [
            [
                'id'       => 1,
                'title'    => 'Perfect Score',
                'subtitle' => 'Scored 100% in a quiz',
                'date'     => 'May 28, 2025',
                'icon'     => '🛡️',
                'icon_bg'  => 'bg-emerald-500/20 border border-emerald-500/30 text-emerald-300',
            ],
            [
                'id'       => 2,
                'title'    => 'Quiz Master',
                'subtitle' => 'Completed 10 quizzes',
                'date'     => 'May 25, 2025',
                'icon'     => '🛡️',
                'icon_bg'  => 'bg-purple-500/20 border border-purple-500/30 text-purple-300',
            ],
            [
                'id'       => 3,
                'title'    => 'Consistent Learner',
                'subtitle' => '7 days quiz streak',
                'date'     => 'May 22, 2025',
                'icon'     => '🔥',
                'icon_bg'  => 'bg-orange-500/20 border border-orange-500/30 text-orange-300',
            ],
        ];

        // 8. Need Improvement Topics
        $weakTopics = [
            'SQL JOINS',
            'Database Queries',
            'React Hooks',
        ];

        // 9. Results Table (8 items matching reference)
        $resultsTable = [
            [
                'id'            => 1,
                'title'         => 'JavaScript Advanced Quiz',
                'subtitle'      => 'Advanced JavaScript concepts',
                'course'        => 'JavaScript Advanced',
                'score'         => '19 / 20',
                'percentage'    => 95,
                'correct_total' => '19 / 20',
                'time_taken'    => '28m 15s',
                'completed_on'  => 'Jun 1, 2025 10:30 AM',
                'result'        => 'Passed',
                'result_type'   => 'passed',
                'code'          => 'JS',
                'icon_bg'       => 'from-amber-400 to-amber-500 text-slate-950',
                'questions'     => [
                    ['q' => 'What is a JavaScript Closure?', 'user_ans' => 'A function with access to its parent scope', 'correct_ans' => 'A function with access to its parent scope', 'is_correct' => true, 'points' => 5],
                    ['q' => 'What does Promise.race() do?', 'user_ans' => 'Resolves as soon as the first promise resolves', 'correct_ans' => 'Resolves as soon as the first promise resolves', 'is_correct' => true, 'points' => 5],
                ],
            ],
            [
                'id'            => 2,
                'title'         => 'React Components Quiz',
                'subtitle'      => 'React.js Fundamentals',
                'course'        => 'React.js Fundamentals',
                'score'         => '17 / 25',
                'percentage'    => 68,
                'correct_total' => '17 / 25',
                'time_taken'    => '32m 45s',
                'completed_on'  => 'Jun 1, 2025 09:15 AM',
                'result'        => 'Passed',
                'result_type'   => 'passed',
                'code'          => '⚛️',
                'icon_bg'       => 'from-cyan-400 to-blue-500 text-white',
            ],
            [
                'id'            => 3,
                'title'         => 'SQL JOINS Quiz',
                'subtitle'      => 'Database Systems',
                'course'        => 'Database Design',
                'score'         => '7 / 20',
                'percentage'    => 35,
                'correct_total' => '7 / 20',
                'time_taken'    => '25m 10s',
                'completed_on'  => 'May 31, 2025 08:45 PM',
                'result'        => 'Failed',
                'result_type'   => 'failed',
                'code'          => '🗄️',
                'icon_bg'       => 'from-blue-500 to-indigo-600 text-white',
            ],
            [
                'id'            => 4,
                'title'         => 'HTML & CSS Quiz',
                'subtitle'      => 'Web Development',
                'course'        => 'Web Development',
                'score'         => '22 / 25',
                'percentage'    => 88,
                'correct_total' => '22 / 25',
                'time_taken'    => '30m 00s',
                'completed_on'  => 'May 31, 2025 04:20 PM',
                'result'        => 'Passed',
                'result_type'   => 'passed',
                'code'          => '5',
                'icon_bg'       => 'from-orange-500 to-amber-500 text-white',
            ],
            [
                'id'            => 5,
                'title'         => 'Node.js Basics Quiz',
                'subtitle'      => 'Node.js Fundamentals',
                'course'        => 'Backend Development',
                'score'         => '15 / 25',
                'percentage'    => 60,
                'correct_total' => '15 / 25',
                'time_taken'    => '35m 45s',
                'completed_on'  => 'May 30, 2025 07:30 PM',
                'result'        => 'Passed',
                'result_type'   => 'passed',
                'code'          => '🟢',
                'icon_bg'       => 'from-emerald-500 to-teal-600 text-white',
            ],
            [
                'id'            => 6,
                'title'         => 'Python Functions Quiz',
                'subtitle'      => 'Python Programming',
                'course'        => 'Python Programming',
                'score'         => '18 / 20',
                'percentage'    => 90,
                'correct_total' => '18 / 20',
                'time_taken'    => '22m 30s',
                'completed_on'  => 'May 30, 2025 02:10 PM',
                'result'        => 'Passed',
                'result_type'   => 'passed',
                'code'          => '🐍',
                'icon_bg'       => 'from-blue-400 to-amber-400 text-white',
            ],
            [
                'id'            => 7,
                'title'         => 'Git & GitHub Quiz',
                'subtitle'      => 'Version Control',
                'course'        => 'DevOps Tools',
                'score'         => '8 / 15',
                'percentage'    => 53,
                'correct_total' => '8 / 15',
                'time_taken'    => '18m 20s',
                'completed_on'  => 'May 29, 2025 09:05 AM',
                'result'        => 'Failed',
                'result_type'   => 'failed',
                'code'          => '🐙',
                'icon_bg'       => 'from-rose-500 to-orange-500 text-white',
            ],
            [
                'id'            => 8,
                'title'         => 'JavaScript Functions Quiz',
                'subtitle'      => 'Functions and Scope',
                'course'        => 'JavaScript Basics',
                'score'         => '14 / 20',
                'percentage'    => 70,
                'correct_total' => '14 / 20',
                'time_taken'    => '25m 30s',
                'completed_on'  => 'May 28, 2025 06:45 PM',
                'result'        => 'Passed',
                'result_type'   => 'passed',
                'code'          => 'JS',
                'icon_bg'       => 'from-amber-400 to-amber-500 text-slate-950',
            ],
        ];

        // Filter by status tab
        if ($status !== 'all') {
            if ($status === 'needs_improvement') {
                $resultsTable = array_values(array_filter($resultsTable, fn($r) => $r['percentage'] < 70));
            } else {
                $resultsTable = array_values(array_filter($resultsTable, fn($r) => strtolower($r['result_type']) === strtolower($status)));
            }
        }

        // Filter by course
        if ($course !== 'all') {
            $resultsTable = array_values(array_filter($resultsTable, fn($r) => strtolower($r['course']) === strtolower($course)));
        }

        // Filter by search
        if (!empty($search)) {
            $s = strtolower($search);
            $resultsTable = array_values(array_filter($resultsTable, fn($r) => str_contains(strtolower($r['title']), $s) || str_contains(strtolower($r['course']), $s)));
        }

        return [
            'summary'              => $summary,
            'performance_overview' => $performanceOverview,
            'score_trend'          => $scoreTrend,
            'score_by_course'      => $scoreByCourse,
            'result_distribution'  => $resultDistribution,
            'score_comparison'     => $scoreComparison,
            'recent_achievements'  => $recentAchievements,
            'weak_topics'          => $weakTopics,
            'results'              => $resultsTable,
            'total_count'          => 12,
            'current_page'         => $page,
            'per_page'             => $perPage,
        ];
    }
}
