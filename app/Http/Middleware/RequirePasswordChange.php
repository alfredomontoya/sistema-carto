<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePasswordChange
{
    /**
     * Redirect users flagged with must_change_password to their
     * profile password tab until they update it.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && (bool) $user->must_change_password && ! $this->isAllowed($request)) {
            return redirect()
                ->route('profile.edit', ['tab' => 'password'])
                ->with('error', 'Debes actualizar tu contraseña para continuar.');
        }

        return $next($request);
    }

    private function isAllowed(Request $request): bool
    {
        if ($request->routeIs('profile.edit', 'profile.password', 'profile.avatar', 'logout')) {
            return true;
        }

        if ($request->routeIs('users.search', 'areas.search')) {
            return true;
        }

        return false;
    }
}
