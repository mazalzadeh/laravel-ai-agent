<?php

namespace App\Services\Llm;

use App\Services\Llm\Contracts\LlmClient;

class MeteredLlmClient implements LlmClient
{
    /**
     * Creates a metered LLM client.
     *
     * @param LlmRateLimiter $rateLimiter Rate limiter checked before each request.
     * @param TokenUsageCalculator $calculator Token usage calculator.
     * @param LlmClient $client Underlying LLM client.
     * @param TokenUsageRecorder $recorder Token usage recorder.
     */
    public function __construct(
        private readonly LlmRateLimiter $rateLimiter,
        private readonly TokenUsageCalculator $calculator,
        private readonly LlmClient $client,
        private readonly TokenUsageRecorder $recorder,
    ) {}



    /**
     * Sends a metered completion request and records its token usage.
     *
     * @param string $prompt Prompt sent to the underlying LLM client.
     * @return array<string, mixed> Response returned by the LLM client.
     *
     * @throws \App\Exceptions\LlmRateLimitExceededException
     */
    public function requestCompletion(string $prompt): array
    {
        $this->rateLimiter->checkLimit();

        $response = $this->client->requestCompletion($prompt);

        $usage = TokenUsage::fromArray($this->calculator->calculate($response));

        $this->recorder->record($usage, ['prompt' => $prompt]);

        return $response;
    }
}
