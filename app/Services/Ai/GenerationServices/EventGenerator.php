<?php

namespace App\Services\Ai\GenerationServices;

use App\DTOs\Ai\AiProviderRoute;
use Laravel\Ai\Contracts\Agent;

abstract class EventGenerator
{
    /**
     * Run the agent for this generator and return the mapped result.
     *
     * @param  array<string, mixed>  $inputs
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public function generate(array $inputs, AiProviderRoute $route, array $config): array
    {
        return $this->runAgentFlow(
            $this->buildAgent($inputs),
            $this->promptText(),
            $route,
            $config,
            fn (array $data): array => $this->mapResult($data, $inputs),
        );
    }

    /**
     * @param  array<string, mixed>  $inputs
     */
    abstract protected function buildAgent(array $inputs): Agent;

    abstract protected function promptText(): string;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $inputs
     * @return array<string, mixed>
     */
    abstract protected function mapResult(array $data, array $inputs): array;

    /**
     * Run the agent prompt and decode its structured output.
     *
     * @param  array<string, mixed>  $config
     * @param  callable(array<string, mixed>): array<string, mixed>  $map
     * @return array<string, mixed>
     */
    private function runAgentFlow(Agent $agent, string $promptText, AiProviderRoute $route, array $config, callable $map): array
    {
        $response = $agent->prompt(
            $promptText,
            provider: $route->provider,
            model: $route->model,
            timeout: $config['timeout'],
        );

        return $map($this->decodeStructuredResponse($response->text));
    }

    /**
     * Decode the agent's structured output into an array.
     *
     * A null or non-JSON payload is treated as an invalid structured output:
     * a permanent (non-retryable) failure so the generation ends in ERROR
     * instead of silently persisting an empty result.
     *
     * @return array<string, mixed>
     */
    private function decodeStructuredResponse(?string $text): array
    {
        if (! is_string($text) || $text === '') {
            throw new \RuntimeException('AI returned an invalid response.');
        }

        $data = json_decode($text, true);

        if (! is_array($data)) {
            throw new \RuntimeException('AI returned an invalid response.');
        }

        return $data;
    }
}
