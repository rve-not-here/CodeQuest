<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(private readonly ActivityService $activity) {}

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->safe(['username', 'password']);

        if (! Auth::attempt($credentials)) {
            return back()
                ->withInput($request->safe()->only('username'))
                ->withErrors(['username' => 'Invalid credentials.']);
        }

        // An inactive account is refused its own new login even though the
        // credentials matched. Distinct error on purpose (US-701, §13/§14):
        // session cookie or not, the account is signed out on the next request.
        /** @var User $user */
        $user = Auth::user();

        if ($user->status !== 'active') {
            Auth::logout();

            return back()
                ->withInput($request->safe()->only('username'))
                ->withErrors(['account' => 'This account has been deactivated.']);
        }

        $request->session()->regenerate();

        $this->activity->record($user, [
            'type' => 'login',
            'message' => 'Operator '.$user->username.' logged in',
        ]);

        return redirect()->intended($this->homeFor($user->role));
    }

    public function logout(): RedirectResponse
    {
        $user = Auth::user();

        if ($user !== null) {
            $this->activity->record($user, [
                'type' => 'logout',
                'message' => 'Operator '.$user->username.' logged out',
            ]);
        }

        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function homeFor(string $role): string
    {
        return match ($role) {
            'admin' => route('admin.dashboard'),
            'teacher' => route('students'),
            default => route('dashboard'),
        };
    }
}
