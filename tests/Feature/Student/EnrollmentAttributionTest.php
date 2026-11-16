<?php

namespace Tests\Feature\Student;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Notification;
use App\Models\User;
use App\Services\EnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentAttributionTest extends TestCase
{
    public function test_teacher_can_enroll_student_in_own_course(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create(['teacher_id' => $teacher->id]);

        $service = app(EnrollmentService::class);
        $enrollment = $service->enroll($student, $course, 'teacher', $teacher);

        $this->assertEquals('active', $enrollment->status);
        $this->assertEquals('teacher', $enrollment->source);
        $this->assertEquals($teacher->id, $enrollment->enrolled_by);
        $this->assertNull($enrollment->seen_at);

        $this->assertDatabaseHas('notifications', [
            'target' => "user:{$student->id}",
        ]);
    }

    public function test_teacher_cannot_enroll_student_in_another_teacher_course(): void
    {
        $teacher1 = User::factory()->create(['role' => 'teacher']);
        $teacher2 = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create(['teacher_id' => $teacher1->id]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $service = app(EnrollmentService::class);
        $service->enroll($student, $course, 'teacher', $teacher2);
    }

    public function test_student_self_enrollment_sets_self_source_and_seen_at(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'is_paid' => false]);

        $service = app(EnrollmentService::class);
        $enrollment = $service->enroll($student, $course, 'self', $student);

        $this->assertEquals('active', $enrollment->status);
        $this->assertEquals('self', $enrollment->source);
        $this->assertEquals($student->id, $enrollment->enrolled_by);
        $this->assertNotNull($enrollment->seen_at);

        // Self enrollment does not trigger a notification
        $this->assertDatabaseMissing('notifications', [
            'target' => "user:{$student->id}",
            'title' => 'វគ្គសិក្សាថ្មីត្រូវបានបញ្ចូល (New Course Added)',
        ]);
    }

    public function test_enrollment_is_idempotent(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);
        $student = User::factory()->create(['role' => 'student']);
        $course = Course::factory()->create(['teacher_id' => $teacher->id]);

        $service = app(EnrollmentService::class);
        $first = $service->enroll($student, $course, 'teacher', $teacher);
        $second = $service->enroll($student, $course, 'teacher', $teacher);

        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(1, Enrollment::where('student_id', $student->id)->where('course_id', $course->id)->count());
    }
}
