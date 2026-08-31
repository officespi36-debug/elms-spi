<?php

namespace App\Services;

use App\Models\LessonProgress;
use App\Models\QuizAttempt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LearningActivityService
{
    /**
     * Get complete student learning activity analytics.
     */
    public function getStudentLearningActivity(?User $user = null, string $range = '7d', string $granularity = 'daily'): array
    {
        $completedLessonsCount = 0;
        $totalSeconds = 0;
        $quizAttemptsCount = 0;

        if ($user) {
            try {
                $completedLessonsCount = LessonProgress::where('user_id', $user->id)
                    ->where(function ($q) {
                        $q->whereNotNull('completed_at')->orWhere('percent', '>=', 100);
                    })
                    ->count();

                $totalSeconds = (int)LessonProgress::where('user_id', $user->id)
                    ->sum('seconds_watched');

                $quizAttemptsCount = QuizAttempt::where('user_id', $user->id)
                    ->whereNotNull('score')
                    ->count();
            } catch (\Throwable $e) {
                \Log::warning('LearningActivityService DB error: ' . $e->getMessage());
            }
        }

        return $this->buildActivityAnalytics($user, $completedLessonsCount, $totalSeconds, $quizAttemptsCount, $range, $granularity);
    }

    /**
     * Build activity analytics structure.
     */
    protected function buildActivityAnalytics(?User $user, int $completedLessonsCount, int $totalSeconds, int $quizAttemptsCount, string $range, string $granularity): array
    {
        // 1. Summary Cards
        $summary = [
            'total_study_time'    => '48h 30m',
            'total_seconds'       => 174600,
            'time_trend'          => '+12% vs last 7 days',
            'daily_average'       => '6h 55m',
            'daily_avg_trend'     => '+18% vs last 7 days',
            'study_sessions'      => 28,
            'sessions_trend'      => '+8% vs last 7 days',
            'active_days'         => '7 / 7',
            'active_days_note'    => 'Perfect!',
            'longest_session'     => '2h 45m',
            'longest_session_date'=> 'Jun 1, 2025',
        ];

        // 2. Study Time Over Time Trend (May 20 to Jun 2)
        $trendChart = [
            'granularity' => $granularity,
            'points' => [
                ['date' => 'May 20', 'label' => 'May 20', 'hours' => 2.0, 'duration' => '2h 00m'],
                ['date' => 'May 21', 'label' => 'May 21', 'hours' => 3.5, 'duration' => '3h 30m'],
                ['date' => 'May 22', 'label' => 'May 22', 'hours' => 4.8, 'duration' => '4h 48m'],
                ['date' => 'May 23', 'label' => 'May 23', 'hours' => 4.2, 'duration' => '4h 12m'],
                ['date' => 'May 24', 'label' => 'May 24', 'hours' => 3.8, 'duration' => '3h 48m'],
                ['date' => 'May 25', 'label' => 'May 25', 'hours' => 6.0, 'duration' => '6h 00m'],
                ['date' => 'May 26', 'label' => 'May 26', 'hours' => 5.8, 'duration' => '5h 48m'],
                ['date' => 'May 27', 'label' => 'May 27', 'hours' => 4.5, 'duration' => '4h 30m'],
                ['date' => 'May 28', 'label' => 'May 28', 'hours' => 5.2, 'duration' => '5h 12m'],
                ['date' => 'May 29', 'label' => 'May 29', 'hours' => 6.5, 'duration' => '6h 30m'],
                ['date' => 'May 30', 'label' => 'May 30', 'hours' => 5.0, 'duration' => '5h 00m'],
                ['date' => 'May 31', 'label' => 'May 31', 'hours' => 9.25, 'duration' => '9h 15m'], // Peak Tooltip
                ['date' => 'Jun 1',  'label' => 'Jun 1',  'hours' => 7.2, 'duration' => '7h 12m'],
                ['date' => 'Jun 2',  'label' => 'Jun 2',  'hours' => 5.5, 'duration' => '5h 30m'],
            ]
        ];

        // 3. Study Time by Activity (Donut)
        $activityBreakdown = [
            [
                'id' => 1,
                'name' => 'Watching Lessons',
                'percentage' => 60,
                'duration' => '29h 10m',
                'color' => '#8B5CF6',
                'bg_class' => 'bg-purple-500',
                'code' => '▶',
            ],
            [
                'id' => 2,
                'name' => 'Doing Quizzes',
                'percentage' => 20,
                'duration' => '9h 40m',
                'color' => '#3B82F6',
                'bg_class' => 'bg-blue-500',
                'code' => '📝',
            ],
            [
                'id' => 3,
                'name' => 'Practice & Exercises',
                'percentage' => 10,
                'duration' => '4h 50m',
                'color' => '#10B981',
                'bg_class' => 'bg-emerald-500',
                'code' => '💻',
            ],
            [
                'id' => 4,
                'name' => 'Reading Materials',
                'percentage' => 6,
                'duration' => '2h 55m',
                'color' => '#F59E0B',
                'bg_class' => 'bg-amber-500',
                'code' => '📖',
            ],
            [
                'id' => 5,
                'name' => 'Others',
                'percentage' => 4,
                'duration' => '1h 55m',
                'color' => '#EAB308',
                'bg_class' => 'bg-yellow-500',
                'code' => '⚡',
            ],
        ];

        // 4. Calendar Heatmap Matrix (5 Weeks x 7 Days: Mon-Sun)
        $calendarHeatmap = [
            'days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            'weeks' => [
                [
                    'label' => 'May 20 - 26',
                    'cells' => [
                        ['day' => 'Mon', 'date' => 'May 20', 'hours' => 2.0, 'level' => 1, 'sessions' => 2],
                        ['day' => 'Tue', 'date' => 'May 21', 'hours' => 3.5, 'level' => 2, 'sessions' => 3],
                        ['day' => 'Wed', 'date' => 'May 22', 'hours' => 4.8, 'level' => 2, 'sessions' => 4],
                        ['day' => 'Thu', 'date' => 'May 23', 'hours' => 4.2, 'level' => 2, 'sessions' => 3],
                        ['day' => 'Fri', 'date' => 'May 24', 'hours' => 3.8, 'level' => 2, 'sessions' => 3],
                        ['day' => 'Sat', 'date' => 'May 25', 'hours' => 6.0, 'level' => 3, 'sessions' => 5],
                        ['day' => 'Sun', 'date' => 'May 26', 'hours' => 5.8, 'level' => 3, 'sessions' => 4],
                    ]
                ],
                [
                    'label' => 'May 27 - Jun 2',
                    'cells' => [
                        ['day' => 'Mon', 'date' => 'May 27', 'hours' => 4.5, 'level' => 2, 'sessions' => 3],
                        ['day' => 'Tue', 'date' => 'May 28', 'hours' => 5.2, 'level' => 3, 'sessions' => 4],
                        ['day' => 'Wed', 'date' => 'May 29', 'hours' => 6.5, 'level' => 3, 'sessions' => 5],
                        ['day' => 'Thu', 'date' => 'May 30', 'hours' => 5.0, 'level' => 3, 'sessions' => 4],
                        ['day' => 'Fri', 'date' => 'May 31', 'hours' => 9.25, 'level' => 4, 'sessions' => 6],
                        ['day' => 'Sat', 'date' => 'Jun 1',  'hours' => 7.2, 'level' => 4, 'sessions' => 5],
                        ['day' => 'Sun', 'date' => 'Jun 2',  'hours' => 5.5, 'level' => 3, 'sessions' => 4],
                    ]
                ],
                [
                    'label' => 'Jun 3 - 9',
                    'cells' => [
                        ['day' => 'Mon', 'date' => 'Jun 3', 'hours' => 2.0, 'level' => 1, 'sessions' => 2],
                        ['day' => 'Tue', 'date' => 'Jun 4', 'hours' => 3.0, 'level' => 1, 'sessions' => 2],
                        ['day' => 'Wed', 'date' => 'Jun 5', 'hours' => 1.5, 'level' => 1, 'sessions' => 1],
                        ['day' => 'Thu', 'date' => 'Jun 6', 'hours' => 4.0, 'level' => 2, 'sessions' => 3],
                        ['day' => 'Fri', 'date' => 'Jun 7', 'hours' => 0.0, 'level' => 0, 'sessions' => 0],
                        ['day' => 'Sat', 'date' => 'Jun 8', 'hours' => 0.0, 'level' => 0, 'sessions' => 0],
                        ['day' => 'Sun', 'date' => 'Jun 9', 'hours' => 0.0, 'level' => 0, 'sessions' => 0],
                    ]
                ],
                [
                    'label' => 'Jun 10 - 16',
                    'cells' => [
                        ['day' => 'Mon', 'date' => 'Jun 10', 'hours' => 0.0, 'level' => 0, 'sessions' => 0],
                        ['day' => 'Tue', 'date' => 'Jun 11', 'hours' => 0.0, 'level' => 0, 'sessions' => 0],
                        ['day' => 'Wed', 'date' => 'Jun 12', 'hours' => 0.0, 'level' => 0, 'sessions' => 0],
                        ['day' => 'Thu', 'date' => 'Jun 13', 'hours' => 0.0, 'level' => 0, 'sessions' => 0],
                        ['day' => 'Fri', 'date' => 'Jun 14', 'hours' => 0.0, 'level' => 0, 'sessions' => 0],
                        ['day' => 'Sat', 'date' => 'Jun 15', 'hours' => 0.0, 'level' => 0, 'sessions' => 0],
                        ['day' => 'Sun', 'date' => 'Jun 16', 'hours' => 0.0, 'level' => 0, 'sessions' => 0],
                    ]
                ],
                [
                    'label' => 'Jun 17 - 23',
                    'cells' => [
                        ['day' => 'Mon', 'date' => 'Jun 17', 'hours' => 0.0, 'level' => 0, 'sessions' => 0],
                        ['day' => 'Tue', 'date' => 'Jun 18', 'hours' => 0.0, 'level' => 0, 'sessions' => 0],
                        ['day' => 'Wed', 'date' => 'Jun 19', 'hours' => 0.0, 'level' => 0, 'sessions' => 0],
                        ['day' => 'Thu', 'date' => 'Jun 20', 'hours' => 0.0, 'level' => 0, 'sessions' => 0],
                        ['day' => 'Fri', 'date' => 'Jun 21', 'hours' => 0.0, 'level' => 0, 'sessions' => 0],
                        ['day' => 'Sat', 'date' => 'Jun 22', 'hours' => 0.0, 'level' => 0, 'sessions' => 0],
                        ['day' => 'Sun', 'date' => 'Jun 23', 'hours' => 0.0, 'level' => 0, 'sessions' => 0],
                    ]
                ]
            ]
        ];

        // 5. Recent Learning Activity Table
        $recentActivities = [
            [
                'id' => 1,
                'title' => 'JavaScript Functions - Part 2',
                'description' => 'Learn about function parameters and return',
                'course' => 'JavaScript Fundamentals',
                'type' => 'Lesson',
                'duration' => '42m',
                'time' => 'Today, 10:30 AM',
                'progress' => 85,
                'icon' => '▶',
                'icon_bg' => 'from-purple-500 to-indigo-600',
                'progress_color' => 'bg-purple-500',
            ],
            [
                'id' => 2,
                'title' => 'JavaScript Functions Quiz',
                'description' => 'Test your knowledge on functions',
                'course' => 'JavaScript Fundamentals',
                'type' => 'Quiz',
                'duration' => '30m',
                'time' => 'Today, 09:15 AM',
                'progress' => 90,
                'icon' => '📝',
                'icon_bg' => 'from-blue-500 to-indigo-600',
                'progress_color' => 'bg-blue-500',
            ],
            [
                'id' => 3,
                'title' => 'Practice: Array Methods',
                'description' => 'Solve array manipulation problems',
                'course' => 'JavaScript Fundamentals',
                'type' => 'Practice',
                'duration' => '55m',
                'time' => 'Yesterday, 04:20 PM',
                'progress' => 75,
                'icon' => '💻',
                'icon_bg' => 'from-emerald-500 to-teal-600',
                'progress_color' => 'bg-emerald-500',
            ],
            [
                'id' => 4,
                'title' => 'React Components',
                'description' => 'Learn functional components',
                'course' => 'React.js Basics',
                'type' => 'Lesson',
                'duration' => '1h 05m',
                'time' => 'Yesterday, 02:30 PM',
                'progress' => 60,
                'icon' => '📙',
                'icon_bg' => 'from-purple-500 to-indigo-600',
                'progress_color' => 'bg-purple-500',
            ],
            [
                'id' => 5,
                'title' => 'React Components Quiz',
                'description' => 'Check your understanding',
                'course' => 'React.js Basics',
                'type' => 'Quiz',
                'duration' => '25m',
                'time' => 'Yesterday, 01:10 PM',
                'progress' => 70,
                'icon' => '📝',
                'icon_bg' => 'from-amber-500 to-orange-600',
                'progress_color' => 'bg-blue-500',
            ],
        ];

        // 6. This Week Overview (6 Metrics)
        $thisWeekOverview = [
            'date_range' => 'May 27 - Jun 2, 2025',
            'metrics' => [
                ['label' => 'Total Time', 'value' => '48h 30m', 'trend' => '+12% vs last week', 'is_positive' => true],
                ['label' => 'Sessions', 'value' => '28', 'trend' => '+8% vs last week', 'is_positive' => true],
                ['label' => 'Avg. Session', 'value' => '1h 43m', 'trend' => '+10% vs last week', 'is_positive' => true],
                ['label' => 'Active Days', 'value' => '7', 'trend' => '0% vs last week', 'is_positive' => true],
                ['label' => 'Completed Lessons', 'value' => '18', 'trend' => '+6% vs last week', 'is_positive' => true],
                ['label' => 'Quizzes Taken', 'value' => '12', 'trend' => '+9% vs last week', 'is_positive' => true],
            ]
        ];

        // 7. Learning Streak
        $learningStreak = [
            'current_streak' => 17,
            'best_streak' => 21,
            'best_streak_date' => 'May 2025',
            'days' => [
                ['label' => 'May 27', 'date' => '27', 'active' => true],
                ['label' => 'May 28', 'date' => '28', 'active' => true],
                ['label' => 'May 29', 'date' => '29', 'active' => true],
                ['label' => 'May 30', 'date' => '30', 'active' => true],
                ['label' => 'May 31', 'date' => '31', 'active' => true],
                ['label' => 'Jun 1',  'date' => '1',  'active' => true],
                ['label' => 'Jun 2',  'date' => '2',  'active' => true],
            ]
        ];

        // 8. Recent Achievements
        $recentAchievements = [
            [
                'id' => 1,
                'title' => 'Consistent Learner',
                'description' => 'Study 7 days in a row',
                'date' => 'May 31, 2025',
                'icon' => '🏆',
                'icon_bg' => 'from-emerald-500/20 to-teal-500/20 text-emerald-400 border border-emerald-500/30',
            ],
            [
                'id' => 2,
                'title' => 'Time Master',
                'description' => 'Study more than 40 hours',
                'date' => 'May 30, 2025',
                'icon' => '⏱',
                'icon_bg' => 'from-purple-500/20 to-indigo-500/20 text-purple-400 border border-purple-500/30',
            ],
            [
                'id' => 3,
                'title' => 'Early Bird',
                'description' => 'First session before 9 AM',
                'date' => 'May 28, 2025',
                'icon' => '🌅',
                'icon_bg' => 'from-amber-500/20 to-orange-500/20 text-amber-400 border border-amber-500/30',
            ],
        ];

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
}
