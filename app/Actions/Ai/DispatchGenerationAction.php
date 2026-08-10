<?php

namespace App\Actions\Ai;

use App\Jobs\ProcessAiGenerationJob;
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
     * Create a generation record, persist inputs, dispatch the queue job,
     * and return the accepted response.
     */
    public function __invoke(
        FormRequest $request,
        string $operation,
        \Closure $inputExtractor,
    ): JsonResponse {
        $inputs = $inputExtractor($request);

        $generation = $this->service->create($operation, $inputs, $request->user());

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
