<?php

namespace App\Services\Llm;

use App\Exceptions\LlmRateLimitExceededException;
use App\Services\Llm\UsageProvider;
use Throwable;

class LlmRateLimiter
{
    private UsageProvider $usageProvider;

    /**
     * Constructor for LlmRateLimiter.
     *
     * @param UsageProvider $usageProvider The service to get current usage and rate limit.
     */
    public function __construct(UsageProvider $usageProvider)
    {
        $this->usageProvider = $usageProvider;
    }


    /**
     * Checks if the current LLM rate limit is exceeded.
     *
     * @throws LlmRateLimitExceededException If the rate limit is exceeded.
     */
    public function checkLimit(): void
    {
        $currentUsage = $this->usageProvider->getCurrentUsage();
        $rateLimit = $this->usageProvider->getRateLimit();

        if ($currentUsage > $rateLimit) {
            throw new LlmRateLimitExceededException('LLM rate limit exceeded.');
        }
    }
}
