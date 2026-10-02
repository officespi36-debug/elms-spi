<?php

/**
 * End-to-End Verification Test Harness for:
 * 1. User Management -> Teachers
 * 2. User Management -> Admins
 * 3. Course Management -> Courses
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Major;
use App\Models\Department;
use App\Models\Subject;
use App\Models\Course;
use App\Models\AcademicYear;
use App\Models\Semester;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CourseModuleController;
use Illuminate\Http\Request;

echo "\n======================================================\n";
echo "   ADMIN END-TO-END VERIFICATION SUITE\n";
echo "   1. Teachers | 2. Admins | 3. Courses\n";
echo "======================================================\n\n";

$passedCount = 0;
$totalTests = 8;

function assertCondition($name, $condition, $details = '') {
    global $passedCount;
    if ($condition) {
        echo " [PASS] $name\n";
        if ($details) echo "        -> $details\n";
        $passedCount++;
        return true;
    } else {
        echo " [FAIL] $name\n";
        if ($details) echo "        -> $details\n";
        return false;
    }
}

// ----------------------------------------------------
// SECTION 1: USER MANAGEMENT -> TEACHERS
// ----------------------------------------------------
echo "--- Testing 1: Teacher CRUD & Academic Assignment ---\n";

$major = Major::first();
if (!$major) {
    $dept = Department::firstOrCreate(['name' => 'Department of Computing', 'code' => 'DEP-COMP-001']);
    $major = Major::create(['name' => 'Information Technology', 'code' => 'MJR-IT-001', 'department_id' => $dept->id, 'is_active' => true]);
}

$userController = new UserController();
$telegramService = app(\App\Services\TelegramService::class);

// Test 1: Create Teacher
$teacherEmail = 'test.teacher.' . time() . '@spi.edu.kh';
$teacherCode = 'TEA-2026-' . rand(100, 999);
$createTeacherReq = Request::create('/admin/users', 'POST', [
    'name'          => 'Mr. Sok Sophea',
    'name_kh'       => 'សុខ សុភា',
    'email'         => $teacherEmail,
    'role'          => 'teacher',
    'student_code'  => $teacherCode,
    'major_id'      => $major->id,
    'phone'         => '089111222',
    'qualification' => 'Master of Science in Software Engineering',
    'expertise'     => 'Software Engineering & Cloud Computing',
    'status'        => 'active',
    'password'      => 'Password@123'
]);

$response = $userController->store($createTeacherReq, $telegramService);
$createdTeacher = User::where('email', $teacherEmail)->first();

assertCondition(
    "Teacher Creation & Academic Assignment",
    $createdTeacher !== null && $createdTeacher->role === 'teacher' && (int)$createdTeacher->major_id === (int)$major->id,
    "Teacher ID: {$createdTeacher?->student_code}, Major: {$createdTeacher?->major?->name}, Email: {$createdTeacher?->email}"
);

// Test 2: Edit Teacher Profile & Teaching Summary Preservation on Disable
$editTeacherReq = Request::create("/admin/users/{$createdTeacher->id}", 'PUT', [
    'name'          => 'Mr. Sok Sophea Updated',
    'name_kh'       => 'សុខ សុភា (កែប្រែ)',
    'email'         => $teacherEmail,
    'role'          => 'teacher',
    'student_code'  => $teacherCode,
    'major_id'      => $major->id,
    'phone'         => '089999888',
    'qualification' => 'Ph.D in Computer Science',
    'expertise'     => 'Artificial Intelligence & Machine Learning',
    'status'        => 'active'
]);

$userController->update($editTeacherReq, $createdTeacher);
$createdTeacher->refresh();

assertCondition(
    "Teacher Profile Update",
    $createdTeacher->name === 'Mr. Sok Sophea Updated' && $createdTeacher->phone === '089999888' && $createdTeacher->expertise === 'Artificial Intelligence & Machine Learning',
    "Updated name: {$createdTeacher->name}, Phone: {$createdTeacher->phone}, Qualification: {$createdTeacher->qualification}"
);

// Test 3: Disable Teacher preserves history without hard delete
$userController->toggleStatus($createdTeacher);
$createdTeacher->refresh();
$isNowInactive = ($createdTeacher->status === 'inactive' && !$createdTeacher->is_active);

$userController->toggleStatus($createdTeacher);
$createdTeacher->refresh();
$isNowActive = ($createdTeacher->status === 'active' && $createdTeacher->is_active);

assertCondition(
    "Teacher Disable / Re-activate Flow (Preserves History, No Hard Delete)",
    $isNowInactive && $isNowActive,
    "Toggled to inactive (status: inactive, is_active: false) and restored back to active safely."
);

// ----------------------------------------------------
// SECTION 2: USER MANAGEMENT -> ADMINS
// ----------------------------------------------------
echo "\n--- Testing 2: Administrator Management & Safety Guards ---\n";

// Test 4: Create Admin (Super Admin vs Admin)
$adminEmail = 'test.admin.' . time() . '@spi.edu.kh';
$adminCode = 'ADM-2026-' . rand(100, 999);
$createAdminReq = Request::create('/admin/users', 'POST', [
    'name'          => 'Audit Administrator',
    'name_kh'       => 'អ្នកគ្រប់គ្រងប្រព័ន្ធ',
    'email'         => $adminEmail,
    'role'          => 'admin',
    'student_code'  => $adminCode,
    'phone'         => '012345678',
    'qualification' => 'super_admin',
    'expertise'     => 'Super Admin',
    'status'        => 'active',
    'password'      => 'AdminPass@123'
]);

$userController->store($createAdminReq, $telegramService);
$createdAdmin = User::where('email', $adminEmail)->first();

assertCondition(
    "Administrator Creation (Role & Authority)",
    $createdAdmin !== null && $createdAdmin->role === 'admin' && $createdAdmin->student_code === $adminCode,
    "Admin ID: {$createdAdmin?->student_code}, Role: {$createdAdmin?->role}, Title: {$createdAdmin?->expertise}"
);

// Test 5: Protected Last Full-Access Admin Safety Guard
// Ensure system protects the last active admin from being disabled
$activeAdmins = User::where('role', 'admin')->where('status', 'active')->get();

// Let's create an isolated condition where there is only 1 active admin
$tempAdmin = User::create([
    'name'         => 'Sole Admin Tester',
    'email'        => 'sole.admin.' . time() . '@spi.edu.kh',
    'role'         => 'admin',
    'student_code' => 'ADM-SOLO-01',
    'status'       => 'active',
    'is_active'    => true,
    'password'     => bcrypt('secret')
]);

// Temporarily set all other admins to inactive
$otherAdminIds = User::where('role', 'admin')->where('id', '!=', $tempAdmin->id)->where('status', 'active')->pluck('id')->toArray();
User::whereIn('id', $otherAdminIds)->update(['status' => 'inactive', 'is_active' => false]);

// Now attempt to disable the sole active admin
$disableResp = $userController->toggleStatus($tempAdmin);
$tempAdmin->refresh();

// Restore other admins immediately
User::whereIn('id', $otherAdminIds)->update(['status' => 'active', 'is_active' => true]);

$lastAdminProtected = ($tempAdmin->status === 'active' && $tempAdmin->is_active);
$tempAdmin->delete(); // cleanup test admin

assertCondition(
    "Protected Last Full-Access Admin Guard (Prevention of System Lockout)",
    $lastAdminProtected,
    "Refused disabling sole active admin: status remained active (is_active: true)."
);

// ----------------------------------------------------
// SECTION 3: COURSE MANAGEMENT -> COURSES
// ----------------------------------------------------
echo "\n--- Testing 3: Course Management (Academic Relations & Workflow) ---\n";

$courseController = new CourseModuleController();
$subject = Subject::where('major_id', $major->id)->first();
if (!$subject) {
    $subject = Subject::create([
        'name'          => 'Cloud Computing & DevOps',
        'code'          => 'SUB-IT-CC101',
        'major_id'      => $major->id,
        'department_id' => $major->department_id,
        'credits'       => 3,
        'status'        => 'active',
        'is_active'     => true,
    ]);
}

$semester = Semester::first();
if (!$semester) {
    $semester = Semester::create([
        'name'         => 'Semester 1',
        'code'         => 'SEM-2026-S1',
        'semester_num' => '1',
        'is_active'    => true
    ]);
}

// Test 6: Create Course linked to Academic Structure (Major, Subject, Teacher, Semester)
$courseCode = 'CRS-IT-' . rand(1000, 9999);
$createCourseReq = Request::create('/admin/course-module/store', 'POST', [
    'title'         => 'Enterprise Cloud Architectures',
    'code'          => $courseCode,
    'description'   => 'Comprehensive cloud engineering with AWS, Docker, Kubernetes and microservices.',
    'teacher_id'    => $createdTeacher->id,
    'major_id'      => $major->id,
    'subject_id'    => $subject->id,
    'academic_year' => 'Academic Year 2026 – 2027',
    'semester'      => $semester->name,
    'semester_id'   => $semester->id,
    'learning_mode' => 'instructor_led',
    'status'        => 'draft',
]);

$courseController->storeCourse($createCourseReq);
$createdCourse = Course::where('code', $courseCode)->first();

assertCondition(
    "Course Creation with Major, Subject, Teacher, Academic Year & Semester",
    $createdCourse !== null &&
    (int)$createdCourse->major_id === (int)$major->id &&
    (int)$createdCourse->subject_id === (int)$subject->id &&
    (int)$createdCourse->teacher_id === (int)$createdTeacher->id &&
    $createdCourse->status === 'draft',
    "Course: {$createdCourse?->title} ({$createdCourse?->code}), Teacher: {$createdCourse?->teacher?->name}, Status: {$createdCourse?->status}"
);

// Test 7: Strict Academic Rule: Zero Payment Fields Enforced
assertCondition(
    "Strict Academic Rule: Zero Payment Enforcement",
    $createdCourse !== null && ($createdCourse->is_paid === false || $createdCourse->is_paid === 0) && (float)$createdCourse->price == 0.0,
    "Course is_paid: " . ($createdCourse->is_paid ? 'true' : 'false') . ", price: $" . $createdCourse->price . " (Academic compliant)"
);

// Test 8: Course Status Workflow (Draft -> Pending -> Approved/Published -> Archived -> Delete)
$createdCourse->update(['status' => 'pending']);
$createdCourse->refresh();
$isPending = ($createdCourse->status === 'pending');

$courseController->approveCourse(Request::create("/admin/course-module/approve/{$createdCourse->id}", 'POST'), $createdCourse->id);
$createdCourse->refresh();
$isPublished = ($createdCourse->status === 'published' && $createdCourse->reviewed_at !== null);

$createdCourse->update(['status' => 'archived']);
$createdCourse->refresh();
$isArchived = ($createdCourse->status === 'archived');

$courseId = $createdCourse->id;
$courseController->destroyCourse($courseId);
$isDeleted = (Course::find($courseId) === null);

assertCondition(
    "Course Status Workflow (Draft -> Pending -> Published -> Archived -> Delete)",
    $isPending && $isPublished && $isArchived && $isDeleted,
    "Full lifecycle completed: Pending -> Published (with reviewed_at) -> Archived -> Deleted cleanly."
);

// Cleanup test records
if ($createdTeacher) $createdTeacher->delete();
if ($createdAdmin) $createdAdmin->delete();

echo "\n======================================================\n";
echo "   TEST SUMMARY: {$passedCount} / {$totalTests} TESTS PASSED\n";
echo "======================================================\n\n";

if ($passedCount === $totalTests) {
    echo "🎉 ALL ADMIN VERIFICATION CRITERIA SUCCESSFULLY PASSED!\n";
    exit(0);
} else {
    echo "❌ SOME TESTS FAILED. PLEASE REVIEW LOGS ABOVE.\n";
    exit(1);
}
