<?php

namespace App\Services\Ai;

use App\DTOs\Ai\AiProviderRoute;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class AiProviderRouter
{
    public function __construct() {}

    /**
     * Resolve the primary provider + model from config.
     */
    public function resolvePrimary(): AiProviderRoute
    {
        $config = config('ai-event-copilot');

        return new AiProviderRoute(
            provider: $config['provider'],
            model: $config['model'],
        );
    }

    /**
     * Resolve the fallback provider + model from config (may be null).
     */
    public function resolveFallback(): ?AiProviderRoute
    {
        $config = config('ai-event-copilot');

        $fallbackProvider = $config['fallback_provider'] ?? null;
        $fallbackModel = $config['fallback_model'] ?? null;

        if ($fallbackProvider === null || $fallbackModel === null) {
            return null;
        }

        return new AiProviderRoute(
            provider: $fallbackProvider,
            model: $fallbackModel,
        );
    }

    /**
     * Determine if an exception qualifies as a transient failure (eligible for fallback).
     *
     * Transient: 429 (rate limit), connection errors, timeouts, 5xx server errors.
     * NOT transient: 401/403/422 (auth/validation).
     */
    public function isTransientFailure(Throwable $e): bool
    {
        if ($e instanceof ConnectionException) {
            return true;
        }

        if ($e instanceof RequestException) {
            $status = $e->response->status();

            return in_array($status, [429, 500, 502, 503, 504]);
        }

        if ($e instanceof HttpException) {
            $status = $e->getStatusCode();

            return in_array($status, [429, 500, 502, 503, 504]);
        }

        // Generic timeouts and connection-related errors
        $message = strtolower($e->getMessage());

        if (str_contains($message, 'timeout') || str_contains($message, 'timed out')) {
            return true;
        }

        if (str_contains($message, 'connection') || str_contains($message, 'connect')) {
            return true;
        }

        // Check for HTTP status codes in message (e.g. "429 Too Many Requests")
        if (preg_match('/\b(429|5\d{2})\b/', $message)) {
            return true;
        }

        return false;
    }

    /**
     * Map an exception to an error code following the established contract.
     */
    public function mapErrorCode(Throwable $e): string
    {
        if ($this->isTransientFailure($e)) {
            if ($e instanceof RequestException) {
                return match ($e->response->status()) {
                    429 => 'ai_provider_refused',
                    default => 'ai_provider_unavailable',
                };
            }

            $message = strtolower($e->getMessage());
            if (str_contains($message, 'timeout') || str_contains($message, 'timed out')) {
                return 'ai_generation_timeout';
            }

            return 'ai_provider_unavailable';
        }

        $message = strtolower($e->getMessage());
        if (str_contains($message, 'timeout') || str_contains($message, 'timed out')) {
            return 'ai_generation_timeout';
        }
        if (str_contains($message, 'rate')) {
            return 'ai_provider_refused';
        }
        if (str_contains($message, 'invalid') || str_contains($message, 'parse')) {
            return 'ai_invalid_response';
        }

        return 'ai_provider_unavailable';
    }
}
