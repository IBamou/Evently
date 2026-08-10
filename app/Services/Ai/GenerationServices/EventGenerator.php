<?php

namespace App\Services\Ai\GenerationServices;

use App\DTOs\Ai\AiProviderRoute;
use App\Schemas\Contracts\AiSchema;
use Laravel\Ai\Contracts\Agent;

abstract class EventGenerator
{
    /**
     * Run the agent for this generator and return the mapped result.
     */
    public function generate(array $inputs, AiProviderRoute $route, array $config): array
    {
        return $this->runAgentFlow(
            $this->buildAgent($inputs),
            $this->promptText(),
            $route,
            $config,
            $this->schema(),
            fn (array $data): array => $this->mapResult($data, $inputs),
        );
    }

    abstract protected function buildAgent(array $inputs): Agent;

    abstract protected function promptText(): string;

    abstract protected function schema(): AiSchema;

    abstract protected function mapResult(array $data, array $inputs): array;

    /**
     * Run the agent prompt and decode its structured output.
     */
    private function runAgentFlow(Agent $agent, string $promptText, AiProviderRoute $route, array $config, AiSchema $schema, callable $map): array
    {
        $response = $agent->prompt(
            $promptText,
            provider: $route->provider,
            model: $route->model,
            timeout: $config['timeout'],
        );

        return $map($this->decodeStructuredResponse($response->text, $schema));
    }

    /**
     * Decode the agent's structured output into an array and validate it.
     *
     * Handles raw JSON and JSON wrapped in markdown code fences (```json),
     * which some providers return despite structured-output instructions.
     * A null or non-decodable payload is treated as an invalid structured
     * output: a permanent (non-retryable) failure so the generation ends in
     * ERROR instead of silently persisting an empty result.
     */
    private function decodeStructuredResponse(?string $text, AiSchema $schema): array
    {
        if (! is_string($text) || $text === '') {
            throw new \RuntimeException('AI returned an invalid response.');
        }

        $data = json_decode($text, true);

        if (! is_array($data)) {
            // Fallback: try extracting JSON from markdown code fences.
            if (preg_match('/```(?:json)?\s*\n?(.*?)\n?\s*```/s', $text, $m)) {
                $data = json_decode($m[1], true);
            }
        }

        if (! is_array($data)) {
            throw new \RuntimeException('AI returned an invalid response.');
        }

        return $schema->validate($data);
    }
}
