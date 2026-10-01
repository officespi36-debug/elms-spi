<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\EmailTemplate;
use App\Models\PushNotification;
use App\Models\NotificationAutomation;
use App\Models\NotificationLog;
use App\Models\SmtpSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    /**
     * Top-level common metrics & service statuses
     */
    private function getCommonStats()
    {
        return [
            'total_sent' => '28,450',
            'email_delivered_rate' => '96.8%',
            'push_open_rate' => '72.4%',
            'scheduled_pending' => 17,
            'failed_count' => 142,
            'avg_read_rate' => '84.2%',
            'services' => [
                'smtp' => ['status' => 'connected', 'label' => '🟢 Connected'],
                'firebase' => ['status' => 'active', 'label' => '🟢 Active'],
                'sms_gateway' => ['status' => 'limited', 'label' => '🟡 Limited Credit ($12)'],
            ]
        ];
    }

    /**
     * 1. 📢 Announcements View
     */
    public function announcements(Request $request)
    {
        $announcements = Announcement::latest()->get();
        if ($announcements->isEmpty()) {
            // Seed rich default mock announcements matching SPI 5 majors
            $announcements = collect([
                [
                    'id' => 1,
                    'title_kh' => 'ការចុះឈ្មោះមុខវិជ្ជាឆមាសទី១ ឆ្នាំ២០២៦',
                    'title_en' => 'Semester 1 Course Registration 2026',
                    'message_kh' => 'សូមជម្រាបជូននិស្សិតដេប៉ាតឺម៉ង់ព័ត៌មានវិទ្យាទាំងអស់ ការចុះឈ្មោះមុខវិជ្ជាសម្រាប់ឆមាសទី១ បានបើកហើយ។ សូមចូលទៅកាន់ Course Registration មុនថ្ងៃទី ១០ តុលា។',
                    'message_en' => 'Notice to all IT students: Semester 1 course registration is now officially open. Please register before October 10th.',
                    'audience_type' => 'students_by_major',
                    'audience_label' => 'Students by Major',
                    'major' => 'Information Technology',
                    'course' => 'All Year 2 IT Courses',
                    'academic_year' => '2026',
                    'published_date' => '2026-09-28 08:30',
                    'status' => 'Published',
                    'priority' => 'high',
                    'attachment' => 'IT_Course_Catalog_2026.pdf',
                    'sent_count' => 520,
                    'read_rate' => '94%',
                    'is_pinned' => true,
                ],
                [
                    'id' => 2,
                    'title_kh' => 'កាលវិភាគប្រឡងបញ្ចប់វគ្គសិក្សាផ្លូវការ',
                    'title_en' => 'Official Final Examination Schedule',
                    'message_kh' => 'វិទ្យាស្ថានសូមជូនដំណឹងអំពីកាលវិភាគប្រឡងបញ្ចប់វគ្គសិក្សា សម្រាប់និស្សិតគ្រប់ជំនាញទាំង ៥ សូមពិនិត្យកាលវិភាគលម្អិតក្នុងឯកសារភ្ជាប់។',
                    'message_en' => 'The institute announces the official final examination schedule for all 5 majors. Check the attached document for details.',
                    'audience_type' => 'all_students',
                    'audience_label' => 'All Students',
                    'major' => 'All Majors',
                    'course' => 'All Courses',
                    'academic_year' => '2026',
                    'published_date' => '2026-09-30 09:00',
                    'status' => 'Published',
                    'priority' => 'urgent',
                    'attachment' => 'SPI_Final_Exam_Schedule_2026.pdf',
                    'sent_count' => 2458,
                    'read_rate' => '96%',
                    'is_pinned' => true,
                ],
                [
                    'id' => 3,
                    'title_kh' => 'ដំណើរសិក្សាស្រាវជ្រាវជីវជាតិដីកសិកម្ម (Field Trip)',
                    'title_en' => 'Soil Science Field Trip & Research Workshop',
                    'message_kh' => 'ជូនដំណឹងដល់និស្សិតជំនាញកសិកម្ម ដំណើរសិក្សាស្រាវជ្រាវដី និងសារធាតុចិញ្ចឹមដំណាំនឹងប្រព្រឹត្តទៅនៅស្រុកបន្ទាយស្រី។',
                    'message_en' => 'Field study research on soil nutrition for Agriculture students will be conducted at Banteay Srei district.',
                    'audience_type' => 'students_by_major',
                    'audience_label' => 'Students by Major',
                    'major' => 'Agriculture',
                    'course' => 'Soil Science & Plant Nutrition',
                    'academic_year' => '2026',
                    'published_date' => '2026-10-05 07:30',
                    'status' => 'Scheduled',
                    'priority' => 'medium',
                    'attachment' => 'Field_Trip_Guide_Agriculture.pdf',
                    'sent_count' => 380,
                    'read_rate' => '0%',
                    'is_pinned' => false,
                ],
                [
                    'id' => 4,
                    'title_kh' => 'កិច្ចប្រជុំគណៈកម្មការកែលម្អកម្មវិធីសិក្សា',
                    'title_en' => 'Curriculum Revision & Faculty Board Meeting',
                    'message_kh' => 'សូមអញ្ជើញលោកគ្រូ-អ្នកគ្រូប្រធានដេប៉ាតឺម៉ង់ និងសាស្ត្រាចារ្យទាំងអស់ចូលរួមកិច្ចប្រជុំត្រួតពិនិត្យ Syllabus នៅបន្ទប់សន្និសីទ។',
                    'message_en' => 'All department heads and faculty members are invited to the curriculum and syllabus review meeting.',
                    'audience_type' => 'all_teachers',
                    'audience_label' => 'All Teachers',
                    'major' => 'All Departments',
                    'course' => '-',
                    'academic_year' => '2026',
                    'published_date' => '2026-09-29 14:00',
                    'status' => 'Published',
                    'priority' => 'high',
                    'attachment' => 'Curriculum_Agenda_Agenda.pdf',
                    'sent_count' => 145,
                    'read_rate' => '91%',
                    'is_pinned' => false,
                ],
                [
                    'id' => 5,
                    'title_kh' => 'គោលការណ៍ណែនាំកិច្ចការស្រាវជ្រាវទេសចរណ៍',
                    'title_en' => 'Tourism Operations Midterm Assignment Guidelines',
                    'message_kh' => 'និស្សិតដែលរៀនមុខវិជ្ជា Tourism Operations & Booking Systems ត្រូវទាញយកទម្រង់ assignment គំរូ និងបញ្ជូនមុនថ្ងៃផុតកំណត់។',
                    'message_en' => 'Students enrolled in Tourism Operations & Booking Systems must review the assignment rubric and submit on time.',
                    'audience_type' => 'specific_course',
                    'audience_label' => 'Specific Course',
                    'major' => 'Tourism Management',
                    'course' => 'Tourism Operations & Booking Systems',
                    'academic_year' => '2026',
                    'published_date' => '2026-10-08 09:00',
                    'status' => 'Scheduled',
                    'priority' => 'medium',
                    'attachment' => 'Tourism_Assignment_Rubric.pdf',
                    'sent_count' => 245,
                    'read_rate' => '0%',
                    'is_pinned' => false,
                ],
                [
                    'id' => 6,
                    'title_kh' => 'សេចក្តីព្រាង៖ ការចុះអនុវត្តការងារសង្គមកិច្ចសហគមន៍',
                    'title_en' => 'Draft: Social Work Community Practicum Guidelines',
                    'message_kh' => 'សេចក្តីព្រាងបទបញ្ញត្តិស្តីពីការចុះកម្មសិក្សាផ្ទាល់នៅអង្គការដៃគូ និងមន្ទីរសង្គមកិច្ចខេត្តសៀមរាប។',
                    'message_en' => 'Drafting internship protocols with NGO partners and Siem Reap Social Affairs Department.',
                    'audience_type' => 'students_by_major',
                    'audience_label' => 'Students by Major',
                    'major' => 'Social Work',
                    'course' => 'Child Protection Protocols',
                    'academic_year' => '2026',
                    'published_date' => '-',
                    'status' => 'Draft',
                    'priority' => 'low',
                    'attachment' => '',
                    'sent_count' => 0,
                    'read_rate' => '0%',
                    'is_pinned' => false,
                ]
            ]);
        }

        return Inertia::render('Admin/NotificationModule/Announcements', [
            'announcements' => $announcements,
            'topStats' => $this->getCommonStats(),
        ]);
    }

    /**
     * 2. 🔔 Notifications Hub View (Official 8 Types)
     */
    public function notifications(Request $request)
    {
        return Inertia::render('Admin/NotificationModule/Notifications', [
            'topStats' => $this->getCommonStats(),
        ]);
    }

    /**
     * 2. 📧 Email Notifications View
     */
    public function emails(Request $request)
    {
        $templates = EmailTemplate::latest()->get();
        if ($templates->isEmpty()) {
            $templates = collect([
                ['id' => 1, 'name' => '🎉 Welcome Email', 'trigger_event' => 'student.registered', 'trigger_label' => 'Student Registration', 'sent_count' => 2458, 'open_rate' => '89%', 'status' => 'active'],
                ['id' => 2, 'name' => '💳 Payment Receipt', 'trigger_event' => 'payment.success', 'trigger_label' => 'ABA Payment Success', 'sent_count' => 1240, 'open_rate' => '94%', 'status' => 'active'],
                ['id' => 3, 'name' => '⏰ Payment Reminder', 'trigger_event' => 'payment.unpaid_3days', 'trigger_label' => 'Unpaid after 3 days', 'sent_count' => 180, 'open_rate' => '76%', 'status' => 'active'],
                ['id' => 4, 'name' => '🎓 Certificate Issued', 'trigger_event' => 'course.completed', 'trigger_label' => 'Course Completion', 'sent_count' => 485, 'open_rate' => '92%', 'status' => 'active'],
                ['id' => 5, 'name' => '⚠️ At-Risk Alert', 'trigger_event' => 'progress.low', 'trigger_label' => 'Progress < 40%', 'sent_count' => 213, 'open_rate' => '54%', 'status' => 'active'],
                ['id' => 6, 'name' => '🔐 Password Reset', 'trigger_event' => 'auth.password_reset', 'trigger_label' => 'Reset Request', 'sent_count' => 145, 'open_rate' => '98%', 'status' => 'active'],
                ['id' => 7, 'name' => '📝 Assignment Due', 'trigger_event' => 'assignment.due_24h', 'trigger_label' => '24h before deadline', 'sent_count' => 890, 'open_rate' => '71%', 'status' => 'active'],
                ['id' => 8, 'name' => '🤖 AI Recommendation', 'trigger_event' => 'ai.weak_topic', 'trigger_label' => 'Weak Topic Detected', 'sent_count' => 1450, 'open_rate' => '62%', 'status' => 'active'],
            ]);
        }

        $smtp = SmtpSetting::first() ?? [
            'provider' => 'mailgun',
            'host' => 'smtp.mailgun.org',
            'port' => 587,
            'encryption' => 'tls',
            'username' => 'postmaster@elms.com',
            'from_email' => 'noreply@elms.com',
            'daily_limit' => 10000,
            'used_today' => 1245,
            'status' => 'connected',
        ];

        return Inertia::render('Admin/NotificationModule/EmailNotifications', [
            'templates' => $templates,
            'smtp' => $smtp,
            'topStats' => $this->getCommonStats(),
        ]);
    }

    /**
     * 3. 📱 Push Notifications View
     */
    public function push(Request $request)
    {
        $pushes = PushNotification::latest()->get();
        if ($pushes->isEmpty()) {
            $pushes = collect([
                ['id' => 1, 'title' => '🎬 New video uploaded', 'type' => 'Content Update', 'audience' => 'IT Students', 'sent_count' => 520, 'open_rate' => '78%', 'status' => 'Sent', 'created_at' => '2025-05-26 15:30'],
                ['id' => 2, 'title' => '⏰ Quiz starts in 1hr', 'type' => 'Reminder', 'audience' => 'Enrolled', 'sent_count' => 245, 'open_rate' => '91%', 'status' => 'Sent', 'created_at' => '2025-05-26 12:00'],
                ['id' => 3, 'title' => '🔥 Don\'t lose streak!', 'type' => 'Engagement', 'audience' => 'Inactive 3d', 'sent_count' => 380, 'open_rate' => '64%', 'status' => 'Sent', 'created_at' => '2025-05-25 18:00'],
                ['id' => 4, 'title' => '🎓 Certificate ready!', 'type' => 'Achievement', 'audience' => 'Completers', 'sent_count' => 45, 'open_rate' => '96%', 'status' => 'Sent', 'created_at' => '2025-05-24 10:15'],
            ]);
        }

        return Inertia::render('Admin/NotificationModule/PushNotifications', [
            'pushes' => $pushes,
            'topStats' => $this->getCommonStats(),
            'deviceStats' => [
                'android' => 1845,
                'ios' => 620,
                'web' => 890,
            ]
        ]);
    }

    /**
     * 4. ⏰ Scheduled Notifications & Automations View
     */
    public function scheduled(Request $request)
    {
        $queue = collect([
            ['id' => 101, 'time' => 'Today 18:00', 'type' => '📱 Push', 'title' => 'Daily study reminder', 'audience' => '1,890 users', 'status' => 'pending'],
            ['id' => 102, 'time' => 'Tomorrow 08:00', 'type' => '📢 Announce', 'title' => 'Exam schedule notice', 'audience' => '245 students', 'status' => 'pending'],
            ['id' => 103, 'time' => '28/05 09:00', 'type' => '📧 Email', 'title' => 'Payment reminder notice', 'audience' => '180 students', 'status' => 'pending'],
            ['id' => 104, 'time' => '30/05 07:00', 'type' => '🚨 All Channels', 'title' => 'Exam day alert popup', 'audience' => '2,458 students', 'status' => 'pending'],
        ]);

        $automations = NotificationAutomation::latest()->get();
        if ($automations->isEmpty()) {
            $automations = collect([
                ['id' => 1, 'name' => 'Daily Study Reminder', 'trigger' => 'Every day 18:00', 'action' => '📱 Push', 'status' => 'active'],
                ['id' => 2, 'name' => 'Weekly Progress Report', 'trigger' => 'Every Sunday 20:00', 'action' => '📧 Email', 'status' => 'active'],
                ['id' => 3, 'name' => 'Inactivity Nudge', 'trigger' => 'No login for 3 days', 'action' => '📱 Push', 'status' => 'active'],
                ['id' => 4, 'name' => 'Payment Reminder', 'trigger' => 'Unpaid after 3 days', 'action' => '📧 + 📱', 'status' => 'active'],
                ['id' => 5, 'name' => 'Deadline Alert', 'trigger' => '24h before assignment due', 'action' => '📧 + 📱', 'status' => 'active'],
                ['id' => 6, 'name' => 'Birthday Wish 🎂', 'trigger' => 'Student birthday 08:00', 'action' => '📧 + 📱', 'status' => 'active'],
                ['id' => 7, 'name' => 'Course Anniversary', 'trigger' => '1 year after enrollment', 'action' => '📧 Email', 'status' => 'paused'],
            ]);
        }

        return Inertia::render('Admin/NotificationModule/ScheduledNotifications', [
            'queue' => $queue,
            'automations' => $automations,
            'topStats' => $this->getCommonStats(),
        ]);
    }

    /**
     * 5. 📜 Notification History View
     */
    public function history(Request $request)
    {
        $logs = NotificationLog::latest()->get();
        if ($logs->isEmpty()) {
            $logs = collect([
                [
                    'notif_code' => 'NOTIF-2025-28450',
                    'time' => '26/05 10:45',
                    'channel' => '📧 Email',
                    'title' => 'Payment Receipt',
                    'recipient_name' => 'Chan Dara',
                    'recipient_email' => 'dara@example.com',
                    'status' => '✅ Read',
                    'status_key' => 'read',
                    'device' => 'iPhone 15 / iOS 17',
                    'location' => 'Phnom Penh, KH',
                    'time_to_open' => '7 min 16 sec',
                ],
                [
                    'notif_code' => 'NOTIF-2025-28449',
                    'time' => '26/05 10:30',
                    'channel' => '📱 Push',
                    'title' => 'New video Mod 3',
                    'recipient_name' => '520 users',
                    'recipient_email' => 'IT & Networking',
                    'status' => '✅ 78% Open',
                    'status_key' => 'delivered',
                    'device' => 'Multi-Device',
                    'location' => 'Cambodia',
                    'time_to_open' => '12 min avg',
                ],
                [
                    'notif_code' => 'NOTIF-2025-28448',
                    'time' => '26/05 09:15',
                    'channel' => '📢 Announce',
                    'title' => 'Exam Schedule',
                    'recipient_name' => '2,458 users',
                    'recipient_email' => 'All Students',
                    'status' => '✅ 92% Read',
                    'status_key' => 'read',
                    'device' => 'Web & Mobile',
                    'location' => 'Cambodia',
                    'time_to_open' => '5 min avg',
                ],
                [
                    'notif_code' => 'NOTIF-2025-28447',
                    'time' => '26/05 08:00',
                    'channel' => '📧 Email',
                    'title' => 'At-Risk Alert',
                    'recipient_name' => 'Sok Channa',
                    'recipient_email' => 'channa@example.com',
                    'status' => '👁️ Opened',
                    'status_key' => 'opened',
                    'device' => 'Android Phone',
                    'location' => 'Siem Reap, KH',
                    'time_to_open' => '15 min',
                ],
                [
                    'notif_code' => 'NOTIF-2025-28446',
                    'time' => '25/05 22:10',
                    'channel' => '📧 Email',
                    'title' => 'Welcome Email',
                    'recipient_name' => 'new@ex.com',
                    'recipient_email' => 'new@ex.com',
                    'status' => '🔴 Bounce',
                    'status_key' => 'failed',
                    'failure_reason' => 'Invalid email domain address',
                    'device' => 'Unknown',
                    'location' => 'N/A',
                    'time_to_open' => 'N/A',
                ],
            ]);
        }

        return Inertia::render('Admin/NotificationModule/NotificationHistory', [
            'logs' => $logs,
            'topStats' => $this->getCommonStats(),
            'analytics' => [
                'delivery_trend' => [
                    ['date' => 'May 20', 'sent' => 850, 'delivered' => 840],
                    ['date' => 'May 21', 'sent' => 920, 'delivered' => 905],
                    ['date' => 'May 22', 'sent' => 1100, 'delivered' => 1080],
                    ['date' => 'May 23', 'sent' => 950, 'delivered' => 930],
                    ['date' => 'May 24', 'sent' => 1250, 'delivered' => 1210],
                    ['date' => 'May 25', 'sent' => 1400, 'delivered' => 1370],
                    ['date' => 'May 26', 'sent' => 1600, 'delivered' => 1560],
                ],
                'failure_reasons' => [
                    ['reason' => 'Invalid email address', 'count' => 68, 'pct' => '48%'],
                    ['reason' => 'Mailbox full / Quota', 'count' => 32, 'pct' => '23%'],
                    ['reason' => 'Device unregistered', 'count' => 28, 'pct' => '20%'],
                    ['reason' => 'Blocked / Spam filter', 'count' => 14, 'pct' => '9%'],
                ]
            ]
        ]);
    }

    /**
     * Store endpoints
     */
    public function storeAnnouncement(Request $request)
    {
        $validated = $request->validate([
            'title_kh' => 'required|string',
            'body_kh' => 'required|string',
            'priority' => 'required|string',
        ]);

        Announcement::create([
            'title_kh' => $validated['title_kh'],
            'title_en' => $request->input('title_en', ''),
            'body_kh' => $validated['body_kh'],
            'body_en' => $request->input('body_en', ''),
            'audience_type' => $request->input('audience_type', 'everyone'),
            'priority' => $validated['priority'],
            'delivery_channels' => $request->input('delivery_channels', ['in_app', 'email']),
            'status' => 'sent',
            'sent_count' => 245,
            'read_rate' => 0.00,
        ]);

        return back()->with('success', 'Announcement published successfully!');
    }

    public function storePush(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:50',
            'body' => 'required|string|max:120',
        ]);

        PushNotification::create([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'type' => $request->input('type', 'Content Update'),
            'target_audience' => $request->input('target_audience', 'IT Students'),
            'status' => 'Sent',
            'sent_count' => 520,
            'open_rate' => 78.00,
        ]);

        return back()->with('success', 'Push Notification sent successfully!');
    }
}
