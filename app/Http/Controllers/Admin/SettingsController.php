<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBrandSettingsRequest;
use App\Services\BrandSettingsService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(
        private readonly BrandSettingsService $brand,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Settings/Index', [
            'brand' => $this->brand->withUrls(),
        ]);
    }

    public function update(UpdateBrandSettingsRequest $request): RedirectResponse
    {
        $this->brand->update(
            $request->safe()->only(['app_name']),
        );

        return redirect()->route('admin.settings.index')->with('success', 'Ajustes actualizados.');
    }
}
