<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\QuizAttempt;
use App\Models\StudentGoal;
use App\Models\UserGoal;
use App\Models\StudyDaily;
use App\Models\AssignmentSubmission;
use App\Services\CourseProgressService;
use App\Services\LearningActivityService;
use App\Services\QuizAnalyticsService;
use App\Services\SkillAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class ProgressController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'overview');
        return Inertia::render('Student/ProgressTracking/Index', [
            'activeTab'    => $tab,
            'progress'     => LessonProgress::where('user_id', $request->user()->id)->with('lesson.course')->get(),
            'overviewData' => $this->buildOverviewPayload($request->user()),
        ]);
    }

    public function overview(Request $request)
    {
        return Inertia::render('Student/ProgressTracking/Overview', [
            'overviewData' => $this->buildOverviewPayload($request->user()),
        ]);
    }

    public function learningTime(Request $request)
    {
        $range = $request->query('range', '7d');
        $granularity = $request->query('granularity', 'daily');

        $analytics = app(LearningActivityService::class)->getStudentLearningActivity(
            $request->user(),
            $range,
            $granularity
        );

        return Inertia::render('Student/ProgressTracking/LearningTime', [
            'analytics' => $analytics,
            'filters'   => [
                'range'       => $range,
                'granularity' => $granularity,
            ],
        ]);
    }

    public function weeklyProgress(Request $request)
    {
        $period = $request->query('period', 'this_month');
        $granularity = $request->query('granularity', 'daily');

        $analytics = app(QuizAnalyticsService::class)->getStudentAnalytics(
            $request->user(),
            $period,
            $granularity
        );

        return Inertia::render('Student/ProgressTracking/WeeklyProgress', [
            'analytics' => $analytics,
            'filters'   => [
                'period'      => $period,
                'granularity' => $granularity,
            ],
        ]);
    }

    public function achievementsBadges(Request $request)
    {
        $category = $request->query('category', 'all');
        $sort = $request->query('sort', 'progress_desc');
        $tab = $request->query('tab', 'all');
        $period = $request->query('period', 'this_month');

        $analytics = app(SkillAnalyticsService::class)->getStudentSkillAnalytics(
            $request->user(),
            $category,
            $sort,
            $tab,
            $period
        );

        return Inertia::render('Student/ProgressTracking/AchievementsBadges', [
            'analytics' => $analytics,
            'filters'   => [
                'category' => $category,
                'sort'     => $sort,
                'tab'      => $tab,
                'period'   => $period,
            ],
        ]);
    }

    public function updateGoal(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'title'        => ['required', 'string', 'max:255'],
            'weekly_hours' => ['nullable', 'integer', 'min:1', 'max:60'],
            'target_date'  => ['nullable', 'date'],
        ]);

        $studentGoal = StudentGoal::where('user_id', $user->id)->first();
        if ($studentGoal) {
            $studentGoal->update([
                'title'     => $validated['title'],
                'title_km'  => $validated['title'],
                'goal_date' => $validated['target_date'] ?? $studentGoal->goal_date,
            ]);
        } else {
            StudentGoal::create([
                'user_id'      => $user->id,
                'title'        => $validated['title'],
                'title_km'     => $validated['title'],
                'is_completed' => false,
                'target_route' => '/student/progress/overview',
                'goal_date'    => $validated['target_date'] ?? now()->addMonths(6)->toDateString(),
            ]);
        }

        if (Schema::hasTable('user_goals')) {
            $userGoal = UserGoal::where('user_id', $user->id)->first();
            if ($userGoal && !empty($validated['weekly_hours'])) {
                $userGoal->update(['weekly_hours' => (int) $validated['weekly_hours']]);
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'ok'      => true,
                'message' => 'Learning Goal updated successfully!',
            ]);
        }

        return redirect()->back()->with('success', 'គោលដៅសិក្សាត្រូវបានរក្សាទុកដោយជោគជ័យ!');
    }

    private function buildOverviewPayload($user): array
    {
        if (!$user) {
            return [];
        }

        $enrollments = Enrollment::with(['course.lessons', 'course.modules.lessons'])
            ->where('student_id', $user->id)
            ->get();

        $progressMap = $enrollments->isNotEmpty()
            ? app(CourseProgressService::class)->calculateForEnrollments($user, $enrollments)
            : collect();

        $totalLessons = 0;
        $completedLessons = 0;
        $courseProgressList = [];
        $palette = [
            'from-purple-500 to-indigo-600 text-white',
            'from-cyan-500 to-blue-600 text-white',
            'from-emerald-500 to-green-600 text-white',
            'from-amber-400 to-amber-500 text-slate-950',
        ];

        foreach ($progressMap->values() as $idx => $item) {
            $course = $item['course'] ?? null;
            if (!$course) {
                continue;
            }
            $cTotal = (int) ($item['total_lessons'] ?? $course->lessons->count());
            $cDone = (int) ($item['completed_lessons'] ?? 0);
            $pct = (int) ($item['percent'] ?? 0);

            $totalLessons += $cTotal;
            $completedLessons += $cDone;

            $title = $course->title_kh ?: $course->title;
            $courseProgressList[] = [
                'id'           => $course->id,
                'title'        => $title,
                'level'        => ucfirst($course->level ?: 'beginner'),
                'lessonsCount' => $cTotal,
                'progress'     => $pct,
                'icon'         => mb_strtoupper(mb_substr($course->code ?: $title, 0, 2)),
                'iconBg'       => $palette[$idx % count($palette)],
                'status'       => $pct >= 100 ? 'completed' : 'in_progress',
                'href'         => "/student/courses/{$course->id}/learn",
            ];
        }

        $overallProgress = $totalLessons > 0 ? (int) round(($completedLessons / $totalLessons) * 100) : 0;

        $totalStudyDaily = 0;
        $weekStudyDaily = 0;
        if (Schema::hasTable('study_daily')) {
            try {
                $totalStudyDaily = (int) StudyDaily::where('user_id', $user->id)->sum('seconds');
                $weekStudyDaily = (int) StudyDaily::where('user_id', $user->id)
                    ->where('study_date', '>=', now()->startOfWeek()->toDateString())
                    ->sum('seconds');
            } catch (\Throwable $e) {}
        }
        $totalLessonSec = (int) LessonProgress::where('user_id', $user->id)->sum('seconds_watched');
        $weekLessonSec = (int) LessonProgress::where('user_id', $user->id)
            ->where('updated_at', '>=', now()->startOfWeek())
            ->sum('seconds_watched');

        $totalWatchedSeconds = max($totalStudyDaily, $totalLessonSec);
        $studyHours = intdiv($totalWatchedSeconds, 3600);
        $studyMins = intdiv($totalWatchedSeconds % 3600, 60);

        $certsCount = Certificate::where('student_id', $user->id)->whereIn('status', ['valid', 'issued'])->count();

        $quizAttempts = QuizAttempt::with('quiz')->where('user_id', $user->id)->whereNotNull('score')->latest()->get();
        $avgQuizScore = $quizAttempts->isNotEmpty() ? (int) round((float) $quizAttempts->avg('score')) : 0;
        $highestAttempt = $quizAttempts->sortByDesc('score')->first();
        $lowestAttempt = $quizAttempts->sortBy('score')->first();

        // Learning Goal: Integrate UserGoal (AI roadmap) and StudentGoal
        $userGoal = null;
        if (Schema::hasTable('user_goals')) {
            try {
                $userGoal = UserGoal::with('path')->where('user_id', $user->id)->first();
            } catch (\Throwable $e) {
                $userGoal = null;
            }
        }
        $studentGoal = StudentGoal::where('user_id', $user->id)->first();

        $goalTitle = $userGoal?->path?->title
            ?: ($studentGoal?->title ?: ($studentGoal?->title_km ?: 'កំណត់គោលដៅសិក្សារបស់អ្នក'));

        $targetHours = (int) ($userGoal?->weekly_hours ?: 10);
        $targetDateObj = null;
        if ($userGoal && $userGoal->started_at && $userGoal->target_months) {
            $targetDateObj = Carbon::parse($userGoal->started_at)->addMonths($userGoal->target_months);
        } elseif ($studentGoal?->goal_date) {
            $targetDateObj = Carbon::parse($studentGoal->goal_date);
        }

        $weekSeconds = max($weekStudyDaily, $weekLessonSec);
        $weekHoursFloat = $weekSeconds / 3600;
        $weeklyPct = $targetHours > 0 ? min(100, (int) round(($weekHoursFloat / $targetHours) * 100)) : 0;

        $recentActivities = [];
        foreach (LessonProgress::with('lesson')->where('user_id', $user->id)->whereNotNull('completed_at')->latest('completed_at')->take(4)->get() as $lp) {
            $recentActivities[] = [
                'id'     => 'lp-' . $lp->id,
                'title'  => 'Completed lesson',
                'detail' => $lp->lesson?->kh_title ?: ($lp->lesson?->title ?? 'Lesson'),
                'time'   => $lp->completed_at?->diffForHumans() ?? '',
                'icon'   => '✓',
                'iconBg' => 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30',
            ];
        }
        foreach ($quizAttempts->take(3) as $qa) {
            $recentActivities[] = [
                'id'     => 'qa-' . $qa->id,
                'title'  => 'Scored ' . (int) round((float) $qa->score) . '% on Quiz',
                'detail' => $qa->quiz?->title ?? 'Quiz',
                'time'   => ($qa->submitted_at ?? $qa->created_at)?->diffForHumans() ?? '',
                'icon'   => '📑',
                'iconBg' => 'bg-blue-500/20 text-blue-400 border border-blue-500/30',
            ];
        }

        $circumference = 163.36;
        $mkCat = function (string $name, int $pct, string $color) use ($circumference) {
            $clamped = max(0, min(100, $pct));
            return [
                'name'         => $name,
                'percent'      => $clamped,
                'color'        => $color,
                'strokeDash'   => (string) $circumference,
                'strokeOffset' => (string) round($circumference * (1 - ($clamped / 100)), 2),
            ];
        };

        $skillsData = app(SkillAnalyticsService::class)->getStudentSkillAnalytics($user);
        $skillsList = array_map(fn ($s) => [
            'name'           => $s['name'],
            'icon'           => $s['code'] ?? 'SK',
            'iconBg'         => $s['icon_bg'] ?? 'from-purple-600 to-indigo-600 text-white',
            'percent'        => (int) $s['progress'],
            'segments'       => 10,
            'activeSegments' => (int) round(((int) $s['progress']) / 10),
        ], array_slice($skillsData['skills'] ?? [], 0, 5));

        // Build 30-day real learning progress multi-line chart data
        $chartPoints = [];
        $chartLabels = [];
        $today = now();
        $allLessonProgress = LessonProgress::where('user_id', $user->id)->get();
        $allQuizAttempts = $quizAttempts;
        $allAssignments = collect();
        if (Schema::hasTable('assignment_submissions')) {
            try {
                $allAssignments = AssignmentSubmission::where('student_id', $user->id)
                    ->whereNotNull('score')
                    ->get();
            } catch (\Throwable $e) {}
        }

        for ($i = 29; $i >= 0; $i--) {
            $currentDay = $today->copy()->subDays($i);
            $dateStr = $currentDay->toDateString();

            $completedUpToDay = $totalLessons > 0
                ? $allLessonProgress->filter(function ($lp) use ($currentDay) {
                    $ts = $lp->completed_at ?? $lp->updated_at;
                    return $ts && $ts->lte($currentDay->endOfDay()) && ((int) $lp->percent >= 100 || $lp->completed_at !== null);
                })->count()
                : 0;
            $dayCoursePct = $totalLessons > 0 ? (int) round(($completedUpToDay / $totalLessons) * 100) : 0;

            $dayQuizAvg = $allQuizAttempts->filter(function ($qa) use ($currentDay) {
                $ts = $qa->submitted_at ?? $qa->created_at;
                return $ts && $ts->lte($currentDay->endOfDay());
            })->avg('score');
            $dayQuizPct = $dayQuizAvg !== null ? (int) round((float) $dayQuizAvg) : 0;

            $dayAssignAvg = $allAssignments->filter(function ($as) use ($currentDay) {
                $ts = $as->submitted_at ?? $as->created_at;
                return $ts && $ts->lte($currentDay->endOfDay());
            })->avg('score');
            $dayAssignPct = $dayAssignAvg !== null ? (int) round((float) $dayAssignAvg) : 0;

            $dayOverall = (int) round(($dayCoursePct + $dayQuizPct) / 2);

            $chartPoints[] = [
                'date'       => $currentDay->format('M j'),
                'iso_date'   => $dateStr,
                'overall'    => $dayOverall,
                'course'     => $dayCoursePct,
                'quiz'       => $dayQuizPct,
                'assignment' => $dayAssignPct,
            ];
        }

        for ($step = 0; $step <= 6; $step++) {
            $dayIndex = (int) round(($step / 6) * 29);
            $chartLabels[] = $chartPoints[$dayIndex]['date'];
        }

        // 31-day Quiz Bar data & labels
        $quizBars = [];
        $quizBarLabels = [];
        for ($i = 30; $i >= 0; $i--) {
            $d = now()->subDays($i);
            $dStr = $d->toDateString();
            $dayAvg = $quizAttempts->filter(fn ($a) => ($a->submitted_at ?? $a->created_at)?->toDateString() === $dStr)->avg('score');
            $quizBars[] = $dayAvg !== null ? (int) round((float) $dayAvg) : 0;
        }
        for ($step = 0; $step <= 6; $step++) {
            $dayIndex = (int) round(($step / 6) * 30);
            $d = now()->subDays(30 - $dayIndex);
            $quizBarLabels[] = $d->format('M j');
        }

        // Real Achievements Badges (Milestone-based)
        $achievements = [
            [
                'id'       => 'first_lesson',
                'title'    => 'ជំហានដំបូង',
                'subtitle' => $completedLessons >= 1 ? 'សម្រេចបាន' : 'បញ្ចប់ ១ មេរៀន',
                'unlocked' => $completedLessons >= 1,
                'icon'     => '🎯',
                'shieldBg' => $completedLessons >= 1
                    ? 'bg-gradient-to-b from-purple-500/20 to-indigo-600/30 border-purple-500/40 text-purple-300'
                    : 'bg-slate-100 dark:bg-slate-900/60 border-slate-300 dark:border-slate-800 text-slate-400 opacity-60',
            ],
            [
                'id'       => 'quiz_ace',
                'title'    => 'ជើងខ្លាំង Quiz',
                'subtitle' => ($highestAttempt && (float) $highestAttempt->score >= 80) ? 'សម្រេចបាន' : 'ពិន្ទុ Quiz ≥ 80%',
                'unlocked' => ($highestAttempt && (float) $highestAttempt->score >= 80),
                'icon'     => '🏆',
                'shieldBg' => ($highestAttempt && (float) $highestAttempt->score >= 80)
                    ? 'bg-gradient-to-b from-emerald-500/20 to-teal-600/30 border-emerald-500/40 text-emerald-300'
                    : 'bg-slate-100 dark:bg-slate-900/60 border-slate-300 dark:border-slate-800 text-slate-400 opacity-60',
            ],
            [
                'id'       => 'dedicated_scholar',
                'title'    => 'អ្នកសិក្សាឧស្សាហ៍',
                'subtitle' => ($studyHours >= 5 || $certsCount >= 1) ? 'សម្រេចបាន' : 'រៀនបាន ៥ ម៉ោង',
                'unlocked' => ($studyHours >= 5 || $certsCount >= 1),
                'icon'     => '⏱️',
                'shieldBg' => ($studyHours >= 5 || $certsCount >= 1)
                    ? 'bg-gradient-to-b from-amber-500/20 to-orange-600/30 border-amber-500/40 text-amber-300'
                    : 'bg-slate-100 dark:bg-slate-900/60 border-slate-300 dark:border-slate-800 text-slate-400 opacity-60',
            ],
        ];
        $unlockedCount = collect($achievements)->where('unlocked', true)->count();
        $achievementsNote = $unlockedCount < 3
            ? '🔥 នៅសល់ ' . (3 - $unlockedCount) . ' សមិទ្ធផលទៀតដែលអ្នកអាចសម្រេចបានក្នុងខែនេះ!'
            : '🎉 អស្ចារ្យណាស់! អ្នកបានសម្រេចសមិទ្ធផលគោលដៅទាំងអស់ហើយ!';

        return [
            'learningGoal' => [
                'title'             => $goalTitle,
                'progress'          => $overallProgress,
                'targetDate'        => $targetDateObj ? $targetDateObj->format('M d, Y') : '—',
                'timeRemaining'     => $targetDateObj ? $targetDateObj->diffForHumans() : '—',
                'weeklyTargetHours' => $targetHours,
            ],
            'topMetrics' => [
                'overallProgress'     => $overallProgress,
                'completedItems'      => $completedLessons,
                'totalItems'          => $totalLessons,
                'overallTrend'        => $overallProgress . '% completed',
                'coursesEnrolled'     => $enrollments->count(),
                'upcomingCompletions' => collect($courseProgressList)->where('progress', '>=', 70)->where('progress', '<', 100)->count(),
                'totalStudyTime'      => sprintf('%dh %02dm', $studyHours, $studyMins),
                'weeklyStudyIncrease' => sprintf('%dh %02dm this week', intdiv($weekSeconds, 3600), intdiv($weekSeconds % 3600, 60)),
                'certificatesEarned'  => $certsCount,
                'nextCertificate'     => collect($courseProgressList)->where('progress', '<', 100)->sortByDesc('progress')->first()['title'] ?? '—',
            ],
            'weeklyGoal' => [
                'percent'           => $weeklyPct,
                'currentHours'      => sprintf('%dh %02dm', intdiv($weekSeconds, 3600), intdiv($weekSeconds % 3600, 60)),
                'targetHours'       => $targetHours . 'h',
                'targetDescription' => "Study {$targetHours} hours this week",
            ],
            'categories' => [
                $mkCat('Courses', $overallProgress, '#8b5cf6'),
                $mkCat('Quizzes', $avgQuizScore, '#3b82f6'),
                $mkCat('Certificates', $enrollments->count() > 0 ? (int) round(($certsCount / $enrollments->count()) * 100) : 0, '#10b981'),
            ],
            'recentActivities'   => $recentActivities,
            'courseProgressList' => $courseProgressList,
            'skillsProgressList' => $skillsList,
            'achievements'       => $achievements,
            'achievementsNote'   => $achievementsNote,
            'learningProgressChart' => [
                'points' => $chartPoints,
                'labels' => $chartLabels,
            ],
            'quizStats'          => [
                'averageScore'     => $avgQuizScore,
                'averageTrend'     => $quizAttempts->count() . ' attempts',
                'quizzesTaken'     => $quizAttempts->count(),
                'quizzesTrend'     => 'All time',
                'highestScore'     => $highestAttempt ? (int) round((float) $highestAttempt->score) : 0,
                'highestScoreQuiz' => $highestAttempt?->quiz?->title ?? '—',
                'lowestScore'      => $lowestAttempt ? (int) round((float) $lowestAttempt->score) : 0,
                'lowestScoreQuiz'  => $lowestAttempt?->quiz?->title ?? '—',
            ],
            'quizBarData'   => $quizBars,
            'quizBarLabels' => $quizBarLabels,
        ];
    }
}
