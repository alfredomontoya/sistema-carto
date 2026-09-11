<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    private const FACTORY_COUNT = 20;

    /**
     * Create test users (password 'password' for all): 5 fixed ones plus
     * 20 from the factory, each assigned a random position (any area
     * except OTRO). Users that already hold a position keep it.
     * Run with: php artisan db:seed --class=TestUsersSeeder
     */
    public function run(): void
    {
        $userService = app(UserService::class);

        $users = [
            ['name' => 'Romer Teddy', 'username' => 'jefe'],
            ['name' => 'Cumpa Albert', 'username' => 'tecnico'],
            ['name' => 'Ana', 'username' => 'abogado'],
            ['name' => 'Yeny Ortiz', 'username' => 'secretaria'],
            ['name' => 'Ramiro Renteria', 'username' => 'asistente'],
        ];

        foreach ($users as $entry) {
            $user = User::updateOrCreate(
                ['email' => UserService::emailFor($entry['username'])],
                [
                    'name' => $entry['name'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'avatar_kind' => 'gallery',
                    'avatar_value' => 'ocean',
                ],
            );

            $user->assignRole('usuario');
            $position = $this->randomPosition();

            $this->assignIfVacant($userService, $user, $position);

            $area = $position?->area?->code ?? '?';
            $this->command?->info("Usuario creado: {$entry['name']} ({$entry['username']}) -> {$position?->code} / {$area}");
        }

        User::factory()->count(self::FACTORY_COUNT)->create()->each(function (User $user) use ($userService) {
            $user->assignRole('usuario');
            $position = $this->randomPosition();

            $this->assignIfVacant($userService, $user, $position);
        });

        $this->command?->info(self::FACTORY_COUNT.' usuarios aleatorios creados con el factory.');
    }

    private function randomPosition(): ?Position
    {
        return Position::whereHas('area', fn ($query) => $query->where('code', '!=', 'otro'))
            ->inRandomOrder()
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
