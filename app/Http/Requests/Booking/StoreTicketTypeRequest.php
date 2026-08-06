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
            'min_per_booking' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail): void {
                    $maxPerBooking = $this->input('max_per_booking', $this->input('quantity'));
                    if ($value > $maxPerBooking) {
                        $fail('Min per booking must be less than or equal to max per booking.');
                    }
                    $quantity = $this->input('quantity');
                    if ($quantity !== null && $value > $quantity) {
                        $fail('Min per booking must be less than or equal to quantity.');
                    }
                },
            ],
            'max_per_booking' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail): void {
                    $minPerBooking = $this->input('min_per_booking');
                    if ($minPerBooking !== null && $value < $minPerBooking) {
                        $fail('Max per booking must be greater than or equal to min per booking.');
                    }
                    $quantity = $this->input('quantity');
                    if ($quantity !== null && $value > $quantity) {
                        $fail('Max per booking must be less than or equal to quantity.');
                    }
                },
            ],
            'sales_start_at' => ['nullable', 'date'],
            'sales_end_at' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) use ($routeEvent): void {
                    $salesStartAt = $this->input('sales_start_at');
                    if ($value && $salesStartAt && Carbon::parse($value)->lte(Carbon::parse($salesStartAt))) {
                        $fail('Sales end date must be after sales start date.');
                    }
                    if ($routeEvent instanceof Event && $value && $routeEvent->starts_at && Carbon::parse($value)->gte($routeEvent->starts_at)) {
                        $fail('Sales end date must be before the event start date.');
                    }
                },
            ],
        ];
    }
}
