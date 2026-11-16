<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\EnrollmentService;
use Illuminate\Http\Request;

class EnrollmentController extends Controller
{
    public function store(Request $request, Course $course, EnrollmentService $enrollmentService)
    {
        $user = $request->user();
        $enrollment = $enrollmentService->enroll($user, $course, 'self', $user);

        if ($enrollment->status === 'pending_payment') {
            return redirect()->route('student.payments.index')
                ->with('info', 'សូមបង់ប្រាក់ដើម្បីចូលរៀនវគ្គសិក្សានេះ (Please complete payment to access course).');
        }

        return back()->with('success', 'Enrolled successfully');
    }
}
