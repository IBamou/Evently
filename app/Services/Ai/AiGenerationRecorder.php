<?php

namespace App\Services\Ai;

use App\Enums\AiGenerationStatus;
use App\Models\AiGeneration;
use App\Models\AiGenerationFeedback;
use Illuminate\Support\Str;

class AiGenerationRecorder
{
    public function record(
        int $userId,
        string $operation,
        string $provider,
        string $model,
        AiGenerationStatus $status,
        string $language,
        string $inputHash,
        ?int $inputTokens = null,
        ?int $outputTokens = null,
        ?int $latencyMs = null,
        ?string $errorCode = null,
    ): AiGeneration {
        return AiGeneration::create([
            'public_id' => Str::ulid()->toString(),
            'user_id' => $userId,
            'feature' => 'event_copilot',
            'operation' => $operation,
            'provider' => $provider,
            'model' => $model,
            'prompt_version' => config('ai.prompt_version'),
            'status' => $status,
            'language' => $language,
            'input_hash' => $inputHash,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'latency_ms' => $latencyMs,
            'error_code' => $errorCode,
        ]);
    }

    public function recordFeedback(
        AiGeneration $generation,
        string $action,
        ?string $field = null,
    ): AiGenerationFeedback {
        return AiGenerationFeedback::create([
            'generation_id' => $generation->id,
            'action' => $action,
            'field' => $field,
        ]);
    }

    public function getGenerationByPublicId(string $publicId): ?AiGeneration
    {
        return AiGeneration::where('public_id', $publicId)->first();
    }
}
