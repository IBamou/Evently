<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Event Copilot
    |--------------------------------------------------------------------------
    |
    | App-specific configuration for the AI Event Copilot feature.
    | Nested under its own key so it never clashes with the Laravel AI
    | package keys (default, providers, caching, conversations...).
    |
    */

    'event_copilot' => [
        'enabled' => env('AI_EVENT_COPILOT_ENABLED', false),

        'provider' => env('AI_EVENT_COPILOT_PROVIDER', 'openai'),

        'model' => env('AI_EVENT_COPILOT_MODEL', 'gpt-4o-mini'),

        'api_key' => env('AI_EVENT_COPILOT_API_KEY'),

        'fallback_provider' => env('AI_EVENT_COPILOT_FALLBACK_PROVIDER'),

        'fallback_model' => env('AI_EVENT_COPILOT_FALLBACK_MODEL'),

        'timeout' => (int) env('AI_EVENT_COPILOT_TIMEOUT', 30),

        'prompt_version' => env('AI_EVENT_COPILOT_PROMPT_VERSION', 'event-copilot-v1'),

        'limits' => [
            'brief_max' => 500,
            'audience_max' => 200,
            'field_content_max' => 5000,
            'event_context_max' => 2000,
        ],

        'languages' => ['en', 'fr', 'ar'],

        'tones' => ['professional', 'friendly', 'energetic', 'formal', 'concise'],

        'transform_operations' => ['rewrite', 'shorten', 'expand', 'translate'],

        'transform_fields' => ['title', 'description'],
    ],

];
