<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\DashboardStatsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardStatsService $dashboardStatsService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login');
        }

        $forceRefresh = $request->has('refresh');
        $payload = $this->dashboardStatsService->getDashboardData($user, $forceRefresh);

        return Inertia::render('Student/Dashboard', $payload);
    }

    /**
     * Compute mathematically consistent real database stats for student dashboard.
     */
    public function computeStats($user): array
    {
        return $this->dashboardStatsService->computeStats($user);
    }

    /**
     * Official JSON API Endpoint for asynchronous loading.
     */
    public function apiStats(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if ($request->has('refresh')) {
            DashboardStatsService::forgetForUser($user->id);
        }

        $stats = $this->dashboardStatsService->computeStats($user);

        return response()->json([
            'success' => true,
            'stats'   => $stats,
        ]);
    }
}
