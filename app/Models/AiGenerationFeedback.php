<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $generation_id
 * @property string $action
 * @property string|null $field
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> query()
 */
class AiGenerationFeedback extends Model
{
    /** @use HasFactory */
    use HasFactory;

    /** @var array */
    protected $fillable = [
        'generation_id',
        'action',
        'field',
    ];

    public function generation(): BelongsTo
    {
        return $this->belongsTo(AiGeneration::class, 'generation_id');
    }
}
