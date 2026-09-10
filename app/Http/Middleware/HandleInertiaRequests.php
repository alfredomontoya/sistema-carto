<?php

namespace App\Http\Middleware;

use App\Http\Resources\UserResource;
use App\Services\BrandSettingsService;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Inertia\Inertia;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        if ($user !== null) {
            $isLookup = $request->routeIs('users.search') || $request->routeIs('areas.search');

            if (! $isLookup) {
                $user->load(['roles', 'currentAssignment.position.area']);
            } else {
                $user->load('roles');
            }
        }

        /** @var BrandSettingsService $brand */
        $brand = app(BrandSettingsService::class);

        return [
            ...parent::share($request),
            'app' => [
                'name' => (string) config('app.name'),
                'user_domain' => (string) config('auth.user_domain'),
            ],
            'auth' => [
                'user' => $user ? UserResource::make($user)->resolve() : null,
            ],
            'brand' => $brand->withUrls(),
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
                'celebrate' => $request->session()->get('celebrate'),
            ],
            'created' => $request->session()->get('created'),
        ];
    }
}
