<?php

namespace Tests\Feature;

use App\Models\Users;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function getAuthToken(Users $user, array $claims = []): string
    {
        return JWTAuth::claims([
            'claims' => $claims,
            'userId' => $user->id,
            'email' => $user->email
        ])->fromUser($user);
    }

    protected function actingAsUser(Users $user, array $claims = [])
    {
        $token = $this->getAuthToken($user, $claims);
        return $this->withHeader('Authorization', "Bearer {$token}");
    }

    public function test_create_user_with_valid_data_succeeds()
    {
        $admin = Users::factory()->create();

        $response = $this->actingAsUser($admin, ['USER_CREATE_USER'])
            ->postJson('/api/user', [
                'email' => 'newuser@example.com',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'password' => 'Test@1234',
                'phoneNumber' => '1234567890',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
        ]);
    }

    public function test_create_user_with_weak_password_returns_error()
    {
        $admin = Users::factory()->create();

        $response = $this->actingAsUser($admin, ['USER_CREATE_USER'])
            ->postJson('/api/user', [
                'email' => 'newuser@example.com',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'password' => 'weak',
                'phoneNumber' => '1234567890',
            ]);

        $response->assertStatus(422);
    }

    public function test_create_user_with_existing_email_returns_error()
    {
        $admin = Users::factory()->create();
        Users::factory()->create(['email' => 'existing@example.com']);

        $response = $this->actingAsUser($admin, ['USER_CREATE_USER'])
            ->postJson('/api/user', [
                'email' => 'existing@example.com',
                'firstName' => 'John',
                'lastName' => 'Doe',
                'password' => 'Test@1234',
                'phoneNumber' => '1234567890',
            ]);

        $response->assertStatus(422);
    }

    public function test_index_requires_USER_VIEW_USERS_claim()
    {
        $user = Users::factory()->create();

        $response = $this->actingAsUser($user, ['USER_VIEW_USERS'])
            ->getJson('/api/user');

        $response->assertStatus(200);
    }

    public function test_index_without_USER_VIEW_USERS_claim_is_rejected()
    {
        $user = Users::factory()->create();

        $response = $this->actingAsUser($user, [])
            ->getJson('/api/user');

        $response->assertStatus(403);
    }

    public function test_submit_reset_password_with_weak_password_returns_validation_error()
    {
        $admin = Users::factory()->create();
        $targetUser = Users::factory()->create([
            'email' => 'target@example.com',
        ]);

        $response = $this->actingAsUser($admin, ['USER_RESET_PASSWORD'])
            ->postJson('/api/user/reset-password', [
                'email' => 'target@example.com',
                'password' => 'weak',
            ]);

        $response->assertStatus(422);
    }

    public function test_submit_reset_password_with_valid_password_succeeds()
    {
        $admin = Users::factory()->create();
        $targetUser = Users::factory()->create([
            'email' => 'target@example.com',
            'password' => bcrypt('OldPass@123'),
        ]);

        $response = $this->actingAsUser($admin, ['USER_RESET_PASSWORD'])
            ->postJson('/api/user/reset-password', [
                'email' => 'target@example.com',
                'password' => 'NewPass@123',
            ]);

        $response->assertStatus(204);
    }

    public function test_change_password_with_weak_new_password_returns_validation_error()
    {
        $user = Users::factory()->create([
            'password' => bcrypt('Current@123'),
        ]);

        $token = $this->getAuthToken($user);
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/user/change-password', [
                'oldPassword' => 'Current@123',
                'newPassword' => 'weak',
            ]);

        $response->assertStatus(422);
    }

    public function test_change_password_with_valid_data_succeeds()
    {
        $user = Users::factory()->create([
            'password' => bcrypt('Current@123'),
        ]);

        $token = $this->getAuthToken($user);
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/user/change-password', [
                'oldPassword' => 'Current@123',
                'newPassword' => 'NewPass@123',
            ]);

        $response->assertStatus(200);
    }
}
