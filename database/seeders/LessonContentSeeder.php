<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\LessonResource;
use App\Models\LessonComment;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class LessonContentSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::where('role', 'teacher')->first() ?: User::first();
        $student = User::where('email', 'kosalsensok582@gmail.com')->first();

        // ─────────────────────────────────────────────────────────────
        // 1. WEB DEVELOPMENT WITH VUE & LARAVEL (CRS-IT-WD101)
        // ─────────────────────────────────────────────────────────────
        $webCourse = Course::where('code', 'CRS-IT-WD101')
            ->orWhere('title', 'like', '%Web Development with Vue%')
            ->first();

        if ($webCourse) {
            // Clean up any stray C Syntax or generic template lessons mistakenly assigned to Web Dev
            Lesson::where('course_id', $webCourse->id)
                ->where(function($q) {
                    $q->where('title', 'like', '%C Syntax%')
                      ->orWhere('title', 'like', 'Unit %');
                })
                ->delete();

            Module::where('course_id', $webCourse->id)
                ->where('title', 'like', '%Fundamental Concepts%')
                ->delete();

            // Module 1: Frontend Architecture & Vue 3
            $mod1 = Module::updateOrCreate([
                'course_id' => $webCourse->id,
                'title'     => 'Module 1: Frontend Architecture & Vue 3',
            ], [
                'kh_title'    => 'ម៉ូឌុល ១៖ ស្ថាបត្យកម្ម Frontend និង Vue 3',
                'description' => 'មូលដ្ឋានគ្រឹះស្ថាបត្យកម្ម Fullstack និងការបង្កើត Reactive Components ជាមួយ Vue 3 Composition API',
                'order'       => 1,
            ]);

            // Module 2: Laravel Backend & Inertia Integration
            $mod2 = Module::updateOrCreate([
                'course_id' => $webCourse->id,
                'title'     => 'Module 2: Laravel Backend & Inertia Integration',
            ], [
                'kh_title'    => 'ម៉ូឌុល ២៖ ប្រព័ន្ធ Backend Laravel និងការតភ្ជាប់ Inertia.js',
                'description' => 'ការរៀបចំ RESTful Endpoints, Eloquent ORM និងការតភ្ជាប់ Seamless SPA ជាមួយ Inertia.js',
                'order'       => 2,
            ]);

            // Define rich lessons for Web Dev
            $webLessons = [
                // Lesson 1
                [
                    'module_id'        => $mod1->id,
                    'course_id'        => $webCourse->id,
                    'title'            => 'Lesson 1.1: Introduction to Fullstack Web Development',
                    'kh_title'         => 'មេរៀន ១.១៖ សេចក្តីផ្តើមអំពីការអភិវឌ្ឍន៍ Fullstack Web ទំនើប',
                    'type'             => 'video',
                    'video_provider'   => 'mp4',
                    'video_url'        => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
                    'preview_url'      => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
                    'duration_seconds' => 900,
                    'order'            => 1,
                    'summary'          => 'ស្វែងយល់អំពីរចនាសម្ព័ន្ធ Web Development ទំនើប ការរៀបចំ Environment សម្រាប់ Vue 3, Vite, Tailwind CSS និងស្ថាបត្យកម្ម Fullstack ជាមួយ Laravel REST API។ វគ្គសិក្សានេះណែនាំអំពីគោលការណ៍គ្រឹះនៃ Client-Server Architecture និងការរៀបចំ Workspace ប្រកបដោយវិជ្ជាជីវៈ។',
                    'objectives'       => [
                        'យល់ដឹងពីស្ថាបត្យកម្ម Client-Server និង Single Page Application (SPA)',
                        'រៀបចំ Node.js, Composer, Vite និង Laravel Development Environment',
                        'ស្វែងយល់ពីទំនាក់ទំនងរវាង Vue Frontend និង Laravel Backend API',
                        'អនុវត្តការដំឡើង និងដំណើរការ First Project នៅលើ Local Server'
                    ],
                    'content'          => "# ស្ថាបត្យកម្ម Fullstack Web Development ទំនើប\n\nសូមស្វាគមន៍មកកាន់មេរៀនដំបូងនៃវគ្គសិក្សា **Web Development with Vue & Laravel**។ នៅក្នុងមេរៀននេះ យើងនឹងស្វែងយល់អំពី:\n\n### ១. ស្ថាបត្យកម្ម Single Page Application (SPA)\nSPA គឺជាបច្ចេកវិទ្យាដែលអនុញ្ញាតឱ្យគេហទំព័រដំណើរការលឿនដូច Native App ដោយមិនចាំបាច់ Reload ទំព័រទាំងមូលឡើងវិញពេលផ្លាស់ប្តូរទិន្នន័យ។\n\n```bash\n# បង្កើត Project Laravel ថ្មី\ncomposer create-project laravel/laravel elms-project\n\n# ដំឡើង Inertia.js និង Vue 3\ncomposer require inertiajs/inertia-laravel\nnpm install @inertiajs/vue3 vue\n```\n\n### ២. អត្ថប្រយោជន៍នៃ Inertia.js\n- មិនបាច់សរសេរ REST API endpoints ដោយឡែក\n- ប្រើប្រាស់ Routing របស់ Laravel ផ្ទាល់\n- សរសេរ UI ជាមួយ Vue 3 Composition API\n\n### ៣. ការរៀបចំ Development Environment\nសូមប្រាកដថាអ្នកបានដំឡើង **PHP 8.3+**, **Composer 2.x**, និង **Node.js 20+** រួចរាល់។",
                    'resources'        => [
                        [
                            'title'         => 'Lecture 1: Web Architecture & Setup Guide (PDF)',
                            'disk_path'     => 'resources/lecture1_setup_guide.pdf',
                            'original_name' => 'lecture1_setup_guide.pdf',
                            'mime'          => 'application/pdf',
                            'size_bytes'    => 2450000,
                            'sort_order'    => 1,
                        ],
                        [
                            'title'         => 'Fullstack Environment Cheatsheet (PDF)',
                            'disk_path'     => 'resources/fullstack_cheatsheet.pdf',
                            'original_name' => 'fullstack_cheatsheet.pdf',
                            'mime'          => 'application/pdf',
                            'size_bytes'    => 1250000,
                            'sort_order'    => 2,
                        ],
                    ],
                    'comments'         => [
                        [
                            'user_name' => 'Dr. Mengly Chea',
                            'role'      => 'teacher',
                            'body'      => 'សូមស្វាគមន៍និស្សិតទាំងអស់មកកាន់មេរៀនទី១! ប្រសិនបើមានចម្ងល់លើការដំឡើង Environment ឬ Node.js សូមសួរនៅក្នុង Comment នេះបាន។',
                            'replies'   => [
                                [
                                    'user_name' => 'Chan Dara Student',
                                    'role'      => 'student',
                                    'body'      => 'អរគុណលោកគ្រូ! ខ្ញុំបាន Setup Node.js និង Composer ជោគជ័យហើយ បទបង្ហាញច្បាស់ល្អណាស់បាទ។',
                                ],
                            ],
                        ],
                    ],
                ],

                // Lesson 2
                [
                    'module_id'        => $mod1->id,
                    'course_id'        => $webCourse->id,
                    'title'            => 'Lesson 1.2: Vue 3 Composition API & Reactive State Management',
                    'kh_title'         => 'មេរៀន ១.២៖ Vue 3 Composition API និងការគ្រប់គ្រង Reactive State',
                    'type'             => 'video',
                    'video_provider'   => 'mp4',
                    'video_url'        => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
                    'preview_url'      => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
                    'duration_seconds' => 1200,
                    'order'            => 2,
                    'summary'          => 'រៀនស៊ីជម្រៅពី Reactive State ក្នុង Vue 3 ដោយប្រើ ref(), reactive(), computed() និង lifecycle hooks សម្រាប់ការបង្កើត Interactive Components ប្រកបដោយប្រសិទ្ធភាពខ្ពស់ និងស្អាតតាមស្តង់ដារ Clean Code។',
                    'objectives'       => [
                        'បែងចែកភាពខុសគ្នារវាង Options API និង Composition API',
                        'ប្រើប្រាស់ ref() និង reactive() ក្នុងការគ្រប់គ្រង Dynamic State',
                        'បង្កើត Computed Properties និង Watchers ដើម្បី Handle Side Effects',
                        'អនុវត្តការសរសេរ Reusable Composables សម្រាប់ Form Validation'
                    ],
                    'content'          => "# Vue 3 Composition API & Reactivity System\n\nComposition API គឺជាវិធីសាស្ត្រទំនើបបំផុតក្នុងការសរសេរ Logic ក្នុង Vue 3 ដែលផ្តល់នូវភាពងាយស្រួលក្នុងការ reuse និងរៀបចំ code។\n\n### ១. ការប្រកាស Reactive State\n\n```vue\n<script setup>\nimport { ref, computed, onMounted } from 'vue'\n\n// Primitive reactive state\nconst count = ref(0)\nconst searchQuery = ref('')\n\n// Computed property (Auto updates)\nconst doubleCount = computed(() => count.value * 2)\n\nconst increment = () => {\n  count.value++\n}\n\nonMounted(() => {\n  console.log('Component mounted successfully!')\n})\n</script>\n\n<template>\n  <div class=\"p-6 rounded-2xl bg-slate-900 text-white\">\n    <p class=\"text-lg font-bold\">Count: {{ count }}</p>\n    <p class=\"text-sm text-purple-400\">Double: {{ doubleCount }}</p>\n    <button @click=\"increment\" class=\"px-4 py-2 mt-3 rounded-xl bg-purple-600 font-semibold\">\n      Add One\n    </button>\n  </div>\n</template>\n```\n\n### ២. គោលការណ៍ ref() ទល់នឹង reactive()\n- **`ref()`**: ស័ក្តិសមសម្រាប់ primitive values (`string`, `number`, `boolean`) និង references ដែលត្រូវការ `.value` ក្នុង script។\n- **`reactive()`**: ប្រើសម្រាប់ object ស្មុគស្មាញ ប៉ុន្តែមិនអាច destructure ដោយផ្ទាល់បានទេបើគ្មាន `toRefs()`។",
                    'resources'        => [
                        [
                            'title'         => 'Vue 3 Composition API Guide & Cheatsheet (PDF)',
                            'disk_path'     => 'resources/vue3_composition_api_guide.pdf',
                            'original_name' => 'vue3_composition_api_guide.pdf',
                            'mime'          => 'application/pdf',
                            'size_bytes'    => 1850000,
                            'sort_order'    => 1,
                        ],
                        [
                            'title'         => 'Reactive Form Sample Source Code (ZIP)',
                            'disk_path'     => 'resources/vue3_reactive_form_sample.zip',
                            'original_name' => 'vue3_reactive_form_sample.zip',
                            'mime'          => 'application/zip',
                            'size_bytes'    => 3200000,
                            'sort_order'    => 2,
                        ],
                    ],
                    'comments'         => [
                        [
                            'user_name' => 'Sothea Rim',
                            'role'      => 'student',
                            'body'      => 'លោកគ្រូ ពេលណាដែលយើងគួរប្រើ ref() ហើយពេលណាគួរប្រើ reactive() បាទ?',
                            'replies'   => [
                                [
                                    'user_name' => 'Dr. Mengly Chea',
                                    'role'      => 'teacher',
                                    'body'      => 'សួស្តីប្អូន! ជាទូទៅ ref() ងាយស្រួលនិងពេញនិយមជាងព្រោះវាគាំទ្រគ្រប់ data types និងអាច destructure ជាមួយ toRefs() បានយ៉ាងងាយស្រួល។',
                                ],
                            ],
                        ],
                    ],
                ],

                // Lesson 3
                [
                    'module_id'        => $mod1->id,
                    'course_id'        => $webCourse->id,
                    'title'            => 'Lesson 1.3: Building Reusable UI Components & Tailwind Styling',
                    'kh_title'         => 'មេរៀន ១.៣៖ ការបង្កើត Reusable UI Components និងការប្រើប្រាស់ Tailwind CSS',
                    'type'             => 'video',
                    'video_provider'   => 'mp4',
                    'video_url'        => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4',
                    'preview_url'      => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4',
                    'duration_seconds' => 1500,
                    'order'            => 3,
                    'summary'          => 'អនុវត្តការបង្កើត Reusable Components, Two-way Data Binding ជាមួយ v-model, និងការរចនា UI ស្អាតទាក់ទាញដោយប្រើ Tailwind CSS Utility Classes។',
                    'objectives'       => [
                        'បង្កើត Reusable UI Components ជាមួយ Props និង Custom Events ($emit)',
                        'អនុវត្ត Custom v-model លើ Input និង Select Components',
                        'រចនា Layout responsive ដោយប្រើ Tailwind CSS Grid & Flexbox',
                        'គ្រប់គ្រង Theme Dark/Light Mode ក្នុង Vue Application'
                    ],
                    'content'          => '# Building Modern UI Components with Tailwind CSS

ការបង្កើត Component ដែលអាចប្រើឡើងវិញបាន (Reusable Components) គឺជាបេះដូងនៃ frontend development ប្រកបដោយប្រសិទ្ធភាព។

### Props & Emits Definition

```vue
<script setup>
defineProps({
  label: { type: String, required: true },
  modelValue: { type: String, default: \'\' },
  error: { type: String, default: null }
})

defineEmits([\'update:modelValue\'])
</script>

<template>
  <div class="space-y-1.5">
    <label class="text-xs font-semibold text-slate-700 dark:text-slate-300">{{ label }}</label>
    <input
      :value="modelValue"
      @input="$emit(\'update:modelValue\', $event.target.value)"
      class="w-full px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm focus:ring-2 focus:ring-purple-500 outline-none transition-all"
    />
    <p v-if="error" class="text-xs text-rose-500">{{ error }}</p>
  </div>
</template>
```',
                    'resources'        => [
                        [
                            'title'         => 'Tailwind UI Component Templates (ZIP)',
                            'disk_path'     => 'resources/tailwind_ui_components.zip',
                            'original_name' => 'tailwind_ui_components.zip',
                            'mime'          => 'application/zip',
                            'size_bytes'    => 4500000,
                            'sort_order'    => 1,
                        ],
                    ],
                    'comments'         => [],
                ],

                // Lesson 4
                [
                    'module_id'        => $mod2->id,
                    'course_id'        => $webCourse->id,
                    'title'            => 'Lesson 2.1: Laravel Routing, Controllers & Eloquent Relationships',
                    'kh_title'         => 'មេរៀន ២.១៖ Laravel Routing, Controllers និង Eloquent Relationships',
                    'type'             => 'video',
                    'video_provider'   => 'mp4',
                    'video_url'        => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
                    'preview_url'      => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
                    'duration_seconds' => 1800,
                    'order'            => 4,
                    'summary'          => 'ការរៀបចំ RESTful Routes, Resource Controllers, និងការប្រើប្រាស់ Eloquent ORM ក្នុងការគ្រប់គ្រង Database Relational Data ប្រកបដោយសុវត្ថិភាព។',
                    'objectives'       => [
                        'យល់ដឹងពី Routing System និង Middleware Protection',
                        'បង្កើត Controllers សម្រាប់ CRUD Operations',
                        'គ្រប់គ្រង Database តាមរយៈ Eloquent Models & Relationships',
                        'ដោះស្រាយបញ្ហា N+1 Queries ដោយប្រើ Eager Loading'
                    ],
                    'content'          => '# Laravel Controllers & Eloquent Relationships

Laravel ផ្តល់នូវ ORM ដ៏មានអំណាចបំផុតគឺ Eloquent សម្រាប់ការទាញ និងគ្រប់គ្រងទិន្នន័យ។

```php
// CourseController.php
public function index(Request $request)
{
    $courses = Course::with([\'teacher:id,name,avatar\', \'modules.lessons\'])
        ->where(\'status\', \'published\')
        ->latest()
        ->paginate(12);

    return Inertia::render(\'Student/Courses/Index\', [
        \'courses\' => $courses,
    ]);
}
```',
                    'resources'        => [
                        [
                            'title'         => 'Laravel Architecture & Eager Loading Guide (PDF)',
                            'disk_path'     => 'resources/laravel_architecture_guide.pdf',
                            'original_name' => 'laravel_architecture_guide.pdf',
                            'mime'          => 'application/pdf',
                            'size_bytes'    => 2700000,
                            'sort_order'    => 1,
                        ],
                    ],
                    'comments'         => [],
                ],

                // Lesson 5
                [
                    'module_id'        => $mod2->id,
                    'course_id'        => $webCourse->id,
                    'title'            => 'Lesson 2.2: Inertia.js Fullstack Architecture & Data Passing',
                    'kh_title'         => 'មេរៀន ២.២៖ ស្ថាបត្យកម្ម Inertia.js Fullstack និងការបញ្ជូនទិន្នន័យ',
                    'type'             => 'video',
                    'video_provider'   => 'mp4',
                    'video_url'        => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerFun.mp4',
                    'preview_url'      => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerFun.mp4',
                    'duration_seconds' => 1600,
                    'order'            => 5,
                    'summary'          => 'ស្វែងយល់ពីរបៀបដែល Inertia.js ភ្ជាប់ Laravel Backend ជាមួយ Vue Frontend ដោយមិនចាំបាច់សរសេរ REST API endpoints ដាច់ដោយឡែក។',
                    'objectives'       => [
                        'យល់ដឹងពីគោលការណ៍ Inertia Protocol និង Single-page Experience',
                        'បញ្ជូនទិន្នន័យពី Laravel Controllers ទៅកាន់ Vue Pages តាម Inertia::render',
                        'ប្រើប្រាស់ router.visit, router.post និង Partial Reloads',
                        'គ្រប់គ្រង Shared Props, Auth Sessions និង Global Flash Messages'
                    ],
                    'content'          => "# Inertia.js: The Modern Monolith Architecture\n\nInertia.js គឺជាបច្ចេកវិទ្យាដែលលុបបំបាត់ការសរសេរ REST APIs ស្មុគស្មាញ និងជំនួសដោយ Controller-to-Component rendering ដោយរក្សាបាននូវ SPA speed។",
                    'resources'        => [],
                    'comments'         => [],
                ],

                // Lesson 6
                [
                    'module_id'        => $mod2->id,
                    'course_id'        => $webCourse->id,
                    'title'            => 'Lesson 2.3: Authentication, Role Security & Production Deployment',
                    'kh_title'         => 'មេរៀន ២.៣៖ ប្រព័ន្ធសុវត្ថិភាព Authentication និងការ Deploy ទៅកាន់ Production',
                    'type'             => 'video',
                    'video_provider'   => 'mp4',
                    'video_url'        => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/TearsOfSteel.mp4',
                    'preview_url'      => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/TearsOfSteel.mp4',
                    'duration_seconds' => 2000,
                    'order'            => 6,
                    'summary'          => 'ការរៀបចំប្រព័ន្ធសុវត្ថិភាព Authentication, Role-based Access Control (RBAC), និងការ Deploy កម្មវិធីទៅកាន់ Production Server (Docker/Cloud)។',
                    'objectives'       => [
                        'អនុវត្ត Security Middleware និង Role Checking',
                        'ការពារប្រឆាំងនឹង CSRF, XSS និង SQL Injection',
                        'បង្កើត Production Build ជាមួយ Vite Optimization',
                        'Deploy កម្មវិធីឡើង Cloud Hosting ជាមួយ Docker Containerization'
                    ],
                    'content'          => "# Production Security & Deployment\n\nការធានាសុវត្ថិភាពទិន្នន័យ និងការ Deploy ទៅកាន់ Production ជាមួយ Docker និង Nginx។",
                    'resources'        => [],
                    'comments'         => [],
                ],
            ];

            foreach ($webLessons as $lData) {
                $resources = $lData['resources'];
                $comments = $lData['comments'];
                unset($lData['resources'], $lData['comments']);

                $lesson = Lesson::updateOrCreate([
                    'course_id' => $webCourse->id,
                    'title'     => $lData['title'],
                ], $lData);

                // Resources
                foreach ($resources as $res) {
                    LessonResource::updateOrCreate([
                        'lesson_id' => $lesson->id,
                        'title'     => $res['title'],
                    ], $res);
                }

                // Comments
                foreach ($comments as $comm) {
                    $commentUser = User::where('name', $comm['user_name'])->first() ?: $teacher;
                    $parentComment = LessonComment::updateOrCreate([
                        'lesson_id' => $lesson->id,
                        'user_id'   => $commentUser->id,
                        'body'      => $comm['body'],
                    ]);

                    foreach ($comm['replies'] as $rep) {
                        $replyUser = User::where('name', $rep['user_name'])->first() ?: $student;
                        LessonComment::updateOrCreate([
                            'lesson_id' => $lesson->id,
                            'user_id'   => $replyUser->id,
                            'parent_id' => $parentComment->id,
                            'body'      => $rep['body'],
                        ]);
                    }
                }
            }
        }

        // ─────────────────────────────────────────────────────────────
        // 2. C PROGRAMMING BASICS (CRS-IT-CP101)
        // ─────────────────────────────────────────────────────────────
        $cCourse = Course::where('code', 'CRS-IT-CP101')
            ->orWhere('title', 'like', '%C Programming%')
            ->first();

        if ($cCourse) {
            $cMod = Module::updateOrCreate([
                'course_id' => $cCourse->id,
                'title'     => 'Module 1: C Programming Fundamentals',
            ], [
                'kh_title'    => 'ម៉ូឌុល ១៖ មូលដ្ឋានគ្រឹះនៃភាសា C Programming',
                'description' => 'ស្វែងយល់អំពីរចនាសម្ព័ន្ធ Compiler, Syntax, Data Types និង Memory Management',
                'order'       => 1,
            ]);

            $cLesson = Lesson::updateOrCreate([
                'course_id' => $cCourse->id,
                'title'     => 'Lab 1.1: Hello World & C Syntax Basics',
            ], [
                'module_id'        => $cMod->id,
                'kh_title'         => 'មន្ទីរពិសោធន៍ ១.១៖ សេចក្តីផ្តើមអំពី C Syntax និងកម្មវិធី Hello World',
                'type'             => 'video',
                'video_provider'   => 'mp4',
                'video_url'        => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerJoyBlazes.mp4',
                'preview_url'      => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerJoyBlazes.mp4',
                'duration_seconds' => 600,
                'order'            => 1,
                'summary'          => 'ស្វែងយល់អំពីមូលដ្ឋានគ្រឹះនៃភាសា C Syntax ការប្រកាសអថេរ Data Types និងរចនាសម្ព័ន្ធកម្មវិធីដំបូង Hello World ជាមួយ GCC Compiler។',
                'objectives'       => [
                    'ស្វែងយល់ពី Compiler និង Execution Lifecycle នៃភាសា C',
                    'សរសេរកម្មវិធីដំបូង Hello World ជាមួយ printf()',
                    'យល់ដឹងពី Primitive Data Types (int, float, char)',
                    'ដោះស្រាយ Error Syntax ទូទៅក្នុងការ Compile'
                ],
                'content'          => "# Introduction to C Programming Syntax\n\nភាសា C គឺជាភាសាមូលដ្ឋានដ៏សំខាន់សម្រាប់វិស្វកម្មកុំព្យូទ័រ។\n\n```c\n#include <stdio.h>\n\nint main() {\n    printf(\"Hello, World from Saint Paul Institute!\\n\");\n    return 0;\n}\n```",
            ]);

            LessonResource::updateOrCreate([
                'lesson_id' => $cLesson->id,
                'title'     => 'C Programming Cheat Sheet & GCC Guide (PDF)',
            ], [
                'disk_path'     => 'resources/c_syntax_cheatsheet.pdf',
                'original_name' => 'c_syntax_cheatsheet.pdf',
                'mime'          => 'application/pdf',
                'size_bytes'    => 1150000,
                'sort_order'    => 1,
            ]);
        }

        // ─────────────────────────────────────────────────────────────
        // 3. SET UP REALISTIC LEARNING PROGRESS FOR KOSAL SENSOK
        // ─────────────────────────────────────────────────────────────
        if ($student && $webCourse) {
            // Ensure Active Enrollment
            Enrollment::updateOrCreate([
                'student_id' => $student->id,
                'course_id'  => $webCourse->id,
            ], [
                'status'      => 'active',
                'enrolled_at' => now()->subDays(15),
                'updated_at'  => now(),
            ]);

            $lessons = $webCourse->lessons()->orderBy('order')->get();

            if ($lessons->count() >= 2) {
                // Lesson 1: Completed
                LessonProgress::updateOrCreate([
                    'user_id'   => $student->id,
                    'lesson_id' => $lessons[0]->id,
                ], [
                    'seconds_watched'   => $lessons[0]->duration_seconds ?: 900,
                    'percent'           => 100,
                    'completed_at'      => now()->subDays(2),
                    'client_updated_at' => now()->subDays(2),
                ]);

                // Lesson 2: Current In-Progress (Watched 450s / 1200s = 38%)
                LessonProgress::updateOrCreate([
                    'user_id'   => $student->id,
                    'lesson_id' => $lessons[1]->id,
                ], [
                    'seconds_watched'   => 450,
                    'percent'           => 38,
                    'completed_at'      => null,
                    'client_updated_at' => now(),
                    'updated_at'        => now(),
                ]);

                // Clear future lessons so they are cleanly unlocked/upcoming
                for ($i = 2; $i < $lessons->count(); $i++) {
                    LessonProgress::where('user_id', $student->id)
                        ->where('lesson_id', $lessons[$i]->id)
                        ->delete();
                }
            }

            // Flush curriculum caches
            Cache::forget("course_{$webCourse->id}_user_{$student->id}_curriculum_v4");
            Cache::forget("course_{$webCourse->id}_user_{$student->id}_curriculum_v3");
            Cache::forget("course_{$webCourse->id}_user_{$student->id}_curriculum");
            Cache::forget("student_dashboard_payload_{$student->id}");
        }

        // ─────────────────────────────────────────────────────────────
        // 4. BACKFILL ALL REMAINING LESSONS WITHOUT VIDEO
        // ─────────────────────────────────────────────────────────────
        $emptyLessons = Lesson::whereNull('video_url')->orWhere('video_url', '')->get();
        $sampleVideos = [
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4',
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerFun.mp4',
            'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/TearsOfSteel.mp4',
        ];

        foreach ($emptyLessons as $idx => $el) {
            $vid = $sampleVideos[$idx % count($sampleVideos)];
            $el->video_provider = 'mp4';
            $el->video_url = $vid;
            $el->preview_url = $vid;
            if (!$el->duration_seconds) {
                $el->duration_seconds = 1200;
            }
            if (!$el->summary) {
                $el->summary = "ស្វែងយល់អំពីខ្លឹមសារសំខាន់ៗនៃមេរៀន {$el->title} ការអនុវត្តជាក់ស្តែង និងគោលការណ៍គ្រឹះក្នុងការសិក្សាស្រាវជ្រាវ។";
            }
            if (!$el->objectives) {
                $el->objectives = [
                    "ស្វែងយល់ពីគោលគំនិតចម្បងនៃមេរៀន {$el->title}",
                    "អនុវត្តការដោះស្រាយលំហាត់ និងកិច្ចការជាក់ស្តែង",
                    "ពង្រឹងសមត្ថភាពក្នុងការវិភាគ និងការអនុវត្តការងារ",
                ];
            }
            if (!$el->content) {
                $el->content = "# មេរៀន៖ {$el->title}\n\nសូមស្វាគមន៍មកកាន់មេរៀននេះ! សូមតាមដានវីដេអូបង្រៀនឱ្យបានចប់សព្វគ្រប់ និងអនុវត្តតាមការណែនាំរបស់សាស្ត្រាចារ្យ។\n\n### គោលបំណងសំខាន់\n- យល់ដឹងពីទ្រឹស្តីគ្រឹះ\n- អនុវត្តលំហាត់គំរូ\n- សួរសំណួរនៅក្នុងផ្ទាំងពិភាក្សា (Discussion) ប្រសិនបើមានចម្ងល់។";
            }
            $el->save();
        }
    }
}
