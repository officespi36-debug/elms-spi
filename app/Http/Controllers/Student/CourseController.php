<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'enrolled');

        $enrollments = Enrollment::where('student_id', $request->user()->id)
            ->with(['course.teacher:id,name,avatar', 'course.major:id,name'])
            ->latest('updated_at')
            ->get();

        return Inertia::render('Student/MyCourses/Index', [
            'activeTab' => $tab,
            'enrollments' => $enrollments,
        ]);
    }

    public function enrolled(Request $request)
    {
        $enrollments = Enrollment::where('student_id', $request->user()->id)
            ->with(['course.teacher:id,name,avatar', 'course.major:id,name'])
            ->latest('updated_at')
            ->get();

        return Inertia::render('Student/MyCourses/EnrolledCourses', [
            'enrollments' => $enrollments,
        ]);
    }

    public function current(Request $request)
    {
        $enrollments = Enrollment::where('student_id', $request->user()->id)
            ->where('status', 'active')
            ->with(['course.teacher:id,name,avatar', 'course.major:id,name', 'course.modules.lessons'])
            ->latest('updated_at')
            ->get();

        return Inertia::render('Student/MyCourses/CurrentCourse', [
            'enrollments' => $enrollments,
        ]);
    }

    public function completed(Request $request)
    {
        $enrollments = Enrollment::where('student_id', $request->user()->id)
            ->where('status', 'completed')
            ->with(['course.teacher:id,name,avatar', 'course.major:id,name'])
            ->latest('updated_at')
            ->get();

        return Inertia::render('Student/MyCourses/CompletedCourses', [
            'enrollments' => $enrollments,
        ]);
    }

    public function wishlist(Request $request)
    {
        return Inertia::render('Student/MyCourses/Wishlist');
    }
}
