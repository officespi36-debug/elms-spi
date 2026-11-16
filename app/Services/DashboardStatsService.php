<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardStatsService
{
    public function __construct(
        protected CourseProgressService $courseProgressService,
        protected ContinueLearningService $continueLearningService
    ) {}

    /**
     * Invalidate cached dashboard stats for a specific student.
     */
    public static function forgetForUser(int $userId): void
    {
        Cache::forget("student_dashboard_stats_{$userId}");
        Cache::forget("student_dashboard_payload_{$userId}");
        Cache::forget("student_stats_{$userId}");
    }

    /**
     * Build and cache the single-source-of-truth dashboard payload for a student.
     */
    public function getDashboardData(User $user, bool $forceRefresh = false): array
    {
        $cacheKey = "student_dashboard_stats_{$user->id}";

        if ($forceRefresh) {
            self::forgetForUser($user->id);
        }

        return Cache::remember($cacheKey, 300, function () use ($user) {
            return $this->buildPayload($user);
        });
    }

    /**
     * Compute lightweight stats summary (used by apiStats & Dashboard).
     */
    public function computeStats(User $user): array
    {
        $data = $this->getDashboardData($user);
        return $data['stats'];
    }

    protected function buildPayload(User $user): array
    {
        // 1. Eager-loaded enrollments & CourseProgressService (0 N+1 queries)
        try {
            $enrollments = Enrollment::where('student_id', $user->id)
                ->whereIn('status', ['active', 'completed'])
                ->select(['id', 'student_id', 'course_id', 'status', 'enrolled_at', 'updated_at'])
                ->with([
                    'course:id,title,code,teacher_id,major_id,thumbnail,status',
                    'course.teacher:id,name,name_kh,email,avatar',
                    'course.major:id,name',
                    'course.lessons:id,course_id,title,order,duration_seconds',
                    'course.modules:id,course_id,title,order',
                    'course.modules.lessons:id,module_id,course_id,title,order,duration_seconds',
                ])
                ->latest('updated_at')
                ->get();
        } catch (\Throwable $e) {
            $enrollments = collect();
        }

        $computedCollection = $this->courseProgressService->calculateForEnrollments($user, $enrollments);

        $enrolledCount = $computedCollection->count();
        $inProgressCount = $computedCollection->where('state', 'in_progress')->count();
        $completedCount = $computedCollection->where('state', 'completed')->count();

        $myCoursesList = $computedCollection->map(function ($item) {
            $course = $item['course'];
            $teacherName = $this->courseProgressService->cleanTeacherName($course->teacher, $course);
            $status = $item['state'];

            return [
                'id'           => $course->id,
                'title'        => $course->title,
                'code'         => $course->code,
                'category'     => $course->major->name ?? 'General Studies',
                'teacher'      => $teacherName,
                'progress'     => $item['percent'],
                'lessonsDone'  => $item['lessons_done'],
                'lessonsTotal' => $item['lessons_total'],
                'status'       => $status,
                'statusLabel'  => $item['state_label'],
                'thumbnail'    => $course->thumbnail,
                'href'         => "/student/courses/{$course->id}/overview",
                'continueHref' => "/student/my-courses/{$course->id}/continue",
            ];
        })->values()->all();

        // 2. Real certificates count from DB
        try {
            $certsCount = Certificate::where('student_id', $user->id)
                ->whereIn('status', ['valid', 'issued'])
                ->count();
        } catch (\Throwable $e) {
            $certsCount = 0;
        }

        // 3. Real study time from lesson_progress + quiz_attempts (no fake 58/28/14% split)
        $lessonsSeconds = 0;
        $quizSeconds = 0;
        $practiceSeconds = 0;
        $dailyBars = [];
        $thisWeekSeconds = 0;
        $prevWeekSeconds = 0;
        $activeDaysLast7 = 0;

        try {
            $lessonsSeconds = (int) LessonProgress::where('user_id', $user->id)->sum('seconds_watched');

            $now = Carbon::now();
            $sevenDaysAgo = $now->copy()->subDays(6)->startOfDay();
            $fourteenDaysAgo = $now->copy()->subDays(13)->startOfDay();

            $recentProgressRows = LessonProgress::where('user_id', $user->id)
                ->where('updated_at', '>=', $fourteenDaysAgo)
                ->select(['seconds_watched', 'updated_at'])
                ->get();

            $dayBuckets = [];
            for ($i = 6; $i >= 0; $i--) {
                $d = $now->copy()->subDays($i);
                $dayBuckets[$d->format('Y-m-d')] = [
                    'date'    => $d->format('Y-m-d'),
                    'label'   => $d->format('D'),
                    'seconds' => 0,
                ];
            }

            foreach ($recentProgressRows as $row) {
                if (!$row->updated_at) {
                    continue;
                }
                $dt = Carbon::parse($row->updated_at);
                $secs = max(0, (int) $row->seconds_watched);
                $dateKey = $dt->format('Y-m-d');

                if ($dt->gte($sevenDaysAgo)) {
                    $thisWeekSeconds += $secs;
                    if (isset($dayBuckets[$dateKey])) {
                        $dayBuckets[$dateKey]['seconds'] += $secs;
                    }
                } else {
                    $prevWeekSeconds += $secs;
                }
            }

            foreach ($dayBuckets as &$bucket) {
                if ($bucket['seconds'] > 0) {
                    $activeDaysLast7++;
                }
                $bucket['minutes'] = (int) round($bucket['seconds'] / 60);
                $bucket['hours'] = round($bucket['seconds'] / 3600, 1);
            }
            unset($bucket);

            $dailyBars = array_values($dayBuckets);
        } catch (\Throwable $e) {
            $lessonsSeconds = 0;
            $dailyBars = [];
        }

        // Quiz attempts: real average, highest, count, and real duration
        $quizAvgNum = null;
        $quizMaxNum = null;
        $quizAttemptsCount = 0;
        $quizTrend = null;

        try {
            $quizRows = QuizAttempt::where('user_id', $user->id)
                ->select(['id', 'quiz_id', 'score', 'passed', 'started_at', 'submitted_at', 'created_at'])
                ->with(['quiz:id,course_id,title', 'quiz.course:id,title'])
                ->latest('submitted_at')
                ->get();

            $scoredRows = $quizRows->whereNotNull('score')->values();
            $quizAttemptsCount = $scoredRows->count();

            if ($quizAttemptsCount > 0) {
                $quizAvgNum = (int) round($scoredRows->avg('score'));
                $quizMaxNum = (int) round($scoredRows->max('score'));
                if ($quizAttemptsCount >= 2) {
                    $diff = (int) round($scoredRows[0]->score - $scoredRows[1]->score);
                    $quizTrend = ($diff >= 0 ? "+{$diff}%" : "{$diff}%") . ' vs last test';
                }
            }

            foreach ($quizRows as $qa) {
                if ($qa->started_at && $qa->submitted_at) {
                    $diffSec = Carbon::parse($qa->submitted_at)->diffInSeconds(Carbon::parse($qa->started_at));
                    $quizSeconds += max(0, min(7200, $diffSec));
                }
            }
        } catch (\Throwable $e) {
            $quizRows = collect();
            $quizAvgNum = null;
            $quizMaxNum = null;
            $quizAttemptsCount = 0;
        }

        // Exact minute-consistent breakdown so total === lessons + practice + quiz
        $lessonsMin = (int) round($lessonsSeconds / 60);
        $practiceMin = (int) round($practiceSeconds / 60);
        $quizMin = (int) round($quizSeconds / 60);
        $totalMin = $lessonsMin + $practiceMin + $quizMin;
        $totalSeconds = $lessonsSeconds + $practiceSeconds + $quizSeconds;

        $formatMinutes = static function (int $mins): string {
            $h = intdiv($mins, 60);
            $m = $mins % 60;
            return "{$h}h {$m}m";
        };

        $dailyAvgHours = round(($thisWeekSeconds / 7) / 3600, 1);
        $weekTrendPct = $prevWeekSeconds > 0
            ? (int) round((($thisWeekSeconds - $prevWeekSeconds) / $prevWeekSeconds) * 100)
            : null;

        $studyBreakdown = [
            'total'           => $formatMinutes($totalMin),
            'totalMinutes'    => $totalMin,
            'lessons'         => $formatMinutes($lessonsMin),
            'lessonsMinutes'  => $lessonsMin,
            'practice'        => $formatMinutes($practiceMin),
            'practiceMinutes' => $practiceMin,
            'quiz'            => $formatMinutes($quizMin),
            'quizMinutes'     => $quizMin,
            'dailyAverage'    => $thisWeekSeconds > 0 ? "{$dailyAvgHours}h / day" : '0h / day',
            'thisWeekTotal'   => $formatMinutes((int) round($thisWeekSeconds / 60)),
            'weekTrendPct'    => $weekTrendPct,
            'dailyBars'       => $dailyBars,
        ];

        $quizPerformanceData = [
            'hasData'         => $quizAttemptsCount > 0,
            'attemptsCount'   => $quizAttemptsCount,
            'averageScore'    => $quizAvgNum !== null ? "{$quizAvgNum}%" : '—',
            'averageScoreNum' => $quizAvgNum,
            'highestScore'    => $quizMaxNum !== null ? "{$quizMaxNum}%" : '—',
            'trend'           => $quizTrend,
        ];

        // 4. Continue Learning (unified ContinueLearningService)
        $continueCourseData = null;
        $currentData = $this->continueLearningService->resolve($user);

        if ($currentData && isset($currentData['course'])) {
            $activeCourse = $currentData['course'];
            $currentLesson = $currentData['lesson'] ?? null;
            $activeCourseItem = collect($myCoursesList)->firstWhere('id', $activeCourse->id);

            $continueCourseData = [
                'id'           => $activeCourse->id,
                'title'        => $activeCourse->title,
                'chapter'      => $activeCourse->major->name ?? 'Core Academic Studies',
                'teacher'      => $activeCourseItem['teacher'] ?? $this->courseProgressService->cleanTeacherName($activeCourse->teacher, $activeCourse),
                'progress'     => $currentData['progress_percent'] ?? 0,
                'nextLesson'   => $currentLesson?->title ?? (!empty($currentData['all_completed']) ? 'All lessons completed' : 'Course overview'),
                'timeLeft'     => $currentData['time_left'] ?? null,
                'thumbnail'    => $activeCourse->thumbnail,
                'href'         => $currentLesson
                    ? "/student/my-courses/{$activeCourse->id}/lessons/{$currentLesson->id}"
                    : "/student/my-courses/{$activeCourse->id}/continue",
                'overviewHref' => "/student/courses/{$activeCourse->id}/overview",
            ];
        }

        // 5. Recent Activity & Today's Goal from unified activity source
        $recentActivitiesList = [];
        try {
            $recentLessons = LessonProgress::where('user_id', $user->id)
                ->select(['id', 'user_id', 'lesson_id', 'percent', 'seconds_watched', 'completed_at', 'updated_at'])
                ->with(['lesson:id,course_id,title', 'lesson.course:id,title'])
                ->latest('updated_at')
                ->take(4)
                ->get();

            foreach ($recentLessons as $lp) {
                if ($lp->lesson && $lp->lesson->course) {
                    $recentActivitiesList[] = [
                        'id'        => 'lp-' . $lp->id,
                        'type'      => 'lesson',
                        'title'     => $lp->lesson->title,
                        'course'    => $lp->lesson->course->title,
                        'time'      => $lp->updated_at ? $lp->updated_at->diffForHumans() : 'Recently',
                        'href'      => "/student/my-courses/{$lp->lesson->course_id}/lessons/{$lp->lesson_id}",
                        'timestamp' => $lp->updated_at ? $lp->updated_at->timestamp : 0,
                        'progress'  => (int) ($lp->percent ?? 0),
                        'completed' => !empty($lp->completed_at) || ($lp->percent ?? 0) >= 90,
                    ];
                }
            }

            if (isset($quizRows)) {
                foreach ($quizRows->take(3) as $qa) {
                    if ($qa->quiz && $qa->quiz->course) {
                        $recentActivitiesList[] = [
                            'id'        => 'qa-' . $qa->id,
                            'type'      => 'quiz',
                            'title'     => $qa->quiz->title . ' (' . round((float) $qa->score) . '%)',
                            'course'    => $qa->quiz->course->title,
                            'time'      => $qa->submitted_at ? Carbon::parse($qa->submitted_at)->diffForHumans() : 'Recently',
                            'href'      => '/student/quizzes/scores',
                            'timestamp' => $qa->submitted_at ? Carbon::parse($qa->submitted_at)->timestamp : 0,
                            'progress'  => (int) round((float) ($qa->score ?? 0)),
                            'completed' => (bool) $qa->passed,
                        ];
                    }
                }
            }

            usort($recentActivitiesList, fn ($a, $b) => $b['timestamp'] <=> $a['timestamp']);
            $recentActivitiesList = array_slice($recentActivitiesList, 0, 5);
        } catch (\Throwable $e) {
            $recentActivitiesList = [];
        }

        // Today's goal built from the same ContinueLearningService + recent activity state
        $todayGoalItems = [];
        if ($continueCourseData) {
            $todayGoalItems[] = [
                'id'          => 'next-lesson-' . $continueCourseData['id'],
                'titleEn'     => "{$continueCourseData['title']} — {$continueCourseData['nextLesson']}",
                'titleKm'     => "{$continueCourseData['title']} — {$continueCourseData['nextLesson']}",
                'completed'   => false,
                'targetRoute' => $continueCourseData['href'],
            ];
        }
        $todayCompletedCount = count(array_filter($recentActivitiesList, function ($act) {
            return $act['completed'] && $act['timestamp'] >= Carbon::today()->timestamp;
        }));
        if ($enrolledCount > 0) {
            $todayGoalItems[] = [
                'id'          => 'daily-quiz',
                'titleEn'     => 'Complete 1 practice quiz session',
                'titleKm'     => 'ធ្វើកម្រងសំណួរអនុវត្តចំនួន ១ វគ្គ',
                'completed'   => $todayCompletedCount > 0 && $quizAttemptsCount > 0,
                'targetRoute' => '/student/quizzes/practice',
            ];
        }
        $goalDone = count(array_filter($todayGoalItems, fn ($g) => $g['completed']));
        $goalTotal = count($todayGoalItems);

        $todayGoalsData = [
            'completedCount' => $goalDone,
            'totalCount'     => $goalTotal,
            'percentage'     => $goalTotal > 0 ? (int) round(($goalDone / $goalTotal) * 100) : 0,
            'items'          => $todayGoalItems,
        ];

        // 6. Upcoming Quizzes (strictly from enrolled courses)
        $upcomingEventsList = [];
        try {
            $activeCourseIds = $enrollments->where('status', 'active')->pluck('course_id');
            if ($activeCourseIds->isNotEmpty()) {
                $quizzes = Quiz::whereIn('course_id', $activeCourseIds)
                    ->select(['id', 'course_id', 'title', 'duration_minutes'])
                    ->with('course:id,title')
                    ->take(3)
                    ->get();

                foreach ($quizzes as $q) {
                    $upcomingEventsList[] = [
                        'id'     => $q->id,
                        'title'  => $q->title,
                        'course' => $q->course->title ?? 'Course',
                        'meta'   => ($q->duration_minutes ?: 30) . ' min',
                        'href'   => '/student/quizzes/practice',
                    ];
                }
            }
        } catch (\Throwable $e) {
            $upcomingEventsList = [];
        }

        // 7. Recommended (with clear AI label, grounded in real weak quiz or active course)
        $aiRecommendationsData = [];
        if ($quizAttemptsCount > 0 && $quizAvgNum !== null && $quizAvgNum < 85) {
            $weakAttempt = $quizRows->whereNotNull('score')->sortBy('score')->first();
            if ($weakAttempt && $weakAttempt->quiz) {
                $aiRecommendationsData[] = [
                    'id'          => 'ai-weak-' . $weakAttempt->id,
                    'title'       => 'Review ' . $weakAttempt->quiz->title,
                    'title_km'    => 'រំលឹកឡើងវិញ ' . $weakAttempt->quiz->title,
                    'desc'        => 'Recent score: ' . round((float) $weakAttempt->score) . '%',
                    'desc_km'     => 'ពិន្ទុថ្មីៗ៖ ' . round((float) $weakAttempt->score) . '%',
                    'actionLabel' => 'Practice',
                    'actionHref'  => '/student/quizzes/practice',
                ];
            }
        } elseif ($continueCourseData) {
            $aiRecommendationsData[] = [
                'id'          => 'ai-next-' . $continueCourseData['id'],
                'title'       => 'Focus on ' . $continueCourseData['nextLesson'],
                'title_km'    => 'ផ្តោតលើ ' . $continueCourseData['nextLesson'],
                'desc'        => $continueCourseData['title'],
                'desc_km'     => $continueCourseData['title'],
                'actionLabel' => 'Open',
                'actionHref'  => $continueCourseData['href'],
            ];
        }

        // 8. Achievements Rule Engine (Strict rules + honest progress string)
        $studyMinutesTotal = (int) floor($totalSeconds / 60);
        $achievementsData = [
            [
                'id'       => 'streak',
                'title'    => '7-Day Streak',
                'title_km' => 'រៀន ៧ ថ្ងៃជាប់',
                'unlocked' => $activeDaysLast7 >= 7,
                'progress' => "{$activeDaysLast7} of 7 days",
                'href'     => '/student/certificates/achievements',
            ],
            [
                'id'       => 'quick_learner',
                'title'    => 'Quick Learner',
                'title_km' => 'បញ្ចប់វគ្គសិក្សា',
                'unlocked' => $completedCount >= 1,
                'progress' => "{$completedCount} of 1 course",
                'href'     => '/student/certificates/achievements',
            ],
            [
                'id'       => 'quiz_master',
                'title'    => 'Quiz Master',
                'title_km' => 'ពិន្ទុ Quiz ខ្ពស់',
                'unlocked' => $quizAttemptsCount >= 1 && $quizAvgNum !== null && $quizAvgNum >= 75,
                'progress' => $quizAvgNum !== null ? "{$quizAvgNum}% of 75%" : '0 of 1 quiz',
                'href'     => '/student/certificates/achievements',
            ],
            [
                'id'       => 'early_bird',
                'title'    => 'Focused Hour',
                'title_km' => 'សិក្សាគ្រប់ ១ ម៉ោង',
                'unlocked' => $studyMinutesTotal >= 60,
                'progress' => min(60, $studyMinutesTotal) . ' of 60 min',
                'href'     => '/student/certificates/achievements',
            ],
            [
                'id'       => 'top_performer',
                'title'    => 'Top Performer',
                'title_km' => 'លទ្ធផលឆ្នើម',
                'unlocked' => $completedCount >= 1 && $quizAvgNum !== null && $quizAvgNum >= 70,
                'progress' => "{$completedCount}/1 course · " . ($quizAvgNum !== null ? "{$quizAvgNum}%/70%" : 'No quiz'),
                'href'     => '/student/certificates/achievements',
            ],
        ];

        $stats = [
            'enrolledCount'     => $enrolledCount,
            'inProgressCount'   => $inProgressCount,
            'completedCount'    => $completedCount,
            'certificatesCount' => $certsCount,
            'learningTime'      => $studyBreakdown['total'],
            'totalSeconds'      => $totalSeconds,
            'averageScore'      => $quizPerformanceData['averageScore'],
            'averageScoreNum'   => $quizAvgNum,
        ];

        return [
            'stats'              => $stats,
            'continueCourse'     => $continueCourseData,
            'dbCourses'          => $myCoursesList,
            'recentActivities'   => $recentActivitiesList,
            'upcomingEvents'     => $upcomingEventsList,
            'todayGoals'         => $todayGoalsData,
            'studyTimeBreakdown' => $studyBreakdown,
            'quizPerformance'    => $quizPerformanceData,
            'aiRecommendations'  => $aiRecommendationsData,
            'achievements'       => $achievementsData,
        ];
    }
}
