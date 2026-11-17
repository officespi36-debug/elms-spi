<?php

use App\Models\AcademicYear;
use App\Models\AiRecommendation;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\QuizAttempt;
use App\Models\Semester;
use App\Models\StudentGoal;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('student_goals')) {
            Schema::create('student_goals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('title', 255);
                $table->string('title_km', 255)->nullable();
                $table->boolean('is_completed')->default(false);
                $table->string('target_route', 255)->nullable();
                $table->date('goal_date')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'goal_date']);
            });
        }

        // Ensure 2026-2027 is the single active academic year and semester in academic_years/semesters
        if (Schema::hasTable('academic_years')) {
            AcademicYear::where('code', '!=', 'AY-2026-2027')->update(['is_active' => false]);
            AcademicYear::where('code', '!=', 'AY-2026-2027')
                ->where('status', 'active')
                ->update(['status' => 'completed']);

            $ay2026 = AcademicYear::updateOrCreate(
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

            if (Schema::hasTable('semesters') && $ay2026) {
                Semester::where('code', '!=', 'SEM-1-2026-2027')->update(['is_active' => false]);
                Semester::where('code', '!=', 'SEM-1-2026-2027')
                    ->where('status', 'active')
                    ->update(['status' => 'completed']);

                Semester::updateOrCreate(
                    ['code' => 'SEM-1-2026-2027'],
                    [
                        'academic_year_id' => $ay2026->id,
                        'name'             => 'Semester 1 — 2026-2027',
                        'parent_year'      => $ay2026->name,
                        'semester_num'     => 'Semester 1',
                        'start_date'       => '2026-09-01',
                        'end_date'         => '2027-02-15',
                        'status'           => 'active',
                        'is_active'        => true,
                    ]
                );
            }
        }

        // Seed real initial goals & deduplicate/seed AI recommendations from real DB records
        $students = User::where('role', 'student')->get();
        $today = now('Asia/Phnom_Penh')->toDateString();

        // Deduplicate identical (user_id, type, reason) ai_recommendations
        if (Schema::hasTable('ai_recommendations')) {
            $seenRecs = [];
            foreach (AiRecommendation::orderBy('id')->get() as $rec) {
                $key = $rec->user_id . '|' . $rec->type . '|' . trim((string) $rec->reason);
                if (isset($seenRecs[$key])) {
                    $rec->delete();
                } else {
                    $seenRecs[$key] = true;
                }
            }
        }

        foreach ($students as $student) {
            $activeEnrollments = Enrollment::where('student_id', $student->id)
                ->where('status', 'active')
                ->with(['course' => function ($q) {
                    $q->with('lessons');
                }])
                ->latest('updated_at')
                ->get();

            $allEnrollments = $activeEnrollments->isNotEmpty()
                ? $activeEnrollments
                : Enrollment::where('student_id', $student->id)
                    ->whereIn('status', ['active', 'completed'])
                    ->with(['course' => function ($q) {
                        $q->with('lessons');
                    }])
                    ->latest('updated_at')
                    ->get();

            // Seed initial StudentGoal rows only when the student has active enrollments and 0 goals yet
            if (Schema::hasTable('student_goals') && $activeEnrollments->isNotEmpty()) {
                $hasGoals = StudentGoal::where('user_id', $student->id)->exists();
                if (!$hasGoals) {
                    $completedLessonIds = LessonProgress::where('user_id', $student->id)
                        ->where(function ($q) {
                            $q->where('percent', '>=', 90)->orWhereNotNull('completed_at');
                        })
                        ->pluck('lesson_id')
                        ->flip()
                        ->toArray();

                    $firstActiveEnrollment = $activeEnrollments->first(fn ($e) => $e->course !== null);
                    if ($firstActiveEnrollment && $firstActiveEnrollment->course) {
                        $c = $firstActiveEnrollment->course;
                        $lessons = $c->lessons->sortBy('order')->values();
                        $nextLesson = $lessons->first(fn ($l) => !isset($completedLessonIds[$l->id])) ?? $lessons->first();

                        $courseTitleEn = trim((string) $c->title);
                        $courseTitleKm = trim((string) ($c->title_kh ?: $c->title));
                        $lessonTitleEn = $nextLesson ? trim((string) $nextLesson->title) : 'Course Overview';
                        $lessonTitleKm = $nextLesson ? trim((string) ($nextLesson->kh_title ?: $nextLesson->title)) : 'ទិដ្ឋភាពទូទៅ';

                        StudentGoal::create([
                            'user_id'      => $student->id,
                            'title'        => "{$courseTitleEn} - {$lessonTitleEn}",
                            'title_km'     => "{$courseTitleKm} - {$lessonTitleKm}",
                            'is_completed' => $nextLesson ? isset($completedLessonIds[$nextLesson->id]) : false,
                            'target_route' => $nextLesson
                                ? "/student/my-courses/{$c->id}/lessons/{$nextLesson->id}"
                                : "/student/my-courses/{$c->id}/continue",
                            'goal_date'    => $today,
                        ]);

                        $secondEnrollment = $activeEnrollments->skip(1)->first(fn ($e) => $e->course !== null);
                        if ($secondEnrollment && $secondEnrollment->course) {
                            $c2 = $secondEnrollment->course;
                            $c2Lessons = $c2->lessons->sortBy('order')->values();
                            $c2Next = $c2Lessons->first(fn ($l) => !isset($completedLessonIds[$l->id])) ?? $c2Lessons->first();
                            $c2TitleEn = trim((string) $c2->title);
                            $c2TitleKm = trim((string) ($c2->title_kh ?: $c2->title));
                            $c2LessonEn = $c2Next ? trim((string) $c2Next->title) : 'Review Lessons';
                            $c2LessonKm = $c2Next ? trim((string) ($c2Next->kh_title ?: $c2Next->title)) : 'រំលឹកមេរៀន';

                            StudentGoal::create([
                                'user_id'      => $student->id,
                                'title'        => "{$c2TitleEn} - {$c2LessonEn}",
                                'title_km'     => "{$c2TitleKm} - {$c2LessonKm}",
                                'is_completed' => false,
                                'target_route' => $c2Next
                                    ? "/student/my-courses/{$c2->id}/lessons/{$c2Next->id}"
                                    : "/student/my-courses/{$c2->id}/continue",
                                'goal_date'    => $today,
                            ]);
                        } else {
                            StudentGoal::create([
                                'user_id'      => $student->id,
                                'title'        => 'Complete today’s quiz practice',
                                'title_km'     => 'អនុវត្តសំណួរ Quiz ប្រចាំថ្ងៃ',
                                'is_completed' => QuizAttempt::where('user_id', $student->id)->whereDate('submitted_at', $today)->exists(),
                                'target_route' => '/student/quizzes/practice',
                                'goal_date'    => $today,
                            ]);
                        }
                    }
                }
            }

            // Ensure existing students with real QuizAttempts / Enrollments have real AiRecommendation rows
            if (Schema::hasTable('ai_recommendations') && $allEnrollments->isNotEmpty()) {
                $hasAiRecs = AiRecommendation::where('user_id', $student->id)
                    ->where('is_dismissed', false)
                    ->exists();

                if (!$hasAiRecs) {
                    $latestAttempt = QuizAttempt::where('user_id', $student->id)
                        ->whereNotNull('score')
                        ->with(['quiz:id,course_id,title', 'quiz.course:id,title,title_kh'])
                        ->oldest('score')
                        ->first();

                    if ($latestAttempt && $latestAttempt->quiz) {
                        $scoreNum = (int) round((float) $latestAttempt->score);
                        $quizTitle = $latestAttempt->quiz->title;
                        $courseId = $latestAttempt->quiz->course_id;
                        $firstLessonId = $courseId
                            ? \App\Models\Lesson::where('course_id', $courseId)->orderBy('order')->value('id')
                            : null;

                        if ($scoreNum < 50) {
                            $type = 'remedial';
                            $reason = "Score {$scoreNum}% in {$quizTitle}: Remedial review recommended.";
                        } elseif ($scoreNum < 80) {
                            $type = 'review';
                            $reason = "Score {$scoreNum}% in {$quizTitle}: Reinforcement drill recommended.";
                        } else {
                            $type = 'next_module';
                            $reason = "Quiz score {$scoreNum}% on {$quizTitle}: Ready to advance to Next Module.";
                        }

                        AiRecommendation::create([
                            'user_id'      => $student->id,
                            'lesson_id'    => $firstLessonId,
                            'type'         => $type,
                            'reason'       => $reason,
                            'is_dismissed' => false,
                        ]);
                    }

                    $targetEnr = $activeEnrollments->first(fn ($e) => $e->course && $e->course->lessons->isNotEmpty())
                        ?? $allEnrollments->first(fn ($e) => $e->course && $e->course->lessons->isNotEmpty());

                    if ($targetEnr && $targetEnr->course) {
                        $course = $targetEnr->course;
                        $lesson = $course->lessons->sortBy('order')->first();
                        AiRecommendation::firstOrCreate(
                            [
                                'user_id'      => $student->id,
                                'lesson_id'    => $lesson?->id,
                                'type'         => 'resume',
                                'is_dismissed' => false,
                            ],
                            [
                                'reason' => "Continue {$course->title}: Next recommended lesson is " . ($lesson?->title ?? 'Course Curriculum') . '.',
                            ]
                        );
                    }
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_goals');
    }
};
