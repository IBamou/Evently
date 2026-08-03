<?php

namespace App\Http\Requests\Booking;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $routeEvent = $this->route('event');
        $eventId = $routeEvent instanceof Event ? $routeEvent->id : $this->input('event_id');

        return [
            'name' => ['required', 'string', 'max:255', "unique:ticket_types,name,NULL,id,event_id,{$eventId}"],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0'],
            'quantity' => ['required', 'integer', 'min:1'],
            'min_per_booking' => ['required', 'integer', 'min:1'],
            'max_per_booking' => [
                'required',
                'integer',
                'min:1',
                'lte:quantity',
            ],
            'sales_start_at' => ['nullable', 'date'],
            'sales_end_at' => [
                'nullable',
                'date',
                'after:sales_start_at',
                function ($attribute, $value, $fail) use ($routeEvent) {
                    if ($routeEvent instanceof Event && $value && $routeEvent->starts_at && Carbon::parse($value)->gte($routeEvent->starts_at)) {
                        $fail('Sales end date must be before the event start date.');
                    }
                },
            ],
        ];
    }
}
