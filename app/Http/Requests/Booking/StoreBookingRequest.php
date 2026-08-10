<?php

namespace App\Http\Requests\Booking;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class StoreBookingRequest extends FormRequest
{
    protected float $computedTotal = 0;

    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Drop rows the checkout page submits with quantity 0 (UI always posts every
     * ticket type row; only rows with quantity > 0 count as a selection).
     * Also compute the booking total so we can conditionally require payment fields.
     */
    protected function prepareForValidation(): void
    {
        /** @var array $rawItems */
        $rawItems = $this->input('items', []);

        $items = collect($rawItems)
            ->filter(fn ($item) => (int) ($item['quantity'] ?? 0) > 0)
            ->values()
            ->all();

        $this->merge(['items' => $items]);

        // Compute total from server-side prices (same logic as BookingService).
        $eventId = $this->input('event_id');
        if ($eventId && count($items) > 0) {
            $ticketTypeIds = array_column($items, 'ticket_type_id');
            $prices = DB::table('ticket_types')
                ->whereIn('id', $ticketTypeIds)
                ->pluck('price', 'id');

            $total = 0.0;
            foreach ($items as $item) {
                $ttId = $item['ticket_type_id'];
                $total += (float) ($prices[$ttId] ?? 0) * (int) $item['quantity'];
            }

            $this->computedTotal = $total;
        }
    }

    public function rules(): array
    {
        $requiresPayment = $this->computedTotal > 0;

        return [
            'event_id' => ['required', 'exists:events,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.ticket_type_id' => ['required', 'exists:ticket_types,id', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],

            // Mock payment fields — only validated when the payment array is
            // submitted. When omitted, the booking stays pending (confirm later).
            // When provided for a paid event, all three fields are required and
            // must pass format/business-rule checks.
            'payment' => ['nullable', 'array'],
            'payment.card_number' => array_merge(
                $requiresPayment ? ['required_with:payment'] : ['nullable'],
                ['string', 'regex:/^[0-9 ]{12,19}$/'],
                [function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }
                    $digits = (string) preg_replace('/\s+/', '', (string) $value);
                    if (! str_starts_with($digits, '4') || strlen($digits) !== 16) {
                        $fail('The card number must be a valid Visa test card (start with 4, 16 digits).');
                    }
                }],
            ),
            'payment.expiry' => array_merge(
                $requiresPayment ? ['required_with:payment'] : ['nullable'],
                ['string', 'regex:/^(0[1-9]|1[0-2])\s?\/\s?\d{2}$/'],
                [function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }
                    if (preg_match('/^(0[1-9]|1[0-2])\s?\/\s?(\d{2})$/', (string) $value, $m)) {
                        $month = (int) $m[1];
                        $year = 2000 + (int) $m[2];
                        $expiry = Carbon::createFromDate($year, $month, 1)->endOfMonth();
                        if ($expiry->isPast()) {
                            $fail('The card expiry date must be in the future.');
                        }
                    }
                }],
            ),
            'payment.cvc' => array_merge(
                $requiresPayment ? ['required_with:payment'] : ['nullable'],
                ['string', 'digits_between:3,4'],
            ),
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'You must select at least one ticket type.',
            'items.min' => 'You must select at least one ticket type.',
            'items.*.ticket_type_id.distinct' => 'Each ticket type can only be ordered once per booking.',
            'payment.card_number.required_with' => 'Card number is required when providing payment details.',
            'payment.card_number.regex' => 'Please enter a valid card number (12-19 digits, spaces allowed).',
            'payment.expiry.required_with' => 'Card expiry is required when providing payment details.',
            'payment.expiry.regex' => 'Please enter a valid expiry date (MM/YY).',
            'payment.cvc.required_with' => 'Card CVC is required when providing payment details.',
            'payment.cvc.digits_between' => 'CVC must be 3 or 4 digits.',
        ];
    }
}
