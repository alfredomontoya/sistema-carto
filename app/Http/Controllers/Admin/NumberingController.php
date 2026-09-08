<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SetAreaNumberingRequest;
use App\Services\AreaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NumberingController extends Controller
{
    public function __construct(
        private readonly AreaService $areas,
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

        $type = $request->validated('type');
        $value = (int) $request->validated('value');
        $force = $request->boolean('force');

        $set = $this->areas->setNumbering($area, $type, $value, $force);

        if (! $set) {
            return redirect()->route('admin.numbering.index')
                ->with('error', "No se puede fijar {$area->name} ({$type}) en {$value}: ya se emitieron números superiores. Usa forzar para continuar.");
        }

        $label = $type === 'ci' ? 'comunicaciones' : 'oficios';

        return redirect()->route('admin.numbering.index')
            ->with('success', "Numeración de {$label} de {$area->name} fijada en {$value}.");
    }
}
