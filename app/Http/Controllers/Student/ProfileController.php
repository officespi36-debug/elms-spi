<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return Inertia::render('Student/Profile/Index', [
            'profile' => [
                'name' => $user->name,
                'email' => $user->email,
                'student_id' => $user->student_code ?? $user->student_id ?? 'SPI-2026-001',
                'faculty' => 'Faculty of Science & Information Technology',
                'department' => 'Department of Computer Science & Networking',
                'major' => $user->major ?? 'IT & Networking',
                'phone' => $user->phone ?? '+855 12 345 678',
                'bio' => $user->bio ?? 'Passionate 2nd-year computer science student specializing in web engineering and network infrastructure.',
                'semester' => 'Semester 2, Year 2',
                'language' => 'km',
                'email_notifications' => true,
                'app_notifications' => true,
            ]
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:50',
            'bio' => 'nullable|string|max:1000',
        ]);

        $user->update($validated);

        return back()->with('success', 'Profile updated successfully!');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'The provided password does not match your current password.']);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return back()->with('success', 'Password changed successfully!');
    }
}
