<?php

namespace App\Http\Controllers\Organizer;

use App\Enums\AiOperation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\Ai\GenerateEventDraftRequest;
use App\Http\Requests\Organizer\Ai\TransformEventFieldRequest;
use App\Jobs\EventGenerationJob;
use App\Models\AiGeneration;
use App\Services\Ai\GenerationPersistenceService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class EventAiController extends Controller
{
    public function __construct(
        private GenerationPersistenceService $persistence,
    ) {}

    public function generateDraft(GenerateEventDraftRequest $request): JsonResponse
    {
        return $this->dispatchGeneration($request, AiOperation::DRAFT);
    }

    public function transformField(TransformEventFieldRequest $request): JsonResponse
    {
        return $this->dispatchGeneration($request, AiOperation::TRANSFORM);
    }

    public function status(Request $request, int $generationId): JsonResponse
    {
        $generation = AiGeneration::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($generationId);

        return response()->json([
            'data' => [
                'generation_id' => $generation->id,
                'status' => $generation->status,
                'result' => $generation->result,
                'error_message' => $generation->error_message,
            ],
        ]);
    }

    public function workspace()
    {
        return view('organizer.ai.workspace');
    }

    private function dispatchGeneration(FormRequest $request, AiOperation $operation): JsonResponse
    {
        $generation = $this->persistence->create(
            $request->user(),
            $operation->value,
            $request->validated(),
        );

        EventGenerationJob::dispatch($generation);

        return response()->json([
            'data' => [
                'generation_id' => $generation->id,
                'status' => $generation->status,
            ],
        ], Response::HTTP_ACCEPTED);
    }
}
