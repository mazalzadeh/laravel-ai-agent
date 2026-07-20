<?php

namespace App\Services;

use App\Models\DocumentChunk;
use App\Services\AIService;
use App\Services\VectorSimilarityService;
use Ramsey\Collection\Collection;

class RagService
{
    /**
     * Create a new RAG service instance.
     *
     * @param AIService $aiservice Service responsible for AI interactions such as
     *                             text generation and embeddings.
     * @param VectorSimilarityService $vectorService Service used to compare vectors
     *                                               and retrieve relevant documents.
     * @param RagContextBuilder $contextBuilder Builder for constructing the final
     *                                          RAG context from retrieved documents.
     * @param EmbeddingCacheService $embeddingCache Cache layer for storing and
     *                                             reusing embeddings.
     */
    public function __construct(
        protected AIService $aiservice,
        protected VectorSimilarityService $vectorService,
        protected RagContextBuilder $contextBuilder,
        protected EmbeddingCacheService $embeddingCache
    ) {}

    /**
     * Orchestrate the RAG process to answer a given question.
     *
     * This method coordinates several services to:
     * 1. Embed the user's question (with caching).
     * 2. Retrieve relevant document chunks from the vector store.
     * 3. Construct a limited-length context for the LLM.
     * 4. Generate a final answer and return it along with cited sources.
     *
     * @param string $question The user's input query.
     * @param int $limit Maximum number of documents to retrieve initially.
     *
     * @return array{question: string, answer: array, sources: \Illuminate\Support\Collection}
     */
    public function answer(string $question, int $limit = 5): array
    {
        // 1. Generate embedding for the question (utilizing cache-aside pattern)
        $queryEmbedding = $this->embeddingCache->remember(
            $question,
            fn() => $this->aiservice->embed($question)
        );

        // 2. Retrieve similar documents based on vector distance
        $similarDocuments = $this->vectorService->findMostSimilar($queryEmbedding, $limit);

        // 3. Build a structured context string from retrieved chunks
        $context = $this->contextBuilder->build($similarDocuments);

        // 4. Construct the prompt with instructions and context
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

        // 5. Call the AI service to generate the response
        $answer = $this->aiservice->chat($messages);

        // 6. Prepare the sources list for transparency and citation
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

    /**
     * Ask a question and receive a structured JSON response.
     *
     * This method retrieves relevant context and then forces the AI model
     * to follow a specific JSON schema for its response, ensuring
     * programmatic consistency for fields like answer, source IDs, and confidence.
     *
     * @param string $question The user's query.
     *
     * @return array{answer: string, source_ids: array<int>, confidence: float}
     *               The structured response from the AI service.
     */
    public function ask(string $question): array
    {
        // 1. Retrieve relevant document chunks
        $documents = $this->retrieve($question);

        // 2. Build the context string for the prompt
        $contextString = $this->contextBuilder->build($documents);

        // 3. Define the JSON schema for the AI's output
        $schema = [
            "type" => "object",
            "properties" => [
                "answer" => ["type" => "string"],
                "source_ids" => ["type" => "array", "items" => ["type" => "integer"]],
                "confidence" => ["type" => "number"]
            ],
            "required" => ["answer", "source_ids"]
        ];

        // 4. Construct the prompt
        $prompt = "Context:\n{$contextString}\n\nQuestion:\n{$question}";

        // 5. Request a structured response from the AI service
        return $this->aiservice->structured($prompt, $schema);
    }

    /**
     * Retrieve relevant document chunks using simple keyword matching.
     *
     * This method:
     * - Extracts normalized keywords from the input question.
     * - Loads all document chunks with their parent documents.
     * - Scores each chunk based on keyword matches in the chunk content
     *   and document title.
     * - Filters out non-matching results.
     * - Returns the highest-ranked matches up to the configured limit.
     *
     * @param string $question The user's input query.
     *
     * @return \Illuminate\Support\Collection<int, array{
     *     document: \App\Models\Document,
     *     chunk: \App\Models\DocumentChunk,
     *     score: float|int
     * }>
     */
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
