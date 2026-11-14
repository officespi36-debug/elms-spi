<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Major;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StudentDashboardSeeder extends Seeder
{
    public function run(): void
    {
        $mjrIT = Major::where('code', 'MJR-IT-001')->first() ?? Major::first();

        // 1. Ensure Target Student Users Exist with Active Status and Proper Passwords
        $studentsToSeed = [
            [
                'email'        => 'kosalsensok582@gmail.com',
                'name'         => 'kosal sensok',
                'name_kh'      => 'កុសល សែនសុខ',
                'student_code' => 'SPI-2026-582',
                'phone'        => '096888555',
                'password'     => 'Kosalsensokpk@12pk',
            ],
            [
                'email'        => 'kosalsensok098@gmail.com',
                'name'         => 'kosal sensok',
                'name_kh'      => 'កុសល សែនសុខ',
                'student_code' => 'SPI-2026-098',
                'phone'        => '096888098',
                'password'     => 'Kosalsensokpk@12pk',
            ],
            [
                'email'        => 'student@elms.com',
                'name'         => 'Chan Dara Student',
                'name_kh'      => 'ចាន់ ដារ៉ា',
                'student_code' => 'SPI-2026-001',
                'phone'        => '011222333',
                'password'     => 'password',
            ],
        ];

        $targetUsers = [];
        foreach ($studentsToSeed as $data) {
            $user = User::updateOrCreate(['email' => $data['email']], [
                'name'           => $data['name'],
                'name_kh'        => $data['name_kh'],
                'password'       => Hash::make($data['password']),
                'role'           => 'student',
                'student_code'   => $data['student_code'],
                'phone'          => $data['phone'],
                'major_id'       => $mjrIT?->id,
                'academic_year'  => 'Academic Year 2026 – 2027',
                'status'         => 'active',
                'is_active'      => true,
                'login_attempts' => 0,
                'locked_until'   => null,
            ]);
            $targetUsers[] = $user;
        }

        // 2. Define the Realistic 9-Course Curriculum Plan
        // 3 In Progress + 4 Completed + 2 Not Started = 9 Total Courses
        $curriculum = [
            // In Progress (Active)
            [
                'course_code' => 'CRS-IT-WD101',
                'status'      => 'active',
                'complete_n'  => 2, // 2 of 3 lessons done (66%)
                'enrolled_at' => now()->subDays(25),
            ],
            [
                'course_code' => 'CRS-MODERN-482',
                'status'      => 'active',
                'complete_n'  => 1, // 1 of 2 lessons done (50%)
                'enrolled_at' => now()->subDays(20),
            ],
            [
                'course_code' => 'CRS-BUSINE-739',
                'status'      => 'active',
                'complete_n'  => 1, // 1 of 2 lessons done (50%)
                'enrolled_at' => now()->subDays(15),
            ],

            // Completed (100% with certificates & quiz attempts)
            [
                'course_code' => 'CRS-IT-CP101',
                'status'      => 'completed',
                'complete_n'  => 99, // all lessons done
                'cert_code'   => 'VER-CP101-8982',
                'cert_num'    => 'CERT-SPI-CP101-001',
                'grade'       => 'A',
                'score'       => 94,
                'quiz_score'  => 92,
                'enrolled_at' => now()->subDays(60),
            ],
            [
                'course_code' => 'CRS-EL-EG101',
                'status'      => 'completed',
                'complete_n'  => 99,
                'cert_code'   => 'VER-EG101-7712',
                'cert_num'    => 'CERT-SPI-EG101-002',
                'grade'       => 'A',
                'score'       => 90,
                'quiz_score'  => 88,
                'enrolled_at' => now()->subDays(55),
            ],
            [
                'course_code' => 'CRS-SW-SW101',
                'status'      => 'completed',
                'complete_n'  => 99,
                'cert_code'   => 'VER-SW101-6543',
                'cert_num'    => 'CERT-SPI-SW101-003',
                'grade'       => 'B+',
                'score'       => 85,
                'quiz_score'  => 85,
                'enrolled_at' => now()->subDays(50),
            ],
            [
                'course_code' => 'CRS-AG-PS101',
                'status'      => 'completed',
                'complete_n'  => 99,
                'cert_code'   => 'VER-PS101-4321',
                'cert_num'    => 'CERT-SPI-PS101-004',
                'grade'       => 'A',
                'score'       => 92,
                'quiz_score'  => 90,
                'enrolled_at' => now()->subDays(45),
            ],

            // Not Started (Active Enrollment with 0% progress)
            [
                'course_code' => 'CRS-SUSTAI-251',
                'status'      => 'active',
                'complete_n'  => 0,
                'enrolled_at' => now()->subDays(5),
            ],
            [
                'course_code' => 'CRS-ADVANC-506',
                'status'      => 'active',
                'complete_n'  => 0,
                'enrolled_at' => now()->subDays(2),
            ],
        ];

        // 3. Apply the Curriculum to Each Target Student User
        foreach ($targetUsers as $studentUser) {
            foreach ($curriculum as $item) {
                $course = Course::where('code', $item['course_code'])->first();
                if (!$course) {
                    continue;
                }

                // 3.1 Enrollment Record
                Enrollment::updateOrCreate([
                    'student_id' => $studentUser->id,
                    'course_id'  => $course->id,
                ], [
                    'semester'    => 'Semester 1',
                    'status'      => $item['status'],
                    'enrolled_at' => $item['enrolled_at'],
                ]);

                // 3.2 Lessons & LessonProgress
                $lessons = $course->lessons()->orderBy('order')->get();
                $completedCount = 0;
                foreach ($lessons as $idx => $lesson) {
                    if ($item['complete_n'] > 0 && ($idx < $item['complete_n'] || $item['complete_n'] >= 90)) {
                        LessonProgress::updateOrCreate([
                            'user_id'   => $studentUser->id,
                            'lesson_id' => $lesson->id,
                        ], [
                            'percent'         => 100,
                            'seconds_watched' => $lesson->duration_seconds ?: 3600,
                            'completed_at'    => now()->subDays(10 - $idx),
                        ]);
                        $completedCount++;
                    } else {
                        // Uncompleted lesson - clear or set partial
                        LessonProgress::where('user_id', $studentUser->id)
                            ->where('lesson_id', $lesson->id)
                            ->delete();
                    }
                }

                // 3.3 Certificates (for Completed Courses)
                if ($item['status'] === 'completed') {
                    Certificate::updateOrCreate([
                        'student_id' => $studentUser->id,
                        'course_id'  => $course->id,
                    ], [
                        'certificate_number' => $item['cert_num'] . '-' . $studentUser->id,
                        'verification_code'  => $item['cert_code'] . '-' . $studentUser->id,
                        'issued_at'          => now()->subDays(15),
                        'grade'              => $item['grade'],
                        'score'              => $item['score'],
                        'status'             => 'issued',
                        'show_reason_publicly' => true,
                    ]);

                    // 3.4 Quiz Attempt
                    $quiz = $course->quizzes()->first();
                    if ($quiz) {
                        QuizAttempt::updateOrCreate([
                            'user_id' => $studentUser->id,
                            'quiz_id' => $quiz->id,
                        ], [
                            'answers'        => json_encode(['q1' => 'A', 'q2' => 'B', 'q3' => 'C', 'score' => $item['quiz_score']]),
                            'score'          => $item['quiz_score'],
                            'passed'         => true,
                            'attempt_number' => 1,
                            'started_at'     => now()->subDays(16),
                            'submitted_at'   => now()->subDays(16)->addMinutes(25),
                        ]);
                    }
                } else {
                    // Remove accidental certificates for active or enrolled
                    Certificate::where('student_id', $studentUser->id)
                        ->where('course_id', $course->id)
                        ->delete();
                }
            }
        }
    }
}
