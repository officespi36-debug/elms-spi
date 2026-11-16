<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CourseProgressService
{
    /**
     * Clean and standardize teacher display name.
     * Prevents raw email/usernames (e.g. pitaja6820) and handles co-instructors.
     */
    public function cleanTeacherName(?User $teacher, ?Course $course = null): string
    {
        if (!$teacher) {
            return 'Dr. Mengly Chea';
        }

        $rawName = trim($teacher->name ?? '');
        $email = strtolower($teacher->email ?? '');

        // Check for test username or pitaja artifact
        if (
            empty($rawName) ||
            strcasecmp($rawName, 'pitaja6820') === 0 ||
            str_contains(strtolower($rawName), 'pitaja') ||
            str_contains($rawName, '@')
        ) {
            if (($course && (int)$course->teacher_id === 21) || (int)$teacher->id === 21 || str_contains($email, 'pitaja')) {
                return 'Dr. Mengly Chea';
            }
            return !empty($teacher->name_kh) ? trim($teacher->name_kh) : 'SPI Instructor';
        }

        return $rawName;
    }

    /**
     * Calculate single course progress for a student.
     */
    public function calculate(User $user, Course $course): array
    {
        $modules = $course->modules ?? collect();
        $lessons = collect();

        if ($modules->isNotEmpty()) {
            foreach ($modules->sortBy('order') as $mod) {
                if ($mod->lessons) {
                    foreach ($mod->lessons->sortBy('order') as $l) {
                        $lessons->push($l);
                    }
                }
            }
        }

        if ($lessons->isEmpty()) {
            $lessons = ($course->lessons ?? collect())->sortBy('order');
        }

        $lessonIds = $lessons->pluck('id')->filter()->all();
        $totalLessons = count($lessonIds);

        $progressRecords = empty($lessonIds)
            ? collect()
            : LessonProgress::where('user_id', $user->id)
                ->whereIn('lesson_id', $lessonIds)
                ->get()
                ->keyBy('lesson_id');

        return $this->buildProgressData($user, $course, $lessons, $progressRecords);
    }

    /**
     * Bulk calculate progress for a collection of enrollments in 1 single database query.
     * Eliminates N+1 queries.
     */
    public function calculateForEnrollments(User $user, Collection $enrollments): Collection
    {
        // 1. Collect all lesson IDs across all courses
        $allLessonIds = collect();
        foreach ($enrollments as $enr) {
            $c = $enr->course;
            if (!$c) continue;

            if ($c->modules && $c->modules->isNotEmpty()) {
                foreach ($c->modules as $mod) {
                    if ($mod->lessons) {
                        $allLessonIds = $allLessonIds->merge($mod->lessons->pluck('id'));
                    }
                }
            }
            if ($c->lessons && $c->lessons->isNotEmpty()) {
                $allLessonIds = $allLessonIds->merge($c->lessons->pluck('id'));
            }
        }

        $allLessonIds = $allLessonIds->unique()->filter()->values()->all();

        // 2. Fetch all progress records for this user in 1 query
        $progressRecords = empty($allLessonIds)
            ? collect()
            : LessonProgress::where('user_id', $user->id)
                ->whereIn('lesson_id', $allLessonIds)
                ->get()
                ->keyBy('lesson_id');

        // 3. Map each enrollment with computed progress
        return $enrollments->map(function ($enr) use ($user, $progressRecords) {
            $c = $enr->course;
            if (!$c) return null;

            $modules = ($c->modules ?? collect())->sortBy('order')->values();
            $lessons = collect();

            if ($modules->isNotEmpty()) {
                foreach ($modules as $mod) {
                    if ($mod->lessons) {
                        foreach ($mod->lessons->sortBy('order') as $l) {
                            $lessons->push($l);
                        }
                    }
                }
            }
            if ($lessons->isEmpty()) {
                $lessons = ($c->lessons ?? collect())->sortBy('order')->values();
            }

            $progressData = $this->buildProgressData($user, $c, $lessons, $progressRecords, $enr);
            return array_merge(['enrollment' => $enr], $progressData);
        })->filter()->values();
    }

    /**
     * Internal builder for progress data.
     */
    protected function buildProgressData(
        User $user,
        Course $course,
        Collection $lessons,
        Collection $progressRecords,
        ?Enrollment $enrollment = null
    ): array {
        $lessonIds = $lessons->pluck('id')->all();
        $totalLessons = count($lessonIds);

        $doneCount = 0;
        $anyWatched = false;
        $latestActivity = null;

        foreach ($lessonIds as $lid) {
            /** @var LessonProgress|null $p */
            $p = $progressRecords->get($lid);
            if ($p) {
                $isDone = ($p->percent >= 90 || !empty($p->completed_at));
                if ($isDone) {
                    $doneCount++;
                }
                if ($p->percent > 0 || (int)$p->seconds_watched > 0) {
                    $anyWatched = true;
                }

                $recordTime = $p->updated_at ?? $p->completed_at ?? $p->created_at;
                if ($recordTime && (!$latestActivity || Carbon::parse($recordTime)->gt($latestActivity))) {
                    $latestActivity = Carbon::parse($recordTime);
                }
            }
        }

        // Percent calculation: floor(done / total * 100); 0 if total is 0
        $percent = $totalLessons > 0 ? (int) floor(($doneCount / $totalLessons) * 100) : 0;

        // State calculation:
        // completed = done >= total && total > 0
        // in_progress = (done > 0 || anyWatched) && done < total
        // not_started = done == 0 && !anyWatched
        if ($totalLessons > 0 && $doneCount >= $totalLessons) {
            $state = 'completed';
            $percent = 100;
        } elseif ($doneCount > 0 || $anyWatched) {
            $state = 'in_progress';
        } else {
            $state = 'not_started';
            $percent = 0;
        }

        // If enrollment explicitly marked completed, ensure completed state
        if ($enrollment && $enrollment->status === 'completed') {
            $state = 'completed';
            $percent = 100;
        }

        // Next / Current lesson resolution
        $nextLesson = $lessons->first(function ($l) use ($progressRecords) {
            $p = $progressRecords->get($l->id);
            return !$p || ($p->percent < 90 && empty($p->completed_at));
        }) ?: $lessons->first();

        // Current module determination
        $modules = ($course->modules ?? collect())->sortBy('order')->values();
        $currentModuleIndex = 1;
        $currentModuleTitle = 'Module 1';
        $totalModules = max(1, $modules->count());

        if ($nextLesson && $modules->isNotEmpty()) {
            foreach ($modules as $idx => $mod) {
                if ($mod->id === $nextLesson->module_id) {
                    $currentModuleIndex = $idx + 1;
                    $currentModuleTitle = $mod->title ?: "Module " . ($idx + 1);
                    break;
                }
            }
        } elseif ($modules->isNotEmpty()) {
            $currentModuleTitle = $modules->first()->title ?: 'Module 1';
        }

        $completedModules = (int) floor($totalModules * ($percent / 100));

        // Format relative last activity
        $lastActivityRelative = $latestActivity
            ? $latestActivity->diffForHumans()
            : ($enrollment && $enrollment->enrolled_at ? $enrollment->enrolled_at->diffForHumans() : null);

        $lastActivityTimestamp = $latestActivity
            ? $latestActivity->getTimestamp() * 1000
            : ($enrollment && $enrollment->updated_at ? $enrollment->updated_at->getTimestamp() * 1000 : 0);

        return [
            'course'                 => $course,
            'lessons_total'          => $totalLessons,
            'lessons_done'           => $doneCount,
            'percent'                => $percent,
            'state'                  => $state,
            'state_label'            => $state === 'completed' ? 'Completed' : ($state === 'in_progress' ? 'In Progress' : 'Not Started'),
            'last_activity_at'       => $lastActivityRelative,
            'last_activity_ms'       => $lastActivityTimestamp,
            'current_module_index'   => $currentModuleIndex,
            'current_module_title'   => $currentModuleTitle,
            'completed_modules'      => $completedModules,
            'total_modules'          => $totalModules,
            'current_lesson'         => $nextLesson,
            'current_lesson_title'   => $nextLesson?->title ?: 'Lesson 1',
        ];
    }
}
