<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\GradingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QuizController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'pretest');
        return Inertia::render('Student/Quizzes/Index', [
            'activeTab' => $tab,
        ]);
    }

    public function preTest(Request $request)
    {
        return Inertia::render('Student/Quizzes/PreTest');
    }

    public function practice(Request $request)
    {
        $category = $request->query('category', 'all');
        $course = $request->query('course', 'all');
        $difficulty = $request->query('difficulty', 'all');
        $status = $request->query('status', 'available');
        $search = $request->query('search', '');

        $data = app(\App\Services\AvailableQuizService::class)->getAvailableQuizzesData(
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
            ]
        ]);
    }

    public function postTest(Request $request)
    {
        return Inertia::render('Student/Quizzes/PostTest');
    }

    public function assignments(Request $request)
    {
        return Inertia::render('Student/Quizzes/Assignments');
    }

    public function history(Request $request)
    {
        return Inertia::render('Student/Quizzes/History');
    }

    public function scores(Request $request)
    {
        return Inertia::render('Student/Quizzes/Scores');
    }

    public function show(Request $request, Quiz $quiz)
    {
        $quiz->load(['questions' => function ($q) {
            $q->select('id', 'quiz_id', 'type', 'question', 'options', 'points')
              ->inRandomOrder();
        }]);

        $attempts = QuizAttempt::where('user_id', $request->user()->id)
            ->where('quiz_id', $quiz->id)->count();

        abort_if($attempts >= $quiz->max_attempts, 403, 'Maximum quiz attempt limit reached.');

        return Inertia::render('Student/Quizzes/Show', [
            'quiz'            => $quiz,
            'attempts_used'   => $attempts,
            'attempt_number'  => $attempts + 1,
        ]);
    }

    public function submit(Request $request, Quiz $quiz)
    {
        $data = $request->validate([
            'answers'     => 'required|array',
            'client_uuid' => 'required|string',
            'started_at'  => 'nullable',
        ]);

        // Deduplicate
        if (!empty($data['client_uuid']) && QuizAttempt::where('client_uuid', $data['client_uuid'])->exists()) {
            $attempt = QuizAttempt::where('client_uuid', $data['client_uuid'])->first();
            return response()->json([
                'score' => $attempt->score, 'passed' => $attempt->passed, 'duplicate' => true,
            ]);
        }

        [$score, $passed, $breakdown] = app(GradingService::class)->grade($quiz, $data['answers']);

        $startedAt = !empty($data['started_at'])
            ? \Carbon\Carbon::createFromTimestampMs($data['started_at'])
            : now();

        $attempt = QuizAttempt::create([
            'user_id'        => $request->user()->id,
            'quiz_id'        => $quiz->id,
            'answers'        => $data['answers'],
            'score'          => $score,
            'passed'         => $passed,
            'client_uuid'    => $data['client_uuid'] ?? (string) \Illuminate\Support\Str::uuid(),
            'attempt_number' => QuizAttempt::where('user_id', $request->user()->id)
                ->where('quiz_id', $quiz->id)->count() + 1,
            'started_at'     => $startedAt,
            'submitted_at'   => now(),
        ]);

        return response()->json([
            'score' => $score, 'passed' => $passed, 'breakdown' => $breakdown,
        ]);
    }
}
