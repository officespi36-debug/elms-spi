<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LearningController extends Controller
{
    public function content(Request $request)
    {
        $tab = $request->query('tab', 'videos');

        return Inertia::render('Student/LearningContent/Index', [
            'activeTab' => $tab,
        ]);
    }

    public function videos(Request $request)
    {
        return Inertia::render('Student/LearningContent/Videos');
    }

    public function pdfs(Request $request)
    {
        return Inertia::render('Student/LearningContent/Pdfs');
    }

    public function slides(Request $request)
    {
        return Inertia::render('Student/LearningContent/Slides');
    }

    public function notes(Request $request)
    {
        return Inertia::render('Student/LearningContent/NotesDownloads');
    }

    public function links(Request $request)
    {
        return Inertia::render('Student/LearningContent/UrlResources');
    }

    /**
     * Display the Fullscreen Course Learning Player (Focus Mode)
     */
    public function show(Request $request, Course $course)
    {
        $user = $request->user();

        // 1. Enrollment Verification or Auto-Enroll for Free / Demo Courses
        $enrollment = Enrollment::where('student_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if (!$enrollment) {
            if (!$course->is_paid || (float)$course->price <= 0) {
                $enrollment = Enrollment::create([
                    'student_id'  => $user->id,
                    'course_id'   => $course->id,
                    'status'      => 'active',
                    'enrolled_at' => now(),
                ]);
            } else {
                return redirect()->route('student.browse.show', $course->id)
                    ->with('error', 'សូមចុះឈ្មោះ ឬទូទាត់ប្រាក់ជាមុនសិនដើម្បីចូលរៀន!');
            }
        }

        // 2. Load Deep Course Relations with Modules & Lessons
        $course->load([
            'teacher',
            'major',
            'modules.lessons.materials',
            'modules.lessons.videos',
            'modules.lessons.labIntegrations',
            'quizzes'
        ]);

        // 3. Collect all lessons across all modules
        $allLessons = collect();
        if ($course->modules && $course->modules->count() > 0) {
            foreach ($course->modules as $mod) {
                if ($mod->lessons && $mod->lessons->count() > 0) {
                    foreach ($mod->lessons as $l) {
                        $allLessons->push($l);
                    }
                }
            }
        }

        // If no lessons found directly on modules, check course->lessons relation
        if ($allLessons->isEmpty()) {
            $directLessons = $course->lessons()->with(['materials', 'videos'])->get();
            if ($directLessons->isNotEmpty()) {
                $allLessons = $directLessons;
            }
        }

        $lessonIds = $allLessons->pluck('id')->filter()->all();

        // 4. Fetch User's Lesson Progress Records
        $progress = LessonProgress::where('user_id', $user->id)
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

        if ($requestedLessonId && in_array((int)$requestedLessonId, $lessonIds)) {
            $initialLessonId = (int) $requestedLessonId;
        } else {
            // Find most recently updated lesson progress
            $lastWatched = LessonProgress::where('user_id', $user->id)
                ->whereIn('lesson_id', $lessonIds)
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($lastWatched) {
                $initialLessonId = $lastWatched->lesson_id;
            } elseif ($allLessons->isNotEmpty()) {
                // First uncompleted lesson
                $firstIncomplete = $allLessons->first(function ($l) use ($progress) {
                    return !isset($progress[$l->id]) || ($progress[$l->id]->percent < 90 && !$progress[$l->id]->completed_at);
                });
                $initialLessonId = $firstIncomplete ? $firstIncomplete->id : $allLessons->first()->id;
            }
        }

        return Inertia::render('Student/Learning/Show', [
            'course'                => $course,
            'progress'              => $progress,
            'enrollment'            => $enrollment,
            'initialLessonId'       => $initialLessonId,
            'courseProgressPercent' => $courseProgressPercent,
            'completedLessonsCount' => $completedLessonsCount,
            'totalLessonsCount'     => $totalLessonsCount,
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

        $newPercent = max($prog->percent ?? 0, (int) $data['percent']);
        $newSeconds = max($prog->seconds_watched ?? 0, (int) $data['seconds']);
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

        // Calculate Course Overall Progress
        $course = $lesson->course ?: Course::find($lesson->course_id);
        $courseProgress = 0;
        $allComplete = false;

        if ($course) {
            $courseLessonIds = $course->lessons()->pluck('id')->all();
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
                }
            }
        }

        return response()->json([
            'ok'                   => true,
            'lesson_id'            => $lesson->id,
            'percent'              => $prog->percent,
            'seconds_watched'      => $prog->seconds_watched,
            'is_completed'         => $isCompleted,
            'course_progress'      => $courseProgress,
            'is_course_completed'  => $allComplete,
        ]);
    }

    /**
     * AI Tutor Instant Q&A in Focus Mode (Powered by Cloudflare Workers AI)
     */
    public function askAi(Request $request, Lesson $lesson, \App\Services\CloudflareAIService $cfAi)
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
                'role' => 'system',
                'content' => "You are an intelligent AI Tutor at Saint Paul Institute. The student is currently studying: Course: '{$courseTitle}', Lesson: '{$title}'.
Provide a helpful, pedagogical, structured explanation answering their question. Explain key points clearly with code snippets or practical steps if applicable.
Answer in Khmer or English matching the user's language."
            ],
            [
                'role' => 'user',
                'content' => $question
            ]
        ];

        $reply = $cfAi->chat($messages);

        return response()->json([
            'success' => true,
            'reply'   => $reply,
            'time'    => now()->format('h:i A'),
        ]);
    }

    /**
     * Post Lesson Q&A Discussion
     */
    public function postDiscussion(Request $request, Lesson $lesson)
    {
        $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'សំណួររបស់អ្នកត្រូវបានបង្ហោះជោគជ័យ!',
            'discussion' => [
                'id'         => rand(100, 9999),
                'user_name'  => $request->user()->name,
                'avatar'     => $request->user()->avatar,
                'content'    => $request->input('content'),
                'created_at' => 'អម្បាញ់មិញ',
                'replies'    => [],
            ]
        ]);
    }

    /**
     * Save Personal Lesson Note
     */
    public function saveNote(Request $request, Lesson $lesson)
    {
        $request->validate([
            'text'      => 'required|string|max:3000',
            'timestamp' => 'nullable|string',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'បានរក្សាទុកកំណត់ចំណាំជោគជ័យ!',
            'note' => [
                'id'        => rand(1000, 99999),
                'text'      => $request->input('text'),
                'timestamp' => $request->input('timestamp', '00:00'),
                'saved_at'  => now()->format('Y-m-d H:i'),
            ]
        ]);
    }
}
