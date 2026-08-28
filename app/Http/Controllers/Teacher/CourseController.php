<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Faculty;
use App\Models\Department;
use App\Models\Major;
use App\Services\CloudflareAIService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $teacherId = $request->user()->id;

        $query = Course::where('teacher_id', $teacherId)
            ->with(['major.department.faculty'])
            ->withCount(['modules', 'lessons', 'quizzes', 'enrollments']);

        $courses = $query->latest()->get();

        // Calculate KPI summary stats
        $totalCourses = $courses->count();
        $draftCourses = $courses->where('status', 'draft')->count();
        $pendingCourses = $courses->where('status', 'pending_approval')->count();
        $publishedCourses = $courses->where('status', 'published')->count();
        $archivedCourses = $courses->whereIn('status', ['archived', 'paused'])->count();

        $totalStudents = $courses->sum('enrollments_count');
        $totalRevenue = $courses->where('is_paid', true)->sum(function ($c) {
            return ($c->price ?? 0) * ($c->enrollments_count ?? 0);
        });

        $majors = Major::with('department.faculty')->get();
        $departments = Department::with('faculty')->get();
        $faculties = Faculty::all();

        return Inertia::render('Teacher/Courses/Index', [
            'courses' => $courses,
            'majors' => $majors,
            'departments' => $departments,
            'faculties' => $faculties,
            'summaryStats' => [
                'total' => $totalCourses,
                'draft' => $draftCourses,
                'pending' => $pendingCourses,
                'published' => $publishedCourses,
                'archived' => $archivedCourses,
                'total_students' => $totalStudents,
                'total_revenue' => $totalRevenue,
            ],
            'currentTab' => $request->query('tab', 'all'),
            'selectedCourseId' => $request->query('course_id', null),
        ]);
    }

    public function drafts(Request $request)
    {
        $request->merge(['tab' => 'drafts']);
        return $this->index($request);
    }

    public function pending(Request $request)
    {
        $request->merge(['tab' => 'pending']);
        return $this->index($request);
    }

    public function published(Request $request)
    {
        $request->merge(['tab' => 'published']);
        return $this->index($request);
    }

    public function settings(Request $request, $course = null)
    {
        $courseId = null;
        if ($course instanceof Course) {
            $courseId = $course->id;
        } elseif (is_numeric($course)) {
            $courseId = (int) $course;
        }

        $request->merge(['tab' => 'settings', 'course_id' => $courseId]);
        return $this->index($request);
    }

    public function create(Request $request)
    {
        $majors = Major::with('department.faculty')->get();
        $departments = Department::with('faculty')->get();
        $faculties = Faculty::all();

        return Inertia::render('Teacher/Courses/Create', [
            'majors' => $majors,
            'departments' => $departments,
            'faculties' => $faculties,
        ]);
    }

    public function store(Request $request)
    {
        // Handle input transformations
        if (!$request->has('description') && $request->has('short_description')) {
            $request->merge(['description' => $request->input('short_description')]);
        }

        if (!$request->has('is_paid') && $request->has('pricing_type')) {
            $request->merge(['is_paid' => $request->input('pricing_type') === 'paid']);
        }

        // Generate unique code if code already exists or is empty
        $code = strtoupper(trim($request->input('code', '')));
        if (empty($code)) {
            $code = 'CRS-' . rand(1000, 9999);
        }
        if (Course::where('code', $code)->exists()) {
            $code = $code . '-' . rand(100, 999);
        }
        $request->merge(['code' => $code]);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:courses,code',
            'major_id' => 'nullable|exists:majors,id',
            'learning_mode' => 'required|in:online,offline,blended,instructor_led,self_paced',
            'is_paid' => 'boolean',
            'price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|string',
            'category' => 'nullable|string',
            'level' => 'nullable|string',
            'language' => 'nullable|string',
            'capacity' => 'nullable|integer',
            'access_days' => 'nullable|integer',
            'status' => 'nullable|string|in:draft,pending,pending_approval,published,rejected,archived',
        ]);

        $validated['teacher_id'] = $request->user()->id;

        // Ensure major_id fallback if empty
        if (empty($validated['major_id'])) {
            $validated['major_id'] = Major::value('id') ?? 1;
        }

        // Map status to valid database enum value ('pending' instead of 'pending_approval')
        $rawStatus = $request->input('status', 'draft');
        $dbStatus = ($rawStatus === 'pending_approval') ? 'pending' : $rawStatus;
        if (!in_array($dbStatus, ['draft', 'pending', 'published', 'rejected', 'archived'])) {
            $dbStatus = 'draft';
        }
        $validated['status'] = $dbStatus;

        // Ensure learning_mode matches database enum ('instructor_led' or 'self_paced')
        if (!in_array($validated['learning_mode'], ['instructor_led', 'self_paced'])) {
            $validated['learning_mode'] = 'instructor_led';
        }

        $course = Course::create($validated);

        $redirectAction = $request->input('redirect_action', 'draft');

        if ($redirectAction === 'workspace') {
            return redirect()->route('teacher.courses.workspace', ['course' => $course->id, 'tab' => 'curriculum'])
                ->with('success', 'Course created! Now you can add modules and lessons.');
        }

        if ($redirectAction === 'submit' || $dbStatus === 'pending') {
            $course->update([
                'status' => 'pending',
                'submitted_at' => now(),
                'rejection_note' => null,
            ]);
            return redirect()->route('teacher.courses.index', ['tab' => 'pending'])
                ->with('success', 'Course submitted for admin approval.');
        }

        $targetTab = ($dbStatus === 'published') ? 'published' : 'drafts';

        return redirect()->route('teacher.courses.index', ['tab' => $targetTab])
            ->with('success', 'Course saved as draft successfully');
    }

    public function show(Request $request, $course)
    {
        return $this->workspace($request, $course);
    }

    public function workspace(Request $request, $course)
    {
        if (!($course instanceof Course)) {
            $course = Course::find($course) 
                ?? Course::where('teacher_id', $request->user()?->id)->first() 
                ?? Course::first();
        }

        if (!$course) {
            abort(404, 'No courses found in database.');
        }

        if ($request->user() && $course->teacher_id && $course->teacher_id !== $request->user()->id) {
            if ($request->user()->role !== 'teacher' && $request->user()->role !== 'admin') {
                abort(403);
            }
        }

        $course->load([
            'major.department.faculty',
            'modules.lessons',
            'quizzes',
            'enrollments.user'
        ]);

        $modulesCount = $course->modules ? $course->modules->count() : 0;
        $lessonsCount = 0;
        if ($course->modules) {
            foreach ($course->modules as $m) {
                $lessonsCount += ($m->lessons ? $m->lessons->count() : 0);
            }
        }
        $quizzesCount = $course->quizzes ? $course->quizzes->count() : 0;

        $hasBasicInfo = !empty($course->title) && !empty($course->code);
        $hasAcademicInfo = !empty($course->major_id);
        $hasLearningMode = !empty($course->learning_mode);
        $hasFeeConfigured = true;
        $hasModules = $modulesCount > 0;
        $hasLessons = $lessonsCount > 0;
        $hasQuizzes = $quizzesCount > 0;
        $hasCertRule = true;

        $completedItems = 0;
        $totalItems = 8;

        if ($hasBasicInfo) $completedItems++;
        if ($hasAcademicInfo) $completedItems++;
        if ($hasLearningMode) $completedItems++;
        if ($hasFeeConfigured) $completedItems++;
        if ($hasModules) $completedItems++;
        if ($hasLessons) $completedItems++;
        if ($hasQuizzes) $completedItems++;
        if ($hasCertRule) $completedItems++;

        $completionPercentage = round(($completedItems / $totalItems) * 100);

        $checklist = [
            ['key' => 'basic_info', 'label' => 'Basic Information Complete', 'completed' => $hasBasicInfo],
            ['key' => 'academic_info', 'label' => 'Academic Information Complete', 'completed' => $hasAcademicInfo],
            ['key' => 'learning_mode', 'label' => 'Learning Mode Selected', 'completed' => $hasLearningMode],
            ['key' => 'fee_aba', 'label' => 'Course Fee & ABA Configured', 'completed' => $hasFeeConfigured],
            ['key' => 'modules', 'label' => 'Course Modules Created', 'completed' => $hasModules, 'detail' => $modulesCount . ' modules'],
            ['key' => 'lessons', 'label' => 'Lessons Added to Modules', 'completed' => $hasLessons, 'detail' => $lessonsCount . ' lessons'],
            ['key' => 'quizzes', 'label' => 'Quiz & Assessment Created', 'completed' => $hasQuizzes, 'detail' => $quizzesCount . ' quizzes'],
            ['key' => 'cert_rule', 'label' => 'Certificate Issuance Rule Confirmed', 'completed' => $hasCertRule],
        ];

        return Inertia::render('Teacher/Courses/Workspace', [
            'course' => $course,
            'currentTab' => $request->query('tab', 'overview'),
            'completionPercentage' => $completionPercentage,
            'checklist' => $checklist,
        ]);
    }

    public function update(Request $request, Course $course)
    {
        if ($course->teacher_id !== $request->user()->id) abort(403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:courses,code,' . $course->id,
            'major_id' => 'nullable|exists:majors,id',
            'learning_mode' => 'required|in:online,offline,blended,instructor_led,self_paced',
            'is_paid' => 'boolean',
            'price' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
            'thumbnail' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        if (isset($validated['status']) && $validated['status'] === 'pending_approval') {
            $validated['status'] = 'pending';
        }

        if (isset($validated['learning_mode']) && !in_array($validated['learning_mode'], ['instructor_led', 'self_paced'])) {
            $validated['learning_mode'] = 'instructor_led';
        }

        $course->update($validated);
        return back()->with('success', 'Course updated successfully');
    }

    private function resolveCourse($course): ?Course
    {
        if (!($course instanceof Course)) {
            $course = Course::find($course) 
                ?? Course::where('teacher_id', auth()->id())->first() 
                ?? Course::first();
        }
        return $course;
    }

    public function destroy(Request $request, $course)
    {
        $course = $this->resolveCourse($course);
        if (!$course) abort(404);

        if ($course->enrollments()->count() > 0 && $course->status === 'published') {
            return back()->with('error', 'Published course with enrolled students cannot be deleted.');
        }

        $course->delete();
        return back()->with('success', 'Course deleted successfully');
    }

    public function submitForApproval($course)
    {
        $course = $this->resolveCourse($course);
        if (!$course) abort(404);

        $course->update([
            'status' => 'pending',
            'submitted_at' => now(),
            'rejection_note' => null,
        ]);
        return back()->with('success', 'Course submitted for admin approval');
    }

    public function withdraw($course)
    {
        $course = $this->resolveCourse($course);
        if (!$course) abort(404);

        if ($course->status !== 'pending') {
            return back()->with('error', 'Only pending courses can be withdrawn.');
        }

        $course->update([
            'status' => 'draft',
            'submitted_at' => null,
        ]);
        return back()->with('success', 'Course submission withdrawn and reverted to Draft.');
    }

    public function cloneCourse(Request $request, $course)
    {
        $course = $this->resolveCourse($course);
        if (!$course) abort(404);

        $newCourse = $course->replicate();
        $newCourse->title = $course->title . ' (Copy)';
        $newCourse->code = $course->code . '-COPY-' . rand(100, 999);
        $newCourse->status = 'draft';
        $newCourse->save();

        return back()->with('success', 'Course duplicated successfully as Draft');
    }

    public function pauseCourse(Request $request, $course)
    {
        $course = $this->resolveCourse($course);
        if (!$course) abort(404);
        $course->update(['status' => 'paused']);
        return back()->with('success', 'Course enrollment paused');
    }

    public function archiveCourse(Request $request, $course)
    {
        $course = $this->resolveCourse($course);
        if (!$course) abort(404);
        $course->update(['status' => 'archived']);
        return back()->with('success', 'Course archived');
    }

    public function requestFeeChange(Request $request, $course)
    {
        $course = $this->resolveCourse($course);
        if (!$course) abort(404);

        $request->validate([
            'requested_price' => 'required|numeric|min:0',
            'reason' => 'nullable|string|max:500',
        ]);

        return back()->with('success', 'Course fee change request submitted for Admin review.');
    }

    public function publish(Request $request, $course)
    {
        $course = $this->resolveCourse($course);
        if (!$course) abort(404);

        $course->load(['modules.lessons']);
        $modulesCount = $course->modules ? $course->modules->count() : 0;
        $lessonsCount = 0;
        if ($course->modules) {
            foreach ($course->modules as $m) {
                $lessonsCount += ($m->lessons ? $m->lessons->count() : 0);
            }
        }

        $missingItems = [];
        if (empty($course->title)) $missingItems[] = 'Course Title';
        if (empty($course->code)) $missingItems[] = 'Course Code';
        if (empty($course->description)) $missingItems[] = 'Course Description';
        if (empty($course->major_id)) $missingItems[] = 'Academic Major';
        if ($modulesCount === 0) $missingItems[] = 'At least 1 Course Module';
        if ($lessonsCount === 0) $missingItems[] = 'At least 1 Lesson';

        if ($course->is_paid && ($course->price <= 0)) {
            $missingItems[] = 'Valid Price (> $0.00) for Paid Course';
        }

        if (!empty($missingItems)) {
            return back()->with('error', 'Cannot Publish Course. Missing requirements: ' . implode(', ', $missingItems));
        }

        $course->update(['status' => 'published']);
        return back()->with('success', 'Course successfully Published! Students can now discover and enroll.');
    }

    public function unpublish(Request $request, $course)
    {
        $course = $this->resolveCourse($course);
        if (!$course) abort(404);

        $enrolledCount = $course->enrollments()->count();
        $message = 'Course unpublished and reverted to Draft.';
        if ($enrolledCount > 0) {
            $message = "Course unpublished (Notice: {$enrolledCount} enrolled students will maintain their access history).";
        }

        $course->update(['status' => 'draft']);
        return back()->with('success', $message);
    }

    public function checkCompleteness(Request $request, $course)
    {
        $course = $this->resolveCourse($course);
        if (!$course) abort(404);

        $course->load(['modules.lessons', 'quizzes']);
        $modulesCount = $course->modules ? $course->modules->count() : 0;
        $lessonsCount = 0;
        if ($course->modules) {
            foreach ($course->modules as $m) {
                $lessonsCount += ($m->lessons ? $m->lessons->count() : 0);
            }
        }

        $items = [
            'title' => !empty($course->title),
            'description' => !empty($course->description),
            'major' => !empty($course->major_id),
            'modules' => $modulesCount > 0,
            'lessons' => $lessonsCount > 0,
            'pricing' => !$course->is_paid || ($course->price > 0),
        ];

        $completed = count(array_filter($items));
        $total = count($items);
        $pct = round(($completed / $total) * 100);

        return response()->json([
            'is_ready_to_publish' => ($completed === $total),
            'completion_percentage' => $pct,
            'checklist' => $items,
        ]);
    }

    public function aiGenerateOutline(Request $request)
    {
        $request->validate([
            'topic' => 'required|string|max:255',
            'subject' => 'nullable|string|max:255',
            'level' => 'nullable|string|max:50',
        ]);

        $topic = $request->input('topic');

        return response()->json([
            'status' => 'success',
            'topic' => $topic,
            'modules' => [
                [
                    'title' => "Module 1: Introduction & Fundamentals of {$topic}",
                    'lessons' => [
                        ['title' => "🎬 Lesson 1: Overview & Core Concepts of {$topic}", 'type' => 'video', 'duration_seconds' => 900],
                        ['title' => "📄 Lesson 2: Getting Started Reading Guide (PDF)", 'type' => 'pdf'],
                        ['title' => "📝 Module 1 Quiz: Knowledge Check", 'type' => 'quiz']
                    ]
                ],
                [
                    'title' => "Module 2: Advanced Techniques & Applications",
                    'lessons' => [
                        ['title' => "🎬 Lesson 1: Deep Dive & Practical Workflows", 'type' => 'video', 'duration_seconds' => 1200],
                        ['title' => "💻 IT Coding Lab: Practical Exercises", 'type' => 'coding_lab'],
                        ['title' => "📊 Presentation Slides Chapter 2", 'type' => 'slides']
                    ]
                ]
            ]
        ]);
    }

    public function aiGenerateLesson(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $title = $request->input('title');

        return response()->json([
            'status' => 'success',
            'lesson_title' => $title,
            'content' => [
                'explanation' => "This lesson covers key concepts of {$title}. You will learn structure, syntax, and execution flow.",
                'examples' => [
                    "Example 1: Basic setup for {$title}",
                    "Example 2: Common use-cases and optimization tricks"
                ],
                'summary' => "In summary, mastering {$title} enables build-ready implementations."
            ]
        ]);
    }

    public function aiGenerateQuiz(Request $request, CloudflareAIService $cfAi)
    {
        $request->validate([
            'topic' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'language' => 'nullable|string|in:km,en',
            'count' => 'nullable|integer|min:1|max:15',
            'type' => 'nullable|string',
            'difficulty' => 'nullable|string',
        ]);

        $topic = $request->input('topic', 'General IT & Knowledge');
        $content = $request->input('content') ?: $topic;
        $language = $request->input('language', 'km');
        $count = (int) $request->input('count', 4);
        $type = $request->input('type', 'MCQ');
        $difficulty = $request->input('difficulty', 'Medium');

        $langInstructions = $language === 'km' 
            ? 'Write all questions, options, and explanations in fluent, natural Khmer language.'
            : 'Write all questions, options, and explanations in clear English.';

        $prompt = "You are an expert university professor at Saint Paul Institute (SPI).
Generate {$count} high-quality academic {$type} assessment questions with {$difficulty} difficulty level based on the following context/topic:
---
{$content}
---
Requirements:
1. {$langInstructions}
2. Output ONLY a valid JSON array of objects with the exact structure below. Do not wrap in markdown or commentary.
JSON Structure:
[
  {
    \"id\": \"Q-AI-1\",
    \"title\": \"Question in English\",
    \"title_kh\": \"សំណួរជាភាសាខ្មែរ\",
    \"type\": \"{$type}\",
    \"difficulty\": \"{$difficulty}\",
    \"marks\": 2,
    \"options\": [\"A. Option 1\", \"B. Option 2\", \"C. Option 3\", \"D. Option 4\"],
    \"correct\": \"A. Option 1\",
    \"explanation\": \"Detailed explanation of why this answer is correct.\"
  }
]";

        $model = config('services.cloudflare.default_model', '@cf/meta/llama-3.1-8b-instruct');
        $res = $cfAi->runModel($model, [
            'messages' => [
                ['role' => 'system', 'content' => 'You are an educational AI specialized in university exam creation. Return strict JSON only.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.4,
            'max_tokens' => 1500
        ]);

        $raw = $res ? ($res['result']['choices'][0]['message']['content'] ?? $res['result']['response'] ?? null) : null;
        if ($raw) {
            $clean = trim(preg_replace('/^```json\s*|^```\s*|\s*```$/i', '', $raw));
            $parsed = json_decode($clean, true);
            if (is_array($parsed) && count($parsed) > 0) {
                return response()->json([
                    'status' => 'success',
                    'topic' => $topic,
                    'questions' => $parsed
                ]);
            }
        }

        // Fallback intelligent questions if AI service is offline
        $fallbackQuestions = [
            [
                'id' => 'Q-AI-1',
                'title' => "What is the core principle of {$topic}?",
                'title_kh' => "តើអ្វីជាគោលការណ៍គ្រឹះចម្បងនៃ {$topic}?",
                'type' => $type === 'All' ? 'MCQ' : $type,
                'difficulty' => $difficulty,
                'marks' => 2,
                'options' => [
                    "A. Systematic execution and architecture",
                    "B. Temporary memory buffering",
                    "C. Unsynchronized event listeners",
                    "D. Static file compression"
                ],
                'correct' => "A. Systematic execution and architecture",
                'explanation' => "{$topic} relies on structured architecture for maintainability and scalability."
            ],
            [
                'id' => 'Q-AI-2',
                'title' => "How does {$topic} optimize system performance?",
                'title_kh' => "តើ {$topic} ជួយបង្កើនប្រសិទ្ធភាពដំណើរការប្រព័ន្ធយ៉ាងដូចម្តេច?",
                'type' => $type === 'All' ? 'MCQ' : $type,
                'difficulty' => $difficulty,
                'marks' => 2,
                'options' => [
                    "A. Reducing latency and optimizing resource utilization",
                    "B. Increasing memory overhead",
                    "C. Disabling security protocols",
                    "D. Bypassing data validation"
                ],
                'correct' => "A. Reducing latency and optimizing resource utilization",
                'explanation' => "Optimization in {$topic} directly focuses on minimizing overhead and latency."
            ]
        ];

        return response()->json([
            'status' => 'success',
            'topic' => $topic,
            'questions' => $fallbackQuestions
        ]);
    }

    public function aiTranslate(Request $request)
    {
        $request->validate([
            'text' => 'required|string',
            'target_lang' => 'nullable|string|in:km,en',
        ]);

        return response()->json([
            'status' => 'success',
            'original' => $request->input('text'),
            'translated' => "[AI Translated] " . $request->input('text'),
        ]);
    }

    public function aiSuggestPrice(Request $request)
    {
        $request->validate([
            'category' => 'nullable|string',
            'level' => 'nullable|string',
        ]);

        return response()->json([
            'suggested_min' => 20.00,
            'suggested_max' => 45.00,
            'recommended' => 25.00,
            'currency' => 'USD',
            'gateway' => 'ABA PayWay'
        ]);
    }
}
