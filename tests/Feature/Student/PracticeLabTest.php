<?php

namespace Tests\Feature\Student;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PracticeLabTest extends TestCase
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

    public function test_practice_lab_index_page_loads_successfully(): void
    {
        $student = $this->makeUser('student');

        $response = $this->actingAs($student)->get('/student/practice-lab');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Student/PracticeLab/Index'));
    }

    public function test_practice_lab_it_subpage_loads_successfully(): void
    {
        $student = $this->makeUser('student');

        $response = $this->actingAs($student)->get('/student/practice-lab/it');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Student/PracticeLab/ItLab'));
    }

    public function test_practice_lab_tourism_subpage_loads_successfully(): void
    {
        $student = $this->makeUser('student');

        $response = $this->actingAs($student)->get('/student/practice-lab/tourism');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Student/PracticeLab/TourismLab'));
    }

    public function test_practice_lab_english_subpage_loads_successfully(): void
    {
        $student = $this->makeUser('student');

        $response = $this->actingAs($student)->get('/student/practice-lab/english');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Student/PracticeLab/EnglishLab'));
    }

    public function test_practice_lab_agronomy_subpage_loads_successfully(): void
    {
        $student = $this->makeUser('student');

        $response = $this->actingAs($student)->get('/student/practice-lab/agronomy');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Student/PracticeLab/AgronomyLab'));
    }

    public function test_practice_lab_social_work_subpage_loads_successfully(): void
    {
        $student = $this->makeUser('student');

        $response = $this->actingAs($student)->get('/student/practice-lab/social-work');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Student/PracticeLab/SocialWorkLab'));
    }
}
