<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $published_count Computed via withCount query alias.
 */
class Category extends Model
{
    /** @use HasFactory */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'image'];

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
