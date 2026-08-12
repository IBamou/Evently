<?php

namespace App\Services\Ai;

use App\Schemas\Contracts\AiSchema;
use RuntimeException;

class ResponseValidationService
{
    public function decodeAndValidate(?string $text, AiSchema $schema): array
    {
        if (! is_string($text) || $text === '') {
            throw new RuntimeException('AI returned an invalid response.');
        }

        $data = json_decode($text, true);

        if (! is_array($data)) {
            if (preg_match('/```(?:json)?\s*\n?(.*?)\n?\s*```/s', $text, $m)) {
                $data = json_decode($m[1], true);
            }
        }

        if (! is_array($data)) {
            throw new RuntimeException('AI returned an invalid response.');
        }

        return $schema->validate($data);
    }

    public function mapDraft(array $data, array $categories): array
    {
        $categoryId = $data['category_id'] ?? null;
        $category = $categoryId !== null
            ? $this->matchCategory($categoryId, $categories)
            : null;

        $marketing = $data['marketing'] ?? [];

        return [
            'title' => $data['title'],
            'description' => $data['description'],
            'category' => $category,
            'marketing' => [
                'social_post' => $marketing['social_post'] ?? '',
                'email_subject' => $marketing['email_subject'] ?? '',
                'email_intro' => $marketing['email_intro'] ?? '',
            ],
            'missing_information' => array_values($data['missing_information'] ?? []),
        ];
    }

    public function mapTransform(array $data, array $inputs): array
    {
        return [
            'content' => $data['content'],
            'language' => $data['language'] ?: ($inputs['target_language'] ?? 'en'),
            'warnings' => array_values($data['warnings'] ?? []),
        ];
    }

    private function matchCategory(int $id, array $categories): ?array
    {
        foreach ($categories as $category) {
            if ($category['id'] === $id) {
                return $category;
            }
        }

        return null;
    }
}
