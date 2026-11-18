<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Models\AiFeedback;
use App\Models\AiMessage;
use App\Models\User;
use App\Services\AiContextService;
use App\Services\AiTutorChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AiTutorController extends Controller
{
    public function index(
        Request $request,
        AiContextService $contextService,
        AiTutorChatService $chatService
    ) {
        /** @var User $user */
        $user = $request->user();
        $courseParam = $request->query('course');
        $selectedCourseId = is_numeric($courseParam) ? (int) $courseParam : null;
        $lessonParam = $request->query('lesson');
        $selectedLessonId = is_numeric($lessonParam) ? (int) $lessonParam : null;

        $context = $contextService->getStudentContext($user, $selectedCourseId, $selectedLessonId);

        $conversationsCollection = collect();
        $activeConversation = null;

        if ($chatService->ensureTablesExist()) {
            try {
                $conversationsCollection = AiConversation::where('user_id', $user->id)
                    ->whereHas('messages')
                    ->with('course')
                    ->orderByDesc('last_message_at')
                    ->orderByDesc('id')
                    ->limit(10)
                    ->get();

                $requestedConvId = $request->query('conversation');
                $activeConvModel = null;
                if (is_numeric($requestedConvId)) {
                    $activeConvModel = $conversationsCollection->firstWhere('id', (int) $requestedConvId)
                        ?: AiConversation::where('user_id', $user->id)
                            ->with('course')
                            ->find((int) $requestedConvId);
                }
                if (!$activeConvModel) {
                    $activeConvModel = $conversationsCollection->first();
                }

                if ($activeConvModel) {
                    $activeConvModel->load([
                        'messages' => fn ($q) => $q->orderBy('id'),
                        'messages.feedbacks' => fn ($q) => $q->where('user_id', $user->id),
                    ]);
                    $activeConversation = $this->serializeConversation($activeConvModel, $chatService, true);
                }
            } catch (\Throwable $e) {
                Log::warning('AI Tutor index query warning: ' . $e->getMessage());
                $conversationsCollection = collect();
                $activeConversation = null;
            }
        }

        return Inertia::render('Student/AiTutor/Index', [
            'context'            => $context,
            'conversations'      => $conversationsCollection->map(fn ($c) => $this->serializeConversation($c, $chatService, false))->values()->all(),
            'activeConversation' => $activeConversation,
            'quota'              => $chatService->getQuotaStatus($user),
            'csrfToken'          => csrf_token(),
        ]);
    }

    public function storeConversation(
        Request $request,
        AiContextService $contextService,
        AiTutorChatService $chatService
    ) {
        $chatService->ensureTablesExist();

        /** @var User $user */
        $user = $request->user();
        $data = $request->validate([
            'course_id' => 'nullable|integer',
            'title'     => 'nullable|string|max:120',
        ]);

        $courseId = isset($data['course_id']) ? (int) $data['course_id'] : null;
        if ($courseId !== null && !$contextService->isCourseEnrolled($user, $courseId)) {
            abort(403, 'You are not enrolled in the selected course.');
        }

        $conversation = AiConversation::create([
            'user_id'         => $user->id,
            'course_id'       => $courseId,
            'title'           => trim((string) ($data['title'] ?? '')) ?: 'ការសន្ទនាថ្មី',
            'last_message_at' => now(),
        ]);

        $conversation->load('course');
        $conversation->setRelation('messages', collect());

        return response()->json([
            'ok'           => true,
            'conversation' => $this->serializeConversation($conversation, $chatService, true),
        ], 201);
    }

    public function showConversation(
        Request $request,
        AiConversation $conversation,
        AiTutorChatService $chatService
    ) {
        $chatService->ensureTablesExist();

        /** @var User $user */
        $user = $request->user();
        Gate::forUser($user)->authorize('view', $conversation);

        $conversation->load([
            'course',
            'messages' => fn ($q) => $q->orderBy('id'),
            'messages.feedbacks' => fn ($q) => $q->where('user_id', $user->id),
        ]);

        return response()->json([
            'ok'           => true,
            'conversation' => $this->serializeConversation($conversation, $chatService, true),
        ]);
    }

    public function destroyConversation(
        Request $request,
        AiConversation $conversation,
        AiTutorChatService $chatService
    ) {
        $chatService->ensureTablesExist();

        /** @var User $user */
        $user = $request->user();
        Gate::forUser($user)->authorize('delete', $conversation);

        $conversation->delete();

        return response()->json([
            'ok' => true,
        ]);
    }

    public function storeMessage(
        Request $request,
        AiConversation $conversation,
        AiContextService $contextService,
        AiTutorChatService $chatService
    ) {
        $chatService->ensureTablesExist();
        /** @var User $user */
        $user = $request->user();
        Gate::forUser($user)->authorize('update', $conversation);

        $data = $request->validate([
            'quick_action' => 'nullable|string|in:explain_lesson,generate_quiz,summarize_topic,give_example',
            'message'      => 'required_without:quick_action|nullable|string|min:2|max:1000',
            'course_id'    => 'nullable|integer',
            'lesson_id'    => 'nullable|integer',
            'lang'         => 'nullable|string|in:km,en',
        ]);

        if (isset($data['course_id']) && $data['course_id'] !== null) {
            if (!$contextService->isCourseEnrolled($user, (int) $data['course_id'])) {
                abort(403, 'You are not enrolled in the selected course.');
            }
        }

        $quota = $chatService->getQuotaStatus($user);
        if ($quota['remaining'] <= 0) {
            return response()->json([
                'ok'         => false,
                'error_code' => 'quota_exceeded',
                'message'    => "អ្នកបានប្រើប្រាស់គ្រប់កូតា {$quota['limit']} សារសម្រាប់ថ្ងៃនេះហើយ។ សូមត្រឡប់មកវិញនៅថ្ងៃស្អែក។",
                'quota'      => $quota,
            ], 429);
        }

        $effectiveCourseId = isset($data['course_id']) && $data['course_id'] !== null
            ? (int) $data['course_id']
            : ($conversation->course_id ? (int) $conversation->course_id : null);
        $selectedLessonId = isset($data['lesson_id']) && $data['lesson_id'] !== null
            ? (int) $data['lesson_id']
            : null;

        $context = $contextService->getStudentContext($user, $effectiveCourseId, $selectedLessonId);
        $lang = $data['lang'] ?? 'km';

        if (!empty($data['quick_action'])) {
            if (!$context['has_course'] || empty($context['active_course'])) {
                return response()->json([
                    'ok'         => false,
                    'error_code' => 'no_course',
                    'message'    => 'សូមចុះឈ្មោះ ឬជ្រើសរើសវគ្គសិក្សាជាមុនសិន មុននឹងប្រើសកម្មភាពរហ័ស។',
                ], 422);
            }
            $rawPrompt = $chatService->buildQuickActionMessage(
                (string) $data['quick_action'],
                $context['active_course'],
                $lang
            );
        } else {
            $rawPrompt = trim((string) ($data['message'] ?? ''));
        }

        $cleanPrompt = $chatService->scrubPii($rawPrompt);
        if (mb_strlen($cleanPrompt, 'UTF-8') < 2) {
            throw ValidationException::withMessages([
                'message' => ['សំណួរត្រូវមានយ៉ាងតិច ២ តួអក្សរ។'],
            ]);
        }

        $historyRows = $conversation->messages()
            ->reorder()
            ->orderByDesc('id')
            ->limit(AiTutorChatService::MAX_HISTORY)
            ->get()
            ->reverse()
            ->values()
            ->map(fn (AiMessage $m) => [
                'role' => $m->role,
                'body' => $m->body,
            ])
            ->all();

        $safeCoursePayload = $contextService->buildSafeAiCoursePayload($context['active_course'] ?? null);

        try {
            $aiResult = $chatService->generateReply($historyRows, $cleanPrompt, $safeCoursePayload);
        } catch (\RuntimeException $e) {
            return response()->json([
                'ok'         => false,
                'error_code' => 'provider_unavailable',
                'message'    => 'សេវា AI បច្ចុប្បន្នមិនអាចឆ្លើយតបបានទេ សូមសាកល្បងម្ដងទៀត។',
            ], 503);
        }

        [$userMsg, $assistantMsg] = DB::transaction(function () use (
            $conversation,
            $cleanPrompt,
            $aiResult,
            $effectiveCourseId
        ) {
            $existingCount = $conversation->messages()->count();
            $normalizedTitle = preg_replace('/\s+/u', ' ', $cleanPrompt) ?? $cleanPrompt;
            $shortTitle = mb_substr(trim($normalizedTitle), 0, 20, 'UTF-8');

            $updatePayload = [
                'last_message_at' => now(),
            ];
            if ($existingCount === 0 && $shortTitle !== '') {
                $updatePayload['title'] = $shortTitle;
            }
            if ($effectiveCourseId !== null && $conversation->course_id === null) {
                $updatePayload['course_id'] = $effectiveCourseId;
            }
            $conversation->update($updatePayload);

            $userMsg = AiMessage::create([
                'conversation_id' => $conversation->id,
                'role'            => 'user',
                'body'            => $cleanPrompt,
            ]);

            $assistantMsg = AiMessage::create([
                'conversation_id' => $conversation->id,
                'role'            => 'assistant',
                'body'            => $aiResult['body'],
                'provider'        => $aiResult['provider'],
                'model'           => $aiResult['model'],
                'latency_ms'      => $aiResult['latency_ms'],
                'tokens'          => $aiResult['tokens'],
            ]);

            return [$userMsg, $assistantMsg];
        });

        $conversation->load('course');
        $userMsg->setRelation('feedbacks', collect());
        $assistantMsg->setRelation('feedbacks', collect());

        return response()->json([
            'ok'                => true,
            'conversation'      => $this->serializeConversation($conversation, $chatService, false),
            'user_message'      => $this->serializeMessage($userMsg, $chatService),
            'assistant_message' => $this->serializeMessage($assistantMsg, $chatService),
            'quota'             => $chatService->getQuotaStatus($user),
        ]);
    }

    public function storeFeedback(Request $request, AiMessage $message, AiTutorChatService $chatService)
    {
        $chatService->ensureTablesExist();
        /** @var User $user */
        $user = $request->user();
        $message->load('conversation');

        abort_if(
            !$message->conversation || (int) $message->conversation->user_id !== (int) $user->id,
            403,
            'Unauthorized.'
        );
        abort_if($message->role !== 'assistant', 422, 'Feedback is only allowed on assistant messages.');

        $data = $request->validate([
            'rating' => 'required|string|in:up,down',
            'note'   => 'nullable|string|max:500',
        ]);

        $fb = AiFeedback::updateOrCreate(
            [
                'message_id' => $message->id,
                'user_id'    => $user->id,
            ],
            [
                'rating' => $data['rating'],
                'note'   => isset($data['note']) ? trim($data['note']) : null,
            ]
        );

        return response()->json([
            'ok'     => true,
            'rating' => $fb->rating,
        ]);
    }

    public function english(Request $request)
    {
        return Inertia::render('Student/AiTutor/EnglishTutor');
    }

    public function chat(Request $request)
    {
        return Inertia::render('Student/AiTutor/ChatSupport');
    }

    public function feedback(Request $request)
    {
        return Inertia::render('Student/AiTutor/InstantFeedback');
    }

    public function askTutor(Request $request, AiTutorChatService $chatService)
    {
        $request->validate([
            'message'        => 'required|string|min:2|max:2000',
            'context_course' => 'nullable|string|max:255',
            'major'          => 'nullable|string|max:100',
        ]);

        $message = $chatService->scrubPii(trim((string) $request->input('message')));
        $course = trim((string) $request->input('context_course', ''));
        $major = trim((string) $request->input('major', ''));

        $safeContext = $course !== ''
            ? [
                'course_title' => $course,
                'module_title' => $major !== '' ? $major : null,
            ]
            : null;

        try {
            $aiResult = $chatService->generateReply([], $message, $safeContext);

            return response()->json([
                'success'  => true,
                'reply'    => $aiResult['body'],
                'html'     => $chatService->renderSafeHtml($aiResult['body']),
                'provider' => $aiResult['provider'],
                'model'    => $aiResult['model'],
            ]);
        } catch (\Throwable $e) {
            Log::warning('AI Tutor endpoint unavailable: ' . $e->getMessage());

            return response()->json([
                'success'    => false,
                'error_code' => 'provider_unavailable',
                'message'    => 'សេវា AI បច្ចុប្បន្នមិនអាចឆ្លើយតបបានទេ សូមសាកល្បងម្ដងទៀត។',
            ], 503);
        }
    }

    protected function serializeConversation(
        AiConversation $conv,
        AiTutorChatService $chatService,
        bool $includeMessages = false
    ): array {
        $data = [
            'id'              => (int) $conv->id,
            'course_id'       => $conv->course_id ? (int) $conv->course_id : null,
            'course_title'    => $conv->course?->title,
            'course_title_kh' => $conv->course?->title_kh ?: $conv->course?->title,
            'title'           => $conv->title,
            'last_message_at' => $conv->last_message_at?->toIso8601String(),
            'relative_time'   => ($conv->last_message_at ?? $conv->updated_at)?->diffForHumans() ?? '',
        ];

        if ($includeMessages) {
            $msgs = $conv->relationLoaded('messages') ? $conv->messages : collect();
            $data['messages'] = $msgs
                ->map(fn (AiMessage $m) => $this->serializeMessage($m, $chatService))
                ->values()
                ->all();
        }

        return $data;
    }

    protected function serializeMessage(AiMessage $msg, AiTutorChatService $chatService): array
    {
        $myRating = null;
        if ($msg->relationLoaded('feedbacks')) {
            $myRating = $msg->feedbacks->first()?->rating;
        }

        return [
            'id'             => (int) $msg->id,
            'role'           => $msg->role,
            'body'           => $msg->body,
            'html'           => $msg->role === 'assistant' ? $chatService->renderSafeHtml($msg->body) : null,
            'provider'       => $msg->provider,
            'model'          => $msg->model,
            'my_rating'      => $myRating,
            'created_at_iso' => $msg->created_at?->toIso8601String(),
            'time_label'     => $msg->created_at?->format('H:i') ?? '',
        ];
    }
}
