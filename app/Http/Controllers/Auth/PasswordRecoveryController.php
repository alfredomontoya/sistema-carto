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

    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:255']]);

        $this->recovery->requestReset($request->input('email'));

        return back()->with(
            'status',
            'Si el correo está registrado y verificado, recibirás un enlace para restablecer tu contraseña.'
        );
    }

    public function reset(string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => (string) request()->query('email', ''),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
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
            'Contraseña restablecida. Ya puedes iniciar sesión.'
        );
    }

    public function verify(Request $request, string $id, string $email): RedirectResponse
    {
        $user = User::find($id);

        if ($user === null
            || $user->recovery_email === null
            || $user->recovery_email !== $email
        ) {
            return redirect()->route('login')->with(
                'error',
                'El enlace de verificación no es válido.'
            );
        }

        $this->recovery->verifyRecoveryEmail($user);

        return redirect()->route('recovery.request')->with(
            'status',
            'Correo verificado. Solicita tu enlace para restablecer la contraseña.'
        );
    }
}
