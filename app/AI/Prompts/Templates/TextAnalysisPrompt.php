<?php

declare(strict_types=1);

namespace App\AI\Prompts\Templates;

use App\AI\Prompts\AbstractPrompt;


final class TextAnalysisPrompt extends AbstractPrompt
{
    /**
     * Return the system message.
     *
     * @return string
     */
    public function getSystemMessage(): string
    {
        return 'You are an expert text analyst.';
    }

    /**
     * Return the user message.
     *
     * @return string
     */
    public function getUserMessage(): string
    {
        return 'Analyze the following text carefully: {{text}}';
    }
}
