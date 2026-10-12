<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admins = [
            ['name' => 'Alfredo Montoya Calderon', 'ci' => '4725253', 'username' => 'amontoya', 'role' => 'administrador', 'area' => 'sistemas', 'position' => 'soporte'],
            ['name' => 'Romer Teddy Fernandez', 'ci' => '111111', 'username' => 'rtfernandez', 'role' => 'administrador', 'area' => 'carto', 'position' => 'jefe'],
            ['name' => 'Cristian Marcelo Pizarroso', 'ci' => '22222', 'username' => 'cpizarroso', 'role' => 'administrador', 'area' => 'sistemas', 'position' => 'soporte'],
        ];

        foreach ($admins as $entry) {
            $user = User::updateOrCreate(
                ['email' => UserService::emailFor($entry['username'])],
                [
                    'name' => $entry['name'],
                    'ci' => $entry['ci'],
                    'password' => 'password',
                    'is_active' => true,
                    'avatar_kind' => 'gallery',
                    'avatar_value' => 'ocean',
                ],
            );

            $user->syncRoles([$entry['role']]);

            $position = Position::where('code', $entry['position'])
                ->whereHas('area', fn ($query) => $query->where('code', $entry['area']))
                ->first();

            if ($position !== null && $user->currentAssignment()->first() === null) {
                PositionAssignment::create([
                    'position_id' => $position->id,
                    'user_id' => $user->id,
                    'started_at' => now(),
                    'ended_at' => null,
                ]);
            }

            $this->command?->info("Admin creado: {$entry['name']} ({$entry['username']}) -> {$entry['position']} / {$entry['area']}");
        }

        $this->removeLegacyAdmin($admins);
    }

    /**
     * Remove the previous generic admin (ADMIN_EMAIL / ADMIN_USERNAME) when
     * it is not part of the new admin list. Kept when it owns
     * communications so correlative numbers are never lost.
     *
     * @param  array<int, array<string, string>>  $admins
     */
    private function removeLegacyAdmin(array $admins): void
    {
        $legacyEmail = env('ADMIN_EMAIL') !== null
            ? env('ADMIN_EMAIL')
            : UserService::emailFor(env('ADMIN_USERNAME', 'admin'));

        $knownEmails = array_map(
            fn (array $entry) => UserService::emailFor($entry['username']),
            $admins,
        );

        if (in_array($legacyEmail, $knownEmails, true)) {
            return;
        }

        $legacy = User::where('email', $legacyEmail)->first();

        if ($legacy === null) {
            return;
        }

        if ($legacy->communications()->exists()) {
            $this->command?->warn("Admin anterior conservado ({$legacyEmail}): tiene comunicaciones registradas.");

            return;
        }

        $legacy->delete();
        $this->command?->info("Admin anterior eliminado: {$legacyEmail}");
    }
}
