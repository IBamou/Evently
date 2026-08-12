<?php

namespace App\Services\Ai;

use App\Enums\AiOperation;
use App\Models\Category;
use App\Prompts\EventCopilotPrompts;

class PromptBuilderService
{
    private const LANGUAGE_NAMES = [
        'en' => 'English',
        'fr' => 'French',
        'ar' => 'Arabic',
    ];

    public static function getSystemPrompt(AiOperation $operation): string
    {
        $promptVersion = config('ai.event_copilot.prompt_version');

        if ($operation === AiOperation::DRAFT) {
            return EventCopilotPrompts::generateDraft(self::getCategories(), $promptVersion);
        }

        return EventCopilotPrompts::transformField($promptVersion);
    }

    public static function getCategories(): array
    {
        return array_values(
            Category::select('id', 'name', 'slug')->get()->toArray()
        );
    }

    public static function buildUserContext(AiOperation $operation, array $inputs): string
    {
        return match ($operation) {
            AiOperation::DRAFT => self::buildDraftContext($inputs),
            AiOperation::TRANSFORM => self::buildTransformContext($inputs),
        };
    }

    private static function buildDraftContext(array $inputs): string
    {
        $languageName = self::LANGUAGE_NAMES[$inputs['language'] ?? 'en']
            ?? ($inputs['language'] ?? 'en');

        $message = "Brief: {$inputs['brief']}\n";

        if (! empty($inputs['audience'])) {
            $message .= "Target audience: {$inputs['audience']}\n";
        }

        $message .= "Tone: {$inputs['tone']}\n";
        $message .= "Output language: {$languageName}\n";

        $message .= self::formatEventContext($inputs['event_context'] ?? []);

        return $message;
    }

    private static function buildTransformContext(array $inputs): string
    {
        $message = "Operation: {$inputs['operation']}\n";
        $message .= "Field: {$inputs['field']}\n";

        if (! empty($inputs['tone'])) {
            $message .= "Tone: {$inputs['tone']}\n";
        }

        if (! empty($inputs['target_language'])) {
            $languageName = self::LANGUAGE_NAMES[$inputs['target_language']]
                ?? $inputs['target_language'];
            $message .= "Target language: {$languageName}\n";
        }

        $message .= self::formatEventContext($inputs['event_context'] ?? []);

        $message .= "\nCurrent {$inputs['field']} content:\n\"\"\"\n{$inputs['content']}\n\"\"\"";

        return $message;
    }

    private static function formatEventContext(array $contextFields): string
    {
        $contextFields = array_filter($contextFields, fn ($v) => $v !== null);

        if (empty($contextFields)) {
            return '';
        }

        $message = "\nExisting event context:\n";
        foreach ($contextFields as $key => $value) {
            $message .= "- {$key}: {$value}\n";
        }

        return $message;
    }
}
