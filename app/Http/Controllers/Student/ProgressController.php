<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\LessonProgress;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProgressController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'overview');
        return Inertia::render('Student/ProgressTracking/Index', [
            'activeTab' => $tab,
            'progress' => LessonProgress::where('user_id', $request->user()->id)->with('lesson.course')->get()
        ]);
    }

    public function overview(Request $request)
    {
        return Inertia::render('Student/ProgressTracking/Overview');
    }

    public function learningTime(Request $request)
    {
        return Inertia::render('Student/ProgressTracking/LearningTime');
    }

    public function weeklyProgress(Request $request)
    {
        $period = $request->query('period', 'this_month');
        $granularity = $request->query('granularity', 'daily');

        $analytics = app(\App\Services\QuizAnalyticsService::class)->getStudentAnalytics(
            $request->user(),
            $period,
            $granularity
        );

        return Inertia::render('Student/ProgressTracking/WeeklyProgress', [
            'analytics' => $analytics,
            'filters' => [
                'period' => $period,
                'granularity' => $granularity,
            ]
        ]);
    }

    public function achievementsBadges(Request $request)
    {
        $category = $request->query('category', 'all');
        $sort = $request->query('sort', 'progress_desc');
        $tab = $request->query('tab', 'all');
        $period = $request->query('period', 'this_month');

        $analytics = app(\App\Services\SkillAnalyticsService::class)->getStudentSkillAnalytics(
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
            ]
        ]);
    }
}
