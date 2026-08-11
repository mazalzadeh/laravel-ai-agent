<?php

declare(strict_types=1);

namespace App\AI\Prompts;

use App\AI\Prompts\Contracts\PromptTemplate;

/**
 * Renders dynamic prompt templates by replacing placeholders with runtime values.
 */
final class PromptRenderer
{
    /**
     * Render a prompt template into an array of chat messages.
     *
     * Supported placeholder format:
     * - {{variable_name}}
     *
     * @param PromptTemplate $template
     * @param array<string, scalar|null> $variables
     *
     * @return array<int, array{role: string, content: string}>
     */
    public function render(PromptTemplate $template, array $variables): array
    {
        return [
            [
                'role' => 'system',
                'content' => $this->replaceVariables($template->getSystemMessage(), $variables)
            ],
            [
                'role' => 'user',
                'content' => $this->replaceVariables($template->getUserMessage(), $variables)
            ]
        ];
    }


    /**
     * Replace placeholders inside a message string.
     *
     * @param string $message
     * @param array<string, scalar|null> $variables
     *
     * @return string
     */
    public function replaceVariables(string $message, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $message = str_replace('{{' . $key . '}}', $this->normalizeValue($value), $message);
        }
        return $message;
    }

    /**
     * Normalize variable values into strings.
     *
     * @param scalar|null $value
     *
     * @return string
     */
    public function normalizeValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string)$value;
    }
}
