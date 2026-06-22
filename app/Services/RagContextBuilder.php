<?php

namespace App\Services;

use Illuminate\Support\Collection;

class RagContextBuilder
{
    public function __construct(
        protected TokenEstimator $tokenEstimator
    ) {}

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
