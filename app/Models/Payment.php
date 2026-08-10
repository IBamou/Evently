<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $booking_id
 * @property string $provider
 * @property string|null $provider_reference
 * @property PaymentStatus $status
 * @property string $amount
 * @property string $currency
 * @property Carbon|null $paid_at
 * @property array $metadata
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Payment extends Model
{
    /** @use HasFactory */
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'provider',
        'provider_reference',
        'status',
        'amount',
        'currency',
        'paid_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
