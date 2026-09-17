<?php

namespace Tests\Unit\Agent;

use App\Agent\DTO\DecisionResult;
use PHPUnit\Framework\TestCase;


class DecisionResultTest extends TestCase
{
    public function test_can_instantiate_decision_result_with_valid_data(): void
    {
        $action = 'SEARCH_DATABASE';
        $parameters = ['query' => 'migration to germany', 'limit' => 5];
        $reasoning = 'The user is asking about specific migration paths.';

        $decision = new DecisionResult($action, $parameters, $reasoning);

        $this->assertEquals($action, $decision->action);
        $this->assertEquals($parameters, $decision->parameters);
        $this->assertEquals($reasoning, $decision->reasoning);
    }


    public function test_can_create_from_array(): void
    {
        $data = [
            'action' => 'SEARCH_DATABASE',
            'parameters' => ['query' => 'migration'],
            'reasoning' => 'User asked about migration',
        ];

        $decision = DecisionResult::fromArray($data);

        $this->assertEquals('SEARCH_DATABASE', $decision->action);
        $this->assertEquals(['query' => 'migration'], $decision->parameters);
        $this->assertEquals('User asked about migration', $decision->reasoning);
    }


    public function test_can_transform_to_array(): void
    {
        $decision = new DecisionResult('DIRECT_ANSWER', ['message' => 'Hello'], 'Simple greeting');

        $expected = [
            'action' => 'DIRECT_ANSWER',
            'parameters' => ['message' => 'Hello'],
            'reasoning' => 'Simple greeting',
        ];

        $this->assertEquals($expected, $decision->toArray());
    }


    public function test_is_action_check(): void
    {
        $decision = new DecisionResult('SEARCH_DATABASE');

        $this->assertTrue($decision->isAction('SEARCH_DATABASE'));
        $this->assertFalse($decision->isAction('DIRECT_ANSWER'));
    }
}
