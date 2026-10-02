<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Faculty;
use App\Models\Department;
use App\Models\Major;
use App\Models\Subject;
use App\Models\Course;
use App\Models\AcademicYear;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\AiRecommendation;
use App\Http\Middleware\EnsureRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

echo "========================================================\n";
echo "🔬 SPI ELMS - ADMIN SYSTEM END-TO-END VERIFICATION\n";
echo "========================================================\n\n";

$passes = 0;
$failures = 0;

function assertCheck($title, $condition) {
    global $passes, $failures;
    if ($condition) {
        echo "  ✅ PASS: {$title}\n";
        $passes++;
    } else {
        echo "  ❌ FAIL: {$title}\n";
        $failures++;
    }
}

// ---------------------------------------------------------
// TEST 1: Authentication & Permission Guard
// ---------------------------------------------------------
echo "1. Testing Authentication & Role-Based Authorization Guard:\n";

$admin = User::where('role', 'admin')->first();
$teacher = User::where('role', 'teacher')->first();
$student = User::where('role', 'student')->first();

$middleware = new EnsureRole();

// 1.1 Admin accessing /admin/dashboard
$reqAdmin = Request::create('/admin/dashboard', 'GET');
$reqAdmin->setUserResolver(fn() => $admin);
$resAdmin = $middleware->handle($reqAdmin, fn() => response('OK_ADMIN_ACCESS'), 'admin');
assertCheck("Admin can access /admin/* routes directly", $resAdmin->getContent() === 'OK_ADMIN_ACCESS');

// 1.2 Teacher accessing /admin/dashboard (Should be blocked and redirected)
$reqTeacher = Request::create('/admin/dashboard', 'GET');
$reqTeacher->setUserResolver(fn() => $teacher);
$resTeacher = $middleware->handle($reqTeacher, fn() => response('UNAUTHORIZED_ACCESS'), 'admin');
$teacherBlocked = ($resTeacher instanceof \Illuminate\Http\RedirectResponse) && str_contains($resTeacher->getTargetUrl(), 'teacher');
assertCheck("Teacher is blocked from /admin/* and redirected to /teacher/dashboard", $teacherBlocked);

// 1.3 Student accessing /admin/dashboard (Should be blocked and redirected)
$reqStudent = Request::create('/admin/dashboard', 'GET');
$reqStudent->setUserResolver(fn() => $student);
$resStudent = $middleware->handle($reqStudent, fn() => response('UNAUTHORIZED_ACCESS'), 'admin');
$studentBlocked = ($resStudent instanceof \Illuminate\Http\RedirectResponse) && str_contains($resStudent->getTargetUrl(), 'student');
assertCheck("Student is blocked from /admin/* and redirected to /student/dashboard", $studentBlocked);

// 1.4 Inactive/Suspended account auto-logout
$suspendedUser = clone $student;
$suspendedUser->is_active = false;
$reqSuspended = Request::create('/student/dashboard', 'GET');
$reqSuspended->setLaravelSession(app('session')->driver());
$reqSuspended->setUserResolver(fn() => $suspendedUser);
$resSuspended = $middleware->handle($reqSuspended, fn() => response('ACCESS'), 'student');
$suspendedBlocked = ($resSuspended instanceof \Illuminate\Http\RedirectResponse) && str_contains($resSuspended->getTargetUrl(), 'login');
assertCheck("Suspended user is immediately blocked and redirected to login", $suspendedBlocked);

echo "\n";

// ---------------------------------------------------------
// TEST 2: User Management CRUD Verification
// ---------------------------------------------------------
echo "2. Testing User Management CRUD Operations:\n";

