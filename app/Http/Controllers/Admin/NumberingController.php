<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetAreaNumberingRequest;
use App\Http\Requests\Admin\SetAreaNumberingRequest;
use App\Services\AreaService;
use App\Services\CommunicationTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NumberingController extends Controller
{
    public function __construct(
        private readonly AreaService $areas,
        private readonly CommunicationTypeService $types,
    ) {}

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        return Inertia::render('Admin/Numbering/Index', [
            'rows' => $this->areas->numberingOverview(now()->year, $search === '' ? null : $search),
            'filters' => ['search' => $search],
            'year' => now()->year,
        ]);
    }

    public function setNumber(SetAreaNumberingRequest $request, string $id): RedirectResponse
    {
        $area = $this->areas->find($id);

        if ($area === null) {
            abort(404);
        }

        $type = $this->types->find($request->validated('communication_type_id'));

        if ($type === null) {
            abort(404);
        }

        $value = (int) $request->validated('value');
        $force = $request->boolean('force');

        $set = $this->areas->setNumbering($area, $type->id, $value, $force);

        if (! $set) {
            return redirect()->route('admin.numbering.index')
                ->with('error', "No se puede fijar {$area->name} ({$type->code}) en {$value}: ya se emitieron números superiores. Usa forzar para continuar.");
        }

        return redirect()->route('admin.numbering.index')
            ->with('success', "Numeración de {$type->name} de {$area->name} fijada en {$value}.");
    }

    public function resetAll(ResetAreaNumberingRequest $request, string $id): RedirectResponse
    {
        $area = $this->areas->find($id);

        if ($area === null) {
            abort(404);
        }

        $typeIds = $request->validated('types') ?? [];
        $force = $request->boolean('force');

        $reset = $this->areas->resetNumbering($area, $typeIds, $force);

        if (! $reset) {
            return redirect()->route('admin.numbering.index')
                ->with('error', "No se puede reiniciar {$area->name}: ya se emitieron números este año. Usa forzar para continuar.");
        }

        return redirect()->route('admin.numbering.index')
            ->with('success', "Numeración de {$area->name} reiniciada: el siguiente correlativo será el 1.");
    }
}
