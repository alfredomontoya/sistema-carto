<?php

namespace App\Http\Middleware;

use App\Services\PasswordRecoveryService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordRecoveryEnabled
{
    public function __construct(
        private readonly PasswordRecoveryService $recovery,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->recovery->enabled()) {
            abort(404);
        }

        return $next($request);
    }
}
