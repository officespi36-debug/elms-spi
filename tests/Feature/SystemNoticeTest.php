<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SystemNoticeTest extends TestCase
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

    private function createUserWithRole(string $role): User
    {
        return User::create([
            'name'     => ucfirst($role) . ' Tester',
            'email'    => $role . '_' . uniqid() . '@spilms.test',
            'password' => Hash::make('password'),
            'role'     => $role,
            'status'   => 'active',
        ]);
    }

    public function test_student_page_renders_notice_banner_when_enabled(): void
    {
        config()->set('student.notice.enabled', true);
        config()->set('student.notice.url', null);

        $student = $this->createUserWithRole('student');

        $response = $this->actingAs($student)
            ->withSession(['elms_lang' => 'km'])
            ->get('/student/dashboard');

        $response->assertStatus(200);
        $response->assertSee('id="student-system-notice"', false);
        $response->assertSee('role="status"', false);
        $response->assertSee('aria-live="polite"', false);
        $response->assertSee(trans('notice.system_improving', [], 'km'));
        $response->assertDontSee('id="student-system-notice-link"', false);
    }

    public function test_student_page_does_not_render_notice_banner_when_disabled(): void
    {
        config()->set('student.notice.enabled', false);

        $student = $this->createUserWithRole('student');

        $response = $this->actingAs($student)
            ->withSession(['elms_lang' => 'km'])
            ->get('/student/dashboard');

        $response->assertStatus(200);
        $response->assertDontSee('id="student-system-notice"', false);
        $response->assertDontSee(trans('notice.system_improving', [], 'km'));
        $response->assertDontSee(trans('notice.system_improving', [], 'en'));
    }

    public function test_student_notice_respects_km_and_en_locales_and_report_url(): void
    {
        config()->set('student.notice.enabled', true);
        config()->set('student.notice.url', 'https://spilms.tech/report-issue');

        $student = $this->createUserWithRole('student');

        $kmResponse = $this->actingAs($student)
            ->withSession(['elms_lang' => 'km'])
            ->get('/student/dashboard');

        $kmResponse->assertStatus(200);
        $kmResponse->assertSee(
            '<span id="student-system-notice-text" class="student-system-notice__text">' . e(trans('notice.system_improving', [], 'km')) . '</span>',
            false
        );
        $kmResponse->assertSee('target="_blank"', false);
        $kmResponse->assertSee('rel="noopener"', false);
        $kmResponse->assertSee(e(trans('notice.report', [], 'km')) . '</a>', false);

        $enResponse = $this->actingAs($student)
            ->withSession(['elms_lang' => 'en'])
            ->get('/student/dashboard');

        $enResponse->assertStatus(200);
        $enResponse->assertSee(
            '<span id="student-system-notice-text" class="student-system-notice__text">' . e(trans('notice.system_improving', [], 'en')) . '</span>',
            false
        );
        $enResponse->assertSee(e(trans('notice.report', [], 'en')) . '</a>', false);
    }

    public function test_teacher_and_admin_pages_never_render_student_notice_banner(): void
    {
        config()->set('student.notice.enabled', true);
        config()->set('student.notice.url', 'https://spilms.tech/report-issue');

        $teacher = $this->createUserWithRole('teacher');
        $admin = $this->createUserWithRole('admin');

        $teacherResponse = $this->actingAs($teacher)->get('/teacher/dashboard');
        $teacherResponse->assertStatus(200);
        $teacherResponse->assertDontSee('id="student-system-notice"', false);
        $teacherResponse->assertDontSee(trans('notice.system_improving', [], 'km'));
        $teacherResponse->assertDontSee(trans('notice.system_improving', [], 'en'));

        $adminResponse = $this->actingAs($admin)->get('/admin/dashboard');
        $adminResponse->assertStatus(200);
        $adminResponse->assertDontSee('id="student-system-notice"', false);
        $adminResponse->assertDontSee(trans('notice.system_improving', [], 'km'));
        $adminResponse->assertDontSee(trans('notice.system_improving', [], 'en'));
    }
}
