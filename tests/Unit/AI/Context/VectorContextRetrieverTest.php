<?php

namespace Tests\Unit\AI\Context;

use App\AI\Context\RetrievedDocument;
use Mockery;
use Closure;
use Mockery\Mock;
use Tests\TestCase;
use RuntimeException;
use App\Services\AIService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use App\Services\EmbeddingCacheService;
use App\Services\VectorSimilarityService;
use App\AI\Context\VectorContextRetriever;
use App\Models\DocumentChunk;
use App\Models\Document;

final class VectorContextRetrieverTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }


    private function makeDependencies(
        string $query,
        array $embedding,
        array $rawResult,
    ): array {
        $aiService = Mockery::mock(AIService::class);
        $aiService
            ->shouldReceive('embed')
            ->once()
            ->with($query)
            ->andReturn($embedding);

        $embeddingCache = Mockery::mock(EmbeddingCacheService::class);
        $embeddingCache
            ->shouldReceive('remember')
            ->once()
            ->with($query, Mockery::type('callable'))
            ->andReturnUsing(
                static function (string $key, callable $callback): array {
                    return $callback();
                }
            );

        $similarityservice = Mockery::mock(VectorSimilarityService::class);
        $similarityservice
            ->shouldReceive('findMostSimilar')
            ->once()
            ->with($embedding, 5)
            ->andReturn([$rawResult]);

        return [$aiService, $embeddingCache, $similarityservice];
    }


    private function makeDocument(): Document
    {
        $document=new Document();

        $document->setAttribute('id',10);
        $document->setAttribute('title','Laravel Guide');
        $document->setAttribute('source','docs/laravel.md');

        return $document;
    }


    private function makeChunk(): DocumentChunk
    {
        $chunk = new DocumentChunk();

        $chunk->setAttribute('id', 25);
        $chunk->setAttribute('chunk_index', 2);
        $chunk->setAttribute(
            'content',
            'Laravel is a PHP framework for building web applications.',
        );

        return $chunk;
    }


    private function makeSimilarityResult(
        Document $document,
        DocumentChunk $chunk,
        float $score,
    ): array {
        return [
            'document' => $document,
            'chunk' => $chunk,
            'score' => $score,
        ];
    }


    private function assertRetrievedDocumentMatchesSourceData(RetrievedDocument $retrievedDocument): void
    {
        $this->assertSame(10, $retrievedDocument->documentId);
        $this->assertSame(25, $retrievedDocument->chunkId);
        $this->assertSame(2, $retrievedDocument->chunkIndex);
        $this->assertSame(
            'Laravel is a PHP framework for building web applications.',
            $retrievedDocument->content,
        );
        $this->assertEquals(0.91, $retrievedDocument->score);

        $this->assertIsArray($retrievedDocument->metadata);
        $this->assertArrayHasKey('document_title', $retrievedDocument->metadata);
        $this->assertArrayHasKey('document_source', $retrievedDocument->metadata);
        $this->assertSame('Laravel Guide', $retrievedDocument->metadata['document_title']);
        $this->assertSame('docs/laravel.md', $retrievedDocument->metadata['document_source']);
        $this->assertCount(2, $retrievedDocument->metadata);
    }


    public function test_it_does_not_swallow_dependency_exceptions(): void
    {
        $aiService = Mockery::mock(AIService::class);
        $embeddingCacheService = Mockery::mock(EmbeddingCacheService::class);
        $vectorSimilarityService = Mockery::mock(VectorSimilarityService::class);

        $embeddingCacheService
            ->shouldReceive('remember')
            ->with('query', Mockery::type('Closure'))
            ->andThrow(new RuntimeException('embedding failed'));

        $retriever = new VectorContextRetriever(
            $aiService,
            $embeddingCacheService,
            $vectorSimilarityService
        );


        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('embedding failed');

        $retriever->retrieve('query');
    }


    public function test_it_uses_the_default_limit_when_no_limit_is_passed(): void
    {
        $embeddingCache = Mockery::mock(EmbeddingCacheService::class);
        $aiService = Mockery::mock(AIService::class);
        $vectorSimilarity = Mockery::mock(VectorSimilarityService::class);

        $query = 'Laravel context retrieval';

        $embeddingCache
            ->shouldReceive('remember')
            ->once()
            ->with($query, Mockery::type(Closure::class))
            ->andReturnUsing(function (string $query, Closure $callback): array {
                return $callback();
            });

        $aiService
            ->shouldReceive('embed')
            ->once()
            ->with($query)
            ->andReturn([0.1, 0.2, 0.3]);

        $vectorSimilarity
            ->shouldReceive('findMostSimilar')
            ->once()
            ->with([0.1, 0.2, 0.3], 5)
            ->andReturn(collect());

        $retriever = new VectorContextRetriever(
            $aiService,
            $embeddingCache,
            $vectorSimilarity,
        );

        $result = $retriever->retrieve($query);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }


    public function test_it_maps_a_similarity_result_into_a_tetrieved_document(): void
    {
        $query = 'How do I build web application with Laravel?';
        $embedding = [0.12, 0.34, 0.56];
        $document = $this->makeDocument();
        $chunk = $this->makeChunk();
        $rawResult = $this->makeSimilarityResult($document, $chunk, 0.91);

        [$aiService, $embeddingCache, $similarityservice] = $this->makeDependencies(
            $query,
            $embedding,
            $rawResult,
        );

        $retriever = new VectorContextRetriever(
            $aiService,
            $embeddingCache,
            $similarityservice,
        );

        $result = $retriever->retrieve($query);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);

        $retrievedDocument = $result->first();

        $this->assertInstanceOf(RetrievedDocument::class, $retrievedDocument);
        $this->assertRetrievedDocumentMatchesSourceData($retrievedDocument);
    }


    public function test_it_returns_an_empty_collection_when_similarity_returns_no_results(): void
    {
        $aiService = Mockery::mock(AIService::class);
        $embeddingCache = Mockery::mock(EmbeddingCacheService::class);
        $vectorSimilarity = Mockery::mock(VectorSimilarityService::class);

        $query = 'laravel context';
        $embedding = [0.1, 0.2, 0.3];

        $aiService
            ->shouldReceive('embed')
            ->once()
            ->with($query)
            ->andReturn($embedding);

        $embeddingCache
            ->shouldReceive('remember')
            ->once()
            ->with($query, Mockery::type(\Closure::class))
            ->andReturnUsing(
                function (string $query, \Closure $callback): array {
                    return $callback();
                },
            );

        $vectorSimilarity
            ->shouldReceive('findMostSimilar')
            ->once()
            ->with($embedding, 5)
            ->andReturn([]);

        $retriever = new VectorContextRetriever(
            $aiService,
            $embeddingCache,
            $vectorSimilarity,
        );

        $result = $retriever->retrieve($query);

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
        $this->assertSame([], $result->all());
    }


    public function test_it_preserves_the_order_of_similarity_results(): void
    {
        $firstDocument = new Document();
        $firstDocument->id = 10;

        $firstChunk = new DocumentChunk();
        $secondDocument = new Document();
        $secondDocument->id = 11;

        $secondChunk = new DocumentChunk();

        $firstResult = [
            'document' => $firstDocument,
            'chunk' => $firstChunk,
            'score' => 0.91,
        ];

        $secondResult = [
            'document' => $secondDocument,
            'chunk' => $secondChunk,
            'score' => 0.78,
        ];

        $aiService = Mockery::mock(AIService::class);
        $embeddingCacheService = Mockery::mock(EmbeddingCacheService::class);
        $vectorSimilarityService = Mockery::mock(VectorSimilarityService::class);

        $embeddingCacheService
            ->shouldReceive('remember')
            ->once()
            ->andReturnUsing(static function (string $key, callable $callback): mixed {
                return $callback();
            });

        $aiService
            ->shouldReceive('embed')
            ->once()
            ->with('ordered query')
            ->andReturn([0.1, 0.2, 0.3]);

        $vectorSimilarityService
            ->shouldReceive('findMostSimilar')
            ->once()
            ->with([0.1, 0.2, 0.3], 5)
            ->andReturn(new Collection([$firstResult, $secondResult]));

        $retriever = new VectorContextRetriever(
            $aiService,
            $embeddingCacheService,
            $vectorSimilarityService,
        );

        $results = $retriever->retrieve('ordered query');

        $this->assertInstanceOf(Collection::class, $results);
        $this->assertCount(2, $results);

        $first = $results->first();
        $second = $results->last();

        $this->assertInstanceOf(RetrievedDocument::class, $first);
        $this->assertInstanceOf(RetrievedDocument::class, $second);
        $this->assertSame(10, $first->documentId);
        $this->assertSame(0.91, $first->score);
        $this->assertSame(11, $second->documentId);
        $this->assertSame(0.78, $second->score);
    }
}
