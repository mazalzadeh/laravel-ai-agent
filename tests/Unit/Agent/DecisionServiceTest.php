<?php

namespace Tests\Unit\Agent;

use App\Agent\DTO\DecisionResult;
use App\Agent\Enums\AgentAction;
use App\Agent\Parsers\DecisionOutputParser;
use App\Agent\Prompts\DecisionPromptTemplate;
use App\Agent\Services\DecisionService;
use App\Services\AIClientInterface;
use PHPUnit\Framework\TestCase;

class DecisionServiceTest extends TestCase
{
   private AIClientInterface $aiClientMock;
    private DecisionPromptTemplate $promptTemplateMock;
    private DecisionOutputParser $outputParserMock;
    private DecisionService $decisionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aiClientMock = $this->createMock(AIClientInterface::class);
        $this->promptTemplateMock = $this->createMock(DecisionPromptTemplate::class);
        $this->outputParserMock = $this->createMock(DecisionOutputParser::class);

        $this->decisionService = new DecisionService(
            $this->aiClientMock,
            $this->promptTemplateMock,
            $this->outputParserMock
        );
    }


    public function test_decide_orchestrates_flow_returns_decision_result(): void
    {
        $userInput = 'Can you show me my recent expenses?';
        $context = 'User is logged in as John Doe';

        $systemPrompt = 'System prompt text';
        $userPrompt = 'User prompt text';
        $rawAiResponse = '{"action": "DIRECT_ANSWER", "parameters": {"text": "Your total expenses: $120"}, "reasoning": "Answer directly."}';

        $expectedResult = new DecisionResult(
            action: AgentAction::DIRECT_ANSWER->value,
            parameters: ['text' => 'Your total expenses: $120'],
            reasoning: 'Answer directly.'
        );

        // 1. Mocking prompt template calls
        $this->promptTemplateMock
            ->expects($this->once())
            ->method('renderSystemPrompt')
            ->willReturn($systemPrompt);

        $this->promptTemplateMock
            ->expects($this->once())
            ->method('renderUserPrompt')
            ->with($userInput, $context)
            ->willReturn($userPrompt);

        // Expected messages sent to chat()
        $expectedMessages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt],
        ];

        // 2. Mocking AI client call via chat()
        $this->aiClientMock
            ->expects($this->once())
            ->method('chat')
            ->with($expectedMessages)
            ->willReturn([
                'content' => $rawAiResponse,
            ]);

        // 3. Mocking parser call
        $this->outputParserMock
            ->expects($this->once())
            ->method('parse')
            ->with($rawAiResponse)
            ->willReturn($expectedResult);

        // Act
        $actualResult = $this->decisionService->decide($userInput, $context);

        // Assert
        $this->assertSame($expectedResult, $actualResult);
    }
}
