<?php

namespace Tests\Feature\Student;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Faculty;
use App\Models\Department;
use App\Models\Major;
use App\Models\Simulation;
use App\Models\SimulationAttempt;
use App\Models\SimulationChoice;
use App\Models\SimulationNode;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimulationTest extends TestCase
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

    private function createSimulationScenario(User $teacher): Simulation
    {
        $faculty = Faculty::create(['code' => 'F-T', 'name' => 'Tourism', 'is_active' => true]);
        $dept = Department::create(['faculty_id' => $faculty->id, 'code' => 'D-T', 'name' => 'Hospitality', 'is_active' => true]);
        $major = Major::create(['department_id' => $dept->id, 'code' => 'M-T', 'name' => 'Tourism', 'is_active' => true]);

        $course = Course::create([
            'major_id' => $major->id,
            'teacher_id' => $teacher->id,
            'code' => 'TOUR-101',
            'title' => 'Tourism 101',
            'level' => 'beginner',
            'status' => 'published',
            'price' => 0,
            'is_paid' => false,
        ]);

        $sim = Simulation::create([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'title' => 'Guest Complaint',
            'title_kh' => 'ដោះស្រាយបណ្តឹងភ្ញៀវ',
            'skill' => 'tm',
            'kind' => 'scenario',
            'instructions' => 'Test scenario instructions',
            'estimated_minutes' => 10,
            'is_graded' => true,
            'pass_score' => 50,
            'max_attempts' => 2,
            'is_published' => true,
        ]);

        $node1 = SimulationNode::create([
            'simulation_id' => $sim->id,
            'title' => 'Step 1',
            'body' => 'Customer is shouting.',
            'is_end' => false,
        ]);

        $node2 = SimulationNode::create([
            'simulation_id' => $sim->id,
            'title' => 'Step 2 (End)',
            'body' => 'Resolution reached.',
            'is_end' => true,
        ]);

        $sim->update(['start_node_id' => $node1->id]);

        SimulationChoice::create([
            'node_id' => $node1->id,
            'next_node_id' => $node2->id,
            'label' => 'Listen and apologize calmly',
            'points' => 50,
            'is_best' => true,
            'feedback' => 'Great choice!',
        ]);

        SimulationChoice::create([
            'node_id' => $node1->id,
            'next_node_id' => $node2->id,
            'label' => 'Blame coworker',
            'points' => 10,
            'is_best' => false,
            'feedback' => 'Poor choice.',
        ]);

        return $sim;
    }

    public function test_guest_cannot_access_simulations_redirects_to_login(): void
    {
        $response = $this->get('/student/simulations');
        $response->assertRedirect('/login');
    }

    public function test_student_can_view_simulations_index(): void
    {
        $student = $this->makeUser('student');
        $teacher = $this->makeUser('teacher');
        $this->createSimulationScenario($teacher);

        $response = $this->actingAs($student)->get('/student/simulations');
        $response->assertStatus(200);
    }

    public function test_unenrolled_student_cannot_start_simulation_403(): void
    {
        $student = $this->makeUser('student');
        $teacher = $this->makeUser('teacher');
        $sim = $this->createSimulationScenario($teacher);

        $response = $this->actingAs($student)->post("/student/simulations/{$sim->id}/start");
        $response->assertStatus(403);
    }

    public function test_enrolled_student_can_start_and_play_simulation_without_leaking_points(): void
    {
        $student = $this->makeUser('student');
        $teacher = $this->makeUser('teacher');
        $sim = $this->createSimulationScenario($teacher);

        Enrollment::create([
            'student_id' => $student->id,
            'course_id' => $sim->course_id,
            'status' => 'active',
            'enrolled_at' => now(),
        ]);

        // Start attempt
        $startRes = $this->actingAs($student)->post("/student/simulations/{$sim->id}/start");
        $startRes->assertRedirect();

        $attempt = SimulationAttempt::where('simulation_id', $sim->id)->where('user_id', $student->id)->firstOrFail();
        $this->assertSame(1, $attempt->attempt_no);
        $this->assertSame('in_progress', $attempt->status);

        // Access play screen
        $playRes = $this->actingAs($student)->get("/student/simulations/{$sim->id}/play/{$attempt->id}");
        $playRes->assertStatus(200);

        // Verify choices in node do NOT leak points or is_best
        $pageProps = $playRes->original->getData()['page']['props'];
        $choices = $pageProps['node']['choices'];
        $this->assertCount(2, $choices);
        $this->assertArrayNotHasKey('points', $choices[0]);
        $this->assertArrayNotHasKey('is_best', $choices[0]);

        // Choose best choice
        $bestChoice = SimulationChoice::where('node_id', $attempt->current_node_id)->where('is_best', true)->firstOrFail();
        $chooseRes = $this->actingAs($student)->postJson("/student/simulation-attempts/{$attempt->id}/choose", [
            'choice_id' => $bestChoice->id,
        ]);

        $chooseRes->assertStatus(200);
        $chooseRes->assertJsonPath('points_awarded', 50);
        $chooseRes->assertJsonPath('done', true);

        // Double submit protection
        $doubleRes = $this->actingAs($student)->postJson("/student/simulation-attempts/{$attempt->id}/choose", [
            'choice_id' => $bestChoice->id,
        ]);
        // Since attempt is now completed, subsequent requests return 409
        $doubleRes->assertStatus(409);

        // Check attempt is completed
        $attempt->refresh();
        $this->assertSame('completed', $attempt->status);
        $this->assertSame(50, $attempt->score);

        // Check summary
        $summaryRes = $this->actingAs($student)->get("/student/simulation-attempts/{$attempt->id}/summary");
        $summaryRes->assertStatus(200);
        $summaryProps = $summaryRes->original->getData()['page']['props'];
        $this->assertTrue($summaryProps['attempt']['passed']);
        $this->assertSame(50, $summaryProps['attempt']['score']);
    }

    public function test_user_cannot_access_other_users_attempt_403(): void
    {
        $studentA = $this->makeUser('student');
        $studentB = $this->makeUser('student');
        $teacher = $this->makeUser('teacher');
        $sim = $this->createSimulationScenario($teacher);

        Enrollment::create(['student_id' => $studentA->id, 'course_id' => $sim->course_id, 'status' => 'active', 'enrolled_at' => now()]);
        Enrollment::create(['student_id' => $studentB->id, 'course_id' => $sim->course_id, 'status' => 'active', 'enrolled_at' => now()]);

        $attemptA = SimulationAttempt::create([
            'simulation_id' => $sim->id,
            'user_id' => $studentA->id,
            'attempt_no' => 1,
            'current_node_id' => $sim->start_node_id,
            'score' => 0,
            'status' => 'in_progress',
        ]);

        // Student B tries to play or choose on Student A's attempt -> 403
        $this->actingAs($studentB)->get("/student/simulations/{$sim->id}/play/{$attemptA->id}")->assertStatus(403);
        $this->actingAs($studentB)->postJson("/student/simulation-attempts/{$attemptA->id}/choose", ['choice_id' => 1])->assertStatus(403);
        $this->actingAs($studentB)->get("/student/simulation-attempts/{$attemptA->id}/summary")->assertStatus(403);
    }
}
