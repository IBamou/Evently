<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Ticket extends Model
{
    /** @use HasFactory */
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'booking_item_id',
        'ticket_type_id',
        'user_id',
        'event_id',
        'code',
        'status',
        'issued_at',
        'checked_in_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'issued_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Generate a unique ticket code: T-XXXXXXXXXX (10 uppercase alphanumeric).
     */
    public static function generateCode(): string
    {
        do {
            $code = 'T-'.strtoupper(Str::random(10));
        } while (static::withoutGlobalScopes()->where('code', $code)->exists());

        return $code;
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function bookingItem(): BelongsTo
    {
        return $this->belongsTo(BookingItem::class);
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
