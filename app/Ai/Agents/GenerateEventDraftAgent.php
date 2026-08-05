<?php

namespace App\Ai\Agents;

use App\Ai\Prompts\EventCopilotPrompts;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class GenerateEventDraftAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    private string $systemPrompt;

    /**
     * @param  array<string, mixed>  $eventContext
     * @param  list<array{id: int, name: string, slug: string}>  $categories
     */
    public function __construct(
        string $brief,
        ?string $audience,
        string $tone,
        string $language,
        array $eventContext,
        array $categories,
    ) {
        $promptVersion = config('ai-event-copilot.prompt_version');
        [$systemPrompt] = EventCopilotPrompts::generateDraft($categories, $promptVersion);

        $this->systemPrompt = $systemPrompt.$this->buildUserContext($brief, $audience, $tone, $language, $eventContext);
    }

    public function instructions(): Stringable|string
    {
        return $this->systemPrompt;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->max(255)->required(),
            'description' => $schema->string()->required(),
            'category_id' => $schema->integer()->nullable()->required(),
            'marketing' => $schema->object(fn (JsonSchema $s) => [
                'social_post' => $s->string()->max(500)->required(),
                'email_subject' => $s->string()->max(100)->required(),
                'email_intro' => $s->string()->max(300)->required(),
            ])->required(),
            'missing_information' => $schema->array()->items($schema->string())->required(),
        ];
    }

    /**
     * @param  array<string, mixed>  $eventContext
     */
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
