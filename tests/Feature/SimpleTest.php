<?php

namespace Tests\Feature;

use App\Models\Users;
use App\Models\Reminders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SimpleTest extends TestCase
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

    /** @test */
    public function test_delete_route()
    {
        $user = Users::factory()->create();
        $reminderId = \Ramsey\Uuid\Uuid::uuid4();

        DB::table('reminders')->insert([
            'id' => $reminderId,
            'eventName' => 'To Be Deleted',
            'startDate' => Carbon::now()->addDay(),
            'createdBy' => $user->id,
            'modifiedBy' => $user->id,
            'isDeleted' => 0
        ]);

        $token = $this->getAuthToken($user, ['REMINDER_DELETE_REMINDER']);
        
        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/reminder/{$reminderId}");

        $response->dump();
        $response->assertStatus(204);
    }
}
