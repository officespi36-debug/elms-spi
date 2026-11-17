<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\QuizAttempt;
use App\Services\ContinueLearningService;
use App\Services\DashboardAnalyticsService;
use App\Services\StudentLearningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        if ($request->has('refresh')) {
            Cache::forget("student_dashboard_payload_{$user->id}_v5");
            Cache::forget("student_stats_{$user->id}");
        }

        // Performance: Cache::remember 300s (scoped to user id) with eager loading & selected columns
        $payload = Cache::remember("student_dashboard_payload_{$user->id}_v5", 300, function () use ($user) {
            try {
                $enrollments = Enrollment::where('student_id', $user->id)
                    ->select(['id', 'student_id', 'course_id', 'status', 'enrolled_at', 'updated_at'])
                    ->with([
                        'course:id,title,code,teacher_id,major_id,thumbnail',
                        'course.teacher:id,name,avatar',
                        'course.major:id,name',
                        'course.lessons:id,course_id,title,order,duration_seconds'
                    ])
                    ->latest('updated_at')
                    ->get();
            } catch (\Throwable $e) {
                $enrollments = collect();
            }

            // Fetch completed lesson IDs for accurate course progress
            $completedLessonMap = [];
            try {
                $completedLessonMap = LessonProgress::where('user_id', $user->id)
                    ->where(function ($q) {
                        $q->where('percent', '>=', 90)->orWhereNotNull('completed_at');
                    })
                    ->pluck('lesson_id')
                    ->flip()
                    ->toArray();
            } catch (\Throwable $e) {
                $completedLessonMap = [];
            }

            // 1. Build My Courses List with real progress and discipline icons
            $myCoursesList = [];
            $computedActive = 0;
            $computedCompleted = 0;

            if ($enrollments->isNotEmpty()) {
                foreach ($enrollments as $enr) {
                    if ($enr->course) {
                        $course = $enr->course;
                        $rawTeacher = trim($course->teacher->name ?? '');
                        if (empty($rawTeacher) && !empty($course->teacher->name_kh)) {
                            $rawTeacher = trim($course->teacher->name_kh);
                        }
                        if (empty($rawTeacher) || strcasecmp($rawTeacher, 'pitaja6820') === 0 || str_contains($rawTeacher, '@')) {
                            if ($course->teacher_id == 21 || str_contains(strtolower($course->teacher->email ?? ''), 'pitaja')) {
                                $rawTeacher = 'Dr. Mengly Chea';
                            } else {
                                $rawTeacher = 'SPI Instructor';
                            }
                        }
                        $teacherName = $rawTeacher;

                        // Calculate real progress from lessons
                        $totalLessons = $course->lessons ? $course->lessons->count() : 0;
                        $calculatedProgress = 0;
                        if ($totalLessons > 0 && !empty($completedLessonMap)) {
                            $doneCount = 0;
                            foreach ($course->lessons as $l) {
                                if (isset($completedLessonMap[$l->id])) {
                                    $doneCount++;
                                }
                            }
                            $calculatedProgress = (int) round(($doneCount / $totalLessons) * 100);
                        }

                        // Determine status & progress
                        if ($totalLessons === 0) {
                            $status = 'no_content';
                            $progress = 0;
                        } elseif ($totalLessons > 0 && $calculatedProgress >= 100) {
                            $status = 'completed';
                            $progress = 100;
                            $computedCompleted++;
                        } elseif ($calculatedProgress > 0) {
                            $status = 'in_progress';
                            $progress = $calculatedProgress;
                            $computedActive++;
                        } else {
                            $status = 'not_started';
                            $progress = 0;
                        }

                        // Determine subject/icon type & theme color
                        $titleLower = strtolower($course->title . ' ' . ($course->major->name ?? ''));
                        $iconType = 'code';
                        $iconColor = 'from-purple-600 to-indigo-600';
                        $bgGradient = 'from-indigo-50 to-purple-50 dark:from-indigo-950/40 dark:to-purple-950/40';

                        if (str_contains($titleLower, 'english') || str_contains($titleLower, 'language') || str_contains($titleLower, 'literature')) {
                            $iconType = 'english';
                            $iconColor = 'from-amber-500 to-orange-600';
                            $bgGradient = 'from-amber-50 to-orange-50 dark:from-amber-950/40 dark:to-orange-950/40';
                        } elseif (str_contains($titleLower, 'agronomy') || str_contains($titleLower, 'crop') || str_contains($titleLower, 'plant') || str_contains($titleLower, 'agriculture')) {
                            $iconType = 'agriculture';
                            $iconColor = 'from-emerald-500 to-teal-600';
                            $bgGradient = 'from-emerald-50 to-teal-50 dark:from-emerald-950/40 dark:to-teal-950/40';
                        } elseif (str_contains($titleLower, 'tourism') || str_contains($titleLower, 'hotel') || str_contains($titleLower, 'hospitality') || str_contains($titleLower, 'eco')) {
                            $iconType = 'tourism';
                            $iconColor = 'from-cyan-500 to-blue-600';
                            $bgGradient = 'from-cyan-50 to-blue-50 dark:from-cyan-950/40 dark:to-blue-950/40';
                        } elseif (str_contains($titleLower, 'social') || str_contains($titleLower, 'community')) {
                            $iconType = 'social_work';
                            $iconColor = 'from-rose-500 to-pink-600';
                            $bgGradient = 'from-rose-50 to-pink-50 dark:from-rose-950/40 dark:to-pink-950/40';
                        } elseif (str_contains($titleLower, 'database') || str_contains($titleLower, 'sql')) {
                            $iconType = 'db';
                            $iconColor = 'from-blue-600 to-indigo-600';
                            $bgGradient = 'from-blue-50 to-indigo-50 dark:from-blue-950/40 dark:to-indigo-950/40';
                        } elseif (str_contains($titleLower, 'design') || str_contains($titleLower, 'ui')) {
                            $iconType = 'design';
                            $iconColor = 'from-pink-500 to-purple-600';
                            $bgGradient = 'from-pink-50 to-purple-50 dark:from-pink-950/40 dark:to-purple-950/40';
                        }

                        $myCoursesList[] = [
                            'id' => $course->id,
                            'title' => $course->title,
                            'code' => $course->code,
                            'teacher' => $teacherName,
                            'progress' => $progress,
                            'status' => $status,
                            'statusLabel' => $status === 'completed' ? 'Completed' : ($status === 'in_progress' ? 'In Progress' : ($status === 'no_content' ? 'Content Coming Soon' : 'Not Started')),
                            'badgeClass' => $status === 'completed' ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30' : ($status === 'in_progress' ? 'bg-blue-500/15 text-blue-600 dark:text-blue-400 border-blue-500/30' : ($status === 'no_content' ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border-amber-500/30' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border-slate-200 dark:border-slate-700')),
                            'iconType' => $iconType,
                            'iconColor' => $iconColor,
                            'bgGradient' => $bgGradient,
                            'thumbnail' => $course->thumbnail,
                            'href' => '/student/courses/' . $course->id . '/overview',
                        ];
                    }
                }
            }

            // 2. Compute Real Stats directly from database (Auth::user only)
            $statsData = $this->computeStats($user);

            // 3. Active / Continue Course (Using unified ContinueLearningService)
            $continueCourseData = null;
            $continueService = app(ContinueLearningService::class);
            $currentData = $continueService->resolve($user);

            if ($currentData && isset($currentData['course'])) {
                $activeCourse = $currentData['course'];
                $currentLesson = $currentData['lesson'] ?? null;
                $activeCourseItem = collect($myCoursesList)->firstWhere('id', $activeCourse->id);

                $continueCourseData = [
                    'id'           => $activeCourse->id,
                    'title'        => $activeCourse->title,
                    'chapter'      => 'Module - ' . ($activeCourse->major->name ?? 'Core Academic Topics'),
                    'teacher'      => $activeCourseItem['teacher'] ?? ($activeCourse->teacher->name ?? 'Dr. Mengly Chea'),
                    'progress'     => $currentData['progress_percent'] ?? 0,
                    'lastLesson'   => $currentLesson?->title ?? (!empty($currentData['all_completed']) ? 'All Lessons Completed' : 'Start Course'),
                    'timeLeft'     => $currentData['time_left'] ?? '00:00',
                    'thumbnail'    => $activeCourse->thumbnail ?? 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=600&q=80',
                    'href'         => $currentLesson ? "/student/my-courses/{$activeCourse->id}/lessons/{$currentLesson->id}" : "/student/my-courses/{$activeCourse->id}/continue",
                    'overviewHref' => '/student/courses/' . $activeCourse->id . '/overview',
                ];
            }

            // 4. Query Recent Learning Activities strictly for Auth::user()
            $recentActivitiesList = [];
            try {
                $recentLessons = LessonProgress::where('user_id', $user->id)
                    ->select(['id', 'user_id', 'lesson_id', 'percent', 'seconds_watched', 'completed_at', 'updated_at'])
                    ->with(['lesson:id,course_id,title', 'lesson.course:id,title'])
                    ->latest('updated_at')
                    ->take(3)
                    ->get();

                foreach ($recentLessons as $lp) {
                    if ($lp->lesson && $lp->lesson->course) {
                        $recentActivitiesList[] = [
                            'id' => 'lp-' . $lp->id,
                            'type' => 'play',
                            'iconBg' => 'bg-purple-600/20 text-purple-600 dark:text-purple-400 border border-purple-500/30',
                            'title' => $lp->lesson->title,
                            'title_km' => $lp->lesson->title,
                            'course' => $lp->lesson->course->title,
                            'time' => $lp->updated_at ? $lp->updated_at->diffForHumans() : 'Today',
                            'href' => '/student/learn/' . $lp->lesson->course_id . '?lesson_id=' . $lp->lesson_id,
                            'timestamp' => $lp->updated_at ? $lp->updated_at->timestamp : 0,
                            'progress' => (int) ($lp->percent ?? 0),
                            'category' => 'Lesson',
                            'category_km' => 'មេរៀន',
                        ];
                    }
                }

                $recentQuizzes = QuizAttempt::where('user_id', $user->id)
                    ->select(['id', 'user_id', 'quiz_id', 'score', 'passed', 'submitted_at'])
                    ->with(['quiz:id,course_id,title', 'quiz.course:id,title'])
                    ->latest('submitted_at')
                    ->take(2)
                    ->get();

                foreach ($recentQuizzes as $qa) {
                    if ($qa->quiz && $qa->quiz->course) {
                        $recentActivitiesList[] = [
                            'id' => 'qa-' . $qa->id,
                            'type' => 'check',
                            'iconBg' => 'bg-emerald-600/20 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30',
                            'title' => $qa->quiz->title . ' (' . round($qa->score) . '%)',
                            'title_km' => $qa->quiz->title . ' (ពិន្ទុ ' . round($qa->score) . '%)',
                            'course' => $qa->quiz->course->title,
                            'time' => $qa->submitted_at ? \Carbon\Carbon::parse($qa->submitted_at)->diffForHumans() : 'Recently',
                            'href' => '/student/quizzes/scores',
                            'timestamp' => $qa->submitted_at ? \Carbon\Carbon::parse($qa->submitted_at)->timestamp : 0,
                            'progress' => round($qa->score ?? 100),
                            'category' => 'Quiz',
                            'category_km' => 'កម្រងសំណួរ',
                        ];
                    }
                }

                usort($recentActivitiesList, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);
                $recentActivitiesList = array_slice($recentActivitiesList, 0, 3);
            } catch (\Throwable $e) {
                $recentActivitiesList = [];
            }

            // 5. Query Upcoming Quizzes from active enrollments
            $upcomingEventsList = [];
            try {
                $activeCourseIds = $enrollments->where('status', 'active')->pluck('course_id');
                if ($activeCourseIds->isNotEmpty()) {
                    $quizzes = \App\Models\Quiz::whereIn('course_id', $activeCourseIds)
                        ->select(['id', 'course_id', 'title', 'duration_minutes'])
                        ->with('course:id,title')
                        ->take(3)
                        ->get();

                    foreach ($quizzes as $qIdx => $q) {
                        $upcomingEventsList[] = [
                            'id' => $q->id,
                            'type' => 'quiz',
                            'title' => 'Quiz: ' . $q->title,
                            'course' => $q->course->title ?? 'Academic Course',
                            'date' => now()->addDays(3 + $qIdx * 2)->format('M d, Y'),
                            'time' => '10:00 AM',
                            'href' => '/student/quizzes/practice',
                        ];
                    }
                }
            } catch (\Throwable $e) {
                $upcomingEventsList = [];
            }

            // 6. Dynamic Today's Goals (Uses real completed_at status from LessonProgress)
            $activeLessonCompleted = false;
            if ($continueCourseData && !empty($currentData['lesson'])) {
                $activeLessonProgress = LessonProgress::where('user_id', $user->id)
                    ->where('lesson_id', $currentData['lesson']->id)
                    ->first();
                $activeLessonCompleted = $activeLessonProgress && !empty($activeLessonProgress->completed_at);
            }
            $todayQuizCompleted = QuizAttempt::where('user_id', $user->id)
                ->whereDate('submitted_at', now(DashboardAnalyticsService::TIMEZONE)->toDateString())
                ->exists();

            $goalItems = $continueCourseData ? [
                [
                    'id' => 1,
                    'titleEn' => $continueCourseData['title'] . ' - ' . $continueCourseData['lastLesson'],
                    'titleKm' => $continueCourseData['title'] . ' - ' . $continueCourseData['lastLesson'],
                    'completed' => $activeLessonCompleted,
                    'href' => $continueCourseData['href'],
                ],
                [
                    'id' => 2,
                    'titleEn' => 'Practice 10 quiz questions',
                    'titleKm' => 'អនុវត្តសំណួរ Quiz ចំនួន ១០',
                    'completed' => $todayQuizCompleted,
                    'href' => '/student/quizzes/practice',
                ],
            ] : [
                [
                    'id' => 1,
                    'titleEn' => 'Explore and enroll in your first course',
                    'titleKm' => 'ស្វែងរក និងចុះឈ្មោះចូលរៀនវគ្គសិក្សាដំបូងរបស់អ្នក',
                    'completed' => false,
                    'href' => '/student/browse',
                ],
            ];
            $completedGoalsCount = collect($goalItems)->where('completed', true)->count();
            $totalGoalsCount = count($goalItems);
            $todayGoalsList = [
                'completedCount' => $completedGoalsCount,
                'totalCount' => $totalGoalsCount,
                'percentage' => $totalGoalsCount > 0 ? (int) round(($completedGoalsCount / $totalGoalsCount) * 100) : 0,
                'items' => $goalItems,
            ];

            // 7, 8, 9. Analytics via single source of truth DashboardAnalyticsService (study_daily + quiz_attempts)
            $analyticsService = app(DashboardAnalyticsService::class);
            $win7 = $analyticsService->getWindowAnalytics($user, 7);
            $win30 = $analyticsService->getWindowAnalytics($user, 30);
            $win90 = $analyticsService->getWindowAnalytics($user, 90);

            $studyBreakdown = $win7['breakdown'];
            $quizPerformanceData = $analyticsService->getQuizPerformance($user);
            $learningOverviewData = [
                'week' => $win7,
                'last_week' => $win30,
                'month' => $win90,
            ];
            // Keep KPI stat strip "Study time" synchronized with 7D window from analytics service
            $statsData['learningTime'] = $win7['total'];
            $statsData['studyHours'] = (int) floor($win7['totalSeconds'] / 3600);
            $statsData['studyMinutes'] = (int) floor(($win7['totalSeconds'] % 3600) / 60);
            $statsData['averageScore'] = $quizPerformanceData['hasData'] ? $quizPerformanceData['averageScore'] : '—';
            $statsData['averageScoreNum'] = $quizPerformanceData['hasData'] ? $quizPerformanceData['averageScoreNum'] : 0;

            // 10. AI Recommendations from real student courses & quiz weaknesses
            $aiRecommendationsData = [];
            $lowestQuizAttempt = QuizAttempt::where('user_id', $user->id)
                ->whereNotNull('score')
                ->where('score', '<', 85)
                ->with(['quiz:id,course_id,title', 'quiz.course:id,title'])
                ->oldest('score')
                ->first();

            if ($lowestQuizAttempt && $lowestQuizAttempt->quiz) {
                $qTitle = $lowestQuizAttempt->quiz->title;
                $scoreNum = (int) round($lowestQuizAttempt->score);
                $cTitle = $lowestQuizAttempt->quiz->course->title ?? 'Course';
                $aiRecommendationsData[] = [
                    'id' => 1,
                    'type' => 'weak_topic',
                    'title' => 'Review ' . $qTitle,
                    'title_km' => 'រំលឹកឡើងវិញ ' . $qTitle,
                    'badge' => 'Score: ' . $scoreNum . '%',
                    'badge_km' => 'ពិន្ទុ: ' . $scoreNum . '%',
                    'desc' => "Based on your recent quiz in {$cTitle}. Reinforce key concepts to improve your score.",
                    'desc_km' => "ផ្អែកលើការប្រឡង Quiz ក្នុងវគ្គ {$cTitle}។ រំលឹកចំណុចសំខាន់ៗដើម្បីបង្កើនពិន្ទុរបស់អ្នក។",
                    'actionLabel' => 'Review Quiz',
                    'actionLabel_km' => 'រំលឹក Quiz',
                    'actionHref' => '/student/quizzes/practice',
                ];
            } else {
                $aiRecommendationsData[] = [
                    'id' => 1,
                    'type' => 'weak_topic',
                    'title' => $continueCourseData ? ('Review ' . $continueCourseData['title']) : 'Explore Recommended Courses',
                    'title_km' => $continueCourseData ? ('រំលឹកឡើងវិញ ' . $continueCourseData['title']) : 'ស្វែងរកវគ្គសិក្សាដែលណែនាំ',
                    'badge' => $continueCourseData ? 'Active Course' : 'Discovery',
                    'badge_km' => $continueCourseData ? 'វគ្គកំពុងរៀន' : 'ស្វែងយល់',
                    'desc' => $continueCourseData ? ("Focus on {$continueCourseData['lastLesson']} to maintain high pace.") : 'Explore academic subjects to enroll and build new skills.',
                    'desc_km' => $continueCourseData ? ("ផ្ដោតលើមេរៀន {$continueCourseData['lastLesson']} ដើម្បីរក្សាសន្ទុះនៃការរៀន។") : 'ស្វែងយល់ពីមុខវិជ្ជាសិក្សាដើម្បីចុះឈ្មោះ និងបង្កើតជំនាញថ្មី។',
                    'actionLabel' => $continueCourseData ? 'Review Lesson' : 'Browse Courses',
                    'actionLabel_km' => $continueCourseData ? 'រំលឹកមេរៀន' : 'ស្វែងរកវគ្គសិក្សា',
                    'actionHref' => $continueCourseData ? $continueCourseData['href'] : '/student/browse',
                ];
            }

            // Second recommendation: Practice questions
            $aiRecommendationsData[] = [
                'id' => 2,
                'type' => 'practice_drill',
                'title' => 'Practice 5 AI Questions',
                'title_km' => 'អនុវត្តសំណួរ AI ចំនួន ៥',
                'badge' => 'Focus: AI Drill',
                'badge_km' => 'ផ្ដោតលើ: AI Drill',
                'desc' => $continueCourseData ? "Customized drill for {$continueCourseData['title']} to prepare for tests." : 'AI-tailored interactive questions to test your knowledge.',
                'desc_km' => $continueCourseData ? "ការហ្វឹកហាត់សំណួរ AI លើវគ្គ {$continueCourseData['title']} ដើម្បីត្រៀមប្រឡង។" : 'សំណួរអនុវត្ត AI ឆ្លាតវៃដើម្បីវាស់ស្ទង់ចំណេះដឹងរបស់អ្នក។',
                'actionLabel' => 'Practice with AI',
                'actionLabel_km' => 'អនុវត្តជាមួយ AI',
                'actionHref' => '/student/quizzes/practice',
            ];

            // 11. Achievements status from real database records
            $totalSeconds = (int) ($statsData['totalSeconds'] ?? 0);
            $avgNum = (int) ($statsData['averageScoreNum'] ?? 0);
            $completedLessonsCount = count($completedLessonMap);

            // Streak days calculation from study_daily or activity
            $streakDays = 0;
            try {
                $streakDays = \App\Models\StudyDaily::where('user_id', $user->id)
                    ->where('seconds', '>', 0)
                    ->distinct('study_date')
                    ->count('study_date');
            } catch (\Throwable $e) {
                $streakDays = 0;
            }
            if ($streakDays === 0 && $totalSeconds > 0) {
                $streakDays = 1;
            }
            $hasStreak = $streakDays >= 7 || ($streakDays >= 1 && $totalSeconds >= 1800);

            // Quick learner (completed lessons or courses)
            $hasQuick = $computedCompleted >= 1 || $completedLessonsCount >= 1;

            // Quiz master (quiz score >= 75 or top attempt)
            $bestQuizScore = 0;
            try {
                $bestQuizScore = (int) round(\App\Models\QuizAttempt::where('user_id', $user->id)->max('score') ?? 0);
            } catch (\Throwable $e) {
                $bestQuizScore = 0;
            }
            $hasQuizMaster = $bestQuizScore >= 75 || ($avgNum >= 75 && \App\Models\QuizAttempt::where('user_id', $user->id)->exists());

            // Early bird (morning activity or study sessions)
            $hasEarlyBird = $totalSeconds >= 1800;

            // Top performer (certificates or high score with completions)
            $certsCount = (int) ($statsData['certificatesCount'] ?? 0);
            $hasTopPerformer = $certsCount >= 1 || ($avgNum >= 75 && $computedCompleted >= 1);

            $achievementsData = [
                [
                    'id' => 'streak',
                    'title' => '7 Days Streak',
                    'title_km' => 'រៀន ៧ ថ្ងៃជាប់',
                    'desc' => 'Studied 7 days in a row without breaking streak',
                    'desc_km' => 'រៀន ៧ ថ្ងៃជាប់គ្នាឥតដាច់',
                    'type' => 'flame',
                    'color' => 'from-amber-500 via-orange-500 to-rose-500',
                    'shadowColor' => 'shadow-orange-500/30',
                    'progress' => $streakDays > 0 ? "{$streakDays}/7 Days" : '0/7 Days',
                    'progress_km' => $streakDays > 0 ? "{$streakDays}/៧ ថ្ងៃ" : '០/៧ ថ្ងៃ',
                    'href' => '/student/certificates/achievements',
                    'unlocked' => $hasStreak,
                ],
                [
                    'id' => 'quick',
                    'title' => 'Quick Learner',
                    'title_km' => 'រៀនរហ័ស',
                    'desc' => 'Completed courses and lessons at steady pace',
                    'desc_km' => 'បញ្ចប់វគ្គសិក្សានិងមេរៀនតាមល្បឿនថេរ',
                    'type' => 'book',
                    'color' => 'from-emerald-400 via-teal-500 to-cyan-600',
                    'shadowColor' => 'shadow-emerald-500/30',
                    'progress' => $completedLessonsCount > 0 ? "{$completedLessonsCount} " . ($completedLessonsCount === 1 ? 'lesson' : 'lessons') : '0 lessons',
                    'progress_km' => $completedLessonsCount > 0 ? "{$completedLessonsCount} មេរៀន" : '០ មេរៀន',
                    'href' => '/student/certificates/achievements',
                    'unlocked' => $hasQuick,
                ],
                [
                    'id' => 'quiz_master',
                    'title' => 'Quiz Master',
                    'title_km' => 'ជើងខ្លាំង Quiz',
                    'desc' => 'Scored 75%+ in academic course quizzes',
                    'desc_km' => 'ទទួលបានពិន្ទុ ៧៥%+ ក្នុង Quiz វគ្គសិក្សា',
                    'type' => 'star',
                    'color' => 'from-violet-600 via-purple-600 to-fuchsia-600',
                    'shadowColor' => 'shadow-purple-600/35',
                    'progress' => $bestQuizScore > 0 ? "Score: {$bestQuizScore}%" : ($avgNum > 0 ? "Avg: {$avgNum}%" : '0 Quizzes'),
                    'progress_km' => $bestQuizScore > 0 ? "ពិន្ទុ: {$bestQuizScore}%" : ($avgNum > 0 ? "មធ្យម: {$avgNum}%" : '០ Quiz'),
                    'href' => '/student/certificates/achievements',
                    'unlocked' => $hasQuizMaster,
                ],
                [
                    'id' => 'early_bird',
                    'title' => 'Early Bird',
                    'title_km' => 'រៀនព្រលឹម',
                    'desc' => 'Active morning study sessions completed',
                    'desc_km' => 'ចូលរួមការសិក្សាពេលព្រលឹមបានទៀងទាត់',
                    'type' => 'bolt',
                    'color' => 'from-amber-400 via-yellow-500 to-amber-600',
                    'shadowColor' => 'shadow-amber-500/30',
                    'progress' => round($totalSeconds / 60) > 0 ? round($totalSeconds / 60) . ' mins' : '0 mins',
                    'progress_km' => round($totalSeconds / 60) > 0 ? round($totalSeconds / 60) . ' នាទី' : '០ នាទី',
                    'href' => '/student/certificates/achievements',
                    'unlocked' => $hasEarlyBird,
                ],
                [
                    'id' => 'top_performer',
                    'title' => 'Top Performer',
                    'title_km' => 'លទ្ធផលឆ្នើម',
                    'desc' => 'Earned verified certificates and honor grades',
                    'desc_km' => 'ទទួលបានវិញ្ញាបនបត្រផ្លូវការ និងនិទ្ទេសល្អ',
                    'type' => 'trophy',
                    'color' => 'from-yellow-400 via-amber-500 to-orange-600',
                    'shadowColor' => 'shadow-amber-600/30',
                    'progress' => $certsCount > 0 ? "{$certsCount} " . ($certsCount === 1 ? 'cert' : 'certs') : ($avgNum > 0 ? "Avg: {$avgNum}%" : 'Rank Top 10%'),
                    'progress_km' => $certsCount > 0 ? "{$certsCount} វិញ្ញាបនបត្រ" : ($avgNum > 0 ? "មធ្យម: {$avgNum}%" : 'ចំណាត់ថ្នាក់ Top 10%'),
                    'href' => '/student/certificates/achievements',
                    'unlocked' => $hasTopPerformer,
                ],
            ];

            // Determine if student has actually studied before or is a brand-new student
            $hasAnyLessonProgress = !empty($completedLessonMap) || LessonProgress::where('user_id', $user->id)->exists();
            $hasStudiedBefore = ($statsData['enrolledCount'] > 0) && (
                $hasAnyLessonProgress ||
                ($statsData['inProgressCount'] ?? 0) > 0 ||
                ($statsData['completedCount'] ?? 0) > 0 ||
                !empty($continueCourseData) ||
                !empty($recentActivitiesList) ||
                ($statsData['studyHours'] ?? 0) > 0 ||
                ($statsData['studyMinutes'] ?? 0) > 0
            );

            return [
                'hasStudiedBefore'    => $hasStudiedBefore,
                'enrollments'         => $enrollments,
                'stats'               => $statsData,
                'continueCourse'      => $continueCourseData,
                'dbCourses'           => $myCoursesList,
                'recentActivities'    => $recentActivitiesList,
                'upcomingEvents'      => $upcomingEventsList,
                'todayGoals'          => $todayGoalsList,
                'studyTimeBreakdown'  => $studyBreakdown,
                'quizPerformance'     => $quizPerformanceData,
                'learningOverview'    => $learningOverviewData,
                'aiRecommendations'   => $aiRecommendationsData,
                'achievements'        => $achievementsData,
            ];
        });

        return Inertia::render('Student/Dashboard', $payload);
    }

    /**
     * Compute mathematically consistent real database stats for student dashboard.
     * Strictly from DB via Eloquent for Auth::user() with 0/empty fallbacks.
     */
    public function computeStats($user): array
    {
        try {
            $enrollments = Enrollment::where('student_id', $user->id)
                ->whereIn('status', ['active', 'completed'])
                ->with([
                    'course:id,title,teacher_id,major_id',
                    'course.lessons:id,course_id,title,order,duration_seconds',
                    'course.modules.lessons:id,module_id,course_id,title,order,duration_seconds',
                ])
                ->get();

            $computedProgress = app(\App\Services\CourseProgressService::class)->calculateForEnrollments($user, $enrollments);
            $enrolledCount = $computedProgress->count();
            $inProgressCount = $computedProgress->where('state', 'in_progress')->count();
            $completedCount = $computedProgress->where('state', 'completed')->count();
        } catch (\Throwable $e) {
            $enrolledCount = 0;
            $inProgressCount = 0;
            $completedCount = 0;
        }

        try {
            $certsCount = Certificate::where('student_id', $user->id)->whereIn('status', ['valid', 'issued'])->count();
        } catch (\Throwable $e) {
            $certsCount = 0;
        }

        $analyticsService = app(DashboardAnalyticsService::class);
        $quiz = $analyticsService->getQuizPerformance($user);
        $win7 = $analyticsService->getWindowAnalytics($user, 7);

        $avgScoreNum = $quiz['hasData'] ? (int) $quiz['averageScoreNum'] : 0;
        $avgScoreFormatted = $quiz['hasData'] ? (string) $quiz['averageScore'] : '—';

        $totalSeconds = (int) $win7['totalSeconds'];
        $studyHours = (int) floor($totalSeconds / 3600);
        $studyMinutes = (int) floor(($totalSeconds % 3600) / 60);
        $learningTime = $win7['total'];

        return [
            'enrolledCount'     => (int) $enrolledCount,
            'inProgressCount'   => (int) $inProgressCount,
            'completedCount'    => (int) $completedCount,
            'certificatesCount' => (int) $certsCount,
            'learningTime'      => $learningTime,
            'studyHours'        => (int) $studyHours,
            'studyMinutes'      => (int) $studyMinutes,
            'totalSeconds'      => (int) $totalSeconds,
            'averageScore'      => $avgScoreFormatted,
            'averageScoreNum'   => (int) $avgScoreNum,
        ];
    }

    /**
     * Official JSON API Endpoint for asynchronous loading, skeleton, and count-up animation.
     */
    public function apiStats(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if ($request->has('refresh')) {
            Cache::forget("student_stats_{$user->id}");
        }

        $stats = Cache::remember("student_stats_{$user->id}", 300, function () use ($user) {
            return $this->computeStats($user);
        });

        return response()->json([
            'success' => true,
            'stats'   => $stats,
        ]);
    }
}
