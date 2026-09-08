<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAreaRequest;
use App\Http\Requests\Admin\UpdateAreaRequest;
use App\Services\AreaService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AreaController extends Controller
{
    public function __construct(
        private readonly AreaService $areas,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Areas/Index', [
            'areas' => Inertia::defer(fn () => $this->areas->tree()),
        ]);
    }

    public function store(StoreAreaRequest $request): RedirectResponse
    {
        $this->areas->create($request->validated());

        return redirect()->route('admin.areas.index')->with('success', 'Área creada correctamente.');
    }

    public function update(UpdateAreaRequest $request, string $id): RedirectResponse
    {
        $area = $this->areas->find($id);

        if ($area === null) {
            abort(404);
        }

        if ($this->areas->wouldCreateCycle($area, $request->validated('parent_id'))) {
            return redirect()->route('admin.areas.index')
                ->with('error', 'El área no puede moverse debajo de sí misma ni de una subárea.');
        }

        $this->areas->update($area, $request->validated());

        return redirect()->route('admin.areas.index')->with('success', 'Área actualizada correctamente.');
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $area = $this->areas->find($id);

        if ($area === null) {
            abort(404);
        }

        $deleted = $this->areas->delete($area);

        if (! $deleted) {
            return redirect()->route('admin.areas.index')
                ->with('error', 'No se puede eliminar el área: tiene subáreas, puestos o comunicaciones.');
        }

        return redirect()->route('admin.areas.index')->with('success', 'Área eliminada.');
    }
}
