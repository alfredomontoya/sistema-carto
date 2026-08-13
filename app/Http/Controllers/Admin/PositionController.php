<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePositionRequest;
use App\Http\Requests\Admin\UpdatePositionRequest;
use App\Services\PositionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function __construct(
        private readonly PositionService $positions,
    ) {}

    public function store(StorePositionRequest $request): RedirectResponse
    {
        $this->positions->create($request->validated());

        return redirect()
            ->route('admin.areas.index')
            ->with('success', 'Puesto creado correctamente.');
    }

    public function update(UpdatePositionRequest $request, string $id): RedirectResponse
    {
        $position = $this->positions->find($id);

        if ($position === null) {
            abort(404);
        }

        $this->positions->update($position, $request->validated());

        return redirect()
            ->route('admin.areas.index')
            ->with('success', 'Puesto actualizado correctamente.');
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $position = $this->positions->find($id);

        if ($position === null) {
            abort(404);
        }

        $deleted = $this->positions->delete($position);

        if (! $deleted) {
            return redirect()->route('admin.areas.index')
                ->with('error', 'No se puede eliminar el puesto: tiene usuarios asignados o historial.');
        }

        return redirect()->route('admin.areas.index')->with('success', 'Puesto eliminado.');
    }
}
