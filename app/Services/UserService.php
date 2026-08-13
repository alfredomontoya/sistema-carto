<?php

namespace App\Services;

use App\Models\PositionAssignment;
use App\Models\User;
use App\Repositories\Contracts\PositionRepository;
use App\Repositories\Contracts\RoleRepository;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PositionRepository $positions,
        private readonly RoleRepository $roles,
    ) {}

    /**
     * Access to the underlying repository for querying.
     */
    public function repository(): UserRepository
    {
        return $this->users;
    }

    /**
     * Create a user. The admin is the only one who can do this.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $roleIds
     */
    public function create(array $data, array $roleIds = [], ?string $positionId = null): User
    {
        return DB::transaction(function () use ($data, $roleIds, $positionId): User {
            $user = $this->users->create($data);

            if ($roleIds !== []) {
                $this->roles->syncUserRoles($user, $roleIds);
            }

            if ($positionId !== null) {
                $this->assignPosition($user, $positionId);
            }

            return $user;
        });
    }

    /**
     * Update the user's basic profile data.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(User $user, array $data): User
    {
        return $this->users->update($user, $data);
    }

    public function updatePassword(User $user, string $password): User
    {
        return $this->users->update($user, ['password' => $password]);
    }

    /**
     * Admin resets a user's password (forces re-login).
     */
    public function resetPassword(User $user, string $password): void
    {
        $this->users->update($user, ['password' => $password]);

        DB::table('sessions')->where('user_id', $user->id)->delete();
    }

    /**
     * @param  array<int, string>  $roleIds
     */
    public function syncRoles(User $user, array $roleIds): void
    {
        $this->roles->syncUserRoles($user, $roleIds);
    }

    public function assignPosition(User $user, string $positionId): void
    {
        $position = $this->positions->find($positionId);
        if ($position === null) {
            throw new \InvalidArgumentException('El puesto no existe.');
        }

        DB::transaction(function () use ($user, $position): void {
            $current = $user->currentAssignment()->first();
            if ($current !== null) {
                if ($current->position_id === $position->id) {
                    return;
                }
                $current->update(['ended_at' => now()]);
            }

            PositionAssignment::create([
                'position_id' => $position->id,
                'user_id' => $user->id,
                'started_at' => now(),
                'ended_at' => null,
            ]);
        });
    }

    /**
     * Remove the user from their current position (keeps history).
     */
    public function removeFromCurrentPosition(User $user): void
    {
        $current = $user->currentAssignment()->first();
        if ($current !== null) {
            $current->update(['ended_at' => now()]);
        }
    }

    /**
     * @return Collection<int, \Spatie\Permission\Models\Role>
     */
    public function availableRoles(): Collection
    {
        return $this->roles->all();
    }

    public function toggleActive(User $user, bool $active): void
    {
        $this->users->update($user, ['is_active' => $active]);

        if (! $active) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
    }

    /**
     * Deletes a user. Refuses if the user created communications, so the
     * correlative numbers are never lost by cascade.
     *
     * @return bool true when deleted, false when blocked
     */
    public function delete(User $user): bool
    {
        if ($user->communications()->exists()) {
            return false;
        }

        $this->users->delete($user);

        return true;
    }
}
