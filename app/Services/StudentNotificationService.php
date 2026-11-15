<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Notification;
use App\Models\Quiz;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StudentNotificationService
{
    /**
     * Get real notifications for a student from database.
     *
     * @param User $user
     * @return array{items: array, unread_count: int}
     */
    public static function getForUser(User $user): array
    {
        $cacheKey = "student_notif_payload_{$user->id}";

        $payload = Cache::remember($cacheKey, 60, function () use ($user) {
            return self::buildNotificationsPayload($user);
        });

        // Apply dynamic read status for this user
        $readIds = Cache::get("user_{$user->id}_read_notifications", []);
        $items = $payload['items'] ?? [];

        $unreadCount = 0;
        foreach ($items as &$item) {
            $isRead = in_array($item['id'], $readIds);
            $item['read'] = $isRead;
            if (!$isRead) {
                $unreadCount++;
            }
        }

        return [
            'items' => $items,
            'unread_count' => $unreadCount,
        ];
    }

    /**
     * Build notifications payload from database tables.
     */
    protected static function buildNotificationsPayload(User $user): array
    {
        $items = [];

        // 1. Announcements Table
        try {
            $announcements = DB::table('announcements')
                ->whereIn('audience_type', ['everyone', 'students', 'All Students & Teachers', 'Active Students'])
                ->whereIn('status', ['sent', 'scheduled'])
                ->where(function ($q) {
                    $q->whereNull('scheduled_at')
                      ->orWhere('scheduled_at', '<=', Carbon::now());
                })
                ->latest()
                ->take(4)
                ->get();

            foreach ($announcements as $a) {
                $createdAt = Carbon::parse($a->created_at ?: Carbon::now());
                $items[] = [
                    'id' => 1000 + (int)$a->id,
                    'title_km' => $a->title_kh ?: ($a->title_en ?: 'សេចក្តីប្រកាសព័ត៌មាន'),
                    'title_en' => $a->title_en ?: ($a->title_kh ?: 'Official Announcement'),
                    'desc_km' => Str::limit(strip_tags($a->body_kh ?: $a->body_en ?: 'ព័ត៌មានលម្អិត...'), 85),
                    'desc_en' => Str::limit(strip_tags($a->body_en ?: $a->body_kh ?: 'Announcement details...'), 85),
                    'time_km' => self::formatKhmerRelativeTime($createdAt),
                    'time_en' => self::formatEnglishRelativeTime($createdAt),
                    'type' => 'announcement',
                    'defaultRead' => false,
                    'read' => false,
                    'link' => '/student/notifications/announcements',
                    'created_at' => $createdAt->toISOString(),
                ];
            }
        } catch (\Throwable $e) {}

        // 2. Notifications Table (System & AI alerts)
        try {
            $dbNotifs = Notification::whereIn('target', ['all', 'students'])
                ->latest()
                ->take(4)
                ->get();

            foreach ($dbNotifs as $n) {
                $createdAt = Carbon::parse($n->created_at ?: Carbon::now());
                $isAi = Str::contains(strtolower($n->title), 'ai');
                $items[] = [
                    'id' => 2000 + (int)$n->id,
                    'title_km' => $isAi ? ('🤖 AI: ' . $n->title) : $n->title,
                    'title_en' => $isAi ? ('🤖 AI: ' . $n->title) : $n->title,
                    'desc_km' => Str::limit($n->message, 85),
                    'desc_en' => Str::limit($n->message, 85),
                    'time_km' => self::formatKhmerRelativeTime($createdAt),
                    'time_en' => self::formatEnglishRelativeTime($createdAt),
                    'type' => $isAi ? 'ai' : 'announcement',
                    'defaultRead' => false,
                    'read' => false,
                    'link' => $isAi ? '/student/ai-path/recommended' : '/student/notifications/announcements',
                    'created_at' => $createdAt->toISOString(),
                ];
            }
        } catch (\Throwable $e) {}

        // 3. Enrolled Courses: Recent Lessons
        try {
            $enrolledCourseIds = Enrollment::where('student_id', $user->id)->pluck('course_id');
            if ($enrolledCourseIds->isNotEmpty()) {
                $recentLessons = Lesson::whereHas('module', function ($q) use ($enrolledCourseIds) {
                    $q->whereIn('course_id', $enrolledCourseIds);
                })->with('module.course')->latest()->take(3)->get();

                foreach ($recentLessons as $l) {
                    $createdAt = Carbon::parse($l->created_at ?: Carbon::now()->subHours(1));
                    $courseTitle = $l->module->course->title ?? 'Course';
                    $courseId = $l->module->course_id ?? 1;

                    $items[] = [
                        'id' => 3000 + (int)$l->id,
                        'title_km' => 'មេរៀនថ្មីត្រូវបានបន្ថែម: ' . $courseTitle,
                        'title_en' => 'New Lesson Available: ' . $courseTitle,
                        'desc_km' => 'មេរៀនថ្មី "' . $l->title . '" អាចចូលរៀនបានហើយ',
                        'desc_en' => 'New lesson "' . $l->title . '" is now available',
                        'time_km' => self::formatKhmerRelativeTime($createdAt),
                        'time_en' => self::formatEnglishRelativeTime($createdAt),
                        'type' => 'lesson',
                        'defaultRead' => false,
                        'read' => false,
                        'link' => "/student/learn/{$courseId}?lesson_id={$l->id}",
                        'created_at' => $createdAt->toISOString(),
                    ];
                }
            }
        } catch (\Throwable $e) {}

        // 4. Enrolled Courses: Recent Quizzes
        try {
            if (isset($enrolledCourseIds) && $enrolledCourseIds->isNotEmpty()) {
                $recentQuizzes = Quiz::whereIn('course_id', $enrolledCourseIds)
                    ->with('course')
                    ->latest()
                    ->take(3)
                    ->get();

                foreach ($recentQuizzes as $q) {
                    $createdAt = Carbon::parse($q->created_at ?: Carbon::now()->subHours(3));
                    $courseTitle = $q->course->title ?? 'Course';

                    $items[] = [
                        'id' => 4000 + (int)$q->id,
                        'title_km' => 'Quiz ថ្មីអាចធ្វើបានហើយ: ' . $q->title,
                        'title_en' => 'New Quiz Available: ' . $q->title,
                        'desc_km' => 'គ្រូបង្រៀនបានបើក Quiz សម្រាប់ ' . $courseTitle,
                        'desc_en' => 'Teacher unlocked Practice Quiz for ' . $courseTitle,
                        'time_km' => self::formatKhmerRelativeTime($createdAt),
                        'time_en' => self::formatEnglishRelativeTime($createdAt),
                        'type' => 'quiz',
                        'defaultRead' => false,
                        'read' => false,
                        'link' => '/student/quizzes',
                        'created_at' => $createdAt->toISOString(),
                    ];
                }
            }
        } catch (\Throwable $e) {}

        // 5. AI Recommendations Table
        try {
            $aiRecs = DB::table('ai_recommendations')
                ->where('user_id', $user->id)
                ->where('is_dismissed', 0)
                ->latest()
                ->take(3)
                ->get();

            foreach ($aiRecs as $r) {
                $createdAt = Carbon::parse($r->created_at ?: Carbon::now()->subHours(5));
                $items[] = [
                    'id' => 5000 + (int)$r->id,
                    'title_km' => '🤖 AI Recommendation: មេរៀនពង្រឹងសមត្ថភាព',
                    'title_en' => '🤖 AI Recommendation: Study Topic For You',
                    'desc_km' => Str::limit($r->reason, 85),
                    'desc_en' => Str::limit($r->reason, 85),
                    'time_km' => self::formatKhmerRelativeTime($createdAt),
                    'time_en' => self::formatEnglishRelativeTime($createdAt),
                    'type' => 'ai',
                    'defaultRead' => false,
                    'read' => false,
                    'link' => '/student/ai-path/recommended',
                    'created_at' => $createdAt->toISOString(),
                ];
            }
        } catch (\Throwable $e) {}

        // Sort descending by created_at
        usort($items, function ($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });

        // Take top 10 notifications
        $items = array_slice($items, 0, 10);

        return [
            'items' => $items,
        ];
    }

    /**
     * Mark a single notification ID as read for this user.
     */
    public static function markRead(User $user, int $notifId): void
    {
        $key = "user_{$user->id}_read_notifications";
        $readIds = Cache::get($key, []);
        if (!in_array($notifId, $readIds)) {
            $readIds[] = $notifId;
            Cache::put($key, $readIds, Carbon::now()->addDays(60));
        }
    }

    /**
     * Mark all notifications as read for this user.
     */
    public static function markAllRead(User $user, array $itemIds = []): void
    {
        $key = "user_{$user->id}_read_notifications";
        $readIds = Cache::get($key, []);

        if (empty($itemIds)) {
            $payload = self::getForUser($user);
            $itemIds = array_column($payload['items'] ?? [], 'id');
        }

        $merged = array_unique(array_merge($readIds, $itemIds));
        Cache::put($key, $merged, Carbon::now()->addDays(60));
    }

    /**
     * Relative time in Khmer.
     */
    public static function formatKhmerRelativeTime(Carbon $carbon): string
    {
        $now = Carbon::now();
        $diffMinutes = (int) $carbon->diffInMinutes($now);
        if ($diffMinutes < 1) return 'ទើបតែឥឡូវនេះ';
        if ($diffMinutes < 60) return $diffMinutes . ' នាទីមុន';
        $diffHours = (int) $carbon->diffInHours($now);
        if ($diffHours < 24) return $diffHours . ' ម៉ោងមុន';
        $diffDays = (int) $carbon->diffInDays($now);
        if ($diffDays < 7) return $diffDays . ' ថ្ងៃមុន';
        if ($diffDays < 30) return (int)ceil($diffDays / 7) . ' សប្តាហ៍មុន';
        return $carbon->format('d M Y');
    }

    /**
     * Relative time in English.
     */
    public static function formatEnglishRelativeTime(Carbon $carbon): string
    {
        $now = Carbon::now();
        $diffMinutes = (int) $carbon->diffInMinutes($now);
        if ($diffMinutes < 1) return 'Just now';
        if ($diffMinutes < 60) return $diffMinutes . 'm ago';
        $diffHours = (int) $carbon->diffInHours($now);
        if ($diffHours < 24) return $diffHours . ($diffHours === 1 ? ' hour ago' : ' hours ago');
        $diffDays = (int) $carbon->diffInDays($now);
        if ($diffDays < 7) return $diffDays . ($diffDays === 1 ? ' day ago' : ' days ago');
        if ($diffDays < 30) return (int)ceil($diffDays / 7) . ' weeks ago';
        return $carbon->format('d M Y');
    }
}