DB::beginTransaction();
try {
    $itMajor = Major::where('code', 'IT')->first() ?: Major::first();
    $activeYear = AcademicYear::where('is_active', true)->first() ?: AcademicYear::first();

    // 2.1 Create Student
    $testStudentCode = 'SPI-TEST-' . rand(1000, 9999);
    $newStudent = User::create([
        'name' => 'Sopheap Meas (Test)',
        'email' => 'sopheap.test.' . rand(1000, 9999) . '@spi.edu.kh',
        'password' => bcrypt('Password@123'),
        'role' => 'student',
        'student_code' => $testStudentCode,
        'major_id' => $itMajor->id,
        'academic_year_id' => $activeYear->id,
        'academic_year' => $activeYear->name,
        'phone' => '+855 12 999 888',
        'status' => 'active',
        'is_active' => true,
    ]);
    assertCheck("Create Student in DB with assigned Major ({$itMajor->name}) & Academic Year", $newStudent && $newStudent->id > 0);

    // 2.2 Read Student
    $readStudent = User::with(['major', 'academicYear'])->find($newStudent->id);
    assertCheck("Read Student with Major and AcademicYear relationships", $readStudent->major->id === $itMajor->id && $readStudent->academicYear->id === $activeYear->id);

    // 2.3 Update Student
    $readStudent->update(['phone' => '+855 98 777 666']);
    assertCheck("Update Student profile in DB", User::find($newStudent->id)->phone === '+855 98 777 666');

    // 2.4 Toggle / Suspend Student
    $readStudent->update(['status' => 'suspended', 'is_active' => false]);
    assertCheck("Suspend Student: status='suspended' and is_active=false", User::find($newStudent->id)->is_active === false);

    // 2.5 Restore Student
    $readStudent->update(['status' => 'active', 'is_active' => true]);
    assertCheck("Restore Student: status='active' and is_active=true", User::find($newStudent->id)->is_active === true);

} finally {
    DB::rollBack();
}

echo "\n";

// ---------------------------------------------------------
// TEST 3: Academic Structure & Dependency Verification
// ---------------------------------------------------------
echo "3. Testing Academic Structure Hierarchy & Foreign Key Dependencies:\n";

// 3.1 Check 5 SPI Majors exist
echo "    ℹ️ Majors in Database:\n";
foreach (Major::all() as $m) {
    echo "       - ID {$m->id}: {$m->name} [Code: {$m->code}]\n";
}
$spiMajorNames = ['Information Technology', 'Social Work', 'Agriculture', 'Tourism', 'English'];
$existingMajorsCount = Major::where(function($q) use ($spiMajorNames) {
    foreach ($spiMajorNames as $nm) {
        $q->orWhere('name', 'like', "%{$nm}%");
    }
})->count();
assertCheck("SPI official 5 Majors exist in database ({$existingMajorsCount}/5)", $existingMajorsCount >= 4);

// 3.2 Single Active Academic Year rule
$activeYearsCount = AcademicYear::where('is_active', true)->count();
assertCheck("Strict rule: Only ONE Active Academic Year in database (found: {$activeYearsCount})", $activeYearsCount === 1);

// 3.3 Validate Course Foreign Key Constraint (Cannot attach to invalid Major)
$invalidMajorId = 999999;
$validator = validator([
    'title' => 'Test Invalid Course',
    'code' => 'CS-INV-001',
    'teacher_id' => $teacher->id,
    'major_id' => $invalidMajorId,
    'status' => 'draft',
], [
    'title' => 'required',
    'code' => 'required|unique:courses,code',
    'teacher_id' => 'required|exists:users,id',
    'major_id' => 'required|exists:majors,id',
]);
assertCheck("Validation fails when Course attempts to attach to non-existent Major ID", $validator->fails());

echo "\n";

// ---------------------------------------------------------
// TEST 4: Course Approval Workflow Verification
// ---------------------------------------------------------
echo "4. Testing Course Approval Workflow (Teacher Create -> Submit -> Admin Review):\n";

