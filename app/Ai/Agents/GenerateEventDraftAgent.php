<?php

namespace App\Ai\Agents;

use App\Prompts\EventCopilotPrompts;
use App\Schemas\Contracts\AiSchema;
use App\Schemas\EventDraftSchema;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class GenerateEventDraftAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    private string $systemPrompt;

    public function __construct(
        string $brief,
        ?string $audience,
        string $tone,
        string $language,
        array $eventContext,
        array $categories,
    ) {
        $promptVersion = config('ai.event_copilot.prompt_version');
        [$systemPrompt] = EventCopilotPrompts::generateDraft($categories, $promptVersion);

        $this->systemPrompt = $systemPrompt.$this->buildUserContext($brief, $audience, $tone, $language, $eventContext);
    }

    public function instructions(): Stringable|string
    {
        return $this->systemPrompt;
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->aiSchema()->schema($schema);
    }

    public function aiSchema(): AiSchema
    {
        return app(EventDraftSchema::class);
    }

    private function buildUserContext(
        string $brief,
        ?string $audience,
        string $tone,
        string $language,
        array $eventContext,
    ): string {
        $languageNames = ['en' => 'English', 'fr' => 'French', 'ar' => 'Arabic'];
        $languageName = $languageNames[$language] ?? $language;

        $message = "\n\n## USER REQUEST\n\n";
        $message .= "Brief: {$brief}\n";

        if ($audience) {
            $message .= "Target audience: {$audience}\n";
        }

        $message .= "Tone: {$tone}\n";
        $message .= "Output language: {$languageName}\n";

        $contextFields = array_filter($eventContext, fn ($v) => $v !== null);
        if (! empty($contextFields)) {
            $message .= "\nExisting event context:\n";
            foreach ($contextFields as $key => $value) {
                $message .= "- {$key}: {$value}\n";
            }
        }

        return $message;
    }
}
