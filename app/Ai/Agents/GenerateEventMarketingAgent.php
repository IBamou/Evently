<?php

namespace App\Ai\Agents;

use App\Ai\Prompts\EventCopilotPrompts;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class GenerateEventMarketingAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    private string $systemPrompt;

    /**
     * @param  array<string, mixed>  $eventContext
     */
    public function __construct(
        string $language,
        string $tone,
        array $eventContext,
    ) {
        $promptVersion = config('ai-event-copilot.prompt_version');
        $this->systemPrompt = EventCopilotPrompts::generateMarketing($promptVersion)
            .$this->buildUserContext($language, $tone, $eventContext);
    }

    public function instructions(): Stringable|string
    {
        return $this->systemPrompt;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'social_post' => $schema->string()->max(500)->required(),
            'email_subject' => $schema->string()->max(100)->required(),
            'email_intro' => $schema->string()->max(300)->required(),
        ];
    }

    /**
     * @param  array<string, mixed>  $eventContext
     */
    private function buildUserContext(string $language, string $tone, array $eventContext): string
    {
        $languageNames = ['en' => 'English', 'fr' => 'French', 'ar' => 'Arabic'];
        $languageName = $languageNames[$language] ?? $language;

        $message = "\n\n## USER REQUEST\n\n";
        $message .= "Output language: {$languageName}\n";
        $message .= "Tone: {$tone}\n\n";
        $message .= "Event details:\n";

        foreach ($eventContext as $key => $value) {
            if ($value !== null) {
                $message .= "- {$key}: {$value}\n";
            }
        }

        return $message;
    }
}
