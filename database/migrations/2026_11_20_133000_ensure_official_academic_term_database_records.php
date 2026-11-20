<?php

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Faculty;
use App\Models\Department;
use App\Models\Major;
use App\Models\Semester;
use App\Models\StudentGoal;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ensure official Academic Year, Semester, and Courses records exist for Student Dashboard.
     */
    public function up(): void
    {
        // 1. Ensure Active Academic Year exists
        $ay = null;
        if (Schema::hasTable('academic_years')) {
            $ay = AcademicYear::firstOrCreate(
                ['code' => 'AY-2026-2027'],
                [
                    'name'            => 'Academic Year 2026 – 2027',
                    'start_date'      => '2026-09-01',
                    'end_date'        => '2027-08-31',
                    'semesters_count' => 2,
                    'status'          => 'active',
                    'is_active'       => true,
                ]
            );
        }

        // 2. Ensure Active Semester 1 exists
        if (Schema::hasTable('semesters')) {
            Semester::firstOrCreate(
                ['code' => 'SEM-1-2026-2027'],
                [
                    'academic_year_id' => $ay?->id,
                    'name'             => 'Semester 1 — 2026-2027',
                    'parent_year'      => 'Academic Year 2026–2027',
                    'semester_num'     => 'Semester 1',
                    'start_date'       => '2026-09-01',
                    'end_date'         => '2027-02-15',
                    'status'           => 'active',
                    'is_active'        => true,
                ]
            );
        }

        // 3. Ensure primary user has active courses & enrollments
        try {
            $primaryUser = User::where('email', 'kosalsensok065@gmail.com')->first() ?: User::first();
            if ($primaryUser && Schema::hasTable('courses') && Schema::hasTable('enrollments')) {
                $courses = Course::where('status', 'published')->take(3)->get();
                foreach ($courses as $c) {
                    Enrollment::firstOrCreate(
                        ['student_id' => $primaryUser->id, 'course_id' => $c->id],
                        ['status' => 'active', 'enrolled_at' => now()]
                    );
                }

                if (Schema::hasTable('student_goals')) {
                    StudentGoal::firstOrCreate(
                        ['user_id' => $primaryUser->id, 'title' => 'Complete Chapter 1 on Web Development'],
                        [
                            'title_km'     => 'បញ្ចប់ជំពូកទី ១ ស្តីពីការអភិវឌ្ឍគេហទំព័រ',
                            'is_completed' => false,
                            'target_route' => '/student/my-courses/enrolled',
                            'goal_date'    => now()->toDateString(),
                        ]
                    );
                }
            }
        } catch (\Throwable $e) {
            // Non-blocking
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep data intact
    }
};
