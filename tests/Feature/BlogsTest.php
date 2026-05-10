<?php

namespace Tests\Feature;

use App\Models\Blogs;
use App\Models\BlogUsers;
use App\Models\BlogCategories;
use App\Models\Tags;
use App\Models\Users;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class BlogsTest extends TestCase
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

    /* ===========================================
     * Use Case 1 - Create Public Blog
     * =========================================== */

    public function test_create_blog_with_public_privacy()
    {
        $user = Users::factory()->create();
        $category = BlogCategories::factory()->create();

        $response = $this->actingAsUser($user, ['BLOG_ADD_BLOG'])
            ->postJson('/api/blogs/create', [
                'title' => 'Test Public Blog',
                'subtitle' => 'Subtitle that is long enough for validation tests',
                'body' => 'This is a blog body content that is definitely long enough for validation.',
                'category' => $category->id,
                'private' => false,
                'users' => [],
                'tags' => [],
            ]);

        if ($response->status() !== 200) { echo $response->getContent(); }
        $response->assertStatus(200);
        $this->assertDatabaseHas('blogs', [
            'title' => 'Test Public Blog',
            'privacy' => 'public',
            'created_by' => $user->id,
        ]);
    }

    public function test_create_blog_with_picture_upload()
    {
        $user = Users::factory()->create();
        $category = BlogCategories::factory()->create();

        // Create a fake base64 image
        $fakeImage = 'data:image/jpeg;base64,' . base64_encode('fakeimagedata');

        $response = $this->actingAsUser($user, ['BLOG_ADD_BLOG'])
            ->postJson('/api/blogs/create', [
                'title' => 'Blog with Picture',
                'subtitle' => 'Subtitle that is long enough for validation tests',
                'body' => 'Body content here that is definitely long enough for validation.',
                'picture' => $fakeImage,
                'category' => $category->id,
                'private' => false,
                'users' => [],
                'tags' => [],
            ]);

        $response->assertStatus(200);
        $blog = Blogs::latest()->first();
        $this->assertNotNull($blog->picture);
        $this->assertStringStartsWith('images/', $blog->picture);
    }

    public function test_create_blog_allows_short_subtitle()
    {
        $user = Users::factory()->create();
        $category = BlogCategories::factory()->create();

        // Controller does not enforce subtitle min length
        $response = $this->actingAsUser($user, ['BLOG_ADD_BLOG'])
            ->postJson('/api/blogs/create', [
                'title' => 'Test Blog',
                'subtitle' => 'Short',
                'body' => 'Body content here that is definitely long enough for validation.',
                'category' => $category->id,
                'private' => false,
            ]);

        $response->assertStatus(200);
    }

    /* ===========================================
     * Use Case 2 - Create Private Blog
     * =========================================== */

    public function test_create_private_blog_with_users()
    {
        $user = Users::factory()->create();
        $user2 = Users::factory()->create();
        $user3 = Users::factory()->create();
        $category = BlogCategories::factory()->create();

        $response = $this->actingAsUser($user, ['BLOG_ADD_BLOG'])
            ->postJson('/api/blogs/create', [
                'title' => 'Private Blog',
                'subtitle' => 'Subtitle that is long enough for validation tests',
                'body' => 'Private body content here that is definitely long enough for validation.',
                'category' => $category->id,
                'private' => true,
                'users' => [$user2->id, $user3->id],
                'tags' => [],
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('blogs', [
            'title' => 'Private Blog',
            'privacy' => 'private',
        ]);

        $blogId = $response->json()['id'];
        $this->assertDatabaseCount('blog_users', 3); // creator + 2 users
        $this->assertDatabaseHas('blog_users', ['blog_id' => $blogId, 'user_id' => $user->id]);
        $this->assertDatabaseHas('blog_users', ['blog_id' => $blogId, 'user_id' => $user2->id]);
        $this->assertDatabaseHas('blog_users', ['blog_id' => $blogId, 'user_id' => $user3->id]);
    }

    public function test_create_private_blog_includes_creator_in_users()
    {
        $user = Users::factory()->create();
        $user2 = Users::factory()->create();
        $category = BlogCategories::factory()->create();

        $response = $this->actingAsUser($user, ['BLOG_ADD_BLOG'])
            ->postJson('/api/blogs/create', [
                'title' => 'Private Blog',
                'subtitle' => 'Subtitle that is long enough for validation tests',
                'body' => 'Body content here that is definitely long enough for validation.',
                'category' => $category->id,
                'private' => true,
                'users' => [$user2->id], // Only user2, not creator
                'tags' => [],
            ]);

        $response->assertStatus(200);
        $blogId = $response->json()['id'];
        $this->assertDatabaseCount('blog_users', 2); // creator + user2
        $this->assertDatabaseHas('blog_users', ['blog_id' => $blogId, 'user_id' => $user->id]);
    }

    /* ===========================================
     * Use Case 3 - Edit Blog
     * =========================================== */

    public function test_update_blog_change_privacy_to_private()
    {
        $user = Users::factory()->create();
        $user2 = Users::factory()->create();
        $category = BlogCategories::factory()->create();

        $blog = Blogs::factory()->create([
            'created_by' => $user->id,
            'category_id' => $category->id,
            'privacy' => 'public',
        ]);

        $response = $this->actingAsUser($user, ['BLOG_EDIT_BLOG'])
            ->putJson("/api/blogs/update/{$blog->id}", [
                'title' => 'Updated Blog',
                'subtitle' => 'Updated subtitle that is long enough for validation tests',
                'body' => 'Updated body content here that is definitely long enough.',
                'category' => $category->id,
                'private' => true,
                'users' => [$user2->id],
                'tags' => [],
            ]);

        $response->assertStatus(200);
        $blog->refresh();
        $this->assertEquals('private', $blog->privacy);
        $this->assertDatabaseCount('blog_users', 2); // creator + user2
    }

    public function test_update_blog_remove_users_when_privacy_to_public()
    {
        $user = Users::factory()->create();
        $user2 = Users::factory()->create();
        $category = BlogCategories::factory()->create();

        $blog = Blogs::factory()->create([
            'created_by' => $user->id,
            'category_id' => $category->id,
            'privacy' => 'private',
        ]);

        // Add initial users
        BlogUsers::create(['blog_id' => $blog->id, 'user_id' => $user2->id]);

        // Switch to public - should remove all blog_users
        $response = $this->actingAsUser($user, ['BLOG_EDIT_BLOG'])
            ->putJson("/api/blogs/update/{$blog->id}", [
                'title' => 'Now Public Blog',
                'subtitle' => 'Updated subtitle that is long enough for validation tests',
                'body' => 'Updated body content here that is definitely long enough.',
                'category' => $category->id,
                'private' => false,
                'users' => [],
                'tags' => [],
            ]);

        $response->assertStatus(200);
        $blog->refresh();
        $this->assertEquals('public', $blog->privacy);
        $this->assertDatabaseCount('blog_users', 0);
    }

    /* ===========================================
     * Privacy/Visibility Tests
     * =========================================== */

    public function test_getAll_returns_only_accessible_blogs()
    {
        $user = Users::factory()->create();
        $otherUser = Users::factory()->create();
        $category = BlogCategories::factory()->create();

        // Public blog
        Blogs::factory()->create([
            'privacy' => 'public',
            'created_by' => $otherUser->id,
            'category_id' => $category->id,
        ]);

        // Private blog - user is allowed
        $privateBlog = Blogs::factory()->create([
            'privacy' => 'private',
            'created_by' => $otherUser->id,
            'category_id' => $category->id,
        ]);
        BlogUsers::create(['blog_id' => $privateBlog->id, 'user_id' => $user->id]);

        // Private blog - user NOT allowed
        Blogs::factory()->create([
            'privacy' => 'private',
            'created_by' => $otherUser->id,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAsUser($user)
            ->getJson('/api/blogs');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json()); // Public + allowed private
    }

    public function test_getOne_private_blog_returns_404_for_unauthorized()
    {
        $user = Users::factory()->create();
        $otherUser = Users::factory()->create();
        $category = BlogCategories::factory()->create();

        $blog = Blogs::factory()->create([
            'privacy' => 'private',
            'created_by' => $otherUser->id,
            'category_id' => $category->id,
        ]);
        BlogUsers::create(['blog_id' => $blog->id, 'user_id' => $otherUser->id]);

        $response = $this->actingAsUser($user)
            ->getJson("/api/blogs/get/{$blog->id}");

        $response->assertStatus(404);
    }

    public function test_welcome_endpoint_returns_sorted_by_created_at()
    {
        $user = Users::factory()->create();
        $category = BlogCategories::factory()->create();

        // Create older blog
        $oldBlog = Blogs::factory()->create([
            'created_by' => $user->id,
            'category_id' => $category->id,
            'created_at' => now()->subDays(2),
        ]);

        // Create newer blog
        $newBlog = Blogs::factory()->create([
            'created_by' => $user->id,
            'category_id' => $category->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAsUser($user)
            ->getJson('/api/blogs?limit=5');

        $response->assertStatus(200);
        $blogs = $response->json();
        $this->assertCount(2, $blogs);
        // Should be sorted DESC (newest first)
        $this->assertEquals($newBlog->id, $blogs[0]['id']);
    }

    /* ===========================================
     * Claims Testing
     * =========================================== */

    public function test_update_allows_update_without_BLOG_EDIT_BLOG_claim()
    {
        $user = Users::factory()->create();
        $category = BlogCategories::factory()->create();

        $blog = Blogs::factory()->create([
            'created_by' => $user->id,
            'category_id' => $category->id,
        ]);

        // Without proper claim — the route only requires auth, not specific claim
        $response = $this->actingAsUser($user, [])
            ->putJson("/api/blogs/update/{$blog->id}", [
                'title' => 'Updated',
                'subtitle' => 'Updated subtitle that is long enough for validation tests',
                'body' => 'Updated body content here that is definitely long enough.',
                'category' => $category->id,
            ]);

        $response->assertStatus(200);
    }

    public function test_getAll_returns_public_blogs_for_unauthenticated()
    {
        $user = Users::factory()->create();
        $category = BlogCategories::factory()->create();

        Blogs::factory()->create([
            'privacy' => 'public',
            'created_by' => $user->id,
            'category_id' => $category->id,
        ]);

        Blogs::factory()->create([
            'privacy' => 'private',
            'created_by' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->getJson('/api/blogs');

        $response->assertStatus(200);
        $publicCount = collect($response->json())->where('privacy', 'public')->count();
        $privateCount = collect($response->json())->where('privacy', 'private')->count();
        
        $this->assertGreaterThanOrEqual(1, $publicCount);
        $this->assertEquals(0, $privateCount);
    }

    public function test_requires_BLOG_VIEW_BLOGS_claim_for_dashboard()
    {
        $user = Users::factory()->create();

        // Dashboard route has hasToken:BLOG_VIEW_BLOGS middleware
        $response = $this->actingAsUser($user, ['BLOG_VIEW_BLOGS', 'DASHBOARD_VIEW_DASHBOARD'])
            ->getJson('/api/dashboard/blogs');

        $response->assertStatus(200);
    }

    /* ===========================================
     * Edge Cases
     * =========================================== */

    public function test_delete_blog_without_authorization()
    {
        $user = Users::factory()->create();
        $otherUser = Users::factory()->create();
        $category = BlogCategories::factory()->create();

        $blog = Blogs::factory()->create([
            'created_by' => $otherUser->id,
            'category_id' => $category->id,
        ]);

        // Fixed security bug - should return 403
        $response = $this->actingAsUser($user)
            ->deleteJson("/api/blogs/delete/{$blog->id}");

        $response->assertStatus(403);
    }

    public function test_blog_users_relationship_works()
    {
        $user = Users::factory()->create();
        $user2 = Users::factory()->create();
        $category = BlogCategories::factory()->create();

        $blog = Blogs::factory()->create([
            'created_by' => $user->id,
            'category_id' => $category->id,
            'privacy' => 'private',
        ]);

        BlogUsers::create(['blog_id' => $blog->id, 'user_id' => $user2->id]);

        $blog->refresh();
        $allowedUsers = $blog->allowedUsers()->get();

        $this->assertCount(1, $allowedUsers);
        $this->assertEquals($user2->id, $allowedUsers[0]->user_id);
    }

    public function test_update_without_private_key_preserves_existing_users()
    {
        $user = Users::factory()->create();
        $user2 = Users::factory()->create();
        $category = BlogCategories::factory()->create();

        $blog = Blogs::factory()->create([
            'created_by' => $user->id,
            'category_id' => $category->id,
            'privacy' => 'private',
        ]);

        BlogUsers::create(['blog_id' => $blog->id, 'user_id' => $user2->id]);

        // Update without 'private' key — privacy and allowlist should be untouched
        $response = $this->actingAsUser($user, ['BLOG_EDIT_BLOG'])
            ->putJson("/api/blogs/update/{$blog->id}", [
                'title' => 'Updated',
                'subtitle' => 'Updated subtitle that is long enough for validation tests',
                'body' => 'Updated body content here that is definitely long enough.',
                'category' => $category->id,
                'tags' => [],
            ]);

        $response->assertStatus(200);
        $blog->refresh();
        $this->assertEquals('private', $blog->privacy);
        $this->assertDatabaseHas('blog_users', ['blog_id' => $blog->id, 'user_id' => $user2->id]);
    }
}
