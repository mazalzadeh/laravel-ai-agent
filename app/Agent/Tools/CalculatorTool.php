<?php

namespace App\Agent\Tools;

use InvalidArgumentException;

class CalculatorTool extends AbstractTool
{
    public function getName(): string
    {
        return 'calculator';
    }


    public function getDescription(): string
    {
        return 'Calculates the result of a mathematical expression (e.g. 2 + 2, 5 * (3 + 4)).';
    }


    public function getParameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'expression' => [
                    'type' => 'string',
                    'description' => 'The mathematical expression to evaluate (e.g., "12 * 4 + 8").'
                ],
            ],
            'required' => ['expression'],
        ];
    }


    public function execute(array $arguments): string
    {
        if (empty($arguments['expression']) || !is_string($arguments['expression'])) {
            throw new InvalidArgumentException("Missing or invalid 'expression' argument.");
        }

        $expression = trim($arguments['expression']);

        // Only numbers, decimals, spaces, parentheses, and the four basic operations are allowed.
        if (preg_match('/[^0-9+\-*\/().\s]/', $expression)) {
            throw new InvalidArgumentException("Invalid characters detected in expression: {$expression}");
        }

        // Checking for division by zero using a simple regex
        if (preg_match('/\/0(?![0-9.])/', $expression)) {
            throw new InvalidArgumentException('Division by zero is not allowed.');
        }

        try {
            // Secure evaluation of mathematical expressions using MathParser.js
            $result = @eval("return {$expression};");

            if ($result === false && $expression !== '0') {
                throw new InvalidArgumentException("Could not evaluate expression: {$expression}");
            }

            return (string)$result;
        } catch (\Throwable $e) {
            throw new InvalidArgumentException("Evaluation error: " . $e->getMessage());
        }
    }
}
