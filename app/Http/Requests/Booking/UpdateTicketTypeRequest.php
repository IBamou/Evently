<?php

namespace App\Http\Requests\Booking;

use App\Models\Event;
use App\Models\TicketType;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTicketTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        /** @var TicketType $ticketType */
        $ticketType = $this->route('ticketType');

        $routeEvent = $this->route('event');
        /** @var Event $routeEvent */
        $routeEvent = $routeEvent instanceof Event ? $routeEvent : $ticketType->event;
        $eventId = $routeEvent->id;

        // Effective values: submitted value if present, otherwise the existing model value.
        $effectiveQuantity = (int) ($this->input('quantity') ?? $ticketType->quantity);
        $effectiveMin = (int) ($this->input('min_per_booking') ?? $ticketType->min_per_booking);
        $effectiveMax = (int) ($this->input('max_per_booking') ?? $ticketType->max_per_booking);
        $effectiveSalesStart = $this->input('sales_start_at') !== null
            ? Carbon::parse($this->input('sales_start_at'))
            : $ticketType->sales_start_at;
        $effectiveSalesEnd = $this->input('sales_end_at') !== null
            ? Carbon::parse($this->input('sales_end_at'))
            : $ticketType->sales_end_at;

        return [
            'name' => ['sometimes', 'string', 'max:255', Rule::unique('ticket_types', 'name')
                ->ignore($ticketType->id)
                ->where('event_id', $eventId)],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'price' => [
                'sometimes',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) use ($ticketType): void {
                    if ($ticketType->bookingItems()->exists() && $value != $ticketType->price) {
                        $fail('Cannot change price when booking items exist.');
                    }
                },
            ],
            'quantity' => [
                'sometimes',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) use ($ticketType): void {
                    $allocated = $ticketType->allocatedQuantity();
                    if ($value < $allocated) {
                        $fail("Cannot reduce quantity below allocated count ({$allocated}).");
                    }
                },
            ],
            'min_per_booking' => ['sometimes', 'integer', 'min:1'],
            'max_per_booking' => ['sometimes', 'integer', 'min:1'],
            'sales_start_at' => ['sometimes', 'nullable', 'date'],
            'sales_end_at' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * Handle the post-validation processing.
     *
     * Uses effective-state closures so partial updates are validated against
     * the resulting combined state (existing + submitted).
     */
    protected function withValidator(Validator $validator): void
    {
        /** @var TicketType $ticketType */
        $ticketType = $this->route('ticketType');

        // Route-model-bound event when present (nested routes), otherwise the
        // ticket type's own event (the instanceof narrows the mixed param).
        $routeEvent = $this->route('event');
        $routeEvent = $routeEvent instanceof Event ? $routeEvent : $ticketType->event;
        $eventStartsAt = $routeEvent?->starts_at;

        $effectiveQuantity = (int) ($this->input('quantity') ?? $ticketType->quantity);
        $effectiveMin = (int) ($this->input('min_per_booking') ?? $ticketType->min_per_booking);
        $effectiveMax = (int) ($this->input('max_per_booking') ?? $ticketType->max_per_booking);
        $effectiveSalesStart = $this->input('sales_start_at') !== null
            ? Carbon::parse($this->input('sales_start_at'))
            : $ticketType->sales_start_at;
        $effectiveSalesEnd = $this->input('sales_end_at') !== null
            ? Carbon::parse($this->input('sales_end_at'))
            : $ticketType->sales_end_at;

        $validator->after(function ($validator) use ($effectiveMin, $effectiveMax, $effectiveQuantity, $effectiveSalesStart, $effectiveSalesEnd, $eventStartsAt): void {
            // Effective min <= effective max.
            if ($effectiveMin > $effectiveMax) {
                $validator->errors()->add(
                    'min_per_booking',
                    'Min per booking must be less than or equal to max per booking.'
                );
            }

            // Effective max <= effective quantity.
            if ($effectiveMax > $effectiveQuantity) {
                $validator->errors()->add(
                    'max_per_booking',
                    'Max per booking must be less than or equal to quantity.'
                );
            }

            // Effective min <= effective quantity.
            if ($effectiveMin > $effectiveQuantity) {
                $validator->errors()->add(
                    'min_per_booking',
                    'Min per booking must be less than or equal to quantity.'
                );
            }

            // Sales end must be after sales start (effective state).
            if ($effectiveSalesStart !== null && $effectiveSalesEnd !== null && $effectiveSalesEnd->lte($effectiveSalesStart)) {
                $validator->errors()->add(
                    'sales_end_at',
                    'Sales end date must be after sales start date.'
                );
            }

            // Sales end must be before event start.
            if ($effectiveSalesEnd !== null && $eventStartsAt !== null && $effectiveSalesEnd->gte($eventStartsAt)) {
                $validator->errors()->add(
                    'sales_end_at',
                    'Sales end date must be before the event start date.'
                );
            }
        });
    }
}
