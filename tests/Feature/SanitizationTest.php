<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\User;
use App\Services\UserService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SanitizationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $area = Area::create([
            'code' => 'CARTO',
            'name' => 'DEPARTAMENTO DE CARTOGRAFIA',
            'parent_id' => null,
            'is_active' => true,
        ]);

        $position = Position::create([
            'area_id' => $area->id,
            'code' => 'JEFE',
            'name' => 'JEFE',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $user = User::create([
            'name' => 'Admin Test',
            'email' => UserService::emailFor('admin-test'),
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

        return $user;
    }

    public function test_user_store_sanitizes_fields(): void
    {
        $this->actingAs($this->admin())->post('/admin/users', [
            'name' => '  Juan   Pérez  ',
            'username' => '  JPerez  ',
            'password' => 'password',
            'password_confirmation' => 'password',
            'phone' => '+591 765-43-21',
            'address' => '  Calle   Falsa 123  ',
        ])->assertSessionHasNoErrors();

        $user = User::where('email', 'jperez@carto.com')->firstOrFail();

        $this->assertSame('Juan Pérez', $user->name);
        $this->assertSame('5917654321', $user->phone);
        $this->assertSame('Calle Falsa 123', $user->address);
    }

    public function test_area_store_uppercases_code_and_collapses_name(): void
    {
        $this->actingAs($this->admin())->post('/admin/areas', [
            'name' => '  Nueva   Área  ',
            'code' => 'nueva-area',
        ])->assertSessionHasNoErrors();

        $area = Area::where('code', 'NUEVA-AREA')->firstOrFail();

        $this->assertSame('Nueva Área', $area->name);
    }

    public function test_communication_store_collapses_spaces(): void
    {
        $this->actingAs($this->admin())->post('/comunicaciones', [
            'type' => 'ci',
            'reference' => '  Motivo   con   espacios  ',
            'recipient_name' => '  Destinatario   Prueba  ',
            'recipient_position' => '  TECNICO  ',
            'area_destino_nombre' => '  CARTOGRAFIA  ',
        ])->assertSessionHasNoErrors();

        $communication = \App\Models\Communication::firstOrFail();

        $this->assertSame('Motivo con espacios', $communication->reference);
        $this->assertSame('Destinatario Prueba', $communication->recipient_name);
        $this->assertSame('TECNICO', $communication->recipient_position);
        $this->assertSame('CARTOGRAFIA', $communication->area_destino_nombre);
    }

    public function test_login_accepts_username_with_spaces_and_uppercase(): void
    {
        $admin = $this->admin();

        $response = $this->post('/login', [
            'username' => '  ADMIN-TEST  ',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);
    }
}
