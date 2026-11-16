<?php

namespace Tests\Feature\Student;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\QuizAttempt;
use App\Models\StudyDaily;
use App\Models\User;
use App\Services\CourseProgressService;
use App\Services\DashboardAnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDashboardAndEnrolledParityTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_progress_service_states_and_percentages(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $teacher = User::factory()->create(['role' => 'teacher']);

        $courseEmpty = Course::create(['title' => 'Empty Course', 'teacher_id' => $teacher->id, 'status' => 'published']);
        $courseOne = Course::create(['title' => 'One Lesson', 'teacher_id' => $teacher->id, 'status' => 'published']);
        $courseTwo = Course::create(['title' => 'Two Lessons', 'teacher_id' => $teacher->id, 'status' => 'published']);

        $l1 = Lesson::create(['course_id' => $courseOne->id, 'title' => 'L1', 'order' => 1, 'duration_seconds' => 600]);
        $l2a = Lesson::create(['course_id' => $courseTwo->id, 'title' => 'L2A', 'order' => 1, 'duration_seconds' => 600]);
        $l2b = Lesson::create(['course_id' => $courseTwo->id, 'title' => 'L2B', 'order' => 2, 'duration_seconds' => 600]);

        $svc = app(CourseProgressService::class);
        $this->assertSame('coming_soon', $svc->calculate($student, $courseEmpty)['state']);
        $this->assertSame('not_started', $svc->calculate($student, $courseTwo)['state']);

        LessonProgress::create(['user_id' => $student->id, 'lesson_id' => $l1->id, 'percent' => 100, 'seconds_watched' => 600, 'completed_at' => now()]);
        $resOne = $svc->calculate($student, $courseOne);
        $this->assertSame('completed', $resOne['state']);
        $this->assertSame(100, $resOne['percent']);

        LessonProgress::create(['user_id' => $student->id, 'lesson_id' => $l2a->id, 'percent' => 100, 'seconds_watched' => 600, 'completed_at' => now()]);
        $resTwo = $svc->calculate($student, $courseTwo);
        $this->assertSame('in_progress', $resTwo['state']);
        $this->assertSame(50, $resTwo['percent']);
    }

    public function test_study_daily_clamp_and_analytics_parity(): void
    {
        $studentA = User::factory()->create(['role' => 'student']);
        $studentB = User::factory()->create(['role' => 'student']);
        $analytics = app(DashboardAnalyticsService::class);

        $analytics->recordDailyDelta($studentA->id, 'lesson', 120);
        $today = Carbon::now(DashboardAnalyticsService::TIMEZONE)->toDateString();
        $row = StudyDaily::where('user_id', $studentA->id)->whereDate('study_date', $today)->first();
        $this->assertNotNull($row);
        $this->assertSame(40, $row->seconds);

        $win7 = $analytics->getWindowAnalytics($studentA, 7);
        $seriesSum = collect($win7['series'])->sum('seconds');
        $this->assertSame($win7['totalSeconds'], $seriesSum);
        $this->assertNull($win7['trend']);
        $this->assertFalse($analytics->getQuizPerformance($studentA)['hasData']);
        $this->assertSame(0, $analytics->getWindowAnalytics($studentB, 7)['totalSeconds']);
    }
}
