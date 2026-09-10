<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Notifications\RecoveryResetLink;
use App\Notifications\VerifyRecoveryEmail;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    private function enableRecovery(): void
    {
        Setting::updateOrCreate(
            ['key' => 'security.password_recovery_enabled'],
            ['value' => '1'],
        );
    }

    private function user(string $username = 'recupera'): User
    {
        $user = User::create([
            'name' => 'Recupera',
            'email' => $username.'@carto.com',
            'password' => 'password',
            'is_active' => true,
        ]);
        $user->assignRole('usuario');

        return $user;
    }

    public function test_routes_are_404_when_disabled(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->get('/recuperar')->assertNotFound();
        $this->post('/recuperar', ['email' => 'a@b.com'])->assertNotFound();
    }

    public function test_setting_recovery_email_sends_verification(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->enableRecovery();
        Notification::fake();

        $user = $this->user();

        $this->actingAs($user)->patch('/profile', [
            'recovery_email' => 'personal@ejemplo.com',
        ])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('personal@ejemplo.com', $user->recovery_email);
        $this->assertNull($user->recovery_email_verified_at);
        Notification::assertSentOnDemand(VerifyRecoveryEmail::class);
    }

    public function test_verify_link_activates_email(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->enableRecovery();
        Notification::fake();

        $user = $this->user();
        $user->forceFill([
            'recovery_email' => 'personal@ejemplo.com',
            'recovery_email_verified_at' => null,
        ])->save();

        $url = URL::temporarySignedRoute(
            'recovery.verify',
            now()->addHours(24),
            ['user' => $user->id, 'email' => 'personal@ejemplo.com'],
        );

        // visit the signed path only (host differs in tests)
        $path = parse_url($url, PHP_URL_PATH).'?'.parse_url($url, PHP_URL_QUERY);
        $this->get($path)->assertRedirect(route('recovery.request'));

        $this->assertNotNull($user->fresh()->recovery_email_verified_at);
    }

    public function test_request_sends_link_only_for_verified_email(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->enableRecovery();
        Notification::fake();

        $verified = $this->user('verificado');
        $verified->forceFill([
            'recovery_email' => 'verificado@ejemplo.com',
            'recovery_email_verified_at' => now(),
        ])->save();

        $unverified = $this->user('sinverificar');
        $unverified->forceFill(['recovery_email' => 'nuevo@ejemplo.com'])->save();

        $this->post('/recuperar', ['email' => 'verificado@ejemplo.com'])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, DB::table('password_recovery_tokens')->count());
        Notification::assertSentOnDemand(RecoveryResetLink::class);

        Notification::fake();

        $this->post('/recuperar', ['email' => 'nuevo@ejemplo.com'])
            ->assertSessionHasNoErrors();
        $this->post('/recuperar', ['email' => 'nadie@ejemplo.com'])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, DB::table('password_recovery_tokens')->count());
        Notification::assertNothingSent();
    }

    public function test_reset_with_valid_token_changes_password_without_forcing_change(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->enableRecovery();

        $user = $this->user();
        $user->forceFill([
            'recovery_email' => 'personal@ejemplo.com',
            'recovery_email_verified_at' => now(),
        ])->save();

        $this->post('/recuperar', ['email' => 'personal@ejemplo.com'])
            ->assertSessionHasNoErrors();

        // retrieve the raw token is impossible (hashed); use a known one
        DB::table('password_recovery_tokens')->where('email', 'personal@ejemplo.com')->delete();
        DB::table('password_recovery_tokens')->insert([
            'email' => 'personal@ejemplo.com',
            'token' => Hash::make('token-secreto'),
            'created_at' => now(),
        ]);

        $response = $this->post('/recuperar/restablecer', [
            'email' => 'personal@ejemplo.com',
            'token' => 'token-secreto',
            'password' => 'nueva-clave-123',
            'password_confirmation' => 'nueva-clave-123',
        ]);

        $response->assertRedirect(route('login'));
        $user->refresh();
        $this->assertTrue(Hash::check('nueva-clave-123', $user->password));
        $this->assertFalse((bool) $user->must_change_password);
        $this->assertSame(0, DB::table('password_recovery_tokens')->count());
    }

    public function test_reset_with_invalid_or_expired_token_fails(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->enableRecovery();

        $user = $this->user();
        $user->forceFill([
            'recovery_email' => 'personal@ejemplo.com',
            'recovery_email_verified_at' => now(),
        ])->save();

        DB::table('password_recovery_tokens')->insert([
            'email' => 'personal@ejemplo.com',
            'token' => Hash::make('token-bueno'),
            'created_at' => now()->subHours(2),
        ]);

        $this->post('/recuperar/restablecer', [
            'email' => 'personal@ejemplo.com',
            'token' => 'token-malo',
            'password' => 'nueva-clave-123',
            'password_confirmation' => 'nueva-clave-123',
        ])->assertSessionHasErrors('email');

        $this->post('/recuperar/restablecer', [
            'email' => 'personal@ejemplo.com',
            'token' => 'token-bueno',
            'password' => 'nueva-clave-123',
            'password_confirmation' => 'nueva-clave-123',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }
}
