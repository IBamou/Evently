<?php

use App\Models\NewsletterSubscription;

it('subscribes with a valid email and redirects back with success', function () {
    $response = $this->post(route('newsletter.store'), [
        'email' => 'subscriber@example.com',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success', 'You are subscribed!');
    $this->assertDatabaseHas('newsletter_subscriptions', [
        'email' => 'subscriber@example.com',
    ]);
});

it('rejects duplicate email with validation error', function () {
    NewsletterSubscription::create(['email' => 'taken@example.com']);

    $response = $this->post(route('newsletter.store'), [
        'email' => 'taken@example.com',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertDatabaseCount('newsletter_subscriptions', 1);
});

it('rejects invalid email', function () {
    $response = $this->post(route('newsletter.store'), [
        'email' => 'not-an-email',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertDatabaseCount('newsletter_subscriptions', 0);
});

it('rejects missing email', function () {
    $response = $this->post(route('newsletter.store'), []);

    $response->assertSessionHasErrors('email');
});

it('rejects empty email', function () {
    $response = $this->post(route('newsletter.store'), [
        'email' => '',
    ]);

    $response->assertSessionHasErrors('email');
});
