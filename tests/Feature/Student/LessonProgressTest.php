<?php

namespace Tests\Feature\Student;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LessonProgressTest extends TestCase
{
    use DatabaseTransactions;

    protected User $studentA;
    protected User $studentB;
    protected Course $course;
    protected Lesson $lesson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->studentA = User::factory()->create([
            'role' => 'student',
        ]);

        $this->studentB = User::factory()->create([
            'role' => 'student',
        ]);

        $this->course = Course::first() ?: Course::create([
            'teacher_id' => $this->studentA->id,
            'title' => 'Test Course for Progress',
            'code' => 'CRS-TEST-001',
            'status' => 'published',
        ]);

        $this->lesson = Lesson::where('course_id', $this->course->id)->first() ?: Lesson::create([
            'course_id' => $this->course->id,
            'module_id' => 1,
            'title' => 'Test Lesson for Progress',
            'duration_seconds' => 600,
            'order' => 1,
        ]);

        // Enroll Student A
        Enrollment::firstOrCreate([
            'student_id' => $this->studentA->id,
            'course_id' => $this->course->id,
        ], [
            'status' => 'active',
            'enrolled_at' => now(),
        ]);
    }

    /**
     * 1. Progress ធម្មតា returns 200
     */
    public function test_normal_progress_returns_200(): void
    {
        $response = $this->actingAs($this->studentA)
            ->postJson("/student/lessons/{$this->lesson->id}/progress", [
                'seconds' => 120,
                'duration' => 600,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'ok' => true,
                'lesson_id' => $this->lesson->id,
                'seconds_watched' => 120,
                'percent' => 20,
            ]);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $this->studentA->id,
            'lesson_id' => $this->lesson->id,
            'seconds_watched' => 120,
            'percent' => 20,
        ]);
    }

    /**
     * 2. Position > duration clamps to duration and returns 200 (not 422)
     */
    public function test_position_greater_than_duration_clamps_and_returns_200(): void
    {
        $duration = $this->lesson->duration_seconds ?: 600;

        $response = $this->actingAs($this->studentA)
            ->postJson("/student/lessons/{$this->lesson->id}/progress", [
                'seconds' => 3600, // Exceeds duration
                'duration' => $duration,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'ok' => true,
                'lesson_id' => $this->lesson->id,
                'seconds_watched' => $duration,
                'percent' => 100,
            ]);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $this->studentA->id,
            'lesson_id' => $this->lesson->id,
            'seconds_watched' => $duration,
            'percent' => 100,
        ]);
    }

    /**
     * 3. Lesson completed returns 200 and preserves completed_at
     */
    public function test_lesson_completed_returns_200_and_preserves_completed_at(): void
    {
        $completedTime = now()->subDays(2);
        LessonProgress::updateOrCreate([
            'user_id' => $this->studentA->id,
            'lesson_id' => $this->lesson->id,
        ], [
            'seconds_watched' => 600,
            'percent' => 100,
            'completed_at' => $completedTime,
        ]);

        $response = $this->actingAs($this->studentA)
            ->postJson("/student/lessons/{$this->lesson->id}/progress", [
                'seconds' => 600,
                'duration' => 600,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'ok' => true,
                'is_completed' => true,
            ]);

        $record = LessonProgress::where('user_id', $this->studentA->id)
            ->where('lesson_id', $this->lesson->id)
            ->first();

        $this->assertNotNull($record->completed_at);
    }

    /**
     * 4. User not enrolled returns 403
     */
    public function test_user_not_enrolled_returns_403(): void
    {
        // Student B is NOT enrolled
        Enrollment::where('student_id', $this->studentB->id)
            ->where('course_id', $this->course->id)
            ->delete();

        $response = $this->actingAs($this->studentB)
            ->postJson("/student/lessons/{$this->lesson->id}/progress", [
                'seconds' => 50,
                'duration' => 600,
            ]);

        $response->assertStatus(403);
    }

    /**
     * 5. User A cannot modify User B's progress
     */
    public function test_user_a_cannot_modify_user_b_progress(): void
    {
        // Set User B progress to 300s
        LessonProgress::updateOrCreate([
            'user_id' => $this->studentB->id,
            'lesson_id' => $this->lesson->id,
        ], [
            'seconds_watched' => 300,
            'percent' => 50,
        ]);

        // Student A sends 150s
        $this->actingAs($this->studentA)
            ->postJson("/student/lessons/{$this->lesson->id}/progress", [
                'seconds' => 150,
                'duration' => 600,
            ]);

        // Verify Student B's progress is completely untouched
        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $this->studentB->id,
            'lesson_id' => $this->lesson->id,
            'seconds_watched' => 300,
            'percent' => 50,
        ]);
    }

    /**
     * 6. Calling progress twice does not duplicate records
     */
    public function test_calling_progress_twice_does_not_duplicate_records(): void
    {
        $this->actingAs($this->studentA)
            ->postJson("/student/lessons/{$this->lesson->id}/progress", [
                'seconds' => 100,
                'duration' => 600,
            ]);

        $this->actingAs($this->studentA)
            ->postJson("/student/lessons/{$this->lesson->id}/progress", [
                'seconds' => 200,
                'duration' => 600,
            ]);

        $count = LessonProgress::where('user_id', $this->studentA->id)
            ->where('lesson_id', $this->lesson->id)
            ->count();

        $this->assertEquals(1, $count);
    }
}
