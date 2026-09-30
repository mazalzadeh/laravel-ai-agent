<?php

namespace App\Agent\Services;

use App\Services\AIClientInterface;
use App\Agent\DTO\DecisionResult;
use App\Agent\Parsers\DecisionOutputParser;
use App\Agent\Prompts\DecisionPromptTemplate;

class DecisionService
{
    /**
     * @param AIClientInterface $aiClient
     * @param DecisionPromptTemplate $promptTemplate
     * @param DecisionOutputParser $outputParser
     */
    public function __construct(
        private AIClientInterface $aiclient,
        private DecisionPromptTemplate $promptTemplate,
        private DecisionOutputParser $outputParser
    ) {}


    /**
     * Decides the next course of action based on user input and optional context.
     *
     * @param string $userInput The raw input from the user.
     * @param string|null $context Optional context for the decision.
     * @return DecisionResult
     */
    public function decide(
        string $userInput,
        ?string $context = null,
        ?string $stepHistory = null
    ): DecisionResult {
        // 1. Render prompts
        $systemPrompt = $this->promptTemplate->renderSystemPrompt();
        $userPrompt = $this->promptTemplate->renderUserPrompt($userInput, $context, $stepHistory);

        // 2. Prepare chat messages
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        // 3. Request chat completion
        $response = $this->aiclient->chat($messages);

        // 4. Extract raw text from response array
        $rawText = $this->extractContent($response);

        // 5. Parse and return final DecisionResult
        return $this->outputParser->parse($rawText);
    }


    /**
     * Extract string content from an AI client response payload.
     *
     * Supports direct content, message content, standard choices,
     * and provider responses nested under a data key.
     *
     * @param array<string, mixed> $response The AI client response payload.
     * @return string The extracted content, or an empty string when unavailable.
     */
    private function extractContent(array $response): string
    {
        if (array_key_exists('content', $response) && is_string($response['content'])) {
            return $response['content'];
        }

        if (isset($response['message']['content']) && is_string($response['message']['content'])) {
            return $response['message']['content'];
        }

        if (
            isset($response['choices'][0]['message']['content'])
            && is_string($response['choices'][0]['message']['content'])
        ) {
            return $response['choices'][0]['message']['content'];
        }

        if (
            isset($response['data']['choices'][0]['message']['content'])
            && is_string($response['data']['choices'][0]['message']['content'])
        ) {
            return $response['data']['choices'][0]['message']['content'];
        }

        return '';
    }
}
