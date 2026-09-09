<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\UserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_deletion_works(): void
    {
        $user = User::factory()->create();
        
        // Check user exists
        $this->assertNotNull($user->fresh());
        
        // Delete via service
        $service = app(UserService::class);
        $deleted = $service->delete($user);
        
        $this->assertTrue($deleted);
        
        // Check user is deleted
        $this->assertNull($user->fresh());
    }
}