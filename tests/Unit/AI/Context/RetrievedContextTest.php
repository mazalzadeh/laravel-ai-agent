<?php

declare(strict_types=1);

namespace Tests\Unit\AI\Context;

use App\AI\Context\RetrievedDocument;
use App\AI\Context\RetrievedContext;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RetrievedContextTest extends TestCase
{
    public function test_it_contains_formatted_content_and_documents(): void
    {
        $documents = collect([
            new RetrievedDocument(
                documentId: 10,
                chunkId: 20,
                chunkIndex: 0,
                content: 'First chunk content',
                score: 0.91,
                metadata: ['title' => 'Doc 1'],
            ),
            new RetrievedDocument(
                documentId: 11,
                chunkId: 21,
                chunkIndex: 1,
                content: 'Second chunk content',
                score: 0.87,
                metadata: ['title' => 'Doc 2'],
            ),
        ]);

        $context = new RetrievedContext(
            content: "Formatted context text",
            documents: $documents,
        );

        $this->assertSame('Formatted context text', $context->content);
        $this->assertInstanceOf(Collection::class, $context->documents);
        $this->assertCount(2, $context->documents);
        $this->assertSame(10, $context->documents->first()->documentId);
    }


    public function test_it_reports_when_empty(): void
    {
        $context = new RetrievedContext(
            content: '',
            documents: collect(),
        );

        $this->assertTrue($context->isEmpty());
    }


    public function test_it_reports_when_not_empty(): void
    {
        $context = new RetrievedContext(
            content: 'Some content',
            documents: collect([
                new RetrievedDocument(
                    documentId: 1,
                    chunkId: 2,
                    chunkIndex: 0,
                    content: 'chunk content',
                    score: 0.8,
                    metadata: [],
                ),
            ]),
        );


        $this->assertFalse($context->isEmpty());
    }


}
