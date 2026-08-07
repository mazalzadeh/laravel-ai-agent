<?php

namespace App\Services\Llm;

class TokenUsageCalculator
{
    /**
     * Calculates tokens for the given response.
     *
     * @param array $response
     * @return array
     */
    public function calculate(array $response): array
    {
        return $response['usage'] ?? ['prompt_tokens' => 0, 'completion_tokens' => 0];
    }
}
