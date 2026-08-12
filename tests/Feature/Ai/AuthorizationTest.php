<?php

use App\Ai\Agents\EventDraftAgent;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

it('returns 401 for unauthenticated guest', function () {
    $response = $this->postJson(route('organizer.ai.event-drafts'), [
        'brief' => 'A music concert',
        'tone' => 'professional',
        'language' => 'en',
    ]);

    $response->assertStatus(401);
});

it('returns 403 for regular user', function () {
    $user = User::factory()->create(['role' => UserRole::User]);

    $response = $this->actingAs($user)->postJson(route('organizer.ai.event-drafts'), [
        'brief' => 'A music concert',
        'tone' => 'professional',
        'language' => 'en',
    ]);

    $response->assertStatus(403);
});

it('allows organizer to access AI endpoints', function () {
    $user = User::factory()->create(['role' => UserRole::Organizer]);
    config(['ai.event_copilot.enabled' => true]);

    EventDraftAgent::fake([
        [
            'title' => 'Test Event',
            'description' => 'Test description',
            'category_id' => null,
            'marketing' => [
                'social_post' => 'Test post',
                'email_subject' => 'Test subject',
                'email_intro' => 'Test intro',
            ],
            'missing_information' => [],
        ],
    ]);

    $response = $this->actingAs($user)->postJson(route('organizer.ai.event-drafts'), [
        'brief' => 'A music concert',
        'tone' => 'professional',
        'language' => 'en',
    ]);

    $response->assertStatus(202)
        ->assertJsonStructure([
            'data' => [
                'generation_id',
                'status',
            ],
        ])
        ->assertJsonPath('data.status', 'processing');
});

it('allows an organizer to open the AI event workspace', function () {
    $user = User::factory()->create(['role' => UserRole::Organizer]);
    config(['ai.event_copilot.enabled' => true]);

    $this->actingAs($user)
        ->get(route('organizer.ai.workspace'))
        ->assertOk()
        ->assertSee('AI Event Copilot')
        ->assertSee('Draft setup')
        ->assertSee('Your draft will appear here');
});

it('returns 403 ai_feature_disabled when copilot is disabled', function () {
    $user = User::factory()->create(['role' => UserRole::Organizer]);
    config(['ai.event_copilot.enabled' => false]);

    $response = $this->actingAs($user)->postJson(route('organizer.ai.event-drafts'), [
        'brief' => 'A music concert',
        'tone' => 'professional',
        'language' => 'en',
    ]);

    $response->assertStatus(403)
        ->assertJson([
            'error_code' => 'ai_feature_disabled',
        ]);
});
