<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Services\DashboardAnalyticsService;
use App\Services\EnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class EnrollmentController extends Controller
{
    public function store(Request $request, Course $course, EnrollmentService $enrollmentService)
    {
        $user = $request->user();

        if ($course->status !== 'published') {
            abort(404);
        }

        $directLessonsCount = $course->lessons()->count();
        $totalLessonsCount = $directLessonsCount > 0
            ? $directLessonsCount
            : (int) $course->modules()->withCount('lessons')->get()->sum('lessons_count');

        if ($totalLessonsCount === 0) {
            abort(422, 'វគ្គសិក្សានេះមិនទាន់មានមេរៀននៅឡើយទេ (Coming soon).');
        }

        $alreadyActive = Enrollment::where('student_id', $user->id)
            ->where('course_id', $course->id)
            ->whereIn('status', ['active', 'completed'])
            ->exists();

        // Do not enroll before payment when course price > 0
        if ((float) ($course->price ?? 0) > 0 && !$alreadyActive) {
            return redirect()->route('student.payments.create', $course->id)
                ->with('info', app()->getLocale() === 'km'
                    ? 'សូមបង់ប្រាក់ដើម្បីចូលរៀនវគ្គសិក្សានេះ។'
                    : 'Please complete payment to access course.');
        }

        $enrollment = $enrollmentService->enroll($user, $course, 'self', $user);

        Cache::forget("student.{$user->id}.new_enrollments");
        app(DashboardAnalyticsService::class)->invalidateUserCache($user->id);

        if ($enrollment->status === 'pending_payment') {
            return redirect()->route('student.payments.create', $course->id)
                ->with('info', app()->getLocale() === 'km'
                    ? 'សូមបង់ប្រាក់ដើម្បីចូលរៀនវគ្គសិក្សានេះ។'
                    : 'Please complete payment to access course.');
        }

        return back()->with('success', app()->getLocale() === 'km'
            ? 'ចុះឈ្មោះចូលរៀនវគ្គសិក្សាដោយជោគជ័យ!'
            : 'Enrolled successfully!');
    }
}
