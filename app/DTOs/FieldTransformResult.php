<?php

namespace App\DTOs;

readonly class FieldTransformResult
{
    public function __construct(
        public string $content,
        public string $language,
        /** @var array */
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
