<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\TicketStatus;
use Carbon\Carbon;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property int $event_id
 * @property string $reference
 * @property BookingStatus $status
 * @property string $subtotal
 * @property string $fees
 * @property string $total
 * @property string $currency
 * @property Carbon|null $expires_at
 * @property Carbon|null $confirmed_at
 * @property Carbon|null $cancelled_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    protected $fillable = [
        'reference',
        'idempotency_key',
        'user_id',
        'event_id',
        'status',
        'subtotal',
        'fees',
        'total',
        'currency',
        'expires_at',
        'confirmed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'subtotal' => 'decimal:2',
            'fees' => 'decimal:2',
            'total' => 'decimal:2',
            'expires_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Generate a unique booking reference: IEV-XXXXXXXX (8 uppercase alphanumeric).
     */
    public static function generateReference(): string
    {
        do {
            $reference = 'IEV-'.strtoupper(Str::random(8));
        } while (static::withoutGlobalScopes()->where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * @return HasMany<BookingItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Check if the booking can be cancelled per REQ-CN-001.
     */
    public function isCancellable(): bool
    {
        if (! in_array($this->status, [BookingStatus::Pending, BookingStatus::Confirmed])) {
            return false;
        }

        if ($this->event && $this->event->starts_at && $this->event->starts_at->isPast()) {
            return false;
        }

        if ($this->tickets()->where('status', TicketStatus::Used->value)->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Check if this is a free booking.
     */
    public function isFree(): bool
    {
        // Float round-trip is exact for stored decimals ("0.00" → 0.0 → "0")
        // and keeps bccomp's precision guard against float drift.
        return bccomp((string) (float) $this->total, '0', 2) === 0;
    }
}
