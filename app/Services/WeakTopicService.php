<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\StudyPlan;
use App\Models\StudyPlanItem;
use App\Models\Topic;
use App\Models\TopicMastery;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class WeakTopicService
{
    public function __construct(
        protected AiTutorChatService $chatService
    ) {}

    public static function invalidateForUser(int $userId): void
    {
        Cache::forget("weak_topics_payload_{$userId}");
        Cache::forget("weak_topics_ai_{$userId}");
    }

    /**
     * Get 100% real database weak topics payload for the authenticated student.
     */
    public function getWeakTopicsPayload(?User $user): array
    {
        if (!$user) {
            return $this->emptyPayload();
        }

        return Cache::remember("weak_topics_payload_{$user->id}", 60, function () use ($user) {
            return $this->buildWeakTopicsPayload($user);
        });
    }

    protected function emptyPayload(): array
    {
        return [
            'hasData'             => false,
            'unmappedQuestions'   => 0,
            'summary'             => [
                'totalTopics'          => 0,
                'avgScore'             => null,
                'recommendedFocusTime' => null,
                'recommendedMinutes'   => 0,
                'gapToPass'            => null,
            ],
            'aiRecommendation'    => null,
            'chips'               => [
                'all'        => 0,
                'quiz'       => 0,
                'assessment' => 0,
                'course'     => 0,
            ],
            'topics'              => [],
            'focusThisWeek'       => [
                'totalMinutes'   => 0,
                'formattedTotal' => '0m',
                'high'           => ['minutes' => 0, 'formatted' => '0m', 'percent' => 0],
                'medium'         => ['minutes' => 0, 'formatted' => '0m', 'percent' => 0],
                'low'            => ['minutes' => 0, 'formatted' => '0m', 'percent' => 0],
            ],
            'recentImprovements'  => [],
            'studyPlan'           => null,
        ];
    }

    protected function buildWeakTopicsPayload(User $user): array
    {
        // 1. Get student's enrolled courses (active or completed)
        $enrolledCourseIds = Enrollment::where('student_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->pluck('course_id')
            ->unique()
            ->filter()
            ->values()
            ->all();

        if (empty($enrolledCourseIds)) {
            return $this->emptyPayload();
        }

        $minAnswers = (int) config('learning.weak.min_answers', 5);
        $windowDays = (int) config('learning.weak.window_days', 90);
        $threshold = (float) config('learning.weak.threshold', 70);
        $sinceDate = Carbon::now()->subDays($windowDays);

        // 2. Count unmapped questions for student's enrolled courses (audit reporting)
        $unmappedQuestions = Question::whereNull('topic_id')
            ->whereHas('quiz', fn ($q) => $q->whereIn('course_id', $enrolledCourseIds))
            ->count();

        // 3. Query all quiz attempt answers in the window for enrolled courses linked to a topic
        $answers = QuizAttemptAnswer::query()
            ->whereHas('attempt', function ($q) use ($user, $sinceDate) {
                $q->where('user_id', $user->id)
                  ->where('submitted_at', '>=', $sinceDate);
            })
            ->whereHas('question.quiz', function ($q) use ($enrolledCourseIds) {
                $q->whereIn('course_id', $enrolledCourseIds);
            })
            ->whereHas('question', function ($q) {
                $q->whereNotNull('topic_id');
            })
            ->with(['question.quiz', 'question.topic.course', 'question.topic.lesson'])
            ->get();

        if ($answers->isEmpty()) {
            return array_merge($this->emptyPayload(), [
                'unmappedQuestions' => $unmappedQuestions,
            ]);
        }

        // 4. Group by topic and calculate score & category
        $byTopic = $answers->groupBy('question.topic_id');
        $rawTopicsList = [];

        foreach ($byTopic as $topicId => $topicAnswers) {
            $totalAnswered = $topicAnswers->count();
            if ($totalAnswered < $minAnswers) {
                continue; // Skip topics with < 5 answers
            }

            $correctCount = $topicAnswers->where('is_correct', true)->count();
            $score = round(($correctCount / $totalAnswered) * 100);

            if ($score >= $threshold) {
                continue; // Only include weak topics (< 70)
            }

            /** @var Question $sampleQuestion */
            $sampleQuestion = $topicAnswers->first()->question;
            /** @var Topic $topic */
            $topic = $sampleQuestion->topic;
            /** @var Course $course */
            $course = $topic->course;
            /** @var Lesson|null $lesson */
            $lesson = $topic->lesson;

            // Determine priority
            $priority = 'low';
            if ($score < (int) config('learning.weak.priority.high', 40)) {
                $priority = 'high';
            } elseif ($score < (int) config('learning.weak.priority.medium', 60)) {
                $priority = 'medium';
            }

            // Determine mutually exclusive category
            // 1. assessment: majority of answers from post_test or assignment
            // 2. course_content: has direct lesson_id attached
            // 3. quiz: standard practice / pre-test quizzes
            $assessmentCount = $topicAnswers->filter(fn ($a) => in_array($a->question->quiz?->type, ['post_test', 'assignment'], true))->count();
            if ($assessmentCount >= ($totalAnswered / 2)) {
                $category = 'assessment';
            } elseif ($topic->lesson_id !== null) {
                $category = 'course_content';
            } else {
                $category = 'quiz';
            }

            // Latest attempt date
            $latestAnswer = $topicAnswers->sortByDesc('answered_at')->first();
            $lastAttemptAt = $latestAnswer?->answered_at ?? $latestAnswer?->created_at;

            // Lesson / course link
            $lessonHref = $lesson
                ? "/student/my-courses/{$course->id}/lessons/{$lesson->id}"
                : "/student/courses/{$course->id}/overview";

            $minutesCfg = config('learning.minutes', ['high' => 45, 'medium' => 30, 'low' => 20]);
            $recommendedMins = (int) ($minutesCfg[$priority] ?? 30);

            $rawTopicsList[] = [
                'id'              => (int) $topic->id,
                'title'           => (string) ($topic->title_kh ?: $topic->title),
                'title_en'        => (string) $topic->title,
                'title_kh'        => (string) ($topic->title_kh ?: $topic->title),
                'subtitle'        => $lesson ? ($lesson->kh_title ?: $lesson->title) : ($course->title_kh ?: $course->title),
                'course'          => (string) ($course->title_kh ?: $course->title),
                'course_id'       => (int) $course->id,
                'lesson_id'       => $lesson ? (int) $lesson->id : null,
                'chapter'         => $lesson ? "Lesson: {$lesson->title}" : "Course: {$course->title}",
                'lessonHref'      => $lessonHref,
                'score'           => (int) $score,
                'totalAnswered'   => $totalAnswered,
                'correctCount'    => $correctCount,
                'lastAttemptAt'   => $lastAttemptAt?->toIso8601String(),
                'lastAttempt'     => $lastAttemptAt ? $lastAttemptAt->diffForHumans() : 'Recently',
                'priority'        => $priority, // 'high' | 'medium' | 'low'
                'category'        => $category, // 'quiz' | 'assessment' | 'course_content'
                'recommendedMins' => $recommendedMins,
            ];
        }

        if (empty($rawTopicsList)) {
            return array_merge($this->emptyPayload(), [
                'unmappedQuestions' => $unmappedQuestions,
            ]);
        }

        // Sort by score ascending (lowest score first)
        usort($rawTopicsList, fn ($a, $b) => $a['score'] <=> $b['score']);

        // 5. Summary calculations (Single Source of Truth)
        $totalTopics = count($rawTopicsList);
        $avgScore = (int) round(array_sum(array_column($rawTopicsList, 'score')) / $totalTopics);
        $totalFocusMinutes = (int) array_sum(array_column($rawTopicsList, 'recommendedMins'));
        $gapToPass = max(0, (int) round($threshold - $avgScore));

        // Format focus time (e.g. 2h 15m or 45m)
        $formattedFocusTime = $this->formatMinutes($totalFocusMinutes);

        // Chip counts (Mutually Exclusive: all = quiz + assessment + course_content)
        $quizCount = count(array_filter($rawTopicsList, fn ($t) => $t['category'] === 'quiz'));
        $assessmentCount = count(array_filter($rawTopicsList, fn ($t) => $t['category'] === 'assessment'));
        $courseCount = count(array_filter($rawTopicsList, fn ($t) => $t['category'] === 'course_content'));

        // 6. Focus this week priority breakdown (sum strictly equals totalFocusMinutes)
        $highMins = (int) array_sum(array_column(array_filter($rawTopicsList, fn ($t) => $t['priority'] === 'high'), 'recommendedMins'));
        $medMins = (int) array_sum(array_column(array_filter($rawTopicsList, fn ($t) => $t['priority'] === 'medium'), 'recommendedMins'));
        $lowMins = (int) array_sum(array_column(array_filter($rawTopicsList, fn ($t) => $t['priority'] === 'low'), 'recommendedMins'));

        $highPct = $totalFocusMinutes > 0 ? (int) round(($highMins / $totalFocusMinutes) * 100) : 0;
        $medPct = $totalFocusMinutes > 0 ? (int) round(($medMins / $totalFocusMinutes) * 100) : 0;
        $lowPct = max(0, 100 - $highPct - $medPct);

        $focusThisWeek = [
            'totalMinutes'   => $totalFocusMinutes,
            'formattedTotal' => $formattedFocusTime,
            'high'           => ['minutes' => $highMins, 'formatted' => $this->formatMinutes($highMins), 'percent' => $highPct],
            'medium'         => ['minutes' => $medMins, 'formatted' => $this->formatMinutes($medMins), 'percent' => $medPct],
            'low'            => ['minutes' => $lowMins, 'formatted' => $this->formatMinutes($lowMins), 'percent' => $lowPct],
        ];

        // 7. Recent improvements (strictly computed from real attempts: compare earliest vs latest attempt in window)
        $recentImprovements = $this->calculateRecentImprovements($user, $enrolledCourseIds, $sinceDate);

        // 8. Active study plan (if already created by student)
        $studyPlan = $this->getStudentStudyPlanPayload($user);

        // 9. AI Recommendation
        $aiRecommendation = $this->getAiRecommendation($user, $rawTopicsList);

        return [
            'hasData'             => true,
            'unmappedQuestions'   => $unmappedQuestions,
            'summary'             => [
                'totalTopics'          => $totalTopics,
                'avgScore'             => $avgScore,
                'recommendedFocusTime' => $formattedFocusTime,
                'recommendedMinutes'   => $totalFocusMinutes,
                'gapToPass'            => $gapToPass,
            ],
            'aiRecommendation'    => $aiRecommendation,
            'chips'               => [
                'all'        => $totalTopics,
                'quiz'       => $quizCount,
                'assessment' => $assessmentCount,
                'course'     => $courseCount,
            ],
            'topics'              => $rawTopicsList,
            'focusThisWeek'       => $focusThisWeek,
            'recentImprovements'  => $recentImprovements,
            'studyPlan'           => $studyPlan,
        ];
    }

    protected function calculateRecentImprovements(User $user, array $enrolledCourseIds, Carbon $sinceDate): array
    {
        // Must have at least 2 attempts for a topic with >= 5 questions total, and score improved
        $answers = QuizAttemptAnswer::query()
            ->whereHas('attempt', function ($q) use ($user, $sinceDate) {
                $q->where('user_id', $user->id)
                  ->where('submitted_at', '>=', $sinceDate);
            })
            ->whereHas('question.quiz', function ($q) use ($enrolledCourseIds) {
                $q->whereIn('course_id', $enrolledCourseIds);
            })
            ->whereHas('question', function ($q) {
                $q->whereNotNull('topic_id');
            })
            ->with(['question.topic', 'attempt'])
            ->get();

        $byTopic = $answers->groupBy('question.topic_id');
        $improvements = [];

        foreach ($byTopic as $topicId => $tAnswers) {
            $byAttempt = $tAnswers->groupBy('attempt_id')->sortBy(fn ($g) => $g->first()->attempt->submitted_at);
            if ($byAttempt->count() < 2) {
                continue; // Needs at least 2 distinct attempts
            }

            $firstAttemptAnswers = $byAttempt->first();
            $lastAttemptAnswers = $byAttempt->last();

            if ($firstAttemptAnswers->count() < 2 || $lastAttemptAnswers->count() < 2) {
                continue;
            }

            $firstScore = round(($firstAttemptAnswers->where('is_correct', true)->count() / $firstAttemptAnswers->count()) * 100);
            $lastScore = round(($lastAttemptAnswers->where('is_correct', true)->count() / $lastAttemptAnswers->count()) * 100);

            if ($lastScore > $firstScore) {
                $topic = $firstAttemptAnswers->first()->question->topic;
                $improvements[] = [
                    'topic_id'    => (int) $topic->id,
                    'title'       => (string) ($topic->title_kh ?: $topic->title),
                    'first_score' => (int) $firstScore,
                    'last_score'  => (int) $lastScore,
                    'gain'        => (int) ($lastScore - $firstScore),
                ];
            }
        }

        return array_slice($improvements, 0, 4);
    }

    protected function getStudentStudyPlanPayload(User $user): ?array
    {
        $plan = StudyPlan::where('user_id', $user->id)
            ->where('status', 'active')
            ->with(['items.topic'])
            ->latest()
            ->first();

        if (!$plan || $plan->items->isEmpty()) {
            return null;
        }

        $items = $plan->items->map(fn ($item) => [
            'id'        => (int) $item->id,
            'day_index' => (int) $item->day_index,
            'topic_id'  => (int) $item->topic_id,
            'topic'     => $item->topic ? ($item->topic->title_kh ?: $item->topic->title) : 'Topic',
            'minutes'   => (int) $item->minutes,
            'kind'      => (string) $item->kind,
            'is_done'   => $item->done_at !== null,
            'done_at'   => $item->done_at?->toIso8601String(),
        ])->values()->all();

        $completedCount = count(array_filter($items, fn ($i) => $i['is_done']));
        $totalItems = count($items);
        $percent = $totalItems > 0 ? (int) round(($completedCount / $totalItems) * 100) : 0;

        return [
            'id'             => (int) $plan->id,
            'status'         => (string) $plan->status,
            'starts_on'      => $plan->starts_on ? $plan->starts_on->toDateString() : now()->toDateString(),
            'totalDays'      => $totalItems,
            'completedCount' => $completedCount,
            'percent'        => $percent,
            'items'          => $items,
        ];
    }

    protected function getAiRecommendation(User $user, array $weakTopics): array
    {
        if (empty($weakTopics)) {
            return [
                'isAiGenerated' => false,
                'targetTopicId' => null,
                'text_km'       => 'មិនទាន់មានប្រធានបទខ្សោយត្រូវកែលម្អនៅឡើយទេ។',
                'text_en'       => 'No weak topics identified yet.',
            ];
        }

        $cacheKey = "weak_topics_ai_{$user->id}";
        $cached = Cache::get($cacheKey);
        if ($cached && is_array($cached)) {
            return $cached;
        }

        $topWeak = $weakTopics[0]; // Lowest score
        $fallback = [
            'isAiGenerated' => false,
            'targetTopicId' => $topWeak['id'],
            'text_km'       => "ផ្តោតលើប្រធានបទអាទិភាពខ្ពស់ «{$topWeak['title_kh']}» ជាមុនសិន ព្រោះអ្នកទទួលបានពិន្ទុ {$topWeak['score']}% ដើម្បីពង្រឹងមូលដ្ឋានរឹងមាំ។",
            'text_en'       => "Focus on the high priority topic \"{$topWeak['title_en']}\" first (current score: {$topWeak['score']}%) to build a stronger foundation.",
        ];

        // Call AI if quota permits
        try {
            $quota = $this->chatService->getQuotaStatus($user);
            if ($quota['used'] < $quota['limit']) {
                $nonPiiTopics = array_map(fn ($t) => [
                    'id'       => $t['id'],
                    'title'    => $t['title_en'],
                    'score'    => $t['score'],
                    'priority' => $t['priority'],
                ], array_slice($weakTopics, 0, 5));

                $sysPrompt = "You are the SPI E-LMS Weak Topics Learning Coach. Analyze the student's weak topics (score < 70%). Pick ONE topic ID to prioritize and give concise advice (1-2 sentences in Khmer and English). NEVER invent topic IDs. Output JSON ONLY: {\"topic_id\": int, \"advice_km\": string, \"advice_en\": string}";
                $usrPrompt = 'Student weak topics: ' . json_encode($nonPiiTopics, JSON_UNESCAPED_SLASHES);

                $aiResp = $this->chatService->generateStructuredOrCustomText($sysPrompt, $usrPrompt);
                $decoded = json_decode($aiResp['body'], true);

                if (is_array($decoded) && isset($decoded['topic_id']) && is_numeric($decoded['topic_id'])) {
                    $validIds = array_column($weakTopics, 'id');
                    $pickedId = (int) $decoded['topic_id'];
                    if (in_array($pickedId, $validIds, true)) {
                        $result = [
                            'isAiGenerated' => true,
                            'targetTopicId' => $pickedId,
                            'text_km'       => trim(strip_tags((string) ($decoded['advice_km'] ?? $fallback['text_km']))),
                            'text_en'       => trim(strip_tags((string) ($decoded['advice_en'] ?? $fallback['text_en']))),
                        ];
                        Cache::put($cacheKey, $result, 21600); // 6 hours
                        return $result;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Deterministic rule fallback
        }

        return $fallback;
    }

    protected function formatMinutes(int $minutes): string
    {
        if ($minutes <= 0) return '0m';
        $hours = intdiv($minutes, 60);
        $rem = $minutes % 60;
        if ($hours > 0 && $rem > 0) {
            return "{$hours}h {$rem}m";
        }
        if ($hours > 0) {
            return "{$hours}h";
        }
        return "{$rem}m";
    }
}
