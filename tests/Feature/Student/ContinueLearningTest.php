<?php

namespace Tests\Feature\Student;

use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContinueLearningTest extends TestCase
{
    use DatabaseTransactions;

    protected User $student;
    protected Course $course;
    protected Lesson $lesson1;
    protected Lesson $lesson2;
    protected Enrollment $enrollment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create([
            'role' => 'student',
        ]);

        $teacher = User::factory()->create([
            'role' => 'teacher',
        ]);

        $sampleCourse = Course::first();
        $majorId = $sampleCourse ? $sampleCourse->major_id : 1;
        $subjectId = $sampleCourse ? $sampleCourse->subject_id : 1;

        $this->course = Course::create([
            'teacher_id' => $teacher->id,
            'major_id'   => $majorId,
            'subject_id' => $subjectId,
            'title'      => 'Continue Learning Test Course',
            'code'       => 'TEST-CONTINUE-' . rand(1000, 9999),
            'status'     => 'published',
        ]);

        // Module
        $module = Module::create([
            'course_id' => $this->course->id,
            'title'     => 'Module 1',
            'order'     => 1,
        ]);

        // Lesson 1 (duration: 100s)
        $this->lesson1 = Lesson::create([
            'course_id'        => $this->course->id,
            'module_id'        => $module->id,
            'title'            => 'Lesson 1',
            'order'            => 1,
            'duration_seconds' => 100,
        ]);

        // Lesson 2 (duration: 100s)
        $this->lesson2 = Lesson::create([
            'course_id'        => $this->course->id,
            'module_id'        => $module->id,
            'title'            => 'Lesson 2',
            'order'            => 2,
            'duration_seconds' => 100,
        ]);

        // Enroll Student
        $this->enrollment = Enrollment::create([
            'student_id'  => $this->student->id,
            'course_id'   => $this->course->id,
            'status'      => 'active',
            'enrolled_at' => now(),
        ]);
    }

    /**
     * 1. GET /student/my-courses/current redirects (302) to first incomplete lesson.
     */
    public function test_current_redirects_to_first_incomplete_lesson(): void
    {
        $response = $this->actingAs($this->student)
            ->get('/student/my-courses/current');

        $response->assertStatus(302);
        $response->assertRedirect("/student/my-courses/{$this->course->id}/lessons/{$this->lesson1->id}");
    }

    /**
     * 2. GET /student/my-courses/{course}/continue redirects (302) to first incomplete lesson.
     */
    public function test_course_continue_redirects_to_first_incomplete_lesson(): void
    {
        $response = $this->actingAs($this->student)
            ->get("/student/my-courses/{$this->course->id}/continue");

        $response->assertStatus(302);
        $response->assertRedirect("/student/my-courses/{$this->course->id}/lessons/{$this->lesson1->id}");
    }

    /**
     * 3. Lock Enforcement: Accessing Lesson 2 directly when Lesson 1 is incomplete redirects to Lesson 1.
     */
    public function test_lock_enforcement_redirects_locked_lesson_to_first_incomplete(): void
    {
        $response = $this->actingAs($this->student)
            ->get("/student/my-courses/{$this->course->id}/lessons/{$this->lesson2->id}");

        $response->assertStatus(302);
        $response->assertRedirect("/student/my-courses/{$this->course->id}/lessons/{$this->lesson1->id}");
        $response->assertSessionHas('warning');
    }

    /**
     * 4. Complete Lesson validation: < 90% watched fails with 422.
     */
    public function test_complete_lesson_with_less_than_90_percent_returns_422(): void
    {
        // 50s watched out of 100s (< 90%)
        $response = $this->actingAs($this->student)
            ->postJson("/student/lessons/{$this->lesson1->id}/complete", [
                'seconds' => 50,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure(['error', 'watched_seconds', 'required_seconds']);
    }

    /**
     * 5. Complete Lesson with >= 90% succeeds and unlocks next lesson.
     */
    public function test_complete_lesson_with_90_percent_succeeds_and_unlocks_next(): void
    {
        // 95s watched out of 100s (>= 90%)
        $response = $this->actingAs($this->student)
            ->postJson("/student/lessons/{$this->lesson1->id}/complete", [
                'seconds' => 95,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'ok'                  => true,
                'is_completed'        => true,
                'is_course_completed' => false,
                'next_lesson_url'     => "/student/my-courses/{$this->course->id}/lessons/{$this->lesson2->id}",
            ]);

        // Now Lesson 2 can be accessed directly without redirect
        $accessResponse = $this->actingAs($this->student)
            ->get("/student/my-courses/{$this->course->id}/lessons/{$this->lesson2->id}");

        $accessResponse->assertStatus(200);
    }

    /**
     * 6. Completing the last lesson marks course completed.
     */
    public function test_completing_last_lesson_marks_course_completed(): void
    {
        // Complete Lesson 1
        $this->actingAs($this->student)
            ->postJson("/student/lessons/{$this->lesson1->id}/complete", ['seconds' => 100]);

        // Complete Lesson 2 (last lesson)
        $response = $this->actingAs($this->student)
            ->postJson("/student/lessons/{$this->lesson2->id}/complete", ['seconds' => 100]);

        $response->assertStatus(200)
            ->assertJson([
                'ok'                  => true,
                'is_completed'        => true,
                'is_course_completed' => true,
            ]);

        $this->assertDatabaseHas('enrollments', [
            'id'     => $this->enrollment->id,
            'status' => 'completed',
        ]);
    }
}
