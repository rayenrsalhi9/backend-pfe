<?php

namespace Tests\Feature;

use App\Models\Surveys;
use App\Models\SurveyAnswers;
use App\Models\Users;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class SurveyTest extends TestCase
{
    use RefreshDatabase;

    protected function getAuthToken(Users $user): string
    {
        return JWTAuth::fromUser($user);
    }

    protected function actingAsUser(Users $user)
    {
        $token = $this->getAuthToken($user);
        return $this->withHeader('Authorization', "Bearer {$token}");
    }

    public function test_getOne_returns_public_survey_for_any_authenticated_user()
    {
        $user = Users::factory()->create();
        $otherUser = Users::factory()->create();

        $survey = Surveys::factory()->create([
            'privacy' => 'public',
            'created_by' => $otherUser->id,
        ]);

        $response = $this->actingAsUser($user)
            ->getJson("/api/surveys/get/{$survey->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $survey->id);
    }

    public function test_getOne_returns_private_survey_for_allowed_user()
    {
        $user = Users::factory()->create();
        $survey = Surveys::factory()->create([
            'privacy' => 'private',
            'users' => [$user->id],
        ]);

        $response = $this->actingAsUser($user)
            ->getJson("/api/surveys/get/{$survey->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $survey->id);
    }

    public function test_getOne_returns_403_for_private_survey_without_access()
    {
        $user = Users::factory()->create();
        $otherUser = Users::factory()->create();

        $survey = Surveys::factory()->create([
            'privacy' => 'private',
            'created_by' => $otherUser->id,
            'users' => [$otherUser->id],
        ]);

        $response = $this->actingAsUser($user)
            ->getJson("/api/surveys/get/{$survey->id}");

        $response->assertStatus(403);
    }

    public function test_getOne_returns_403_for_private_survey_when_creator_without_users_entry()
    {
        $user = Users::factory()->create();

        $survey = Surveys::factory()->create([
            'privacy' => 'private',
            'created_by' => $user->id,
            'users' => null,
        ]);

        $response = $this->actingAsUser($user)
            ->getJson("/api/surveys/get/{$survey->id}");

        $response->assertStatus(200);
    }

    public function test_getOne_returns_404_for_nonexistent_survey()
    {
        $user = Users::factory()->create();

        $response = $this->actingAsUser($user)
            ->getJson('/api/surveys/get/nonexistent-id');

        $response->assertStatus(404);
    }

    public function test_getOne_returns_401_without_authentication()
    {
        $survey = Surveys::factory()->create(['privacy' => 'public']);

        $response = $this->getJson("/api/surveys/get/{$survey->id}");

        $response->assertStatus(401);
    }

    public function test_getLast_excludes_private_surveys_without_access()
    {
        $user = Users::factory()->create();
        $otherUser = Users::factory()->create();

        Surveys::factory()->create([
            'privacy' => 'private',
            'created_by' => $otherUser->id,
            'users' => [$otherUser->id],
            'closed' => false,
        ]);

        Surveys::factory()->create([
            'privacy' => 'public',
            'closed' => false,
        ]);

        $response = $this->actingAsUser($user)
            ->getJson('/api/surveys/latest');

        $response->assertStatus(200)
            ->assertJsonPath('privacy', 'public');
    }

    public function test_getLast_returns_private_survey_for_allowed_user()
    {
        $user = Users::factory()->create();

        $survey = Surveys::factory()->create([
            'privacy' => 'private',
            'users' => [$user->id],
            'closed' => false,
        ]);

        $response = $this->actingAsUser($user)
            ->getJson('/api/surveys/latest');

        $response->assertStatus(200)
            ->assertJsonPath('id', $survey->id);
    }

    public function test_getAll_respects_privacy_and_limit()
    {
        $user = Users::factory()->create();
        $otherUser = Users::factory()->create();

        Surveys::factory()->count(3)->create(['privacy' => 'public', 'closed' => false]);

        Surveys::factory()->create([
            'privacy' => 'private',
            'created_by' => $otherUser->id,
            'users' => [$otherUser->id],
            'closed' => false,
        ]);

        $response = $this->actingAsUser($user)
            ->getJson('/api/surveys?limit=5');

        $response->assertStatus(200);
        $this->assertLessThanOrEqual(5, count($response->json()));
        $this->assertEquals(3, count($response->json()));
    }

    public function test_update_requires_permission_or_ownership()
    {
        $user = Users::factory()->create();
        $owner = Users::factory()->create();

        $survey = Surveys::factory()->create(['created_by' => $owner->id]);

        $response = $this->actingAsUser($user)
            ->putJson("/api/surveys/update/{$survey->id}", [
                'title' => 'Updated Title',
            ]);

        $response->assertStatus(403);
    }

    public function test_update_saves_users_list_for_private_survey()
    {
        $owner = Users::factory()->create();
        $user1 = Users::factory()->create();
        $user2 = Users::factory()->create();

        $survey = Surveys::factory()->create([
            'created_by' => $owner->id,
            'privacy' => 'private',
            'users' => [$owner->id, $user1->id, $user2->id],
        ]);

        $response = $this->actingAsUser($owner)
            ->putJson("/api/surveys/update/{$survey->id}", [
                'title' => 'Updated Survey',
                'type' => 'simple',
                'privacy' => 'private',
                'users' => [$owner->id, $user1->id, $user2->id],
            ]);

        $response->assertStatus(200);

        $survey->refresh();
        $this->assertEquals('private', $survey->privacy);

        $userIds = array_map('strval', $survey->users ?? []);
        $this->assertContains((string)$owner->id, $userIds);
        $this->assertContains((string)$user1->id, $userIds);
        $this->assertContains((string)$user2->id, $userIds);
    }

    public function test_update_removes_users_from_private_survey()
    {
        $owner = Users::factory()->create();
        $user1 = Users::factory()->create();
        $user2 = Users::factory()->create();

        $survey = Surveys::factory()->create([
            'created_by' => $owner->id,
            'privacy' => 'private',
            'users' => [$owner->id, $user1->id, $user2->id],
        ]);

        $response = $this->actingAsUser($owner)
            ->putJson("/api/surveys/update/{$survey->id}", [
                'title' => 'Updated Survey',
                'type' => 'simple',
                'privacy' => 'private',
                'users' => [$owner->id, $user1->id],
            ]);

        $response->assertStatus(200);

        $survey->refresh();
        $this->assertEquals('private', $survey->privacy);

        $userIds = array_map('strval', $survey->users ?? []);
        $this->assertContains((string)$owner->id, $userIds);
        $this->assertContains((string)$user1->id, $userIds);
        $this->assertNotContains((string)$user2->id, $userIds);
    }

    public function test_update_returns_403_without_permission()
    {
        $user = Users::factory()->create();
        $owner = Users::factory()->create();

        $survey = Surveys::factory()->create(['created_by' => $owner->id]);

        $response = $this->actingAsUser($user)
            ->putJson("/api/surveys/update/{$survey->id}", [
                'title' => 'Updated Title',
            ]);

        $response->assertStatus(403);
    }

    public function test_create_validates_and_normalizes_users_list()
    {
        $user = Users::factory()->create();

        $response = $this->actingAsUser($user)
            ->postJson('/api/surveys/create', [
                'title' => 'Test Survey',
                'type' => 'simple',
                'privacy' => 'private',
                'users' => ['valid-uuid-1', 'valid-uuid-2'],
            ]);

        $response->assertStatus(200);

        $survey = Surveys::latest()->first();
        $this->assertEquals('private', $survey->privacy);
        $this->assertNotNull($survey->users);
        $userIds = array_map('strval', $survey->users ?? []);
        $this->assertContains((string) $user->id, $userIds);
        $this->assertContains('valid-uuid-1', $userIds);
        $this->assertContains('valid-uuid-2', $userIds);
    }

    public function test_create_ensures_creator_included_in_private_survey_users()
    {
        $user = Users::factory()->create();

        $response = $this->actingAsUser($user)
            ->postJson('/api/surveys/create', [
                'title' => 'Test Survey',
                'type' => 'simple',
                'privacy' => 'private',
                'users' => [],
            ]);

        $response->assertStatus(200);

        $survey = Surveys::latest()->first();
        $this->assertNotNull($survey->users);
        $userIds = array_map('strval', $survey->users);
        $this->assertContains((string) $user->id, $userIds);
    }

    public function test_survey_users_cast_to_array()
    {
        $survey = Surveys::factory()->create([
            'users' => [1, 2, 3],
        ]);

        $survey->refresh();

        $this->assertIsArray($survey->users);
        $this->assertEquals([1, 2, 3], $survey->users);
    }

    public function test_getAll_returns_only_accessible_surveys_for_unauthenticated_user()
    {
        Surveys::factory()->create(['privacy' => 'public']);
        Surveys::factory()->create(['privacy' => 'private', 'users' => ['some-user-id']]);

        $response = $this->getJson('/api/surveys');

        $response->assertStatus(200);
        $this->assertEquals(1, count($response->json()));
        $this->assertEquals('public', $response->json()[0]['privacy']);
    }

    public function test_answer_returns_403_for_private_survey_without_access()
    {
        $user = Users::factory()->create();
        $otherUser = Users::factory()->create();

        $survey = Surveys::factory()->create([
            'privacy' => 'private',
            'created_by' => $otherUser->id,
            'users' => [$otherUser->id],
        ]);

        $response = $this->actingAsUser($user)
            ->postJson("/api/surveys/answer/{$survey->id}", [
                'answer' => 1,
            ]);

        $response->assertStatus(403);
    }

    public function test_answer_returns_409_for_already_answered_survey()
    {
        $user = Users::factory()->create();

        $survey = Surveys::factory()->create([
            'privacy' => 'public',
        ]);

        SurveyAnswers::factory()->create([
            'survey_id' => $survey->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAsUser($user)
            ->postJson("/api/surveys/answer/{$survey->id}", [
                'answer' => 1,
            ]);

        $response->assertStatus(409);
    }

    public function test_statistics_returns_403_for_unauthorized_user()
    {
        $user = Users::factory()->create();
        $otherUser = Users::factory()->create();

        $survey = Surveys::factory()->create([
            'privacy' => 'private',
            'created_by' => $otherUser->id,
            'users' => [$otherUser->id],
        ]);

        $response = $this->actingAsUser($user)
            ->getJson("/api/surveys/statistics/{$survey->id}");

        $response->assertStatus(403);
    }

    public function test_statistics_returns_401_without_authentication()
    {
        $survey = Surveys::factory()->create(['privacy' => 'public']);

        $response = $this->getJson("/api/surveys/statistics/{$survey->id}");

        $response->assertStatus(401);
    }

    public function test_statistics_returns_200_for_public_survey()
    {
        $user = Users::factory()->create();

        $survey = Surveys::factory()->create([
            'privacy' => 'public',
            'created_by' => $user->id,
        ]);

        SurveyAnswers::factory()->create([
            'survey_id' => $survey->id,
            'user_id' => $user->id,
            'answer' => 5,
        ]);

        $response = $this->actingAsUser($user)
            ->getJson("/api/surveys/statistics/{$survey->id}");

        $response->assertStatus(200);
    }

    public function test_getLast_returns_public_survey_for_anonymous_user()
    {
        Surveys::factory()->create([
            'privacy' => 'public',
            'closed' => false,
        ]);
        Surveys::factory()->create([
            'privacy' => 'private',
            'closed' => false,
        ]);

        $response = $this->getJson('/api/surveys/latest');

        $response->assertStatus(200);
        $this->assertEquals('public', $response->json()['privacy']);
    }

    public function test_create_rejects_invalid_type()
    {
        $user = Users::factory()->create();

        $response = $this->actingAsUser($user)
            ->postJson('/api/surveys/create', [
                'title' => 'Test Survey',
                'type' => 'invalid_type',
            ]);

        $response->assertStatus(422);
    }

    public function test_create_rejects_title_exceeding_255_chars()
    {
        $user = Users::factory()->create();

        $response = $this->actingAsUser($user)
            ->postJson('/api/surveys/create', [
                'title' => str_repeat('a', 256),
                'type' => 'simple',
            ]);

        $response->assertStatus(422);
    }

    public function test_create_rejects_end_date_before_start_date()
    {
        $user = Users::factory()->create();

        $response = $this->actingAsUser($user)
            ->postJson('/api/surveys/create', [
                'title' => 'Test Survey',
                'type' => 'simple',
                'startDate' => '2026-12-31',
                'endDate' => '2026-01-01',
            ]);

        $response->assertStatus(422);
    }

    public function test_update_rejects_invalid_type()
    {
        $user = Users::factory()->create();

        $survey = Surveys::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAsUser($user)
            ->putJson("/api/surveys/update/{$survey->id}", [
                'title' => 'Updated',
                'type' => 'invalid',
            ]);

        $response->assertStatus(422);
    }

    public function test_update_rejects_title_exceeding_255_chars()
    {
        $user = Users::factory()->create();

        $survey = Surveys::factory()->create(['created_by' => $user->id]);

        $response = $this->actingAsUser($user)
            ->putJson("/api/surveys/update/{$survey->id}", [
                'title' => str_repeat('a', 256),
                'type' => 'simple',
            ]);

        $response->assertStatus(422);
    }

    public function test_update_preserves_privacy_and_users_when_not_provided()
    {
        $owner = Users::factory()->create();
        $user1 = Users::factory()->create();
        $user2 = Users::factory()->create();

        $survey = Surveys::factory()->create([
            'created_by' => $owner->id,
            'privacy' => 'private',
            'users' => [$owner->id, $user1->id, $user2->id],
        ]);

        $originalUsers = $survey->users;
        $originalPrivacy = $survey->privacy;

        $response = $this->actingAsUser($owner)
            ->putJson("/api/surveys/update/{$survey->id}", [
                'title' => 'Updated Title',
            ]);

        $response->assertStatus(200);

        $survey->refresh();
        $this->assertEquals('private', $survey->privacy);
        $this->assertEquals($originalPrivacy, $survey->privacy);
        $this->assertEquals($originalUsers, $survey->users);
    }

    public function test_create_requires_type_field()
    {
        $user = Users::factory()->create();

        $response = $this->actingAsUser($user)
            ->postJson('/api/surveys/create', [
                'title' => 'Test Survey',
            ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['errors' => ['type']]);
    }
}