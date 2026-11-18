<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Support\Collection;

class ContinueLearningService
{
    public function __construct(
        protected StudentLearningService $studentLearningService
    ) {}

    /**
     * Resolve the active learning target for a user in a specific course or across their enrollments.
     *
     * Rules:
     * 1. Active enrollments only (status = 'active').
     * 2. Order of courses:
     *    - If course is provided, use that course.
     *    - Otherwise, find the course with the most recently updated lesson progress.
     *    - If no progress exists, pick the active enrollment with the most recent enrolled_at / created_at.
     * 3. First incomplete lesson:
     *    - Modules ordered by `order asc`, lessons ordered by `order asc`.
     *    - Skip unpublished courses.
     *    - A lesson is incomplete if completed_at is null AND percent < 90.
     * 4. If all lessons are completed:
     *    - Returns 'all_completed' => true.
     */
    public function resolve(User $user, ?Course $course = null): ?array
    {
        $targetCourse = $course;

        if ($targetCourse) {
            // Verify enrollment without silent auto-enrolling
            $enrollment = Enrollment::where('student_id', $user->id)
                ->where('course_id', $targetCourse->id)
                ->first();

            if (!$enrollment) {
                return null;
            }

            if ($enrollment->status !== 'active') {
                // If enrolled and completed, return real lesson counts from curriculum
                if ($enrollment->status === 'completed') {
                    $curriculum = $this->studentLearningService->getCourseCurriculum($user, $targetCourse);
                    $allLessons = collect($curriculum['lessons'] ?? []);
                    $totalLessons = $allLessons->count();
                    $completedLessons = $allLessons->where('is_completed', true)->count();
                    if ($completedLessons === 0 && $totalLessons > 0) {
                        $completedLessons = $totalLessons;
                    }
                    $lastLesson = $allLessons->last();
                    $lastLessonModel = $lastLesson ? Lesson::find($lastLesson['id']) : null;

                    return [
                        'course'                  => $targetCourse,
                        'lesson'                  => $lastLessonModel,
                        'lesson_item'             => $lastLesson,
                        'all_completed'           => true,
                        'progress_percent'        => 100,
                        'completed_lessons_count' => $completedLessons,
                        'total_lessons_count'     => $totalLessons,
                        'time_left'               => 'Completed',
                        'next_lesson_url'         => "/student/courses/{$targetCourse->id}/overview",
                        'curriculum'              => $curriculum,
                    ];
                }
                return null;
            }
        } else {
            // Find active or completed enrollments (prioritize active courses first)
            $allEnrollments = Enrollment::where('student_id', $user->id)
                ->whereIn('status', ['active', 'completed'])
                ->whereHas('course', function ($q) {
                    $q->where('status', 'published');
                })
                ->with('course')
                ->get();

            if ($allEnrollments->isEmpty()) {
                return null;
            }

            $activeEnrollments = $allEnrollments->where('status', 'active');
            $candidateEnrollments = $activeEnrollments->isNotEmpty() ? $activeEnrollments : $allEnrollments;

            $courseIds = $candidateEnrollments->pluck('course_id')->all();

            // Find latest progress among candidate courses
            $latestProgress = LessonProgress::where('user_id', $user->id)
                ->whereHas('lesson', function ($q) use ($courseIds) {
                    $q->whereIn('course_id', $courseIds);
                })
                ->latest('updated_at')
                ->with('lesson.course')
                ->first();

            if ($latestProgress && $latestProgress->lesson && $latestProgress->lesson->course) {
                $targetCourse = $latestProgress->lesson->course;
            } else {
                // Fallback to the latest enrolled course
                $latestEnrollment = $candidateEnrollments->sortByDesc(function ($enr) {
                    return $enr->enrolled_at ? $enr->enrolled_at->timestamp : ($enr->created_at ? $enr->created_at->timestamp : 0);
                })->first();

                $targetCourse = $latestEnrollment?->course;
            }

            if (!$targetCourse) {
                return null;
            }
        }

        // Get full curriculum and lock states using StudentLearningService
        $curriculum = $this->studentLearningService->getCourseCurriculum($user, $targetCourse);
        $allLessons = collect($curriculum['lessons'] ?? []);

        if ($allLessons->isEmpty()) {
            return [
                'course'                  => $targetCourse,
                'lesson'                  => null,
                'all_completed'           => false,
                'progress_percent'        => 0,
                'completed_lessons_count' => 0,
                'total_lessons_count'     => 0,
                'next_lesson_url'         => "/student/courses/{$targetCourse->id}/overview",
                'curriculum'              => $curriculum,
            ];
        }

        // Find the first incomplete lesson
        $firstIncomplete = $allLessons->first(function ($l) {
            return empty($l['is_completed']);
        });

        $totalLessons = $allLessons->count();
        $completedLessons = $allLessons->where('is_completed', true)->count();
        $progressPercent = $totalLessons > 0 ? (int) round(($completedLessons / $totalLessons) * 100) : 0;

        if (!$firstIncomplete) {
            // All lessons completed in this course
            $lastLesson = $allLessons->last();
            $lastLessonModel = $lastLesson ? Lesson::find($lastLesson['id']) : null;

            return [
                'course'                  => $targetCourse,
                'lesson'                  => $lastLessonModel,
                'lesson_item'             => $lastLesson,
                'all_completed'           => true,
                'progress_percent'        => 100,
                'completed_lessons_count' => $completedLessons,
                'total_lessons_count'     => $totalLessons,
                'time_left'               => 'Completed',
                'next_lesson_url'         => "/student/courses/{$targetCourse->id}/overview",
                'curriculum'              => $curriculum,
            ];
        }

        $lessonModel = Lesson::find($firstIncomplete['id']);
        if (!$lessonModel) {
            return null;
        }

        $watchedSeconds = (int) ($firstIncomplete['watched_seconds'] ?? 0);
        $durationSeconds = (int) ($firstIncomplete['duration_seconds'] ?? 0);
        $secondsLeft = max(0, $durationSeconds - $watchedSeconds);

        return [
            'course'                  => $targetCourse,
            'lesson'                  => $lessonModel,
            'lesson_item'             => $firstIncomplete,
            'all_completed'           => false,
            'watched_seconds'         => $watchedSeconds,
            'duration_seconds'        => $durationSeconds,
            'time_left'               => $this->studentLearningService->formatTimeLeft($secondsLeft),
            'progress_percent'        => $progressPercent,
            'completed_lessons_count' => $completedLessons,
            'total_lessons_count'     => $totalLessons,
            'next_lesson_url'         => "/student/my-courses/{$targetCourse->id}/lessons/{$lessonModel->id}",
            'curriculum'              => $curriculum,
        ];
    }

    /**
     * Find the first incomplete lesson model for a user in a given course.
     */
    public function firstIncomplete(User $user, Course $course): ?Lesson
    {
        $resolved = $this->resolve($user, $course);
        if ($resolved && !empty($resolved['lesson']) && empty($resolved['all_completed'])) {
            return $resolved['lesson'];
        }
        return null;
    }

    /**
     * Get the next sequential lesson in the course after a given lesson.
     */
    public function getNextLesson(User $user, Course $course, Lesson $currentLesson): ?Lesson
    {
        $curriculum = $this->studentLearningService->getCourseCurriculum($user, $course);
        $allLessons = collect($curriculum['lessons'] ?? []);

        $currentIndex = $allLessons->search(fn($l) => (int)$l['id'] === (int)$currentLesson->id);
        if ($currentIndex !== false && $currentIndex + 1 < $allLessons->count()) {
            $nextItem = $allLessons[$currentIndex + 1];
            return Lesson::find($nextItem['id']);
        }

        return null;
    }
}
