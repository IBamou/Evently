<?php

namespace App\DTOs;

readonly class FieldTransformResult
{
    public function __construct(
        public string $content,
        public string $language,
        public array $warnings,
    ) {}

    public function toArray(): array
    {
        return [
            'content' => $this->content,
            'language' => $this->language,
            'warnings' => $this->warnings,
        ];
    }
}
