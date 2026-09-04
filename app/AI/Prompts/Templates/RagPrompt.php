<?php

declare(strict_types=1);

namespace App\AI\Prompts\Templates;

use App\AI\Prompts\AbstractPrompt;


final class RagPrompt extends AbstractPrompt
{
    /**
     * Return the system message establishing the assistant's role, citation rules, and fallback behavior.
     *
     * @return string
     */
    public function getSystemMessage():string
    {
        return <<<'PROMPT'
            You are a helpful and accurate AI assistant.
            Use the provided context documents to answer the user's question.

            Rules:
            1. Rely primarily on the provided context to answer questions.
            2. If relevant, cite or reference the context documents appropriately.
            3. If the context does not contain sufficient information to answer the question, clearly state that the provided documents do not contain the answer.
            4. Always respond in the same language as the user's question.
            PROMPT;
    }


    public function getUserMessage():string
    {
        return <<<'PROMPT'
            Context:
            {{context}}

            Question:
            {{question}}
            PROMPT;
    }
}
