<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseReview;
use App\Models\CourseWishlist;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Faculty;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Major;
use App\Models\Module;
use App\Models\Notification as ElmsNotification;
use App\Models\User;
use App\Notifications\TeacherStudentJoined;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StudentBrowseCatalogTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private User $otherStudent;
    private User $teacher;
    private Major $itMajor;
    private Major $tourismMajor;

    protected function beforeRefreshingDatabase(): void
    {
        Schema::disableForeignKeyConstraints();
    }

    protected function afterRefreshingDatabase(): void
    {
        Schema::enableForeignKeyConstraints();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Clear any rows created by legacy data-seeding migrations so assertions see clean fixtures
        DB::table('enrollments')->delete();
        DB::table('lessons')->delete();
        DB::table('modules')->delete();
        DB::table('courses')->delete();
        DB::table('majors')->delete();
        DB::table('departments')->delete();
        DB::table('faculties')->delete();
        DB::table('users')->delete();
        DB::table('notifications')->delete();

        $faculty = Faculty::create([
            'name' => 'Science & Technology',
            'code' => 'FST',
            'is_active' => true,
        ]);

        $dept = Department::create([
            'faculty_id' => $faculty->id,
            'name' => 'Computing',
            'code' => 'COMP',
            'is_active' => true,
        ]);

        $this->itMajor = Major::create([
            'department_id' => $dept->id,
            'name' => 'Information Technology',
            'name_kh' => 'បច្ចេកវិទ្យាព័ត៌មាន',
            'code' => 'IT',
            'is_active' => true,
        ]);

        $this->tourismMajor = Major::create([
            'department_id' => $dept->id,
            'name' => 'Tourism',
            'name_kh' => 'ទេសចរណ៍',
            'code' => 'TRM',
            'is_active' => true,
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'name' => 'Dr. Sokha Meas',
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'name' => 'Student One',
        ]);

        $this->otherStudent = User::factory()->create([
            'role' => 'student',
            'name' => 'Student Two',
        ]);
    }

    private function createCourseWithLessons(array $courseAttrs = [], array $lessonDurations = [600]): Course
    {
        static $seq = 1;
        $seq++;

        $course = Course::create(array_merge([
            'major_id' => $this->itMajor->id,
            'teacher_id' => $this->teacher->id,
            'title' => "Course {$seq}",
            'code' => "CRS-{$seq}",
            'description' => "Description {$seq}",
            'learning_mode' => 'self_paced',
            'level' => 'beginner',
            'is_paid' => false,
            'price' => 0,
            'status' => 'published',
        ], $courseAttrs));

        if (!empty($lessonDurations)) {
            $module = Module::create([
                'course_id' => $course->id,
                'title' => 'Module 1',
                'order' => 1,
            ]);

            foreach ($lessonDurations as $idx => $seconds) {
                Lesson::create([
                    'module_id' => $module->id,
                    'course_id' => $course->id,
                    'title' => 'Lesson ' . ($idx + 1),
                    'type' => 'video',
                    'duration_seconds' => $seconds,
                    'order' => $idx + 1,
                ]);
            }
        }

        return $course;
    }

    public function test_browse_returns_200_excludes_unpublished_and_validates_filters(): void
    {
        $pub = $this->createCourseWithLessons(['title' => 'Laravel Fundamentals', 'status' => 'published'], [1800, 1800]);
        $this->createCourseWithLessons(['title' => 'Secret Draft', 'status' => 'draft'], [600]);
        $this->createCourseWithLessons(['title' => 'Pending Review', 'status' => 'pending'], [600]);

        $res = $this->actingAs($this->student)->get('/student/browse');
        $res->assertStatus(200);
        $res->assertInertia(fn ($page) => $page
            ->component('Student/Browse/Index')
            ->where('totalPublished', 1)
            ->where('courses.total', 1)
            ->has('courses.data', 1)
            ->where('courses.data.0.id', $pub->id)
            ->where('courses.data.0.lessonsCount', 2)
            ->where('courses.data.0.durationSeconds', 3600)
            ->where('courses.data.0.duration', '1h 00m')
        );

        // Invalid filter params return 422
        $this->actingAs($this->student)->get('/student/browse?price=invalid')->assertStatus(422);
        $this->actingAs($this->student)->get('/student/browse?sort=invalid_sort')->assertStatus(422);
        $this->actingAs($this->student)->get('/student/browse?category[]=999999')->assertStatus(422);
        $this->actingAs($this->student)->get('/student/browse?level[]=expert')->assertStatus(422);
        $this->actingAs($this->student)->get('/student/browse?q=' . str_repeat('a', 101))->assertStatus(422);
    }

    public function test_filters_sorting_pagination_and_self_excluding_facet_counts(): void
    {
        for ($i = 1; $i <= 14; $i++) {
            $this->createCourseWithLessons([
                'title' => sprintf('IT Course %02d', $i),
                'major_id' => $this->itMajor->id,
                'level' => $i <= 10 ? 'beginner' : 'advanced',
                'is_paid' => $i === 14,
                'price' => $i === 14 ? 29.00 : 0,
            ], [$i * 600]);
        }

        $tourism = $this->createCourseWithLessons([
            'title' => 'Eco Tourism Special',
            'major_id' => $this->tourismMajor->id,
            'level' => 'intermediate',
            'is_paid' => true,
            'price' => 45.00,
        ], [5400]);

        // Page 1 has 12 items of 15 total published
        $res = $this->actingAs($this->student)->get('/student/browse?sort=title');
        $res->assertStatus(200);
        $res->assertInertia(fn ($page) => $page
            ->where('courses.total', 15)
            ->where('courses.per_page', 12)
            ->has('courses.data', 12)
            ->where('courses.data.0.title', 'Eco Tourism Special')
            ->where('facets.prices.all', 15)
            ->where('facets.prices.free', 13)
            ->where('facets.prices.paid', 2)
        );

        // Filter by category=tourism&price=paid -> facets keep category=it count for price=paid (1), level intermediate=1
        $filtered = $this->actingAs($this->student)->get("/student/browse?category[]={$this->tourismMajor->id}&price=paid&q=Eco");
        $filtered->assertStatus(200);
        $filtered->assertInertia(fn ($page) => $page
            ->where('courses.total', 1)
            ->where('courses.data.0.id', $tourism->id)
            ->where('facets.prices.free', 0)
            ->where('facets.prices.paid', 1)
        );

        // Longest duration sort returns the 14*600 = 8400s course first
        $durRes = $this->actingAs($this->student)->get('/student/browse?sort=duration');
        $durRes->assertStatus(200);
        $durRes->assertInertia(fn ($page) => $page
            ->where('courses.data.0.title', 'IT Course 14')
            ->where('courses.data.0.durationSeconds', 8400)
        );
    }

    public function test_card_states_and_rating_threshold(): void
    {
        $enrolledCourse = $this->createCourseWithLessons(['title' => 'Enrolled Course'], [1200]);
        $completedCourse = $this->createCourseWithLessons(['title' => 'Completed Course'], [1200]);
        $comingSoonCourse = $this->createCourseWithLessons(['title' => 'Coming Soon Course'], []);

        Enrollment::create([
            'student_id' => $this->student->id,
            'course_id' => $enrolledCourse->id,
            'status' => 'active',
            'source' => 'self',
        ]);

        Enrollment::create([
            'student_id' => $this->student->id,
            'course_id' => $completedCourse->id,
            'status' => 'completed',
            'source' => 'self',
        ]);

        // 2 reviews -> hidden rating (null); 3 reviews -> visible rating
        $u1 = User::factory()->create(['role' => 'student']);
        $u2 = User::factory()->create(['role' => 'student']);
        $u3 = User::factory()->create(['role' => 'student']);

        CourseReview::create(['user_id' => $u1->id, 'course_id' => $enrolledCourse->id, 'rating' => 5]);
        CourseReview::create(['user_id' => $u2->id, 'course_id' => $enrolledCourse->id, 'rating' => 4]);

        CourseReview::create(['user_id' => $u1->id, 'course_id' => $completedCourse->id, 'rating' => 5]);
        CourseReview::create(['user_id' => $u2->id, 'course_id' => $completedCourse->id, 'rating' => 4]);
        CourseReview::create(['user_id' => $u3->id, 'course_id' => $completedCourse->id, 'rating' => 3]);

        $res = $this->actingAs($this->student)->get('/student/browse?sort=title');
        $res->assertStatus(200);
        $res->assertInertia(function ($page) use ($comingSoonCourse, $completedCourse, $enrolledCourse) {
            $data = collect($page->toArray()['props']['courses']['data'])->keyBy('id');

            $this->assertEquals('coming_soon', $data[$comingSoonCourse->id]['cardState']);
            $this->assertTrue($data[$comingSoonCourse->id]['isComingSoon']);

            $this->assertEquals('completed', $data[$completedCourse->id]['cardState']);
            $this->assertEquals(4.0, $data[$completedCourse->id]['rating']);
            $this->assertEquals(3, $data[$completedCourse->id]['reviewsCount']);

            $this->assertEquals('enrolled', $data[$enrolledCourse->id]['cardState']);
            $this->assertNull($data[$enrolledCourse->id]['rating']);
            $this->assertEquals(2, $data[$enrolledCourse->id]['reviewsCount']);

            return $page;
        });

        // Coming soon course cannot be enrolled
        $this->actingAs($this->student)
            ->post("/student/courses/{$comingSoonCourse->id}/enroll")
            ->assertStatus(422);
    }

    public function test_enrollment_free_duplicate_paid_and_unpublished(): void
    {
        Notification::fake();

        $freeCourse = $this->createCourseWithLessons(['title' => 'Free Course', 'is_paid' => false, 'price' => 0], [900]);
        $paidCourse = $this->createCourseWithLessons(['title' => 'Paid Course', 'is_paid' => true, 'price' => 35.00], [900]);
        $draftCourse = $this->createCourseWithLessons(['title' => 'Draft Course', 'status' => 'draft'], [900]);

        // 1. Enroll free succeeds, sets source=self and notifies teacher once
        $this->actingAs($this->student)
            ->post("/student/courses/{$freeCourse->id}/enroll")
            ->assertRedirect();

        $this->assertDatabaseHas('enrollments', [
            'student_id' => $this->student->id,
            'course_id' => $freeCourse->id,
            'status' => 'active',
            'source' => 'self',
        ]);
        Notification::assertSentToTimes($this->teacher, TeacherStudentJoined::class, 1);

        // 2. Duplicate enroll is idempotent and does not notify teacher twice
        $this->actingAs($this->student)
            ->post("/student/courses/{$freeCourse->id}/enroll")
            ->assertRedirect();

        $this->assertEquals(1, Enrollment::where('student_id', $this->student->id)->where('course_id', $freeCourse->id)->count());
        Notification::assertSentToTimes($this->teacher, TeacherStudentJoined::class, 1);

        // 3. Paid course redirects to checkout without creating active enrollment beforehand
        $paidRes = $this->actingAs($this->student)
            ->post("/student/courses/{$paidCourse->id}/enroll");
        $paidRes->assertRedirect(route('student.payments.create', $paidCourse->id));
        $this->assertDatabaseMissing('enrollments', [
            'student_id' => $this->student->id,
            'course_id' => $paidCourse->id,
        ]);

        // 4. Unpublished course returns 404
        $this->actingAs($this->student)
            ->post("/student/courses/{$draftCourse->id}/enroll")
            ->assertStatus(404);
    }

    public function test_wishlist_toggle_is_idempotent_and_isolated_per_user(): void
    {
        $course = $this->createCourseWithLessons(['title' => 'Wish Course'], [600]);

        // Add wishlist (idempotent)
        $this->actingAs($this->student)->postJson("/student/courses/{$course->id}/wishlist")
            ->assertStatus(200)
            ->assertJson(['ok' => true, 'wishlisted' => true]);

        $this->actingAs($this->student)->postJson("/student/courses/{$course->id}/wishlist")
            ->assertStatus(200)
            ->assertJson(['ok' => true, 'wishlisted' => true]);

        $this->assertEquals(1, CourseWishlist::where('user_id', $this->student->id)->where('course_id', $course->id)->count());

        // Other student adds then deletes — student A's wishlist must remain intact
        $this->actingAs($this->otherStudent)->postJson("/student/courses/{$course->id}/wishlist")->assertStatus(200);
        $this->actingAs($this->otherStudent)->deleteJson("/student/courses/{$course->id}/wishlist")
            ->assertStatus(200)
            ->assertJson(['ok' => true, 'wishlisted' => false]);

        $this->assertDatabaseHas('course_wishlists', [
            'user_id' => $this->student->id,
            'course_id' => $course->id,
        ]);

        // Student A deletes (idempotent)
        $this->actingAs($this->student)->deleteJson("/student/courses/{$course->id}/wishlist")->assertStatus(200);
        $this->actingAs($this->student)->deleteJson("/student/courses/{$course->id}/wishlist")->assertStatus(200);

        $this->assertDatabaseMissing('course_wishlists', [
            'user_id' => $this->student->id,
            'course_id' => $course->id,
        ]);
    }

    public function test_browse_catalog_has_no_n_plus_one_queries(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $c = $this->createCourseWithLessons(['title' => "Perf Course {$i}"], [600, 900]);
            Enrollment::create([
                'student_id' => $this->student->id,
                'course_id' => $c->id,
                'status' => 'active',
                'source' => 'self',
            ]);
            CourseWishlist::create([
                'user_id' => $this->student->id,
                'course_id' => $c->id,
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($this->student)->get('/student/browse')->assertStatus(200);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Bounded query count regardless of 10 courses + enrollments + wishlists
        $this->assertLessThanOrEqual(20, count($queries), 'Unexpected N+1 queries detected: ' . count($queries));
    }
}
