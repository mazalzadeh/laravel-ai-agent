<?php

namespace App\Services;

class DocumentChunker
{
    public function chunk(string $text): array
    {
        $chunkSize = config('rag.chunk_size', 1000);
        $overlap = config('rag.chunk_overlap', 200);

        $text = trim($text);

        if ($text === '') {
            return [];
        }

        if (strlen($text) <= $chunkSize) {
            return [$text];
        }

        $chunks = [];
        $start = 0;
        $length = strlen($text);

        while ($start < $length) {
            $chunk = substr($text, $start, $chunkSize);
            $chunks[] = trim($chunk);
            $start += ($chunkSize - $overlap);
        }

        return array_values(array_filter($chunks));
    }
}
