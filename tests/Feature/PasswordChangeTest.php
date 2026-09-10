<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordChangeTest extends TestCase
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

    public function test_created_user_starts_with_must_change_password(): void
    {
        $this->actingAs($this->admin())->post('/admin/users', [
            'name' => 'Nuevo Usuario',
            'username' => 'nuevousuario',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasNoErrors();

        $this->assertTrue(
            (bool) User::where('email', 'nuevousuario@carto.com')->value('must_change_password')
        );
    }

    public function test_flagged_user_is_redirected_to_password_tab(): void
    {
        $admin = $this->admin();

        $user = User::create([
            'name' => 'Forzado',
            'email' => 'forzado@carto.com',
            'password' => 'password',
            'is_active' => true,
            'must_change_password' => true,
        ]);
        $user->assignRole('usuario');

        $this->actingAs($user)->get('/dashboard')
            ->assertRedirect(route('profile.edit', ['tab' => 'password']));

        $this->actingAs($user)->get('/comunicaciones')
            ->assertRedirect(route('profile.edit', ['tab' => 'password']));

        $this->actingAs($user)->get('/profile')->assertOk();

        $this->actingAs($admin)->get('/dashboard')->assertOk();
    }

    public function test_changing_password_clears_flag_and_rejects_reuse(): void
    {
        $this->admin();

        $user = User::create([
            'name' => 'Forzado',
            'email' => 'forzado@carto.com',
            'password' => 'password',
            'is_active' => true,
            'must_change_password' => true,
        ]);
        $user->assignRole('usuario');

        $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'password',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('password');

        $this->assertTrue((bool) $user->fresh()->must_change_password);

        $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'password',
            'password' => 'nueva-clave-123',
            'password_confirmation' => 'nueva-clave-123',
        ])->assertSessionHasNoErrors();

        $this->assertFalse((bool) $user->fresh()->must_change_password);
        $this->actingAs($user->fresh())->get('/dashboard')->assertOk();
    }

    public function test_admin_reset_reactivates_flag_and_switch_clears_it(): void
    {
        $admin = $this->admin();

        $user = User::create([
            'name' => 'Normal',
            'email' => 'normal@carto.com',
            'password' => 'password',
            'is_active' => true,
        ]);
        $user->assignRole('usuario');

        $this->actingAs($admin)->post("/admin/users/{$user->id}/reset-password", [
            'password' => 'otra-clave-123',
            'password_confirmation' => 'otra-clave-123',
        ])->assertSessionHasNoErrors();

        $this->assertTrue((bool) $user->fresh()->must_change_password);

        $this->actingAs($admin)->put("/admin/users/{$user->id}", [
            'name' => 'Normal',
            'username' => 'normal',
            'must_change_password' => false,
        ])->assertSessionHasNoErrors();

        $this->assertFalse((bool) $user->fresh()->must_change_password);
    }
}
