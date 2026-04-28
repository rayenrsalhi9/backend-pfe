<?php

namespace Tests\Feature;

use App\Models\Users;
use App\Models\Reminders;
use App\Models\ReminderUsers;
use App\Models\UserNotifications;
use App\Models\FrequencyEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function getAuthToken(Users $user, array $claims = []): string
    {
        // We need to match the structure expected by AuthController and HasPermissionTrait
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

    protected function tearDown(): void
    {
        JWTAuth::unsetToken();
        parent::tearDown();
    }

    /** @test */
    public function it_can_list_all_reminders()
    {
        $user = Users::factory()->create();
        
        // Create some reminders
        DB::table('reminders')->insert([
            [
                'id' => \Ramsey\Uuid\Uuid::uuid4(),
                'eventName' => 'Test Event 1',
                'description' => 'Description for Test Event 1',
                'startDate' => Carbon::now()->addDay(),
                'endDate' => Carbon::now()->addDays(2),
                'frequency' => FrequencyEnum::OneTime->value,
                'createdBy' => $user->id,
                'modifiedBy' => $user->id,
                'isDeleted' => 0
            ],
            [
                'id' => \Ramsey\Uuid\Uuid::uuid4(),
                'eventName' => 'Test Event 2',
                'description' => 'Description for Test Event 2',
                'startDate' => Carbon::now()->addDays(3),
                'endDate' => Carbon::now()->addDays(4),
                'frequency' => FrequencyEnum::Daily->value,
                'createdBy' => $user->id,
                'modifiedBy' => $user->id,
                'isDeleted' => 0
            ]
        ]);

        $response = $this->actingAsUser($user, ['REMINDER_VIEW_REMINDERS'])
            ->getJson('/api/reminder/all?pageSize=10&skip=0');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json());
    }

    /** @test */
    public function it_can_list_reminders_for_logged_in_user()
    {
        $user1 = Users::factory()->create();
        $user2 = Users::factory()->create();
        
        $reminderId1 = \Ramsey\Uuid\Uuid::uuid4();
        $reminderId2 = \Ramsey\Uuid\Uuid::uuid4();

        // Reminder 1 created by user1
        DB::table('reminders')->insert([
            'id' => $reminderId1,
            'eventName' => 'User 1 Event',
            'description' => 'Description for User 1 Event',
            'startDate' => Carbon::now()->addDay(),
            'createdBy' => $user1->id,
            'modifiedBy' => $user1->id,
            'isDeleted' => 0
        ]);

        // Reminder 2 created by user2 but assigned to user1
        DB::table('reminders')->insert([
            'id' => $reminderId2,
            'eventName' => 'Assigned to User 1',
            'description' => 'Description for Assigned to User 1',
            'startDate' => Carbon::now()->addDay(),
            'createdBy' => $user2->id,
            'modifiedBy' => $user2->id,
            'isDeleted' => 0
        ]);
        
        DB::table('reminderUsers')->insert([
            'id' => \Ramsey\Uuid\Uuid::uuid4(),
            'reminderId' => $reminderId2,
            'userId' => $user1->id
        ]);

        $response = $this->actingAsUser($user1)
            ->getJson('/api/reminder/all/current-user?pageSize=10&skip=0');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json());
        
        $eventNames = collect($response->json())->pluck('eventName');
        $this->assertContains('User 1 Event', $eventNames);
        $this->assertContains('Assigned to User 1', $eventNames);
    }

    /** @test */
    public function it_requires_permission_to_create_reminder()
    {
        $user = Users::factory()->create();
        
        $response = $this->actingAsUser($user) // No claims
            ->postJson('/api/reminder', [
                'eventName' => 'New Reminder',
                'startDate' => Carbon::now()->addDay()
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_create_a_one_time_reminder()
    {
        $user = Users::factory()->create();
        
        $response = $this->actingAsUser($user, ['REMINDER_CREATE_REMINDER'])
            ->postJson('/api/reminder', [
                'eventName' => 'New OneTime Reminder',
                'startDate' => Carbon::now()->addDay(),
                'isRepeated' => false,
                'category' => 'normal'
            ]);

        $response->assertStatus(201);
        
        $this->assertDatabaseHas('reminders', [
            'eventName' => 'New OneTime Reminder',
            'createdBy' => $user->id,
            'frequency' => FrequencyEnum::OneTime->value
        ]);

        // Check notification
        $this->assertDatabaseHas('userNotifications', [
            'userId' => $user->id,
            'message' => 'You created a reminder: New OneTime Reminder',
            'type' => 'reminder'
        ]);
    }

    /** @test */
    public function it_can_create_a_daily_reminder_assigned_to_others()
    {
        $creator = Users::factory()->create();
        $assignee = Users::factory()->create();
        
        $response = $this->actingAsUser($creator, ['REMINDER_CREATE_REMINDER'])
            ->postJson('/api/reminder', [
                'eventName' => 'Daily Team Meeting',
                'startDate' => Carbon::now()->addDay(),
                'isRepeated' => true,
                'frequency' => FrequencyEnum::Daily->value,
                'reminderUsers' => [
                    ['userId' => $assignee->id]
                ],
                'dailyReminders' => [
                    ['dayOfWeek' => 1], // Monday
                    ['dayOfWeek' => 2]  // Tuesday
                ]
            ]);

        $response->assertStatus(201);
        
        $reminder = Reminders::where('eventName', 'Daily Team Meeting')->first();
        $this->assertNotNull($reminder);
        
        // Check assignments
        $this->assertDatabaseHas('reminderUsers', [
            'reminderId' => $reminder->id,
            'userId' => $assignee->id
        ]);

        // Check daily configuration
        $this->assertDatabaseHas('dailyReminders', [
            'reminderId' => $reminder->id,
            'dayOfWeek' => 1
        ]);

        // Check notifications for both
        $this->assertDatabaseHas('userNotifications', [
            'userId' => $creator->id,
            'message' => 'You created a reminder: Daily Team Meeting'
        ]);
        $this->assertDatabaseHas('userNotifications', [
            'userId' => $assignee->id,
            'message' => 'New reminder: Daily Team Meeting'
        ]);
    }

    /** @test */
    public function it_can_update_a_reminder_owned_by_user()
    {
        $user = Users::factory()->create();
        $reminderId = \Ramsey\Uuid\Uuid::uuid4();

        DB::table('reminders')->insert([
            'id' => $reminderId,
            'eventName' => 'Old Name',
            'description' => 'Old description',
            'startDate' => Carbon::now()->addDay(),
            'createdBy' => $user->id,
            'modifiedBy' => $user->id,
            'isDeleted' => 0
        ]);

        $response = $this->actingAsUser($user, ['REMINDER_EDIT_REMINDER'])
            ->putJson("/api/reminder/{$reminderId}", [
                'eventName' => 'Updated Name',
                'category' => 'urgent'
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('reminders', [
            'id' => $reminderId,
            'eventName' => 'Updated Name',
            'category' => 'urgent'
        ]);
    }

    /** @test */
    public function it_can_update_a_reminder_assigned_to_user()
    {
        $creator = Users::factory()->create();
        $assignee = Users::factory()->create();
        $reminderId = \Ramsey\Uuid\Uuid::uuid4();

        DB::table('reminders')->insert([
            'id' => $reminderId,
            'eventName' => 'Original Event',
            'description' => 'Original description',
            'startDate' => Carbon::now()->addDay(),
            'createdBy' => $creator->id,
            'modifiedBy' => $creator->id,
            'isDeleted' => 0
        ]);
        
        DB::table('reminderUsers')->insert([
            'id' => \Ramsey\Uuid\Uuid::uuid4(),
            'reminderId' => $reminderId,
            'userId' => $assignee->id
        ]);

        $response = $this->actingAsUser($assignee, ['REMINDER_EDIT_REMINDER'])
            ->putJson("/api/reminder/{$reminderId}", [
                'eventName' => 'Modified by Assignee'
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('reminders', [
            'id' => $reminderId,
            'eventName' => 'Modified by Assignee'
        ]);
    }

    /** @test */
    public function it_returns_403_when_updating_non_owned_or_assigned_reminder()
    {
        $owner = Users::factory()->create();
        $intruder = Users::factory()->create();
        $reminderId = \Ramsey\Uuid\Uuid::uuid4();

        DB::table('reminders')->insert([
            'id' => $reminderId,
            'eventName' => 'Private Event',
            'description' => 'Private description',
            'startDate' => Carbon::now()->addDay(),
            'createdBy' => $owner->id,
            'modifiedBy' => $owner->id,
            'isDeleted' => 0
        ]);

        $response = $this->actingAsUser($intruder, ['REMINDER_EDIT_REMINDER'])
            ->putJson("/api/reminder/{$reminderId}", [
                'eventName' => 'Hacked Name'
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_soft_delete_a_reminder()
    {
        $user = Users::factory()->create();
        $reminderId = \Ramsey\Uuid\Uuid::uuid4();

        DB::table('reminders')->insert([
            'id' => $reminderId,
            'eventName' => 'To Be Deleted',
            'description' => 'Description for deletion',
            'startDate' => Carbon::now()->addDay(),
            'createdBy' => $user->id,
            'modifiedBy' => $user->id,
            'isDeleted' => 0
        ]);

        $response = $this->actingAsUser($user, ['REMINDER_DELETE_REMINDER'])
            ->deleteJson("/api/reminder/{$reminderId}");

        $response->assertStatus(204);
        
        $this->assertDatabaseHas('reminders', [
            'id' => $reminderId,
            'isDeleted' => 1,
            'deletedBy' => $user->id
        ]);
    }

    /** @test */
    public function it_can_remove_itself_from_a_reminder()
    {
        $creator = Users::factory()->create();
        $user = Users::factory()->create();
        $reminderId = \Ramsey\Uuid\Uuid::uuid4();

        DB::table('reminders')->insert([
            'id' => $reminderId,
            'eventName' => 'Group Event',
            'description' => 'Group event description',
            'startDate' => Carbon::now()->addDay(),
            'createdBy' => $creator->id,
            'modifiedBy' => $creator->id,
            'isDeleted' => 0
        ]);
        
        DB::table('reminderUsers')->insert([
            'id' => \Ramsey\Uuid\Uuid::uuid4(),
            'reminderId' => $reminderId,
            'userId' => $user->id
        ]);

        $response = $this->actingAsUser($user)
            ->deleteJson("/api/reminder/current-user/{$reminderId}");

        $response->assertStatus(200);
        
        $this->assertDatabaseMissing('reminderUsers', [
            'reminderId' => $reminderId,
            'userId' => $user->id
        ]);
    }

    /** @test */
    public function it_validates_required_fields_on_create()
    {
        $user = Users::factory()->create();
        
        $response = $this->actingAsUser($user, ['REMINDER_CREATE_REMINDER'])
            ->postJson('/api/reminder', [
                'startDate' => Carbon::now()->addDay()
            ]);

        $response->assertStatus(500); 
        $this->assertStringContainsString('Event name is required', $response->json('message'));
    }

    /** @test */
    public function it_validates_non_empty_event_name_on_update()
    {
        $user = Users::factory()->create();
        $reminderId = \Ramsey\Uuid\Uuid::uuid4();

        DB::table('reminders')->insert([
            'id' => $reminderId,
            'eventName' => 'Original',
            'description' => 'Original description',
            'startDate' => Carbon::now()->addDay(),
            'createdBy' => $user->id,
            'modifiedBy' => $user->id,
            'isDeleted' => 0
        ]);

        $response = $this->actingAsUser($user, ['REMINDER_EDIT_REMINDER'])
            ->putJson("/api/reminder/{$reminderId}", [
                'eventName' => ''
            ]);

        $response->assertStatus(500);
        $this->assertStringContainsString('Event name cannot be empty', $response->json('message'));
    }
}
