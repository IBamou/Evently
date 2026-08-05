<?php

namespace App\Http\Requests\Organizer\Ai;

use Illuminate\Foundation\Http\FormRequest;

class GenerationFeedbackRequest extends FormRequest
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
            'action' => ['required', 'string', 'in:'.implode(',', $config['feedback_actions'])],
            'field' => ['nullable', 'string', 'in:title,description', 'required_if:action,applied_field'],
        ];
    }
}
