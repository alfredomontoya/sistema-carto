<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'phone' => '+591 765-43-21',
                'address' => 'Calle Falsa 123',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('5917654321', $user->phone);
        $this->assertSame('Calle Falsa 123', $user->address);
    }

    public function test_name_and_username_cannot_be_updated_from_profile(): void
    {
        $user = User::factory()->create();
        $originalName = $user->name;
        $originalEmail = $user->email;

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Otro Nombre',
                'username' => 'otronombre',
                'phone' => '70000000',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame($originalName, $user->name);
        $this->assertSame($originalEmail, $user->email);
        $this->assertSame('70000000', $user->phone);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrors('password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    public function test_avatar_rejects_files_over_2mb(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post('/profile/avatar', [
                'avatar' => \Illuminate\Http\UploadedFile::fake()->image('foto.png')->size(3000),
            ]);

        $response->assertSessionHasErrors('avatar');
    }

    public function test_avatar_accepts_valid_image(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post('/profile/avatar', [
                'avatar' => \Illuminate\Http\UploadedFile::fake()->image('foto.png')->size(500),
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame('upload', $user->fresh()->avatar_kind);
    }

    public function test_avatar_upload_uses_unique_filename_and_removes_old_file(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)->post('/profile/avatar', [
            'avatar' => \Illuminate\Http\UploadedFile::fake()->image('foto.jpg')->size(500),
        ]);

        $first = $user->fresh()->avatar_value;
        $this->assertNotNull($first);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists('avatars/'.$first);

        $this->actingAs($user)->post('/profile/avatar', [
            'avatar' => \Illuminate\Http\UploadedFile::fake()->image('foto.jpg')->size(500),
        ]);

        $second = $user->fresh()->avatar_value;
        $this->assertNotNull($second);
        $this->assertNotSame($first, $second);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists('avatars/'.$second);
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing('avatars/'.$first);
    }
}
