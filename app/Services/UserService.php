<?php

namespace App\Services;

use App\Models\PositionAssignment;
use App\Models\User;
use App\Repositories\Contracts\PositionRepository;
use App\Repositories\Contracts\RoleRepository;
use App\Repositories\Contracts\SettingsRepository;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserService
{
    public const PASSWORD_EXPIRY_DAYS_DEFAULT = 90;

    public function __construct(
        private readonly UserRepository $users,
        private readonly PositionRepository $positions,
        private readonly RoleRepository $roles,
        private readonly SettingsRepository $settings,
    ) {}

    /**
     * Derive the full email from a username using the configured domain.
     * Accepts a full email too (kept as-is).
     */
    public static function emailFor(string $username): string
    {
        $username = Str::lower(Str::trim($username));
        $domain = (string) config('auth.user_domain', 'carto');

        return Str::contains($username, '@') ? $username : $username.'@'.$domain;
    }

    /**
     * Access to the underlying repository for querying.
     */
    public function repository(): UserRepository
    {
        return $this->users;
    }

    /**
     * Create a user. The admin is the only one who can do this.
     * New users must change their password on first login, unless
     * the caller explicitly passes must_change_password as false
     * (e.g. demo seeders).
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, string>  $roleIds
     */
    public function create(array $data, array $roleIds = [], ?string $positionId = null): User
    {
        $data['must_change_password'] = $data['must_change_password'] ?? true;
        $data['password_changed_at'] = $data['password_changed_at'] ?? now();

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
        return $this->users->update($user, ['password' => $password, 'password_changed_at' => now()]);
    }

    /**
     * Admin resets a user's password (forces re-login and a password change).
     */
    public function resetPassword(User $user, string $password): void
    {
        $this->users->update($user, [
            'password' => $password,
            'must_change_password' => true,
            'password_changed_at' => now(),
        ]);

        DB::table('sessions')->where('user_id', $user->id)->delete();
    }

    /**
     * Force (or release) the password-change requirement without
     * touching the password itself.
     */
    public function setMustChangePassword(User $user, bool $value): void
    {
        $this->users->update($user, ['must_change_password' => $value]);
    }

    /**
     * Configured password lifetime in days.
     */
    public function passwordExpiryDays(): int
    {
        return max(1, (int) $this->settings->get(
            'security.password_expiry_days',
            self::PASSWORD_EXPIRY_DAYS_DEFAULT,
        ));
    }

    /**
     * Days left before the user's password expires. May be negative
     * when already expired. Falls back to account creation when the
     * password timestamp is unknown.
     */
    public function passwordDaysLeft(User $user): int
    {
        $since = $user->password_changed_at ?? $user->created_at ?? now();

        $elapsed = (int) $since->copy()->startOfDay()->diffInDays(now()->startOfDay(), false);

        return $this->passwordExpiryDays() - $elapsed;
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
            $current = $user->currentAssignment()->lockForUpdate()->first();
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
