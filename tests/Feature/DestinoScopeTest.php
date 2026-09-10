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
use Tests\TestCase;

class DestinoScopeTest extends TestCase
{
    use RefreshDatabase;

    private Area $root;

    private Area $child;

    private Area $other;

    private function setupTree(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->root = Area::create([
            'code' => 'ROOT', 'name' => 'ROOT AREA', 'parent_id' => null, 'is_active' => true,
        ]);
        $this->child = Area::create([
            'code' => 'CHILD', 'name' => 'CHILD AREA', 'parent_id' => $this->root->id, 'is_active' => true,
        ]);
        $this->other = Area::create([
            'code' => 'OTHER', 'name' => 'OTHER AREA', 'parent_id' => null, 'is_active' => true,
        ]);
    }

    private function makeUser(string $name, string $username, string $role, Area $area): User
    {
        $position = Position::create([
            'area_id' => $area->id, 'code' => 'J-'.$username, 'name' => 'JEFE',
            'is_active' => true, 'sort_order' => 0,
        ]);

        $user = User::create([
            'name' => $name,
            'email' => $username.'@carto.com',
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

    private function makeComm(User $author, Area $destino): void
    {
        Communication::create([
            'type' => 'ci',
            'number' => 'ci.x.'.random_int(1000, 9999).'/2026',
            'year' => 2026,
            'sequence' => random_int(1000, 9999),
            'area_id' => $author->currentAssignment()->first()->position->area_id,
            'user_id' => $author->id,
            'reference' => 'Ref',
            'recipient_name' => 'Dest',
            'recipient_position' => 'T',
            'area_destino_id' => $destino->id,
            'area_destino_nombre' => $destino->name,
            'status' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_admin_sees_all_areas_including_empty(): void
    {
        $this->setupTree();
        $admin = $this->makeUser('Admin', 'adminx', 'administrador', $this->root);
        $author = $this->makeUser('Autor', 'autor', 'usuario', $this->root);
        $this->makeComm($author, $this->child);

        $stats = app(CommunicationService::class)
            ->getDestinoStats($admin->fresh(), '2020-01-01', '2030-01-01');

        $codes = array_column($stats, 'destino');
        $this->assertContains('child', array_map('strtolower', $codes));
        $this->assertCount(3, $stats);

        $empty = collect($stats)->firstWhere('destino', 'ROOT');
        $this->assertNotNull($empty);
        $this->assertSame(0, $empty['total']);
    }

    public function test_jefe_sees_only_own_subtree(): void
    {
        $this->setupTree();
        $jefe = $this->makeUser('Jefe', 'jefex', 'jefe', $this->root);
        $author = $this->makeUser('Autor', 'autor', 'usuario', $this->root);
        $this->makeComm($author, $this->child);
        $this->makeComm($author, $this->other);

        $stats = app(CommunicationService::class)
            ->getDestinoStats($jefe->fresh(), '2020-01-01', '2030-01-01');

        $codes = array_map('strtolower', array_column($stats, 'destino'));
        $this->assertContains('root', $codes);
        $this->assertContains('child', $codes);
        $this->assertNotContains('other', $codes);
    }

    public function test_basic_user_keeps_legacy_rows(): void
    {
        $this->setupTree();
        $author = $this->makeUser('Autor', 'autor', 'usuario', $this->root);
        $this->makeComm($author, $this->child);

        $stats = app(CommunicationService::class)
            ->getDestinoStats($author->fresh(), '2020-01-01', '2030-01-01');

        $this->assertCount(1, $stats);
        $this->assertSame('CHILD', $stats[0]['destino']);
    }
}
