<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\CommunicationService;
use App\Services\PasswordRecoveryService;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly CommunicationService $communications,
        private readonly UserService $users,
        private readonly PasswordRecoveryService $recovery,
    ) {}
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => $this->recovery->enabled(),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        if (! $request->user()->is_active) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'username' => 'Tu cuenta está desactivada. Contacta al administrador.',
            ]);
        }

        $request->session()->regenerate();

        $redirect = redirect()->intended(route('dashboard', absolute: false));

        $ranking = $this->communications->yesterdayCreatorStats($request->user());

        if ($ranking['is_top']) {
            $redirect->with('celebrate', $ranking['count']);
        }

        $daysLeft = $this->users->passwordDaysLeft($request->user());

        if ($daysLeft <= 3) {
            $redirect->with('warning', $daysLeft <= 0
                ? 'Tu contraseña ya venció. Actualízala cuanto antes.'
                : "Tu contraseña vence en {$daysLeft} ".($daysLeft === 1 ? 'día' : 'días').'. Actualízala pronto.');
        }

        return $redirect;
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
