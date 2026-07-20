<?php

namespace App\Services;

use Illuminate\Support\Collection;

class RagContextBuilder
{
    /**
     * Create a new RAG context builder instance.
     *
     * @param TokenEstimator $tokenEstimator Service used to estimate token counts.
     */
    public function __construct(
        protected TokenEstimator $tokenEstimator
    ) {}

    /**
     * Build a context string from a collection of documents for RAG usage.
     *
     * The method:
     * - Limits the number of documents based on configuration.
     * - Prepends metadata headers such as document ID and score.
     * - Uses chunk content when available, otherwise falls back to the full document content.
     * - Stops adding documents when the estimated token limit is reached.
     *
     * @param \Illuminate\Support\Collection $documents Collection of ranked documents/chunks.
     *
     * @return string The final context string to be sent to the LLM.
     */
    public function build(Collection $documents): string
    {
        $maxDocuments = config('rag.max_documents', 5);
        $maxTokens = config('rag.max_context_tokens', 1200);
        $separator = config('rag.separator', "\n\n---\n\n");

        $showScores = config('rag.show_scores', true);
        $showDocIds = config('rag.show_doc_ids', true);

        $documents = $documents->take($maxDocuments);

        $contextParts = [];
        $currentTokens = 0;

        foreach ($documents as $index => $item) {

            $doc = $item['document'];
            $score = round($item['score'], 3);

            $header = "[Document" . ($index + 1);

            if ($showDocIds) {
                $header .= "|id:{$doc->id}";
            }

            if ($showScores) {
                $header .= "|score:{$score}";
            }

            $header .= "]";

            // $chunk = $header . "\n" . $doc->content;
            $content = isset($item['chunk']) ? $item['chunk']->content : $doc->content;
            $chunk = $header . "\n" . $content;

            $chunkTokens = $this->tokenEstimator->estimate($chunk);

            if (($currentTokens + $chunkTokens) > $maxTokens) {
                break;
            }

            $contextParts[] = $chunk;
            $currentLength = $chunkTokens;
        }

        return implode($separator, $contextParts);
    }
}
