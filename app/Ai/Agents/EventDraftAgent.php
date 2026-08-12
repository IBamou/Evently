<?php

namespace App\Ai\Agents;

use App\Enums\AiOperation;
use App\Schemas\Contracts\AiSchema;
use App\Schemas\EventDraftSchema;
use App\Services\Ai\PromptBuilderService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class EventDraftAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        return PromptBuilderService::getSystemPrompt(AiOperation::DRAFT);
    }

    public function schema(JsonSchema $schema): array
    {
        return (new EventDraftSchema)->schema($schema);
    }

    public function aiSchema(): AiSchema
    {
        return new EventDraftSchema;
    }
}
