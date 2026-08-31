<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AssessmentService
{
    /**
     * Get aggregated assessments, submissions, grading analytics, and history table.
     */
    public function getAssessmentsData(?User $user = null, string $status = 'all', string $course = 'all', string $dateRange = 'all', string $search = '', int $page = 1, int $perPage = 8): array
    {
        return $this->buildAssessmentsPayload($user, $status, $course, $dateRange, $search, $page, $perPage);
    }

    /**
     * Build structured assessments dashboard payload matching reference design.
     */
    protected function buildAssessmentsPayload(?User $user, string $status, string $course, string $dateRange, string $search, int $page, int $perPage): array
    {
        // 1. Summary Cards
        $summary = [
            'total_assignments' => 18,
            'total_note'        => 'All time assignments',
            'submitted'         => 14,
            'submitted_note'    => '78% of total',
            'graded'            => 10,
            'graded_note'       => '56% of total',
            'average_score'     => 82,
            'average_note'      => 'Good job! Keep it up.',
            'highest_score'     => 95,
            'highest_title'     => 'Web Development Project',
        ];

        // 2. Assessment Performance (Donut Chart)
        $performanceOverview = [
            'average_score' => 82,
            'distribution'  => [
                ['label' => '90 - 100%', 'count' => 3, 'percentage' => 30, 'color' => '#10B981', 'class' => 'text-emerald-400'],
                ['label' => '70 - 89%',  'count' => 5, 'percentage' => 50, 'color' => '#3B82F6', 'class' => 'text-blue-400'],
                ['label' => '50 - 69%',  'count' => 1, 'percentage' => 10, 'color' => '#F59E0B', 'class' => 'text-amber-400'],
                ['label' => 'Below 50%', 'count' => 1, 'percentage' => 10, 'color' => '#EF4444', 'class' => 'text-rose-400'],
            ],
        ];

        // 3. Score Trend (Line Chart)
        $scoreTrend = [
            'points' => [
                ['date' => 'May 1',  'percentage' => 50],
                ['date' => 'May 8',  'percentage' => 55],
                ['date' => 'May 15', 'percentage' => 65],
                ['date' => 'May 22', 'percentage' => 45],
                ['date' => 'May 29', 'percentage' => 80],
                ['date' => 'Jun 1',  'percentage' => 95],
            ],
            'highlight' => [
                'date'       => 'May 24, 2025',
                'score_text' => 'Score: 80%',
            ]
        ];

        // 4. Score by Course
        $scoreByCourse = [
            ['course' => 'Web Development',       'percentage' => 88, 'color' => 'from-cyan-400 to-blue-500'],
            ['course' => 'React Development',     'percentage' => 75, 'color' => 'from-cyan-400 to-blue-500'],
            ['course' => 'JavaScript Advanced',   'percentage' => 85, 'color' => 'from-purple-500 to-indigo-600'],
            ['course' => 'Database Design',       'percentage' => 45, 'color' => 'from-amber-400 to-orange-500'],
            ['course' => 'Python Programming',    'percentage' => 88, 'color' => 'from-cyan-400 to-blue-500'],
        ];

        // 5. Submission Status Donut
        $submissionStatus = [
            'total' => 18,
            'items' => [
                ['label' => 'Graded',    'count' => 10, 'percentage' => 56, 'color' => '#10B981'],
                ['label' => 'Submitted', 'count' => 4,  'percentage' => 22, 'color' => '#3B82F6'],
                ['label' => 'Pending',   'count' => 2,  'percentage' => 11, 'color' => '#F59E0B'],
                ['label' => 'Overdue',   'count' => 2,  'percentage' => 11, 'color' => '#EF4444'],
            ]
        ];

        // 6. Recent Feedback
        $recentFeedback = [
            [
                'id'       => 1,
                'title'    => 'Personal Portfolio Website',
                'score'    => 95,
                'feedback' => 'Excellent work! Clean code and great design.',
                'date'     => 'Jun 1, 2025',
                'icon_bg'  => 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30',
            ],
            [
                'id'       => 2,
                'title'    => 'React Components Project',
                'score'    => 75,
                'feedback' => 'Good job! Improve state management.',
                'date'     => 'May 27, 2025',
                'icon_bg'  => 'bg-amber-500/20 text-amber-300 border border-amber-500/30',
            ],
            [
                'id'       => 3,
                'title'    => 'Database Design Report',
                'score'    => 45,
                'feedback' => 'Review normalization and relationships.',
                'date'     => 'May 29, 2025',
                'icon_bg'  => 'bg-rose-500/20 text-rose-300 border border-rose-500/30',
            ],
        ];

        // 7. Upcoming Deadlines
        $upcomingDeadlines = [
            [
                'id'       => 1,
                'title'    => 'Node.js API Development',
                'due_text' => 'Due in 2 days',
                'due_date' => 'May 24, 2025',
                'icon_bg'  => 'bg-amber-500/20 text-amber-300 border border-amber-500/30',
            ],
            [
                'id'       => 2,
                'title'    => 'Git Workflow Assignment',
                'due_text' => 'Due in 4 days',
                'due_date' => 'May 22, 2025',
                'icon_bg'  => 'bg-rose-500/20 text-rose-300 border border-rose-500/30',
            ],
            [
                'id'       => 3,
                'title'    => 'Advanced CSS Project',
                'due_text' => 'Due in 6 days',
                'due_date' => 'May 20, 2025',
                'icon_bg'  => 'bg-blue-500/20 text-blue-300 border border-blue-500/30',
            ],
        ];

        // 8. Need Improvement Topics
        $weakTopics = [
            'Database Design Report',
            'Git Workflow Assignment',
        ];

        // 9. Assessments Table Catalog (8 items matching reference)
        $assessmentsTable = [
            [
                'id'           => 1,
                'title'        => 'Personal Portfolio Website',
                'subtitle'     => 'Build a responsive personal portfolio',
                'course'       => 'Web Development (Advanced)',
                'due_date'     => 'Jun 5, 2025 11:59 PM',
                'status'       => 'Graded',
                'status_type'  => 'graded',
                'score'        => '95%',
                'score_points' => '95 / 100',
                'submitted_on' => 'Jun 1, 2025 10:30 AM',
                'icon_bg'      => 'bg-purple-600/20 text-purple-300 border border-purple-500/30',
                'feedback'     => 'Excellent work! Clean code and great design.',
                'rubric'       => [
                    ['criteria' => 'Design & Responsiveness', 'score' => 25, 'max' => 25],
                    ['criteria' => 'Code Quality & Structure', 'score' => 24, 'max' => 25],
                    ['criteria' => 'Interactivity & Features', 'score' => 23, 'max' => 25],
                    ['criteria' => 'Documentation & Readme', 'score' => 23, 'max' => 25],
                ],
            ],
            [
                'id'           => 2,
                'title'        => 'JavaScript Functions Assignment',
                'subtitle'     => 'Implement and test JS functions',
                'course'       => 'JavaScript Advanced (Intermediate)',
                'due_date'     => 'Jun 3, 2025 11:59 PM',
                'status'       => 'Graded',
                'status_type'  => 'graded',
                'score'        => '85%',
                'score_points' => '85 / 100',
                'submitted_on' => 'May 31, 2025 09:15 PM',
                'icon_bg'      => 'bg-amber-500/20 text-amber-300 border border-amber-500/30',
                'feedback'     => 'Great implementation of arrow functions and scope closures.',
            ],
            [
                'id'           => 3,
                'title'        => 'Database Design Report',
                'subtitle'     => 'Design a database for e-commerce',
                'course'       => 'Database Design (Intermediate)',
                'due_date'     => 'May 30, 2025 11:59 PM',
                'status'       => 'Graded',
                'status_type'  => 'graded',
                'score'        => '45%',
                'score_points' => '45 / 100',
                'submitted_on' => 'May 29, 2025 08:45 PM',
                'icon_bg'      => 'bg-blue-500/20 text-blue-300 border border-blue-500/30',
                'feedback'     => 'Review normalization and foreign key constraints.',
            ],
            [
                'id'           => 4,
                'title'        => 'React Components Project',
                'subtitle'     => 'Build a component-based app',
                'course'       => 'React Development (Advanced)',
                'due_date'     => 'May 28, 2025 11:59 PM',
                'status'       => 'Returned',
                'status_type'  => 'returned',
                'score'        => '75%',
                'score_points' => '75 / 100',
                'submitted_on' => 'May 27, 2025 04:20 PM',
                'icon_bg'      => 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30',
                'feedback'     => 'Good job! Improve state management and resubmit.',
            ],
            [
                'id'           => 5,
                'title'        => 'UI/UX Case Study',
                'subtitle'     => 'Analyze and improve UI/UX',
                'course'       => 'UI/UX Design (Beginner)',
                'due_date'     => 'May 25, 2025 11:59 PM',
                'status'       => 'Graded',
                'status_type'  => 'graded',
                'score'        => '80%',
                'score_points' => '80 / 100',
                'submitted_on' => 'May 24, 2025 07:30 PM',
                'icon_bg'      => 'bg-rose-500/20 text-rose-300 border border-rose-500/30',
                'feedback'     => 'Clear wireframing and user journey documentation.',
            ],
            [
                'id'           => 6,
                'title'        => 'Node.js API Development',
                'subtitle'     => 'Create RESTful API with Node.js',
                'course'       => 'Backend Development (Advanced)',
                'due_date'     => 'May 24, 2025 11:59 PM',
                'status'       => 'Pending',
                'status_type'  => 'pending',
                'score'        => '-',
                'score_points' => '-',
                'submitted_on' => '-',
                'icon_bg'      => 'bg-purple-500/20 text-purple-300 border border-purple-500/30',
                'can_upload'   => true,
            ],
            [
                'id'           => 7,
                'title'        => 'Git Workflow Assignment',
                'subtitle'     => 'Implement git branching workflow',
                'course'       => 'DevOps Tools (Intermediate)',
                'due_date'     => 'May 22, 2025 11:59 PM',
                'status'       => 'Overdue',
                'status_type'  => 'overdue',
                'score'        => '-',
                'score_points' => '-',
                'submitted_on' => '-',
                'icon_bg'      => 'bg-orange-500/20 text-orange-300 border border-orange-500/30',
                'can_upload'   => true,
            ],
            [
                'id'           => 8,
                'title'        => 'Python Data Analysis',
                'subtitle'     => 'Analyze dataset using Python',
                'course'       => 'Python Programming (Intermediate)',
                'due_date'     => 'May 21, 2025 11:59 PM',
                'status'       => 'Graded',
                'status_type'  => 'graded',
                'score'        => '88%',
                'score_points' => '88 / 100',
                'submitted_on' => 'May 20, 2025 06:40 PM',
                'icon_bg'      => 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30',
                'feedback'     => 'Great pandas and matplotlib visualization techniques.',
            ],
        ];

        // Filter by status tab
        if ($status !== 'all') {
            $assessmentsTable = array_values(array_filter($assessmentsTable, fn($a) => strtolower($a['status_type']) === strtolower($status)));
        }

        // Filter by course
        if ($course !== 'all') {
            $assessmentsTable = array_values(array_filter($assessmentsTable, fn($a) => str_contains(strtolower($a['course']), strtolower($course))));
        }

        // Filter by search
        if (!empty($search)) {
            $s = strtolower($search);
            $assessmentsTable = array_values(array_filter($assessmentsTable, fn($a) => str_contains(strtolower($a['title']), $s) || str_contains(strtolower($a['course']), $s)));
        }

        return [
            'summary'              => $summary,
            'performance_overview' => $performanceOverview,
            'score_trend'          => $scoreTrend,
            'score_by_course'      => $scoreByCourse,
            'submission_status'    => $submissionStatus,
            'recent_feedback'      => $recentFeedback,
            'upcoming_deadlines'   => $upcomingDeadlines,
            'weak_topics'          => $weakTopics,
            'assessments'          => $assessmentsTable,
            'total_count'          => 18,
            'current_page'         => $page,
            'per_page'             => $perPage,
        ];
    }
}
