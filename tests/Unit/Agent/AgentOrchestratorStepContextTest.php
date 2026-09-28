<?php

namespace Tests\Unit\Agent;

use App\Agent\Context\StepContext;
use App\Agent\DTO\DecisionResult;
use App\Agent\Enums\AgentAction;
use App\Agent\Services\AgentOrchestrator;
use App\Agent\Services\DecisionService;
use App\Agent\Services\DirectAnswerHandler;
use App\Agent\Services\KnowledgeBaseHandler;
use App\Agent\Services\TaskDecomposeHandler;
use PHPUnit\Framework\TestCase;

class AgentOrchestratorStepContextTest extends TestCase
{
    public function test_it_appends_decision_and_final_answer_steps(): void
    {
        $decisionService = $this->createMock(DecisionService::class);
        $direct = $this->createMock(DirectAnswerHandler::class);
        $kb = $this->createMock(KnowledgeBaseHandler::class);
        $decompose = $this->createMock(TaskDecomposeHandler::class);

        $orchestrator = new AgentOrchestrator($decisionService, $direct, $kb, $decompose);

        $decision = new DecisionResult(
            action: AgentAction::DIRECT_ANSWER->value,
            parameters: ['text' => 'x'],
            reasoning: 'y'
        );

        $decisionService->method('decide')->willReturn($decision);
        $direct->method('handle')->willReturn('FINAL');

        $ctx = new StepContext();

        $result = $orchestrator->runWithStepContext('hello', $ctx);

        $this->assertSame('FINAL', $resul);

        $data = $ctx->toArray();
        $this->assertCount(2, $data['steps']);
        $this->assertSame('decision', $data['steps'][0]['name']);
        $this->assertSame('final_answer', $data['steps'][1]['name']);
    }
}
