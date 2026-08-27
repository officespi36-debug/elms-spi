<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\Student;
use App\Http\Controllers\Teacher;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        if ($user->role === 'admin') {
            return redirect()->route('admin.dashboard');
        } elseif ($user->role === 'teacher') {
            return redirect()->route('teacher.dashboard');
        }
        return redirect()->route('student.dashboard');
    }
    return Inertia::render('Auth/Login');
})->name('home');

// ─── Health Check Route (UptimeRobot / Monitoring) ───
Route::get('/health', function () {
    return response()->json([
        'status' => 'active',
        'timestamp' => now()->toIso8601String(),
    ], 200);
});

// ─── Development Preview for OTP Email (Manus Style) ───
Route::get('/preview/otp-email', function () {
    $user = (object)[
        'name' => 'Kosal Sensok',
        'email' => 'kosalsensok@gmail.com',
    ];
    return view('emails.otp', ['otp' => '550274', 'user' => $user]);
});

// ─── Sitemap XML for Googlebot & Search Engines ───
Route::get('/sitemap.xml', function () {
    $path = public_path('sitemap.xml');
    if (file_exists($path)) {
        return response()->file($path, [
            'Content-Type' => 'application/xml; charset=utf-8',
        ]);
    }
    return response('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><url><loc>https://spilms.tech/</loc><lastmod>' . date('Y-m-d') . '</lastmod><changefreq>daily</changefreq><priority>1.0</priority></url></urlset>', 200, [
        'Content-Type' => 'application/xml; charset=utf-8',
    ]);
});

