<?php

namespace Database\Factories;

use App\Models\AiGeneration;
use App\Models\AiGenerationFeedback;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiGenerationFeedback>
 */
class AiGenerationFeedbackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'generation_id' => AiGeneration::factory(),
            'action' => 'accepted',
            'field' => null,
        ];
    }
}
