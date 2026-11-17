<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CourseMultilingualSeeder extends Seeder
{
    /**
     * Seed authentic Khmer translations, levels, thumbnails, instructors, and lessons for catalog courses.
     */
    public function run(): void
    {
        if (!Schema::hasTable('courses')) {
            return;
        }

        // 1. Clean any stray pitaja username/display values and restore authentic instructor names
        User::where('name', 'like', '%pitaja%')
            ->orWhere('name_kh', 'like', '%pitaja%')
            ->orWhere('email', 'like', '%pitaja%')
            ->orWhere(function ($q) {
                $q->where('id', 21)->where('role', 'teacher');
            })
            ->update([
                'name'    => 'Dr. Mengly Chea',
                'name_kh' => 'បណ្ឌិត ម៉េងលី ជា',
            ]);

        $teacherTranslations = [
            'Dr. Mengly Chea'   => 'បណ្ឌិត ម៉េងលី ជា',
            'Mr. Vuthy Seng'    => 'លោក សេង វុទ្ធី',
            'Dr. Meas Sokha'    => 'បណ្ឌិត មាស សុខា',
            'Dr. Sokha Meas'    => 'បណ្ឌិត មាស សុខា',
            'Mr. Sophea Chea'   => 'លោក ជា សុភា',
            'Mr. Chea Sothea'   => 'លោក ជា សុភា',
            'Sophea Teacher'    => 'លោក ជា សុភា',
            'Mr. Sophea'        => 'លោក ជា សុភា',
            'Mrs. Pich Sreyneng'=> 'អ្នកគ្រូ ពេជ្រ ស្រីនាង',
            'Ms. Sreyneang Pich'=> 'អ្នកគ្រូ ពេជ្រ ស្រីនាង',
            'Mrs. Meng Linda'   => 'អ្នកគ្រូ ម៉េង លីនដា',
            'Ms. Linda Meng'    => 'អ្នកគ្រូ ម៉េង លីនដា',
            'Dr. Chea Virak'    => 'បណ្ឌិត ជា វីរៈ',
            'Dr. Virak Chea'    => 'បណ្ឌិត ជា វីរៈ',
            'Dr. Heng Chantha'  => 'បណ្ឌិត ហេង ចាន់ថា',
            'Mrs. Kim Vanna'    => 'អ្នកគ្រូ គីម វណ្ណា',
            'Ms. Kim Vanna'     => 'អ្នកគ្រូ គីម វណ្ណា',
            'Mr. Ouk Daneth'    => 'លោក អ៊ុក ដានេត',
        ];

        foreach ($teacherTranslations as $enName => $khName) {
            User::where('name', 'like', "%{$enName}%")
                ->update(['name_kh' => $khName]);
        }

        // Known secondary teacher & student records
        User::where('email', 'vuthy.seng@spilms.tech')->update(['name_kh' => 'លោក សេង វុទ្ធី']);
        User::where('email', 'teacher@elms.com')->update(['name_kh' => 'លោក ជា សុភា']);
        User::where('email', 'ounmengly3@gmail.com')
            ->orWhere('name', 'Mengly Khun')
            ->update(['name_kh' => 'ម៉េងលី ឃុន']);

        // Ensure remaining teachers with empty/pitaja name_kh receive a clean Khmer name
        User::where('role', 'teacher')
            ->where(function ($q) {
                $q->whereNull('name_kh')
                    ->orWhere('name_kh', '')
                    ->orWhere('name_kh', 'like', '%pitaja%');
            })
            ->update(['name_kh' => 'បណ្ឌិត ម៉េងលី ជា']);

        // 2. Course catalog metadata: Khmer title/desc, accurate subject thumbnails, and levels
        $byCode = [
            'CRS-IT-WD101' => [
                'title_kh'       => 'ការអភិវឌ្ឍគេហទំព័រជាមួយ Vue និង Laravel',
                'description_kh' => 'ការអភិវឌ្ឍកម្មវិធីគេហទំព័រពេញលេញ (Fullstack) ចាប់ពីមូលដ្ឋាន JavaScript រហូតដល់ការតភ្ជាប់ប្រព័ន្ធ API ផ្លូវការ។',
                'thumbnail'      => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=600&q=80',
                'level'          => 'intermediate',
            ],
            'CRS-IT-CP101' => [
                'title_kh'       => 'មូលដ្ឋានគ្រឹះនៃការសរសេរកូដ C',
                'description_kh' => 'គោលការណ៍គ្រឹះនៃការសរសេរកម្មវិធី ការគ្រប់គ្រង Memory Pointer និងរចនាសម្ព័ន្ធម៉ូឌុលកូដ។',
                'thumbnail'      => 'https://images.unsplash.com/photo-1515879218367-8466d910aaa4?auto=format&fit=crop&w=600&q=80',
                'level'          => 'beginner',
            ],
            'CRS-TH-TB101' => [
                'title_kh'       => 'មូលដ្ឋានគ្រឹះទេសចរណ៍ និងបដិសណ្ឋារកិច្ច',
                'description_kh' => 'គោលការណ៍សេវាកម្មបដិសណ្ឋារកិច្ច សីលធម៌អេកូទេសចរណ៍ និងការគ្រប់គ្រងទំនាក់ទំនងអតិថិជន។',
                'thumbnail'      => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=600&q=80',
                'level'          => 'beginner',
            ],
            'CRS-TM-TB101' => [
                'title_kh'       => 'មូលដ្ឋានគ្រឹះទេសចរណ៍ និងបដិសណ្ឋារកិច្ច',
                'description_kh' => 'គោលការណ៍សេវាកម្មបដិសណ្ឋារកិច្ច សីលធម៌អេកូទេសចរណ៍ និងការគ្រប់គ្រងទំនាក់ទំនងអតិថិជន។',
                'thumbnail'      => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=600&q=80',
                'level'          => 'beginner',
            ],
            'CRS-EL-EG101' => [
                'title_kh'       => 'ជំនាញវេយ្យាករណ៍ភាសាអង់គ្លេសកម្រិតខ្ពស់',
                'description_kh' => 'ការស្ថាបនាល្បះកម្រិតខ្ពស់ សញ្ញាវណ្ណយុត្តិក្នុងការសិក្សាស្រាវជ្រាវ និងទម្រង់រចនាសម្ព័ន្ធវេយ្យាករណ៍។',
                'thumbnail'      => 'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?auto=format&fit=crop&w=600&q=80',
                'level'          => 'intermediate',
            ],
            'CRS-AG-PS101' => [
                'title_kh'       => 'មូលដ្ឋានគ្រឹះវិទ្យាសាស្ត្ររុក្ខជាតិ',
                'description_kh' => 'បច្ចេកទេសក្សេត្រសាស្ត្រសម័យទំនើប រោគវិទ្យារុក្ខជាតិ និងការថែរក្សាសារធាតុចិញ្ចឹមក្នុងដី។',
                'thumbnail'      => 'https://images.unsplash.com/photo-1530836369250-ef72a3f5cda8?auto=format&fit=crop&w=600&q=80',
                'level'          => 'beginner',
            ],
            'CRS-SW-SW101' => [
                'title_kh'       => 'សេចក្តីណែនាំអំពីការងារសង្គម',
                'description_kh' => 'ការពង្រឹងសមត្ថភាពសហគមន៍ ការដោះស្រាយករណីសុខុមាលភាព និងការអនុវត្តប្រឹក្សាយោបល់ជាក់ស្តែង។',
                'thumbnail'      => 'https://images.unsplash.com/photo-1582213782179-e0d53f98f2ca?auto=format&fit=crop&w=600&q=80',
                'level'          => 'beginner',
            ],
            'CRS-IT-DSA102' => [
                'title_kh'       => 'រចនាសម្ព័ន្ធទិន្នន័យ និងក្បួនដោះស្រាយជាមួយ Java',
                'description_kh' => 'ការសិក្សាស៊ីជម្រៅអំពីកម្រិតស្មុគស្មាញ Big-O, Binary Search Tree, Graph Traversal និង Dynamic Programming។',
                'thumbnail'      => 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=600&q=80',
                'level'          => 'advanced',
            ],
            'CRS-SW-CD102' => [
                'title_kh'       => 'ការអភិវឌ្ឍសហគមន៍ និងការតស៊ូមតិ',
                'description_kh' => 'យុទ្ធសាស្ត្រការងារចុះមូលដ្ឋាន កិច្ចប្រជុំភាគីពាក់ព័ន្ធ និងកម្មវិធីលើកកម្ពស់សេដ្ឋកិច្ចសង្គម។',
                'thumbnail'      => 'https://images.unsplash.com/photo-1531206715517-5c0ba140b2b8?auto=format&fit=crop&w=600&q=80',
                'level'          => 'intermediate',
            ],
            'CRS-IT-MAD101' => [
                'title_kh'       => 'ការអភិវឌ្ឍកម្មវិធីទូរស័ព្ទជាមួយ Flutter',
                'description_kh' => 'ការបង្កើតកម្មវិធីទូរស័ព្ទឆ្លងប្រព័ន្ធ (Cross-Platform) ជាមួយ Flutter និង Dart ការគ្រប់គ្រង State និង Firebase Auth។',
                'thumbnail'      => 'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?auto=format&fit=crop&w=600&q=80',
                'level'          => 'intermediate',
            ],
        ];

        $byTitle = [
            'Web Development with Vue & Laravel' => $byCode['CRS-IT-WD101'],
            'C Programming Basics'               => $byCode['CRS-IT-CP101'],
            'Tourism Basics & Hospitality'       => $byCode['CRS-TH-TB101'],
            'English Grammar Mastery'            => $byCode['CRS-EL-EG101'],
            'Plant Science Fundamentals'         => $byCode['CRS-AG-PS101'],
            'Introduction to Social Work'        => $byCode['CRS-SW-SW101'],
            'Data Structures & Algorithms'       => $byCode['CRS-IT-DSA102'],
            'Community Development & Advocacy'   => $byCode['CRS-SW-CD102'],
            'Mobile App Development with Flutter'=> $byCode['CRS-IT-MAD101'],
            'Web & Mobile Development' => [
                'title_kh'       => 'ការអភិវឌ្ឍគេហទំព័រ និងកម្មវិធីទូរស័ព្ទ',
                'description_kh' => 'ការសិក្សាអំពីការរចនា និងបង្កើតកម្មវិធី Web និង Mobile ទំនើបប្រកបដោយប្រសិទ្ធភាពខ្ពស់។',
                'thumbnail'      => 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?auto=format&fit=crop&w=600&q=80',
                'level'          => 'intermediate',
            ],
            'Community Social Work' => [
                'title_kh'       => 'ការងារសង្គមក្នុងសហគមន៍',
                'description_kh' => 'ការអនុវត្តការងារសង្គម គម្រោងអភិវឌ្ឍន៍សហគមន៍ និងការគាំពារសុខុមាលភាពប្រជាពលរដ្ឋ។',
                'thumbnail'      => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?auto=format&fit=crop&w=600&q=80',
                'level'          => 'beginner',
            ],
            'Modern Crop Agronomy' => [
                'title_kh'       => 'ក្សេត្រសាស្ត្រដំណាំសម័យទំនើប',
                'description_kh' => 'បច្ចេកវិទ្យាដាំដុះដំណាំបែបទំនើប ការគ្រប់គ្រងជីជាតិដី និងការការពារដំណាំប្រកបដោយចីរភាព។',
                'thumbnail'      => 'https://images.unsplash.com/photo-1500937386664-56d1dfef3854?auto=format&fit=crop&w=600&q=80',
                'level'          => 'intermediate',
            ],
            'Eco-Tourism & Hospitality' => [
                'title_kh'       => 'អេកូទេសចរណ៍ និងបដិសណ្ឋារកិច្ច',
                'description_kh' => 'ការគ្រប់គ្រងតំបន់អេកូទេសចរណ៍ ការអភិរក្សបរិស្ថាន និងសេវាកម្មទទួលបដិសណ្ឋារកិច្ចលំដាប់ខ្ពស់។',
                'thumbnail'      => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=600&q=80',
                'level'          => 'beginner',
            ],
            'Business English Communication' => [
                'title_kh'       => 'ការទំនាក់ទំនងភាសាអង់គ្លេសពាណិជ្ជកម្ម',
                'description_kh' => 'ការបង្កើនប្រសិទ្ធភាពនៃការទំនាក់ទំនង ការចរចាពាណិជ្ជកម្ម និងការធ្វើបទបង្ហាញជាភាសាអង់គ្លេស។',
                'thumbnail'      => 'https://images.unsplash.com/photo-1557804506-669a67965ba0?auto=format&fit=crop&w=600&q=80',
                'level'          => 'intermediate',
            ],
            'Advanced Machine Learning & AI' => [
                'title_kh'       => 'បញ្ញាសិប្បនិម្មិត និងម៉ាស៊ីនរៀនកម្រិតខ្ពស់ (AI)',
                'description_kh' => 'ការសិក្សាស្រាវជ្រាវអំពី Neural Networks, Deep Learning និងការបង្កើតគំរូ AI សម្រាប់ដោះស្រាយបញ្ហាជាក់ស្តែង។',
                'thumbnail'      => 'https://images.unsplash.com/photo-1620712943543-bcc4688e7485?auto=format&fit=crop&w=600&q=80',
                'level'          => 'advanced',
            ],
            'Clinical Social Work Case Studies' => [
                'title_kh'       => 'ករណីសិក្សាការងារសង្គមគ្លីនិក',
                'description_kh' => 'ការវិភាគករណីសិក្សាជាក់ស្តែងក្នុងវិស័យគ្លីនិក ការព្យាបាលផ្លូវចិត្ត និងការគាំទ្រអ្នកជំងឺ។',
                'thumbnail'      => 'https://images.unsplash.com/photo-1573497620053-ea5300f94f21?auto=format&fit=crop&w=600&q=80',
                'level'          => 'advanced',
            ],
            'Organic Crop Agronomy Practices' => [
                'title_kh'       => 'ការអនុវត្តក្សេត្រសាស្ត្រដំណាំសរីរាង្គ',
                'description_kh' => 'វិធីសាស្ត្រដាំដុះដំណាំសរីរាង្គ ការកាត់បន្ថយសារធាតុគីមី និងស្តង់ដារកសិកម្មសុវត្ថិភាព។',
                'thumbnail'      => 'https://images.unsplash.com/photo-1625246333195-78d9c38ad449?auto=format&fit=crop&w=600&q=80',
                'level'          => 'intermediate',
            ],
            'Sustainable Eco-Tourism Operations' => [
                'title_kh'       => 'ប្រតិបត្តិការអេកូទេសចរណ៍ប្រកបដោយចីរភាព',
                'description_kh' => 'យុទ្ធសាស្ត្រដំណើរការអាជីវកម្មទេសចរណ៍បៃតង ការចូលរួមរបស់សហគមន៍ និងការអភិវឌ្ឍចីរភាព។',
                'thumbnail'      => 'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?auto=format&fit=crop&w=600&q=80',
                'level'          => 'intermediate',
            ],
            'International Business English' => [
                'title_kh'       => 'ភាសាអង់គ្លេសពាណិជ្ជកម្មអន្តរជាតិ',
                'description_kh' => 'ការសរសេរសំបុត្រពាណិជ្ជកម្ម កិច្ចសន្យាអន្តរជាតិ និងជំនាញទំនាក់ទំនងក្នុងបរិបទសកលលោក។',
                'thumbnail'      => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=600&q=80',
                'level'          => 'advanced',
            ],
        ];

        $hasTitleKh = Schema::hasColumn('courses', 'title_kh');
        $hasDescKh = Schema::hasColumn('courses', 'description_kh');
        $hasLevel = Schema::hasColumn('courses', 'level');
        $hasThumbnail = Schema::hasColumn('courses', 'thumbnail');

        $filterPayload = function (array $meta) use ($hasTitleKh, $hasDescKh, $hasLevel, $hasThumbnail): array {
            $update = [];
            if ($hasTitleKh && !empty($meta['title_kh'])) {
                $update['title_kh'] = $meta['title_kh'];
            }
            if ($hasDescKh && !empty($meta['description_kh'])) {
                $update['description_kh'] = $meta['description_kh'];
            }
            if ($hasThumbnail && !empty($meta['thumbnail'])) {
                $update['thumbnail'] = $meta['thumbnail'];
            }
            if ($hasLevel && !empty($meta['level'])) {
                $update['level'] = $meta['level'];
            }
            return $update;
        };

        foreach ($byCode as $code => $meta) {
            $update = $filterPayload($meta);
            if (!empty($update)) {
                Course::where('code', $code)->update($update);
            }
        }

        // Apply title-based updates AFTER code-based updates so exact titles (like Modern Crop Agronomy) win
        foreach ($byTitle as $title => $meta) {
            $update = $filterPayload($meta);
            if (!empty($update)) {
                Course::where('title', 'like', "%{$title}%")->update($update);
            }
        }

        // Guarantee every course has a valid level for sidebar facets
        if ($hasLevel) {
            Course::whereNull('level')
                ->orWhere('level', '')
                ->update(['level' => 'beginner']);
        }

        // 3. Populate real Module + 3 Lessons + Quiz for any published course that currently has 0 lessons
        if (Schema::hasTable('modules') && Schema::hasTable('lessons')) {
            $modHasKhTitle = Schema::hasColumn('modules', 'kh_title');
            $modHasDesc = Schema::hasColumn('modules', 'description');

            $lesHasKhTitle = Schema::hasColumn('lessons', 'kh_title');
            $lesHasSummary = Schema::hasColumn('lessons', 'summary');
            $lesHasObjectives = Schema::hasColumn('lessons', 'objectives');
            $lesHasPreviewUrl = Schema::hasColumn('lessons', 'preview_url');
            $lesHasVideoProvider = Schema::hasColumn('lessons', 'video_provider');

            $sampleVideos = [
                'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
                'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
                'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4',
            ];

            $publishedCourses = Course::where('status', 'published')->get();
            foreach ($publishedCourses as $cIdx => $course) {
                $existingCount = Lesson::where('course_id', $course->id)->count();
                if ($existingCount > 0) {
                    continue;
                }

                $moduleAttrs = [
                    'course_id' => $course->id,
                    'order'     => 1,
                ];
                $moduleValues = [
                    'title' => 'Module 1: Core Fundamentals & Practice',
                ];
                if ($modHasKhTitle) {
                    $moduleValues['kh_title'] = 'ម៉ូឌុល ១៖ មូលដ្ឋានគ្រឹះ និងការអនុវត្តជាក់ស្តែង';
                }
                if ($modHasDesc) {
                    $moduleValues['description'] = 'សិក្សាអំពីគោលគំនិតស្នូល វិធីសាស្ត្រអនុវត្ត និងករណីសិក្សាសំខាន់ៗសម្រាប់ ' . ($course->title_kh ?: $course->title);
                }

                $module = Module::firstOrCreate($moduleAttrs, $moduleValues);

                $courseTitleEn = $course->title;
                $courseTitleKh = ($hasTitleKh && !empty($course->title_kh)) ? $course->title_kh : $course->title;

                $lessonSpecs = [
                    [
                        'order'            => 1,
                        'duration_seconds' => 1200,
                        'title'            => "Lesson 1.1: Foundations of {$courseTitleEn}",
                        'kh_title'         => "មេរៀន ១.១៖ មូលដ្ឋានគ្រឹះនៃ{$courseTitleKh}",
                        'summary'          => "ស្វែងយល់អំពីនិយមន័យ គោលការណ៍ស្នូល និងក្របខ័ណ្ឌមូលដ្ឋាននៃ {$courseTitleKh}។",
                        'content'          => "# {$courseTitleKh}\n\nសូមស្វាគមន៍មកកាន់មេរៀនទី១! នៅក្នុងមេរៀននេះ អ្នកនឹងស្វែងយល់អំពីគោលការណ៍គ្រឹះ និងរចនាសម្ព័ន្ធសំខាន់ៗនៃមុខវិជ្ជា។",
                        'is_free_preview'  => true,
                    ],
                    [
                        'order'            => 2,
                        'duration_seconds' => 1300,
                        'title'            => "Lesson 1.2: Core Principles & Methodology",
                        'kh_title'         => "មេរៀន ១.២៖ គោលការណ៍ស្នូល និងវិធីសាស្ត្រអនុវត្ត",
                        'summary'          => "ការសិក្សាស៊ីជម្រៅលើវិធីសាស្ត្រ ឧបករណ៍ និងបច្ចេកទេសសំខាន់ៗក្នុង {$courseTitleKh}។",
                        'content'          => "# គោលការណ៍ស្នូល និងវិធីសាស្ត្រអនុវត្ត\n\nអនុវត្តតាមជំហានស្តង់ដារ និងបច្ចេកទេសវិភាគក្នុងវិស័យជំនាញ។",
                        'is_free_preview'  => false,
                    ],
                    [
                        'order'            => 3,
                        'duration_seconds' => 1400,
                        'title'            => "Lesson 1.3: Applied Case Studies & Review",
                        'kh_title'         => "មេរៀន ១.៣៖ ករណីសិក្សាជាក់ស្តែង និងការរំលឹកឡើងវិញ",
                        'summary'          => "ការអនុវត្តករណីសិក្សាជាក់ស្តែង លំហាត់វាយតម្លៃ និងការត្រៀមខ្លួនសម្រាប់ការប្រឡងបញ្ចប់។",
                        'content'          => "# ករណីសិក្សាជាក់ស្តែង\n\nសូមអនុវត្តកិច្ចការគំរូ និងចូលរួមធ្វើ Quiz ដើម្បីវាស់ស្ទង់លទ្ធផលសិក្សារបស់អ្នក។",
                        'is_free_preview'  => false,
                    ],
                ];

                foreach ($lessonSpecs as $lIdx => $spec) {
                    $vid = $sampleVideos[($cIdx + $lIdx) % count($sampleVideos)];
                    $payload = [
                        'module_id'        => $module->id,
                        'course_id'        => $course->id,
                        'title'            => $spec['title'],
                        'type'             => 'video',
                        'video_url'        => $vid,
                        'content'          => $spec['content'],
                        'duration_seconds' => $spec['duration_seconds'],
                        'order'            => $spec['order'],
                        'is_free_preview'  => $spec['is_free_preview'],
                        'downloadable'     => true,
                    ];

                    if ($lesHasKhTitle) {
                        $payload['kh_title'] = $spec['kh_title'];
                    }
                    if ($lesHasSummary) {
                        $payload['summary'] = $spec['summary'];
                    }
                    if ($lesHasObjectives) {
                        $payload['objectives'] = [
                            "យល់ដឹងពីគោលគំនិតស្នូលនៃ {$courseTitleKh}",
                            'អនុវត្តវិធីសាស្ត្រ និងករណីសិក្សាជាក់ស្តែង',
                            'វាយតម្លៃលទ្ធផល និងពង្រឹងជំនាញវិជ្ជាជីវៈ',
                        ];
                    }
                    if ($lesHasPreviewUrl) {
                        $payload['preview_url'] = $vid;
                    }
                    if ($lesHasVideoProvider) {
                        $payload['video_provider'] = 'mp4';
                    }

                    Lesson::updateOrCreate([
                        'course_id' => $course->id,
                        'title'     => $spec['title'],
                    ], $payload);
                }

                // Ensure a published Quiz exists for assessment continuity
                if (Schema::hasTable('quizzes') && !Quiz::where('course_id', $course->id)->exists()) {
                    Quiz::create([
                        'course_id'          => $course->id,
                        'module_id'          => $module->id,
                        'title'              => "Assessment: {$courseTitleEn}",
                        'type'               => 'practice',
                        'time_limit_minutes' => 30,
                        'passing_score'      => 70,
                        'status'             => 'published',
                    ]);
                }
            }
        }

        // Flush browse facets & student payload caches so updates appear immediately
        Cache::flush();
    }
}
