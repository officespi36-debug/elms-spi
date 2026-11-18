<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One-time production cleanup migration to purge all legacy dummy/seeded
     * courses, faculties, majors, enrollments, certificates, quizzes, and demo
     * accounts while preserving the official Super Admin account.
     */
    public function up(): void
    {
        $superAdminEmail = 'kosalsensok065@gmail.com';

        // 1. Ensure the 1 official Super Admin account exists & is active
        if (Schema::hasTable('users')) {
            $superAdmin = User::firstOrNew(['email' => $superAdminEmail]);
            $superAdmin->name = $superAdmin->name ?: 'kosal sensok';
            $superAdmin->name_kh = $superAdmin->name_kh ?: 'កុសល សែនសុខ';
            $superAdmin->role = 'admin';
            $superAdmin->status = 'active';
            $superAdmin->is_active = true;
            $superAdmin->login_attempts = 0;

            if (!$superAdmin->exists) {
                $superAdmin->password = Hash::make('Kosalsensokpk@12pk');
                $superAdmin->email_verified_at = now();
            }
            $superAdmin->save();
        }

        // 2. Purge all legacy seeded / dummy courses & dependent learning records
        $seededCourseCodes = [
            'CRS-IT-WD101', 'CRS-IT-CP101', 'CRS-TM-TB101', 'CRS-TH-TB101',
            'CRS-EL-EG101', 'CRS-AG-PS101', 'CRS-SW-SW101', 'CRS-IT-DSA102',
            'CRS-SW-CD102', 'CRS-AG-SA102', 'CRS-IT-MD103', 'CRS-IT-MAD101',
            'CRS-MODERN-482', 'CRS-BUSINE-739',
        ];
        $seededCourseTitles = [
            'Web Development with Vue & Laravel',
            'C Programming Basics',
            'Tourism Basics & Hospitality',
            'English Grammar Mastery',
            'Plant Science Fundamentals',
            'Introduction to Social Work',
            'Web & Mobile Development',
            'Community Social Work',
            'Modern Crop Agronomy',
            'Eco-Tourism & Hospitality',
            'Business English Communication',
            'Advanced Machine Learning & AI',
            'Clinical Social Work Case Studies',
            'Organic Crop Agronomy Practices',
            'Sustainable Eco-Tourism Operations',
            'International Business English',
            'Data Structures & Algorithms in Java',
            'Community Development & Advocacy',
            'Mobile App Development with Flutter',
        ];

        if (Schema::hasTable('courses')) {
            $dummyCourseIds = DB::table('courses')
                ->whereIn('code', $seededCourseCodes)
                ->orWhereIn('title', $seededCourseTitles)
                ->orWhere('code', 'like', 'CRS-WEBAND-%')
                ->orWhere('code', 'like', 'CRS-COMMUN-%')
                ->orWhere('code', 'like', 'CRS-MODERN-%')
                ->orWhere('code', 'like', 'CRS-ECOTOU-%')
                ->orWhere('code', 'like', 'CRS-BUSINE-%')
                ->orWhere('code', 'like', 'CRS-ADVANC-%')
                ->orWhere('code', 'like', 'CRS-CLINIC-%')
                ->orWhere('code', 'like', 'CRS-ORGANI-%')
                ->orWhere('code', 'like', 'CRS-SUSTAI-%')
                ->orWhere('code', 'like', 'CRS-INTERN-%')
                ->pluck('id')
                ->all();

            if (!empty($dummyCourseIds)) {
                $lessonIds = Schema::hasTable('lessons')
                    ? DB::table('lessons')->whereIn('course_id', $dummyCourseIds)->pluck('id')->all()
                    : [];
                $quizIds = Schema::hasTable('quizzes')
                    ? DB::table('quizzes')->whereIn('course_id', $dummyCourseIds)->pluck('id')->all()
                    : [];

                if (!empty($lessonIds)) {
                    foreach (['lesson_progress', 'lesson_comments', 'lesson_resources', 'lesson_notes'] as $tbl) {
                        if (Schema::hasTable($tbl)) {
                            DB::table($tbl)->whereIn('lesson_id', $lessonIds)->delete();
                        }
                    }
                }

                if (!empty($quizIds)) {
                    if (Schema::hasTable('quiz_attempts')) {
                        $attemptIds = DB::table('quiz_attempts')->whereIn('quiz_id', $quizIds)->pluck('id')->all();
                        if (!empty($attemptIds) && Schema::hasTable('quiz_attempt_answers')) {
                            DB::table('quiz_attempt_answers')->whereIn('quiz_attempt_id', $attemptIds)->delete();
                        }
                        DB::table('quiz_attempts')->whereIn('quiz_id', $quizIds)->delete();
                    }
                    if (Schema::hasTable('questions')) {
                        $questionIds = DB::table('questions')->whereIn('quiz_id', $quizIds)->pluck('id')->all();
                        if (!empty($questionIds) && Schema::hasTable('options')) {
                            DB::table('options')->whereIn('question_id', $questionIds)->delete();
                        }
                        DB::table('questions')->whereIn('quiz_id', $quizIds)->delete();
                    }
                    if (Schema::hasTable('assignment_submissions')) {
                        DB::table('assignment_submissions')->whereIn('quiz_id', $quizIds)->delete();
                    }
                    DB::table('quizzes')->whereIn('id', $quizIds)->delete();
                }

                foreach ([
                    'certificates', 'enrollments', 'course_reviews', 'course_schedules',
                    'course_deadlines', 'course_materials', 'course_videos',
                    'course_approval_histories', 'payments', 'lessons', 'modules',
                ] as $tbl) {
                    if (Schema::hasTable($tbl) && Schema::hasColumn($tbl, 'course_id')) {
                        DB::table($tbl)->whereIn('course_id', $dummyCourseIds)->delete();
                    }
                }

                DB::table('courses')->whereIn('id', $dummyCourseIds)->delete();
            }
        }

        // 3. Purge legacy seeded Academic structure (Subjects, Majors, Departments, Faculties, Semesters, AcademicYears)
        $seededSubjectCodes = [
            'SUB-IT-101', 'SUB-IT-102', 'SUB-IT-103', 'SUB-IT-104', 'SUB-IT-105',
            'SUB-TM-101', 'SUB-TM-102', 'SUB-TM-103',
            'SUB-EL-101', 'SUB-EL-102', 'SUB-EL-103',
            'SUB-AG-101', 'SUB-AG-102', 'SUB-AG-103',
            'SUB-SW-101', 'SUB-SW-102', 'SUB-SW-103',
        ];
        if (Schema::hasTable('subjects')) {
            $subIds = DB::table('subjects')->whereIn('code', $seededSubjectCodes)->pluck('id')->all();
            if (!empty($subIds)) {
                if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'subject_id')) {
                    DB::table('courses')->whereIn('subject_id', $subIds)->update(['subject_id' => null]);
                }
                DB::table('subjects')->whereIn('id', $subIds)->delete();
            }
        }

        $seededMajorCodes = ['MJR-IT-001', 'MJR-TRM-002', 'MJR-ENG-003', 'MJR-AGR-004', 'MJR-SW-005'];
        if (Schema::hasTable('majors')) {
            $majorIds = DB::table('majors')->whereIn('code', $seededMajorCodes)->pluck('id')->all();
            if (!empty($majorIds)) {
                if (Schema::hasTable('users') && Schema::hasColumn('users', 'major_id')) {
                    DB::table('users')->whereIn('major_id', $majorIds)->update(['major_id' => null]);
                }
                if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'major_id')) {
                    DB::table('courses')->whereIn('major_id', $majorIds)->update(['major_id' => null]);
                }
                if (Schema::hasTable('subjects') && Schema::hasColumn('subjects', 'major_id')) {
                    DB::table('subjects')->whereIn('major_id', $majorIds)->delete();
                }
                DB::table('majors')->whereIn('id', $majorIds)->delete();
            }
        }

        $seededDeptCodes = [
            'DEPT-CMP-001', 'DEPT-SE-002', 'DEPT-TRM-003', 'DEPT-HSP-004',
            'DEPT-EDU-005', 'DEPT-HUM-006', 'DEPT-LNG-007', 'DEPT-AGR-008',
            'DEPT-PLN-009', 'DEPT-SOC-010', 'DEPT-DEV-011', 'DEPT-COM-012',
        ];
        if (Schema::hasTable('departments')) {
            $deptIds = DB::table('departments')->whereIn('code', $seededDeptCodes)->pluck('id')->all();
            if (!empty($deptIds)) {
                if (Schema::hasTable('majors') && Schema::hasColumn('majors', 'department_id')) {
                    DB::table('majors')->whereIn('department_id', $deptIds)->delete();
                }
                DB::table('departments')->whereIn('id', $deptIds)->delete();
            }
        }

        $seededFacultyCodes = ['FAC-001', 'FAC-002', 'FAC-003', 'FAC-004', 'FAC-005'];
        if (Schema::hasTable('faculties')) {
            $facIds = DB::table('faculties')->whereIn('code', $seededFacultyCodes)->pluck('id')->all();
            if (!empty($facIds)) {
                if (Schema::hasTable('departments') && Schema::hasColumn('departments', 'faculty_id')) {
                    DB::table('departments')->whereIn('faculty_id', $facIds)->delete();
                }
                DB::table('faculties')->whereIn('id', $facIds)->delete();
            }
        }

        if (Schema::hasTable('semesters')) {
            DB::table('semesters')->whereIn('code', [
                'SEM-1-2024-2025', 'SEM-2-2024-2025',
                'SEM-1-2023-2024', 'SEM-2-2023-2024',
                'SEM-1-2025-2026',
            ])->delete();
        }

        if (Schema::hasTable('academic_years')) {
            DB::table('academic_years')->whereIn('code', [
                'AY-2022-2023', 'AY-2023-2024', 'AY-2024-2025', 'AY-2025-2026', 'AY-2026-2027',
            ])->delete();
        }

        if (Schema::hasTable('announcements')) {
            DB::table('announcements')->whereIn('title_en', [
                'Semester 2 Academic Launch & Course Enrollment Window',
                'Final Exam Preparation & Quiz Bank Practice Open',
            ])->delete();
        }

        // 4. Purge all legacy fake/demo accounts (preserving real Super Admin)
        if (Schema::hasTable('users')) {
            $fakeEmails = [
                'admin@elms.com',
                'teacher@elms.com',
                'student@elms.com',
                'dara_locked@elms.com',
                'kosalsensok582@gmail.com',
                'kosalsensok098@gmail.com',
                'pitaja6820@ehwit.com',
                'sokha.meas@spilms.tech',
                'sophea.chea@spilms.tech',
                'sreyneang.pich@spilms.tech',
                'vuthy.seng@spilms.tech',
                'linda.meng@spilms.tech',
                'virak.chea@spilms.tech',
                'chantha.heng@spilms.tech',
                'vanna.kim@spilms.tech',
                'daneth.ouk@spilms.tech',
            ];

            $fakeUserIds = DB::table('users')
                ->where('email', '!=', $superAdminEmail)
                ->where(function ($q) use ($fakeEmails) {
                    $q->whereIn('email', $fakeEmails)
                        ->orWhere('email', 'like', '%@elms.com')
                        ->orWhere('email', 'like', '%@elms.edu')
                        ->orWhere('email', 'like', '%@spi.edu.kh')
                        ->orWhere('email', 'like', '%@ehwit.com')
                        ->orWhere('email', 'like', '%.risk@spilms.tech')
                        ->orWhere('email', 'like', '%@example.com');
                })
                ->pluck('id')
                ->all();

            if (!empty($fakeUserIds)) {
                // Reassign or delete any stray courses owned by fake teachers before deleting users
                if (Schema::hasTable('courses')) {
                    $orphanCourseIds = DB::table('courses')->whereIn('teacher_id', $fakeUserIds)->pluck('id')->all();
                    if (!empty($orphanCourseIds)) {
                        foreach (['certificates', 'enrollments', 'course_reviews', 'lessons', 'modules', 'quizzes'] as $tbl) {
                            if (Schema::hasTable($tbl) && Schema::hasColumn($tbl, 'course_id')) {
                                DB::table($tbl)->whereIn('course_id', $orphanCourseIds)->delete();
                            }
                        }
                        DB::table('courses')->whereIn('id', $orphanCourseIds)->delete();
                    }
                }

                foreach ([
                    ['enrollments', 'student_id'],
                    ['certificates', 'student_id'],
                    ['lesson_progress', 'user_id'],
                    ['lesson_progress', 'student_id'],
                    ['quiz_attempts', 'user_id'],
                    ['quiz_attempts', 'student_id'],
                    ['course_reviews', 'user_id'],
                    ['lesson_comments', 'user_id'],
                    ['lesson_notes', 'user_id'],
                    ['assignment_submissions', 'student_id'],
                    ['notification_reads', 'user_id'],
                    ['student_goals', 'user_id'],
                    ['student_preferences', 'user_id'],
                    ['ai_recommendations', 'user_id'],
                    ['ai_recommendations', 'student_id'],
                    ['payments', 'student_id'],
                ] as [$tbl, $col]) {
                    if (Schema::hasTable($tbl) && Schema::hasColumn($tbl, $col)) {
                        DB::table($tbl)->whereIn($col, $fakeUserIds)->delete();
                    }
                }

                DB::table('users')->whereIn('id', $fakeUserIds)->delete();
            }
        }

        try {
            Cache::flush();
        } catch (\Throwable $e) {
            // Ignore cache flush errors during CLI migration
        }
    }

    public function down(): void
    {
        // Irreversible one-time official launch cleanup
    }
};
