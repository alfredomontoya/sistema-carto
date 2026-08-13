<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetPasswordRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Services\AreaService;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $users,
        private readonly AreaService $areas,
    ) {}

    public function index(Request $request): Response
    {
        $users = $this->users->repository()->paginate(
            $request->only(['search', 'role', 'area_id']),
            (int) $request->integer('per_page', 15),
        );

        return Inertia::render('Admin/Users/Index', [
            'users' => UserResource::collection($users)->resolve(),
            'filters' => $request->only(['search', 'role', 'area_id']),
            'roles' => $this->users->availableRoles()->toArray(),
            'areas' => $this->areas->tree(),
            'pagination' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Create', [
            'roles' => $this->users->availableRoles()->toArray(),
            'areas' => $this->areas->tree(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = $this->users->create(
            [
                ...$request->safe()->except(['role_ids', 'position_id', 'password_confirmation', 'username']),
                'email' => UserService::emailFor($request->string('username')->toString()),
            ],
            $request->validated('role_ids') ?? [],
            $request->validated('position_id'),
        );

        return redirect()
            ->route('admin.users.edit', $user->id)
            ->with('success', 'Usuario creado correctamente.');
    }

    public function edit(string $id): Response
    {
        $user = $this->users->repository()->find($id);

        if ($user === null) {
            abort(404);
        }

        $user->load(['roles', 'currentAssignment.position.area', 'assignmentHistory.position.area']);

        return Inertia::render('Admin/Users/Edit', [
            'user' => UserResource::make($user)->resolve(),
            'roles' => $this->users->availableRoles()->toArray(),
            'areas' => $this->areas->tree(),
            'position_history' => $user->assignmentHistory->map(
                fn ($assignment) => [
                    'id' => $assignment->id,
                    'position_name' => $assignment->position->name,
                    'area_name' => $assignment->position->area->name,
                    'started_at' => $assignment->started_at?->toIso8601String(),
                    'ended_at' => $assignment->ended_at?->toIso8601String(),
                ]
            )->values()->all(),
        ]);
    }

    public function update(UpdateUserRequest $request, string $id): RedirectResponse
    {
        $user = $this->users->repository()->find($id);

        if ($user === null) {
            abort(404);
        }

        $this->users->updateProfile($user, [
            ...$request->safe()->except(['role_ids', 'position_id', 'username']),
            'email' => UserService::emailFor($request->string('username')->toString()),
        ]);

        if ($request->has('role_ids')) {
            $this->users->syncRoles($user, $request->validated('role_ids'));
        }

        if ($request->filled('position_id')) {
            $this->users->assignPosition($user, $request->string('position_id'));
        } elseif ($request->has('position_id') && $request->input('position_id') === null) {
            $this->users->removeFromCurrentPosition($user);
        }

        return redirect()
            ->route('admin.users.edit', $user->id)
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function resetPassword(ResetPasswordRequest $request, string $id): RedirectResponse
    {
        $user = $this->users->repository()->find($id);

        if ($user === null) {
            abort(404);
        }

        $this->users->resetPassword($user, $request->string('password'));

        return redirect()
            ->route('admin.users.edit', $user->id)
            ->with('success', 'Contraseña restablecida. El usuario deberá volver a iniciar sesión.');
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $user = $this->users->repository()->find($id);

        if ($user === null) {
            abort(404);
        }

        if ($user->id === $request->user()->id) {
            return redirect()->route('admin.users.index')->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $deleted = $this->users->delete($user);

        if (! $deleted) {
            return redirect()->route('admin.users.index')
                ->with('error', 'No se puede eliminar el usuario: tiene comunicaciones registradas. Desactívalo en su lugar.');
        }

        return redirect()->route('admin.users.index')->with('success', 'Usuario eliminado.');
    }
}
