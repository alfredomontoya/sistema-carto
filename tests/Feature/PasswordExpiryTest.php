<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\UserService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordExpiryTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPasswordAge(int $daysAgo): User
    {
        $user = User::create([
            'name' => 'Vigencia',
            'email' => 'vigencia'.random_int(1000, 9999).'@carto.com',
            'password' => 'password',
            'is_active' => true,
        ]);
        $user->assignRole('usuario');
        $user->forceFill(['password_changed_at' => now()->subDays($daysAgo)])->save();

        return $user->fresh();
    }

    public function test_days_left_defaults_to_90_day_period(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $service = app(UserService::class);
        $this->assertSame(90, $service->passwordExpiryDays());
        $this->assertSame(2, $service->passwordDaysLeft($this->userWithPasswordAge(88)));
    }

    public function test_expiry_days_are_configurable(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        Setting::updateOrCreate(['key' => 'security.password_expiry_days'], ['value' => '30']);

        $service = app(UserService::class);
        $this->assertSame(30, $service->passwordExpiryDays());
        $this->assertSame(2, $service->passwordDaysLeft($this->userWithPasswordAge(28)));
    }

    public function test_login_warns_when_three_or_fewer_days_left(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = $this->userWithPasswordAge(88);

        $response = $this->post('/login', [
            'username' => explode('@', $user->email)[0],
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $response->assertSessionHas('warning');
    }

    public function test_login_does_not_warn_with_enough_days_left(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = $this->userWithPasswordAge(10);

        $response = $this->post('/login', [
            'username' => explode('@', $user->email)[0],
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard', absolute: false));
        $response->assertSessionMissing('warning');
    }

    public function test_admin_can_update_expiry_days(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin-test@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);
        $admin->assignRole('administrador');

        $this->actingAs($admin)->put('/admin/settings', [
            'app_name' => 'siscarto',
            'password_expiry_days' => 45,
            'password_recovery_enabled' => true,
        ])->assertSessionHasNoErrors();

        $this->assertSame('45', Setting::where('key', 'security.password_expiry_days')->value('value'));
        $this->assertSame(45, app(UserService::class)->passwordExpiryDays());
    }
}
