<?php

namespace App\Services\Ai;

use App\Ai\Agents\EventDraftAgent;
use App\Ai\Agents\EventPolishAgent;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

/**
 * Handles all HTTP communication with AI providers, including
 * primary/fallback retry logic and transient-failure detection.
 */
class AiCallService
{
    /**
     * Call the agent on the primary provider; on a transient failure,
     * retry once on the fallback provider. Rethrows only when both fail.
     */
    public function callWithFallback(
        EventDraftAgent|EventPolishAgent $agent,
        string $promptText,
        array $config,
    ): string {
        try {
            return $this->callAgent(
                $agent,
                $promptText,
                $config['provider'],
                $config['model'],
                $config['timeout'],
            );
        } catch (Throwable $e) {
            $fallbackProvider = $config['fallback_provider'] ?? null;
            $fallbackModel = $config['fallback_model'] ?? null;

            if ($fallbackProvider === null || $fallbackModel === null || ! $this->isTransientFailure($e)) {
                throw $e;
            }

            return $this->callAgent(
                $agent,
                $promptText,
                $fallbackProvider,
                $fallbackModel,
                $config['timeout'],
            );
        }
    }

    /**
     * Execute a single prompt call against a specific provider/model.
     */
    public function callAgent(
        EventDraftAgent|EventPolishAgent $agent,
        string $promptText,
        string $provider,
        string $model,
        int $timeout,
    ): string {
        $response = $agent->prompt(
            $promptText,
            provider: $provider,
            model: $model,
            timeout: $timeout,
        );

        return $response->text;
    }

    /**
     * Determine if an exception qualifies as a transient failure.
     *
     * Transient: 429 / 5xx, connection errors and timeouts.
     * Authentication and validation failures (401/403/422) are permanent.
     */
    public function isTransientFailure(Throwable $e): bool
    {
        if ($e instanceof ConnectionException) {
            return true;
        }

        if ($e instanceof RequestException) {
            return in_array($e->response->status(), [429, 500, 502, 503, 504]);
        }

        if ($e instanceof HttpException) {
            return in_array($e->getStatusCode(), [429, 500, 502, 503, 504]);
        }

        $message = strtolower($e->getMessage());

        if (str_contains($message, 'timeout') || str_contains($message, 'timed out')) {
            return true;
        }

        if (str_contains($message, 'connection') || str_contains($message, 'connect')) {
            return true;
        }

        return preg_match('/\b(429|5\d{2})\b/', (string) $message) === 1;
    }
}