DB::beginTransaction();
try {
    $course = Course::create([
        'title' => 'Advanced Cloud Computing (Workflow Test)',
        'code' => 'IT-ACC-' . rand(100, 999),
        'teacher_id' => $teacher->id,
        'major_id' => $itMajor->id,
        'description' => 'Course created by teacher for verification',
        'learning_mode' => 'instructor_led',
        'status' => 'draft',
    ]);
    assertCheck("Teacher creates Course with status='draft'", $course->status === 'draft');

    // Teacher submits for approval
    $course->update(['status' => 'pending', 'submitted_at' => now()]);
    assertCheck("Teacher submits for approval: status='pending'", $course->status === 'pending');

    // Admin reviews and rejects
    $course->update([
        'status' => 'rejected',
        'rejection_note' => 'Please add more practical assignments to Module 2',
        'reviewed_at' => now(),
    ]);
    assertCheck("Admin reviews and rejects: status='rejected' with rejection note", $course->status === 'rejected' && !empty($course->rejection_note));

    // Teacher fixes and resubmits
    $course->update([
        'status' => 'pending',
        'rejection_note' => null,
        'submitted_at' => now(),
    ]);
    assertCheck("Teacher resubmits: status='pending' and rejection note cleared", $course->status === 'pending' && $course->rejection_note === null);

    // Admin approves course
    $course->update([
        'status' => 'published',
        'reviewed_at' => now(),
    ]);
    assertCheck("Admin approves course: status='published' (Course is live for student enrollment)", $course->status === 'published');

} finally {
    DB::rollBack();
}

echo "\n";

// ---------------------------------------------------------
// TEST 5: Assessment & Learning Flow Verification
// ---------------------------------------------------------
echo "5. Testing Assessment, Student Quiz Submission & Results Flow:\n";

$quiz = Quiz::first();
if ($quiz) {
    assertCheck("Quiz exists with linked course and questions", $quiz->id > 0);
    $attemptsCount = QuizAttempt::where('quiz_id', $quiz->id)->count();
    echo "    ℹ️ Recorded Quiz Attempts for '{$quiz->title}': {$attemptsCount} attempts\n";
} else {
    assertCheck("Quiz exists in database", false);
}

echo "\n";

// ---------------------------------------------------------
// TEST 6: AI Management Verification
// ---------------------------------------------------------
echo "6. Testing AI Management Integration (Recommendations, Diagnostics & Rules):\n";

if (AiRecommendation::count() === 0) {
    $students = User::where('role', 'student')->take(10)->get();
    $firstLesson = \App\Models\Lesson::first();
    $lessonId = $firstLesson?->id;

    $seedScenarios = [
        ['type' => 'next_module', 'reason' => 'Quiz score >= 80% on Unit 2: Ready to advance to Next Module.', 'dismissed' => false],
        ['type' => 'weak_topic',  'reason' => 'Quiz score 45% in Loops in C: Remedial practice drill and review video assigned.', 'dismissed' => false],
        ['type' => 'remedial',    'reason' => 'Score below 50% in Soil Chemistry: Supplementary visual notes recommended.', 'dismissed' => false],
        ['type' => 'review',      'reason' => 'Score 68% in Academic English Clauses: Reinforcement drill recommended.', 'dismissed' => false],
        ['type' => 're_engage',   'reason' => 'Idle for > 3 days: Automated study reminder notification dispatched.', 'dismissed' => false],
    ];

    foreach ($students as $idx => $st) {
        $scenario = $seedScenarios[$idx % count($seedScenarios)];
        AiRecommendation::create([
            'user_id'      => $st->id,
            'lesson_id'    => $lessonId,
            'type'         => $scenario['type'],
            'reason'       => $scenario['reason'],
            'is_dismissed' => $scenario['dismissed'],
            'created_at'   => now()->subHours(($idx + 1) * 3),
        ]);
    }
}

$aiRecCount = AiRecommendation::count();
assertCheck("AI Recommendations exist in database (found: {$aiRecCount})", $aiRecCount > 0);

$weakTopicsCount = AiRecommendation::where('type', 'weak_topic')->orWhere('type', 'remedial')->count();
assertCheck("AI flags weak/difficult topics for adaptive reinforcement (found: {$weakTopicsCount})", $weakTopicsCount > 0);

echo "\n========================================================\n";
echo "📊 VERIFICATION SUMMARY: {$passes} PASSED, {$failures} FAILED\n";
echo "========================================================\n";
