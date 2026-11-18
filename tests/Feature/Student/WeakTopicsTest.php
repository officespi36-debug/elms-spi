<?php

namespace Tests\Feature\Student;

use App\Models\Course;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Faculty;
use App\Models\Lesson;
use App\Models\Major;
use App\Models\Module;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizAttemptAnswer;
use App\Models\StudyPlan;
use App\Models\StudyPlanItem;
use App\Models\Topic;
use App\Models\TopicMastery;
use App\Models\User;
use App\Services\WeakTopicService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeakTopicsTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'student'): User
    {
        /** @var User $user */
        $user = User::factory()->createOne(['role' => $role]);
        return $user;
    }

    public function test_student_without_attempts_gets_empty_state_and_zero_fake_topics(): void
    {
        $student = $this->makeUser('student');

        $response = $this->actingAs($student)->get('/student/ai-path/weak-topics');
        $response->assertStatus(200);

        $content = $response->getContent();
        // Zero fake hardcoded topics or fake scores
        $this->assertStringNotContainsString('JavaScript Function Parameters', $content);
        $this->assertStringNotContainsString('SQL JOIN Operations', $content);
        $this->assertStringNotContainsString('47%', $content);
    }

    public function test_weak_topic_service_calculates_score_priority_and_single_source_of_truth(): void
    {
        $teacher = $this->makeUser('teacher');
        $student = $this->makeUser('student');

        $fac = Faculty::create(['code' => 'F1', 'name' => 'IT Fac', 'is_active' => true]);
        $dept = Department::create(['faculty_id' => $fac->id, 'code' => 'D1', 'name' => 'IT Dept', 'is_active' => true]);
        $major = Major::create(['department_id' => $dept->id, 'code' => 'M1', 'name' => 'IT Major', 'is_active' => true]);

        $course = Course::create([
            'code'       => 'CRS-101',
            'title'      => 'PHP & Laravel',
            'teacher_id' => $teacher->id,
            'major_id'   => $major->id,
            'status'     => 'published',
            'level'      => 'beginner',
            'price'      => 0,
            'is_paid'    => false,
        ]);

        $module = Module::create(['course_id' => $course->id, 'title' => 'Module 1', 'order' => 1]);
        $lesson = Lesson::create([
            'course_id'        => $course->id,
            'module_id'        => $module->id,
            'title'            => 'Routing Basics',
            'type'             => 'video',
            'duration_seconds' => 600,
            'order'            => 1,
        ]);

        $topic = Topic::create([
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'title'     => 'HTTP Routing',
            'title_kh'  => 'មូលដ្ឋាន Routing',
            'position'  => 1,
        ]);

        $quiz = Quiz::create([
            'course_id'     => $course->id,
            'module_id'     => $module->id,
            'title'         => 'Routing Quiz',
            'type'          => 'practice',
            'passing_score' => 70,
            'status'        => 'published',
        ]);

        $questions = [];
        for ($i = 1; $i <= 5; $i++) {
            $questions[] = Question::create([
                'quiz_id'        => $quiz->id,
                'topic_id'       => $topic->id,
                'type'           => 'mcq',
                'question'       => "Question $i",
                'options'        => ['A', 'B'],
                'correct_answer' => ['A'],
                'points'         => 1,
            ]);
        }

        Enrollment::create(['student_id' => $student->id, 'course_id' => $course->id, 'status' => 'active', 'enrolled_at' => now()]);

        // Attempt with 1 correct out of 5 -> 20% score (< 40 -> high priority)
        $attempt = QuizAttempt::create([
            'user_id'        => $student->id,
            'quiz_id'        => $quiz->id,
            'answers'        => [],
            'score'          => 20.0,
            'passed'         => false,
            'attempt_number' => 1,
            'started_at'     => now(),
            'submitted_at'   => now(),
        ]);

        foreach ($questions as $idx => $q) {
            QuizAttemptAnswer::create([
                'attempt_id'      => $attempt->id,
                'question_id'     => $q->id,
                'selected_answer' => $idx === 0 ? ['A'] : ['B'],
                'is_correct'      => $idx === 0,
                'points_earned'   => $idx === 0 ? 1 : 0,
                'answered_at'     => now(),
            ]);
        }

        $service = app(WeakTopicService::class);
        $payload = $service->getWeakTopicsPayload($student);

        $this->assertTrue($payload['hasData']);
        $this->assertSame(1, $payload['summary']['totalTopics']);
        $this->assertSame(20, $payload['summary']['avgScore']);
        $this->assertSame('high', $payload['topics'][0]['priority']);
        // High priority = 45 mins (from config)
        $this->assertSame(45, $payload['summary']['recommendedMinutes']);
        $this->assertSame(45, $payload['focusThisWeek']['totalMinutes']);
        $this->assertSame(45, $payload['focusThisWeek']['high']['minutes']);
    }

    public function test_practice_quiz_returns_real_questions_from_question_bank(): void
    {
        $teacher = $this->makeUser('teacher');
        $student = $this->makeUser('student');

        $fac = Faculty::create(['code' => 'F2', 'name' => 'Fac', 'is_active' => true]);
        $dept = Department::create(['faculty_id' => $fac->id, 'code' => 'D2', 'name' => 'Dept', 'is_active' => true]);
        $major = Major::create(['department_id' => $dept->id, 'code' => 'M2', 'name' => 'Major', 'is_active' => true]);

        $course = Course::create(['code' => 'CRS-202', 'title' => 'JS', 'teacher_id' => $teacher->id, 'major_id' => $major->id, 'status' => 'published', 'level' => 'beginner', 'price' => 0, 'is_paid' => false]);
        $topic = Topic::create(['course_id' => $course->id, 'title' => 'Arrays', 'position' => 1]);
        $quiz = Quiz::create(['course_id' => $course->id, 'title' => 'JS Quiz', 'type' => 'practice', 'passing_score' => 60, 'status' => 'published']);

        Question::create(['quiz_id' => $quiz->id, 'topic_id' => $topic->id, 'type' => 'mcq', 'question' => 'What is slice?', 'options' => ['A', 'B'], 'points' => 1]);

        $res = $this->actingAs($student)->postJson("/student/ai-path/weak-topics/{$topic->id}/practice-quiz");
        $res->assertStatus(200)
            ->assertJsonPath('ok', true)
            ->assertJsonPath('questions.0.question', 'What is slice?');
    }
}
