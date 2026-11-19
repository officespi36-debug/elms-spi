<?php

namespace App\Services;

use App\Models\AssignmentSubmission;
use App\Models\Deadline;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\User;
use Carbon\Carbon;

class AssessmentService
{
    /**
     * Get aggregated assessments, submissions, grading analytics, and history table from 100% real DB records.
     */
    public function getAssessmentsData(
        ?User $user = null,
        string $status = 'all',
        string $course = 'all',
        string $dateRange = 'all',
        string $search = '',
        int $page = 1,
        int $perPage = 8
    ): array {
        if (!$user) {
            return $this->emptyPayload($page, $perPage);
        }

        $enrolledCourseIds = Enrollment::where('student_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->pluck('course_id')
            ->unique()
            ->filter()
            ->values()
            ->all();

        // If the student has no active enrollments yet (e.g. newly registered, or admin inspecting portal),
        // fallback to all published courses so they can explore available assignments
        if (empty($enrolledCourseIds)) {
            $enrolledCourseIds = Course::where('status', 'published')->pluck('id')->all();
        }

        $submissions = AssignmentSubmission::where('user_id', $user->id)
            ->with(['quiz.course', 'course'])
            ->latest('submitted_at')
            ->get();

        if ($submissions->isNotEmpty()) {
            $enrolledCourseIds = array_values(array_unique(array_merge(
                $enrolledCourseIds,
                $submissions->pluck('course_id')->filter()->all(),
                $submissions->pluck('quiz.course_id')->filter()->all()
            )));
        }

        if (empty($enrolledCourseIds) && $submissions->isEmpty()) {
            return $this->emptyPayload($page, $perPage);
        }

        $assignmentQuizzes = empty($enrolledCourseIds)
            ? collect()
            : Quiz::whereIn('course_id', $enrolledCourseIds)
                ->where('type', 'assignment')
                ->where(function ($q) {
                    $q->where('status', 'published')->orWhereNull('status');
                })
                ->with(['course', 'module'])
                ->latest()
                ->get();

        $deadlines = empty($enrolledCourseIds)
            ? collect()
            : Deadline::whereIn('course_id', $enrolledCourseIds)
                ->with('course:id,title')
                ->orderBy('due_at')
                ->get();

        $deadlinesByQuiz = $deadlines
            ->where('linked_type', 'quiz')
            ->keyBy('linked_id');

        $submissionsByQuiz = $submissions->whereNotNull('quiz_id')->keyBy('quiz_id');

        $allRows = [];
        $gradedScores = [];
        $gradedRows = [];
        $courseScoresMap = [];

        foreach ($assignmentQuizzes as $quiz) {
            /** @var AssignmentSubmission|null $sub */
            $sub = $submissionsByQuiz->get($quiz->id);
            /** @var Deadline|null $dl */
            $dl = $deadlinesByQuiz->get($quiz->id);

            $courseTitle = $quiz->course?->title ?? 'Course';
            $dueAt = $dl?->due_at;
            $dueDateStr = $dueAt ? $dueAt->format('M j, Y g:i A') : 'No due date';

            $statusType = 'pending';
            $statusLabel = 'Pending';
            $scoreDisplay = '-';
            $scorePointsDisplay = '-';
            $submittedOnDisplay = '-';
            $scoreNumeric = null;

            if ($sub) {
                $submittedOnDisplay = $sub->submitted_at
                    ? $sub->submitted_at->format('M j, Y g:i A')
                    : ($sub->created_at ? $sub->created_at->format('M j, Y g:i A') : '-');

                if ($sub->status === 'returned') {
                    $statusType = 'returned';
                    $statusLabel = 'Returned';
                } elseif ($sub->status === 'graded' || $sub->graded_at !== null || $sub->score !== null) {
                    $statusType = 'graded';
                    $statusLabel = 'Graded';
                } else {
                    $statusType = 'submitted';
                    $statusLabel = 'Submitted';
                }

                if ($sub->score !== null) {
                    $scoreNumeric = (int) round((float) $sub->score);
                    $scoreDisplay = "{$scoreNumeric}%";
                    $scorePointsDisplay = "{$scoreNumeric} / 100";
                    $gradedScores[] = [
                        'title' => $quiz->title,
                        'score' => $scoreNumeric,
                        'date'  => $sub->graded_at ?? $sub->submitted_at ?? $sub->updated_at,
                    ];
                    $gradedRows[] = [
                        'id'       => $sub->id,
                        'title'    => $quiz->title,
                        'score'    => $scoreNumeric,
                        'feedback' => $sub->feedback,
                        'date'     => ($sub->graded_at ?? $sub->submitted_at ?? $sub->updated_at)?->format('M j, Y') ?? '',
                    ];
                    $courseScoresMap[$courseTitle][] = $scoreNumeric;
                }
            } elseif ($dueAt && $dueAt->isPast()) {
                $statusType = 'overdue';
                $statusLabel = 'Overdue';
            }

            $allRows[] = [
                'id'           => $quiz->id,
                'quiz_id'      => $quiz->id,
                'submission_id'=> $sub?->id,
                'title'        => $quiz->title,
                'subtitle'     => $quiz->module?->title ?: $courseTitle,
                'course'       => $courseTitle,
                'due_date'     => $dueDateStr,
                'due_at_raw'   => $dueAt?->toIso8601String(),
                'status'       => $statusLabel,
                'status_type'  => $statusType,
                'score'        => $scoreDisplay,
                'score_points' => $scorePointsDisplay,
                'score_num'    => $scoreNumeric,
                'submitted_on' => $submittedOnDisplay,
                'submitted_at_raw' => $sub?->submitted_at?->toIso8601String(),
                'file_name'    => $sub?->file_name,
                'file_path'    => $sub?->file_path,
                'file_size'    => $sub?->file_size,
                'notes'        => $sub?->notes,
                'icon_bg'      => 'bg-purple-600/20 text-purple-300 border border-purple-500/30',
                'feedback'     => $sub?->feedback,
                'can_upload'   => in_array($statusType, ['pending', 'overdue', 'returned', 'submitted'], true),
            ];
        }

        $totalAssignments = count($allRows);
        $submittedCount = count(array_filter($allRows, fn ($r) => in_array($r['status_type'], ['submitted', 'graded', 'returned'], true)));
        $gradedCount = count(array_filter($allRows, fn ($r) => $r['status_type'] === 'graded'));
        $pendingCount = count(array_filter($allRows, fn ($r) => $r['status_type'] === 'pending'));
        $overdueCount = count(array_filter($allRows, fn ($r) => $r['status_type'] === 'overdue'));
        $onlySubmittedUnGraded = max(0, $submittedCount - $gradedCount);

        $avgScore = !empty($gradedScores)
            ? (int) round(collect($gradedScores)->avg('score'))
            : 0;

        $highestItem = !empty($gradedScores)
            ? collect($gradedScores)->sortByDesc('score')->first()
            : null;

        $submittedPct = $totalAssignments > 0 ? (int) round(($submittedCount / $totalAssignments) * 100) : 0;
        $gradedPct = $totalAssignments > 0 ? (int) round(($gradedCount / $totalAssignments) * 100) : 0;

        $summary = [
            'total_assignments' => $totalAssignments,
            'total_note'        => $totalAssignments > 0 ? 'Enrolled course assignments' : 'No assignments yet',
            'submitted'         => $submittedCount,
            'submitted_note'    => "{$submittedPct}% of total",
            'graded'            => $gradedCount,
            'graded_note'       => "{$gradedPct}% of total",
            'average_score'     => $avgScore,
            'average_note'      => !empty($gradedScores) ? ($avgScore >= 70 ? 'Good job! Keep it up.' : 'Keep practicing!') : 'No graded assignments',
            'highest_score'     => $highestItem ? $highestItem['score'] : 0,
            'highest_title'     => $highestItem ? $highestItem['title'] : '-',
        ];

        // Performance overview buckets (from graded assignments)
        $gTotal = count($gradedScores);
        $b90 = count(array_filter($gradedScores, fn ($g) => $g['score'] >= 90));
        $b70 = count(array_filter($gradedScores, fn ($g) => $g['score'] >= 70 && $g['score'] < 90));
        $b50 = count(array_filter($gradedScores, fn ($g) => $g['score'] >= 50 && $g['score'] < 70));
        $bLow = count(array_filter($gradedScores, fn ($g) => $g['score'] < 50));

        $performanceOverview = [
            'average_score' => $avgScore,
            'distribution'  => [
                ['label' => '90 - 100%', 'count' => $b90,  'percentage' => $gTotal > 0 ? (int) round(($b90 / $gTotal) * 100) : 0,  'color' => '#10B981', 'class' => 'text-emerald-400'],
                ['label' => '70 - 89%',  'count' => $b70,  'percentage' => $gTotal > 0 ? (int) round(($b70 / $gTotal) * 100) : 0,  'color' => '#3B82F6', 'class' => 'text-blue-400'],
                ['label' => '50 - 69%',  'count' => $b50,  'percentage' => $gTotal > 0 ? (int) round(($b50 / $gTotal) * 100) : 0,  'color' => '#F59E0B', 'class' => 'text-amber-400'],
                ['label' => 'Below 50%', 'count' => $bLow, 'percentage' => $gTotal > 0 ? (int) round(($bLow / $gTotal) * 100) : 0, 'color' => '#EF4444', 'class' => 'text-rose-400'],
            ],
        ];

        // Score trend
        $sortedGraded = collect($gradedScores)
            ->sortBy(fn ($g) => $g['date'] ? Carbon::parse($g['date'])->timestamp : 0)
            ->values();

        $trendPoints = $sortedGraded->take(-6)->map(fn ($g) => [
            'date'       => $g['date'] ? Carbon::parse($g['date'])->format('M j') : 'Recent',
            'percentage' => $g['score'],
        ])->values()->all();

        $lastTrend = $sortedGraded->last();
        $scoreTrend = [
            'points'    => $trendPoints,
            'highlight' => $lastTrend ? [
                'date'       => $lastTrend['date'] ? Carbon::parse($lastTrend['date'])->format('M j, Y') : 'Latest',
                'score_text' => "Score: {$lastTrend['score']}%",
            ] : null,
        ];

        // Score by course
        $scoreByCourse = [];
        foreach ($courseScoresMap as $cTitle => $scoresArr) {
            $cAvg = (int) round(array_sum($scoresArr) / count($scoresArr));
            $scoreByCourse[] = [
                'course'     => $cTitle,
                'percentage' => $cAvg,
                'color'      => $cAvg >= 70 ? 'from-cyan-400 to-blue-500' : 'from-amber-400 to-orange-500',
            ];
        }

        // Submission Status Donut
        $submissionStatus = [
            'total' => $totalAssignments,
            'items' => [
                ['label' => 'Graded',    'count' => $gradedCount,           'percentage' => $totalAssignments > 0 ? (int) round(($gradedCount / $totalAssignments) * 100) : 0,           'color' => '#10B981'],
                ['label' => 'Submitted', 'count' => $onlySubmittedUnGraded, 'percentage' => $totalAssignments > 0 ? (int) round(($onlySubmittedUnGraded / $totalAssignments) * 100) : 0, 'color' => '#3B82F6'],
                ['label' => 'Pending',   'count' => $pendingCount,          'percentage' => $totalAssignments > 0 ? (int) round(($pendingCount / $totalAssignments) * 100) : 0,          'color' => '#F59E0B'],
                ['label' => 'Overdue',   'count' => $overdueCount,          'percentage' => $totalAssignments > 0 ? (int) round(($overdueCount / $totalAssignments) * 100) : 0,          'color' => '#EF4444'],
            ],
        ];

        // Recent feedback
        $recentFeedback = collect($gradedRows)
            ->filter(fn ($r) => !empty($r['feedback']))
            ->take(4)
            ->map(fn ($r) => [
                'id'       => $r['id'],
                'title'    => $r['title'],
                'score'    => $r['score'],
                'feedback' => $r['feedback'],
                'date'     => $r['date'],
                'icon_bg'  => $r['score'] >= 80
                    ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30'
                    : ($r['score'] >= 50
                        ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30'
                        : 'bg-rose-500/20 text-rose-300 border border-rose-500/30'),
            ])
            ->values()
            ->all();

        // Upcoming deadlines
        $upcomingDeadlines = $deadlines
            ->filter(fn (Deadline $d) => $d->due_at && $d->due_at->isFuture())
            ->take(4)
            ->map(fn (Deadline $d) => [
                'id'       => $d->id,
                'title'    => $d->title,
                'due_text' => 'Due ' . $d->due_at->diffForHumans(),
                'due_date' => $d->due_at->format('M j, Y'),
                'icon_bg'  => 'bg-amber-500/20 text-amber-300 border border-amber-500/30',
            ])
            ->values()
            ->all();

        // Weak topics (graded assignments < 70% or overdue)
        $weakTopics = collect($allRows)
            ->filter(fn ($r) => ($r['score_num'] !== null && $r['score_num'] < 70) || $r['status_type'] === 'overdue')
            ->pluck('title')
            ->unique()
            ->take(4)
            ->values()
            ->all();

        // Filters
        $filtered = $allRows;
        if ($status !== 'all') {
            $filtered = array_values(array_filter($filtered, fn ($a) => strcasecmp($a['status_type'], $status) === 0));
        }
        if ($course !== 'all') {
            $filtered = array_values(array_filter($filtered, fn ($a) => str_contains(mb_strtolower($a['course'], 'UTF-8'), mb_strtolower($course, 'UTF-8'))));
        }
        if ($dateRange !== 'all') {
            $now = Carbon::now();
            $filtered = array_values(array_filter($filtered, function ($a) use ($dateRange, $now) {
                $targetDate = null;
                if (!empty($a['submitted_at_raw'])) {
                    $targetDate = Carbon::parse($a['submitted_at_raw']);
                } elseif (!empty($a['due_at_raw'])) {
                    $targetDate = Carbon::parse($a['due_at_raw']);
                }

                if (!$targetDate) {
                    return true;
                }

                return match ($dateRange) {
                    'this_week'  => $targetDate->between($now->copy()->startOfWeek(), $now->copy()->endOfWeek()),
                    'this_month' => $targetDate->between($now->copy()->startOfMonth(), $now->copy()->endOfMonth()),
                    'past_month' => $targetDate->between($now->copy()->subMonth()->startOfMonth(), $now->copy()->subMonth()->endOfMonth()),
                    'past_30'    => $targetDate->greaterThanOrEqualTo($now->copy()->subDays(30)),
                    default      => true,
                };
            }));
        }
        if ($search !== '') {
            $s = mb_strtolower(trim($search), 'UTF-8');
            $filtered = array_values(array_filter($filtered, fn ($a) => str_contains(mb_strtolower($a['title'], 'UTF-8'), $s) || str_contains(mb_strtolower($a['course'], 'UTF-8'), $s)));
        }

        $filteredTotal = count($filtered);
        $safePage = max(1, $page);
        $offset = ($safePage - 1) * $perPage;
        $pagedRows = array_slice($filtered, $offset, $perPage);

        $coursesList = Course::whereIn('id', $enrolledCourseIds)->pluck('title')->filter()->unique()->values()->all();
        if (empty($coursesList)) {
            $coursesList = collect($allRows)->pluck('course')->filter()->unique()->values()->all();
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
            'assessments'          => $pagedRows,
            'total_count'          => $filteredTotal,
            'current_page'         => $safePage,
            'per_page'             => $perPage,
            'courses'              => $coursesList,
        ];
    }

    protected function emptyPayload(int $page = 1, int $perPage = 8): array
    {
        return [
            'summary'              => [
                'total_assignments' => 0,
                'total_note'        => 'No assignments yet',
                'submitted'         => 0,
                'submitted_note'    => '0% of total',
                'graded'            => 0,
                'graded_note'       => '0% of total',
                'average_score'     => 0,
                'average_note'      => 'No graded assignments',
                'highest_score'     => 0,
                'highest_title'     => '-',
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
            'submission_status'    => [
                'total' => 0,
                'items' => [
                    ['label' => 'Graded',    'count' => 0, 'percentage' => 0, 'color' => '#10B981'],
                    ['label' => 'Submitted', 'count' => 0, 'percentage' => 0, 'color' => '#3B82F6'],
                    ['label' => 'Pending',   'count' => 0, 'percentage' => 0, 'color' => '#F59E0B'],
                    ['label' => 'Overdue',   'count' => 0, 'percentage' => 0, 'color' => '#EF4444'],
                ],
            ],
            'recent_feedback'      => [],
            'upcoming_deadlines'   => [],
            'weak_topics'          => [],
            'assessments'          => [],
            'total_count'          => 0,
            'current_page'         => $page,
            'per_page'             => $perPage,
            'courses'              => [],
        ];
    }
}
