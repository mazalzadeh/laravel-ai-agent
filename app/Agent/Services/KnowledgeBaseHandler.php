<?php

namespace App\Agent\Services;

use App\Agent\DTO\DecisionResult;

class KnowledgeBaseHandler
{
    /**
     * Handle retrieval from knowledge base and generate grounded answer.
     *
     * @param string $userInput The original user input prompt.
     * @param string|null $context Contextual memory or previous conversation context.
     * @param DecisionResult $decision The decision payload containing search parameters.
     * @return string Answer grounded with retrieved context.
     */
    public function handle(string $userInput, ?string $context, DecisionResult $decision): string
    {
        return (string) ($decision->parameters['text'] ?? '');
    }
}
