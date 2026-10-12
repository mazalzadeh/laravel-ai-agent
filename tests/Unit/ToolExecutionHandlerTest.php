<?php

namespace Tests\Unit;

use App\Agent\Contracts\ToolInterface;
use App\Agent\Services\ToolExecutionHandler;
use App\Agent\Services\ToolRegistry;
use App\DTO\ChatResponseDTO;
use App\DTO\ToolCallDTO;
use App\Services\AIClientInterface;
use PHPUnit\Framework\TestCase;

class ToolExecutionHandlerTest extends TestCase
{
    public function test_it_executes_tool_and_returns_final_model_response(): void
    {
        // 1. Tool simulation
        $tool = $this->createMock(ToolInterface::class);
        $tool->method('getName')->willReturn('calculator');
        $tool->method('execute')
            ->with(['expression' => '5*5'])
            ->willReturn('25');

        $registry = new ToolRegistry();
        $registry->register($tool);

        // 2. AI Client Simulation:
        // Round 1: The model decides to call the tool.
        // Round 2: After receiving the tool's response, the model generates the final text response.
        $aiClient = $this->createMock(AIClientInterface::class);

        $initalResponse = new ChatResponseDTO(
            id: 'call_1',
            content: null,
            role: 'assistant',
            toolCalls: [
                new ToolCallDTO(id: 'call_abc_123', name: 'calculator', arguments: ['expression' => '5*5']),
            ]
        );

        $finalResponse = new ChatResponseDTO(
            id: 'call_2',
            content: 'The answer is 25.',
            role: 'assistant',
            toolCalls: []
        );

        $aiClient->expects($this->exactly(2))
            ->method('chat')
            ->willReturnOnConsecutiveCalls(
                ['success' => true, 'data' => $initalResponse],
                ['success' => true, 'data' => $finalResponse]
            );

        $handler = new ToolExecutionHandler($aiClient, $registry);

        $result = $handler->handle('Calculate 5*5');

        $this->assertEquals('The answer is 25.', $result);
    }
}
