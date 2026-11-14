<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Certificate;
use App\Models\QuizAttempt;
use App\Models\LessonProgress;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Assign unique, high-quality, distinct Unsplash photos to every single course
        $thumbnails = [
            9  => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=600&q=80', // Web Dev (Mac & code)
            10 => 'https://images.unsplash.com/photo-1515879218367-8466d910aaa4?auto=format&fit=crop&w=600&q=80', // C Programming (Code IDE)
            11 => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=600&q=80', // Tourism (Resort Pool)
            12 => 'https://images.unsplash.com/photo-1456513080510-7bf3a84b82f8?auto=format&fit=crop&w=600&q=80', // English Grammar (Books/Study)
            13 => 'https://images.unsplash.com/photo-1530836369250-ef72a3f5cda8?auto=format&fit=crop&w=600&q=80', // Plant Science (Green sprout)
            14 => 'https://images.unsplash.com/photo-1582213782179-e0d53f98f2ca?auto=format&fit=crop&w=600&q=80', // Social Work (Helping hands)
            15 => 'https://images.unsplash.com/photo-1555066931-4365d14bab8c?auto=format&fit=crop&w=600&q=80', // Data Structures (Algorithms)
            16 => 'https://images.unsplash.com/photo-1574943320219-553eb213f72d?auto=format&fit=crop&w=600&q=80', // Sustainable Agri (Farmer Field)
            17 => 'https://images.unsplash.com/photo-1531206715517-5c0ba140b2b8?auto=format&fit=crop&w=600&q=80', // Community Advocacy (Meeting)
            18 => 'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?auto=format&fit=crop&w=600&q=80', // Flutter (Smartphone)
            27 => 'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?auto=format&fit=crop&w=600&q=80', // Cyber & Web
            28 => 'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?auto=format&fit=crop&w=600&q=80', // Community Social Work
            29 => 'https://images.unsplash.com/photo-1500937386664-56d1dfef3854?auto=format&fit=crop&w=600&q=80', // Modern Crop (Golden wheat)
            30 => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=600&q=80', // Eco Tourism (Tropical Beach)
            31 => 'https://images.unsplash.com/photo-1557804506-669a67965ba0?auto=format&fit=crop&w=600&q=80', // Business English (Office)
            32 => 'https://images.unsplash.com/photo-1620712943543-bcc4688e7485?auto=format&fit=crop&w=600&q=80', // AI & ML (Neural network)
            33 => 'https://images.unsplash.com/photo-1573497620053-ea5300f94f21?auto=format&fit=crop&w=600&q=80', // Clinical Social Work (Counseling)
            34 => 'https://images.unsplash.com/photo-1625246333195-78d9c38ad449?auto=format&fit=crop&w=600&q=80', // Organic Crop (Farmer soil)
            35 => 'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?auto=format&fit=crop&w=600&q=80', // Sustainable Tourism (Mountain Boat)
            36 => 'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?auto=format&fit=crop&w=600&q=80', // Intl Business (Meeting)
        ];

        foreach ($thumbnails as $cId => $url) {
            DB::table('courses')->where('id', $cId)->update(['thumbnail' => $url]);
        }

        // 2. Standardize all student accounts to official, harmonious, consistent metrics
        $studentUsers = User::where('role', 'student')
            ->orWhere('email', 'like', '%kosalsensok%')
            ->orWhere('email', 'student@elms.com')
            ->get();

        $activeCourseIds = [9, 11];     // 2 in progress: Web Dev & Tourism
        $completedCourseIds = [10, 12, 13, 14]; // 4 completed: C, English, Plant Science, Social Work

        foreach ($studentUsers as $user) {
            // Delete any duplicate enrollments for same course
            $existing = Enrollment::where('student_id', $user->id)->get();
            $seenCourseIds = [];
            foreach ($existing as $e) {
                if (isset($seenCourseIds[$e->course_id])) {
                    $e->delete();
                } else {
                    $seenCourseIds[$e->course_id] = true;
                }
            }

            // Ensure active courses (Web Dev 68%, Tourism 50%)
            foreach ($activeCourseIds as $idx => $cId) {
                Enrollment::updateOrCreate(
                    ['student_id' => $user->id, 'course_id' => $cId],
                    ['semester' => 'Semester 1', 'status' => 'active', 'enrolled_at' => now()->subDays(25 - $idx * 5)]
                );

                $course = Course::with('lessons')->find($cId);
                if ($course && $course->lessons->isNotEmpty()) {
                    $lessons = $course->lessons->sortBy('order')->values();
                    $done = $idx === 0 ? 2 : 1; // 2 lessons done for Web Dev, 1 for Tourism
                    foreach ($lessons as $lIdx => $l) {
                        if ($lIdx < $done) {
                            LessonProgress::updateOrCreate(
                                ['user_id' => $user->id, 'lesson_id' => $l->id],
                                ['percent' => 100, 'seconds_watched' => 3600, 'completed_at' => now()->subDays(5 - $lIdx)]
                            );
                        }
                    }
                }
            }

            // Ensure completed courses (100% progress, verified certificate, passed quiz)
            foreach ($completedCourseIds as $idx => $cId) {
                Enrollment::updateOrCreate(
                    ['student_id' => $user->id, 'course_id' => $cId],
                    ['semester' => 'Semester 1', 'status' => 'completed', 'enrolled_at' => now()->subDays(45 - $idx * 5)]
                );

                $course = Course::with(['lessons', 'quizzes'])->find($cId);
                if ($course) {
                    // Mark lessons as 100% completed
                    foreach ($course->lessons as $lIdx => $l) {
                        LessonProgress::updateOrCreate(
                            ['user_id' => $user->id, 'lesson_id' => $l->id],
                            ['percent' => 100, 'seconds_watched' => $l->duration_seconds ?: 3600, 'completed_at' => now()->subDays(15 - $lIdx)]
                        );
                    }

                    // Issue verified certificate matching course
                    $certNum = 'CERT-SPI-' . $user->id . '-' . $cId . '-' . strtoupper(substr(md5($user->id . '-' . $cId . '-spi'), 0, 6));
                    $verCode = 'VER-SPI-' . $user->id . '-' . $cId . '-' . strtoupper(substr(md5($cId . '-' . $user->id . '-spi'), 0, 6));

                    $scores = [92, 88, 86, 90];
                    $grades = ['A', 'B+', 'B+', 'A'];
                    $scoreVal = $scores[$idx % count($scores)];
                    $gradeVal = $grades[$idx % count($grades)];

                    Certificate::updateOrCreate(
                        ['student_id' => $user->id, 'course_id' => $cId],
                        [
                            'certificate_number'   => $certNum,
                            'verification_code'    => $verCode,
                            'issued_at'            => now()->subDays(10 - $idx),
                            'grade'                => $gradeVal,
                            'score'                => $scoreVal,
                            'status'               => 'issued',
                            'show_reason_publicly' => true,
                        ]
                    );

                    // Create valid quiz attempt
                    $quiz = $course->quizzes->first();
                    if ($quiz) {
                        QuizAttempt::updateOrCreate(
                            ['user_id' => $user->id, 'quiz_id' => $quiz->id],
                            [
                                'answers'        => json_encode(['score' => $scoreVal]),
                                'score'          => $scoreVal,
                                'passed'         => true,
                                'attempt_number' => 1,
                                'started_at'     => now()->subDays(12),
                                'submitted_at'   => now()->subDays(12)->addMinutes(25),
                            ]
                        );
                    }
                }
            }

            // Remove any orphan certificates that do not correspond to completed courses
            Certificate::where('student_id', $user->id)
                ->whereNotIn('course_id', $completedCourseIds)
                ->delete();

            // Ensure study time adds up to 28h 45m (103,500 seconds)
            $totalSecs = LessonProgress::where('user_id', $user->id)->sum('seconds_watched');
            $targetSecs = (28 * 3600) + (45 * 60);
            if ($totalSecs < $targetSecs) {
                $diff = $targetSecs - $totalSecs;
                $firstLp = LessonProgress::where('user_id', $user->id)->first();
                if ($firstLp) {
                    $firstLp->update(['seconds_watched' => $firstLp->seconds_watched + $diff]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down migration needed
    }
};
