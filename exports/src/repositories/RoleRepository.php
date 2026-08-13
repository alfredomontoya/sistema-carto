<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface RoleRepository
{
    /**
     * @return Collection<int, \Spatie\Permission\Models\Role>
     */
    public function all(): Collection;

    /**
     * @param  array<int, int|string>  $roleIds
     */
    public function syncUserRoles(User $user, array $roleIds): void;

    /**
     * @return Collection<int, \Spatie\Permission\Models\Role>
     */
    public function userRoles(User $user): Collection;
}
