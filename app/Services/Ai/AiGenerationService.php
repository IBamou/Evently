<?php

namespace App\Services\Ai;

use App\Ai\Agents\GenerateEventDraftAgent;
use App\Ai\Agents\GenerateEventMarketingAgent;
use App\Ai\Agents\TransformEventFieldAgent;
use App\Dto\Ai\AiProviderRoute;
use App\Dto\EventDraftResult;
use App\Dto\FieldTransformResult;
use App\Dto\MarketingResult;
use App\Dto\SocialMarketing;
use App\Enums\AiGenerationStatus;
use App\Models\AiGeneration;
use App\Models\Category;
use App\Models\User;
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
     */
    public function execute(AiGeneration $generation): void
    {
        $startTime = microtime(true);

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

            $generation->update([
                'status' => AiGenerationStatus::ERROR,
                'provider_used' => $providerUsed->provider,
                'model_used' => $providerUsed->model,
                'error_code' => $errorCode,
                'latency_ms' => $latencyMs,
            ]);
        }
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

        $response = $agent->prompt(
            'Generate event draft',
            provider: $route->provider,
            model: $route->model,
            timeout: $config['timeout'],
        );

        /** @var array<string, mixed> $data */
        $data = json_decode($response->text, true) ?? [];

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

        $response = $agent->prompt(
            'Generate marketing content',
            provider: $route->provider,
            model: $route->model,
            timeout: $config['timeout'],
        );

        /** @var array<string, mixed> $data */
        $data = json_decode($response->text, true) ?? [];

        $result = new MarketingResult(
            socialPost: $data['social_post'] ?? '',
            emailSubject: $data['email_subject'] ?? '',
            emailIntro: $data['email_intro'] ?? '',
        );

        return $result->toArray();
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

        $response = $agent->prompt(
            'Transform field',
            provider: $route->provider,
            model: $route->model,
            timeout: $config['timeout'],
        );

        /** @var array<string, mixed> $data */
        $data = json_decode($response->text, true) ?? [];

        $result = new FieldTransformResult(
            content: $data['content'] ?? '',
            language: $data['language'] ?? ($inputs['target_language'] ?? 'en'),
            warnings: array_values($data['warnings'] ?? []),
        );

        return $result->toArray();
    }

    /**
     * Load the original inputs from the generation's operation context.
     *
     * @return array<string, mixed>
     */
    private function loadInputs(AiGeneration $generation): array
    {
        // The inputs are stored in the input_hash but we need the actual data.
        // We store them in the result column as a temporary measure during creation.
        // Actually, let's use the operation metadata approach: the generation has
        // language and operation; we need to store inputs during creation.
        // We'll use a separate cache-based approach keyed by public_id.
        $cacheKey = "ai_copilot:inputs:{$generation->public_id}";

        return cache()->get($cacheKey, []);
    }

    /**
     * Store inputs for later retrieval by the job.
     *
     * @param  array<string, mixed>  $inputs
     */
    public function storeInputs(AiGeneration $generation, array $inputs): void
    {
        $cacheKey = "ai_copilot:inputs:{$generation->public_id}";
        cache()->put($cacheKey, $inputs, now()->addHour());
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
