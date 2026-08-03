<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Carbon\Carbon;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $booking_id
 * @property int|null $booking_item_id
 * @property int|null $ticket_type_id
 * @property int $user_id
 * @property int $event_id
 * @property string $code
 * @property TicketStatus $status
 * @property Carbon|null $issued_at
 * @property Carbon|null $checked_in_at
 * @property int|null $checked_in_by
 * @property Carbon|null $cancelled_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
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
        'checked_in_by',
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

    /**
     * @return BelongsTo<Booking, $this>
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * @return BelongsTo<BookingItem, $this>
     */
    public function bookingItem(): BelongsTo
    {
        return $this->belongsTo(BookingItem::class);
    }

    /**
     * @return BelongsTo<TicketType, $this>
     */
    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }
}
