<?php

namespace App\Services\Llm;

/**
 * Extracts token usage information from LLM responses.
 */
final class TokenUsageExtractor
{
    /**
     * Extracts token usage from an OpenAI-style response.
     *
     * A response without a usage field is represented by zero token usage.
     *
     * @param array{
     *     usage?: array{
     *         prompt_tokens?: int,
     *         completion_tokens?: int,
     *         total_tokens?: int
     *     }
     * } $response Response returned by the LLM provider.
     *
     * @return TokenUsage
     */
    public function extract(array $response): TokenUsage
    {
        return TokenUsage::fromArray($response['usage'] ?? []);
    }
}
