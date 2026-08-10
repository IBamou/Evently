<?php

namespace App\Services\Ai\GenerationServices;

use App\Ai\Agents\GenerateEventDraftAgent;
use App\DTOs\EventDraftResult;
use App\DTOs\SocialMarketing;
use App\Models\Category;
use App\Schemas\Contracts\AiSchema;
use App\Schemas\EventDraftSchema;
use Laravel\Ai\Contracts\Agent;

class EventDraftGenerator extends EventGenerator
{
    /**
     * Categories are loaded once and reused for the agent context and the
     * category_id validation in mapResult().
     *
     * @var list<array{id: int, name: string, slug: string}>
     */
    private array $categories = [];

    /**
     * @param  array<string, mixed>  $inputs
     */
    protected function buildAgent(array $inputs): Agent
    {
        $this->categories = array_values(Category::select('id', 'name', 'slug')->get()->toArray());

        return new GenerateEventDraftAgent(
            brief: $inputs['brief'],
            audience: $inputs['audience'] ?? null,
            tone: $inputs['tone'],
            language: $inputs['language'],
            eventContext: $inputs['event_context'] ?? [],
            categories: $this->categories,
        );
    }

    protected function promptText(): string
    {
        return 'Generate event draft';
    }

    protected function schema(): AiSchema
    {
        return app(EventDraftSchema::class);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $inputs
     * @return array<string, mixed>
     */
    protected function mapResult(array $data, array $inputs): array
    {
        $categoryId = $data['category_id'] ?? null;
        $category = null;
        if ($categoryId !== null) {
            $category = collect($this->categories)->firstWhere('id', $categoryId);
            if ($category === null) {
                $categoryId = null;
            }
        }

        /** @var array<string, mixed> $marketing */
        $marketing = $data['marketing'] ?? [];

        $result = new EventDraftResult(
            title: $data['title'] ?? '',
            description: $data['description'] ?? '',
            category: $category ? ['id' => $category['id'], 'name' => $category['name'], 'slug' => $category['slug']] : null,
            marketing: new SocialMarketing(
                socialPost: $marketing['social_post'] ?? '',
                emailSubject: $marketing['email_subject'] ?? '',
                emailIntro: $marketing['email_intro'] ?? '',
            ),
            missingInformation: array_values($data['missing_information'] ?? []),
        );

        return $result->toArray();
    }
}
