<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\AuthLog;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\JwtSession;
use App\Models\Major;
use App\Models\Semester;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ─── Default Users ───
        $admin = User::updateOrCreate(['email' => 'admin@elms.com'], [
            'name'           => 'System Admin',
            'password'       => 'password',
            'role'           => 'admin',
            'phone'          => '012345678',
            'status'         => 'active',
            'login_attempts' => 0,
        ]);

        $kosalAdmin = User::firstOrNew(['email' => 'kosalsensok065@gmail.com']);
        $kosalAdmin->name = 'kosal sensok';
        $kosalAdmin->name_kh = 'កុសល សែនសុខ';
        $kosalAdmin->role = 'admin';
        $kosalAdmin->status = 'active';
        if (!$kosalAdmin->exists) {
            $kosalAdmin->password = bcrypt('Kosalsensokpk@12pk');
            $kosalAdmin->phone = '012345678';
            $kosalAdmin->email_verified_at = now();
        }
        $kosalAdmin->save();

        // Also ensure any secondary kosal accounts match the official name
        User::where('email', 'kosalsensok@gmail.com')->update(['name' => 'kosal sensok', 'name_kh' => 'កុសល សែនសុខ']);
        User::where('email', 'kosalsensok098@gmail.com')->update(['name' => 'kosal sensok', 'name_kh' => 'កុសល សែនសុខ']);

        $teacher = User::updateOrCreate(['email' => 'teacher@elms.com'], [
            'name'           => 'Sophea Teacher',
            'password'       => 'password',
            'role'           => 'teacher',
            'phone'          => '098765432',
            'status'         => 'active',
            'login_attempts' => 0,
        ]);

        $student = User::updateOrCreate(['email' => 'student@elms.com'], [
            'name'           => 'Chan Dara Student',
            'password'       => 'password',
            'role'           => 'student',
            'phone'          => '011222333',
            'status'         => 'active',
            'login_attempts' => 0,
        ]);

        $lockedUser = User::updateOrCreate(['email' => 'dara_locked@elms.com'], [
            'name'           => 'Dara LockedAccount',
            'password'       => 'password',
            'role'           => 'student',
            'phone'          => '015999888',
            'status'         => 'suspended',
            'login_attempts' => 5,
            'locked_until'   => now()->addHours(2),
        ]);

        // ─── Faculties & Departments & Majors ───
        $f1 = Faculty::updateOrCreate(['code' => 'FAC-001'], [
            'name'        => 'Faculty of Computing',
            'name_kh'     => 'មហាវិទ្យាល័យ វិទ្យាសាស្ត្រកុំព្យូទ័រ',
            'description' => 'Faculty focused on IT and Computer Science',
            'is_active'   => true,
        ]);

        $f2 = Faculty::updateOrCreate(['code' => 'FAC-002'], [
            'name'        => 'Faculty of Tourism',
            'name_kh'     => 'មហាវិទ្យាល័យ ទេសចរណ៍',
            'description' => 'Faculty dedicated to Hospitality and Tourism',
            'is_active'   => true,
        ]);

        $f3 = Faculty::updateOrCreate(['code' => 'FAC-003'], [
            'name'        => 'Faculty of Education',
            'name_kh'     => 'មហាវិទ្យាល័យ អប់រំ',
            'description' => 'Faculty providing Pedagogy and Language Studies',
            'is_active'   => true,
        ]);

        $f4 = Faculty::updateOrCreate(['code' => 'FAC-004'], [
            'name'        => 'Faculty of Agriculture',
            'name_kh'     => 'មហាវិទ្យាល័យ កសិកម្ម',
            'description' => 'Faculty for Agricultural Technology and Plant Science',
            'is_active'   => true,
        ]);

        $f5 = Faculty::updateOrCreate(['code' => 'FAC-005'], [
            'name'        => 'Faculty of Social Science',
            'name_kh'     => 'មហាវិទ្យាល័យ វិទ្យាសាស្ត្រសង្គម',
            'description' => 'Faculty for Development & Social Studies',
            'is_active'   => true,
        ]);

        // Departments
        $d1 = Department::updateOrCreate(['code' => 'DEPT-CMP-001'], [
            'faculty_id'  => $f1->id,
            'name'        => 'Computing',
            'name_kh'     => 'ដេប៉ាតឺម៉ង់ វិទ្យាសាស្ត្រកុំព្យូទ័រ',
            'description' => 'Focused on Computer Science and Software',
            'is_active'   => true,
        ]);

        $d2 = Department::updateOrCreate(['code' => 'DEPT-TRM-003'], [
            'faculty_id'  => $f2->id,
            'name'        => 'Tourism',
            'name_kh'     => 'ដេប៉ាតឺម៉ង់ ទេសចរណ៍',
            'description' => 'Tourism and Event Management',
            'is_active'   => true,
        ]);

        $d3 = Department::updateOrCreate(['code' => 'DEPT-EDU-005'], [
            'faculty_id'  => $f3->id,
            'name'        => 'Education',
            'name_kh'     => 'ដេប៉ាតឺម៉ង់ អប់រំ',
            'description' => 'Educational Leadership and English Pedagogy',
            'is_active'   => true,
        ]);

        $d4 = Department::updateOrCreate(['code' => 'DEPT-AGR-008'], [
            'faculty_id'  => $f4->id,
            'name'        => 'Agriculture',
            'name_kh'     => 'ដេប៉ាតឺម៉ង់ កសិកម្ម',
            'description' => 'Agronomy and Crop Science',
            'is_active'   => true,
        ]);

        $d5 = Department::updateOrCreate(['code' => 'DEPT-SOC-010'], [
            'faculty_id'  => $f5->id,
            'name'        => 'Social Science',
            'name_kh'     => 'ដេប៉ាតឺម៉ង់ វិទ្យាសាស្ត្រសង្គម',
            'description' => 'Social Work and Community Development',
            'is_active'   => true,
        ]);

        // Majors
        Major::updateOrCreate(['code' => 'MJR-IT-001'], [
            'department_id' => $d1->id,
            'name'          => 'IT & Networking',
            'name_kh'       => 'បច្ចេកវិទ្យាព័ត៌មាន និងបណ្តាញ',
            'description'   => 'Information Technology, Cloud & Networking',
            'is_active'     => true,
        ]);

        Major::updateOrCreate(['code' => 'MJR-TRM-002'], [
            'department_id' => $d2->id,
            'name'          => 'Tourism Management',
            'name_kh'       => 'គ្រប់គ្រងទេសចរណ៍',
            'description'   => 'Tourism Services & Hospitality Management',
            'is_active'     => true,
        ]);

        Major::updateOrCreate(['code' => 'MJR-ENG-003'], [
            'department_id' => $d3->id,
            'name'          => 'English Literature',
            'name_kh'       => 'អក្សរសាស្ត្រអង់គ្លេស',
            'description'   => 'English Language & Linguistics',
            'is_active'     => true,
        ]);

        Major::updateOrCreate(['code' => 'MJR-AGR-004'], [
            'department_id' => $d4->id,
            'name'          => 'Agronomy',
            'name_kh'       => 'កសិកម្មសាស្ត្រ',
            'description'   => 'Modern Agronomy and Soil Science',
            'is_active'     => true,
        ]);

        Major::updateOrCreate(['code' => 'MJR-SW-005'], [
            'department_id' => $d5->id,
            'name'          => 'Social Work',
            'name_kh'       => 'ការងារសង្គម',
            'description'   => 'Social Development and Public Welfare',
            'is_active'     => true,
        ]);

        // ─── Academic Years & Semesters ───
        $ay1 = AcademicYear::updateOrCreate(['code' => 'AY-2024-2025'], [
            'name'            => 'Academic Year 2024 – 2025',
            'start_date'      => '2024-09-01',
            'end_date'        => '2025-08-31',
            'semesters_count' => 2,
            'status'          => 'active',
            'is_active'       => true,
        ]);

        $ay2 = AcademicYear::updateOrCreate(['code' => 'AY-2023-2024'], [
            'name'            => 'Academic Year 2023 – 2024',
            'start_date'      => '2023-09-01',
            'end_date'        => '2024-08-31',
            'semesters_count' => 2,
            'status'          => 'completed',
            'is_active'       => false,
        ]);

        Semester::updateOrCreate(['code' => 'SEM-1-2024-2025'], [
            'academic_year_id' => $ay1->id,
            'name'             => 'Semester 1 — 2024-2025',
            'parent_year'      => 'Academic Year 2024–2025',
            'semester_num'     => 'Semester 1',
            'start_date'       => '2024-09-01',
            'end_date'         => '2025-02-15',
            'status'           => 'completed',
            'is_active'        => false,
        ]);

        Semester::updateOrCreate(['code' => 'SEM-2-2024-2025'], [
            'academic_year_id' => $ay1->id,
            'name'             => 'Semester 2 — 2024-2025',
            'parent_year'      => 'Academic Year 2024–2025',
            'semester_num'     => 'Semester 2',
            'start_date'       => '2025-02-16',
            'end_date'         => '2025-08-31',
            'status'           => 'active',
            'is_active'        => true,
        ]);

        // Default Settings
        $settings = [
            ['key' => 'site_name', 'value' => 'E.LMS Platform'],
            ['key' => 'allow_registration', 'value' => '1'],
            ['key' => 'aba_account_name', 'value' => 'E-LMS CAMBODIA'],
            ['key' => 'aba_account_number', 'value' => '000 123 456'],
            ['key' => 'min_password_length', 'value' => '8'],
            ['key' => 'max_failed_attempts', 'value' => '5'],
            ['key' => 'session_expiration_hours', 'value' => '24'],
            ['key' => 'security_level', 'value' => 'Strong'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
