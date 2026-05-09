<?php

namespace Tests\Feature;

use App\Models\Users;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class DocumentTest extends TestCase
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

    private function createCategory(string $createdBy): string
    {
        $id = Uuid::uuid4()->toString();
        DB::table('categories')->insert([
            'id' => $id,
            'name' => 'Test Category',
            'createdBy' => $createdBy,
            'modifiedBy' => $createdBy,
            'createdDate' => now(),
            'modifiedDate' => now(),
            'isDeleted' => 0,
        ]);
        return $id;
    }

    private function createDocument(string $userId, string $categoryId, array $overrides = []): string
    {
        $id = Uuid::uuid4()->toString();
        DB::table('documents')->insert(array_merge([
            'id' => $id,
            'name' => 'Test Document',
            'description' => 'Test Description',
            'url' => 'documents/test.pdf',
            'categoryId' => $categoryId,
            'extension' => 'pdf',
            'createdBy' => $userId,
            'modifiedBy' => $userId,
            'createdDate' => now(),
            'modifiedDate' => now(),
            'isDeleted' => 0,
        ], $overrides));
        return $id;
    }

    /* ============================================
     * GET /documents - isAllowDownload flag
     * ============================================ */

    public function test_get_documents_requires_claim()
    {
        $user = Users::factory()->create();

        $response = $this->actingAsUser($user, [])
            ->getJson('/api/documents?orderBy=createdDate desc&pageSize=10&skip=0&fields=&createDateString=&searchQuery=&categoryId=&name=&metaTags=&id=');

        $response->assertStatus(403);
    }

    public function test_get_documents_returns_isAllowDownload_true_for_user_permission()
    {
        $user = Users::factory()->create();
        $categoryId = $this->createCategory($user->id);
        $docId = $this->createDocument($user->id, $categoryId);

        DB::table('documentUserPermissions')->insert([
            'id' => Uuid::uuid4()->toString(),
            'documentId' => $docId,
            'userId' => $user->id,
            'isAllowDownload' => 1,
            'isTimeBound' => 0,
            'createdBy' => $user->id,
            'modifiedBy' => $user->id,
            'createdDate' => now(),
            'modifiedDate' => now(),
        ]);

        $response = $this->actingAsUser($user, ['ALL_DOCUMENTS_VIEW_DOCUMENTS'])
            ->getJson('/api/documents?orderBy=createdDate desc&pageSize=10&skip=0&fields=&createDateString=&searchQuery=&categoryId=&name=&metaTags=&id=');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json()[0]['isAllowDownload']);
    }

    public function test_get_documents_returns_isAllowDownload_false_without_permission()
    {
        $user = Users::factory()->create();
        $categoryId = $this->createCategory($user->id);
        $this->createDocument($user->id, $categoryId);

        $response = $this->actingAsUser($user, ['ALL_DOCUMENTS_VIEW_DOCUMENTS'])
            ->getJson('/api/documents?orderBy=createdDate desc&pageSize=10&skip=0&fields=&createDateString=&searchQuery=&categoryId=&name=&metaTags=&id=');

        $response->assertStatus(200);
        $this->assertEquals(0, $response->json()[0]['isAllowDownload']);
    }

    public function test_get_documents_returns_isAllowDownload_true_for_role_permission()
    {
        $user = Users::factory()->create();
        $categoryId = $this->createCategory($user->id);
        $docId = $this->createDocument($user->id, $categoryId);

        $roleId = Uuid::uuid4()->toString();
        DB::table('roles')->insert([
            'id' => $roleId,
            'name' => 'Test Role',
            'createdBy' => $user->id,
            'modifiedBy' => $user->id,
            'createdDate' => now(),
            'modifiedDate' => now(),
            'isDeleted' => 0,
        ]);

        DB::table('userRoles')->insert([
            'id' => Uuid::uuid4()->toString(),
            'userId' => $user->id,
            'roleId' => $roleId,
        ]);

        DB::table('documentRolePermissions')->insert([
            'id' => Uuid::uuid4()->toString(),
            'documentId' => $docId,
            'roleId' => $roleId,
            'isAllowDownload' => 1,
            'isTimeBound' => 0,
            'createdBy' => $user->id,
            'modifiedBy' => $user->id,
            'createdDate' => now(),
            'modifiedDate' => now(),
        ]);

        $response = $this->actingAsUser($user, ['ALL_DOCUMENTS_VIEW_DOCUMENTS'])
            ->getJson('/api/documents?orderBy=createdDate desc&pageSize=10&skip=0&fields=&createDateString=&searchQuery=&categoryId=&name=&metaTags=&id=');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json()[0]['isAllowDownload']);
    }

    /* ============================================
     * GET /documents - property_exists guard
     * ============================================ */

    public function test_get_documents_without_optional_filters_does_not_error()
    {
        $user = Users::factory()->create();
        $categoryId = $this->createCategory($user->id);
        $this->createDocument($user->id, $categoryId);

        $response = $this->actingAsUser($user, ['ALL_DOCUMENTS_VIEW_DOCUMENTS'])
            ->getJson('/api/documents?orderBy=createdDate desc&pageSize=10&skip=0');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json());
    }

    public function test_get_documents_filters_by_category_id()
    {
        $user = Users::factory()->create();
        $catA = $this->createCategory($user->id);
        $catB = $this->createCategory($user->id);
        $this->createDocument($user->id, $catA, ['name' => 'Doc A']);
        $this->createDocument($user->id, $catB, ['name' => 'Doc B']);

        $response = $this->actingAsUser($user, ['ALL_DOCUMENTS_VIEW_DOCUMENTS'])
            ->getJson("/api/documents?orderBy=createdDate desc&pageSize=10&skip=0&categoryId={$catA}");

        $response->assertStatus(200);
        $this->assertCount(1, $response->json());
        $this->assertEquals('Doc A', $response->json()[0]['name']);
    }

    /* ============================================
     * PUT /document/{id} - update metadata
     * ============================================ */

    public function test_update_document_requires_edit_claim()
    {
        $user = Users::factory()->create();
        $categoryId = $this->createCategory($user->id);
        $docId = $this->createDocument($user->id, $categoryId);

        $response = $this->actingAsUser($user, [])
            ->putJson("/api/document/{$docId}", [
                'name' => 'Updated',
                'description' => 'Updated desc',
                'categoryId' => $categoryId,
            ]);

        $response->assertStatus(403);
    }

    public function test_update_document_updates_metadata()
    {
        $user = Users::factory()->create();
        $categoryId = $this->createCategory($user->id);
        $docId = $this->createDocument($user->id, $categoryId);

        $response = $this->actingAsUser($user, ['ALL_DOCUMENTS_EDIT_DOCUMENT'])
            ->putJson("/api/document/{$docId}", [
                'name' => 'Updated Name',
                'description' => 'Updated Description',
                'categoryId' => $categoryId,
                'documentMetaDatas' => [],
            ]);

        $response->assertStatus(200);

        $doc = DB::table('documents')->where('id', $docId)->first();
        $this->assertEquals('Updated Name', $doc->name);
        $this->assertEquals('Updated Description', $doc->description);
    }

    /* ============================================
     * PUT /document/{id} - permission persistence
     * ============================================ */

    public function test_update_document_replaces_user_permissions()
    {
        $user = Users::factory()->create();
        $user2 = Users::factory()->create();
        $categoryId = $this->createCategory($user->id);
        $docId = $this->createDocument($user->id, $categoryId);

        $oldPermId = Uuid::uuid4()->toString();
        DB::table('documentUserPermissions')->insert([
            'id' => $oldPermId,
            'documentId' => $docId,
            'userId' => $user2->id,
            'isAllowDownload' => 1,
            'isTimeBound' => 0,
            'createdBy' => $user->id,
            'modifiedBy' => $user->id,
            'createdDate' => now(),
            'modifiedDate' => now(),
        ]);

        $user3 = Users::factory()->create();
        $response = $this->actingAsUser($user, ['ALL_DOCUMENTS_EDIT_DOCUMENT'])
            ->putJson("/api/document/{$docId}", [
                'name' => 'Updated',
                'description' => 'Updated desc',
                'categoryId' => $categoryId,
                'documentMetaDatas' => [],
                'documentUserPermissions' => [
                    ['userId' => $user3->id, 'isAllowDownload' => 1, 'isTimeBound' => false],
                ],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('documentUserPermissions', ['id' => $oldPermId]);
        $this->assertDatabaseHas('documentUserPermissions', [
            'documentId' => $docId,
            'userId' => $user3->id,
            'isAllowDownload' => 1,
        ]);
    }

    public function test_update_document_replaces_role_permissions()
    {
        $user = Users::factory()->create();
        $categoryId = $this->createCategory($user->id);
        $docId = $this->createDocument($user->id, $categoryId);

        $roleId = Uuid::uuid4()->toString();
        DB::table('roles')->insert([
            'id' => $roleId,
            'name' => 'Old Role',
            'createdBy' => $user->id,
            'modifiedBy' => $user->id,
            'createdDate' => now(),
            'modifiedDate' => now(),
            'isDeleted' => 0,
        ]);

        $oldPermId = Uuid::uuid4()->toString();
        DB::table('documentRolePermissions')->insert([
            'id' => $oldPermId,
            'documentId' => $docId,
            'roleId' => $roleId,
            'isAllowDownload' => 0,
            'isTimeBound' => 0,
            'createdBy' => $user->id,
            'modifiedBy' => $user->id,
            'createdDate' => now(),
            'modifiedDate' => now(),
        ]);

        $newRoleId = Uuid::uuid4()->toString();
        DB::table('roles')->insert([
            'id' => $newRoleId,
            'name' => 'New Role',
            'createdBy' => $user->id,
            'modifiedBy' => $user->id,
            'createdDate' => now(),
            'modifiedDate' => now(),
            'isDeleted' => 0,
        ]);

        $response = $this->actingAsUser($user, ['ALL_DOCUMENTS_EDIT_DOCUMENT'])
            ->putJson("/api/document/{$docId}", [
                'name' => 'Updated',
                'description' => 'Updated desc',
                'categoryId' => $categoryId,
                'documentMetaDatas' => [],
                'documentRolePermissions' => [
                    ['roleId' => $newRoleId, 'isAllowDownload' => 1, 'isTimeBound' => false],
                ],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('documentRolePermissions', ['id' => $oldPermId]);
        $this->assertDatabaseHas('documentRolePermissions', [
            'documentId' => $docId,
            'roleId' => $newRoleId,
            'isAllowDownload' => 1,
        ]);
    }

    /* ============================================
     * PUT /document/{id} - notification sync
     * ============================================ */

    public function test_update_document_replaces_notifications()
    {
        $user = Users::factory()->create();
        $user2 = Users::factory()->create();
        $categoryId = $this->createCategory($user->id);
        $docId = $this->createDocument($user->id, $categoryId);

        DB::table('userNotifications')->insert([
            'id' => Uuid::uuid4()->toString(),
            'documentId' => $docId,
            'userId' => $user2->id,
            'type' => 'document',
            'createdDate' => now(),
            'modifiedDate' => now(),
        ]);

        $user3 = Users::factory()->create();
        $response = $this->actingAsUser($user, ['ALL_DOCUMENTS_EDIT_DOCUMENT'])
            ->putJson("/api/document/{$docId}", [
                'name' => 'Updated',
                'description' => 'Updated desc',
                'categoryId' => $categoryId,
                'documentMetaDatas' => [],
                'documentUserPermissions' => [
                    ['userId' => $user3->id, 'isAllowDownload' => 1, 'isTimeBound' => false],
                ],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('userNotifications', [
            'documentId' => $docId,
            'userId' => $user2->id,
        ]);
        $this->assertDatabaseHas('userNotifications', [
            'documentId' => $docId,
            'userId' => $user3->id,
        ]);
    }

    /* ============================================
     * PUT /document/{id} - audit trails
     * ============================================ */

    public function test_update_document_creates_audit_trails_for_role_and_user_assignments()
    {
        $user = Users::factory()->create();
        $user2 = Users::factory()->create();
        $user3 = Users::factory()->create();
        $categoryId = $this->createCategory($user->id);
        $docId = $this->createDocument($user->id, $categoryId);

        $roleId = Uuid::uuid4()->toString();
        DB::table('roles')->insert([
            'id' => $roleId,
            'name' => 'Test Role',
            'createdBy' => $user->id,
            'modifiedBy' => $user->id,
            'createdDate' => now(),
            'modifiedDate' => now(),
            'isDeleted' => 0,
        ]);
        $roleId2 = Uuid::uuid4()->toString();
        DB::table('roles')->insert([
            'id' => $roleId2,
            'name' => 'Test Role 2',
            'createdBy' => $user->id,
            'modifiedBy' => $user->id,
            'createdDate' => now(),
            'modifiedDate' => now(),
            'isDeleted' => 0,
        ]);

        $response = $this->actingAsUser($user, ['ALL_DOCUMENTS_EDIT_DOCUMENT'])
            ->putJson("/api/document/{$docId}", [
                'name' => 'Updated',
                'description' => 'Updated desc',
                'categoryId' => $categoryId,
                'documentMetaDatas' => [],
                'documentRolePermissions' => [
                    ['roleId' => $roleId, 'isAllowDownload' => 1, 'isTimeBound' => false],
                    ['roleId' => $roleId2, 'isAllowDownload' => 1, 'isTimeBound' => false],
                ],
                'documentUserPermissions' => [
                    ['userId' => $user2->id, 'isAllowDownload' => 0, 'isTimeBound' => false],
                    ['userId' => $user3->id, 'isAllowDownload' => 0, 'isTimeBound' => false],
                ],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('documentAuditTrails', [
            'documentId' => $docId,
            'assignToRoleId' => "$roleId,$roleId2",
        ]);
        $this->assertDatabaseHas('documentAuditTrails', [
            'documentId' => $docId,
            'assignToUserId' => "$user2->id,$user3->id",
        ]);
    }

    /* ============================================
     * PUT /document/{id} - creator permission guarantee
     * ============================================ */

    public function test_update_document_ensures_creator_has_permission()
    {
        $user = Users::factory()->create();
        $categoryId = $this->createCategory($user->id);
        $docId = $this->createDocument($user->id, $categoryId);

        $response = $this->actingAsUser($user, ['ALL_DOCUMENTS_EDIT_DOCUMENT'])
            ->putJson("/api/document/{$docId}", [
                'name' => 'Updated',
                'description' => 'Updated desc',
                'categoryId' => $categoryId,
                'documentMetaDatas' => [],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('documentUserPermissions', [
            'documentId' => $docId,
            'userId' => $user->id,
            'isAllowDownload' => 1,
        ]);
    }

    /* ============================================
     * GET /document/assigned-documents - isAllowDownload
     * ============================================ */

    public function test_assigned_documents_returns_isAllowDownload()
    {
        $user = Users::factory()->create();
        $categoryId = $this->createCategory($user->id);
        $docId = $this->createDocument($user->id, $categoryId);

        DB::table('documentUserPermissions')->insert([
            'id' => Uuid::uuid4()->toString(),
            'documentId' => $docId,
            'userId' => $user->id,
            'isAllowDownload' => 1,
            'isTimeBound' => 0,
            'createdBy' => $user->id,
            'modifiedBy' => $user->id,
            'createdDate' => now(),
            'modifiedDate' => now(),
        ]);

        $response = $this->actingAsUser($user)
            ->getJson('/api/document/assigned-documents?orderBy=createdDate desc&pageSize=10&skip=0');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json());
        $this->assertEquals(1, $response->json()[0]['isAllowDownload']);
    }

    /* ============================================
     * GET /document/assigned-documents - property_exists
     * ============================================ */

    public function test_assigned_documents_without_optional_filters_does_not_error()
    {
        $user = Users::factory()->create();
        $categoryId = $this->createCategory($user->id);
        $docId = $this->createDocument($user->id, $categoryId);

        DB::table('documentUserPermissions')->insert([
            'id' => Uuid::uuid4()->toString(),
            'documentId' => $docId,
            'userId' => $user->id,
            'isAllowDownload' => 0,
            'isTimeBound' => 0,
            'createdBy' => $user->id,
            'modifiedBy' => $user->id,
            'createdDate' => now(),
            'modifiedDate' => now(),
        ]);

        $response = $this->actingAsUser($user)
            ->getJson('/api/document/assigned-documents?orderBy=createdDate desc&pageSize=10&skip=0');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json());
    }
}
