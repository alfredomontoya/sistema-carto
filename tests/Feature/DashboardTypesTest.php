<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Communication;
use App\Models\Position;
use App\Models\PositionAssignment;
use App\Models\User;
use App\Services\CommunicationService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\HasCommunicationTypes;
use Tests\TestCase;

class DashboardTypesTest extends TestCase
{
    use HasCommunicationTypes, RefreshDatabase;

    private Area $carto;

    private Area $taes;

    private User $admin;

    private User $jefe;

    private User $basic;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->carto = Area::create([
            'code' => 'carto',
            'name' => 'DEPARTAMENTO DE CARTOGRAFIA',
            'is_active' => true,
        ]);

        $this->taes = Area::create([
            'code' => 'taes',
            'name' => 'DEPARTAMENTO DE TAES',
            'is_active' => true,
        ]);

        $this->admin = $this->makeUser('adminx@carto.com', 'jefe', $this->carto, 'administrador', 'Admin');
        $this->jefe = $this->makeUser('jefex@carto.com', 'jefe2', $this->carto, 'jefe', 'Jefe');
        $this->basic = $this->makeUser('userx@carto.com', 'tecnico', $this->taes, 'usuario', 'Usuario');
    }

    public function test_monthly_stats_carry_dynamic_type_keys(): void
    {
        $this->createCommunication($this->carto, 'ci', 'ci.carto.0001/'.now()->year);
        $this->createCommunication($this->carto, 'of.ext', 'of.ext.carto.0001/'.now()->year);
        $this->createCommunication($this->taes, 'itl', 'itl.taes.0001/'.now()->year);

        $rows = app(CommunicationService::class)->getDashboardStats($this->admin->fresh(), now()->year, 'all');

        $this->assertNotEmpty($rows);
        $this->assertArrayHasKey('of.ext', $rows[0]);
        $this->assertArrayHasKey('itl', $rows[0]);
        $this->assertArrayNotHasKey('of', $rows[0]);
        $this->assertSame(1, array_sum(array_column($rows, 'ci')));
        $this->assertSame(1, array_sum(array_column($rows, 'of.ext')));
        $this->assertSame(1, array_sum(array_column($rows, 'itl')));
        $this->assertSame(3, array_sum(array_column($rows, 'total')));
    }

    public function test_admin_area_filter_scopes_stats(): void
    {
        $this->createCommunication($this->carto, 'ci', 'ci.carto.0001/'.now()->year);
        $this->createCommunication($this->carto, 'ci', 'ci.carto.0002/'.now()->year);
        $this->createCommunication($this->taes, 'ci', 'ci.taes.0001/'.now()->year);

        $service = app(CommunicationService::class);
        $year = now()->year;

        $own = $service->getDashboardStats($this->admin->fresh(), $year);
        $this->assertSame(2, array_sum(array_column($own, 'total')));

        $other = $service->getDashboardStats($this->admin->fresh(), $year, $this->taes->id);
        $this->assertSame(1, array_sum(array_column($other, 'total')));

        $all = $service->getDashboardStats($this->admin->fresh(), $year, 'all');
        $this->assertSame(3, array_sum(array_column($all, 'total')));
    }

    public function test_basic_user_ignores_area_filter(): void
    {
        $this->createCommunication($this->carto, 'ci', 'ci.carto.0001/'.now()->year);
        $this->createCommunication($this->taes, 'ci', 'ci.taes.0001/'.now()->year);

        $rows = app(CommunicationService::class)
            ->getDashboardStats($this->basic->fresh(), now()->year, $this->carto->id);

        $this->assertSame(1, array_sum(array_column($rows, 'total')));
    }

    public function test_dashboard_page_exposes_types_and_filter_props(): void
    {
        $this->communicationType('ci');
        $this->communicationType('of.ext');

        $response = $this->actingAs($this->admin)->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('communicationTypes', 2)
            ->where('canFilterAreas', true)
            ->where('selectedAreaId', null)
            ->has('filterAreas')
        );

        $response = $this->actingAs($this->admin)->get('/dashboard?area_id=all');

        $response->assertInertia(fn ($page) => $page
            ->where('selectedAreaId', 'all')
            ->where('canFilterAreas', true)
        );

        $response = $this->actingAs($this->basic)->get('/dashboard?area_id=all');

        $response->assertInertia(fn ($page) => $page
            ->where('canFilterAreas', false)
            ->where('selectedAreaId', null)
        );
    }

    public function test_login_redirects_by_role(): void
    {
        $this->post('/login', ['username' => 'adminx@carto.com', 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->post('/logout');

        $this->post('/login', ['username' => 'jefex@carto.com', 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->post('/logout');

        $this->post('/login', ['username' => 'userx@carto.com', 'password' => 'password'])
            ->assertRedirect(route('communications.index', absolute: false));
    }

    private function makeUser(string $email, string $positionCode, Area $area, string $role, string $name): User
    {
        $position = Position::create([
            'area_id' => $area->id,
            'code' => $positionCode,
            'name' => strtoupper($positionCode),
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $user = User::create([
            'name' => $name,
            'ci' => fake()->unique()->numerify('#######'),
            'email' => $email,
            'password' => 'password',
            'is_active' => true,
        ]);
        $user->assignRole($role);

        PositionAssignment::create([
            'user_id' => $user->id,
            'position_id' => $position->id,
            'started_at' => now(),
            'ended_at' => null,
        ]);

        return $user;
    }

    private function createCommunication(Area $area, string $type, string $number): void
    {
        Communication::create([
            'communication_type_id' => $this->communicationType($type)->id,
            'number' => $number,
            'year' => now()->year,
            'sequence' => Communication::where('area_id', $area->id)->count() + 1,
            'area_id' => $area->id,
            'user_id' => $this->admin->id,
            'reference' => "Comunicación para {$area->code}",
            'recipient_name' => 'Destinatario',
            'status' => Communication::STATUS_ACTIVE,
        ]);
    }
}
