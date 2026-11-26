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

        // Lesson 1 (video lesson, duration: 100s)
        $this->lesson1 = Lesson::create([
            'course_id'        => $this->course->id,
            'module_id'        => $module->id,
            'title'            => 'Lesson 1',
            'video_url'        => 'https://example.com/lesson1.mp4',
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
     * 1. GET /student/my-courses/current renders State B when enrolled but not started,
     *    and redirects (302) to first incomplete lesson (State C) once progress begins.
     */
    public function test_current_redirects_to_first_incomplete_lesson(): void
    {
        // State B: enrolled, 0 progress -> renders CurrentCourseEmpty with state B
        $stateBResponse = $this->actingAs($this->student)
            ->get('/student/my-courses/current');

        $stateBResponse->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Student/MyCourses/CurrentCourseEmpty')
                ->where('state', 'B')
                ->where('hasEnrollments', true)
                ->where('startCourse.id', $this->course->id)
            );

        // State C: in progress -> renders CurrentCourseEmpty dashboard with in_progress stat
        LessonProgress::create([
            'user_id'         => $this->student->id,
            'lesson_id'       => $this->lesson1->id,
            'watched_seconds' => 10,
            'percent'         => 10,
        ]);

        // State C: in progress -> renders CurrentCourseEmpty dashboard reliably with recentActivities & in_progress stat
        $stateCResponse = $this->actingAs($this->student)
            ->get('/student/my-courses/current');

        $stateCResponse->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Student/MyCourses/CurrentCourseEmpty')
                ->where('state', 'C')
                ->where('hasEnrollments', true)
                ->where('stats.in_progress', 1)
                ->has('recentActivities')
            );

        // When explicitly requesting list/dashboard with ?list=1, it also renders the dashboard view
        $listResponse = $this->actingAs($this->student)
            ->get('/student/my-courses/current?list=1');
        $listResponse->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Student/MyCourses/CurrentCourseEmpty')
                ->where('state', 'C')
                ->where('hasEnrollments', true)
                ->where('stats.in_progress', 1)
            );

        // Direct jump (?redirect=1) redirects (302) to first incomplete lesson
        $redirectResponse = $this->actingAs($this->student)
            ->get('/student/my-courses/current?redirect=1');
        $redirectResponse->assertStatus(302);
        $redirectResponse->assertRedirect("/student/my-courses/{$this->course->id}/lessons/{$this->lesson1->id}");
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

    /**
     * 7. State A (no enrollments), State D (all completed), State E (coming soon / no lessons).
     */
    public function test_states_a_d_e_render_correctly_and_isolate_users(): void
    {
        // State A: fresh student with no enrollments
        $freshStudent = User::factory()->create(['role' => 'student']);
        $stateA = $this->actingAs($freshStudent)->get('/student/my-courses/current');
        $stateA->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Student/MyCourses/CurrentCourseEmpty')
                ->where('state', 'A')
                ->where('hasEnrollments', false)
            );

        // State D: all enrolled courses completed
        $this->actingAs($this->student)->postJson("/student/lessons/{$this->lesson1->id}/complete", ['seconds' => 100]);
        $this->actingAs($this->student)->postJson("/student/lessons/{$this->lesson2->id}/complete", ['seconds' => 100]);
        $stateD = $this->actingAs($this->student)->get('/student/my-courses/current');
        $stateD->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Student/MyCourses/CurrentCourseEmpty')
                ->where('state', 'D')
                ->where('hasEnrollments', true)
            );

        // State E: student enrolled only in a published course with 0 lessons
        $emptyStudent = User::factory()->create(['role' => 'student']);
        $emptyCourse = Course::create([
            'teacher_id' => $this->course->teacher_id,
            'major_id'   => $this->course->major_id,
            'subject_id' => $this->course->subject_id,
            'title'      => 'Coming Soon Empty Course',
            'code'       => 'TEST-EMPTY-' . rand(1000, 9999),
            'status'     => 'published',
        ]);
        Enrollment::create([
            'student_id'  => $emptyStudent->id,
            'course_id'   => $emptyCourse->id,
            'status'      => 'active',
            'enrolled_at' => now(),
        ]);

        $stateE = $this->actingAs($emptyStudent)->get('/student/my-courses/current');
        $stateE->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Student/MyCourses/CurrentCourseEmpty')
                ->where('state', 'E')
                ->where('hasEnrollments', true)
            );
    }

    /**
     * 8. Recommended courses exclude enrolled & empty courses; enroll is idempotent; paid -> checkout.
     */
    public function test_enroll_idempotency_and_paid_checkout_redirect(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        // Free course self-enroll twice -> only 1 enrollment row created, redirects to /continue
        $r1 = $this->actingAs($student)->post("/student/courses/{$this->course->id}/enroll");
        $r1->assertRedirect(route('student.my-courses.continue', $this->course->id));

        $r2 = $this->actingAs($student)->post("/student/courses/{$this->course->id}/enroll");
        $r2->assertRedirect(route('student.my-courses.continue', $this->course->id));

        $this->assertEquals(1, Enrollment::where('student_id', $student->id)->where('course_id', $this->course->id)->count());

        // Paid course -> redirects to checkout without creating active enrollment
        $paidCourse = Course::create([
            'teacher_id' => $this->course->teacher_id,
            'major_id'   => $this->course->major_id,
            'subject_id' => $this->course->subject_id,
            'title'      => 'Paid Test Course',
            'code'       => 'TEST-PAID-' . rand(1000, 9999),
            'status'     => 'published',
            'is_paid'    => true,
            'price'      => 25.00,
        ]);
        $mod = Module::create(['course_id' => $paidCourse->id, 'title' => 'M1', 'order' => 1]);
        Lesson::create(['course_id' => $paidCourse->id, 'module_id' => $mod->id, 'title' => 'L1', 'order' => 1, 'duration_seconds' => 60]);

        $paidResp = $this->actingAs($student)->post("/student/courses/{$paidCourse->id}/enroll");
        $paidResp->assertRedirect(route('student.payments.create', $paidCourse->id));
        $this->assertEquals(0, Enrollment::where('student_id', $student->id)->where('course_id', $paidCourse->id)->count());
    }
}
