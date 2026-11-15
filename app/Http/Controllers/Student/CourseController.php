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

    public function enrolled(Request $request)
    {
        $enrollments = Enrollment::where('student_id', $request->user()->id)
            ->with(['course.teacher:id,name,avatar', 'course.major:id,name'])
            ->latest('updated_at')
            ->get();

        return Inertia::render('Student/MyCourses/EnrolledCourses', [
            'enrollments' => $enrollments,
        ]);
    }

    /**
     * Redirect to the current/next lesson of the active enrolled course (302).
     */
    public function current(Request $request, StudentLearningService $learningService)
    {
        $user = $request->user();
        $current = $learningService->resolveCurrentLesson($user);

        if ($current && isset($current['course'], $current['lesson'])) {
            return redirect()->to("/student/my-courses/{$current['course']->id}/lessons/{$current['lesson']->id}", 302);
        }

        return redirect()->route('student.my-courses.enrolled')
            ->with('info', 'សូមចុះឈ្មោះវគ្គសិក្សាដើម្បីចាប់ផ្តើមរៀន (Please enroll in a course to start learning).');
    }

    /**
     * Show Lesson Player connected to real course and database.
     */
    public function showLesson(Request $request, Course $course, Lesson $lesson, StudentLearningService $learningService)
    {
        $user = $request->user();

        // 1. Authorization: Only enrolled students can view the lesson
        $enrollment = Enrollment::where('student_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if (!$enrollment) {
            abort(403, 'You are not enrolled in this course.');
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

        $incomingSeconds = (int) $data['seconds'];
        $duration = (int) ($data['duration'] ?: ($lesson->duration_seconds ?: 1100));

        // Prevent invalid values beyond duration
        if ($duration > 0 && $incomingSeconds > ($duration + 15)) {
            return response()->json([
                'error' => 'Watched seconds cannot exceed video duration.'
            ], 422);
        }

        $prog = LessonProgress::firstOrNew([
            'user_id'   => $user->id,
            'lesson_id' => $lesson->id,
        ]);

        $existingSeconds = (int) ($prog->seconds_watched ?? 0);
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
        $enrollments = Enrollment::where('student_id', $request->user()->id)
            ->where('status', 'completed')
            ->with(['course.teacher:id,name,avatar', 'course.major:id,name'])
            ->latest('updated_at')
            ->get();

        return Inertia::render('Student/MyCourses/CompletedCourses', [
            'enrollments' => $enrollments,
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
