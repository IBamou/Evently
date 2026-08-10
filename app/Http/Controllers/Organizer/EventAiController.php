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
    public function status(AiGeneration $generation): JsonResponse
    {
        $this->authorize('view', $generation);

        return response()->json([
            'data' => $this->service->statusPayload($generation),
        ]);
    }

    public function recordFeedback(AiGeneration $generation, GenerationFeedbackRequest $feedbackRequest): JsonResponse
    {
        $this->authorize('feedback', $generation);

        $validated = $feedbackRequest->validated();

        $this->recorder->recordFeedback(
            generation: $generation,
            action: $validated['action'],
            field: $validated['field'] ?? null,
        );

        return response()->json([
            'message' => 'Feedback recorded.',
        ]);
    }
}
