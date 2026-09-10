<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBrandSettingsRequest;
use App\Repositories\Contracts\SettingsRepository;
use App\Services\BrandSettingsService;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(
        private readonly BrandSettingsService $brand,
        private readonly SettingsRepository $settings,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Settings/Index', [
            'brand' => $this->brand->withUrls(),
            'password_expiry_days' => (int) $this->settings->get(
                'security.password_expiry_days',
                UserService::PASSWORD_EXPIRY_DAYS_DEFAULT,
            ),
            'password_recovery_enabled' => (bool) $this->settings->get(
                'security.password_recovery_enabled',
                false,
            ),
        ]);
    }

    public function update(UpdateBrandSettingsRequest $request): RedirectResponse
    {
        $this->brand->update(
            $request->safe()->only(['app_name']),
        );

        $this->settings->set(
            'security.password_expiry_days',
            (int) $request->validated('password_expiry_days'),
        );

        $this->settings->set(
            'security.password_recovery_enabled',
            (bool) $request->validated('password_recovery_enabled'),
        );

        return redirect()->route('admin.settings.index')->with('success', 'Ajustes actualizados.');
    }
}
