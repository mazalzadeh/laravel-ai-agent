<?php

namespace Tests\Unit;

use App\Agent\Contracts\ToolInterface;
use App\Agent\Services\ToolRegistry;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ToolRegistryTest extends TestCase
{
    protected ToolRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new ToolRegistry();
    }

    protected function createMockTool(
        string $name,
        string $description = 'A test tool'
    ): ToolInterface {
        $mock = $this->createMock(ToolInterface::class);
        $mock->method('getName')->willReturn($name);
        $mock->method('getDescription')->willReturn($description);
        $mock->method('getParameters')->willReturn([
            'type' => 'object',
            'properties' => [],
        ]);
        $mock->method('toOpenAiDefinition')->willReturn([
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => $description,
                'parameters' => [
                    'type' => 'object',
                    'properties' => [],
                ],
            ],
        ]);

        return $mock;
    }


    public function test_it_can_register_and_retrieve_a_tool(): void
    {
        $tool = $this->createMockTool('calculator');

        $this->registry->register($tool);

        $this->assertTrue($this->registry->has('calculator'));
        $this->assertSame($tool, $this->registry->get('calculator'));
    }


    public function test_it_throws_exception_when_retrieving_unregistered_tool(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Tool 'unknown_tool' is not registered.");

        $this->registry->get('unknown_tool');
    }


    public function test_it_returns_all_tools_formatted_for_openai(): void
    {
        $tool1 = $this->createMockTool('calculator');
        $tool2 = $this->createMockTool('weather');

        $this->registry->register($tool1);
        $this->registry->register($tool2);

        $toolsPayload = $this->registry->toOpenAiTools();

        $this->assertCount(2, $toolsPayload);
        $this->assertEquals('calculator', $toolsPayload[0]['function']['name']);
        $this->assertEquals('weather', $toolsPayload[1]['function']['name']);
    }
}
