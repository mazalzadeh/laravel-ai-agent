<?php

namespace App\Agent\Prompts;

use App\Agent\Enums\AgentAction;

class DecisionPromptTemplate
{
    /**
     * Render the system prompt that explains available actions and JSON contract.
     *
     * @return string
     */
    public function renderSystemPrompt(): string
    {
        return <<<PROMPT
                    You are an intelligent AI decision-maker.

                    Your task is to decide how to handle the user's request by choosing exactly ONE of the following actions:

                    {$this->renderActionOptions()}

                    Respond strictly in the following JSON format (no extra text, no markdown):

                    {
                        "action": "ACTION_VALUE",
                        "parameters": { ... },
                        "reasoning": "One short sentence explaining your decision."
                    }
                PROMPT;
    }

    /**
     * Render the user prompt with the main input and optional context.
     *
     * @param string $userInput The user's raw message.
     * @param string|null $context Optional helpful context about the current state.
     * @return string
     */
    public function renderUserPrompt(string $userInput, ?string $context = null): string
    {
        $prompt = "User input:{$userInput}";

        if ($context !== null && trim($context) !== '') {
            $prompt .= "\n\nContext: {$context}";
        }

        return $prompt;
    }

    /**
     * Generate a bullet list of action keys and their descriptions.
     *
     * @return string
     */
    protected function renderActionOptions(): string
    {
        $lines = [];

        foreach (AgentAction::cases() as $action) {
            $lines[] = "- {$action->value}: {$action->description()}";
        }

        return implode(PHP_EOL, $lines);
    }
}
