<?php

namespace App\Ai\Agents;

use App\Prompts\EventCopilotPrompts;
use App\Schemas\Contracts\AiSchema;
use App\Schemas\FieldTransformSchema;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class TransformEventFieldAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    private string $systemPrompt;

    public function __construct(
        string $field,
        string $operation,
        string $content,
        ?string $tone,
        ?string $targetLanguage,
        array $eventContext,
    ) {
        $promptVersion = config('ai.prompt_version');
        $this->systemPrompt = EventCopilotPrompts::transformField($promptVersion)
            .$this->buildUserContext($field, $operation, $content, $tone, $targetLanguage, $eventContext);
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
        return app(FieldTransformSchema::class);
    }

    private function buildUserContext(
        string $field,
        string $operation,
        string $content,
        ?string $tone,
        ?string $targetLanguage,
        array $eventContext,
    ): string {
        $languageNames = ['en' => 'English', 'fr' => 'French', 'ar' => 'Arabic'];

        $message = "\n\n## USER REQUEST\n\n";
        $message .= "Operation: {$operation}\n";
        $message .= "Field: {$field}\n";

        if ($tone) {
            $message .= "Tone: {$tone}\n";
        }

        if ($targetLanguage) {
            $languageName = $languageNames[$targetLanguage] ?? $targetLanguage;
            $message .= "Target language: {$languageName}\n";
        }

        $contextFields = array_filter($eventContext, fn ($v) => $v !== null);
        if (! empty($contextFields)) {
            $message .= "\nEvent context:\n";
            foreach ($contextFields as $key => $value) {
                $message .= "- {$key}: {$value}\n";
            }
        }

        $message .= "\nCurrent {$field} content:\n\"\"\"\n{$content}\n\"\"\"";

        return $message;
    }
}
