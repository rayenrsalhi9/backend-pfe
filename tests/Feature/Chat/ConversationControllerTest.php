<?php

namespace Tests\Feature\Chat;

use App\Models\Users;
use App\Models\Conversation;
use App\Models\ConversationUser;
use App\Models\ConversationMessage;
use App\Models\MessageReaction;
use App\Models\UserNotifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;
use Ramsey\Uuid\Uuid;

class ConversationControllerTest extends TestCase
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

    // ===========================================
    // Authentication
    // ===========================================

    public function test_unauthenticated_user_cannot_list_conversations()
    {
        $response = $this->getJson('/api/conversations');
        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_send_messages()
    {
        $response = $this->postJson('/api/conversations/message', []);
        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_create_conversations()
    {
        $response = $this->postJson('/api/conversations/create', ['users' => [Uuid::uuid4()]]);
        $response->assertStatus(401);
    }

    // ===========================================
    // Conversation Listing
    // ===========================================

    public function test_empty_list_for_new_user_returns_paginated_response()
    {
        $user = Users::factory()->create();

        $response = $this->actingAsUser($user)->getJson('/api/conversations');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'meta']);
        $response->assertJson(['data' => []]);
        $this->assertEquals(0, $response->json()['meta']['total']);
    }

    public function test_lists_conversations_user_belongs_to()
    {
        $user = Users::factory()->create();
        $other = Users::factory()->create();

        $conversation = new Conversation();
        $conversation->save();
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $user->id]);
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $other->id]);

        $response = $this->actingAsUser($user)->getJson('/api/conversations');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json()['data']);

        ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => 'Hello',
            'type' => 'msg',
        ]);

        $response = $this->actingAsUser($user)->getJson('/api/conversations');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json()['data']);
    }

    public function test_does_not_show_other_users_conversations()
    {
        $userA = Users::factory()->create();
        $userB = Users::factory()->create();

        $convA = new Conversation();
        $convA->save();
        ConversationUser::create(['conversation_id' => $convA->id, 'user_id' => $userA->id]);
        ConversationUser::create(['conversation_id' => $convA->id, 'user_id' => Users::factory()->create()->id]);

        ConversationMessage::create([
            'conversation_id' => $convA->id,
            'sender_id' => $userA->id,
            'content' => 'Hi',
            'type' => 'msg',
        ]);

        $response = $this->actingAsUser($userB)->getJson('/api/conversations');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json()['data']);
    }

    public function test_orders_by_latest_message_activity()
    {
        $user = Users::factory()->create();
        $other = Users::factory()->create();

        $conv1 = new Conversation();
        $conv1->save();
        ConversationUser::create(['conversation_id' => $conv1->id, 'user_id' => $user->id]);
        ConversationUser::create(['conversation_id' => $conv1->id, 'user_id' => $other->id]);

        $conv2 = new Conversation();
        $conv2->save();
        ConversationUser::create(['conversation_id' => $conv2->id, 'user_id' => $user->id]);
        ConversationUser::create(['conversation_id' => $conv2->id, 'user_id' => $other->id]);

        ConversationMessage::create([
            'conversation_id' => $conv1->id,
            'sender_id' => $user->id,
            'content' => 'Older',
            'type' => 'msg',
            'created_at' => now()->subHour(),
        ]);

        ConversationMessage::create([
            'conversation_id' => $conv2->id,
            'sender_id' => $user->id,
            'content' => 'Newer',
            'type' => 'msg',
            'created_at' => now(),
        ]);

        $response = $this->actingAsUser($user)->getJson('/api/conversations');

        $response->assertStatus(200);
        $data = $response->json()['data'];
        $this->assertCount(2, $data);
        $this->assertEquals($conv2->id, $data[0]['id']);
        $this->assertEquals($conv1->id, $data[1]['id']);
    }

    public function test_pagination_respects_per_page()
    {
        $user = Users::factory()->create();
        $other = Users::factory()->create();

        for ($i = 0; $i < 3; $i++) {
            $conv = new Conversation();
            $conv->save();
            ConversationUser::create(['conversation_id' => $conv->id, 'user_id' => $user->id]);
            ConversationUser::create(['conversation_id' => $conv->id, 'user_id' => $other->id]);
            ConversationMessage::create([
                'conversation_id' => $conv->id,
                'sender_id' => $user->id,
                'content' => "Msg $i",
                'type' => 'msg',
            ]);
        }

        $response = $this->actingAsUser($user)->getJson('/api/conversations?per_page=2');

        $response->assertStatus(200);
        $body = $response->json();
        $this->assertArrayHasKey('data', $body, 'Response missing data key: ' . json_encode($body));
        $this->assertArrayHasKey('meta', $body, 'Response missing meta key. Full response: ' . json_encode($body));
        $this->assertArrayHasKey('perPage', $body['meta'], 'Meta keys: ' . json_encode(array_keys($body['meta'])));
        $this->assertCount(2, $body['data']);
        $this->assertEquals(2, $body['meta']['perPage']);
        $this->assertEquals(2, $body['meta']['lastPage']);
    }

    // ===========================================
    // Conversation Creation
    // ===========================================

    public function test_creates_one_on_one_conversation()
    {
        $user = Users::factory()->create();
        $other = Users::factory()->create();

        $response = $this->actingAsUser($user)->postJson('/api/conversations/create', [
            'users' => [$user->id, $other->id],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('conversation_users', ['user_id' => $user->id]);
        $this->assertDatabaseHas('conversation_users', ['user_id' => $other->id]);
    }

    public function test_returns_existing_one_on_one_instead_of_duplicate()
    {
        $user = Users::factory()->create();
        $other = Users::factory()->create();

        $response1 = $this->actingAsUser($user)->postJson('/api/conversations/create', [
            'users' => [$user->id, $other->id],
        ]);
        $response1->assertStatus(200);
        $conversationId = $response1->json()['id'];

        $response2 = $this->actingAsUser($user)->postJson('/api/conversations/create', [
            'users' => [$user->id, $other->id],
        ]);

        $response2->assertStatus(200);
        $this->assertEquals($conversationId, $response2->json()['id']);
    }

    public function test_creates_group_with_new_flag()
    {
        $user = Users::factory()->create();
        $users = collect([$user]);
        for ($i = 0; $i < 3; $i++) {
            $users->push(Users::factory()->create());
        }

        $response = $this->actingAsUser($user)->postJson('/api/conversations/create', [
            'users' => $users->pluck('id')->toArray(),
            'title' => 'Test Group',
            'new' => true,
        ]);

        $response->assertStatus(200);
        $this->assertNotNull($response->json()['title']);
        $this->assertEquals('Test Group', $response->json()['title']);
    }

    public function test_creates_group_without_title()
    {
        $user = Users::factory()->create();
        $users = collect([$user]);
        for ($i = 0; $i < 3; $i++) {
            $users->push(Users::factory()->create());
        }

        $response = $this->actingAsUser($user)->postJson('/api/conversations/create', [
            'users' => $users->pluck('id')->toArray(),
            'new' => true,
        ]);

        $response->assertStatus(200);
        $this->assertNull($response->json()['title']);
    }

    public function test_returns_400_for_empty_users_array()
    {
        $user = Users::factory()->create();

        $response = $this->actingAsUser($user)->postJson('/api/conversations/create', [
            'users' => [],
        ]);

        $response->assertStatus(400);
    }

    // ===========================================
    // Messages
    // ===========================================

    public function test_sends_text_message_successfully()
    {
        $user = Users::factory()->create();
        $other = Users::factory()->create();

        $conversation = new Conversation();
        $conversation->save();
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $user->id]);
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $other->id]);

        $response = $this->actingAsUser($user)->postJson('/api/conversations/message', [
            'conversation' => ['id' => $conversation->id],
            'content' => 'Hello world',
            'type' => 'msg',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('conversation_messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => 'Hello world',
            'type' => 'msg',
        ]);
    }

    public function test_returns_403_when_non_participant_sends_message()
    {
        $user = Users::factory()->create();
        $other = Users::factory()->create();
        $stranger = Users::factory()->create();

        $conversation = new Conversation();
        $conversation->save();
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $user->id]);
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $other->id]);

        $response = $this->actingAsUser($stranger)->postJson('/api/conversations/message', [
            'conversation' => ['id' => $conversation->id],
            'content' => 'Hacked',
            'type' => 'msg',
        ]);

        $response->assertStatus(403);
    }

    public function test_returns_404_when_conversation_does_not_exist()
    {
        $user = Users::factory()->create();

        $response = $this->actingAsUser($user)->postJson('/api/conversations/message', [
            'conversation' => ['id' => Uuid::uuid4()],
            'content' => 'Test',
            'type' => 'msg',
        ]);

        $response->assertStatus(404);
    }

    public function test_gets_conversation_messages()
    {
        $user = Users::factory()->create();
        $other = Users::factory()->create();

        $conversation = new Conversation();
        $conversation->save();
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $user->id]);
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $other->id]);

        ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => 'First',
            'type' => 'msg',
        ]);
        ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $other->id,
            'content' => 'Second',
            'type' => 'msg',
        ]);

        $response = $this->actingAsUser($user)->getJson("/api/conversations/{$conversation->id}/messages");

        $response->assertStatus(200);
        $this->assertCount(2, $response->json()['messages']);
    }

    public function test_returns_404_when_non_participant_gets_messages()
    {
        $user = Users::factory()->create();
        $other = Users::factory()->create();
        $stranger = Users::factory()->create();

        $conversation = new Conversation();
        $conversation->save();
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $user->id]);
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $other->id]);

        $response = $this->actingAsUser($stranger)->getJson("/api/conversations/{$conversation->id}/messages");

        $response->assertStatus(404);
    }

    public function test_no_notification_created_when_sending_message()
    {
        $user = Users::factory()->create();
        $other = Users::factory()->create();

        $conversation = new Conversation();
        $conversation->save();
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $user->id]);
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $other->id]);

        $this->actingAsUser($user)->postJson('/api/conversations/message', [
            'conversation' => ['id' => $conversation->id],
            'content' => 'No notification',
            'type' => 'msg',
        ]);

        $this->assertEquals(0, UserNotifications::where('type', 'message')->count());
    }

    public function test_sends_file_message()
    {
        $user = Users::factory()->create();
        $this->actingAs($user, 'api');
        $conversation = new Conversation();
        $conversation->save();
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $user->id]);

        $response = $this->postJson('/api/conversations/message', [
            'conversation' => ['id' => $conversation->id],
            'content' => 'data:application/pdf;base64,JVBERi0xLjQKJ...' ,
            'type' => 'application',
            'mime' => 'application/pdf'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('conversation_messages', [
            'conversation_id' => $conversation->id,
            'type' => 'application'
        ]);
    }

    // ===========================================
    // Message Seen
    // ===========================================

    public function test_marks_message_as_seen()
    {
        $user = Users::factory()->create();
        $other = Users::factory()->create();

        $conversation = new Conversation();
        $conversation->save();
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $user->id]);
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $other->id]);

        $message = ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $other->id,
            'content' => 'Test',
            'type' => 'msg',
        ]);

        $response = $this->actingAsUser($user)->putJson("/api/conversations/message/{$message->id}/seen");

        $response->assertStatus(200);
        $this->assertNotNull($response->json()['isRead']);
    }

    // ===========================================
    // Reactions
    // ===========================================

    public function test_adds_new_reaction()
    {
        $user = Users::factory()->create();
        $other = Users::factory()->create();

        $conversation = new Conversation();
        $conversation->save();
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $user->id]);
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $other->id]);

        $message = ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $other->id,
            'content' => 'Nice',
            'type' => 'msg',
        ]);

        $response = $this->actingAsUser($user)->putJson("/api/conversations/message/{$message->id}/reaction", [
            'mid' => $message->id,
            'uid' => $user->id,
            'type' => 'like',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('message_reactions', [
            'conversation_message_id' => $message->id,
            'sender_id' => $user->id,
            'type' => 'like',
        ]);
    }

    public function test_changes_reaction_type()
    {
        $user = Users::factory()->create();
        $other = Users::factory()->create();

        $conversation = new Conversation();
        $conversation->save();
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $user->id]);
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $other->id]);

        $message = ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $other->id,
            'content' => 'Test',
            'type' => 'msg',
        ]);

        MessageReaction::create([
            'conversation_message_id' => $message->id,
            'sender_id' => $user->id,
            'type' => 'like',
        ]);

        $response = $this->actingAsUser($user)->putJson("/api/conversations/message/{$message->id}/reaction", [
            'mid' => $message->id,
            'uid' => $user->id,
            'type' => 'heart',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('message_reactions', [
            'conversation_message_id' => $message->id,
            'sender_id' => $user->id,
            'type' => 'heart',
        ]);
        $this->assertDatabaseMissing('message_reactions', [
            'conversation_message_id' => $message->id,
            'type' => 'like',
        ]);
    }

    public function test_removes_reaction_on_same_type_toggle()
    {
        $user = Users::factory()->create();
        $other = Users::factory()->create();

        $conversation = new Conversation();
        $conversation->save();
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $user->id]);
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $other->id]);

        $message = ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $other->id,
            'content' => 'Test',
            'type' => 'msg',
        ]);

        MessageReaction::create([
            'conversation_message_id' => $message->id,
            'sender_id' => $user->id,
            'type' => 'like',
        ]);

        $this->actingAsUser($user)->putJson("/api/conversations/message/{$message->id}/reaction", [
            'mid' => $message->id,
            'uid' => $user->id,
            'type' => 'like',
        ]);

        $this->assertDatabaseMissing('message_reactions', [
            'conversation_message_id' => $message->id,
            'sender_id' => $user->id,
        ]);
    }

    public function test_returns_404_if_message_does_not_exist_on_reaction()
    {
        $user = Users::factory()->create();
        $this->actingAs($user, 'api');
        $conversation = new Conversation();
        $conversation->save();
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $user->id]);

        $response = $this->putJson('/api/conversations/message/9999/reaction', [
            'mid' => 9999,
            'type' => 'like',
            'uid' => $user->id
        ]);

        $response->assertStatus(404);
    }

    public function test_reaction_creates_system_message_in_conversation()
    {
        $user = Users::factory()->create();
        $other = Users::factory()->create();

        $conversation = new Conversation();
        $conversation->save();
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $user->id]);
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $other->id]);

        $message = ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $other->id,
            'content' => 'Nice',
            'type' => 'msg',
        ]);

        $this->actingAsUser($user)->putJson("/api/conversations/message/{$message->id}/reaction", [
            'mid' => $message->id,
            'uid' => $user->id,
            'type' => 'like',
        ]);

        $this->assertDatabaseHas('conversation_messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'type' => 'reaction',
            'content' => 'Liked your message'
        ]);
    }

    // ===========================================
    // Conversation Users
    // ===========================================

    public function test_participant_can_get_users()
    {
        $user = Users::factory()->create();
        $other = Users::factory()->create();

        $conversation = new Conversation();
        $conversation->save();
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $user->id]);
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $other->id]);

        $response = $this->actingAsUser($user)->getJson("/api/conversations/{$conversation->id}/users");

        $response->assertStatus(200);
    }

    public function test_non_participant_gets_404_for_users()
    {
        $user = Users::factory()->create();
        $stranger = Users::factory()->create();

        $conversation = new Conversation();
        $conversation->save();
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $user->id]);

        $response = $this->actingAsUser($stranger)->getJson("/api/conversations/{$conversation->id}/users");

        $response->assertStatus(404);
    }

    public function test_adds_user_to_conversation()
    {
        $user = Users::factory()->create();
        $existing = Users::factory()->create();
        $newUser = Users::factory()->create();

        $conversation = new Conversation();
        $conversation->save();
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $user->id]);
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $existing->id]);

        $response = $this->actingAsUser($user)->postJson('/api/conversations/add-user', [
            'conversationId' => $conversation->id,
            'selectedUser' => ['id' => $newUser->id],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('conversation_users', [
            'conversation_id' => $conversation->id,
            'user_id' => $newUser->id,
        ]);
    }

    public function test_returns_409_when_adding_existing_user()
    {
        $user = Users::factory()->create();
        $other = Users::factory()->create();

        $conversation = new Conversation();
        $conversation->save();
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $user->id]);
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $other->id]);

        $response = $this->actingAsUser($user)->postJson('/api/conversations/add-user', [
            'conversationId' => $conversation->id,
            'selectedUser' => ['id' => $other->id],
        ]);

        $response->assertStatus(409);
    }

    public function test_removes_user_from_conversation()
    {
        $user = Users::factory()->create();
        $other = Users::factory()->create();
        $third = Users::factory()->create();

        $conversation = new Conversation();
        $conversation->save();
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $user->id]);
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $other->id]);
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $third->id]);

        $response = $this->actingAsUser($user)->postJson('/api/conversations/remove-user', [
            'conversationId' => $conversation->id,
            'userId' => $other->id,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('conversation_users', [
            'conversation_id' => $conversation->id,
            'user_id' => $other->id,
        ]);
    }

    public function test_returns_400_when_removing_last_participant()
    {
        $user = Users::factory()->create();

        $conversation = new Conversation();
        $conversation->save();
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $user->id]);

        $response = $this->actingAsUser($user)->postJson('/api/conversations/remove-user', [
            'conversationId' => $conversation->id,
            'userId' => $user->id,
        ]);

        $response->assertStatus(400);
    }

    public function test_returns_403_when_non_participant_adds_user()
    {
        $user = Users::factory()->create();
        $stranger = Users::factory()->create();
        $newUser = Users::factory()->create();

        $conversation = new Conversation();
        $conversation->save();
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $user->id]);

        $response = $this->actingAsUser($stranger)->postJson('/api/conversations/add-user', [
            'conversationId' => $conversation->id,
            'selectedUser' => ['id' => $newUser->id],
        ]);

        $response->assertStatus(403);
    }

    // ===========================================
    // Conversation Update
    // ===========================================

    public function test_updates_conversation_title()
    {
        $user = Users::factory()->create();
        $other = Users::factory()->create();

        $conversation = new Conversation();
        $conversation->title = 'Old Title';
        $conversation->save();
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $user->id]);
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $other->id]);

        $response = $this->actingAsUser($user)->putJson("/api/conversations/update/{$conversation->id}", [
            'title' => 'New Title',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('New Title', $response->json()['title']);
    }

    public function test_returns_404_when_updating_nonexistent_conversation()
    {
        $user = Users::factory()->create();

        $response = $this->actingAsUser($user)->putJson('/api/conversations/update/' . Uuid::uuid4(), [
            'title' => 'New Title',
        ]);

        $response->assertStatus(404);
    }

    public function test_returns_403_when_non_participant_updates()
    {
        $user = Users::factory()->create();
        $stranger = Users::factory()->create();

        $conversation = new Conversation();
        $conversation->save();
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $user->id]);

        $response = $this->actingAsUser($stranger)->putJson("/api/conversations/update/{$conversation->id}", [
            'title' => 'Hacked',
        ]);

        $response->assertStatus(403);
    }

    // ===========================================
    // Conversation Delete
    // ===========================================

    public function test_deletes_conversation()
    {
        $user = Users::factory()->create();
        $other = Users::factory()->create();

        $conversation = new Conversation();
        $conversation->save();
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $user->id]);
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $other->id]);

        $response = $this->actingAsUser($user)->deleteJson("/api/conversations/delete/{$conversation->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('conversations', ['id' => $conversation->id]);
    }

    public function test_returns_404_when_deleting_nonexistent()
    {
        $user = Users::factory()->create();

        $response = $this->actingAsUser($user)->deleteJson('/api/conversations/delete/' . Uuid::uuid4());

        $response->assertStatus(404);
    }

    public function test_returns_403_when_non_participant_deletes()
    {
        $user = Users::factory()->create();
        $other = Users::factory()->create();
        $this->actingAs($user, 'api');

        $conversation = new Conversation();
        $conversation->save();
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $other->id]);

        $response = $this->deleteJson("/api/conversations/delete/{$conversation->id}");

        $response->assertStatus(403);
    }

    public function test_marks_message_as_delivered()
    {
        $user = Users::factory()->create();
        $this->actingAs($user, 'api');
        $conversation = new Conversation();
        $conversation->save();
        ConversationUser::create(['conversation_id' => $conversation->id, 'user_id' => $user->id]);

        $message = ConversationMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $user->id,
            'content' => 'Test message',
            'type' => 'msg'
        ]);

        $response = $this->putJson("/api/conversations/message/{$message->id}/delivered");

        $response->assertStatus(200);
        $this->assertNotNull($message->fresh()->is_delivered);
    }
}
