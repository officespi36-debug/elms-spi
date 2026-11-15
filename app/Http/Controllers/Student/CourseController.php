<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Services\CertificateService;
use App\Services\StudentLearningService;
use Illuminate\Http\Request;
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

    public function enrolled(Request $request, StudentLearningService $learningService)
    {
        $user = $request->user();
        $enrollments = Enrollment::where('student_id', $user->id)
            ->with([
                'course.teacher:id,name,avatar',
                'course.major:id,name',
                'course.modules.lessons'
            ])
            ->latest('updated_at')
            ->get();

        $enrolledList = $enrollments->map(function ($enr) use ($user) {
            $c = $enr->course;
            if (!$c) return null;

            $lessons = collect();
            if ($c->modules && $c->modules->count() > 0) {
                foreach ($c->modules as $mod) {
                    if ($mod->lessons) {
                        foreach ($mod->lessons as $l) {
                            $lessons->push($l);
                        }
                    }
                }
            }
            if ($lessons->isEmpty()) {
                $lessons = $c->lessons ?: collect();
            }

            $lessonIds = $lessons->pluck('id')->all();
            $totalLessons = $lessons->count();

            $completedCount = LessonProgress::where('user_id', $user->id)
                ->whereIn('lesson_id', $lessonIds)
                ->where(fn($q) => $q->where('percent', '>=', 90)->orWhereNotNull('completed_at'))
                ->count();

            $progress = $totalLessons > 0 ? (int) round(($completedCount / $totalLessons) * 100) : 0;
            if ($enr->status === 'completed' || $progress >= 100) {
                $status = 'completed';
                $progress = 100;
            } elseif ($progress > 0 || $enr->status === 'active') {
                $status = 'in_progress';
            } else {
                $status = 'not_started';
            }

            $nextLesson = $lessons->first(function ($l) use ($user) {
                $p = LessonProgress::where('user_id', $user->id)->where('lesson_id', $l->id)->first();
                return !$p || ($p->percent < 90 && !$p->completed_at);
            }) ?: $lessons->first();

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

            return [
                'id'                 => $c->id,
                'slug'               => $c->code ?: ('course-' . $c->id),
                'title'              => $c->title,
                'category'           => $c->major?->name ?: 'Academic Studies',
                'status'             => $status,
                'statusLabel'        => $status === 'completed' ? 'Completed' : ($status === 'in_progress' ? 'In Progress' : 'Not Started'),
                'progress'           => $progress,
                'teacher'            => [
                    'name'   => $c->teacher?->name ?: 'Dr. Mengly Chea',
                    'avatar' => $c->teacher?->avatar ?: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=120&auto=format&fit=crop&q=80',
                    'role'   => 'SPI Instructor',
                ],
                'currentChapter'     => $c->modules?->first()?->title ?: 'Module 1',
                'completedChapters'  => (int) round(($c->modules?->count() ?: 1) * ($progress / 100)),
                'totalChapters'      => $c->modules?->count() ?: 1,
                'currentLesson'      => $nextLesson?->title ?: 'Lesson 1',
                'completedLessons'   => $completedCount,
                'totalLessons'       => $totalLessons,
                'remainingTime'      => $progress >= 100 ? 'Completed' : 'Study in progress',
                'lastAccessed'       => $enr->updated_at ? $enr->updated_at->diffForHumans() : 'Recently',
                'lastAccessedTimeMs' => $enr->updated_at ? $enr->updated_at->getTimestamp() * 1000 : 0,
                'enrolledDate'       => $enr->enrolled_at ? $enr->enrolled_at->format('Y-m-d') : ($enr->created_at ? $enr->created_at->format('Y-m-d') : '2026-08-01'),
                'overviewHref'       => "/student/courses/{$c->id}/overview",
                'learningHref'       => $nextLesson ? "/student/my-courses/{$c->id}/lessons/{$nextLesson->id}" : "/student/courses/{$c->id}/overview",
                'certificateHref'    => '/student/certificates/my-certificates',
                'themeColor'         => $themeColor,
                'accentGradient'     => $gradient,
                'illustrationType'   => $illus,
            ];
        })->filter()->values();

        return Inertia::render('Student/MyCourses/EnrolledCourses', [
            'enrollments' => $enrolledList,
        ]);
    }

    /**
     * Display current/next lesson player of the active enrolled course.
     */
    public function current(Request $request, StudentLearningService $learningService)
    {
        $user = $request->user();
        $current = $learningService->resolveCurrentLesson($user);

        if (!$current || !isset($current['course'], $current['lesson'])) {
            $defaultCourse = Course::where('status', 'published')
                ->whereHas('lessons')
                ->first()
                ?: Course::where('status', 'published')->first();

            if ($defaultCourse) {
                Enrollment::firstOrCreate(
                    ['student_id' => $user->id, 'course_id' => $defaultCourse->id],
                    ['status' => 'active', 'enrolled_at' => now()]
                );
                $current = $learningService->resolveCurrentLesson($user, $defaultCourse);
            }
        }

        if ($current && isset($current['course'], $current['lesson'])) {
            return $this->showLesson($request, $current['course'], $current['lesson'], $learningService);
        }

        return redirect()->route('student.browse')
            ->with('info', 'សូមចុះឈ្មោះវគ្គសិក្សាដើម្បីចាប់ផ្តើមរៀន (Please enroll in a course to start learning).');
    }

    /**
     * Show Lesson Player connected to real course and database.
     */
    public function showLesson(Request $request, Course $course, Lesson $lesson, StudentLearningService $learningService)
    {
        $user = $request->user();

        // 1. Authorization: Ensure enrollment exists (auto-enroll if student is viewing a published course)
        $enrollment = Enrollment::where('student_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if (!$enrollment) {
            $enrollment = Enrollment::firstOrCreate(
                ['student_id' => $user->id, 'course_id' => $course->id],
                ['status' => 'active', 'enrolled_at' => now()]
            );
        }

        // 2. Fetch full curriculum with sequential lock states
        $curriculum = $learningService->getCourseCurriculum($user, $course);
        $allLessons = collect($curriculum['lessons']);

        $lessonItem = $allLessons->firstWhere('id', $lesson->id);
        if (!$lessonItem) {
            abort(404, 'Lesson not found in this course.');
        }

        // 3. Lock enforcement: If lesson is locked, redirect to current unlocked lesson
        if ($lessonItem['is_locked']) {
            $unlockedLesson = $allLessons->first(fn($l) => !$l['is_locked'] && !$l['is_completed'])
                ?: $allLessons->firstWhere('is_locked', false)
                ?: $allLessons->first();

            return redirect()->to("/student/my-courses/{$course->id}/lessons/{$unlockedLesson['id']}")
                ->with('warning', 'សូមបញ្ចប់មេរៀនមុនសិន (Complete previous lesson first).');
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

        $learningService->invalidateCourseCache($user->id, $course->id);

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

        return response()->json([
            'ok'                      => true,
            'lesson_id'               => $lesson->id,
            'is_completed'            => true,
            'course_progress_percent' => $courseProgressPercent,
            'completed_lessons_count' => $completedCount,
            'total_lessons_count'     => $totalCount,
            'is_course_completed'     => $isCourseCompleted,
            'next_lesson_url'         => $nextLessonItem ? "/student/my-courses/{$course->id}/lessons/{$nextLessonItem['id']}" : null,
            'next_lesson_id'          => $nextLessonItem['id'] ?? null,
            'message'                 => 'មេរៀនត្រូវបានបញ្ចប់ដោយជោគជ័យ! (Lesson completed successfully)',
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
        return Inertia::render('Student/Courses/Overview', [
            'courseId' => $course,
        ]);
    }

    public function wishlist(Request $request)
    {
        return Inertia::render('Student/MyCourses/Wishlist');
    }
}
