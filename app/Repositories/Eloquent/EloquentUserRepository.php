<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentUserRepository implements UserRepository
{
    public function find(string $id): ?User
    {
        return User::with(['roles', 'currentAssignment.position.area'])->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return User::query()
            ->with(['roles', 'currentAssignment.position.area'])
            ->when(
                $filters['search'] ?? null,
                fn ($query, string $search) => $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
            )
            ->when(
                $filters['role'] ?? null,
                fn ($query, string $role) => $query->role($role)
            )
            ->when(
                $filters['area_id'] ?? null,
                fn ($query, string $areaId) => $query->whereHas(
                    'currentAssignment',
                    fn ($query) => $query->whereHas(
                        'position',
                        fn ($query) => $query->where('area_id', $areaId)
                    )
                )
            )
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user;
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    public function search(string $term, int $limit = 10): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            })
            ->with(['currentAssignment.position.area'])
            ->limit($limit)
            ->get(['id', 'name', 'email']);
    }
}
