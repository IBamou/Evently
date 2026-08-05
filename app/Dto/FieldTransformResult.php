<?php

namespace App\Dto;

readonly class FieldTransformResult
{
    public function __construct(
        public string $content,
        public string $language,
        /** @var list<string> */
        public array $warnings,
    ) {}

    /** @return array{content: string, language: string, warnings: list<string>} */
    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'language' => $this->language,
            'warnings' => $this->warnings,
        ];
    }
}
