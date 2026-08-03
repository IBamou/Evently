<?php

namespace App\Http\Requests\Booking;

use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTicketTypeRequest extends FormRequest
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
        /** @var TicketType $ticketType */
        $ticketType = $this->route('ticketType');

        $routeEvent = $this->route('event');
        $eventId = $routeEvent instanceof Event ? $routeEvent->id : $ticketType->event_id;

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('ticket_types', 'name')
                ->ignore($ticketType->id)
                ->where('event_id', $eventId)],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'price' => [
                'sometimes',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($ticketType) {
                    if ($ticketType->bookingItems()->exists() && $value != $ticketType->price) {
                        $fail('Cannot change price when booking items exist.');
                    }
                },
            ],
            'quantity' => [
                'sometimes',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) use ($ticketType) {
                    $allocated = $ticketType->allocatedQuantity();
                    if ($value < $allocated) {
                        $fail("Cannot reduce quantity below allocated count ({$allocated}).");
                    }
                },
            ],
            'min_per_booking' => ['sometimes', 'integer', 'min:1'],
            'max_per_booking' => ['sometimes', 'integer', 'min:1', 'lte:quantity'],
            'sales_start_at' => ['sometimes', 'nullable', 'date'],
            'sales_end_at' => ['sometimes', 'nullable', 'date', 'after:sales_start_at'],
        ];
    }
}
