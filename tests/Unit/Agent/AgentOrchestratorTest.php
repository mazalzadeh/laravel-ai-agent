<?php

namespace Tests\Unit\Agent;

use App\Agent\DTO\DecisionResult;
use App\Agent\Enums\AgentAction;
use App\Agent\Services\AgentOrchestrator;
use App\Agent\Services\DecisionService;
use App\Agent\Services\DirectAnswerHandler;
use App\Agent\Services\KnowledgeBaseHandler;
use App\Agent\Services\TaskDecomposeHandler;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use App\Agent\Services\ToolExecutionHandler;

class AgentOrchestratorTest extends TestCase
{
    public function test_routes_direct_answer_action_to_direct_answer_handler(): void
    {
        $decisionService = $this->createMock(DecisionService::class);
        $directAnswerHandler = $this->createMock(DirectAnswerHandler::class);
        $knowledgeBaseHandler = $this->createMock(KnowledgeBaseHandler::class);
        $taskDecomposeHandler = $this->createMock(TaskDecomposeHandler::class);
        $toolExecutionHandler = $this->createMock(ToolExecutionHandler::class);

        $orchestrator = new AgentOrchestrator(
            $decisionService,
            $directAnswerHandler,
            $knowledgeBaseHandler,
            $taskDecomposeHandler,
            $toolExecutionHandler
        );

        $userInput = 'What is Laravel?';
        $context = null;

        $decision = new DecisionResult(
            action: AgentAction::DIRECT_ANSWER->value,
            parameters: ['text' => 'Laravel is a PHP web framework.'],
            reasoning: 'Can answer directly'
        );

        $decisionService->expects($this->once())
            ->method('decide')
            ->with($userInput, $context)
            ->willReturn($decision);

        $directAnswerHandler->expects($this->once())
            ->method('handle')
            ->with($userInput, $context, $decision)
            ->willReturn('Laravel is a PHP web framework.');

        $knowledgeBaseHandler->expects($this->never())->method('handle');
        $taskDecomposeHandler->expects($this->never())->method('handle');

        $result = $orchestrator->run($userInput, $context);

        $this->assertSame('Laravel is a PHP web framework.', $result);
    }


    public function test_routes_search_knowledge_base_action_to_kb_handler(): void
    {
        $decisionService = $this->createMock(DecisionService::class);
        $directAnswerHandler = $this->createMock(DirectAnswerHandler::class);
        $knowledgeBaseHandler = $this->createMock(KnowledgeBaseHandler::class);
        $taskDecomposeHandler = $this->createMock(TaskDecomposeHandler::class);
        $toolExecutionHandler = $this->createMock(ToolExecutionHandler::class);

        $orchestrator = new AgentOrchestrator(
            $decisionService,
            $directAnswerHandler,
            $knowledgeBaseHandler,
            $taskDecomposeHandler,
            $toolExecutionHandler
        );

        $userInput = 'What is our return policy?';
        $context = 'shop-policy';

        $decision = new DecisionResult(
            action: AgentAction::SEARCH_KNOWLEDGE_BASE->value,
            parameters: ['query' => 'return policy'],
            reasoning: 'Requires private KB lookup'
        );

        $decisionService->expects($this->once())
            ->method('decide')
            ->with($userInput, $context)
            ->willReturn($decision);

        $knowledgeBaseHandler->expects($this->once())
            ->method('handle')
            ->with($userInput, $context, $decision)
            ->willReturn('Items can be returned within 14 days.');

        $directAnswerHandler->expects($this->never())->method('handle');
        $taskDecomposeHandler->expects($this->never())->method('handle');

        $result = $orchestrator->run($userInput, $context);

        $this->assertSame('Items can be returned within 14 days.', $result);
    }


