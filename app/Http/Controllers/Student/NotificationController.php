<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AiRecommendation;
use App\Models\AssignmentSubmission;
use App\Models\LessonComment;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\StudentQuestion;
use App\Models\User;
use App\Services\StudentNotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tab = $request->query('tab', 'all');
        $readKeyMap = StudentNotificationService::getReadKeyMap($user);

        [$announcements, $summaryStats] = $this->buildAnnouncementsData($user, $readKeyMap);
        $aiAlerts = $this->buildAiAlertsData($user, $readKeyMap);
        $feedbackItems = $this->buildTeacherFeedbackData($user, $readKeyMap);
        $paymentAlerts = $this->buildPaymentAlertsData($user, $readKeyMap);

        $unreadAnnouncements = collect($announcements)->where('is_read', false)->count();
        $unreadAi = collect($aiAlerts)->where('is_read', false)->count();
        $unreadFeedback = collect($feedbackItems)->where('is_read', false)->count();
        $unreadPayments = collect($paymentAlerts)->where('is_read', false)->count();

        $counts = [
            'all'           => count($announcements) + count($aiAlerts) + count($feedbackItems) + count($paymentAlerts),
            'announcements' => count($announcements),
            'ai_alerts'     => count($aiAlerts),
            'feedback'      => count($feedbackItems),
            'payments'      => count($paymentAlerts),
            'unread'        => $unreadAnnouncements + $unreadAi + $unreadFeedback + $unreadPayments,
        ];

        return Inertia::render('Student/Notifications/Index', [
            'activeTab'         => $tab,
            'announcements'     => $announcements,
            'summaryStats'      => $summaryStats,
            'notificationPrefs' => $this->extractNotificationPrefs($user),
            'aiAlerts'          => $aiAlerts,
            'feedbackItems'     => $feedbackItems,
            'paymentAlerts'     => $paymentAlerts,
            'counts'            => $counts,
        ]);
    }

    public function announcements(Request $request)
    {
        $user = $request->user();
        $readKeyMap = StudentNotificationService::getReadKeyMap($user);
        [$announcements, $summaryStats] = $this->buildAnnouncementsData($user, $readKeyMap);

        return Inertia::render('Student/Notifications/Announcements', [
            'dbAnnouncements'   => $announcements,
            'summaryStats'      => $summaryStats,
            'notificationPrefs' => $this->extractNotificationPrefs($user),
        ]);
    }

    public function aiAlerts(Request $request)
    {
        $user = $request->user();
        $readKeyMap = StudentNotificationService::getReadKeyMap($user);
        $aiAlerts = $this->buildAiAlertsData($user, $readKeyMap);

        return Inertia::render('Student/Notifications/AiAlerts', [
            'aiAlerts' => $aiAlerts,
        ]);
    }

    public function dismissAiAlert(Request $request, AiRecommendation $recommendation)
    {
        $user = $request->user();
        if ((int) $recommendation->user_id !== (int) $user->id) {
            abort(403, 'Unauthorized to dismiss this alert.');
        }

        $recommendation->update(['is_dismissed' => true]);
        Cache::forget("student_notif_payload_{$user->id}");

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'success' => true,
                'id'      => $recommendation->id,
            ]);
        }

        return back()->with('success', 'AI study alert dismissed.');
    }

    public function teacherFeedback(Request $request)
    {
        $user = $request->user();
        $readKeyMap = StudentNotificationService::getReadKeyMap($user);
        $feedbackItems = $this->buildTeacherFeedbackData($user, $readKeyMap);

        return Inertia::render('Student/Notifications/TeacherFeedback', [
            'feedbackItems' => $feedbackItems,
        ]);
    }

    public function paymentAlerts(Request $request)
    {
        $user = $request->user();
        $readKeyMap = StudentNotificationService::getReadKeyMap($user);
        $paymentAlerts = $this->buildPaymentAlertsData($user, $readKeyMap);

        return Inertia::render('Student/Notifications/PaymentAlerts', [
            'paymentAlerts' => $paymentAlerts,
        ]);
    }

    /**
     * API: Get realtime notifications for student from database.
     */
    public function apiList(Request $request)
    {
        $data = StudentNotificationService::getForUser($request->user());
        return response()->json($data);
    }

    /**
     * API: Mark a notification as read (or unread if `unread=true`).
     */
    public function apiMarkRead(Request $request)
    {
        $idOrKey = $request->input('key') ?? $request->input('id');
        if ($idOrKey !== null && $idOrKey !== '') {
            if ($request->boolean('unread')) {
                StudentNotificationService::markUnread($request->user(), $idOrKey);
            } else {
                StudentNotificationService::markRead($request->user(), $idOrKey);
            }
        }
        return response()->json(['success' => true]);
    }

    /**
     * API: Mark all notifications as read.
     */
    public function apiMarkAllRead(Request $request)
    {
        $keys = $request->input('keys', []);
        $ids = $request->input('ids', []);
        $merged = array_merge(
            is_array($keys) ? $keys : [],
            is_array($ids) ? $ids : []
        );

        StudentNotificationService::markAllRead($request->user(), $merged);

        return response()->json(['success' => true]);
    }

    /**
     * Extract saved notification preferences from `users.preferences`.
     */
    protected function extractNotificationPrefs(User $user): array
    {
        $prefs = is_array($user->preferences) ? $user->preferences : [];
        $saved = is_array($prefs['notification_prefs'] ?? null) ? $prefs['notification_prefs'] : [];

        return [
            'email'            => array_key_exists('email', $saved) ? (bool) $saved['email'] : (bool) ($prefs['email_notifications'] ?? true),
            'sms'              => array_key_exists('sms', $saved) ? (bool) $saved['sms'] : false,
            'push'             => array_key_exists('push', $saved) ? (bool) $saved['push'] : (bool) ($prefs['app_notifications'] ?? true),
            'systemAlerts'     => array_key_exists('systemAlerts', $saved) ? (bool) $saved['systemAlerts'] : true,
            'teacherAlerts'    => array_key_exists('teacherAlerts', $saved) ? (bool) $saved['teacherAlerts'] : true,
            'assignmentAlerts' => array_key_exists('assignmentAlerts', $saved) ? (bool) $saved['assignmentAlerts'] : true,
            'examAlerts'       => array_key_exists('examAlerts', $saved) ? (bool) $saved['examAlerts'] : true,
        ];
    }

    /**
     * Build published/sent announcements + summary statistics.
     *
     * @param User $user
     * @param array<string, bool> $readKeyMap
     * @return array{0: array<int, array<string, mixed>>, 1: array{total: int, teacher: int, system: int, unread: int}}
     */
    protected function buildAnnouncementsData(User $user, array $readKeyMap): array
    {
        $now = Carbon::now();

        $rows = DB::table('announcements')
            ->where(function ($q) {
                $q->whereNull('audience_type')
                  ->orWhereIn('audience_type', [
                      'everyone',
                      'students',
                      'all_students',
                      'students_by_major',
                      'course_students',
                      'course',
                      'All Students & Teachers',
                      'Active Students',
                  ]);
            })
            ->where(function ($q) {
                $q->whereNull('status')
                  ->orWhereIn('status', ['sent', 'published', 'Sent', 'Published']);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('scheduled_at')
                  ->orWhere('scheduled_at', '<=', $now);
            })
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $seen = [];
        $announcements = [];
        $teacherCount = 0;
        $systemCount = 0;
        $unreadCount = 0;

        foreach ($rows as $row) {
            $dedupeKey = trim((string) ($row->title_en ?: $row->title_kh)) . '|' . trim((string) ($row->body_en ?: $row->body_kh));
            if ($dedupeKey !== '|' && isset($seen[$dedupeKey])) {
                continue;
            }
            $seen[$dedupeKey] = true;

            $filters = [];
            if (!empty($row->audience_filters)) {
                $decoded = json_decode((string) $row->audience_filters, true);
                if (is_array($decoded)) {
                    $filters = $decoded;
                }
            }

            // If announcement targets a specific major_id and student has a different major_id, skip
            if (!empty($filters['major_id']) && !empty($user->major_id) && (int) $filters['major_id'] !== (int) $user->major_id) {
                continue;
            }

            $isTeacherSource = in_array((string) $row->audience_type, ['course_students', 'course'], true)
                || !empty($filters['teacher_id'])
                || !empty($filters['teacher_name'])
                || !empty($filters['course_id']);

            $sourceType = $isTeacherSource ? 'teacher' : 'system';
            if ($isTeacherSource) {
                $teacherCount++;
            } else {
                $systemCount++;
            }

            $notifKey = 'announcement:' . (int) $row->id;
            $isRead = isset($readKeyMap[$notifKey]);
            if (!$isRead) {
                $unreadCount++;
            }

            $attachments = [];
            if (!empty($row->attachments)) {
                $decodedAtt = json_decode((string) $row->attachments, true);
                if (is_array($decodedAtt)) {
                    $attachments = $decodedAtt;
                }
            }

            $rawTs = $row->scheduled_at ?? $row->created_at ?? $row->updated_at;
            $parsedTs = $rawTs ? Carbon::parse($rawTs) : null;

            $announcements[] = [
                'id'               => (int) $row->id,
                'notification_key' => $notifKey,
                'is_read'          => $isRead,
                'title_kh'         => $row->title_kh,
                'title_en'         => $row->title_en,
                'body_kh'          => $row->body_kh,
                'body_en'          => $row->body_en,
                'audience_type'    => $row->audience_type,
                'source_type'      => $sourceType,
                'category'         => $filters['category'] ?? ($isTeacherSource ? 'Course Updates' : 'Academic'),
                'teacher_name'     => $filters['teacher_name'] ?? null,
                'course_name'      => $filters['course_name'] ?? null,
                'course_id'        => $filters['course_id'] ?? null,
                'priority'         => $row->priority ?? 'normal',
                'is_pinned'        => (bool) $row->is_pinned,
                'attachments'      => $attachments,
                'created_at'       => $parsedTs ? $parsedTs->toIso8601String() : null,
                'date_label'       => $parsedTs ? $parsedTs->format('M j, Y') : '—',
                'time_label'       => $parsedTs ? $parsedTs->format('h:i A') : '',
            ];
        }

        $summaryStats = [
            'total'   => count($announcements),
            'teacher' => $teacherCount,
            'system'  => $systemCount,
            'unread'  => $unreadCount,
        ];

        return [$announcements, $summaryStats];
    }

    /**
     * Build real AI study alerts from `ai_recommendations` (non-dismissed).
     *
     * @param User $user
     * @param array<string, bool> $readKeyMap
     * @return array<int, array<string, mixed>>
     */
    protected function buildAiAlertsData(User $user, array $readKeyMap): array
    {
        $recs = AiRecommendation::where('user_id', $user->id)
            ->where('is_dismissed', false)
            ->with(['lesson.course:id,title,title_kh', 'lesson.module.course:id,title,title_kh'])
            ->latest()
            ->get();

        $items = [];
        foreach ($recs as $rec) {
            $lesson = $rec->lesson;
            $course = $lesson?->course ?? $lesson?->module?->course;
            $courseId = $lesson?->course_id ?? $lesson?->module?->course_id;
            $notifKey = 'ai_rec:' . (int) $rec->id;
            $rawTs = $rec->created_at ?? $rec->updated_at;
            $parsedTs = $rawTs ? Carbon::parse($rawTs) : null;

            $actionHref = '/student/ai-path/recommended';
            if ($courseId && $lesson?->id) {
                $actionHref = "/student/courses/{$courseId}/lessons/{$lesson->id}";
            } elseif ($rec->type === 'weak_topic') {
                $actionHref = '/student/ai-path/weak-topics';
            } elseif ($rec->type === 'quiz_milestone' || $rec->type === 'practice') {
                $actionHref = '/student/quizzes/practice';
            }

            $items[] = [
                'id'               => (int) $rec->id,
                'notification_key' => $notifKey,
                'is_read'          => isset($readKeyMap[$notifKey]),
                'type'             => $rec->type ?: 'recommendation',
                'reason'           => (string) ($rec->reason ?? ''),
                'lesson_id'        => $rec->lesson_id,
                'lesson_title'     => $lesson?->title,
                'lesson_title_kh'  => $lesson?->kh_title ?: $lesson?->title,
                'course_id'        => $courseId,
                'course_title'     => $course?->title,
                'course_title_kh'  => $course?->title_kh ?: $course?->title,
                'action_href'      => $actionHref,
                'created_at'       => $parsedTs ? $parsedTs->toIso8601String() : null,
                'time_en'          => $parsedTs ? StudentNotificationService::formatEnglishRelativeTime($parsedTs) : '—',
                'time_km'          => $parsedTs ? StudentNotificationService::formatKhmerRelativeTime($parsedTs) : '—',
            ];
        }

        return $items;
    }

    /**
     * Build real Teacher Feedback items from:
     * 1) Graded `AssignmentSubmission`s
     * 2) Answered `LessonComment`s & `StudentQuestion`s
     * 3) Direct feedback `Notification`s
     *
     * @param User $user
     * @param array<string, bool> $readKeyMap
     * @return array<int, array<string, mixed>>
     */
    protected function buildTeacherFeedbackData(User $user, array $readKeyMap): array
    {
        $items = [];

        // 1. Graded AssignmentSubmissions
        try {
            $submissions = AssignmentSubmission::where('user_id', $user->id)
                ->where(function ($q) {
                    $q->whereNotNull('feedback')
                      ->orWhereNotNull('score')
                      ->orWhere('status', 'graded');
                })
                ->with(['quiz.course.teacher:id,name,name_kh', 'course.teacher:id,name,name_kh'])
                ->latest('graded_at')
                ->latest('updated_at')
                ->get();

            foreach ($submissions as $sub) {
                $course = $sub->course ?? $sub->quiz?->course;
                $teacher = $course?->teacher;
                $notifKey = 'submission:' . (int) $sub->id;
                $rawTs = $sub->graded_at ?? $sub->updated_at ?? $sub->created_at;
                $parsedTs = $rawTs ? Carbon::parse($rawTs) : null;

                $items[] = [
                    'id'               => 'sub_' . $sub->id,
                    'notification_key' => $notifKey,
                    'is_read'          => isset($readKeyMap[$notifKey]),
                    'kind'             => 'assignment',
                    'title'            => $sub->title ?: ($sub->quiz?->title ?: 'Assignment Evaluation'),
                    'course_title'     => $course?->title,
                    'course_title_kh'  => $course?->title_kh ?: $course?->title,
                    'teacher_name'     => $teacher?->name,
                    'teacher_name_kh'  => $teacher?->name_kh ?: $teacher?->name,
                    'score'            => $sub->score !== null ? (float) $sub->score : null,
                    'feedback'         => $sub->feedback,
                    'question'         => $sub->notes,
                    'status'           => $sub->status ?: 'graded',
                    'action_href'      => '/student/quizzes/assignments',
                    'created_at'       => $parsedTs ? $parsedTs->toIso8601String() : '',
                    'date_label'       => $parsedTs ? $parsedTs->format('d M Y · h:i A') : '—',
                ];
            }
        } catch (\Throwable $e) {
        }

        // 2. Answered LessonComments (replies by teacher/admin or marked answered)
        try {
            $comments = LessonComment::where('user_id', $user->id)
                ->whereNull('parent_id')
                ->where(function ($q) {
                    $q->where('is_answered', true)
                      ->orWhereHas('replies', function ($rq) {
                          $rq->whereHas('user', function ($uq) {
                              $uq->whereIn('role', ['teacher', 'admin']);
                          });
                      });
                })
                ->with(['lesson.course:id,title,title_kh', 'lesson.module.course:id,title,title_kh', 'replies.user'])
                ->latest('updated_at')
                ->get();

            foreach ($comments as $comment) {
                $teacherReply = $comment->replies
                    ->filter(fn ($r) => in_array($r->user?->role, ['teacher', 'admin'], true))
                    ->last() ?? $comment->replies->last();

                if (!$teacherReply && !$comment->is_answered) {
                    continue;
                }

                $lesson = $comment->lesson;
                $course = $lesson?->course ?? $lesson?->module?->course;
                $courseId = $lesson?->course_id ?? $lesson?->module?->course_id;
                $notifKey = 'comment:' . (int) $comment->id;
                $rawTs = $teacherReply?->created_at ?? $comment->updated_at ?? $comment->created_at;
                $parsedTs = $rawTs ? Carbon::parse($rawTs) : null;

                $items[] = [
                    'id'               => 'comment_' . $comment->id,
                    'notification_key' => $notifKey,
                    'is_read'          => isset($readKeyMap[$notifKey]),
                    'kind'             => 'discussion',
                    'title'            => $lesson?->title ?: 'Lesson Q&A Reply',
                    'course_title'     => $course?->title,
                    'course_title_kh'  => $course?->title_kh ?: $course?->title,
                    'teacher_name'     => $teacherReply?->user?->name,
                    'teacher_name_kh'  => $teacherReply?->user?->name,
                    'score'            => null,
                    'question'         => $comment->body,
                    'feedback'         => $teacherReply?->body,
                    'status'           => 'answered',
                    'action_href'      => ($courseId && $lesson?->id)
                        ? "/student/courses/{$courseId}/lessons/{$lesson->id}"
                        : '/student/discussions/my-questions',
                    'created_at'       => $parsedTs ? $parsedTs->toIso8601String() : '',
                    'date_label'       => $parsedTs ? $parsedTs->format('d M Y · h:i A') : '—',
                ];
            }
        } catch (\Throwable $e) {
        }

        // 3. Answered StudentQuestions matching student's name
        try {
            $studentNames = array_values(array_filter([
                trim((string) $user->name),
                trim((string) $user->name_kh),
            ]));

            if (!empty($studentNames)) {
                $questions = StudentQuestion::whereIn('student_name', $studentNames)
                    ->whereNotNull('final_answer')
                    ->where('final_answer', '!=', '')
                    ->latest('updated_at')
                    ->get();

                foreach ($questions as $sq) {
                    $notifKey = 'question:' . (int) $sq->id;
                    $rawTs = $sq->updated_at ?? $sq->created_at;
                    $parsedTs = $rawTs ? Carbon::parse($rawTs) : null;

                    $items[] = [
                        'id'               => 'sq_' . $sq->id,
                        'notification_key' => $notifKey,
                        'is_read'          => isset($readKeyMap[$notifKey]),
                        'kind'             => 'question',
                        'title'            => $sq->chapter_name ?: ($sq->question_code ?: 'Course Q&A'),
                        'course_title'     => $sq->course_name,
                        'course_title_kh'  => $sq->course_name,
                        'teacher_name'     => $sq->answered_by,
                        'teacher_name_kh'  => $sq->answered_by,
                        'score'            => null,
                        'question'         => $sq->question_text,
                        'feedback'         => $sq->final_answer,
                        'status'           => $sq->status ?: 'answered',
                        'action_href'      => '/student/discussions/my-questions',
                        'created_at'       => $parsedTs ? $parsedTs->toIso8601String() : '',
                        'date_label'       => $parsedTs ? $parsedTs->format('d M Y · h:i A') : '—',
                    ];
                }
            }
        } catch (\Throwable $e) {
        }

        // 4. Direct Feedback Notifications targeted to this student
        try {
            $feedbackNotifs = Notification::where('target', "user:{$user->id}")
                ->where(function ($q) {
                    $q->where('title', 'like', '%feedback%')
                      ->orWhere('title', 'like', '%graded%')
                      ->orWhere('title', 'like', '%មតិយោបល់%')
                      ->orWhere('title', 'like', '%ពិន្ទុ%')
                      ->orWhere('title', 'like', '%ឆ្លើយតប%')
                      ->orWhere('title', 'like', '%reply%');
                })
                ->latest()
                ->get();

            foreach ($feedbackNotifs as $n) {
                $notifKey = 'notification:' . (int) $n->id;
                $rawTs = $n->created_at ?? $n->updated_at;
                $parsedTs = $rawTs ? Carbon::parse($rawTs) : null;

                $actionHref = '/student/quizzes/assignments';
                $messageText = (string) $n->message;
                if (preg_match('/\[url:(.*?)\]/', $messageText, $matches)) {
                    $actionHref = $matches[1];
                    $messageText = trim((string) preg_replace('/\[url:.*?\]/', '', $messageText));
                }

                $items[] = [
                    'id'               => 'notif_' . $n->id,
                    'notification_key' => $notifKey,
                    'is_read'          => isset($readKeyMap[$notifKey]),
                    'kind'             => 'notification',
                    'title'            => $n->title,
                    'course_title'     => null,
                    'course_title_kh'  => null,
                    'teacher_name'     => null,
                    'teacher_name_kh'  => null,
                    'score'            => null,
                    'question'         => null,
                    'feedback'         => $messageText,
                    'status'           => 'received',
                    'action_href'      => $actionHref,
                    'created_at'       => $parsedTs ? $parsedTs->toIso8601String() : '',
                    'date_label'       => $parsedTs ? $parsedTs->format('d M Y · h:i A') : '—',
                ];
            }
        } catch (\Throwable $e) {
        }

        usort($items, function ($a, $b) {
            return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
        });

        return $items;
    }

    /**
     * Build real Payment Alerts from `payments` for this student.
     *
     * @param User $user
     * @param array<string, bool> $readKeyMap
     * @return array<int, array<string, mixed>>
     */
    protected function buildPaymentAlertsData(User $user, array $readKeyMap): array
    {
        $payments = Payment::where('student_id', $user->id)
            ->with('course:id,title,title_kh,code')
            ->latest()
            ->get();

        $items = [];
        foreach ($payments as $p) {
            $rawStatus = strtolower((string) ($p->status ?? 'pending'));
            $normalizedType = in_array($rawStatus, ['verified', 'completed', 'paid', 'approved'], true)
                ? 'confirmed'
                : (in_array($rawStatus, ['rejected', 'failed', 'cancelled'], true) ? 'rejected' : 'pending');

            $currency = strtoupper((string) ($p->currency ?: 'USD'));
            $formattedAmount = $currency === 'KHR'
                ? number_format((float) $p->amount, 0) . ' ៛'
                : '$' . number_format((float) $p->amount, 2);

            $notifKey = 'payment:' . (int) $p->id;
            $rawTs = $p->verified_at ?? $p->created_at ?? $p->updated_at;
            $parsedTs = $rawTs ? Carbon::parse($rawTs) : null;

            $items[] = [
                'id'                 => (int) $p->id,
                'notification_key'   => $notifKey,
                'is_read'            => isset($readKeyMap[$notifKey]),
                'type'               => $normalizedType,
                'raw_status'         => $p->status ?: 'pending',
                'course_id'          => $p->course_id,
                'course_title'       => $p->course?->title ?? 'Course Enrollment',
                'course_title_kh'    => $p->course?->title_kh ?: ($p->course?->title ?? 'វគ្គសិក្សា'),
                'amount'             => (float) $p->amount,
                'currency'           => $currency,
                'formatted_amount'   => $formattedAmount,
                'aba_transaction_id' => $p->aba_transaction_id,
                'date_label'         => $parsedTs ? $parsedTs->format('d M Y · h:i A') : '—',
                'created_at'         => $parsedTs ? $parsedTs->toIso8601String() : '',
                'learn_href'         => $p->course_id ? "/student/courses/{$p->course_id}" : '/student/my-courses/enrolled',
            ];
        }

        return $items;
    }
}
