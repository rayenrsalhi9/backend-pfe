<?php

namespace Tests\Feature;

use App\Models\Users;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function getAuthToken(Users $user, array $claims = []): string
    {
        return JWTAuth::claims([
            'claims' => $claims,
            'userId' => $user->id,
            'email' => $user->email,
        ])->fromUser($user);
    }

    protected function actingAsUser(Users $user, array $claims = [])
    {
        $token = $this->getAuthToken($user, $claims);

        return $this->withHeader('Authorization', "Bearer {$token}");
    }

    /* ===========================================
     * Login Tests
     * =========================================== */

    public function test_login_with_valid_credentials_returns_jwt_token()
    {
        $user = Users::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('Test@1234'),
            'userName' => 'testuser',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Test@1234',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'claims',
            'user' => ['id', 'firstName', 'lastName', 'email', 'userName'],
            'authorisation' => ['token', 'type'],
        ]);
        $response->assertJson(['status' => 'success']);
    }

    public function test_login_with_invalid_credentials_returns_401()
    {
        $user = Users::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('Test@1234'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'WrongPass1!',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_creates_login_audit_entry()
    {
        $user = Users::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('Test@1234'),
            'userName' => 'testuser',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Test@1234',
        ]);

        $this->assertDatabaseHas('loginAudits', [
            'userName' => 'test@example.com',
            'status' => 'Success',
        ]);
    }

    public function test_login_sets_is_connected_true()
    {
        $user = Users::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('Test@1234'),
            'userName' => 'testuser',
            'isConnected' => false,
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Test@1234',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'isConnected' => true,
        ]);
    }

    public function test_login_returns_claims_array()
    {
        $user = Users::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('Test@1234'),
            'userName' => 'testuser',
        ]);

        $pageId = \Ramsey\Uuid\Uuid::uuid4();
        DB::table('pages')->insert([
            'id' => $pageId,
            'name' => 'Users',
            'order' => 1,
            'isDeleted' => false,
            'createdBy' => $user->id,
            'modifiedBy' => $user->id,
            'createdDate' => Carbon::now(),
            'modifiedDate' => Carbon::now(),
        ]);

        $actionId = \Ramsey\Uuid\Uuid::uuid4();
        DB::table('actions')->insert([
            'id' => $actionId,
            'name' => 'View Users',
            'order' => 1,
            'pageId' => $pageId,
            'isDeleted' => false,
            'createdBy' => $user->id,
            'modifiedBy' => $user->id,
            'createdDate' => Carbon::now(),
            'modifiedDate' => Carbon::now(),
        ]);

        DB::table('userClaims')->insert([
            'id' => \Ramsey\Uuid\Uuid::uuid4(),
            'userId' => $user->id,
            'actionId' => $actionId,
            'claimType' => 'USER_VIEW_USERS',
            'claimValue' => 'true',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Test@1234',
        ]);

        $response->assertStatus(200);
        $this->assertContains('USER_VIEW_USERS', $response->json()['claims']);
    }

    /* ===========================================
     * Forgot Password Tests
     * =========================================== */

    public function test_forgot_with_valid_email_sends_pin()
    {
        $user = Users::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('Test@1234'),
        ]);

        $response = $this->postJson('/api/auth/forgot', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'Please check your email for a 6 digit pin',
        ]);

        $this->assertDatabaseHas('password_resets', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_forgot_with_non_existent_email_returns_error()
    {
        $response = $this->postJson('/api/auth/forgot', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'status' => 'error',
            'message' => 'This email does not exist',
        ]);
    }

    public function test_forgot_with_invalid_email_format_returns_422()
    {
        $response = $this->postJson('/api/auth/forgot', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422);
    }

    /* ===========================================
     * Verify PIN Tests
     * =========================================== */

    public function test_verify_pin_with_valid_token_returns_reset_token()
    {
        $user = Users::factory()->create([
            'email' => 'test@example.com',
        ]);

        DB::table('password_resets')->insert([
            'email' => 'test@example.com',
            'token' => '123456',
            'created_at' => Carbon::now(),
        ]);

        $response = $this->postJson('/api/auth/verify', [
            'email' => 'test@example.com',
            'token' => '123456',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'message',
            'token',
        ]);
        $response->assertJson([
            'status' => 'success',
            'message' => 'You can now reset your password',
        ]);
    }

    public function test_verify_pin_with_expired_token_returns_error()
    {
        $user = Users::factory()->create([
            'email' => 'test@example.com',
        ]);

        DB::table('password_resets')->insert([
            'email' => 'test@example.com',
            'token' => '123456',
            'created_at' => Carbon::now()->subHours(2),
        ]);

        $response = $this->postJson('/api/auth/verify', [
            'email' => 'test@example.com',
            'token' => '123456',
        ]);

        $response->assertStatus(400);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Token Expired',
        ]);
    }

    public function test_verify_pin_with_invalid_token_returns_401()
    {
        $user = Users::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->postJson('/api/auth/verify', [
            'email' => 'test@example.com',
            'token' => '000000',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Invalid token',
        ]);
    }

    /* ===========================================
     * Reset Password Tests
     * =========================================== */

    public function test_reset_password_with_valid_token_updates_password()
    {
        $user = Users::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('OldPass1!'),
        ]);

        $resetToken = Hash::make('123456:test@example.com');
        DB::table('password_resets')->insert([
            'email' => 'test@example.com',
            'token' => $resetToken,
            'created_at' => Carbon::now(),
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'test@example.com',
            'token' => $resetToken,
            'password' => 'NewPass@123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'message' => 'Your password has been reset',
        ]);

        $this->assertDatabaseMissing('password_resets', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_reset_password_with_invalid_token_returns_error()
    {
        $user = Users::factory()->create([
            'email' => 'test@example.com',
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'test@example.com',
            'token' => 'nonexistent-token',
            'password' => 'NewPass@123',
        ]);

        $response->assertStatus(400);
    }

    public function test_reset_password_with_weak_password_returns_422()
    {
        $user = Users::factory()->create([
            'email' => 'test@example.com',
        ]);

        $resetToken = Hash::make('123456:test@example.com');
        DB::table('password_resets')->insert([
            'email' => 'test@example.com',
            'token' => $resetToken,
            'created_at' => Carbon::now(),
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'email' => 'test@example.com',
            'token' => $resetToken,
            'password' => 'weak',
        ]);

        $response->assertStatus(422);
    }

    /* ===========================================
     * Registration Tests
     * =========================================== */

    public function test_register_with_valid_data_creates_user_and_returns_jwt()
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'newuser@example.com',
            'password' => 'Test@1234',
            'username' => 'newuser',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'claims',
            'user' => ['id', 'firstName', 'lastName', 'email', 'userName'],
            'authorisation' => ['token', 'type'],
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
        ]);
    }

    public function test_register_with_duplicate_email_returns_422()
    {
        $existingUser = Users::factory()->create([
            'email' => 'existing@example.com',
        ]);

        $response = $this->postJson('/api/auth/register', [
            'email' => 'existing@example.com',
            'password' => 'Test@1234',
            'username' => 'newuser',
        ]);

        $response->assertStatus(422);
    }

    public function test_register_with_weak_password_returns_422()
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'newuser@example.com',
            'password' => 'weak',
            'username' => 'newuser',
        ]);

        $response->assertStatus(422);
    }

    /* ===========================================
     * Logout Tests
     * =========================================== */

    public function test_logout_blacklists_token_and_sets_disconnected()
    {
        $user = Users::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('Test@1234'),
            'isConnected' => true,
        ]);

        $token = $this->getAuthToken($user);
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout', ['user' => $user->id]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'isConnected' => false,
        ]);

        $this->assertDatabaseHas('jwt_blacklist', [
            'token_hash' => hash('sha256', $token),
        ]);
    }

    /* ===========================================
     * Token Refresh Tests
     * =========================================== */

    public function test_refresh_returns_new_token()
    {
        $user = Users::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('Test@1234'),
            'userName' => 'testuser',
        ]);

        $token = $this->getAuthToken($user);
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/refresh');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'claims',
            'user',
            'authorisation' => ['token', 'type'],
        ]);

        $newToken = $response->json('authorisation.token');
        $this->assertNotEquals($token, $newToken, 'Refresh must return a different token string');
    }

    public function test_refresh_expired_token_returns_new_token()
    {
        $user = Users::factory()->create([
            'email' => 'expired@example.com',
            'password' => bcrypt('Test@1234'),
            'userName' => 'expireduser',
        ]);

        // Create a valid token
        $token = JWTAuth::claims([
            'claims' => ['TEST_CLAIM'],
            'userId' => $user->id,
            'email' => $user->email,
        ])->fromUser($user);

        // Move clock past token TTL so the token is now expired
        $ttl = config('jwt.ttl', 60);
        Carbon::setTestNow(Carbon::now()->addMinutes($ttl + 1));

        // Assert the token is actually expired
        try {
            JWTAuth::setToken($token)->authenticate();
            $this->fail('Token should have expired');
        } catch (\PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException $e) {
            $this->assertTrue(true);
        }

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/refresh');

        // Restore clock
        Carbon::setTestNow();

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);

        $newToken = $response->json('authorisation.token');
        $this->assertNotNull($newToken);
        $this->assertNotEquals($token, $newToken);
    }

    public function test_refresh_rejects_token_present_in_custom_jwt_blacklist()
    {
        $user = Users::factory()->create([
            'email' => 'customblacklist@example.com',
            'password' => bcrypt('Test@1234'),
            'userName' => 'customblacklistuser',
        ]);

        $token = $this->getAuthToken($user);

        DB::table('jwt_blacklist')->insert([
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes(config('jwt.ttl', 60)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/refresh');

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Token has been revoked']);
    }

    public function test_refresh_blacklisted_token_returns_401()
    {
        $user = Users::factory()->create([
            'email' => 'blacklisted@example.com',
            'password' => bcrypt('Test@1234'),
            'userName' => 'blacklisteduser',
        ]);

        $token = $this->getAuthToken($user);

        // Blacklist the token
        JWTAuth::setToken($token)->invalidate();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/refresh');

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Token has been revoked']);
    }

    public function test_refresh_without_token_returns_401()
    {
        $response = $this->postJson('/api/auth/refresh', [], [
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(401);
    }

    public function test_refresh_invalid_token_string_returns_401()
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid.token.string')
            ->postJson('/api/auth/refresh');

        $response->assertStatus(401);
    }

    public function test_refresh_returns_refreshed_claims()
    {
        $user = Users::factory()->create([
            'email' => 'claims@example.com',
            'password' => bcrypt('Test@1234'),
            'userName' => 'claimsuser',
        ]);

        $token = JWTAuth::claims([
            'claims' => ['ARTICLE_VIEW_ARTICLES', 'USER_VIEW_USERS'],
            'userId' => $user->id,
            'email' => $user->email,
        ])->fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/refresh');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'claims',
            'user' => ['id', 'firstName', 'lastName', 'email', 'userName'],
            'authorisation' => ['token', 'type'],
        ]);
        $response->assertJson(['status' => 'success']);
    }
}
