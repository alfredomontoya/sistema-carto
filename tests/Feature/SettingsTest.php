<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
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

    public function test_settings_screen_can_be_rendered(): void
    {
        $response = $this->actingAs($this->admin())->get('/admin/settings');

        $response->assertOk();
    }

    public function test_app_name_can_be_updated_without_colors_or_files(): void
    {
        $response = $this->actingAs($this->admin())->put('/admin/settings', [
            'app_name' => 'Sistema Nuevo',
        ]);

        $response->assertRedirect(route('admin.settings.index'));
        $response->assertSessionHasNoErrors();

        $this->assertSame(
            'Sistema Nuevo',
            Setting::where('key', 'brand.app_name')->value('value')
        );
    }
}
