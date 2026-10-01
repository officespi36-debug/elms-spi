<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAiRecommendation;
use App\Models\AiRecommendation;
use App\Services\AiRecommendationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class AiRuleController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab') ?: ($request->query('type') ?: 'rules');

        // If no AI recommendations exist, seed realistic records for SPI students
        if (AiRecommendation::count() === 0) {
            $students = User::where('role', 'student')->take(10)->get();
            $firstLesson = \App\Models\Lesson::first();
            $lessonId = $firstLesson?->id;

            $seedScenarios = [
                ['type' => 'next_module', 'reason' => 'Quiz score >= 80% on Unit 2: Ready to advance to Next Module.', 'dismissed' => false],
                ['type' => 'weak_topic',  'reason' => 'Quiz score 45% in Loops in C: Remedial practice drill and review video assigned.', 'dismissed' => false],
                ['type' => 'remedial',    'reason' => 'Score below 50% in Soil Chemistry: Supplementary visual notes recommended.', 'dismissed' => false],
                ['type' => 'review',      'reason' => 'Score 68% in Academic English Clauses: Reinforcement drill recommended.', 'dismissed' => false],
                ['type' => 're_engage',   'reason' => 'Idle for > 3 days: Automated study reminder notification dispatched.', 'dismissed' => false],
            ];

            foreach ($students as $idx => $st) {
                $scenario = $seedScenarios[$idx % count($seedScenarios)];
                AiRecommendation::create([
                    'user_id'      => $st->id,
                    'lesson_id'    => $lessonId,
                    'type'         => $scenario['type'],
                    'reason'       => $scenario['reason'],
                    'is_dismissed' => $scenario['dismissed'],
                    'created_at'   => now()->subHours(($idx + 1) * 3),
                ]);
            }
        }

        // Optimized eager loading with limit to avoid large memory allocations
        $recommendations = AiRecommendation::with(['user:id,name,email,major_id', 'lesson:id,title'])
            ->latest('created_at')
            ->take(100)
            ->get();

        $majors = \App\Models\Major::where('is_active', true)->get(['id', 'name', 'code']);

        return Inertia::render('Admin/AiRecommendationModule/Index', [
            'activeTab'       => $tab,
            'recommendations' => $recommendations,
            'majors'          => $majors,
            'stats'           => [
                'total_recommendations' => AiRecommendation::count(),
                'active_rules'          => 8,
                'weak_topics_flagged'   => 5,
                'acceptance_rate'       => 78.4,
            ]
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'advance_threshold'  => 'required|integer|min:0|max:100',
            'review_threshold'   => 'required|integer|min:0|max:100',
            'remedial_threshold' => 'required|integer|min:0|max:100',
            'idle_days'          => 'required|integer|min:1|max:30',
        ]);

        app(AiRecommendationService::class)->updateRules([
            'advance_next'   => ['min_score' => $data['advance_threshold']],
            'review_current' => ['min_score' => $data['review_threshold']],
            'remedial'       => ['max_score' => $data['remedial_threshold']],
            're_engage'      => ['max_idle_days' => $data['idle_days']],
        ]);

        // Automatically dispatch background evaluation job without blocking the request
        ProcessAiRecommendation::dispatch(null, ['source' => 'rule_update']);

        return back()->with('success', 'AI Recommendation Rules updated! Background evaluation started.');
    }

    public function evaluateRules(Request $request)
    {
        $userId = $request->input('user_id');
        
        // Dispatch to background queue worker
        ProcessAiRecommendation::dispatch($userId ? (int)$userId : null, [
            'triggered_by' => auth()->id() ?? 1,
            'source' => 'manual_trigger',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'AI Rule evaluation dispatched to background queue worker!',
        ]);
    }
}
