<?php

namespace App\Http\Controllers\Organizer;

use App\Actions\Ai\DispatchGenerationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\Ai\GenerateEventDraftRequest;
use App\Http\Requests\Organizer\Ai\GenerationFeedbackRequest;
use App\Http\Requests\Organizer\Ai\TransformEventFieldRequest;
use App\Models\AiGeneration;
use App\Services\Ai\AiGenerationRecorder;
use App\Services\Ai\AiGenerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EventAiController extends Controller
{
    public function __construct(
        private readonly AiGenerationRecorder $recorder,
        private readonly AiGenerationService $service,
        private readonly DispatchGenerationAction $dispatchGeneration,
    ) {}

    public function generateDraft(GenerateEventDraftRequest $request): JsonResponse
    {
        return ($this->dispatchGeneration)(
            $request,
            'generate_draft',
            fn ($r) => $r->validated(),
        );
    }

    public function transformField(TransformEventFieldRequest $request): JsonResponse
    {
        return ($this->dispatchGeneration)(
            $request,
            'transform_field',
            fn ($r) => $r->validated(),
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

        $this->authorize('view', $generation);

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

        if ($user === null) {
            abort(401);
        }

        $this->authorize('feedback', $generationModel);

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
}
