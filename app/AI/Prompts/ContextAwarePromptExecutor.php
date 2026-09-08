<?php

declare(strict_types=1);

namespace App\AI\Prompts;

use App\AI\Prompts\Contracts\PromptTemplate;
use App\Services\AIService;

class ContextAwarePromptExecutor
{
    public const DEFAULT_FALLBACK_CONTEXT = 'No relevant context found.';

    /**
     * Create a new context-aware prompt executor instance.
     *
     * @param PromptRenderer $renderer
     * @param AIService $aiService
     */
    public function __construct(
        protected PromptRenderer $renderer,
        protected AIService $aiservice
    ) {}


    /**
     * Execute a prompt template with the provided context and query.
     *
     * @param PromptTemplate $prompt The prompt template to render.
     * @param string $context The retrieved context text.
     * @param string $query The user query/question.
     * @param array<string, mixed> $extraVariables Additional template variables if any.
     * @param string $fallbackContext Fallback string to use if context is empty.
     *
     * @return PromptExecutionResult The execution result DTO.
     */
    public function execute(
        PromptTemplate $prompt,
        string $context,
        string $query,
        array $extraVariables = [],
        string $fallbackContext = self::DEFAULT_FALLBACK_CONTEXT
    ): PromptExecutionResult {

        $resolvedContext = trim($context) === '' ? $fallbackContext : $context;

        $variables = array_merge($extraVariables, [
            'context' => $resolvedContext,
            'question' => $query,
        ]);

        $messages = $this->renderer->render($prompt, $variables);

        $content = $this->aiservice->chat($messages);

        return new PromptExecutionResult(
            content: $content,
            metadata: [
                'has_context' => trim($context) !== '',
            ]
        );
    }
}
