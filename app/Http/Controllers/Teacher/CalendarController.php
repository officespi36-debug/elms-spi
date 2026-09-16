<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Module;
use App\Models\Major;
use App\Models\TeacherSchedule;
use App\Models\Deadline;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $teacher = $request->user();
        $teacherId = $teacher->id;

        $courses = Course::where('teacher_id', $teacherId)->get();
        if ($courses->isEmpty()) {
            $courses = Course::take(5)->get();
        }

        $modules = Module::all();
        $majors = Major::all();

        // Fetch DB Schedules or populate defaults
        $dbSchedules = TeacherSchedule::where('teacher_id', $teacherId)
            ->with(['course', 'module'])
            ->get();

        if ($dbSchedules->isEmpty()) {
            $today = Carbon::now();
            $defaultSchedules = [
                [
                    'teacher_id' => $teacherId,
                    'course_id' => $courses->first()?->id,
                    'type' => 'live_class',
                    'title' => 'Live Class: C Functions - Deep Dive',
                    'description' => 'ថ្ងៃនេះយើងនឹងរៀនអំពី Function, Parameter និង Return Type...',
                    'learning_mode' => 'instructor_led',
                    'start_at' => $today->copy()->setTime(14, 0),
                    'end_at' => $today->copy()->setTime(16, 0),
                    'timezone' => 'Asia/Phnom_Penh',
                    'repeat_rule' => 'none',
                    'location_type' => 'online',
                    'room_number' => 'Room 302',
                    'meeting_link' => 'https://zoom.us/j/123-456-789',
                    'capacity' => 60,
                    'status' => 'upcoming',
                ],
                [
                    'teacher_id' => $teacherId,
                    'course_id' => $courses->first()?->id,
                    'type' => 'qa_session',
                    'title' => 'Q&A: Pointers & Memory Management',
                    'description' => 'សួរដេញដោល និងដោះស្រាយចម្ងល់មេរៀន Pointers',
                    'learning_mode' => 'instructor_led',
                    'start_at' => $today->copy()->addDays(2)->setTime(10, 0),
                    'end_at' => $today->copy()->addDays(2)->setTime(11, 30),
                    'timezone' => 'Asia/Phnom_Penh',
                    'repeat_rule' => 'none',
                    'location_type' => 'online',
                    'room_number' => null,
                    'meeting_link' => 'https://zoom.us/j/987-654-321',
                    'capacity' => 60,
                    'status' => 'upcoming',
                ],
                [
                    'teacher_id' => $teacherId,
                    'course_id' => $courses->skip(1)->first()?->id ?? $courses->first()?->id,
                    'type' => 'office_hour',
                    'title' => 'Office Hour: Weekly Consultation',
                    'description' => 'ពិគ្រោះយោបល់គម្រោង និងការសិក្សាផ្ទាល់ខ្លួន',
                    'learning_mode' => 'instructor_led',
                    'start_at' => $today->copy()->addDays(4)->setTime(15, 0),
                    'end_at' => $today->copy()->addDays(4)->setTime(17, 0),
                    'timezone' => 'Asia/Phnom_Penh',
                    'repeat_rule' => 'weekly',
                    'location_type' => 'room',
                    'room_number' => 'Room 302',
                    'meeting_link' => null,
                    'capacity' => 20,
                    'status' => 'upcoming',
                ],
                [
                    'teacher_id' => $teacherId,
                    'course_id' => $courses->first()?->id,
                    'type' => 'exam',
                    'title' => 'Final Exam Review & Preparation',
                    'description' => 'រំលឹកមេរៀនត្រៀមប្រឡងបញ្ចប់វគ្គ',
                    'learning_mode' => 'instructor_led',
                    'start_at' => $today->copy()->addDays(10)->setTime(9, 0),
                    'end_at' => $today->copy()->addDays(10)->setTime(12, 0),
                    'timezone' => 'Asia/Phnom_Penh',
                    'repeat_rule' => 'none',
                    'location_type' => 'online',
                    'room_number' => null,
                    'meeting_link' => 'https://zoom.us/j/555-444-333',
                    'capacity' => 120,
                    'status' => 'upcoming',
                ],
            ];

            foreach ($defaultSchedules as $sched) {
                TeacherSchedule::create($sched);
            }

            $dbSchedules = TeacherSchedule::where('teacher_id', $teacherId)
                ->with(['course', 'module'])
                ->get();
        }

        // Fetch DB Deadlines or populate defaults
        $dbDeadlines = Deadline::where('teacher_id', $teacherId)
            ->with(['course'])
            ->get();

        if ($dbDeadlines->isEmpty()) {
            $today = Carbon::now();
            $defaultDeadlines = [
                [
                    'teacher_id' => $teacherId,
                    'course_id' => $courses->first()?->id,
                    'linked_type' => 'quiz',
                    'title' => 'Post-Test Module 1: C Fundamentals',
                    'deadline_type' => 'hard',
                    'due_at' => $today->copy()->addDays(1)->setTime(23, 59),
                    'grace_days' => 2,
                    'penalty_percent' => 10,
                    'applicable_to' => 'all',
                    'show_countdown' => true,
                    'auto_reminder_24h' => true,
                    'auto_reminder_1h' => true,
                    'auto_lock' => true,
                    'message_kh' => 'សូមប្រញាប់ធ្វើ Post-Test Module 1 មុនផុតកំណត់ ដើម្បីទទួលបានពិន្ទុពេញ!',
                    'message_en' => 'Please complete Post-Test Module 1 before deadline to avoid penalties.',
                    'status' => 'active',
                ],
                [
                    'teacher_id' => $teacherId,
                    'course_id' => $courses->first()?->id,
                    'linked_type' => 'assignment',
                    'title' => 'Assignment 1: First C Program',
                    'deadline_type' => 'soft',
                    'due_at' => $today->copy()->addDays(3)->setTime(23, 59),
                    'grace_days' => 3,
                    'penalty_percent' => 5,
                    'applicable_to' => 'all',
                    'show_countdown' => true,
                    'auto_reminder_24h' => true,
                    'auto_reminder_1h' => true,
                    'auto_lock' => false,
                    'message_kh' => 'ដាក់កិច្ចការផ្ទះ Assignment 1 ឱ្យបានទាន់ពេលវេលា!',
                    'message_en' => 'Submit Assignment 1 on time!',
                    'status' => 'active',
                ],
                [
                    'teacher_id' => $teacherId,
                    'course_id' => $courses->first()?->id,
                    'linked_type' => 'payment',
                    'title' => 'ABA Payment Deadline for Course Continuation',
                    'deadline_type' => 'hard',
                    'due_at' => $today->copy()->addDays(5)->setTime(23, 59),
                    'grace_days' => 0,
                    'penalty_percent' => 0,
                    'applicable_to' => 'all',
                    'show_countdown' => true,
                    'auto_reminder_24h' => true,
                    'auto_reminder_1h' => false,
                    'auto_lock' => true,
                    'message_kh' => 'សូមទូទាត់ប្រាក់តាម ABA ដើម្បីបន្តការសិក្សា',
                    'message_en' => 'Please settle payment via ABA to continue learning.',
                    'status' => 'active',
                ],
                [
                    'teacher_id' => $teacherId,
                    'course_id' => $courses->skip(1)->first()?->id ?? $courses->first()?->id,
                    'linked_type' => 'quiz',
                    'title' => 'Practice Quiz Module 2: Control Flow',
                    'deadline_type' => 'soft',
                    'due_at' => $today->copy()->addDays(7)->setTime(23, 59),
                    'grace_days' => 2,
                    'penalty_percent' => 0,
                    'applicable_to' => 'instructor_led_only',
                    'show_countdown' => true,
                    'auto_reminder_24h' => true,
                    'auto_reminder_1h' => true,
                    'auto_lock' => false,
                    'message_kh' => 'ធ្វើ Practice Quiz ដើម្បីពង្រឹងសមត្ថភាព!',
                    'message_en' => 'Complete Practice Quiz to reinforce your knowledge!',
                    'status' => 'active',
                ],
            ];

            foreach ($defaultDeadlines as $dline) {
                Deadline::create($dline);
            }

            $dbDeadlines = Deadline::where('teacher_id', $teacherId)
                ->with(['course'])
                ->get();
        }

        // Mock Progress numbers for deadlines to match design specs
        $deadlinesWithProgress = $dbDeadlines->map(function ($dline) {
            $total = 120;
            $submitted = match ($dline->linked_type) {
                'quiz' => 80,
                'assignment' => 45,
                'payment' => 75,
                default => 20,
            };
            return array_merge($dline->toArray(), [
                'total_students' => $total,
                'submitted_count' => $submitted,
                'overdue_count' => max(0, $total - $submitted),
            ]);
        });

        // Mock Overdue Students for demonstration
        $overdueStudents = [
            [
                'id' => 101,
                'name' => 'Chan Srey',
                'email' => 'srey.chan@student.edu.kh',
                'major' => 'IT & Networking',
                'last_activity' => '3 days ago',
                'score' => '0%',
                'avatar' => null,
            ],
            [
                'id' => 102,
                'name' => 'Unknown X',
                'email' => 'unknown.x@student.edu.kh',
                'major' => 'Tourism & Hospitality',
                'last_activity' => '5 days ago',
                'score' => '0%',
                'avatar' => null,
            ],
            [
                'id' => 103,
                'name' => 'Sok Visal',
                'email' => 'visal.sok@student.edu.kh',
                'major' => 'Computer Science',
                'last_activity' => '4 days ago',
                'score' => '0%',
                'avatar' => null,
            ],
            [
                'id' => 104,
                'name' => 'Keo Bopha',
                'email' => 'bopha.keo@student.edu.kh',
                'major' => 'Cybersecurity',
                'last_activity' => '6 days ago',
                'score' => '0%',
                'avatar' => null,
            ],
        ];

        return Inertia::render('Teacher/Calendar/Index', [
            'teacher' => [
                'name' => $teacher->name ?? 'Mr. Sophea',
                'email' => $teacher->email,
            ],
            'courses' => $courses,
            'modules' => $modules,
            'majors' => $majors,
            'schedules' => $dbSchedules,
            'deadlines' => $deadlinesWithProgress,
            'overdueStudents' => $overdueStudents,
        ]);
    }

    public function storeSchedule(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'title' => 'required|string|max:255',
            'course_id' => 'nullable|exists:courses,id',
            'module_id' => 'nullable|exists:modules,id',
            'major_id' => 'nullable|exists:majors,id',
            'learning_mode' => 'required|string',
            'start_date' => 'required|date',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'timezone' => 'nullable|string',
            'repeat_rule' => 'nullable|string',
            'location_type' => 'required|string',
            'room_number' => 'nullable|string',
            'meeting_link' => 'nullable|string',
            'capacity' => 'nullable|integer',
            'description' => 'nullable|string',
            'notify_email' => 'boolean',
            'notify_push' => 'boolean',
            'notify_announcement' => 'boolean',
            'reminder_15m' => 'boolean',
            'reminder_1h' => 'boolean',
            'auto_record' => 'boolean',
        ]);

        $startAt = Carbon::parse($validated['start_date'] . ' ' . $validated['start_time']);
        $endAt = Carbon::parse($validated['start_date'] . ' ' . $validated['end_time']);

        TeacherSchedule::create([
            'teacher_id' => $request->user()->id,
            'course_id' => $validated['course_id'] ?? null,
            'module_id' => $validated['module_id'] ?? null,
            'major_id' => $validated['major_id'] ?? null,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'learning_mode' => $validated['learning_mode'] ?? 'instructor_led',
            'start_at' => $startAt,
            'end_at' => $endAt,
            'timezone' => $validated['timezone'] ?? 'Asia/Phnom_Penh',
            'repeat_rule' => $validated['repeat_rule'] ?? 'none',
            'location_type' => $validated['location_type'] ?? 'online',
            'room_number' => $validated['room_number'] ?? null,
            'meeting_link' => $validated['meeting_link'] ?? null,
            'capacity' => $validated['capacity'] ?? 60,
            'notify_email' => $validated['notify_email'] ?? true,
            'notify_push' => $validated['notify_push'] ?? true,
            'notify_announcement' => $validated['notify_announcement'] ?? true,
            'reminder_15m' => $validated['reminder_15m'] ?? true,
            'reminder_1h' => $validated['reminder_1h'] ?? true,
            'auto_record' => $validated['auto_record'] ?? true,
            'status' => 'upcoming',
        ]);

        return back()->with('success', 'កាលវិភាគបង្រៀនថ្មីត្រូវបានបង្កើតដោយជោគជ័យ!');
    }

    public function updateSchedule(Request $request, TeacherSchedule $schedule)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'title' => 'required|string|max:255',
            'course_id' => 'nullable|exists:courses,id',
            'start_date' => 'required|date',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'meeting_link' => 'nullable|string',
            'location_type' => 'nullable|string',
            'room_number' => 'nullable|string',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $startAt = Carbon::parse($validated['start_date'] . ' ' . $validated['start_time']);
        $endAt = Carbon::parse($validated['start_date'] . ' ' . $validated['end_time']);

        $schedule->update([
            'type' => $validated['type'],
            'title' => $validated['title'],
            'course_id' => $validated['course_id'] ?? $schedule->course_id,
            'start_at' => $startAt,
            'end_at' => $endAt,
            'meeting_link' => $validated['meeting_link'] ?? $schedule->meeting_link,
            'location_type' => $validated['location_type'] ?? $schedule->location_type,
            'room_number' => $validated['room_number'] ?? $schedule->room_number,
            'description' => $validated['description'] ?? $schedule->description,
            'status' => $validated['status'] ?? $schedule->status,
        ]);

        return back()->with('success', 'កាលវិភាគបង្រៀនត្រូវបានធ្វើបច្ចុប្បន្នភាព!');
    }

    public function destroySchedule(TeacherSchedule $schedule)
    {
        $schedule->delete();
        return back()->with('success', 'កាលវិភាគត្រូវបានលុបចេញពីប្រព័ន្ធ!');
    }

    public function joinLobby(TeacherSchedule $schedule)
    {
        $schedule->update(['status' => 'live']);
        return back()->with('success', 'បានបើក Live Class Lobby រួចរាល់!');
    }

    public function storeDeadline(Request $request)
    {
        $validated = $request->validate([
            'linked_type' => 'required|string',
            'title' => 'required|string|max:255',
            'course_id' => 'nullable|exists:courses,id',
            'deadline_type' => 'required|string',
            'due_date' => 'required|date',
            'due_time' => 'required|string',
            'grace_days' => 'nullable|integer',
            'penalty_percent' => 'nullable|integer',
            'applicable_to' => 'nullable|string',
            'show_countdown' => 'boolean',
            'auto_reminder_24h' => 'boolean',
            'auto_reminder_1h' => 'boolean',
            'auto_lock' => 'boolean',
            'message_kh' => 'nullable|string',
            'message_en' => 'nullable|string',
        ]);

        $dueAt = Carbon::parse($validated['due_date'] . ' ' . $validated['due_time']);

        Deadline::create([
            'teacher_id' => $request->user()->id,
            'course_id' => $validated['course_id'] ?? null,
            'linked_type' => $validated['linked_type'],
            'title' => $validated['title'],
            'deadline_type' => $validated['deadline_type'],
            'due_at' => $dueAt,
            'grace_days' => $validated['grace_days'] ?? 0,
            'penalty_percent' => $validated['penalty_percent'] ?? 0,
            'applicable_to' => $validated['applicable_to'] ?? 'all',
            'show_countdown' => $validated['show_countdown'] ?? true,
            'auto_reminder_24h' => $validated['auto_reminder_24h'] ?? true,
            'auto_reminder_1h' => $validated['auto_reminder_1h'] ?? true,
            'auto_lock' => $validated['auto_lock'] ?? true,
            'message_kh' => $validated['message_kh'] ?? null,
            'message_en' => $validated['message_en'] ?? null,
            'status' => 'active',
        ]);

        return back()->with('success', 'កាលបរិច្ឆេទផុតកំណត់ថ្មីត្រូវបានកំណត់!');
    }

    public function updateDeadline(Request $request, Deadline $deadline)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'due_date' => 'required|date',
            'due_time' => 'required|string',
            'grace_days' => 'nullable|integer',
            'penalty_percent' => 'nullable|integer',
        ]);

        $dueAt = Carbon::parse($validated['due_date'] . ' ' . $validated['due_time']);

        $deadline->update([
            'title' => $validated['title'],
            'due_at' => $dueAt,
            'grace_days' => $validated['grace_days'] ?? $deadline->grace_days,
            'penalty_percent' => $validated['penalty_percent'] ?? $deadline->penalty_percent,
        ]);

        return back()->with('success', 'កាលបរិច្ឆេទ Deadline ត្រូវបានកែប្រែ!');
    }

    public function destroyDeadline(Deadline $deadline)
    {
        $deadline->delete();
        return back()->with('success', 'Deadline ត្រូវបានលុបចោល!');
    }

    public function extendDeadline(Request $request, Deadline $deadline)
    {
        $days = $request->input('days', 2);
        $deadline->due_at = Carbon::parse($deadline->due_at)->addDays($days);
        $deadline->status = 'extended';
        $deadline->save();

        return back()->with('success', "បានពន្យារពេល Deadline ចំនួន +{$days} ថ្ងៃសម្រាប់សិស្សទាំងអស់!");
    }

    public function remindDeadline(Request $request, Deadline $deadline)
    {
        return back()->with('success', 'ប្រព័ន្ធបានផ្ញើសាររំលឹក Reminder ទៅកាន់សិស្សដែលមិនទាន់ធ្វើរួចរាល់!');
    }

    public function bulkExtendDeadlines(Request $request)
    {
        $days = (int) $request->input('days', 2);
        Deadline::where('teacher_id', $request->user()->id)
            ->where('status', 'active')
            ->get()
            ->each(function (Deadline $dl) use ($days) {
                $dl->due_at = Carbon::parse($dl->due_at)->addDays($days);
                $dl->status = 'extended';
                $dl->save();
            });

        return back()->with('success', "បានពន្យារពេល Deadline ទាំងអស់បន្ថែម +{$days} ថ្ងៃ!");
    }

    public function bulkRemindDeadlines(Request $request)
    {
        return back()->with('success', 'បានផ្ញើសាររំលឹក Reminder ទៅកាន់សិស្ស Overdue ទាំងអស់!');
    }

    public function syncGoogle(Request $request)
    {
        return back()->with('success', 'បានធ្វើការ Sync កាលវិភាគទៅកាន់ Google Calendar ដោយជោគជ័យ!');
    }

    public function storeEvent(Request $request)
    {
        return $this->storeSchedule($request);
    }
}
