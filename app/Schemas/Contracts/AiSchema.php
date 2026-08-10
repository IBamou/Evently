<?php

namespace App\Schemas\Contracts;

use Illuminate\Contracts\JsonSchema\JsonSchema;

interface AiSchema
{
    /**
     * Return the JsonSchema array for the AI provider's structured output.
     */
    public function schema(JsonSchema $schema): array;

    /**
     * Validate and coerce decoded AI response data.
     *
     * Returns the cleaned data array on success, throws \RuntimeException on failure.
     *
     *
     * @throws \RuntimeException
     */
    public function validate(array $data): array;
}
