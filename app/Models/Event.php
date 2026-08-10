<?php

namespace App\Models;

use App\Enums\EventFormat;
use App\Enums\EventStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $category_id
 * @property int $organizer_id
 * @property string $title
 * @property string $slug
 * @property string $description
 * @property string $location
 * @property string $city
 * @property EventFormat $format
 * @property EventStatus $status
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property string|null $banner_url
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
class Event extends Model
{
    /** @use HasFactory */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organizer_id',
        'category_id',
        'title',
        'slug',
        'description',
        'location',
        'city',
        'format',
        'starts_at',
        'ends_at',
        'banner_url',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => EventStatus::class,
            'format' => EventFormat::class,
        ];
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', EventStatus::Published->value);
    }

    public function isPublished(): bool
    {
        return $this->status === EventStatus::Published;
    }

    public function isUpcoming(): bool
    {
        return $this->starts_at !== null && $this->starts_at->isFuture();
    }

    public function isBookable(): bool
    {
        return $this->isPublished() && $this->isUpcoming() && ! $this->trashed();
    }

    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
