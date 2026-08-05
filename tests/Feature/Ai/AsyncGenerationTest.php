<?php

use App\Ai\Agents\GenerateEventDraftAgent;
use App\Enums\AiGenerationStatus;
use App\Enums\UserRole;
use App\Jobs\ProcessAiGenerationJob;
use App\Models\AiGeneration;
use App\Models\User;
use App\Services\Ai\AiGenerationRecorder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Cache::flush();
    $this->user = User::factory()->create(['role' => UserRole::Organizer]);
    config([
        'ai-event-copilot.enabled' => true,
        'ai-event-copilot.provider' => 'openai',
        'ai-event-copilot.model' => 'gpt-4o-mini',
    ]);
});

it('dispatches ProcessAiGenerationJob and returns 202', function () {
    Queue::fake();

    $response = $this->actingAs($this->user)->postJson(route('organizer.ai.event-drafts'), [
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

    Queue::assertPushed(ProcessAiGenerationJob::class, function ($job) {
        return $job->generation->user_id === $this->user->id;
    });
});

it('dispatches job for transform field endpoint', function () {
    Queue::fake();

    $response = $this->actingAs($this->user)->postJson(route('organizer.ai.event-fields.transform'), [
        'field' => 'title',
        'operation' => 'rewrite',
        'content' => 'A great concert',
    ]);

    $response->assertStatus(202)
        ->assertJsonPath('data.status', 'processing');

    Queue::assertPushed(ProcessAiGenerationJob::class);
});

it('dispatches job for marketing endpoint', function () {
    Queue::fake();

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

    Queue::assertPushed(ProcessAiGenerationJob::class);
});

it('returns processing status immediately via polling', function () {
    // Without agent fake, the job would fail. But we just want to test the POST response.
    Queue::fake();

    $response = $this->actingAs($this->user)->postJson(route('organizer.ai.event-drafts'), [
        'brief' => 'A test event',
        'tone' => 'professional',
        'language' => 'en',
    ]);

    $generationId = $response->json('data.generation_id');
    expect($generationId)->not->toBeNull();

    $generation = AiGeneration::where('public_id', $generationId)->first();
    expect($generation)->not->toBeNull()
        ->and($generation->status)->toBe(AiGenerationStatus::PROCESSING);
});

it('polls status for completed generation', function () {
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

    $postResponse = $this->actingAs($this->user)->postJson(route('organizer.ai.event-drafts'), [
        'brief' => 'A test event',
        'tone' => 'professional',
        'language' => 'en',
    ]);

    $postResponse->assertStatus(202);
    $generationId = $postResponse->json('data.generation_id');

    // Job ran inline (sync queue), so generation should be completed
    $statusResponse = $this->actingAs($this->user)->getJson(
        route('organizer.ai.generations.status', ['generation' => $generationId]),
    );

    $statusResponse->assertOk()
        ->assertJsonPath('data.status', 'success')
        ->assertJsonPath('data.result.title', 'Test Event')
        ->assertJsonStructure([
            'data' => [
                'generation_id',
                'status',
                'result',
                'error_code',
                'error_message',
                'operation',
                'provider_used',
                'model_used',
                'latency_ms',
            ],
        ]);
});

it('polls status for failed generation with error_code', function () {
    GenerateEventDraftAgent::fake(fn () => throw new RuntimeException('AI service is down'));

    $postResponse = $this->actingAs($this->user)->postJson(route('organizer.ai.event-drafts'), [
        'brief' => 'A test event',
        'tone' => 'professional',
        'language' => 'en',
    ]);

    $postResponse->assertStatus(202);
    $generationId = $postResponse->json('data.generation_id');

    $statusResponse = $this->actingAs($this->user)->getJson(
        route('organizer.ai.generations.status', ['generation' => $generationId]),
    );

    $statusResponse->assertOk()
        ->assertJsonPath('data.status', 'error')
        ->assertJsonPath('data.error_code', 'ai_provider_unavailable')
        ->assertJsonPath('data.error_message', 'The AI service is temporarily unavailable. Please try again later.')
        ->assertJsonPath('data.result', null);
});

it('uses fallback on transient failure', function () {
    $callCount = 0;

    // Primary throws transient (connection error)
    GenerateEventDraftAgent::fake(function () use (&$callCount) {
        $callCount++;

        if ($callCount === 1) {
            throw new ConnectionException('Connection timed out');
        }

        return [
            'title' => 'Fallback Event',
            'description' => 'Generated by fallback.',
            'category_id' => null,
            'marketing' => [
                'social_post' => 'Fallback post',
                'email_subject' => 'Fallback subject',
                'email_intro' => 'Fallback intro',
            ],
            'missing_information' => [],
        ];
    });

    config([
        'ai-event-copilot.fallback_provider' => 'groq',
        'ai-event-copilot.fallback_model' => 'openai/gpt-oss-20b',
    ]);

    $postResponse = $this->actingAs($this->user)->postJson(route('organizer.ai.event-drafts'), [
        'brief' => 'A test event',
        'tone' => 'professional',
        'language' => 'en',
    ]);

    $postResponse->assertStatus(202);
    $generationId = $postResponse->json('data.generation_id');

    $statusResponse = $this->actingAs($this->user)->getJson(
        route('organizer.ai.generations.status', ['generation' => $generationId]),
    );

    $statusResponse->assertOk()
        ->assertJsonPath('data.status', 'success')
        ->assertJsonPath('data.result.title', 'Fallback Event')
        ->assertJsonPath('data.provider_used', 'groq')
        ->assertJsonPath('data.model_used', 'openai/gpt-oss-20b');

    // Verify the generation record also has fallback info
    $generation = AiGeneration::where('public_id', $generationId)->first();
    expect($generation->provider_used)->toBe('groq')
        ->and($generation->model_used)->toBe('openai/gpt-oss-20b');
});

it('returns 404 for status of nonexistent generation', function () {
    $response = $this->actingAs($this->user)->getJson(
        route('organizer.ai.generations.status', ['generation' => 'nonexistent']),
    );

    $response->assertStatus(404);
});

it('returns 404 for status of another users generation', function () {
    GenerateEventDraftAgent::fake([
        [
            'title' => 'Test',
            'description' => 'Test.',
            'category_id' => null,
            'marketing' => ['social_post' => 'x', 'email_subject' => 'x', 'email_intro' => 'x'],
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

    $response = $this->actingAs($otherUser)->getJson(
        route('organizer.ai.generations.status', ['generation' => $generation->public_id]),
    );

    $response->assertStatus(404)
        ->assertJson(['message' => 'Generation not found.']);
});

it('returns 403 for status when copilot is disabled', function () {
    config(['ai-event-copilot.enabled' => false]);

    $generation = AiGeneration::factory()->create([
        'user_id' => $this->user->id,
        'status' => AiGenerationStatus::SUCCESS,
    ]);

    $response = $this->actingAs($this->user)->getJson(
        route('organizer.ai.generations.status', ['generation' => $generation->public_id]),
    );

    $response->assertStatus(403)
        ->assertJson([
            'error_code' => 'ai_feature_disabled',
        ]);
});

it('returns 401 for unauthenticated status poll', function () {
    $generation = AiGeneration::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->getJson(
        route('organizer.ai.generations.status', ['generation' => $generation->public_id]),
    );

    $response->assertStatus(401);
});

it('does not dispatch job when copilot is disabled', function () {
    config(['ai-event-copilot.enabled' => false]);

    Queue::fake();

    $response = $this->actingAs($this->user)->postJson(route('organizer.ai.event-drafts'), [
        'brief' => 'A music concert',
        'tone' => 'professional',
        'language' => 'en',
    ]);

    $response->assertStatus(403);
    Queue::assertNotPushed(ProcessAiGenerationJob::class);
});

it('does not dispatch job when rate limited', function () {
    config(['ai-event-copilot.enabled' => true]);

    $recorder = app(AiGenerationRecorder::class);
    for ($i = 0; $i < 5; $i++) {
        $recorder->incrementMinuteCount($this->user->id);
    }

    Queue::fake();

    $response = $this->actingAs($this->user)->postJson(route('organizer.ai.event-drafts'), [
        'brief' => 'A music concert',
        'tone' => 'professional',
        'language' => 'en',
    ]);

    $response->assertStatus(429);
    Queue::assertNotPushed(ProcessAiGenerationJob::class);
});

it('stores result data on generation for polling', function () {
    GenerateEventDraftAgent::fake([
        [
            'title' => 'Stored Event',
            'description' => 'Stored description.',
            'category_id' => null,
            'marketing' => [
                'social_post' => 'Stored post',
                'email_subject' => 'Stored subject',
                'email_intro' => 'Stored intro',
            ],
            'missing_information' => ['missing item'],
        ],
    ]);

    $postResponse = $this->actingAs($this->user)->postJson(route('organizer.ai.event-drafts'), [
        'brief' => 'A stored test',
        'tone' => 'friendly',
        'language' => 'fr',
    ]);

    $postResponse->assertStatus(202);
    $generationId = $postResponse->json('data.generation_id');

    $statusResponse = $this->actingAs($this->user)->getJson(
        route('organizer.ai.generations.status', ['generation' => $generationId]),
    );

    $statusResponse->assertOk()
        ->assertJsonPath('data.status', 'success')
        ->assertJsonPath('data.result.title', 'Stored Event')
        ->assertJsonPath('data.result.description', 'Stored description.')
        ->assertJsonPath('data.result.missing_information.0', 'missing item')
        ->assertJsonPath('data.operation', 'generate_draft');
});
