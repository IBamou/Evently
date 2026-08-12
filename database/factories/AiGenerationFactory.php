<?php

namespace Database\Factories;

use App\Enums\AiGenerationStatus;
use App\Enums\AiOperation;
use App\Models\AiGeneration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiGeneration>
 */
class AiGenerationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'operation' => AiOperation::DRAFT->value,
            'status' => AiGenerationStatus::Processing,
            'inputs' => [
                'brief' => fake()->sentence(),
                'tone' => 'professional',
                'language' => 'en',
            ],
            'result' => null,
            'error_message' => null,
        ];
    }

    public function successful(array $result = []): static
    {
        return $this->state(fn (): array => [
            'status' => AiGenerationStatus::Success,
            'result' => $result,
            'error_message' => null,
        ]);
    }

    public function failed(string $errorMessage = 'The AI assistant is temporarily unavailable.'): static
    {
        return $this->state(fn (): array => [
            'status' => AiGenerationStatus::Error,
            'result' => null,
            'error_message' => $errorMessage,
        ]);
    }
}
