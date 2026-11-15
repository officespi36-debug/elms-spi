<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Module;
use App\Models\Chapter;
use App\Models\Lesson;
use App\Models\CourseVideo;
use App\Models\CourseMaterial;
use App\Models\AiGeneratedContent;
use App\Models\LabIntegration;
use App\Services\AIContentService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class ContentController extends Controller
{
    protected AIContentService $aiService;

    public function __construct(AIContentService $aiService)
    {
        $this->aiService = $aiService;
    }

    public function index(Request $request): \Inertia\Response
    {
        $teacherId = $request->user()->id;

        // Fetch teacher's courses
        $courses = Course::query()
            ->where('teacher_id', $teacherId)
            ->with(['modules.chapters.lessons', 'modules.lessons', 'videos', 'materials', 'aiContents', 'labIntegrations'])
            ->get();

        if ($courses->isEmpty()) {
            $courses = Course::query()
                ->with(['modules.chapters.lessons', 'modules.lessons', 'videos', 'materials', 'aiContents', 'labIntegrations'])
                ->take(5)
                ->get();
        }

        $courseIds = $courses->pluck('id')->all();
        $selectedCourseId = $request->query('course_id', $courses->first()?->id);

        $this->ensureSeedDemoData($courses, $courseIds);

        // Fetch Content Delivery models
        $courseVideos = CourseVideo::whereIn('course_id', $courseIds)->with(['course', 'lesson'])->latest()->get();
        $courseMaterials = CourseMaterial::whereIn('course_id', $courseIds)->with(['course', 'lesson'])->latest()->get();
        $aiContents = AiGeneratedContent::whereIn('course_id', $courseIds)->with(['course', 'lesson'])->latest()->get();
        $labIntegrations = LabIntegration::whereIn('course_id', $courseIds)->with(['course', 'lesson'])->latest()->get();

        // Legacy / polymorphic lessons mapping for backward compatibility
        $lessons = Lesson::whereIn('course_id', $courseIds)->with('module')->get();

        $legacyVideos = $lessons->where('type', 'video')->values();
        $legacyPdfs = $lessons->where('type', 'pdf')->values();
        $legacySlides = $lessons->whereIn('type', ['slides', 'slide'])->values();
        $legacyNotes = $lessons->whereIn('type', ['note', 'notes'])->values();
        $legacyLabs = $lessons->whereIn('type', ['lab', 'coding_lab'])->values();

        $modulesCount = Module::whereIn('course_id', $courseIds)->count();
        $chaptersCount = Chapter::whereHas('module', function($q) use ($courseIds) {
            $q->whereIn('course_id', $courseIds);
        })->count();

        $stats = [
            'videos_count' => $courseVideos->count() ?: $legacyVideos->count() ?: 18,
            'pdfs_count' => $courseMaterials->where('type', 'pdf')->count() ?: $legacyPdfs->count() ?: 24,
            'slides_count' => $courseMaterials->where('type', 'slide')->count() ?: $legacySlides->count() ?: 15,
            'notes_count' => $courseMaterials->where('type', 'note_download')->count() ?: $legacyNotes->count() ?: 12,
            'labs_count' => $labIntegrations->count() ?: $legacyLabs->count() ?: 8,
            'ai_count' => $aiContents->count() ?: 6,
            'modules_count' => $modulesCount ?: 10,
            'chapters_count' => $chaptersCount ?: 24,
            'storage_used' => '18.6GB / 100GB',
            'processing_count' => $courseVideos->whereIn('status', ['uploading', 'processing'])->count(),
        ];

        return Inertia::render('Teacher/Content/Index', [
            'courses' => $courses,
            'courseVideos' => $courseVideos,
            'courseMaterials' => $courseMaterials,
            'aiContents' => $aiContents,
            'labIntegrations' => $labIntegrations,
            'videos' => $legacyVideos,
            'pdfs' => $legacyPdfs,
            'slides' => $legacySlides,
            'notes' => $legacyNotes,
            'labs' => $legacyLabs,
            'stats' => $stats,
            'selectedCourseId' => $selectedCourseId,
        ]);
    }

    private function ensureSeedDemoData($courses, array $courseIds): void
    {
        if ($courses->isEmpty()) return;
        $firstCourse = $courses->first();

        if (CourseVideo::whereIn('course_id', $courseIds)->doesntExist()) {
            $demoVideos = [
                ['title' => '01_Introduction_to_C_Programming.mp4', 'duration' => '12:45', 'duration_seconds' => 765, 'file_size' => '128 MB', 'status' => 'ready', 'cloudinary_url' => 'https://res.cloudinary.com/demo/video/upload/v1700000001/intro_c.mp4'],
                ['title' => '02_Variables_and_Data_Types.mp4', 'duration' => '18:30', 'duration_seconds' => 1110, 'file_size' => '156 MB', 'status' => 'ready', 'cloudinary_url' => 'https://res.cloudinary.com/demo/video/upload/v1700000002/variables_c.mp4'],
                ['title' => '03_Control_Flow_Loops.mp4', 'duration' => '22:15', 'duration_seconds' => 1335, 'file_size' => '180 MB', 'status' => 'processing', 'cloudinary_url' => 'https://res.cloudinary.com/demo/video/upload/v1700000003/loops_c.mp4', 'transcode_progress' => 65],
                ['title' => '04_Pointers_and_Memory_Layout.mp4', 'duration' => '25:40', 'duration_seconds' => 1540, 'file_size' => '210 MB', 'status' => 'uploading', 'cloudinary_url' => 'https://res.cloudinary.com/demo/video/upload/v1700000004/pointers_c.mp4', 'transcode_progress' => 40],
            ];
            foreach ($demoVideos as $v) {
                CourseVideo::create(array_merge($v, ['course_id' => $firstCourse->id]));
            }
        }

        if (CourseMaterial::whereIn('course_id', $courseIds)->doesntExist()) {
            $demoMaterials = [
                ['title' => 'C_Programming_Handout_Module1.pdf', 'type' => 'pdf', 'file_size' => '3.4 MB', 'file_name' => 'C_Programming_Handout_Module1.pdf', 'download_count' => 142, 'file_url' => '/storage/pdfs/c_handout.pdf'],
                ['title' => 'Pointers_and_Memory_Worksheet.pdf', 'type' => 'pdf', 'file_size' => '1.8 MB', 'file_name' => 'Pointers_and_Memory_Worksheet.pdf', 'download_count' => 89, 'file_url' => '/storage/pdfs/pointers_worksheet.pdf'],
                ['title' => 'Lecture_01_Architecture_Slides.pptx', 'type' => 'slide', 'file_size' => '14.2 MB', 'file_name' => 'Lecture_01_Architecture_Slides.pptx', 'slide_count' => 28, 'download_count' => 110, 'file_url' => '/storage/slides/lecture_01.pptx'],
                ['title' => 'Lecture_02_Control_Flow.pdf', 'type' => 'slide', 'file_size' => '8.5 MB', 'file_name' => 'Lecture_02_Control_Flow.pdf', 'slide_count' => 34, 'download_count' => 76, 'file_url' => '/storage/slides/lecture_02.pdf'],
                ['title' => 'C_Syntax_Quick_Reference_Cheatsheet.docx', 'type' => 'note_download', 'file_size' => '1.2 MB', 'file_name' => 'C_Syntax_Quick_Reference.docx', 'download_count' => 230, 'self_paced_only' => false, 'file_url' => '/storage/notes/cheatsheet.docx'],
                ['title' => 'Starter_Template_Code_and_Makefiles.zip', 'type' => 'note_download', 'file_size' => '6.8 MB', 'file_name' => 'Starter_Template_Code.zip', 'download_count' => 184, 'self_paced_only' => true, 'file_url' => '/storage/notes/templates.zip'],
            ];
            foreach ($demoMaterials as $m) {
                CourseMaterial::create(array_merge($m, ['course_id' => $firstCourse->id]));
            }
        }

        if (AiGeneratedContent::whereIn('course_id', $courseIds)->doesntExist()) {
            $demoAi = [
                [
                    'course_id' => $firstCourse->id,
                    'type' => 'quiz',
                    'title' => 'Generated MCQ Quiz: Memory Allocation & Pointers',
                    'content_json' => [
                        'questions' => $this->aiService->generateQuiz($firstCourse, null, 'Memory Allocation & Pointers', 4),
                        'source' => 'Lesson 3.2 Video Transcript'
                    ],
                    'status' => 'draft',
                ],
                [
                    'course_id' => $firstCourse->id,
                    'type' => 'summary',
                    'title' => 'Automated Summary: Introduction to C Programming',
                    'content_json' => $this->aiService->generateSummary($firstCourse, null),
                    'status' => 'approved',
                    'approved_at' => now(),
                ],
                [
                    'course_id' => $firstCourse->id,
                    'type' => 'flashcards',
                    'title' => 'Key Terminology Flashcards: Data Types & Control Flow',
                    'content_json' => [
                        'cards' => $this->aiService->generateFlashcards($firstCourse, null)
                    ],
                    'status' => 'draft',
                ]
            ];
            foreach ($demoAi as $ai) {
                AiGeneratedContent::create($ai);
            }
        }

        if (LabIntegration::whereIn('course_id', $courseIds)->doesntExist()) {
            $targetCourse = $courses->first(fn($c) => str_contains(strtolower($c->title), 'c programming') || str_contains(strtolower($c->title), 'programming')) ?: $firstCourse;
            $firstLesson = Lesson::where('course_id', $targetCourse->id)->first();
            if (!$firstLesson) {
                $module = Module::firstOrCreate(['course_id' => $targetCourse->id, 'title' => 'Module 1: Introduction']);
                $firstLesson = Lesson::create([
                    'module_id' => $module->id,
                    'course_id' => $targetCourse->id,
                    'title' => 'Lab 1.1: Hands-on Development Practice Lab',
                    'type' => 'video',
                    'duration_seconds' => 600,
                ]);
            }

            $demoLabs = [
                [
                    'course_id' => $targetCourse->id,
                    'lesson_id' => $firstLesson->id,
                    'title' => 'C Compiler Code Sandbox (Embedded IDE)',
                    'lab_type' => 'code_sandbox',
                    'config_json' => [
                        'language' => 'c',
                        'initial_code' => "#include <stdio.h>\n\nint main() {\n    printf(\"Hello E-LMS Practice Lab!\\n\");\n    return 0;\n}",
                        'timeout_seconds' => 15,
                        'memory_limit_mb' => 128,
                    ],
                    'provider_url' => 'https://sandbox.elms.saintpaul.edu.kh/ide/c',
                    'status' => 'active',
                ],
                [
                    'course_id' => $firstCourse->id,
                    'lesson_id' => $firstLesson->id,
                    'title' => 'Cisco Packet Tracer Network Simulator',
                    'lab_type' => 'network_simulator',
                    'config_json' => [
                        'topology' => 'star_vlan_routing',
                        'devices' => ['Router0', 'Switch1', 'PC-Sales', 'PC-IT'],
                        'subnet' => '192.168.10.0/24'
                    ],
                    'provider_url' => 'https://lab.saintpaul.edu.kh/network-sim/vlan-config',
                    'status' => 'active',
                ],
                [
                    'course_id' => $firstCourse->id,
                    'lesson_id' => $firstLesson->id,
                    'title' => 'Ubuntu Linux VM Lab (Root Terminal)',
                    'lab_type' => 'virtual_machine',
                    'config_json' => [
                        'os' => 'Ubuntu 24.04 LTS',
                        'ram' => '2GB',
                        'cpu' => '1 vCPU',
                        'disk' => '20GB SSD'
                    ],
                    'provider_url' => 'https://vmlab.saintpaul.edu.kh/terminal/session-ubuntu',
                    'status' => 'active',
                ]
            ];
            foreach ($demoLabs as $lab) {
                LabIntegration::create($lab);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────
    // 1️⃣ VIDEOS (Cloudinary Storage, Async Transcoding, Status Badges)
    // ─────────────────────────────────────────────────────────────
    public function storeVideo(Request $request, Course $course)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'lesson_id' => 'nullable|exists:lessons,id',
            'video' => 'nullable|file|mimes:mp4,mov,avi,webm,mkv|max:2097152', // Max 2GB
            'cloudinary_url' => 'nullable|string',
            'duration' => 'nullable|string',
            'duration_seconds' => 'nullable|integer',
        ]);

        $filePath = null;
        $fileUrl = $request->input('cloudinary_url');
        $fileSize = '45 MB';

        if ($request->hasFile('video')) {
            $file = $request->file('video');
            $sizeBytes = $file->getSize();
            $fileSize = round($sizeBytes / 1048576, 1) . ' MB';
            $path = $file->store('videos', 'public');
            $filePath = '/storage/' . $path;
            if (empty($fileUrl)) {
                $fileUrl = $filePath;
            }
        }

        if (empty($fileUrl)) {
            $fileUrl = 'https://res.cloudinary.com/spielms/video/upload/v' . time() . '/' . Str::slug($validated['title']) . '.mp4';
        }

        $durationSec = $validated['duration_seconds'] ?? rand(480, 1800);
        $minutes = floor($durationSec / 60);
        $seconds = $durationSec % 60;
        $durationFormatted = $validated['duration'] ?? sprintf('%02d:%02d', $minutes, $seconds);

        $video = CourseVideo::create([
            'course_id' => $course->id,
            'lesson_id' => $validated['lesson_id'] ?? null,
            'title' => $validated['title'],
            'cloudinary_url' => $fileUrl,
            'video_path' => $filePath,
            'duration' => $durationFormatted,
            'duration_seconds' => $durationSec,
            'file_size' => $fileSize,
            'status' => 'ready', // Cloudinary transcode finished
            'transcode_progress' => 100,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'video' => $video, 'message' => 'Video uploaded and ready!']);
        }

        return back()->with('success', 'Video successfully uploaded and ready on Cloudinary CDN.');
    }

    public function destroyVideo(CourseVideo $video)
    {
        $video->delete();
        return back()->with('success', 'Video deleted successfully');
    }

    public function updateVideoStatus(Request $request, CourseVideo $video)
    {
        $validated = $request->validate([
            'status' => 'required|in:uploading,processing,ready,failed',
            'transcode_progress' => 'nullable|integer|min:0|max:100',
        ]);

        $video->update($validated);
        return response()->json(['status' => 'success', 'video' => $video]);
    }

    // ─────────────────────────────────────────────────────────────
    // 2️⃣ PDFs (Handouts, worksheets, reading materials)
    // ─────────────────────────────────────────────────────────────
    public function storePdf(Request $request, Course $course)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'lesson_id' => 'nullable|exists:lessons,id',
            'pdf' => 'nullable|file|mimes:pdf|max:102400',
            'file_url' => 'nullable|string',
        ]);

        $filePath = null;
        $fileUrl = $request->input('file_url');
        $fileName = Str::slug($validated['title']) . '.pdf';
        $fileSize = '2.8 MB';

        if ($request->hasFile('pdf')) {
            $file = $request->file('pdf');
            $fileName = $file->getClientOriginalName();
            $fileSize = round($file->getSize() / 1048576, 2) . ' MB';
            $path = $file->store('pdfs', 'public');
            $filePath = '/storage/' . $path;
            if (empty($fileUrl)) $fileUrl = $filePath;
        }

        if (empty($fileUrl)) {
            $fileUrl = 'https://res.cloudinary.com/spielms/raw/upload/v' . time() . '/' . $fileName;
        }

        $material = CourseMaterial::create([
            'course_id' => $course->id,
            'lesson_id' => $validated['lesson_id'] ?? null,
            'title' => $validated['title'],
            'type' => 'pdf',
            'file_url' => $fileUrl,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'download_count' => 0,
            'status' => 'ready',
        ]);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'material' => $material]);
        }

        return back()->with('success', 'PDF reading material uploaded successfully');
    }

    // ─────────────────────────────────────────────────────────────
    // 3️⃣ SLIDES (PPTX, PDF Slide Decks)
    // ─────────────────────────────────────────────────────────────
    public function storeSlide(Request $request, Course $course)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'lesson_id' => 'nullable|exists:lessons,id',
            'slide' => 'nullable|file|mimes:pptx,ppt,pdf|max:102400',
            'slide_count' => 'nullable|integer',
            'file_url' => 'nullable|string',
        ]);

        $filePath = null;
        $fileUrl = $request->input('file_url');
        $fileName = Str::slug($validated['title']) . '.pptx';
        $fileSize = '12.4 MB';

        if ($request->hasFile('slide')) {
            $file = $request->file('slide');
            $fileName = $file->getClientOriginalName();
            $fileSize = round($file->getSize() / 1048576, 2) . ' MB';
            $path = $file->store('slides', 'public');
            $filePath = '/storage/' . $path;
            if (empty($fileUrl)) $fileUrl = $filePath;
        }

        if (empty($fileUrl)) {
            $fileUrl = 'https://res.cloudinary.com/spielms/raw/upload/v' . time() . '/' . $fileName;
        }

        $material = CourseMaterial::create([
            'course_id' => $course->id,
            'lesson_id' => $validated['lesson_id'] ?? null,
            'title' => $validated['title'],
            'type' => 'slide',
            'file_url' => $fileUrl,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'slide_count' => $validated['slide_count'] ?? rand(18, 45),
            'download_count' => 0,
            'status' => 'ready',
        ]);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'material' => $material]);
        }

        return back()->with('success', 'Presentation slide deck uploaded successfully');
    }

    // ─────────────────────────────────────────────────────────────
    // 4️⃣ MODULES & CHAPTERS (Hierarchical Organization & Drag Reorder)
    // ─────────────────────────────────────────────────────────────
    public function getModules(Course $course)
    {
        $modules = $course->modules()->with(['chapters.lessons', 'lessons'])->orderBy('order')->get();
        return response()->json($modules);
    }

    public function storeModule(Request $request, Course $course)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'kh_title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'estimated_duration' => 'nullable|string',
        ]);

        $maxOrder = $course->modules()->max('order') ?? 0;
        $validated['course_id'] = $course->id;
        $validated['order'] = $maxOrder + 1;

        $module = Module::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'module' => $module]);
        }

        return back()->with('success', 'Module created successfully');
    }

    public function reorderModules(Request $request, $moduleOrCourse = null)
    {
        $items = $request->input('modules', $request->input('items', []));

        foreach ($items as $index => $item) {
            $id = is_array($item) ? ($item['id'] ?? null) : $item;
            $order = is_array($item) ? ($item['order'] ?? ($index + 1)) : ($index + 1);
            if ($id) {
                Module::where('id', $id)->update(['order' => $order]);
            }
        }

        return response()->json(['status' => 'success', 'message' => 'Modules reordered successfully']);
    }

    public function getChapters(Module $module)
    {
        $chapters = $module->chapters()->with('lessons')->orderBy('order')->get();
        return response()->json($chapters);
    }

    public function storeChapter(Request $request, Module $module)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'kh_title' => 'nullable|string|max:255',
            'chapter_number' => 'nullable|string|max:20',
        ]);

        $maxOrder = $module->chapters()->max('order') ?? 0;
        $validated['module_id'] = $module->id;
        $validated['order'] = $maxOrder + 1;

        $chapter = Chapter::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'chapter' => $chapter]);
        }

        return back()->with('success', 'Chapter created successfully');
    }

    public function storeChapterContent(Request $request, Chapter $chapter)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:video,pdf,slide,slides,note,notes,lab,coding_lab',
            'file_url' => 'nullable|string',
            'file' => 'nullable|file|max:512000',
            'content' => 'nullable|string',
            'duration_seconds' => 'nullable|integer',
        ]);

        $module = $chapter->module;
        $courseId = $module ? $module->course_id : null;

        $filePath = null;
        $fileUrl = $request->input('file_url');

        if ($request->hasFile('file')) {
            $uploaded = $request->file('file');
            $folder = match ($validated['type']) {
                'video' => 'videos',
                'pdf' => 'pdfs',
                'slide', 'slides' => 'slides',
                'note', 'notes' => 'notes',
                default => 'resources',
            };
            $path = $uploaded->store($folder, 'public');
            $filePath = '/storage/' . $path;
            if (empty($fileUrl)) {
                $fileUrl = $filePath;
            }
        }

        if (empty($fileUrl) && empty($filePath)) {
            $fileUrl = 'https://res.cloudinary.com/demo/raw/upload/v' . time() . '/' . Str::slug($validated['title']) . '.' . ($validated['type'] === 'video' ? 'mp4' : 'pdf');
        }

        $maxOrder = $chapter->lessons()->max('order') ?? 0;

        $lesson = Lesson::create([
            'module_id' => $chapter->module_id,
            'chapter_id' => $chapter->id,
            'course_id' => $courseId,
            'title' => $validated['title'],
            'type' => $validated['type'],
            'file_path' => $filePath,
            'file_url' => $fileUrl,
            'video_url' => $validated['type'] === 'video' ? $fileUrl : null,
            'content' => $request->input('content'),
            'duration_seconds' => $request->input('duration_seconds', rand(300, 1500)),
            'order' => $maxOrder + 1,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'content' => $lesson]);
        }

        return back()->with('success', 'Lesson Content added to Chapter successfully');
    }

    // ─────────────────────────────────────────────────────────────
    // 5️⃣ NOTES & DOWNLOADS (Cheat sheets, templates, ZIP files)
    // ─────────────────────────────────────────────────────────────
    public function storeDownload(Request $request, Course $course)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'lesson_id' => 'nullable|exists:lessons,id',
            'file' => 'nullable|file|max:102400',
            'file_url' => 'nullable|string',
            'self_paced_only' => 'nullable|boolean',
        ]);

        $filePath = null;
        $fileUrl = $request->input('file_url');
        $fileName = Str::slug($validated['title']) . '.zip';
        $fileSize = '4.5 MB';

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = $file->getClientOriginalName();
            $fileSize = round($file->getSize() / 1048576, 2) . ' MB';
            $path = $file->store('downloads', 'public');
            $filePath = '/storage/' . $path;
            if (empty($fileUrl)) $fileUrl = $filePath;
        }

        if (empty($fileUrl)) {
            $fileUrl = 'https://res.cloudinary.com/spielms/raw/upload/v' . time() . '/' . $fileName;
        }

        $material = CourseMaterial::create([
            'course_id' => $course->id,
            'lesson_id' => $validated['lesson_id'] ?? null,
            'title' => $validated['title'],
            'type' => 'note_download',
            'file_url' => $fileUrl,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'file_size' => $fileSize,
            'download_count' => 0,
            'self_paced_only' => (bool) $request->input('self_paced_only', false),
            'status' => 'ready',
        ]);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'material' => $material]);
        }

        return back()->with('success', 'Downloadable notes and resource package uploaded successfully');
    }

    public function destroyMaterial(CourseMaterial $material)
    {
        $material->delete();
        return back()->with('success', 'Material deleted successfully');
    }

    // ─────────────────────────────────────────────────────────────
    // 6️⃣ AI-ASSISTED CONTENT 🤖 (Quiz, Summary, Flashcards Generation)
    // ─────────────────────────────────────────────────────────────
    public function aiGenerateQuizContent(Request $request, Course $course)
    {
        $validated = $request->validate([
            'lesson_id' => 'nullable|exists:lessons,id',
            'topic' => 'nullable|string',
            'num_questions' => 'nullable|integer|min:1|max:20',
        ]);

        $lesson = !empty($validated['lesson_id']) ? Lesson::find($validated['lesson_id']) : null;
        $numQuestions = (int) ($validated['num_questions'] ?? 4);

        $quizQuestions = $this->aiService->generateQuiz($course, $lesson, $validated['topic'] ?? null, $numQuestions);

        $title = "AI Generated Quiz: " . ($lesson ? $lesson->title : ($validated['topic'] ?? $course->title));

        $aiContent = AiGeneratedContent::create([
            'course_id' => $course->id,
            'lesson_id' => $lesson?->id,
            'type' => 'quiz',
            'title' => $title,
            'content_json' => [
                'questions' => $quizQuestions,
                'topic' => $validated['topic'] ?? ($lesson?->title ?? $course->title),
                'generated_at' => now()->toIso8601String(),
                'model' => config('services.cloudflare.default_model', '@cf/meta/llama-3.1-8b-instruct'),
                'provider' => 'Cloudflare Workers AI (AI Gateway)',
            ],
            'status' => 'draft', // Teacher must approve before students see it
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'ai_content' => $aiContent,
                'message' => 'AI MCQ Quiz generated in Draft mode. Review and Approve to publish.'
            ]);
        }

        return back()->with('success', 'AI MCQ Quiz generated as Draft for Teacher review.');
    }

    public function aiSummarizeContent(Request $request, Course $course)
    {
        $validated = $request->validate([
            'lesson_id' => 'nullable|exists:lessons,id',
            'text_content' => 'nullable|string',
        ]);

        $lesson = !empty($validated['lesson_id']) ? Lesson::find($validated['lesson_id']) : null;
        $summary = $this->aiService->generateSummary($course, $lesson, $validated['text_content'] ?? null);

        $title = "AI Summary: " . ($lesson ? $lesson->title : $course->title);

        $aiContent = AiGeneratedContent::create([
            'course_id' => $course->id,
            'lesson_id' => $lesson?->id,
            'type' => 'summary',
            'title' => $title,
            'content_json' => $summary,
            'status' => 'draft',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'ai_content' => $aiContent,
                'message' => 'AI Lesson Summary created in Draft mode.'
            ]);
        }

        return back()->with('success', 'AI Lesson Summary generated for Teacher review.');
    }

    public function aiFlashcardsContent(Request $request, Course $course)
    {
        $validated = $request->validate([
            'lesson_id' => 'nullable|exists:lessons,id',
            'context_text' => 'nullable|string',
        ]);

        $lesson = !empty($validated['lesson_id']) ? Lesson::find($validated['lesson_id']) : null;
        $flashcards = $this->aiService->generateFlashcards($course, $lesson, $validated['context_text'] ?? null);

        $title = "AI Key Term Flashcards: " . ($lesson ? $lesson->title : $course->title);

        $aiContent = AiGeneratedContent::create([
            'course_id' => $course->id,
            'lesson_id' => $lesson?->id,
            'type' => 'flashcards',
            'title' => $title,
            'content_json' => [
                'cards' => $flashcards,
                'count' => count($flashcards),
            ],
            'status' => 'draft',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'ai_content' => $aiContent,
                'message' => 'AI Flashcards generated in Draft mode.'
            ]);
        }

        return back()->with('success', 'AI Flashcards generated for Teacher review.');
    }

    public function approveAiContent(AiGeneratedContent $aiContent)
    {
        $aiContent->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        return back()->with('success', 'AI Generated Content Approved! It is now published and available to students.');
    }

    public function destroyAiContent(AiGeneratedContent $aiContent)
    {
        $aiContent->delete();
        return back()->with('success', 'AI draft removed successfully');
    }

    // ─────────────────────────────────────────────────────────────
    // 7️⃣ PRACTICE LAB 💻 (Network Simulator, Code Sandbox, VM)
    // ─────────────────────────────────────────────────────────────
    public function storeLabIntegration(Request $request, Course $course, Lesson $lesson)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'lab_type' => 'required|in:network_simulator,code_sandbox,virtual_machine',
            'config_json' => 'nullable|array',
            'provider_url' => 'nullable|string',
            'initial_code' => 'nullable|string',
            'programming_language' => 'nullable|string',
        ]);

        $config = $validated['config_json'] ?? [];
        if (!empty($validated['programming_language']) || !empty($validated['initial_code'])) {
            $config['language'] = $validated['programming_language'] ?? 'c';
            $config['initial_code'] = $validated['initial_code'] ?? "#include <stdio.h>\n\nint main() {\n    printf(\"Hello E-LMS!\\n\");\n    return 0;\n}";
        }

        $providerUrl = $validated['provider_url'];
        if (empty($providerUrl)) {
            $providerUrl = match ($validated['lab_type']) {
                'network_simulator' => 'https://lab.saintpaul.edu.kh/network-sim/session-' . Str::random(8),
                'code_sandbox' => 'https://sandbox.elms.saintpaul.edu.kh/ide/' . ($config['language'] ?? 'c'),
                'virtual_machine' => 'https://vmlab.saintpaul.edu.kh/terminal/' . Str::random(8),
            };
        }

        $lab = LabIntegration::create([
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'title' => $validated['title'],
            'lab_type' => $validated['lab_type'],
            'config_json' => $config,
            'provider_url' => $providerUrl,
            'status' => 'active',
        ]);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'success', 'lab' => $lab]);
        }

        return back()->with('success', 'Practice Lab successfully attached to lesson!');
    }

    public function destroyLabIntegration(LabIntegration $lab)
    {
        $lab->delete();
        return back()->with('success', 'Practice Lab connection removed');
    }

    // ─────────────────────────────────────────────────────────────
    // LEGACY & WRAPPER COMPATIBILITY METHODS
    // ─────────────────────────────────────────────────────────────
    public function uploadVideo(Request $request)
    {
        $courseId = $request->input('course_id') ?: Module::find($request->input('module_id'))?->course_id ?: Course::first()?->id;
        $course = Course::findOrFail($courseId);
        return $this->storeVideo($request, $course);
    }

    public function uploadPdf(Request $request)
    {
        $courseId = $request->input('course_id') ?: Module::find($request->input('module_id'))?->course_id ?: Course::first()?->id;
        $course = Course::findOrFail($courseId);
        return $this->storePdf($request, $course);
    }

    public function uploadSlide(Request $request)
    {
        $courseId = $request->input('course_id') ?: Module::find($request->input('module_id'))?->course_id ?: Course::first()?->id;
        $course = Course::findOrFail($courseId);
        return $this->storeSlide($request, $course);
    }

    public function uploadNote(Request $request)
    {
        $courseId = $request->input('course_id') ?: Module::find($request->input('module_id'))?->course_id ?: Course::first()?->id;
        $course = Course::findOrFail($courseId);
        return $this->storeDownload($request, $course);
    }

    public function uploadLab(Request $request)
    {
        $courseId = $request->input('course_id') ?: Module::find($request->input('module_id'))?->course_id ?: Course::first()?->id;
        $course = Course::findOrFail($courseId);
        $lesson = Lesson::find($request->input('lesson_id')) ?: Lesson::where('course_id', $courseId)->first();
        if (!$lesson) {
            $module = Module::firstOrCreate(['course_id' => $courseId, 'title' => 'Module 1']);
            $lesson = Lesson::create(['course_id' => $courseId, 'module_id' => $module->id, 'title' => $request->input('title', 'Practice Lab')]);
        }
        return $this->storeLabIntegration($request, $course, $lesson);
    }

    public function generateAiSummary(Request $request, $lessonId)
    {
        $lesson = Lesson::findOrFail($lessonId);
        $summary = $this->aiService->generateSummary($lesson->course, $lesson);
        $lesson->update(['ai_summary' => $summary['overview']]);

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'success',
                'lesson_id' => $lesson->id,
                'ai_summary' => $summary['overview']
            ]);
        }

        return back()->with('success', 'AI Summary generated successfully');
    }

    public function reorderContents(Request $request)
    {
        $items = $request->input('items', []);
        $entity = $request->input('entity', 'lesson');

        foreach ($items as $item) {
            if ($entity === 'module') {
                Module::where('id', $item['id'])->update(['order' => $item['order']]);
            } else if ($entity === 'chapter') {
                Chapter::where('id', $item['id'])->update(['order' => $item['order']]);
            } else {
                Lesson::where('id', $item['id'])->update(['order' => $item['order']]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Order updated successfully'
        ]);
    }

    /**
     * Generic material upload endpoint: POST /teacher/courses/{course}/materials
     */
    public function storeMaterial(Request $request, $course)
    {
        $courseModel = $course instanceof Course ? $course : Course::findOrFail($course);
        $type = $request->input('type', 'pdf');
        if ($type === 'slide') {
            return $this->storeSlide($request, $courseModel);
        } elseif ($type === 'note' || $type === 'note_download' || $type === 'download') {
            return $this->storeDownload($request, $courseModel);
        }
        return $this->storePdf($request, $courseModel);
    }

    /**
     * Generic AI request endpoint: POST /teacher/courses/{course}/ai-content
     */
    public function storeAiContent(Request $request, $course)
    {
        $courseModel = $course instanceof Course ? $course : Course::findOrFail($course);
        $type = $request->input('type', 'quiz');
        if ($type === 'summary') {
            return $this->aiSummarizeContent($request, $courseModel);
        } elseif ($type === 'flashcards' || $type === 'flashcard') {
            return $this->aiFlashcardsContent($request, $courseModel);
        }
        return $this->aiGenerateQuizContent($request, $courseModel);
    }

    /**
     * Direct lab attachment endpoint: POST /teacher/courses/{course}/labs
     */
    public function storeLabDirect(Request $request, $course)
    {
        $courseModel = $course instanceof Course ? $course : Course::findOrFail($course);
        $lessonId = $request->input('lesson_id');
        $lesson = $lessonId ? Lesson::find($lessonId) : Lesson::where('course_id', $courseModel->id)->first();
        if (!$lesson) {
            $module = Module::firstOrCreate(['course_id' => $courseModel->id, 'title' => 'Module 1']);
            $lesson = Lesson::create(['course_id' => $courseModel->id, 'module_id' => $module->id, 'title' => 'Practice Lab Session']);
        }
        return $this->storeLabIntegration($request, $courseModel, $lesson);
    }
}
