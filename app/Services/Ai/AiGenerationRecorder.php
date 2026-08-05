<?php

namespace App\Services\Ai;

use App\Enums\AiGenerationStatus;
use App\Models\AiGeneration;
use App\Models\AiGenerationFeedback;
use Illuminate\Support\Facades\Cache;
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
            'prompt_version' => config('ai-event-copilot.prompt_version'),
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

    public function getDailyCount(int $userId): int
    {
        $key = "ai_copilot:daily:{$userId}:".date('Y-m-d');

        return (int) Cache::get($key, 0);
    }

    public function incrementDailyCount(int $userId): void
    {
        $key = "ai_copilot:daily:{$userId}:".date('Y-m-d');

        Cache::add($key, 0, now()->endOfDay()->addMinute());
        Cache::increment($key);
    }

    public function getMinuteCount(int $userId): int
    {
        $key = "ai_copilot:minute:{$userId}:".floor(time() / 60);

        return (int) Cache::get($key, 0);
    }

    public function incrementMinuteCount(int $userId): void
    {
        $key = "ai_copilot:minute:{$userId}:".floor(time() / 60);

        Cache::add($key, 0, 120);
        Cache::increment($key);
    }

    /**
     * Atomically reserve one AI generation slot for the user.
     *
     * The cache increment is a single atomic store operation, so concurrent
     * requests cannot both pass the same limit check (no check-then-act race).
     * When a limit is exceeded the reservation is rolled back.
     *
     * @return string|null Error code when a limit is exceeded, null when the slot was reserved.
     */
    public function reserveGenerationSlot(int $userId): ?string
    {
        $config = config('ai-event-copilot');

        $minuteKey = "ai_copilot:minute:{$userId}:".floor(time() / 60);
        Cache::add($minuteKey, 0, 120);
        $minuteCount = (int) Cache::increment($minuteKey);

        if ($minuteCount > (int) $config['per_minute_limit']) {
            Cache::decrement($minuteKey);

            return 'ai_rate_limited';
        }

        $dailyKey = "ai_copilot:daily:{$userId}:".date('Y-m-d');
        Cache::add($dailyKey, 0, now()->endOfDay()->addMinute());
        $dailyCount = (int) Cache::increment($dailyKey);

        if ($dailyCount > (int) $config['daily_limit']) {
            Cache::decrement($dailyKey);
            Cache::decrement($minuteKey);

            return 'ai_daily_limit_reached';
        }

        return null;
    }

    public function getGenerationByPublicId(string $publicId): ?AiGeneration
    {
        return AiGeneration::where('public_id', $publicId)->first();
    }
}
