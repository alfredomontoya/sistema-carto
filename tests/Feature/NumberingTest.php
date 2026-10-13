<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\AreaNumberCounter;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\HasCommunicationTypes;
use Tests\TestCase;

class NumberingTest extends TestCase
{
    use HasCommunicationTypes, RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::create([
            'name' => 'Admin Test',
            'ci' => '1000008',
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
            'ci' => '1000009',
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
        $type = $this->communicationType();

        $this->actingAs($this->admin())->post("/admin/numbering/{$area->id}", [
            'communication_type_id' => $type->id,
            'value' => 41,
        ])->assertSessionHasNoErrors();

        $this->assertSame(41, (int) AreaNumberCounter::where('area_id', $area->id)
            ->where('communication_type_id', $type->id)
            ->where('year', now()->year)
            ->value('last_sequence'));
    }

    public function test_set_number_below_issued_is_blocked_without_force(): void
    {
        $admin = $this->admin();
        $area = $this->area();
        $type = $this->communicationType('of');

        AreaNumberCounter::create([
            'area_id' => $area->id,
            'communication_type_id' => $type->id,
            'year' => now()->year,
            'last_sequence' => 30,
        ]);

        \App\Models\Communication::create([
            'communication_type_id' => $type->id,
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
            'communication_type_id' => $type->id,
            'value' => 10,
        ]);

        $response->assertRedirect(route('admin.numbering.index'));
        $response->assertSessionHas('error');

        $this->assertSame(30, (int) AreaNumberCounter::where('area_id', $area->id)
            ->where('communication_type_id', $type->id)
            ->where('year', now()->year)
            ->value('last_sequence'));
    }

    public function test_set_number_below_issued_passes_with_force(): void
    {
        $area = $this->area();
        $type = $this->communicationType();

        $this->actingAs($this->admin())->post("/admin/numbering/{$area->id}", [
            'communication_type_id' => $type->id,
            'value' => 5,
            'force' => true,
        ])->assertSessionHasNoErrors();

        $this->assertSame(5, (int) AreaNumberCounter::where('area_id', $area->id)
            ->where('communication_type_id', $type->id)
            ->where('year', now()->year)
            ->value('last_sequence'));
    }

    public function test_reset_all_types_sets_every_counter_to_zero(): void
    {
        $area = $this->area();
        $ci = $this->communicationType();
        $of = $this->communicationType('of');

        foreach ([$ci, $of] as $type) {
            AreaNumberCounter::create([
                'area_id' => $area->id,
                'communication_type_id' => $type->id,
                'year' => now()->year,
                'last_sequence' => 12,
            ]);
        }

        $this->actingAs($this->admin())->post("/admin/numbering/{$area->id}/reset", [
            'types' => [$ci->id, $of->id],
        ])->assertSessionHasNoErrors();

        foreach ([$ci, $of] as $type) {
            $this->assertSame(0, (int) AreaNumberCounter::where('area_id', $area->id)
                ->where('communication_type_id', $type->id)
                ->where('year', now()->year)
                ->value('last_sequence'));
        }
    }

    public function test_reset_all_is_blocked_when_numbers_were_issued_without_force(): void
    {
        $admin = $this->admin();
        $area = $this->area();
        $type = $this->communicationType();

        AreaNumberCounter::create([
            'area_id' => $area->id,
            'communication_type_id' => $type->id,
            'year' => now()->year,
            'last_sequence' => 7,
        ]);

        \App\Models\Communication::create([
            'communication_type_id' => $type->id,
            'number' => 'ci.carto.0007/'.now()->year,
            'year' => now()->year,
            'sequence' => 7,
            'area_id' => $area->id,
            'user_id' => $admin->id,
            'reference' => 'Ref',
            'recipient_name' => 'Dest',
            'status' => 'activo',
        ]);

        $response = $this->actingAs($admin)->post("/admin/numbering/{$area->id}/reset", [
            'types' => [],
        ]);

        $response->assertRedirect(route('admin.numbering.index'));
        $response->assertSessionHas('error');

        $this->assertSame(7, (int) AreaNumberCounter::where('area_id', $area->id)
            ->where('communication_type_id', $type->id)
            ->where('year', now()->year)
            ->value('last_sequence'));
    }

    public function test_reset_all_with_force_passes_despite_issued_numbers(): void
    {
        $admin = $this->admin();
        $area = $this->area();
        $type = $this->communicationType();

        AreaNumberCounter::create([
            'area_id' => $area->id,
            'communication_type_id' => $type->id,
            'year' => now()->year,
            'last_sequence' => 7,
        ]);

        \App\Models\Communication::create([
            'communication_type_id' => $type->id,
            'number' => 'ci.carto.0007/'.now()->year,
            'year' => now()->year,
            'sequence' => 7,
            'area_id' => $area->id,
            'user_id' => $admin->id,
            'reference' => 'Ref',
            'recipient_name' => 'Dest',
            'status' => 'activo',
        ]);

        $this->actingAs($admin)->post("/admin/numbering/{$area->id}/reset", [
            'types' => [],
            'force' => true,
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, (int) AreaNumberCounter::where('area_id', $area->id)
            ->where('communication_type_id', $type->id)
            ->where('year', now()->year)
            ->value('last_sequence'));
    }
}
