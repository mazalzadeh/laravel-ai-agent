<?php

declare(strict_types=1);

namespace App\AI\Prompts;

use App\AI\Prompts\Contracts\PromptTemplate;

/**
 * Base class for prompt templates.
 */
abstract class AbstractPrompt implements PromptTemplate
{
    /**
     * Return the optional schema for structured output.
     *
     * @return array<string, mixed>|null
     */
    public function getSchema(): ?array
    {
        return null;
    }
}
