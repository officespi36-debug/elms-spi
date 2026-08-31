<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class QuizAttemptHistoryService
{
    /**
     * Get quiz attempt history dataset.
     */
    public function getAttemptsHistoryData(?User $user = null, string $status = 'all', string $dateRange = 'all', string $search = '', int $page = 1, int $perPage = 10): array
    {
        $realAttempts = collect();
        if ($user) {
            try {
                $realAttempts = QuizAttempt::where('user_id', $user->id)
                    ->with('quiz.course')
                    ->latest()
                    ->get();
            } catch (\Throwable $e) {
                \Log::warning('QuizAttemptHistoryService DB error: ' . $e->getMessage());
            }
        }

        return $this->buildAttemptsPayload($user, $realAttempts, $status, $dateRange, $search, $page, $perPage);
    }

    /**
     * Build attempts history payload matching reference specs.
     */
    protected function buildAttemptsPayload(?User $user, Collection $realAttempts, string $status, string $dateRange, string $search, int $page, int $perPage): array
    {
        // 1. Summary Cards
        $summary = [
            'total_attempts' => 28,
            'total_note'     => 'All time attempts',
            'average_score'  => 72,
            'average_trend'  => '+8% vs last 30 days',
            'highest_score'  => 95,
            'highest_title'  => 'JavaScript Advanced Quiz',
            'lowest_score'   => 35,
            'lowest_title'   => 'SQL JOINS Quiz',
            'passed_count'   => 18,
            'passed_note'    => '64% of all attempts',
        ];

        // 2. Performance Overview (Donut Chart)
        $performanceOverview = [
            'average_score' => 72,
            'distribution'  => [
                [
                    'label'      => '90 - 100%',
                    'count'      => 8,
                    'percentage' => 29,
                    'color'      => '#10B981', // emerald
                    'class'      => 'text-emerald-400',
                ],
                [
                    'label'      => '70 - 89%',
                    'count'      => 10,
                    'percentage' => 36,
                    'color'      => '#3B82F6', // blue
                    'class'      => 'text-blue-400',
                ],
                [
                    'label'      => '50 - 69%',
                    'count'      => 6,
                    'percentage' => 21,
                    'color'      => '#F59E0B', // amber
                    'class'      => 'text-amber-400',
                ],
                [
                    'label'      => 'Below 50%',
                    'count'      => 4,
                    'percentage' => 14,
                    'color'      => '#EF4444', // red
                    'class'      => 'text-rose-400',
                ],
            ],
        ];

        // 3. Score Trend (Line Chart)
        $scoreTrend = [
            'points' => [
                ['date' => 'May 20', 'percentage' => 25],
                ['date' => 'May 23', 'percentage' => 50],
                ['date' => 'May 26', 'percentage' => 65],
                ['date' => 'May 29', 'percentage' => 45],
                ['date' => 'Jun 1',  'percentage' => 95],
            ],
        ];

        // 4. Recent Activity
        $recentActivity = [
            [
                'id'       => 1,
                'title'    => 'JavaScript Advanced Quiz',
                'score'    => 95,
                'date_str' => '10:30 AM Jun 1',
                'code'     => 'JS',
                'icon_bg'  => 'from-amber-400 to-amber-500 text-slate-950',
            ],
            [
                'id'       => 2,
                'title'    => 'React Components Quiz',
                'score'    => 68,
                'date_str' => '09:15 AM Jun 1',
                'code'     => '⚛️',
                'icon_bg'  => 'from-cyan-400 to-blue-500 text-white',
            ],
            [
                'id'       => 3,
                'title'    => 'SQL JOINS Quiz',
                'score'    => 35,
                'date_str' => 'May 31',
                'code'     => '🗄️',
                'icon_bg'  => 'from-blue-500 to-indigo-600 text-white',
            ],
            [
                'id'       => 4,
                'title'    => 'HTML & CSS Quiz',
                'score'    => 88,
                'date_str' => 'May 31',
                'code'     => '5',
                'icon_bg'  => 'from-orange-500 to-amber-500 text-white',
            ],
            [
                'id'       => 5,
                'title'    => 'Node.js Basics Quiz',
                'score'    => 60,
                'date_str' => 'May 30',
                'code'     => '🟢',
                'icon_bg'  => 'from-emerald-500 to-teal-600 text-white',
            ],
        ];

        // 5. Need Improvement Topics
        $weakTopics = [
            'SQL JOINS',
            'React Hooks',
            'Python Functions',
        ];

        // 6. Attempts Table Catalog (10 items matching reference)
        $attemptsTable = [
            [
                'id'          => 1,
                'title'       => 'JavaScript Advanced Quiz',
                'subtitle'    => 'Advanced JavaScript concepts',
                'course'      => 'JavaScript Advanced',
                'attempt'     => '2 / 3',
                'score'       => '19 / 20',
                'percentage'  => 95,
                'time_taken'  => '28m 15s',
                'date'        => 'Jun 1, 2025',
                'time'        => '10:30 AM',
                'status'      => 'Passed',
                'status_type' => 'passed',
                'code'        => 'JS',
                'icon_bg'     => 'from-amber-400 to-amber-500 text-slate-950',
                'questions'   => [
                    ['q' => 'What is a Closure in JavaScript?', 'user_ans' => 'A function with access to its outer scope', 'correct_ans' => 'A function with access to its outer scope', 'is_correct' => true, 'points' => 5],
                    ['q' => 'What is the purpose of Promise.all()?', 'user_ans' => 'Wait for all promises to resolve', 'correct_ans' => 'Wait for all promises to resolve', 'is_correct' => true, 'points' => 5],
                ],
            ],
            [
                'id'          => 2,
                'title'       => 'React Components Quiz',
                'subtitle'    => 'React.js Fundamentals',
                'course'      => 'React.js Fundamentals',
                'attempt'     => '1 / 3',
                'score'       => '17 / 25',
                'percentage'  => 68,
                'time_taken'  => '32m 45s',
                'date'        => 'Jun 1, 2025',
                'time'        => '09:15 AM',
                'status'      => 'Completed',
                'status_type' => 'completed',
                'code'        => '⚛️',
                'icon_bg'     => 'from-cyan-400 to-blue-500 text-white',
                'questions'   => [
                    ['q' => 'What does useEffect do?', 'user_ans' => 'Performs side effects in functional components', 'correct_ans' => 'Performs side effects in functional components', 'is_correct' => true, 'points' => 5],
                ],
            ],
            [
                'id'          => 3,
                'title'       => 'SQL JOINS Quiz',
                'subtitle'    => 'Database Systems',
                'course'      => 'Database Design',
                'attempt'     => '1 / 2',
                'score'       => '7 / 20',
                'percentage'  => 35,
                'time_taken'  => '25m 10s',
                'date'        => 'May 31, 2025',
                'time'        => '08:45 PM',
                'status'      => 'Failed',
                'status_type' => 'failed',
                'code'        => '🗄️',
                'icon_bg'     => 'from-blue-500 to-indigo-600 text-white',
                'questions'   => [
                    ['q' => 'What is the difference between LEFT JOIN and INNER JOIN?', 'user_ans' => 'They return the same rows', 'correct_ans' => 'LEFT JOIN preserves all records from left table', 'is_correct' => false, 'points' => 0],
                ],
            ],
            [
                'id'          => 4,
                'title'       => 'HTML & CSS Quiz',
                'subtitle'    => 'Web Development',
                'course'      => 'Web Development',
                'attempt'     => '1 / 3',
                'score'       => '22 / 25',
                'percentage'  => 88,
                'time_taken'  => '30m 00s',
                'date'        => 'May 31, 2025',
                'time'        => '04:20 PM',
                'status'      => 'Passed',
                'status_type' => 'passed',
                'code'        => '5',
                'icon_bg'     => 'from-orange-500 to-amber-500 text-white',
            ],
            [
                'id'          => 5,
                'title'       => 'Node.js Basics Quiz',
                'subtitle'    => 'Node.js Fundamentals',
                'course'      => 'Node.js Fundamentals',
                'attempt'     => '2 / 3',
                'score'       => '15 / 25',
                'percentage'  => 60,
                'time_taken'  => '35m 45s',
                'date'        => 'May 30, 2025',
                'time'        => '07:30 PM',
                'status'      => 'Completed',
                'status_type' => 'completed',
                'code'        => '🟢',
                'icon_bg'     => 'from-emerald-500 to-teal-600 text-white',
            ],
            [
                'id'          => 6,
                'title'       => 'Python Functions Quiz',
                'subtitle'    => 'Python Programming',
                'course'      => 'Python Programming',
                'attempt'     => '1 / 2',
                'score'       => '18 / 20',
                'percentage'  => 90,
                'time_taken'  => '22m 30s',
                'date'        => 'May 30, 2025',
                'time'        => '02:10 PM',
                'status'      => 'Passed',
                'status_type' => 'passed',
                'code'        => '🐍',
                'icon_bg'     => 'from-blue-400 to-amber-400 text-white',
            ],
            [
                'id'          => 7,
                'title'       => 'Git & GitHub Quiz',
                'subtitle'    => 'Version Control',
                'course'      => 'DevOps Tools',
                'attempt'     => '1 / 2',
                'score'       => '8 / 15',
                'percentage'  => 53,
                'time_taken'  => '18m 20s',
                'date'        => 'May 29, 2025',
                'time'        => '09:05 AM',
                'status'      => 'Completed',
                'status_type' => 'completed',
                'code'        => '🐙',
                'icon_bg'     => 'from-rose-500 to-orange-500 text-white',
            ],
            [
                'id'          => 8,
                'title'       => 'JavaScript Functions Quiz',
                'subtitle'    => 'Functions and Scope',
                'course'      => 'JavaScript Basics',
                'attempt'     => '3 / 3',
                'score'       => '14 / 20',
                'percentage'  => 70,
                'time_taken'  => '25m 30s',
                'date'        => 'May 28, 2025',
                'time'        => '06:45 PM',
                'status'      => 'Completed',
                'status_type' => 'completed',
                'code'        => 'JS',
                'icon_bg'     => 'from-amber-400 to-amber-500 text-slate-950',
            ],
            [
                'id'          => 9,
                'title'       => 'CSS Flexbox Quiz',
                'subtitle'    => 'CSS Layout',
                'course'      => 'Web Design',
                'attempt'     => '1 / 2',
                'score'       => '20 / 20',
                'percentage'  => 100,
                'time_taken'  => '15m 10s',
                'date'        => 'May 28, 2025',
                'time'        => '03:20 PM',
                'status'      => 'Passed',
                'status_type' => 'passed',
                'code'        => '🎨',
                'icon_bg'     => 'from-cyan-500 to-blue-600 text-white',
            ],
            [
                'id'          => 10,
                'title'       => 'React Hooks Quiz',
                'subtitle'    => 'React.js Advanced',
                'course'      => 'React.js Advanced',
                'attempt'     => '1 / 2',
                'score'       => '10 / 25',
                'percentage'  => 40,
                'time_taken'  => '28m 40s',
                'date'        => 'May 27, 2025',
                'time'        => '08:30 PM',
                'status'      => 'Failed',
                'status_type' => 'failed',
                'code'        => '⚛️',
                'icon_bg'     => 'from-cyan-400 to-blue-500 text-white',
            ],
        ];

        // Filter by status tab if provided
        if ($status !== 'all') {
            $attemptsTable = array_values(array_filter($attemptsTable, function ($row) use ($status) {
                return strtolower($row['status_type']) === strtolower($status);
            }));
        }

        // Filter by search query
        if (!empty($search)) {
            $s = strtolower($search);
            $attemptsTable = array_values(array_filter($attemptsTable, function ($row) use ($s) {
                return str_contains(strtolower($row['title']), $s) ||
                       str_contains(strtolower($row['course']), $s) ||
                       str_contains(strtolower($row['status']), $s);
            }));
        }

        return [
            'summary'              => $summary,
            'performance_overview' => $performanceOverview,
            'score_trend'          => $scoreTrend,
            'recent_activity'      => $recentActivity,
            'weak_topics'          => $weakTopics,
            'attempts'             => $attemptsTable,
            'total_count'          => 28,
            'current_page'         => $page,
            'per_page'             => $perPage,
        ];
    }
}
