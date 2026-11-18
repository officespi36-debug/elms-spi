<?php

namespace App\Services;

use App\Models\AiRecommendation;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AiLearningPathService
{
    public function __construct(
        protected AiTutorChatService $chatService,
        protected AiRecommendationService $ruleService
    ) {}

    /**
     * Self-healing schema check so ai_recommendations always accepts structured ai_payload on production.
     */
    public function ensureSchemaReady(): void
    {
        try {
            if (Schema::hasTable('ai_recommendations') && !Schema::hasColumn('ai_recommendations', 'ai_payload')) {
                Schema::table('ai_recommendations', function (Blueprint $table) {
                    $table->text('ai_payload')->nullable()->after('reason');
                });
            }
        } catch (\Throwable $e) {
            Log::warning('AI Path schema check warning: ' . $e->getMessage());
        }
    }

    /**
     * Analyze the student's real database snapshot using Real AI API and persist in ai_recommendations.
     *
     * @throws \RuntimeException when all configured AI providers are unavailable
     */
    public function analyzeAndPersist(User $user, array $snapshot): AiRecommendation
    {
        $this->ensureSchemaReady();

        $systemPrompt = $this->buildPathAnalysisSystemPrompt();
        $userPrompt = "Analyze this non-PII academic snapshot from SPI E-LMS (MySQL `elms-system`) and return ONLY the JSON roadmap recommendation object:\n"
            . json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $aiResult = $this->chatService->generateStructuredOrCustomText($systemPrompt, $userPrompt);
        $parsed = $this->parseAiResponse($aiResult['body'], $snapshot);

        // Validate recommended_course_id and recommended_lesson_id against real DB IDs present in snapshot
        $validCourseIds = $this->extractValidCourseIds($snapshot);
        $validLessonIds = $this->extractValidLessonIds($snapshot);

        $recCourseId = isset($parsed['recommended_course_id']) && is_numeric($parsed['recommended_course_id'])
            ? (int) $parsed['recommended_course_id']
            : null;
        if ($recCourseId !== null && !in_array($recCourseId, $validCourseIds, true)) {
            $recCourseId = null;
        }

        $recLessonId = isset($parsed['recommended_lesson_id']) && is_numeric($parsed['recommended_lesson_id'])
            ? (int) $parsed['recommended_lesson_id']
            : null;
        if ($recLessonId !== null && !in_array($recLessonId, $validLessonIds, true)) {
            $recLessonId = null;
        }

        $tone = in_array($parsed['tone'] ?? '', ['purple', 'amber', 'emerald', 'neutral'], true)
            ? $parsed['tone']
            : (($snapshot['has_enrollments'] ?? false) ? 'purple' : 'neutral');

        $payload = [
            'badge_km'              => $this->cleanText($parsed['badge_km'] ?? 'ការណែនាំពី AI', 60),
            'badge_en'              => $this->cleanText($parsed['badge_en'] ?? 'AI Recommendation', 60),
            'title_km'              => $this->cleanText($parsed['title_km'] ?? '', 220),
            'title_en'              => $this->cleanText($parsed['title_en'] ?? '', 220),
            'desc_km'               => $this->cleanText($parsed['desc_km'] ?? '', 500),
            'desc_en'               => $this->cleanText($parsed['desc_en'] ?? '', 500),
            'next_step_title_km'    => $this->cleanText($parsed['next_step_title_km'] ?? '', 220),
            'next_step_title_en'    => $this->cleanText($parsed['next_step_title_en'] ?? '', 220),
            'next_step_reason_km'   => $this->cleanText($parsed['next_step_reason_km'] ?? '', 500),
            'next_step_reason_en'   => $this->cleanText($parsed['next_step_reason_en'] ?? '', 500),
            'recommended_course_id' => $recCourseId,
            'recommended_lesson_id' => $recLessonId,
            'tone'                  => $tone,
            'provider'              => $aiResult['provider'],
            'model'                 => $aiResult['model'],
            'analyzed_at'           => now()->toIso8601String(),
        ];

        try {
            $this->ruleService->analyzeAndRecommend($user);
        } catch (\Throwable $e) {
            Log::warning('AI Rule evaluation non-blocking warning: ' . $e->getMessage());
        }

        $reasonSummary = $payload['title_km'] !== ''
            ? $payload['title_km']
            : ($payload['title_en'] !== '' ? $payload['title_en'] : $payload['desc_km']);
        if ($reasonSummary === '') {
            $reasonSummary = Str::limit(trim($this->chatService->sanitizeAssistantText($aiResult['body'])), 240);
        }

        // Preserve history while superseding prior active path analysis rows for this student
        AiRecommendation::where('user_id', $user->id)
            ->where('type', 'ai_path')
            ->where('is_dismissed', false)
            ->update(['is_dismissed' => true]);

        $attributes = [
            'user_id'      => $user->id,
            'lesson_id'    => $recLessonId,
            'type'         => 'ai_path',
            'reason'       => Str::limit($reasonSummary, 245, '...'),
            'is_dismissed' => false,
        ];

        if (Schema::hasColumn('ai_recommendations', 'ai_payload')) {
            $attributes['ai_payload'] = $payload;
        }

        return AiRecommendation::create($attributes);
    }

    protected function buildPathAnalysisSystemPrompt(): string
    {
        return <<<'PROMPT'
You are the Saint Paul Institute (SPI) E-LMS AI Academic Learning Path Advisor.
Analyze the student's real academic data (Major, Faculty, Academic Year, Enrolled Courses, Lessons Progress, Quiz Scores, Weekly Study Time, and Available Catalog Courses) and produce a concrete, accurate, bilingual (Khmer & English) personalized learning path recommendation.

Strict Rules:
1. Reference ONLY real courses, lessons, majors, scores, and counts present in the input JSON. NEVER invent fake courses, fake dates, fake scores, or personal user info.
2. If `has_enrollments` is false: encourage the student to enroll in their first course (mentioning a real `available_catalog_courses` course if provided, or advising browsing the catalog & asking AI Tutor). Leave `recommended_lesson_id` as null.
3. If `has_enrollments` is true and any quiz score is low (< 70%), prioritize reviewing that weak quiz/course first (`tone`: "amber").
4. Otherwise recommend continuing the active incomplete lesson or advancing to the next logical module/course (`tone`: "purple" or "emerald").
5. Respond strictly with a single valid JSON object (no surrounding markdown prose) containing these exact keys:
{
  "badge_km": "short Khmer status badge (2-4 words)",
  "badge_en": "short English status badge (2-4 words)",
  "title_km": "concise Khmer action headline",
  "title_en": "concise English action headline",
  "desc_km": "1-2 clear Khmer sentences grounded in real progress/quiz numbers",
  "desc_en": "1-2 clear English sentences grounded in real progress/quiz numbers",
  "next_step_title_km": "short Khmer next-step card title",
  "next_step_title_en": "short English next-step card title",
  "next_step_reason_km": "concise Khmer explanation for why this step is next",
  "next_step_reason_en": "concise English explanation for why this step is next",
  "recommended_course_id": integer course_id from input JSON or null,
  "recommended_lesson_id": integer lesson_id from input JSON or null,
  "tone": "purple" | "amber" | "emerald" | "neutral"
}
PROMPT;
    }

    protected function parseAiResponse(string $rawBody, array $snapshot): array
    {
        $clean = trim($rawBody);
        if (preg_match('/```(?:json)?\s*(\{[\s\S]*\})\s*```/i', $clean, $matches)) {
            $clean = $matches[1];
        } elseif (preg_match('/(\{[\s\S]*\})/u', $clean, $matches)) {
            $clean = $matches[1];
        }

        $decoded = json_decode($clean, true);
        if (is_array($decoded) && (!empty($decoded['title_km']) || !empty($decoded['title_en']) || !empty($decoded['desc_km']) || !empty($decoded['desc_en']))) {
            return $decoded;
        }

        // Plain-text fallback if a model answered without JSON formatting (still 100% genuine AI response)
        $prose = trim($this->chatService->sanitizeAssistantText($rawBody));
        $firstLine = Str::limit(trim(strtok($prose, "\n") ?: $prose), 140);
        return [
            'badge_km'              => 'ការវិភាគ AI',
            'badge_en'              => 'AI Analysis',
            'title_km'              => $firstLine,
            'title_en'              => $firstLine,
            'desc_km'               => Str::limit($prose, 420),
            'desc_en'               => Str::limit($prose, 420),
            'next_step_title_km'    => $firstLine,
            'next_step_title_en'    => $firstLine,
            'next_step_reason_km'   => Str::limit($prose, 350),
            'next_step_reason_en'   => Str::limit($prose, 350),
            'recommended_course_id' => null,
            'recommended_lesson_id' => null,
            'tone'                  => ($snapshot['has_enrollments'] ?? false) ? 'purple' : 'neutral',
        ];
    }

    protected function extractValidCourseIds(array $snapshot): array
    {
        $ids = [];
        foreach (($snapshot['enrolled_courses'] ?? []) as $c) {
            if (!empty($c['course_id'])) {
                $ids[] = (int) $c['course_id'];
            }
        }
        foreach (($snapshot['available_catalog_courses'] ?? []) as $c) {
            if (!empty($c['course_id'])) {
                $ids[] = (int) $c['course_id'];
            }
        }
        return array_values(array_unique($ids));
    }

    protected function extractValidLessonIds(array $snapshot): array
    {
        $ids = [];
        foreach (($snapshot['enrolled_courses'] ?? []) as $c) {
            if (!empty($c['next_lesson_id'])) {
                $ids[] = (int) $c['next_lesson_id'];
            }
            foreach (($c['lesson_ids'] ?? []) as $lid) {
                $ids[] = (int) $lid;
            }
        }
        return array_values(array_unique($ids));
    }

    protected function cleanText(mixed $val, int $limit): string
    {
        if (!is_scalar($val)) {
            return '';
        }
        $clean = $this->chatService->sanitizeAssistantText((string) $val);
        $clean = preg_replace('/\s+/u', ' ', trim(strip_tags($clean))) ?? '';
        return Str::limit($clean, $limit, '...');
    }
}
