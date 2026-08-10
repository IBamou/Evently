<?php

namespace App\Services\Ai\GenerationServices;

use App\Ai\Agents\TransformEventFieldAgent;
use App\DTOs\FieldTransformResult;
use Laravel\Ai\Contracts\Agent;

class EventFieldTransformGenerator extends EventGenerator
{
    /**
     * @param  array<string, mixed>  $inputs
     */
    protected function buildAgent(array $inputs): Agent
    {
        return new TransformEventFieldAgent(
            field: $inputs['field'],
            operation: $inputs['operation'],
            content: $inputs['content'],
            tone: $inputs['tone'] ?? null,
            targetLanguage: $inputs['target_language'] ?? null,
            eventContext: $inputs['event_context'] ?? [],
        );
    }

    protected function promptText(): string
    {
        return 'Transform field';
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $inputs
     * @return array<string, mixed>
     */
    protected function mapResult(array $data, array $inputs): array
    {
        $result = new FieldTransformResult(
            content: $data['content'] ?? '',
            language: $data['language'] ?? ($inputs['target_language'] ?? 'en'),
            warnings: array_values($data['warnings'] ?? []),
        );

        return $result->toArray();
    }
}
