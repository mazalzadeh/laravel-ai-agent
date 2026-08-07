<?php

namespace App\Services\Llm;

use App\Services\Llm\Contracts\LlmClient;

class MeteredLlmClient implements LlmClient
{
    private LlmRateLimiter $rateLimiter;
    private TokenUsageCalculator $calculator;
    private LlmClient $client;


    /**
     * @param LlmRateLimiter $rateLimiter
     * @param TokenUsageCalculator $calculator
     * @param LlmClient $client
     */
    public function __construct(
        LlmRateLimiter $rateLimiter,
        TokenUsageCalculator $calculator,
        LlmClient $client
    ) {
        $this->rateLimiter = $rateLimiter;
        $this->calculator = $calculator;
        $this->client = $client;
    }


    public function requestCompletion(string $prompt): array
    {
        $this->rateLimiter->checkLimit();

        $response = $this->client->requestCompletion($prompt);

        $usage = $this->calculator->calculate($response);

        return $response;
    }
}
