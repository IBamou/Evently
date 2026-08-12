<?php

namespace App\Services\Ai;

use App\Enums\AiGenerationStatus;
use App\Models\AiGeneration;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Handles all database persistence for AI generation records:
 * creating new generations, recording successful results,
 * and recording errors.
 */
class GenerationPersistenceService
{
    /**
     * Create a new generation record and return it.
     */
    public function create(User $user, string $operation, array $inputs): AiGeneration
    {
        return AiGeneration::create([
            'user_id' => $user->id,
            'operation' => $operation,
            'inputs' => $inputs,
        ]);
    }

    /**
     * Record a successful result on the generation.
     */
    public function recordSuccess(AiGeneration $generation, array $result): void
    {
        $generation->update([
            'status' => AiGenerationStatus::Success,
            'result' => $result,
        ]);
    }

    /**
     * Record a failure on the generation.
     */
    public function recordFailure(AiGeneration $generation, ?Throwable $exception): void
    {
        $generation->update([
            'status' => AiGenerationStatus::Error,
            'error_message' => 'The AI assistant is temporarily unavailable. Please try again later.',
        ]);

        Log::error('AI generation job failed', [
            'generation_id' => $generation->id,
            'operation' => $generation->operation,
            'error' => $exception?->getMessage(),
        ]);
    }

    /**
     * Check if a generation is still in processing state.
     */
    public function isProcessing(AiGeneration $generation): bool
    {
        return $generation->status === AiGenerationStatus::Processing;
    }
}
