<?php

namespace App\Models;

use App\Enums\AiGenerationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $feature
 * @property string $operation
 * @property string $provider
 * @property string $model
 * @property string|null $provider_used
 * @property string|null $model_used
 * @property string|null $prompt_version
 * @property AiGenerationStatus $status
 * @property string|null $language
 * @property string|null $input_hash
 * @property array|null $input_payload
 * @property int|null $input_tokens
 * @property int|null $output_tokens
 * @property int|null $latency_ms
 * @property string|null $error_code
 * @property array|null $result
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> query()
 */
class AiGeneration extends Model
{
    /** @use HasFactory */
    use HasFactory;

    /** @var array */
    protected $fillable = [
        'public_id',
        'user_id',
        'feature',
        'operation',
        'provider',
        'model',
        'provider_used',
        'model_used',
        'prompt_version',
        'status',
        'language',
        'input_hash',
        'input_payload',
        'input_tokens',
        'output_tokens',
        'latency_ms',
        'error_code',
        'result',
    ];

    protected function casts(): array
    {
        return [
            'status' => AiGenerationStatus::class,
            'input_payload' => 'array',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'latency_ms' => 'integer',
            'result' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(AiGenerationFeedback::class, 'generation_id');
    }
}
