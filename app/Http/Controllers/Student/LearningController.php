<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\CourseVideo;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonComment;
use App\Models\LessonNote;
use App\Models\LessonProgress;
use App\Models\LessonResource;
use App\Services\CertificateService;
use App\Services\CloudflareAIService;
use App\Services\DashboardAnalyticsService;
use App\Services\StudentLearningService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LearningController extends Controller
{
    /**
     * Helper to gather real learning content across a student's enrolled courses.
     */
    protected function buildEnrolledContentPayload($user): array
    {
        if (!$user) {
            return [
                'enrolledCourses' => [],
                'videos'          => [],
                'pdfs'            => [],
                'slides'          => [],
                'materials'       => [],
                'resources'       => [],
                'notes'           => [],
                'links'           => [],
            ];
        }

        $enrollments = Enrollment::where('student_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->whereHas('course')
            ->with('course:id,title,title_kh,code,teacher_id')
            ->get();

        $enrolledCourseIds = $enrollments->pluck('course_id')->unique()->filter()->values()->all();
        $enrolledCourses = $enrollments->map(fn ($enr) => [
            'id'       => $enr->course->id,
            'title'    => $enr->course->title,
            'title_kh' => $enr->course->title_kh,
            'code'     => $enr->course->code,
        ])->values()->all();

        if (empty($enrolledCourseIds)) {
            $notes = LessonNote::where('user_id', $user->id)
                ->with(['course:id,title', 'lesson:id,title,course_id'])
                ->latest()
                ->get()
                ->map(fn (LessonNote $n) => $this->formatLessonNote($n))
                ->values()
                ->all();

            return [
                'enrolledCourses' => [],
                'videos'          => [],
                'pdfs'            => [],
                'slides'          => [],
                'materials'       => [],
                'resources'       => [],
                'notes'           => $notes,
                'links'           => [],
            ];
        }

        $lessons = Lesson::whereIn('course_id', $enrolledCourseIds)
            ->with('course:id,title')
            ->orderBy('order')
            ->get();
        $lessonIds = $lessons->pluck('id')->all();

        $courseVideos = CourseVideo::whereIn('course_id', $enrolledCourseIds)
            ->with(['course:id,title', 'lesson:id,title,duration_seconds'])
            ->latest()
            ->get()
            ->map(fn (CourseVideo $v) => [
                'id'               => $v->id,
                'course_id'        => $v->course_id,
                'course_title'     => $v->course?->title ?? '',
                'lesson_id'        => $v->lesson_id,
                'title'            => $v->title ?: ($v->lesson?->title ?? 'Video'),
                'video_url'        => $v->cloudinary_url ?: ($v->video_path ? '/storage/' . ltrim($v->video_path, '/') : null),
                'duration'         => $v->duration,
                'duration_seconds' => (int) ($v->duration_seconds ?? $v->lesson?->duration_seconds ?? 0),
                'file_size'        => $v->file_size,
                'status'           => $v->status,
            ])
            ->values();

        // Include video lessons that may not have a separate CourseVideo record
        $videoLessons = $lessons->filter(fn (Lesson $l) => !empty($l->video_url) || $l->type === 'video')
            ->map(fn (Lesson $l) => [
                'id'               => 'lesson-' . $l->id,
                'course_id'        => $l->course_id,
                'course_title'     => $l->course?->title ?? '',
                'lesson_id'        => $l->id,
                'title'            => $l->title,
                'video_url'        => $l->video_url,
                'duration'         => $this->formatSeconds((int) ($l->duration_seconds ?? 0)),
                'duration_seconds' => (int) ($l->duration_seconds ?? 0),
                'file_size'        => null,
                'status'           => 'published',
            ])
            ->values();

        $videos = $courseVideos->isNotEmpty() ? $courseVideos->all() : $videoLessons->all();

        $allMaterials = CourseMaterial::whereIn('course_id', $enrolledCourseIds)
            ->with(['course:id,title', 'lesson:id,title'])
            ->latest()
            ->get();

        $formatMaterial = fn (CourseMaterial $m) => [
            'id'             => $m->id,
            'course_id'      => $m->course_id,
            'course_title'   => $m->course?->title ?? '',
            'lesson_id'      => $m->lesson_id,
            'lesson_title'   => $m->lesson?->title ?? '',
            'title'          => $m->title,
            'type'           => $m->type ?: 'material',
            'file_url'       => $m->file_url ?: ($m->file_path ? '/storage/' . ltrim($m->file_path, '/') : null),
            'file_name'      => $m->file_name,
            'file_size'      => $m->file_size,
            'slide_count'    => (int) ($m->slide_count ?? 0),
            'download_count' => (int) ($m->download_count ?? 0),
            'status'         => $m->status,
        ];

        $materials = $allMaterials->map($formatMaterial)->values()->all();

        $pdfs = $allMaterials->filter(function (CourseMaterial $m) {
            $t = strtolower((string) $m->type);
            $n = strtolower((string) ($m->file_name ?: $m->file_url ?: ''));
            return $t === 'pdf' || str_ends_with($n, '.pdf');
        })->map($formatMaterial)->values()->all();

        $slides = $allMaterials->filter(function (CourseMaterial $m) {
            $t = strtolower((string) $m->type);
            $n = strtolower((string) ($m->file_name ?: $m->file_url ?: ''));
            return in_array($t, ['slide', 'slides', 'presentation', 'ppt', 'pptx'], true)
                || str_ends_with($n, '.ppt')
                || str_ends_with($n, '.pptx');
        })->map($formatMaterial)->values()->all();

        $links = $allMaterials->filter(function (CourseMaterial $m) {
            $t = strtolower((string) $m->type);
            return in_array($t, ['link', 'url', 'external', 'youtube', 'github', 'doc'], true)
                || (!empty($m->file_url) && empty($m->file_path) && str_starts_with((string) $m->file_url, 'http'));
        })->map($formatMaterial)->values()->all();

        $resources = empty($lessonIds)
            ? []
            : LessonResource::whereIn('lesson_id', $lessonIds)
                ->with('lesson:id,title,course_id')
                ->orderBy('sort_order')
                ->get()
                ->map(fn (LessonResource $r) => [
                    'id'             => $r->id,
                    'lesson_id'      => $r->lesson_id,
                    'lesson_title'   => $r->lesson?->title ?? '',
                    'course_id'      => $r->lesson?->course_id,
                    'title'          => $r->title,
                    'original_name'  => $r->original_name,
                    'file_extension' => $r->file_extension,
                    'formatted_size' => $r->formatted_size,
                    'download_url'   => "/student/lessons/{$r->lesson_id}/resources/{$r->id}",
                ])
                ->values()
                ->all();

        $notes = LessonNote::where('user_id', $user->id)
            ->with(['course:id,title', 'lesson:id,title,course_id'])
            ->latest()
            ->get()
            ->map(fn (LessonNote $n) => $this->formatLessonNote($n))
            ->values()
            ->all();

        return [
            'enrolledCourses' => $enrolledCourses,
            'videos'          => $videos,
            'pdfs'            => $pdfs,
            'slides'          => $slides,
            'materials'       => $materials,
            'resources'       => $resources,
            'notes'           => $notes,
            'links'           => $links,
        ];
    }

    public function content(Request $request)
    {
        $tab = $request->query('tab', 'videos');
        $payload = $this->buildEnrolledContentPayload($request->user());

        return Inertia::render('Student/LearningContent/Index', array_merge($payload, [
            'activeTab' => $tab,
        ]));
    }

    public function videos(Request $request)
    {
        $payload = $this->buildEnrolledContentPayload($request->user());

        return Inertia::render('Student/LearningContent/Videos', [
            'videos'          => $payload['videos'],
            'notes'           => $payload['notes'],
            'resources'       => $payload['resources'],
            'enrolledCourses' => $payload['enrolledCourses'],
        ]);
    }

    public function pdfs(Request $request)
    {
        $payload = $this->buildEnrolledContentPayload($request->user());

        return Inertia::render('Student/LearningContent/Pdfs', [
            'pdfs'            => $payload['pdfs'],
            'materials'       => $payload['materials'],
            'enrolledCourses' => $payload['enrolledCourses'],
        ]);
    }

    public function slides(Request $request)
    {
        $payload = $this->buildEnrolledContentPayload($request->user());

        return Inertia::render('Student/LearningContent/Slides', [
            'slides'          => $payload['slides'],
            'enrolledCourses' => $payload['enrolledCourses'],
        ]);
    }

    public function notes(Request $request)
    {
        $payload = $this->buildEnrolledContentPayload($request->user());

        return Inertia::render('Student/LearningContent/NotesDownloads', [
            'notes'           => $payload['notes'],
            'resources'       => $payload['resources'],
            'materials'       => $payload['materials'],
            'enrolledCourses' => $payload['enrolledCourses'],
        ]);
    }

    public function links(Request $request)
    {
        $payload = $this->buildEnrolledContentPayload($request->user());

        return Inertia::render('Student/LearningContent/UrlResources', [
            'links'           => $payload['links'],
            'resources'       => $payload['resources'],
            'enrolledCourses' => $payload['enrolledCourses'],
        ]);
    }

    /**
     * Display the Fullscreen Course Learning Player (Focus Mode)
     */
    public function show(Request $request, Course $course)
    {
        $user = $request->user();

        // 1. Strict Enrollment Verification (No silent auto-enrollment)
        $enrollment = Enrollment::where('student_id', $user->id)
            ->where('course_id', $course->id)
            ->whereIn('status', ['active', 'completed'])
            ->first();

        if (!$enrollment) {
            return redirect("/student/courses/{$course->id}/overview")
                ->with('error', 'សូមចុះឈ្មោះចូលរៀនជាមុនសិនដើម្បីចូលរៀន! (Please enroll in this course first to access lessons.)');
        }

        // 2. Load Deep Course Relations with Modules & Direct Lessons
        $course->load([
            'teacher:id,name,name_kh,avatar,role',
            'major:id,name,name_kh',
            'modules' => fn ($q) => $q->orderBy('order'),
            'modules.lessons' => fn ($q) => $q->orderBy('order')->with(['materials', 'videos', 'resources', 'labIntegrations']),
            'lessons' => fn ($q) => $q->orderBy('order')->with(['materials', 'videos', 'resources', 'labIntegrations']),
            'materials',
            'quizzes',
        ]);

        // 3. Collect all lessons across modules and direct course->lessons
        $allLessons = collect();
        $seenLessonIds = [];

        if ($course->modules && $course->modules->count() > 0) {
            foreach ($course->modules as $mod) {
                if ($mod->lessons && $mod->lessons->count() > 0) {
                    foreach ($mod->lessons as $l) {
                        $allLessons->push($l);
                        $seenLessonIds[(int) $l->id] = true;
                    }
                }
            }
        }

        if ($course->lessons && $course->lessons->count() > 0) {
            foreach ($course->lessons as $dl) {
                if (!isset($seenLessonIds[(int) $dl->id])) {
                    $allLessons->push($dl);
                    $seenLessonIds[(int) $dl->id] = true;
                }
            }
        }

        $lessonIds = $allLessons->pluck('id')->filter()->values()->all();

        // 4. Fetch User's Lesson Progress Records
        $progress = empty($lessonIds)
            ? collect()
            : LessonProgress::where('user_id', $user->id)
                ->whereIn('lesson_id', $lessonIds)
                ->get()
                ->keyBy('lesson_id');

        // 5. Calculate Overall Course Progress Statistics
        $totalLessonsCount = count($lessonIds);
        $completedLessonsCount = 0;
        foreach ($lessonIds as $lId) {
            if (isset($progress[$lId]) && ($progress[$lId]->percent >= 90 || $progress[$lId]->completed_at)) {
                $completedLessonsCount++;
            }
        }

        $courseProgressPercent = $totalLessonsCount > 0
            ? (int) round(($completedLessonsCount / $totalLessonsCount) * 100)
            : 0;

        // 6. Determine the Active/Last Watched Lesson to Resume
        $requestedLessonId = $request->query('lesson_id') ?: $request->query('lesson');
        $initialLessonId = null;

        if ($requestedLessonId && in_array((int) $requestedLessonId, array_map('intval', $lessonIds), true)) {
            $initialLessonId = (int) $requestedLessonId;
        } elseif (!empty($lessonIds)) {
            $lastWatched = LessonProgress::where('user_id', $user->id)
                ->whereIn('lesson_id', $lessonIds)
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($lastWatched) {
                $initialLessonId = $lastWatched->lesson_id;
            } else {
                $firstIncomplete = $allLessons->first(function ($l) use ($progress) {
                    return !isset($progress[$l->id]) || ($progress[$l->id]->percent < 90 && !$progress[$l->id]->completed_at);
                });
                $initialLessonId = $firstIncomplete ? $firstIncomplete->id : $allLessons->first()?->id;
            }
        }

        // 7. Real Discussions (LessonComment for lessons in this course)
        $discussions = empty($lessonIds)
            ? []
            : LessonComment::whereIn('lesson_id', $lessonIds)
                ->whereNull('parent_id')
                ->with([
                    'user:id,name,name_kh,avatar,role',
                    'replies' => fn ($rq) => $rq->with(['user:id,name,name_kh,avatar,role'])->oldest(),
                ])
                ->latest()
                ->get()
                ->map(fn (LessonComment $c) => $this->formatDiscussionComment($c, $course->teacher_id))
                ->values()
                ->all();

        // 8. Real Personal Notes (LessonNote for this user & course/lessons)
        $notes = LessonNote::where('user_id', $user->id)
            ->where(function ($q) use ($course, $lessonIds) {
                $q->where('course_id', $course->id);
                if (!empty($lessonIds)) {
                    $q->orWhereIn('lesson_id', $lessonIds);
                }
            })
            ->latest()
            ->get()
            ->map(fn (LessonNote $n) => $this->formatLessonNote($n))
            ->values()
            ->all();

        // 9. Real Downloadable Resources (LessonResource + CourseMaterial)
        $lessonResources = empty($lessonIds)
            ? collect()
            : LessonResource::whereIn('lesson_id', $lessonIds)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (LessonResource $r) => [
                    'id'           => 'lr-' . $r->id,
                    'raw_id'       => $r->id,
                    'source'       => 'lesson_resource',
                    'lesson_id'    => $r->lesson_id,
                    'course_id'    => $course->id,
                    'title'        => $r->title ?: $r->original_name,
                    'type'         => $r->file_extension ?: 'FILE',
                    'size'         => $r->formatted_size,
                    'download_url' => "/student/lessons/{$r->lesson_id}/resources/{$r->id}",
                ]);

        $courseMaterials = CourseMaterial::where('course_id', $course->id)
            ->orderBy('id')
            ->get()
            ->map(function (CourseMaterial $m) {
                $url = $m->file_url ?: ($m->file_path ? '/storage/' . ltrim($m->file_path, '/') : null);
                $ext = strtoupper(pathinfo($m->file_name ?: ($m->file_path ?: ($m->file_url ?: '')), PATHINFO_EXTENSION) ?: ($m->type ?: 'FILE'));
                return [
                    'id'           => 'cm-' . $m->id,
                    'raw_id'       => $m->id,
                    'source'       => 'course_material',
                    'lesson_id'    => $m->lesson_id,
                    'course_id'    => $m->course_id,
                    'title'        => $m->title ?: ($m->file_name ?: 'Course Material'),
                    'type'         => $ext,
                    'size'         => $m->file_size ?: '',
                    'download_url' => $url,
                ];
            })
            ->filter(fn ($item) => !empty($item['download_url']));

        $resources = $lessonResources->concat($courseMaterials)->values()->all();

        return Inertia::render('Student/Learning/Show', [
            'course'                => $course,
            'progress'              => $progress,
            'enrollment'            => $enrollment,
            'initialLessonId'       => $initialLessonId,
            'courseProgressPercent' => $courseProgressPercent,
            'completedLessonsCount' => $completedLessonsCount,
            'totalLessonsCount'     => $totalLessonsCount,
            'discussions'           => $discussions,
            'notes'                 => $notes,
            'resources'             => $resources,
        ]);
    }

    /**
     * Real-time Progress Tracking API (Dispatched on 90% watched or completed)
     */
    public function updateProgress(Request $request, Lesson $lesson)
    {
        $user = $request->user();

        $data = $request->validate([
            'percent'      => 'required|integer|min:0|max:100',
            'seconds'      => 'required|integer|min:0',
            'is_completed' => 'nullable|boolean',
        ]);

        $prog = LessonProgress::firstOrNew([
            'user_id'   => $user->id,
            'lesson_id' => $lesson->id,
        ]);

        $existingSeconds = (int) ($prog->seconds_watched ?? 0);
        $newPercent = max((int) ($prog->percent ?? 0), (int) $data['percent']);
        $newSeconds = max($existingSeconds, (int) $data['seconds']);
        $isCompleted = ($newPercent >= 90) || !empty($data['is_completed']);

        if ($isCompleted) {
            $newPercent = 100;
            if (!$prog->completed_at) {
                $prog->completed_at = now();
            }
        }

        $prog->percent = $newPercent;
        $prog->seconds_watched = $newSeconds;
        $prog->client_updated_at = now();
        $prog->save();

        $deltaSeconds = max(0, min(40, $newSeconds - $existingSeconds));
        if ($deltaSeconds > 0) {
            app(DashboardAnalyticsService::class)->recordDailyDelta($user->id, 'lesson', $deltaSeconds);
        }

        $course = $lesson->course ?: Course::find($lesson->course_id);
        $courseProgress = 0;
        $allComplete = false;

        if ($course) {
            app(StudentLearningService::class)->invalidateCourseCache($user->id, $course->id);

            $courseLessonIds = $course->lessons()->pluck('id')->all();
            if (empty($courseLessonIds)) {
                $courseLessonIds = Lesson::whereHas('module', fn ($q) => $q->where('course_id', $course->id))
                    ->pluck('id')
                    ->all();
            }
            $totalCount = count($courseLessonIds);

            if ($totalCount > 0) {
                $completedCount = LessonProgress::where('user_id', $user->id)
                    ->whereIn('lesson_id', $courseLessonIds)
                    ->where(function ($q) {
                        $q->where('percent', '>=', 90)->orWhereNotNull('completed_at');
                    })
                    ->count();

                $courseProgress = (int) round(($completedCount / $totalCount) * 100);

                if ($courseProgress >= 100) {
                    $allComplete = true;
                    Enrollment::where('student_id', $user->id)
                        ->where('course_id', $course->id)
                        ->update(['status' => 'completed']);

                    try {
                        app(CertificateService::class)->issue($user, $course);
                    } catch (\Throwable $e) {
                        // Non-blocking if certificate generation encounters missing PDF template
                    }
                }
            }
        }

        app(DashboardAnalyticsService::class)->invalidateUserCache($user->id);

        return response()->json([
            'ok'                  => true,
            'lesson_id'           => $lesson->id,
            'percent'             => $prog->percent,
            'seconds_watched'     => $prog->seconds_watched,
            'is_completed'        => $isCompleted,
            'course_progress'     => $courseProgress,
            'is_course_completed' => $allComplete,
        ]);
    }

    /**
     * AI Tutor Instant Q&A in Focus Mode (Powered by Cloudflare Workers AI)
     */
    public function askAi(Request $request, Lesson $lesson, CloudflareAIService $cfAi)
    {
        $request->validate([
            'question' => 'required|string|max:1000',
        ]);

        $question = trim($request->input('question'));
        $title = $lesson->title ?: 'Current Lesson';
        $course = $lesson->course ?: Course::find($lesson->course_id);
        $courseTitle = $course ? $course->title : 'Saint Paul Institute Course';

        $messages = [
            [
                'role'    => 'system',
                'content' => "You are an intelligent AI Tutor at Saint Paul Institute. The student is currently studying: Course: '{$courseTitle}', Lesson: '{$title}'.\nProvide a helpful, pedagogical, structured explanation answering their question. Explain key points clearly with code snippets or practical steps if applicable.\nAnswer in Khmer or English matching the user's language.",
            ],
            [
                'role'    => 'user',
                'content' => $question,
            ],
        ];

        $reply = $cfAi->chat($messages);

        return response()->json([
            'success' => true,
            'reply'   => $reply,
            'time'    => now()->format('h:i A'),
        ]);
    }

    /**
     * Post Lesson Q&A Discussion (Persisted in lesson_comments table)
     */
    public function postDiscussion(Request $request, Lesson $lesson)
    {
        $user = $request->user();

        $data = $request->validate([
            'content'       => 'nullable|string|max:2000',
            'body'          => 'nullable|string|max:2000',
            'video_seconds' => 'nullable|integer|min:0',
        ]);

        $body = trim((string) ($data['content'] ?? $data['body'] ?? ''));
        if ($body === '') {
            return response()->json([
                'success' => false,
                'message' => 'សូមបញ្ចូលសំណួររបស់អ្នកជាមុនសិន។ (Question content is required.)',
            ], 422);
        }

        $comment = LessonComment::create([
            'lesson_id'     => $lesson->id,
            'user_id'       => $user->id,
            'body'          => $body,
            'video_seconds' => $data['video_seconds'] ?? null,
        ]);

        $comment->load('user:id,name,name_kh,avatar,role');
        $course = $lesson->course ?: Course::find($lesson->course_id);

        return response()->json([
            'success'    => true,
            'message'    => 'សំណួររបស់អ្នកត្រូវបានបង្ហោះជោគជ័យ!',
            'comment'    => $comment,
            'discussion' => $this->formatDiscussionComment($comment, $course?->teacher_id),
        ]);
    }

    /**
     * Save Personal Lesson Note (Persisted in lesson_notes table)
     */
    public function saveNote(Request $request, Lesson $lesson)
    {
        $user = $request->user();

        $data = $request->validate([
            'text'          => 'nullable|string|max:3000',
            'body'          => 'nullable|string|max:3000',
            'timestamp'     => 'nullable|string|max:20',
            'video_seconds' => 'nullable|integer|min:0',
        ]);

        $body = trim((string) ($data['text'] ?? $data['body'] ?? ''));
        if ($body === '') {
            return response()->json([
                'success' => false,
                'message' => 'សូមបញ្ចូលកំណត់ចំណាំរបស់អ្នក។ (Note text is required.)',
            ], 422);
        }

        $videoSeconds = isset($data['video_seconds'])
            ? (int) $data['video_seconds']
            : $this->parseTimestampToSeconds($data['timestamp'] ?? '00:00');

        $courseId = $lesson->course_id ?: $lesson->module?->course_id;

        $note = LessonNote::create([
            'user_id'       => $user->id,
            'lesson_id'     => $lesson->id,
            'course_id'     => $courseId,
            'body'          => $body,
            'video_seconds' => $videoSeconds,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'បានរក្សាទុកកំណត់ចំណាំជោគជ័យ!',
            'note'    => $this->formatLessonNote($note),
        ]);
    }

    /**
     * Delete Personal Lesson Note (Persisted in lesson_notes table)
     */
    public function deleteNote(Request $request, LessonNote $note)
    {
        $user = $request->user();
        abort_unless((int) $note->user_id === (int) $user->id, 403, 'Unauthorized action.');

        $note->delete();

        return response()->json([
            'success' => true,
            'message' => 'កំណត់ចំណាំត្រូវបានលុបជោគជ័យ!',
        ]);
    }

    protected function formatDiscussionComment(LessonComment $comment, ?int $teacherId = null): array
    {
        $replies = ($comment->relationLoaded('replies') && $comment->replies)
            ? $comment->replies->map(fn (LessonComment $rep) => [
                'id'            => $rep->id,
                'user_id'       => $rep->user_id,
                'user_name'     => $rep->user?->name ?? 'User',
                'user_name_kh'  => $rep->user?->name_kh ?? null,
                'avatar'        => $rep->user?->avatar ?? null,
                'role'          => ($teacherId && (int) $rep->user_id === (int) $teacherId) ? 'instructor' : ($rep->user?->role ?? 'student'),
                'body'          => $rep->body,
                'created_at'    => $rep->created_at?->toIso8601String(),
                'relative_time' => $rep->relative_time,
            ])->values()->all()
            : [];

        $firstInstructorReply = collect($replies)->firstWhere('role', 'instructor') ?: ($replies[0] ?? null);

        return [
            'id'             => $comment->id,
            'lesson_id'      => $comment->lesson_id,
            'user_id'        => $comment->user_id,
            'user_name'      => $comment->user?->name ?? 'Student',
            'user_name_kh'   => $comment->user?->name_kh ?? null,
            'userName'       => $comment->user?->name ?? 'Student',
            'avatar'         => $comment->user?->avatar ?? null,
            'role'           => ($teacherId && (int) $comment->user_id === (int) $teacherId) ? 'instructor' : ($comment->user?->role ?? 'student'),
            'content'        => $comment->body,
            'body'           => $comment->body,
            'video_seconds'  => $comment->video_seconds,
            'time'           => $comment->relative_time ?: 'Just now',
            'created_at'     => $comment->created_at?->toIso8601String() ?? now()->toIso8601String(),
            'is_answered'    => (bool) $comment->is_answered,
            'isTeacherReply' => !empty($firstInstructorReply) && ($firstInstructorReply['role'] ?? '') === 'instructor',
            'reply'          => $firstInstructorReply['body'] ?? null,
            'replies'        => $replies,
        ];
    }

    protected function formatLessonNote(LessonNote $note): array
    {
        $sec = (int) ($note->video_seconds ?? 0);
        return [
            'id'            => $note->id,
            'lesson_id'     => $note->lesson_id,
            'lesson_title'  => $note->relationLoaded('lesson') ? ($note->lesson?->title ?? '') : '',
            'course_id'     => $note->course_id,
            'course_title'  => $note->relationLoaded('course') ? ($note->course?->title ?? '') : '',
            'body'          => $note->body,
            'text'          => $note->body,
            'video_seconds' => $sec,
            'timestamp'     => $this->formatSeconds($sec),
            'time'          => $this->formatSeconds($sec),
            'saved_at'      => $note->created_at ? $note->created_at->format('Y-m-d H:i') : now()->format('Y-m-d H:i'),
            'savedAt'       => $note->created_at ? $note->created_at->format('Y-m-d H:i') : now()->format('Y-m-d H:i'),
        ];
    }

    protected function parseTimestampToSeconds(?string $ts): int
    {
        if (!$ts) {
            return 0;
        }
        $parts = array_map('intval', explode(':', trim($ts)));
        if (count($parts) === 3) {
            return max(0, ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2]);
        }
        if (count($parts) === 2) {
            return max(0, ($parts[0] * 60) + $parts[1]);
        }
        return max(0, (int) ($parts[0] ?? 0));
    }

    protected function formatSeconds(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $hours = (int) floor($seconds / 3600);
        $minutes = (int) floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
        }
        return sprintf('%02d:%02d', $minutes, $secs);
    }
}
