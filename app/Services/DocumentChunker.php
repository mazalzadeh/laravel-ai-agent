<?php

namespace App\Services;

class DocumentChunker
{
    /**
     * Split a long text into smaller overlapping segments (chunks).
     *
     * This is a core utility for RAG (Retrieval-Augmented Generation) pipelines.
     * It breaks down large documents into manageable pieces while maintaining
     * context through a configurable overlap between consecutive chunks.
     *
     * @param string $text The raw document text to be chunked.
     *
     * @return array<int, string> An array of text segments, excluding empty results.
     */
    public function chunk(string $text): array
    {
        // Retrieve chunking parameters from the application configuration
        $chunkSize = config('rag.chunk_size', 1000);
        $overlap = config('rag.chunk_overlap', 200);

        $text = trim($text);

        // Guard against empty input
        if ($text === '') {
            return [];
        }

        // If the text is already smaller than the target size, return it as a single chunk
        if (strlen($text) <= $chunkSize) {
            return [$text];
        }

        $chunks = [];
        $start = 0;
        $length = strlen($text);

        // Iterate through the text, extracting substrings based on size and overlap
        while ($start < $length) {
            $chunk = substr($text, $start, $chunkSize);
            $chunks[] = trim($chunk);
            // Move the pointer forward by (size - overlap) to ensure continuity
            $start += ($chunkSize - $overlap);
        }

        // Filter out any potential empty strings and re-index the array
        return array_values(array_filter($chunks));
    }
}
