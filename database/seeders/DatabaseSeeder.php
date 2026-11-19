<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
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

        // ─── Fake / Sample Seeders Disabled for Official Production ───
        // $this->call([
        //     AcademicSeeder::class,
        //     DashboardRealDataSeeder::class,
        //     LessonContentSeeder::class,
        //     StudentDashboardSeeder::class,
        //     CourseMultilingualSeeder::class,
        // ]);
    }
}
