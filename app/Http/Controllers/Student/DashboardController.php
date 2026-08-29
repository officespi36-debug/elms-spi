<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        try {
            $enrollments = Enrollment::where('student_id', $user->id)
                ->with(['course.teacher:id,name,avatar', 'course.major:id,name'])
                ->latest('updated_at')
                ->get();
        } catch (\Throwable $e) {
            $enrollments = collect();
        }

        $stats = [
            'enrolledCount'     => $enrollments->count(),
            'inProgressCount'   => $enrollments->where('status', 'active')->count(),
            'completedCount'    => $enrollments->where('status', 'completed')->count(),
            'certificatesCount' => 0,
            'learningTime'      => '0h 00m',
            'averageScore'      => 0,
        ];

        return Inertia::render('Student/Dashboard', [
            'enrollments' => $enrollments,
            'stats'       => $stats,
        ]);
    }
}
