<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\CourseReview;
use App\Models\CourseVideo;
use App\Models\CourseWishlist;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonComment;
use App\Models\LessonCommentLike;
use App\Models\LessonProgress;
use App\Models\LessonResource;
use App\Models\Major;
use App\Models\Notification;
use App\Models\QuizAttempt;
use App\Models\ReportedContent;
use App\Models\StudyDaily;
use App\Models\User;
use App\Services\CertificateService;
use App\Services\ContinueLearningService;
use App\Services\CourseProgressService;
use App\Services\DashboardAnalyticsService;
use App\Services\EnrollmentService;
use App\Services\StudentLearningService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        $status = (string) $request->query('status', 'all');
        if (!in_array($status, ['all', 'in_progress', 'not_started', 'completed', 'coming_soon'], true)) {
            $status = 'all';
        }
        $category = (string) $request->query('category', 'all');
        $sort = (string) $request->query('sort', 'newest');
        if (!in_array($sort, ['newest', 'oldest', 'recent', 'progress', 'title'], true)) {
            $sort = 'newest';
        }
        $q = substr(trim((string) $request->query('q', '')), 0, 100);

        $savedView = is_array($user->preferences) ? ($user->preferences['courses_view'] ?? 'grid') : 'grid';
        $urlView = $request->query('view');
        if (in_array($urlView, ['grid', 'list'], true)) {
            $view = $urlView;
            if ($savedView !== $urlView) {
                try {
                    $prefs = is_array($user->preferences) ? $user->preferences : [];
                    $prefs['courses_view'] = $urlView;
                    $user->preferences = $prefs;
                    $user->save();
                } catch (\Throwable $e) {
                }
            }
        } else {
            $view = in_array($savedView, ['grid', 'list'], true) ? $savedView : 'grid';
        }

        // 2 & 3. Dynamic state stamp so new enrollments or progress never show stale cache
        $enrMeta = Enrollment::where('student_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->selectRaw('COUNT(*) as cnt, MAX(updated_at) as max_upd')
            ->first();
        $lpMeta = LessonProgress::where('user_id', $user->id)
            ->selectRaw('COUNT(*) as cnt, MAX(updated_at) as max_upd')
            ->first();
        $stateStamp = ($enrMeta->cnt ?? 0) . ':' . ($enrMeta->max_upd ?? '') . ':' . ($lpMeta->cnt ?? 0) . ':' . ($lpMeta->max_upd ?? '');
        $cacheKey = "student_enrolled_{$user->id}_v5_" . md5($stateStamp);

        $cachedItems = Cache::remember($cacheKey, 60, function () use ($user, $progressService) {
            $withRelations = [
                'course.teacher:id,name,name_kh,email,avatar,role',
                'course.major:id,name,name_kh',
                'course.modules:id,course_id,title,kh_title,order',
                'course.modules.chapters:id,module_id,chapter_number,title,kh_title,order',
                'course.modules.lessons:id,module_id,chapter_id,course_id,title,kh_title,order,duration_seconds',
                'course.lessons:id,module_id,chapter_id,course_id,title,kh_title,order,duration_seconds',
            ];
            if (Schema::hasColumn('enrollments', 'enrolled_by')) {
                $withRelations[] = 'enrolledBy:id,name,role,avatar';
            }

            $enrollments = Enrollment::where('student_id', $user->id)
                ->whereIn('status', ['active', 'completed'])
                ->whereHas('course')
                ->with($withRelations)
                ->latest('updated_at')
                ->get();

            $computedCollection = $progressService->calculateForEnrollments($user, $enrollments);

            $issuedCertificateCourseIds = [];
            try {
                $issuedCertificateCourseIds = Certificate::where('student_id', $user->id)
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
                $hasCert = in_array($c->id, $issuedCertificateCourseIds, true);

                $thumb = $c->thumbnail ? trim((string) $c->thumbnail) : null;
                if ($thumb && !str_starts_with($thumb, 'http://') && !str_starts_with($thumb, 'https://') && !str_starts_with($thumb, '/')) {
                    $thumb = asset('storage/' . ltrim($thumb, '/'));
                }

                $enrolledDateStr = '';
                try {
                    if ($enr->enrolled_at) {
                        $enrolledDateStr = Carbon::parse($enr->enrolled_at)->format('j M Y');
                    } elseif ($enr->created_at) {
                        $enrolledDateStr = Carbon::parse($enr->created_at)->format('j M Y');
                    }
                } catch (\Throwable $e) {
                    $enrolledDateStr = '';
                }

                $enrolledRel = 'Recently';
                try {
                    if ($enr->enrolled_at) {
                        $enrolledRel = Carbon::parse($enr->enrolled_at)->diffForHumans();
                    } elseif ($enr->created_at) {
                        $enrolledRel = Carbon::parse($enr->created_at)->diffForHumans();
                    }
                } catch (\Throwable $e) {
                    $enrolledRel = 'Recently';
                }

                $enrolledTimestampMs = 0;
                try {
                    if ($enr->enrolled_at) {
                        $enrolledTimestampMs = Carbon::parse($enr->enrolled_at)->getTimestampMs();
                    } elseif ($enr->created_at) {
                        $enrolledTimestampMs = Carbon::parse($enr->created_at)->getTimestampMs();
                    } else {
                        $enrolledTimestampMs = ((int) $enr->id) * 1000;
                    }
                } catch (\Throwable $e) {
                    $enrolledTimestampMs = ((int) $enr->id) * 1000;
                }

                $hasEnrolledBy = $enr->relationLoaded('enrolledBy') && $enr->enrolledBy;
                $currentLesson = $item['current_lesson'] ?? null;
                $learningHref = !empty($currentLesson?->id)
                    ? "/student/my-courses/{$c->id}/lessons/{$currentLesson->id}"
                    : "/student/my-courses/{$c->id}/continue";

                return [
                    'id'                  => $c->id,
                    'enrollment_id'       => $enr->id,
                    'enrolledTimestampMs' => $enrolledTimestampMs,
                    'is_new'              => array_key_exists('seen_at', $enr->getAttributes()) ? ($enr->seen_at === null) : false,
                    'enrolled_by'         => $hasEnrolledBy ? [
                        'name'   => $enr->enrolledBy->name,
                        'role'   => $enr->enrolledBy->role,
                        'avatar' => $enr->enrolledBy->avatar,
                    ] : null,
                    'enrolled_relative'   => $enrolledRel,
                    'source'              => $enr->source ?: 'system',
                    'slug'                => $c->code ?: ('course-' . $c->id),
                    'title'               => $c->title,
                    'titleKh'             => $c->title_kh ?: $c->title,
                    'thumbnail'           => $thumb ?: null,
                    'category'            => $c->major?->name ?: 'Academic Studies',
                    'categoryKh'          => $c->major?->name_kh ?: ($c->major?->name ?: 'ការសិក្សាទូទៅ'),
                    'status'              => $item['state'],
                    'statusLabel'         => $item['state_label'],
                    'progress'            => (int) $item['percent'],
                    'progress_percentage' => (int) ($item['progress_percentage'] ?? $item['percent']),
                    'teacher'             => [
                        'name'    => $teacherName,
                        'name_kh' => $progressService->cleanTeacherNameKh($c->teacher, $c),
                        'avatar'  => $c->teacher?->avatar_url ?: null,
                        'role'    => 'SPI Instructor',
                    ],
                    'currentChapter'      => $item['current_module_title'],
                    'currentModuleIndex'  => $item['current_module_index'],
                    'completedChapters'   => $item['completed_modules'],
                    'totalChapters'       => $item['total_chapters'] ?? $item['total_modules'],
                    'currentLesson'       => $item['current_lesson_title'],
                    'currentLessonKh'     => $currentLesson?->kh_title ?: $item['current_lesson_title'],
                    'completedLessons'    => $item['lessons_done'],
                    'totalLessons'        => $item['lessons_total'],
                    'remainingTime'       => $item['percent'] >= 100 ? 'Completed' : 'Study in progress',
                    'lastAccessed'        => $item['last_activity_at'] ?: 'Not started yet',
                    'lastAccessedTimeMs'  => $item['last_activity_ms'],
                    'enrolledDate'        => $enrolledDateStr,
                    'overviewHref'        => "/student/courses/{$c->id}/overview",
                    'learningHref'        => $learningHref,
                    'has_certificate'     => $hasCert,
                    'certificateHref'     => $hasCert ? '/student/certificates/my-certificates' : null,
                    'themeColor'          => $themeColor,
                    'accentGradient'      => $gradient,
                    'illustrationType'    => $illus,
                ];
            })->values()->all();
        });

        $allCoursesList = collect($cachedItems);

        // 4. Calculate accurate counts strictly matching:
        //    in_progress (1-99%), not_started (0%), completed (100%)
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

        // 5. Real categories from `majors` table merged with any category present on enrolled courses
        $categoryOptionsMap = [];
        try {
            $majors = Major::query()->select(['id', 'name', 'name_kh'])->orderBy('id')->get();
            foreach ($majors as $m) {
                $val = trim((string) ($m->name ?? ''));
                if ($val === '') {
                    continue;
                }
                $key = mb_strtolower($val, 'UTF-8');
                if (!isset($categoryOptionsMap[$key])) {
                    $categoryOptionsMap[$key] = [
                        'value'    => $val,
                        'label_en' => $val,
                        'label_km' => trim((string) ($m->name_kh ?: $val)),
                    ];
                }
            }
        } catch (\Throwable $e) {
        }
        foreach ($allCoursesList as $cItem) {
            $val = trim((string) ($cItem['category'] ?? ''));
            if ($val === '') {
                continue;
            }
            $key = mb_strtolower($val, 'UTF-8');
            if (!isset($categoryOptionsMap[$key])) {
                $categoryOptionsMap[$key] = [
                    'value'    => $val,
                    'label_en' => $val,
                    'label_km' => trim((string) ($cItem['categoryKh'] ?: $val)),
                ];
            }
        }
        $categoryOptions = array_values($categoryOptionsMap);
        $distinctCategories = array_map(fn ($o) => $o['value'], $categoryOptions);

        // 6. Filter by status, category, and multilingual search query
        $filtered = $allCoursesList;
        if ($status !== 'all') {
            $filtered = $filtered->where('status', $status);
        }
        if ($category !== 'all') {
            $catLower = mb_strtolower($category, 'UTF-8');
            $filtered = $filtered->filter(function ($item) use ($catLower) {
                return mb_strtolower((string) ($item['category'] ?? ''), 'UTF-8') === $catLower
                    || mb_strtolower((string) ($item['categoryKh'] ?? ''), 'UTF-8') === $catLower;
            });
        }
        if ($q !== '') {
            $qLower = mb_strtolower($q, 'UTF-8');
            $filtered = $filtered->filter(function ($item) use ($qLower) {
                return str_contains(mb_strtolower((string) ($item['title'] ?? ''), 'UTF-8'), $qLower)
                    || str_contains(mb_strtolower((string) ($item['titleKh'] ?? ''), 'UTF-8'), $qLower)
                    || str_contains(mb_strtolower((string) ($item['teacher']['name'] ?? ''), 'UTF-8'), $qLower)
                    || str_contains(mb_strtolower((string) ($item['teacher']['name_kh'] ?? ''), 'UTF-8'), $qLower)
                    || str_contains(mb_strtolower((string) ($item['category'] ?? ''), 'UTF-8'), $qLower)
                    || str_contains(mb_strtolower((string) ($item['categoryKh'] ?? ''), 'UTF-8'), $qLower)
                    || str_contains(mb_strtolower((string) ($item['currentLesson'] ?? ''), 'UTF-8'), $qLower)
                    || str_contains(mb_strtolower((string) ($item['currentChapter'] ?? ''), 'UTF-8'), $qLower);
            });
        }

        // 7. Sort
        if ($sort === 'recent') {
            $filtered = $filtered->sortByDesc('lastAccessedTimeMs');
        } elseif ($sort === 'progress') {
            $filtered = $filtered->sortByDesc('progress');
        } elseif ($sort === 'title') {
            $filtered = $filtered->sortBy('title', SORT_NATURAL | SORT_FLAG_CASE);
        } elseif ($sort === 'oldest') {
            $filtered = $filtered->sortBy('enrolledTimestampMs');
        } else { // 'newest'
            $filtered = $filtered->sortByDesc('enrolledTimestampMs');
        }

        $savedOfflineIds = is_array($user->preferences) && is_array($user->preferences['offline_course_ids'] ?? null)
            ? array_values(array_unique(array_map('intval', $user->preferences['offline_course_ids'])))
            : [];

        return Inertia::render('Student/MyCourses/EnrolledCourses', [
            'enrollments'           => $filtered->values()->all(),
            'all_enrollments'       => $allCoursesList->values()->all(),
            'counts'                => $counts,
            'categories'            => $distinctCategories,
            'categoryOptions'       => $categoryOptions,
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
            $recommended = Course::query()
                ->where('status', 'published')
                ->with(['major:id,name,name_kh'])
                ->withCount('lessons')
                ->withCount(['enrollments' => fn ($q) => $q->whereIn('status', ['active', 'completed'])])
                ->orderByDesc('enrollments_count')
                ->orderByDesc('id')
                ->limit(3)
                ->get()
                ->filter(fn ($c) => (int) ($c->lessons_count ?? 0) > 0)
                ->map(function (Course $c) {
                    $priceVal = (float) ($c->price ?? 0);
                    $isFree = $priceVal <= 0;
                    $thumb = $c->thumbnail ? trim((string) $c->thumbnail) : null;
                    if ($thumb && !str_starts_with($thumb, 'http://') && !str_starts_with($thumb, 'https://') && !str_starts_with($thumb, '/')) {
                        $thumb = asset('storage/' . ltrim($thumb, '/'));
                    }
                    return [
                        'id'           => $c->id,
                        'title'        => $c->title,
                        'titleKh'      => $c->title_kh ?: $c->title,
                        'code'         => $c->code,
                        'thumbnail'    => $thumb ?: null,
                        'category'     => $c->major?->name,
                        'categoryKh'   => $c->major?->name_kh ?: $c->major?->name,
                        'lessonsCount' => (int) ($c->lessons_count ?? 0),
                        'isFree'       => $isFree,
                        'priceDisplay' => $isFree ? 'Free' : ('$' . number_format($priceVal, 2)),
                        'overviewHref' => "/student/courses/{$c->id}/overview",
                        'enrollHref'   => "/student/courses/{$c->id}/enroll",
                        'checkoutHref' => "/student/payments/{$c->id}/upload",
                    ];
                })
                ->values()
                ->all();

            return Inertia::render('Student/MyCourses/CurrentCourseEmpty', [
                'recommendedCourses' => $recommended,
            ]);
        }

        if (!empty($resolved['all_completed'])) {
            return redirect()->route('student.my-courses.completed')->with(
                'info',
                app()->getLocale() === 'km'
                    ? 'អ្នកបានបញ្ចប់វគ្គសិក្សាទាំងអស់រួចរាល់ហើយ!'
                    : 'You have completed all your enrolled courses!'
            );
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
     * Resolve and 302 redirect to the first incomplete lesson (or first lesson if completed) for a specific course.
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
        } elseif (!in_array($enrollment->status, ['active', 'completed'], true)) {
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

        $firstLesson = $course->lessons()->orderBy('order')->orderBy('id')->first();

        if ($enrollment->status === 'completed') {
            if ($firstLesson) {
                return redirect()->to("/student/my-courses/{$course->id}/lessons/{$firstLesson->id}");
            }
            return redirect()->to("/student/courses/{$course->id}/overview");
        }

        $resolved = $continueService->resolve($user, $course);

        if (!$resolved) {
            return $firstLesson
                ? redirect()->to("/student/my-courses/{$course->id}/lessons/{$firstLesson->id}")
                : redirect()->to("/student/courses/{$course->id}/overview");
        }

        if (!empty($resolved['all_completed'])) {
            $reviewLesson = $resolved['lesson'] ?? $firstLesson;
            return $reviewLesson
                ? redirect()->to("/student/my-courses/{$course->id}/lessons/{$reviewLesson->id}")
                : redirect()->to("/student/courses/{$course->id}/overview");
        }

        if (!empty($resolved['lesson'])) {
            return redirect()->to("/student/my-courses/{$course->id}/lessons/{$resolved['lesson']->id}");
        }

        return $firstLesson
            ? redirect()->to("/student/my-courses/{$course->id}/lessons/{$firstLesson->id}")
            : redirect()->to("/student/courses/{$course->id}/overview");
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
        app(DashboardAnalyticsService::class)->invalidateUserCache($user->id);

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

    public function completed(Request $request, CourseProgressService $progressService)
    {
        $user = $request->user();

        // 1. Validate query parameters & sync grid/list view preference
        $status = (string) $request->query('status', 'all');
        if (!in_array($status, ['all', 'certificates', 'no_certificate'], true)) {
            $status = 'all';
        }
        $sort = (string) $request->query('sort', 'newest');
        if (!in_array($sort, ['newest', 'oldest', 'highest_score', 'name', 'most_time'], true)) {
            $sort = 'newest';
        }
        $q = substr(trim((string) $request->query('q', '')), 0, 100);

        $savedView = is_array($user->preferences) ? ($user->preferences['courses_view'] ?? 'grid') : 'grid';
        $urlView = $request->query('view');
        if (in_array($urlView, ['grid', 'list'], true)) {
            $view = $urlView;
            if ($savedView !== $urlView) {
                try {
                    $prefs = is_array($user->preferences) ? $user->preferences : [];
                    $prefs['courses_view'] = $urlView;
                    $user->preferences = $prefs;
                    $user->save();
                } catch (\Throwable $e) {
                }
            }
        } else {
            $view = in_array($savedView, ['grid', 'list'], true) ? $savedView : 'grid';
        }

        // 2. Load student's enrollments & compute progress cleanly
        $enrollments = Enrollment::where('student_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->whereHas('course')
            ->with([
                'course.teacher:id,name,name_kh,email,avatar,role',
                'course.major:id,name,name_kh',
                'course.modules:id,course_id,title,kh_title,order',
                'course.modules.chapters:id,module_id,chapter_number,title,kh_title,order',
                'course.modules.lessons:id,module_id,chapter_id,course_id,title,kh_title,order,duration_seconds',
                'course.lessons:id,module_id,chapter_id,course_id,title,kh_title,order,duration_seconds',
            ])
            ->latest('updated_at')
            ->get();

        $computedCollection = $progressService->calculateForEnrollments($user, $enrollments);

        // Filter to courses that are either explicitly marked 'completed' or have 100% progress
        $completedItems = $computedCollection->filter(function ($item) {
            $enr = $item['enrollment'];
            $pct = (int) ($item['progress_percentage'] ?? $item['percent'] ?? 0);
            return $enr->status === 'completed' || ($item['state'] ?? '') === 'completed' || $pct >= 100;
        })->values();

        // Sync active enrollments that reached 100% progress to status = 'completed'
        foreach ($completedItems as $item) {
            $enr = $item['enrollment'];
            if ($enr && $enr->status !== 'completed') {
                try {
                    $enr->update(['status' => 'completed']);
                } catch (\Throwable $e) {
                }
            }
        }

        $completedCourseIds = $completedItems->map(fn ($item) => (int) $item['course']->id)->unique()->values()->all();

        // Map all lesson IDs belonging to completed courses -> course_id (works via modules OR direct course_id)
        $lessonCourseMap = [];
        foreach ($completedItems as $item) {
            $c = $item['course'];
            if ($c->modules) {
                foreach ($c->modules as $mod) {
                    if ($mod->lessons) {
                        foreach ($mod->lessons as $l) {
                            $lessonCourseMap[(int) $l->id] = (int) $c->id;
                        }
                    }
                }
            }
            if ($c->lessons) {
                foreach ($c->lessons as $l) {
                    $lessonCourseMap[(int) $l->id] = (int) $c->id;
                }
            }
        }

        // 3. Batch load real certificates for student (both per-course map & latest overall)
        $allUserCerts = collect();
        $certsByCourse = collect();
        try {
            $allUserCerts = Certificate::where('student_id', $user->id)
                ->whereIn('status', ['issued', 'valid'])
                ->with(['course:id,title,title_kh,teacher_id,major_id,code', 'course.teacher:id,name,name_kh,email,avatar'])
                ->orderByDesc('issued_at')
                ->orderByDesc('id')
                ->get();

            $certsByCourse = $allUserCerts
                ->filter(fn ($cert) => $cert->course_id !== null)
                ->unique('course_id')
                ->keyBy('course_id');
        } catch (\Throwable $e) {
            $allUserCerts = collect();
            $certsByCourse = collect();
        }

        // 4. Batch load real study time & last lesson completion timestamp per completed course
        $secondsByCourse = [];
        $lastLessonCompletedAtByCourse = [];
        try {
            if (!empty($lessonCourseMap)) {
                $progressRows = LessonProgress::where('user_id', $user->id)
                    ->whereIn('lesson_id', array_keys($lessonCourseMap))
                    ->get(['lesson_id', 'seconds_watched', 'completed_at', 'updated_at']);

                foreach ($progressRows as $pr) {
                    $cid = $lessonCourseMap[(int) $pr->lesson_id] ?? null;
                    if (!$cid) {
                        continue;
                    }
                    $secondsByCourse[$cid] = ($secondsByCourse[$cid] ?? 0) + (int) ($pr->seconds_watched ?? 0);
                    $cand = $pr->completed_at ?: $pr->updated_at;
                    if ($cand) {
                        $candDt = Carbon::parse($cand);
                        if (!isset($lastLessonCompletedAtByCourse[$cid]) || $candDt->gt($lastLessonCompletedAtByCourse[$cid])) {
                            $lastLessonCompletedAtByCourse[$cid] = $candDt;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            $secondsByCourse = [];
            $lastLessonCompletedAtByCourse = [];
        }

        // 5. Batch load real quiz scores (QuizAttempt) per completed course
        $quizScoresByCourse = [];
        $quizAttemptCountsByCourse = [];
        $realQuizAttemptsTotal = 0;
        $realQuizAttemptWeightedSum = 0.0;
        try {
            if (!empty($completedCourseIds) && Schema::hasTable('quiz_attempts') && Schema::hasTable('quizzes')) {
                $rows = QuizAttempt::query()
                    ->join('quizzes', 'quiz_attempts.quiz_id', '=', 'quizzes.id')
                    ->where('quiz_attempts.user_id', $user->id)
                    ->whereIn('quizzes.course_id', $completedCourseIds)
                    ->whereNotNull('quiz_attempts.score')
                    ->selectRaw('quizzes.course_id, AVG(quiz_attempts.score) as avg_score, COUNT(quiz_attempts.id) as attempt_count')
                    ->groupBy('quizzes.course_id')
                    ->get();
                foreach ($rows as $r) {
                    $cid = (int) $r->course_id;
                    $cnt = (int) $r->attempt_count;
                    $avg = (float) $r->avg_score;
                    $quizScoresByCourse[$cid] = (int) round($avg);
                    $quizAttemptCountsByCourse[$cid] = $cnt;
                    $realQuizAttemptsTotal += $cnt;
                    $realQuizAttemptWeightedSum += ($avg * $cnt);
                }
            }
        } catch (\Throwable $e) {
            $quizScoresByCourse = [];
            $quizAttemptCountsByCourse = [];
        }

        // 6. Map full unfiltered completed courses list
        $allCompletedList = $completedItems->map(function ($item) use (
            $progressService,
            $certsByCourse,
            $secondsByCourse,
            $lastLessonCompletedAtByCourse,
            $quizScoresByCourse
        ) {
            $enr = $item['enrollment'];
            $c = $item['course'];
            if (!$c) {
                return null;
            }

            $cert = $certsByCourse->get($c->id);

            $lessonsCount = (int) ($item['lessons_total'] ?? 0);
            if ($lessonsCount === 0) {
                $lessonsCount = $c->lessons ? $c->lessons->count() : 0;
            }
            $chaptersCount = (int) ($item['total_chapters'] ?? $item['total_modules'] ?? 1);

            // Deduplicate lessons across modules & course relation for duration fallback
            $uniqueLessons = collect();
            if ($c->modules) {
                foreach ($c->modules as $mod) {
                    if ($mod->lessons) {
                        foreach ($mod->lessons as $l) {
                            $uniqueLessons->put($l->id, $l);
                        }
                    }
                }
            }
            if ($c->lessons) {
                foreach ($c->lessons as $l) {
                    $uniqueLessons->put($l->id, $l);
                }
            }

            // Real study time from DB (lesson_progress.seconds_watched -> fallback to lesson duration_seconds if 0)
            $totalSeconds = (int) ($secondsByCourse[(int) $c->id] ?? 0);
            if ($totalSeconds <= 0 && $uniqueLessons->isNotEmpty()) {
                $totalSeconds = (int) $uniqueLessons->sum('duration_seconds');
            }
            $studyMinutes = (int) round($totalSeconds / 60);
            $hours = intdiv($studyMinutes, 60);
            $mins = $studyMinutes % 60;
            $studyTimeFormatted = $hours > 0
                ? "{$hours}h " . str_pad((string) $mins, 2, '0', STR_PAD_LEFT) . 'm'
                : "{$mins}m";

            // Real quiz score from QuizAttempt (fallback to certificate.score if present)
            $hasQuizScore = array_key_exists((int) $c->id, $quizScoresByCourse);
            $quizScore = $hasQuizScore ? $quizScoresByCourse[(int) $c->id] : 0;
            if (!$hasQuizScore && $cert && $cert->score !== null) {
                $quizScore = (int) round((float) $cert->score);
                $hasQuizScore = true;
            }

            $medalType = $quizScore >= 90 ? 'gold' : ($quizScore >= 75 ? 'silver' : 'bronze');

            $titleLower = strtolower($c->title . ' ' . ($c->major->name ?? ''));
            $illus = 'web';
            if (str_contains($titleLower, 'python') || str_contains($titleLower, 'ai') || str_contains($titleLower, 'machine')) {
                $illus = 'python';
            } elseif (str_contains($titleLower, 'database') || str_contains($titleLower, 'sql')) {
                $illus = 'database';
            } elseif (str_contains($titleLower, 'design') || str_contains($titleLower, 'ui')) {
                $illus = 'uiux';
            }

            $thumb = $c->thumbnail ? trim((string) $c->thumbnail) : null;
            if ($thumb && !str_starts_with($thumb, 'http://') && !str_starts_with($thumb, 'https://') && !str_starts_with($thumb, '/')) {
                $thumb = asset('storage/' . ltrim($thumb, '/'));
            }

            $completedDate = '';
            $completedTs = 0;
            try {
                $dateSource = $lastLessonCompletedAtByCourse[(int) $c->id]
                    ?? $enr->updated_at
                    ?? $enr->enrolled_at
                    ?? $enr->created_at;
                if ($dateSource) {
                    $dt = Carbon::parse($dateSource)->timezone(DashboardAnalyticsService::TIMEZONE);
                    $completedDate = $dt->format('M d, Y');
                    $completedTs = $dt->getTimestampMs();
                }
            } catch (\Throwable $e) {
            }

            $certIssueDate = null;
            $certIssuedTs = 0;
            try {
                if ($cert && ($cert->issued_at || $cert->created_at)) {
                    $cdt = Carbon::parse($cert->issued_at ?: $cert->created_at)->timezone(DashboardAnalyticsService::TIMEZONE);
                    $certIssueDate = $cdt->format('M d, Y');
                    $certIssuedTs = $cdt->getTimestampMs();
                }
            } catch (\Throwable $e) {
            }

            $missingCertReason = null;
            $missingCertReasonKh = null;
            if ($cert === null) {
                if (!$hasQuizScore) {
                    $missingCertReason = 'Final course assessment has not been completed yet.';
                    $missingCertReasonKh = 'មិនទាន់បានបញ្ចប់តេស្តវាយតម្លៃចុងក្រោយសម្រាប់វគ្គសិក្សានេះនៅឡើយទេ។';
                } elseif ($quizScore < 70) {
                    $missingCertReason = "Current quiz score is {$quizScore}% (minimum 70% required for certificate).";
                    $missingCertReasonKh = "ពិន្ទុបច្ចុប្បន្ន {$quizScore}% (ត្រូវការយ៉ាងតិច 70% ដើម្បីទទួលបានវិញ្ញាបនបត្រ)។";
                } else {
                    $missingCertReason = 'Certificate issuance is pending verification by the academic office.';
                    $missingCertReasonKh = 'វិញ្ញាបនបត្រកំពុងរង់ចាំការផ្ទៀងផ្ទាត់ និងចេញជូនពីការិយាល័យសិក្សា។';
                }
            }

            $grade = $cert?->grade;
            if (!$grade && $hasQuizScore) {
                $grade = $quizScore >= 90 ? 'A' : ($quizScore >= 80 ? 'B' : ($quizScore >= 70 ? 'C' : 'D'));
            }

            return [
                'id'                      => (int) $c->id,
                'enrollmentId'            => (int) $enr->id,
                'slug'                    => $c->code ?: ('course-' . $c->id),
                'title'                   => $c->title,
                'titleKh'                 => $c->title_kh ?: $c->title,
                'category'                => $c->major?->name ?: 'Academic Studies',
                'categoryKh'              => $c->major?->name_kh ?: ($c->major?->name ?: 'ការសិក្សាទូទៅ'),
                'status'                  => 'completed',
                'progress'                => 100,
                'progress_percentage'     => 100,
                'teacher'                 => [
                    'name'    => $progressService->cleanTeacherName($c->teacher, $c),
                    'name_kh' => $progressService->cleanTeacherNameKh($c->teacher, $c),
                    'avatar'  => $c->teacher?->avatar_url ?: null,
                    'role'    => 'SPI Instructor',
                ],
                'completedDate'           => $completedDate,
                'completedDateTimestamp'  => $completedTs,
                'chaptersCount'           => $chaptersCount,
                'lessonsCount'            => $lessonsCount,
                'studyTime'               => $studyTimeFormatted,
                'studyTimeMinutes'        => $studyMinutes,
                'studyTimeSeconds'        => $totalSeconds,
                'quizScore'               => (int) $quizScore,
                'hasQuizScore'            => $hasQuizScore,
                'grade'                   => $grade,
                'hasCertificate'          => $cert !== null,
                'certificateDbId'         => $cert?->id ? (int) $cert->id : null,
                'certificateId'           => $cert?->certificate_number ?: null,
                'verificationCode'        => $cert?->verification_code ?: null,
                'certIssueDate'           => $certIssueDate,
                'certIssuedTimestamp'     => $certIssuedTs,
                'certificateDownloadHref' => $cert ? "/certificate/download/{$cert->id}" : null,
                'certificateVerifyHref'   => $cert ? "/certificate/verify/{$cert->certificate_number}" : null,
                'missingCertReason'       => $missingCertReason,
                'missingCertReasonKh'     => $missingCertReasonKh,
                'medalType'               => $medalType,
                'illustrationType'        => $illus,
                'thumbnail'               => $thumb ?: null,
                'overviewHref'            => "/student/courses/{$c->id}/overview",
                'reviewHref'              => "/student/my-courses/{$c->id}/continue",
            ];
        })->filter()->values();

        // 7. Compute unfiltered counts & sidebar analytics
        $totalCompleted = $allCompletedList->count();
        $withCertCount = $allCompletedList->where('hasCertificate', true)->count();
        $noCertCount = $allCompletedList->where('hasCertificate', false)->count();
        $certPercent = $totalCompleted > 0 ? (int) round(($withCertCount / $totalCompleted) * 100) : 0;
        $noCertPercent = $totalCompleted > 0 ? (100 - $certPercent) : 0;

        $totalStudySeconds = (int) $allCompletedList->sum('studyTimeSeconds');
        $totalStudyMinutes = (int) round($totalStudySeconds / 60);
        $totalHrs = intdiv($totalStudyMinutes, 60);
        $totalMns = $totalStudyMinutes % 60;
        $totalStudyTimeFormatted = "{$totalHrs}h " . str_pad((string) $totalMns, 2, '0', STR_PAD_LEFT) . 'm';

        if ($realQuizAttemptsTotal > 0) {
            $averageQuizScore = (int) round($realQuizAttemptWeightedSum / $realQuizAttemptsTotal);
            $completedQuizzesCount = $realQuizAttemptsTotal;
        } else {
            $scoredCourses = $allCompletedList->where('hasQuizScore', true);
            $averageQuizScore = $scoredCourses->isNotEmpty() ? (int) round((float) $scoredCourses->avg('quizScore')) : 0;
            $completedQuizzesCount = $scoredCourses->count();
        }

        $counts = [
            'all'            => $totalCompleted,
            'certificates'   => $withCertCount,
            'no_certificate' => $noCertCount,
        ];

        $stats = [
            'totalCompleted'          => $totalCompleted,
            'withCertificateCount'    => $withCertCount,
            'noCertificateCount'      => $noCertCount,
            'certPercent'             => $certPercent,
            'noCertPercent'           => $noCertPercent,
            'totalStudyMinutes'       => $totalStudyMinutes,
            'totalStudySeconds'       => $totalStudySeconds,
            'totalStudyTimeFormatted' => $totalStudyTimeFormatted,
            'averageQuizScore'        => $averageQuizScore,
            'completedQuizzesCount'   => $completedQuizzesCount,
        ];

        // 8. Build real Latest Certificate payload (null if user has 0 issued/valid certificates)
        $latestCertModel = $allUserCerts->first();
        $latestCertificate = null;
        if ($latestCertModel) {
            $latestIssueStr = '';
            try {
                if ($latestCertModel->issued_at || $latestCertModel->created_at) {
                    $latestIssueStr = Carbon::parse($latestCertModel->issued_at ?: $latestCertModel->created_at)
                        ->timezone(DashboardAnalyticsService::TIMEZONE)
                        ->format('M d, Y');
                }
            } catch (\Throwable $e) {
            }

            $certCourse = $latestCertModel->course;
            $latestCertificate = [
                'id'               => (int) $latestCertModel->id,
                'courseId'         => $latestCertModel->course_id ? (int) $latestCertModel->course_id : null,
                'title'            => $certCourse?->title ?? 'Course Certificate',
                'titleKh'          => $certCourse?->title_kh ?: ($certCourse?->title ?? 'វិញ្ញាបនបត្រវគ្គសិក្សា'),
                'certificateId'    => $latestCertModel->certificate_number,
                'verificationCode' => $latestCertModel->verification_code,
                'certIssueDate'    => $latestIssueStr,
                'teacherName'      => $certCourse?->teacher ? $progressService->cleanTeacherName($certCourse->teacher, $certCourse) : 'SPI Instructor',
                'teacherNameKh'    => $certCourse?->teacher ? $progressService->cleanTeacherNameKh($certCourse->teacher, $certCourse) : 'គ្រូបង្រៀន SPI',
                'score'            => $latestCertModel->score !== null ? (int) round((float) $latestCertModel->score) : null,
                'grade'            => $latestCertModel->grade,
                'downloadHref'     => "/certificate/download/{$latestCertModel->id}",
                'verifyHref'       => "/certificate/verify/{$latestCertModel->certificate_number}",
            ];
        }

        // 9. Build real 4 Achievements Badges from MySQL activity
        $achievements = $this->buildCompletedAchievements(
            $user,
            $completedItems,
            $lastLessonCompletedAtByCourse,
            $totalCompleted,
            $allUserCerts->unique('course_id')->count()
        );

        // 10. Filter & sort list for active view
        $filtered = $allCompletedList;
        if ($status === 'certificates') {
            $filtered = $filtered->where('hasCertificate', true);
        } elseif ($status === 'no_certificate') {
            $filtered = $filtered->where('hasCertificate', false);
        }

        if ($q !== '') {
            $qLower = mb_strtolower($q, 'UTF-8');
            $filtered = $filtered->filter(function ($item) use ($qLower) {
                return str_contains(mb_strtolower((string) ($item['title'] ?? ''), 'UTF-8'), $qLower)
                    || str_contains(mb_strtolower((string) ($item['titleKh'] ?? ''), 'UTF-8'), $qLower)
                    || str_contains(mb_strtolower((string) ($item['category'] ?? ''), 'UTF-8'), $qLower)
                    || str_contains(mb_strtolower((string) ($item['categoryKh'] ?? ''), 'UTF-8'), $qLower)
                    || str_contains(mb_strtolower((string) ($item['teacher']['name'] ?? ''), 'UTF-8'), $qLower)
                    || str_contains(mb_strtolower((string) ($item['teacher']['name_kh'] ?? ''), 'UTF-8'), $qLower)
                    || str_contains(mb_strtolower((string) ($item['slug'] ?? ''), 'UTF-8'), $qLower)
                    || str_contains(mb_strtolower((string) ($item['certificateId'] ?? ''), 'UTF-8'), $qLower);
            });
        }

        if ($sort === 'oldest') {
            $filtered = $filtered->sortBy('completedDateTimestamp');
        } elseif ($sort === 'highest_score') {
            $filtered = $filtered->sortByDesc('quizScore');
        } elseif ($sort === 'name') {
            $filtered = $filtered->sortBy('title', SORT_NATURAL | SORT_FLAG_CASE);
        } elseif ($sort === 'most_time') {
            $filtered = $filtered->sortByDesc('studyTimeSeconds');
        } else {
            $filtered = $filtered->sortByDesc('completedDateTimestamp');
        }

        return Inertia::render('Student/MyCourses/CompletedCourses', [
            'enrollments'       => $filtered->values()->all(),
            'all_enrollments'   => $allCompletedList->values()->all(),
            'counts'            => $counts,
            'stats'             => $stats,
            'latestCertificate' => $latestCertificate,
            'achievements'      => $achievements,
            'filters'           => [
                'status' => $status,
                'sort'   => $sort,
                'q'      => $q,
                'view'   => $view,
            ],
        ]);
    }

    /**
     * Build the 4 Completed Courses Achievements badges strictly from real database records.
     */
    protected function buildCompletedAchievements(
        User $user,
        Collection $completedItems,
        array $lastLessonCompletedAtByCourse,
        int $totalCompleted,
        int $totalCertsCount
    ): array {
        $completedWithin7Days = false;
        foreach ($completedItems as $item) {
            $enr = $item['enrollment'] ?? null;
            $c = $item['course'] ?? null;
            if (!$enr || !$c) {
                continue;
            }
            $startRaw = $enr->enrolled_at ?: $enr->created_at;
            $endRaw = $lastLessonCompletedAtByCourse[(int) $c->id] ?? $enr->updated_at;
            if ($startRaw && $endRaw) {
                try {
                    $startDt = Carbon::parse($startRaw);
                    $endDt = Carbon::parse($endRaw);
                    if ($endDt->gte($startDt) && $startDt->diffInDays($endDt) <= 7) {
                        $completedWithin7Days = true;
                        break;
                    }
                } catch (\Throwable $e) {
                }
            }
        }

        $totalCompletedLessons = 0;
        $bestQuizScoreOverall = 0;
        $distinctDaysCount = 0;
        $overallStudySeconds = 0;

        try {
            $totalCompletedLessons = LessonProgress::where('user_id', $user->id)
                ->where(function ($q) {
                    $q->whereNotNull('completed_at')->orWhere('percent', '>=', 90);
                })
                ->count();
        } catch (\Throwable $e) {
        }

        try {
            if (Schema::hasTable('quiz_attempts')) {
                $bestQuizScoreOverall = (int) round((float) (QuizAttempt::where('user_id', $user->id)->whereNotNull('score')->max('score') ?? 0));
            }
        } catch (\Throwable $e) {
        }

        try {
            $studyDateSet = [];
            $studyDailySeconds = 0;
            if (Schema::hasTable('study_daily')) {
                $dailyRows = StudyDaily::where('user_id', $user->id)
                    ->where('seconds', '>', 0)
                    ->get(['study_date', 'seconds']);
                foreach ($dailyRows as $dr) {
                    if ($dr->study_date) {
                        $studyDateSet[Carbon::parse($dr->study_date)->toDateString()] = true;
                    }
                    $studyDailySeconds += (int) $dr->seconds;
                }
            }

            $lpRows = LessonProgress::where('user_id', $user->id)
                ->where(function ($q) {
                    $q->where('seconds_watched', '>', 0)->orWhere('percent', '>', 0)->orWhereNotNull('completed_at');
                })
                ->get(['seconds_watched', 'completed_at', 'updated_at', 'created_at']);

            $lpSeconds = 0;
            foreach ($lpRows as $lpr) {
                $lpSeconds += (int) ($lpr->seconds_watched ?? 0);
                $dtSource = $lpr->completed_at ?: ($lpr->updated_at ?: $lpr->created_at);
                if ($dtSource) {
                    $studyDateSet[Carbon::parse($dtSource)->timezone(DashboardAnalyticsService::TIMEZONE)->toDateString()] = true;
                }
            }

            $distinctDaysCount = count($studyDateSet);
            $overallStudySeconds = max($studyDailySeconds, $lpSeconds);
        } catch (\Throwable $e) {
        }

        $quickUnlocked = $completedWithin7Days || $totalCompletedLessons >= 3;
        $courseMasterUnlocked = $totalCompleted >= 1;
        $topPerformerUnlocked = $bestQuizScoreOverall >= 80 || $totalCertsCount >= 1;
        $consistentUnlocked = $distinctDaysCount >= 3 || $overallStudySeconds >= 3600;

        return [
            [
                'id'          => 'quick_learner',
                'key'         => 'quick_learner',
                'title'       => 'Quick Learner',
                'title_km'    => 'រៀនរហ័ស',
                'unlocked'    => $quickUnlocked,
                'progress'    => $completedWithin7Days ? '1/1 course' : (min(3, $totalCompletedLessons) . '/3 lessons'),
                'progress_km' => $completedWithin7Days ? '១/១ វគ្គ' : (min(3, $totalCompletedLessons) . '/3 មេរៀន'),
                'theme'       => 'emerald',
            ],
            [
                'id'          => 'course_master',
                'key'         => 'course_master',
                'title'       => 'Course Master',
                'title_km'    => 'ម្ចាស់វគ្គ',
                'unlocked'    => $courseMasterUnlocked,
                'progress'    => "{$totalCompleted}/1 course",
                'progress_km' => "{$totalCompleted}/1 វគ្គ",
                'theme'       => 'blue',
            ],
            [
                'id'          => 'top_performer',
                'key'         => 'top_performer',
                'title'       => 'Top Performer',
                'title_km'    => 'ពូកែបំផុត',
                'unlocked'    => $topPerformerUnlocked,
                'progress'    => $bestQuizScoreOverall > 0 ? "{$bestQuizScoreOverall}% / 80%" : ($totalCertsCount > 0 ? "{$totalCertsCount} cert" : '0% / 80%'),
                'progress_km' => $bestQuizScoreOverall > 0 ? "{$bestQuizScoreOverall}% / 80%" : ($totalCertsCount > 0 ? "{$totalCertsCount} វិញ្ញាបនបត្រ" : '0% / 80%'),
                'theme'       => 'orange',
            ],
            [
                'id'          => 'consistent_learner',
                'key'         => 'consistent_learner',
                'title'       => 'Consistent Learner',
                'title_km'    => 'ព្យាយាមរៀន',
                'unlocked'    => $consistentUnlocked,
                'progress'    => "{$distinctDaysCount}/3 days",
                'progress_km' => "{$distinctDaysCount}/3 ថ្ងៃ",
                'theme'       => 'purple',
            ],
        ];
    }

    public function overview(Request $request, CourseProgressService $progressService, $course = null)
    {
        $user = $request->user();
        $courseData = null;

        $withRelations = [
            'teacher:id,name,name_kh,email,avatar,role,qualification,expertise',
            'major:id,name,name_kh',
            'modules' => fn ($mq) => $mq->orderBy('order'),
            'modules.chapters' => fn ($cq) => $cq->orderBy('order'),
            'modules.chapters.lessons' => fn ($lq) => $lq->orderBy('order'),
            'modules.lessons' => fn ($lq) => $lq->orderBy('order'),
            'lessons' => fn ($lq) => $lq->orderBy('order'),
        ];

        $realCourse = null;
        $courseId = null;

        if ($course instanceof Course) {
            $realCourse = $course->loadMissing($withRelations);
            $courseId = (int) $realCourse->id;
        } elseif ($course !== null && $course !== '') {
            $rawParam = trim((string) $course);
            if (ctype_digit($rawParam)) {
                $courseId = (int) $rawParam;
            } elseif (preg_match('/^course-(\d+)$/i', $rawParam, $m)) {
                $courseId = (int) $m[1];
            }

            if ($courseId) {
                $realCourse = Course::with($withRelations)->find($courseId);
            } else {
                $realCourse = Course::with($withRelations)->where('code', $rawParam)->first();
                $courseId = $realCourse?->id;
            }
        }

        if ($user && $courseId) {
            try {
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
            } catch (\Throwable $e) {
            }
        }

        if ($realCourse) {
            $enrollment = $user
                ? Enrollment::where('student_id', $user->id)->where('course_id', $realCourse->id)->first()
                : null;

            if ($realCourse->status !== 'published' && !$enrollment && $user?->role === 'student') {
                abort(404);
            }

            $allLessons = collect();
            if ($realCourse->modules && $realCourse->modules->isNotEmpty()) {
                foreach ($realCourse->modules as $mod) {
                    if ($mod->lessons) {
                        foreach ($mod->lessons as $l) {
                            $allLessons->put($l->id, $l);
                        }
                    }
                }
            }
            if ($realCourse->lessons && $realCourse->lessons->isNotEmpty()) {
                foreach ($realCourse->lessons as $l) {
                    $allLessons->put($l->id, $l);
                }
            }
            $allLessons = $allLessons->sortBy('order')->values();

            $totalLessons = $allLessons->count();
            $totalSeconds = (int) $allLessons->sum('duration_seconds');
            $totalMins = (int) round($totalSeconds / 60);
            $hours = intdiv($totalMins, 60);
            $mins = $totalMins % 60;
            $durationFormatted = $totalSeconds > 0
                ? ($hours > 0 ? "{$hours}h " . str_pad((string) $mins, 2, '0', STR_PAD_LEFT) . 'm' : "{$mins}m")
                : null;

            $lessonIds = $allLessons->pluck('id')->all();
            $completedLessonIds = ($user && !empty($lessonIds))
                ? LessonProgress::where('user_id', $user->id)
                    ->whereIn('lesson_id', $lessonIds)
                    ->where(function ($pq) {
                        $pq->whereNotNull('completed_at')->orWhere('percent', '>=', 90);
                    })
                    ->pluck('lesson_id')
                    ->all()
                : [];

            $progress = $user ? $progressService->calculate($user, $realCourse) : [
                'percent'       => 0,
                'lessons_done'  => 0,
                'lessons_total' => $totalLessons,
                'state'         => $totalLessons === 0 ? 'coming_soon' : 'not_started',
            ];

            // 1. Build Chapters / Modules Curriculum
            $mapLessonItem = function ($l) use ($completedLessonIds, $realCourse) {
                $secs = (int) ($l->duration_seconds ?? 0);
                $m = intdiv($secs, 60);
                $s = $secs % 60;
                return [
                    'id'            => (int) $l->id,
                    'title'         => $l->title,
                    'titleKh'       => $l->kh_title ?: $l->title,
                    'type'          => $l->type ?: 'video',
                    'duration'      => $secs > 0 ? sprintf('%02d:%02d', $m, $s) : '—',
                    'durationSeconds' => $secs,
                    'completed'     => in_array($l->id, $completedLessonIds, true),
                    'isFreePreview' => (bool) ($l->is_free_preview ?? false),
                    'lessonHref'    => "/student/my-courses/{$realCourse->id}/lessons/{$l->id}",
                ];
            };

            $chapters = [];
            if ($realCourse->modules && $realCourse->modules->isNotEmpty()) {
                foreach ($realCourse->modules as $mod) {
                    $modChapters = $mod->chapters ?? collect();
                    if ($modChapters->isNotEmpty()) {
                        foreach ($modChapters as $ch) {
                            $chLessons = $ch->lessons ?? collect();
                            $chSecs = (int) $chLessons->sum('duration_seconds');
                            $chMins = max(1, (int) round($chSecs / 60));
                            $chapters[] = [
                                'id'           => (int) $ch->id,
                                'title'        => $ch->title,
                                'titleKh'      => $ch->kh_title ?: $ch->title,
                                'lessonsCount' => $chLessons->count(),
                                'duration'     => $chSecs > 0 ? "{$chMins} mins" : '—',
                                'completed'    => $chLessons->isNotEmpty() && $chLessons->every(fn ($l) => in_array($l->id, $completedLessonIds, true)),
                                'lessons'      => $chLessons->map($mapLessonItem)->values()->all(),
                            ];
                        }
                    } else {
                        $modLessons = $mod->lessons ?? collect();
                        $modSecs = (int) $modLessons->sum('duration_seconds');
                        $modMins = max(1, (int) round($modSecs / 60));
                        $chapters[] = [
                            'id'           => (int) $mod->id,
                            'title'        => $mod->title,
                            'titleKh'      => $mod->kh_title ?: $mod->title,
                            'lessonsCount' => $modLessons->count(),
                            'duration'     => $modSecs > 0 ? "{$modMins} mins" : '—',
                            'completed'    => $modLessons->isNotEmpty() && $modLessons->every(fn ($l) => in_array($l->id, $completedLessonIds, true)),
                            'lessons'      => $modLessons->map($mapLessonItem)->values()->all(),
                        ];
                    }
                }
            } elseif ($allLessons->isNotEmpty()) {
                $chapters[] = [
                    'id'           => 1,
                    'title'        => $realCourse->title,
                    'titleKh'      => $realCourse->title_kh ?: $realCourse->title,
                    'lessonsCount' => $allLessons->count(),
                    'duration'     => $durationFormatted ?: '—',
                    'completed'    => count($completedLessonIds) >= $allLessons->count(),
                    'lessons'      => $allLessons->map($mapLessonItem)->values()->all(),
                ];
            }

            // 2. Real Course Videos (course_videos table + video lessons)
            $videos = [];
            $seenVideoLessonIds = [];
            try {
                if (Schema::hasTable('course_videos')) {
                    $dbVideos = CourseVideo::where('course_id', $realCourse->id)
                        ->orderBy('id')
                        ->get();

                    foreach ($dbVideos as $cv) {
                        if ($cv->lesson_id) {
                            $seenVideoLessonIds[] = (int) $cv->lesson_id;
                        }
                        $vSecs = (int) ($cv->duration_seconds ?? 0);
                        $vDur = $cv->duration ?: ($vSecs > 0 ? sprintf('%02d:%02d', intdiv($vSecs, 60), $vSecs % 60) : '—');
                        $vUrl = $cv->cloudinary_url ?: ($cv->video_path ? asset('storage/' . ltrim((string) $cv->video_path, '/')) : null);

                        $videos[] = [
                            'id'              => 'cv-' . $cv->id,
                            'lessonId'        => $cv->lesson_id ? (int) $cv->lesson_id : null,
                            'title'           => $cv->title,
                            'titleKh'         => $cv->title,
                            'duration'        => $vDur,
                            'durationSeconds' => $vSecs,
                            'status'          => $cv->status ?: 'ready',
                            'url'             => $vUrl,
                            'lessonHref'      => $cv->lesson_id ? "/student/my-courses/{$realCourse->id}/lessons/{$cv->lesson_id}" : null,
                            'source'          => 'course_videos',
                        ];
                    }
                }
            } catch (\Throwable $e) {
            }

            foreach ($allLessons as $l) {
                if (in_array((int) $l->id, $seenVideoLessonIds, true)) {
                    continue;
                }
                if (($l->type ?: 'video') === 'video' || !empty($l->video_url)) {
                    $vSecs = (int) ($l->duration_seconds ?? 0);
                    $videos[] = [
                        'id'              => 'lv-' . $l->id,
                        'lessonId'        => (int) $l->id,
                        'title'           => $l->title,
                        'titleKh'         => $l->kh_title ?: $l->title,
                        'duration'        => $vSecs > 0 ? sprintf('%02d:%02d', intdiv($vSecs, 60), $vSecs % 60) : '—',
                        'durationSeconds' => $vSecs,
                        'status'          => 'ready',
                        'url'             => $l->video_url ?: null,
                        'lessonHref'      => "/student/my-courses/{$realCourse->id}/lessons/{$l->id}",
                        'source'          => 'lesson',
                    ];
                }
            }

            // 3. Real Course Materials (course_materials + lesson_resources)
            $materials = [];
            try {
                if (Schema::hasTable('course_materials')) {
                    $dbMaterials = CourseMaterial::where('course_id', $realCourse->id)
                        ->orderBy('id')
                        ->get();
                    foreach ($dbMaterials as $cm) {
                        $mUrl = $cm->file_url;
                        if (!$mUrl && $cm->file_path) {
                            $fp = trim((string) $cm->file_path);
                            $mUrl = (str_starts_with($fp, 'http://') || str_starts_with($fp, 'https://') || str_starts_with($fp, '/'))
                                ? $fp
                                : asset('storage/' . ltrim($fp, '/'));
                        }
                        $materials[] = [
                            'id'            => 'cm-' . $cm->id,
                            'title'         => $cm->title,
                            'type'          => strtoupper((string) ($cm->type ?: 'PDF')),
                            'fileName'      => $cm->file_name ?: $cm->title,
                            'fileSize'      => $cm->file_size ?: null,
                            'downloadCount' => (int) ($cm->download_count ?? 0),
                            'downloadUrl'   => $mUrl,
                            'source'        => 'course_materials',
                        ];
                    }
                }
            } catch (\Throwable $e) {
            }

            try {
                if (!empty($lessonIds) && Schema::hasTable('lesson_resources')) {
                    $dbResources = LessonResource::whereIn('lesson_id', $lessonIds)
                        ->orderBy('sort_order')
                        ->orderBy('id')
                        ->get();
                    foreach ($dbResources as $lr) {
                        $sizeLabel = null;
                        if ((int) ($lr->size_bytes ?? 0) > 0) {
                            $kb = round(((int) $lr->size_bytes) / 1024, 1);
                            $sizeLabel = $kb >= 1024 ? round($kb / 1024, 2) . ' MB' : $kb . ' KB';
                        }
                        $materials[] = [
                            'id'            => 'lr-' . $lr->id,
                            'title'         => $lr->title,
                            'type'          => strtoupper((string) (pathinfo((string) ($lr->original_name ?: $lr->disk_path), PATHINFO_EXTENSION) ?: 'FILE')),
                            'fileName'      => $lr->original_name ?: $lr->title,
                            'fileSize'      => $sizeLabel,
                            'downloadCount' => 0,
                            'downloadUrl'   => "/student/lessons/{$lr->lesson_id}/resources/{$lr->id}",
                            'source'        => 'lesson_resources',
                        ];
                    }
                }
            } catch (\Throwable $e) {
            }

            // 4. Real Course Reviews (course_reviews)
            $reviewsList = [];
            $myReview = null;
            $reviewsCount = 0;
            $avgRating = null;
            try {
                if (Schema::hasTable('course_reviews')) {
                    $dbReviews = CourseReview::where('course_id', $realCourse->id)
                        ->with('user:id,name,name_kh,avatar,role')
                        ->latest('created_at')
                        ->latest('id')
                        ->get();

                    $reviewsCount = $dbReviews->count();
                    if ($reviewsCount > 0) {
                        $avgRating = round((float) $dbReviews->avg('rating'), 1);
                    }

                    foreach ($dbReviews as $rev) {
                        $isMine = $user && (int) $rev->user_id === (int) $user->id;
                        $revItem = [
                            'id'         => (int) $rev->id,
                            'userId'     => (int) $rev->user_id,
                            'userName'   => $rev->user?->name ?: 'Student',
                            'userNameKh' => $rev->user?->name_kh ?: ($rev->user?->name ?: 'និស្សិត'),
                            'userAvatar' => $rev->user?->avatar_url ?: null,
                            'rating'     => (int) $rev->rating,
                            'body'       => $rev->body,
                            'createdAt'  => $rev->created_at ? Carbon::parse($rev->created_at)->format('M d, Y') : '',
                            'isMine'     => $isMine,
                        ];
                        $reviewsList[] = $revItem;
                        if ($isMine && $myReview === null) {
                            $myReview = $revItem;
                        }
                    }
                }
            } catch (\Throwable $e) {
            }

            $isWishlisted = ($user && Schema::hasTable('course_wishlists'))
                ? CourseWishlist::where('user_id', $user->id)->where('course_id', $realCourse->id)->exists()
                : false;

            $priceVal = (float) ($realCourse->price ?? 0);
            $isFree = $priceVal <= 0;

            $thumb = $realCourse->thumbnail ? trim((string) $realCourse->thumbnail) : null;
            if ($thumb && !str_starts_with($thumb, 'http://') && !str_starts_with($thumb, 'https://') && !str_starts_with($thumb, '/')) {
                $thumb = asset('storage/' . ltrim($thumb, '/'));
            }

            $isEnrolled = $enrollment && in_array($enrollment->status, ['active', 'completed'], true);
            $overviewStatus = $totalLessons === 0
                ? 'coming_soon'
                : (($enrollment && $enrollment->status === 'completed') || ($progress['state'] ?? '') === 'completed'
                    ? 'completed'
                    : (($isEnrolled && ($progress['lessons_done'] ?? 0) > 0) ? 'in_progress' : ($isEnrolled ? 'not_started' : 'not_enrolled')));

            $courseData = [
                'id'               => (int) $realCourse->id,
                'slug'             => $realCourse->code ?: ('course-' . $realCourse->id),
                'title'            => $realCourse->title,
                'titleKh'          => $realCourse->title_kh ?: $realCourse->title,
                'subtitle'         => $realCourse->description ?: null,
                'subtitleKh'       => $realCourse->description_kh ?: ($realCourse->description ?: null),
                'bannerUrl'        => $thumb,
                'category'         => $realCourse->major?->name ?: null,
                'categoryKh'       => $realCourse->major?->name_kh ?: ($realCourse->major?->name ?: null),
                'level'            => $realCourse->level ? strtolower(trim((string) $realCourse->level)) : null,
                'levelLabel'       => $realCourse->level ? ucfirst(strtolower($realCourse->level)) : null,
                'mode'             => $realCourse->learning_mode === 'instructor_led' ? 'Instructor-Led' : 'Self-Paced',
                'duration'         => $durationFormatted,
                'progress'         => (int) ($progress['percent'] ?? 0),
                'status'           => $overviewStatus,
                'isEnrolled'       => $isEnrolled,
                'isPendingPayment' => $enrollment?->status === 'pending_payment',
                'isWishlisted'     => $isWishlisted,
                'totalLessons'     => $totalLessons,
                'completedLessons' => (int) ($progress['lessons_done'] ?? 0),
                'continueHref'     => "/student/my-courses/{$realCourse->id}/continue",
                'enrollHref'       => "/student/courses/{$realCourse->id}/enroll",
                'checkoutHref'     => "/student/payments/{$realCourse->id}/upload",
                'reviewsHref'      => "/student/courses/{$realCourse->id}/reviews",
                'price'            => $priceVal,
                'priceDisplay'     => $isFree ? 'Free' : ('$' . number_format($priceVal, 2)),
                'isFree'           => $isFree,
                'rating'           => $avgRating,
                'reviewsCount'     => $reviewsCount,
                'instructor'       => [
                    'name'    => $realCourse->teacher ? $progressService->cleanTeacherName($realCourse->teacher, $realCourse) : null,
                    'name_kh' => $realCourse->teacher ? $progressService->cleanTeacherNameKh($realCourse->teacher, $realCourse) : null,
                    'role'    => $realCourse->teacher?->qualification ?: 'Instructor',
                    'avatar'  => $realCourse->teacher?->avatar_url ?: null,
                    'bio'     => $realCourse->teacher?->expertise ?: null,
                ],
                'chapters'         => $chapters,
                'videos'           => $videos,
                'materials'        => $materials,
                'reviews'          => $reviewsList,
                'myReview'         => $myReview,
            ];
        }

        return Inertia::render('Student/Courses/Overview', [
            'courseId'   => $courseId ?? $course,
            'courseData' => $courseData,
        ]);
    }

    /**
     * Store or update a student's rating & review for a course they are enrolled in.
     */
    public function storeReview(Request $request, Course $course)
    {
        $user = $request->user();

        $enrollment = Enrollment::where('student_id', $user->id)
            ->where('course_id', $course->id)
            ->whereIn('status', ['active', 'completed'])
            ->first();

        if (!$enrollment) {
            abort(403, 'សូមចុះឈ្មោះចូលរៀនវគ្គសិក្សានេះជាមុនសិនមុននឹងវាយតម្លៃ (Must be enrolled to review this course).');
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body'   => ['nullable', 'string', 'max:1000'],
        ]);

        $cleanBody = isset($validated['body']) ? trim(strip_tags((string) $validated['body'])) : null;

        $review = CourseReview::updateOrCreate(
            [
                'user_id'   => $user->id,
                'course_id' => $course->id,
            ],
            [
                'rating' => (int) $validated['rating'],
                'body'   => $cleanBody !== '' ? $cleanBody : null,
            ]
        );

        if ($request->expectsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'ok'     => true,
                'review' => $review,
            ]);
        }

        return back()->with('success', app()->getLocale() === 'km'
            ? 'អរគុណសម្រាប់ការវាយតម្លៃវគ្គសិក្សា!'
            : 'Thank you for your course review!');
    }

    public function wishlist(Request $request, CourseProgressService $progressService)
    {
        $user = $request->user();
        $items = [];

        if ($user && Schema::hasTable('course_wishlists')) {
            $wishlists = \App\Models\CourseWishlist::where('user_id', $user->id)
                ->with([
                    'course' => fn ($q) => $q->where('status', 'published')
                        ->with(['teacher:id,name,name_kh,email,avatar', 'major:id,name,name_kh'])
                        ->withCount('lessons')
                        ->withSum('lessons as duration_seconds_sum', 'duration_seconds')
                        ->withCount(['enrollments' => fn ($eq) => $eq->whereIn('status', ['active', 'completed'])]),
                ])
                ->latest('created_at')
                ->get();

            $enrolledIds = Enrollment::where('student_id', $user->id)
                ->whereIn('status', ['active', 'completed'])
                ->pluck('course_id')
                ->all();

            foreach ($wishlists as $w) {
                $c = $w->course;
                if (!$c) {
                    continue;
                }
                $priceVal = (float) ($c->price ?? 0);
                $isFree = $priceVal <= 0;
                $lessonsCount = (int) ($c->lessons_count ?? 0);
                $durSec = (int) ($c->duration_seconds_sum ?? 0);
                $minsTotal = (int) round($durSec / 60);
                $hrs = intdiv($minsTotal, 60);
                $mns = $minsTotal % 60;
                $durLabel = $durSec > 0 ? ($hrs > 0 ? "{$hrs}h " . str_pad((string) $mns, 2, '0', STR_PAD_LEFT) . 'm' : "{$mns}m") : null;

                $items[] = [
                    'id'               => $c->id,
                    'title'            => $c->title,
                    'thumbnail'        => $c->thumbnail ?: null,
                    'category'         => $c->major?->name ?: null,
                    'categoryKh'       => $c->major?->name_kh ?: ($c->major?->name ?: null),
                    'teacher'          => $c->teacher ? $progressService->cleanTeacherName($c->teacher, $c) : null,
                    'mode'             => $c->learning_mode === 'instructor_led' ? 'Instructor-Led' : 'Self-Paced',
                    'price'            => $priceVal,
                    'priceDisplay'     => $isFree ? 'Free' : ('$' . number_format($priceVal, 2)),
                    'isFree'           => $isFree,
                    'lessonsCount'     => $lessonsCount,
                    'duration'         => $durLabel,
                    'enrollmentsCount' => (int) ($c->enrollments_count ?? 0),
                    'isEnrolled'       => in_array($c->id, $enrolledIds, true),
                    'isComingSoon'     => $lessonsCount === 0,
                    'addedDate'        => $w->created_at ? \Carbon\Carbon::parse($w->created_at)->format('d M Y') : '',
                    'overviewHref'     => "/student/courses/{$c->id}/overview",
                    'continueHref'     => "/student/my-courses/{$c->id}/continue",
                ];
            }
        }

        return Inertia::render('Student/MyCourses/Wishlist', [
            'wishlistedCourses' => $items,
        ]);
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
