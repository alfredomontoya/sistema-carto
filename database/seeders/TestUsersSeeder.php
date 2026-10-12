<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    private const FACTORY_COUNT = 0;

    /**
     * Create test users (password 'password' for all): 3 fixed ones plus
     * 0 from the factory, each assigned to ABOGADO /
     * DEPARTAMENTO DE CARTOGRAFIA. Users that already hold a position
     * keep it.
     * Run with: php artisan db:seed --class=TestUsersSeeder
     */
    public function run(): void
    {
        $userService = app(UserService::class);

        $users = [
            ['name' => 'Tatiana Parada Coimbra', 'ci' => '4709978', 'username' => 'tparada', 'role' => 'user'],
            ['name' => 'Maria Silvana Wesember Zelaya', 'ci' => '6255105', 'username' => 'mswesember', 'role' => 'user'],
            ['name' => 'Paola Ydem Ordoñez Roca', 'ci' => '8195426', 'username' => 'pyordonez', 'role' => 'user'],
        ];

        foreach ($users as $entry) {
            $user = User::updateOrCreate(
                ['email' => UserService::emailFor($entry['username'])],
                [
                    'name' => $entry['name'],
                    'ci' => $entry['ci'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'avatar_kind' => 'gallery',
                    'avatar_value' => 'ocean',
                ],
            );

            $user->assignRole('usuario');
            $position = $this->lawyerPosition();

            $this->assignIfVacant($userService, $user, $position);

            $area = $position?->area?->code ?? '?';
            $this->command?->info("Usuario creado: {$entry['name']} ({$entry['username']}) -> {$position?->code} / {$area}");
        }

        User::factory()->count(self::FACTORY_COUNT)->create()->each(function (User $user) use ($userService) {
            $user->assignRole('usuario');
            $position = $this->lawyerPosition();

            $this->assignIfVacant($userService, $user, $position);
        });

        $this->command?->info(self::FACTORY_COUNT.' usuarios aleatorios creados con el factory.');
    }

    private function lawyerPosition(): ?Position
    {
        return Position::where('code', 'abogado')
            ->whereHas('area', fn ($query) => $query->where('code', 'carto'))
            ->first();
    }

    private function assignIfVacant(UserService $userService, User $user, ?Position $position): void
    {
        if ($position === null) {
            return;
        }

        if ($user->currentAssignment()->first() !== null) {
            return;
        }

        $userService->assignPosition($user, $position->id);
    }
}
