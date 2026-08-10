<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Event Copilot Feature Flag
    |--------------------------------------------------------------------------
    |
    | Master toggle for the AI Event Copilot feature.
    |
    */

    'enabled' => env('AI_EVENT_COPILOT_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Provider Configuration
    |--------------------------------------------------------------------------
    |
    | The AI provider used by the copilot. The API key is resolved from
    | AI_API_KEY, falling back to the provider's key in
    | config/ai.php (e.g. OPENAI_API_KEY).
    |
    */

    'provider' => env('AI_EVENT_COPILOT_PROVIDER', 'openai'),

    'model' => env('AI_EVENT_COPILOT_MODEL', 'gpt-4o-mini'),

    'api_key' => env('AI_EVENT_COPILOT_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Fallback Provider
    |--------------------------------------------------------------------------
    |
    | Used when the primary provider returns a transient failure (rate limit,
    | timeouts, 5xx). The fallback model may differ from the primary one.
    |
    */

    'fallback_provider' => env('AI_EVENT_COPILOT_FALLBACK_PROVIDER'),

    'fallback_model' => env('AI_EVENT_COPILOT_FALLBACK_MODEL'),

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | Maximum number of seconds to wait for an AI response.
    |
    */

    'timeout' => (int) env('AI_EVENT_COPILOT_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Prompt Version
    |--------------------------------------------------------------------------
    |
    | Version tag stamped on every generation record for tracking.
    |
    */

    'prompt_version' => env('AI_EVENT_COPILOT_PROMPT_VERSION', 'event-copilot-v1'),

    /*
    |--------------------------------------------------------------------------
    | Validation Limits
    |--------------------------------------------------------------------------
    |
    | Maximum lengths for request inputs.
    |
    */

    'limits' => [
        'brief_max' => 500,
        'audience_max' => 200,
        'field_content_max' => 5000,
        'event_context_max' => 2000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Values
    |--------------------------------------------------------------------------
    |
    | Enumerations for validation rules.
    |
    */

    'languages' => ['en', 'fr', 'ar'],

    'tones' => ['professional', 'friendly', 'energetic', 'formal', 'concise'],

    'transform_operations' => ['rewrite', 'shorten', 'expand', 'translate'],

    'transform_fields' => ['title', 'description'],

    'feedback_actions' => ['applied_field', 'applied_all', 'regenerated', 'dismissed'],

];