// ─── Robots TXT for Search Engines ───
Route::get('/robots.txt', function () {
    $path = public_path('robots.txt');
    if (file_exists($path)) {
        return response()->file($path, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
    return response("User-agent: *\nAllow: /\nSitemap: https://spilms.tech/sitemap.xml\n", 200, [
        'Content-Type' => 'text/plain; charset=utf-8',
    ]);
});

// ─── Public Certificate Verification ───
Route::get('/verify/{code?}', [CertificateController::class, 'verify']);
Route::get('/certificate/verify/{code?}', [CertificateController::class, 'verify'])->name('certificate.verify');
Route::get('/verify-certificate/{uuid?}', [Student\CertificateController::class, 'publicVerify'])->name('verify-certificate.public');

// ─── Legal & Google OAuth Compliance (Privacy & Terms) ───
Route::get('/privacy', function () {
    return Inertia::render('Privacy');
})->name('privacy');
Route::get('/privacy-policy', function () {
    return redirect()->route('privacy');
});

Route::get('/terms', function () {
    return Inertia::render('Terms');
})->name('terms');
Route::get('/terms-of-service', function () {
    return redirect()->route('terms');
});

// ─── FR: Authentication Module (ទាំង 3 Roles) ───
require __DIR__ . '/auth.php';

Route::middleware(['auth'])->group(function () {

    // Profile Avatar Upload
    Route::post('/user/avatar', [Admin\UserController::class, 'updateAvatar'])->name('user.avatar.update');

    // Redirect តាម Role
    Route::get('/dashboard', function () {
        return match (auth()->user()->role) {
            'admin' => redirect()->route('admin.dashboard'),
            'teacher' => redirect()->route('teacher.dashboard'),
            default => redirect()->route('student.dashboard'),
        };
    })->name('dashboard');

    // Certificate Download
    Route::get('/certificate/download/{certificate}', [CertificateController::class, 'download'])->name('certificate.download');

    // Dashboard Direct API Endpoints
    Route::prefix('api/admin/dashboard')->group(function () {
        Route::get('/summary', [Admin\DashboardController::class, 'apiSummary']);
        Route::get('/kpis', [Admin\DashboardController::class, 'apiKpis']);
        Route::get('/enrollment-chart', [Admin\DashboardController::class, 'apiEnrollmentChart']);
        Route::get('/payment-overview', [Admin\DashboardController::class, 'apiPaymentOverview']);
        Route::get('/students-by-major', [Admin\DashboardController::class, 'apiStudentsByMajor']);
        Route::get('/recent-activities', [Admin\DashboardController::class, 'apiRecentActivities']);
        Route::get('/system-status', [Admin\DashboardController::class, 'apiSystemStatus']);
        Route::get('/alerts', [Admin\DashboardController::class, 'apiAlerts']);
        Route::get('/top-courses', [Admin\DashboardController::class, 'apiTopCourses']);
    });

    // ─── ADMIN (FR: A5 Admin column) ───
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

        // Admin Dashboard API Endpoints
        Route::get('/api/summary', [Admin\DashboardController::class, 'apiSummary']);
        Route::get('/api/kpis', [Admin\DashboardController::class, 'apiKpis']);
        Route::get('/api/enrollment-chart', [Admin\DashboardController::class, 'apiEnrollmentChart']);
        Route::get('/api/payment-overview', [Admin\DashboardController::class, 'apiPaymentOverview']);
        Route::get('/api/students-by-major', [Admin\DashboardController::class, 'apiStudentsByMajor']);
        Route::get('/api/recent-activities', [Admin\DashboardController::class, 'apiRecentActivities']);
        Route::get('/api/system-status', [Admin\DashboardController::class, 'apiSystemStatus']);
        Route::get('/api/alerts', [Admin\DashboardController::class, 'apiAlerts']);
        Route::get('/api/top-courses', [Admin\DashboardController::class, 'apiTopCourses']);
        Route::resource('users', Admin\UserController::class);
        Route::get('user-management', [Admin\UserController::class, 'allUsers'])->name('user-management.all');
        Route::get('user-management/all', [Admin\UserController::class, 'allUsers']);
        Route::get('user-management/administrators', [Admin\UserController::class, 'administrators'])->name('user-management.administrators');
        Route::get('user-management/teachers', [Admin\UserController::class, 'teachers'])->name('user-management.teachers');
        Route::get('user-management/students', [Admin\UserController::class, 'students'])->name('user-management.students');
        Route::get('user-management/suspended', [Admin\UserController::class, 'suspendedUsers'])->name('user-management.suspended');
        Route::get('user-management/import-export', [Admin\UserController::class, 'importExport'])->name('user-management.import-export');

        Route::post('user-management/suspend/{user}', [Admin\UserController::class, 'suspend'])->name('user-management.suspend');
        Route::post('user-management/restore/{user}', [Admin\UserController::class, 'restore'])->name('user-management.restore');
        // ─── Academic Structure Module ───
        Route::get('academic-structure/faculties', [Admin\AcademicStructureController::class, 'faculties'])->name('academic-structure.faculties');
        Route::post('academic-structure/faculties', [Admin\AcademicStructureController::class, 'storeFaculty'])->name('academic-structure.faculties.store');
        Route::put('academic-structure/faculties/{faculty}', [Admin\AcademicStructureController::class, 'updateFaculty'])->name('academic-structure.faculties.update');
        Route::delete('academic-structure/faculties/{faculty}', [Admin\AcademicStructureController::class, 'destroyFaculty'])->name('academic-structure.faculties.destroy');

        Route::get('academic-structure/departments', [Admin\AcademicStructureController::class, 'departments'])->name('academic-structure.departments');
        Route::post('academic-structure/departments', [Admin\AcademicStructureController::class, 'storeDepartment'])->name('academic-structure.departments.store');
        Route::put('academic-structure/departments/{department}', [Admin\AcademicStructureController::class, 'updateDepartment'])->name('academic-structure.departments.update');
        Route::delete('academic-structure/departments/{department}', [Admin\AcademicStructureController::class, 'destroyDepartment'])->name('academic-structure.departments.destroy');

        Route::get('academic-structure/majors', [Admin\AcademicStructureController::class, 'majors'])->name('academic-structure.majors');
        Route::post('academic-structure/majors', [Admin\AcademicStructureController::class, 'storeMajor'])->name('academic-structure.majors.store');
        Route::put('academic-structure/majors/{major}', [Admin\AcademicStructureController::class, 'updateMajor'])->name('academic-structure.majors.update');
        Route::delete('academic-structure/majors/{major}', [Admin\AcademicStructureController::class, 'destroyMajor'])->name('academic-structure.majors.destroy');

        Route::get('academic-structure/academic-years', [Admin\AcademicStructureController::class, 'academicYears'])->name('academic-structure.academic-years');
        Route::post('academic-structure/academic-years', [Admin\AcademicStructureController::class, 'storeAcademicYear'])->name('academic-structure.academic-years.store');
        Route::put('academic-structure/academic-years/{academicYear}', [Admin\AcademicStructureController::class, 'updateAcademicYear'])->name('academic-structure.academic-years.update');
        Route::delete('academic-structure/academic-years/{academicYear}', [Admin\AcademicStructureController::class, 'destroyAcademicYear'])->name('academic-structure.academic-years.destroy');
        Route::post('academic-structure/academic-years/{academicYear}/set-active', [Admin\AcademicStructureController::class, 'setActiveAcademicYear'])->name('academic-structure.academic-years.set-active');

        Route::get('academic-structure/semesters', [Admin\AcademicStructureController::class, 'semesters'])->name('academic-structure.semesters');
        Route::post('academic-structure/semesters', [Admin\AcademicStructureController::class, 'storeSemester'])->name('academic-structure.semesters.store');
        Route::put('academic-structure/semesters/{semester}', [Admin\AcademicStructureController::class, 'updateSemester'])->name('academic-structure.semesters.update');
        Route::delete('academic-structure/semesters/{semester}', [Admin\AcademicStructureController::class, 'destroySemester'])->name('academic-structure.semesters.destroy');
        Route::post('academic-structure/semesters/{semester}/set-active', [Admin\AcademicStructureController::class, 'setActiveSemester'])->name('academic-structure.semesters.set-active');

        Route::get('faculties', [Admin\AcademicStructureController::class, 'faculties'])->name('faculties.index');
        Route::get('departments', [Admin\AcademicStructureController::class, 'departments'])->name('departments.index');
        // Course & Subject Management Module (Modular routes)
        Route::get('course-module/all', [Admin\CourseModuleController::class, 'allCourses'])->name('course-module.all');
        Route::get('course-module/subjects', [Admin\CourseModuleController::class, 'subjects'])->name('course-module.subjects');
        Route::get('course-module/teacher-assignments', [Admin\CourseModuleController::class, 'teacherAssignments'])->name('course-module.teacher-assignments');
        Route::get('course-module/teacher-led', [Admin\CourseModuleController::class, 'teacherLed'])->name('course-module.teacher-led');
        Route::get('course-module/self-study', [Admin\CourseModuleController::class, 'selfStudy'])->name('course-module.self-study');
        Route::get('course-module/free', [Admin\CourseModuleController::class, 'freeCourses'])->name('course-module.free');
        Route::get('course-module/paid', [Admin\CourseModuleController::class, 'paidCourses'])->name('course-module.paid');
        Route::post('course-module/store', [Admin\CourseModuleController::class, 'storeCourse'])->name('course-module.store');
        Route::put('course-module/update/{id}', [Admin\CourseModuleController::class, 'updateCourse'])->name('course-module.update');
        Route::delete('course-module/destroy/{id}', [Admin\CourseModuleController::class, 'destroyCourse'])->name('course-module.destroy');

        Route::post('course-module/subjects/store', [Admin\CourseModuleController::class, 'storeSubject'])->name('course-module.subjects.store');
        Route::put('course-module/subjects/update/{id}', [Admin\CourseModuleController::class, 'updateSubject'])->name('course-module.subjects.update');
        Route::delete('course-module/subjects/destroy/{id}', [Admin\CourseModuleController::class, 'destroySubject'])->name('course-module.subjects.destroy');

        Route::post('course-module/assignments/store', [Admin\CourseModuleController::class, 'storeAssignment'])->name('course-module.assignments.store');
        Route::put('course-module/assignments/update/{id}', [Admin\CourseModuleController::class, 'updateAssignment'])->name('course-module.assignments.update');
        Route::delete('course-module/assignments/destroy/{id}', [Admin\CourseModuleController::class, 'destroyAssignment'])->name('course-module.assignments.destroy');

        // Enrollment Management Module
        Route::get('enrollment/majors', [Admin\EnrollmentController::class, 'majorEnrollments'])->name('enrollment.majors');
        Route::get('enrollment/courses', [Admin\EnrollmentController::class, 'courseEnrollments'])->name('enrollment.courses');
        Route::get('enrollment/single', [Admin\EnrollmentController::class, 'singleEnrollment'])->name('enrollment.single');
        Route::get('enrollment/bulk', [Admin\EnrollmentController::class, 'bulkEnrollment'])->name('enrollment.bulk');
        Route::get('enrollment/history', [Admin\EnrollmentController::class, 'enrollmentHistory'])->name('enrollment.history');

        Route::post('enrollment/majors/store', [Admin\EnrollmentController::class, 'storeMajorEnrollment'])->name('enrollment.majors.store');
        Route::put('enrollment/majors/transfer/{id}', [Admin\EnrollmentController::class, 'transferMajor'])->name('enrollment.majors.transfer');
        Route::delete('enrollment/majors/withdraw/{id}', [Admin\EnrollmentController::class, 'withdrawMajor'])->name('enrollment.majors.withdraw');

        Route::post('enrollment/courses/store', [Admin\EnrollmentController::class, 'storeCourseEnrollment'])->name('enrollment.courses.store');
        Route::put('enrollment/courses/toggle-access/{id}', [Admin\EnrollmentController::class, 'toggleAccess'])->name('enrollment.courses.toggle-access');
        Route::put('enrollment/courses/verify-payment/{id}', [Admin\EnrollmentController::class, 'verifyPayment'])->name('enrollment.courses.verify-payment');
        Route::delete('enrollment/courses/remove/{id}', [Admin\EnrollmentController::class, 'removeCourseEnrollment'])->name('enrollment.courses.remove');

        Route::post('enrollment/single/store', [Admin\EnrollmentController::class, 'storeSingleEnrollment'])->name('enrollment.single.store');
        Route::post('enrollment/history/reverse/{id}', [Admin\EnrollmentController::class, 'reverseHistoryAction'])->name('enrollment.history.reverse');

        Route::get('courses', [Admin\CourseModuleController::class, 'allCourses'])->name('courses.index');
        Route::get('subjects', [Admin\CourseModuleController::class, 'subjects'])->name('subjects.index');
        Route::get('payments', [Admin\PaymentController::class, 'index'])->name('payments.index');
        Route::post('payments/{payment}/verify', [Admin\PaymentController::class, 'verify'])->name('payments.verify');
        Route::post('payments/{payment}/reject', [Admin\PaymentController::class, 'reject'])->name('payments.reject');
        Route::get('reports', [Admin\ReportController::class, 'index'])->name('reports');
        Route::get('reports/financials', [Admin\ReportController::class, 'exportFinancials'])->name('reports.financials');
        Route::get('reports/enrollments', function () {
            return redirect()->route('enrollment.majors');
        })->name('reports.enrollments');
        Route::get('settings', [Admin\SettingController::class, 'index'])->name('settings');
        Route::post('settings', [Admin\SettingController::class, 'update'])->name('settings.update');
        Route::post('settings/test-smtp', [Admin\SettingController::class, 'testSmtp'])->name('settings.test-smtp');
        Route::post('settings/test-s3', [Admin\SettingController::class, 'testS3'])->name('settings.test-s3');
        Route::post('settings/test-aba', [Admin\SettingController::class, 'testAba'])->name('settings.test-aba');
        Route::post('settings/test-reverb', [Admin\SettingController::class, 'testReverb'])->name('settings.test-reverb');
        Route::post('settings/purge-cdn', [Admin\SettingController::class, 'purgeCdn'])->name('settings.purge-cdn');
        Route::post('settings/run-backup', [Admin\SettingController::class, 'runBackup'])->name('settings.run-backup');
        Route::post('settings/restore-backup', [Admin\SettingController::class, 'restoreBackup'])->name('settings.restore-backup');
        Route::post('settings/clear-logs', [Admin\SettingController::class, 'clearLogs'])->name('settings.clear-logs');

        // Authentication Module (Modular routes)
        Route::get('auth-logs', [Admin\AuthLogController::class, 'index'])->name('auth-logs');
        Route::get('auth/roles', [Admin\AuthLogController::class, 'rolesPermissions'])->name('auth.roles');
        Route::get('auth/sessions', [Admin\AuthLogController::class, 'activeSessions'])->name('auth.sessions');
        Route::get('auth/history', [Admin\AuthLogController::class, 'loginHistory'])->name('auth.history');
        Route::get('auth/failed', [Admin\AuthLogController::class, 'failedAttempts'])->name('auth.failed');
        Route::get('auth/policies', [Admin\AuthLogController::class, 'securityPolicies'])->name('auth.policies');

        Route::post('auth-logs/revoke/{id}', [Admin\AuthLogController::class, 'revokeSession'])->name('auth-logs.revoke');
        Route::post('auth-logs/revoke-all', [Admin\AuthLogController::class, 'revokeAllSessions'])->name('auth-logs.revoke-all');
        Route::post('auth-logs/unlock/{user}', [Admin\AuthLogController::class, 'unlockUser'])->name('auth-logs.unlock');
        Route::post('auth-logs/block-ip', [Admin\AuthLogController::class, 'blockIp'])->name('auth-logs.block-ip');
        Route::post('auth-logs/policies', [Admin\AuthLogController::class, 'updatePolicies'])->name('auth-logs.policies');
        Route::post('auth-logs/permissions', [Admin\AuthLogController::class, 'updatePermissions'])->name('auth-logs.permissions');
        Route::post('auth-logs/roles/create', [Admin\AuthLogController::class, 'createRole'])->name('auth-logs.roles.create');
        Route::post('auth-logs/roles/delete', [Admin\AuthLogController::class, 'deleteRole'])->name('auth-logs.roles.delete');
        Route::post('auth-logs/roles/update', [Admin\AuthLogController::class, 'updateRole'])->name('auth-logs.roles.update');
        Route::post('auth-logs/unblock-ip', [Admin\AuthLogController::class, 'unblockIp'])->name('auth-logs.unblock-ip');
        Route::post('auth-logs/clear-logs', [Admin\AuthLogController::class, 'clearLogs'])->name('auth-logs.clear-logs');
        Route::post('auth-logs/ban-all-suspicious', [Admin\AuthLogController::class, 'banAllSuspicious'])->name('auth-logs.ban-all-suspicious');
        Route::get('content', [Admin\ContentController::class, 'index'])->name('content');
        Route::get('content-delivery', [Admin\ContentController::class, 'index'])->name('content-delivery');
        Route::post('content/store', [Admin\ContentController::class, 'store'])->name('content.store');
        Route::put('content/update/{id}', [Admin\ContentController::class, 'update'])->name('content.update');
        Route::delete('content/destroy/{id}', [Admin\ContentController::class, 'destroy'])->name('content.destroy');
        Route::post('content/module/store', [Admin\ContentController::class, 'storeModule'])->name('content.module.store');
        Route::post('content/offline-package/store', [Admin\ContentController::class, 'storeOfflinePackage'])->name('content.offline-package.store');
        Route::post('content/process-uploaded-slide', [Admin\ContentController::class, 'processUploadedSlide'])->name('content.process-uploaded-slide');
        Route::post('content/upload-slide', [Admin\ContentController::class, 'processUploadedSlide'])->name('content.upload-slide');
        Route::post('content/ai-translate', [Admin\ContentController::class, 'aiTranslate'])->name('content.ai-translate');
        Route::get('quizzes', [Admin\QuizController::class, 'index'])->name('quizzes');
        Route::post('quizzes/question/store', [Admin\QuizController::class, 'storeQuestion'])->name('quizzes.question.store');
        Route::post('quizzes/store', [Admin\QuizController::class, 'storeQuiz'])->name('quizzes.store');
        Route::post('quizzes/assignment/store', [Admin\QuizController::class, 'storeAssignment'])->name('quizzes.assignment.store');
        Route::post('quizzes/grade-submission/{id}', [Admin\QuizController::class, 'gradeSubmission'])->name('quizzes.grade');
        Route::delete('quizzes/{quiz}', [Admin\QuizController::class, 'destroy'])->name('quizzes.destroy');
        Route::get('progress', [Admin\ProgressController::class, 'index'])->name('progress');
        Route::get('ai-rules', [Admin\AiRuleController::class, 'index'])->name('ai-rules');
        Route::post('ai-rules/update', [Admin\AiRuleController::class, 'update'])->name('ai-rules.update');
        Route::post('ai-rules/evaluate', [Admin\AiRuleController::class, 'evaluateRules'])->name('ai-rules.evaluate');
        Route::get('certificates', [Admin\CertificateController::class, 'index'])->name('certificates');
        Route::get('certificates/templates', [Admin\CertificateController::class, 'templates'])->name('certificates.templates');
        Route::get('certificates/issue', [Admin\CertificateController::class, 'issueView'])->name('certificates.issue');
        Route::get('certificates/issued', [Admin\CertificateController::class, 'index'])->name('certificates.issued');
        Route::get('certificates/verify', [Admin\CertificateController::class, 'verifyView'])->name('certificates.verify');
        Route::get('certificates/revoked', [Admin\CertificateController::class, 'revokedView'])->name('certificates.revoked');
        Route::post('certificates/template', [Admin\CertificateController::class, 'storeTemplate'])->name('certificates.template.store');
        Route::put('certificates/template/{id}', [Admin\CertificateController::class, 'updateTemplate'])->name('certificates.template.update');
        Route::post('certificates/template/{id}/duplicate', [Admin\CertificateController::class, 'duplicateTemplate'])->name('certificates.template.duplicate');
        Route::post('certificates/issue-single', [Admin\CertificateController::class, 'issueSingle'])->name('certificates.issue-single');
        Route::post('certificates/issue-bulk', [Admin\CertificateController::class, 'issueBulk'])->name('certificates.issue-bulk');
        Route::post('certificates/quick-verify', [Admin\CertificateController::class, 'quickVerify'])->name('certificates.quick-verify');
        Route::post('certificates/revoke/{id}', [Admin\CertificateController::class, 'requestRevocation'])->name('certificates.revoke');
        Route::post('certificates/restore/{id}', [Admin\CertificateController::class, 'restoreCertificate'])->name('certificates.restore');
        Route::get('notifications', [Admin\NotificationController::class, 'announcements'])->name('notifications');
        Route::get('notifications/announcements', [Admin\NotificationController::class, 'announcements'])->name('notifications.announcements');
        Route::get('notifications/emails', [Admin\NotificationController::class, 'emails'])->name('notifications.emails');
        Route::get('notifications/push', [Admin\NotificationController::class, 'push'])->name('notifications.push');
        Route::get('notifications/scheduled', [Admin\NotificationController::class, 'scheduled'])->name('notifications.scheduled');
        Route::get('notifications/history', [Admin\NotificationController::class, 'history'])->name('notifications.history');
        Route::post('notifications/announcement', [Admin\NotificationController::class, 'storeAnnouncement'])->name('notifications.announcement.store');
        Route::post('notifications/push', [Admin\NotificationController::class, 'storePush'])->name('notifications.push.store');
        Route::get('discussions', [Admin\DiscussionController::class, 'discussions'])->name('discussions');
        Route::get('discussions/board', [Admin\DiscussionController::class, 'discussions'])->name('discussions.board');
        Route::get('discussions/questions', [Admin\DiscussionController::class, 'questions'])->name('discussions.questions');
        Route::get('discussions/tickets', [Admin\DiscussionController::class, 'tickets'])->name('discussions.tickets');
        Route::get('discussions/reports', [Admin\DiscussionController::class, 'reports'])->name('discussions.reports');
        Route::get('calendar', [Admin\CalendarController::class, 'index'])->name('calendar');
    });

    // ─── TEACHER (FR: A5 Teacher column) ───
    Route::middleware('role:teacher')->prefix('teacher')->name('teacher.')->group(function () {
        Route::get('/dashboard', [Teacher\DashboardController::class, 'index'])->name('dashboard');

        // Course Management
        Route::get('courses/drafts', [Teacher\CourseController::class, 'drafts'])->name('courses.drafts');
        Route::get('courses/draft', [Teacher\CourseController::class, 'drafts'])->name('courses.draft');
        Route::get('courses/pending', [Teacher\CourseController::class, 'pending'])->name('courses.pending');
        Route::get('courses/published', [Teacher\CourseController::class, 'published'])->name('courses.published');
        Route::get('courses/settings', [Teacher\CourseController::class, 'settings'])->name('courses.settings');
        Route::get('courses/{course}/settings', [Teacher\CourseController::class, 'settings'])->name('courses.single-settings');
        Route::get('courses/{course}/workspace', [Teacher\CourseController::class, 'workspace'])->name('courses.workspace');
        Route::resource('courses', Teacher\CourseController::class);
        Route::post('courses/{course}/submit', [Teacher\CourseController::class, 'submitForApproval'])->name('courses.submit');
        Route::post('courses/{course}/submit-approval', [Teacher\CourseController::class, 'submitForApproval'])->name('courses.submit-approval');
        Route::post('courses/{course}/withdraw', [Teacher\CourseController::class, 'withdraw'])->name('courses.withdraw');
        Route::post('courses/{course}/publish', [Teacher\CourseController::class, 'publish'])->name('courses.publish');
        Route::patch('courses/{course}/publish', [Teacher\CourseController::class, 'publish'])->name('courses.patch-publish');
        Route::post('courses/{course}/unpublish', [Teacher\CourseController::class, 'unpublish'])->name('courses.unpublish');
        Route::get('courses/{course}/completeness', [Teacher\CourseController::class, 'checkCompleteness'])->name('courses.completeness');
        Route::post('courses/{course}/clone', [Teacher\CourseController::class, 'cloneCourse'])->name('courses.clone');
        Route::post('courses/{course}/pause', [Teacher\CourseController::class, 'pauseCourse'])->name('courses.pause');
        Route::post('courses/{course}/archive', [Teacher\CourseController::class, 'archiveCourse'])->name('courses.archive');
        Route::post('courses/{course}/request-fee-change', [Teacher\CourseController::class, 'requestFeeChange'])->name('courses.request-fee-change');

        // AI Assistance Endpoints
        Route::post('ai/generate-outline', [Teacher\CourseController::class, 'aiGenerateOutline'])->name('ai.generate-outline');
        Route::post('ai/generate-lesson', [Teacher\CourseController::class, 'aiGenerateLesson'])->name('ai.generate-lesson');
        Route::post('ai/generate-quiz', [Teacher\CourseController::class, 'aiGenerateQuiz'])->name('ai.generate-quiz');
        Route::post('ai/translate', [Teacher\CourseController::class, 'aiTranslate'])->name('ai.translate');
        Route::post('ai/suggest-price', [Teacher\CourseController::class, 'aiSuggestPrice'])->name('ai.suggest-price');

        // Modules & Lessons Hierarchy
        Route::resource('courses.modules', Teacher\ModuleController::class)->shallow();
        Route::post('courses/{course}/modules/reorder', [Teacher\ModuleController::class, 'reorder'])->name('modules.reorder');
        Route::resource('modules.lessons', Teacher\LessonController::class)->shallow();
        Route::post('modules/{module}/lessons/reorder', [Teacher\LessonController::class, 'reorder'])->name('lessons.reorder');

        // Content Delivery Module
        Route::get('content', [Teacher\ContentController::class, 'index'])->name('content.index');
        Route::get('content-delivery', [Teacher\ContentController::class, 'index'])->name('content.delivery');
        Route::get('courses/{course}/content', [Teacher\ContentController::class, 'index'])->name('courses.content');
        Route::get('content/videos', [Teacher\ContentController::class, 'index'])->defaults('tab', 'videos')->name('content.tab.videos');
        Route::get('content/pdfs', [Teacher\ContentController::class, 'index'])->defaults('tab', 'pdfs')->name('content.tab.pdfs');
        Route::get('content/slides', [Teacher\ContentController::class, 'index'])->defaults('tab', 'slides')->name('content.tab.slides');
        Route::get('content/modules', [Teacher\ContentController::class, 'index'])->defaults('tab', 'modules')->name('content.tab.modules');
        Route::get('content/notes', [Teacher\ContentController::class, 'index'])->defaults('tab', 'notes')->name('content.tab.notes');
        Route::get('content/ai-content', [Teacher\ContentController::class, 'index'])->defaults('tab', 'ai-content')->name('content.tab.ai-content');
        Route::get('content/coding-lab', [Teacher\ContentController::class, 'index'])->defaults('tab', 'coding-lab')->name('content.tab.coding-lab');
        Route::get('content/practice-lab', [Teacher\ContentController::class, 'index'])->defaults('tab', 'coding-lab')->name('content.tab.practice-lab');
        Route::get('courses/{course}/modules', [Teacher\ContentController::class, 'getModules'])->name('content.get-modules');
        Route::post('courses/{course}/modules', [Teacher\ContentController::class, 'storeModule'])->name('content.store-module');
        Route::put('courses/modules/{module}/reorder', [Teacher\ContentController::class, 'reorderModules'])->name('content.modules.reorder');
        Route::post('courses/{course}/modules/reorder', [Teacher\ContentController::class, 'reorderModules'])->name('content.courses.modules.reorder');
        Route::get('modules/{module}/chapters', [Teacher\ContentController::class, 'getChapters'])->name('content.get-chapters');
        Route::post('modules/{module}/chapters', [Teacher\ContentController::class, 'storeChapter'])->name('content.store-chapter');
        Route::post('chapters/{chapter}/contents', [Teacher\ContentController::class, 'storeChapterContent'])->name('content.store-chapter-content');
        Route::post('contents/{content}/generate-ai-summary', [Teacher\ContentController::class, 'generateAiSummary'])->name('content.generate-ai-summary');
        Route::patch('contents/reorder', [Teacher\ContentController::class, 'reorderContents'])->name('content.reorder');

        // 1. Videos
        Route::post('courses/{course}/videos', [Teacher\ContentController::class, 'storeVideo'])->name('content.courses.videos.store');
        Route::delete('courses/videos/{video}', [Teacher\ContentController::class, 'destroyVideo'])->name('content.videos.destroy');
        Route::post('courses/videos/{video}/status', [Teacher\ContentController::class, 'updateVideoStatus'])->name('content.videos.status');
        Route::post('content/upload-video', [Teacher\ContentController::class, 'uploadVideo'])->name('content.upload-video');

        // 2. PDFs & Materials
        Route::post('courses/{course}/materials', [Teacher\ContentController::class, 'storeMaterial'])->name('content.courses.materials.store');
        Route::post('courses/{course}/pdfs', [Teacher\ContentController::class, 'storePdf'])->name('content.courses.pdfs.store');
        Route::post('content/upload-pdf', [Teacher\ContentController::class, 'uploadPdf'])->name('content.upload-pdf');

        // 3. Slides
        Route::post('courses/{course}/slides', [Teacher\ContentController::class, 'storeSlide'])->name('content.courses.slides.store');
        Route::post('content/upload-slide', [Teacher\ContentController::class, 'uploadSlide'])->name('content.upload-slide');

        // 5. Notes & Downloads
        Route::post('courses/{course}/downloads', [Teacher\ContentController::class, 'storeDownload'])->name('content.courses.downloads.store');
        Route::post('content/upload-note', [Teacher\ContentController::class, 'uploadNote'])->name('content.upload-note');
        Route::delete('courses/materials/{material}', [Teacher\ContentController::class, 'destroyMaterial'])->name('content.materials.destroy');

        // 6. AI-Assisted Content Endpoints
        Route::post('courses/{course}/ai-content', [Teacher\ContentController::class, 'storeAiContent'])->name('content.courses.ai-content.store');
        Route::post('courses/{course}/ai/generate-quiz', [Teacher\ContentController::class, 'aiGenerateQuizContent'])->name('content.ai.generate-quiz');
        Route::post('courses/{course}/ai/summarize', [Teacher\ContentController::class, 'aiSummarizeContent'])->name('content.ai.summarize');
        Route::post('courses/{course}/ai/flashcards', [Teacher\ContentController::class, 'aiFlashcardsContent'])->name('content.ai.flashcards');
        Route::post('courses/ai/{aiContent}/approve', [Teacher\ContentController::class, 'approveAiContent'])->name('content.ai.approve');
        Route::delete('courses/ai/{aiContent}', [Teacher\ContentController::class, 'destroyAiContent'])->name('content.ai.destroy');

        // 7. Practice Lab Endpoints
        Route::post('courses/{course}/labs', [Teacher\ContentController::class, 'storeLabDirect'])->name('content.courses.labs.store');
        Route::post('courses/{course}/lessons/{lesson}/lab', [Teacher\ContentController::class, 'storeLabIntegration'])->name('content.courses.lessons.lab.store');
        Route::delete('courses/labs/{lab}', [Teacher\ContentController::class, 'destroyLabIntegration'])->name('content.labs.destroy');
        Route::post('content/upload-lab', [Teacher\ContentController::class, 'uploadLab'])->name('content.upload-lab');

        // Quiz & Assessment Module
        Route::get('question-bank', [Teacher\QuizController::class, 'questionBankIndex'])->name('question-bank.index');
        Route::post('question-bank', [Teacher\QuizController::class, 'storeQuestionBank'])->name('question-bank.store');
        Route::resource('courses.quizzes', Teacher\QuizController::class)->shallow();
        Route::resource('quizzes.questions', Teacher\QuestionController::class)->shallow();
        Route::get('quizzes', [Teacher\QuizController::class, 'globalIndex'])->name('quizzes.index');
        Route::get('assessment', [Teacher\QuizController::class, 'globalIndex'])->name('assessment.index');
        Route::post('quizzes/store', [Teacher\QuizController::class, 'store'])->name('quizzes.store');
        Route::post('quizzes/{quiz}/allow-retake', [Teacher\QuizController::class, 'allowRetake'])->name('quizzes.allow-retake');

        // Sub-routes for Pre-test, Practice, Post-test, Assignments, Coding, and Results
        Route::get('courses/{course}/quizzes', [Teacher\QuizController::class, 'courseQuizzes'])->name('courses.quizzes.index');
        Route::get('courses/{course}/pretest', [Teacher\QuizController::class, 'preTestIndex'])->name('courses.pretest.index');
        Route::post('courses/{course}/pretest', [Teacher\QuizController::class, 'storePreTest'])->name('courses.pretest.store');
        Route::get('courses/{course}/practice-quiz', [Teacher\QuizController::class, 'practiceQuizIndex'])->name('courses.practice-quiz.index');
        Route::post('courses/{course}/practice-quiz', [Teacher\QuizController::class, 'storePracticeQuiz'])->name('courses.practice-quiz.store');
        Route::get('courses/{course}/posttest', [Teacher\QuizController::class, 'postTestIndex'])->name('courses.posttest.index');
        Route::post('courses/{course}/posttest', [Teacher\QuizController::class, 'storePostTest'])->name('courses.posttest.store');
        Route::get('courses/{course}/assignments', [Teacher\QuizController::class, 'assignmentsIndex'])->name('courses.assignments.index');
        Route::post('courses/{course}/assignments', [Teacher\QuizController::class, 'storeAssignment'])->name('courses.assignments.store');
        Route::get('courses/{course}/coding-assessments', [Teacher\QuizController::class, 'codingAssessmentsIndex'])->name('courses.coding-assessments.index');
        Route::post('courses/{course}/coding-assessments', [Teacher\QuizController::class, 'storeCodingAssessment'])->name('courses.coding-assessments.store');
        Route::get('courses/{course}/quiz-results', [Teacher\QuizController::class, 'quizResultsIndex'])->name('courses.quiz-results.index');
        Route::get('courses/{course}/quiz-results/export', [Teacher\QuizController::class, 'exportQuizResults'])->name('courses.quiz-results.export');
        Route::get('quiz-results/export', [Teacher\QuizController::class, 'exportQuizResults'])->name('quiz-results.export');

        // Students Module
        Route::get('students', [Teacher\StudentController::class, 'index'])->name('students.index');
        Route::post('students/{student}/feedback', [Teacher\StudentController::class, 'sendFeedback'])->name('students.feedback');
        Route::post('students/{student}/toggle-at-risk', [Teacher\StudentController::class, 'toggleAtRisk'])->name('students.toggle-at-risk');

        // Progress Tracking Module
        Route::get('progress', [Teacher\ProgressController::class, 'index'])->name('progress.index');
        Route::post('progress/send-reminder', [Teacher\ProgressController::class, 'sendReminder'])->name('progress.send-reminder');

        // Reports Module
        Route::get('reports', [Teacher\ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export-student/{id}', [Teacher\ReportController::class, 'exportStudent'])->name('reports.export-student');
        Route::get('reports/export-course/{id}', [Teacher\ReportController::class, 'exportCourse'])->name('reports.export-course');

        // Discussion & Announcements
        Route::get('discussions', [Teacher\DiscussionController::class, 'index'])->name('discussions.index');
        Route::get('discussion', [Teacher\DiscussionController::class, 'index'])->name('discussion.index');
        Route::post('discussions/answer/{id}', [Teacher\DiscussionController::class, 'answerQuestion'])->name('discussions.answer');
        Route::post('discussions/announcement', [Teacher\DiscussionController::class, 'storeAnnouncement'])->name('discussions.announcement');

        // Calendar
        Route::get('calendar', [Teacher\CalendarController::class, 'index'])->name('calendar.index');
        Route::post('calendar/event', [Teacher\CalendarController::class, 'storeEvent'])->name('calendar.event');
        Route::post('calendar/schedule', [Teacher\CalendarController::class, 'storeSchedule'])->name('calendar.schedule.store');
        Route::put('calendar/schedule/{schedule}', [Teacher\CalendarController::class, 'updateSchedule'])->name('calendar.schedule.update');
        Route::delete('calendar/schedule/{schedule}', [Teacher\CalendarController::class, 'destroySchedule'])->name('calendar.schedule.destroy');
        Route::post('calendar/schedule/{schedule}/lobby', [Teacher\CalendarController::class, 'joinLobby'])->name('calendar.schedule.lobby');
        Route::post('calendar/deadline', [Teacher\CalendarController::class, 'storeDeadline'])->name('calendar.deadline.store');
        Route::put('calendar/deadline/{deadline}', [Teacher\CalendarController::class, 'updateDeadline'])->name('calendar.deadline.update');
        Route::delete('calendar/deadline/{deadline}', [Teacher\CalendarController::class, 'destroyDeadline'])->name('calendar.deadline.destroy');
        Route::post('calendar/deadline/{deadline}/extend', [Teacher\CalendarController::class, 'extendDeadline'])->name('calendar.deadline.extend');
        Route::post('calendar/deadline/{deadline}/remind', [Teacher\CalendarController::class, 'remindDeadline'])->name('calendar.deadline.remind');
        Route::post('calendar/deadlines/bulk-extend', [Teacher\CalendarController::class, 'bulkExtendDeadlines'])->name('calendar.deadlines.bulk-extend');
        Route::post('calendar/deadlines/bulk-remind', [Teacher\CalendarController::class, 'bulkRemindDeadlines'])->name('calendar.deadlines.bulk-remind');
        Route::post('calendar/sync-google', [Teacher\CalendarController::class, 'syncGoogle'])->name('calendar.sync-google');

        // Profile
        Route::get('profile', [Teacher\ProfileController::class, 'index'])->name('profile.index');
        Route::post('profile/update', [Teacher\ProfileController::class, 'update'])->name('profile.update');
        Route::post('profile/password', [Teacher\ProfileController::class, 'updatePassword'])->name('profile.password');

        // Earnings / Payments
        Route::get('earnings', [Teacher\EarningsController::class, 'index'])->name('earnings.index');
        Route::post('earnings/request-payout', [Teacher\EarningsController::class, 'requestPayout'])->name('earnings.request-payout');

        // Notifications
        Route::get('notifications', function () {
            return Inertia::render('Teacher/Notifications/Index');
        })->name('notifications.index');
    });

    // ─── STUDENT (FR: A5 Student column) ───
    Route::middleware('role:student')->prefix('student')->name('student.')->group(function () {
        Route::get('/dashboard', [Student\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/browse', [Student\BrowseController::class, 'index'])->name('browse');
        Route::get('/browse/{course}', [Student\BrowseController::class, 'show'])->name('browse.show');
        Route::get('/courses', [Student\CourseController::class, 'index'])->name('courses');
        Route::get('/courses/enrolled', [Student\CourseController::class, 'enrolled']);
        Route::get('/courses/current', [Student\CourseController::class, 'current']);
        Route::get('/courses/completed', [Student\CourseController::class, 'completed']);

        // My Courses sub-routes
        Route::prefix('my-courses')->name('my-courses.')->group(function () {
            Route::get('/', [Student\CourseController::class, 'enrolled'])->name('index');
            Route::get('/enrolled', [Student\CourseController::class, 'enrolled'])->name('enrolled');
            Route::get('/current', [Student\CourseController::class, 'current'])->name('current');
            Route::get('/completed', [Student\CourseController::class, 'completed'])->name('completed');
            Route::get('/browse', [Student\BrowseController::class, 'index'])->name('browse');
            Route::get('/wishlist', [Student\CourseController::class, 'wishlist'])->name('wishlist');
        });

        // URL Aliases for space-separated or underscore-separated requests (e.g. 'my courses' / 'my_courses')
        Route::get('/my courses', [Student\CourseController::class, 'enrolled']);
        Route::get('/my courses/enrolled', [Student\CourseController::class, 'enrolled']);
        Route::get('/my courses/current', [Student\CourseController::class, 'current']);
        Route::get('/my courses/completed', [Student\CourseController::class, 'completed']);
        Route::get('/my courses/browse', [Student\BrowseController::class, 'index']);
        Route::get('/my courses/wishlist', [Student\CourseController::class, 'wishlist']);

        Route::get('/my_courses', [Student\CourseController::class, 'enrolled']);
        Route::get('/my_courses/enrolled', [Student\CourseController::class, 'enrolled']);
        Route::get('/my_courses/current', [Student\CourseController::class, 'current']);
        Route::get('/my_courses/completed', [Student\CourseController::class, 'completed']);
        Route::get('/my_courses/browse', [Student\BrowseController::class, 'index']);
        Route::get('/my_courses/wishlist', [Student\CourseController::class, 'wishlist']);

        Route::get('/content', [Student\LearningController::class, 'content'])->name('content');
        Route::prefix('learning-content')->name('learning-content.')->group(function () {
            Route::get('/videos', [Student\LearningController::class, 'videos'])->name('videos');
            Route::get('/pdfs', [Student\LearningController::class, 'pdfs'])->name('pdfs');
            Route::get('/slides', [Student\LearningController::class, 'slides'])->name('slides');
            Route::get('/notes', [Student\LearningController::class, 'notes'])->name('notes');
            Route::get('/links', [Student\LearningController::class, 'links'])->name('links');
        });

        Route::post('/courses/{course}/enroll', [Student\EnrollmentController::class, 'store'])->name('enroll');
        Route::get('/learn/{course}', [Student\LearningController::class, 'show'])->name('learn');
        Route::post('/learn/progress/{lesson}', [Student\LearningController::class, 'updateProgress'])->name('learn.progress');
        Route::post('/learn/ai-tutor/{lesson}', [Student\LearningController::class, 'askAi'])->name('learn.ai-tutor');
        Route::post('/learn/discussion/{lesson}', [Student\LearningController::class, 'postDiscussion'])->name('learn.discussion');
        Route::post('/learn/notes/{lesson}', [Student\LearningController::class, 'saveNote'])->name('learn.notes');
        Route::get('/quizzes', [Student\QuizController::class, 'index'])->name('quizzes');
        Route::prefix('quizzes')->name('quizzes.')->group(function () {
            Route::get('/pre-test', [Student\QuizController::class, 'preTest'])->name('pre-test');
            Route::get('/practice', [Student\QuizController::class, 'practice'])->name('practice');
            Route::get('/post-test', [Student\QuizController::class, 'postTest'])->name('post-test');
            Route::get('/assignments', [Student\QuizController::class, 'assignments'])->name('assignments');
            Route::get('/history', [Student\QuizController::class, 'history'])->name('history');
            Route::get('/scores', [Student\QuizController::class, 'scores'])->name('scores');
        });

        Route::get('/quiz/{quiz}', [Student\QuizController::class, 'show'])->name('quiz');
        Route::post('/quiz/{quiz}/submit', [Student\QuizController::class, 'submit'])->name('quiz.submit');
        Route::get('/payments', [Student\PaymentController::class, 'index'])->name('payments');
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/my-payments', [Student\PaymentController::class, 'myPayments'])->name('my-payments');
            Route::get('/pending', [Student\PaymentController::class, 'pending'])->name('pending');
            Route::get('/history', [Student\PaymentController::class, 'history'])->name('history');
            Route::get('/receipts', [Student\PaymentController::class, 'receipts'])->name('receipts');
        });
        Route::get('/payments/{course}/upload', [Student\PaymentController::class, 'create'])->name('payments.create');
        Route::post('/payments/{course}', [Student\PaymentController::class, 'store'])->name('payments.store');
        Route::get('/ai-path', [Student\AiPathController::class, 'index'])->name('ai-path');
        Route::prefix('ai-path')->name('ai-path.')->group(function () {
            Route::get('/recommended', [Student\AiPathController::class, 'recommended'])->name('recommended');
            Route::get('/review', [Student\AiPathController::class, 'review'])->name('review');
            Route::get('/weak-topics', [Student\AiPathController::class, 'weakTopics'])->name('weak-topics');
            Route::get('/next-module', [Student\AiPathController::class, 'nextModule'])->name('next-module');
            Route::get('/next-course', [Student\AiPathController::class, 'nextCourse'])->name('next-course');
        });

        // 6. AI Assistant / Tutor
        Route::get('/ai-tutor', [Student\AiTutorController::class, 'index'])->name('ai-tutor');
        Route::prefix('ai-tutor')->name('ai-tutor.')->group(function () {
            Route::get('/english', [Student\AiTutorController::class, 'english'])->name('english');
            Route::get('/chat', [Student\AiTutorController::class, 'chat'])->name('chat');
            Route::get('/feedback', [Student\AiTutorController::class, 'feedback'])->name('feedback');
        });

        // 7. Practice Lab (dynamic 5 Majors)
        Route::get('/practice-lab', [Student\PracticeLabController::class, 'index'])->name('practice-lab');
        Route::prefix('practice-lab')->name('practice-lab.')->group(function () {
            Route::get('/it', [Student\PracticeLabController::class, 'it'])->name('it');
            Route::get('/tourism', [Student\PracticeLabController::class, 'tourism'])->name('tourism');
            Route::get('/english', [Student\PracticeLabController::class, 'english'])->name('english');
            Route::get('/agronomy', [Student\PracticeLabController::class, 'agronomy'])->name('agronomy');
            Route::get('/social-work', [Student\PracticeLabController::class, 'social-work'])->name('social-work');
        });

        Route::get('/progress', [Student\ProgressController::class, 'index'])->name('progress');
        Route::prefix('progress')->name('progress.')->group(function () {
            Route::get('/overview', [Student\ProgressController::class, 'overview'])->name('overview');
            Route::get('/learning-time', [Student\ProgressController::class, 'learningTime'])->name('learning-time');
            Route::get('/weekly', [Student\ProgressController::class, 'weeklyProgress'])->name('weekly');
            Route::get('/achievements', [Student\ProgressController::class, 'achievementsBadges'])->name('achievements');
        });

        Route::get('/certificates', [Student\CertificateController::class, 'index'])->name('certificates');
        Route::prefix('certificates')->name('certificates.')->group(function () {
            Route::get('/my-certificates', [Student\CertificateController::class, 'myCertificates'])->name('my-certificates');
            Route::get('/download-share', [Student\CertificateController::class, 'downloadShare'])->name('download-share');
            Route::get('/verify', [Student\CertificateController::class, 'verify'])->name('verify');
        });

        Route::get('/discussions', [Student\DiscussionController::class, 'index'])->name('discussions');
        Route::prefix('discussions')->name('discussions.')->group(function () {
            Route::get('/ask', [Student\DiscussionController::class, 'ask'])->name('ask');
            Route::get('/my-questions', [Student\DiscussionController::class, 'myQuestions'])->name('my-questions');
            Route::get('/course-discussions', [Student\DiscussionController::class, 'courseDiscussions'])->name('course-discussions');
            Route::get('/faq', [Student\DiscussionController::class, 'faq'])->name('faq');
            Route::get('/tickets', [Student\DiscussionController::class, 'tickets'])->name('tickets');
        });

        Route::get('/notifications', [Student\NotificationController::class, 'index'])->name('notifications');
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/announcements', [Student\NotificationController::class, 'announcements'])->name('announcements');
            Route::get('/ai-alerts', [Student\NotificationController::class, 'aiAlerts'])->name('ai-alerts');
            Route::get('/feedback', [Student\NotificationController::class, 'teacherFeedback'])->name('feedback');
            Route::get('/payment-alerts', [Student\NotificationController::class, 'paymentAlerts'])->name('payment-alerts');
        });

        Route::get('/calendar', [Student\CalendarController::class, 'index'])->name('calendar');
        Route::prefix('calendar')->name('calendar.')->group(function () {
            Route::get('/live-class', [Student\CalendarController::class, 'liveClassSchedule'])->name('live-class');
            Route::get('/upcoming-quiz', [Student\CalendarController::class, 'upcomingQuiz'])->name('upcoming-quiz');
            Route::get('/assignment-due', [Student\CalendarController::class, 'assignmentDue'])->name('assignment-due');
            Route::get('/academic-events', [Student\CalendarController::class, 'academicEvents'])->name('academic-events');
        });

        // 13. Profile Settings
        Route::get('/profile', [Student\ProfileController::class, 'index'])->name('profile');
        Route::post('/profile', [Student\ProfileController::class, 'update'])->name('profile.update');
        Route::post('/profile/password', [Student\ProfileController::class, 'updatePassword'])->name('profile.password');

        // 14. 24/7 AI Academic Tutor Endpoint
        Route::post('/api/ai-tutor/chat', [Student\AiTutorController::class, 'askTutor'])->name('ai.tutor.chat');
    });
});
