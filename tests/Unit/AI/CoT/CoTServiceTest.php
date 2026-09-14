<?php

declare(strict_types=1);

namespace Tests\Unit\AI\CoT;

use App\AI\CoT\CoTOutputParser;
use App\AI\CoT\CoTService;
use App\AI\CoT\DTOs\CoTResult;
use App\AI\CoT\Prompts\CoTPromptTemplate;
use App\AI\Contracts\ClientInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class CoTServiceTest extends TestCase
{
    private $clientMock;

    private CoTPromptTemplate $template;

    private CoTOutputParser $parser;

    private CoTService $service;

    /**
     * Set up dependencies and test subject.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->clientMock = Mockery::mock(ClientInterface::class);
        $this->template = new CoTPromptTemplate();
        $this->parser = new CoTOutputParser();

        $this->service = new CoTService(
            $this->clientMock,
            $this->template,
            $this->parser
        );
    }

    /**
     * Clean up Mockery resources after each test.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }


    public function test_executes_question_and_returns_cot_result(): void
    {
        $question = 'What is 15% of 200?';
        $rawModelResponse = "<thought>\n10% of 200 is 20. 5% is 10. 20 + 10 = 30.\n</thought>\n<answer>\n30\n</answer>";

        $this->clientMock
            ->shouldReceive('generateText')
            ->once()
            ->with(Mockery::on(function ($prompt) use ($question) {
                return str_contains($prompt, $question) && str_contains($prompt, '<thought>');
            }))->andReturn($rawModelResponse);

        $result = $this->service->ask($question);

        $this->assertInstanceOf(CoTResult::class, $result);
        $this->assertTrue($result->hasReasoning());
        $this->assertStringContainsString('10% of 200 is 20', $result->getReasoning() ?? '');
        $this->assertEquals('30', $result->getAnswer());
    }


    public function test_executes_with_cintext_and_returns_cot_result(): void
    {
        $question = 'What is our refund window?';
        $context = 'Our policy states customers can refund products within 14 days.';
        $rawModelResponse = "<thought>\nRefund policy specifies 14 days.\n</thought>\n<answer>\n14 days.\n</answer>";

        $this->clientMock
            ->shouldReceive('generateText')
            ->once()
            ->with(Mockery::on(function ($prompt) use ($question, $context) {
                return str_contains($prompt, $question) && str_contains($prompt, $context);
            }))->andReturn($rawModelResponse);

        $result = $this->service->askWithContext($question, $context);

        $this->assertInstanceOf(CoTResult::class, $result);
        $this->assertEquals('14 days.', $result->getAnswer());
    }
}
