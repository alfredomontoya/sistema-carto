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

    public function test_admin_can_update_communication_of_another_user(): void
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

        $author = User::create([
            'name' => 'Autor',
            'email' => 'autor@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);
        $author->assignRole('usuario');

        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin-test@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);
        $admin->assignRole('administrador');

        PositionAssignment::create([
            'user_id' => $author->id,
            'position_id' => $position->id,
            'started_at' => now(),
            'ended_at' => null,
        ]);

        $communication = \App\Models\Communication::create([
            'type' => 'ci',
            'number' => 'ci.carto.0001/2026',
            'year' => 2026,
            'sequence' => 1,
            'area_id' => $area->id,
            'user_id' => $author->id,
            'position_id' => $position->id,
            'reference' => 'Original',
            'recipient_name' => 'Dest',
            'recipient_position' => 'TECNICO',
            'area_destino_nombre' => 'CARTO',
            'status' => 'activo',
        ]);

        $response = $this->actingAs($admin)->put("/comunicaciones/{$communication->id}", [
            'reference' => 'Editada por admin',
            'recipient_name' => 'Dest',
            'recipient_position' => 'TECNICO',
            'area_destino_nombre' => 'CARTO',
        ]);

        $response->assertRedirect(route('communications.index'));
        $this->assertSame('Editada por admin', $communication->fresh()->reference);
    }

    public function test_non_author_cannot_update_communication(): void
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

        $author = User::create([
            'name' => 'Autor',
            'email' => 'autor@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);
        $author->assignRole('usuario');

        $other = User::create([
            'name' => 'Otro',
            'email' => 'otro@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);
        $other->assignRole('usuario');

        $communication = \App\Models\Communication::create([
            'type' => 'ci',
            'number' => 'ci.carto.0001/2026',
            'year' => 2026,
            'sequence' => 1,
            'area_id' => $area->id,
            'user_id' => $author->id,
            'position_id' => $position->id,
            'reference' => 'Original',
            'recipient_name' => 'Dest',
            'recipient_position' => 'TECNICO',
            'area_destino_nombre' => 'CARTO',
            'status' => 'activo',
        ]);

        $this->actingAs($other)->put("/comunicaciones/{$communication->id}", [
            'reference' => 'Intento ajeno',
            'recipient_name' => 'Dest',
            'recipient_position' => 'TECNICO',
            'area_destino_nombre' => 'CARTO',
        ])->assertForbidden();

        $this->assertSame('Original', $communication->fresh()->reference);
    }

    private function makeAuthoredCommunication(): array
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

        $author = User::create([
            'name' => 'Autor',
            'email' => 'autor@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);
        $author->assignRole('usuario');

        $admin = User::create([
            'name' => 'Admin Test',
            'email' => 'admin-test@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);
        $admin->assignRole('administrador');

        $communication = \App\Models\Communication::create([
            'type' => 'ci',
            'number' => 'ci.carto.0001/2026',
            'year' => 2026,
            'sequence' => 1,
            'area_id' => $area->id,
            'user_id' => $author->id,
            'position_id' => $position->id,
            'reference' => 'Original',
            'recipient_name' => 'Dest',
            'recipient_position' => 'TECNICO',
            'area_destino_nombre' => 'CARTO',
            'status' => 'activo',
        ]);

        return [$author, $admin, $communication];
    }

    public function test_admin_can_annul_any_communication(): void
    {
        [$author, $admin, $communication] = $this->makeAuthoredCommunication();

        $response = $this->actingAs($admin)->post("/comunicaciones/{$communication->id}/anular");

        $response->assertRedirect(route('communications.index'));
        $this->assertSame('anulado', $communication->fresh()->status);
    }

    public function test_author_cannot_annul_own_communication(): void
    {
        [$author, $admin, $communication] = $this->makeAuthoredCommunication();

        $this->actingAs($author)->post("/comunicaciones/{$communication->id}/anular")
            ->assertForbidden();

        $this->assertSame('activo', $communication->fresh()->status);
    }
}