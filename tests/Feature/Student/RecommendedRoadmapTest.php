<?php

namespace Tests\Feature\Student;

use App\Models\Course;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Faculty;
use App\Models\LearningPath;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Major;
use App\Models\Module;
use App\Models\PathStage;
use App\Models\PathStageCourse;
use App\Models\Skill;
use App\Models\User;
use App\Services\PathProgressService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RecommendedRoadmapTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'student'): User
    {
        /** @var User $user */
        $user = User::factory()->createOne(['role' => $role]);

        return $user;
    }

    public function test_student_without_goal_gets_clean_empty_state_with_no_fake_data(): void
    {
        $student = $this->makeUser('student');

        $response = $this->actingAs($student)->get('/student/ai-path/next-course');
        $response->assertStatus(200);

        $content = $response->getContent();
        $this->assertStringNotContainsString('Become a Full Stack Web Developer', $content);
        $this->assertStringNotContainsString('58%', $content);
    }

    public function test_goal_validation_and_persistence_and_progress_math(): void
    {
        config(['services.gemini.api_key' => 'test-gemini-key']);
        Http::fake(['*' => Http::response(['error' => 'down'], 500)]);

        $teacher = $this->makeUser('teacher');
        $studentA = $this->makeUser('student');
        $studentB = $this->makeUser('student');

        $fac = Faculty::create(['code' => 'F1', 'name' => 'Computing', 'is_active' => true]);
        $dept = Department::create(['faculty_id' => $fac->id, 'code' => 'D1', 'name' => 'IT', 'is_active' => true]);
        $major = Major::create(['department_id' => $dept->id, 'code' => 'M1', 'name' => 'IT', 'is_active' => true]);

        $c1 = Course::create(['code' => 'C1', 'title' => 'Course One', 'teacher_id' => $teacher->id, 'major_id' => $major->id, 'status' => 'published', 'level' => 'beginner', 'price' => 0, 'is_paid' => false]);
        $c2 = Course::create(['code' => 'C2', 'title' => 'Course Two', 'teacher_id' => $teacher->id, 'major_id' => $major->id, 'status' => 'published', 'level' => 'intermediate', 'price' => 0, 'is_paid' => false]);
        $cDraft = Course::create(['code' => 'C3', 'title' => 'Draft Course', 'teacher_id' => $teacher->id, 'major_id' => $major->id, 'status' => 'draft', 'level' => 'beginner', 'price' => 0, 'is_paid' => false]);

        $mod1 = Module::create(['course_id' => $c1->id, 'title' => 'M1', 'order' => 1]);
        $c1Lessons = [];
        for ($i = 1; $i <= 10; $i++) {
            $c1Lessons[] = Lesson::create(['course_id' => $c1->id, 'module_id' => $mod1->id, 'title' => "L{$i}", 'type' => 'video', 'duration_seconds' => 600, 'order' => $i]);
        }
        $mod2 = Module::create(['course_id' => $c2->id, 'title' => 'M2', 'order' => 1]);
        $c2Lessons = [];
        for ($i = 1; $i <= 20; $i++) {
            $c2Lessons[] = Lesson::create(['course_id' => $c2->id, 'module_id' => $mod2->id, 'title' => "K{$i}", 'type' => 'video', 'duration_seconds' => 600, 'order' => $i]);
        }

        $sk = Skill::create(['name' => 'SQL', 'name_kh' => 'SQL']);
        $c1->skills()->attach($sk->id);

        $path = LearningPath::create(['slug' => 'it-track', 'major_id' => $major->id, 'title' => 'IT Track', 'target_months' => 6, 'is_published' => true]);
        $unpublishedPath = LearningPath::create(['slug' => 'draft-track', 'title' => 'Draft', 'target_months' => 6, 'is_published' => false]);

        $st1 = PathStage::create(['path_id' => $path->id, 'position' => 1, 'title' => 'Stage 1']);
        PathStageCourse::create(['stage_id' => $st1->id, 'course_id' => $c1->id, 'position' => 1]);
        PathStageCourse::create(['stage_id' => $st1->id, 'course_id' => $c2->id, 'position' => 2]);
        PathStageCourse::create(['stage_id' => $st1->id, 'course_id' => $cDraft->id, 'position' => 3]);

        // Validation 422 on bad goal inputs
        $this->actingAs($studentA)
            ->putJson('/student/ai-path/goal', ['path_id' => $unpublishedPath->id, 'target_months' => 0, 'weekly_hours' => 99])
            ->assertStatus(422);

        // Save valid goal for Student A
        $this->actingAs($studentA)
            ->putJson('/student/ai-path/goal', ['path_id' => $path->id, 'target_months' => 6, 'weekly_hours' => 12])
            ->assertStatus(200)
            ->assertJsonPath('roadmap.hasGoal', true)
            ->assertJsonPath('roadmap.counts.total', 2);

        // User B still has no goal
        $payloadB = app(PathProgressService::class)->getRoadmapPayload($studentB);
        $this->assertFalse($payloadB['hasGoal']);

        // Enroll Student A: complete all 10 lessons of C1 + 2 of 20 lessons of C2 => floor(12 / 30 * 100) = 40%
        Enrollment::create(['student_id' => $studentA->id, 'course_id' => $c1->id, 'status' => 'active', 'enrolled_at' => now()]);
        Enrollment::create(['student_id' => $studentA->id, 'course_id' => $c2->id, 'status' => 'active', 'enrolled_at' => now()]);
        foreach ($c1Lessons as $l) {
            LessonProgress::create(['user_id' => $studentA->id, 'lesson_id' => $l->id, 'percent' => 100, 'completed_at' => now()]);
        }
        LessonProgress::create(['user_id' => $studentA->id, 'lesson_id' => $c2Lessons[0]->id, 'percent' => 100, 'completed_at' => now()]);
        LessonProgress::create(['user_id' => $studentA->id, 'lesson_id' => $c2Lessons[1]->id, 'percent' => 100, 'completed_at' => now()]);

        PathProgressService::invalidateForUser($studentA->id);
        $payloadA = app(PathProgressService::class)->getRoadmapPayload($studentA);

        $this->assertSame(40, $payloadA['overallProgress']);
        $this->assertSame(40, $payloadA['stages'][0]['progress']);
        $this->assertSame(1, $payloadA['counts']['completed']);
        $this->assertSame(1, $payloadA['counts']['inProgress']);
        $this->assertSame(2, $payloadA['counts']['total']);
        $this->assertSame(false, $payloadA['recommendation']['isAiGenerated']);
        $this->assertCount(1, $payloadA['skills']);
    }
}
