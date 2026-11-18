<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\AssignmentSubmission;
use App\Models\Deadline;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Semester;
use App\Models\TeacherSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tab = $request->query('tab', 'live-class');
        $data = $this->buildStudentCalendarData($user);

        return Inertia::render('Student/Calendar/Index', [
            'activeTab'       => $tab,
            'enrolledCourses' => $data['enrolledCourses'],
            'liveSchedules'   => $data['liveSchedules'],
            'upcomingQuizzes' => $data['upcomingQuizzes'],
            'assignmentsDue'  => $data['assignmentsDue'],
            'academicEvents'  => $data['academicEvents'],
            'calendarEvents'  => $data['calendarEvents'],
        ]);
    }

    public function liveClassSchedule(Request $request)
    {
        $user = $request->user();
        $data = $this->buildStudentCalendarData($user);

        return Inertia::render('Student/Calendar/LiveClassSchedule', [
            'enrolledCourses' => $data['enrolledCourses'],
            'liveSchedules'   => $data['liveSchedules'],
            'calendarEvents'  => $data['calendarEvents'],
        ]);
    }

    public function upcomingQuiz(Request $request)
    {
        $user = $request->user();
        $data = $this->buildStudentCalendarData($user);

        return Inertia::render('Student/Calendar/UpcomingQuiz', [
            'enrolledCourses' => $data['enrolledCourses'],
            'upcomingQuizzes' => $data['upcomingQuizzes'],
        ]);
    }

    public function assignmentDue(Request $request)
    {
        $user = $request->user();
        $data = $this->buildStudentCalendarData($user);

        return Inertia::render('Student/Calendar/AssignmentDue', [
            'enrolledCourses' => $data['enrolledCourses'],
            'assignmentsDue'  => $data['assignmentsDue'],
        ]);
    }

    public function academicEvents(Request $request)
    {
        $user = $request->user();
        $data = $this->buildStudentCalendarData($user);

        return Inertia::render('Student/Calendar/AcademicEvents', [
            'academicEvents' => $data['academicEvents'],
        ]);
    }

    /**
     * Build 100% real schedule, quiz, assignment deadline, and academic event data
     * for the authenticated student from MySQL.
     *
     * @param User $user
     * @return array<string, mixed>
     */
    protected function buildStudentCalendarData(User $user): array
    {
        $now = Carbon::now();

        // 1. Enrolled courses
        $enrollments = Enrollment::where('student_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->with('course:id,title,title_kh,code')
            ->get();

        $enrolledCourseIds = $enrollments->pluck('course_id')->filter()->unique()->values();

        $enrolledCourses = $enrollments
            ->map(fn ($e) => $e->course)
            ->filter()
            ->unique('id')
            ->values()
            ->map(fn ($c) => [
                'id'       => (int) $c->id,
                'title'    => $c->title,
                'title_kh' => $c->title_kh ?: $c->title,
                'code'     => $c->code,
            ])
            ->values()
            ->all();

        $calendarEvents = [];

        // 2. TeacherSchedule (live_class, qa_session, office_hour, exam)
        $rawSchedules = collect();
        if ($enrolledCourseIds->isNotEmpty() || !empty($user->major_id)) {
            $rawSchedules = TeacherSchedule::with([
                'teacher:id,name,name_kh',
                'course:id,title,title_kh,code',
                'module:id,title',
                'major:id,name,name_kh',
            ])
                ->where(function ($q) use ($enrolledCourseIds, $user) {
                    if ($enrolledCourseIds->isNotEmpty()) {
                        $q->whereIn('course_id', $enrolledCourseIds);
                    }
                    if (!empty($user->major_id)) {
                        $q->orWhere('major_id', $user->major_id);
                    }
                })
                ->whereIn('type', ['live_class', 'qa_session', 'office_hour', 'exam'])
                ->where(function ($q) {
                    $q->whereNull('status')
                      ->orWhereNotIn('status', ['cancelled', 'deleted']);
                })
                ->orderBy('start_at')
                ->get();
        }

        $liveSchedules = [];
        foreach ($rawSchedules as $sched) {
            $startAt = $sched->start_at ? Carbon::parse($sched->start_at) : null;
            $endAt = $sched->end_at ? Carbon::parse($sched->end_at) : null;
            $courseTitle = $sched->course?->title ?? ($sched->major?->name ?? null);
            $courseTitleKh = $sched->course?->title_kh ?: ($courseTitle ?? ($sched->major?->name_kh ?? null));

            $locationLabel = $sched->location_type === 'campus' || $sched->location_type === 'offline' || $sched->location_type === 'room'
                ? ($sched->room_number ? 'Room ' . $sched->room_number : 'On Campus')
                : 'Online Live Session';

            $timeLabel = '—';
            if ($startAt && $endAt) {
                $timeLabel = $startAt->format('h:i A') . ' - ' . $endAt->format('h:i A');
            } elseif ($startAt) {
                $timeLabel = $startAt->format('h:i A');
            }

            $isOngoing = $startAt && $endAt && $now->between($startAt, $endAt);
            $isUpcoming = $startAt ? ($startAt->isFuture() || $isOngoing) : false;

            $item = [
                'id'              => (int) $sched->id,
                'type'            => $sched->type ?: 'live_class',
                'title'           => $sched->title,
                'description'     => $sched->description,
                'course_id'       => $sched->course_id,
                'course_title'    => $courseTitle,
                'course_title_kh' => $courseTitleKh,
                'module_title'    => $sched->module?->title,
                'teacher_name'    => $sched->teacher?->name,
                'teacher_name_kh' => $sched->teacher?->name_kh ?: $sched->teacher?->name,
                'start_at'        => $startAt ? $startAt->toIso8601String() : null,
                'end_at'          => $endAt ? $endAt->toIso8601String() : null,
                'date_key'        => $startAt ? $startAt->format('Y-m-d') : null,
                'date_label'      => $startAt ? $startAt->format('M j, Y') : '—',
                'time_label'      => $timeLabel,
                'location_type'   => $sched->location_type ?: 'online',
                'location_label'  => $locationLabel,
                'room_number'     => $sched->room_number,
                'meeting_link'    => $sched->meeting_link,
                'capacity'        => $sched->capacity,
                'status'          => $sched->status ?: ($isOngoing ? 'live' : ($isUpcoming ? 'upcoming' : 'completed')),
                'is_ongoing'      => $isOngoing,
                'is_upcoming'     => $isUpcoming,
            ];

            $liveSchedules[] = $item;

            if ($startAt) {
                $calendarEvents[] = [
                    'id'              => 'sched_' . $sched->id,
                    'date'            => $startAt->format('Y-m-d'),
                    'type'            => $sched->type === 'exam' ? 'academic' : 'live_class',
                    'title'           => $sched->title,
                    'course_id'       => $sched->course_id,
                    'course_title'    => $courseTitle,
                    'course_title_kh' => $courseTitleKh,
                    'time'            => $startAt->format('h:i A'),
                    'status'          => $isUpcoming ? 'upcoming' : 'completed',
                    'href'            => '/student/calendar/live-class',
                    'meeting_link'    => $sched->meeting_link,
                ];
            }
        }

        // 3. Deadlines in enrolled courses (status != 'closed')
        $deadlines = $enrolledCourseIds->isNotEmpty()
            ? Deadline::with(['course:id,title,title_kh,code', 'teacher:id,name,name_kh'])
                ->whereIn('course_id', $enrolledCourseIds)
                ->where(function ($q) {
                    $q->whereNull('status')
                      ->orWhere('status', '!=', 'closed');
                })
                ->orderBy('due_at')
                ->get()
            : collect();

        $quizDeadlineMap = [];
        $assignmentDeadlineMap = [];
        foreach ($deadlines as $dl) {
            if ($dl->linked_type === 'quiz' && $dl->linked_id) {
                $quizDeadlineMap[(int) $dl->linked_id] = $dl;
            } elseif ($dl->linked_type === 'assignment' && $dl->linked_id) {
                $assignmentDeadlineMap[(int) $dl->linked_id] = $dl;
            }
        }

        // 4. Published Quizzes in enrolled courses + Student's QuizAttempts & AssignmentSubmissions
        $publishedQuizzes = $enrolledCourseIds->isNotEmpty()
            ? Quiz::whereIn('course_id', $enrolledCourseIds)
                ->where('status', 'published')
                ->with(['course:id,title,title_kh,code', 'module:id,title'])
                ->withCount('questions')
                ->latest()
                ->get()
            : collect();

        $attemptsByQuiz = QuizAttempt::where('user_id', $user->id)
            ->get()
            ->groupBy('quiz_id');

        $submissionsByQuiz = AssignmentSubmission::where('user_id', $user->id)
            ->whereNotNull('quiz_id')
            ->get()
            ->keyBy('quiz_id');

        $upcomingQuizzes = [];
        $assignmentsDue = [];
        $handledDeadlineIds = [];

        foreach ($publishedQuizzes as $quiz) {
            $quizType = strtolower((string) ($quiz->type ?? 'practice'));

            if ($quizType === 'assignment') {
                $linkedDl = $assignmentDeadlineMap[(int) $quiz->id] ?? $quizDeadlineMap[(int) $quiz->id] ?? null;
                if ($linkedDl) {
                    $handledDeadlineIds[(int) $linkedDl->id] = true;
                }

                $dueAt = $linkedDl && $linkedDl->due_at ? Carbon::parse($linkedDl->due_at) : null;
                $submission = $submissionsByQuiz->get($quiz->id);
                $isSubmitted = $submission !== null;
                $isOverdue = $dueAt ? ($dueAt->isPast() && !$isSubmitted) : false;

                $assignmentItem = [
                    'id'                => 'quiz_assign_' . $quiz->id,
                    'quiz_id'           => (int) $quiz->id,
                    'course_id'         => (int) $quiz->course_id,
                    'course_title'      => $quiz->course?->title ?? 'Course',
                    'course_title_kh'   => $quiz->course?->title_kh ?: ($quiz->course?->title ?? 'វគ្គសិក្សា'),
                    'title'             => $quiz->title,
                    'due_at'            => $dueAt ? $dueAt->toIso8601String() : null,
                    'date_key'          => $dueAt ? $dueAt->format('Y-m-d') : null,
                    'due_label'         => $dueAt ? $this->formatDeadlineDiff($dueAt, $now) : null,
                    'due_date_formatted'=> $dueAt ? $dueAt->format('M j, Y · h:i A') : null,
                    'is_overdue'        => $isOverdue,
                    'grace_days'        => $linkedDl?->grace_days,
                    'penalty_percent'   => $linkedDl?->penalty_percent,
                    'message_kh'        => $linkedDl?->message_kh,
                    'message_en'        => $linkedDl?->message_en,
                    'submission_status' => $submission ? ($submission->status ?: 'submitted') : ($isOverdue ? 'overdue' : 'pending'),
                    'submitted_score'   => $submission && $submission->score !== null ? (float) $submission->score : null,
                    'action_href'       => '/student/quizzes/assignments',
                ];

                $assignmentsDue[] = $assignmentItem;

                if ($dueAt) {
                    $calendarEvents[] = [
                        'id'              => 'assign_q_' . $quiz->id,
                        'date'            => $dueAt->format('Y-m-d'),
                        'type'            => 'assignment',
                        'title'           => $quiz->title,
                        'course_id'       => $quiz->course_id,
                        'course_title'    => $quiz->course?->title,
                        'course_title_kh' => $quiz->course?->title_kh ?: $quiz->course?->title,
                        'time'            => $dueAt->format('h:i A'),
                        'status'          => $isSubmitted ? 'completed' : 'upcoming',
                        'href'            => '/student/quizzes/assignments',
                    ];
                }

                continue;
            }

            // Standard Quiz (pre_test, practice, post_test, coding, etc.)
            $linkedDl = $quizDeadlineMap[(int) $quiz->id] ?? null;
            if ($linkedDl) {
                $handledDeadlineIds[(int) $linkedDl->id] = true;
            }

            $dueAt = $linkedDl && $linkedDl->due_at ? Carbon::parse($linkedDl->due_at) : null;
            $quizAttempts = $attemptsByQuiz->get($quiz->id, collect());
            $attemptsCount = $quizAttempts->count();
            $bestScore = $attemptsCount > 0 ? (float) $quizAttempts->max('score') : null;
            $hasPassed = $quizAttempts->contains(fn ($a) => (bool) $a->passed);

            $attemptStatus = $hasPassed
                ? 'passed'
                : ($attemptsCount > 0 ? 'attempted' : 'not_started');

            $isPostTest = in_array($quizType, ['post_test', 'post-test', 'final'], true);

            $upcomingQuizzes[] = [
                'id'                 => (int) $quiz->id,
                'title'              => $quiz->title,
                'type'               => $quiz->type ?: 'practice',
                'is_post_test'       => $isPostTest,
                'course_id'          => (int) $quiz->course_id,
                'course_title'       => $quiz->course?->title ?? 'Course',
                'course_title_kh'    => $quiz->course?->title_kh ?: ($quiz->course?->title ?? 'វគ្គសិក្សា'),
                'module_title'       => $quiz->module?->title,
                'questions_count'    => (int) ($quiz->questions_count ?? 0),
                'time_limit_minutes' => $quiz->time_limit_minutes,
                'passing_score'      => $quiz->passing_score !== null ? (float) $quiz->passing_score : null,
                'max_attempts'       => $quiz->max_attempts,
                'attempts_count'     => $attemptsCount,
                'best_score'         => $bestScore,
                'passed'             => $hasPassed,
                'attempt_status'     => $attemptStatus,
                'due_at'             => $dueAt ? $dueAt->toIso8601String() : null,
                'date_key'           => $dueAt ? $dueAt->format('Y-m-d') : null,
                'due_label'          => $dueAt ? $this->formatDeadlineDiff($dueAt, $now) : null,
                'due_date_formatted' => $dueAt ? $dueAt->format('M j, Y · h:i A') : null,
                'start_href'         => "/student/quiz/{$quiz->id}",
            ];

            if ($dueAt) {
                $calendarEvents[] = [
                    'id'              => 'quiz_' . $quiz->id,
                    'date'            => $dueAt->format('Y-m-d'),
                    'type'            => 'quiz',
                    'title'           => $quiz->title,
                    'course_id'       => $quiz->course_id,
                    'course_title'    => $quiz->course?->title,
                    'course_title_kh' => $quiz->course?->title_kh ?: $quiz->course?->title,
                    'time'            => $dueAt->format('h:i A'),
                    'status'          => $hasPassed ? 'completed' : 'upcoming',
                    'href'            => "/student/quiz/{$quiz->id}",
                ];
            }
        }

        // Remaining Deadlines not already attached to a published quiz above
        foreach ($deadlines as $dl) {
            if (isset($handledDeadlineIds[(int) $dl->id])) {
                continue;
            }

            $dueAt = $dl->due_at ? Carbon::parse($dl->due_at) : null;
            $dlType = strtolower((string) ($dl->linked_type ?: $dl->deadline_type ?: 'assignment'));

            if ($dlType === 'quiz') {
                $upcomingQuizzes[] = [
                    'id'                 => 'dl_' . $dl->id,
                    'title'              => $dl->title,
                    'type'               => 'quiz_deadline',
                    'is_post_test'       => false,
                    'course_id'          => (int) $dl->course_id,
                    'course_title'       => $dl->course?->title ?? 'Course',
                    'course_title_kh'    => $dl->course?->title_kh ?: ($dl->course?->title ?? 'វគ្គសិក្សា'),
                    'module_title'       => null,
                    'questions_count'    => 0,
                    'time_limit_minutes' => null,
                    'passing_score'      => null,
                    'max_attempts'       => null,
                    'attempts_count'     => 0,
                    'best_score'         => null,
                    'passed'             => false,
                    'attempt_status'     => 'not_started',
                    'due_at'             => $dueAt ? $dueAt->toIso8601String() : null,
                    'date_key'           => $dueAt ? $dueAt->format('Y-m-d') : null,
                    'due_label'          => $dueAt ? $this->formatDeadlineDiff($dueAt, $now) : null,
                    'due_date_formatted' => $dueAt ? $dueAt->format('M j, Y · h:i A') : null,
                    'start_href'         => '/student/quizzes/practice',
                ];

                if ($dueAt) {
                    $calendarEvents[] = [
                        'id'              => 'dl_quiz_' . $dl->id,
                        'date'            => $dueAt->format('Y-m-d'),
                        'type'            => 'quiz',
                        'title'           => $dl->title,
                        'course_id'       => $dl->course_id,
                        'course_title'    => $dl->course?->title,
                        'course_title_kh' => $dl->course?->title_kh ?: $dl->course?->title,
                        'time'            => $dueAt->format('h:i A'),
                        'status'          => $dueAt->isPast() ? 'completed' : 'upcoming',
                        'href'            => '/student/quizzes/practice',
                    ];
                }
            } else {
                $isOverdue = $dueAt ? $dueAt->isPast() : false;

                $assignmentsDue[] = [
                    'id'                => 'dl_' . $dl->id,
                    'quiz_id'           => $dl->linked_id ? (int) $dl->linked_id : null,
                    'course_id'         => (int) $dl->course_id,
                    'course_title'      => $dl->course?->title ?? 'Course',
                    'course_title_kh'   => $dl->course?->title_kh ?: ($dl->course?->title ?? 'វគ្គសិក្សា'),
                    'title'             => $dl->title,
                    'due_at'            => $dueAt ? $dueAt->toIso8601String() : null,
                    'date_key'          => $dueAt ? $dueAt->format('Y-m-d') : null,
                    'due_label'         => $dueAt ? $this->formatDeadlineDiff($dueAt, $now) : null,
                    'due_date_formatted'=> $dueAt ? $dueAt->format('M j, Y · h:i A') : null,
                    'is_overdue'        => $isOverdue,
                    'grace_days'        => $dl->grace_days,
                    'penalty_percent'   => $dl->penalty_percent,
                    'message_kh'        => $dl->message_kh,
                    'message_en'        => $dl->message_en,
                    'submission_status' => $isOverdue ? 'overdue' : 'pending',
                    'submitted_score'   => null,
                    'action_href'       => $dlType === 'payment' ? '/student/payments' : '/student/quizzes/assignments',
                ];

                if ($dueAt) {
                    $calendarEvents[] = [
                        'id'              => 'dl_assign_' . $dl->id,
                        'date'            => $dueAt->format('Y-m-d'),
                        'type'            => 'assignment',
                        'title'           => $dl->title,
                        'course_id'       => $dl->course_id,
                        'course_title'    => $dl->course?->title,
                        'course_title_kh' => $dl->course?->title_kh ?: $dl->course?->title,
                        'time'            => $dueAt->format('h:i A'),
                        'status'          => $isOverdue ? 'overdue' : 'upcoming',
                        'href'            => $dlType === 'payment' ? '/student/payments' : '/student/quizzes/assignments',
                    ];
                }
            }
        }

        // 5. Academic Events from real `academic_years` and `semesters` tables
        $academicEvents = $this->buildAcademicEvents($calendarEvents, $now);

        return [
            'enrolledCourses' => $enrolledCourses,
            'liveSchedules'   => $liveSchedules,
            'upcomingQuizzes' => $upcomingQuizzes,
            'assignmentsDue'  => $assignmentsDue,
            'academicEvents'  => $academicEvents,
            'calendarEvents'  => $calendarEvents,
        ];
    }

    /**
     * Build real academic milestone events from `AcademicYear` and `Semester` records.
     *
     * @param array<int, array<string, mixed>> &$calendarEvents
     * @param Carbon $now
     * @return array<int, array<string, mixed>>
     */
    protected function buildAcademicEvents(array &$calendarEvents, Carbon $now): array
    {
        $events = [];

        $academicYears = AcademicYear::orderByDesc('is_active')
            ->orderByDesc('start_date')
            ->get();

        foreach ($academicYears as $ay) {
            if (!$ay->start_date && !$ay->end_date) {
                continue;
            }
            $start = $ay->start_date ? Carbon::parse($ay->start_date) : null;
            $end = $ay->end_date ? Carbon::parse($ay->end_date) : null;
            $dateRange = $start && $end
                ? $start->format('M j, Y') . ' – ' . $end->format('M j, Y')
                : ($start ? $start->format('M j, Y') : ($end ? $end->format('M j, Y') : '—'));

            $events[] = [
                'id'             => 'ay_' . $ay->id,
                'title'          => 'Academic Year ' . $ay->name,
                'title_kh'       => 'ឆ្នាំសិក្សា ' . $ay->name,
                'type'           => 'Academic Year',
                'type_kh'        => 'ឆ្នាំសិក្សា',
                'category'       => 'term',
                'date'           => $dateRange,
                'date_key'       => $start ? $start->format('Y-m-d') : ($end ? $end->format('Y-m-d') : null),
                'location'       => 'Saint Paul Institute',
                'location_kh'    => 'វិទ្យាស្ថាន សន្តប៉ូល',
                'status'         => $ay->is_active ? 'Active' : ($ay->status ?: 'Scheduled'),
                'description'    => 'Official academic year calendar period (' . ($ay->code ?: $ay->name) . ').',
                'description_kh' => 'កាលបរិច្ឆេទឆ្នាំសិក្សាផ្លូវការ (' . ($ay->code ?: $ay->name) . ')។',
            ];
        }

        $semesters = Semester::with('academicYear')
            ->orderByDesc('is_active')
            ->orderBy('start_date')
            ->get();

        foreach ($semesters as $sem) {
            $yearLabel = $sem->academicYear?->name ?: ($sem->parent_year ?: '');
            $semTitle = trim($sem->name . ($yearLabel ? " ({$yearLabel})" : ''));

            if ($sem->start_date || $sem->end_date) {
                $sDate = $sem->start_date ? Carbon::parse($sem->start_date) : null;
                $eDate = $sem->end_date ? Carbon::parse($sem->end_date) : null;
                $range = $sDate && $eDate
                    ? $sDate->format('M j, Y') . ' – ' . $eDate->format('M j, Y')
                    : ($sDate ? $sDate->format('M j, Y') : ($eDate ? $eDate->format('M j, Y') : '—'));

                $events[] = [
                    'id'             => 'sem_term_' . $sem->id,
                    'title'          => $semTitle . ' — Term Period',
                    'title_kh'       => $semTitle . ' — រយៈពេលឆមាសសិក្សា',
                    'type'           => 'Semester',
                    'type_kh'        => 'ឆមាសសិក្សា',
                    'category'       => 'term',
                    'date'           => $range,
                    'date_key'       => $sDate ? $sDate->format('Y-m-d') : null,
                    'location'       => 'All Faculties',
                    'location_kh'    => 'គ្រប់មហាវិទ្យាល័យ',
                    'status'         => $sem->is_active ? 'Active' : ($sem->status ?: 'Scheduled'),
                    'description'    => 'Official semester instruction window for ' . $semTitle . '.',
                    'description_kh' => 'រយៈពេលសិក្សាផ្លូវការសម្រាប់ ' . $semTitle . '។',
                ];

                if ($sDate) {
                    $calendarEvents[] = [
                        'id'           => 'acad_sem_' . $sem->id,
                        'date'         => $sDate->format('Y-m-d'),
                        'type'         => 'academic',
                        'title'        => $semTitle . ' Starts',
                        'course_id'    => null,
                        'course_title' => $semTitle,
                        'time'         => 'All Day',
                        'status'       => $sDate->isPast() ? 'completed' : 'upcoming',
                        'href'         => '/student/calendar/academic-events',
                    ];
                }
            }

            if ($sem->enrollment_open || $sem->enrollment_close) {
                $openDate = $sem->enrollment_open ? Carbon::parse($sem->enrollment_open) : null;
                $closeDate = $sem->enrollment_close ? Carbon::parse($sem->enrollment_close) : null;
                $range = $openDate && $closeDate
                    ? $openDate->format('M j, Y') . ' – ' . $closeDate->format('M j, Y')
                    : ($openDate ? 'Opens ' . $openDate->format('M j, Y') : 'Closes ' . $closeDate->format('M j, Y'));

                $events[] = [
                    'id'             => 'sem_enroll_' . $sem->id,
                    'title'          => $semTitle . ' — Course Enrollment Window',
                    'title_kh'       => $semTitle . ' — រយៈពេលចុះឈ្មោះចូលរៀន',
                    'type'           => 'Enrollment',
                    'type_kh'        => 'ការចុះឈ្មោះ',
                    'category'       => 'enrollment',
                    'date'           => $range,
                    'date_key'       => ($closeDate ?? $openDate)?->format('Y-m-d'),
                    'location'       => 'Online Portal',
                    'location_kh'    => 'ប្រព័ន្ធអនឡាញ E-LMS',
                    'status'         => ($closeDate && $closeDate->isPast()) ? 'Closed' : 'Open',
                    'description'    => 'Course registration and enrollment period for ' . $semTitle . '.',
                    'description_kh' => 'កាលបរិច្ឆេទចុះឈ្មោះជ្រើសរើសមុខវិជ្ជាសម្រាប់ ' . $semTitle . '។',
                ];

                if ($closeDate) {
                    $calendarEvents[] = [
                        'id'           => 'acad_enroll_' . $sem->id,
                        'date'         => $closeDate->format('Y-m-d'),
                        'type'         => 'academic',
                        'title'        => 'Enrollment Closes (' . $sem->name . ')',
                        'course_id'    => null,
                        'course_title' => $semTitle,
                        'time'         => 'All Day',
                        'status'       => $closeDate->isPast() ? 'completed' : 'upcoming',
                        'href'         => '/student/calendar/academic-events',
                    ];
                }
            }

            if ($sem->midterm_exam) {
                $midDate = Carbon::parse($sem->midterm_exam);
                $events[] = [
                    'id'             => 'sem_mid_' . $sem->id,
                    'title'          => $semTitle . ' — Midterm Examination',
                    'title_kh'       => $semTitle . ' — ការប្រឡងពាក់កណ្តាលឆមាស (Midterm)',
                    'type'           => 'Exam',
                    'type_kh'        => 'ការប្រឡង',
                    'category'       => 'exam',
                    'date'           => $midDate->format('M j, Y'),
                    'date_key'       => $midDate->format('Y-m-d'),
                    'location'       => 'Faculty Departments',
                    'location_kh'    => 'តាមដេប៉ាតឺម៉ង់ជំនាញ',
                    'status'         => $midDate->isPast() ? 'Completed' : 'Upcoming',
                    'description'    => 'Official midterm assessment date for ' . $semTitle . '.',
                    'description_kh' => 'កាលបរិច្ឆេទប្រឡងពាក់កណ្តាលឆមាសសម្រាប់ ' . $semTitle . '។',
                ];

                $calendarEvents[] = [
                    'id'           => 'acad_mid_' . $sem->id,
                    'date'         => $midDate->format('Y-m-d'),
                    'type'         => 'academic',
                    'title'        => 'Midterm Exam (' . $sem->name . ')',
                    'course_id'    => null,
                    'course_title' => $semTitle,
                    'time'         => 'Exam Day',
                    'status'       => $midDate->isPast() ? 'completed' : 'upcoming',
                    'href'         => '/student/calendar/academic-events',
                ];
            }

            if ($sem->final_exam) {
                $finalDate = Carbon::parse($sem->final_exam);
                $events[] = [
                    'id'             => 'sem_final_' . $sem->id,
                    'title'          => $semTitle . ' — Final Examination',
                    'title_kh'       => $semTitle . ' — ការប្រឡងបញ្ចប់ឆមាស (Final)',
                    'type'           => 'Exam',
                    'type_kh'        => 'ការប្រឡងផ្តាច់ព្រ័ត្រ',
                    'category'       => 'exam',
                    'date'           => $finalDate->format('M j, Y'),
                    'date_key'       => $finalDate->format('Y-m-d'),
                    'location'       => 'Examination Halls',
                    'location_kh'    => 'មណ្ឌលប្រឡងវិទ្យាស្ថាន',
                    'status'         => $finalDate->isPast() ? 'Completed' : 'Upcoming',
                    'description'    => 'Official end-of-semester final examination date for ' . $semTitle . '.',
                    'description_kh' => 'កាលបរិច្ឆេទប្រឡងបញ្ចប់ឆមាសសម្រាប់ ' . $semTitle . '។',
                ];

                $calendarEvents[] = [
                    'id'           => 'acad_final_' . $sem->id,
                    'date'         => $finalDate->format('Y-m-d'),
                    'type'         => 'academic',
                    'title'        => 'Final Exam (' . $sem->name . ')',
                    'course_id'    => null,
                    'course_title' => $semTitle,
                    'time'         => 'Exam Day',
                    'status'       => $finalDate->isPast() ? 'completed' : 'upcoming',
                    'href'         => '/student/calendar/academic-events',
                ];
            }

            if ($sem->payment_due) {
                $payDate = Carbon::parse($sem->payment_due);
                $lateFeeText = $sem->late_fee && (float) $sem->late_fee > 0
                    ? ' (Late fee: $' . number_format((float) $sem->late_fee, 2) . ')'
                    : '';

                $events[] = [
                    'id'             => 'sem_pay_' . $sem->id,
                    'title'          => $semTitle . ' — Tuition Payment Deadline',
                    'title_kh'       => $semTitle . ' — ថ្ងៃផុតកំណត់បង់ថ្លៃសិក្សា',
                    'type'           => 'Payment Due',
                    'type_kh'        => 'កាលកំណត់បង់ប្រាក់',
                    'category'       => 'payment',
                    'date'           => $payDate->format('M j, Y'),
                    'date_key'       => $payDate->format('Y-m-d'),
                    'location'       => 'Finance / ABA KHQR',
                    'location_kh'    => 'ការិយាល័យហិរញ្ញវត្ថុ / ABA KHQR',
                    'status'         => $payDate->isPast() ? 'Past Due' : 'Upcoming',
                    'description'    => 'Semester tuition fee payment due date for ' . $semTitle . $lateFeeText . '.',
                    'description_kh' => 'ថ្ងៃផុតកំណត់បង់ថ្លៃសិក្សាសម្រាប់ ' . $semTitle . $lateFeeText . '។',
                ];

                $calendarEvents[] = [
                    'id'           => 'acad_pay_' . $sem->id,
                    'date'         => $payDate->format('Y-m-d'),
                    'type'         => 'academic',
                    'title'        => 'Payment Due (' . $sem->name . ')',
                    'course_id'    => null,
                    'course_title' => $semTitle,
                    'time'         => 'Due Date',
                    'status'       => $payDate->isPast() ? 'completed' : 'upcoming',
                    'href'         => '/student/payments',
                ];
            }
        }

        return $events;
    }

    /**
     * Human-friendly countdown / overdue text from a Carbon due date.
     */
    protected function formatDeadlineDiff(Carbon $dueAt, Carbon $now): string
    {
        if ($dueAt->greaterThanOrEqualTo($now)) {
            $days = (int) $now->diffInDays($dueAt);
            if ($days >= 1) {
                return 'Due in ' . $days . ($days === 1 ? ' day' : ' days');
            }
            $hours = (int) $now->diffInHours($dueAt);
            if ($hours >= 1) {
                return $hours . 'h left';
            }
            $mins = max(1, (int) $now->diffInMinutes($dueAt));
            return $mins . 'm left';
        }

        $lateDays = (int) $dueAt->diffInDays($now);
        if ($lateDays >= 1) {
            return $lateDays . ($lateDays === 1 ? ' day overdue' : ' days overdue');
        }
        $lateHours = max(1, (int) $dueAt->diffInHours($now));
        return $lateHours . 'h overdue';
    }
}
