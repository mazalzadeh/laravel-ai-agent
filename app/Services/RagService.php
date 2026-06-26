<?php

namespace App\Services;

use App\Models\DocumentChunk;
use App\Services\AIService;
use App\Services\VectorSimilarityService;
use Ramsey\Collection\Collection;

class RagService
{
    public function __construct(
        protected AIService $aiservice,
        protected VectorSimilarityService $vectorService,
        protected RagContextBuilder $contextBuilder,
        protected EmbeddingCacheService $embeddingCache
    ) {}

    public function answer(string $question, int $limit = 5): array
    {
        //1- generate embedding for question
        /*$queryEmbedding = $this->aiservice->embed($question);*/
        $queryEmbedding = $this->embeddingCache->remember(
            $question,
            fn() => $this->aiservice->embed($question)
        );

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

    public function ask(string $question): array
    {
        $documents = $this->retrieve($question);
        $contextString = $this->contextBuilder->build($documents);

        $schema = [
            "type" => "object",
            "properties" => [
                "answer" => ["type" => "string"],
                "source_ids" => ["type" => "array", "items" => ["type" => "integer"]],
                "confidence" => ["type" => "number"]
            ],
            "required" => ["answer", "source_ids"]
        ];

        $prompt = "Context:\n{$contextString}\n\nQuestion:\n{$question}";

        return $this->aiservice->structured($prompt, $schema);
    }

    private function retrieve(string $question): Collection
    {
        $maxDocuments = config('rag.max_documents', 5);

        $keywords = collect(preg_split('/\s+/', trim($question)))
            ->filter(fn($word) => mb_strlen($word) >= 3)
            ->map(fn($word) => mb_strtolower($word))
            ->unique()
            ->values();

        $chunks = DocumentChunk::query()
            ->with('document')
            ->get()
            ->map(function (DocumentChunk $chunk) use ($keywords) {
                $haystack = mb_strtolower(
                    ($chunk->content ?? '') . ' ' . ($chunk->document->title ?? '')
                );

                $score = $keywords->reduce(function (float $carry, string $keyword) use ($haystack) {
                    return str_contains($haystack, $keyword) ? $carry + 1 : $carry;
                }, 0);

                return [
                    'document' => $chunk->document,
                    'chunk' => $chunk,
                    'score' => $score,
                ];
            })
            ->filter(fn(array $item) => $item['score'] > 0)
            ->sortByDesc('score')
            ->take($maxDocuments)
            ->values();
    }
}
