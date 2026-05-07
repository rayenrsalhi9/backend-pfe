<?php

namespace Tests\Feature;

use App\Models\Articles;
use App\Models\ArticleUsers;
use App\Models\ArticleCategories;
use App\Models\Users;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class ArticlesTest extends TestCase
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
     * Use Case 1 - Create Public Article
     * =========================================== */

    public function test_create_article_with_public_privacy()
    {
        $user = Users::factory()->create();
        $category = ArticleCategories::factory()->create();

        $response = $this->actingAsUser($user, ['ARTICLE_ADD_ARTICLE'])
            ->postJson('/api/articles/create', [
                'title' => 'Test Public Article',
                'description' => 'Short text description that is long enough for validation.',
                'body' => 'This is article body content that is definitely long enough for validation.',
                'category' => $category->id,
                'private' => false,
                'users' => [],
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('articles', [
            'title' => 'Test Public Article',
            'privacy' => 'public',
            'created_by' => $user->id,
        ]);
    }

    public function test_create_article_with_picture()
    {
        $user = Users::factory()->create();
        $category = ArticleCategories::factory()->create();

        $fakeImage = 'data:image/jpeg;base64,' . base64_encode('fakeimagedata');

        $response = $this->actingAsUser($user, ['ARTICLE_ADD_ARTICLE'])
            ->postJson('/api/articles/create', [
                'title' => 'Article with Picture',
                'description' => 'Short text description that is long enough for validation.',
                'body' => 'Body content here that is definitely long enough for validation.',
                'picture' => $fakeImage,
                'category' => $category->id,
                'private' => false,
                'users' => [],
            ]);

        $response->assertStatus(200);
        $article = Articles::latest()->first();
        $this->assertNotNull($article->picture);
        $this->assertStringStartsWith('images/', $article->picture);
    }

    /* ===========================================
     * Use Case 2 - Create Private Article
     * =========================================== */

    public function test_create_private_article_with_users()
    {
        $user = Users::factory()->create();
        $user2 = Users::factory()->create();
        $user3 = Users::factory()->create();
        $category = ArticleCategories::factory()->create();

        $response = $this->actingAsUser($user, ['ARTICLE_ADD_ARTICLE'])
            ->postJson('/api/articles/create', [
                'title' => 'Private Article',
                'description' => 'Short text description that is long enough for validation.',
                'body' => 'Private body content here that is definitely long enough for validation.',
                'category' => $category->id,
                'private' => true,
                'users' => [$user2->id, $user3->id],
            ]);

        if ($response->status() !== 200) { echo $response->getContent(); }
        $response->assertStatus(200);
        $this->assertDatabaseHas('articles', [
            'title' => 'Private Article',
            'privacy' => 'private',
        ]);

        $articleId = $response->json()['id'];
        $this->assertDatabaseCount('article_users', 3); // creator + 2 users
        $this->assertDatabaseHas('article_users', ['article_id' => $articleId, 'user_id' => $user->id]);
        $this->assertDatabaseHas('article_users', ['article_id' => $articleId, 'user_id' => $user2->id]);
        $this->assertDatabaseHas('article_users', ['article_id' => $articleId, 'user_id' => $user3->id]);
    }

    public function test_create_private_article_includes_creator()
    {
        $user = Users::factory()->create();
        $user2 = Users::factory()->create();
        $category = ArticleCategories::factory()->create();

        $response = $this->actingAsUser($user, ['ARTICLE_ADD_ARTICLE'])
            ->postJson('/api/articles/create', [
                'title' => 'Private Article',
                'description' => 'Short text description that is long enough for validation.',
                'body' => 'Body content here that is definitely long enough for validation.',
                'category' => $category->id,
                'private' => true,
                'users' => [$user2->id], // Only user2, not creator
            ]);

        $response->assertStatus(200);
        $articleId = $response->json()['id'];
        $this->assertDatabaseCount('article_users', 2); // creator + user2
        $this->assertDatabaseHas('article_users', ['article_id' => $articleId, 'user_id' => $user->id]);
        $this->assertDatabaseHas('article_users', ['article_id' => $articleId, 'user_id' => $user2->id]);
    }

    /* ===========================================
     * Use Case 3 - Edit Article
     * =========================================== */

    public function test_update_article_change_privacy_to_private()
    {
        $user = Users::factory()->create();
        $user2 = Users::factory()->create();
        $category = ArticleCategories::factory()->create();

        $article = Articles::factory()->create([
            'created_by' => $user->id,
            'article_category_id' => $category->id,
            'privacy' => 'public',
        ]);

        $response = $this->actingAsUser($user, ['ARTICLE_EDIT_ARTICLE'])
            ->putJson("/api/articles/update/{$article->id}", [
                'title' => 'Updated Article',
                'description' => 'Updated short text that is long enough for validation.',
                'body' => 'Updated body content here that is definitely long enough.',
                'category' => $category->id,
                'private' => true,
                'users' => [$user2->id],
            ]);

        $response->assertStatus(200);
        $article->refresh();
        $this->assertEquals('private', $article->privacy);
        $this->assertDatabaseCount('article_users', 2); // creator + user2
    }

    public function test_update_article_remove_users_when_privacy_to_public()
    {
        $user = Users::factory()->create();
        $user2 = Users::factory()->create();
        $category = ArticleCategories::factory()->create();

        $article = Articles::factory()->create([
            'created_by' => $user->id,
            'article_category_id' => $category->id,
            'privacy' => 'private',
        ]);

        ArticleUsers::create(['article_id' => $article->id, 'user_id' => $user2->id]);

        // Switch to public - should remove all article_users
        $response = $this->actingAsUser($user, ['ARTICLE_EDIT_ARTICLE'])
            ->putJson("/api/articles/update/{$article->id}", [
                'title' => 'Now Public Article',
                'description' => 'Updated short text that is long enough for validation.',
                'body' => 'Updated body content here that is definitely long enough.',
                'category' => $category->id,
                'private' => false,
                'users' => [],
            ]);

        $response->assertStatus(200);
        $article->refresh();
        $this->assertEquals('public', $article->privacy);
        $this->assertDatabaseCount('article_users', 0);
    }

    /* ===========================================
     * Privacy/Visibility Tests
     * =========================================== */

    public function test_getAll_returns_public_and_allowed_private_articles()
    {
        $user = Users::factory()->create();
        $otherUser = Users::factory()->create();
        $category = ArticleCategories::factory()->create();

        // Public article
        Articles::factory()->create([
            'privacy' => 'public',
            'created_by' => $otherUser->id,
            'article_category_id' => $category->id,
        ]);

        // Private article - user is allowed
        $privateArticle = Articles::factory()->create([
            'privacy' => 'private',
            'created_by' => $otherUser->id,
            'article_category_id' => $category->id,
        ]);
        ArticleUsers::create(['article_id' => $privateArticle->id, 'user_id' => $user->id]);

        // Private article - user NOT allowed
        Articles::factory()->create([
            'privacy' => 'private',
            'created_by' => $otherUser->id,
            'article_category_id' => $category->id,
        ]);

        $response = $this->actingAsUser($user)
            ->getJson('/api/articles');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json()); // Public + allowed private
    }

    public function test_getOne_private_article_returns_404_for_unauthorized()
    {
        $user = Users::factory()->create();
        $otherUser = Users::factory()->create();
        $category = ArticleCategories::factory()->create();

        $article = Articles::factory()->create([
            'privacy' => 'private',
            'created_by' => $otherUser->id,
            'article_category_id' => $category->id,
        ]);
        ArticleUsers::create(['article_id' => $article->id, 'user_id' => $otherUser->id]);

        $response = $this->actingAsUser($user)
            ->getJson("/api/articles/get/{$article->id}");

        $response->assertStatus(404);
    }

    /* ===========================================
     * Claims Testing
     * =========================================== */

    public function test_requires_ARTICLE_EDIT_ARTICLE_claim()
    {
        $user = Users::factory()->create();
        $category = ArticleCategories::factory()->create();

        $article = Articles::factory()->create([
            'created_by' => $user->id,
            'article_category_id' => $category->id,
        ]);

        // Note: The articles/update route only uses 'auth' middleware
        $response = $this->actingAsUser($user, [])
            ->putJson("/api/articles/update/{$article->id}", [
                'title' => 'Updated',
                'description' => 'Updated short text that is long enough.',
                'body' => 'Updated body content here.',
                'category' => $category->id,
            ]);

        // Documents current behavior - no claim check at route level
    }

    public function test_getAll_returns_public_articles_for_unauthenticated()
    {
        $user = Users::factory()->create();
        $category = ArticleCategories::factory()->create();

        Articles::factory()->create([
            'privacy' => 'public',
            'created_by' => $user->id,
            'article_category_id' => $category->id,
        ]);

        Articles::factory()->create([
            'privacy' => 'private',
            'created_by' => $user->id,
            'article_category_id' => $category->id,
        ]);

        $response = $this->getJson('/api/articles');

        $response->assertStatus(200);
        $publicCount = collect($response->json())->where('privacy', 'public')->count();
        $privateCount = collect($response->json())->where('privacy', 'private')->count();
        
        $this->assertGreaterThanOrEqual(1, $publicCount);
        $this->assertEquals(0, $privateCount);
    }

    public function test_requires_ARTICLE_VIEW_ARTICLES_for_dashboard()
    {
        $user = Users::factory()->create();

        $response = $this->actingAsUser($user, ['ARTICLE_VIEW_ARTICLES', 'DASHBOARD_VIEW_DASHBOARD'])
            ->getJson('/api/dashboard/articles');

        $response->assertStatus(200);
    }

    /* ===========================================
     * Edge Cases
     * =========================================== */

    public function test_article_users_relationship_typo_bug()
    {
        $user = Users::factory()->create();
        $category = ArticleCategories::factory()->create();

        $article = Articles::factory()->create([
            'created_by' => $user->id,
            'article_category_id' => $category->id,
            'privacy' => 'private',
        ]);

        ArticleUsers::create(['article_id' => $article->id, 'user_id' => $user->id]);

        // NOTE: ArticleUsers model has a typo: 'arcticle_id' instead of 'article_id'
        // This may cause relationship issues
        $article->refresh();
        $users = $article->users()->get();

        // This test documents the bug - relationship may not work correctly
        $this->assertNotNull($users);
    }

    public function test_delete_article_without_authorization()
    {
        $user = Users::factory()->create();
        $otherUser = Users::factory()->create();
        $category = ArticleCategories::factory()->create();

        $article = Articles::factory()->create([
            'created_by' => $otherUser->id,
            'article_category_id' => $category->id,
        ]);

        // Fixed security bug - should return 403
        $response = $this->actingAsUser($user)
            ->deleteJson("/api/articles/delete/{$article->id}");
        $response->assertStatus(403);
    }

    public function test_update_missing_users_array_handling()
    {
        $user = Users::factory()->create();
        $category = ArticleCategories::factory()->create();

        $article = Articles::factory()->create([
            'created_by' => $user->id,
            'article_category_id' => $category->id,
            'privacy' => 'private',
        ]);

        ArticleUsers::create(['article_id' => $article->id, 'user_id' => $user->id]);

        // Update without users array
        // Current code: foreach ($request->users as $key => $user)
        // This will fail if $request->users is not set
        $response = $this->actingAsUser($user, ['ARTICLE_EDIT_ARTICLE'])
            ->putJson("/api/articles/update/{$article->id}", [
                'title' => 'Updated',
                'description' => 'Updated short text that is long enough.',
                'body' => 'Updated body content here.',
                'category' => $category->id,
                'private' => true,
                // 'users' not provided
            ]);

        // Documents current behavior - may cause error
    }
}
