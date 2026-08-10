<?php

namespace App\Services\Ai;

use App\Ai\Agents\GenerateEventDraftAgent;
use App\Ai\Agents\GenerateEventMarketingAgent;
use App\Ai\Agents\TransformEventFieldAgent;
use App\DTOs\Ai\AiProviderRoute;
use App\DTOs\EventDraftResult;
use App\DTOs\FieldTransformResult;
use App\DTOs\MarketingResult;
use App\DTOs\SocialMarketing;
use App\Enums\AiGenerationStatus;
use App\Models\AiGeneration;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Contracts\Agent;
use Throwable;

class AiGenerationService
{
    public function __construct(
        private readonly AiGenerationRecorder $recorder,
        private readonly AiProviderRouter $router,
    ) {}

    /**
     * Create a new generation record in Processing status.
     *
     * @param  array<string, mixed>  $inputs
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
     *
     * @return array{generation_id: string, status: string, result: array<string, mixed>|null, error_code: string|null, error_message: string|null, operation: string, provider_used: string|null, model_used: string|null, latency_ms: int|null}
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
     * Run the appropriate agent for the given generation.
     *
     * @return array<string, mixed>
     */
    private function runAgent(AiGeneration $generation, AiProviderRoute $route): array
    {
        $inputs = $this->loadInputs($generation);

        /** @var array<string, mixed> $config */
        $config = config('ai-event-copilot');

        return match ($generation->operation) {
            'generate_draft' => $this->runDraftAgent($inputs, $route, $config),
            'generate_marketing' => $this->runMarketingAgent($inputs, $route, $config),
            default => $this->runTransformAgent($inputs, $route, $config),
        };
    }

    /**
     * Run an agent and decode its structured output.
     *
     * @param  array<string, mixed>  $config
     * @param  callable(array<string, mixed>): array<string, mixed>  $map
     * @return array<string, mixed>
     */
    private function runAgentFlow(Agent $agent, string $promptText, AiProviderRoute $route, array $config, callable $map): array
    {
        $response = $agent->prompt(
            $promptText,
            provider: $route->provider,
            model: $route->model,
            timeout: $config['timeout'],
        );

        return $map($this->decodeStructuredResponse($response->text));
    }

    /**
     * @param  array<string, mixed>  $inputs
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function runDraftAgent(array $inputs, AiProviderRoute $route, array $config): array
    {
        $categories = array_values(Category::select('id', 'name', 'slug')->get()->toArray());

        $agent = new GenerateEventDraftAgent(
            brief: $inputs['brief'],
            audience: $inputs['audience'] ?? null,
            tone: $inputs['tone'],
            language: $inputs['language'],
            eventContext: $inputs['event_context'] ?? [],
            categories: $categories,
        );

        return $this->runAgentFlow($agent, 'Generate event draft', $route, $config, function (array $data) use ($categories): array {
            $categoryId = $data['category_id'] ?? null;
            $category = null;
            if ($categoryId !== null) {
                $category = collect($categories)->firstWhere('id', $categoryId);
                if ($category === null) {
                    $categoryId = null;
                }
            }

            /** @var array<string, mixed> $marketing */
            $marketing = $data['marketing'] ?? [];

            $result = new EventDraftResult(
                title: $data['title'] ?? '',
                description: $data['description'] ?? '',
                category: $category ? ['id' => $category['id'], 'name' => $category['name'], 'slug' => $category['slug']] : null,
                marketing: new SocialMarketing(
                    socialPost: $marketing['social_post'] ?? '',
                    emailSubject: $marketing['email_subject'] ?? '',
                    emailIntro: $marketing['email_intro'] ?? '',
                ),
                missingInformation: array_values($data['missing_information'] ?? []),
            );

            return $result->toArray();
        });
    }

    /**
     * @param  array<string, mixed>  $inputs
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function runMarketingAgent(array $inputs, AiProviderRoute $route, array $config): array
    {
        $agent = new GenerateEventMarketingAgent(
            language: $inputs['language'],
            tone: $inputs['tone'],
            eventContext: $inputs['event_context'] ?? [],
        );

        return $this->runAgentFlow($agent, 'Generate marketing content', $route, $config, function (array $data): array {
            $result = new MarketingResult(
                socialPost: $data['social_post'] ?? '',
                emailSubject: $data['email_subject'] ?? '',
                emailIntro: $data['email_intro'] ?? '',
            );

            return $result->toArray();
        });
    }

    /**
     * @param  array<string, mixed>  $inputs
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function runTransformAgent(array $inputs, AiProviderRoute $route, array $config): array
    {
        $agent = new TransformEventFieldAgent(
            field: $inputs['field'],
            operation: $inputs['operation'],
            content: $inputs['content'],
            tone: $inputs['tone'] ?? null,
            targetLanguage: $inputs['target_language'] ?? null,
            eventContext: $inputs['event_context'] ?? [],
        );

        return $this->runAgentFlow($agent, 'Transform field', $route, $config, function (array $data) use ($inputs): array {
            $result = new FieldTransformResult(
                content: $data['content'] ?? '',
                language: $data['language'] ?? ($inputs['target_language'] ?? 'en'),
                warnings: array_values($data['warnings'] ?? []),
            );

            return $result->toArray();
        });
    }

    /**
     * Load the original inputs for the generation.
     *
     * Source of truth is the durable input_payload column on the generation
     * row. Legacy rows created before that column existed fall back to the
     * cache entry and, when found, are persisted back to the database so the
     * cache is no longer required for execution.
     *
     * @return array<string, mixed>
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
     *
     * @param  array<string, mixed>  $inputs
     */
    public function storeInputs(AiGeneration $generation, array $inputs): void
    {
        $generation->update(['input_payload' => $inputs]);
    }

    /**
     * Decode the agent's structured output into an array.
     *
     * A null or non-JSON payload is treated as an invalid structured output:
     * a permanent (non-retryable) failure so the generation ends in ERROR
     * instead of silently persisting an empty result.
     *
     * @return array<string, mixed>
     */
    private function decodeStructuredResponse(?string $text): array
    {
        if (! is_string($text) || $text === '') {
            throw new \RuntimeException('AI returned an invalid response.');
        }

        $data = json_decode($text, true);

        if (! is_array($data)) {
            throw new \RuntimeException('AI returned an invalid response.');
        }

        return $data;
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
            'ai_rate_limited' => 'Too many requests. Please wait a moment.',
            'ai_daily_limit_reached' => 'You have reached your daily AI generation limit.',
            default => null,
        };
    }
}
