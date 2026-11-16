<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonComment;
use App\Models\LessonCommentLike;
use App\Models\LessonProgress;
use App\Models\LessonResource;
use App\Models\Notification;
use App\Models\ReportedContent;
use App\Services\CertificateService;
use App\Services\ContinueLearningService;
use App\Services\CourseProgressService;
use App\Services\DashboardAnalyticsService;
use App\Services\EnrollmentService;
use App\Services\StudentLearningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'enrolled');

        $enrollments = Enrollment::where('student_id', $request->user()->id)
            ->with(['course.teacher:id,name,avatar', 'course.major:id,name'])
            ->latest('updated_at')
            ->get();

        return Inertia::render('Student/MyCourses/Index', [
            'activeTab' => $tab,
            'enrollments' => $enrollments,
        ]);
    }

    public function enrolled(Request $request, CourseProgressService $progressService)
    {
        $user = $request->user();

        // 1. Validation of query parameters
        $status = (string)$request->query('status', 'all');
        if (!in_array($status, ['all', 'in_progress', 'not_started', 'completed', 'coming_soon'])) {
            $status = 'all';
        }
        $category = (string)$request->query('category', 'all');
        $sort = (string)$request->query('sort', 'newest');
        if (!in_array($sort, ['newest', 'recent', 'progress', 'title'])) {
            $sort = 'newest';
        }
        $q = substr(trim((string)$request->query('q', '')), 0, 100);

        $savedView = is_array($user->preferences) ? ($user->preferences['courses_view'] ?? 'grid') : 'grid';
        $urlView = $request->query('view');
        $view = in_array($urlView, ['grid', 'list'], true)
            ? $urlView
            : (in_array($savedView, ['grid', 'list'], true) ? $savedView : 'grid');

        // 2 & 3. Fetch + compute enrolled courses with 60s user-scoped cache (0 N+1 queries)
        $cachedItems = Cache::remember("student_enrolled_{$user->id}_v2", 60, function () use ($user, $progressService) {
            $withRelations = [
                'course.teacher:id,name,name_kh,email,avatar,role',
                'course.major:id,name',
                'course.modules.lessons:id,module_id,course_id,title,order,duration_seconds',
                'course.lessons:id,course_id,title,order,duration_seconds',
            ];
            if (Schema::hasColumn('enrollments', 'enrolled_by')) {
                $withRelations[] = 'enrolledBy:id,name,role,avatar';
            }

            $enrollments = Enrollment::where('student_id', $user->id)
                ->whereIn('status', ['active', 'completed'])
                ->with($withRelations)
                ->latest('updated_at')
                ->get();

            $computedCollection = $progressService->calculateForEnrollments($user, $enrollments);

            $issuedCertificateCourseIds = [];
            try {
                $issuedCertificateCourseIds = \App\Models\Certificate::where('student_id', $user->id)
                    ->whereIn('status', ['issued', 'valid'])
                    ->pluck('course_id')
                    ->all();
            } catch (\Throwable $e) {
                $issuedCertificateCourseIds = [];
            }

            return $computedCollection->map(function ($item) use ($progressService, $issuedCertificateCourseIds) {
                $enr = $item['enrollment'];
                $c = $item['course'];

                $titleLower = strtolower($c->title . ' ' . ($c->major->name ?? ''));
                $themeColor = 'indigo';
                $gradient = 'from-indigo-600 to-blue-600';
                $illus = 'web';

                if (str_contains($titleLower, 'python') || str_contains($titleLower, 'ai') || str_contains($titleLower, 'machine')) {
                    $themeColor = 'blue';
                    $gradient = 'from-blue-600 to-cyan-600';
                    $illus = 'python';
                } elseif (str_contains($titleLower, 'database') || str_contains($titleLower, 'sql')) {
                    $themeColor = 'purple';
                    $gradient = 'from-purple-600 to-indigo-600';
                    $illus = 'database';
                } elseif (str_contains($titleLower, 'design') || str_contains($titleLower, 'ui')) {
                    $themeColor = 'emerald';
                    $gradient = 'from-emerald-600 to-teal-600';
                    $illus = 'uiux';
                }

                $teacherName = $progressService->cleanTeacherName($c->teacher, $c);
                $hasCert = in_array($c->id, $issuedCertificateCourseIds);

                $enrolledDateStr = '';
                try {
                    if ($enr->enrolled_at) {
                        $enrolledDateStr = \Carbon\Carbon::parse($enr->enrolled_at)->format('j M Y');
                    } elseif ($enr->created_at) {
                        $enrolledDateStr = \Carbon\Carbon::parse($enr->created_at)->format('j M Y');
                    }
                } catch (\Throwable $e) {
                    $enrolledDateStr = '';
                }

                $enrolledRel = 'Recently';
                try {
                    if ($enr->created_at) {
                        $enrolledRel = \Carbon\Carbon::parse($enr->created_at)->diffForHumans();
                    }
                } catch (\Throwable $e) {
                    $enrolledRel = 'Recently';
                }

                $hasEnrolledBy = $enr->relationLoaded('enrolledBy') && $enr->enrolledBy;

                return [
                    'id'                 => $c->id,
                    'enrollment_id'      => $enr->id,
                    'is_new'             => array_key_exists('seen_at', $enr->getAttributes()) ? ($enr->seen_at === null) : false,
                    'enrolled_by'        => $hasEnrolledBy ? [
                        'name'   => $enr->enrolledBy->name,
                        'role'   => $enr->enrolledBy->role,
                        'avatar' => $enr->enrolledBy->avatar,
                    ] : null,
                    'enrolled_relative'  => $enrolledRel,
                    'source'             => $enr->source ?: 'system',
                    'slug'               => $c->code ?: ('course-' . $c->id),
                    'title'              => $c->title,
                    'thumbnail'          => $c->thumbnail ?: null,
                    'category'           => $c->major?->name ?: 'Academic Studies',
                    'status'             => $item['state'],
                    'statusLabel'        => $item['state_label'],
                    'progress'           => $item['percent'],
                    'teacher'            => [
                        'name'   => $teacherName,
                        'avatar' => $c->teacher?->avatar ?: null,
                        'role'   => 'SPI Instructor',
                    ],
                    'currentChapter'     => $item['current_module_title'],
                    'currentModuleIndex' => $item['current_module_index'],
                    'completedChapters'  => $item['completed_modules'],
                    'totalChapters'      => $item['total_modules'],
                    'currentLesson'      => $item['current_lesson_title'],
                    'completedLessons'   => $item['lessons_done'],
                    'totalLessons'       => $item['lessons_total'],
                    'remainingTime'      => $item['percent'] >= 100 ? 'Completed' : 'Study in progress',
                    'lastAccessed'       => $item['last_activity_at'] ?: 'Not started yet',
                    'lastAccessedTimeMs' => $item['last_activity_ms'],
                    'enrolledDate'       => $enrolledDateStr,
                    'overviewHref'       => "/student/courses/{$c->id}/overview",
                    'learningHref'       => "/student/my-courses/{$c->id}/continue",
                    'has_certificate'    => $hasCert,
                    'certificateHref'    => $hasCert ? '/student/certificates/my-certificates' : null,
                    'themeColor'         => $themeColor,
                    'accentGradient'     => $gradient,
                    'illustrationType'   => $illus,
                ];
            })->values()->all();
        });

        $allCoursesList = collect($cachedItems);

        // 4. Calculate accurate counts from single unified collection (All = sum of all states)
        $inProgressCount = $allCoursesList->where('status', 'in_progress')->count();
        $notStartedCount = $allCoursesList->where('status', 'not_started')->count();
        $completedCount = $allCoursesList->where('status', 'completed')->count();
        $comingSoonCount = $allCoursesList->where('status', 'coming_soon')->count();

        $counts = [
            'all'         => $inProgressCount + $notStartedCount + $completedCount + $comingSoonCount,
            'in_progress' => $inProgressCount,
            'not_started' => $notStartedCount,
            'completed'   => $completedCount,
            'coming_soon' => $comingSoonCount,
        ];

        // 5. Distinct categories from user courses
        $distinctCategories = $allCoursesList->pluck('category')->unique()->filter()->values()->all();

        // 6. Filter by status, category, and search query
        $filtered = $allCoursesList;
        if ($status !== 'all') {
            $filtered = $filtered->where('status', $status);
        }
        if ($category !== 'all') {
            $filtered = $filtered->where('category', $category);
        }
        if (!empty($q)) {
            $qLower = strtolower($q);
            $filtered = $filtered->filter(function ($item) use ($qLower) {
                return str_contains(strtolower((string) ($item['title'] ?? '')), $qLower)
                    || str_contains(strtolower((string) ($item['teacher']['name'] ?? '')), $qLower)
                    || str_contains(strtolower((string) ($item['category'] ?? '')), $qLower)
                    || str_contains(strtolower((string) ($item['currentLesson'] ?? '')), $qLower);
            });
        }

        // 7. Sort
        if ($sort === 'recent') {
            $filtered = $filtered->sortByDesc('lastAccessedTimeMs');
        } elseif ($sort === 'progress') {
            $filtered = $filtered->sortByDesc('progress');
        } elseif ($sort === 'title') {
            $filtered = $filtered->sortBy('title', SORT_NATURAL | SORT_FLAG_CASE);
        } else { // 'newest'
            $filtered = $filtered->sortByDesc('enrollment_id');
        }

        $savedOfflineIds = is_array($user->preferences) && is_array($user->preferences['offline_course_ids'] ?? null)
            ? array_values(array_unique(array_map('intval', $user->preferences['offline_course_ids'])))
            : [];

        return Inertia::render('Student/MyCourses/EnrolledCourses', [
            'enrollments'           => $filtered->values()->all(),
            'counts'                => $counts,
            'categories'            => $distinctCategories,
            'savedOfflineCourseIds' => $savedOfflineIds,
            'filters'               => [
                'status'   => $status,
                'category' => $category,
                'sort'     => $sort,
                'q'        => $q,
                'view'     => $view,
            ],
        ]);
    }

    /**
     * Resolve current learning session and 302 redirect to the active lesson.
     */
    public function current(Request $request, ContinueLearningService $continueService)
    {
        $user = $request->user();
        $resolved = $continueService->resolve($user);

        if (!$resolved) {
            return redirect()->route('student.browse')
                ->with('info', 'សូមចុះឈ្មោះវគ្គសិក្សាដើម្បីចាប់ផ្តើមរៀន (Please enroll in a course to start learning).');
        }

        if (!empty($resolved['all_completed'])) {
            return redirect()->route('student.my-courses.completed')
                ->with('info', 'អ្នកបានបញ្ចប់វគ្គសិក្សាទាំងអស់រួចរាល់ហើយ! (You have completed all your courses!)');
        }

        if (!empty($resolved['course']) && !empty($resolved['lesson'])) {
            return redirect()->to("/student/my-courses/{$resolved['course']->id}/lessons/{$resolved['lesson']->id}");
        }

        if (!empty($resolved['course'])) {
            return redirect()->to("/student/courses/{$resolved['course']->id}/overview");
        }

        return redirect()->route('student.browse');
    }

    /**
     * Resolve and 302 redirect to the first incomplete lesson for a specific course.
     */
    public function continueCourse(Request $request, Course $course, ContinueLearningService $continueService)
    {
        $user = $request->user();

        // 1. Enrollment check
        $enrollment = Enrollment::where('student_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if (!$enrollment) {
            if ($course->status === 'published') {
                $enrollment = app(EnrollmentService::class)->enroll($user, $course, 'self', $user);
            } else {
                return redirect()->route('student.my-courses.enrolled')
                    ->with('error', 'វគ្គសិក្សាមិនទាន់ត្រូវបានដាក់ឱ្យដំណើរការនៅឡើយទេ (Course is not published).');
            }
        } elseif ($enrollment->status !== 'active') {
            if ($enrollment->status === 'completed') {
                return redirect()->to("/student/courses/{$course->id}/overview")
                    ->with('info', 'អ្នកបានបញ្ចប់វគ្គសិក្សានេះរួចរាល់ហើយ! (You have completed this course!)');
            }
            return redirect()->route('student.my-courses.enrolled')
                ->with('warning', 'ស្ថានភាពចុះឈ្មោះមិនទាន់សកម្មទេ (Enrollment is not active).');
        }

        // Mark enrollment as seen on first access
        try {
            if ($enrollment && Schema::hasColumn('enrollments', 'seen_at') && $enrollment->seen_at === null) {
                $enrollment->update(['seen_at' => now()]);
                Cache::forget("student.{$user->id}.new_enrollments");
            }
        } catch (\Throwable $e) {}

        $resolved = $continueService->resolve($user, $course);

        if (!$resolved || !empty($resolved['all_completed'])) {
            return redirect()->to("/student/courses/{$course->id}/overview")
                ->with('info', 'អ្នកបានបញ្ចប់វគ្គសិក្សានេះរួចរាល់ហើយ! (You have completed this course!)');
        }

        if (!empty($resolved['lesson'])) {
            return redirect()->to("/student/my-courses/{$course->id}/lessons/{$resolved['lesson']->id}");
        }

        return redirect()->to("/student/courses/{$course->id}/overview");
    }

    /**
     * Show Lesson Player connected to real course and database.
     */
    public function showLesson(Request $request, Course $course, Lesson $lesson, StudentLearningService $learningService, ContinueLearningService $continueService)
    {
        $user = $request->user();

        // 1. Ownership & Spoofing Protection: Verify lesson belongs to this course
        if ((int)$lesson->course_id !== (int)$course->id) {
            abort(404, 'Lesson does not belong to this course.');
        }

        // 2. Authorization: Ensure enrollment exists (auto-enroll if student is viewing a published course)
        $enrollment = Enrollment::where('student_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if (!$enrollment) {
            if ($course->status === 'published') {
                $enrollment = app(EnrollmentService::class)->enroll($user, $course, 'self', $user);
            } else {
                abort(403, 'Unauthorized access to unpublished course.');
            }
        } else {
            if ($enrollment->status !== 'active' && $enrollment->status !== 'completed') {
                abort(403, 'Your enrollment in this course is not active.');
            }

            // Mark seen_at idempotently
            if (isset($enrollment->seen_at) && $enrollment->seen_at === null) {
                try {
                    $enrollment->update(['seen_at' => now()]);
                } catch (\Throwable $e) {}
                Cache::forget("student.{$user->id}.new_enrollments");
            }
        }

        // 3. Fetch full curriculum with sequential lock states
        $curriculum = $learningService->getCourseCurriculum($user, $course);
        $allLessons = collect($curriculum['lessons']);

        $lessonItem = $allLessons->firstWhere('id', $lesson->id);
        if (!$lessonItem) {
            abort(404, 'Lesson not found in curriculum.');
        }

        // 4. Sequential Lock Enforcement:
        // A lesson is unlocked if it is completed OR if it is the first incomplete lesson.
        // If student navigates directly to a locked lesson, redirect to firstIncomplete.
        if (!empty($lessonItem['is_locked'])) {
            $firstIncomplete = $continueService->firstIncomplete($user, $course);
            $targetLessonId = $firstIncomplete ? $firstIncomplete->id : ($allLessons->firstWhere('is_locked', false)['id'] ?? $allLessons->first()['id']);

            return redirect()->to("/student/my-courses/{$course->id}/lessons/{$targetLessonId}")
                ->with('warning', 'សូមបញ្ចប់មេរៀនមុនសិន (Complete the previous lesson first).');
        }

        // 4. Calculate student first name
        $nameParts = explode(' ', trim($user->name ?: 'Student'));
        $studentFirstName = $nameParts[0];

        // 5. Breadcrumbs
        $breadcrumbs = [
            ['label' => 'Dashboard', 'href' => '/student/dashboard'],
            ['label' => 'My Courses', 'href' => '/student/my-courses/enrolled'],
            ['label' => $course->title, 'href' => "/student/courses/{$course->id}/overview"],
            ['label' => $lessonItem['title']]
        ];

        // 6. Resources & Comments for this lesson
        $resources = LessonResource::where('lesson_id', $lesson->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $commentsQuery = LessonComment::where('lesson_id', $lesson->id)
            ->whereNull('parent_id')
            ->with([
                'user:id,name,avatar,role',
                'replies' => function ($q) use ($user) {
                    $q->with(['user:id,name,avatar,role'])
                      ->withCount('likes')
                      ->withExists(['likes as liked_by_me' => fn($lq) => $lq->where('user_id', $user->id)])
                      ->oldest('created_at');
                }
            ])
            ->withCount('likes')
            ->withExists(['likes as liked_by_me' => fn($lq) => $lq->where('user_id', $user->id)])
            ->orderByDesc('is_pinned')
            ->latest('created_at');

        $initialComments = $commentsQuery->take(10)->get();

        // Calculate role labels and format
        $comments = $initialComments->map(function ($comm) use ($course) {
            $comm->role_label = ($comm->user_id === $course->teacher_id) ? 'instructor' : ($comm->user?->role ?? 'student');
            if ($comm->replies) {
                foreach ($comm->replies as $rep) {
                    $rep->role_label = ($rep->user_id === $course->teacher_id) ? 'instructor' : ($rep->user?->role ?? 'student');
                }
            }
            return $comm;
        });

        $discussionCounts = [
            'all'        => LessonComment::where('lesson_id', $lesson->id)->whereNull('parent_id')->count(),
            'unanswered' => LessonComment::where('lesson_id', $lesson->id)->whereNull('parent_id')->where('is_answered', false)->count(),
            'answered'   => LessonComment::where('lesson_id', $lesson->id)->whereNull('parent_id')->where('is_answered', true)->count(),
            'mine'       => LessonComment::where('lesson_id', $lesson->id)->whereNull('parent_id')->where('user_id', $user->id)->count(),
        ];

        // 7. Smart Completion Action calculation
        $currentIndex = $allLessons->search(fn($l) => $l['id'] === $lesson->id);
        $nextLesson = ($currentIndex !== false && $currentIndex < $allLessons->count() - 1)
            ? $allLessons[$currentIndex + 1]
            : null;

        $isCurrentCompleted = (bool) $lessonItem['is_completed'];
        $watchedSeconds = (int) ($lessonItem['watched_seconds'] ?? 0);
        $durationSeconds = (int) ($lessonItem['duration_seconds'] ?? 600);
        $hasVideo = !empty($lessonItem['video_url']);

        $canComplete = true;
        $cannotCompleteReason = null;

        if ($hasVideo && !$isCurrentCompleted) {
            $requiredSeconds = (int) round($durationSeconds * 0.9);
            if ($watchedSeconds < $requiredSeconds) {
                $canComplete = false;
                $cannotCompleteReason = 'សូមទស្សនាវីដេអូយ៉ាងតិច 90% សិនមុននឹងចុចបញ្ចប់ (Please watch at least 90% of the video).';
            }
        }

        if ($isCurrentCompleted) {
            if ($nextLesson) {
                $completionAction = [
                    'type'         => 'next',
                    'label'        => 'Next Lesson →',
                    'can_complete' => true,
                    'next_url'     => "/student/my-courses/{$course->id}/lessons/{$nextLesson['id']}",
                ];
            } else {
                $completionAction = [
                    'type'         => 'finish',
                    'label'        => 'Finish Course 🎓',
                    'can_complete' => true,
                    'next_url'     => "/student/courses/{$course->id}/overview",
                ];
            }
        } else {
            if ($nextLesson) {
                $completionAction = [
                    'type'         => 'complete_and_next',
                    'label'        => 'Completed & Next Lesson →',
                    'can_complete' => $canComplete,
                    'reason'       => $cannotCompleteReason,
                    'next_url'     => "/student/my-courses/{$course->id}/lessons/{$nextLesson['id']}",
                ];
            } else {
                $completionAction = [
                    'type'         => 'complete_and_finish',
                    'label'        => 'Complete & Finish Course 🎓',
                    'can_complete' => $canComplete,
                    'reason'       => $cannotCompleteReason,
                    'next_url'     => "/student/courses/{$course->id}/overview",
                ];
            }
        }

        return Inertia::render('Student/MyCourses/CurrentCourse', [
            'course'                => $curriculum['course'],
            'currentLesson'         => $lessonItem,
            'chapters'              => $curriculum['chapters'],
            'breadcrumbs'           => $breadcrumbs,
            'studentFirstName'      => $studentFirstName,
            'teacherName'           => $curriculum['teacher_name'],
            'overallProgress'       => $curriculum['overall_progress_percent'],
            'completedLessonsCount' => $curriculum['completed_lessons_count'],
            'totalLessonsCount'     => $curriculum['total_lessons_count'],
            'resources'             => $resources,
            'comments'              => $comments,
            'discussionCounts'      => $discussionCounts,
            'courseTeacherId'       => $course->teacher_id,
            'completionAction'      => $completionAction,
            'currentUserId'         => $user->id,
        ]);
    }

    /**
     * Save/upsert video watching progress monotonically.
     */
    public function saveProgress(Request $request, Lesson $lesson, StudentLearningService $learningService)
    {
        $user = $request->user();
        $course = $lesson->course ?: Course::find($lesson->course_id);

        if (!$course) {
            return response()->json(['error' => 'Course not found'], 404);
        }

        // Authorization check
        $isEnrolled = Enrollment::where('student_id', $user->id)
            ->where('course_id', $course->id)
            ->exists();

        if (!$isEnrolled) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data = $request->validate([
            'seconds'  => 'required|integer|min:0',
            'duration' => 'nullable|integer|min:0',
        ]);

        $duration = (int) ($data['duration'] ?: ($lesson->duration_seconds ?: 600));
        $incomingSeconds = (int) $data['seconds'];

        // Server clamp position to [0, duration_seconds] instead of throwing 422
        if ($duration > 0) {
            $incomingSeconds = max(0, min($incomingSeconds, $duration));
        } else {
            $incomingSeconds = max(0, $incomingSeconds);
        }

        $prog = LessonProgress::firstOrNew([
            'user_id'   => $user->id,
            'lesson_id' => $lesson->id,
        ]);

        $existingSeconds = (int) ($prog->seconds_watched ?? 0);
        // Clamp legacy over-duration seconds if present
        if ($duration > 0 && $existingSeconds > $duration) {
            $existingSeconds = $duration;
        }

        // Monotonic: only accept greater values, no backward progress
        $newSeconds = max($existingSeconds, $incomingSeconds);

        $percent = $duration > 0 ? min(100, (int) round(($newSeconds / $duration) * 100)) : 100;
        $newPercent = max((int) ($prog->percent ?? 0), $percent);

        $prog->seconds_watched = $newSeconds;
        $prog->percent = $newPercent;
        $prog->client_updated_at = now();
        $prog->save();

        $deltaSeconds = max(0, min(40, $newSeconds - $existingSeconds));
        if ($deltaSeconds > 0) {
            app(DashboardAnalyticsService::class)->recordDailyDelta($user->id, 'lesson', $deltaSeconds);
        }

        $learningService->invalidateCourseCache($user->id, $course->id);
        app(DashboardAnalyticsService::class)->invalidateUserCache($user->id);

        return response()->json([
            'ok'              => true,
            'lesson_id'       => $lesson->id,
            'seconds_watched' => $newSeconds,
            'percent'         => $newPercent,
            'is_completed'    => !empty($prog->completed_at),
        ]);
    }

    /**
     * Complete a lesson and unlock the next lesson.
     */
    public function completeLesson(Request $request, Lesson $lesson, StudentLearningService $learningService, CertificateService $certificateService)
    {
        $user = $request->user();
        $course = $lesson->course ?: Course::find($lesson->course_id);

        if (!$course) {
            return response()->json(['error' => 'Course not found'], 404);
        }

        $enrollment = Enrollment::where('student_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if (!$enrollment) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $duration = (int) ($lesson->duration_seconds ?: 1100);
        $prog = LessonProgress::firstOrNew([
            'user_id'   => $user->id,
            'lesson_id' => $lesson->id,
        ]);

        $watchedSeconds = (int) ($prog->seconds_watched ?? 0);
        if ($request->has('seconds')) {
            $incoming = (int) $request->input('seconds');
            $watchedSeconds = max($watchedSeconds, $incoming);
        }

        $threshold = (int) round(0.9 * $duration);

        // Verification: Must have watched at least 90% if video has duration
        if ($duration > 0 && $watchedSeconds < $threshold) {
            return response()->json([
                'error' => "សូមទស្សនាមេរៀនយ៉ាងហោចណាស់ 90% (យ៉ាងតិច {$threshold} វិនាទី) មុននឹងបញ្ចប់មេរៀន។ (Please watch at least 90% of the lesson before completing)",
                'watched_seconds' => $watchedSeconds,
                'required_seconds' => $threshold,
            ], 422);
        }

        // Idempotent completion
        if (!$prog->completed_at) {
            $prog->completed_at = now();
        }
        $prog->percent = 100;
        $prog->seconds_watched = max($watchedSeconds, $duration);
        $prog->client_updated_at = now();
        $prog->save();

        $learningService->invalidateCourseCache($user->id, $course->id);

        // Compute updated progress
        $curriculum = $learningService->getCourseCurriculum($user, $course);
        $allLessons = collect($curriculum['lessons']);
        $totalCount = $curriculum['total_lessons_count'];
        $completedCount = $curriculum['completed_lessons_count'];
        $courseProgressPercent = $curriculum['overall_progress_percent'];

        $isCourseCompleted = ($completedCount >= $totalCount && $totalCount > 0);
        if ($isCourseCompleted) {
            $enrollment->update(['status' => 'completed']);
            try {
                $certificateService->issue($user, $course);
            } catch (\Throwable $e) {
                // Ignore certificate error if template not configured
            }
        }

        // Find next lesson URL
        $currentIndex = $allLessons->search(fn($item) => (int)$item['id'] === (int)$lesson->id);
        $nextLessonItem = ($currentIndex !== false && $currentIndex + 1 < $allLessons->count())
            ? $allLessons[$currentIndex + 1]
            : null;

        $finishUrl = "/student/courses/{$course->id}/overview";

        return response()->json([
            'ok'                      => true,
            'lesson_id'               => $lesson->id,
            'is_completed'            => true,
            'course_progress_percent' => $courseProgressPercent,
            'completed_lessons_count' => $completedCount,
            'total_lessons_count'     => $totalCount,
            'is_course_completed'     => $isCourseCompleted,
            'next_lesson_url'         => $nextLessonItem ? "/student/my-courses/{$course->id}/lessons/{$nextLessonItem['id']}" : $finishUrl,
            'next_lesson_id'          => $nextLessonItem['id'] ?? null,
            'message'                 => $isCourseCompleted ? 'អបអរសាទរ! អ្នកបានបញ្ចប់វគ្គសិក្សាទាំងមូលដោយជោគជ័យ! (Congratulations! You have completed the entire course!)' : 'មេរៀនត្រូវបានបញ្ចប់ដោយជោគជ័យ! (Lesson completed successfully)',
        ]);
    }

    public function completed(Request $request)
    {
        $user = $request->user();
        $enrollments = Enrollment::where('student_id', $user->id)
            ->where('status', 'completed')
            ->with([
                'course.teacher:id,name,avatar',
                'course.major:id,name',
                'course.modules.lessons',
                'course.certificates' => fn($q) => $q->where('student_id', $user->id)
            ])
            ->latest('updated_at')
            ->get();

        $completedList = $enrollments->map(function ($enr) use ($user) {
            $c = $enr->course;
            if (!$c) return null;

            $cert = $c->certificates ? $c->certificates->first() : null;
            if (!$cert) {
                $cert = Certificate::where('student_id', $user->id)->where('course_id', $c->id)->first();
            }

            $lessonsCount = $c->lessons ? $c->lessons->count() : 3;
            $chaptersCount = $c->modules ? $c->modules->count() : 1;

            return [
                'id'                     => $c->id,
                'slug'                   => $c->code ?: ('course-' . $c->id),
                'title'                  => $c->title,
                'category'               => $c->major?->name ?: 'Academic Studies',
                'status'                 => 'completed',
                'progress'               => 100,
                'teacher'                => [
                    'name'   => $c->teacher?->name ?: 'Dr. Mengly Chea',
                    'avatar' => $c->teacher?->avatar ?: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=120&auto=format&fit=crop&q=80',
                    'role'   => 'SPI Instructor',
                ],
                'completedDate'          => $enr->updated_at ? $enr->updated_at->format('M d, Y') : 'Nov 2026',
                'completedDateTimestamp' => $enr->updated_at ? $enr->updated_at->getTimestamp() * 1000 : 0,
                'chaptersCount'          => $chaptersCount,
                'lessonsCount'           => $lessonsCount,
                'studyTime'              => '18h 20m',
                'studyTimeMinutes'       => 1100,
                'quizScore'              => 90,
                'hasCertificate'         => $cert !== null,
                'certificateId'          => $cert?->certificate_number ?: ('SPI-CERT-' . $c->id),
                'certIssueDate'          => $cert?->issued_at ? $cert->issued_at->format('M d, Y') : null,
                'medalType'              => 'gold',
                'illustrationType'       => 'web',
                'overviewHref'           => "/student/courses/{$c->id}/overview",
            ];
        })->filter()->values();

        return Inertia::render('Student/MyCourses/CompletedCourses', [
            'enrollments' => $completedList,
        ]);
    }

    public function overview(Request $request, $course = null)
    {
        $user = $request->user();
        if ($user && $course) {
            try {
                $courseId = is_object($course) ? $course->id : (int) $course;
                if (Schema::hasColumn('enrollments', 'seen_at')) {
                    $enr = Enrollment::where('student_id', $user->id)
                        ->where('course_id', $courseId)
                        ->whereNull('seen_at')
                        ->first();
                    if ($enr) {
                        $enr->update(['seen_at' => now()]);
                        Cache::forget("student.{$user->id}.new_enrollments");
                    }
                }
            } catch (\Throwable $e) {}
        }

        return Inertia::render('Student/Courses/Overview', [
            'courseId' => $course,
        ]);
    }

    public function wishlist(Request $request)
    {
        return Inertia::render('Student/MyCourses/Wishlist');
    }

    /**
     * Download an attached lesson resource (guarded by enrollment check).
     */
    public function downloadResource(Request $request, Lesson $lesson, LessonResource $resource)
    {
        $user = $request->user();
        $course = $lesson->course ?: Course::find($lesson->course_id);

        if (!$course) {
            abort(404, 'Course not found');
        }

        $isEnrolled = Enrollment::where('student_id', $user->id)
            ->where('course_id', $course->id)
            ->exists();

        if (!$isEnrolled && $user->role !== 'admin' && $user->role !== 'teacher') {
            abort(403, 'Unauthorized access to course resources');
        }

        if ($resource->lesson_id !== $lesson->id) {
            abort(404, 'Resource does not belong to this lesson');
        }

        $path = $resource->disk_path;
        $name = $resource->original_name ?: $resource->title;

        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->download($path, $name);
        }

        if ($path && file_exists(storage_path('app/' . $path))) {
            return response()->download(storage_path('app/' . $path), $name);
        }

        if ($path && file_exists(public_path($path))) {
            return response()->download(public_path($path), $name);
        }

        // On-the-fly generated notes file if storage file not uploaded yet
        $cleanTitle = preg_replace('/[^A-Za-z0-9_\-]/', '_', $resource->title);
        $filename = ($cleanTitle ?: 'lesson_resource') . '.txt';
        $content = "Course: {$course->title}\nLesson: {$lesson->title}\nResource: {$resource->title}\n\n"
                 . ($lesson->summary ?: "Study materials for {$lesson->title}.\n\nSPI E-LMS: https://spilms.tech");

        return response($content)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Get paginated comments with sorting and filtering.
     */
    public function getComments(Request $request, Lesson $lesson)
    {
        $user = $request->user() ?: Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        $course = $lesson->course ?: Course::find($lesson->course_id);

        if (!$course) {
            return response()->json(['error' => 'Course not found'], 404);
        }

        $isEnrolled = Enrollment::where('student_id', $user->id)->where('course_id', $course->id)->exists();
        if (!$isEnrolled && $user->role !== 'admin' && $user->id !== $course->teacher_id) {
            return response()->json(['error' => 'Unauthorized. Must be enrolled in this course.'], 403);
        }

        $sort = $request->query('sort', 'newest');
        $filter = $request->query('filter', 'all');

        $query = LessonComment::where('lesson_id', $lesson->id)
            ->whereNull('parent_id')
            ->with([
                'user:id,name,avatar,role',
                'replies' => function ($q) use ($user) {
                    $q->with(['user:id,name,avatar,role'])
                      ->withCount('likes')
                      ->withExists(['likes as liked_by_me' => fn($lq) => $lq->where('user_id', $user->id)])
                      ->oldest('created_at');
                }
            ])
            ->withCount('likes')
            ->withExists(['likes as liked_by_me' => fn($lq) => $lq->where('user_id', $user->id)]);

        // Filter
        if ($filter === 'unanswered') {
            $query->where('is_answered', false);
        } elseif ($filter === 'answered') {
            $query->where('is_answered', true);
        } elseif ($filter === 'mine') {
            $query->where('user_id', $user->id);
        }

        // Sort
        if ($sort === 'oldest') {
            $query->oldest('created_at');
        } elseif ($sort === 'helpful') {
            $query->orderByDesc('likes_count')->latest('created_at');
        } else {
            $query->orderByDesc('is_pinned')->latest('created_at');
        }

        $paginated = $query->paginate(10);

        // Map role labels
        $paginated->getCollection()->transform(function ($comm) use ($course) {
            $comm->role_label = ($comm->user_id === $course->teacher_id) ? 'instructor' : ($comm->user?->role ?? 'student');
            if ($comm->replies) {
                foreach ($comm->replies as $rep) {
                    $rep->role_label = ($rep->user_id === $course->teacher_id) ? 'instructor' : ($rep->user?->role ?? 'student');
                }
            }
            return $comm;
        });

        $counts = [
            'all'        => LessonComment::where('lesson_id', $lesson->id)->whereNull('parent_id')->count(),
            'unanswered' => LessonComment::where('lesson_id', $lesson->id)->whereNull('parent_id')->where('is_answered', false)->count(),
            'answered'   => LessonComment::where('lesson_id', $lesson->id)->whereNull('parent_id')->where('is_answered', true)->count(),
            'mine'       => LessonComment::where('lesson_id', $lesson->id)->whereNull('parent_id')->where('user_id', $user->id)->count(),
        ];

        return response()->json([
            'ok'                    => true,
            'data'                  => $paginated->items(),
            'current_page'          => $paginated->currentPage(),
            'last_page'             => $paginated->lastPage(),
            'total'                 => $paginated->total(),
            'has_more'              => $paginated->hasMorePages(),
            'counts'                => $counts,
            'total_questions_count' => $counts['all'],
            'course_teacher_id'     => $course->teacher_id,
        ]);
    }

    /**
     * Store a comment/discussion or reply on a lesson.
     */
    public function storeComment(Request $request, Lesson $lesson)
    {
        $user = $request->user() ?: Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        $course = $lesson->course ?: Course::find($lesson->course_id);

        if (!$course) {
            return response()->json(['error' => 'Course not found'], 404);
        }

        $isEnrolled = Enrollment::where('student_id', $user->id)
            ->where('course_id', $course->id)
            ->exists();

        if (!$isEnrolled && $user->role !== 'admin' && $user->id !== $course->teacher_id) {
            return response()->json(['error' => 'Only enrolled students can comment'], 403);
        }

        $validated = $request->validate([
            'body'          => 'required|string|min:2|max:1000',
            'parent_id'     => 'nullable|integer',
            'video_seconds' => 'nullable|integer|min:0',
        ]);

        $cleanBody = trim(strip_tags($validated['body']));
        if (empty($cleanBody)) {
            return response()->json(['error' => 'Comment body cannot be empty'], 422);
        }

        $parentId = $validated['parent_id'] ?? null;
        if ($parentId) {
            $parentComment = LessonComment::find($parentId);
            if (!$parentComment || $parentComment->lesson_id !== $lesson->id) {
                return response()->json(['error' => 'Parent comment does not belong to this lesson'], 422);
            }
            if ($parentComment->parent_id !== null) {
                return response()->json(['error' => 'Nested replies are not allowed (maximum depth is 1)'], 422);
            }
        }

        // Timestamp parsing: look for 07:40, 1:02:15, or 7:40 in body
        $videoSeconds = isset($validated['video_seconds']) && $validated['video_seconds'] !== null
            ? (int) $validated['video_seconds']
            : null;

        if ($videoSeconds === null) {
            if (preg_match('/\b(?:(\d{1,2}):)?([0-5]?\d):([0-5]\d)\b/', $cleanBody, $matches)) {
                if (!empty($matches[1])) {
                    $videoSeconds = ((int)$matches[1] * 3600) + ((int)$matches[2] * 60) + (int)$matches[3];
                } else {
                    $videoSeconds = ((int)$matches[2] * 60) + (int)$matches[3];
                }
            }
        }

        // Clamp video_seconds to [0, duration]
        if ($videoSeconds !== null) {
            $duration = (int) ($lesson->duration_seconds ?: 3600);
            $videoSeconds = max(0, min($videoSeconds, $duration));
        }

        $comment = LessonComment::create([
            'lesson_id'     => $lesson->id,
            'user_id'       => $user->id,
            'parent_id'     => $parentId,
            'body'          => $cleanBody,
            'video_seconds' => $videoSeconds,
        ]);

        $comment->load('user:id,name,avatar,role');
        $comment->likes_count = 0;
        $comment->liked_by_me = false;
        $comment->role_label = ($comment->user_id === $course->teacher_id) ? 'instructor' : ($comment->user?->role ?? 'student');

        // Step 4: Dispatch notification to teacher or parent author
        try {
            $commentUrl = "/student/my-courses/{$course->id}/lessons/{$lesson->id}?tab=discussion#comment-{$comment->id}";
            $shortBody = Str::limit($cleanBody, 70);

            if ($parentId === null) {
                // Student asked question -> notify course teacher
                $teacherId = $course->teacher_id;
                if ($teacherId && $teacherId !== $user->id) {
                    Notification::create([
                        'title'   => "សំណួរថ្មីពីសិស្ស ({$course->title})",
                        'message' => "សិស្ស {$user->name} បានសួរក្នុងមេរៀន '{$lesson->title}': \"{$shortBody}\" [url:{$commentUrl}]",
                        'target'  => "user:{$teacherId}",
                    ]);
                    Cache::forget("student_notif_payload_{$teacherId}");
                }
            } else {
                // Reply posted -> notify question author
                $parent = LessonComment::find($parentId);
                if ($parent && $parent->user_id !== $user->id) {
                    Notification::create([
                        'title'   => "ចម្លើយតបថ្មី ({$lesson->title})",
                        'message' => "{$user->name} បានឆ្លើយតបសំណួររបស់អ្នក: \"{$shortBody}\" [url:{$commentUrl}]",
                        'target'  => "user:{$parent->user_id}",
                    ]);
                    Cache::forget("student_notif_payload_{$parent->user_id}");
                }
            }
        } catch (\Throwable $e) {}

        return response()->json([
            'ok'      => true,
            'comment' => $comment,
            'message' => 'Comment posted successfully',
        ]);
    }

    /**
     * Update comment within 15 minutes of posting (Owner only).
     */
    public function updateComment(Request $request, LessonComment $comment)
    {
        $user = $request->user() ?: Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if ($comment->user_id !== $user->id) {
            return response()->json(['error' => 'You can only edit your own comment'], 403);
        }

        // Allowed only within 15 minutes
        if ($comment->created_at && $comment->created_at->diffInMinutes(now()) > 15) {
            return response()->json(['error' => 'Comments can only be edited within 15 minutes of posting'], 403);
        }

        $validated = $request->validate([
            'body' => 'required|string|min:2|max:1000',
        ]);

        $cleanBody = trim(strip_tags($validated['body']));
        if (empty($cleanBody)) {
            return response()->json(['error' => 'Comment body cannot be empty'], 422);
        }

        $lesson = $comment->lesson;
        $videoSeconds = $comment->video_seconds;
        if (preg_match('/\b(?:(\d{1,2}):)?([0-5]?\d):([0-5]\d)\b/', $cleanBody, $matches)) {
            if (!empty($matches[1])) {
                $videoSeconds = ((int)$matches[1] * 3600) + ((int)$matches[2] * 60) + (int)$matches[3];
            } else {
                $videoSeconds = ((int)$matches[2] * 60) + (int)$matches[3];
            }
            if ($lesson) {
                $videoSeconds = max(0, min($videoSeconds, (int)($lesson->duration_seconds ?: 3600)));
            }
        }

        $comment->update([
            'body'          => $cleanBody,
            'video_seconds' => $videoSeconds,
            'edited_at'     => now(),
        ]);

        $comment->load('user:id,name,avatar,role');

        return response()->json([
            'ok'      => true,
            'comment' => $comment,
            'message' => 'Comment updated successfully',
        ]);
    }

    /**
     * Delete comment (Owner, Course Instructor, or Admin).
     */
    public function destroyComment(Request $request, LessonComment $comment)
    {
        $user = $request->user() ?: Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        $course = $comment->lesson?->course ?: ($comment->lesson?->course_id ? Course::find($comment->lesson->course_id) : null);

        $isOwner = ($comment->user_id === $user->id);
        $isAdmin = ($user->role === 'admin');
        $isTeacher = ($course && $user->id === $course->teacher_id);

        if (!$isOwner && !$isAdmin && !$isTeacher) {
            return response()->json(['error' => 'Unauthorized to delete this comment'], 403);
        }

        $comment->delete();

        return response()->json([
            'ok'      => true,
            'message' => 'Comment deleted successfully',
        ]);
    }

    /**
     * Toggle like on a comment (Idempotent).
     */
    public function toggleLikeComment(Request $request, LessonComment $comment)
    {
        $user = $request->user() ?: Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $existing = LessonCommentLike::where('comment_id', $comment->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $liked = false;
        } else {
            LessonCommentLike::create([
                'comment_id' => $comment->id,
                'user_id'    => $user->id,
            ]);
            $liked = true;
        }

        $likesCount = LessonCommentLike::where('comment_id', $comment->id)->count();

        return response()->json([
            'ok'          => true,
            'liked'       => $liked,
            'likes_count' => $likesCount,
        ]);
    }

    /**
     * Mark/unmark a reply as accepted answer (Question owner, Course Instructor, or Admin).
     */
    public function toggleAnswerComment(Request $request, LessonComment $comment)
    {
        $user = $request->user() ?: Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }
        $parent = $comment->parent_id ? $comment->parent : $comment;
        $course = $parent->lesson?->course ?: ($parent->lesson?->course_id ? Course::find($parent->lesson->course_id) : null);

        $isQuestionOwner = ($parent->user_id === $user->id);
        $isAdmin = ($user->role === 'admin');
        $isTeacher = ($course && $user->id === $course->teacher_id);

        if (!$isQuestionOwner && !$isAdmin && !$isTeacher) {
            return response()->json(['error' => 'Only the question author or course instructor can mark this answer'], 403);
        }

        if ($parent->is_answered && $parent->answered_comment_id === $comment->id) {
            $parent->update([
                'is_answered'         => false,
                'answered_comment_id' => null,
            ]);
            $isAnswered = false;
        } else {
            $parent->update([
                'is_answered'         => true,
                'answered_comment_id' => $comment->id,
            ]);
            $isAnswered = true;
        }

        return response()->json([
            'ok'                  => true,
            'is_answered'         => $isAnswered,
            'answered_comment_id' => $parent->answered_comment_id,
        ]);
    }

    /**
     * Report a comment to administrators.
     */
    public function reportComment(Request $request, LessonComment $comment)
    {
        $user = $request->user() ?: Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'reason' => 'required|string|min:3|max:500',
        ]);

        $lesson = $comment->lesson;
        $courseId = $lesson?->course_id;
        $evidenceUrl = url("/student/my-courses/{$courseId}/lessons/{$comment->lesson_id}?tab=discussion#comment-{$comment->id}");

        ReportedContent::create([
            'report_code'  => 'REP-' . strtoupper(Str::random(8)),
            'reported_by'  => $user->name ?: ('User #' . $user->id),
            'reason'       => $validated['reason'],
            'content_type' => 'lesson_comment',
            'author_name'  => $comment->user?->name ?? 'User',
            'comment_text' => $comment->body,
            'evidence_url' => $evidenceUrl,
            'status'       => 'pending',
        ]);

        return response()->json([
            'ok'      => true,
            'message' => 'Report submitted successfully. Administrators will review it.',
        ]);
    }

    /**
     * Mark an enrollment as seen by the student (Idempotent).
     */
    public function markEnrollmentSeen(Request $request, Enrollment $enrollment)
    {
        $user = $request->user() ?: Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if ($enrollment->student_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized access to enrollment'], 403);
        }

        try {
            if ($enrollment->seen_at === null) {
                $enrollment->update(['seen_at' => now()]);
                Cache::forget("student.{$user->id}.new_enrollments");
            }
        } catch (\Throwable $e) {}

        return response()->json([
            'ok'      => true,
            'seen_at' => $enrollment->seen_at,
            'message' => 'Enrollment marked as seen',
        ]);
    }
}
