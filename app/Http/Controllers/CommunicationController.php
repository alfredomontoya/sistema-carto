<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommunicationRequest;
use App\Http\Requests\UpdateCommunicationRequest;
use App\Http\Resources\AreaResource;
use App\Http\Resources\CommunicationResource;
use App\Models\Communication;
use App\Services\AreaService;
use App\Services\CommunicationService;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommunicationController extends Controller
{
    public function __construct(
        private readonly CommunicationService $communications,
        private readonly UserService $users,
        private readonly AreaService $areas,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $request->only([
            'search',
            'type',
            'area_id',
            'user_id',
            'recipient_user_id',
            'recipient_name',
            'number',
            'date_from',
            'date_to',
            'status',
        ]);

        if (! array_key_exists('area_id', $filters)) {
            $request->user()->loadMissing('currentAssignment.position.area');
            $filters['area_id'] = $request->user()->currentArea?->id ?? 'all';
        }

        $records = $this->communications->paginate(
            $filters,
            (int) $request->integer('per_page', 10),
        );

        return Inertia::render('Communications/Index', [
            'areas' => AreaResource::collection($this->areas->all())->resolve(),
            'filters' => $filters,
            'communications' => Inertia::defer(fn () => CommunicationResource::collection($records)->resolve()),
            'pagination' => Inertia::defer(fn () => [
                'total' => $records->total(),
                'per_page' => $records->perPage(),
                'current_page' => $records->currentPage(),
                'last_page' => $records->lastPage(),
            ]),
        ]);
    }

    public function create(Request $request): Response
    {
        $request->user()->load(['currentAssignment.position.area']);

        return Inertia::render('Communications/Create', [
            'counters' => $this->communications->countersFor($request->user(), now()->year),
            'current_area' => $request->user()->currentArea?->name,
            'year' => now()->year,
        ]);
    }

    public function store(StoreCommunicationRequest $request): RedirectResponse
    {
        $communication = $this->communications->create(
            $request->user(),
            $request->safe()->except(['file']),
            $request->file('file'),
        );

        $communication->load([
            'area',
            'user.currentAssignment.position.area',
            'position',
            'recipientUser.currentAssignment.position.area',
        ]);

        return redirect()
            ->route('communications.index')
            ->with('success', "Comunicación {$communication->number} registrada correctamente.")
            ->with('created', CommunicationResource::make($communication)->resolve());
    }

    public function edit(Request $request, string $id): Response
    {
        $communication = $this->communications->find($id);

        if ($communication === null) {
            abort(404);
        }

        $this->authorize('edit', $communication);

        $communication->load(['area', 'user', 'recipientUser']);

        return Inertia::render('Communications/Edit', [
            'communication' => CommunicationResource::make($communication)->resolve(),
        ]);
    }

    public function update(UpdateCommunicationRequest $request, string $id): RedirectResponse
    {
        $communication = $this->communications->find($id);

        if ($communication === null) {
            abort(404);
        }

        $this->authorize('edit', $communication);

        $this->communications->update(
            $communication,
            $request->safe()->except(['file']),
            $request->file('file'),
        );

        return redirect()
            ->route('communications.index')
            ->with('success', 'Comunicación actualizada correctamente.');
    }

    public function annul(Request $request, string $id): RedirectResponse
    {
        $communication = $this->communications->find($id);

        if ($communication === null) {
            abort(404);
        }

        $this->authorize('edit', $communication);

        if ($communication->status !== Communication::STATUS_ACTIVE) {
            return redirect()->route('communications.index')->with('error', 'El registro ya no está activo.');
        }

        $this->communications->annul($communication);

        return redirect()
            ->route('communications.index')
            ->with('success', "La comunicación {$communication->number} fue anulada.");
    }

    public function download(Request $request, string $id): StreamedResponse
    {
        $communication = $this->communications->find($id);

        if ($communication === null || $communication->file_path === null) {
            abort(404);
        }

        return Storage::disk('public')->download(
            $communication->file_path,
            $communication->file_name ?? basename($communication->file_path),
        );
    }
}
