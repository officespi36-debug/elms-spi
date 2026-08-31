<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AvailableQuizService
{
    /**
     * Get available quizzes and analytics data.
     */
    public function getAvailableQuizzesData(?User $user = null, string $category = 'all', string $course = 'all', string $difficulty = 'all', string $status = 'available', string $search = ''): array
    {
        $realQuizzes = collect();
        $realAttempts = collect();

        if ($user) {
            try {
                $realQuizzes = Quiz::with('course')->get();
                $realAttempts = QuizAttempt::where('user_id', $user->id)->get();
            } catch (\Throwable $e) {
                \Log::warning('AvailableQuizService DB error: ' . $e->getMessage());
            }
        }

        return $this->buildAvailableQuizzesPayload($user, $realQuizzes, $realAttempts, $category, $course, $difficulty, $status, $search);
    }

    /**
     * Build aggregated available quizzes dataset.
     */
    protected function buildAvailableQuizzesPayload(?User $user, Collection $realQuizzes, Collection $realAttempts, string $category, string $course, string $difficulty, string $status, string $search): array
    {
        // 1. Summary Cards
        $summary = [
            'available_quizzes' => 24,
            'available_note'    => 'Quizzes ready to take',
            'practice_quizzes'  => 18,
            'practice_note'     => 'Improve your skills',
            'assessments'       => 6,
            'assessments_note'  => 'Test your knowledge',
            'total_points'      => 1450,
            'points_note'       => 'Points you can earn',
        ];

        // 2. Practice Quizzes Catalog
        $practiceQuizzes = [
            [
                'id'              => 1,
                'title'           => 'JavaScript Basics Quiz',
                'course'          => 'JavaScript Fundamentals',
                'category'        => 'Front-End',
                'difficulty'      => 'Easy',
                'questions_count' => 20,
                'time_limit'      => 20,
                'passing_score'   => 70,
                'points'          => 100,
                'code'            => 'JS',
                'icon_bg'         => 'from-amber-400 to-amber-500 text-slate-950',
                'badge_color'     => 'emerald',
                'description'     => 'Test your understanding of JavaScript basics including variables, data types.',
                'instructions'    => 'You will have 20 minutes to complete 20 multiple-choice questions. Score 70% or higher to earn 100 Points.',
                'status'          => 'available',
            ],
            [
                'id'              => 2,
                'title'           => 'React Components Quiz',
                'course'          => 'React.js Fundamentals',
                'category'        => 'Front-End',
                'difficulty'      => 'Medium',
                'questions_count' => 25,
                'time_limit'      => 30,
                'passing_score'   => 70,
                'points'          => 125,
                'code'            => '⚛️',
                'icon_bg'         => 'from-cyan-400 to-blue-500 text-white',
                'badge_color'     => 'amber',
                'description'     => 'Test your knowledge of React components, props, and state management.',
                'instructions'    => '25 conceptual questions on functional components, useState, useEffect, and prop drilling.',
                'status'          => 'available',
            ],
            [
                'id'              => 3,
                'title'           => 'HTML & CSS Quiz',
                'course'          => 'Web Development',
                'category'        => 'Front-End',
                'difficulty'      => 'Easy',
                'questions_count' => 15,
                'time_limit'      => 20,
                'passing_score'   => 70,
                'points'          => 75,
                'code'            => '5',
                'icon_bg'         => 'from-orange-500 to-amber-500 text-white',
                'badge_color'     => 'emerald',
                'description'     => 'Test your HTML and CSS knowledge including tags, selectors, and layout.',
                'instructions'    => '15 questions covering semantic HTML5 tags, flexbox layout, and CSS grid selectors.',
                'status'          => 'available',
            ],
            [
                'id'              => 4,
                'title'           => 'SQL Queries Quiz',
                'course'          => 'Database Systems',
                'category'        => 'Database',
                'difficulty'      => 'Hard',
                'questions_count' => 30,
                'time_limit'      => 35,
                'passing_score'   => 75,
                'points'          => 150,
                'code'            => '🗄️',
                'icon_bg'         => 'from-blue-500 to-indigo-600 text-white',
                'badge_color'     => 'rose',
                'description'     => 'Test your SQL skills including SELECT, JOIN, WHERE, and GROUP BY.',
                'instructions'    => '30 query-analysis problems involving subqueries, aggregate grouping, and indexing.',
                'status'          => 'available',
            ],
        ];

        // 3. Assessments Catalog
        $assessments = [
            [
                'id'              => 101,
                'title'           => 'JavaScript Assessment',
                'course'          => 'Advanced JavaScript',
                'category'        => 'Front-End',
                'difficulty'      => 'Hard',
                'questions_count' => 50,
                'time_limit'      => 60,
                'passing_score'   => 80,
                'points'          => 250,
                'icon'            => '🛡️',
                'icon_bg'         => 'from-purple-600 to-indigo-600',
                'badge_color'     => 'rose',
                'description'     => 'Comprehensive assessment covering advanced JavaScript concepts.',
                'instructions'    => 'Official midterm assessment for Advanced JavaScript. Requires 80% passing grade.',
                'status'          => 'available',
            ],
            [
                'id'              => 102,
                'title'           => 'Full Stack Assessment',
                'course'          => 'Full Stack Development',
                'category'        => 'Full Stack',
                'difficulty'      => 'Expert',
                'questions_count' => 100,
                'time_limit'      => 120,
                'passing_score'   => 85,
                'points'          => 500,
                'icon'            => '</>',
                'icon_bg'         => 'from-emerald-500 to-teal-600',
                'badge_color'     => 'amber',
                'description'     => 'Complete full stack assessment covering frontend, backend, and database.',
                'instructions'    => 'Comprehensive 100-question full stack certification exam.',
                'status'          => 'available',
            ],
            [
                'id'              => 103,
                'title'           => 'React Developer Test',
                'course'          => 'React Development',
                'category'        => 'Front-End',
                'difficulty'      => 'Hard',
                'questions_count' => 60,
                'time_limit'      => 75,
                'passing_score'   => 80,
                'points'          => 300,
                'icon'            => '🛡️',
                'icon_bg'         => 'from-blue-600 to-indigo-600',
                'badge_color'     => 'rose',
                'description'     => 'Test your React development skills and best practices.',
                'instructions'    => 'Covers Redux Toolkit, Context API, SSR hydration, and performance memoization.',
                'status'          => 'available',
            ],
        ];

        // 4. Upcoming Quizzes
        $upcomingQuizzes = [
            [
                'id'           => 201,
                'title'        => 'TypeScript Basics Quiz',
                'course'       => 'TypeScript Fundamentals',
                'available_in' => '2 days',
                'points'       => 50,
                'icon'         => '📅',
                'icon_bg'      => 'bg-blue-500/20 text-blue-300 border border-blue-500/30',
            ],
            [
                'id'           => 202,
                'title'        => 'Node.js Quiz',
                'course'       => 'Backend Development',
                'available_in' => '5 days',
                'points'       => 100,
                'icon'         => '🟢',
                'icon_bg'      => 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30',
            ],
            [
                'id'           => 203,
                'title'        => 'API Development Quiz',
                'course'       => 'Backend Development',
                'available_in' => '1 week',
                'points'       => 125,
                'icon'         => '🔥',
                'icon_bg'      => 'bg-orange-500/20 text-orange-300 border border-orange-500/30',
            ],
        ];

        // 5. Your Quiz Progress (Right Widget 1)
        $quizProgress = [
            'average_score'      => 72,
            'quizzes_count'      => 16,
            'quizzes_trend'      => '+4 this month',
            'correct_answers'    => 78,
            'correct_trend'      => '+6%',
        ];

        // 6. Recommended For You (Weak Topics)
        $recommendedQuizzes = [
            [
                'id'          => 301,
                'title'       => 'JavaScript Functions Quiz',
                'note'        => 'Low score in Functions',
                'score'       => 28,
                'color_class' => 'text-rose-400 font-bold',
                'icon'        => '📙',
            ],
            [
                'id'          => 302,
                'title'       => 'DOM Manipulation Quiz',
                'note'        => 'Improve DOM skills',
                'score'       => 35,
                'color_class' => 'text-rose-400 font-bold',
                'icon'        => '💻',
            ],
            [
                'id'          => 303,
                'title'       => 'Array Methods Quiz',
                'note'        => 'Practice array methods',
                'score'       => 42,
                'color_class' => 'text-amber-400 font-bold',
                'icon'        => '📙',
            ],
            [
                'id'          => 304,
                'title'       => 'Async JavaScript Quiz',
                'note'        => 'Learn async concepts',
                'score'       => 50,
                'color_class' => 'text-amber-400 font-bold',
                'icon'        => '🟢',
            ],
        ];

        // 7. Quiz Streak
        $quizStreak = [
            'streak_days' => 7,
            'days' => [
                ['label' => 'May 26', 'date' => '26', 'active' => true],
                ['label' => 'May 27', 'date' => '27', 'active' => true],
                ['label' => 'May 28', 'date' => '28', 'active' => true],
                ['label' => 'May 29', 'date' => '29', 'active' => true],
                ['label' => 'May 30', 'date' => '30', 'active' => true],
                ['label' => 'May 31', 'date' => '31', 'active' => true],
                ['label' => 'Jun 1',  'date' => '1',  'active' => true],
            ]
        ];

        // Filter practice quizzes by search/category/difficulty if provided
        if (!empty($search)) {
            $s = strtolower($search);
            $practiceQuizzes = array_values(array_filter($practiceQuizzes, function ($q) use ($s) {
                return str_contains(strtolower($q['title']), $s) ||
                       str_contains(strtolower($q['course']), $s) ||
                       str_contains(strtolower($q['description']), $s);
            }));
        }

        if ($category !== 'all') {
            $practiceQuizzes = array_values(array_filter($practiceQuizzes, fn($q) => strtolower($q['category']) === strtolower($category)));
        }

        if ($difficulty !== 'all') {
            $practiceQuizzes = array_values(array_filter($practiceQuizzes, fn($q) => strtolower($q['difficulty']) === strtolower($difficulty)));
        }

        return [
            'summary'              => $summary,
            'practice_quizzes'     => $practiceQuizzes,
            'assessments'          => $assessments,
            'upcoming_quizzes'     => $upcomingQuizzes,
            'quiz_progress'        => $quizProgress,
            'recommended_quizzes'  => $recommendedQuizzes,
            'quiz_streak'          => $quizStreak,
        ];
    }
}
