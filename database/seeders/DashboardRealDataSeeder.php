<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Faculty;
use App\Models\Lesson;
use App\Models\Major;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DashboardRealDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure the 5 Canonical SPI Majors exist with proper departments
        $facIT = Faculty::firstOrCreate(['code' => 'FAC-001'], ['name' => 'Faculty of Information Technology', 'is_active' => true]);
        $facSW = Faculty::firstOrCreate(['code' => 'FAC-005'], ['name' => 'Faculty of Social Science', 'is_active' => true]);
        $facAGR = Faculty::firstOrCreate(['code' => 'FAC-004'], ['name' => 'Faculty of Agriculture', 'is_active' => true]);
        $facTRM = Faculty::firstOrCreate(['code' => 'FAC-002'], ['name' => 'Faculty of Tourism', 'is_active' => true]);
        $facENG = Faculty::firstOrCreate(['code' => 'FAC-003'], ['name' => 'Faculty of Humanities & Languages', 'is_active' => true]);

        $deptIT = Department::firstOrCreate(['code' => 'DEPT-CMP-001'], ['faculty_id' => $facIT->id, 'name' => 'Computing & Software', 'is_active' => true]);
        $deptSW = Department::firstOrCreate(['code' => 'DEPT-SOC-010'], ['faculty_id' => $facSW->id, 'name' => 'Social Work & Development', 'is_active' => true]);
        $deptAGR = Department::firstOrCreate(['code' => 'DEPT-AGR-008'], ['faculty_id' => $facAGR->id, 'name' => 'Agronomy & Agriculture', 'is_active' => true]);
        $deptTRM = Department::firstOrCreate(['code' => 'DEPT-TRM-003'], ['faculty_id' => $facTRM->id, 'name' => 'Tourism & Hospitality', 'is_active' => true]);
        $deptENG = Department::firstOrCreate(['code' => 'DEPT-EDU-005'], ['faculty_id' => $facENG->id, 'name' => 'English Language Studies', 'is_active' => true]);

        $majorIT = Major::firstOrCreate(['code' => 'MJR-IT-001'], ['department_id' => $deptIT->id, 'name' => 'Information Technology', 'name_kh' => 'បច្ចេកវិទ្យាព័ត៌មាន (IT)', 'is_active' => true]);
        $majorSW = Major::firstOrCreate(['code' => 'MJR-SW-005'], ['department_id' => $deptSW->id, 'name' => 'Social Work', 'name_kh' => 'ការងារសង្គម (SW)', 'is_active' => true]);
        $majorAGR = Major::firstOrCreate(['code' => 'MJR-AGR-004'], ['department_id' => $deptAGR->id, 'name' => 'Agriculture', 'name_kh' => 'កសិកម្ម (AGR)', 'is_active' => true]);
        $majorTRM = Major::firstOrCreate(['code' => 'MJR-TRM-002'], ['department_id' => $deptTRM->id, 'name' => 'Tourism', 'name_kh' => 'ទេសចរណ៍ (TRM)', 'is_active' => true]);
        $majorENG = Major::firstOrCreate(['code' => 'MJR-ENG-003'], ['department_id' => $deptENG->id, 'name' => 'English Literature', 'name_kh' => 'អក្សរសាស្ត្រអង់គ្លេស (ENG)', 'is_active' => true]);

        $majors = [$majorIT, $majorSW, $majorAGR, $majorTRM, $majorENG];

        // 2. Ensure Teachers across majors
        $teachersData = [
            ['name' => 'Dr. Sokha Meas', 'email' => 'sokha.meas@spilms.tech', 'major_id' => $majorIT->id, 'status' => 'active', 'is_active' => true],
            ['name' => 'Mr. Sophea Chea', 'email' => 'sophea.chea@spilms.tech', 'major_id' => $majorIT->id, 'status' => 'active', 'is_active' => true],
            ['name' => 'Ms. Sreyneang Pich', 'email' => 'sreyneang.pich@spilms.tech', 'major_id' => $majorSW->id, 'status' => 'active', 'is_active' => true],
            ['name' => 'Mr. Vuthy Seng', 'email' => 'vuthy.seng@spilms.tech', 'major_id' => $majorAGR->id, 'status' => 'active', 'is_active' => true],
            ['name' => 'Ms. Linda Meng', 'email' => 'linda.meng@spilms.tech', 'major_id' => $majorTRM->id, 'status' => 'active', 'is_active' => true],
            ['name' => 'Dr. Virak Chea', 'email' => 'virak.chea@spilms.tech', 'major_id' => $majorENG->id, 'status' => 'active', 'is_active' => true],
            // 3 Teachers pending verification
            ['name' => 'Dr. Heng Chantha', 'email' => 'chantha.heng@spilms.tech', 'major_id' => null, 'status' => 'pending', 'is_active' => false],
            ['name' => 'Ms. Kim Vanna', 'email' => 'vanna.kim@spilms.tech', 'major_id' => null, 'status' => 'pending', 'is_active' => false],
            ['name' => 'Mr. Ouk Daneth', 'email' => 'daneth.ouk@spilms.tech', 'major_id' => null, 'status' => 'pending', 'is_active' => false],
        ];

        $teacherModels = [];
        foreach ($teachersData as $td) {
            $t = User::firstOrCreate(['email' => $td['email']], [
                'name' => $td['name'],
                'role' => 'teacher',
                'password' => Hash::make('password'),
                'major_id' => $td['major_id'],
                'status' => $td['status'],
                'is_active' => $td['is_active'],
            ]);
            $teacherModels[] = $t;
        }

        // 3. Ensure Courses (both published and 5 pending review)
        $coursesData = [
            // Top performing published courses
            ['title' => 'Web & Mobile Development', 'major_id' => $majorIT->id, 'teacher_id' => $teacherModels[0]->id, 'status' => 'published'],
            ['title' => 'Community Social Work', 'major_id' => $majorSW->id, 'teacher_id' => $teacherModels[2]->id, 'status' => 'published'],
            ['title' => 'Modern Crop Agronomy', 'major_id' => $majorAGR->id, 'teacher_id' => $teacherModels[3]->id, 'status' => 'published'],
            ['title' => 'Eco-Tourism & Hospitality', 'major_id' => $majorTRM->id, 'teacher_id' => $teacherModels[4]->id, 'status' => 'published'],
            ['title' => 'Business English Communication', 'major_id' => $majorENG->id, 'teacher_id' => $teacherModels[5]->id, 'status' => 'published'],
            // 5 Courses waiting for approval / review
            ['title' => 'Advanced Machine Learning & AI', 'major_id' => $majorIT->id, 'teacher_id' => $teacherModels[0]->id, 'status' => 'pending'],
            ['title' => 'Clinical Social Work Case Studies', 'major_id' => $majorSW->id, 'teacher_id' => $teacherModels[2]->id, 'status' => 'pending'],
            ['title' => 'Organic Crop Agronomy Practices', 'major_id' => $majorAGR->id, 'teacher_id' => $teacherModels[3]->id, 'status' => 'pending'],
            ['title' => 'Sustainable Eco-Tourism Operations', 'major_id' => $majorTRM->id, 'teacher_id' => $teacherModels[4]->id, 'status' => 'pending'],
            ['title' => 'International Business English', 'major_id' => $majorENG->id, 'teacher_id' => $teacherModels[5]->id, 'status' => 'pending'],
        ];

        $courseModels = [];
        foreach ($coursesData as $cd) {
            $c = Course::firstOrCreate(['title' => $cd['title']], [
                'code' => 'CRS-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $cd['title']), 0, 6)) . '-' . rand(100, 999),
                'major_id' => $cd['major_id'],
                'teacher_id' => $cd['teacher_id'],
                'status' => $cd['status'],
                'learning_mode' => 'instructor_led',
                'price' => 0,
                'is_paid' => false,
            ]);
            $courseModels[] = $c;

            // Ensure modules & lessons
            $mod = Module::firstOrCreate(['course_id' => $c->id, 'title' => 'Module 1: Fundamentals'], ['order' => 1]);
            Lesson::firstOrCreate(['course_id' => $c->id, 'title' => 'Lesson 1.1: Introduction to ' . $c->title], [
                'module_id' => $mod->id,
                'order' => 1,
                'type' => 'video',
                'duration_seconds' => 3600,
            ]);
            Lesson::firstOrCreate(['course_id' => $c->id, 'title' => 'Lesson 1.2: Advanced Applications'], [
                'module_id' => $mod->id,
                'order' => 2,
                'type' => 'pdf',
                'duration_seconds' => 4200,
            ]);

            // Quiz
            Quiz::firstOrCreate(['course_id' => $c->id, 'title' => 'Midterm Assessment: ' . $c->title], [
                'type' => 'practice',
                'passing_score' => 70,
                'time_limit_minutes' => 60,
                'status' => 'published',
            ]);
        }

        // 4. Ensure At-Risk Students (12 students with low scores or suspended status)
        $atRiskData = [
            ['name' => 'Chan Dara', 'email' => 'chan.dara.risk@spilms.tech', 'major_id' => $majorIT->id, 'status' => 'suspended'],
            ['name' => 'Long Vichida', 'email' => 'long.vichida.risk@spilms.tech', 'major_id' => $majorENG->id, 'status' => 'suspended'],
            ['name' => 'Pov Sreynich', 'email' => 'pov.sreynich.risk@spilms.tech', 'major_id' => $majorAGR->id, 'status' => 'suspended'],
            ['name' => 'Sok Chanra', 'email' => 'sok.chanra.risk@spilms.tech', 'major_id' => $majorTRM->id, 'status' => 'suspended'],
            ['name' => 'Mao Sreynich', 'email' => 'mao.sreynich.risk@spilms.tech', 'major_id' => $majorSW->id, 'status' => 'suspended'],
            ['name' => 'Borey Kem', 'email' => 'borey.kem.risk@spilms.tech', 'major_id' => $majorIT->id, 'status' => 'suspended'],
            ['name' => 'Sothea Rim', 'email' => 'sothea.rim.risk@spilms.tech', 'major_id' => $majorSW->id, 'status' => 'suspended'],
            ['name' => 'Chanthy Net', 'email' => 'chanthy.net.risk@spilms.tech', 'major_id' => $majorAGR->id, 'status' => 'suspended'],
            ['name' => 'Keo Bopha', 'email' => 'keo.bopha.risk@spilms.tech', 'major_id' => $majorTRM->id, 'status' => 'suspended'],
            ['name' => 'Vannak Tep', 'email' => 'vannak.tep.risk@spilms.tech', 'major_id' => $majorENG->id, 'status' => 'suspended'],
            ['name' => 'Phanna Long', 'email' => 'phanna.long.risk@spilms.tech', 'major_id' => $majorIT->id, 'status' => 'suspended'],
            ['name' => 'Piseth Ouk', 'email' => 'piseth.ouk.risk@spilms.tech', 'major_id' => $majorSW->id, 'status' => 'suspended'],
        ];

        foreach ($atRiskData as $ar) {
            User::firstOrCreate(['email' => $ar['email']], [
                'name' => $ar['name'],
                'role' => 'student',
                'password' => Hash::make('password'),
                'major_id' => $ar['major_id'],
                'status' => $ar['status'],
                'is_active' => false,
            ]);
        }

        // 5. Ensure Scheduled Announcements
        Announcement::firstOrCreate(['title_kh' => 'សេចក្តីប្រកាសចាប់ផ្តើមឆមាសទី២ និងការចុះឈ្មោះវគ្គសិក្សា'], [
            'title_en' => 'Semester 2 Academic Launch & Course Enrollment Window',
            'body_kh' => 'សេចក្តីជូនដំណឹងដល់និស្សិត និងសាស្ត្រាចារ្យទាំងអស់អំពីការបើកឆមាសសិក្សាថ្មី។',
            'body_en' => 'Official announcement regarding course enrollment period for Semester 2.',
            'status' => 'scheduled',
            'audience_type' => 'All Students & Teachers',
            'priority' => 'high',
            'scheduled_at' => now()->addDay(),
        ]);

        Announcement::firstOrCreate(['title_kh' => 'ការរៀបចំប្រឡងបញ្ចប់ និងការអនុវត្តធនាគារកម្រងសំណួរ'], [
            'title_en' => 'Final Exam Preparation & Quiz Bank Practice Open',
            'body_kh' => 'ធនាគារកម្រងសំណួរ និងវិញ្ញាសាអនុវត្តត្រូវបានបើកជូននិស្សិតធ្វើស្វ័យសិក្សា។',
            'body_en' => 'Quiz banks and practice assessments are now accessible for all cohorts.',
            'status' => 'scheduled',
            'audience_type' => 'Active Students',
            'priority' => 'medium',
            'scheduled_at' => now()->addDays(3),
        ]);

        // 6. Ensure Enrollments across published courses
        $activeStudents = User::where('role', 'student')->where('status', 'active')->get();
        $publishedCourses = Course::where('status', 'published')->get();

        foreach ($publishedCourses as $idx => $course) {
            foreach ($activeStudents as $stIdx => $student) {
                if (($stIdx + $idx) % 2 === 0) {
                    $status = ($stIdx % 3 === 0) ? 'completed' : 'active';
                    Enrollment::firstOrCreate([
                        'student_id' => $student->id,
                        'course_id' => $course->id,
                    ], [
                        'status' => $status,
                        'enrolled_at' => now()->subDays(rand(5, 60)),
                    ]);

                    if ($status === 'completed') {
                        $code = 'VER-' . strtoupper(substr(md5($student->id . '-' . $course->id . '-spi'), 0, 10));
                        Certificate::firstOrCreate([
                            'student_id' => $student->id,
                            'course_id' => $course->id,
                        ], [
                            'certificate_number' => 'CERT-SPI-' . strtoupper(substr(md5($student->id . '-' . $course->id), 0, 8)),
                            'verification_code' => $code,
                            'issued_at' => now()->subDays(rand(1, 20)),
                            'status' => 'issued',
                        ]);
                    }
                }
            }
        }
    }
}
