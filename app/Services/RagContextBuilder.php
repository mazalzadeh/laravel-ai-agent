<?php

namespace App\Services;

use Illuminate\Support\Collection;

class RagContextBuilder
{
    public function build(Collection $documents): string
    {
        return $documents
            ->map(function ($item, $index) {
                $doc = $item['document'];
                $score = round($item['score'], 3);

                return "Document" . ($index + 1) . "|score:{$score}]\n" . $doc->content;
            })->implode("\n\n---\n\n");
    }
}
