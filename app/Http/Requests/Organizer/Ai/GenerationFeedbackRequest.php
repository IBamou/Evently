<?php

namespace App\Http\Requests\Organizer\Ai;

use Illuminate\Foundation\Http\FormRequest;

class GenerationFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array> */
    public function rules(): array
    {
        $config = config('ai');

        return [
            'action' => ['required', 'string', 'in:'.implode(',', $config['feedback_actions'])],
            'field' => ['nullable', 'string', 'in:title,description', 'required_if:action,applied_field'],
        ];
    }
}
