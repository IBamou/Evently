<?php

use App\Enums\UserRole;
use App\Models\AiGeneration;
use App\Models\User;
use App\Services\Ai\AiGenerationRecorder;
use Illuminate\Support\Facades\Cache;
use Laravel\Ai\Ai;
use Laravel\Ai\AiManager;

beforeEach(function () {
    Cache::flush();
    $this->user = User::factory()->create(['role' => UserRole::Organizer]);
    config(['ai-event-copilot.enabled' => true]);

    // Ensure no agent fakes persist from other test files
    $manager = Ai::getFacadeRoot();
    if ($manager instanceof AiManager) {
        $r = new ReflectionClass($manager);
        $r->getProperty('fakeAgentGateways')->setValue($manager, []);
        $r->getProperty('recordedPrompts')->setValue($manager, []);
        $r->getProperty('recordedQueuedPrompts')->setValue($manager, []);
    }
});

it('returns 429 ai_rate_limited when per-minute limit exceeded', function () {
    $recorder = app(AiGenerationRecorder::class);

    for ($i = 0; $i < 5; $i++) {
        $recorder->incrementMinuteCount($this->user->id);
    }

    $response = $this->actingAs($this->user)->postJson(route('organizer.ai.event-drafts'), [
        'brief' => 'A music concert',
        'tone' => 'professional',
        'language' => 'en',
    ]);

    $response->assertStatus(429)
        ->assertJson([
            'error_code' => 'ai_rate_limited',
        ]);
});

it('returns 429 ai_daily_limit_reached when daily limit exceeded', function () {
    $recorder = app(AiGenerationRecorder::class);

    for ($i = 0; $i < 50; $i++) {
        $recorder->incrementDailyCount($this->user->id);
    }

    $response = $this->actingAs($this->user)->postJson(route('organizer.ai.event-drafts'), [
        'brief' => 'A music concert',
        'tone' => 'professional',
        'language' => 'en',
    ]);

    $response->assertStatus(429)
        ->assertJson([
            'error_code' => 'ai_daily_limit_reached',
        ]);
});

it('allows request when under both limits and returns 202', function () {
    $response = $this->actingAs($this->user)->postJson(route('organizer.ai.event-drafts'), [
        'brief' => 'A music concert',
        'tone' => 'professional',
        'language' => 'en',
    ]);

    // Async: always 202 regardless of job outcome
    $response->assertStatus(202)
        ->assertJson([
            'data' => [
                'status' => 'processing',
            ],
        ]);

    // Generation was created and job ran inline (sync queue)
    $generation = AiGeneration::where('user_id', $this->user->id)->latest()->first();
    expect($generation)->not->toBeNull()
        ->and($generation->status->value)->toBeIn(['success', 'error'])
        ->and($generation->public_id)->not->toBeEmpty();
});
