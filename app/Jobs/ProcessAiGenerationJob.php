<?php

namespace App\Jobs;

use App\Enums\AiGenerationStatus;
use App\Models\AiGeneration;
use App\Services\Ai\AiGenerationService;
use App\Services\Ai\AiProviderRouter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessAiGenerationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var list<int>
     */
    public array $backoff = [10, 30];

    /**
     * The time in seconds the job can run before timing out.
     */
    public int $timeout = 40;

    public function __construct(
        public AiGeneration $generation,
    ) {
        $this->timeout = (int) (config('ai-event-copilot.timeout', 30)) + 10;
        $this->queue = 'ai-copilot';
    }

    /**
     * Execute the job.
     */
    public function handle(AiGenerationService $service): void
    {
        // Reload to check current status
        $this->generation->refresh();

        if ($this->generation->status !== AiGenerationStatus::PROCESSING) {
            Log::info('AI generation job skipped: status is '.$this->generation->status->value, [
                'generation_id' => $this->generation->id,
            ]);

            return;
        }

        $service->execute($this->generation);
    }

    /**
     * Handle a job failure after all queue attempts (tries/backoff exhausted).
     *
     * Only finalizes generations that are still PROCESSING; a generation that
     * already reached SUCCESS (e.g. a duplicate attempt after a commit) is
     * left untouched.
     */
    public function failed(?Throwable $exception): void
    {
        $this->generation->refresh();

        if ($this->generation->status === AiGenerationStatus::PROCESSING) {
            $errorCode = $exception !== null
                ? app(AiProviderRouter::class)->mapErrorCode($exception)
                : 'ai_provider_unavailable';

            $this->generation->update([
                'status' => AiGenerationStatus::ERROR,
                'error_code' => $errorCode,
            ]);

            Log::error('AI generation job failed permanently', [
                'generation_id' => $this->generation->id,
                'operation' => $this->generation->operation,
                'error_code' => $errorCode,
                'error' => $exception?->getMessage(),
            ]);
        }
    }
}
