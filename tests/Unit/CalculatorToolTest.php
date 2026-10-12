<?php

namespace Tests\Unit;

use App\Agent\Tools\CalculatorTool;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CalculatorToolTest extends TestCase
{
    protected CalculatorTool $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new CalculatorTool();
    }

    public function test_it_returns_correct_metadata(): void
    {
        $this->assertEquals('calculator', $this->calculator->getName());
        $this->assertNotEmpty($this->calculator->getDescription());

        $schema = $this->calculator->getParameters();
        $this->assertEquals('object', $schema['type']);
        $this->assertArrayHasKey('expression', $schema['properties']);
    }


    public function test_it_evaluates_basic_arithmetic_expressions(): void
    {
        $this->assertEquals('4', $this->calculator->execute(['expression' => '2+2']));
        $this->assertEquals('42', $this->calculator->execute(['expression' => '6*7']));
        $this->assertEquals('2.5', $this->calculator->execute(['expression' => '5/2']));
        $this->assertEquals('10', $this->calculator->execute(['expression' => '(2+3)*2']));
    }


    public function test_it_throws_exception_on_division_by_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Division by zero');

        $this->calculator->execute(['expression' => '10/0']);
    }


    public function test_it_throws_exception_on_invalid_expression(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator->execute(['expression' => 'system("ls")']);
    }
}
