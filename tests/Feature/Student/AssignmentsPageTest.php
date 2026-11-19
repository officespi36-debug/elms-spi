<?php

namespace Tests\Feature\Student;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Faculty;
use App\Models\Department;
use App\Models\Major;
use App\Models\Quiz;
use App\Models\User;
use App\Services\AssessmentService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssignmentsPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return User&Authenticatable
     */
    private function makeUser(string $role = 'student'): User
    {
        /** @var User&Authenticatable $user */
        $user = User::factory()->createOne(['role' => $role]);

        return $user;
    }

    public function test_assignments_page_loads_successfully_without_500_error(): void
    {
        $student = $this->makeUser('student');

        $response = $this->actingAs($student)->get('/student/quizzes/assignments');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Student/Quizzes/Assignments')
            ->has('analytics')
            ->has('filters')
        );
    }

    public function test_assessment_service_returns_data_with_published_courses(): void
    {
        $teacher = $this->makeUser('teacher');
        $student = $this->makeUser('student');

        $faculty = Faculty::create(['code' => 'F-1', 'name' => 'IT', 'is_active' => true]);
        $dept = Department::create(['faculty_id' => $faculty->id, 'code' => 'D-1', 'name' => 'Computer Science', 'is_active' => true]);
        $major = Major::create(['department_id' => $dept->id, 'code' => 'M-1', 'name' => 'Software Eng', 'is_active' => true]);

        $course = Course::create([
            'major_id'    => $major->id,
            'teacher_id'  => $teacher->id,
            'title'       => 'Full Stack Web Development',
            'code'        => 'CRS-IT-001',
            'credit'      => 3,
            'status'      => 'published',
            'is_approved' => true,
        ]);

        $quiz = Quiz::create([
            'course_id'          => $course->id,
            'title'              => 'Capstone Web Project',
            'type'               => 'assignment',
            'passing_score'      => 70,
            'time_limit_minutes' => null,
            'status'             => 'published',
        ]);

        $service = app(AssessmentService::class);
        $data = $service->getAssessmentsData($student);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('assessments', $data);
        $this->assertGreaterThanOrEqual(1, $data['total_count']);
        $this->assertEquals('Capstone Web Project', $data['assessments'][0]['title']);
    }
}