    public function test_routes_decompose_task_action_to_decompose_handler(): void
    {
        $decisionService = $this->createMock(DecisionService::class);
        $directAnswerHandler = $this->createMock(DirectAnswerHandler::class);
        $knowledgeBaseHandler = $this->createMock(KnowledgeBaseHandler::class);
        $taskDecomposeHandler = $this->createMock(TaskDecomposeHandler::class);
        $toolExecutionHandler = $this->createMock(ToolExecutionHandler::class);

        $orchestrator = new AgentOrchestrator(
            $decisionService,
            $directAnswerHandler,
            $knowledgeBaseHandler,
            $taskDecomposeHandler,
            $toolExecutionHandler
        );

        $userInput = 'Plan a full migration strategy to Germany.';
        $context = null;

        $decision = new DecisionResult(
            action: AgentAction::DECOMPOSE_TASK->value,
            parameters: ['goal' => 'migration plan'],
            reasoning: 'Multi-step complex task'
        );

        $decisionService->expects($this->once())
            ->method('decide')
            ->with($userInput, $context)
            ->willReturn($decision);

        $taskDecomposeHandler->expects($this->once())
            ->method('handle')
            ->with($userInput, $context, $decision)
            ->willReturn('Step 1: Language, Step 2: Visa, Step 3: Job search');

        $directAnswerHandler->expects($this->never())->method('handle');
        $knowledgeBaseHandler->expects($this->never())->method('handle');

        $result = $orchestrator->run($userInput, $context);

        $this->assertSame('Step 1: Language, Step 2: Visa, Step 3: Job search', $result);
    }


    public function test_throws_exception_on_unknown_action(): void
    {
        $decisionService = $this->createMock(DecisionService::class);
        $directAnswerHandler = $this->createMock(DirectAnswerHandler::class);
        $knowledgeBaseHandler = $this->createMock(KnowledgeBaseHandler::class);
        $taskDecomposeHandler = $this->createMock(TaskDecomposeHandler::class);
        $toolExecutionHandler = $this->createMock(ToolExecutionHandler::class);

        $orchestrator = new AgentOrchestrator(
            $decisionService,
            $directAnswerHandler,
            $knowledgeBaseHandler,
            $taskDecomposeHandler,
            $toolExecutionHandler
        );

        $decision = new DecisionResult(
            action: 'INVALID_ACTION',
            parameters: [],
            reasoning: 'Invalid test case'
        );

        $decisionService->expects($this->once())
            ->method('decide')
            ->willReturn($decision);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown action: INVALID_ACTION');

        $orchestrator->run('test input', null);
    }


    public function test_ot_delegates_to_tool_execution_handler_when_action_is_use_tool(): void
    {
        $userInput = 'Calculate 10 + 20';

        // 1. Simulation of a decision-maker indicating that the tool should be used
        $decisionService = $this->createMock(\App\Agent\Services\DecisionService::class);
        $decision = new \App\Agent\DTO\DecisionResult(
            action: \App\Agent\Enums\AgentAction::USE_TOOL->value,
            parameters: [],
            reasoning: 'Need to compute mathematical operation'
        );
        $decisionService->method('decide')->willReturn($decision);

        // 2. Other dependent handlers
        $directAnswerHandler = $this->createMock(\App\Agent\Services\DirectAnswerHandler::class);
        $knowledgeBaseHandler = $this->createMock(\App\Agent\Services\KnowledgeBaseHandler::class);
        $taskDecomposeHandler = $this->createMock(\App\Agent\Services\TaskDecomposeHandler::class);

        // 3. Simulating the tools handler
        $toolExecutionHandler = $this->createMock(\App\Agent\Services\ToolExecutionHandler::class);
        $toolExecutionHandler->expects($this->once())
            ->method('handle')
            ->with($userInput, null)
            ->willReturn('The answer is 30.');

        // 4. Create the orchestrator with the new handler
        $orchestrator = new \App\Agent\Services\AgentOrchestrator(
            $decisionService,
            $directAnswerHandler,
            $knowledgeBaseHandler,
            $taskDecomposeHandler,
            $toolExecutionHandler
        );

        $response = $orchestrator->run($userInput);

        $this->assertEquals('The answer is 30.', $response);
    }
}
