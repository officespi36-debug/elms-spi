<?php

namespace Database\Seeders;

use App\Models\AcademicRecord;
use App\Models\Course;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Faculty;
use App\Models\IdCard;
use App\Models\Major;
use App\Models\Setting;
use App\Models\StudentGoal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database for official production use.
     *
     * Only 1 official Super Admin account and core system settings are seeded.
     * All dummy/sample faculties, majors, courses, teachers, students, reviews,
     * and enrollments are intentionally disabled so administrators, teachers,
     * and students manage 100% real data via the platform UI.
     */
    public function run(): void
    {
        // ─── 1. Official Super Admin Account (Single Account Only) ───
        $adminEmail = 'kosalsensok065@gmail.com';
        $adminPassword = 'Kosalsensokpk@12pk';

        $superAdmin = User::firstOrNew(['email' => $adminEmail]);
        $superAdmin->name = $superAdmin->name ?: 'kosal sensok';
        $superAdmin->name_kh = $superAdmin->name_kh ?: 'កុសល សែនសុខ';
        $superAdmin->role = 'admin';
        $superAdmin->status = 'active';
        $superAdmin->is_active = true;
        $superAdmin->login_attempts = 0;

        if (!$superAdmin->exists) {
            $superAdmin->password = bcrypt($adminPassword);
            $superAdmin->email_verified_at = now();
        }
        $superAdmin->save();

        // ─── 2. Core System Settings ───
        $settings = [
            ['key' => 'site_name', 'value' => 'E.LMS Platform'],
            ['key' => 'allow_registration', 'value' => '1'],
            ['key' => 'min_password_length', 'value' => '8'],
            ['key' => 'max_failed_attempts', 'value' => '5'],
            ['key' => 'session_expiration_hours', 'value' => '24'],
            ['key' => 'security_level', 'value' => 'Strong'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }

        // Seed published course-backed learning paths only when real published courses exist
        try {
            $this->call(LearningPathCatalogSeeder::class);
        } catch (\Throwable $e) {
            // Non-blocking during early bootstrap before tables are migrated
        }

        // Seed realistic course assignments and deadlines for student practice & assessment
        try {
            $this->call(AssignmentSeeder::class);
        } catch (\Throwable $e) {
            // Non-blocking during early bootstrap
        }

        // ─── 3. Ensure Official Digital ID Cards for Official Students & Admin ───
        try {
            $faculty = Faculty::firstOrCreate(
                ['code' => 'FST'],
                [
                    'name'        => 'Faculty of Science & Technology',
                    'name_kh'     => 'មហាវិទ្យាល័យវិទ្យាសាស្ត្រ និងបច្ចេកវិទ្យា',
                    'dean'        => 'H.E. Bishop Olivier Schmitthaeusler',
                    'email'       => 'fst@spilms.tech',
                    'est_year'    => 2009,
                    'description' => 'Faculty of Science and Information Technology at Saint Paul Institute',
                    'is_active'   => true,
                ]
            );

            $department = Department::firstOrCreate(
                ['code' => 'DIT'],
                [
                    'faculty_id'  => $faculty->id,
                    'name'        => 'Department of Information Technology',
                    'name_kh'     => 'ដេប៉ាតឺម៉ង់បច្ចេកវិទ្យាព័ត៌មាន',
                    'head'        => 'Ph.D. IT Department Head',
                    'email'       => 'dit@spilms.tech',
                    'description' => 'Department of IT & Software Engineering',
                    'is_active'   => true,
                ]
            );

            $major = Major::firstOrCreate(
                ['code' => 'IT-BSC'],
                [
                    'department_id' => $department->id,
                    'name'          => 'Information Technology',
                    'name_kh'       => 'បច្ចេកវិទ្យាព័ត៌មាន (IT)',
                    'duration'      => '4 Years',
                    'degree_level'  => 'Bachelor of Science (B.Sc.)',
                    'credits'       => 120,
                    'language'      => 'Khmer / English',
                    'is_active'     => true,
                ]
            );

            if ($superAdmin) {
                $superAdmin->update([
                    'major_id'     => $major->id,
                    'status'       => 'active',
                    'is_active'    => true,
                    'student_code' => $superAdmin->student_code ?: 'SPI-2026-001582',
                ]);

                AcademicRecord::firstOrCreate(
                    ['user_id' => $superAdmin->id],
                    [
                        'faculty_id'    => $faculty->id,
                        'department_id' => $department->id,
                        'major_id'      => $major->id,
                        'degree_level'  => 'Bachelor of Science (B.Sc.)',
                        'study_type'    => 'Full-time',
                        'gpa'           => 3.85,
                        'total_credits' => 120,
                    ]
                );

                IdCard::updateOrCreate(
                    ['user_id' => $superAdmin->id],
                    [
                        'card_no'     => 'SPI-2026-001582',
                        'token'       => '0b7a68d6a1a3c4de139b5fa8750c2f9960b3264c',
                        'issued_at'   => Carbon::create(2026, 11, 14, 0, 0, 0),
                        'expires_at'  => Carbon::create(2030, 11, 14, 23, 59, 59),
                        'status'      => 'active',
                        'approved_by' => $superAdmin->id,
                        'approved_at' => Carbon::create(2026, 11, 14, 0, 0, 0),
                    ]
                );
            }
        } catch (\Throwable $e) {
            // Non-blocking
        }

        // ─── 4. Seed Official Academic Structure & Published Courses ───
        try {
            $this->call(AcademicSeeder::class);
            $this->call(CourseApprovalAndEnrollmentSeeder::class);

            // Ensure primary superAdmin / student has active enrolled courses & study goals
            if ($superAdmin) {
                $published = Course::where('status', 'published')->take(3)->get();
                foreach ($published as $c) {
                    Enrollment::firstOrCreate(
                        ['student_id' => $superAdmin->id, 'course_id' => $c->id],
                        ['status' => 'active', 'enrolled_at' => now()]
                    );
                }

                StudentGoal::firstOrCreate(
                    ['user_id' => $superAdmin->id, 'title' => 'Complete Chapter 1 on Web Development'],
                    [
                        'title_km'     => 'បញ្ចប់ជំពូកទី ១ ស្តីពីការអភិវឌ្ឍគេហទំព័រ',
                        'is_completed' => false,
                        'target_route' => '/student/my-courses/enrolled',
                        'goal_date'    => now()->toDateString(),
                    ]
                );
            }
        } catch (\Throwable $e) {
            // Non-blocking
        }
    }
}
