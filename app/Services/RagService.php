<?php

declare(strict_types=1);

namespace App\Services;

use App\AI\Prompts\ContextAwarePromptExecutor;
use App\AI\Context\ContextInjectionService;
use App\AI\Context\RetrievedDocument;
use App\AI\Prompts\PromptRenderer;
use App\AI\Prompts\Templates\RagPrompt;
use Illuminate\Support\Collection;
use App\Services\AIService;

class RagService
{
   /**
     * Create a new RAG service instance.
     *
     * @param AIService $aiService
     * @param ContextInjectionService $contextService
     * @param ContextAwarePromptExecutor $executor
     * @param RagPrompt $ragPrompt
     */
    public function __construct(
        protected AIService $aiservice,
        protected ContextInjectionService $contextService,
        protected ContextAwarePromptExecutor $executor,
        protected RagPrompt $ragPrompt
    ){}


    /**
     * Orchestrate the RAG process to answer a given question.
     *
     * @param string $question The user's input query.
     * @param int $limit Maximum number of documents to retrieve.
     *
     * @return array{question: string, answer: array, sources: \Illuminate\Support\Collection}
     */
    public function answer(string $question, int $limit = 5): array
    {
        // 1. Retrieve and format context using ContextInjectionService
        $retrievedContext = $this->contextService->inject($question, $limit);

       // 2. Execute prompt with dynamic context and fallback via ContextAwarePromptExecutor
        $result=$this->executor->execute(
            prompt:$this->ragPrompt,
            context:$retrievedContext->content,
            query:$question,
            fallbackContext:'No relevant context found.'
        );

        // 3. Map retrieved documents to sources array
        $sources = $retrievedContext->documents
            ->map(fn(RetrievedDocument $doc) => [
                'document_id' => $doc->documentId,
                'chunk_id' => $doc->chunkId,
                'content' => $doc->content,
                'score' => $doc->score,
            ])
            ->values();

        return [
            'question' => $question,
            'answer' => $result->content,
            'sources' => $sources,
        ];
    }

   /**
     * Ask a question and receive a structured JSON response.
     *
     * @param string $question
     *
     * @return array<string, mixed>
     */
    public function ask(string $question): array
    {
        $retrievedContext = $this->contextService->inject($question);

        $schema = [
            "type" => "object",
            "properties" => [
                "answer" => ["type" => "string"],
                "source_ids" => ["type" => "array", "items" => ["type" => "integer"]],
                "confidence" => ["type" => "number"]
            ],
            "required" => ["answer", "source_ids"]
        ];

        $contextContent = $retrievedContext->isEmpty()
            ? 'No relevant context found.'
            : $retrievedContext->content;

        $prompt = "Context:\n{$contextContent}\n\nQuestion:\n{$question}";

        return $this->aiservice->structured($prompt, $schema);
    }
}
