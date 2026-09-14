<?php

declare(strict_types=1);

namespace App\AI\CoT\Prompts;

class CoTPromptTemplate
{
    /**
     * Render the prompt template with given variables.
     *
     * Supported variables in $data:
     * - 'question' (string, required): The user's input question.
     * - 'context' (string, optional): Retrieved context information (RAG).
     *
     * @param array<string, mixed> $data Variables to substitute into the template.
     * @return string The compiled prompt text.
     */
    public function render(array $data): string
    {
        $question = $data['question'] ?? '';
        $context = $data['context'] ?? null;

        $prompt = "You are an advanced AI assistant. You must solve complex requests using a hidden reasoning process.\n\n";

        $prompt .= "INSTRUCTIONS:\n";
        $prompt .= "1. First, think step-by-step, analyze the logic, and break down the problem inside <thought> and </thought> tags.\n";
        $prompt .= "2. Second, provide the final, clean, and direct answer for the user inside <answer> and </answer> tags.\n";
        $prompt .= "3. Never reveal or mention the thought process outside of the thought tags. The user must only see the clean answer.\n\n";

        if ($context !== null && trim((string)$context) !== '') {
            $prompt .= "Context Information:\n" . trim((string)$context) . "\n\n";
        }

        $prompt .= "User Question / Request:\n" . trim($question);

        return $prompt;
    }
}
