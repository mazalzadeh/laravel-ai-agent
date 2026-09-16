<?php

declare(strict_types=1);

namespace App\AI\CoT;

use App\AI\CoT\DTOs\CoTResult;
use App\AI\CoT\Prompts\CoTPromptTemplate;
use App\Services\AIClientInterface;

class CoTService
{
    private AIClientInterface $client;

    private CoTPromptTemplate $template;

    private CoTOutputParser $parser;

    /**
     * CoTService constructor.
     *
     * @param AIClientInterface $client
     * @param CoTPromptTemplate $template
     * @param CoTOutputParser $parser
     */
    public function __construct(
        AIClientInterface $client,
        CoTPromptTemplate $template,
        CoTOutputParser $parser
    ) {
        $this->client = $client;
        $this->template = $template;
        $this->parser = $parser;
    }

    /**
     * Ask a question using Chain-of-Thought reasoning.
     *
     * @param string $question The user input question.
     * @return CoTResult
     */
    public function ask(string $question): CoTResult
    {
        $prompt = $this->template->render(['question' => $question]);

        return $this->execute($prompt);
    }


    /**
     * Ask a question with provided context using Chain-of-Thought reasoning.
     *
     * @param string $question The user input question.
     * @param string $context The retrieved context information.
     * @return CoTResult
     */
    public function askWithContext(string $question, string $context): CoTResult
    {
        $prompt = $this->template->render([
            'question' => $question,
            'context' => $context
        ]);

        return $this->execute($prompt);
    }

    /**
     * Executes the chat call to the AIClient and parses response into CoTResult.
     *
     * @param string $prompt
     * @return CoTResult
     */
    private function execute(string $prompt): CoTResult
    {
        $messages = [
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ];

        $response = $this->client->chat($messages);

        $rawContent = $response['content'] ?? $response['message']['content'] ?? '';

        return $this->parser->parse((string)$rawContent);
    }
}
