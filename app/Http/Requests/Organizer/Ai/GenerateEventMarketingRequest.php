<?php

namespace App\Http\Requests\Organizer\Ai;

use Illuminate\Foundation\Http\FormRequest;

class GenerateEventMarketingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string|string[]|int|null>> */
    public function rules(): array
    {
        $config = config('ai-event-copilot');

        return [
            'language' => ['required', 'string', 'in:'.implode(',', $config['languages'])],
            'tone' => ['required', 'string', 'in:'.implode(',', $config['tones'])],
            'event_context' => ['required', 'array'],
            'event_context.title' => ['required', 'string', 'max:255'],
            'event_context.description' => ['nullable', 'string'],
            'event_context.city' => ['nullable', 'string', 'max:255'],
            'event_context.location' => ['nullable', 'string', 'max:255'],
            'event_context.starts_at' => ['nullable', 'date'],
        ];
    }
}
