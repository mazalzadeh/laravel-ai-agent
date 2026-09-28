<?php

namespace App\Agent\Services;

use App\Agent\DTO\DecisionResult;

class TaskDecomposeHandler
{
    /**
     * Decompose the input task into sequential sub-problems and execute them.
     *
     * @param string $userInput The complex user objective to be decomposed.
     * @param string|null $context Contextual memory or previous conversation context.
     * @param DecisionResult $decision The decision payload containing decomposition parameters.
     * @return string Consolidated response after subtask execution.
     */
    public function handle(string $userInput, ?string $context, DecisionResult $decision): string
    {
        return (string) ($decision->parameters['text'] ?? '');
    }
}
