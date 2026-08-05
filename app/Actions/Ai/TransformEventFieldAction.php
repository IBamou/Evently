<?php

namespace App\Actions\Ai;

use App\Ai\Agents\TransformEventFieldAgent;
use App\Dto\FieldTransformResult;
use App\Enums\AiGenerationStatus;
use App\Http\Requests\Organizer\Ai\TransformEventFieldRequest;
use App\Services\Ai\AiGenerationRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class TransformEventFieldAction
{
    public function __construct(
        private readonly AiGenerationRecorder $recorder,
    ) {}

    public function execute(TransformEventFieldRequest $request): JsonResponse
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

        $inputHash = hash('sha256', (string) json_encode([
            'field' => $validated['field'],
            'operation' => $validated['operation'],
            'content' => $validated['content'],
            'tone' => $validated['tone'] ?? null,
            'target_language' => $validated['target_language'] ?? null,
            'event_context' => $validated['event_context'] ?? null,
        ]));

        $startTime = microtime(true);

        try {
            $agent = new TransformEventFieldAgent(
                field: $validated['field'],
                operation: $validated['operation'],
                content: $validated['content'],
                tone: $validated['tone'] ?? null,
                targetLanguage: $validated['target_language'] ?? null,
                eventContext: $validated['event_context'] ?? [],
            );

            $providerName = $config['provider'];
            $modelName = $config['model'];
            $response = $agent->prompt(
                'Transform field',
                provider: $providerName,
                model: $modelName,
                timeout: $config['timeout'],
            );

            /** @var array{content: string, language: string, warnings: string[]} $data */
            $data = json_decode($response->text, true) ?? [];

            $result = new FieldTransformResult(
                content: $data['content'],
                language: $data['language'],
                warnings: array_values($data['warnings']),
            );

            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            $generation = $this->recorder->record(
                userId: $userId,
                operation: $validated['operation'],
                provider: $providerName,
                model: $modelName,
                status: AiGenerationStatus::SUCCESS,
                language: $result->language,
                inputHash: $inputHash,
                latencyMs: $latencyMs,
            );

            $this->recorder->incrementDailyCount($userId);
            $this->recorder->incrementMinuteCount($userId);

            return response()->json([
                'data' => [
                    'generation_id' => $generation->public_id,
                    'operation' => $validated['operation'],
                    'field' => $validated['field'],
                    'content' => $result->content,
                    'language' => $result->language,
                    'warnings' => $result->warnings,
                    'prompt_version' => $config['prompt_version'],
                ],
            ]);

        } catch (\Throwable $e) {
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
                operation: $validated['operation'],
                provider: $providerName,
                model: $modelName,
                status: AiGenerationStatus::ERROR,
                language: $validated['target_language'] ?? 'en',
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
