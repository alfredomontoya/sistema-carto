<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PasswordRecoveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class PasswordRecoveryController extends Controller
{
    public function __construct(
        private readonly PasswordRecoveryService $recovery,
    ) {}

    private function ensureEnabled(): void
    {
        if (! $this->recovery->enabled()) {
            abort(404);
        }
    }

    public function create(): Response
    {
        $this->ensureEnabled();

        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureEnabled();

        $request->validate(['email' => ['required', 'email', 'max:255']]);

        $this->recovery->requestReset($request->input('email'));

        return back()->with(
            'status',
            'Si el correo está registrado y verificado, recibirás un enlace para restablecer tu contraseña.'
        );
    }

    public function reset(string $token): Response
    {
        $this->ensureEnabled();

        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => (string) request()->query('email', ''),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->ensureEnabled();

        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $ok = $this->recovery->resetFromToken(
            $request->input('email'),
            $request->input('token'),
            $request->input('password'),
        );

        if (! $ok) {
            return back()->withErrors([
                'email' => 'El enlace es inválido o ha vencido. Solicita uno nuevo.',
            ]);
        }

        return redirect()->route('login')->with(
            'status',
            'Contraseña restablecida. Inicia sesión y actualízala por una definitiva.'
        );
    }

    public function verify(Request $request, string $id): RedirectResponse
    {
        $this->ensureEnabled();

        $user = User::find($id);

        if ($user === null
            || $user->recovery_email === null
            || $user->recovery_email !== $request->query('email')
        ) {
            return redirect()->route('login')->with(
                'error',
                'El enlace de verificación no es válido.'
            );
        }

        $this->recovery->verifyRecoveryEmail($user);

        return redirect()->route('login')->with(
            'status',
            'Correo verificado. Ya puedes recuperar tu contraseña con él.'
        );
    }
}
