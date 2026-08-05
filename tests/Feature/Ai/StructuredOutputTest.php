<?php

use App\Ai\Agents\GenerateEventDraftAgent;
use App\Ai\Agents\GenerateEventMarketingAgent;
use App\Ai\Agents\TransformEventFieldAgent;
use App\Enums\UserRole;
use App\Models\Category;
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

it('returns valid draft output with null category via polling', function () {
    GenerateEventDraftAgent::fake([
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

    // Poll the status endpoint (job ran inline with sync queue)
    $statusResponse = $this->actingAs($this->user)->getJson(
        route('organizer.ai.generations.status', ['generation' => $generationId]),
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
                'error_code',
                'error_message',
            ],
        ])
        ->assertJsonPath('data.status', 'success')
        ->assertJsonPath('data.result.title', 'Music Festival 2026')
        ->assertJsonPath('data.result.category', null);
});

it('returns valid draft output with category via polling', function () {
    $category = Category::create(['name' => 'Music', 'slug' => 'music']);

    GenerateEventDraftAgent::fake([
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
        route('organizer.ai.generations.status', ['generation' => $generationId]),
    );

    $statusResponse->assertOk()
        ->assertJsonPath('data.status', 'success')
        ->assertJsonPath('data.result.category.id', $category->id)
        ->assertJsonPath('data.result.category.name', 'Music')
        ->assertJsonPath('data.result.category.slug', 'music');
});

it('nulls invalid category_id from AI response via polling', function () {
    GenerateEventDraftAgent::fake([
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
        route('organizer.ai.generations.status', ['generation' => $generationId]),
    );

    $statusResponse->assertOk()
        ->assertJsonPath('data.status', 'success')
        ->assertJsonPath('data.result.category', null);
});

it('returns valid marketing output via polling', function () {
    GenerateEventMarketingAgent::fake([
        [
            'social_post' => 'Come join us for a fantastic event!',
            'email_subject' => 'You are invited!',
            'email_intro' => 'We are excited to host this amazing event.',
        ],
    ]);

    $response = $this->actingAs($this->user)->postJson(route('organizer.ai.event-marketing'), [
        'language' => 'en',
        'tone' => 'professional',
        'event_context' => [
            'title' => 'Music Festival',
            'description' => 'A music festival',
        ],
    ]);

    $response->assertStatus(202)
        ->assertJsonPath('data.status', 'processing');

    $generationId = $response->json('data.generation_id');

    $statusResponse = $this->actingAs($this->user)->getJson(
        route('organizer.ai.generations.status', ['generation' => $generationId]),
    );

    $statusResponse->assertOk()
        ->assertJsonPath('data.status', 'success')
        ->assertJsonStructure([
            'data' => [
                'generation_id',
                'status',
                'result' => [
                    'social_post',
                    'email_subject',
                    'email_intro',
                ],
            ],
        ]);
});

it('returns valid transform output via polling', function () {
    TransformEventFieldAgent::fake([
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
        route('organizer.ai.generations.status', ['generation' => $generationId]),
    );

    $statusResponse->assertOk()
        ->assertJsonPath('data.status', 'success')
        ->assertJsonPath('data.result.content', 'A wonderful music festival featuring world-class artists and amazing performances.')
        ->assertJsonPath('data.result.language', 'en');
});
