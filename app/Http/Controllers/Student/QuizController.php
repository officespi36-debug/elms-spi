<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AssignmentSubmission;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\AssessmentService;
use App\Services\AvailableQuizService;
use App\Services\CertificateService;
use App\Services\CourseProgressService;
use App\Services\DashboardAnalyticsService;
use App\Services\GradingService;
use App\Services\QuizAttemptHistoryService;
use App\Services\QuizResultService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class QuizController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tab = $request->query('tab', 'pretest');

        $preTestData = $this->buildTypedQuizzesPayload($user, 'pre_test');
        $postTestData = $this->buildTypedQuizzesPayload($user, 'post_test');
        $practiceData = app(AvailableQuizService::class)->getAvailableQuizzesData($user);
        $assignmentsData = app(AssessmentService::class)->getAssessmentsData($user);
        $historyData = app(QuizAttemptHistoryService::class)->getAttemptsHistoryData($user);
        $scoresData = app(QuizResultService::class)->getQuizResultsData($user);

        return Inertia::render('Student/Quizzes/Index', [
            'activeTab'       => $tab,
            'preTestData'     => $preTestData,
            'postTestData'    => $postTestData,
            'practiceData'    => $practiceData,
            'assignmentsData' => $assignmentsData,
            'historyData'     => $historyData,
            'scoresData'      => $scoresData,
        ]);
    }

    public function preTest(Request $request)
    {
        $data = $this->buildTypedQuizzesPayload($request->user(), 'pre_test');

        return Inertia::render('Student/Quizzes/PreTest', [
            'quizzesData' => $data,
        ]);
    }

    public function practice(Request $request)
    {
        $category = $request->query('category', 'all');
        $course = $request->query('course', 'all');
        $difficulty = $request->query('difficulty', 'all');
        $status = $request->query('status', 'available');
        $search = $request->query('search', '');

        $data = app(AvailableQuizService::class)->getAvailableQuizzesData(
            $request->user(),
            $category,
            $course,
            $difficulty,
            $status,
            $search
        );

        return Inertia::render('Student/Quizzes/Practice', [
            'analytics' => $data,
            'filters'   => [
                'category'   => $category,
                'course'     => $course,
                'difficulty' => $difficulty,
                'status'     => $status,
                'search'     => $search,
            ],
        ]);
    }

    public function postTest(Request $request)
    {
        $data = $this->buildTypedQuizzesPayload($request->user(), 'post_test');

        return Inertia::render('Student/Quizzes/PostTest', [
            'quizzesData' => $data,
        ]);
    }

    public function assignments(Request $request)
    {
        $status = $request->query('status', 'all');
        $course = $request->query('course', 'all');
        $dateRange = $request->query('date_range', 'all');
        $search = $request->query('search', '');
        $page = (int) $request->query('page', 1);

        $data = app(AssessmentService::class)->getAssessmentsData(
            $request->user(),
            $status,
            $course,
            $dateRange,
            $search,
            $page
        );

        return Inertia::render('Student/Quizzes/Assignments', [
            'analytics' => $data,
            'filters'   => [
                'status'     => $status,
                'course'     => $course,
                'date_range' => $dateRange,
                'search'     => $search,
                'page'       => $page,
            ],
        ]);
    }

    public function submitAssignment(Request $request, Quiz $quiz)
    {
        $user = $request->user();

        $validated = $request->validate([
            'notes' => 'nullable|string|max:2000',
            'file'  => 'nullable|file|max:10240',
        ]);

        $existing = AssignmentSubmission::where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->first();

        $filePath = $existing?->file_path;
        $fileName = $existing?->file_name;
        $fileSize = $existing?->file_size;

        if ($request->hasFile('file')) {
            $uploaded = $request->file('file');
            $originalName = $uploaded->getClientOriginalName();
            $sizeBytes = (int) $uploaded->getSize();
            $ext = strtolower($uploaded->getClientOriginalExtension() ?: 'bin');
            $safeBase = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) ?: 'assignment';
            $storedName = $user->id . '_' . $quiz->id . '_' . time() . '_' . $safeBase . '.' . $ext;

            $destDir = public_path('uploads/assignments');
            if (!is_dir($destDir)) {
                @mkdir($destDir, 0755, true);
            }
            $uploaded->move($destDir, $storedName);

            $filePath = '/uploads/assignments/' . $storedName;
            $fileName = $originalName;
            $fileSize = $sizeBytes >= 1048576
                ? round($sizeBytes / 1048576, 1) . ' MB'
                : max(1, (int) round($sizeBytes / 1024)) . ' KB';
        }

        $submission = AssignmentSubmission::updateOrCreate(
            [
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
            ],
            [
                'course_id'    => $quiz->course_id,
                'title'        => $quiz->title,
                'notes'        => $validated['notes'] ?? $existing?->notes,
                'file_path'    => $filePath,
                'file_name'    => $fileName,
                'file_size'    => $fileSize,
                'status'       => 'submitted',
                'submitted_at' => now(),
            ]
        );

        app(DashboardAnalyticsService::class)->invalidateUserCache($user->id);

        if ($request->wantsJson() && !$request->header('X-Inertia')) {
            return response()->json([
                'success'    => true,
                'message'    => 'Assignment submitted successfully.',
                'submission' => $submission,
            ]);
        }

        return back()->with('success', 'កិច្ចការត្រូវបានដាក់ស្នើដោយជោគជ័យ! (Assignment submitted successfully.)');
    }

    public function history(Request $request)
    {
        $status = $request->query('status', 'all');
        $dateRange = $request->query('date_range', 'all');
        $search = $request->query('search', '');
        $page = (int) $request->query('page', 1);

        $data = app(QuizAttemptHistoryService::class)->getAttemptsHistoryData(
            $request->user(),
            $status,
            $dateRange,
            $search,
            $page
        );

        return Inertia::render('Student/Quizzes/History', [
            'analytics' => $data,
            'filters'   => [
                'status'     => $status,
                'date_range' => $dateRange,
                'search'     => $search,
                'page'       => $page,
            ],
        ]);
    }

    public function scores(Request $request)
    {
        $status = $request->query('status', 'all');
        $course = $request->query('course', 'all');
        $category = $request->query('category', 'all');
        $difficulty = $request->query('difficulty', 'all');
        $dateRange = $request->query('date_range', 'all');
        $search = $request->query('search', '');
        $page = (int) $request->query('page', 1);

        $data = app(QuizResultService::class)->getQuizResultsData(
            $request->user(),
            $status,
            $course,
            $category,
            $difficulty,
            $dateRange,
            $search,
            $page
        );

        return Inertia::render('Student/Quizzes/Scores', [
            'analytics' => $data,
            'filters'   => [
                'status'     => $status,
                'course'     => $course,
                'category'   => $category,
                'difficulty' => $difficulty,
                'date_range' => $dateRange,
                'search'     => $search,
                'page'       => $page,
            ],
        ]);
    }

    public function show(Request $request, Quiz $quiz)
    {
        $quiz->load([
            'course:id,title',
            'module:id,title',
            'questions' => function ($q) {
                $q->select('id', 'quiz_id', 'type', 'question', 'options', 'points')
                  ->inRandomOrder();
            },
        ]);

        $attempts = QuizAttempt::where('user_id', $request->user()->id)
            ->where('quiz_id', $quiz->id)
            ->count();

        abort_if(
            $quiz->max_attempts !== null && (int) $quiz->max_attempts > 0 && $attempts >= (int) $quiz->max_attempts,
            403,
            'Maximum quiz attempt limit reached.'
        );

        return Inertia::render('Student/Quizzes/Show', [
            'quiz'           => $quiz,
            'attempts_used'  => $attempts,
            'attempt_number' => $attempts + 1,
        ]);
    }

    public function submit(Request $request, Quiz $quiz)
    {
        $user = $request->user();

        $data = $request->validate([
            'answers'     => 'present|array',
            'client_uuid' => 'required|string',
            'started_at'  => 'nullable',
        ]);

        // Deduplicate by client_uuid
        if (!empty($data['client_uuid'])) {
            $existingAttempt = QuizAttempt::where('client_uuid', $data['client_uuid'])->first();
            if ($existingAttempt) {
                return response()->json([
                    'score'     => (float) $existingAttempt->score,
                    'passed'    => (bool) $existingAttempt->passed,
                    'duplicate' => true,
                ]);
            }
        }

        $attemptsCount = QuizAttempt::where('user_id', $user->id)
            ->where('quiz_id', $quiz->id)
            ->count();

        abort_if(
            $quiz->max_attempts !== null && (int) $quiz->max_attempts > 0 && $attemptsCount >= (int) $quiz->max_attempts,
            403,
            'Maximum quiz attempt limit reached.'
        );

        [$score, $passed, $breakdown] = app(GradingService::class)->grade($quiz, $data['answers']);

        $startedAt = now();
        if (!empty($data['started_at'])) {
            if (is_numeric($data['started_at'])) {
                $num = (int) $data['started_at'];
                $startedAt = $num > 9999999999
                    ? Carbon::createFromTimestampMs($num)
                    : Carbon::createFromTimestamp($num);
            } else {
                try {
                    $startedAt = Carbon::parse($data['started_at']);
                } catch (\Throwable $e) {
                    $startedAt = now();
                }
            }
        }

        $submittedAt = now();

        $attempt = QuizAttempt::create([
            'user_id'        => $user->id,
            'quiz_id'        => $quiz->id,
            'answers'        => $data['answers'],
            'score'          => $score,
            'passed'         => $passed,
            'client_uuid'    => $data['client_uuid'] ?? (string) Str::uuid(),
            'attempt_number' => $attemptsCount + 1,
            'started_at'     => $startedAt,
            'submitted_at'   => $submittedAt,
        ]);

        // Record individual question answers for fine-grained weak topic analytics
        if (!empty($breakdown) && is_array($breakdown)) {
            foreach ($breakdown as $qId => $info) {
                $userAns = $data['answers'][$qId] ?? $data['answers'][(string) $qId] ?? null;
                $isCorr = (bool) ($info['correct'] ?? false);
                $pts = (float) ($info['points'] ?? 0);

                \App\Models\QuizAttemptAnswer::create([
                    'attempt_id'      => $attempt->id,
                    'question_id'     => (int) $qId,
                    'selected_answer' => is_array($userAns) ? $userAns : ($userAns !== null ? [$userAns] : null),
                    'is_correct'      => $isCorr,
                    'points_earned'   => $isCorr ? $pts : 0,
                    'answered_at'     => $submittedAt,
                ]);

                // Update topic_mastery cache if question is linked to a topic
                $questionObj = \App\Models\Question::find($qId);
                if ($questionObj && $questionObj->topic_id) {
                    $mastery = \App\Models\TopicMastery::firstOrNew([
                        'user_id'  => $user->id,
                        'topic_id' => $questionObj->topic_id,
                    ]);
                    $mastery->answered_count = ($mastery->answered_count ?? 0) + 1;
                    if ($isCorr) {
                        $mastery->correct_count = ($mastery->correct_count ?? 0) + 1;
                    }
                    $mastery->score = $mastery->answered_count > 0
                        ? round(($mastery->correct_count / $mastery->answered_count) * 100, 2)
                        : 0;
                    $mastery->last_attempt_at = $submittedAt;
                    $mastery->save();
                }
            }
        }

        // Record quiz study time & invalidate user analytics cache
        $analyticsService = app(DashboardAnalyticsService::class);
        $kind = str_contains(strtolower((string) $quiz->type), 'practice') ? 'practice' : 'quiz';
        $elapsedSeconds = max(15, (int) abs($submittedAt->diffInSeconds($startedAt)));
        $analyticsService->recordDailyDelta($user->id, $kind, $elapsedSeconds);
        $analyticsService->invalidateUserCache($user->id);

        // Issue certificate if passed and course lessons are 100% completed
        $certificateIssued = false;
        if ($passed && $quiz->course_id) {
            $course = $quiz->course ?: Course::with(['modules.lessons', 'lessons'])->find($quiz->course_id);
            if ($course) {
                $course->loadMissing(['modules.lessons', 'lessons']);
                $progressInfo = app(CourseProgressService::class)->calculate($user, $course);
                $allLessonsCompleted = ($progressInfo['lessons_total'] > 0 && $progressInfo['lessons_done'] >= $progressInfo['lessons_total'])
                    || (int) ($progressInfo['percent'] ?? 0) >= 100;

                if ($allLessonsCompleted) {
                    Enrollment::where('student_id', $user->id)
                        ->where('course_id', $course->id)
                        ->update(['status' => 'completed']);

                    try {
                        $grade = $score >= 90 ? 'A+' : ($score >= 80 ? 'A' : ($score >= 70 ? 'B+' : 'B'));
                        app(CertificateService::class)->issue($user, $course, null, $grade, (int) round($score));
                        $certificateIssued = true;
                    } catch (\Throwable $e) {
                        // Non-blocking if certificate PDF template is missing
                    }
                }
            }
        }

        return response()->json([
            'score'              => $score,
            'passed'             => $passed,
            'breakdown'          => $breakdown,
            'attempt_id'         => $attempt->id,
            'attempt_number'     => $attempt->attempt_number,
            'certificate_issued' => $certificateIssued,
        ]);
    }

    /**
     * Build real DB payload for Pre-Test or Post-Test screens.
     */
    protected function buildTypedQuizzesPayload($user, string $quizType): array
    {
        if (!$user) {
            return [
                'summary'   => [
                    'total_count'     => 0,
                    'completed_count' => 0,
                    'passed_count'    => 0,
                    'average_score'   => 0,
                ],
                'available' => [],
                'completed' => [],
            ];
        }

        $enrolledCourseIds = Enrollment::where('student_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->pluck('course_id')
            ->unique()
            ->filter()
            ->values()
            ->all();

        if (empty($enrolledCourseIds)) {
            return [
                'summary'   => [
                    'total_count'     => 0,
                    'completed_count' => 0,
                    'passed_count'    => 0,
                    'average_score'   => 0,
                ],
                'available' => [],
                'completed' => [],
            ];
        }

        $quizzes = Quiz::whereIn('course_id', $enrolledCourseIds)
            ->where('type', $quizType)
            ->where(function ($q) {
                $q->where('status', 'published')->orWhereNull('status');
            })
            ->with(['course:id,title,code', 'module:id,title,order'])
            ->withCount('questions')
            ->orderBy('course_id')
            ->orderBy('id')
            ->get();

        if ($quizzes->isEmpty()) {
            return [
                'summary'   => [
                    'total_count'     => 0,
                    'completed_count' => 0,
                    'passed_count'    => 0,
                    'average_score'   => 0,
                ],
                'available' => [],
                'completed' => [],
            ];
        }

        $quizIds = $quizzes->pluck('id')->all();
        $attempts = QuizAttempt::where('user_id', $user->id)
            ->whereIn('quiz_id', $quizIds)
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get()
            ->groupBy('quiz_id');

        $available = [];
        $completed = [];
        $completedScores = [];
        $passedQuizzesCount = 0;

        foreach ($quizzes as $quiz) {
            $qAttempts = $attempts->get($quiz->id, collect());
            $attemptsUsed = $qAttempts->count();
            $maxAttempts = ($quiz->max_attempts !== null && (int) $quiz->max_attempts > 0)
                ? (int) $quiz->max_attempts
                : null;
            $canRetake = $maxAttempts === null || $attemptsUsed < $maxAttempts;

            $bestAttempt = $qAttempts->sortByDesc('score')->first();
            $latestAttempt = $qAttempts->first();
            $hasPassed = $qAttempts->contains(fn ($a) => (bool) $a->passed);

            if ($hasPassed) {
                $passedQuizzesCount++;
            }

            $item = [
                'id'                 => $quiz->id,
                'course_id'          => $quiz->course_id,
                'course_title'       => $quiz->course?->title ?? 'Course',
                'module_title'       => $quiz->module?->title ?? null,
                'title'              => $quiz->title,
                'type'               => $quiz->type,
                'questions_count'    => (int) ($quiz->questions_count ?? 0),
                'time_limit_minutes' => (int) ($quiz->time_limit_minutes ?? 0),
                'passing_score'      => (int) round((float) ($quiz->passing_score ?? 50)),
                'max_attempts'       => $maxAttempts,
                'attempts_used'      => $attemptsUsed,
                'can_attempt'        => $canRetake,
                'best_score'         => $bestAttempt ? (int) round((float) $bestAttempt->score) : null,
                'latest_score'       => $latestAttempt ? (int) round((float) $latestAttempt->score) : null,
                'passed'             => $hasPassed,
                'taken_at'           => ($latestAttempt?->submitted_at ?? $latestAttempt?->created_at)?->format('M j, Y h:i A'),
                'quiz_url'           => "/student/quiz/{$quiz->id}",
                'course_url'         => "/student/learn/{$quiz->course_id}",
            ];

            if ($attemptsUsed > 0) {
                $completed[] = $item;
                $completedScores[] = $item['best_score'] ?? 0;
                // If student hasn't passed yet and still has attempts left, also keep in available!
                if ($quizType === 'post_test' && !$hasPassed && $canRetake) {
                    $available[] = $item;
                }
            } else {
                $available[] = $item;
            }
        }

        $avgScore = !empty($completedScores)
            ? (int) round(array_sum($completedScores) / count($completedScores))
            : 0;

        return [
            'summary'   => [
                'total_count'     => $quizzes->count(),
                'completed_count' => count($completed),
                'passed_count'    => $passedQuizzesCount,
                'average_score'   => $avgScore,
            ],
            'available' => $available,
            'completed' => $completed,
        ];
    }
}
