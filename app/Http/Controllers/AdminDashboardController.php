<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AdminDashboardService;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __construct(private readonly AdminDashboardService $dashboard) {}

    /**
     * Admin landing page (US-702): the fleet-wide system console, replacing
     * the US-701 landing stub. Every figure is composed server-side from the
     * existing analytics services and the dedicated admin audit trail — the
     * view receives no client-supplied numbers.
     */
    public function __invoke(): View
    {
        $overview = $this->dashboard->overview();

        /** @var User $user */
        $user = auth()->user();

        return view('admin.dashboard', [
            'role' => $user->role,
            'metrics' => $overview['metrics'],
            'recentSystemActivity' => $overview['recent_system_activity'],
        ]);
    }
}
