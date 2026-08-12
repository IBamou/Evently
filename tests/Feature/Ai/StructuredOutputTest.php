<?php

use App\Ai\Agents\EventDraftAgent;
use App\Ai\Agents\EventPolishAgent;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
    $this->user = User::factory()->create(['role' => UserRole::Organizer]);
    config([
        'ai.event_copilot.enabled' => true,
        'ai.event_copilot.provider' => 'openai',
        'ai.event_copilot.model' => 'gpt-4o-mini',
    ]);
});

it('returns a validated draft with null category via polling', function () {
    EventDraftAgent::fake([
        [
            'title' => 'Music Festival 2026',
            'description' => 'An amazing music festival featuring top artists.',
            'category_id' => null,
            'marketing' => [
                'social_post' => 'Join us for an amazing music festival!',
                'email_subject' => 'You are invited!',
                'email_intro' => 'We are excited to announce our upcoming music festival.',
            ],
            'missing_information' => ['ticket prices', 'lineup details'],
        ],
    ]);

    $response = $this->actingAs($this->user)->postJson(route('organizer.ai.event-drafts'), [
        'brief' => 'A music festival in Casablanca',
        'tone' => 'energetic',
        'language' => 'en',
    ]);

    $response->assertStatus(202)
        ->assertJsonPath('data.status', 'processing');

    $generationId = $response->json('data.generation_id');

    $statusResponse = $this->actingAs($this->user)->getJson(
        route('organizer.ai.generations.status', ['generationId' => $generationId]),
    );

    $statusResponse->assertOk()
        ->assertJsonStructure([
            'data' => [
                'generation_id',
                'status',
                'result' => [
                    'title',
                    'description',
                    'category',
                    'marketing' => [
                        'social_post',
                        'email_subject',
                        'email_intro',
                    ],
                    'missing_information',
                ],
                'error_message',
            ],
        ])
        ->assertJsonPath('data.status', 'success')
        ->assertJsonPath('data.result.title', 'Music Festival 2026')
        ->assertJsonPath('data.result.category', null)
        ->assertJsonPath('data.result.missing_information', ['ticket prices', 'lineup details']);
});

it('returns a validated draft with category via polling', function () {
    $category = Category::create(['name' => 'Music', 'slug' => 'music']);

    EventDraftAgent::fake([
        [
            'title' => 'Music Festival 2026',
            'description' => 'An amazing music festival.',
            'category_id' => $category->id,
            'marketing' => [
                'social_post' => 'Join us!',
                'email_subject' => 'You are invited!',
                'email_intro' => 'Welcome to our festival.',
            ],
            'missing_information' => [],
        ],
    ]);

    $response = $this->actingAs($this->user)->postJson(route('organizer.ai.event-drafts'), [
        'brief' => 'A music festival',
        'tone' => 'professional',
        'language' => 'en',
    ]);

    $response->assertStatus(202);

    $generationId = $response->json('data.generation_id');

    $statusResponse = $this->actingAs($this->user)->getJson(
        route('organizer.ai.generations.status', ['generationId' => $generationId]),
    );

    $statusResponse->assertOk()
        ->assertJsonPath('data.status', 'success')
        ->assertJsonPath('data.result.category.id', $category->id)
        ->assertJsonPath('data.result.category.name', 'Music')
        ->assertJsonPath('data.result.category.slug', 'music');
});

it('nulls an unknown category id from the AI response via polling', function () {
    EventDraftAgent::fake([
        [
            'title' => 'Tech Conference',
            'description' => 'A tech conference.',
            'category_id' => 99999,
            'marketing' => [
                'social_post' => 'Join us!',
                'email_subject' => 'You are invited!',
                'email_intro' => 'Welcome.',
            ],
            'missing_information' => [],
        ],
    ]);

    $response = $this->actingAs($this->user)->postJson(route('organizer.ai.event-drafts'), [
        'brief' => 'A tech conference',
        'tone' => 'professional',
        'language' => 'en',
    ]);

    $response->assertStatus(202);

    $generationId = $response->json('data.generation_id');

    $statusResponse = $this->actingAs($this->user)->getJson(
        route('organizer.ai.generations.status', ['generationId' => $generationId]),
    );

    $statusResponse->assertOk()
        ->assertJsonPath('data.status', 'success')
        ->assertJsonPath('data.result.category', null);
});

it('returns a validated field transformation via polling', function () {
    EventPolishAgent::fake([
        [
            'content' => 'A wonderful music festival featuring world-class artists and amazing performances.',
            'language' => 'en',
            'warnings' => [],
        ],
    ]);

    $response = $this->actingAs($this->user)->postJson(route('organizer.ai.event-fields.transform'), [
        'field' => 'description',
        'operation' => 'expand',
        'content' => 'Music festival',
    ]);

    $response->assertStatus(202)
        ->assertJsonPath('data.status', 'processing');

    $generationId = $response->json('data.generation_id');

    $statusResponse = $this->actingAs($this->user)->getJson(
        route('organizer.ai.generations.status', ['generationId' => $generationId]),
    );

    $statusResponse->assertOk()
        ->assertJsonPath('data.status', 'success')
        ->assertJsonPath('data.result.content', 'A wonderful music festival featuring world-class artists and amazing performances.')
        ->assertJsonPath('data.result.language', 'en')
        ->assertJsonPath('data.result.warnings', []);
});

it('falls back to plain JSON when the response wraps output in markdown fences', function () {
    // The fake serializes strings as plain text; simulate a fenced payload.
    EventDraftAgent::fake([
        "```json\n".json_encode([
            'title' => 'Fenced Event',
            'description' => 'A description.',
            'category_id' => null,
            'marketing' => [
                'social_post' => 'P',
                'email_subject' => 'S',
                'email_intro' => 'I',
            ],
            'missing_information' => [],
        ])."\n```",
    ]);

    $response = $this->actingAs($this->user)->postJson(route('organizer.ai.event-drafts'), [
        'brief' => 'A fenced event',
        'tone' => 'professional',
        'language' => 'en',
    ]);

    $response->assertStatus(202);

    $generationId = $response->json('data.generation_id');

    $this->actingAs($this->user)->getJson(
        route('organizer.ai.generations.status', ['generationId' => $generationId]),
    )
        ->assertOk()
        ->assertJsonPath('data.status', 'success')
        ->assertJsonPath('data.result.title', 'Fenced Event');
});
