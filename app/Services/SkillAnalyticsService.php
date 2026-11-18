<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\QuizAttempt;
use App\Models\User;

class SkillAnalyticsService
{
    /**
     * Alias for compatibility with verification callers.
     */
    public function getSkillMasteryAnalytics(?User $user = null, string $category = 'all', string $sort = 'progress_desc', string $tab = 'all', string $period = 'this_month'): array
    {
        return $this->getStudentSkillAnalytics($user, $category, $sort, $tab, $period);
    }

    /**
     * Get complete skill analytics for a student strictly from real database records.
     */
    public function getStudentSkillAnalytics(
        ?User $user = null,
        string $category = 'all',
        string $sort = 'progress_desc',
        string $tab = 'all',
        string $period = 'this_month'
    ): array {
        if (!$user) {
            return $this->emptyAnalytics();
        }

        $enrollments = Enrollment::with(['course.major', 'course.subject', 'course.lessons', 'course.quizzes'])
            ->where('student_id', $user->id)
            ->get();

        if ($enrollments->isEmpty()) {
            return $this->emptyAnalytics();
        }

        $progressRows = LessonProgress::where('user_id', $user->id)->get()->keyBy('lesson_id');
        $quizAttempts = QuizAttempt::where('user_id', $user->id)->whereNotNull('score')->get();
        $certByCourse = Certificate::where('student_id', $user->id)
            ->whereIn('status', ['valid', 'issued'])
            ->get()
            ->keyBy('course_id');

        $palette = [
            'from-purple-500 to-indigo-600 text-white',
            'from-blue-500 to-cyan-600 text-white',
            'from-emerald-500 to-teal-600 text-white',
            'from-amber-400 to-orange-500 text-white',
            'from-rose-500 to-pink-600 text-white',
        ];
        $catColors = ['#8B5CF6', '#3B82F6', '#10B981', '#F59E0B', '#F43F5E', '#94A3B8'];

        $allSkills = [];
        $categoryTotals = [];
        $recentlyMastered = [];
        $improvementFocus = [];

        foreach ($enrollments->values() as $idx => $enr) {
            $course = $enr->course;
            if (!$course) {
                continue;
            }

            $lessonIds = $course->lessons->pluck('id');
            $totalLessons = $lessonIds->count();
            $completedLessons = 0;
            foreach ($lessonIds as $lid) {
                $lp = $progressRows->get($lid);
                if ($lp && ($lp->completed_at !== null || (int) $lp->percent >= 100)) {
                    $completedLessons++;
                }
            }

            $lessonPct = $totalLessons > 0
                ? (int) round(($completedLessons / $totalLessons) * 100)
                : ($enr->status === 'completed' ? 100 : 0);

            $courseQuizIds = $course->quizzes->pluck('id');
            $courseAttempts = $courseQuizIds->isNotEmpty()
                ? $quizAttempts->whereIn('quiz_id', $courseQuizIds)
                : collect();

            $avgQuizPct = $courseAttempts->isNotEmpty()
                ? (int) round((float) $courseAttempts->avg('score'))
                : null;

            $skillProgress = $avgQuizPct !== null
                ? (int) round(($lessonPct * 0.6) + ($avgQuizPct * 0.4))
                : $lessonPct;

            if ($enr->status === 'completed' || $certByCourse->has($course->id)) {
                $skillProgress = max($skillProgress, 90);
            }

            $level = $this->determineLevel($skillProgress);
            $catName = $course->major?->name_kh ?: ($course->major?->name ?: 'General');
            $skillName = $course->subject?->name_kh
                ?: ($course->subject?->name ?: ($course->title_kh ?: $course->title));
            $code = mb_strtoupper(mb_substr(preg_replace('/\s+/', '', $course->code ?: $skillName), 0, 2)) ?: 'SK';
            $iconBg = $palette[$idx % count($palette)];

            $allSkills[] = [
                'id'          => $course->id,
                'name'        => $skillName,
                'description' => $course->title_kh ?: $course->title,
                'category'    => $catName,
                'level'       => $level,
                'progress'    => $skillProgress,
                'trend'       => $skillProgress > 0 ? ('+' . min(100, $skillProgress) . '%') : '0%',
                'action'      => $this->determineAction($level),
                'code'        => $code,
                'icon_bg'     => $iconBg,
            ];

            $categoryTotals[$catName][] = $skillProgress;

            if ($skillProgress >= 90) {
                $cert = $certByCourse->get($course->id);
                $masteredDate = ($cert?->issued_at ?? $enr->completed_at ?? $enr->updated_at)?->format('M d, Y') ?? now()->format('M d, Y');
                $recentlyMastered[] = [
                    'id'      => $course->id,
                    'title'   => $skillName,
                    'date'    => 'Mastered on ' . $masteredDate,
                    'icon'    => $code,
                    'icon_bg' => $iconBg,
                ];
            } elseif ($skillProgress < 70) {
                $improvementFocus[] = [
                    'id'          => $course->id,
                    'skill'       => $skillName,
                    'description' => $course->title_kh ?: $course->title,
                    'priority'    => $skillProgress < 35 ? 'High' : 'Medium',
                    'badge_color' => $skillProgress < 35 ? 'rose' : 'amber',
                    'progress'    => $skillProgress,
                    'code'        => $code,
                    'icon_bg'     => $iconBg,
                ];
            }
        }

        if (empty($allSkills)) {
            return $this->emptyAnalytics();
        }

        $totalSkills = count($allSkills);
        $masteredCount = count(array_filter($allSkills, fn ($s) => $s['progress'] >= 90));
        $inProgressCount = count(array_filter($allSkills, fn ($s) => $s['progress'] > 0 && $s['progress'] < 90));
        $needWorkCount = count(array_filter($allSkills, fn ($s) => $s['progress'] < 50));
        $avgOverall = (int) round(array_sum(array_column($allSkills, 'progress')) / max(1, $totalSkills));
        $levelName = $this->determineLevel($avgOverall);

        $categories = [];
        $topCategory = '—';
        $topCatScore = -1;
        $cIdx = 0;
        foreach ($categoryTotals as $cName => $scores) {
            $avgCat = (int) round(array_sum($scores) / max(1, count($scores)));
            if ($avgCat > $topCatScore) {
                $topCatScore = $avgCat;
                $topCategory = $cName;
            }
            $categories[] = [
                'name'  => $cName,
                'score' => $avgCat,
                'color' => $catColors[$cIdx % count($catColors)],
            ];
            $cIdx++;
        }

        $levelDistribution = [
            'advanced'     => count(array_filter($allSkills, fn ($s) => in_array($s['level'], ['Advanced', 'Mastered'], true))),
            'intermediate' => count(array_filter($allSkills, fn ($s) => $s['level'] === 'Intermediate')),
            'beginner'     => count(array_filter($allSkills, fn ($s) => $s['level'] === 'Beginner')),
            'not_started'  => count(array_filter($allSkills, fn ($s) => $s['level'] === 'Not Started')),
        ];

        $filteredSkills = $allSkills;
        if ($tab === 'in_progress') {
            $filteredSkills = array_filter($filteredSkills, fn ($s) => $s['progress'] > 0 && $s['progress'] < 90);
        } elseif ($tab === 'mastered') {
            $filteredSkills = array_filter($filteredSkills, fn ($s) => $s['progress'] >= 90);
        } elseif ($tab === 'not_started') {
            $filteredSkills = array_filter($filteredSkills, fn ($s) => $s['progress'] === 0);
        }

        if ($category !== 'all') {
            $filteredSkills = array_filter($filteredSkills, fn ($s) => mb_strtolower($s['category']) === mb_strtolower($category));
        }

        usort($filteredSkills, function ($a, $b) use ($sort) {
            return match ($sort) {
                'progress_asc' => $a['progress'] <=> $b['progress'],
                'name_asc'     => strcmp($a['name'], $b['name']),
                'name_desc'    => strcmp($b['name'], $a['name']),
                default        => $b['progress'] <=> $a['progress'],
            };
        });

        // Build real 7-point trend chart over last 30 days
        $labels = [];
        $points = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i * 5);
            $label = $day->format('M j');
            $labels[] = $label;
            $completedUpToDay = $progressRows->filter(fn ($lp) => $lp->completed_at && $lp->completed_at->lte($day->endOfDay()))->count();
            $dayScore = $totalSkills > 0 ? min(100, (int) round(($completedUpToDay / max(1, $progressRows->count())) * $avgOverall)) : 0;
            $points[] = [
                'date'       => $label,
                'score'      => $dayScore,
                'new_skills' => 0,
                'improved'   => $completedUpToDay,
            ];
        }

        $completedTotalCount = $progressRows->filter(fn ($lp) => $lp->completed_at !== null || (int) $lp->percent >= 100)->count();
        $skillPoints = ($completedTotalCount * 50) + ($masteredCount * 200);

        $weakest = collect($allSkills)->sortBy('progress')->first();
        $aiRecommendation = [
            'primary'    => $weakest ? ('ផ្តោតលើការពង្រឹងមុខវិជ្ជា ' . $weakest['name']) : 'ចាប់ផ្តើមចុះឈ្មោះវគ្គសិក្សាដើម្បីវាស់ស្ទង់ជំនាញរបស់អ្នក។',
            'secondary'  => $weakest ? ('បញ្ចប់មេរៀន និងកម្រងសំណួរក្នុងវគ្គ ' . $weakest['description']) : 'មិនទាន់មានទិន្នន័យសិក្សានៅឡើយទេ។',
            'study_plan' => [],
        ];

        return [
            'summary' => [
                'overall_level'           => $avgOverall,
                'level_name'              => $levelName,
                'mastered_count'          => $masteredCount,
                'total_skills'            => $totalSkills,
                'overall_trend'           => $avgOverall . '% overall',
                'skills_in_progress'      => $inProgressCount,
                'in_progress_note'        => $inProgressCount > 0 ? 'Keep practicing!' : 'No active skills yet',
                'need_work_count'         => $needWorkCount,
                'mastered_skills'         => $masteredCount,
                'mastered_note'           => $masteredCount > 0 ? 'Great progress!' : 'Complete courses to master skills',
                'new_mastered_this_month' => $masteredCount,
                'skill_points'            => $skillPoints,
                'points_trend'            => '+' . $skillPoints . ' total pts',
                'top_category'            => $topCategory,
                'top_category_note'       => $topCategory !== '—' ? 'Your strongest area' : 'No courses enrolled yet',
            ],
            'categories'         => $categories,
            'level_distribution' => $levelDistribution,
            'skills'             => array_values($filteredSkills),
            'trend_chart'        => ['labels' => $labels, 'points' => $points],
            'improvement_focus'  => array_slice($improvementFocus, 0, 5),
            'recently_mastered'  => array_slice($recentlyMastered, 0, 5),
            'ai_recommendation'  => $aiRecommendation,
        ];
    }

    private function emptyAnalytics(): array
    {
        return [
            'summary' => [
                'overall_level'           => 0,
                'level_name'              => 'Not Started',
                'mastered_count'          => 0,
                'total_skills'            => 0,
                'overall_trend'           => '0% overall',
                'skills_in_progress'      => 0,
                'in_progress_note'        => 'មិនទាន់មានជំនាញកំពុងសិក្សាទេ',
                'need_work_count'         => 0,
                'mastered_skills'         => 0,
                'mastered_note'           => 'មិនទាន់មានជំនាញដែលបានស្ទាត់ជំនាញទេ',
                'new_mastered_this_month' => 0,
                'skill_points'            => 0,
                'points_trend'            => '0 pts',
                'top_category'            => '—',
                'top_category_note'       => 'មិនទាន់មានទិន្នន័យសិក្សា',
            ],
            'categories'         => [],
            'level_distribution' => ['advanced' => 0, 'intermediate' => 0, 'beginner' => 0, 'not_started' => 0],
            'skills'             => [],
            'trend_chart'        => ['labels' => [], 'points' => []],
            'improvement_focus'  => [],
            'recently_mastered'  => [],
            'ai_recommendation'  => [
                'primary'    => 'មិនទាន់មានទិន្នន័យវិភាគជំនាញនៅឡើយទេ។',
                'secondary'  => 'សូមចុះឈ្មោះចូលរៀនវគ្គសិក្សា ដើម្បីចាប់ផ្តើមតាមដានជំនាញរបស់អ្នក។',
                'study_plan' => [],
            ],
        ];
    }

    protected function determineLevel(int $progress): string
    {
        if ($progress >= 90) return 'Mastered';
        if ($progress >= 70) return 'Advanced';
        if ($progress >= 40) return 'Intermediate';
        if ($progress >= 1) return 'Beginner';
        return 'Not Started';
    }

    protected function determineAction(string $level): string
    {
        return match ($level) {
            'Mastered', 'Advanced' => 'Practice',
            'Intermediate'         => 'Continue',
            'Beginner'             => 'Learn',
            default                => 'Start',
        };
    }
}
