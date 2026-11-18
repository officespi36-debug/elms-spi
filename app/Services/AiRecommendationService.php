<?php

namespace App\Services;

use App\Models\AiRecommendation;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\QuizAttempt;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class AiRecommendationService
{
    protected CloudflareAIService $cfAi;

    protected array $rules = [
        'advance_next'   => ['min_score' => 80, 'action' => 'next_module'],
        'review_current' => ['min_score' => 50, 'max_score' => 79, 'action' => 'review'],
        'remedial'       => ['max_score' => 49, 'action' => 'remedial'],
        'resume'         => ['max_progress' => 40, 'action' => 'resume'],
        're_engage'      => ['max_idle_days' => 3, 'action' => 're_engage'],
    ];

    public function __construct(?CloudflareAIService $cfAi = null)
    {
        $this->cfAi = $cfAi ?: app(CloudflareAIService::class);
    }

    /**
     * Load active Admin AI rules from cache or MySQL settings table.
     */
    public function getActiveRules(): array
    {
        $customRules = Cache::get('ai_rules');

        if (!is_array($customRules)) {
            try {
                $dbRules = Setting::get('ai_rules', []);
                $customRules = is_array($dbRules) ? $dbRules : [];
                if (!empty($customRules)) {
                    Cache::put('ai_rules', $customRules);
                }
            } catch (\Throwable $e) {
                $customRules = [];
            }
        }

        return array_merge($this->rules, $customRules);
    }

    public function analyzeAndRecommend(User $user): void
    {
        $rules = $this->getActiveRules();

        // 1. Check weak topics (based on quiz scores < 50%)
        $weakTopics = $this->detectWeakTopics($user);
        foreach ($weakTopics as $quizId) {
            $this->createRecommendation($user, null, 'weak_topic', 'Score below 50% - review recommended');
        }

        // 2. Check quiz performance rules
        $this->checkProgressRules($user, $rules);

        // 3. Check engagement (idle days)
        $this->checkEngagement($user, $rules);
    }

    /**
     * Generate dynamic AI Next Steps recommendation tailored to user's major.
     */
    public function getMajorRecommendation(User $user, ?Lesson $lesson = null, ?int $quizScore = null): array
    {
        $major = $user->major ? ($user->major->name ?? $user->major->code ?? 'IT') : 'Information Technology';
        $lessonTitle = $lesson ? $lesson->title : 'General Academic Foundation';
        $score = $quizScore ?? 80;

        $cacheKey = "ai_rec_{$user->id}_" . ($lesson ? $lesson->id : 'latest') . "_{$score}";

        return Cache::remember($cacheKey, 3600, function () use ($major, $lessonTitle, $score) {
            return $this->cfAi->generateRecommendation($major, $lessonTitle, null, $score);
        });
    }

    private function detectWeakTopics(User $user): array
    {
        return QuizAttempt::where('user_id', $user->id)
            ->where('score', '<', 50)
            ->where('created_at', '>', now()->subDays(30))
            ->pluck('quiz_id')
            ->unique()
            ->toArray();
    }

    private function checkProgressRules(User $user, array $rules): void
    {
        $recentAttempts = QuizAttempt::where('user_id', $user->id)
            ->where('created_at', '>', now()->subWeek())
            ->get();

        foreach ($recentAttempts as $attempt) {
            $score = $attempt->score;
            
            if ($score >= ($rules['advance_next']['min_score'] ?? 80)) {
                $this->createRecommendation($user, null, 'next_module', 'Great job! Score >= 80%: Ready for next module.');
            } elseif ($score >= ($rules['review_current']['min_score'] ?? 50)) {
                $this->createRecommendation($user, null, 'review', 'Score 50-79%: Review current module.');
            } else {
                $this->createRecommendation($user, null, 'remedial', 'Score below 50%: Remedial content suggested.');
            }
        }
    }

    private function checkEngagement(User $user, array $rules): void
    {
        $lastActivity = LessonProgress::where('user_id', $user->id)
            ->max('updated_at');

        $maxIdle = $rules['re_engage']['max_idle_days'] ?? 3;

        if ($lastActivity && now()->diffInDays($lastActivity) > $maxIdle) {
            AiRecommendation::firstOrCreate([
                'user_id' => $user->id,
                'type' => 're_engage',
                'reason' => "No learning activity for {$maxIdle}+ days. Re-engagement suggested.",
            ]);
        }
    }

    private function createRecommendation(User $user, ?int $lessonId, string $type, string $reason): void
    {
        AiRecommendation::firstOrCreate(
            [
                'user_id' => $user->id,
                'lesson_id' => $lessonId,
                'type' => $type,
                'is_dismissed' => false,
            ],
            [
                'reason' => $reason,
                'created_at' => now(),
            ]
        );
    }

    public function updateRules(array $newRules): void
    {
        Cache::put('ai_rules', $newRules);
        try {
            Setting::set('ai_rules', $newRules);
        } catch (\Throwable $e) {
            // Non-blocking fallback when settings table is unavailable during test bootstrap
        }
    }
}
