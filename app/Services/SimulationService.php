<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Simulation;
use App\Models\SimulationAttempt;
use App\Models\SimulationChoice;
use App\Models\SimulationNode;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SimulationService
{
    /**
     * Get list of simulations for student with attempts metadata.
     */
    public function getStudentSimulations(User $user, array $filters = []): array
    {
        $enrolledCourseIds = Enrollment::where('student_id', $user->id)
            ->whereIn('status', ['active', 'completed'])
            ->pluck('course_id')
            ->toArray();

        $query = Simulation::query()
            ->where('is_published', true)
            ->with(['course:id,title,code,thumbnail', 'lesson:id,title']);

        if (!empty($filters['skill'])) {
            $query->where('skill', $filters['skill']);
        }

        if (!empty($filters['course_id'])) {
            $query->where('course_id', $filters['course_id']);
        }

        $simulations = $query->orderBy('created_at', 'desc')->get();

        // Get student's attempts for all simulations
        $userAttempts = SimulationAttempt::where('user_id', $user->id)
            ->whereIn('simulation_id', $simulations->pluck('id'))
            ->orderBy('attempt_no', 'desc')
            ->get()
            ->groupBy('simulation_id');

        $items = $simulations->map(function (Simulation $sim) use ($userAttempts, $enrolledCourseIds) {
            $attempts = $userAttempts->get($sim->id, collect());
            $completedAttempts = $attempts->where('status', 'completed');
            $inProgressAttempt = $attempts->firstWhere('status', 'in_progress');
            
            $bestScore = $completedAttempts->max('score');
            $attemptsCount = $completedAttempts->count();
            $isEnrolled = in_array($sim->course_id, $enrolledCourseIds, true);

            $status = 'not_started';
            if ($inProgressAttempt) {
                $status = 'in_progress';
            } elseif ($attemptsCount > 0) {
                $status = ($bestScore !== null && $bestScore >= $sim->pass_score) ? 'passed' : 'needs_practice';
            }

            return [
                'id' => $sim->id,
                'title' => $sim->title,
                'title_kh' => $sim->title_kh,
                'skill' => $sim->skill,
                'kind' => $sim->kind,
                'instructions' => $sim->instructions,
                'estimated_minutes' => $sim->estimated_minutes,
                'is_graded' => $sim->is_graded,
                'pass_score' => $sim->pass_score,
                'max_attempts' => $sim->max_attempts,
                'is_enrolled' => $isEnrolled,
                'course' => $sim->course ? [
                    'id' => $sim->course->id,
                    'title' => $sim->course->title,
                    'code' => $sim->course->code,
                    'thumbnail' => $sim->course->thumbnail,
                ] : null,
                'lesson' => $sim->lesson ? [
                    'id' => $sim->lesson->id,
                    'title' => $sim->lesson->title,
                ] : null,
                'status' => $status,
                'best_score' => $bestScore,
                'attempts_count' => $attemptsCount,
                'can_attempt' => $isEnrolled && ($inProgressAttempt || $attemptsCount < $sim->max_attempts),
                'in_progress_attempt_id' => $inProgressAttempt?->id,
                'latest_attempt_id' => $attempts->first()?->id,
            ];
        });

        if (!empty($filters['status'])) {
            $items = $items->filter(function ($item) use ($filters) {
                return $item['status'] === $filters['status'];
            })->values();
        }

        $allSimulations = $simulations;
        $allAttempts = SimulationAttempt::where('user_id', $user->id)
            ->whereIn('simulation_id', $allSimulations->pluck('id'))
            ->get();
        $completedAll = $allAttempts->where('status', 'completed');

        return [
            'simulations' => $items,
            'counts' => [
                'total' => $simulations->count(),
                'completed' => $completedAll->pluck('simulation_id')->unique()->count(),
                'passed' => $items->where('status', 'passed')->count(),
                'in_progress' => $items->where('status', 'in_progress')->count(),
            ],
        ];
    }

    /**
     * Start or resume a simulation attempt.
     */
    public function startSimulation(Simulation $simulation, User $user): SimulationAttempt
    {
        if (!$simulation->is_published) {
            throw new HttpException(404, 'Simulation is not published.');
        }

        // Check course enrollment
        $isEnrolled = Enrollment::where('student_id', $user->id)
            ->where('course_id', $simulation->course_id)
            ->whereIn('status', ['active', 'completed'])
            ->exists();

        if (!$isEnrolled) {
            throw new HttpException(403, 'You must be enrolled in this course to practice this simulation.');
        }

        // Idempotent resume
        $existing = SimulationAttempt::where('simulation_id', $simulation->id)
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->first();

        if ($existing) {
            return $existing;
        }

        // Check max attempts
        $completedCount = SimulationAttempt::where('simulation_id', $simulation->id)
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        if ($completedCount >= $simulation->max_attempts) {
            throw new HttpException(422, 'You have reached the maximum allowed attempts (' . $simulation->max_attempts . ') for this simulation.');
        }

        $nextAttemptNo = (SimulationAttempt::where('simulation_id', $simulation->id)
            ->where('user_id', $user->id)
            ->max('attempt_no') ?? 0) + 1;

        $startNodeId = $simulation->start_node_id;
        if (!$startNodeId) {
            $firstNode = $simulation->nodes()->orderBy('id', 'asc')->first();
            $startNodeId = $firstNode?->id;
        }

        if (!$startNodeId) {
            throw new HttpException(500, 'This simulation has no starting scenario node configured.');
        }

        return SimulationAttempt::create([
            'simulation_id' => $simulation->id,
            'user_id' => $user->id,
            'attempt_no' => $nextAttemptNo,
            'current_node_id' => $startNodeId,
            'score' => 0,
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    /**
     * Student chooses a step option (Server evaluates, zero client trust).
     */
    public function chooseChoice(SimulationAttempt $attempt, int $choiceId, User $user): array
    {
        if ($attempt->user_id !== $user->id) {
            throw new HttpException(403, 'Unauthorized access to this attempt.');
        }

        if ($attempt->status !== 'in_progress') {
            throw new HttpException(409, 'This simulation attempt is already completed.');
        }

        if (!$attempt->current_node_id) {
            throw new HttpException(400, 'Simulation is in an invalid state.');
        }

        // Choice must strictly belong to the current node
        $choice = SimulationChoice::where('node_id', $attempt->current_node_id)->find($choiceId);
        if (!$choice) {
            throw new HttpException(422, 'Invalid choice for the current scenario step.');
        }

        // Double submit / double-click protection (idempotency check)
        $alreadyAnswered = $attempt->steps()
            ->where('node_id', $attempt->current_node_id)
            ->first();

        if ($alreadyAnswered) {
            // Already processed this node, return existing node state
            return $this->formatStepResponse($attempt, $choice);
        }

        DB::transaction(function () use ($attempt, $choice) {
            $attempt->steps()->create([
                'node_id' => $attempt->current_node_id,
                'choice_id' => $choice->id,
                'points' => $choice->points,
                'created_at' => now(),
            ]);

            $attempt->score += $choice->points;
            $attempt->current_node_id = $choice->next_node_id;

            $nextNode = $choice->next_node_id ? SimulationNode::find($choice->next_node_id) : null;
            if (!$choice->next_node_id || ($nextNode && $nextNode->is_end)) {
                $attempt->status = 'completed';
                $attempt->completed_at = now();
            }

            $attempt->save();
        });

        $attempt->refresh();
        return $this->formatStepResponse($attempt, $choice);
    }

    /**
     * Format node payload without leaking answers, points, or is_best.
     */
    public function sanitizeNodeForStudent(?SimulationNode $node): ?array
    {
        if (!$node) {
            return null;
        }

        return [
            'id' => $node->id,
            'title' => $node->title,
            'body' => $node->body,
            'media_path' => $node->media_path,
            'is_end' => $node->is_end,
            'choices' => $node->choices->map(function (SimulationChoice $choice) {
                // NEVER return points or is_best to browser before choosing!
                return [
                    'id' => $choice->id,
                    'label' => $choice->label,
                ];
            })->values(),
        ];
    }

    /**
     * Format server step response.
     */
    protected function formatStepResponse(SimulationAttempt $attempt, SimulationChoice $choice): array
    {
        $nextNode = $attempt->current_node_id ? SimulationNode::with('choices')->find($attempt->current_node_id) : null;

        return [
            'feedback' => $choice->feedback,
            'points_awarded' => $choice->points,
            'current_score' => $attempt->score,
            'done' => $attempt->status === 'completed',
            'node' => $this->sanitizeNodeForStudent($nextNode),
        ];
    }

    /**
     * Generate summary review for a completed attempt.
     */
    public function getAttemptSummary(SimulationAttempt $attempt, User $user): array
    {
        if ($attempt->user_id !== $user->id) {
            throw new HttpException(403, 'Unauthorized access to this attempt.');
        }

        if ($attempt->status !== 'completed') {
            throw new HttpException(400, 'Attempt is not completed yet.');
        }

        $attempt->load([
            'simulation.course:id,title,code,thumbnail',
            'simulation.lesson:id,title',
            'steps.node',
            'steps.choice',
        ]);

        $sim = $attempt->simulation;
        $passed = $attempt->score >= $sim->pass_score;

        $steps = $attempt->steps->map(function ($step) {
            $node = $step->node;
            $choice = $step->choice;
            $bestChoice = SimulationChoice::where('node_id', $node->id)->where('is_best', true)->first();

            return [
                'step_id' => $step->id,
                'node_id' => $node->id,
                'node_title' => $node->title,
                'node_body' => $node->body,
                'chosen_label' => $choice->label,
                'points' => $step->points,
                'feedback' => $choice->feedback,
                'is_best' => $choice->is_best,
                'best_choice_label' => ($choice->is_best) ? null : $bestChoice?->label,
            ];
        });

        $completedCount = SimulationAttempt::where('simulation_id', $sim->id)
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $canRetry = $completedCount < $sim->max_attempts;

        return [
            'attempt' => [
                'id' => $attempt->id,
                'attempt_no' => $attempt->attempt_no,
                'score' => $attempt->score,
                'status' => $attempt->status,
                'passed' => $passed,
                'started_at' => $attempt->started_at?->toIso8601String(),
                'completed_at' => $attempt->completed_at?->toIso8601String(),
            ],
            'simulation' => [
                'id' => $sim->id,
                'title' => $sim->title,
                'title_kh' => $sim->title_kh,
                'skill' => $sim->skill,
                'kind' => $sim->kind,
                'pass_score' => $sim->pass_score,
                'max_attempts' => $sim->max_attempts,
                'course' => $sim->course ? [
                    'id' => $sim->course->id,
                    'title' => $sim->course->title,
                    'code' => $sim->course->code,
                ] : null,
                'lesson' => $sim->lesson ? [
                    'id' => $sim->lesson->id,
                    'title' => $sim->lesson->title,
                ] : null,
            ],
            'steps' => $steps,
            'can_retry' => $canRetry,
            'attempts_left' => max(0, $sim->max_attempts - $completedCount),
        ];
    }
}
