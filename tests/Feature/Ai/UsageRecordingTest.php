<?php

use App\Ai\Agents\GenerateEventDraftAgent;
use App\Enums\AiGenerationStatus;
use App\Enums\UserRole;
use App\Models\AiGeneration;
use App\Models\AiGenerationFeedback;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
    $this->user = User::factory()->create(['role' => UserRole::Organizer]);
    config([
        'ai-event-copilot.enabled' => true,
        'ai-event-copilot.provider' => 'openai',
        'ai-event-copilot.model' => 'gpt-4o-mini',
    ]);
});

it('creates a generation record on success', function () {
    GenerateEventDraftAgent::fake([
        [
            'title' => 'Test Event',
            'description' => 'Test description.',
            'category_id' => null,
            'marketing' => [
                'social_post' => 'Test post',
                'email_subject' => 'Test subject',
                'email_intro' => 'Test intro',
            ],
            'missing_information' => [],
        ],
    ]);

    $this->actingAs($this->user)->postJson(route('organizer.ai.event-drafts'), [
        'brief' => 'A test event',
        'tone' => 'professional',
        'language' => 'en',
    ]);

    $generation = AiGeneration::where('user_id', $this->user->id)->first();

    expect($generation)->not->toBeNull()
        ->and($generation->feature)->toBe('event_copilot')
        ->and($generation->operation)->toBe('generate_draft')
        ->and($generation->provider)->toBe('openai')
        ->and($generation->model)->toBe('gpt-4o-mini')
        ->and($generation->status)->toBe(AiGenerationStatus::SUCCESS)
        ->and($generation->provider_used)->toBe('openai')
        ->and($generation->model_used)->toBe('gpt-4o-mini')
        ->and($generation->result)->not->toBeNull()
        ->and($generation->language)->toBe('en')
        ->and($generation->prompt_version)->toBe('event-copilot-v1')
        ->and($generation->public_id)->not->toBeEmpty();

    // No duplicate usage-ledger rows: exactly one generation record per request.
    expect(AiGeneration::where('user_id', $this->user->id)->count())->toBe(1);
});

it('records error_code on failure', function () {
    GenerateEventDraftAgent::fake(fn () => throw new RuntimeException('AI provider is unavailable'));

    $this->actingAs($this->user)->postJson(route('organizer.ai.event-drafts'), [
        'brief' => 'A test event',
        'tone' => 'professional',
        'language' => 'en',
    ]);

    $generation = AiGeneration::where('user_id', $this->user->id)->first();

    expect($generation)->not->toBeNull()
        ->and($generation->status)->toBe(AiGenerationStatus::ERROR)
        ->and($generation->error_code)->not->toBeNull();
});

it('records feedback on valid generation', function () {
    GenerateEventDraftAgent::fake([
        [
            'title' => 'Test Event',
            'description' => 'Test description.',
            'category_id' => null,
            'marketing' => [
                'social_post' => 'Test post',
                'email_subject' => 'Test subject',
                'email_intro' => 'Test intro',
            ],
            'missing_information' => [],
        ],
    ]);

    $this->actingAs($this->user)->postJson(route('organizer.ai.event-drafts'), [
        'brief' => 'A test event',
        'tone' => 'professional',
        'language' => 'en',
    ]);

    $generation = AiGeneration::where('user_id', $this->user->id)->first();

    $response = $this->actingAs($this->user)->postJson(
        route('organizer.ai.generations.feedback', ['generation' => $generation->public_id]),
        ['action' => 'applied_all'],
    );

    $response->assertOk()
        ->assertJson(['message' => 'Feedback recorded.']);

    $feedback = AiGenerationFeedback::where('generation_id', $generation->id)->first();
    expect($feedback)->not->toBeNull()
        ->and($feedback->action)->toBe('applied_all');
});

it('returns 404 for feedback on nonexistent generation', function () {
    $response = $this->actingAs($this->user)->postJson(
        route('organizer.ai.generations.feedback', ['generation' => '01HZZZZZZZZZZZZZZZZZZZZZZZZZZ']),
        ['action' => 'applied_all'],
    );

    $response->assertStatus(404);
});

it('returns 403 for feedback on another user generation', function () {
    GenerateEventDraftAgent::fake([
        [
            'title' => 'Test Event',
            'description' => 'Test description.',
            'category_id' => null,
            'marketing' => [
                'social_post' => 'Test post',
                'email_subject' => 'Test subject',
                'email_intro' => 'Test intro',
            ],
            'missing_information' => [],
        ],
    ]);

    $this->actingAs($this->user)->postJson(route('organizer.ai.event-drafts'), [
        'brief' => 'A test event',
        'tone' => 'professional',
        'language' => 'en',
    ]);

    $generation = AiGeneration::where('user_id', $this->user->id)->first();

    $otherUser = User::factory()->create(['role' => UserRole::Organizer]);

    $response = $this->actingAs($otherUser)->postJson(
        route('organizer.ai.generations.feedback', ['generation' => $generation->public_id]),
        ['action' => 'applied_all'],
    );

    $response->assertStatus(403)
        ->assertJson(['message' => 'This action is unauthorized.']);
});
