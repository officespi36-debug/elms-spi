<?php

namespace App\Services;

use App\Models\AssignmentSubmission;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Notification;
use App\Models\NotificationRead;
use App\Models\Payment;
use App\Models\Quiz;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentNotificationService
{
    /**
     * Get real notifications for a student from MySQL database,
     * with read states persisted in `notification_reads`.
     *
     * @param User $user
     * @return array{items: array, unread_count: int}
     */
    public static function getForUser(User $user): array
    {
        $cacheKey = "student_notif_payload_{$user->id}";

        $payload = Cache::remember($cacheKey, 30, function () use ($user) {
            return self::buildNotificationsPayload($user);
        });

        $readKeyMap = self::getReadKeyMap($user);
        $items = $payload['items'] ?? [];

        $unreadCount = 0;
        foreach ($items as &$item) {
            $notifKey = $item['key'] ?? self::normalizeKey($item['id'] ?? '');
            $isRead = $notifKey !== null && isset($readKeyMap[$notifKey]);
            $item['read'] = $isRead;
            if (!$isRead) {
                $unreadCount++;
            }
        }
        unset($item);

        return [
            'items'        => $items,
            'unread_count' => $unreadCount,
        ];
    }

    /**
     * Return associative map [notification_key => true] from MySQL `notification_reads`.
     *
     * @param User $user
     * @return array<string, bool>
     */
    public static function getReadKeyMap(User $user): array
    {
        try {
            $keys = NotificationRead::where('user_id', $user->id)
                ->pluck('notification_key')
                ->all();

            return array_fill_keys($keys, true);
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Normalize either a string key (e.g. "announcement:12") or a numeric composite ID
     * into a collision-free string `notification_key` (max 100 chars).
     */
    public static function normalizeKey(int|string|null $idOrKey): ?string
    {
        if ($idOrKey === null || $idOrKey === '') {
            return null;
        }

        if (is_string($idOrKey) && str_contains($idOrKey, ':')) {
            $trimmed = trim($idOrKey);
            return $trimmed !== '' ? substr($trimmed, 0, 100) : null;
        }

        if (!is_numeric($idOrKey)) {
            $trimmed = trim((string) $idOrKey);
            return $trimmed !== '' ? substr($trimmed, 0, 100) : null;
        }

        $num = (int) $idOrKey;
        if ($num <= 0) {
            return null;
        }

        // 1,000,000-bucket collision-free numeric IDs
        if ($num >= 8000000) {
            return 'payment:' . ($num - 8000000);
        }
        if ($num >= 7000000) {
            return 'comment:' . ($num - 7000000);
        }
        if ($num >= 6000000) {
            return 'submission:' . ($num - 6000000);
        }
        if ($num >= 5000000) {
            return 'ai_rec:' . ($num - 5000000);
        }
        if ($num >= 4000000) {
            return 'quiz:' . ($num - 4000000);
        }
        if ($num >= 3000000) {
            return 'lesson:' . ($num - 3000000);
        }
        if ($num >= 2000000) {
            return 'notification:' . ($num - 2000000);
        }
        if ($num >= 1000000) {
            return 'announcement:' . ($num - 1000000);
        }

        // Legacy 1,000-bucket support
        if ($num >= 5000) {
            return 'ai_rec:' . ($num - 5000);
        }
        if ($num >= 4000) {
            return 'quiz:' . ($num - 4000);
        }
        if ($num >= 3000) {
            return 'lesson:' . ($num - 3000);
        }
        if ($num >= 2000) {
            return 'notification:' . ($num - 2000);
        }
        if ($num >= 1000) {
            return 'announcement:' . ($num - 1000);
        }

        return 'item:' . $num;
    }

    /**
     * Build notifications payload from real MySQL tables.
     */
    protected static function buildNotificationsPayload(User $user): array
    {
        $items = [];
        $userCreatedAt = $user->created_at ? Carbon::parse($user->created_at) : null;
        $now = Carbon::now();

        // 1. Announcements Table (strictly published/sent & scheduled_at <= now())
        try {
            $announcementsQuery = DB::table('announcements')
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
                });

            if ($userCreatedAt) {
                $announcementsQuery->where(function ($q) use ($userCreatedAt) {
                    $q->whereNull('created_at')
                      ->orWhere('created_at', '>=', $userCreatedAt);
                });
            }

            $announcements = $announcementsQuery
                ->orderByDesc('is_pinned')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->take(5)
                ->get();

            foreach ($announcements as $a) {
                $rawTs = $a->created_at ?? $a->scheduled_at ?? $a->updated_at;
                $parsedTs = $rawTs ? Carbon::parse($rawTs) : null;

                $items[] = [
                    'id'          => 1000000 + (int) $a->id,
                    'key'         => 'announcement:' . (int) $a->id,
                    'title_km'    => $a->title_kh ?: ($a->title_en ?: 'សេចក្តីប្រកាសព័ត៌មាន'),
                    'title_en'    => $a->title_en ?: ($a->title_kh ?: 'Official Announcement'),
                    'desc_km'     => Str::limit(strip_tags((string) ($a->body_kh ?: $a->body_en ?: '')), 90),
                    'desc_en'     => Str::limit(strip_tags((string) ($a->body_en ?: $a->body_kh ?: '')), 90),
                    'time_km'     => $parsedTs ? self::formatKhmerRelativeTime($parsedTs) : '—',
                    'time_en'     => $parsedTs ? self::formatEnglishRelativeTime($parsedTs) : '—',
                    'type'        => 'announcement',
                    'defaultRead' => false,
                    'read'        => false,
                    'link'        => '/student/notifications/announcements',
                    'created_at'  => $parsedTs ? $parsedTs->toIso8601String() : '',
                ];
            }
        } catch (\Throwable $e) {
        }

        // 2. Notifications Table (System, AI alerts, Discussion/Feedback alerts)
        try {
            $dbNotifs = Notification::where(function ($q) use ($user, $userCreatedAt) {
                $q->where('target', "user:{$user->id}")
                  ->orWhere(function ($sub) use ($userCreatedAt) {
                      $sub->whereIn('target', ['all', 'students']);
                      if ($userCreatedAt) {
                          $sub->where(function ($t) use ($userCreatedAt) {
                              $t->whereNull('created_at')
                                ->orWhere('created_at', '>=', $userCreatedAt);
                          });
                      }
                  });
            })
                ->latest()
                ->take(6)
                ->get();

            foreach ($dbNotifs as $n) {
                $rawTs = $n->created_at ?? $n->updated_at;
                $parsedTs = $rawTs ? Carbon::parse($rawTs) : null;

                $lowerTitle = strtolower((string) $n->title);
                $isAi = Str::contains($lowerTitle, 'ai');
                $isDiscussion = Str::contains($lowerTitle, ['សំណួរ', 'ចម្លើយ', 'មតិយោបល់', 'question', 'reply', 'discussion', 'feedback', 'graded']);

                $link = $isAi
                    ? '/student/notifications/ai-alerts'
                    : ($isDiscussion ? '/student/notifications/feedback' : '/student/notifications');
                $messageText = (string) $n->message;

                if (preg_match('/\[url:(.*?)\]/', $messageText, $matches)) {
                    $link = $matches[1];
                    $messageText = trim((string) preg_replace('/\[url:.*?\]/', '', $messageText));
                }

                $items[] = [
                    'id'          => 2000000 + (int) $n->id,
                    'key'         => 'notification:' . (int) $n->id,
                    'title_km'    => $n->title,
                    'title_en'    => $n->title,
                    'desc_km'     => Str::limit($messageText, 90),
                    'desc_en'     => Str::limit($messageText, 90),
                    'time_km'     => $parsedTs ? self::formatKhmerRelativeTime($parsedTs) : '—',
                    'time_en'     => $parsedTs ? self::formatEnglishRelativeTime($parsedTs) : '—',
                    'type'        => $isDiscussion ? 'discussion' : ($isAi ? 'ai' : 'announcement'),
                    'defaultRead' => false,
                    'read'        => false,
                    'link'        => $link,
                    'created_at'  => $parsedTs ? $parsedTs->toIso8601String() : '',
                ];
            }
        } catch (\Throwable $e) {
        }

        // 3. Enrolled Courses: Recent Lessons & Published Quizzes
        try {
            $enrolledCourseIds = Enrollment::where('student_id', $user->id)
                ->whereIn('status', ['active', 'completed'])
                ->pluck('course_id');

            if ($enrolledCourseIds->isNotEmpty()) {
                $recentLessons = Lesson::where(function ($q) use ($enrolledCourseIds) {
                    $q->whereIn('course_id', $enrolledCourseIds)
                      ->orWhereHas('module', function ($mq) use ($enrolledCourseIds) {
                          $mq->whereIn('course_id', $enrolledCourseIds);
                      });
                })
                    ->with(['course:id,title,title_kh', 'module.course:id,title,title_kh'])
                    ->latest()
                    ->take(3)
                    ->get();

                foreach ($recentLessons as $l) {
                    $rawTs = $l->created_at ?? $l->updated_at;
                    $parsedTs = $rawTs ? Carbon::parse($rawTs) : null;
                    $course = $l->course ?? $l->module?->course;
                    $courseTitleEn = $course?->title ?? 'Course';
                    $courseTitleKm = $course?->title_kh ?: $courseTitleEn;
                    $courseId = $l->course_id ?? $l->module?->course_id;
                    $lessonTitleKm = $l->kh_title ?: $l->title;

                    $items[] = [
                        'id'          => 3000000 + (int) $l->id,
                        'key'         => 'lesson:' . (int) $l->id,
                        'title_km'    => 'មេរៀនថ្មី៖ ' . $courseTitleKm,
                        'title_en'    => 'New Lesson Available: ' . $courseTitleEn,
                        'desc_km'     => 'មេរៀន "' . $lessonTitleKm . '" អាចចូលរៀនបានហើយ',
                        'desc_en'     => 'Lesson "' . $l->title . '" is available to study',
                        'time_km'     => $parsedTs ? self::formatKhmerRelativeTime($parsedTs) : '—',
                        'time_en'     => $parsedTs ? self::formatEnglishRelativeTime($parsedTs) : '—',
                        'type'        => 'lesson',
                        'defaultRead' => false,
                        'read'        => false,
                        'link'        => $courseId ? "/student/courses/{$courseId}/lessons/{$l->id}" : '/student/my-courses/enrolled',
                        'created_at'  => $parsedTs ? $parsedTs->toIso8601String() : '',
                    ];
                }

                $recentQuizzes = Quiz::whereIn('course_id', $enrolledCourseIds)
                    ->where('status', 'published')
                    ->with('course:id,title,title_kh')
                    ->latest()
                    ->take(3)
                    ->get();

                foreach ($recentQuizzes as $q) {
                    $rawTs = $q->created_at ?? $q->updated_at;
                    $parsedTs = $rawTs ? Carbon::parse($rawTs) : null;
                    $courseTitleEn = $q->course?->title ?? 'Course';
                    $courseTitleKm = $q->course?->title_kh ?: $courseTitleEn;

                    $items[] = [
                        'id'          => 4000000 + (int) $q->id,
                        'key'         => 'quiz:' . (int) $q->id,
                        'title_km'    => 'កម្រងសំណួរថ្មី៖ ' . $q->title,
                        'title_en'    => 'Quiz Available: ' . $q->title,
                        'desc_km'     => 'គ្រូបង្រៀនបានបើកកម្រងសំណួរសម្រាប់ ' . $courseTitleKm,
                        'desc_en'     => 'Published assessment in ' . $courseTitleEn,
                        'time_km'     => $parsedTs ? self::formatKhmerRelativeTime($parsedTs) : '—',
                        'time_en'     => $parsedTs ? self::formatEnglishRelativeTime($parsedTs) : '—',
                        'type'        => 'quiz',
                        'defaultRead' => false,
                        'read'        => false,
                        'link'        => $q->type === 'assignment' ? '/student/quizzes/assignments' : "/student/quiz/{$q->id}",
                        'created_at'  => $parsedTs ? $parsedTs->toIso8601String() : '',
                    ];
                }
            }
        } catch (\Throwable $e) {
        }

        // 4. AI Recommendations Table (non-dismissed)
        try {
            $aiRecs = DB::table('ai_recommendations')
                ->where('user_id', $user->id)
                ->where('is_dismissed', 0)
                ->latest()
                ->take(3)
                ->get();

            foreach ($aiRecs as $r) {
                $rawTs = $r->created_at ?? $r->updated_at;
                $parsedTs = $rawTs ? Carbon::parse($rawTs) : null;

                $items[] = [
                    'id'          => 5000000 + (int) $r->id,
                    'key'         => 'ai_rec:' . (int) $r->id,
                    'title_km'    => 'ការណែនាំពី AI៖ មេរៀនពង្រឹងសមត្ថភាព',
                    'title_en'    => 'AI Study Alert: Recommended Focus',
                    'desc_km'     => Str::limit((string) $r->reason, 90),
                    'desc_en'     => Str::limit((string) $r->reason, 90),
                    'time_km'     => $parsedTs ? self::formatKhmerRelativeTime($parsedTs) : '—',
                    'time_en'     => $parsedTs ? self::formatEnglishRelativeTime($parsedTs) : '—',
                    'type'        => 'ai',
                    'defaultRead' => false,
                    'read'        => false,
                    'link'        => '/student/notifications/ai-alerts',
                    'created_at'  => $parsedTs ? $parsedTs->toIso8601String() : '',
                ];
            }
        } catch (\Throwable $e) {
        }

        // 5. Graded Assignment Submissions (Teacher Feedback)
        try {
            $gradedSubmissions = AssignmentSubmission::where('user_id', $user->id)
                ->where(function ($q) {
                    $q->whereNotNull('feedback')
                      ->orWhereNotNull('score')
                      ->orWhere('status', 'graded');
                })
                ->with(['quiz:id,title', 'course:id,title,title_kh'])
                ->latest('graded_at')
                ->latest('updated_at')
                ->take(3)
                ->get();

            foreach ($gradedSubmissions as $sub) {
                $rawTs = $sub->graded_at ?? $sub->updated_at ?? $sub->created_at;
                $parsedTs = $rawTs ? Carbon::parse($rawTs) : null;
                $titleLabel = $sub->title ?: ($sub->quiz?->title ?: 'Assignment');

                $items[] = [
                    'id'          => 6000000 + (int) $sub->id,
                    'key'         => 'submission:' . (int) $sub->id,
                    'title_km'    => 'មតិយោបល់ពីគ្រូ៖ ' . $titleLabel,
                    'title_en'    => 'Teacher Feedback: ' . $titleLabel,
                    'desc_km'     => Str::limit((string) ($sub->feedback ?: ('ពិន្ទុ៖ ' . ($sub->score ?? '—'))), 90),
                    'desc_en'     => Str::limit((string) ($sub->feedback ?: ('Score: ' . ($sub->score ?? '—'))), 90),
                    'time_km'     => $parsedTs ? self::formatKhmerRelativeTime($parsedTs) : '—',
                    'time_en'     => $parsedTs ? self::formatEnglishRelativeTime($parsedTs) : '—',
                    'type'        => 'discussion',
                    'defaultRead' => false,
                    'read'        => false,
                    'link'        => '/student/notifications/feedback',
                    'created_at'  => $parsedTs ? $parsedTs->toIso8601String() : '',
                ];
            }
        } catch (\Throwable $e) {
        }

        // 6. Payment Status Updates
        try {
            $payments = Payment::where('student_id', $user->id)
                ->with('course:id,title,title_kh')
                ->latest()
                ->take(3)
                ->get();

            foreach ($payments as $p) {
                $rawTs = $p->verified_at ?? $p->updated_at ?? $p->created_at;
                $parsedTs = $rawTs ? Carbon::parse($rawTs) : null;
                $courseNameEn = $p->course?->title ?? 'Course';
                $courseNameKm = $p->course?->title_kh ?: $courseNameEn;
                $isConfirmed = in_array(strtolower((string) $p->status), ['verified', 'completed', 'paid', 'approved'], true);

                $items[] = [
                    'id'          => 8000000 + (int) $p->id,
                    'key'         => 'payment:' . (int) $p->id,
                    'title_km'    => $isConfirmed ? 'ការបង់ប្រាក់ត្រូវបានបញ្ជាក់' : 'ការបង់ប្រាក់កំពុងរង់ចាំផ្ទៀងផ្ទាត់',
                    'title_en'    => $isConfirmed ? 'Payment Confirmed' : 'Payment Pending Verification',
                    'desc_km'     => $courseNameKm . ' • $' . number_format((float) $p->amount, 2),
                    'desc_en'     => $courseNameEn . ' • $' . number_format((float) $p->amount, 2),
                    'time_km'     => $parsedTs ? self::formatKhmerRelativeTime($parsedTs) : '—',
                    'time_en'     => $parsedTs ? self::formatEnglishRelativeTime($parsedTs) : '—',
                    'type'        => 'announcement',
                    'defaultRead' => false,
                    'read'        => false,
                    'link'        => '/student/notifications/payment-alerts',
                    'created_at'  => $parsedTs ? $parsedTs->toIso8601String() : '',
                ];
            }
        } catch (\Throwable $e) {
        }

        // Sort descending by real created_at (items with empty created_at go last)
        usort($items, function ($a, $b) {
            return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? ''));
        });

        $items = array_slice($items, 0, 15);

        return [
            'items' => $items,
        ];
    }

    /**
     * Mark a single notification key/ID as read in MySQL `notification_reads`.
     */
    public static function markRead(User $user, int|string $idOrKey): void
    {
        $notifKey = self::normalizeKey($idOrKey);
        if ($notifKey === null) {
            return;
        }

        NotificationRead::updateOrCreate(
            [
                'user_id'          => $user->id,
                'notification_key' => $notifKey,
            ],
            [
                'read_at' => Carbon::now(),
            ]
        );

        Cache::forget("student_notif_payload_{$user->id}");
    }

    /**
     * Mark a single notification key/ID as unread by removing its row in `notification_reads`.
     */
    public static function markUnread(User $user, int|string $idOrKey): void
    {
        $notifKey = self::normalizeKey($idOrKey);
        if ($notifKey === null) {
            return;
        }

        NotificationRead::where('user_id', $user->id)
            ->where('notification_key', $notifKey)
            ->delete();

        Cache::forget("student_notif_payload_{$user->id}");
    }

    /**
     * Mark multiple (or all current) notifications as read in MySQL `notification_reads`.
     */
    public static function markAllRead(User $user, array $itemsOrKeys = []): void
    {
        $normalizedKeys = [];

        foreach ($itemsOrKeys as $entry) {
            $norm = self::normalizeKey($entry);
            if ($norm !== null) {
                $normalizedKeys[$norm] = true;
            }
        }

        if (empty($normalizedKeys)) {
            $payload = self::getForUser($user);
            foreach ($payload['items'] ?? [] as $item) {
                $norm = $item['key'] ?? self::normalizeKey($item['id'] ?? null);
                if ($norm !== null) {
                    $normalizedKeys[$norm] = true;
                }
            }

            // Also include all visible student announcements, AI alerts, feedback, and payments
            foreach (self::collectAllStudentNotificationKeys($user) as $key) {
                $normalizedKeys[$key] = true;
            }
        }

        if (empty($normalizedKeys)) {
            return;
        }

        $now = Carbon::now();
        $rows = [];
        foreach (array_keys($normalizedKeys) as $key) {
            $rows[] = [
                'user_id'          => $user->id,
                'notification_key' => $key,
                'read_at'          => $now,
            ];
        }

        NotificationRead::upsert(
            $rows,
            ['user_id', 'notification_key'],
            ['read_at']
        );

        Cache::forget("student_notif_payload_{$user->id}");
    }

    /**
     * Collect all active notification keys across student notification tabs.
     *
     * @param User $user
     * @return array<int, string>
     */
    public static function collectAllStudentNotificationKeys(User $user): array
    {
        $keys = [];
        $now = Carbon::now();

        try {
            $announcementIds = DB::table('announcements')
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
                ->pluck('id');

            foreach ($announcementIds as $id) {
                $keys[] = 'announcement:' . (int) $id;
            }
        } catch (\Throwable $e) {
        }

        try {
            $aiIds = DB::table('ai_recommendations')
                ->where('user_id', $user->id)
                ->where('is_dismissed', 0)
                ->pluck('id');
            foreach ($aiIds as $id) {
                $keys[] = 'ai_rec:' . (int) $id;
            }
        } catch (\Throwable $e) {
        }

        try {
            $subIds = AssignmentSubmission::where('user_id', $user->id)
                ->where(function ($q) {
                    $q->whereNotNull('feedback')
                      ->orWhereNotNull('score')
                      ->orWhere('status', 'graded');
                })
                ->pluck('id');
            foreach ($subIds as $id) {
                $keys[] = 'submission:' . (int) $id;
            }
        } catch (\Throwable $e) {
        }

        try {
            $paymentIds = Payment::where('student_id', $user->id)->pluck('id');
            foreach ($paymentIds as $id) {
                $keys[] = 'payment:' . (int) $id;
            }
        } catch (\Throwable $e) {
        }

        return $keys;
    }

    /**
     * Relative time in Khmer.
     */
    public static function formatKhmerRelativeTime(Carbon $carbon): string
    {
        $now = Carbon::now();
        if ($carbon->greaterThan($now)) {
            return $carbon->format('d M Y');
        }
        $diffMinutes = (int) $carbon->diffInMinutes($now);
        if ($diffMinutes < 1) {
            return 'ទើបតែឥឡូវនេះ';
        }
        if ($diffMinutes < 60) {
            return $diffMinutes . ' នាទីមុន';
        }
        $diffHours = (int) $carbon->diffInHours($now);
        if ($diffHours < 24) {
            return $diffHours . ' ម៉ោងមុន';
        }
        $diffDays = (int) $carbon->diffInDays($now);
        if ($diffDays < 7) {
            return $diffDays . ' ថ្ងៃមុន';
        }
        if ($diffDays < 30) {
            return (int) ceil($diffDays / 7) . ' សប្តាហ៍មុន';
        }
        return $carbon->format('d M Y');
    }

    /**
     * Relative time in English.
     */
    public static function formatEnglishRelativeTime(Carbon $carbon): string
    {
        $now = Carbon::now();
        if ($carbon->greaterThan($now)) {
            return $carbon->format('d M Y');
        }
        $diffMinutes = (int) $carbon->diffInMinutes($now);
        if ($diffMinutes < 1) {
            return 'Just now';
        }
        if ($diffMinutes < 60) {
            return $diffMinutes . 'm ago';
        }
        $diffHours = (int) $carbon->diffInHours($now);
        if ($diffHours < 24) {
            return $diffHours . ($diffHours === 1 ? ' hour ago' : ' hours ago');
        }
        $diffDays = (int) $carbon->diffInDays($now);
        if ($diffDays < 7) {
            return $diffDays . ($diffDays === 1 ? ' day ago' : ' days ago');
        }
        if ($diffDays < 30) {
            return (int) ceil($diffDays / 7) . ' weeks ago';
        }
        return $carbon->format('d M Y');
    }
}
