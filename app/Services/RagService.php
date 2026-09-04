<?php

namespace App\Services;

use App\AI\Context\ContextInjectionService;
use App\AI\Context\RetrievedDocument;
use App\Services\AIService;

class RagService
{
    /**
     * Create a new RAG service instance.
     */
    public function __construct(
        protected AIService $aiservice,
        protected ContextInjectionService $contextService
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

        // 2. Construct the prompt messages

        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a helpful assistant answering questions using the provided documents. Cite documents when possible.'
            ],
            [
                'role' => 'user',
                'content' => "Use the following documents to answer the question.\n" .
                    "{$retrievedContext->content}\n\n" .
                    "Question:\n{$question}"
            ]
        ];

        // 3. Call AI service
        $answer = $this->aiservice->chat($messages);

        // 4. Map retrieved documents to sources array
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
            'answer' => $answer,
            'sources' => $sources,
        ];
    }

    /**
     * Ask a question and receive a structured JSON response.
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

        $prompt = "Context:\n{$retrievedContext->content}\n\nQuestion:\n{$question}";

        return $this->aiservice->structured($prompt, $schema);
    }
}
