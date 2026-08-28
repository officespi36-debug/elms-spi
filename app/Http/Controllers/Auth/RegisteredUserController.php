<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Major;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class RegisteredUserController extends Controller
{
    /**
     * Display the multi-step registration view.
     */
    public function create()
    {
        $allowRegistration = Setting::where('key', 'allow_registration')->value('value');

        if ($allowRegistration === '0' || $allowRegistration === false) {
            return redirect()->route('login')->withErrors([
                'email' => 'ការចុះឈ្មោះត្រូវបានបិទជាបណ្តោះអាសន្នដោយ Admin (Registration is currently disabled).',
            ]);
        }

        $majors = Major::with(['department.faculty'])
            ->where('is_active', true)
            ->get();

        return Inertia::render('Auth/Register', [
            'majors' => $majors,
        ]);
    }

    /**
     * Handle an incoming multi-step registration request (Student & Teacher).
     */
    public function store(Request $request, TelegramService $telegramService)
    {
        $allowRegistration = Setting::where('key', 'allow_registration')->value('value');

        if ($allowRegistration === '0' || $allowRegistration === false) {
            return back()->withErrors(['email' => 'Registration is currently disabled.']);
        }

        $targetRole = $request->input('role', 'student');

        // ─── TEACHER REGISTRATION ───
        if ($targetRole === 'teacher') {
            $request->validate([
                'name' => 'required|string|max:255',
                'name_kh' => 'nullable|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email',
                'phone' => 'required|string|max:50|unique:users,phone',
                'password' => ['required', 'confirmed', Rules\Password::min(8)],
                'major_id' => 'nullable|exists:majors,id',
                'terms' => 'accepted',
            ], [
                'email.unique' => 'អាសយដ្ឋានអ៊ីមែលនេះមានក្នុងប្រព័ន្ធរួចហើយ',
                'phone.unique' => 'លេខទូរស័ព្ទនេះមានក្នុងប្រព័ន្ធរួចហើយ',
                'password.confirmed' => 'ការបញ្ជាក់ពាក្យសម្ងាត់មិនត្រូវគ្នាទេ',
                'terms.accepted' => 'សូមយល់ព្រមតាមលក្ខខណ្ឌប្រើប្រាស់',
            ]);

            $user = User::create([
                'name' => $request->name,
                'name_kh' => $request->name_kh,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => 'teacher',
                'major_id' => $request->major_id,
                'qualification' => $request->input('qualification', 'Higher Education Educator'),
                'expertise' => $request->input('expertise', 'Lecturer / Academic Professor'),
                'status' => 'active',
                'is_active' => true,
            ]);

            $user->load(['major.department.faculty']);

            $telegramService->notifyTeacherCreated($user, '[Self-registered]');

            Auth::login($user);

            return redirect()->route('teacher.dashboard')->with('success', 'ចុះឈ្មោះគណនីលោកគ្រូ/អ្នកគ្រូបានជោគជ័យ!');
        }

        // ─── STUDENT REGISTRATION ───
        $request->validate([
            'name' => 'required|string|max:255',
            'name_kh' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'required|string|max:50|unique:users,phone',
            'password' => ['required', 'confirmed', Rules\Password::min(8)],
            'major_id' => 'required|exists:majors,id',
            'study_type' => 'required|in:on_campus,online',
            'payment_method' => 'nullable|in:aba,cash',
            'receipt' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',
            'terms' => 'accepted',
        ], [
            'email.unique' => 'អាសយដ្ឋានអ៊ីមែលនេះមានក្នុងប្រព័ន្ធរួចហើយ',
            'phone.unique' => 'លេខទូរស័ព្ទនេះមានក្នុងប្រព័ន្ធរួចហើយ',
            'password.confirmed' => 'ការបញ្ជាក់ពាក្យសម្ងាត់មិនត្រូវគ្នាទេ',
            'major_id.required' => 'សូមជ្រើសរើសជំនាញសិក្សា',
            'terms.accepted' => 'សូមយល់ព្រមតាមលក្ខខណ្ឌប្រើប្រាស់',
        ]);

        // Auto-Generate Student ID (e.g., STU-2026-89421)
        $year = date('Y');
        do {
            $studentCode = 'STU-' . $year . '-' . mt_rand(10000, 99999);
        } while (User::where('student_code', $studentCode)->exists());

        // Handle optional receipt upload
        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('receipts', 'public');
        }

        // Create Student Account
        $user = User::create([
            'name' => $request->name,
            'name_kh' => $request->name_kh,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'student',
            'major_id' => $request->major_id,
            'student_code' => $studentCode,
            'study_type' => $request->study_type,
            'status' => 'active',
            'is_active' => true,
        ]);

        $user->load(['major.department.faculty']);

        // Create Initial Registration Payment Record if applicable
        $amount = ($request->payment_method === 'aba') ? 324.00 : 360.00;

        $course = Course::first();
        $teacherId = User::where('role', 'teacher')->value('id') ?? User::where('role', 'admin')->value('id');

        if ($course && $teacherId && ($request->filled('payment_method') || $receiptPath)) {
            Payment::create([
                'student_id' => $user->id,
                'course_id' => $course->id,
                'teacher_id' => $teacherId,
                'amount' => $amount,
                'currency' => 'USD',
                'aba_transaction_id' => 'REG-' . strtoupper(Str::random(10)),
                'payment_slip' => $receiptPath,
                'status' => $receiptPath ? 'verifying' : 'pending',
            ]);
        }

        // Send Telegram Notification
        $telegramService->notifyNewStudentRegistration($user, [
            'method' => strtoupper($request->payment_method ?? 'Cash'),
            'amount' => $amount,
            'receipt' => $receiptPath,
        ]);

        // Auto Login & Redirect to Student Dashboard
        Auth::login($user);

        return redirect()->route('student.dashboard')->with('success', 'ការចុះឈ្មោះបានជោគជ័យ!');
    }
}
