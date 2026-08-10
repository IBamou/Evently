<?php

namespace App\Services\Ai\GenerationServices;

use App\Ai\Agents\GenerateEventMarketingAgent;
use App\DTOs\MarketingResult;
use Laravel\Ai\Contracts\Agent;

class EventMarketingGenerator extends EventGenerator
{
    /**
     * @param  array<string, mixed>  $inputs
     */
    protected function buildAgent(array $inputs): Agent
    {
        return new GenerateEventMarketingAgent(
            language: $inputs['language'],
            tone: $inputs['tone'],
            eventContext: $inputs['event_context'] ?? [],
        );
    }

    protected function promptText(): string
    {
        return 'Generate marketing content';
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $inputs
     * @return array<string, mixed>
     */
    protected function mapResult(array $data, array $inputs): array
    {
        $result = new MarketingResult(
            socialPost: $data['social_post'] ?? '',
            emailSubject: $data['email_subject'] ?? '',
            emailIntro: $data['email_intro'] ?? '',
        );

        return $result->toArray();
    }
}
