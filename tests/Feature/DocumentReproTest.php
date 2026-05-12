<?php

namespace Tests\Feature;

use App\Models\Users;
use App\Models\Documents;
use App\Models\DocumentUserPermissions;
use App\Models\DocumentMetaDatas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class DocumentReproTest extends TestCase
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

    public function test_update_document_adds_new_permissions_successfully()
    {
        $user = Users::factory()->create();
        
        // Create category
        $categoryId = Uuid::uuid4()->toString();
        DB::table('categories')->insert([
            'id' => $categoryId,
            'name' => 'Test Cat',
            'createdBy' => $user->id,
            'modifiedBy' => $user->id,
            'createdDate' => now(),
            'modifiedDate' => now(),
            'isDeleted' => 0,
        ]);

        // Create document
        $docId = Uuid::uuid4()->toString();
        DB::table('documents')->insert([
            'id' => $docId,
            'name' => 'Initial Name',
            'description' => 'Initial Desc',
            'url' => 'docs/test.pdf',
            'categoryId' => $categoryId,
            'extension' => 'pdf',
            'createdBy' => $user->id,
            'modifiedBy' => $user->id,
            'createdDate' => now(),
            'modifiedDate' => now(),
            'isDeleted' => 0,
        ]);

        // Initial permission for user
        DB::table('documentUserPermissions')->insert([
            'id' => Uuid::uuid4()->toString(),
            'documentId' => $docId,
            'userId' => $user->id,
            'isAllowDownload' => 1,
            'createdBy' => $user->id,
            'modifiedBy' => $user->id,
            'createdDate' => now(),
            'modifiedDate' => now(),
        ]);

        // New user to add
        $user2 = Users::factory()->create();

        $payload = [
            'name' => 'Updated Name',
            'description' => 'Updated Desc',
            'categoryId' => $categoryId,
            'documentMetaDatas' => [
                ['metatag' => 'newtag']
            ],
            'documentUserPermissions' => [
                ['userId' => $user->id, 'isAllowDownload' => 1],
                ['userId' => $user2->id, 'isAllowDownload' => 0]
            ],
        ];

        $response = $this->actingAsUser($user, ['ALL_DOCUMENTS_EDIT_DOCUMENT'])
            ->putJson("/api/document/{$docId}", $payload);

        $response->assertStatus(200);

        // Check if DB has new permissions
        $this->assertDatabaseHas('documentUserPermissions', [
            'documentId' => $docId,
            'userId' => $user2->id
        ]);
        $this->assertDatabaseHas('documentMetaDatas', [
            'documentId' => $docId,
            'metatag' => 'newtag'
        ]);
    }

    public function test_save_document_repro_generic_error()
    {
        $user = Users::factory()->create();
        
        // Create category
        $categoryId = Uuid::uuid4()->toString();
        DB::table('categories')->insert([
            'id' => $categoryId,
            'name' => 'Test Cat',
            'createdBy' => $user->id,
            'modifiedBy' => $user->id,
            'createdDate' => now(),
            'modifiedDate' => now(),
            'isDeleted' => 0,
        ]);

        $file = new \Illuminate\Http\UploadedFile(
            tempnam(sys_get_temp_dir(), 'upl'),
            'test.pdf',
            'application/pdf',
            null,
            true
        );

        $response = $this->actingAsUser($user, ['ALL_DOCUMENTS_CREATE_DOCUMENT'])
            ->post('/api/document', [
                'name' => 'New Doc',
                'categoryId' => $categoryId,
                'description' => 'Desc',
                'extension' => 'pdf',
                'uploadFile' => $file,
                'documentMetaDatas' => json_encode([['metatag' => 'tag1']]),
                'documentUserPermissions' => json_encode([]),
            ]);

        $response->assertStatus(200);
    }
}
