<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Faculty;
use App\Models\Department;
use App\Models\Major;
use App\Models\Simulation;
use App\Models\SimulationChoice;
use App\Models\SimulationNode;
use App\Models\User;
use Illuminate\Database\Seeder;

class SimulationScenarioSeeder extends Seeder
{
    /**
     * Run the database seeds with complete Khmer and English translations.
     */
    public function run(): void
    {
        // Find or create a teacher
        $teacher = User::where('role', 'teacher')->first();
        if (!$teacher) {
            $teacher = User::create([
                'name' => 'Dr. Sok Visal',
                'name_kh' => 'លោកគ្រូ សុខ វិសាល',
                'email' => 'teacher.tourism@elms.edu.kh',
                'password' => bcrypt('password123'),
                'role' => 'teacher',
                'status' => 'active',
                'is_active' => true,
            ]);
        }

        // Find or create Faculty, Department, Major
        $faculty = Faculty::firstOrCreate(
            ['code' => 'F-TOUR'],
            ['name' => 'Faculty of Tourism and Hospitality', 'name_kh' => 'មហាវិទ្យាល័យទេសចរណ៍ និងបដិសណ្ឋារកិច្ច', 'is_active' => true]
        );

        $department = Department::firstOrCreate(
            ['code' => 'D-HOSP'],
            ['faculty_id' => $faculty->id, 'name' => 'Hospitality Management', 'name_kh' => 'ដេប៉ាតឺម៉ង់គ្រប់គ្រងបដិសណ្ឋារកិច្ច', 'is_active' => true]
        );

        $major = Major::firstOrCreate(
            ['code' => 'M-TOUR'],
            ['department_id' => $department->id, 'name' => 'Tourism and Hospitality Management', 'name_kh' => 'ជំនាញទេសចរណ៍ និងបដិសណ្ឋារកិច្ច', 'is_active' => true]
        );

        // Find or create Course
        $course = Course::firstOrCreate(
            ['code' => 'TOUR-301'],
            [
                'major_id' => $major->id,
                'teacher_id' => $teacher->id,
                'title' => 'Hotel & Hospitality Management Practice',
                'title_kh' => 'ការអនុវត្តគ្រប់គ្រងបដិសណ្ឋារកិច្ច និងសណ្ឋាគារ',
                'description' => 'Hands-on practice on crisis resolution, customer relations, and service standards in hospitality management.',
                'description_kh' => 'វគ្គសិក្សាអនុវត្តជាក់ស្តែងលើការគ្រប់គ្រងសេវាកម្ម ការដោះស្រាយវិបត្តិ និងទំនាក់ទំនងអតិថិជនក្នុងសណ្ឋាគារ',
                'level' => 'intermediate',
                'status' => 'published',
                'price' => 0,
                'is_paid' => false,
            ]
        );

        // Enroll existing admin or students if any
        $admin = User::where('email', 'kosalsensok065@gmail.com')->first();
        if ($admin) {
            \App\Models\Enrollment::firstOrCreate([
                'student_id' => $admin->id,
                'course_id' => $course->id,
            ], [
                'status' => 'active',
                'enrolled_at' => now(),
            ]);
        }

        // Clean existing scenario for TOUR-301 to ensure full bilingual update
        $existingSim = Simulation::where('course_id', $course->id)
            ->where('skill', 'tm')
            ->first();

        if ($existingSim) {
            $existingSim->delete();
        }

        // Create Simulation with bilingual fields
        $sim = Simulation::create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'title' => 'Hotel Guest Complaint Resolution (LAST Method)',
            'title_kh' => 'ការដោះស្រាយបណ្តឹងភ្ញៀវក្នុងសណ្ឋាគារ (វិធីសាស្ត្រ LAST)',
            'skill' => 'tm',
            'kind' => 'scenario',
            'instructions' => 'You are the Duty Manager at a 5-star luxury hotel. A VIP guest arrives at the Front Desk furious because his room air conditioner failed all night, and the hotel is fully booked. Make the best professional decisions according to luxury hospitality standards.',
            'instructions_kh' => 'អ្នកជាអ្នកគ្រប់គ្រងវេន (Duty Manager) នៅសណ្ឋាគារផ្កាយប្រាំ។ ភ្ញៀវ VIP ម្នាក់បានមកដល់តុ Front Desk ដោយកំហឹងខ្លាំងដោយសារម៉ាស៊ីនត្រជាក់ក្នុងបន្ទប់ខូច ហើយសណ្ឋាគារពេញគ្មានបន្ទប់ប្តូរភ្លាមៗទេ។ ចូរជ្រើសរើសដំណោះស្រាយឱ្យបានត្រឹមត្រូវបំផុតតាមវិជ្ជាជីវៈបដិសណ្ឋារកិច្ច។',
            'estimated_minutes' => 12,
            'is_graded' => true,
            'pass_score' => 75,
            'max_attempts' => 3,
            'is_published' => true,
        ]);

        // Node 1
        $node1 = SimulationNode::create([
            'simulation_id' => $sim->id,
            'title' => 'Step 1: Confronting Guest Frustration at Front Desk',
            'title_kh' => 'ជំហានទី ១៖ ការប្រឈមមុខនឹងកំហឹងភ្ញៀវនៅតុ Front Desk',
            'body' => "A guest, **Mr. Smith**, approaches the Front Desk angrily shouting:\n\n> *\"I paid over $200 for a VIP Deluxe room, but the air conditioner was broken all night and I couldn't sleep at all! Your staff is completely irresponsible!\"*\n\nHow should you, as the Duty Manager, respond first?",
            'body_kh' => "ភ្ញៀវឈ្មោះ **លោក Smith** ដើរមកកាន់តុ Front Desk ទាំងកំហឹង ដោយស្រែកខ្លាំងៗថា៖\n\n> *\"ខ្ញុំបង់ថ្លៃបន្ទប់ VIP Deluxe មួយយប់ជាង ២០០ ដុល្លារ តែម៉ាស៊ីនត្រជាក់ខូចពេញមួយយប់ ខ្ញុំដេកមិនលក់សោះ! បុគ្គលិកអ្នកគ្មានការទទួលខុសត្រូវទាល់តែសោះ!\"*\n\nតើអ្នកជា Duty Manager គួរឆ្លើយតបយ៉ាងដូចម្តេចជាជំហានដំបូង?",
            'is_end' => false,
        ]);

        // Node 2
        $node2 = SimulationNode::create([
            'simulation_id' => $sim->id,
            'title' => 'Step 2: Resolving Room Unavailability',
            'title_kh' => 'ជំហានទី ២៖ ការដោះស្រាយបញ្ហាបន្ទប់ដែលពុំមានបន្ទប់ទំនេរ',
            'body' => "After listening to Mr. Smith, you check the PMS system and find the hotel is **Fully Booked**—no replacement rooms are immediately available. Engineering reports they need **30 to 45 minutes** to repair the cooling unit.\n\nHow do you proceed?",
            'body_kh' => "បន្ទាប់ពីលោក Smith បានរៀបរាប់ពីបញ្ហាចប់ អ្នកបានពិនិត្យប្រព័ន្ធ PMS ឃើញថា **បន្ទប់ទាំងអស់ពេញ (Fully Booked)** មិនអាចប្តូរបន្ទប់ថ្មីភ្លាមៗបានទេ។ ក្រុមវិស្វកររាយការណ៍ថា ពួកគេត្រូវការពេល **៣០ ទៅ ៤៥ នាទី** ដើម្បីជួសជុលម៉ាស៊ីនត្រជាក់ឱ្យត្រជាក់ឡើងវិញ។\n\nតើអ្នកគួរបន្តដោះស្រាយយ៉ាងណា?",
            'is_end' => false,
        ]);

        // Node 3
        $node3 = SimulationNode::create([
            'simulation_id' => $sim->id,
            'title' => 'Step 3: Verifying Successful Repair',
            'title_kh' => 'ជំហានទី ៣៖ ការបញ្ជាក់ការជួសជុលរួចរាល់',
            'body' => "While Mr. Smith is relaxing in the lounge, the engineering team confirms the circuit board was replaced and the air conditioning is cooling properly at 20°C.\n\nWhat should you do next to demonstrate premium service?",
            'body_kh' => "ខណៈពេលលោក Smith កំពុងអង្គុយសម្រាកពិសាភេសជ្ជៈ ក្រុមវិស្វករបានទាក់ទងមកថា ម៉ាស៊ីនត្រជាក់ត្រូវបានប្តូរបន្ទះសៀគ្វីរួចរាល់ និងដំណើរការត្រជាក់កម្រិត ២០ អង្សាសេយ៉ាងល្អ។\n\nតើអ្នកគួរអនុវត្តជំហានបន្តបែបណាដើម្បីបង្ហាញពីការយកចិត្តទុកដាក់កម្រិតខ្ពស់?",
            'is_end' => false,
        ]);

        // Node 4
        $node4 = SimulationNode::create([
            'simulation_id' => $sim->id,
            'title' => 'Step 4: Service Recovery & Long-term Loyalty',
            'title_kh' => 'ជំហានទី ៤៖ ការស្តារការពេញចិត្តរបស់ភ្ញៀវ (Service Recovery)',
            'body' => "Mr. Smith enters his room and confirms it is pleasantly cool. He is now calm and expresses gratitude for your dedicated attention.\n\nTo make a lasting positive impression and transform a frustrated customer into a loyal advocate, what should you do?",
            'body_kh' => "លោក Smith បានឡើងទៅដល់បន្ទប់ ហើយបញ្ជាក់ថាបន្ទប់ត្រជាក់ស្រួលឡើងវិញហើយ។ គាត់បានស្ងប់ចិត្ត និងអរគុណអ្នកចំពោះការដោះស្រាយ។\n\nដើម្បីបន្សល់ទុកនូវចំណាប់អារម្មណ៍ល្អយូរអង្វែង និងប្រែក្លាយភ្ញៀវដែលខឹងឱ្យក្លាយជាភ្ញៀវស្មោះត្រង់ (Loyal Guest) តើអ្នកគួរធ្វើអ្វីបន្ថែម?",
            'is_end' => false,
        ]);

        // Node 5 (End)
        $node5 = SimulationNode::create([
            'simulation_id' => $sim->id,
            'title' => 'Scenario Completed',
            'title_kh' => 'ការសន្និដ្ឋានបញ្ចប់សេណារីយ៉ូ',
            'body' => "🎉 **Scenario completed successfully!**\n\nYou have navigated a real-world hospitality service recovery simulation. Review your score breakdown and feedback below.",
            'body_kh' => "🎉 **សេណារីយ៉ូត្រូវបានបញ្ចប់ដោយជោគជ័យ!**\n\nអ្នកបានបំពេញការសម្រេចចិត្តក្នុងស្ថានភាពជាក់ស្តែងនៃការដោះស្រាយបណ្តឹងភ្ញៀវក្នុងវិស័យទេសចរណ៍ និងបដិសណ្ឋារកិច្ច។ សូមពិនិត្យមើលពិន្ទុ និងមតិត្រឡប់លម្អិតខាងក្រោម។",
            'is_end' => true,
        ]);

        $sim->update(['start_node_id' => $node1->id]);

        // Choices for Node 1
        SimulationChoice::create([
            'node_id' => $node1->id,
            'next_node_id' => $node2->id,
            'label' => 'Remain calm, maintain respectful posture, listen attentively, acknowledge his frustration, and apologize sincerely for the disturbance.',
            'label_kh' => 'រក្សាភាពស្ងប់ស្ងាត់ ប្រើកាយវិការគោរព ស្តាប់ដោយយកចិត្តទុកដាក់ និងថ្លែងអំណរគុណដែលគាត់បានរាយការណ៍ រួចសុំទោសចំពោះការរំខាន',
            'points' => 25,
            'is_best' => true,
            'feedback' => 'Excellent! The LAST method (Listen, Apologize, Solve, Thank) requires actively listening and apologizing for the guest\'s feelings first to defuse anger.',
            'feedback_kh' => 'ល្អឥតខ្ចោះ! គោលការណ៍ LAST (Listen, Apologize, Solve, Thank) តម្រូវឱ្យស្តាប់ និងសុំទោសចំពោះអារម្មណ៍ភ្ញៀវមុនគេបង្អស់ ដើម្បីបន្ថយកម្តៅកំហឹង។',
        ]);
        SimulationChoice::create([
            'node_id' => $node1->id,
            'next_node_id' => $node2->id,
            'label' => 'Immediately explain that last night was another staff member\'s shift and not your responsibility.',
            'label_kh' => 'ពន្យល់ភ្លាមៗថា យប់មិញជាវេនរបស់បុគ្គលិកផ្សេង មិនមែនជាការទទួលខុសត្រូវរបស់អ្នកទេ',
            'points' => 5,
            'is_best' => false,
            'feedback' => 'Incorrect. Deflecting blame to colleagues damages hotel reputation and further inflames the guest.',
            'feedback_kh' => 'មិនត្រឹមត្រូវទេ! ការបន្ទោសសហការី ឬរុញការទទួលខុសត្រូវ ធ្វើឱ្យខូចកេរ្តិ៍ឈ្មោះសណ្ឋាគារ និងធ្វើឱ្យភ្ញៀវកាន់តែខឹង។',
        ]);
        SimulationChoice::create([
            'node_id' => $node1->id,
            'next_node_id' => $node2->id,
            'label' => 'Ask the guest to lower his voice because he is disturbing other guests nearby.',
            'label_kh' => 'ប្រាប់ភ្ញៀវឱ្យទម្លាក់សម្លេងចុះ ព្រោះកំពុងរំខានដល់ភ្ញៀវដទៃទៀតនៅជុំវិញ',
            'points' => 0,
            'is_best' => false,
            'feedback' => 'Severe mistake! Telling an angry guest to calm down or lower their voice escalates the tension.',
            'feedback_kh' => 'ខុសធ្ងន់ធ្ងរ! ការប្រាប់ឱ្យភ្ញៀវដែលកំពុងខឹងទម្លាក់សម្លេង នឹងធ្វើឱ្យស្ថានភាពកាន់តែតានតឹងខ្លាំង។',
        ]);

        // Choices for Node 2
        SimulationChoice::create([
            'node_id' => $node2->id,
            'next_node_id' => $node3->id,
            'label' => 'Invite Mr. Smith to the Executive Lounge for complimentary refreshments and pastries while engineering conducts urgent repairs.',
            'label_kh' => 'អញ្ជើញលោក Smith ទៅកាន់ Executive Lounge ពិសាភេសជ្ជៈពិសេសឥតគិតថ្លៃ ខណៈពេលក្រុមវិស្វករកំពុងជួសជុលជាបន្ទាន់',
            'points' => 25,
            'is_best' => true,
            'feedback' => 'Smart move! Providing a comfortable hospitality environment helps de-escalate anxiety and shows genuine care.',
            'feedback_kh' => 'ឆ្លាតវៃណាស់! ការផ្តល់កន្លែងសម្រាកប្រកបដោយផាសុកភាព ជួយឱ្យភ្ញៀវមានអារម្មណ៍ត្រូវបានយកចិត្តទុកដាក់។',
        ]);
        SimulationChoice::create([
            'node_id' => $node2->id,
            'next_node_id' => $node3->id,
            'label' => 'Tell the guest to wait on a lobby sofa until the technician finishes.',
            'label_kh' => 'ប្រាប់ភ្ញៀវឱ្យអង្គុយរង់ចាំនៅកៅអី Lobby រហូតដល់ជាងជួសជុលរួចរាល់',
            'points' => 10,
            'is_best' => false,
            'feedback' => 'Leaving an upset VIP guest stranded in the public lobby without hospitality reflects poorly on 5-star service standards.',
            'feedback_kh' => 'ការទុកឱ្យភ្ញៀវអង្គុយចាំនៅ Lobby ដោយគ្មានការផ្តល់សេវាកម្មសម្រាលអារម្មណ៍ មិនឆ្លុះបញ្ចាំងពីស្តង់ដារផ្កាយប្រាំឡើយ។',
        ]);
        SimulationChoice::create([
            'node_id' => $node2->id,
            'next_node_id' => $node3->id,
            'label' => 'Offer a $10 cash payout immediately and ask him to stop complaining.',
            'label_kh' => 'ផ្តល់ប្រាក់សំណង ១០ ដុល្លារភ្លាមៗ ហើយស្នើកុំឱ្យគាត់បន្តត្អូញត្អែរទៀត',
            'points' => 0,
            'is_best' => false,
            'feedback' => 'Inappropriate. Trivial monetary gestures without resolving the core issue leave guests feeling insulted.',
            'feedback_kh' => 'មិនត្រឹមត្រូវ! ប្រាក់សំណងតិចតួចដោយគ្មានការដោះស្រាយបញ្ហាជាក់ស្តែង ធ្វើឱ្យភ្ញៀវមានអារម្មណ៍ថាមិនត្រូវបានឱ្យតម្លៃ។',
        ]);

        // Choices for Node 3
        SimulationChoice::create([
            'node_id' => $node3->id,
            'next_node_id' => $node4->id,
            'label' => 'Personally escort Mr. Smith back to his room, check the room temperature together, and ensure his complete satisfaction.',
            'label_kh' => 'អមដំណើរលោក Smith ទៅកាន់បន្ទប់ដោយផ្ទាល់ ពិនិត្យមើលសីតុណ្ហភាពជាមួយគាត់ និងធានាថាគាត់ពេញចិត្ត',
            'points' => 25,
            'is_best' => true,
            'feedback' => 'Well done! A personal escort and walkthrough confirms the resolution and reinforces accountability.',
            'feedback_kh' => 'ពូកែណាស់! ការអមដំណើរភ្ញៀវទៅពិនិត្យដោយផ្ទាល់ បង្ហាញពីការទទួលខុសត្រូវខ្ពស់បំផុតក្នុងវិជ្ជាជីវៈ។',
        ]);
        SimulationChoice::create([
            'node_id' => $node3->id,
            'next_node_id' => $node4->id,
            'label' => 'Call the lounge staff and ask them to tell the guest to walk back upstairs on his own.',
            'label_kh' => 'ទូរសព្ទប្រាប់បុគ្គលិក Lounge ឱ្យប្រាប់ភ្ញៀវឱ្យដើរត្រឡប់ទៅបន្ទប់ដោយខ្លួនឯង',
            'points' => 10,
            'is_best' => false,
            'feedback' => 'Lacks personal touch. A Duty Manager should close the loop with the guest directly.',
            'feedback_kh' => 'ខ្វះការយកចិត្តទុកដាក់ផ្ទាល់ខ្លួន (Personal Touch)។ អ្នកគ្រប់គ្រងគួរតែអមដំណើរភ្ញៀវដើម្បីធានាការដោះស្រាយបានសម្រេច។',
        ]);

        // Choices for Node 4
        SimulationChoice::create([
            'node_id' => $node4->id,
            'next_node_id' => $node5->id,
            'label' => 'Arrange a welcome fruit basket, a personalized apology note signed by the GM, and a complimentary dinner voucher at the signature restaurant.',
            'label_kh' => 'រៀបចំកន្ត្រកផ្លែឈើស្វាគមន៍ លិខិតសុំទោសចុះហត្ថលេខាដោយ GM និងប័ណ្ណ Voucher អាហារពេលល្ងាចឥតគិតថ្លៃ',
            'points' => 25,
            'is_best' => true,
            'feedback' => 'Outstanding! Great service recovery turns unhappy guests into loyal lifelong brand ambassadors.',
            'feedback_kh' => 'អស្ចារ្យបំផុត! Service Recovery ដ៏ល្អប្រែក្លាយភ្ញៀវដែលខឹងឱ្យក្លាយជា Brand Advocate និងភ្ញៀវស្មោះត្រង់របស់សណ្ឋាគារ។',
        ]);
        SimulationChoice::create([
            'node_id' => $node4->id,
            'next_node_id' => $node5->id,
            'label' => 'Do nothing further since the technical problem has already been resolved.',
            'label_kh' => 'មិនបាច់ធ្វើអ្វីបន្ថែមទៀតទេ ព្រោះបញ្ហាត្រូវបានដោះស្រាយចប់សព្វគ្រប់ហើយ',
            'points' => 10,
            'is_best' => false,
            'feedback' => 'Misses an opportunity to restore guest goodwill and establish memorable hospitality.',
            'feedback_kh' => 'បាត់បង់ឱកាសមាសក្នុងការបង្កើតអនុស្សាវរីយ៍ល្អ និងស្តារកិត្តិយសរបស់សណ្ឋាគារឡើងវិញ។',
        ]);
    }
}
