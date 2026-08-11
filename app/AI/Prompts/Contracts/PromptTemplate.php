<?php

declare(strict_types=1);

namespace App\AI\Prompts\Contracts;

/**
 * Contract for dynamic prompt templates.
 */
interface PromptTemplate
{
    /**
     * Return the system message of the prompt.
     *
     * @return string
     */
    public function getSystemMessage(): string;

    /**
     * Return the user message of the prompt.
     *
     * @return string
     */
    public function getUserMessage(): string;

    /**
     * Return the optional schema of the prompt.
     *
     * @return array<string, mixed>|null
     */
    public function getSchema(): ?array;
}
