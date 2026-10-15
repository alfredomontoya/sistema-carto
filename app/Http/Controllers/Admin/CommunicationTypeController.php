<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCommunicationTypeRequest;
use App\Http\Requests\Admin\UpdateCommunicationTypeRequest;
use App\Http\Resources\CommunicationTypeResource;
use App\Services\CommunicationService;
use App\Services\CommunicationTypeService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CommunicationTypeController extends Controller
{
    public function __construct(
        private readonly CommunicationTypeService $types,
        private readonly CommunicationService $communications,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/CommunicationTypes/Index', [
            'types' => CommunicationTypeResource::collection($this->types->all())->resolve(),
        ]);
    }

    public function store(StoreCommunicationTypeRequest $request): RedirectResponse
    {
        $this->types->create($request->validated());
        $this->communications->invalidateDashboardStats();

        return redirect()->route('admin.communication-types.index')
            ->with('success', 'Tipo de comunicación creado correctamente.');
    }

    public function update(UpdateCommunicationTypeRequest $request, string $id): RedirectResponse
    {
        $type = $this->types->find($id);

        if ($type === null) {
            abort(404);
        }

        $this->types->update($type, $request->validated());
        $this->communications->invalidateDashboardStats();

        return redirect()->route('admin.communication-types.index')
            ->with('success', 'Tipo de comunicación actualizado correctamente.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $type = $this->types->find($id);

        if ($type === null) {
            abort(404);
        }

        if (! $this->types->delete($type)) {
            return redirect()->route('admin.communication-types.index')
                ->with('error', 'No se puede eliminar: el tipo ya tiene comunicaciones o numeración emitida.');
        }

        $this->communications->invalidateDashboardStats();

        return redirect()->route('admin.communication-types.index')
            ->with('success', 'Tipo de comunicación eliminado correctamente.');
    }
}
