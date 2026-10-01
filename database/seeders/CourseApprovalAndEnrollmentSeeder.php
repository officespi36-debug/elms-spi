<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\CourseApprovalHistory;
use App\Models\CourseMaterial;
use App\Models\CourseVideo;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Major;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class CourseApprovalAndEnrollmentSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        $teachers = User::where('role', 'teacher')->get();
        $teacher = $teachers->first() ?? User::firstOrCreate(
            ['email' => 'teacher@elms.com'],
            ['name' => 'Mr. Sophea', 'password' => bcrypt('password'), 'role' => 'teacher']
        );

        $mjrIT = Major::where('code', 'MJR-IT-001')->first() ?? Major::first();
        $mjrTourism = Major::where('code', 'MJR-TRM-002')->first() ?? Major::skip(1)->first();
        $mjrEnglish = Major::where('code', 'MJR-ENG-003')->first() ?? Major::skip(2)->first();
        $mjrAgri = Major::where('code', 'MJR-AGR-004')->first() ?? Major::skip(3)->first();
        $mjrSocial = Major::where('code', 'MJR-SW-005')->first() ?? Major::skip(4)->first();

        $subWeb = Subject::where('code', 'SUB-IT-101')->first();
        $subProg = Subject::where('code', 'SUB-IT-102')->first();
        $subAlgo = Subject::where('code', 'SUB-IT-105')->first() ?? $subProg;
        $subTour = Subject::where('code', 'SUB-TM-101')->first();
        $subPlant = Subject::where('code', 'SUB-AG-101')->first();
        $subSW = Subject::where('code', 'SUB-SW-101')->first();

        // 1. Ensure Submitted & Reviewed Dates on Existing Published Courses
        $publishedCourses = Course::where('status', 'published')->get();
        foreach ($publishedCourses as $idx => $c) {
            $c->update([
                'submitted_at' => now()->subDays(12 + $idx),
                'reviewed_at'  => now()->subDays(10 + $idx),
                'academic_year' => $c->academic_year ?: 'Academic Year 2026 – 2027',
            ]);

            // Ensure lessons exist
            if ($c->lessons()->count() === 0) {
                $mod = \App\Models\Module::firstOrCreate(['course_id' => $c->id, 'title' => 'Module 1: Fundamental Concepts'], ['order' => 1]);
                Lesson::create(['course_id' => $c->id, 'module_id' => $mod->id, 'title' => 'Unit 1: Introduction & Principles', 'order' => 1, 'duration_seconds' => 3000]);
                Lesson::create(['course_id' => $c->id, 'module_id' => $mod->id, 'title' => 'Unit 2: Core Methodologies', 'order' => 2, 'duration_seconds' => 3600]);
                Lesson::create(['course_id' => $c->id, 'module_id' => $mod->id, 'title' => 'Unit 3: Practical Laboratory Case', 'order' => 3, 'duration_seconds' => 4500]);
            }

            // Ensure materials exist
            if ($c->materials()->count() === 0) {
                CourseMaterial::create(['course_id' => $c->id, 'title' => 'Syllabus & Lecture Notes (PDF)', 'type' => 'pdf', 'file_url' => '/files/syllabus.pdf']);
            }

            // Ensure quizzes exist
            if ($c->quizzes()->count() === 0) {
                Quiz::create(['course_id' => $c->id, 'title' => 'Midterm Assessment Quiz', 'time_limit_minutes' => 30, 'passing_score' => 70, 'status' => 'published']);
            }

            // Add approval history if none
            if ($c->approvalHistories()->count() === 0) {
                CourseApprovalHistory::create([
                    'course_id'   => $c->id,
                    'reviewer_id' => $admin?->id,
                    'action'      => 'approved',
                    'comment'     => 'Course syllabus and learning objectives meet SPI curriculum standards. Approved for enrollment.',
                    'created_at'  => now()->subDays(10 + $idx),
                ]);
            }
        }

        // 2. Create Pending Courses awaiting Admin Review
        $pendingCoursesData = [
            [
                'code'          => 'CRS-IT-DSA102',
                'title'         => 'Data Structures & Algorithms in Java',
                'teacher_id'    => $teacher->id,
                'major_id'      => $mjrIT?->id,
                'subject_id'    => $subAlgo?->id,
                'description'   => 'Comprehensive study of Big-O complexity, binary search trees, graph traversals, and dynamic programming.',
                'academic_year' => 'Academic Year 2026 – 2027',
                'learning_mode' => 'instructor_led',
                'status'        => 'pending',
                'submitted_at'  => now()->subDays(2),
            ],
            [
                'code'          => 'CRS-AG-SA102',
                'title'         => 'Sustainable Organic Agriculture',
                'teacher_id'    => $teachers->skip(1)->first()?->id ?? $teacher->id,
                'major_id'      => $mjrAgri?->id,
                'subject_id'    => $subPlant?->id,
                'description'   => 'Eco-friendly composting, bio-pesticide preparation, and crop rotation for Cambodian regional soils.',
                'academic_year' => 'Academic Year 2026 – 2027',
                'learning_mode' => 'instructor_led',
                'status'        => 'pending',
                'submitted_at'  => now()->subDays(1),
            ],
            [
                'code'          => 'CRS-SW-CD102',
                'title'         => 'Community Development & Advocacy',
                'teacher_id'    => $teachers->skip(2)->first()?->id ?? $teacher->id,
                'major_id'      => $mjrSocial?->id,
                'subject_id'    => $subSW?->id,
                'description'   => 'Fieldwork strategies, community stakeholder meetings, and socio-economic empowerment programs.',
                'academic_year' => 'Academic Year 2026 – 2027',
                'learning_mode' => 'self_paced',
                'status'        => 'pending',
                'submitted_at'  => now()->subHours(18),
            ],
        ];

        foreach ($pendingCoursesData as $data) {
            $course = Course::updateOrCreate(['code' => $data['code']], $data);

            if ($course->lessons()->count() === 0) {
                $mod = \App\Models\Module::firstOrCreate(['course_id' => $course->id, 'title' => 'Module 1: Foundations'], ['order' => 1]);
                Lesson::create(['course_id' => $course->id, 'module_id' => $mod->id, 'title' => 'Lesson 1: Foundational Framework', 'order' => 1, 'duration_seconds' => 2700]);
                Lesson::create(['course_id' => $course->id, 'module_id' => $mod->id, 'title' => 'Lesson 2: Core Practical Concepts', 'order' => 2, 'duration_seconds' => 3600]);
                Lesson::create(['course_id' => $course->id, 'module_id' => $mod->id, 'title' => 'Lesson 3: Applied Industry Scenarios', 'order' => 3, 'duration_seconds' => 3600]);
            }

            if ($course->materials()->count() === 0) {
                CourseMaterial::create(['course_id' => $course->id, 'title' => 'Course Outline & Reading Material', 'type' => 'pdf', 'file_url' => '/files/reading.pdf']);
            }

            if ($course->quizzes()->count() === 0) {
                Quiz::create(['course_id' => $course->id, 'title' => 'Concept Comprehension Check 1', 'time_limit_minutes' => 20, 'passing_score' => 60, 'status' => 'draft']);
            }

            if ($course->approvalHistories()->count() === 0) {
                CourseApprovalHistory::create([
                    'course_id'   => $course->id,
                    'reviewer_id' => null,
                    'action'      => 'submitted',
                    'comment'     => 'Teacher submitted course for administrative review and publication approval.',
                    'created_at'  => $data['submitted_at'],
                ]);
            }
        }

        // 3. Create Rejected Course with Rejection Reason
        $rejectedCourse = Course::updateOrCreate(['code' => 'CRS-IT-MAD101'], [
            'code'          => 'CRS-IT-MAD101',
            'title'         => 'Mobile App Development with Flutter',
            'teacher_id'    => $teacher->id,
            'major_id'      => $mjrIT?->id,
            'subject_id'    => $subWeb?->id,
            'description'   => 'Cross-platform mobile apps with Flutter and Dart, state management, and Firebase cloud authentication.',
            'academic_year' => 'Academic Year 2026 – 2027',
            'learning_mode' => 'instructor_led',
            'status'        => 'rejected',
            'submitted_at'  => now()->subDays(6),
            'reviewed_at'   => now()->subDays(4),
            'rejection_note' => 'ត្រូវការកែសម្រួល៖ ខ្វះ Syllabus សប្តាហ៍ទី ៤ និង Quiz Bank មិនទាន់មានសំណួរគ្រប់គ្រាន់ (Need Week 4 lesson plan and expanded quiz bank).',
        ]);

        if ($rejectedCourse->lessons()->count() === 0) {
            $modRej = \App\Models\Module::firstOrCreate(['course_id' => $rejectedCourse->id, 'title' => 'Module 1: Dart Basics'], ['order' => 1]);
            Lesson::create(['course_id' => $rejectedCourse->id, 'module_id' => $modRej->id, 'title' => 'Lesson 1: Dart Basics', 'order' => 1, 'duration_seconds' => 2700]);
        }

        if ($rejectedCourse->approvalHistories()->count() === 0) {
            CourseApprovalHistory::create([
                'course_id'   => $rejectedCourse->id,
                'reviewer_id' => null,
                'action'      => 'submitted',
                'comment'     => 'Initial course submission by teacher.',
                'created_at'  => now()->subDays(6),
            ]);
            CourseApprovalHistory::create([
                'course_id'   => $rejectedCourse->id,
                'reviewer_id' => $admin?->id,
                'action'      => 'rejected',
                'comment'     => $rejectedCourse->rejection_note,
                'created_at'  => now()->subDays(4),
            ]);
        }

        // 4. Seed Academic Enrollments
        $students = User::where('role', 'student')->where('status', 'active')->get();
        if ($students->count() < 6) {
            // Ensure at least 6 active students exist with matching SPI majors
            $studentsData = [
                ['name' => 'Sok Dara', 'email' => 'dara.sok@spi.edu.kh', 'student_code' => 'SPI-2026-001', 'major_id' => $mjrIT?->id, 'academic_year' => 'Academic Year 2026 – 2027'],
                ['name' => 'Keo Pich', 'email' => 'pich.keo@spi.edu.kh', 'student_code' => 'SPI-2026-002', 'major_id' => $mjrIT?->id, 'academic_year' => 'Academic Year 2026 – 2027'],
                ['name' => 'Vannak Sambath', 'email' => 'sambath.vannak@spi.edu.kh', 'student_code' => 'SPI-2026-003', 'major_id' => $mjrTourism?->id, 'academic_year' => 'Academic Year 2026 – 2027'],
                ['name' => 'Srey Mom', 'email' => 'mom.srey@spi.edu.kh', 'student_code' => 'SPI-2026-004', 'major_id' => $mjrEnglish?->id, 'academic_year' => 'Academic Year 2026 – 2027'],
                ['name' => 'Heng Ratana', 'email' => 'ratana.heng@spi.edu.kh', 'student_code' => 'SPI-2026-005', 'major_id' => $mjrAgri?->id, 'academic_year' => 'Academic Year 2026 – 2027'],
                ['name' => 'Chhorn Thida', 'email' => 'thida.chhorn@spi.edu.kh', 'student_code' => 'SPI-2026-006', 'major_id' => $mjrSocial?->id, 'academic_year' => 'Academic Year 2026 – 2027'],
            ];

            foreach ($studentsData as $st) {
                User::updateOrCreate(['email' => $st['email']], [
                    'name'          => $st['name'],
                    'password'      => bcrypt('password'),
                    'role'          => 'student',
                    'student_code'  => $st['student_code'],
                    'major_id'      => $st['major_id'],
                    'academic_year' => $st['academic_year'],
                    'status'        => 'active',
                ]);
            }
            $students = User::where('role', 'student')->where('status', 'active')->get();
        }

        $allPublishedCourses = Course::where('status', 'published')->get();

        $enrollmentPlan = [
            ['student_idx' => 0, 'course_code' => 'CRS-IT-WD101', 'status' => 'active', 'days_ago' => 30],
            ['student_idx' => 1, 'course_code' => 'CRS-IT-CP101', 'status' => 'completed', 'days_ago' => 45],
            ['student_idx' => 2, 'course_code' => 'CRS-TM-TB101', 'status' => 'active', 'days_ago' => 20],
            ['student_idx' => 3, 'course_code' => 'CRS-EL-EG101', 'status' => 'dropped', 'days_ago' => 28],
            ['student_idx' => 4, 'course_code' => 'CRS-AG-PS101', 'status' => 'active', 'days_ago' => 25],
            ['student_idx' => 5, 'course_code' => 'CRS-SW-SW101', 'status' => 'active', 'days_ago' => 15],
        ];

        foreach ($enrollmentPlan as $plan) {
            $st = $students[$plan['student_idx']] ?? null;
            $crs = Course::where('code', $plan['course_code'])->first();

            if ($st && $crs) {
                Enrollment::updateOrCreate([
                    'student_id' => $st->id,
                    'course_id'  => $crs->id,
                ], [
                    'status'      => $plan['status'],
                    'enrolled_at' => now()->subDays($plan['days_ago']),
                ]);
            }
        }
    }
}
