<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class StandbyController extends Controller
{
    public function __invoke(string $slug): View
    {
        $user = auth()->user();

        return view('standby', [
            'slug' => $slug,
            'user' => $user,
            'role' => $user instanceof User ? $user->role : 'student',
        ]);
    }
}
