<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\Ai\GenerateEventDraftRequest;
use App\Http\Requests\Organizer\Ai\GenerationFeedbackRequest;
use App\Http\Requests\Organizer\Ai\TransformEventFieldRequest;
use App\Jobs\ProcessAiGenerationJob;
use App\Models\AiGeneration;
use App\Services\Ai\AiGenerationRecorder;
use App\Services\Ai\AiGenerationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EventAiController extends Controller
{
    public function __construct(
        private readonly AiGenerationRecorder $recorder,
        private readonly AiGenerationService $service,
    ) {}

    public function generateDraft(GenerateEventDraftRequest $request): JsonResponse
    {
        return $this->dispatchGeneration(
            $request,
            'generate_draft',
            fn (FormRequest $r) => $r->validated(),
        );
    }

    public function transformField(TransformEventFieldRequest $request): JsonResponse
    {
        return $this->dispatchGeneration(
            $request,
            'transform_field',
            fn (FormRequest $r) => $r->validated(),
        );
    }

    /**
     * Poll the status of a generation.
     */
    public function status(Request $request, AiGeneration $generation): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $config = config('ai-event-copilot');

        if (! $config['enabled']) {
            return response()->json([
                'message' => 'AI Event Copilot is disabled.',
                'error_code' => 'ai_feature_disabled',
            ], Response::HTTP_FORBIDDEN);
        }

        if ($generation->user_id !== $user->id) {
            return response()->json([
                'message' => 'Generation not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => $this->service->statusPayload($generation),
        ]);
    }

    public function recordFeedback(Request $request, string $generation, GenerationFeedbackRequest $feedbackRequest): JsonResponse
    {
        $generationModel = $this->recorder->getGenerationByPublicId($generation);

        if (! $generationModel) {
            return response()->json([
                'message' => 'Generation not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $user = $request->user();

        if ($user === null || $generationModel->user_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized.',
            ], Response::HTTP_FORBIDDEN);
        }

        $validated = $feedbackRequest->validated();

        $this->recorder->recordFeedback(
            generation: $generationModel,
            action: $validated['action'],
            field: $validated['field'] ?? null,
        );

        return response()->json([
            'message' => 'Feedback recorded.',
        ]);
    }

    /**
     * Common dispatch logic for all generation endpoints.
     *
     * @param  \Closure(FormRequest): array<string, mixed>  $inputExtractor
     */
    private function dispatchGeneration(
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

        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

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

        // Store inputs for the job to retrieve
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
