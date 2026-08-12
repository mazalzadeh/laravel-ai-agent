<?php

declare(strict_types=1);

namespace App\AI\Prompts\Templates;

use App\AI\Prompts\AbstractPrompt;


final class DocumentAnalysisPrompt extends AbstractPrompt
{
    /**
     * Return the system message that establishes the model's role and constraints.
     *
     * @return string
     */
    public function getSystemMessage(): string
    {
        return 'You are an expert document analyst. Analyze the provided document accurately and return only valid JSON.';
    }

    /**
     * Return the user message containing the dynamic document placeholder.
     *
     * @return string
     */
    public function getUserMessage(): string
    {
        return <<<'PROMPT'
            Analyze the following document.
            Document:
            {{document}}

            Return a consice summary and the most appropriate category.
            PROMPT;
    }

    /**
     * Return the schema expected from the structured model response.
     *
     * @return array{
     *     required: list<string>,
     *     properties: array<string, array{type: string}>
     * }
     */
    public function getSchema(): array
    {
        return [
            'required' => [
                'summary',
                'category',
            ],
            'properties' => [
                'summary' => [
                    'type' => 'string',
                ],
                'category' => [
                    'type' => 'string',
                ],
            ],
        ];
    }
}
