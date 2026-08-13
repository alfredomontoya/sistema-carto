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
        $email = env('ADMIN_EMAIL') !== null
            ? env('ADMIN_EMAIL')
            : UserService::emailFor(env('ADMIN_USERNAME', 'admin'));

        $admin = User::updateOrCreate(
            ['email' => $email],
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
