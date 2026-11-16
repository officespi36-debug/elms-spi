<?php

namespace Tests\Feature\Student;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Major;
use App\Models\Module;
use App\Models\User;
use App\Services\CourseProgressService;
use Tests\TestCase;

class EnrolledCoursesTest extends TestCase
{
    public function test_unauthenticated_user_cannot_access_enrolled_courses(): void
    {
        $response = $this->get('/student/my-courses/enrolled');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_student_can_view_enrolled_courses(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $teacher = User::factory()->create(['role' => 'teacher', 'name' => 'Dr. Mengly Chea']);
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'status' => 'published']);

        Enrollment::create([
            'student_id'  => $student->id,
            'course_id'   => $course->id,
            'status'      => 'active',
            'enrolled_at' => now(),
            'source'      => 'self',
            'seen_at'     => now(),
        ]);

        $response = $this->actingAs($student)->get('/student/my-courses/enrolled');
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Student/MyCourses/EnrolledCourses')
            ->has('enrollments')
            ->has('counts')
            ->has('categories')
            ->has('filters')
        );
    }

    public function test_course_with_zero_progress_has_not_started_state(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $teacher = User::factory()->create(['role' => 'teacher', 'name' => 'Dr. Sokha Meas']);
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'status' => 'published']);
        $lesson = Lesson::factory()->create(['course_id' => $course->id]);

        $enrollment = Enrollment::create([
            'student_id'  => $student->id,
            'course_id'   => $course->id,
            'status'      => 'active',
            'enrolled_at' => now(),
            'source'      => 'admin',
            'seen_at'     => null,
        ]);

        $progressService = app(CourseProgressService::class);
        $result = $progressService->calculate($student, $course);

        $this->assertEquals(0, $result['percent']);
        $this->assertEquals('not_started', $result['state']);
        $this->assertEquals('Not Started', $result['state_label']);
    }

    public function test_course_with_watched_or_done_lesson_has_in_progress_state(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $teacher = User::factory()->create(['role' => 'teacher', 'name' => 'Dr. Sokha Meas']);
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'status' => 'published']);
        $lesson1 = Lesson::factory()->create(['course_id' => $course->id]);
        $lesson2 = Lesson::factory()->create(['course_id' => $course->id]);

        LessonProgress::create([
            'user_id'         => $student->id,
            'lesson_id'       => $lesson1->id,
            'percent'         => 95,
            'seconds_watched' => 300,
            'completed_at'    => now(),
        ]);

        $progressService = app(CourseProgressService::class);
        $result = $progressService->calculate($student, $course);

        $this->assertEquals(50, $result['percent']);
        $this->assertEquals('in_progress', $result['state']);
        $this->assertEquals('In Progress', $result['state_label']);
    }

    public function test_student_can_update_courses_view_preference(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        $response = $this->actingAs($student)->patchJson('/student/preferences', [
            'courses_view' => 'list',
        ]);

        $response->assertStatus(200);
        $student->refresh();
        $this->assertEquals('list', $student->preferences['courses_view']);
    }

    public function test_teacher_name_cleaning_prevents_pitaja_username(): void
    {
        $teacher = User::factory()->create([
            'role'  => 'teacher',
            'name'  => 'Pitaja6820',
            'email' => 'pitaja6820@ehwit.com',
        ]);
        $course = Course::factory()->create(['teacher_id' => 21]);

        $progressService = app(CourseProgressService::class);
        $cleanName = $progressService->cleanTeacherName($teacher, $course);

        $this->assertEquals('Dr. Mengly Chea', $cleanName);
    }
}
