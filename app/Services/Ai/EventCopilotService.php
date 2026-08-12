<?php

namespace App\Services\Ai;

use App\Ai\Agents\EventDraftAgent;
use App\Ai\Agents\EventPolishAgent;
use App\Enums\AiOperation;

class EventCopilotService
{
    public function __construct(
        private AiCallService $aiCallService,
        private ResponseValidationService $validationService,
    ) {}

    public function generate(AiOperation $operation, array $inputs): array
    {
        $config = config('ai.event_copilot');

        $agent = match ($operation) {
            AiOperation::DRAFT => new EventDraftAgent,
            AiOperation::TRANSFORM => new EventPolishAgent,
        };

        $userContext = PromptBuilderService::buildUserContext($operation, $inputs);

        $rawText = $this->aiCallService->callWithFallback($agent, $userContext, $config);

        $data = $this->validationService->decodeAndValidate($rawText, $agent->aiSchema());

        return match ($operation) {
            AiOperation::DRAFT => $this->validationService->mapDraft($data, PromptBuilderService::getCategories()),
            AiOperation::TRANSFORM => $this->validationService->mapTransform($data, $inputs),
        };
    }
}
