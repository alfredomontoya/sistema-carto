<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_lockout_message_is_visible_under_username(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 6; $i++) {
            $response = $this->from('/login')->post('/login', [
                'username' => $user->username,
                'password' => 'wrong-password',
            ]);
        }

        $response->assertSessionHasErrors('username');
    }

    public function test_self_password_change_cycles_remember_token(): void
    {
        $user = User::factory()->create();
        $oldToken = $user->remember_token;

        $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'password',
            'password' => 'nueva-clave-123',
            'password_confirmation' => 'nueva-clave-123',
        ])->assertSessionHasNoErrors();

        $this->assertNotSame($oldToken, $user->fresh()->remember_token);
    }

    public function test_admin_reset_cycles_remember_token(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-test@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);
        $admin->assignRole('administrador');

        $user = User::factory()->create();
        $oldToken = $user->remember_token;

        $this->actingAs($admin)->post("/admin/users/{$user->id}/reset-password", [
            'password' => 'otra-clave-123',
            'password_confirmation' => 'otra-clave-123',
        ])->assertSessionHasNoErrors();

        $this->assertNotSame($oldToken, $user->fresh()->remember_token);
    }

    public function test_remember_duration_is_thirty_days(): void
    {
        $this->assertSame(43200, config('auth.guards.web.remember'));
    }

    public function test_failover_mailer_is_configured(): void
    {
        $this->assertArrayHasKey('failover', config('mail.mailers'));
    }
}
