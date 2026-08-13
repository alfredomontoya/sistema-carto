<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    /**
     * Create test users, one per seeded position.
     * Run with: php artisan db:seed --class=TestUsersSeeder
     */
    public function run(): void
    {
        $users = [
            ['name' => 'Romer Teddy', 'email' => 'jefe@example.com', 'position' => 'jefe'],
            ['name' => 'Cuma Albert', 'email' => 'tecnico@example.com', 'position' => 'tecnico'],
            ['name' => 'Ana Paula', 'email' => 'abogado@example.com', 'position' => 'abogado'],
            ['name' => 'Yeny Ortiz', 'email' => 'secretaria@example.com', 'position' => 'secretaria'],
            ['name' => 'Ramiro Renteria', 'email' => 'asistente@example.com', 'position' => 'asistente'],
        ];

        foreach ($users as $entry) {
            $position = Position::where('code', $entry['position'])
                ->whereHas('area', fn ($query) => $query->where('code', 'carto'))
                ->first();
            $user = User::updateOrCreate(
                ['email' => $entry['email']],
                [
                    'name' => $entry['name'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'avatar_kind' => 'gallery',
                    'avatar_value' => 'ocean',
                ],
            );

            $user->assignRole('usuario');

            if ($position !== null) {
                $current = $user->currentAssignment;
                if ($current === null || $current->position_id !== $position->id) {
                    if ($current !== null) {
                        $current->update(['ended_at' => now()]);
                    }
                    \App\Models\PositionAssignment::create([
                        'position_id' => $position->id,
                        'user_id' => $user->id,
                        'started_at' => now(),
                        'ended_at' => null,
                    ]);
                }
            }

            $this->command?->info("Usuario creado: {$entry['name']} ({$entry['email']}) -> {$entry['position']}");
        }
    }
}
