<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\RoleRepository;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

class EloquentRoleRepository implements RoleRepository
{
    public function all(): Collection
    {
        return Role::orderBy('name')->get(['id', 'name']);
    }

    public function syncUserRoles(User $user, array $roleIds): void
    {
        $user->syncRoles($roleIds);
    }

    public function userRoles(User $user): Collection
    {
        return $user->roles()->get(['id', 'name']);
    }
}
