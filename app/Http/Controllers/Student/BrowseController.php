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
            ->with(['teacher:id,name,avatar', 'major:id,name', 'department:id,name', 'faculty:id,name'])
            ->when($request->search, function ($q, $search) {
                $q->where('title', 'like', "%{$search}%");
            })
            ->when($request->major_id, function ($q, $major_id) {
                $q->where('major_id', $major_id);
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Student/Browse/Index', [
            'courses' => $courses,
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
