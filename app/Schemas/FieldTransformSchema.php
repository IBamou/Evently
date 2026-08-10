<?php

namespace App\Schemas;

use App\Schemas\Contracts\AiSchema;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class FieldTransformSchema implements AiSchema
{
    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'content' => $schema->string()->required(),
            'language' => $schema->string()->required(),
            'warnings' => $schema->array()->items($schema->string())->required(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{content: string, language: string, warnings: list<string>}
     *
     * @throws \RuntimeException
     */
    public function validate(array $data): array
    {
        $content = $data['content'] ?? null;

        if (! is_string($content) || $content === '') {
            throw new \RuntimeException('AI response missing required field: content.');
        }

        $language = $data['language'] ?? null;
        if (! is_string($language) || $language === '') {
            throw new \RuntimeException('AI response missing required field: language.');
        }

        /** @var list<string> $warnings */
        $warnings = array_values(
            array_filter(
                is_array($data['warnings'] ?? null) ? $data['warnings'] : [],
                fn ($v) => is_string($v),
            ),
        );

        return [
            'content' => $content,
            'language' => $language,
            'warnings' => $warnings,
        ];
    }
}
