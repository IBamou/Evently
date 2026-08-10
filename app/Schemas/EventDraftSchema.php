<?php

namespace App\Schemas;

use App\Schemas\Contracts\AiSchema;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class EventDraftSchema implements AiSchema
{
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
     * @throws \RuntimeException
     */
    public function validate(array $data): array
    {
        $title = $data['title'] ?? null;
        $description = $data['description'] ?? null;

        if (! is_string($title) || $title === '') {
            throw new \RuntimeException('AI response missing required field: title.');
        }

        if (! is_string($description) || $description === '') {
            throw new \RuntimeException('AI response missing required field: description.');
        }

        $categoryId = $data['category_id'] ?? null;
        if ($categoryId !== null && ! is_int($categoryId)) {
            $categoryId = null;
        }

        $marketingRaw = is_array($data['marketing'] ?? null) ? $data['marketing'] : [];

        $marketing = [
            'social_post' => is_string($marketingRaw['social_post'] ?? null) ? $marketingRaw['social_post'] : '',
            'email_subject' => is_string($marketingRaw['email_subject'] ?? null) ? $marketingRaw['email_subject'] : '',
            'email_intro' => is_string($marketingRaw['email_intro'] ?? null) ? $marketingRaw['email_intro'] : '',
        ];

        $missingInformation = array_values(
            array_filter(
                is_array($data['missing_information'] ?? null) ? $data['missing_information'] : [],
                fn ($v) => is_string($v),
            ),
        );

        return [
            'title' => $title,
            'description' => $description,
            'category_id' => $categoryId,
            'marketing' => $marketing,
            'missing_information' => $missingInformation,
        ];
    }
}
