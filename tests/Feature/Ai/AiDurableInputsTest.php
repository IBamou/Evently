<?php

use App\Ai\Agents\GenerateEventDraftAgent;
use App\Enums\AiGenerationStatus;
use App\Enums\UserRole;
use App\Jobs\ProcessAiGenerationJob;
use App\Models\AiGeneration;
use App\Models\User;
use App\Services\Ai\AiGenerationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Cache::flush();
    $this->user = User::factory()->create(['role' => UserRole::Organizer]);
    config([
        'ai.enabled' => true,
        'ai.provider' => 'openai',
        'ai.model' => 'gpt-4o-mini',
    ]);
});

it('executes a queued generation from durable inputs when cache is flushed', function () {
    GenerateEventDraftAgent::fake([
        [
            'title' => 'Durable Marker Event',
            'description' => 'Generated from persisted inputs.',
            'category_id' => null,
            'marketing' => ['social_post' => 'x', 'email_subject' => 'x', 'email_intro' => 'x'],
            'missing_information' => [],
        ],
    ]);

    Queue::fake();

    $this->actingAs($this->user)->postJson(route('organizer.ai.event-drafts'), [
        'brief' => 'A durable marker concert',
        'tone' => 'professional',
        'language' => 'en',
    ])->assertStatus(202);

    Queue::assertPushed(ProcessAiGenerationJob::class, function ($job) {
        // The job was captured before execution; the generation row exists.
        return $job->generation->user_id === $this->user->id;
    });

    $generation = AiGeneration::where('user_id', $this->user->id)->firstOrFail();

    // The execution source of truth must not be the cache.
    expect($generation->input_payload)->toMatchArray(['brief' => 'A durable marker concert']);
    Cache::flush();

    $job = new ProcessAiGenerationJob($generation);
    $job->handle(app(AiGenerationService::class));

    $generation->refresh();
    expect($generation->status)->toBe(AiGenerationStatus::SUCCESS)
        ->and($generation->result['title'])->toBe('Durable Marker Event');
});

it('backfills durable inputs from the legacy cache entry', function () {
    GenerateEventDraftAgent::fake([
        [
            'title' => 'Backfilled Event',
            'description' => 'Generated.',
            'category_id' => null,
            'marketing' => ['social_post' => 'x', 'email_subject' => 'x', 'email_intro' => 'x'],
            'missing_information' => [],
        ],
    ]);

    // Simulate a row created before the input_payload column existed.
    $generation = AiGeneration::factory()->processing()->create([
        'user_id' => $this->user->id,
        'operation' => 'generate_draft',
        'language' => 'en',
        'input_payload' => null,
    ]);

    // Legacy input path: cache only.
    Cache::put("ai_copilot:inputs:{$generation->public_id}", [
        'brief' => 'Legacy cache brief',
        'tone' => 'professional',
        'language' => 'en',
    ]);

    app(AiGenerationService::class)->execute($generation);

    $generation->refresh();
    expect($generation->status)->toBe(AiGenerationStatus::SUCCESS)
        ->and($generation->input_payload)->toMatchArray(['brief' => 'Legacy cache brief']);
});
