<?php

declare(strict_types=1);

namespace Tests\Unit\AI\Context;

use App\AI\Context\ContextFormatter;
use App\AI\Context\ContextInjectionService;
use App\AI\Context\ContextRetriever;
use App\AI\Context\RetrievedContext;
use App\AI\Context\RetrievedDocument;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

final class ContextInjectionServiceTest extends TestCase
{
    private ContextRetriever&MockInterface $retriever;
    private ContextFormatter&MockInterface $formatter;
    private ContextInjectionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->retriever = Mockery::mock(ContextRetriever::class);
        $this->formatter = Mockery::mock(ContextFormatter::class);

        $this->service = new ContextInjectionService(
            retriever: $this->retriever,
            formatter: $this->formatter,
        );
    }

    public function test_it_retrieves_and_formats_context_successfully(): void
    {
        $query = 'How does Laravel routing work?';
        $limit = 3;

        $doc1 = new RetrievedDocument(
            documentId: 1,
            chunkId: 10,
            chunkIndex: 0,
            content: 'Route::get() handles GET requests.',
            score: 0.95,
            metadata: []
        );

        $documents = new Collection([$doc1]);
        $formattedText = 'Route::get() handles GET requests.';

        $this->retriever
            ->shouldReceive('retrieve')
            ->once()
            ->with($query, $limit)
            ->andReturn($documents);

        $this->formatter
            ->shouldReceive('format')
            ->once()
            ->with($documents)
            ->andReturn($formattedText);

        $result = $this->service->inject($query, $limit);

        $this->assertInstanceOf(RetrievedContext::class, $result);
        $this->assertSame($formattedText, $result->content);
        $this->assertSame($documents, $result->documents);
    }

    public function test_it_uses_default_limit_when_not_provided(): void
    {
        $query = 'Default limit query';
        $defaultLimit = 5;

        $documents = new Collection();

        $this->retriever
            ->shouldReceive('retrieve')
            ->once()
            ->with($query, $defaultLimit)
            ->andReturn($documents);

        $this->formatter
            ->shouldReceive('format')
            ->once()
            ->with($documents)
            ->andReturn('');

        $result = $this->service->inject($query);

        $this->assertInstanceOf(RetrievedContext::class, $result);
        $this->assertSame('', $result->content);
        $this->assertTrue($result->documents->isEmpty());
    }

    public function test_it_handles_empty_retrieval_results(): void
    {
        $query = 'Non-existent topic';
        $emptyDocuments = new Collection();

        $this->retriever
            ->shouldReceive('retrieve')
            ->once()
            ->with($query, 5)
            ->andReturn($emptyDocuments);

        $this->formatter
            ->shouldReceive('format')
            ->once()
            ->with($emptyDocuments)
            ->andReturn('');

        $result = $this->service->inject($query);

        $this->assertSame('', $result->content);
        $this->assertCount(0, $result->documents);
    }
}
