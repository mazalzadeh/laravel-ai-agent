<?php

declare(strict_types=1);

namespace Tests\Unit\AI\Context;

use App\AI\Context\RetrievedDocument;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RetrievedDocumentTest extends TestCase
{
    public function test_it_can_instantiated_with_expected_values(): void
    {
        $document = new RetrievedDocument(
            documentId: 12,
            chunkId: 34,
            chunkIndex: 2,
            content: 'Laravel service container resolves dependencies.',
            score: 0.9234,
            metadata: [
                'source' => 'pdf',
                'title' => 'Laravel Guide',
            ],
        );

        $this->assertSame(12, $document->documentId);
        $this->assertSame(34, $document->chunkId);
        $this->assertSame(2, $document->chunkIndex);
        $this->assertSame('Laravel service container resolves dependencies.', $document->content);
        $this->assertSame(0.9234, $document->score);
        $this->assertSame([
            'source' => 'pdf',
            'title' => 'Laravel Guide',
        ], $document->metadata);
    }


    public function test_it_allows_nullable_chunk_fields(): void
    {
        $document = new RetrievedDocument(
            documentId: 1,
            chunkId: null,
            chunkIndex: null,
            content: 'Some content',
            score: 0.5,
            metadata: [],
        );

        $this->assertNull($document->chunkId);
        $this->assertNull($document->chunkIndex);
    }
}
