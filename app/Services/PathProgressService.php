<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\Enrollment;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LessonResource;
use App\Models\PathStage;
use App\Models\PathStageCourse;
use App\Models\QuizAttempt;
use App\Models\Skill;
use App\Models\StudyDaily;
use App\Models\User;
use App\Models\UserGoal;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PathProgressService
{
    protected static bool $schemaVerified = false;

    public function __construct(
        protected CourseProgressService $courseProgressService,
        protected AiTutorChatService $chatService
    ) {}

    public static function resetSchemaCache(): void
    {
        self::$schemaVerified = false;
    }

    /**
     * Invalidate cached roadmap payload for a user whenever goals, enrollments, or progress change.
     */
    public static function invalidateForUser(int $userId): void
    {
        try {
            $verKey = "student_roadmap_ver_{$userId}";
            $nextVer = ((int) Cache::get($verKey, 1)) + 1;
            Cache::forever($verKey, $nextVer);
        } catch (\Throwable $e) {
            // Non-blocking fallback
        }
    }

    /**
     * Self-healing schema check so production never crashes on missing roadmap tables.
     */
    public function ensureTablesExist(): bool
    {
        if (self::$schemaVerified) {
            return true;
        }

        try {
            $okPaths = $this->createWithFkFallback(
                'learning_paths',
                function (Blueprint $table) {
                    $table->id();
                    $table->string('slug', 120)->unique();
                    $table->foreignId('major_id')->nullable()->constrained('majors')->nullOnDelete();
                    $table->string('title', 200);
                    $table->string('title_kh', 200)->nullable();
                    $table->text('description')->nullable();
                    $table->text('description_kh')->nullable();
                    $table->unsignedTinyInteger('target_months')->default(6);
                    $table->boolean('is_published')->default(true)->index();
                    $table->timestamps();
                },
                function (Blueprint $table) {
                    $table->id();
                    $table->string('slug', 120)->unique();
                    $table->unsignedBigInteger('major_id')->nullable()->index();
                    $table->string('title', 200);
                    $table->string('title_kh', 200)->nullable();
                    $table->text('description')->nullable();
                    $table->text('description_kh')->nullable();
                    $table->unsignedTinyInteger('target_months')->default(6);
                    $table->boolean('is_published')->default(true)->index();
                    $table->timestamps();
                }
            );

            $okStages = $this->createWithFkFallback(
                'path_stages',
                function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('path_id')->constrained('learning_paths')->cascadeOnDelete();
                    $table->unsignedSmallInteger('position')->default(1);
                    $table->string('title', 200);
                    $table->string('title_kh', 200)->nullable();
                    $table->string('description', 500)->nullable();
                    $table->string('description_kh', 500)->nullable();
                    $table->timestamps();

                    $table->index(['path_id', 'position']);
                },
                function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('path_id');
                    $table->unsignedSmallInteger('position')->default(1);
                    $table->string('title', 200);
                    $table->string('title_kh', 200)->nullable();
                    $table->string('description', 500)->nullable();
                    $table->string('description_kh', 500)->nullable();
                    $table->timestamps();

                    $table->index(['path_id', 'position']);
                }
            );

            $okStageCourses = $this->createWithFkFallback(
                'path_stage_courses',
                function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('stage_id')->constrained('path_stages')->cascadeOnDelete();
                    $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                    $table->unsignedSmallInteger('position')->default(1);
                    $table->boolean('is_required')->default(true);
                    $table->timestamps();

                    $table->unique(['stage_id', 'course_id']);
                    $table->index(['stage_id', 'position']);
                },
                function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('stage_id');
                    $table->unsignedBigInteger('course_id')->index();
                    $table->unsignedSmallInteger('position')->default(1);
                    $table->boolean('is_required')->default(true);
                    $table->timestamps();

                    $table->unique(['stage_id', 'course_id']);
                    $table->index(['stage_id', 'position']);
                }
            );

            $okGoals = $this->createWithFkFallback(
                'user_goals',
                function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                    $table->foreignId('path_id')->constrained('learning_paths')->cascadeOnDelete();
                    $table->unsignedTinyInteger('target_months')->default(6);
                    $table->unsignedTinyInteger('weekly_hours')->default(10);
                    $table->timestamp('started_at')->nullable();
                    $table->timestamps();
                },
                function (Blueprint $table) {
                    $table->id();
                    $table->unsignedBigInteger('user_id')->unique();
                    $table->unsignedBigInteger('path_id')->index();
                    $table->unsignedTinyInteger('target_months')->default(6);
                    $table->unsignedTinyInteger('weekly_hours')->default(10);
                    $table->timestamp('started_at')->nullable();
                    $table->timestamps();
                }
            );

            if (!Schema::hasTable('skills')) {
                Schema::create('skills', function (Blueprint $table) {
                    $table->id();
                    $table->string('name', 100)->unique();
                    $table->string('name_kh', 100)->nullable();
                    $table->timestamps();
                });
            }
            $okSkills = Schema::hasTable('skills');

            $okCourseSkills = $this->createWithFkFallback(
                'course_skills',
                function (Blueprint $table) {
                    $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
                    $table->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();

                    $table->primary(['course_id', 'skill_id']);
                },
                function (Blueprint $table) {
                    $table->unsignedBigInteger('course_id');
                    $table->unsignedBigInteger('skill_id')->index();

                    $table->primary(['course_id', 'skill_id']);
                }
            );

            if ($okPaths && $okStages && $okStageCourses && $okGoals && $okSkills && $okCourseSkills) {
                self::$schemaVerified = true;
                return true;
            }

            return false;
        } catch (\Throwable $e) {
            Log::warning('Roadmap schema check warning: ' . $e->getMessage());
            return false;
        }
    }

    protected function createWithFkFallback(string $tableName, \Closure $withFks, \Closure $withoutFks): bool
    {
        if (Schema::hasTable($tableName)) {
            return true;
        }

        try {
            Schema::create($tableName, $withFks);
        } catch (\Throwable $e) {
            Schema::dropIfExists($tableName);
            Schema::create($tableName, $withoutFks);
        }

        return Schema::hasTable($tableName);
    }

    /**
     * Single source of truth for Recommended Roadmap (/student/ai-path/next-course).
     * Cached 60s per user+version and explicitly invalidated on goal/progress/enrollment updates.
     */
    public function getRoadmapPayload(User $user): array
    {
        if (!$this->ensureTablesExist()) {
            return $this->emptyPayload([]);
        }

        $ver = (int) Cache::get("student_roadmap_ver_{$user->id}", 1);
        $cacheKey = "student_roadmap_u{$user->id}_v{$ver}";

        return Cache::remember($cacheKey, 60, function () use ($user) {
            return $this->computeRoadmapForUser($user);
        });
    }

    protected function emptyPayload(array $publishedPaths): array
    {
        return [
            'hasPublishedPaths' => !empty($publishedPaths),
            'hasGoal'           => false,
            'goal'              => null,
            'overallProgress'   => 0,
            'counts'            => [
                'completed'  => 0,
                'inProgress' => 0,
                'upcoming'   => 0,
                'total'      => 0,
            ],
            'stages'            => [],
            'currentStageIndex' => 0,
            'currentStageId'    => null,
            'nextUp'            => null,
            'skills'            => [],
            'whyFactors'        => [],
            'recommendation'    => null,
            'publishedPaths'    => $publishedPaths,
        ];
    }

    protected function computeRoadmapForUser(User $user): array
    {
        try {
            $publishedPathModels = LearningPath::where('is_published', true)
                ->with([
                    'stages' => fn ($q) => $q->orderBy('position')->orderBy('id'),
                    'stages.stageCourses' => fn ($q) => $q->orderBy('position')->orderBy('id'),
                    'stages.stageCourses.course:id,status',
                ])
                ->orderBy('id')
                ->get();

            $publishedPaths = $publishedPathModels->map(function (LearningPath $p) {
                $seenCourseIds = [];
                $stagesCount = 0;
                foreach ($p->stages as $st) {
                    $stageHasCourse = false;
                    foreach ($st->stageCourses as $sc) {
                        if ($sc->course && $sc->course->status === 'published' && !isset($seenCourseIds[$sc->course_id])) {
                            $seenCourseIds[$sc->course_id] = true;
                            $stageHasCourse = true;
                        }
                    }
                    if ($stageHasCourse) {
                        $stagesCount++;
                    }
                }

                return [
                    'id'                    => (int) $p->id,
                    'slug'                  => (string) $p->slug,
                    'title_en'              => (string) $p->title,
                    'title_km'              => (string) ($p->title_kh ?: $p->title),
                    'desc_en'               => (string) ($p->description ?: ''),
                    'desc_km'               => (string) ($p->description_kh ?: ($p->description ?: '')),
                    'default_target_months' => (int) ($p->target_months ?: 6),
                    'stages_count'          => $stagesCount,
                    'courses_count'         => count($seenCourseIds),
                ];
            })->values()->all();

            $goal = UserGoal::where('user_id', $user->id)
                ->with([
                    'path' => fn ($q) => $q->where('is_published', true),
                    'path.stages' => fn ($q) => $q->orderBy('position')->orderBy('id'),
                    'path.stages.stageCourses' => fn ($q) => $q->orderBy('position')->orderBy('id'),
                    'path.stages.stageCourses.course' => fn ($q) => $q->where('status', 'published')->with([
                        'major',
                        'skills',
                        'materials',
                        'modules' => fn ($mq) => $mq->orderBy('order'),
                        'modules.lessons' => fn ($lq) => $lq->orderBy('order'),
                        'modules.lessons.resources',
                        'lessons' => fn ($lq) => $lq->orderBy('order'),
                        'lessons.resources',
                    ]),
                ])
                ->first();

            if (!$goal || !$goal->path || !$goal->path->is_published) {
                return $this->emptyPayload($publishedPaths);
            }

            /** @var LearningPath $path */
            $path = $goal->path;

            // Collect unique published courses across ordered stages cleanly
            $pathCourseIds = [];
            $pathLessonIds = [];
            $seenCourseIds = [];
            $stageDefinitions = [];

            foreach ($path->stages as $stage) {
                $stageCourses = [];
                foreach ($stage->stageCourses as $sc) {
                    $course = $sc->course;
                    if (!$course || $course->status !== 'published') {
                        continue;
                    }
                    if (isset($seenCourseIds[$course->id])) {
                        continue;
                    }
                    $seenCourseIds[$course->id] = true;
                    $pathCourseIds[] = (int) $course->id;

                    foreach ($this->collectOrderedCourseLessons($course) as $lesson) {
                        $pathLessonIds[] = (int) $lesson->id;
                    }

                    $stageCourses[] = [
                        'stageCourse' => $sc,
                        'course'      => $course,
                    ];
                }

                if (!empty($stageCourses)) {
                    $stageDefinitions[] = [
                        'stage'   => $stage,
                        'entries' => $stageCourses,
                    ];
                }
            }

            $pathLessonIds = array_values(array_unique($pathLessonIds));

            // Bulk-fetch student's enrollments & lesson progress (zero N+1)
            $enrollmentsByCourse = empty($pathCourseIds)
                ? collect()
                : Enrollment::where('student_id', $user->id)
                    ->whereIn('course_id', $pathCourseIds)
                    ->whereIn('status', ['active', 'completed'])
                    ->get()
                    ->keyBy('course_id');

            $progressByLesson = empty($pathLessonIds)
                ? collect()
                : LessonProgress::where('user_id', $user->id)
                    ->whereIn('lesson_id', $pathLessonIds)
                    ->get()
                    ->keyBy('lesson_id');

            // Compute official CourseProgressService metrics for enrolled courses in 1 pass
            $enrolledCourseProgressMap = [];
            if ($enrollmentsByCourse->isNotEmpty()) {
                $coursesById = [];
                foreach ($stageDefinitions as $sd) {
                    foreach ($sd['entries'] as $entry) {
                        $c = $entry['course'];
                        $coursesById[$c->id] = $c;
                    }
                }
                foreach ($enrollmentsByCourse as $enr) {
                    if (isset($coursesById[$enr->course_id])) {
                        $enr->setRelation('course', $coursesById[$enr->course_id]);
                    }
                }
                $calculated = $this->courseProgressService->calculateForEnrollments($user, $enrollmentsByCourse->values());
                foreach ($calculated as $calcItem) {
                    $enrolledCourseProgressMap[(int) $calcItem['course']->id] = $calcItem;
                }
            }

            $builtStages = [];
            $flatCourses = [];
            $totalDoneLessonsPath = 0;
            $totalLessonsPath = 0;
            $skillsById = [];

            foreach ($stageDefinitions as $sIdx => $sd) {
                /** @var PathStage $stage */
                $stage = $sd['stage'];
                $stageNumber = $sIdx + 1;

                $stageCoursesOut = [];
                $stageDoneLessons = 0;
                $stageTotalLessons = 0;
                $stageCompletedCourses = 0;
                $stageResources = [];
                $seenResourceUrls = [];

                foreach ($sd['entries'] as $entry) {
                    /** @var Course $course */
                    $course = $entry['course'];
                    /** @var PathStageCourse $sc */
                    $sc = $entry['stageCourse'];

                    $orderedLessons = $this->collectOrderedCourseLessons($course);
                    $lessonsCount = $orderedLessons->count();
                    $durationSeconds = (int) $orderedLessons->sum('duration_seconds');

                    /** @var Enrollment|null $enrollment */
                    $enrollment = $enrollmentsByCourse->get($course->id);
                    $isEnrolled = $enrollment !== null;
                    $cpItem = $enrolledCourseProgressMap[(int) $course->id] ?? null;

                    $rawDoneCount = 0;
                    foreach ($orderedLessons as $l) {
                        $lp = $progressByLesson->get($l->id);
                        if ($lp && (!empty($lp->completed_at) || (int) $lp->percent >= 90)) {
                            $rawDoneCount++;
                        }
                    }

                    if ($isEnrolled && $cpItem) {
                        $courseState = (string) ($cpItem['state'] ?? 'not_started');
                        $coursePercent = (int) ($cpItem['percent'] ?? 0);
                        /** @var Lesson|null $nextLesson */
                        $nextLesson = $cpItem['current_lesson'] ?? $orderedLessons->first();
                        $effectiveDone = $courseState === 'completed' ? $lessonsCount : $rawDoneCount;
                    } elseif ($lessonsCount === 0) {
                        $courseState = 'coming_soon';
                        $coursePercent = 0;
                        $nextLesson = null;
                        $effectiveDone = 0;
                    } else {
                        $courseState = 'not_enrolled';
                        $coursePercent = 0;
                        $nextLesson = $orderedLessons->first();
                        $effectiveDone = 0;
                    }

                    if ($courseState === 'completed') {
                        $stageCompletedCourses++;
                        $actionType = 'review';
                        $actionHref = "/student/courses/{$course->id}/overview";
                    } elseif ($courseState === 'coming_soon') {
                        $actionType = 'coming_soon';
                        $actionHref = null;
                    } elseif ($courseState === 'in_progress') {
                        $actionType = 'continue';
                        $actionHref = $nextLesson
                            ? "/student/my-courses/{$course->id}/lessons/{$nextLesson->id}"
                            : "/student/my-courses/{$course->id}/continue";
                    } elseif ($courseState === 'not_started') {
                        $actionType = 'start';
                        $actionHref = $nextLesson
                            ? "/student/my-courses/{$course->id}/lessons/{$nextLesson->id}"
                            : "/student/my-courses/{$course->id}/continue";
                    } else {
                        $actionType = 'view_details';
                        $actionHref = "/student/browse/{$course->id}";
                    }

                    $stageDoneLessons += $effectiveDone;
                    $stageTotalLessons += $lessonsCount;

                    // Collect unique DB skills for this course
                    foreach ($course->skills ?? collect() as $sk) {
                        /** @var Skill $sk */
                        if (!isset($skillsById[$sk->id])) {
                            $skillsById[$sk->id] = [
                                'id'      => (int) $sk->id,
                                'name_en' => (string) $sk->name,
                                'name_km' => (string) ($sk->name_kh ?: $sk->name),
                            ];
                        }
                    }

                    // Collect real course materials & downloadable lesson resources (no fake links)
                    if (count($stageResources) < 6) {
                        foreach ($course->materials ?? collect() as $mat) {
                            /** @var CourseMaterial $mat */
                            $url = trim((string) ($mat->file_url ?: $mat->file_path ?: ''));
                            if ($url !== '' && !isset($seenResourceUrls[$url])) {
                                $seenResourceUrls[$url] = true;
                                $stageResources[] = [
                                    'id'       => 'mat-' . $mat->id,
                                    'title'    => (string) ($mat->title ?: $course->title),
                                    'type'     => strtoupper((string) ($mat->type ?: 'material')),
                                    'course'   => (string) ($course->title_kh ?: $course->title),
                                    'url'      => $url,
                                    'external' => str_starts_with($url, 'http://') || str_starts_with($url, 'https://'),
                                ];
                            }
                            if (count($stageResources) >= 6) {
                                break;
                            }
                        }
                        foreach ($orderedLessons as $l) {
                            if (count($stageResources) >= 6) {
                                break;
                            }
                            foreach ($l->resources ?? collect() as $res) {
                                /** @var LessonResource $res */
                                if (!$res->file_path && !$res->external_url) {
                                    continue;
                                }
                                $url = $res->external_url
                                    ? (string) $res->external_url
                                    : ($isEnrolled ? "/student/lessons/{$l->id}/resources/{$res->id}" : "/student/browse/{$course->id}");
                                if (!isset($seenResourceUrls[$url])) {
                                    $seenResourceUrls[$url] = true;
                                    $stageResources[] = [
                                        'id'       => 'res-' . $res->id,
                                        'title'    => (string) $res->title,
                                        'type'     => strtoupper((string) ($res->type ?: 'resource')),
                                        'course'   => (string) ($course->title_kh ?: $course->title),
                                        'url'      => $url,
                                        'external' => (bool) $res->external_url,
                                    ];
                                }
                                if (count($stageResources) >= 6) {
                                    break;
                                }
                            }
                        }
                    }

                    $courseOut = [
                        'id'              => (int) $course->id,
                        'stageId'         => (int) $stage->id,
                        'stageNumber'     => $stageNumber,
                        'stageTitle_en'   => (string) $stage->title,
                        'stageTitle_km'   => (string) ($stage->title_kh ?: $stage->title),
                        'code'            => (string) ($course->code ?: mb_strtoupper(mb_substr($course->title, 0, 2))),
                        'title_en'        => (string) $course->title,
                        'title_km'        => (string) ($course->title_kh ?: $course->title),
                        'desc_en'         => Str::limit(trim(strip_tags((string) ($course->description ?: ''))), 140),
                        'desc_km'         => Str::limit(trim(strip_tags((string) ($course->description_kh ?: ($course->description ?: '')))), 140),
                        'level'           => strtolower((string) ($course->level ?: 'beginner')),
                        'thumbnail'       => $this->normalizeThumbnail($course->thumbnail),
                        'lessonsCount'    => $lessonsCount,
                        'doneLessons'     => $effectiveDone,
                        'durationSeconds' => $durationSeconds,
                        'duration'        => $this->formatDuration($durationSeconds),
                        'progress'        => $coursePercent,
                        'state'           => $courseState,
                        'isEnrolled'      => $isEnrolled,
                        'isRequired'      => (bool) $sc->is_required,
                        'actionType'      => $actionType,
                        'actionHref'      => $actionHref,
                        'overviewHref'    => "/student/courses/{$course->id}/overview",
                        'browseHref'      => "/student/browse/{$course->id}",
                    ];

                    $stageCoursesOut[] = $courseOut;
                    $flatCourses[] = $courseOut;
                }

                $totalDoneLessonsPath += $stageDoneLessons;
                $totalLessonsPath += $stageTotalLessons;

                $stageProgress = $stageTotalLessons > 0
                    ? (int) floor(($stageDoneLessons / $stageTotalLessons) * 100)
                    : 0;
                $isStageCompleted = !empty($stageCoursesOut) && $stageCompletedCourses === count($stageCoursesOut);

                $builtStages[] = [
                    'id'              => (int) $stage->id,
                    'stageNumber'     => $stageNumber,
                    'title_en'        => (string) $stage->title,
                    'title_km'        => (string) ($stage->title_kh ?: $stage->title),
                    'desc_en'         => (string) ($stage->description ?: ''),
                    'desc_km'         => (string) ($stage->description_kh ?: ($stage->description ?: '')),
                    'status'          => $isStageCompleted ? 'COMPLETED' : 'RECOMMENDED_ORDER',
                    'isCompleted'     => $isStageCompleted,
                    'completedCount'  => $stageCompletedCourses,
                    'totalCount'      => count($stageCoursesOut),
                    'doneLessons'     => $stageDoneLessons,
                    'totalLessons'    => $stageTotalLessons,
                    'progress'        => $stageProgress,
                    'courses'         => $stageCoursesOut,
                    'resources'       => $stageResources,
                ];
            }

            // Determine Current Stage = first stage that is NOT completed
            $currentStageIndex = 0;
            $currentStageId = $builtStages[0]['id'] ?? null;
            $foundCurrent = false;
            foreach ($builtStages as $idx => &$st) {
                if (!$st['isCompleted'] && !$foundCurrent) {
                    $st['status'] = 'CURRENT';
                    $currentStageIndex = $idx;
                    $currentStageId = $st['id'];
                    $foundCurrent = true;
                } elseif (!$st['isCompleted']) {
                    $st['status'] = 'RECOMMENDED_ORDER';
                }
            }
            unset($st);

            // If all stages completed, point active index to last stage
            if (!$foundCurrent && !empty($builtStages)) {
                $currentStageIndex = count($builtStages) - 1;
                $currentStageId = $builtStages[$currentStageIndex]['id'];
            }

            // Overall Progress = floor(sum doneLessons / sum totalLessons * 100)
            $overallProgress = $totalLessonsPath > 0
                ? (int) floor(($totalDoneLessonsPath / $totalLessonsPath) * 100)
                : 0;

            // Single-source Counts strictly from $flatCourses (guarantees Completed + In Progress + Upcoming === Total)
            $completedCoursesCount = collect($flatCourses)->where('state', 'completed')->count();
            $inProgressCoursesCount = collect($flatCourses)->where('state', 'in_progress')->count();
            $totalCoursesCount = count($flatCourses);
            $upcomingCoursesCount = max(0, $totalCoursesCount - $completedCoursesCount - $inProgressCoursesCount);

            $startedAt = $goal->started_at ?: ($goal->created_at ?: now());
            $targetDate = Carbon::parse($startedAt)->addMonths((int) $goal->target_months);

            $goalOut = [
                'pathId'        => (int) $path->id,
                'title_en'      => (string) $path->title,
                'title_km'      => (string) ($path->title_kh ?: $path->title),
                'desc_en'       => (string) ($path->description ?: ''),
                'desc_km'       => (string) ($path->description_kh ?: ($path->description ?: '')),
                'targetMonths'  => (int) $goal->target_months,
                'weeklyHours'   => (int) $goal->weekly_hours,
                'startedAtIso'  => Carbon::parse($startedAt)->toDateString(),
                'targetDate_en' => $targetDate->format('M d, Y'),
                'targetDate_km' => $targetDate->format('d/m/Y'),
                'doneLessons'   => $totalDoneLessonsPath,
                'totalLessons'  => $totalLessonsPath,
            ];

            // Rule-based Next Up + AI/Rule Explanation & "Why this roadmap?" Factors
            $nextUp = $this->resolveRuleCandidate($flatCourses, $currentStageId);
            $whyFactors = $this->buildWhyFactors($user, $goalOut, $flatCourses, $totalDoneLessonsPath, $totalLessonsPath);
            $recommendation = $this->resolveRecommendation($user, $path, $flatCourses, $nextUp);

            return [
                'hasPublishedPaths' => !empty($publishedPaths),
                'hasGoal'           => true,
                'goal'              => $goalOut,
                'overallProgress'   => $overallProgress,
                'counts'            => [
                    'completed'  => $completedCoursesCount,
                    'inProgress' => $inProgressCoursesCount,
                    'upcoming'   => $upcomingCoursesCount,
                    'total'      => $totalCoursesCount,
                ],
                'stages'            => $builtStages,
                'currentStageIndex' => $currentStageIndex,
                'currentStageId'    => $currentStageId,
                'nextUp'            => $nextUp,
                'skills'            => array_values($skillsById),
                'whyFactors'        => $whyFactors,
                'recommendation'    => $recommendation,
                'publishedPaths'    => $publishedPaths,
            ];
        } catch (\Throwable $e) {
            Log::warning('PathProgressService computation warning: ' . $e->getMessage());
            return $this->emptyPayload([]);
        }
    }

    /**
     * Deterministic next course rule:
     * 1) Lowest-progress enrolled `in_progress` course in path
     * 2) First enrolled `not_started` course in current stage (else path)
     * 3) First `not_enrolled` course with lessons in current stage (else path)
     */
    protected function resolveRuleCandidate(array $flatCourses, ?int $currentStageId): ?array
    {
        if (empty($flatCourses)) {
            return null;
        }

        $col = collect($flatCourses);

        $inProgress = $col
            ->filter(fn (array $c) => $c['state'] === 'in_progress')
            ->sortBy('progress')
            ->first();
        if ($inProgress) {
            return $inProgress;
        }

        $notStartedInCurrent = $col->first(fn (array $c) => $c['state'] === 'not_started' && $c['stageId'] === $currentStageId);
        if ($notStartedInCurrent) {
            return $notStartedInCurrent;
        }

        $notStartedAny = $col->first(fn (array $c) => $c['state'] === 'not_started');
        if ($notStartedAny) {
            return $notStartedAny;
        }

        $unenrolledInCurrent = $col->first(fn (array $c) => $c['state'] === 'not_enrolled' && $c['stageId'] === $currentStageId);
        if ($unenrolledInCurrent) {
            return $unenrolledInCurrent;
        }

        return $col->first(fn (array $c) => $c['state'] === 'not_enrolled');
    }

    /**
     * Build recommendation card: rule-based fallback guaranteed + validated AI explanation cached 6h (shared daily quota).
     */
    protected function resolveRecommendation(User $user, LearningPath $path, array $flatCourses, ?array $ruleCandidate): ?array
    {
        if ($ruleCandidate === null) {
            if (empty($flatCourses)) {
                return null;
            }
            $allDone = collect($flatCourses)->every(fn (array $c) => $c['state'] === 'completed');
            if ($allDone) {
                $pathTitleKm = $path->title_kh ?: $path->title;
                $pathTitleEn = $path->title;
                return [
                    'courseId'      => null,
                    'courseTitle_en'=> $pathTitleEn,
                    'courseTitle_km'=> $pathTitleKm,
                    'isAiGenerated' => false,
                    'badge_en'      => 'Based on your progress',
                    'badge_km'      => 'ណែនាំតាមវឌ្ឍនភាពរបស់អ្នក',
                    'body_en'       => "Congratulations! You have completed all courses in \"{$pathTitleEn}\". Ask AI Tutor for advanced next steps.",
                    'body_km'       => "អបអរសាទរ! អ្នកបានបញ្ចប់គ្រប់វគ្គសិក្សាក្នុង «{$pathTitleKm}» ហើយ។ អ្នកអាចសួរ AI Tutor សម្រាប់ជំហានបន្ត។",
                    'actionLabel_en'=> 'Ask AI for Advice',
                    'actionLabel_km'=> 'សួរ AI ដើម្បីទទួលការណែនាំ',
                    'askAiHref'     => '/student/ai-tutor?' . http_build_query([
                        'prompt' => "I completed the entire \"{$pathTitleEn}\" roadmap. What should I practice or explore next?",
                    ]),
                ];
            }
            return null;
        }

        $selectableCourses = collect($flatCourses)
            ->filter(fn (array $c) => $c['state'] !== 'completed' && $c['state'] !== 'coming_soon')
            ->keyBy('id');

        $progressSignature = md5(json_encode(array_map(
            fn (array $c) => [$c['id'], $c['progress'], $c['state']],
            $flatCourses
        )));

        $aiCacheKey = "roadmap_ai_u{$user->id}_p{$path->id}_{$progressSignature}";
        $cachedAi = Cache::get($aiCacheKey);

        if (is_array($cachedAi) && isset($cachedAi['course_id']) && $selectableCourses->has((int) $cachedAi['course_id'])) {
            $chosen = $selectableCourses->get((int) $cachedAi['course_id']);
            return $this->formatRecommendationOutput(
                $path,
                $chosen,
                (string) $cachedAi['reason_km'],
                (string) $cachedAi['reason_en'],
                true
            );
        }

        // Try Real AI provider only if within shared daily quota
        $todayKey = Carbon::now(DashboardAnalyticsService::TIMEZONE)->toDateString();
        $usageKey = "roadmap_ai_used_{$user->id}_{$todayKey}";
        try {
            $quota = $this->chatService->getQuotaStatus($user);
            $extraUsed = (int) Cache::get($usageKey, 0);

            if (($quota['used'] + $extraUsed) < $quota['limit'] && $selectableCourses->isNotEmpty()) {
                $safePayload = [
                    'path_title' => (string) ($path->title_kh ?: $path->title),
                    'courses'    => collect($flatCourses)->map(fn (array $c) => [
                        'id'      => $c['id'],
                        'title'   => $c['title_en'],
                        'stage'   => $c['stageTitle_en'],
                        'percent' => $c['progress'],
                        'state'   => $c['state'],
                    ])->values()->all(),
                ];

                $sysPrompt = "You are the SPI E-LMS Roadmap Advisor. Choose ONE incomplete course ID strictly from `courses` in the input JSON that should be studied next (prioritize lowest-progress `in_progress`, else next `not_started` or `not_enrolled`). NEVER invent a course ID outside `courses`. Respond ONLY with JSON: {\"course_id\": int, \"reason_km\": \"1-2 concise Khmer sentences\", \"reason_en\": \"1-2 concise English sentences\"}.";
                $usrPrompt = 'Student roadmap snapshot (non-PII): ' . json_encode($safePayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                $aiResp = $this->chatService->generateStructuredOrCustomText($sysPrompt, $usrPrompt);
                $decoded = $this->parseJsonObject($aiResp['body']);
                $pickedId = isset($decoded['course_id']) && is_numeric($decoded['course_id'])
                    ? (int) $decoded['course_id']
                    : null;

                // Strictly reject hallucinated course IDs not present in the active path
                if ($pickedId !== null && $selectableCourses->has($pickedId)) {
                    $reasonKm = trim(strip_tags($this->chatService->sanitizeAssistantText((string) ($decoded['reason_km'] ?? ''))));
                    $reasonEn = trim(strip_tags($this->chatService->sanitizeAssistantText((string) ($decoded['reason_en'] ?? ''))));

                    if ($reasonKm !== '' || $reasonEn !== '') {
                        if ($reasonKm === '') {
                            $reasonKm = $reasonEn;
                        }
                        if ($reasonEn === '') {
                            $reasonEn = $reasonKm;
                        }

                        Cache::put($aiCacheKey, [
                            'course_id' => $pickedId,
                            'reason_km' => Str::limit($reasonKm, 400),
                            'reason_en' => Str::limit($reasonEn, 400),
                        ], 21600);
                        Cache::put($usageKey, $extraUsed + 1, 86400);

                        return $this->formatRecommendationOutput(
                            $path,
                            $selectableCourses->get($pickedId),
                            Str::limit($reasonKm, 400),
                            Str::limit($reasonEn, 400),
                            true
                        );
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fall through deterministically to rule-based recommendation when AI fails or key missing
        }

        // Rule-based deterministic fallback (honest non-AI badge)
        $courseKm = $ruleCandidate['title_km'];
        $courseEn = $ruleCandidate['title_en'];
        $stageKm = $ruleCandidate['stageTitle_km'];
        $stageEn = $ruleCandidate['stageTitle_en'];
        $pct = (int) $ruleCandidate['progress'];

        if ($ruleCandidate['state'] === 'in_progress') {
            $bodyKm = "ផ្តោតបន្តលើ «{$courseKm}» ក្នុងដំណាក់កាល {$stageKm} (វឌ្ឍនភាពបច្ចុប្បន្ន {$pct}%) ដើម្បីឈានទៅមុខតាមលំដាប់។";
            $bodyEn = "Focus on continuing \"{$courseEn}\" in {$stageEn} (currently {$pct}% complete) to maintain steady roadmap momentum.";
        } elseif ($ruleCandidate['isEnrolled']) {
            $bodyKm = "ចាប់ផ្តើមរៀន «{$courseKm}» ក្នុងដំណាក់កាល {$stageKm} ដែលជាជំហានបន្ទាប់នៃផែនទីសិក្សារបស់អ្នក។";
            $bodyEn = "Start \"{$courseEn}\" in {$stageEn} as your next step along this learning path.";
        } else {
            $bodyKm = "ពិនិត្យមើលព័ត៌មានវគ្គ «{$courseKm}» ក្នុងដំណាក់កាល {$stageKm} ដើម្បីចុះឈ្មោះចូលរៀនបន្ទាប់។";
            $bodyEn = "Explore \"{$courseEn}\" in {$stageEn} to review its curriculum and enroll next.";
        }

        return $this->formatRecommendationOutput($path, $ruleCandidate, $bodyKm, $bodyEn, false);
    }

    protected function formatRecommendationOutput(
        LearningPath $path,
        array $course,
        string $bodyKm,
        string $bodyEn,
        bool $isAiGenerated
    ): array {
        $promptText = sprintf(
            'I am following the "%s" roadmap (Stage %d: %s). Please advise me on "%s" (%d%% progress).',
            $path->title,
            $course['stageNumber'],
            $course['stageTitle_en'],
            $course['title_en'],
            $course['progress']
        );

        // Pass `course` parameter ONLY when enrolled so AiTutorController never returns 403
        $queryParams = array_filter([
            'course' => !empty($course['isEnrolled']) ? $course['id'] : null,
            'prompt' => $promptText,
        ]);

        return [
            'courseId'       => (int) $course['id'],
            'courseTitle_en' => (string) $course['title_en'],
            'courseTitle_km' => (string) $course['title_km'],
            'isAiGenerated'  => $isAiGenerated,
            'badge_en'       => $isAiGenerated ? 'AI' : 'Based on your progress',
            'badge_km'       => $isAiGenerated ? 'AI' : 'ណែនាំតាមវឌ្ឍនភាពរបស់អ្នក',
            'body_en'        => $bodyEn,
            'body_km'        => $bodyKm,
            'actionLabel_en' => 'Ask AI for Advice',
            'actionLabel_km' => 'សួរ AI ដើម្បីទទួលការណែនាំ',
            'askAiHref'      => '/student/ai-tutor?' . http_build_query($queryParams),
        ];
    }

    protected function buildWhyFactors(
        User $user,
        array $goalOut,
        array $flatCourses,
        int $doneLessons,
        int $totalLessons
    ): array {
        $factors = [
            [
                'key'      => 'goal',
                'label_en' => "Your Goal: {$goalOut['title_en']} ({$goalOut['targetMonths']} months, {$goalOut['weeklyHours']}h/wk)",
                'label_km' => "គោលដៅរបស់អ្នក៖ {$goalOut['title_km']} ({$goalOut['targetMonths']} ខែ, {$goalOut['weeklyHours']} ម៉ោង/សប្តាហ៍)",
            ],
        ];

        $enrolledCount = collect($flatCourses)->where('isEnrolled', true)->count();
        if ($enrolledCount > 0) {
            $pct = $totalLessons > 0 ? (int) floor(($doneLessons / $totalLessons) * 100) : 0;
            $factors[] = [
                'key'      => 'progress',
                'label_en' => "Path Progress: {$doneLessons}/{$totalLessons} lessons ({$pct}%)",
                'label_km' => "វឌ្ឍនភាពក្នុងផ្លូវសិក្សា៖ {$doneLessons}/{$totalLessons} មេរៀន ({$pct}%)",
            ];
        }

        if (Schema::hasTable('quiz_attempts')) {
            $quizStats = QuizAttempt::where('user_id', $user->id)
                ->whereNotNull('score')
                ->selectRaw('COUNT(*) as cnt, AVG(score) as avg_score')
                ->first();

            $cnt = (int) ($quizStats?->cnt ?? 0);
            if ($cnt > 0) {
                $avg = (int) round((float) ($quizStats->avg_score ?? 0));
                $factors[] = [
                    'key'      => 'quiz',
                    'label_en' => "Quiz Performance: {$avg}% average across {$cnt} " . ($cnt === 1 ? 'attempt' : 'attempts'),
                    'label_km' => "លទ្ធផលប្រឡង Quiz៖ មធ្យម {$avg}% ក្នុង {$cnt} លើក",
                ];
            }
        }

        if (Schema::hasTable('study_daily')) {
            $since = Carbon::now(DashboardAnalyticsService::TIMEZONE)->subDays(6)->toDateString();
            $weekSeconds = (int) StudyDaily::where('user_id', $user->id)
                ->where('study_date', '>=', $since)
                ->where('seconds', '>', 0)
                ->sum('seconds');

            if ($weekSeconds >= 60) {
                $timeFmt = $this->formatDuration($weekSeconds);
                $factors[] = [
                    'key'      => 'study_time',
                    'label_en' => "Recent Study Consistency: {$timeFmt} recorded this week",
                    'label_km' => "ភាពទៀងទាត់ក្នុងការសិក្សា៖ {$timeFmt} ក្នុងសប្តាហ៍នេះ",
                ];
            }
        }

        return $factors;
    }

    protected function collectOrderedCourseLessons(Course $course): Collection
    {
        $lessons = collect();
        foreach (($course->modules ?? collect())->sortBy('order') as $mod) {
            foreach (($mod->lessons ?? collect())->sortBy('order') as $l) {
                $lessons->push($l);
            }
        }
        if ($lessons->isEmpty()) {
            $lessons = ($course->lessons ?? collect())->sortBy('order')->values();
        }
        return $lessons->unique('id')->values();
    }

    protected function parseJsonObject(string $raw): array
    {
        $clean = trim($raw);
        if (preg_match('/```(?:json)?\s*(\{[\s\S]*\})\s*```/i', $clean, $m)) {
            $clean = $m[1];
        } elseif (preg_match('/(\{[\s\S]*\})/u', $clean, $m)) {
            $clean = $m[1];
        }
        $decoded = json_decode($clean, true);
        return is_array($decoded) ? $decoded : [];
    }

    protected function formatDuration(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0m';
        }
        $hours = intdiv($seconds, 3600);
        $mins = intdiv($seconds % 3600, 60);
        if ($hours > 0 && $mins > 0) {
            return "{$hours}h {$mins}m";
        }
        if ($hours > 0) {
            return "{$hours}h";
        }
        return max(1, $mins) . 'm';
    }

    protected function normalizeThumbnail(?string $thumb): ?string
    {
        $thumb = $thumb ? trim($thumb) : '';
        if ($thumb === '') {
            return null;
        }
        if (str_starts_with($thumb, 'http://') || str_starts_with($thumb, 'https://') || str_starts_with($thumb, '/')) {
            return $thumb;
        }
        return asset('storage/' . ltrim($thumb, '/'));
    }
}
