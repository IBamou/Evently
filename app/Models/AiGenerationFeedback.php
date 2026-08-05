<?php

namespace App\Models;

use Database\Factories\AiGenerationFeedbackFactory;
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
    /** @use HasFactory<AiGenerationFeedbackFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = [
        'generation_id',
        'action',
        'field',
    ];

    /**
     * @return BelongsTo<AiGeneration, $this>
     */
    public function generation(): BelongsTo
    {
        return $this->belongsTo(AiGeneration::class, 'generation_id');
    }
}
