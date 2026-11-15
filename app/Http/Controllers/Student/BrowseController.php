<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Inertia\Inertia;

class BrowseController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $courses = Course::where('status', 'published')
            ->with(['teacher:id,name,avatar', 'major:id,name', 'subject:id,name', 'lessons:id,course_id'])
            ->when($request->search, function ($q, $search) {
                $q->where('title', 'like', "%{$search}%");
            })
            ->when($request->major_id, function ($q, $major_id) {
                $q->where('major_id', $major_id);
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $user = $request->user();
        $enrolledCourseIds = $user ? \App\Models\Enrollment::where('student_id', $user->id)->pluck('course_id')->all() : [];

        $formattedCourses = $courses->through(function ($c) use ($enrolledCourseIds) {
            $isEnrolled = in_array($c->id, $enrolledCourseIds);
            $lessonsCount = $c->lessons ? $c->lessons->count() : 3;

            return [
                'id'                  => $c->id,
                'slug'                => $c->code ?: ('course-' . $c->id),
                'title'               => $c->title,
                'category'            => $c->major?->name ?: 'Academic Studies',
                'categoryKey'         => 'all',
                'badge'               => $c->is_paid ? 'Popular' : 'Free',
                'badgeColor'          => $c->is_paid ? 'bg-purple-600' : 'bg-emerald-600',
                'illustrationType'    => 'js',
                'description'         => $c->description ?: 'Learn core principles, methodologies, and practical applications.',
                'instructor'          => [
                    'name'   => $c->teacher?->name ?: 'Dr. Mengly Chea',
                    'avatar' => $c->teacher?->avatar ?: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=120&auto=format&fit=crop&q=80',
                    'role'   => 'SPI Instructor',
                ],
                'rating'              => 4.9,
                'reviewsCount'        => 120,
                'reviewsCountDisplay' => '120',
                'level'               => 'Beginner',
                'lessonsCount'        => $lessonsCount,
                'duration'            => '15h 00m',
                'price'               => (float) ($c->price ?? 0),
                'priceDisplay'        => $c->is_paid ? ('$' . number_format($c->price, 2)) : 'Free',
                'isFree'              => !$c->is_paid || (float)$c->price <= 0,
                'language'            => 'Khmer',
                'skills'              => [$c->major?->name ?: 'Academic Studies', 'Core Fundamentals', 'Practical Lab'],
                'isWishlisted'        => false,
                'enrollmentStatus'    => $isEnrolled ? 'enrolled' : 'not_enrolled',
                'overviewHref'        => "/student/courses/{$c->id}/overview",
            ];
        });

        return Inertia::render('Student/Browse/Index', [
            'courses' => $formattedCourses,
            'filters' => $request->only(['search', 'major_id'])
        ]);
    }

    public function show(Course $course)
    {
        if ($course->status !== 'published') {
            abort(404);
        }

        $course->load(['teacher', 'major', 'modules.lessons']);
        
        $enrolled = \App\Models\Enrollment::where('course_id', $course->id)
            ->where('student_id', auth()->id())
            ->first();

        return Inertia::render('Student/Browse/Show', [
            'course' => $course,
            'enrolled' => $enrolled
        ]);
    }
}
