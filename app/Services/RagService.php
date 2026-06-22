<?php

namespace App\Services;

use App\Services\AIService;
use App\Services\VectorSimilarityService;

class RagService
{
    public function __construct(
        protected AIService $aiservice,
        protected VectorSimilarityService $vectorService,
        protected RagContextBuilder $contextBuilder
    ) {}

    public function answer(string $question, int $limit = 5): array
    {
        //1- generate embedding for question
        $queryEmbedding = $this->aiservice->embed($question);

        //2- retrieve similar documents
        $similarDocuments = $this->vectorService->findMostSimilar($queryEmbedding, $limit);

        //3- build context (temporary - later refactor)
        /*$context = collect($similarDocuments)->map(function ($item) {
            return $item['document']->content;
        })->implode("\n\n");*/
        $context = $this->contextBuilder->build($similarDocuments);

        //4- build prompr (temporary - later refactor)
        $messages = [
            [
                'role' => 'system',
                'content' =>
                'You are a helpful assistant answering questions using the provided documents. Cite documents when possible.'
            ],
            [
                'role' => 'user',
                'content' => "Use the following documents to answer the question.
                {$context}

                Question:
                {$question}"
            ]
        ];

        //5- generate answer
        $answer = $this->aiservice->chat($messages);

        //6- preaper sources
        /*$sources = collect($similarDocuments)
            ->map(function ($item) {
                return [
                    'id' => $item['document']->id,
                    'content' => $item['document']->content,
                    'score' => $item['score'],
                ];
            })->values();*/
        //6- preaper sources
        $sources = collect($similarDocuments)
            ->map(fn($item) => [
                'document_id' => $item['document']->id,
                'chunk_id' => $item['chunk']->id ?? null,
                'content' => $item['chunk']->content ?? $item['document']->content,
                'score' => $item['score'],
            ])->values();

        return [
            'question' => $question,
            'answer' => $answer,
            'sources' => $sources,
        ];
    }
}
