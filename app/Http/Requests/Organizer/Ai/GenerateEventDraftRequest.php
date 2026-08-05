<?php

namespace App\Http\Requests\Organizer\Ai;

use Illuminate\Foundation\Http\FormRequest;

class GenerateEventDraftRequest extends FormRequest
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
            'brief' => ['required', 'string', 'max:'.$config['limits']['brief_max']],
            'audience' => ['nullable', 'string', 'max:'.$config['limits']['audience_max']],
            'tone' => ['required', 'string', 'in:'.implode(',', $config['tones'])],
            'language' => ['required', 'string', 'in:'.implode(',', $config['languages'])],
            'event_context' => ['nullable', 'array'],
            'event_context.title' => ['nullable', 'string', 'max:255'],
            'event_context.description' => ['nullable', 'string'],
            'event_context.city' => ['nullable', 'string', 'max:255'],
            'event_context.location' => ['nullable', 'string', 'max:255'],
            'event_context.starts_at' => ['nullable', 'date'],
            'event_context.ends_at' => ['nullable', 'date'],
        ];
    }
}
