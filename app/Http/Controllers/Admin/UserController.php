<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\Faculty;
use App\Models\Major;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class UserController extends Controller
{
    private function getSummaryStats()
    {
        return [
            'total_users'     => User::count(),
            'total_admins'    => User::where('role', 'admin')->count(),
            'total_teachers'  => User::where('role', 'teacher')->count(),
            'total_students'  => User::where('role', 'student')->count(),
            'total_suspended' => User::where('status', 'suspended')->count(),
            'active_admins'   => User::where('role', 'admin')->where('status', 'active')->count(),
            'active_teachers' => User::where('role', 'teacher')->where('status', 'active')->count(),
            'active_students' => User::where('role', 'student')->where('status', 'active')->count(),
        ];
    }

    public function allUsers(Request $request)
    {
        $query = User::with(['major.department.faculty'])->latest();

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Admin/UserManagementModule/AllUsers', [
            'users'        => $query->get(),
            'faculties'    => Faculty::where('is_active', true)->get(),
            'departments'  => Department::where('is_active', true)->get(),
            'majors'       => Major::with(['department.faculty'])->where('is_active', true)->get(),
            'summaryStats' => $this->getSummaryStats(),
            'filters'      => $request->only(['role', 'status', 'search']),
        ]);
    }

    public function administrators()
    {
        $admins = User::where('role', 'admin')
            ->with(['major.department.faculty', 'authLogs' => function ($q) {
                $q->latest()->limit(5);
            }])
            ->latest()
            ->get();

        return Inertia::render('Admin/UserManagementModule/Administrators', [
            'administrators' => $admins,
            'departments'    => Department::where('is_active', true)->get(),
            'summaryStats'   => $this->getSummaryStats(),
        ]);
    }

    public function teachers()
    {
        $teachers = User::where('role', 'teacher')
            ->with(['major.department.faculty', 'courses.enrollments', 'courses.lessons', 'courses.quizzes'])
            ->latest()
            ->get();

        return Inertia::render('Admin/UserManagementModule/Teachers', [
            'teachers'     => $teachers,
            'departments'  => Department::where('is_active', true)->get(),
            'majors'       => Major::with(['department.faculty'])->where('is_active', true)->get(),
            'summaryStats' => $this->getSummaryStats(),
        ]);
    }

    public function students()
    {
        $students = User::where('role', 'student')
            ->with(['major.department.faculty', 'enrollments.course', 'academicYear', 'quizAttempts', 'lessonProgress'])
            ->latest()
            ->get()
            ->map(function ($student) {
                $enrolledCoursesCount = $student->enrollments->count();
                $completedCoursesCount = $student->enrollments->where('status', 'completed')->count();
                
                // Average quiz score
                $quizAvg = $student->quizAttempts->count() > 0
                    ? round($student->quizAttempts->avg('score'), 1)
                    : 0;

                // Overall progress: from lesson progress or completed courses
                $progressCount = $student->lessonProgress->count();
                $overallProgress = $progressCount > 0
                    ? round($student->lessonProgress->avg('percent'), 1)
                    : ($enrolledCoursesCount > 0 ? round(($completedCoursesCount / $enrolledCoursesCount) * 100, 1) : 0);

                // Quiz / Assignment stats
                $passedQuizzes = $student->quizAttempts->where('passed', true)->count();
                $totalQuizzes = $student->quizAttempts->count();
                $assignmentStatus = $totalQuizzes > 0
                    ? "{$passedQuizzes}/{$totalQuizzes} Passed"
                    : ($enrolledCoursesCount > 0 ? 'In Progress' : 'No Submissions');

                $student->learning_summary = [
                    'enrolled_courses'   => $enrolledCoursesCount,
                    'completed_courses'  => $completedCoursesCount,
                    'average_quiz_score' => $quizAvg,
                    'assignment_status'  => $assignmentStatus,
                    'overall_progress'   => $overallProgress,
                ];

                return $student;
            });

        return Inertia::render('Admin/UserManagementModule/Students', [
            'students'      => $students,
            'departments'   => Department::where('is_active', true)->get(),
            'majors'        => Major::with(['department.faculty'])->where('is_active', true)->get(),
            'academicYears' => AcademicYear::orderBy('name', 'desc')->get(),
            'summaryStats'  => $this->getSummaryStats(),
        ]);
    }

    public function suspendedUsers()
    {
        $suspended = User::where('status', 'suspended')
            ->with(['major.department.faculty'])
            ->latest()
            ->get();

        return Inertia::render('Admin/UserManagementModule/SuspendedUsers', [
            'suspendedUsers' => $suspended,
            'summaryStats'   => $this->getSummaryStats(),
        ]);
    }

    public function importExport()
    {
        return Inertia::render('Admin/UserManagementModule/ImportExport', [
            'summaryStats' => $this->getSummaryStats(),
        ]);
    }

    public function index(Request $request)
    {
        return $this->allUsers($request);
    }

    public function store(Request $request, TelegramService $telegramService)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'name_kh'          => 'nullable|string|max:255',
            'email'            => 'required|email|unique:users,email',
            'role'             => 'required|in:admin,teacher,student',
            'password'         => 'nullable|string|min:6',
            'student_code'     => 'nullable|string|max:50',
            'major_id'         => 'nullable|exists:majors,id',
            'academic_year'    => 'nullable|string|max:255',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'phone'            => 'nullable|string|max:30',
            'gender'           => 'nullable|string|in:male,female,other',
            'dob'              => 'nullable|date',
            'status'           => 'nullable|in:active,inactive,suspended,pending',
            'qualification'    => 'nullable|string|max:255',
            'expertise'        => 'nullable|string|max:255',
            'bio'              => 'nullable|string',
        ]);

        if (!empty($data['academic_year_id']) && empty($data['academic_year'])) {
            $data['academic_year'] = AcademicYear::where('id', $data['academic_year_id'])->value('name');
        } elseif (!empty($data['academic_year']) && empty($data['academic_year_id'])) {
            $data['academic_year_id'] = AcademicYear::where('name', $data['academic_year'])->value('id');
        }

        $rawPassword = $data['password'] ?? ('Pass@' . rand(10000, 99999));
        $data['password'] = bcrypt($rawPassword);
        $data['status'] = $data['status'] ?? 'active';
        $data['is_active'] = ($data['status'] === 'active');

        if ($data['role'] === 'student' && empty($data['student_code'])) {
            $nextNum = User::where('role', 'student')->count() + 1;
            $data['student_code'] = sprintf('SPI-%s-%03d', date('Y'), $nextNum);
        } elseif ($data['role'] === 'teacher' && empty($data['student_code'])) {
            $nextNum = User::where('role', 'teacher')->count() + 1;
            $data['student_code'] = sprintf('TEA-%s-%03d', date('Y'), $nextNum);
        } elseif ($data['role'] === 'admin' && empty($data['student_code'])) {
            $nextNum = User::where('role', 'admin')->count() + 1;
            $data['student_code'] = sprintf('ADM-%s-%03d', date('Y'), $nextNum);
        }

        $user = User::create($data);
        $user->load(['major.department.faculty']);

        if ($user->role === 'teacher') {
            $telegramService->notifyTeacherCreated($user, $rawPassword);
        }

        return back()->with('success', "Account created successfully for {$user->name}. Password: {$rawPassword}");
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'name_kh'          => 'nullable|string|max:255',
            'email'            => 'required|email|unique:users,email,' . $user->id,
            'role'             => 'required|in:admin,teacher,student',
            'password'         => 'nullable|string|min:6',
            'student_code'     => 'nullable|string|max:50',
            'major_id'         => 'nullable|exists:majors,id',
            'academic_year'    => 'nullable|string|max:255',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'phone'            => 'nullable|string|max:30',
            'gender'           => 'nullable|string|in:male,female,other',
            'dob'              => 'nullable|date',
            'status'           => 'nullable|in:active,inactive,suspended,pending',
            'qualification'    => 'nullable|string|max:255',
            'expertise'        => 'nullable|string|max:255',
            'bio'              => 'nullable|string',
        ]);

        if (!empty($data['academic_year_id']) && empty($data['academic_year'])) {
            $data['academic_year'] = AcademicYear::where('id', $data['academic_year_id'])->value('name');
        } elseif (!empty($data['academic_year']) && empty($data['academic_year_id'])) {
            $data['academic_year_id'] = AcademicYear::where('name', $data['academic_year'])->value('id');
        }

        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        if (isset($data['status'])) {
            $data['is_active'] = ($data['status'] === 'active');
        }

        // Check if student changed major with existing enrollments
        $majorWarning = null;
        if ($user->role === 'student' && isset($data['major_id']) && (int)$data['major_id'] !== (int)$user->major_id) {
            $existingEnrollmentsCount = $user->enrollments()->count();
            if ($existingEnrollmentsCount > 0) {
                $oldMajor = $user->major?->name ?? 'Current Major';
                $newMajor = Major::find($data['major_id']);
                $newMajorName = $newMajor?->name ?? 'New Major';
                $majorWarning = "និស្សិតមានការចុះឈ្មោះ Course ចំនួន {$existingEnrollmentsCount} រួចហើយក្នុង {$oldMajor}។ សូមពិនិត្យផ្ទៀងផ្ទាត់ Course Enrollment ដើម្បីកុំឱ្យទិន្នន័យ Academic ខុសជាមួយ Major ថ្មី ({$newMajorName})។";
            }
        }

        $user->update($data);

        if ($majorWarning) {
            return back()->with('success', 'User profile updated successfully.')->with('warning', $majorWarning);
        }

        return back()->with('success', 'User profile updated successfully.');
    }

    public function toggleStatus(User $user)
    {
        $newStatus = ($user->status === 'active') ? 'inactive' : 'active';
        $user->update([
            'status'    => $newStatus,
            'is_active' => ($newStatus === 'active')
        ]);

        return back()->with('success', "Account '{$user->name}' status set to " . ucfirst($newStatus) . ".");
    }

    public function suspend(User $user, Request $request)
    {
        $reason = $request->input('reason', 'Admin Suspension');
        $user->update([
            'status'    => 'suspended',
            'is_active' => false,
        ]);

        return back()->with('success', "User '{$user->name}' suspended successfully.");
    }

    public function restore(User $user)
    {
        $user->update([
            'status'    => 'active',
            'is_active' => true,
        ]);

        return back()->with('success', "User '{$user->name}' account restored successfully.");
    }

    public function bulkAction(Request $request)
    {
        $data = $request->validate([
            'ids'    => 'required|array',
            'action' => 'required|in:activate,suspend,delete',
        ]);

        if ($data['action'] === 'activate') {
            User::whereIn('id', $data['ids'])->update(['status' => 'active', 'is_active' => true]);
        } elseif ($data['action'] === 'suspend') {
            User::whereIn('id', $data['ids'])->update(['status' => 'suspended', 'is_active' => false]);
        } elseif ($data['action'] === 'delete') {
            User::whereIn('id', $data['ids'])->delete();
        }

        return back()->with('success', 'Bulk operation completed successfully.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return back()->with('success', 'User deleted successfully.');
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
        ]);

        $user = User::findOrFail(auth()->id());

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = 'avatar_' . $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            
            $destinationPath = public_path('uploads/avatars');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0777, true);
            }
            $file->move($destinationPath, $filename);

            $avatarUrl = '/uploads/avatars/' . $filename;
            $user->update(['avatar' => $avatarUrl]);

            return back()->with('success', 'Avatar updated successfully.');
        }

        return back()->with('error', 'No file uploaded.');
    }
}
