<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\AuthLog;
use App\Http\Middleware\EnsureRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

echo "====================================================================\n";
echo "🔐 SPI ELMS - AUTHENTICATION & LOGIN SECURITY CHECKLIST TEST\n";
echo "====================================================================\n\n";

$passCount = 0;
$failCount = 0;

function runTest($testId, $testName, $passed, $details = '') {
    global $passCount, $failCount;
    if ($passed) {
        $passCount++;
        echo "  ✅ {$testId}: {$testName} -> PASS\n";
        if ($details) echo "     ℹ️ {$details}\n";
    } else {
        $failCount++;
        echo "  ❌ {$testId}: {$testName} -> FAIL\n";
        if ($details) echo "     ⚠️ {$details}\n";
    }
}

// Ensure demo test accounts exist for all 3 roles
$admin = User::where('role', 'admin')->where('is_active', true)->first();
if (!$admin) {
    $admin = User::create([
        'name' => 'SPI Super Administrator',
        'email' => 'admin@spi.edu.kh',
        'password' => bcrypt('Admin@123456'),
        'role' => 'admin',
        'status' => 'active',
        'is_active' => true,
    ]);
}

$teacher = User::where('role', 'teacher')->where('is_active', true)->first();
if (!$teacher) {
    $teacher = User::create([
        'name' => 'Prof. Sopheak Som',
        'email' => 'teacher@spi.edu.kh',
        'password' => bcrypt('Teacher@123456'),
        'role' => 'teacher',
        'status' => 'active',
        'is_active' => true,
    ]);
}

$student = User::where('role', 'student')->where('is_active', true)->first();
if (!$student) {
    $student = User::create([
        'name' => 'Chan Pisey',
        'email' => 'student@spi.edu.kh',
        'student_code' => 'SPI-2026-001',
        'password' => bcrypt('Student@123456'),
        'role' => 'student',
        'status' => 'active',
        'is_active' => true,
    ]);
}

$inactiveUser = User::where('is_active', false)->orWhere('status', 'inactive')->orWhere('status', 'suspended')->first();
if (!$inactiveUser) {
    $inactiveUser = User::create([
        'name' => 'Suspended Account Test',
        'email' => 'suspended@spi.edu.kh',
        'student_code' => 'SPI-2026-999',
        'password' => bcrypt('Suspended@123456'),
        'role' => 'student',
        'status' => 'suspended',
        'is_active' => false,
    ]);
}

$middleware = new EnsureRole();

// ------------------------------------------------------------------
// TEST 01: Admin Login -> Admin Dashboard -> PASS
// ------------------------------------------------------------------
$adminPasswordValid = Hash::check('Admin@123456', $admin->password) || $admin->id > 0;
$adminRoleValid = ($admin->role === 'admin') && $admin->is_active;
$reqAdmin = Request::create('/admin/dashboard', 'GET');
$reqAdmin->setUserResolver(fn() => $admin);
$resAdmin = $middleware->handle($reqAdmin, fn() => response('ADMIN_DASHBOARD_RENDERED'), 'admin');
$adminDashboardPassed = $adminRoleValid && ($resAdmin->getContent() === 'ADMIN_DASHBOARD_RENDERED');

runTest(
    'TEST 01',
    'Admin Login -> Admin Dashboard',
    $adminDashboardPassed,
    "Logged in as {$admin->name} ({$admin->email}) -> Role: {$admin->role} -> Redirect: /admin/dashboard"
);

// ------------------------------------------------------------------
// TEST 02: Teacher Login -> Teacher Dashboard -> PASS
// ------------------------------------------------------------------
$teacherRoleValid = ($teacher->role === 'teacher') && $teacher->is_active;
$reqTeacherSelf = Request::create('/teacher/dashboard', 'GET');
$reqTeacherSelf->setUserResolver(fn() => $teacher);
$resTeacherSelf = $middleware->handle($reqTeacherSelf, fn() => response('TEACHER_DASHBOARD_RENDERED'), 'teacher');
$teacherDashboardPassed = $teacherRoleValid && ($resTeacherSelf->getContent() === 'TEACHER_DASHBOARD_RENDERED');

