<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Major;
use App\Models\AcademicYear;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

echo "=========================================================\n";
echo "  SPI E-LEARNING: USER MANAGEMENT -> STUDENTS VERIFICATION\n";
echo "=========================================================\n\n";

$passCount = 0;
$testCount = 0;

function runTest($name, $closure) {
    global $passCount, $testCount;
    $testCount++;
    echo "[TEST {$testCount}] {$name}... ";
    try {
        $result = $closure();
        if ($result === true) {
            echo "PASSED [OK]\n";
            $passCount++;
        } else {
            echo "FAILED: {$result}\n";
        }
    } catch (\Throwable $e) {
        echo "EXCEPTION: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
}

// Prepare baseline data
$itMajor = Major::firstOrCreate(['code' => 'IT'], ['name' => 'Information Technology', 'name_kh' => 'បច្ចេកវិទ្យាព័ត៌មាន', 'department_id' => 1, 'is_active' => true]);
$agriMajor = Major::firstOrCreate(['code' => 'AGRI'], ['name' => 'Agriculture', 'name_kh' => 'កសិកម្ម', 'department_id' => 4, 'is_active' => true]);
$acadYear = AcademicYear::firstOrCreate(['name' => 'Academic Year 2026 – 2027'], ['code' => 'AY-2026-2027', 'is_active' => true]);

// Clean any old test students
User::where('email', 'like', 'test_student_%@spi.edu.kh')->delete();

// TEST 1: Create Student with full Student Information + Academic Information + Account
runTest("Create Student (CRUD - Create) with Major, Academic Year, Gender, DOB", function() use ($itMajor, $acadYear) {
    $controller = app(UserController::class);
    $request = Request::create('/admin/users', 'POST', [
        'name'             => 'Test Dara Student',
        'email'            => 'test_student_dara@spi.edu.kh',
        'password'         => 'Student@123',
        'role'             => 'student',
        'student_code'     => 'SPI-2026-999',
        'phone'            => '+85512999888',
        'gender'           => 'male',
        'dob'              => '2004-05-15',
        'major_id'         => $itMajor->id,
        'academic_year'    => $acadYear->name,
        'academic_year_id' => $acadYear->id,
        'status'           => 'active',
    ]);

    $telegramService = app(\App\Services\TelegramService::class);
    $response = $controller->store($request, $telegramService);

    $student = User::where('email', 'test_student_dara@spi.edu.kh')->first();
    if (!$student) return "Student not created in database";
    if ($student->role !== 'student') return "Role is not 'student'";
    if ($student->student_code !== 'SPI-2026-999') return "Student code mismatch: {$student->student_code}";
    if ($student->gender !== 'male') return "Gender mismatch: {$student->gender}";
    if (substr($student->dob, 0, 10) !== '2004-05-15') return "DOB mismatch: {$student->dob}";
    if ((int)$student->major_id !== (int)$itMajor->id) return "Major ID mismatch";
    if ($student->status !== 'active') return "Status is not active";
    if (!$student->is_active) return "is_active is not true";
    if (!Hash::check('Student@123', $student->password)) return "Password was not hashed properly";

    return true;
});

// TEST 2: Student List and Learning Summary Calculation
runTest("Student List endpoint attaches Academic Structure & Learning Summary", function() use ($itMajor) {
    $student = User::where('email', 'test_student_dara@spi.edu.kh')->first();
    
    // Seed a course, enrollment, and quiz attempt for this student
    $course = Course::first();
    if ($course) {
        Enrollment::firstOrCreate(
            ['student_id' => $student->id, 'course_id' => $course->id],
            ['status' => 'active', 'enrolled_at' => now()]
        );
        $quiz = Quiz::where('course_id', $course->id)->first();
        if ($quiz) {
            QuizAttempt::firstOrCreate(
                ['user_id' => $student->id, 'quiz_id' => $quiz->id],
                [
                    'answers' => ['q1' => 'a'],
                    'score' => 85.00,
                    'passed' => true,
                    'attempt_number' => 1,
                    'started_at' => now(),
                    'submitted_at' => now()
                ]
            );
        }
    }

    $controller = app(UserController::class);
    $studentsResponse = $controller->students();
    $pageProps = $studentsResponse->toResponse(request())->getOriginalContent()->getData()['page']['props'];

    $studentsList = collect($pageProps['students']);
    $retrievedStudent = $studentsList->firstWhere('email', 'test_student_dara@spi.edu.kh');

    if (!$retrievedStudent) return "Test student not found in students() list";
    if (!isset($retrievedStudent['learning_summary'])) return "learning_summary not attached";
    
    $summary = $retrievedStudent['learning_summary'];
    if ($summary['enrolled_courses'] < 1) return "Enrolled courses should be >= 1";
    if ($summary['average_quiz_score'] < 1 && $course && Quiz::where('course_id', $course->id)->exists()) {
        return "Average quiz score should be calculated";
    }

    return true;
});

// TEST 3: Edit Student & Academic Integrity check when Major changes
runTest("Edit Student (CRUD - Update) & Major Change Integrity Protection", function() use ($agriMajor, $acadYear) {
    $student = User::where('email', 'test_student_dara@spi.edu.kh')->first();
    $controller = app(UserController::class);

    // Update name and phone, change major from IT to AGRI (student has existing IT enrollment)
    $request = Request::create("/admin/users/{$student->id}", 'PUT', [
        'name'             => 'Test Dara Student (Edited)',
        'email'            => 'test_student_dara@spi.edu.kh',
        'role'             => 'student',
        'phone'            => '+85512777888',
        'gender'           => 'male',
        'dob'              => '2004-05-15',
        'major_id'         => $agriMajor->id,
        'academic_year'    => $acadYear->name,
        'academic_year_id' => $acadYear->id,
        'status'           => 'active',
    ]);

    // Set flash session
    $session = app('session.store');
    $request->setLaravelSession($session);

    $response = $controller->update($request, $student);

    $student->refresh();
    if ($student->name !== 'Test Dara Student (Edited)') return "Name not updated";
    if ($student->phone !== '+85512777888') return "Phone not updated";
    if ((int)$student->major_id !== (int)$agriMajor->id) return "Major not updated to AGRI";

    // Verify warning was set in session because student had existing course enrollments
    $warning = $session->get('warning');
    if (empty($warning)) return "Warning was not set in session for major change with existing enrollments";

    return true;
});

// TEST 4: Disable Student without Deleting Data (Active -> Inactive)
runTest("Disable Student: Sets Inactive, blocks login, preserves all quiz/enrollment history", function() {
    $student = User::where('email', 'test_student_dara@spi.edu.kh')->first();
    $controller = app(UserController::class);

    // Initial state: active
    if ($student->status !== 'active' || !$student->is_active) {
        return "Student should be active initially";
    }

    // Toggle status to disable
    $controller->toggleStatus($student);
    $student->refresh();

    if ($student->status !== 'inactive') return "Status should be 'inactive', got: {$student->status}";
    if ($student->is_active !== false) return "is_active should be false";

    // Verify data preservation (NOT deleted)
    $enrollmentCount = $student->enrollments()->count();
    $quizCount = $student->quizAttempts()->count();
    if ($enrollmentCount < 1) return "Enrollments were lost when disabled!";
    if ($quizCount < 1) return "Quiz attempts were lost when disabled!";

    // Verify data preservation (NOT deleted)
    $enrollmentCount = $student->enrollments()->count();
    $quizCount = $student->quizAttempts()->count();
    if ($enrollmentCount < 1) return "Enrollments were lost when disabled!";
    if ($quizCount < 1) return "Quiz attempts were lost when disabled!";

    // Verify access is blocked for disabled student via EnsureRole middleware
    $middleware = new \App\Http\Middleware\EnsureRole();
    $req = Request::create('/student/dashboard', 'GET');
    $req->setLaravelSession(app('session')->driver());
    $req->setUserResolver(fn() => $student);
    $res = $middleware->handle($req, fn() => response('ALLOWED'), 'student');
    if ($res->getContent() === 'ALLOWED') {
        return "Disabled/inactive student was allowed access through EnsureRole middleware!";
    }

    return true;
});

// TEST 5: Reactivate Student (Inactive -> Active)
runTest("Reactivate Student: Re-enables access and maintains full learning profile", function() {
    $student = User::where('email', 'test_student_dara@spi.edu.kh')->first();
    $controller = app(UserController::class);

    // Toggle back to active
    $controller->toggleStatus($student);
    $student->refresh();

    if ($student->status !== 'active') return "Status should be 'active'";
    if ($student->is_active !== true) return "is_active should be true";

    // Verify access works now through EnsureRole middleware
    $middleware = new \App\Http\Middleware\EnsureRole();
    $req = Request::create('/student/dashboard', 'GET');
    $req->setLaravelSession(app('session')->driver());
    $req->setUserResolver(fn() => $student);
    $res = $middleware->handle($req, fn() => response('ALLOWED'), 'student');
    if ($res->getContent() !== 'ALLOWED') {
        return "Reactivated student was blocked from student dashboard";
    }

    // Verify all academic records are still 100% intact
    if ($student->enrollments()->count() < 1) return "Enrollments missing after reactivation";
    if ($student->quizAttempts()->count() < 1) return "Quiz attempts missing after reactivation";

    return true;
});

// TEST 6: Strict Verification: No Payment / Paid / Unpaid fields in Students table or form
runTest("Zero Payment / Paid / Unpaid fields in Student CRUD", function() {
    $userCols = \Illuminate\Support\Facades\Schema::getColumnListing('users');
    // Ensure 'payment_status' does NOT exist in users table
    if (in_array('payment_status', $userCols)) {
        return "'payment_status' column exists in users table!";
    }
    if (in_array('paid_status', $userCols)) {
        return "'paid_status' column exists in users table!";
    }

    // Inspect Students.vue to ensure zero occurrences of payment status columns
    $vueContent = file_get_contents(__DIR__ . '/resources/js/Pages/Admin/UserManagementModule/Students.vue');
    if (stripos($vueContent, 'payment status') !== false) {
        return "Students.vue contains 'Payment Status' string!";
    }
    if (stripos($vueContent, 'paid / unpaid') !== false) {
        return "Students.vue contains 'Paid / Unpaid' string!";
    }

    return true;
});

// Clean up test data
User::where('email', 'like', 'test_student_%@spi.edu.kh')->delete();

echo "\n---------------------------------------------------------\n";
echo "  RESULT: {$passCount} / {$testCount} TESTS PASSED\n";
echo "---------------------------------------------------------\n";

if ($passCount === $testCount) {
    echo ">>> ALL USER MANAGEMENT -> STUDENTS REQUIREMENTS FULLY VERIFIED! <<<\n";
    exit(0);
} else {
    echo ">>> SOME TESTS FAILED! <<<\n";
    exit(1);
}
