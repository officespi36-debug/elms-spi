<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\QuizAttempt;
use App\Models\StudentGoal;
use App\Services\CourseProgressService;
use App\Services\LearningActivityService;
use App\Services\QuizAnalyticsService;
use App\Services\SkillAnalyticsService;
use Illuminate\Http\Request;
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
        $totalWatchedSeconds = (int) LessonProgress::where('user_id', $user->id)->sum('seconds_watched');
        $studyHours = intdiv($totalWatchedSeconds, 3600);
        $studyMins = intdiv($totalWatchedSeconds % 3600, 60);

        $certsCount = Certificate::where('student_id', $user->id)->whereIn('status', ['valid', 'issued'])->count();

        $quizAttempts = QuizAttempt::with('quiz')->where('user_id', $user->id)->whereNotNull('score')->latest()->get();
        $avgQuizScore = $quizAttempts->isNotEmpty() ? (int) round((float) $quizAttempts->avg('score')) : 0;
        $highestAttempt = $quizAttempts->sortByDesc('score')->first();
        $lowestAttempt = $quizAttempts->sortBy('score')->first();

        $goal = StudentGoal::where('user_id', $user->id)->first();
        $targetHours = (int) ($goal?->weekly_target_hours ?? 10);
        $weekSeconds = (int) LessonProgress::where('user_id', $user->id)
            ->where('updated_at', '>=', now()->startOfWeek())
            ->sum('seconds_watched');
        $weekHoursFloat = $weekSeconds / 3600;
        $weeklyPct = $targetHours > 0 ? min(100, (int) round(($weekHoursFloat / $targetHours) * 100)) : 0;

        $recentActivities = [];
        foreach (LessonProgress::with('lesson')->where('user_id', $user->id)->whereNotNull('completed_at')->latest('completed_at')->take(3)->get() as $lp) {
            $recentActivities[] = [
                'id'     => 'lp-' . $lp->id,
                'title'  => 'Completed lesson',
                'detail' => $lp->lesson?->kh_title ?: ($lp->lesson?->title ?? 'Lesson'),
                'time'   => $lp->completed_at?->diffForHumans() ?? '',
                'icon'   => '✓',
                'iconBg' => 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30',
            ];
        }
        foreach ($quizAttempts->take(2) as $qa) {
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

        $quizBars = [];
        for ($i = 30; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $dayAvg = $quizAttempts->filter(fn ($a) => ($a->submitted_at ?? $a->created_at)?->toDateString() === $d)->avg('score');
            $quizBars[] = $dayAvg !== null ? (int) round((float) $dayAvg) : 0;
        }

        return [
            'learningGoal' => [
                'title'             => $goal?->title ?: 'កំណត់គោលដៅសិក្សារបស់អ្នក',
                'progress'          => $overallProgress,
                'targetDate'        => $goal?->target_date ? \Carbon\Carbon::parse($goal->target_date)->format('M d, Y') : '—',
                'timeRemaining'     => $goal?->target_date ? \Carbon\Carbon::parse($goal->target_date)->diffForHumans() : '—',
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
            'achievements'       => [],
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
            'quizBarData' => $quizBars,
        ];
    }
}
