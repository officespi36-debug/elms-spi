<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateQuizJob;
use App\Jobs\GenerateRecommendationJob;
use App\Jobs\SummarizeLessonJob;
use App\Models\Course;
use App\Models\Lesson;
use App\Services\CloudflareAIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CloudflareAIController extends Controller
{
    public function __construct(
        protected CloudflareAIService $aiService
    ) {}

    /**
     * 1. Test & Verify Cloudflare Token & Gateway
     */
    public function verify(Request $request): JsonResponse
    {
        $res = $this->aiService->verifyToken();

        return response()->json([
            'status' => $res['success'] ? 'connected' : 'error',
            'token_verified' => $res['success'],
            'account_id' => config('services.cloudflare.account_id'),
            'gateway' => config('services.cloudflare.ai_gateway'),
            'default_model' => config('services.cloudflare.default_model'),
            'details' => $res['data'] ?? null,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * 2. 24/7 AI Tutor Q&A Chat
     */
    public function chat(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'message' => 'required|string|max:2000',
                'history' => 'nullable|array',
                'course_id' => 'nullable|integer',
                'lesson_id' => 'nullable|integer',
                'major' => 'nullable|string',
            ]);

            $user = $request->user();
            $course = !empty($validated['course_id']) ? Course::find($validated['course_id']) : null;
            $lesson = !empty($validated['lesson_id']) ? Lesson::find($validated['lesson_id']) : null;

            $majorName = $validated['major'] ?? ($user?->major?->name ?? 'General Education');
            $courseContext = $course ? "Course: {$course->title}. " : '';
            $lessonContext = $lesson ? "Lesson: {$lesson->title}. " : '';

            $systemPrompt = "You are an intelligent, friendly AI Tutor at Saint Paul Institute (SPI E-LMS).
Major: {$majorName}. {$courseContext}{$lessonContext}
Answer clearly and pedagogically. Provide explanations, structured points, and practical examples.
You can respond in Khmer or English matching the student's question language.";

            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
            ];

            if (!empty($validated['history']) && is_array($validated['history'])) {
                foreach (array_slice($validated['history'], -6) as $h) {
                    if (isset($h['sender']) && isset($h['text'])) {
                        $role = $h['sender'] === 'user' ? 'user' : 'assistant';
                        $messages[] = ['role' => $role, 'content' => (string) $h['text']];
                    } elseif (isset($h['role']) && isset($h['content'])) {
                        $messages[] = ['role' => $h['role'], 'content' => (string) $h['content']];
                    } elseif (isset($h['role']) && isset($h['text'])) {
                        $messages[] = ['role' => $h['role'], 'content' => (string) $h['text']];
                    }
                }
            }

            $messages[] = ['role' => 'user', 'content' => $validated['message']];

            $reply = $this->aiService->chat($messages);

            return response()->json([
                'success' => true,
                'reply' => $reply,
                'time' => now()->format('h:i A'),
                'model' => config('services.cloudflare.default_model', '@cf/meta/llama-3.1-8b-instruct'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $e) {
            $userMsg = $request->input('message', '');
            return response()->json([
                'success' => true,
                'reply' => "🎓 **SPI AI Tutor 24/7 (Saint Paul Institute)**\n\nទាក់ទងនឹងសំណួររបស់អ្នក៖ *\"" . \Illuminate\Support\Str::limit($userMsg, 120) . "\"*\n\n💡 **ការពន្យល់ និងការណែនាំ៖**\n1. សូមពិនិត្យមើលខ្លឹមសារមេរៀន និងស្លាយបទបង្ហាញក្នុងផ្ទាំងសិក្សា។\n2. សាកល្បងអនុវត្តជាក់ស្តែងក្នុង Practice Lab តាមមុខវិជ្ជា។\n3. ប្រសិនបើមានចម្ងល់បន្ថែម អ្នកអាចសួរគ្រូបង្រៀនផ្ទាល់ក្នុងប្រអប់ Course Discussion!",
                'time' => now()->format('h:i A'),
                'model' => 'fallback-tutor',
            ]);
        }
    }

    /**
     * 3. Major-Tailored Recommendations (IT, English, Agri, Social Work, Tourism)
     */
    public function recommendation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'major' => 'nullable|string',
            'lesson_title' => 'nullable|string',
            'technology' => 'nullable|string',
            'quiz_score' => 'nullable|integer|min:0|max:100',
            'lesson_id' => 'nullable|integer',
            'async' => 'nullable|boolean',
        ]);

        $user = $request->user();
        $major = $validated['major'] ?? ($user?->major?->name ?? 'Information Technology');
        $lessonTitle = $validated['lesson_title'] ?? 'Core Module';
        $technology = $validated['technology'] ?? null;
        $score = (int) ($validated['quiz_score'] ?? 80);

        if (!empty($validated['async']) && $user) {
            GenerateRecommendationJob::dispatch($user->id, $validated['lesson_id'] ?? null, $score);
            return response()->json([
                'success' => true,
                'status' => 'queued',
                'message' => 'AI recommendation generation dispatched to queue.'
            ]);
        }

        $recommendation = $this->aiService->generateRecommendation($major, $lessonTitle, $technology, $score);

        return response()->json([
            'success' => true,
            'recommendation' => $recommendation,
        ]);
    }

    /**
     * 4. Teacher / Student MCQ Quiz Generation
     */
    public function generateQuiz(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => 'nullable|integer',
            'lesson_id' => 'nullable|integer',
            'topic' => 'nullable|string',
            'num_questions' => 'nullable|integer|min:1|max:10',
            'major' => 'nullable|string',
            'async' => 'nullable|boolean',
        ]);

        $course = !empty($validated['course_id']) ? Course::find($validated['course_id']) : Course::first();
        $lesson = !empty($validated['lesson_id']) ? Lesson::find($validated['lesson_id']) : null;
        $topic = $validated['topic'] ?: ($lesson ? $lesson->title : ($course ? $course->title : 'SPI Academic Module'));
        $numQuestions = (int) ($validated['num_questions'] ?? 4);
        $major = $validated['major'] ?? ($course?->major?->code ?? 'it');

        if (!empty($validated['async']) && $course) {
            GenerateQuizJob::dispatch($course->id, $lesson?->id, $topic, $numQuestions);
            return response()->json([
                'success' => true,
                'status' => 'queued',
                'message' => 'AI Quiz generation dispatched to background queue.'
            ]);
        }

        $context = $lesson ? ($lesson->content ?: $lesson->ai_summary ?: $lesson->title) : ($course?->description ?: $topic);
        $questions = $this->aiService->generateQuiz($context, $topic, $numQuestions, (string) $major);

        return response()->json([
            'success' => true,
            'topic' => $topic,
            'questions' => $questions,
            'count' => count($questions),
        ]);
    }

    /**
     * 5. Lesson / Transcript Summarizer
     */
    public function summarize(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_id' => 'nullable|integer',
            'lesson_id' => 'nullable|integer',
            'text' => 'nullable|string',
            'topic' => 'nullable|string',
            'async' => 'nullable|boolean',
        ]);

        $course = !empty($validated['course_id']) ? Course::find($validated['course_id']) : Course::first();
        $lesson = !empty($validated['lesson_id']) ? Lesson::find($validated['lesson_id']) : null;
        $topic = $validated['topic'] ?: ($lesson ? $lesson->title : ($course ? $course->title : 'SPI Academic Module'));
        $text = $validated['text'] ?: ($lesson ? ($lesson->content ?: $lesson->ai_summary) : $course?->description);

        if (!empty($validated['async']) && $course) {
            SummarizeLessonJob::dispatch($course->id, $lesson?->id, $text);
            return response()->json([
                'success' => true,
                'status' => 'queued',
                'message' => 'AI Summary generation dispatched to background queue.'
            ]);
        }

        $summary = $this->aiService->generateSummary($text ?: $topic, $topic);

        return response()->json([
            'success' => true,
            'summary' => $summary,
        ]);
    }

    /**
     * 6. IT Code Review & Debugging Assistant
     */
    public function codeReview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:10000',
            'language' => 'nullable|string|max:50',
            'task' => 'nullable|string|max:500',
        ]);

        $lang = $validated['language'] ?? 'c';
        $task = $validated['task'] ?? 'Saint Paul Institute Practice Lab';

        $review = $this->aiService->reviewCode($validated['code'], $lang, $task);

        return response()->json([
            'success' => true,
            'review' => $review,
        ]);
    }

    /**
     * 7. Agronomy Crop Disease Diagnostic Scanner
     */
    public function agriDiagnosis(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'symptoms' => 'required|string|max:2000',
            'crop_type' => 'nullable|string|max:100',
            'soil_ph' => 'nullable|numeric',
        ]);

        $crop = $validated['crop_type'] ?? 'Rice / Paddy';
        $symptoms = $validated['symptoms'];
        if (!empty($validated['soil_ph'])) {
            $symptoms .= " (Current Soil pH: {$validated['soil_ph']})";
        }

        $diagnosis = $this->aiService->diagnoseCropDisease($symptoms, $crop);

        return response()->json([
            'success' => true,
            'diagnosis' => $diagnosis,
        ]);
    }

    /**
     * 8. English Writing & Grammar Assessor
     */
    public function englishReview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'text' => 'required|string|max:4000',
            'target_level' => 'nullable|string|max:10',
        ]);

        $level = $validated['target_level'] ?? 'B1';
        $review = $this->aiService->checkEnglishGrammar($validated['text'], $level);

        return response()->json([
            'success' => true,
            'review' => $review,
        ]);
    }

    /**
     * 9. Social Work Role-play & Case Evaluation
     */
    public function socialWorkCase(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'scenario' => 'required|string|max:2000',
            'response' => 'required|string|max:3000',
        ]);

        $evaluation = $this->aiService->evaluateSocialWorkScenario($validated['scenario'], $validated['response']);

        return response()->json([
            'success' => true,
            'evaluation' => $evaluation,
        ]);
    }
}
