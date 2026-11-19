<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Simulation;
use App\Models\SimulationAttempt;
use App\Models\SimulationNode;
use App\Services\SimulationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SimulationController extends Controller
{
    public function __construct(
        protected SimulationService $simulationService
    ) {}

    /**
     * List simulations for student.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['skill', 'course_id', 'status']);
        $data = $this->simulationService->getStudentSimulations($request->user(), $filters);

        return Inertia::render('Student/Simulations/Index', [
            'simulations' => $data['simulations'],
            'counts' => $data['counts'],
            'filters' => $filters,
        ]);
    }

    /**
     * Start or resume simulation attempt.
     */
    public function start(Simulation $simulation, Request $request): RedirectResponse|JsonResponse
    {
        $attempt = $this->simulationService->startSimulation($simulation, $request->user());

        if ($request->wantsJson()) {
            return response()->json([
                'attempt_id' => $attempt->id,
                'redirect_url' => route('student.simulations.play', [$simulation->id, $attempt->id]),
            ]);
        }

        return redirect()->route('student.simulations.play', [$simulation->id, $attempt->id]);
    }

    /**
     * Interactive scenario playing screen.
     */
    public function play(Simulation $simulation, SimulationAttempt $attempt, Request $request): Response|RedirectResponse
    {
        abort_unless($attempt->user_id === $request->user()->id, 403, 'Unauthorized.');
        abort_unless($attempt->simulation_id === $simulation->id, 404, 'Attempt mismatch.');

        if ($attempt->status === 'completed') {
            return redirect()->route('student.simulations.summary', $attempt->id);
        }

        $currentNode = $attempt->current_node_id
            ? SimulationNode::with('choices')->find($attempt->current_node_id)
            : null;

        return Inertia::render('Student/Simulations/Play', [
            'simulation' => [
                'id' => $simulation->id,
                'title' => $simulation->title,
                'title_kh' => $simulation->title_kh,
                'skill' => $simulation->skill,
                'kind' => $simulation->kind,
                'pass_score' => $simulation->pass_score,
                'estimated_minutes' => $simulation->estimated_minutes,
            ],
            'attempt' => [
                'id' => $attempt->id,
                'attempt_no' => $attempt->attempt_no,
                'score' => $attempt->score,
                'status' => $attempt->status,
                'steps_count' => $attempt->steps()->count(),
            ],
            'node' => $this->simulationService->sanitizeNodeForStudent($currentNode),
        ]);
    }

    /**
     * Submit a decision choice in the current step.
     */
    public function choose(SimulationAttempt $attempt, Request $request): JsonResponse
    {
        $data = $request->validate([
            'choice_id' => ['required', 'integer'],
        ]);

        $res = $this->simulationService->chooseChoice($attempt, (int) $data['choice_id'], $request->user());

        return response()->json($res);
    }

    /**
     * View summary after attempt completion.
     */
    public function summary(SimulationAttempt $attempt, Request $request): Response
    {
        $summary = $this->simulationService->getAttemptSummary($attempt, $request->user());

        return Inertia::render('Student/Simulations/Summary', $summary);
    }
}
