<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\DashboardService;
use App\Services\LearningPathService;
use Illuminate\View\View;

class LearningPathController extends Controller
{
    public function __construct(
        private readonly LearningPathService $path,
        private readonly DashboardService $dashboard,
    ) {}

    public function __invoke(): View
    {
        /** @var User $user */
        $user = auth()->user();

        $course = $this->dashboard->currentCourse($user);
        $nextMission = $this->path->nextMission($user, $course);

        return view('learning-path', [
            'user' => $user,
            'role' => $user->role,
            'tree' => $this->path->build($user),
            'totalXp' => $this->dashboard->totalXp($user),
            'nextMission' => $nextMission,
            'course' => $course,
        ]);
    }
}
