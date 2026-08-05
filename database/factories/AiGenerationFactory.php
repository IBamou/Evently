<?php

namespace Database\Factories;

use App\Enums\AiGenerationStatus;
use App\Models\AiGeneration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiGeneration>
 */
class AiGenerationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'public_id' => $this->faker->unique()->bothify('AI-########'),
            'user_id' => User::factory(),
            'feature' => 'copilot',
            'operation' => 'generate_draft',
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'provider_used' => null,
            'model_used' => null,
            'prompt_version' => 'v1',
            'status' => AiGenerationStatus::SUCCESS,
            'language' => 'en',
            'input_hash' => hash('sha256', $this->faker->text()),
            'input_tokens' => $this->faker->numberBetween(100, 5000),
            'output_tokens' => $this->faker->numberBetween(100, 2000),
            'latency_ms' => $this->faker->numberBetween(500, 5000),
            'error_code' => null,
            'result' => null,
        ];
    }

    public function processing(): static
    {
        return $this->state(fn () => [
            'status' => AiGenerationStatus::PROCESSING,
            'provider_used' => null,
            'model_used' => null,
            'result' => null,
            'error_code' => null,
        ]);
    }

    public function success(?array $result = null): static
    {
        return $this->state(fn () => [
            'status' => AiGenerationStatus::SUCCESS,
            'result' => $result,
            'error_code' => null,
        ]);
    }

    public function error(string $errorCode = 'ai_provider_unavailable'): static
    {
        return $this->state(fn () => [
            'status' => AiGenerationStatus::ERROR,
            'error_code' => $errorCode,
            'result' => null,
        ]);
    }
}
