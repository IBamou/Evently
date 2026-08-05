<?php

use App\Enums\UserRole;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => UserRole::Organizer]);
    config(['ai-event-copilot.enabled' => false]); // prevent real API calls
});

it('validates generate-draft with valid payload', function () {
    $response = $this->actingAs($this->user)->postJson(route('organizer.ai.event-drafts'), [
        'brief' => 'A music concert in Casablanca',
        'tone' => 'professional',
        'language' => 'en',
    ]);

    // Feature is disabled, so it returns 403 — not 422
    $response->assertStatus(403);
});

it('rejects brief exceeding max length', function () {
    $response = $this->actingAs($this->user)->postJson(route('organizer.ai.event-drafts'), [
        'brief' => str_repeat('a', 501),
        'tone' => 'professional',
        'language' => 'en',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('brief');
});

it('rejects invalid language', function () {
    $response = $this->actingAs($this->user)->postJson(route('organizer.ai.event-drafts'), [
        'brief' => 'A music concert',
        'tone' => 'professional',
        'language' => 'de',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('language');
});

it('rejects invalid tone', function () {
    $response = $this->actingAs($this->user)->postJson(route('organizer.ai.event-drafts'), [
        'brief' => 'A music concert',
        'tone' => 'casual',
        'language' => 'en',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('tone');
});

it('rejects transform without required fields', function () {
    $response = $this->actingAs($this->user)->postJson(route('organizer.ai.event-fields.transform'), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['field', 'operation', 'content']);
});

it('rejects transform with invalid field', function () {
    $response = $this->actingAs($this->user)->postJson(route('organizer.ai.event-fields.transform'), [
        'field' => 'invalid_field',
        'operation' => 'rewrite',
        'content' => 'Some content',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('field');
});

it('rejects transform with invalid operation', function () {
    $response = $this->actingAs($this->user)->postJson(route('organizer.ai.event-fields.transform'), [
        'field' => 'title',
        'operation' => 'invalid_op',
        'content' => 'Some content',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('operation');
});

it('requires target_language when operation is translate', function () {
    $response = $this->actingAs($this->user)->postJson(route('organizer.ai.event-fields.transform'), [
        'field' => 'title',
        'operation' => 'translate',
        'content' => 'Some content',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('target_language');
});

it('validates transform with valid payload', function () {
    $response = $this->actingAs($this->user)->postJson(route('organizer.ai.event-fields.transform'), [
        'field' => 'title',
        'operation' => 'rewrite',
        'content' => str_repeat('a', 5001),
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('content');
});

it('rejects marketing without event_context', function () {
    $response = $this->actingAs($this->user)->postJson(route('organizer.ai.event-marketing'), [
        'language' => 'en',
        'tone' => 'professional',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('event_context');
});

it('rejects marketing without title in event_context', function () {
    $response = $this->actingAs($this->user)->postJson(route('organizer.ai.event-marketing'), [
        'language' => 'en',
        'tone' => 'professional',
        'event_context' => [
            'description' => 'A music concert',
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('event_context.title');
});

it('validates marketing with valid payload', function () {
    $response = $this->actingAs($this->user)->postJson(route('organizer.ai.event-marketing'), [
        'language' => 'en',
        'tone' => 'professional',
        'event_context' => [
            'title' => 'Music Festival',
        ],
    ]);

    // Feature is disabled → 403, not 422
    $response->assertStatus(403);
});

it('rejects feedback with invalid action', function () {
    $response = $this->actingAs($this->user)->postJson(
        route('organizer.ai.generations.feedback', ['generation' => 'nonexistent']),
        ['action' => 'invalid_action'],
    );

    $response->assertStatus(422)
        ->assertJsonValidationErrors('action');
});

it('requires field when action is applied_field', function () {
    $response = $this->actingAs($this->user)->postJson(
        route('organizer.ai.generations.feedback', ['generation' => 'nonexistent']),
        ['action' => 'applied_field'],
    );

    $response->assertStatus(422)
        ->assertJsonValidationErrors('field');
});

it('rejects field with invalid value', function () {
    $response = $this->actingAs($this->user)->postJson(
        route('organizer.ai.generations.feedback', ['generation' => 'nonexistent']),
        ['action' => 'applied_field', 'field' => 'invalid_field'],
    );

    $response->assertStatus(422)
        ->assertJsonValidationErrors('field');
});
