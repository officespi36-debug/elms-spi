<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class StudentLearningService
{
    /**
     * Resolve the current lesson for a student in a course or across active enrollments.
     *
     * Rules:
     * - "Current lesson" = last watched lesson (ordered by updated_at desc)
     * - If not yet watched = first uncompleted lesson
     * - Must be an enrolled course
     */
    public function resolveCurrentLesson(User $user, ?Course $course = null): ?array
    {
        // 1. Determine Course
        if ($course) {
            $isEnrolled = Enrollment::where('student_id', $user->id)
                ->where('course_id', $course->id)
                ->exists();

            if (!$isEnrolled) {
                Enrollment::firstOrCreate(
                    ['student_id' => $user->id, 'course_id' => $course->id],
                    ['status' => 'active', 'enrolled_at' => now()]
                );
            }
            $targetCourse = $course;
        } else {
            // Find most recently updated active enrollment
            $enrollment = Enrollment::where('student_id', $user->id)
                ->where('status', 'active')
                ->latest('updated_at')
                ->first();

            if (!$enrollment) {
                // Fallback to any enrollment
                $enrollment = Enrollment::where('student_id', $user->id)
                    ->latest('updated_at')
                    ->first();
            }

            // If no enrollment, auto-enroll user into primary published course
            if (!$enrollment || !$enrollment->course) {
                $defaultCourse = Course::where('status', 'published')
                    ->whereHas('lessons')
                    ->first()
                    ?: Course::where('status', 'published')->first();

                if ($defaultCourse) {
                    $enrollment = Enrollment::firstOrCreate(
                        ['student_id' => $user->id, 'course_id' => $defaultCourse->id],
                        ['status' => 'active', 'enrolled_at' => now()]
                    );
                }
            }

            if (!$enrollment || !$enrollment->course) {
                return null;
            }
            $targetCourse = $enrollment->course;
        }

        // 2. Fetch full curriculum for this course
        $curriculum = $this->getCourseCurriculum($user, $targetCourse);
        if (empty($curriculum['lessons'])) {
            // Fallback to alternative published course that has lessons
            $altCourse = Course::where('status', 'published')
                ->where('id', '!=', $targetCourse->id)
                ->whereHas('lessons')
                ->first();

            if ($altCourse) {
                Enrollment::firstOrCreate(
                    ['student_id' => $user->id, 'course_id' => $altCourse->id],
                    ['status' => 'active', 'enrolled_at' => now()]
                );
                return $this->resolveCurrentLesson($user, $altCourse);
            }
            return null;
        }

        $allLessons = collect($curriculum['lessons']);
        $progressMap = $curriculum['progress_map'];

        // 3. Find Last Watched or First Incomplete
        $currentLessonItem = null;

        // Check if there is a last watched incomplete lesson
        $lastWatchedId = $curriculum['last_watched_lesson_id'];
        if ($lastWatchedId) {
            $lastItem = $allLessons->firstWhere('id', $lastWatchedId);
            if ($lastItem && !$lastItem['is_completed']) {
                $currentLessonItem = $lastItem;
            }
        }

        // If not found, find the first incomplete unlocked lesson
        if (!$currentLessonItem) {
            $currentLessonItem = $allLessons->first(function ($item) {
                return !$item['is_completed'];
            });
        }

        // If all completed, return the last watched or first lesson
        if (!$currentLessonItem) {
            $currentLessonItem = $allLessons->firstWhere('id', $lastWatchedId) ?: $allLessons->first();
        }

        $lessonModel = Lesson::find($currentLessonItem['id']);
        if (!$lessonModel) {
            return null;
        }

        $watchedSeconds = $currentLessonItem['watched_seconds'] ?? 0;
        $durationSeconds = $currentLessonItem['duration_seconds'] ?? 1100;
        $secondsLeft = max(0, $durationSeconds - $watchedSeconds);

        return [
            'course'                  => $targetCourse,
            'lesson'                  => $lessonModel,
            'lesson_item'             => $currentLessonItem,
            'watched_seconds'         => $watchedSeconds,
            'duration_seconds'        => $durationSeconds,
            'time_left'               => $this->formatTimeLeft($secondsLeft),
            'progress_percent'        => $curriculum['overall_progress_percent'],
            'completed_lessons_count' => $curriculum['completed_lessons_count'],
            'total_lessons_count'     => $curriculum['total_lessons_count'],
            'next_lesson_url'         => "/student/my-courses/{$targetCourse->id}/lessons/{$lessonModel->id}",
            'curriculum'              => $curriculum,
        ];
    }

    /**
     * Get Course Curriculum with Eager Loading, Caching, and Sequential Lock States.
     */
    public function getCourseCurriculum(User $user, Course $course): array
    {
        $cacheKey = "course_{$course->id}_user_{$user->id}_curriculum_v5";

        return Cache::remember($cacheKey, 60, function () use ($user, $course) {
            $course->load([
                'teacher:id,name,avatar',
                'major:id,name',
                'modules' => fn($q) => $q->orderBy('order'),
                'modules.lessons' => fn($q) => $q->orderBy('order'),
            ]);

            // Gather all lessons
            $orderedLessons = collect();
            $chapters = [];

            if ($course->modules && $course->modules->count() > 0) {
                foreach ($course->modules as $modIndex => $mod) {
                    $modLessons = $mod->lessons ? $mod->lessons->sortBy('order')->values() : collect();
                    $modLessonList = [];

                    foreach ($modLessons as $l) {
                        $orderedLessons->push($l);
                        $modLessonList[] = $l;
                    }

                    $chapters[] = [
                        'id'          => $mod->id,
                        'title'       => $mod->title ?: ('Module ' . ($modIndex + 1)),
                        'kh_title'    => $mod->kh_title ?: $mod->title,
                        'description' => $mod->description,
                        'order'       => $mod->order ?? ($modIndex + 1),
                        'raw_lessons' => $modLessonList,
                    ];
                }
            }

            // Fallback if modules have no lessons
            if ($orderedLessons->isEmpty()) {
                $directLessons = $course->lessons()->orderBy('order')->get();
                $orderedLessons = $directLessons;
                if ($directLessons->isNotEmpty()) {
                    $chapters[] = [
                        'id'          => 1,
                        'title'       => $course->title . ' Lessons',
                        'kh_title'    => $course->title,
                        'description' => '',
                        'order'       => 1,
                        'raw_lessons' => $directLessons->all(),
                    ];
                }
            }

            $lessonIds = $orderedLessons->pluck('id')->all();

            // Fetch progress for this user
            $progressRecords = LessonProgress::where('user_id', $user->id)
                ->whereIn('lesson_id', $lessonIds)
                ->get()
                ->keyBy('lesson_id');

            // Find last watched by updated_at
            $lastWatched = $progressRecords->sortByDesc('updated_at')->first();
            $lastWatchedLessonId = $lastWatched ? $lastWatched->lesson_id : null;

            // Sequential lock status calculation
            // Rule: Lesson 0 is unlocked. Lesson k is unlocked if lesson k-1 is completed.
            $flattenedLessons = [];
            $completedCount = 0;
            $previousCompleted = true; // First lesson is always unlocked

            foreach ($orderedLessons as $idx => $l) {
                $prog = $progressRecords->get($l->id);
                $isCompleted = $prog && ($prog->percent >= 90 || !empty($prog->completed_at));
                $watchedSecs = $prog ? (int)$prog->seconds_watched : 0;
                $percent = $prog ? (int)$prog->percent : 0;

                if ($isCompleted) {
                    $completedCount++;
                }

                $isLocked = !$previousCompleted && ($idx > 0);

                $duration = $l->duration_seconds ?: 1100;

                $flattenedLessons[] = [
                    'id'               => $l->id,
                    'module_id'        => $l->module_id,
                    'course_id'        => $l->course_id,
                    'title'            => $l->title,
                    'kh_title'         => $l->kh_title,
                    'summary'          => $l->summary,
                    'objectives'       => $l->objectives,
                    'content'          => $l->content,
                    'preview_url'      => $l->preview_url,
                    'video_provider'   => $l->video_provider ?: ($l->video_url ? (str_contains($l->video_url, 'youtube') || str_contains($l->video_url, 'youtu.be') ? 'youtube' : 'mp4') : null),
                    'type'             => $l->type ?: 'video',
                    'video_url'        => $l->video_url,
                    'file_url'         => $l->file_url,
                    'duration_seconds' => $duration,
                    'duration_formatted' => $this->formatDuration($duration),
                    'order'            => $l->order ?? ($idx + 1),
                    'global_index'     => $idx + 1,
                    'watched_seconds'  => $watchedSecs,
                    'percent'          => $percent,
                    'is_completed'     => $isCompleted,
                    'is_locked'        => $isLocked,
                    'completed_at'     => $prog?->completed_at?->toIso8601String(),
                ];

                // For the next lesson to be unlocked, current must be completed
                $previousCompleted = $isCompleted;
            }

            $totalCount = count($flattenedLessons);
            $overallProgress = $totalCount > 0 ? (int) round(($completedCount / $totalCount) * 100) : 0;

            // Build structured chapters for UI
            $chapterItems = [];
            foreach ($chapters as $ch) {
                $chapLessonItems = [];
                $chapCompleted = 0;

                foreach ($ch['raw_lessons'] as $rl) {
                    $matched = collect($flattenedLessons)->firstWhere('id', $rl->id);
                    if ($matched) {
                        $chapLessonItems[] = $matched;
                        if ($matched['is_completed']) {
                            $chapCompleted++;
                        }
                    }
                }

                $chapTotal = count($chapLessonItems);
                if ($chapTotal === 0) {
                    continue; // Skip empty modules with 0 lessons
                }
                $isChapLocked = $chapTotal > 0 && ($chapLessonItems[0]['is_locked'] ?? false);

                $chapterItems[] = [
                    'id'          => $ch['id'],
                    'title'       => $ch['title'],
                    'kh_title'    => $ch['kh_title'],
                    'progress'    => "{$chapCompleted}/{$chapTotal}",
                    'completed_count' => $chapCompleted,
                    'total_count' => $chapTotal,
                    'is_locked'   => $isChapLocked,
                    'lessons'     => $chapLessonItems,
                ];
            }

            return [
                'course'                   => [
                    'id'          => $course->id,
                    'title'       => $course->title,
                    'code'        => $course->code,
                    'description' => $course->description,
                    'thumbnail'   => $course->thumbnail,
                    'status'      => $course->status,
                ],
                'teacher_name'             => $course->teacher?->name ?: 'SPI Instructor',
                'teacher_avatar'           => $course->teacher?->avatar,
                'major_name'               => $course->major?->name ?: 'Academic Studies',
                'lessons'                  => $flattenedLessons,
                'chapters'                 => $chapterItems,
                'progress_map'             => $progressRecords->toArray(),
                'last_watched_lesson_id'   => $lastWatchedLessonId,
                'completed_lessons_count'  => $completedCount,
                'total_lessons_count'      => $totalCount,
                'overall_progress_percent' => $overallProgress,
            ];
        });
    }

    /**
     * Clear Cache on Progress or Completion Update.
     */
    public function invalidateCourseCache(int $userId, int $courseId): void
    {
        Cache::forget("course_{$courseId}_user_{$userId}_curriculum_v5");
        Cache::forget("course_{$courseId}_user_{$userId}_curriculum_v4");
        Cache::forget("course_{$courseId}_user_{$userId}_curriculum_v3");
        Cache::forget("course_{$courseId}_user_{$userId}_curriculum_v2");
        Cache::forget("course_{$courseId}_user_{$userId}_curriculum");
        Cache::forget("student_dashboard_payload_{$userId}");
    }

    public function formatDuration(int $seconds): string
    {
        $m = floor($seconds / 60);
        $s = $seconds % 60;
        return sprintf('%02d:%02d', $m, $s);
    }

    public function formatTimeLeft(int $seconds): string
    {
        if ($seconds <= 0) {
            return '00:00 left';
        }
        $m = floor($seconds / 60);
        $s = $seconds % 60;
        return sprintf('%02d:%02d left', $m, $s);
    }
}
