<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatedCommunicationFlashTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_shares_created_communication(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $area = Area::create([
            'code' => 'carto',
            'name' => 'DEPARTAMENTO DE CARTOGRAFIA',
            'parent_id' => null,
            'is_active' => true,
        ]);

        $position = Position::create([
            'area_id' => $area->id,
            'code' => 'jefe',
            'name' => 'JEFE',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $user = User::create([
            'name' => 'Admin Test',
            'email' => 'admin-test@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);
        $user->assignRole('administrador');

        PositionAssignment::create([
            'user_id' => $user->id,
            'position_id' => $position->id,
            'started_at' => now(),
            'ended_at' => null,
        ]);

        $response = $this->actingAs($user)->post('/comunicaciones', [
            'type' => 'ci',
            'reference' => 'Comunicación de prueba',
            'recipient_name' => 'Destinatario de prueba',
            'recipient_position' => 'TECNICO',
            'area_destino_nombre' => 'DEPARTAMENTO DE CARTOGRAFIA',
        ]);

        $response->assertRedirect(route('communications.index'));

        $response->assertSessionHas('created', function (array $created) {
            return $created['number'] === 'ci.carto.0001/2026'
                && $created['type'] === 'ci'
                && ($created['area']['code'] ?? null) === 'carto'
                && ($created['area_destino_nombre'] ?? null) === 'DEPARTAMENTO DE CARTOGRAFIA';
        });
    }
}