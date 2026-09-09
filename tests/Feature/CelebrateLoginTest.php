<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Communication;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CelebrateLoginTest extends TestCase
{
    use RefreshDatabase;

    private Area $area;

    private User $top;

    private User $other;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->area = Area::create([
            'code' => 'CARTO',
            'name' => 'DEPARTAMENTO DE CARTOGRAFIA',
            'parent_id' => null,
            'is_active' => true,
        ]);

        $this->top = User::create([
            'name' => 'Top',
            'email' => 'top@carto.com',
            'password' => 'password',
            'is_active' => true,
        ]);
        $this->top->assignRole('usuario');

        $this->other = User::create([
            'name' => 'Other',
            'email' => 'other@carto.com',
            'password' => 'password',
            'is_active' => true,
        ]);
        $this->other->assignRole('usuario');
    }

    private function makeComms(User $user, int $count, string $date): void
    {
        for ($i = 0; $i < $count; $i++) {
            Communication::create([
                'type' => 'ci',
                'number' => "ci.carto.{$i}/2026",
                'year' => 2026,
                'sequence' => $i + 1,
                'area_id' => $this->area->id,
                'user_id' => $user->id,
                'reference' => 'Ref',
                'recipient_name' => 'Dest',
                'recipient_position' => 'TECNICO',
                'status' => 'activo',
            ])->forceFill([
                'created_at' => $date,
                'updated_at' => $date,
            ])->save();
        }
    }

    public function test_top_creator_yesterday_gets_celebrate_flash(): void
    {
        $yesterday = now()->subDay()->toDateString();
        $this->makeComms($this->top, 3, $yesterday);
        $this->makeComms($this->other, 1, $yesterday);

        $response = $this->post('/login', [
            'username' => 'top',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $response->assertSessionHas('celebrate', 3);
    }

    public function test_non_top_creator_does_not_get_celebrate_flash(): void
    {
        $yesterday = now()->subDay()->toDateString();
        $this->makeComms($this->top, 3, $yesterday);
        $this->makeComms($this->other, 1, $yesterday);

        $response = $this->post('/login', [
            'username' => 'other',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $response->assertSessionMissing('celebrate');
    }

    public function test_tied_creators_both_get_celebrate_flash(): void
    {
        $yesterday = now()->subDay()->toDateString();
        $this->makeComms($this->top, 2, $yesterday);
        $this->makeComms($this->other, 2, $yesterday);

        $this->post('/login', [
            'username' => 'top',
            'password' => 'password',
        ])->assertSessionHas('celebrate', 2);
    }
}