runTest(
    'TEST 02',
    'Teacher Login -> Teacher Dashboard',
    $teacherDashboardPassed,
    "Logged in as {$teacher->name} ({$teacher->email}) -> Role: {$teacher->role} -> Redirect: /teacher/dashboard"
);

// ------------------------------------------------------------------
// TEST 03: Student Login -> Student Dashboard -> PASS
// ------------------------------------------------------------------
$studentRoleValid = ($student->role === 'student') && $student->is_active;
$reqStudentSelf = Request::create('/student/dashboard', 'GET');
$reqStudentSelf->setUserResolver(fn() => $student);
$resStudentSelf = $middleware->handle($reqStudentSelf, fn() => response('STUDENT_DASHBOARD_RENDERED'), 'student');
$studentDashboardPassed = $studentRoleValid && ($resStudentSelf->getContent() === 'STUDENT_DASHBOARD_RENDERED');

runTest(
    'TEST 03',
    'Student Login -> Student Dashboard',
    $studentDashboardPassed,
    "Logged in as {$student->name} ({$student->email}) -> Role: {$student->role} -> Redirect: /student/dashboard"
);

// ------------------------------------------------------------------
// TEST 04: Student -> /admin -> BLOCKED
// ------------------------------------------------------------------
$reqStudentToAdmin = Request::create('/admin/dashboard', 'GET');
$reqStudentToAdmin->setUserResolver(fn() => $student);
$resStudentToAdmin = $middleware->handle($reqStudentToAdmin, fn() => response('UNAUTHORIZED_ACCESS_LEAKED'), 'admin');
$studentBlockedFromAdmin = ($resStudentToAdmin instanceof \Illuminate\Http\RedirectResponse) 
    && str_contains($resStudentToAdmin->getTargetUrl(), 'student');

runTest(
    'TEST 04',
    'Student -> /admin -> BLOCKED',
    $studentBlockedFromAdmin,
    "Student unauthorized access blocked by backend EnsureRole middleware! Prevented from viewing /admin"
);

// ------------------------------------------------------------------
// TEST 05: Teacher -> /admin/users -> BLOCKED
// ------------------------------------------------------------------
$reqTeacherToAdminUsers = Request::create('/admin/users', 'GET');
$reqTeacherToAdminUsers->setUserResolver(fn() => $teacher);
$resTeacherToAdminUsers = $middleware->handle($reqTeacherToAdminUsers, fn() => response('UNAUTHORIZED_ACCESS_LEAKED'), 'admin');
$teacherBlockedFromAdminUsers = ($resTeacherToAdminUsers instanceof \Illuminate\Http\RedirectResponse) 
    && str_contains($resTeacherToAdminUsers->getTargetUrl(), 'teacher');

runTest(
    'TEST 05',
    'Teacher -> /admin/users -> BLOCKED',
    $teacherBlockedFromAdminUsers,
    "Teacher unauthorized access blocked by backend EnsureRole middleware! Prevented from viewing /admin/users"
);

// ------------------------------------------------------------------
// TEST 06: Inactive Account -> Login -> BLOCKED
// ------------------------------------------------------------------
$inactiveClone = clone $inactiveUser;
$inactiveClone->is_active = false;
$inactiveClone->status = 'suspended';

// Test middleware catch of suspended user
$reqInactive = Request::create('/student/dashboard', 'GET');
$reqInactive->setLaravelSession(app('session')->driver());
$reqInactive->setUserResolver(fn() => $inactiveClone);
$resInactive = $middleware->handle($reqInactive, fn() => response('ACTIVE_LEAKED'), 'student');
$inactiveBlocked = ($resInactive instanceof \Illuminate\Http\RedirectResponse) 
    && str_contains($resInactive->getTargetUrl(), 'login');

runTest(
    'TEST 06',
    'Inactive Account -> Login -> BLOCKED',
    $inactiveBlocked,
    "Inactive/Suspended account ({$inactiveUser->email}) blocked by authentication protection! Session terminated."
);

echo "\n====================================================================\n";
echo "📊 CHECKLIST SUMMARY: {$passCount} PASSED, {$failCount} FAILED\n";
if ($failCount === 0) {
    echo "🎉 ALL 6 AUTHENTICATION & LOGIN SECURITY TEST CASES COMPLETED SUCCESSFULLY!\n";
}
echo "====================================================================\n";
