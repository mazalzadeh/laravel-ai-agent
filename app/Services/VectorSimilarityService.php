<?php

namespace App\Services;

use App\Models\Document;

class VectorSimilarityService
{
    public function cosineSimilarity(array $a, array $b): float
    {
        if (count($a) !== count($b)) {
            throw new \InvalidArgumentException('Vector must have the same lenght');
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($a as $i => $value) {
            $dotProduct += $value * $b[$i];
            $normA = $value * $value;
            $normB = $b[$i] * $b[$i];
        }

        if ($normA == 0 || $normB == 0) {
            return 0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }

    public function findMostSimilar(array $queryEmbedding, int $limit=5)
    {
        $documents= Document::all();

        $scored=$documents->map(function($doc)use($queryEmbedding){
            $score=$this->cosineSimilarity(
                $queryEmbedding,
                $doc->embedding
            );

            return['document'=>$doc,'score'=>$score];
        });

        return $scored->sortByDesc('score')->take($limit)->values();
    }
}
