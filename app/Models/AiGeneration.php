<?php

namespace App\Models;

use App\Enums\AiGenerationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiGeneration extends Model
{
    /** @use HasFactory */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'operation',
        'status',
        'inputs',
        'result',
        'error_message',
    ];

    protected $attributes = [
        'status' => AiGenerationStatus::Processing->value,
    ];

    protected function casts(): array
    {
        return [
            'status' => AiGenerationStatus::class,
            'inputs' => 'array',
            'result' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
