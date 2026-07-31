<?php

namespace App\Http\Requests\Organizer;

use App\Enums\EventStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:100'],
            'status' => ['sometimes', Rule::enum(EventStatus::class)],
            'starts_from' => ['sometimes', 'date'],
            'starts_to' => ['sometimes', 'date', 'after_or_equal:starts_from'],
            'sort' => ['sometimes', 'in:starts_at,-starts_at,created_at,-created_at,title,-title'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'organizer_id' => ['sometimes', 'integer', 'exists:users,id'],
        ];
    }
}
