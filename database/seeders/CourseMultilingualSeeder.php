<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CourseMultilingualSeeder extends Seeder
{
    /**
     * Seed authentic Khmer translations for courses and teachers.
     */
    public function run(): void
    {
        if (!Schema::hasTable('courses')) {
            return;
        }

        $courseTranslations = [
            // By ID and by Code for maximum resilience
            'CRS-IT-WD101' => [
                'title_kh'       => 'ការអភិវឌ្ឍគេហទំព័រជាមួយ Vue និង Laravel',
                'description_kh' => 'ការអភិវឌ្ឍកម្មវិធីគេហទំព័រពេញលេញ (Fullstack) ចាប់ពីមូលដ្ឋាន JavaScript រហូតដល់ការតភ្ជាប់ប្រព័ន្ធ API ផ្លូវការ។',
            ],
            'CRS-IT-CP101' => [
                'title_kh'       => 'មូលដ្ឋានគ្រឹះនៃការសរសេរកូដ C',
                'description_kh' => 'គោលការណ៍គ្រឹះនៃការសរសេរកម្មវិធី ការគ្រប់គ្រង Memory Pointer និងរចនាសម្ព័ន្ធម៉ូឌុលកូដ។',
            ],
            'CRS-TH-TB101' => [
                'title_kh'       => 'មូលដ្ឋានគ្រឹះទេសចរណ៍ និងបដិសណ្ឋារកិច្ច',
                'description_kh' => 'គោលការណ៍សេវាកម្មបដិសណ្ឋារកិច្ច សីលធម៌អេកូទេសចរណ៍ និងការគ្រប់គ្រងទំនាក់ទំនងអតិថិជន។',
            ],
            'CRS-EL-EG101' => [
                'title_kh'       => 'ជំនាញវេយ្យាករណ៍ភាសាអង់គ្លេសកម្រិតខ្ពស់',
                'description_kh' => 'ការស្ថាបនាល្បះកម្រិតខ្ពស់ សញ្ញាវណ្ណយុត្តិក្នុងការសិក្សាស្រាវជ្រាវ និងទម្រង់រចនាសម្ព័ន្ធវេយ្យាករណ៍។',
            ],
            'CRS-AG-PS101' => [
                'title_kh'       => 'មូលដ្ឋានគ្រឹះវិទ្យាសាស្ត្ររុក្ខជាតិ',
                'description_kh' => 'បច្ចេកទេសក្សេត្រសាស្ត្រសម័យទំនើប រោគវិទ្យារុក្ខជាតិ និងការថែរក្សាសារធាតុចិញ្ចឹមក្នុងដី។',
            ],
            'CRS-SW-SW101' => [
                'title_kh'       => 'សេចក្តីណែនាំអំពីការងារសង្គម',
                'description_kh' => 'ការពង្រឹងសមត្ថភាពសហគមន៍ ការដោះស្រាយករណីសុខុមាលភាព និងការអនុវត្តប្រឹក្សាយោបល់ជាក់ស្តែង។',
            ],
            'CRS-IT-DSA102' => [
                'title_kh'       => 'រចនាសម្ព័ន្ធទិន្នន័យ និងក្បួនដោះស្រាយជាមួយ Java',
                'description_kh' => 'ការសិក្សាស៊ីជម្រៅអំពីកម្រិតស្មុគស្មាញ Big-O, Binary Search Tree, Graph Traversal និង Dynamic Programming។',
            ],
            'CRS-SW-CD102' => [
                'title_kh'       => 'ការអភិវឌ្ឍសហគមន៍ និងការតស៊ូមតិ',
                'description_kh' => 'យុទ្ធសាស្ត្រការងារចុះមូលដ្ឋាន កិច្ចប្រជុំភាគីពាក់ព័ន្ធ និងកម្មវិធីលើកកម្ពស់សេដ្ឋកិច្ចសង្គម។',
            ],
            'CRS-IT-MAD101' => [
                'title_kh'       => 'ការអភិវឌ្ឍកម្មវិធីទូរស័ព្ទជាមួយ Flutter',
                'description_kh' => 'ការបង្កើតកម្មវិធីទូរស័ព្ទឆ្លងប្រព័ន្ធ (Cross-Platform) ជាមួយ Flutter និង Dart ការគ្រប់គ្រង State និង Firebase Auth។',
            ],
        ];

        // Specific mappings by Title matching for maximum compatibility
        $titleTranslations = [
            'Web & Mobile Development' => [
                'title_kh'       => 'ការអភិវឌ្ឍគេហទំព័រ និងកម្មវិធីទូរស័ព្ទ',
                'description_kh' => 'ការសិក្សាអំពីការរចនា និងបង្កើតកម្មវិធី Web និង Mobile ទំនើបប្រកបដោយប្រសិទ្ធភាពខ្ពស់។',
            ],
            'Community Social Work' => [
                'title_kh'       => 'ការងារសង្គមក្នុងសហគមន៍',
                'description_kh' => 'ការអនុវត្តការងារសង្គម គម្រោងអភិវឌ្ឍន៍សហគមន៍ និងការគាំពារសុខុមាលភាពប្រជាពលរដ្ឋ។',
            ],
            'Modern Crop Agronomy' => [
                'title_kh'       => 'ក្សេត្រសាស្ត្រដំណាំសម័យទំនើប',
                'description_kh' => 'បច្ចេកវិទ្យាដាំដុះដំណាំបែបទំនើប ការគ្រប់គ្រងជីជាតិដី និងការការពារដំណាំប្រកបដោយចីរភាព។',
            ],
            'Eco-Tourism & Hospitality' => [
                'title_kh'       => 'អេកូទេសចរណ៍ និងបដិសណ្ឋារកិច្ច',
                'description_kh' => 'ការគ្រប់គ្រងតំបន់អេកូទេសចរណ៍ ការអភិរក្សបរិស្ថាន និងសេវាកម្មទទួលបដិសណ្ឋារកិច្ចលំដាប់ខ្ពស់។',
            ],
            'Business English Communication' => [
                'title_kh'       => 'ការទំនាក់ទំនងភាសាអង់គ្លេសពាណិជ្ជកម្ម',
                'description_kh' => 'ការបង្កើនប្រសិទ្ធភាពនៃការទំនាក់ទំនង ការចរចាពាណិជ្ជកម្ម និងការធ្វើបទបង្ហាញជាភាសាអង់គ្លេស។',
            ],
            'Advanced Machine Learning & AI' => [
                'title_kh'       => 'បញ្ញាសិប្បនិម្មិត និងម៉ាស៊ីនរៀនកម្រិតខ្ពស់ (AI)',
                'description_kh' => 'ការសិក្សាស្រាវជ្រាវអំពី Neural Networks, Deep Learning និងការបង្កើតគំរូ AI សម្រាប់ដោះស្រាយបញ្ហាជាក់ស្តែង។',
            ],
            'Clinical Social Work Case Studies' => [
                'title_kh'       => 'ករណីសិក្សាការងារសង្គមគ្លីនិក',
                'description_kh' => 'ការវិភាគករណីសិក្សាជាក់ស្តែងក្នុងវិស័យគ្លីនិក ការព្យាបាលផ្លូវចិត្ត និងការគាំទ្រអ្នកជំងឺ។',
            ],
            'Organic Crop Agronomy Practices' => [
                'title_kh'       => 'ការអនុវត្តក្សេត្រសាស្ត្រដំណាំសរីរាង្គ',
                'description_kh' => 'វិធីសាស្ត្រដាំដុះដំណាំសរីរាង្គ ការកាត់បន្ថយសារធាតុគីមី និងស្តង់ដារកសិកម្មសុវត្ថិភាព។',
            ],
            'Sustainable Eco-Tourism Operations' => [
                'title_kh'       => 'ប្រតិបត្តិការអេកូទេសចរណ៍ប្រកបដោយចីរភាព',
                'description_kh' => 'យុទ្ធសាស្ត្រដំណើរការអាជីវកម្មទេសចរណ៍បៃតង ការចូលរួមរបស់សហគមន៍ និងការអភិវឌ្ឍចីរភាព។',
            ],
            'International Business English' => [
                'title_kh'       => 'ភាសាអង់គ្លេសពាណិជ្ជកម្មអន្តរជាតិ',
                'description_kh' => 'ការសរសេរសំបុត្រពាណិជ្ជកម្ម កិច្ចសន្យាអន្តរជាតិ និងជំនាញទំនាក់ទំនងក្នុងបរិបទសកលលោក។',
            ],
            'Plant Science Fundamentals' => [
                'title_kh'       => 'មូលដ្ឋានគ្រឹះវិទ្យាសាស្ត្ររុក្ខជាតិ',
                'description_kh' => 'បច្ចេកទេសក្សេត្រសាស្ត្រសម័យទំនើប រោគវិទ្យារុក្ខជាតិ និងការថែរក្សាសារធាតុចិញ្ចឹមក្នុងដី។',
            ],
            'English Grammar Mastery' => [
                'title_kh'       => 'ជំនាញវេយ្យាករណ៍ភាសាអង់គ្លេសកម្រិតខ្ពស់',
                'description_kh' => 'ការស្ថាបនាល្បះកម្រិតខ្ពស់ សញ្ញាវណ្ណយុត្តិក្នុងការសិក្សាស្រាវជ្រាវ និងទម្រង់រចនាសម្ព័ន្ធវេយ្យាករណ៍។',
            ],
            'Tourism Basics & Hospitality' => [
                'title_kh'       => 'មូលដ្ឋានគ្រឹះទេសចរណ៍ និងបដិសណ្ឋារកិច្ច',
                'description_kh' => 'គោលការណ៍សេវាកម្មបដិសណ្ឋារកិច្ច សីលធម៌អេកូទេសចរណ៍ និងការគ្រប់គ្រងទំនាក់ទំនងអតិថិជន។',
            ],
            'C Programming Basics' => [
                'title_kh'       => 'មូលដ្ឋានគ្រឹះនៃការសរសេរកូដ C',
                'description_kh' => 'គោលការណ៍គ្រឹះនៃការសរសេរកម្មវិធី ការគ្រប់គ្រង Memory Pointer និងរចនាសម្ព័ន្ធម៉ូឌុលកូដ។',
            ],
            'Web Development with Vue & Laravel' => [
                'title_kh'       => 'ការអភិវឌ្ឍគេហទំព័រជាមួយ Vue និង Laravel',
                'description_kh' => 'ការអភិវឌ្ឍកម្មវិធីគេហទំព័រពេញលេញ (Fullstack) ចាប់ពីមូលដ្ឋាន JavaScript រហូតដល់ការតភ្ជាប់ប្រព័ន្ធ API ផ្លូវការ។',
            ],
            'Introduction to Social Work' => [
                'title_kh'       => 'សេចក្តីណែនាំអំពីការងារសង្គម',
                'description_kh' => 'ការពង្រឹងសមត្ថភាពសហគមន៍ ការដោះស្រាយករណីសុខុមាលភាព និងការអនុវត្តប្រឹក្សាយោបល់ជាក់ស្តែង។',
            ],
        ];

        if (Schema::hasColumn('courses', 'title_kh')) {
            foreach ($courseTranslations as $code => $trans) {
                Course::where('code', $code)->update($trans);
            }

            foreach ($titleTranslations as $title => $trans) {
                Course::where('title', 'like', "%{$title}%")->update($trans);
            }
        }

        // Teacher Khmer Names
        $teacherTranslations = [
            'Dr. Mengly Chea' => 'បណ្ឌិត ម៉េងលី ជា',
            'Mr. Vuthy Seng'  => 'លោក សេង វុទ្ធី',
            'Dr. Meas Sokha'  => 'បណ្ឌិត មាស សុខា',
            'Mr. Chea Sothea' => 'លោក ជា សុភា',
            'Mrs. Pich Sreyneng' => 'អ្នកគ្រូ ពេជ្រ ស្រីនាង',
            'Mrs. Meng Linda' => 'អ្នកគ្រូ ម៉េង លីនដា',
            'Dr. Chea Virak'  => 'បណ្ឌិត ជា វីរៈ',
            'Dr. Heng Chantha' => 'បណ្ឌិត ហេង ចាន់ថា',
            'Mrs. Kim Vanna'  => 'អ្នកគ្រូ គីម វណ្ណា',
            'Mr. Ouk Daneth'  => 'លោក អ៊ុក ដានេត',
        ];

        foreach ($teacherTranslations as $enName => $khName) {
            User::where('name', 'like', "%{$enName}%")
                ->orWhere('name', 'like', '%' . str_replace(['Dr. ', 'Mr. ', 'Mrs. '], '', $enName) . '%')
                ->update(['name_kh' => $khName]);
        }

        // Also ensure specific IDs
        DB::table('users')->where('id', 21)->update(['name_kh' => 'បណ្ឌិត ម៉េងលី ជា']);
        DB::table('users')->where('id', 45)->update(['name_kh' => 'លោក សេង វុទ្ធី']);
    }
}
