<?php

namespace App\Services\Ai;

use App\DTOs\Ai\AiProviderRoute;
use App\Enums\AiGenerationStatus;
use App\Models\AiGeneration;
use App\Models\User;
use App\Services\Ai\GenerationServices\EventDraftGenerator;
use App\Services\Ai\GenerationServices\EventFieldTransformGenerator;
use App\Services\Ai\GenerationServices\EventGenerator;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiGenerationService
{
    public function __construct(
        private readonly AiGenerationRecorder $recorder,
        private readonly AiProviderRouter $router,
    ) {}

    /**
     * Create a new generation record in Processing status.
     */
    public function create(string $operation, array $inputs, User $user): AiGeneration
    {
        $config = config('ai-event-copilot');

        $inputHash = hash('sha256', (string) json_encode($inputs));

        return $this->recorder->record(
            userId: $user->id,
            operation: $operation,
            provider: $config['provider'],
            model: $config['model'],
            status: AiGenerationStatus::PROCESSING,
            language: $inputs['language'] ?? 'en',
            inputHash: $inputHash,
        );
    }

    /**
     * Execute the AI generation: build agent, run with routing, record result.
     *
     * Failure taxonomy (see docs/prompt.md ISSUE 4):
     * - Retryable: transient provider failures (connection errors, timeouts,
     *   429/5xx). After the fallback provider also fails, the exception is
     *   rethrown so the queue retries the job (backoff/tries on the job).
     * - Non-retryable: invalid structured output, unsupported operation,
     *   malformed configuration (e.g. auth failures). These mark the
     *   generation as ERROR and return without throwing.
     *
     * The generation stays PROCESSING across retry attempts and is only
     * finalized (ERROR) by failed() after the last queue attempt, or directly
     * here for non-retryable failures. A SUCCESS result is never overwritten.
     */
    public function execute(AiGeneration $generation): void
    {
        $startTime = microtime(true);

        // Idempotency safeguard: never execute a finalized generation twice.
        if ($generation->status !== AiGenerationStatus::PROCESSING) {
            return;
        }

        try {
            $route = $this->router->resolvePrimary();
            $result = $this->runAgent($generation, $route);

            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            $generation->update([
                'status' => AiGenerationStatus::SUCCESS,
                'provider_used' => $route->provider,
                'model_used' => $route->model,
                'result' => $result,
                'latency_ms' => $latencyMs,
            ]);

        } catch (Throwable $e) {
            Log::error('AI copilot job error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'generation_id' => $generation->id,
                'operation' => $generation->operation,
            ]);

            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);
            $providerUsed = $this->router->resolvePrimary();

            // Try fallback if transient failure
            if ($this->router->isTransientFailure($e)) {
                $fallback = $this->router->resolveFallback();

                if ($fallback !== null) {
                    try {
                        $result = $this->runAgent($generation, $fallback);
                        $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

                        $generation->update([
                            'status' => AiGenerationStatus::SUCCESS,
                            'provider_used' => $fallback->provider,
                            'model_used' => $fallback->model,
                            'result' => $result,
                            'latency_ms' => $latencyMs,
                        ]);

                        return;
                    } catch (Throwable $fallbackException) {
                        $e = $fallbackException;
                        $providerUsed = $fallback;
                    }
                }
            }

            $errorCode = $this->router->mapErrorCode($e);

            // Record telemetry for the attempt. Retryable failures keep the
            // generation PROCESSING and rethrow so the queue can retry;
            // failed() finalizes the ERROR state after the last attempt.
            $generation->update([
                'provider_used' => $providerUsed->provider,
                'model_used' => $providerUsed->model,
                'error_code' => $errorCode,
                'latency_ms' => $latencyMs,
            ]);

            if ($this->isRetryableFailure($e, $errorCode)) {
                throw $e;
            }

            $generation->update(['status' => AiGenerationStatus::ERROR]);
        }
    }

    /**
     * Classify a failure after primary + fallback attempts.
     *
     * Retryable: transient provider failures (connection/timeout/429/5xx).
     * Everything else (invalid structured output, config/auth errors,
     * unsupported operations) is permanent.
     */
    private function isRetryableFailure(Throwable $e, string $errorCode): bool
    {
        if ($errorCode === 'ai_invalid_response') {
            return false;
        }

        return $this->router->isTransientFailure($e);
    }

    /**
     * Build the status payload for the polling endpoint.
     */
    public function statusPayload(AiGeneration $generation): array
    {
        return [
            'generation_id' => $generation->public_id,
            'status' => $generation->status->value,
            'result' => $generation->result,
            'error_code' => $generation->error_code,
            'error_message' => $this->errorMessageFor($generation->error_code),
            'operation' => $generation->operation,
            'provider_used' => $generation->provider_used,
            'model_used' => $generation->model_used,
            'latency_ms' => $generation->latency_ms,
        ];
    }

    /**
     * Run the appropriate generation service for the given operation.
     */
    private function runAgent(AiGeneration $generation, AiProviderRoute $route): array
    {
        $inputs = $this->loadInputs($generation);

        /** @var array $config */
        $config = config('ai-event-copilot');

        return $this->generatorFor($generation->operation)->generate($inputs, $route, $config);
    }

    /**
     * Resolve the isolated generation service responsible for an operation.
     */
    private function generatorFor(string $operation): EventGenerator
    {
        return match ($operation) {
            'generate_draft' => new EventDraftGenerator,
            default => new EventFieldTransformGenerator,
        };
    }

    /**
     * Load the original inputs for the generation.
     *
     * Source of truth is the durable input_payload column on the generation
     * row. Legacy rows created before that column existed fall back to the
     * cache entry and, when found, are persisted back to the database so the
     * cache is no longer required for execution.
     */
    private function loadInputs(AiGeneration $generation): array
    {
        if (is_array($generation->input_payload)) {
            return $generation->input_payload;
        }

        // Legacy fallback: rows created while inputs lived in cache only.
        $cacheKey = "ai_copilot:inputs:{$generation->public_id}";
        $inputs = cache()->get($cacheKey, []);

        if (is_array($inputs) && $inputs !== []) {
            $generation->update(['input_payload' => $inputs]);
        }

        return $inputs;
    }

    /**
     * Persist the validated request inputs for later execution by the job.
     *
     * Privacy / retention: the persisted payload contains only the sanitized,
     * validated request fields (brief, audience, tone, language, event
     * context, content, field, operation, target_language). Payment data,
     * credentials or secrets can never reach this path because inputs come
     * exclusively from validated FormRequests. The payload is retained for
     * the lifetime of the ai_generations row and is used for execution only;
     * input_hash remains available for analytics and deduplication.
     */
    public function storeInputs(AiGeneration $generation, array $inputs): void
    {
        $generation->update(['input_payload' => $inputs]);
    }

    /**
     * Map error_code to a human-readable error message.
     */
    private function errorMessageFor(?string $errorCode): ?string
    {
        return match ($errorCode) {
            'ai_provider_refused' => 'The AI provider refused the request. Please try again later.',
            'ai_invalid_response' => 'The AI returned an invalid response. Please try again.',
            'ai_provider_unavailable' => 'The AI service is temporarily unavailable. Please try again later.',
            'ai_generation_timeout' => 'The AI request timed out. Please try again.',

            default => null,
        };
    }
}
