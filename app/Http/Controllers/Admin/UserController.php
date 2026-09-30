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
            ->with(['major.department.faculty'])
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
            ->with(['major.department.faculty', 'courses'])
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
            ->with(['major.department.faculty', 'enrollments.course', 'academicYear'])
            ->latest()
            ->get();

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
            'name'          => 'required|string|max:255',
            'name_kh'       => 'nullable|string|max:255',
            'email'         => 'required|email|unique:users,email',
            'role'          => 'required|in:admin,teacher,student',
            'password'      => 'nullable|string|min:8',
            'major_id'      => 'nullable|exists:majors,id',
            'academic_year' => 'nullable|string|max:255',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'phone'         => 'nullable|string|max:30',
            'status'        => 'nullable|in:active,inactive,suspended,pending',
            'qualification' => 'nullable|string|max:255',
            'expertise'     => 'nullable|string|max:255',
            'bio'           => 'nullable|string',
            'aba_name'      => 'nullable|string',
            'aba_number'    => 'nullable|string',
        ]);

        $rawPassword = $data['password'] ?? ('Pass@' . rand(10000, 99999));
        $data['password'] = bcrypt($rawPassword);
        $data['status'] = $data['status'] ?? 'active';
        $data['is_active'] = ($data['status'] === 'active');

        if ($data['role'] === 'student' && empty($data['student_code'])) {
            $data['student_code'] = 'STU-' . date('Y') . '-' . rand(10000, 99999);
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
            'name'          => 'required|string|max:255',
            'name_kh'       => 'nullable|string|max:255',
            'email'         => 'required|email|unique:users,email,' . $user->id,
            'role'          => 'required|in:admin,teacher,student',
            'password'      => 'nullable|string|min:8',
            'major_id'      => 'nullable|exists:majors,id',
            'academic_year' => 'nullable|string|max:255',
            'academic_year_id' => 'nullable|exists:academic_years,id',
            'phone'         => 'nullable|string|max:30',
            'status'        => 'nullable|in:active,inactive,suspended,pending',
            'qualification' => 'nullable|string|max:255',
            'expertise'     => 'nullable|string|max:255',
            'bio'           => 'nullable|string',
            'aba_name'      => 'nullable|string',
            'aba_number'    => 'nullable|string',
        ]);

        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }

        if (isset($data['status'])) {
            $data['is_active'] = ($data['status'] === 'active');
        }

        $user->update($data);

        return back()->with('success', 'User profile updated successfully.');
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
