<?php

namespace App\Services;

use App\Models\AiMessage;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AiTutorChatService
{
    public const DAILY_QUOTA = 60;
    public const MAX_HISTORY = 6;
    public const TIMEOUT_SECONDS = 20;

    protected static bool $schemaVerified = false;

    public static function resetSchemaCache(): void
    {
        self::$schemaVerified = false;
    }

    /**
     * Self-healing safeguard: create AI Tutor tables if startup migrations were bypassed on production.
     */
    public function ensureTablesExist(): bool
    {
        if (self::$schemaVerified) {
            return true;
        }

        try {
            $okConversations = $this->createTableWithFkFallback(
                'ai_conversations',
                function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                    $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete();
                    $table->string('title', 120);
                    $table->timestamp('last_message_at')->nullable();
                    $table->timestamps();
                    $table->softDeletes();

                    $table->index(['user_id', 'last_message_at']);
                },
                function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('user_id');
                    $table->unsignedBigInteger('course_id')->nullable();
                    $table->string('title', 120);
                    $table->timestamp('last_message_at')->nullable();
                    $table->timestamps();
                    $table->softDeletes();

                    $table->index(['user_id', 'last_message_at']);
                    $table->index('course_id');
                }
            );

            $okMessages = $this->createTableWithFkFallback(
                'ai_messages',
                function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
                    $table->string('role', 20);
                    $table->text('body');
                    $table->string('provider', 40)->nullable();
                    $table->string('model', 100)->nullable();
                    $table->unsignedInteger('latency_ms')->nullable();
                    $table->unsignedInteger('tokens')->nullable();
                    $table->timestamps();

                    $table->index(['conversation_id', 'id']);
                },
                function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('conversation_id');
                    $table->string('role', 20);
                    $table->text('body');
                    $table->string('provider', 40)->nullable();
                    $table->string('model', 100)->nullable();
                    $table->unsignedInteger('latency_ms')->nullable();
                    $table->unsignedInteger('tokens')->nullable();
                    $table->timestamps();

                    $table->index(['conversation_id', 'id']);
                }
            );

            $okFeedback = $this->createTableWithFkFallback(
                'ai_feedback',
                function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('message_id')->constrained('ai_messages')->cascadeOnDelete();
                    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                    $table->string('rating', 10);
                    $table->string('note', 500)->nullable();
                    $table->timestamps();

                    $table->unique(['message_id', 'user_id']);
                },
                function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('message_id');
                    $table->unsignedBigInteger('user_id');
                    $table->string('rating', 10);
                    $table->string('note', 500)->nullable();
                    $table->timestamps();

                    $table->unique(['message_id', 'user_id']);
                    $table->index('user_id');
                }
            );

            if ($okConversations && $okMessages && $okFeedback) {
                self::$schemaVerified = true;
                return true;
            }

            return false;
        } catch (\Throwable $e) {
            Log::warning('AI Tutor runtime schema check failed: ' . $e->getMessage());
            return false;
        }
    }

    protected function createTableWithFkFallback(string $tableName, \Closure $withFks, \Closure $withoutFks): bool
    {
        if (Schema::hasTable($tableName)) {
            return true;
        }

        try {
            Schema::create($tableName, $withFks);
        } catch (\Throwable $e) {
            Schema::dropIfExists($tableName);
            Schema::create($tableName, $withoutFks);
        }

        return Schema::hasTable($tableName);
    }

    /**
     * Resolve daily message quota from Admin settings or environment config.
     */
    public function getDailyQuotaLimit(): int
    {
        $limit = (int) config('services.ai_tutor.daily_quota', self::DAILY_QUOTA);

        try {
            $stored = Setting::get('ai_daily_quota');
            if (is_numeric($stored) && (int) $stored > 0) {
                $limit = (int) $stored;
            }
        } catch (\Throwable $e) {
            // Fallback to config default
        }

        return max(1, min(500, $limit));
    }

    /**
     * Count user messages sent today in Asia/Phnom_Penh (including soft-deleted conversations).
     */
    public function getQuotaStatus(User $user): array
    {
        $limit = $this->getDailyQuotaLimit();

        if (!$this->ensureTablesExist()) {
            return [
                'limit'     => $limit,
                'used'      => 0,
                'remaining' => $limit,
            ];
        }

        $used = 0;
        try {
            $tz = DashboardAnalyticsService::TIMEZONE;
            $appTz = (string) config('app.timezone', 'UTC');
            $dayStart = Carbon::now($tz)->startOfDay()->setTimezone($appTz);
            $dayEnd = Carbon::now($tz)->endOfDay()->setTimezone($appTz);

            $used = AiMessage::where('role', 'user')
                ->whereBetween('created_at', [$dayStart, $dayEnd])
                ->whereHas('conversation', function ($q) use ($user) {
                    $q->withTrashed()->where('user_id', $user->id);
                })
                ->count();
        } catch (\Throwable $e) {
            Log::warning('AI Tutor quota query warning: ' . $e->getMessage());
            $used = 0;
        }

        return [
            'limit'     => $limit,
            'used'      => $used,
            'remaining' => max(0, $limit - $used),
        ];
    }

    /**
     * Strip emails and phone numbers from student messages before sending/storing.
     */
    public function scrubPii(string $text): string
    {
        $cleaned = preg_replace(
            '/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/u',
            '[redacted-email]',
            $text
        ) ?? $text;

        $cleaned = preg_replace(
            '/(?<!\w)(?:\+?\d[\d\s\-().]{6,}\d)(?!\w)/u',
            '[redacted-phone]',
            $cleaned
        ) ?? $cleaned;

        return trim($cleaned);
    }

    /**
     * Build a structured academic prompt from real course/chapter/lesson context for quick actions.
     */
    public function buildQuickActionMessage(string $action, array $activeCourse, string $lang = 'km'): string
    {
        $courseName = $lang === 'km'
            ? ($activeCourse['title_kh'] ?: $activeCourse['title'])
            : ($activeCourse['title'] ?: $activeCourse['title_kh']);
        $lessonName = $lang === 'km'
            ? ($activeCourse['lesson_title_kh'] ?: $activeCourse['lesson_title'] ?: $courseName)
            : ($activeCourse['lesson_title'] ?: $activeCourse['lesson_title_kh'] ?: $courseName);
        $chapterName = trim((string) ($activeCourse['chapter_title'] ?? ''));
        $chapterSuffix = $chapterName !== '' ? " ({$chapterName})" : '';

        if ($lang === 'en') {
            return match ($action) {
                'explain_lesson'  => "Please explain the lesson \"{$lessonName}\"{$chapterSuffix} in the course \"{$courseName}\" step by step using clear headings, bullet points, and a summary table if helpful.",
                'generate_quiz'   => "Create 3 practice quiz questions to test my understanding of \"{$lessonName}\"{$chapterSuffix} in \"{$courseName}\", with hints (do not skip the explanations).",
                'summarize_topic' => "Summarize the key takeaways I should remember from \"{$lessonName}\"{$chapterSuffix} in \"{$courseName}\" using bullet points and a quick reference table.",
                'give_example'    => "Give me a clear practical real-world or code example illustrating \"{$lessonName}\"{$chapterSuffix} from \"{$courseName}\".",
                default           => "Help me understand \"{$lessonName}\"{$chapterSuffix} in \"{$courseName}\".",
            };
        }

        return match ($action) {
            'explain_lesson'  => "សូមពន្យល់មេរៀន «{$lessonName}»{$chapterSuffix} ក្នុងវគ្គសិក្សា «{$courseName}» ជាជំហានៗដោយប្រើចំណងជើង បញ្ជីចំណុច និងតារាងសង្ខេបងាយយល់។",
            'generate_quiz'   => "សូមបង្កើតសំណួរអនុវត្ត ៣ សំណួរសម្រាប់មេរៀន «{$lessonName}»{$chapterSuffix} នៃវគ្គ «{$courseName}» ព្រមទាំងគន្លឹះណែនាំ។",
            'summarize_topic' => "សូមសង្ខេបចំណុចគន្លឹះសំខាន់ៗនៃមេរៀន «{$lessonName}»{$chapterSuffix} ក្នុងវគ្គ «{$courseName}» ជាតារាង និងចំណុចសំខាន់ៗ។",
            'give_example'    => "សូមលើកឧទាហរណ៍អនុវត្តជាក់ស្តែងសម្រាប់មេរៀន «{$lessonName}»{$chapterSuffix} ក្នុងវគ្គ «{$courseName}»។",
            default           => "សូមជួយពន្យល់មេរៀន «{$lessonName}»{$chapterSuffix} ក្នុងវគ្គ «{$courseName}»។",
        };
    }

    /**
     * Strip dangerous XSS payloads before saving and before rendering Markdown.
     */
    public function sanitizeAssistantText(string $raw): string
    {
        $clean = preg_replace('/<script\b[^>]*>[\s\S]*?<\/script>/i', '', $raw) ?? $raw;
        $clean = preg_replace('/<iframe\b[^>]*>[\s\S]*?<\/iframe>/i', '', $clean) ?? $clean;
        $clean = preg_replace('/<style\b[^>]*>[\s\S]*?<\/style>/i', '', $clean) ?? $clean;
        $clean = preg_replace('/<(?:script|iframe|object|embed)\b[^>]*>/i', '', $clean) ?? $clean;
        $clean = preg_replace('/\son[a-z]+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean) ?? $clean;
        $clean = preg_replace('/(?:javascript|vbscript):/i', 'blocked:', $clean) ?? $clean;

        return trim($clean);
    }

    /**
     * Render Markdown to XSS-safe HTML (strips HTML input & disallows unsafe links).
     */
    public function renderSafeHtml(string $markdown): string
    {
        $clean = $this->sanitizeAssistantText($markdown);

        try {
            return trim(Str::markdown($clean, [
                'html_input'         => 'strip',
                'allow_unsafe_links' => false,
            ]));
        } catch (\Throwable $e) {
            return nl2br(e($clean));
        }
    }

    protected function getConfiguredSetting(string $dbKey, ?string $configKey = null, string $default = ''): string
    {
        if ($configKey !== null) {
            $cfg = trim((string) config($configKey, ''));
            if ($cfg !== '') {
                return $cfg;
            }
        }

        try {
            $stored = Setting::get($dbKey);
            if (is_string($stored) && trim($stored) !== '') {
                return trim($stored);
            }
        } catch (\Throwable $e) {
            // Fall through to default
        }

        return $default;
    }

    /**
     * Call configured real AI providers in order: Gemini -> OpenAI -> Cloudflare Workers AI.
     *
     * @param array<int, array{role: string, body: string}> $historyMessages
     * @return array{body: string, provider: string, model: string, latency_ms: int, tokens: int|null}
     */
    public function generateReply(array $historyMessages, string $cleanUserPrompt, ?array $safeCourseContext = null): array
    {
        $systemPrompt = $this->buildSystemPrompt($safeCourseContext);
        $trimmedHistory = array_slice($historyMessages, -self::MAX_HISTORY);

        // 1. Primary Provider: Google Gemini API
        $geminiKey = $this->getConfiguredSetting('gemini_api_key', 'services.gemini.api_key');
        if ($geminiKey !== '') {
            $geminiResult = $this->callGemini($geminiKey, $systemPrompt, $trimmedHistory, $cleanUserPrompt);
            if ($geminiResult !== null) {
                return $geminiResult;
            }
        }

        // 2. Secondary Provider: OpenAI API (or OpenAI-compatible endpoint)
        $openAiKey = $this->getConfiguredSetting('openai_api_key', 'services.openai.api_key');
        if ($openAiKey !== '') {
            $openAiResult = $this->callOpenAi($openAiKey, $systemPrompt, $trimmedHistory, $cleanUserPrompt);
            if ($openAiResult !== null) {
                return $openAiResult;
            }
        }

        // 3. Fallback Provider: Cloudflare Workers AI
        $cfAccountId = $this->getConfiguredSetting('cloudflare_account_id', 'services.cloudflare.account_id');
        $cfToken = $this->getConfiguredSetting('cloudflare_ai_token', 'services.cloudflare.ai_token');
        if ($cfAccountId !== '' && $cfToken !== '') {
            $cfResult = $this->callWorkersAi($cfAccountId, $cfToken, $systemPrompt, $trimmedHistory, $cleanUserPrompt);
            if ($cfResult !== null) {
                return $cfResult;
            }
        }

        throw new \RuntimeException('AI_PROVIDER_UNAVAILABLE');
    }

    /**
     * Execute a custom system + user prompt through configured real AI providers
     * (Gemini -> OpenAI -> Cloudflare Workers AI).
     *
     * @return array{body: string, provider: string, model: string, latency_ms: int, tokens: int|null}
     */
    public function generateStructuredOrCustomText(string $systemPrompt, string $userPrompt): array
    {
        $geminiKey = $this->getConfiguredSetting('gemini_api_key', 'services.gemini.api_key');
        if ($geminiKey !== '') {
            $geminiResult = $this->callGemini($geminiKey, $systemPrompt, [], $userPrompt);
            if ($geminiResult !== null) {
                return $geminiResult;
            }
        }

        $openAiKey = $this->getConfiguredSetting('openai_api_key', 'services.openai.api_key');
        if ($openAiKey !== '') {
            $openAiResult = $this->callOpenAi($openAiKey, $systemPrompt, [], $userPrompt);
            if ($openAiResult !== null) {
                return $openAiResult;
            }
        }

        $cfAccountId = $this->getConfiguredSetting('cloudflare_account_id', 'services.cloudflare.account_id');
        $cfToken = $this->getConfiguredSetting('cloudflare_ai_token', 'services.cloudflare.ai_token');
        if ($cfAccountId !== '' && $cfToken !== '') {
            $cfResult = $this->callWorkersAi($cfAccountId, $cfToken, $systemPrompt, [], $userPrompt);
            if ($cfResult !== null) {
                return $cfResult;
            }
        }

        throw new \RuntimeException('AI_PROVIDER_UNAVAILABLE');
    }

    protected function buildSystemPrompt(?array $ctx): string
    {
        $base = "You are the Saint Paul Institute (SPI) E-LMS AI Academic Tutor.\n"
            . "Rules:\n"
            . "- Always respond in the same language the student uses (natural Khmer or English).\n"
            . "- Format replies in rich, readable GitHub-Flavored Markdown: use headings (## / ###), bullet points, numbered steps, Markdown tables where helpful, and fenced code blocks with language tags.\n"
            . "- Base explanations directly on the student's SPI course, chapter, and lesson content when provided.\n"
            . "- Academic Integrity: NEVER hand over complete direct answers to full graded exams or assignments. Instead, explain core principles, give guided hints, and walk through examples step by step.";

        try {
            $rules = app(AiRecommendationService::class)->getActiveRules();
            $adv = (int) ($rules['advance_next']['min_score'] ?? 80);
            $rev = (int) ($rules['review_current']['min_score'] ?? 50);
            $rem = (int) ($rules['remedial']['max_score'] ?? 49);
            $idle = (int) ($rules['re_engage']['max_idle_days'] ?? 3);
            $base .= "\n- SPI Academic Policy Thresholds: Advance >= {$adv}%, Review >= {$rev}%, Remedial <= {$rem}%, Re-engagement nudge after {$idle} idle days. Tailor encouragement and difficulty accordingly.";
        } catch (\Throwable $e) {
            // Skip rule hints if unavailable
        }

        if ($ctx && !empty($ctx['course_title'])) {
            $base .= "\n\nCurrent SPI Study Context (Non-PII):"
                . "\n- Course: " . $ctx['course_title']
                . (!empty($ctx['course_description']) ? "\n- Course Description: " . $ctx['course_description'] : '')
                . (!empty($ctx['module_title']) ? "\n- Module: " . $ctx['module_title'] : '')
                . (!empty($ctx['chapter_title']) ? "\n- Chapter: " . $ctx['chapter_title'] : '')
                . (!empty($ctx['lesson_title']) ? "\n- Current Lesson: " . $ctx['lesson_title'] : '')
                . "\n- Course Progress: " . ((int) ($ctx['progress_percent'] ?? 0)) . '%'
                . (!empty($ctx['lesson_objectives']) ? "\n- Learning Objectives: " . $ctx['lesson_objectives'] : '')
                . (!empty($ctx['course_outline']) && is_array($ctx['course_outline'])
                    ? "\n- Course Outline: " . implode(' | ', array_slice($ctx['course_outline'], 0, 12))
                    : '')
                . (!empty($ctx['lesson_excerpt']) ? "\n- Lesson Content Excerpt:\n" . $ctx['lesson_excerpt'] : '');
        }

        return $base;
    }

    protected function buildChatMessages(string $systemPrompt, array $history, string $userPrompt): array
    {
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];
        foreach ($history as $msg) {
            $role = ($msg['role'] ?? 'user') === 'assistant' ? 'assistant' : 'user';
            $text = trim((string) ($msg['body'] ?? ''));
            if ($text !== '') {
                $messages[] = ['role' => $role, 'content' => $text];
            }
        }
        $messages[] = ['role' => 'user', 'content' => $userPrompt];

        return $messages;
    }

    protected function callGemini(string $apiKey, string $systemPrompt, array $history, string $userPrompt): ?array
    {
        $configuredModel = $this->getConfiguredSetting('gemini_model', 'services.gemini.model', 'gemini-2.5-flash');
        $modelsToTry = array_values(array_unique([$configuredModel, 'gemini-2.0-flash']));

        $contents = [];
        foreach ($history as $msg) {
            $role = ($msg['role'] ?? 'user') === 'assistant' ? 'model' : 'user';
            $text = trim((string) ($msg['body'] ?? ''));
            if ($text !== '') {
                $contents[] = [
                    'role'  => $role,
                    'parts' => [['text' => $text]],
                ];
            }
        }
        $contents[] = [
            'role'  => 'user',
            'parts' => [['text' => $userPrompt]],
        ];

        foreach ($modelsToTry as $model) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
            $started = microtime(true);
            try {
                $response = Http::timeout(self::TIMEOUT_SECONDS)
                    ->withHeaders([
                        'x-goog-api-key' => $apiKey,
                        'Content-Type'   => 'application/json',
                    ])
                    ->post($url, [
                        'systemInstruction' => [
                            'parts' => [['text' => $systemPrompt]],
                        ],
                        'contents'         => $contents,
                        'generationConfig' => [
                            'temperature'     => 0.5,
                            'maxOutputTokens' => 1200,
                        ],
                    ]);

                $latencyMs = (int) round((microtime(true) - $started) * 1000);
                if (!$response->successful()) {
                    Log::warning("Gemini API ({$model}) returned HTTP " . $response->status());
                    if ($response->status() === 404) {
                        continue;
                    }
                    return null;
                }

                $json = $response->json();
                $parts = $json['candidates'][0]['content']['parts'] ?? [];
                $textParts = [];
                foreach ($parts as $p) {
                    if (!empty($p['text']) && is_string($p['text'])) {
                        $textParts[] = $p['text'];
                    }
                }
                $rawText = trim(implode("\n", $textParts));
                if ($rawText === '') {
                    return null;
                }

                $tokens = isset($json['usageMetadata']['totalTokenCount'])
                    ? (int) $json['usageMetadata']['totalTokenCount']
                    : null;

                return [
                    'body'       => $this->sanitizeAssistantText($rawText),
                    'provider'   => 'gemini',
                    'model'      => $model,
                    'latency_ms' => max(1, $latencyMs),
                    'tokens'     => $tokens,
                ];
            } catch (\Throwable $e) {
                Log::warning("Gemini API ({$model}) request exception: " . $e->getMessage());
                return null;
            }
        }

        return null;
    }

    protected function callOpenAi(string $apiKey, string $systemPrompt, array $history, string $userPrompt): ?array
    {
        $model = $this->getConfiguredSetting('openai_model', 'services.openai.model', 'gpt-4o-mini');
        $baseUrl = rtrim($this->getConfiguredSetting('openai_base_url', 'services.openai.base_url', 'https://api.openai.com/v1'), '/');
        $url = "{$baseUrl}/chat/completions";

        $started = microtime(true);
        try {
            $response = Http::withToken($apiKey)
                ->timeout(self::TIMEOUT_SECONDS)
                ->post($url, [
                    'model'       => $model,
                    'messages'    => $this->buildChatMessages($systemPrompt, $history, $userPrompt),
                    'temperature' => 0.5,
                    'max_tokens'  => 1200,
                ]);

            $latencyMs = (int) round((microtime(true) - $started) * 1000);
            if (!$response->successful()) {
                Log::warning("OpenAI API ({$model}) returned HTTP " . $response->status());
                return null;
            }

            $json = $response->json() ?? [];
            $rawText = trim((string) ($json['choices'][0]['message']['content'] ?? ''));
            if ($rawText === '') {
                return null;
            }

            $tokens = isset($json['usage']['total_tokens'])
                ? (int) $json['usage']['total_tokens']
                : null;

            return [
                'body'       => $this->sanitizeAssistantText($rawText),
                'provider'   => 'openai',
                'model'      => $model,
                'latency_ms' => max(1, $latencyMs),
                'tokens'     => $tokens,
            ];
        } catch (\Throwable $e) {
            Log::warning('OpenAI API request exception: ' . $e->getMessage());
            return null;
        }
    }

    protected function callWorkersAi(string $accountId, string $token, string $systemPrompt, array $history, string $userPrompt): ?array
    {
        $configuredModel = $this->getConfiguredSetting('cloudflare_default_model', 'services.cloudflare.default_model', '@cf/meta/llama-3.1-8b-instruct');
        $gateway = $this->getConfiguredSetting('cloudflare_ai_gateway', 'services.cloudflare.ai_gateway', 'spilms-ai-gateway');
        $models = array_values(array_unique([$configuredModel, '@cf/meta/llama-3.2-3b-instruct']));
        $messages = $this->buildChatMessages($systemPrompt, $history, $userPrompt);

        foreach ($models as $model) {
            $urls = [];
            if ($gateway !== '') {
                $urls[] = "https://gateway.ai.cloudflare.com/v1/{$accountId}/{$gateway}/workers-ai/{$model}";
            }
            $urls[] = "https://api.cloudflare.com/client/v4/accounts/{$accountId}/ai/run/{$model}";

            foreach ($urls as $url) {
                $started = microtime(true);
                try {
                    $response = Http::withToken($token)
                        ->timeout(self::TIMEOUT_SECONDS)
                        ->post($url, [
                            'messages'    => $messages,
                            'temperature' => 0.5,
                            'max_tokens'  => 1200,
                        ]);

                    $latencyMs = (int) round((microtime(true) - $started) * 1000);
                    if (!$response->successful()) {
                        Log::warning("Workers AI ({$model}) returned HTTP {$response->status()} on {$url}");
                        continue;
                    }

                    $json = $response->json() ?? [];
                    $rawText = (string) (
                        $json['result']['choices'][0]['message']['content']
                        ?? $json['result']['response']
                        ?? $json['response']
                        ?? ''
                    );
                    $rawText = trim($rawText);
                    if ($rawText === '') {
                        continue;
                    }

                    $tokens = isset($json['result']['usage']['total_tokens'])
                        ? (int) $json['result']['usage']['total_tokens']
                        : null;

                    return [
                        'body'       => $this->sanitizeAssistantText($rawText),
                        'provider'   => 'workers_ai',
                        'model'      => $model,
                        'latency_ms' => max(1, $latencyMs),
                        'tokens'     => $tokens,
                    ];
                } catch (\Throwable $e) {
                    Log::warning("Workers AI request exception on {$url}: " . $e->getMessage());
                }
            }
        }

        return null;
    }
}
