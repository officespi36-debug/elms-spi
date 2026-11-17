<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (!Schema::hasColumn('courses', 'title_kh')) {
                $table->string('title_kh')->nullable()->after('title');
            }
            if (!Schema::hasColumn('courses', 'description_kh')) {
                $table->text('description_kh')->nullable()->after('description');
            }
        });

        // Seed comprehensive authentic Khmer course titles and descriptions
        $courseTranslations = [
            // ID 9: Web Development with Vue & Laravel
            9 => [
                'title_kh'       => 'ការអភិវឌ្ឍគេហទំព័រជាមួយ Vue និង Laravel',
                'description_kh' => 'ការអភិវឌ្ឍកម្មវិធីគេហទំព័រពេញលេញ (Fullstack) ចាប់ពីមូលដ្ឋាន JavaScript រហូតដល់ការតភ្ជាប់ប្រព័ន្ធ API ផ្លូវការ។',
            ],
            // ID 10: C Programming Basics
            10 => [
                'title_kh'       => 'មូលដ្ឋានគ្រឹះនៃការសរសេរកូដ C',
                'description_kh' => 'គោលការណ៍គ្រឹះនៃការសរសេរកម្មវិធី ការគ្រប់គ្រង Memory Pointer និងរចនាសម្ព័ន្ធម៉ូឌុលកូដ។',
            ],
            // ID 11: Tourism Basics & Hospitality
            11 => [
                'title_kh'       => 'មូលដ្ឋានគ្រឹះទេសចរណ៍ និងបដិសណ្ឋារកិច្ច',
                'description_kh' => 'គោលការណ៍សេវាកម្មបដិសណ្ឋារកិច្ច សីលធម៌អេកូទេសចរណ៍ និងការគ្រប់គ្រងទំនាក់ទំនងអតិថិជន។',
            ],
            // ID 12: English Grammar Mastery
            12 => [
                'title_kh'       => 'ជំនាញវេយ្យាករណ៍ភាសាអង់គ្លេសកម្រិតខ្ពស់',
                'description_kh' => 'ការស្ថាបនាល្បះកម្រិតខ្ពស់ សញ្ញាវណ្ណយុត្តិក្នុងការសិក្សាស្រាវជ្រាវ និងទម្រង់រចនាសម្ព័ន្ធវេយ្យាករណ៍។',
            ],
            // ID 13: Plant Science Fundamentals
            13 => [
                'title_kh'       => 'មូលដ្ឋានគ្រឹះវិទ្យាសាស្ត្ររុក្ខជាតិ',
                'description_kh' => 'បច្ចេកទេសក្សេត្រសាស្ត្រសម័យទំនើប រោគវិទ្យារុក្ខជាតិ និងការថែរក្សាសារធាតុចិញ្ចឹមក្នុងដី។',
            ],
            // ID 14: Introduction to Social Work
            14 => [
                'title_kh'       => 'សេចក្តីណែនាំអំពីការងារសង្គម',
                'description_kh' => 'ការពង្រឹងសមត្ថភាពសហគមន៍ ការដោះស្រាយករណីសុខុមាលភាព និងការអនុវត្តប្រឹក្សាយោបល់ជាក់ស្តែង។',
            ],
            // ID 15: Data Structures & Algorithms in Java
            15 => [
                'title_kh'       => 'រចនាសម្ព័ន្ធទិន្នន័យ និងក្បួនដោះស្រាយជាមួយ Java',
                'description_kh' => 'ការសិក្សាស៊ីជម្រៅអំពីកម្រិតស្មុគស្មាញ Big-O, Binary Search Tree, Graph Traversal និង Dynamic Programming។',
            ],
            // ID 17: Community Development & Advocacy
            17 => [
                'title_kh'       => 'ការអភិវឌ្ឍសហគមន៍ និងការតស៊ូមតិ',
                'description_kh' => 'យុទ្ធសាស្ត្រការងារចុះមូលដ្ឋាន កិច្ចប្រជុំភាគីពាក់ព័ន្ធ និងកម្មវិធីលើកកម្ពស់សេដ្ឋកិច្ចសង្គម។',
            ],
            // ID 18: Mobile App Development with Flutter
            18 => [
                'title_kh'       => 'ការអភិវឌ្ឍកម្មវិធីទូរស័ព្ទជាមួយ Flutter',
                'description_kh' => 'ការបង្កើតកម្មវិធីទូរស័ព្ទឆ្លងប្រព័ន្ធ (Cross-Platform) ជាមួយ Flutter និង Dart ការគ្រប់គ្រង State និង Firebase Auth។',
            ],
            // ID 27: Web & Mobile Development
            27 => [
                'title_kh'       => 'ការអភិវឌ្ឍគេហទំព័រ និងកម្មវិធីទូរស័ព្ទ',
                'description_kh' => 'ការសិក្សាអំពីការរចនា និងបង្កើតកម្មវិធី Web និង Mobile ទំនើបប្រកបដោយប្រសិទ្ធភាពខ្ពស់។',
            ],
            // ID 28: Community Social Work
            28 => [
                'title_kh'       => 'ការងារសង្គមក្នុងសហគមន៍',
                'description_kh' => 'ការអនុវត្តការងារសង្គម គម្រោងអភិវឌ្ឍន៍សហគមន៍ និងការគាំពារសុខុមាលភាពប្រជាពលរដ្ឋ។',
            ],
            // ID 29: Modern Crop Agronomy
            29 => [
                'title_kh'       => 'ក្សេត្រសាស្ត្រដំណាំសម័យទំនើប',
                'description_kh' => 'បច្ចេកវិទ្យាដាំដុះដំណាំបែបទំនើប ការគ្រប់គ្រងជីជាតិដី និងការការពារដំណាំប្រកបដោយចីរភាព។',
            ],
            // ID 30: Eco-Tourism & Hospitality
            30 => [
                'title_kh'       => 'អេកូទេសចរណ៍ និងបដិសណ្ឋារកិច្ច',
                'description_kh' => 'ការគ្រប់គ្រងតំបន់អេកូទេសចរណ៍ ការអភិរក្សបរិស្ថាន និងសេវាកម្មទទួលបដិសណ្ឋារកិច្ចលំដាប់ខ្ពស់។',
            ],
            // ID 31: Business English Communication
            31 => [
                'title_kh'       => 'ការទំនាក់ទំនងភាសាអង់គ្លេសពាណិជ្ជកម្ម',
                'description_kh' => 'ការបង្កើនប្រសិទ្ធភាពនៃការទំនាក់ទំនង ការចរចាពាណិជ្ជកម្ម និងការធ្វើបទបង្ហាញជាភាសាអង់គ្លេស។',
            ],
            // ID 32: Advanced Machine Learning & AI
            32 => [
                'title_kh'       => 'បញ្ញាសិប្បនិម្មិត និងម៉ាស៊ីនរៀនកម្រិតខ្ពស់ (AI)',
                'description_kh' => 'ការសិក្សាស្រាវជ្រាវអំពី Neural Networks, Deep Learning និងការបង្កើតគំរូ AI សម្រាប់ដោះស្រាយបញ្ហាជាក់ស្តែង។',
            ],
            // ID 33: Clinical Social Work Case Studies
            33 => [
                'title_kh'       => 'ករណីសិក្សាការងារសង្គមគ្លីនិក',
                'description_kh' => 'ការវិភាគករណីសិក្សាជាក់ស្តែងក្នុងវិស័យគ្លីនិក ការព្យាបាលផ្លូវចិត្ត និងការគាំទ្រអ្នកជំងឺ។',
            ],
            // ID 34: Organic Crop Agronomy Practices
            34 => [
                'title_kh'       => 'ការអនុវត្តក្សេត្រសាស្ត្រដំណាំសរីរាង្គ',
                'description_kh' => 'វិធីសាស្ត្រដាំដុះដំណាំសរីរាង្គ ការកាត់បន្ថយសារធាតុគីមី និងស្តង់ដារកសិកម្មសុវត្ថិភាព។',
            ],
            // ID 35: Sustainable Eco-Tourism Operations
            35 => [
                'title_kh'       => 'ប្រតិបត្តិការអេកូទេសចរណ៍ប្រកបដោយចីរភាព',
                'description_kh' => 'យុទ្ធសាស្ត្រដំណើរការអាជីវកម្មទេសចរណ៍បៃតង ការចូលរួមរបស់សហគមន៍ និងការអភិវឌ្ឍចីរភាព។',
            ],
            // ID 36: International Business English
            36 => [
                'title_kh'       => 'ភាសាអង់គ្លេសពាណិជ្ជកម្មអន្តរជាតិ',
                'description_kh' => 'ការសរសេរសំបុត្រពាណិជ្ជកម្ម កិច្ចសន្យាអន្តរជាតិ និងជំនាញទំនាក់ទំនងក្នុងបរិបទសកលលោក។',
            ],
        ];

        foreach ($courseTranslations as $id => $data) {
            DB::table('courses')
                ->where('id', $id)
                ->update([
                    'title_kh'       => $data['title_kh'],
                    'description_kh' => $data['description_kh'],
                ]);
        }

        // Also ensure teachers have authentic Khmer names
        $teacherTranslations = [
            21 => 'បណ្ឌិត ម៉េងលី ជា',
            30 => 'គ្រូ សុភា',
            42 => 'បណ្ឌិត មាស សុខា',
            43 => 'លោក ជា សុភា',
            44 => 'អ្នកគ្រូ ពេជ្រ ស្រីនាង',
            45 => 'លោក សេង វុទ្ធី',
            46 => 'អ្នកគ្រូ ម៉េង លីនដា',
            47 => 'បណ្ឌិត ជា វីរៈ',
            48 => 'បណ្ឌិត ហេង ចាន់ថា',
            49 => 'អ្នកគ្រូ គីម វណ្ណា',
            50 => 'លោក អ៊ុក ដានេត',
        ];

        foreach ($teacherTranslations as $userId => $nameKh) {
            DB::table('users')
                ->where('id', $userId)
                ->where(function ($q) {
                    $q->whereNull('name_kh')->orWhere('name_kh', '');
                })
                ->update(['name_kh' => $nameKh]);
        }

        DB::table('users')
            ->where('email', 'ounmengly3@gmail.com')
            ->orWhere('name', 'Mengly Khun')
            ->update(['name_kh' => 'ម៉េងលី ឃុន']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            if (Schema::hasColumn('courses', 'title_kh')) {
                $table->dropColumn('title_kh');
            }
            if (Schema::hasColumn('courses', 'description_kh')) {
                $table->dropColumn('description_kh');
            }
        });
    }
};
