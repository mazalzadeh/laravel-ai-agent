<?php

declare(strict_types=1);

namespace App\AI\Prompts;

/**
 * Data Transfer Object representing the result of a prompt execution.
 */
final readonly class PromptExecutionResult
{
    /**
     * Create a new prompt execution result instance.
     *
     * @param string $content The generated content or response from the AI.
     * @param array<string, mixed> $metadata Additional execution metadata (e.g., token usage, model).
     */
    public function __construct(
        public string $content,
        public array $metadata = []
    ) {}


    /**
     * Determine if the response content is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return trim($this->content) === '';
    }

    /**
     * Check if context was injected during execution.
     */
    public function hasContext(): bool
    {
        return (bool)($this->metadata['has_context'] ?? false);
    }
}
