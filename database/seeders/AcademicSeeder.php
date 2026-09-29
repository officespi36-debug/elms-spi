<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Major;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Seeder;

class AcademicSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Faculties
        $facultiesData = [
            ['code' => 'FAC-001', 'name' => 'Faculty of Computing', 'name_kh' => 'មហាវិទ្យាល័យ វិទ្យាសាស្ត្រកុំព្យូទ័រ', 'dean' => 'Dr. Sok Vichea', 'email' => 'computing@elms.edu', 'est_year' => 2010, 'is_active' => true, 'description' => 'Faculty focused on IT and Computer Science'],
            ['code' => 'FAC-002', 'name' => 'Faculty of Tourism', 'name_kh' => 'មហាវិទ្យាល័យ ទេសចរណ៍', 'dean' => 'Dr. Keo Samnang', 'email' => 'tourism@elms.edu', 'est_year' => 2012, 'is_active' => true, 'description' => 'Faculty dedicated to Hospitality and Tourism'],
            ['code' => 'FAC-003', 'name' => 'Faculty of Education', 'name_kh' => 'មហាវិទ្យាល័យ អប់រំ', 'dean' => 'Dr. Chan Srey', 'email' => 'education@elms.edu', 'est_year' => 2008, 'is_active' => true, 'description' => 'Faculty providing Pedagogy and Language Studies'],
            ['code' => 'FAC-004', 'name' => 'Faculty of Agriculture', 'name_kh' => 'មហាវិទ្យាល័យ កសិកម្ម', 'dean' => 'Dr. Heng Vuthy', 'email' => 'agriculture@elms.edu', 'est_year' => 2014, 'is_active' => true, 'description' => 'Faculty for Agricultural Technology and Plant Science'],
            ['code' => 'FAC-005', 'name' => 'Faculty of Social Science', 'name_kh' => 'មហាវិទ្យាល័យ វិទ្យាសាស្ត្រសង្គម', 'dean' => 'Dr. Pov Rithy', 'email' => 'social@elms.edu', 'est_year' => 2015, 'is_active' => true, 'description' => 'Faculty for Development & Social Studies'],
        ];

        foreach ($facultiesData as $fac) {
            Faculty::updateOrCreate(['code' => $fac['code']], $fac);
        }

        $facComputing = Faculty::where('code', 'FAC-001')->first();
        $facTourism   = Faculty::where('code', 'FAC-002')->first();
        $facEducation = Faculty::where('code', 'FAC-003')->first();
        $facAgri      = Faculty::where('code', 'FAC-004')->first();
        $facSocial    = Faculty::where('code', 'FAC-005')->first();

        // 2. Seed Departments
        $deptsData = [
            ['faculty_id' => $facComputing?->id, 'code' => 'DEPT-CMP-001', 'name' => 'Computing', 'name_kh' => 'ដេប៉ាតឺម៉ង់ វិទ្យាសាស្ត្រកុំព្យូទ័រ', 'head' => 'Mr. Sophea', 'email' => 'computing.dept@elms.edu', 'is_active' => true],
            ['faculty_id' => $facComputing?->id, 'code' => 'DEPT-SE-002', 'name' => 'Software Engineering', 'name_kh' => 'ដេប៉ាតឺម៉ង់ វិស្វកម្មសូហ្វវែរ', 'head' => 'Dr. Keo Vichea', 'email' => 'se.dept@elms.edu', 'is_active' => true],
            ['faculty_id' => $facTourism?->id, 'code' => 'DEPT-TRM-003', 'name' => 'Tourism', 'name_kh' => 'ដេប៉ាតឺម៉ង់ ទេសចរណ៍', 'head' => 'Mr. Long', 'email' => 'tourism.dept@elms.edu', 'is_active' => true],
            ['faculty_id' => $facTourism?->id, 'code' => 'DEPT-HSP-004', 'name' => 'Hospitality Management', 'name_kh' => 'ដេប៉ាតឺម៉ង់ គ្រប់គ្រងសណ្ឋាគារ', 'head' => 'Ms. Dara', 'email' => 'hospitality.dept@elms.edu', 'is_active' => true],
            ['faculty_id' => $facEducation?->id, 'code' => 'DEPT-EDU-005', 'name' => 'Education', 'name_kh' => 'ដេប៉ាតឺម៉ង់ អប់រំ', 'head' => 'Ms. Srey', 'email' => 'education.dept@elms.edu', 'is_active' => true],
            ['faculty_id' => $facEducation?->id, 'code' => 'DEPT-HUM-006', 'name' => 'Humanities', 'name_kh' => 'ដេប៉ាតឺម៉ង់ មនុស្សសាស្ត្រ', 'head' => 'Mr. Chan', 'email' => 'humanities.dept@elms.edu', 'is_active' => true],
            ['faculty_id' => $facEducation?->id, 'code' => 'DEPT-LNG-007', 'name' => 'Languages', 'name_kh' => 'ដេប៉ាតឺម៉ង់ ភាសាបរទេស', 'head' => 'Ms. Sophea', 'email' => 'languages.dept@elms.edu', 'is_active' => true],
            ['faculty_id' => $facAgri?->id, 'code' => 'DEPT-AGR-008', 'name' => 'Agriculture', 'name_kh' => 'ដេប៉ាតឺម៉ង់ កសិកម្ម', 'head' => 'Mr. Vuthy', 'email' => 'agri.dept@elms.edu', 'is_active' => true],
            ['faculty_id' => $facAgri?->id, 'code' => 'DEPT-PLN-009', 'name' => 'Plant Science', 'name_kh' => 'ដេប៉ាតឺម៉ង់ វិទ្យាសាស្ត្ររុក្ខជាតិ', 'head' => 'Dr. Heng', 'email' => 'plant.dept@elms.edu', 'is_active' => true],
            ['faculty_id' => $facSocial?->id, 'code' => 'DEPT-SOC-010', 'name' => 'Social Science', 'name_kh' => 'ដេប៉ាតឺម៉ង់ វិទ្យាសាស្ត្រសង្គម', 'head' => 'Mr. Rithy', 'email' => 'social.dept@elms.edu', 'is_active' => true],
            ['faculty_id' => $facSocial?->id, 'code' => 'DEPT-DEV-011', 'name' => 'Social Development', 'name_kh' => 'ដេប៉ាតឺម៉ង់ អភិវឌ្ឍន៍សង្គម', 'head' => 'Ms. Bopha', 'email' => 'dev.dept@elms.edu', 'is_active' => true],
            ['faculty_id' => $facSocial?->id, 'code' => 'DEPT-COM-012', 'name' => 'Community Studies', 'name_kh' => 'ដេប៉ាតឺម៉ង់ សិក្សាសហគមន៍', 'head' => 'Mr. Sarath', 'email' => 'community.dept@elms.edu', 'is_active' => true],
        ];

        foreach ($deptsData as $dept) {
            Department::updateOrCreate(['code' => $dept['code']], $dept);
        }

        $deptComputing = Department::where('code', 'DEPT-CMP-001')->first();
        $deptTourism   = Department::where('code', 'DEPT-TRM-003')->first();
        $deptEdu       = Department::where('code', 'DEPT-EDU-005')->first();
        $deptAgri      = Department::where('code', 'DEPT-AGR-008')->first();
        $deptSocial    = Department::where('code', 'DEPT-SOC-010')->first();

        // 3. Seed Majors (The 5 Thesis Core Majors)
        $majorsData = [
            ['department_id' => $deptComputing?->id, 'code' => 'MJR-IT-001', 'name' => 'Information Technology', 'name_kh' => 'បច្ចេកវិទ្យាព័ត៌មាន', 'price_per_subject' => 25, 'duration' => '4 Years', 'degree_level' => 'Bachelor', 'credits' => 120, 'language' => 'English / Khmer', 'is_active' => true],
            ['department_id' => $deptTourism?->id, 'code' => 'MJR-TRM-002', 'name' => 'Tourism', 'name_kh' => 'ទេសចរណ៍', 'price_per_subject' => 20, 'duration' => '4 Years', 'degree_level' => 'Bachelor', 'credits' => 120, 'language' => 'English / Khmer', 'is_active' => true],
            ['department_id' => $deptEdu?->id, 'code' => 'MJR-ENG-003', 'name' => 'English Literature', 'name_kh' => 'អក្សរសាស្ត្រអង់គ្លេស', 'price_per_subject' => 20, 'duration' => '4 Years', 'degree_level' => 'Bachelor', 'credits' => 120, 'language' => 'English', 'is_active' => true],
            ['department_id' => $deptAgri?->id, 'code' => 'MJR-AGR-004', 'name' => 'Agriculture', 'name_kh' => 'កសិកម្ម', 'price_per_subject' => 25, 'duration' => '4 Years', 'degree_level' => 'Bachelor', 'credits' => 120, 'language' => 'Khmer', 'is_active' => true],
            ['department_id' => $deptSocial?->id, 'code' => 'MJR-SW-005', 'name' => 'Social Work', 'name_kh' => 'ការងារសង្គម', 'price_per_subject' => 25, 'duration' => '4 Years', 'degree_level' => 'Bachelor', 'credits' => 120, 'language' => 'Khmer', 'is_active' => true],
        ];

        foreach ($majorsData as $mjr) {
            Major::updateOrCreate(['code' => $mjr['code']], $mjr);
        }

        // 4. Seed Academic Years
        $yearsData = [
            ['code' => 'AY-2024-2025', 'name' => 'Academic Year 2024 – 2025', 'start_date' => '2024-09-01', 'end_date' => '2025-08-31', 'semesters_count' => 2, 'status' => 'active', 'is_active' => true],
            ['code' => 'AY-2023-2024', 'name' => 'Academic Year 2023 – 2024', 'start_date' => '2023-09-01', 'end_date' => '2024-08-31', 'semesters_count' => 2, 'status' => 'completed', 'is_active' => false],
            ['code' => 'AY-2022-2023', 'name' => 'Academic Year 2022 – 2023', 'start_date' => '2022-09-01', 'end_date' => '2023-08-31', 'semesters_count' => 2, 'status' => 'completed', 'is_active' => false],
            ['code' => 'AY-2025-2026', 'name' => 'Academic Year 2025 – 2026', 'start_date' => '2025-09-01', 'end_date' => '2026-08-31', 'semesters_count' => 2, 'status' => 'upcoming', 'is_active' => false],
        ];

        foreach ($yearsData as $yr) {
            AcademicYear::updateOrCreate(['code' => $yr['code']], $yr);
        }

        $ay2024 = AcademicYear::where('code', 'AY-2024-2025')->first();
        $ay2023 = AcademicYear::where('code', 'AY-2023-2024')->first();
        $ay2025 = AcademicYear::where('code', 'AY-2025-2026')->first();

        // 5. Seed Semesters
        $semestersData = [
            ['academic_year_id' => $ay2024?->id, 'code' => 'SEM-1-2024-2025', 'name' => 'Semester 1 — 2024-2025', 'parent_year' => 'Academic Year 2024–2025', 'semester_num' => 'Semester 1', 'start_date' => '2024-09-01', 'end_date' => '2025-02-15', 'status' => 'completed', 'is_active' => false],
            ['academic_year_id' => $ay2024?->id, 'code' => 'SEM-2-2024-2025', 'name' => 'Semester 2 — 2024-2025', 'parent_year' => 'Academic Year 2024–2025', 'semester_num' => 'Semester 2', 'start_date' => '2025-02-16', 'end_date' => '2025-08-31', 'status' => 'active', 'is_active' => true],
            ['academic_year_id' => $ay2023?->id, 'code' => 'SEM-1-2023-2024', 'name' => 'Semester 1 — 2023-2024', 'parent_year' => 'Academic Year 2023–2024', 'semester_num' => 'Semester 1', 'start_date' => '2023-09-01', 'end_date' => '2024-02-15', 'status' => 'completed', 'is_active' => false],
            ['academic_year_id' => $ay2023?->id, 'code' => 'SEM-2-2023-2024', 'name' => 'Semester 2 — 2023-2024', 'parent_year' => 'Academic Year 2023–2024', 'semester_num' => 'Semester 2', 'start_date' => '2024-02-16', 'end_date' => '2024-08-31', 'status' => 'completed', 'is_active' => false],
            ['academic_year_id' => $ay2025?->id, 'code' => 'SEM-1-2025-2026', 'name' => 'Semester 1 — 2025-2026', 'parent_year' => 'Academic Year 2025–2026', 'semester_num' => 'Semester 1', 'start_date' => '2025-09-01', 'end_date' => '2026-02-15', 'status' => 'planned', 'is_active' => false],
        ];

        foreach ($semestersData as $sem) {
            Semester::updateOrCreate(['code' => $sem['code']], $sem);
        }

        // 6. Default Users
        User::firstOrCreate(
            ['email' => 'admin@elms.com'],
            ['name' => 'Super Admin', 'password' => 'password', 'role' => 'admin']
        );

        User::firstOrCreate(
            ['email' => 'teacher@elms.com'],
            ['name' => 'Mr. Sophea', 'password' => 'password', 'role' => 'teacher']
        );

        User::firstOrCreate(
            ['email' => 'student@elms.com'],
            [
                'name' => 'Chan Dara',
                'password' => 'password',
                'role' => 'student',
                'major_id' => Major::where('code', 'MJR-IT-001')->value('id'),
            ]
        );
    }
}
