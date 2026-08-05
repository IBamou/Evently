<?php

namespace App\Dto;

readonly class EventDraftResult
{
    public function __construct(
        public string $title,
        public string $description,
        /** @var array{id: int, name: string, slug: string}|null */
        public ?array $category,
        public SocialMarketing $marketing,
        /** @var list<string> */
        public array $missingInformation,
    ) {}

    /** @return array{title: string, description: string, category: array{id: int, name: string, slug: string}|null, marketing: array{social_post: string, email_subject: string, email_intro: string}, missing_information: list<string>} */
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
