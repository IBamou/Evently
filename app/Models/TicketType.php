<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketType extends Model
{
    /** @use HasFactory */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'event_id',
        'name',
        'description',
        'price',
        'currency',
        'quantity',
        'min_per_booking',
        'max_per_booking',
        'sales_start_at',
        'sales_end_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sales_start_at' => 'datetime',
            'sales_end_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function bookingItems(): HasMany
    {
        return $this->hasMany(BookingItem::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Get the number of allocated tickets via booking items where booking is
     * pending or confirmed.  This is the capacity source-of-truth — NOT the
     * tickets table, because tickets only exist for confirmed bookings.
     */
    public function allocatedQuantity(): int
    {
        return (int) $this->bookingItems()
            ->whereHas('booking', fn ($q) => $q->whereIn('status', [BookingStatus::Pending->value, BookingStatus::Confirmed->value]))
            ->sum('quantity');
    }

    /**
     * Get available quantity (capacity minus allocated).
     * Uses pre-loaded allocated_quantity from withSum if available (single-query optimization).
     */
    public function availableQuantity(): int
    {
        // FIX 1: If allocated_quantity was pre-loaded via withSum, avoid the N+1 query.
        if (isset($this->attributes['allocated_quantity'])) {
            return max(0, $this->quantity - (int) $this->attributes['allocated_quantity']);
        }

        return max(0, $this->quantity - $this->allocatedQuantity());
    }

    /**
     * Check if ticket sales are currently open.
     */
    public function isSalesOpen(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->sales_start_at && $this->sales_start_at->isAfter($now)) {
            return false;
        }

        if ($this->sales_end_at && $this->sales_end_at->isBefore($now)) {
            return false;
        }

        return true;
    }
}
