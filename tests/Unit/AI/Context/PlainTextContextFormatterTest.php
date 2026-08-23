<?php

declare(strict_types=1);

namespace Tests\Unit\AI\Context;

use App\AI\Context\PlainTextContextFormatter;
use App\AI\Context\RetrievedDocument;
use Illuminate\Support\Collection;
use Tests\TestCase;

final class PlainTextContextFormatterTest extends TestCase
{
    public function test_it_returns_an_empty_string_for_na_empty_collection(): void
    {
        $formatter = new PlainTextContextFormatter();

        $result = $formatter->format(new Collection());

        $this->assertSame('', $result);
    }


    public function test_it_formats_a_single_retrieved_document(): void
    {
        $formatter = new PlainTextContextFormatter();

        $document = new RetrievedDocument(
            documentId: 1,
            chunkId: 10,
            chunkIndex: 0,
            content: 'Laravel is a PHP framework.',
            score: 0.95,
            metadata: [],
        );

        $result = $formatter->format(new Collection([$document]));

        $this->assertSame('Laravel is a PHP framework.', $result);
    }


    public function test_it_joins_multiple_documents_with_a_blank_line(): void
    {
        $formatter = new PlainTextContextFormatter();

        $firstDocument = new RetrievedDocument(
            documentId: 1,
            chunkId: 10,
            chunkIndex: 0,
            content: 'First document content.',
            score: 0.95,
            metadata: [],
        );

        $secondDocument = new RetrievedDocument(
            documentId: 2,
            chunkId: 20,
            chunkIndex: 1,
            content: 'Second document content.',
            score: 0.85,
            metadata: [],
        );

        $result = $formatter->format(
            new Collection(
                [
                    $firstDocument,
                    $secondDocument,
                ]
            )
        );

        $this->assertSame("First document content.\n\nSecond document content.", $result);
    }


    public function test_it_preserves_the_order_of_documents(): void
    {
        $formatter = new PlainTextContextFormatter();

        $firstDocument = new RetrievedDocument(
            documentId: 1,
            chunkId: 10,
            chunkIndex: 0,
            content: 'Content A',
            score: 0.70,
            metadata: [],
        );

        $secondDocument = new RetrievedDocument(
            documentId: 2,
            chunkId: 20,
            chunkIndex: 1,
            content: 'Content B',
            score: 0.90,
            metadata: [],
        );

        $thirdDocument = new RetrievedDocument(
            documentId: 3,
            chunkId: 30,
            chunkIndex: 2,
            content: 'Content C',
            score: 0.80,
            metadata: [],
        );

        $result = $formatter->format(
            new Collection(
                [
                    $firstDocument,
                    $secondDocument,
                    $thirdDocument,
                ]
            )
        );

        $this->assertSame(
            "Content A\n\nContent B\n\nContent C",
            $result
        );
    }


    public function test_it_formats_only_documents_content(): void
    {
        $formatter = new PlainTextContextFormatter();

        $document = new RetrievedDocument(
            documentId: 123,
            chunkId: 456,
            chunkIndex: 7,
            content: 'Only this content should be formatted.',
            score: 0.99,
            metadata: [
                'title' => 'Internal title',
                'source' => 'internal-source',
            ],
        );

        $result = $formatter->format(new Collection([$document]));

        $this->assertSame(
            'Only this content should be formatted.',
            $result
        );

        $this->assertStringNotContainsString('123', $result);
        $this->assertStringNotContainsString('456', $result);
        $this->assertStringNotContainsString('0.99', $result);
        $this->assertStringNotContainsString('INternal title', $result);
        $this->assertStringNotContainsString('internal-source', $result);
    }
}
