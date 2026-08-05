<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Ai;
use Laravel\Ai\AiManager;
use Tests\TestCase as BaseTestCase;

uses(BaseTestCase::class, RefreshDatabase::class)->in('Feature');

afterEach(function () {
    // Clear agent fakes between tests to prevent leakage across test files
    try {
        /** @var AiManager $manager */
        $manager = Ai::getFacadeRoot();
        $reflection = new ReflectionClass($manager);

        $prop = $reflection->getProperty('fakeAgentGateways');
        $prop->setValue($manager, []);

        $prop = $reflection->getProperty('recordedPrompts');
        $prop->setValue($manager, []);

        $prop = $reflection->getProperty('recordedQueuedPrompts');
        $prop->setValue($manager, []);
    } catch (Throwable) {
        // Silently ignore if AiManager is not available
    }
});
