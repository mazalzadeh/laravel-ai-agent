<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\AI\Context\ContextFormatter;
use App\AI\Context\ContextInjectionService;
use App\AI\Context\ContextRetriever;
use App\AI\Context\PlainTextContextFormatter;
use App\AI\Context\RetrievedDocument;
use App\AI\Prompts\PromptRenderer;
use App\AI\Prompts\Templates\RagPrompt;
use App\Services\AIService;
use App\Services\RagService;
use Mockery;
use PHPUnit\Framework\TestCase;

final class RagServiceTest extends TestCase
{
    private AIService $aiService;
    private ContextRetriever $retriever;
    private ContextFormatter $formatter;
    private ContextInjectionService $contextService;
    private PromptRenderer $promptRenderer;
    private RagPrompt $ragPrompt;
    private RagService $ragService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aiService = Mockery::mock(AIService::class);
        $this->retriever = Mockery::mock(ContextRetriever::class);
        $this->formatter = new PlainTextContextFormatter();
        $this->contextService = new ContextInjectionService($this->retriever, $this->formatter);
        $this->promptRenderer = new PromptRenderer();
        $this->ragPrompt = new RagPrompt();

        $this->ragService = new RagService(
            $this->aiService,
            $this->contextService,
            $this->promptRenderer,
            $this->ragPrompt
        );
    }


    protected function rearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }


    public function test_answer_uses_rendered_rag_prompt_with_context(): void
    {
        $question = 'What is Laravel?';

        $retrievedDoc = new RetrievedDocument(
            documentId: 1,
            chunkId: 10,
            chunkIndex: 0,
            content: 'Laravel is a web framework.',
            score: 0.95
        );

        $this->retriever
            ->shouldReceive('retrieve')
            ->once()
            ->with($question, 5)
            ->andReturn(collect([$retrievedDoc]));

        $formattedContent = $this->formatter->format(collect([$retrievedDoc]));

        $expectedMessages = $this->promptRenderer->render($this->ragPrompt, [
            'context' => $formattedContent,
            'question' => $question,
        ]);

        $this->aiService
            ->shouldReceive('chat')
            ->once()
            ->with($expectedMessages)
            ->andReturn('Laravel is a PHP framework.');

        $result = $this->ragService->answer($question, 5);

        $this->assertSame($question, $result['question']);
        $this->assertSame('Laravel is a PHP framework.', $result['answer']);
        $this->assertCount(1, $result['sources']);
        $this->assertSame(1, $result['sources'][0]['document_id']);
        $this->assertSame(10, $result['sources'][0]['chunk_id']);
        $this->assertSame('Laravel is a web framework.', $result['sources'][0]['content']);
        $this->assertSame(0.95, $result['sources'][0]['score']);
    }


    public function test_answer_handles_empty_context_fallback(): void
    {
        $question = 'What is Quantum Computing?';

        $this->retriever
            ->shouldReceive('retrieve')
            ->once()
            ->with($question, 3)
            ->andReturn(collect([]));

        $expectedMessages = $this->promptRenderer->render($this->ragPrompt, [
            'context' => 'No relevant context found.',
            'question' => $question,
        ]);

        $this->aiService
            ->shouldReceive('chat')
            ->once()
            ->with($expectedMessages)
            ->andReturn('I could not find information about quantum physics.');

        $result = $this->ragService->answer($question, 3);

        $this->assertSame($question, $result['question']);
        $this->assertEmpty($result['sources']);
    }
}
