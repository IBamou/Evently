<?php

namespace App\Http\Requests\Organizer\Ai;

use Illuminate\Foundation\Http\FormRequest;

class TransformEventFieldRequest extends FormRequest
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
            'field' => ['required', 'string', 'in:'.implode(',', $config['transform_fields'])],
            'operation' => ['required', 'string', 'in:'.implode(',', $config['transform_operations'])],
            'content' => ['required', 'string', 'max:'.$config['limits']['field_content_max']],
            'tone' => ['nullable', 'string', 'in:'.implode(',', $config['tones'])],
            'target_language' => ['required_if:operation,translate', 'nullable', 'string', 'in:'.implode(',', $config['languages'])],
            'event_context' => ['nullable', 'array'],
            'event_context.title' => ['nullable', 'string', 'max:255'],
            'event_context.city' => ['nullable', 'string', 'max:255'],
        ];
    }
}
