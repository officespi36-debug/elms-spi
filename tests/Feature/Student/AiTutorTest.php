<?php

namespace Tests\Feature\Student;

use App\Models\AiConversation;
use App\Models\AiFeedback;
use App\Models\AiMessage;
use App\Models\Course;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Faculty;
use App\Models\Lesson;
use App\Models\Major;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AiTutorTest extends TestCase
{
    use RefreshDatabase;

    protected function beforeRefreshingDatabase(): void
    {
        Schema::disableForeignKeyConstraints();
    }

    protected function afterRefreshingDatabase(): void
    {
        Schema::enableForeignKeyConstraints();
    }

    private function createAcademicCourse(User $teacher, string $code = 'CRS-AI-101'): array
    {
        $fac = Faculty::create(['code' => 'FAC-' . uniqid(), 'name' => 'Computing', 'is_active' => true]);
        $dept = Department::create(['faculty_id' => $fac->id, 'code' => 'DEPT-' . uniqid(), 'name' => 'CS', 'is_active' => true]);
        $major = Major::create(['department_id' => $dept->id, 'code' => 'MJR-' . uniqid(), 'name' => 'IT', 'is_active' => true]);
        $course = Course::create([
            'code'       => $code,
            'title'      => 'Modern Web Development',
            'title_kh'   => 'ការអភិវឌ្ឍគេហទំព័រទំនើប',
            'teacher_id' => $teacher->id,
            'major_id'   => $major->id,
            'status'     => 'published',
            'level'      => 'beginner',
            'is_paid'    => false,
            'price'      => 0,
        ]);
        $module = Module::create(['course_id' => $course->id, 'title' => 'Module 1', 'order' => 1]);
        $lesson = Lesson::create([
            'course_id'        => $course->id,
            'module_id'        => $module->id,
            'title'            => 'HTTP & Routing Basics',
            'kh_title'         => 'មូលដ្ឋាន HTTP និង Routing',
            'type'             => 'video',
            'duration_seconds' => 600,
            'order'            => 1,
        ]);

        return [$course, $module, $lesson];
    }

    private function makeUser(string $role = 'student', ?string $email = null): User
    {
        return User::create([
            'name'              => ucfirst($role) . ' User',
            'name_kh'           => 'អ្នកប្រើប្រាស់សាកល្បង',
            'email'             => $email ?: $role . '_' . uniqid() . '@spilms.test',
            'password'          => Hash::make('Password123!'),
            'role'              => $role,
            'status'            => 'active',
            'is_active'         => true,
            'email_verified_at' => now(),
        ]);
    }

    public function test_send_persists_messages_and_title_and_survives_reload(): void
    {
        config()->set('services.gemini.api_key', 'fake-gemini-key');
        config()->set('services.gemini.model', 'gemini-2.5-flash');

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => "សួស្តី! នេះជាការពន្យល់:\n\n```js\nconsole.log('hi');\n```"]]],
                ]],
                'usageMetadata' => ['totalTokenCount' => 42],
            ], 200),
        ]);

        $teacher = $this->makeUser('teacher');
        $student = $this->makeUser('student');
        [$course] = $this->createAcademicCourse($teacher);
        Enrollment::create(['student_id' => $student->id, 'course_id' => $course->id, 'status' => 'active', 'enrolled_at' => now()]);

        $conv = AiConversation::create(['user_id' => $student->id, 'course_id' => $course->id, 'title' => 'New', 'last_message_at' => now()]);

        $res = $this->actingAs($student)->postJson("/student/ai/conversations/{$conv->id}/messages", [
            'message'   => 'តើអ្វីទៅជា REST API routing ក្នុង Laravel?',
            'course_id' => $course->id,
            'lang'      => 'km',
        ]);

        $res->assertStatus(200)->assertJsonPath('ok', true)->assertJsonPath('assistant_message.provider', 'gemini');
        $this->assertDatabaseHas('ai_messages', ['conversation_id' => $conv->id, 'role' => 'user']);
        $this->assertDatabaseHas('ai_messages', ['conversation_id' => $conv->id, 'role' => 'assistant']);
        $this->assertSame(mb_substr('តើអ្វីទៅជា REST API routing ក្នុង Laravel?', 0, 20, 'UTF-8'), $conv->fresh()->title);

        $reload = $this->actingAs($student)->get('/student/ai-tutor');
        $reload->assertStatus(200);
    }

    public function test_gemini_failure_falls_back_to_workers_ai_and_both_failing_returns_503(): void
    {
        config()->set('services.gemini.api_key', 'fake-gemini-key');
        config()->set('services.cloudflare.account_id', 'cf-acc');
        config()->set('services.cloudflare.ai_token', 'cf-tok');

        $student = $this->makeUser('student');
        $conv = AiConversation::create(['user_id' => $student->id, 'title' => 'Fallback', 'last_message_at' => now()]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'down'], 500),
            'gateway.ai.cloudflare.com/*'         => Http::response([
                'result' => ['choices' => [['message' => ['content' => 'Workers fallback answer']]]],
            ], 200),
        ]);

        $res = $this->actingAs($student)->postJson("/student/ai/conversations/{$conv->id}/messages", [
            'message' => 'Explain fallback mode',
        ]);
        $res->assertStatus(200)->assertJsonPath('assistant_message.provider', 'workers_ai');

        Http::fake([
            '*' => Http::response(['error' => 'all down'], 500),
        ]);

        $failRes = $this->actingAs($student)->postJson("/student/ai/conversations/{$conv->id}/messages", [
            'message' => 'Will this return 503?',
        ]);
        $failRes->assertStatus(503)->assertJsonPath('error_code', 'provider_unavailable');
    }

    public function test_ownership_policy_blocks_cross_user_access_and_unenrolled_course_403(): void
    {
        $teacher = $this->makeUser('teacher');
        [$course] = $this->createAcademicCourse($teacher);
        $userA = $this->makeUser('student');
        $userB = $this->makeUser('student');

        $convB = AiConversation::create(['user_id' => $userB->id, 'title' => 'Private', 'last_message_at' => now()]);

        $this->actingAs($userA)->getJson("/student/ai/conversations/{$convB->id}")->assertStatus(403);
        $this->actingAs($userA)->deleteJson("/student/ai/conversations/{$convB->id}")->assertStatus(403);

        $convA = AiConversation::create(['user_id' => $userA->id, 'title' => 'Mine', 'last_message_at' => now()]);
        $this->actingAs($userA)->postJson("/student/ai/conversations/{$convA->id}/messages", [
            'message'   => 'Can I peek at another course?',
            'course_id' => $course->id,
        ])->assertStatus(403);
    }

    public function test_no_enrollment_state_and_pii_scrubbing_and_xss_stripping_and_quota_and_feedback(): void
    {
        config()->set('services.gemini.api_key', 'fake-gemini-key');
        $student = $this->makeUser('student', 'secret.student@example.edu');
        $student->update(['name' => 'SecretStudentName']);

        Http::fake(function (HttpClientRequest $req) {
            $body = json_encode($req->data());
            $this->assertStringNotContainsString('secret.student@example.edu', $body);
            $this->assertStringNotContainsString('SecretStudentName', $body);
            $this->assertStringNotContainsString('012 345 678', $body);

            return Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => "Safe <script>alert(1)</script> [link](javascript:alert(2))"]]],
                ]],
            ], 200);
        });

        $pageRes = $this->actingAs($student)->get('/student/ai-tutor');
        $pageRes->assertStatus(200);
        $conv = AiConversation::create(['user_id' => $student->id, 'title' => 'No course', 'last_message_at' => now()]);

        // Invalid lengths -> 422
        $this->actingAs($student)->postJson("/student/ai/conversations/{$conv->id}/messages", ['message' => 'a'])->assertStatus(422);
        $this->actingAs($student)->postJson("/student/ai/conversations/{$conv->id}/messages", ['message' => str_repeat('x', 1001)])->assertStatus(422);

        // Quick action without enrollment -> 422
        $this->actingAs($student)->postJson("/student/ai/conversations/{$conv->id}/messages", ['quick_action' => 'explain_lesson'])->assertStatus(422);

        // Send message containing PII + verify XSS strip
        $sendRes = $this->actingAs($student)->postJson("/student/ai/conversations/{$conv->id}/messages", [
            'message' => 'Email me at secret.student@example.edu or 012 345 678 about arrays',
        ]);
        $sendRes->assertStatus(200);
        $html = (string) $sendRes->json('assistant_message.html');
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('javascript:', $html);

        // Unique feedback
        $msgId = (int) $sendRes->json('assistant_message.id');
        $this->actingAs($student)->postJson("/student/ai/messages/{$msgId}/feedback", ['rating' => 'up'])->assertStatus(200);
        $this->actingAs($student)->postJson("/student/ai/messages/{$msgId}/feedback", ['rating' => 'down'])->assertStatus(200);
        $this->assertSame(1, AiFeedback::where('message_id', $msgId)->where('user_id', $student->id)->count());

        // Quota exceeded (60 messages/day) -> 429
        for ($i = 0; $i < 60; $i++) {
            AiMessage::create(['conversation_id' => $conv->id, 'role' => 'user', 'body' => "q{$i}"]);
        }
        $this->actingAs($student)->postJson("/student/ai/conversations/{$conv->id}/messages", [
            'message' => 'Over daily quota test',
        ])->assertStatus(429);
    }

    public function test_all_four_ai_learning_sidebar_routes_return_200(): void
    {
        $student = $this->makeUser('student');
        foreach ([
            '/student/ai-tutor',
            '/student/ai-path/recommended',
            '/student/ai-path/next-course',
            '/student/ai-path/weak-topics',
        ] as $uri) {
            $this->actingAs($student)->get($uri)->assertStatus(200);
        }
    }
}
