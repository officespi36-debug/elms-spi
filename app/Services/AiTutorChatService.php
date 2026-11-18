<?php

namespace App\Services;

use App\Models\AiMessage;
use App\Models\User;
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
     * Count user messages sent today (including soft-deleted conversations so quota cannot be bypassed).
     */
    public function getQuotaStatus(User $user): array
    {
        if (!$this->ensureTablesExist()) {
            return [
                'limit'     => self::DAILY_QUOTA,
                'used'      => 0,
                'remaining' => self::DAILY_QUOTA,
            ];
        }

        $used = 0;
        try {
            $used = AiMessage::where('role', 'user')
                ->where('created_at', '>=', now()->startOfDay())
                ->whereHas('conversation', function ($q) use ($user) {
                    $q->withTrashed()->where('user_id', $user->id);
                })
                ->count();
        } catch (\Throwable $e) {
            Log::warning('AI Tutor quota query warning: ' . $e->getMessage());
            $used = 0;
        }

        return [
            'limit'     => self::DAILY_QUOTA,
            'used'      => $used,
            'remaining' => max(0, self::DAILY_QUOTA - $used),
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
     * Build a structured academic prompt from real course/lesson context for quick actions.
     */
    public function buildQuickActionMessage(string $action, array $activeCourse, string $lang = 'km'): string
    {
        $courseName = $lang === 'km'
            ? ($activeCourse['title_kh'] ?: $activeCourse['title'])
            : ($activeCourse['title'] ?: $activeCourse['title_kh']);
        $lessonName = $lang === 'km'
            ? ($activeCourse['lesson_title_kh'] ?: $activeCourse['lesson_title'] ?: $courseName)
            : ($activeCourse['lesson_title'] ?: $activeCourse['lesson_title_kh'] ?: $courseName);

        if ($lang === 'en') {
            return match ($action) {
                'explain_lesson'  => "Please explain the lesson \"{$lessonName}\" in the course \"{$courseName}\" step by step with simple concepts.",
                'generate_quiz'   => "Create 3 practice quiz questions to test my understanding of \"{$lessonName}\" in \"{$courseName}\", along with hints.",
                'summarize_topic' => "Summarize the key takeaways I should remember from \"{$lessonName}\" in \"{$courseName}\".",
                'give_example'    => "Give me a clear practical example illustrating \"{$lessonName}\" from \"{$courseName}\".",
                default           => "Help me understand \"{$lessonName}\" in \"{$courseName}\".",
            };
        }

        return match ($action) {
            'explain_lesson'  => "សូមពន្យល់មេរៀន «{$lessonName}» ក្នុងវគ្គសិក្សា «{$courseName}» ជាជំហានៗដោយងាយយល់។",
            'generate_quiz'   => "សូមបង្កើតសំណួរអនុវត្ត ៣ សំណួរសម្រាប់មេរៀន «{$lessonName}» នៃវគ្គ «{$courseName}» ព្រមទាំងគន្លឹះណែនាំ។",
            'summarize_topic' => "សូមសង្ខេបចំណុចសំខាន់ៗនៃមេរៀន «{$lessonName}» ក្នុងវគ្គ «{$courseName}»។",
            'give_example'    => "សូមលើកឧទាហរណ៍ជាក់ស្តែងសម្រាប់មេរៀន «{$lessonName}» ក្នុងវគ្គ «{$courseName}»។",
            default           => "សូមជួយពន្យល់មេរៀន «{$lessonName}» ក្នុងវគ្គ «{$courseName}»។",
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

    /**
     * Call configured AI provider (Gemini primary -> Cloudflare Workers AI fallback).
     *
     * @param array<int, array{role: string, body: string}> $historyMessages
     * @return array{body: string, provider: string, model: string, latency_ms: int, tokens: int|null}
     */
    public function generateReply(array $historyMessages, string $cleanUserPrompt, ?array $safeCourseContext = null): array
    {
        $systemPrompt = $this->buildSystemPrompt($safeCourseContext);
        $trimmedHistory = array_slice($historyMessages, -self::MAX_HISTORY);

        // 1. Primary Provider: Google Gemini API (when key configured)
        $geminiKey = (string) config('services.gemini.api_key', '');
        if ($geminiKey !== '') {
            $geminiResult = $this->callGemini($geminiKey, $systemPrompt, $trimmedHistory, $cleanUserPrompt);
            if ($geminiResult !== null) {
                return $geminiResult;
            }
        }

        // 2. Fallback Provider: Cloudflare Workers AI
        $cfAccountId = (string) config('services.cloudflare.account_id', '');
        $cfToken = (string) config('services.cloudflare.ai_token', '');
        if ($cfAccountId !== '' && $cfToken !== '') {
            $cfResult = $this->callWorkersAi($cfAccountId, $cfToken, $systemPrompt, $trimmedHistory, $cleanUserPrompt);
            if ($cfResult !== null) {
                return $cfResult;
            }
        }

        throw new \RuntimeException('AI_PROVIDER_UNAVAILABLE');
    }

    protected function buildSystemPrompt(?array $ctx): string
    {
        $base = "You are the Saint Paul Institute (SPI) E-LMS AI Study Assistant.\n"
            . "Rules:\n"
            . "- Respond in the same language the student uses (Khmer or English).\n"
            . "- Focus on helping the student understand concepts step by step with clear explanations and practical examples.\n"
            . "- Academic Integrity: NEVER hand over complete direct answers to full graded exams or assignments. Instead, explain concepts, give hints, and walk through guiding steps.\n"
            . "- Use clean Markdown formatting and fenced code blocks when showing code.";

        if ($ctx && !empty($ctx['course_title'])) {
            $base .= "\n\nCurrent Study Context (Non-PII):"
                . "\n- Course: " . $ctx['course_title']
                . (!empty($ctx['module_title']) ? "\n- Module: " . $ctx['module_title'] : '')
                . (!empty($ctx['lesson_title']) ? "\n- Lesson: " . $ctx['lesson_title'] : '')
                . "\n- Course Progress: " . ((int) ($ctx['progress_percent'] ?? 0)) . '%'
                . (!empty($ctx['lesson_excerpt']) ? "\n- Lesson Excerpt: " . $ctx['lesson_excerpt'] : '');
        }

        return $base;
    }

    protected function callGemini(string $apiKey, string $systemPrompt, array $history, string $userPrompt): ?array
    {
        $model = (string) config('services.gemini.model', 'gemini-2.5-flash');
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

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
                        'maxOutputTokens' => 1024,
                    ],
                ]);

            $latencyMs = (int) round((microtime(true) - $started) * 1000);
            if (!$response->successful()) {
                Log::warning('Gemini API returned HTTP ' . $response->status());
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
            Log::warning('Gemini API request exception: ' . $e->getMessage());
            return null;
        }
    }

    protected function callWorkersAi(string $accountId, string $token, string $systemPrompt, array $history, string $userPrompt): ?array
    {
        $model = (string) config('services.cloudflare.default_model', '@cf/meta/llama-3.1-8b-instruct');
        $gateway = (string) config('services.cloudflare.ai_gateway', 'spilms-ai-gateway');

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
                        'max_tokens'  => 1024,
                    ]);

                $latencyMs = (int) round((microtime(true) - $started) * 1000);
                if (!$response->successful()) {
                    Log::warning("Workers AI returned HTTP {$response->status()} on {$url}");
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

        return null;
    }
}
