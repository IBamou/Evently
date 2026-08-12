<?php

namespace App\Jobs;

use App\Enums\AiOperation;
use App\Models\AiGeneration;
use App\Services\Ai\EventCopilotService;
use App\Services\Ai\GenerationPersistenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class EventGenerationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public array $backoff = [10, 30];

    public int $timeout = 40;

    public function __construct(
        public AiGeneration $generation,
    ) {}

    public function handle(EventCopilotService $service): void
    {
        $this->generation->refresh();

        $persistence = app(GenerationPersistenceService::class);

        if (! $persistence->isProcessing($this->generation)) {
            return;
        }

        $operation = AiOperation::from($this->generation->operation);

        $result = $service->generate($operation, $this->generation->inputs);

        $persistence->recordSuccess($this->generation, $result);
    }

    public function failed(?Throwable $exception): void
    {
        $this->generation->refresh();

        $persistence = app(GenerationPersistenceService::class);

        if (! $persistence->isProcessing($this->generation)) {
            return;
        }

        $persistence->recordFailure($this->generation, $exception);
    }
}
