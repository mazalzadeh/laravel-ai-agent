<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\AI\Context\ContextFormatter;
use App\AI\Context\ContextInjectionService;
use App\AI\Context\ContextRetriever;
use App\AI\Context\PlainTextContextFormatter;
use App\AI\Context\RetrievedDocument;
use App\AI\Prompts\ContextAwarePromptExecutor;
use App\AI\Prompts\PromptExecutionResult;
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
    private ContextAwarePromptExecutor $executor;
    private RagPrompt $ragPrompt;
    private RagService $ragService;



    protected function setUp(): void
    {
        parent::setUp();

        $this->aiService = Mockery::mock(AIService::class);
        $this->retriever = Mockery::mock(ContextRetriever::class);
        $this->formatter = new PlainTextContextFormatter();
        $this->contextService = new ContextInjectionService($this->retriever, $this->formatter);

        $this->executor = Mockery::mock(ContextAwarePromptExecutor::class);
        $this->ragPrompt = new RagPrompt();

        $this->ragService = new RagService(
            $this->aiService,
            $this->contextService,
            $this->executor,
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
        $aiResponse = 'Laravel is a PHP framework.';

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


        $this->executor
            ->shouldReceive('execute')
            ->once()
            ->with(
                $this->ragPrompt,
                $formattedContent,
                $question,
                [], // اضافه کردن این آرایه برای تطابق با پارامتر variables
                'No relevant context found.'
            )
            ->andReturn(new PromptExecutionResult(content: $aiResponse));
        $result = $this->ragService->answer($question, 5);

        $this->assertSame($question, $result['question']);
        $this->assertSame($aiResponse, $result['answer']);
        $this->assertCount(1, $result['sources']);
        $this->assertSame(1, $result['sources'][0]['document_id']);
    }


    public function test_answer_handles_empty_context_fallback(): void
    {
        $question = 'What is Quantum Computing?';
        $aiResponse = 'I could not find information.';


        $this->retriever
            ->shouldReceive('retrieve')
            ->once()
            ->with($question, 3)
            ->andReturn(collect([]));


        $this->executor
            ->shouldReceive('execute')
            ->once()
            ->with(
                $this->ragPrompt,
                '',
                $question,
                [], // اضافه کردن این آرایه برای تطابق با پارامتر variables
                'No relevant context found.'
            )
            ->andReturn(new PromptExecutionResult(content: $aiResponse));
        $result = $this->ragService->answer($question, 3);

        $this->assertSame($question, $result['question']);
        $this->assertSame($aiResponse, $result['answer']);
        $this->assertEmpty($result['sources']);
    }
}
