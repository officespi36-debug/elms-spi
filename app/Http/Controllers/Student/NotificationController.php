<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\StudentNotificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'all');
        return Inertia::render('Student/Notifications/Index', [
            'activeTab' => $tab,
        ]);
    }

    public function announcements(Request $request)
    {
        $rows = \Illuminate\Support\Facades\DB::table('announcements')
            ->whereIn('audience_type', ['everyone', 'students', 'All Students & Teachers', 'Active Students'])
            ->orderByDesc('is_pinned')
            ->latest('id')
            ->get();

        $seen = [];
        $announcements = [];
        foreach ($rows as $row) {
            $dedupeKey = trim((string) ($row->title_en ?: $row->title_kh)) . '|' . trim((string) ($row->body_en ?: $row->body_kh));
            if (isset($seen[$dedupeKey])) {
                continue;
            }
            $seen[$dedupeKey] = true;
            $announcements[] = $row;
        }

        return Inertia::render('Student/Notifications/Announcements', [
            'dbAnnouncements' => $announcements,
        ]);
    }

    public function aiAlerts(Request $request)
    {
        return Inertia::render('Student/Notifications/AiAlerts');
    }

    public function teacherFeedback(Request $request)
    {
        return Inertia::render('Student/Notifications/TeacherFeedback');
    }

    public function paymentAlerts(Request $request)
    {
        return Inertia::render('Student/Notifications/PaymentAlerts');
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
     * API: Mark a notification as read.
     */
    public function apiMarkRead(Request $request)
    {
        $id = (int)$request->input('id');
        if ($id > 0) {
            StudentNotificationService::markRead($request->user(), $id);
        }
        return response()->json(['success' => true]);
    }

    /**
     * API: Mark all notifications as read.
     */
    public function apiMarkAllRead(Request $request)
    {
        $ids = $request->input('ids', []);
        StudentNotificationService::markAllRead($request->user(), is_array($ids) ? $ids : []);
        return response()->json(['success' => true]);
    }
}

