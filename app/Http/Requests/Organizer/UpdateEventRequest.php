<?php

namespace App\Http\Requests\Organizer;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateEventRequest extends FormRequest
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
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'min:20'],
            'location' => ['sometimes', 'required', 'string', 'max:255'],
            'city' => ['sometimes', 'required', 'string', 'max:100'],
            'format' => ['sometimes', 'required', 'in:in_person,online'],
            'starts_at' => ['sometimes', 'required', 'date'],
            'ends_at' => ['sometimes', 'required', 'date'],
            'banner_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'organizer_id' => ['prohibited'],
            'status' => ['prohibited'],
            'deleted_at' => ['prohibited'],
        ];
    }

    /**
     * Handle the post-validation processing.
     */
    protected function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator): void {
            /** @var Event|null $event */
            $event = $this->route('event');

            if (! $event instanceof Event) {
                return;
            }

            $startsAt = $this->input('starts_at') ? Carbon::parse($this->input('starts_at')) : $event->starts_at;
            $endsAt = $this->input('ends_at') ? Carbon::parse($this->input('ends_at')) : $event->ends_at;

            if ($startsAt instanceof Carbon && $endsAt instanceof Carbon && $endsAt->lte($startsAt)) {
                $validator->errors()->add('ends_at', 'The end time must be after the start time.');
            }
        });
    }
}
