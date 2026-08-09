<?php

namespace App\DTOs\Ai;

readonly class AiProviderRoute
{
    public function __construct(
        public string $provider,
        public string $model,
    ) {}
}
