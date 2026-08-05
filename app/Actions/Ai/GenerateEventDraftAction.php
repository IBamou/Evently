<?php

namespace App\Actions\Ai;

use App\Ai\Agents\GenerateEventDraftAgent;
use App\Dto\EventDraftResult;
use App\Dto\SocialMarketing;
use App\Enums\AiGenerationStatus;
use App\Http\Requests\Organizer\Ai\GenerateEventDraftRequest;
use App\Models\Category;
use App\Services\Ai\AiGenerationRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class GenerateEventDraftAction
{
    public function __construct(
        private readonly AiGenerationRecorder $recorder,
    ) {}

    public function execute(GenerateEventDraftRequest $request): JsonResponse
    {
        $config = config('ai-event-copilot');

        if (! $config['enabled']) {
            return response()->json([
                'message' => 'AI Event Copilot is disabled.',
                'error_code' => 'ai_feature_disabled',
            ], Response::HTTP_FORBIDDEN);
        }

        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        $userId = $user->id;

        if ($this->recorder->getMinuteCount($userId) >= $config['per_minute_limit']) {
            return response()->json([
                'message' => 'You have made several AI requests. Try again shortly.',
                'error_code' => 'ai_rate_limited',
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        if ($this->recorder->getDailyCount($userId) >= $config['daily_limit']) {
            return response()->json([
                'message' => "You have reached today's AI-generation limit. You can still complete the event manually.",
                'error_code' => 'ai_daily_limit_reached',
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $validated = $request->validated();
        $categories = array_values(Category::select('id', 'name', 'slug')->get()->toArray());

        $inputHash = hash('sha256', (string) json_encode([
            'brief' => $validated['brief'],
            'audience' => $validated['audience'] ?? null,
            'tone' => $validated['tone'],
            'language' => $validated['language'],
            'event_context' => $validated['event_context'] ?? null,
        ]));

        $startTime = microtime(true);

        try {
            $agent = new GenerateEventDraftAgent(
                brief: $validated['brief'],
                audience: $validated['audience'] ?? null,
                tone: $validated['tone'],
                language: $validated['language'],
                eventContext: $validated['event_context'] ?? [],
                categories: $categories,
            );

            $providerName = $config['provider'];
            $modelName = $config['model'];
            $response = $agent->prompt(
                'Generate event draft',
                provider: $providerName,
                model: $modelName,
                timeout: $config['timeout'],
            );

            /** @var array{title: string, description: string, category_id: int|null, marketing: array{social_post: string, email_subject: string, email_intro: string}, missing_information: string[]} $data */
            $data = json_decode($response->text, true) ?? [];

            $categoryId = $data['category_id'] ?? null;
            $category = null;
            if ($categoryId !== null) {
                $category = collect($categories)->firstWhere('id', $categoryId);
                if ($category === null) {
                    $categoryId = null;
                }
            }

            $result = new EventDraftResult(
                title: $data['title'],
                description: $data['description'],
                category: $category ? ['id' => $category['id'], 'name' => $category['name'], 'slug' => $category['slug']] : null,
                marketing: new SocialMarketing(
                    socialPost: $data['marketing']['social_post'],
                    emailSubject: $data['marketing']['email_subject'],
                    emailIntro: $data['marketing']['email_intro'],
                ),
                missingInformation: array_values($data['missing_information']),
            );

            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            $generation = $this->recorder->record(
                userId: $userId,
                operation: 'generate_draft',
                provider: $providerName,
                model: $modelName,
                status: AiGenerationStatus::SUCCESS,
                language: $validated['language'],
                inputHash: $inputHash,
                latencyMs: $latencyMs,
            );

            $this->recorder->incrementDailyCount($userId);
            $this->recorder->incrementMinuteCount($userId);

            return response()->json([
                'data' => [
                    'generation_id' => $generation->public_id,
                    'operation' => 'generate_draft',
                    'language' => $validated['language'],
                    'suggestions' => $result->toArray(),
                    'missing_information' => $result->missingInformation,
                    'prompt_version' => $config['prompt_version'],
                ],
            ]);

        } catch (\Throwable $e) {
            \Log::error('AI copilot error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);
            $providerName = $config['provider'];
            $modelName = $config['model'];

            $errorCode = match (true) {
                str_contains($e->getMessage(), 'timeout') => 'ai_generation_timeout',
                str_contains($e->getMessage(), 'rate') => 'ai_provider_refused',
                default => 'ai_provider_unavailable',
            };

            $this->recorder->record(
                userId: $userId,
                operation: 'generate_draft',
                provider: $providerName,
                model: $modelName,
                status: AiGenerationStatus::ERROR,
                language: $validated['language'],
                inputHash: $inputHash,
                latencyMs: $latencyMs,
                errorCode: $errorCode,
            );

            $status = match ($errorCode) {
                'ai_generation_timeout' => Response::HTTP_GATEWAY_TIMEOUT,
                'ai_provider_refused' => Response::HTTP_TOO_MANY_REQUESTS,
                default => Response::HTTP_SERVICE_UNAVAILABLE,
            };

            return response()->json([
                'message' => 'The AI assistant is temporarily unavailable. Your event form has not been changed.',
                'error_code' => $errorCode,
            ], $status);
        }
    }
}
