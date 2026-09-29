<?php

namespace App\Agent\Services;

use App\Agent\DTO\DecisionResult;
use App\Agent\Enums\AgentAction;
use App\Agent\Context\StepContext;
use InvalidArgumentException;


class AgentOrchestrator
{
    /**
     * Initialize orchestrator with required decision and routing handlers.
     *
     * @param DecisionService $decisionService Service responsible for choosing next action.
     * @param DirectAnswerHandler $directAnswerHandler Handler for immediate, direct replies.
     * @param KnowledgeBaseHandler $knowledgeBaseHandler Handler for RAG/retrieval workflows.
     * @param TaskDecomposeHandler $taskDecomposeHandler Handler for decomposing and executing complex subtasks.
     */
    public function __construct(
        private DecisionService $decisionService,
        private DirectAnswerHandler $directAnswerHandler,
        private KnowledgeBaseHandler $knowledgeBaseHandler,
        private TaskDecomposeHandler $taskDecomposeHandler,
    ) {}


    /**
     * Execute the orchestration pipeline for incoming user input.
     *
     * @param string $userInput The raw message or task from the user.
     * @param string|null $context Optional historical context or conversation metadata.
     * @return string The finalized response text.
     *
     * @throws InvalidArgumentException If the decision action is unsupported.
     */
    public function run(string $userInput, ?string $context = null): string
    {
        return $this->runWithStepContext($userInput, null, $context);
    }

    /**
     * Run the orchestration pipeline and record a step-by-step trace.
     *
     * @param string $userInput
     * @param StepContext $stepContext
     * @param string|null $context
     * @return string
     */
    public function runWithStepContext(
        string $userInput,
        ?StepContext $stepContext = null,
        ?string $context = null
    ): string {

        $stepContext ??= new StepContext();

        $stepHistory = $stepContext->toPromptContext();
        $decision = $this->decisionService->decide($userInput, $context, $stepHistory);

        $actionValue = is_string($decision->action) ? $decision->action : $decision->action->value;

        $stepContext->addStep(
            name: 'decision',
            input: $userInput,
            output: $actionValue,
            decision: $decision->toArray()
        );

        $response = $this->handleAction($decision, $userInput, $context);

        $stepContext->addStep(
            name: 'final_answer',
            input: $userInput,
            output: $response
        );

        return $response;
    }


    protected function handleAction(DecisionResult $decision, string $userInput, ?string $context = null): string
    {
        $actionValue = is_string($decision->action) ? $decision->action : $decision->action->value;

        return match ($actionValue) {
            AgentAction::DIRECT_ANSWER->value => $this->directAnswerHandler->handle($userInput, $context, $decision),
            AgentAction::SEARCH_KNOWLEDGE_BASE->value => $this->knowledgeBaseHandler->handle($userInput, $context, $decision),
            AgentAction::DECOMPOSE_TASK->value => $this->taskDecomposeHandler->handle($userInput, $context, $decision),
            default => throw new InvalidArgumentException("Unknown action: {$actionValue}"),
        };
    }
}
