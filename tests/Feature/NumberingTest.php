<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\AreaNumberCounter;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NumberingTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::create([
            'name' => 'Admin Test',
            'email' => 'admin-test@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);
        $user->assignRole('administrador');

        return $user;
    }

    private function area(): Area
    {
        return Area::create([
            'code' => 'CARTO',
            'name' => 'DEPARTAMENTO DE CARTOGRAFIA',
            'parent_id' => null,
            'is_active' => true,
        ]);
    }

    public function test_numbering_index_can_be_rendered(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin/numbering');

        $response->assertOk();
    }

    public function test_numbering_index_requires_manage_areas(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::create([
            'name' => 'User Test',
            'email' => 'user-test@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);
        $user->assignRole('usuario');

        $this->actingAs($user)->get('/admin/numbering')->assertForbidden();
    }

    public function test_set_number_creates_missing_counter_row(): void
    {
        $area = $this->area();

        $this->actingAs($this->admin())->post("/admin/numbering/{$area->id}", [
            'type' => 'ci',
            'value' => 41,
        ])->assertSessionHasNoErrors();

        $this->assertSame(41, (int) AreaNumberCounter::where('area_id', $area->id)
            ->where('type', 'ci')
            ->where('year', now()->year)
            ->value('last_sequence'));
    }

    public function test_set_number_below_issued_is_blocked_without_force(): void
    {
        $admin = $this->admin();
        $area = $this->area();

        AreaNumberCounter::create([
            'area_id' => $area->id,
            'type' => 'of',
            'year' => now()->year,
            'last_sequence' => 30,
        ]);

        \App\Models\Communication::create([
            'type' => 'of',
            'number' => 'of.carto.0030/'.now()->year,
            'year' => now()->year,
            'sequence' => 30,
            'area_id' => $area->id,
            'user_id' => $admin->id,
            'reference' => 'Ref',
            'recipient_name' => 'Dest',
            'status' => 'activo',
        ]);

        $response = $this->actingAs($admin)->post("/admin/numbering/{$area->id}", [
            'type' => 'of',
            'value' => 10,
        ]);

        $response->assertRedirect(route('admin.numbering.index'));
        $response->assertSessionHas('error');

        $this->assertSame(30, (int) AreaNumberCounter::where('area_id', $area->id)
            ->where('type', 'of')
            ->where('year', now()->year)
            ->value('last_sequence'));
    }

    public function test_set_number_below_issued_passes_with_force(): void
    {
        $area = $this->area();

        $this->actingAs($this->admin())->post("/admin/numbering/{$area->id}", [
            'type' => 'ci',
            'value' => 5,
            'force' => true,
        ])->assertSessionHasNoErrors();

        $this->assertSame(5, (int) AreaNumberCounter::where('area_id', $area->id)
            ->where('type', 'ci')
            ->where('year', now()->year)
            ->value('last_sequence'));
    }
}
