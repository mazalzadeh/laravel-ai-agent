<?php

namespace App\Services\Llm;

class UsageProvider
{
    /**
     * Gets the current LLM usage.
     * This is a placeholder and should be implemented later.
     */
    public function getCurrentUsage(): int
    {
        // Placeholder implementation
        return 0;
    }

    /**
     * Gets the LLM rate limit.
     * This is a placeholder and should be implemented later.
     */
    public function getRateLimit(): int
    {
        // Placeholder implementation
        return 1000; // Default limit, will be overridden by test
    }
}
