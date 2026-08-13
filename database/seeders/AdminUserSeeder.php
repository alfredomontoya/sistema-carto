<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@example.com')],
            [
                'name' => 'Administrador',
                'password' => env('ADMIN_PASSWORD', 'password'),
                'is_active' => true,
                'avatar_kind' => 'gallery',
                'avatar_value' => 'ocean',
            ],
        );

        $admin->assignRole('administrador');

        $jefe = Position::where('code', 'jefe')
            ->whereHas('area', fn ($query) => $query->where('code', 'carto'))
            ->first();

        if ($jefe !== null && $admin->currentAssignment === null) {
            PositionAssignment::create([
                'position_id' => $jefe->id,
                'user_id' => $admin->id,
                'started_at' => now(),
                'ended_at' => null,
            ]);
        }
    }
}
