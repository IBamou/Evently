<?php

namespace App\DTOs;

readonly class EventDraftResult
{
    public function __construct(
        public string $title,
        public string $description,
        /** @var array{id: int, name: string, slug: string}|null */
        public ?array $category,
        public SocialMarketing $marketing,
        /** @var array */
        public array $missingInformation,
    ) {}

    /** @return array|null */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'marketing' => $this->marketing->toArray(),
            'missing_information' => $this->missingInformation,
        ];
    }
}
