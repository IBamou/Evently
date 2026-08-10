<?php

namespace App\Actions\Ai;

use App\Jobs\ProcessAiGenerationJob;
use App\Models\User;
use App\Services\Ai\AiGenerationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class DispatchGenerationAction
{
    public function __construct(
        private readonly AiGenerationService $service,
    ) {}

    /**
     * Check guards, create a generation record, persist inputs, dispatch the
     * queue job, and return the accepted response.
     *
     * @param  \Closure(FormRequest): array<string, mixed>  $inputExtractor
     */
    public function __invoke(
        FormRequest $request,
        string $operation,
        \Closure $inputExtractor,
    ): JsonResponse {
        $config = config('ai-event-copilot');

        if (! $config['enabled']) {
            return response()->json([
                'message' => 'AI Event Copilot is disabled.',
                'error_code' => 'ai_feature_disabled',
            ], Response::HTTP_FORBIDDEN);
        }

        /** @var User $user */
        $user = $request->user();

        $canRun = $user->canRunAiGeneration();

        if ($canRun !== true) {
            $message = match ($canRun) {
                'ai_rate_limited' => 'You have made several AI requests. Try again shortly.',
                'ai_daily_limit_reached' => "You have reached today's AI-generation limit. You can still complete the event manually.",
                default => 'Rate limit exceeded.',
            };

            return response()->json([
                'message' => $message,
                'error_code' => $canRun,
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $inputs = $inputExtractor($request);

        $generation = $this->service->create($operation, $inputs, $user);

        $this->service->storeInputs($generation, $inputs);

        ProcessAiGenerationJob::dispatch($generation)
            ->onQueue('ai-copilot');

        return response()->json([
            'data' => [
                'generation_id' => $generation->public_id,
                'status' => 'processing',
            ],
        ], Response::HTTP_ACCEPTED);
    }
}
