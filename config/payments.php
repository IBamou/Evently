<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Mock Payment Confirmation
    |--------------------------------------------------------------------------
    |
    | The booking flow currently simulates payment confirmation (REQ-PY-002):
    | a user submits mock card details and the booking is confirmed instantly
    | without any real charge. This is fine for development and demos, but it
    | must never silently confirm payments in a production-like environment.
    |
    | Set PAYMENTS_MOCK_CONFIRM=false to disable the mock confirmation path:
    | bookings stay pending and the manual confirm endpoint is blocked until a
    | real payment provider is integrated.
    |
    */

    'mock_confirm' => (bool) env('PAYMENTS_MOCK_CONFIRM', true),

];
