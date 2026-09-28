<?php

namespace App\Agent\Services;

use App\Agent\DTO\DecisionResult;

class DirectAnswerHandler
{
    /**
     * Handle direct answer response execution.
     *
     * @param string $userInput The original user input prompt.
     * @param string|null $context Contextual memory or previous conversation context.
     * @param DecisionResult $decision The decision payload emitted by the decision model.
     * @return string Final direct answer string.
     */
    public function handle(string $userInput, ?string $context, DecisionResult $decision): string
    {
        return (string)($decision->parameters['text'] ?? '');
    }
}
